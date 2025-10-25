<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Creating grade_update_requests table...\n";

    $stmt = $db->query("SHOW TABLES LIKE 'grade_update_requests'");
    if ($stmt->rowCount() === 0) {
        $sql = "CREATE TABLE grade_update_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            result_id INT NOT NULL,
            teacher_id INT NOT NULL,
            current_score DECIMAL(5,2) NOT NULL,
            new_score DECIMAL(5,2) NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            approved_by INT NULL,
            approved_at TIMESTAMP NULL,
            rejected_by INT NULL,
            rejected_at TIMESTAMP NULL,
            rejection_reason TEXT NULL,
            INDEX idx_status (status),
            INDEX idx_requested_at (requested_at),
            INDEX idx_result_id (result_id),
            INDEX idx_teacher_id (teacher_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($sql);
        echo "  ✓ Created grade_update_requests table\n";
    } else {
        echo "  - grade_update_requests table already exists\n";
    }

    // Check if exam_results table has approval columns
    $stmt = $db->query("SHOW TABLES LIKE 'exam_results'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM exam_results LIKE 'approved_by'");
        if ($stmt->rowCount() === 0) {
            echo "Adding approval columns to exam_results table...\n";
            $db->exec("ALTER TABLE exam_results
                       ADD COLUMN approved_by INT NULL,
                       ADD COLUMN approved_at TIMESTAMP NULL,
                       ADD COLUMN rejected_by INT NULL,
                       ADD COLUMN rejected_at TIMESTAMP NULL,
                       ADD COLUMN rejection_reason TEXT NULL");
            echo "  ✓ Added approval columns to exam_results table\n";
        } else {
            echo "  - exam_results table already has approval columns\n";
        }
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
