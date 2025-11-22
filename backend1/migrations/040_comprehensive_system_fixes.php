<?php
/**
 * Comprehensive System Fixes Migration
 * - Add currency_code to settings table
 * - Add first_name and last_name columns to teachers table
 * - Add first_name and last_name columns to students table
 * - Create town_masters table and blocks table
 * - Create town_attendance table
 * - Add town_master_id to teachers
 * - Create urgent_notifications table
 * - Fix any missing columns
 */

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $database = \App\Config\Database::getInstance();
    $db = $database->getConnection();
    
    echo "Starting comprehensive system fixes migration...\n\n";
    
    // 1. Fix settings table - add currency_code
    echo "1. Adding currency_code to settings table...\n";
    try {
        $sql = "ALTER TABLE settings 
                ADD COLUMN IF NOT EXISTS currency_code VARCHAR(3) DEFAULT 'USD' AFTER value";
        $db->exec($sql);
        echo "✓ currency_code column added to settings\n\n";
    } catch (\Exception $e) {
        echo "Note: currency_code may already exist or error: " . $e->getMessage() . "\n\n";
    }
    
    // 2. Split teacher names into first_name and last_name
    echo "2. Updating teachers table for first_name and last_name...\n";
    try {
        // Add columns if they don't exist
        $sql = "ALTER TABLE teachers 
                ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER id,
                ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER first_name";
        $db->exec($sql);
        
        // Migrate existing names if not already done
        $checkSql = "SELECT COUNT(*) as count FROM teachers WHERE name IS NOT NULL AND first_name IS NULL";
        $stmt = $db->query($checkSql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $migrateSql = "UPDATE teachers 
                          SET first_name = SUBSTRING_INDEX(name, ' ', 1),
                              last_name = TRIM(SUBSTRING(name, LOCATE(' ', name) + 1))
                          WHERE name IS NOT NULL AND first_name IS NULL";
            $db->exec($migrateSql);
            echo "✓ Migrated {$result['count']} teacher names\n";
        }
        
        echo "✓ Teachers table updated successfully\n\n";
    } catch (\Exception $e) {
        echo "Error updating teachers: " . $e->getMessage() . "\n\n";
    }
    
    // 3. Split student names into first_name and last_name
    echo "3. Updating students table for first_name and last_name...\n";
    try {
        // Add columns if they don't exist
        $sql = "ALTER TABLE students 
                ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER id,
                ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER first_name";
        $db->exec($sql);
        
        // Migrate existing names if not already done
        $checkSql = "SELECT COUNT(*) as count FROM students WHERE name IS NOT NULL AND first_name IS NULL";
        $stmt = $db->query($checkSql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $migrateSql = "UPDATE students 
                          SET first_name = SUBSTRING_INDEX(name, ' ', 1),
                              last_name = TRIM(SUBSTRING(name, LOCATE(' ', name) + 1))
                          WHERE name IS NOT NULL AND first_name IS NULL";
            $db->exec($migrateSql);
            echo "✓ Migrated {$result['count']} student names\n";
        }
        
        echo "✓ Students table updated successfully\n\n";
    } catch (\Exception $e) {
        echo "Error updating students: " . $e->getMessage() . "\n\n";
    }
    
    // 4. Create towns table
    echo "4. Creating towns table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS towns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT NULL,
        capacity INT DEFAULT 0,
        admin_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
        INDEX idx_admin (admin_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "✓ Towns table created\n\n";
    
    // 5. Create town_blocks table
    echo "5. Creating town_blocks table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS town_blocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        town_id INT NOT NULL,
        block_name VARCHAR(10) NOT NULL COMMENT 'A, B, C, D, E, F',
        capacity INT DEFAULT 0,
        current_occupancy INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
        UNIQUE KEY unique_town_block (town_id, block_name),
        INDEX idx_town (town_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "✓ Town blocks table created\n\n";
    
    // 6. Create town_master_assignments table
    echo "6. Creating town_master_assignments table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS town_master_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        town_id INT NOT NULL,
        academic_year_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
        FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
        FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (teacher_id, town_id, academic_year_id),
        INDEX idx_teacher (teacher_id),
        INDEX idx_town (town_id),
        INDEX idx_year (academic_year_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "✓ Town master assignments table created\n\n";
    
    // 7. Create town_student_registrations table
    echo "7. Creating town_student_registrations table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS town_student_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        town_id INT NOT NULL,
        block_id INT NULL,
        academic_year_id INT NOT NULL,
        term INT NOT NULL COMMENT '1, 2, or 3',
        guardian_name VARCHAR(255) NULL,
        guardian_phone VARCHAR(20) NULL,
        guardian_email VARCHAR(255) NULL,
        guardian_address TEXT NULL,
        registered_by INT NOT NULL COMMENT 'Town master teacher_id',
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
        FOREIGN KEY (block_id) REFERENCES town_blocks(id) ON DELETE SET NULL,
        FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
        FOREIGN KEY (registered_by) REFERENCES teachers(id) ON DELETE RESTRICT,
        UNIQUE KEY unique_registration (student_id, town_id, academic_year_id, term),
        INDEX idx_student (student_id),
        INDEX idx_town (town_id),
        INDEX idx_block (block_id),
        INDEX idx_year_term (academic_year_id, term),
        INDEX idx_registered_by (registered_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "✓ Town student registrations table created\n\n";
    
    // 8. Create town_attendance table
    echo "8. Creating town_attendance table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS town_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        town_id INT NOT NULL,
        block_id INT NULL,
        attendance_date DATE NOT NULL,
        attendance_time TIME NOT NULL,
        status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
        marked_by INT NOT NULL COMMENT 'Town master teacher_id',
        notes TEXT NULL,
        parent_notified BOOLEAN DEFAULT FALSE,
        notification_sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
        FOREIGN KEY (block_id) REFERENCES town_blocks(id) ON DELETE SET NULL,
        FOREIGN KEY (marked_by) REFERENCES teachers(id) ON DELETE RESTRICT,
        UNIQUE KEY unique_attendance (student_id, town_id, attendance_date),
        INDEX idx_student (student_id),
        INDEX idx_town (town_id),
        INDEX idx_date (attendance_date),
        INDEX idx_status (status),
        INDEX idx_marked_by (marked_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "✓ Town attendance table created\n\n";
    
    // 9. Create urgent_notifications table
    echo "9. Creating urgent_notifications table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS urgent_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        notification_type ENUM('attendance', 'discipline', 'academic', 'health', 'other') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        action_required BOOLEAN DEFAULT TRUE,
        action_taken BOOLEAN DEFAULT FALSE,
        action_taken_by INT NULL COMMENT 'Admin/Principal ID',
        action_taken_at TIMESTAMP NULL,
        action_notes TEXT NULL,
        created_by INT NOT NULL,
        created_by_role ENUM('admin', 'principal', 'teacher', 'town_master') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (action_taken_by) REFERENCES admins(id) ON DELETE SET NULL,
        INDEX idx_student (student_id),
        INDEX idx_type (notification_type),
        INDEX idx_severity (severity),
        INDEX idx_action (action_required, action_taken),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "✓ Urgent notifications table created\n\n";
    
    // 10. Add is_town_master flag to teachers
    echo "10. Adding town master flag to teachers...\n";
    try {
        $sql = "ALTER TABLE teachers 
                ADD COLUMN IF NOT EXISTS is_town_master BOOLEAN DEFAULT FALSE AFTER is_class_master";
        $db->exec($sql);
        echo "✓ is_town_master column added\n\n";
    } catch (\Exception $e) {
        echo "Note: is_town_master may already exist\n\n";
    }
    
    // 11. Create parents table if it doesn't exist
    echo "11. Ensuring parents table exists...\n";
    $sql = "CREATE TABLE IF NOT EXISTS parents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NULL,
        last_name VARCHAR(100) NULL,
        phone VARCHAR(20) NULL,
        address TEXT NULL,
        emergency_contact VARCHAR(20) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "✓ Parents table ensured\n\n";
    
    // 12. Create parent_student_links table
    echo "12. Creating parent_student_links table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS parent_student_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_id INT NOT NULL,
        student_id INT NOT NULL,
        relationship ENUM('father', 'mother', 'guardian', 'other') DEFAULT 'guardian',
        is_primary BOOLEAN DEFAULT FALSE,
        verified BOOLEAN DEFAULT FALSE,
        verified_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        UNIQUE KEY unique_link (parent_id, student_id),
        INDEX idx_parent (parent_id),
        INDEX idx_student (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "✓ Parent-student links table created\n\n";
    
    echo "\n✓✓✓ Migration completed successfully! ✓✓✓\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
