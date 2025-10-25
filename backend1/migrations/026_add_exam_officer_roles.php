<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Adding exam officer roles to teachers table...\n";

    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'is_exam_officer'");
    if ($stmt->rowCount() === 0) {
        echo "  Adding is_exam_officer and is_head_of_exam_office columns...\n";

        $sql = "ALTER TABLE teachers
                ADD COLUMN is_exam_officer BOOLEAN DEFAULT FALSE AFTER experience_years,
                ADD COLUMN is_head_of_exam_office BOOLEAN DEFAULT FALSE AFTER is_exam_officer,
                ADD INDEX idx_exam_officer (is_exam_officer),
                ADD INDEX idx_head_exam_officer (is_head_of_exam_office)";

        $db->exec($sql);
        echo "  ✓ Added exam officer role columns to teachers table\n";
    } else {
        echo "  - Exam officer columns already exist in teachers table\n";
    }

    // Add verification columns to exam_results table
    $stmt = $db->query("SHOW COLUMNS FROM exam_results LIKE 'verified_by'");
    if ($stmt->rowCount() === 0) {
        echo "  Adding verification columns to exam_results table...\n";

        $sql = "ALTER TABLE exam_results
                ADD COLUMN verified_by INT NULL AFTER approved_at,
                ADD COLUMN verified_at TIMESTAMP NULL AFTER verified_by,
                ADD COLUMN is_verified BOOLEAN DEFAULT FALSE AFTER verified_at,
                ADD INDEX idx_verified (is_verified),
                ADD FOREIGN KEY fk_verified_by (verified_by) REFERENCES teachers(id) ON DELETE SET NULL";

        $db->exec($sql);
        echo "  ✓ Added verification columns to exam_results table\n";
    } else {
        echo "  - Verification columns already exist in exam_results table\n";
    }

    // Update grade_update_requests to link to exam officers
    $stmt = $db->query("SHOW TABLES LIKE 'grade_update_requests'");
    if ($stmt->rowCount() > 0) {
        // Check if approved_by_exam_officer column exists
        $stmt = $db->query("SHOW COLUMNS FROM grade_update_requests LIKE 'approved_by_exam_officer'");
        if ($stmt->rowCount() === 0) {
            echo "  Enhancing grade_update_requests table for exam officer workflow...\n";

            $sql = "ALTER TABLE grade_update_requests
                    ADD COLUMN approved_by_exam_officer INT NULL AFTER rejected_at,
                    ADD COLUMN field_to_update VARCHAR(50) DEFAULT 'marks_obtained' AFTER new_score,
                    ADD COLUMN current_test_score DECIMAL(5,2) NULL AFTER field_to_update,
                    ADD COLUMN new_test_score DECIMAL(5,2) NULL AFTER current_test_score,
                    ADD COLUMN current_exam_score DECIMAL(5,2) NULL AFTER new_test_score,
                    ADD COLUMN new_exam_score DECIMAL(5,2) NULL AFTER current_exam_score,
                    ADD INDEX idx_exam_officer (approved_by_exam_officer),
                    ADD FOREIGN KEY fk_approved_by_exam_officer (approved_by_exam_officer) REFERENCES teachers(id) ON DELETE SET NULL";

            $db->exec($sql);
            echo "  ✓ Enhanced grade_update_requests table\n";
        } else {
            echo "  - grade_update_requests table already enhanced\n";
        }
    }

    echo "\n✅ Exam officer role migration completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Designate teachers as exam officers by updating is_exam_officer = TRUE\n";
    echo "2. Designate one exam officer as head by updating is_head_of_exam_office = TRUE\n";
    echo "3. Only the head of exam office can verify grades (is_verified = TRUE)\n";
    echo "4. Admins/Principals will only see verified grades\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
