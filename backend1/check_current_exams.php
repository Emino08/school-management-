<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "Current exams in database:\n";
    $stmt = $db->query("SELECT id, exam_name, exam_type, term_id, academic_year_id FROM exams ORDER BY academic_year_id, term_id, exam_name");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($exams as $exam) {
        echo "  - ID: {$exam['id']}, Name: {$exam['exam_name']}, Type: {$exam['exam_type']}, Term: {$exam['term_id']}, Year: {$exam['academic_year_id']}\n";
    }

    echo "\nChecking exam_type ENUM values:\n";
    $stmt = $db->query("SHOW COLUMNS FROM exams LIKE 'exam_type'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Current ENUM: {$col['Type']}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
