<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Adding marks_obtained and approval_status columns to exam_results...\n";

    // Check and add marks_obtained column
    $stmt = $db->query("SHOW COLUMNS FROM exam_results LIKE 'marks_obtained'");
    if ($stmt->rowCount() === 0) {
        echo "  Adding marks_obtained column...\n";
        $db->exec("ALTER TABLE exam_results ADD COLUMN marks_obtained DECIMAL(5,2) DEFAULT NULL AFTER subject_id");
        echo "  ✓ Added marks_obtained column\n";
    } else {
        echo "  - marks_obtained column already exists\n";
    }

    // Check and add approval_status column
    $stmt = $db->query("SHOW COLUMNS FROM exam_results LIKE 'approval_status'");
    if ($stmt->rowCount() === 0) {
        echo "  Adding approval_status column...\n";
        $db->exec("ALTER TABLE exam_results ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER remarks");
        echo "  ✓ Added approval_status column\n";
    } else {
        echo "  - approval_status column already exists\n";
    }

    // Check and add uploaded_by_teacher_id column
    $stmt = $db->query("SHOW COLUMNS FROM exam_results LIKE 'uploaded_by_teacher_id'");
    if ($stmt->rowCount() === 0) {
        echo "  Adding uploaded_by_teacher_id column...\n";
        $db->exec("ALTER TABLE exam_results ADD COLUMN uploaded_by_teacher_id INT NULL AFTER approval_status");
        echo "  ✓ Added uploaded_by_teacher_id column\n";
    } else {
        echo "  - uploaded_by_teacher_id column already exists\n";
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
