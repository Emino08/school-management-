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

    echo "=== ACADEMIC YEARS ===\n";
    $stmt = $pdo->query('SELECT id, year_name, start_date, end_date, is_current, number_of_terms, exams_per_term FROM academic_years ORDER BY id');
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($years as $year) {
        echo sprintf("ID: %d | Year: %s | Terms: %d | Exams/Term: %d | Current: %d\n",
            $year['id'], $year['year_name'], $year['number_of_terms'], $year['exams_per_term'], $year['is_current']);
    }

    echo "\n=== TERMS ===\n";
    $stmt = $pdo->query('SELECT id, academic_year_id, term_name, term_number, start_date, end_date, is_current FROM terms ORDER BY academic_year_id, term_number');
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($terms as $term) {
        echo sprintf("Term ID: %d | Year ID: %d | %s (Term %d) | Current: %d\n",
            $term['id'], $term['academic_year_id'], $term['term_name'], $term['term_number'], $term['is_current']);
    }

    echo "\n=== CLASSES ===\n";
    $stmt = $pdo->query('SELECT id, class_name, grade_level FROM classes ORDER BY grade_level, class_name');
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($classes as $class) {
        echo sprintf("Class ID: %d | Name: %s | Grade: %s\n",
            $class['id'], $class['class_name'], $class['grade_level'] ?? 'N/A');
    }

    echo "\n=== EXISTING EXAMS ===\n";
    $stmt = $pdo->query('
        SELECT e.id, e.exam_name, e.exam_type, e.academic_year_id, e.class_id, e.exam_date, e.status,
               ay.year_name, c.class_name
        FROM exams e
        LEFT JOIN academic_years ay ON e.academic_year_id = ay.id
        LEFT JOIN classes c ON e.class_id = c.id
        ORDER BY e.academic_year_id, e.class_id, e.exam_date
    ');
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($exams)) {
        echo "No exams found in database.\n";
    } else {
        foreach ($exams as $exam) {
            echo sprintf("ID: %d | %s | Type: %s | Class: %s | Year: %s | Date: %s | Status: %s\n",
                $exam['id'], $exam['exam_name'], $exam['exam_type'],
                $exam['class_name'] ?? 'N/A', $exam['year_name'] ?? 'N/A',
                $exam['exam_date'] ?? 'Not set', $exam['status']);
        }
    }

    echo "\n=== EXAM COUNT BY YEAR ===\n";
    $stmt = $pdo->query('
        SELECT academic_year_id, ay.year_name, COUNT(*) as total
        FROM exams e
        LEFT JOIN academic_years ay ON e.academic_year_id = ay.id
        GROUP BY academic_year_id, ay.year_name
    ');
    $examCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($examCounts)) {
        echo "No exams found.\n";
    } else {
        foreach ($examCounts as $count) {
            echo sprintf("Year: %s | Total Exams: %d\n", $count['year_name'] ?? 'Unknown', $count['total']);
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
