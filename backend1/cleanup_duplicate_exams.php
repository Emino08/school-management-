<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Cleaning up duplicate exams...\n\n";

    // Find exams with NULL exam_number
    $stmt = $pdo->query("
        SELECT id, exam_name, term_id, exam_type
        FROM exams
        WHERE exam_number IS NULL
        ORDER BY id
    ");
    $oldExams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($oldExams)) {
        echo "No duplicate exams found.\n";
        exit(0);
    }

    echo "Found " . count($oldExams) . " old exams to remove:\n";
    foreach ($oldExams as $exam) {
        echo "  - ID {$exam['id']}: {$exam['exam_name']}\n";
    }

    echo "\nDeleting old exams...\n";
    $stmt = $pdo->prepare("DELETE FROM exams WHERE exam_number IS NULL");
    $stmt->execute();
    $deleted = $stmt->rowCount();

    echo "✓ Deleted {$deleted} duplicate exams\n\n";

    // Show remaining exams
    echo "=== REMAINING EXAMS ===\n";
    $stmt = $pdo->query("
        SELECT e.id, e.exam_name, e.exam_type, e.exam_number, e.exam_date,
               ay.year_name, t.term_name
        FROM exams e
        LEFT JOIN academic_years ay ON e.academic_year_id = ay.id
        LEFT JOIN terms t ON e.term_id = t.id
        ORDER BY e.academic_year_id, e.term_id, e.exam_number
    ");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentYear = null;
    foreach ($exams as $exam) {
        if ($currentYear != $exam['year_name']) {
            $currentYear = $exam['year_name'];
            echo "\n{$currentYear}:\n";
        }
        printf("  ✓ %s (Type: %s, Num: %d, Date: %s)\n",
            $exam['exam_name'],
            $exam['exam_type'],
            $exam['exam_number'],
            $exam['exam_date']
        );
    }

    echo "\n=== SUMMARY ===\n";
    $stmt = $pdo->query("
        SELECT ay.year_name, COUNT(e.id) as count,
               ay.number_of_terms, ay.exams_per_term,
               (ay.number_of_terms * ay.exams_per_term) as expected
        FROM academic_years ay
        LEFT JOIN exams e ON ay.id = e.academic_year_id
        GROUP BY ay.id, ay.year_name, ay.number_of_terms, ay.exams_per_term
        ORDER BY ay.id
    ");
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($summary as $row) {
        $status = ($row['count'] == $row['expected']) ? '✓' : '⚠';
        echo sprintf("%s %s: %d/%d exams (%d terms × %d exams/term)\n",
            $status,
            $row['year_name'],
            $row['count'],
            $row['expected'],
            $row['number_of_terms'],
            $row['exams_per_term']
        );
    }

    echo "\n✅ Cleanup completed!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
