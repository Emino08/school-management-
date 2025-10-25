<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== VERIFICATION OF EXAM AND PROMOTION SYSTEM CHANGES ===\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // 1. Verify exam_type ENUM includes 'test'
    echo "1. Checking exam_type ENUM values:\n";
    $stmt = $db->query("SHOW COLUMNS FROM exams LIKE 'exam_type'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Current ENUM: {$col['Type']}\n";
    if (strpos($col['Type'], 'test') !== false) {
        echo "   ✓ 'test' type is available\n\n";
    } else {
        echo "   ✗ 'test' type is NOT available\n\n";
    }

    // 2. Verify promotion averages in academic_years
    echo "2. Checking promotion average columns in academic_years:\n";
    $stmt = $db->query("SHOW COLUMNS FROM academic_years WHERE Field LIKE '%average'");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   ✓ {$col['Field']}: {$col['Type']} (Default: {$col['Default']})\n";
    }
    echo "\n";

    // 3. Verify promotion_status in student_enrollments
    echo "3. Checking promotion_status column in student_enrollments:\n";
    $stmt = $db->query("SHOW COLUMNS FROM student_enrollments LIKE 'promotion_status'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($col) {
        echo "   ✓ Column exists: {$col['Field']}, Type: {$col['Type']}\n\n";
    } else {
        echo "   ✗ Column does NOT exist\n\n";
    }

    // 4. Show current academic years with their settings
    echo "4. Current academic years and their promotion settings:\n";
    $stmt = $db->query("
        SELECT id, year_name, number_of_terms, exams_per_term,
               promotion_average, repeat_average, drop_average,
               current_term
        FROM academic_years
        ORDER BY id
    ");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($years as $year) {
        echo "   Year: {$year['year_name']} (ID: {$year['id']})\n";
        echo "     - Terms: {$year['number_of_terms']}, Exams per term: {$year['exams_per_term']}\n";
        echo "     - Current term: {$year['current_term']}\n";
        echo "     - Promotion avg: {$year['promotion_average']}%\n";
        echo "     - Repeat avg: {$year['repeat_average']}%\n";
        echo "     - Drop avg: {$year['drop_average']}%\n";
    }
    echo "\n";

    // 5. Show exam distribution by type
    echo "5. Exam distribution by type:\n";
    $stmt = $db->query("
        SELECT exam_type, COUNT(*) as count
        FROM exams
        GROUP BY exam_type
        ORDER BY exam_type
    ");
    $examTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($examTypes as $type) {
        echo "   - {$type['exam_type']}: {$type['count']} exams\n";
    }
    echo "\n";

    // 6. Show term progression tracking
    echo "6. Term progression tracking:\n";
    $stmt = $db->query("
        SELECT t.id, t.term_number, t.exams_required, t.exams_published, t.is_current,
               ay.year_name
        FROM terms t
        JOIN academic_years ay ON t.academic_year_id = ay.id
        ORDER BY ay.id, t.term_number
    ");
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($terms as $term) {
        $current = $term['is_current'] ? ' [CURRENT]' : '';
        echo "   {$term['year_name']} - Term {$term['term_number']}{$current}\n";
        echo "     - Exams published: {$term['exams_published']}/{$term['exams_required']}\n";
    }
    echo "\n";

    echo "=== VERIFICATION COMPLETE ===\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
