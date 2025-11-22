<?php
/**
 * Database Migration - Fix Missing Columns
 * Addresses activity_type and currency_code column issues
 */

require 'vendor/autoload.php';

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    
    echo "=== Starting Database Migration ===" . PHP_EOL . PHP_EOL;
    
    // Fix 1: Ensure activity_logs table has all required columns
    echo "1. Checking activity_logs table..." . PHP_EOL;
    
    $stmt = $db->query("SHOW COLUMNS FROM activity_logs LIKE 'activity_type'");
    if ($stmt->rowCount() == 0) {
        echo "   Adding activity_type column..." . PHP_EOL;
        $db->exec("ALTER TABLE activity_logs ADD COLUMN activity_type VARCHAR(50) AFTER user_type");
        echo "   ✓ Added activity_type column" . PHP_EOL;
    } else {
        echo "   ✓ activity_type column exists" . PHP_EOL;
    }
    
    // Fix 2: Ensure settings table has currency_code column
    echo "2. Checking settings table..." . PHP_EOL;
    
    $stmt = $db->query("SHOW COLUMNS FROM settings LIKE 'currency_code'");
    if ($stmt->rowCount() == 0) {
        echo "   Adding currency_code column..." . PHP_EOL;
        $db->exec("ALTER TABLE settings ADD COLUMN currency_code VARCHAR(3) DEFAULT 'USD'");
        echo "   ✓ Added currency_code column" . PHP_EOL;
    } else {
        echo "   ✓ currency_code column exists" . PHP_EOL;
    }
    
    // Fix 3: Ensure teachers table has first_name and last_name columns
    echo "3. Checking teachers table..." . PHP_EOL;
    
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'first_name'");
    if ($stmt->rowCount() == 0) {
        echo "   Adding first_name column..." . PHP_EOL;
        $db->exec("ALTER TABLE teachers ADD COLUMN first_name VARCHAR(100) AFTER id");
        echo "   ✓ Added first_name column" . PHP_EOL;
    } else {
        echo "   ✓ first_name column exists" . PHP_EOL;
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'last_name'");
    if ($stmt->rowCount() == 0) {
        echo "   Adding last_name column..." . PHP_EOL;
        $db->exec("ALTER TABLE teachers ADD COLUMN last_name VARCHAR(100) AFTER first_name");
        echo "   ✓ Added last_name column" . PHP_EOL;
    } else {
        echo "   ✓ last_name column exists" . PHP_EOL;
    }
    
    // Fix 4: Check if we need to split existing teacher names
    echo "4. Checking for teachers with unsplit names..." . PHP_EOL;
    $stmt = $db->query("SELECT COUNT(*) as count FROM teachers WHERE (first_name IS NULL OR first_name = '') AND name IS NOT NULL AND name != ''");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "   Found {$result['count']} teachers with unsplit names" . PHP_EOL;
        echo "   Splitting names..." . PHP_EOL;
        
        $db->exec("
            UPDATE teachers 
            SET 
                first_name = SUBSTRING_INDEX(name, ' ', 1),
                last_name = TRIM(SUBSTRING(name, LOCATE(' ', name) + 1))
            WHERE (first_name IS NULL OR first_name = '') 
            AND name IS NOT NULL 
            AND name != ''
        ");
        
        echo "   ✓ Split {$result['count']} teacher names" . PHP_EOL;
    } else {
        echo "   ✓ All teacher names are already split" . PHP_EOL;
    }
    
    // Fix 5: Ensure towns table exists for Town Master functionality
    echo "5. Checking towns table..." . PHP_EOL;
    
    $stmt = $db->query("SHOW TABLES LIKE 'towns'");
    if ($stmt->rowCount() == 0) {
        echo "   Creating towns table..." . PHP_EOL;
        $db->exec("
            CREATE TABLE towns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                admin_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                INDEX idx_admin_id (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   ✓ Created towns table" . PHP_EOL;
    } else {
        echo "   ✓ towns table exists" . PHP_EOL;
    }
    
    // Fix 6: Ensure town_blocks table exists
    echo "6. Checking town_blocks table..." . PHP_EOL;
    
    $stmt = $db->query("SHOW TABLES LIKE 'town_blocks'");
    if ($stmt->rowCount() == 0) {
        echo "   Creating town_blocks table..." . PHP_EOL;
        $db->exec("
            CREATE TABLE town_blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                town_id INT NOT NULL,
                block_name VARCHAR(10) NOT NULL,
                capacity INT NOT NULL DEFAULT 50,
                current_count INT NOT NULL DEFAULT 0,
                admin_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                UNIQUE KEY unique_town_block (town_id, block_name),
                INDEX idx_town_id (town_id),
                INDEX idx_admin_id (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   ✓ Created town_blocks table" . PHP_EOL;
    } else {
        echo "   ✓ town_blocks table exists" . PHP_EOL;
    }
    
    // Fix 7: Ensure town_students table exists
    echo "7. Checking town_students table..." . PHP_EOL;
    
    $stmt = $db->query("SHOW TABLES LIKE 'town_students'");
    if ($stmt->rowCount() == 0) {
        echo "   Creating town_students table..." . PHP_EOL;
        $db->exec("
            CREATE TABLE town_students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                town_id INT NOT NULL,
                block_id INT NOT NULL,
                term_id INT NOT NULL,
                academic_year_id INT NOT NULL,
                registered_by INT NOT NULL,
                registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('active', 'inactive') DEFAULT 'active',
                admin_id INT NOT NULL,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
                FOREIGN KEY (block_id) REFERENCES town_blocks(id) ON DELETE CASCADE,
                FOREIGN KEY (registered_by) REFERENCES teachers(id) ON DELETE CASCADE,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                UNIQUE KEY unique_student_term (student_id, term_id, academic_year_id),
                INDEX idx_student_id (student_id),
                INDEX idx_town_id (town_id),
                INDEX idx_block_id (block_id),
                INDEX idx_term_id (term_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   ✓ Created town_students table" . PHP_EOL;
    } else {
        echo "   ✓ town_students table exists" . PHP_EOL;
    }
    
    // Fix 8: Ensure town_attendance table exists
    echo "8. Checking town_attendance table..." . PHP_EOL;
    
    $stmt = $db->query("SHOW TABLES LIKE 'town_attendance'");
    if ($stmt->rowCount() == 0) {
        echo "   Creating town_attendance table..." . PHP_EOL;
        $db->exec("
            CREATE TABLE town_attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                town_id INT NOT NULL,
                block_id INT NOT NULL,
                attendance_date DATE NOT NULL,
                status ENUM('present', 'absent', 'late') NOT NULL,
                marked_by INT NOT NULL,
                marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                notes TEXT,
                admin_id INT NOT NULL,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
                FOREIGN KEY (block_id) REFERENCES town_blocks(id) ON DELETE CASCADE,
                FOREIGN KEY (marked_by) REFERENCES teachers(id) ON DELETE CASCADE,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                UNIQUE KEY unique_student_date (student_id, attendance_date, town_id),
                INDEX idx_student_id (student_id),
                INDEX idx_town_id (town_id),
                INDEX idx_attendance_date (attendance_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   ✓ Created town_attendance table" . PHP_EOL;
    } else {
        echo "   ✓ town_attendance table exists" . PHP_EOL;
    }
    
    // Fix 9: Add town_master_id column to teachers table
    echo "9. Checking teachers table for town_master_id..." . PHP_EOL;
    
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'town_master_id'");
    if ($stmt->rowCount() == 0) {
        echo "   Adding town_master_id column..." . PHP_EOL;
        $db->exec("ALTER TABLE teachers ADD COLUMN town_master_id INT NULL AFTER is_exam_officer");
        $db->exec("ALTER TABLE teachers ADD FOREIGN KEY (town_master_id) REFERENCES towns(id) ON DELETE SET NULL");
        echo "   ✓ Added town_master_id column" . PHP_EOL;
    } else {
        echo "   ✓ town_master_id column exists" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== Migration Completed Successfully ===" . PHP_EOL;
    
} catch (\Exception $e) {
    echo PHP_EOL . "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
