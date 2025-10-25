<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Adding term management to academic years...\n";

    // Check if columns exist
    $stmt = $db->query("SHOW COLUMNS FROM academic_years LIKE 'exams_per_term'");
    if ($stmt->rowCount() === 0) {
        echo "Adding columns to academic_years table...\n";
        $db->exec("ALTER TABLE academic_years
                   ADD COLUMN exams_per_term INT DEFAULT 1 COMMENT '1=single exam, 2=midterm+finals, 3=continuous',
                   ADD COLUMN current_term INT DEFAULT 1 COMMENT 'Current term number (1, 2, 3)',
                   ADD COLUMN total_terms INT DEFAULT 3 COMMENT 'Total terms in the academic year',
                   ADD COLUMN term1_start_date DATE NULL,
                   ADD COLUMN term1_end_date DATE NULL,
                   ADD COLUMN term2_start_date DATE NULL,
                   ADD COLUMN term2_end_date DATE NULL,
                   ADD COLUMN term3_start_date DATE NULL,
                   ADD COLUMN term3_end_date DATE NULL,
                   ADD COLUMN term1_exams_published INT DEFAULT 0,
                   ADD COLUMN term2_exams_published INT DEFAULT 0,
                   ADD COLUMN term3_exams_published INT DEFAULT 0");
        echo "  ✓ Added term management columns to academic_years table\n";
    } else {
        echo "  - academic_years table already has term management columns\n";
    }

    // Create terms table
    $stmt = $db->query("SHOW TABLES LIKE 'terms'");
    if ($stmt->rowCount() === 0) {
        echo "Creating terms table...\n";
        $db->exec("CREATE TABLE terms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            academic_year_id INT NOT NULL,
            term_number INT NOT NULL COMMENT '1, 2, or 3',
            term_name VARCHAR(100) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            is_current BOOLEAN DEFAULT FALSE,
            exams_required INT DEFAULT 1 COMMENT 'Number of exams for this term',
            exams_published INT DEFAULT 0 COMMENT 'Number of exams published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            UNIQUE KEY unique_term_per_year (academic_year_id, term_number),
            INDEX idx_academic_year (academic_year_id),
            INDEX idx_is_current (is_current)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "  ✓ Created terms table\n";
    } else {
        echo "  - terms table already exists\n";
    }

    // Add term_id to exams table
    $stmt = $db->query("SHOW COLUMNS FROM exams LIKE 'term_id'");
    if ($stmt->rowCount() === 0) {
        echo "Adding term_id to exams table...\n";
        $db->exec("ALTER TABLE exams
                   ADD COLUMN term_id INT NULL,
                   ADD COLUMN is_published BOOLEAN DEFAULT FALSE,
                   ADD COLUMN published_at TIMESTAMP NULL,
                   ADD INDEX idx_term_id (term_id)");
        echo "  ✓ Added term_id to exams table\n";
    } else {
        echo "  - exams table already has term_id column\n";
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
