<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "Academic Years and their exam settings:\n";
    $stmt = $db->query("SELECT id, year_name, number_of_terms, exams_per_term FROM academic_years ORDER BY id");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($years as $year) {
        echo "\n  Year ID: {$year['id']}, Name: {$year['year_name']}\n";
        echo "  Terms: {$year['number_of_terms']}, Exams per term: {$year['exams_per_term']}\n";

        // Count exams for this year
        $stmt2 = $db->prepare("SELECT COUNT(*) as exam_count FROM exams WHERE academic_year_id = ?");
        $stmt2->execute([$year['id']]);
        $count = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo "  Total exams created: {$count['exam_count']}\n";

        $expected = $year['number_of_terms'] * $year['exams_per_term'];
        echo "  Expected exams: {$expected}\n";

        if ($count['exam_count'] != $expected) {
            echo "  ⚠️  MISMATCH!\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
