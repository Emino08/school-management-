<?php
/**
 * Create Missing Town Master Tables
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

try {
    echo "Creating Missing Town Master Tables...\n\n";
    
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Create blocks table
    echo "Creating blocks table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `blocks` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `town_id` INT NOT NULL,
          `name` VARCHAR(50) NOT NULL,
          `capacity` INT NOT NULL DEFAULT 50,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_block_name` (`town_id`, `name`),
          KEY `idx_town_id` (`town_id`),
          CONSTRAINT `fk_blocks_town` FOREIGN KEY (`town_id`) REFERENCES `towns` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ blocks table created\n\n";
    
    // Create student_blocks table
    echo "Creating student_blocks table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `student_blocks` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `student_id` INT NOT NULL,
          `block_id` INT NOT NULL,
          `academic_year_id` INT NOT NULL,
          `term` INT NOT NULL,
          `assigned_by` INT NOT NULL,
          `guardian_name` VARCHAR(255),
          `guardian_phone` VARCHAR(20),
          `guardian_email` VARCHAR(255),
          `guardian_address` TEXT,
          `is_active` BOOLEAN DEFAULT TRUE,
          `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `deactivated_at` TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_student_id` (`student_id`),
          KEY `idx_block_id` (`block_id`),
          KEY `idx_academic_year` (`academic_year_id`),
          KEY `idx_active` (`is_active`),
          CONSTRAINT `fk_student_blocks_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_student_blocks_block` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_student_blocks_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_student_blocks_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ student_blocks table created\n\n";
    
    // Create attendance_strikes table
    echo "Creating attendance_strikes table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `attendance_strikes` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `student_id` INT NOT NULL,
          `academic_year_id` INT NOT NULL,
          `term` INT NOT NULL,
          `absence_count` INT NOT NULL DEFAULT 0,
          `last_absence_date` DATE,
          `notification_sent` BOOLEAN DEFAULT FALSE,
          `notification_sent_at` TIMESTAMP NULL DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_student_term_strike` (`student_id`, `academic_year_id`, `term`),
          KEY `idx_student_id` (`student_id`),
          KEY `idx_absence_count` (`absence_count`),
          CONSTRAINT `fk_attendance_strikes_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_attendance_strikes_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ attendance_strikes table created\n\n";
    
    echo "✓ All tables created successfully!\n\n";
    
    // Verify all tables exist
    echo "Verifying tables...\n";
    $tables = ['towns', 'blocks', 'town_masters', 'student_blocks', 'town_attendance', 'attendance_strikes'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '{$table}' exists\n";
        } else {
            echo "✗ Table '{$table}' NOT found\n";
        }
    }
    
    echo "\n✓ Migration complete!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
