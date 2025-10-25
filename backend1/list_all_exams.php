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

    echo "=== ALL EXAMS ===\n";
    $stmt = $pdo->query("
        SELECT e.id, e.exam_name, e.exam_type, e.term_id, e.exam_number, e.exam_date,
               e.academic_year_id, ay.year_name, t.term_name
        FROM exams e
        LEFT JOIN academic_years ay ON e.academic_year_id = ay.id
        LEFT JOIN terms t ON e.term_id = t.id
        ORDER BY e.academic_year_id, e.term_id, e.exam_number, e.id
    ");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentYear = null;
    foreach ($exams as $exam) {
        if ($currentYear != $exam['year_name']) {
            $currentYear = $exam['year_name'];
            echo "\n--- {$currentYear} ---\n";
        }
        printf("ID:%-2d | %-25s | Type:%-8s | Term:%-10s | Num:%-2s | Date:%s\n",
            $exam['id'],
            $exam['exam_name'],
            $exam['exam_type'],
            $exam['term_name'] ?? 'NULL',
            $exam['exam_number'] ?? 'NULL',
            $exam['exam_date'] ?? 'NULL'
        );
    }

    echo "\n=== COUNT BY YEAR ===\n";
    $stmt = $pdo->query("
        SELECT ay.year_name, COUNT(e.id) as count
        FROM academic_years ay
        LEFT JOIN exams e ON ay.id = e.academic_year_id
        GROUP BY ay.id, ay.year_name
        ORDER BY ay.id
    ");
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counts as $row) {
        echo "{$row['year_name']}: {$row['count']} exams\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
