<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    
    echo "Starting comprehensive fix migration...\n\n";
    
    // 1. Check and fix activity_logs table
    echo "1. Checking activity_logs table...\n";
    $stmt = $db->query("SHOW COLUMNS FROM activity_logs LIKE 'activity_type'");
    if ($stmt->rowCount() == 0) {
        echo "   - Adding activity_type column to activity_logs...\n";
        $db->exec("ALTER TABLE activity_logs ADD COLUMN activity_type VARCHAR(50) NOT NULL AFTER user_type");
        echo "   ✓ activity_type column added\n";
    } else {
        echo "   ✓ activity_type column exists\n";
    }
    
    // 2. Check and fix system_settings table for currency_code
    echo "\n2. Checking system_settings table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'system_settings'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM system_settings LIKE 'currency_code'");
        if ($stmt->rowCount() == 0) {
            echo "   - Adding currency_code column to system_settings...\n";
            $db->exec("ALTER TABLE system_settings ADD COLUMN currency_code VARCHAR(10) DEFAULT 'USD' AFTER school_name");
            echo "   ✓ currency_code column added\n";
        } else {
            echo "   ✓ currency_code column exists\n";
        }
        
        $stmt = $db->query("SHOW COLUMNS FROM system_settings LIKE 'currency_symbol'");
        if ($stmt->rowCount() == 0) {
            echo "   - Adding currency_symbol column to system_settings...\n";
            $db->exec("ALTER TABLE system_settings ADD COLUMN currency_symbol VARCHAR(10) DEFAULT '$' AFTER currency_code");
            echo "   ✓ currency_symbol column added\n";
        } else {
            echo "   ✓ currency_symbol column exists\n";
        }
    } else {
        echo "   - Creating system_settings table...\n";
        $db->exec("CREATE TABLE system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            school_name VARCHAR(255),
            currency_code VARCHAR(10) DEFAULT 'USD',
            currency_symbol VARCHAR(10) DEFAULT '$',
            email_host VARCHAR(255),
            email_port INT,
            email_username VARCHAR(255),
            email_password VARCHAR(255),
            email_from_address VARCHAR(255),
            email_from_name VARCHAR(255),
            email_encryption VARCHAR(10),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        echo "   ✓ system_settings table created\n";
    }
    
    // 3. Check and update teachers table for first_name and last_name
    echo "\n3. Checking teachers table...\n";
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'first_name'");
    if ($stmt->rowCount() == 0) {
        echo "   - Splitting teacher names into first_name and last_name...\n";
        $db->exec("ALTER TABLE teachers ADD COLUMN first_name VARCHAR(100) AFTER id");
        $db->exec("ALTER TABLE teachers ADD COLUMN last_name VARCHAR(100) AFTER first_name");
        
        // Migrate existing name data
        $stmt = $db->query("SELECT id, name FROM teachers WHERE name IS NOT NULL AND name != ''");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($teachers as $teacher) {
            $nameParts = explode(' ', trim($teacher['name']), 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            
            $update = $db->prepare("UPDATE teachers SET first_name = ?, last_name = ? WHERE id = ?");
            $update->execute([$firstName, $lastName, $teacher['id']]);
        }
        
        echo "   ✓ Teacher names split successfully (" . count($teachers) . " teachers updated)\n";
    } else {
        echo "   ✓ first_name and last_name columns exist\n";
    }
    
    // 4. Check and create towns table
    echo "\n4. Checking towns table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'towns'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating towns table...\n";
        $db->exec("CREATE TABLE towns (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        echo "   ✓ towns table created\n";
    } else {
        echo "   ✓ towns table exists\n";
    }
    
    // 5. Check and create town_blocks table
    echo "\n5. Checking town_blocks table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'town_blocks'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating town_blocks table...\n";
        $db->exec("CREATE TABLE town_blocks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            town_id INT NOT NULL,
            block_name VARCHAR(10) NOT NULL,
            capacity INT NOT NULL DEFAULT 50,
            current_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
            UNIQUE KEY unique_town_block (town_id, block_name)
        )");
        echo "   ✓ town_blocks table created\n";
    } else {
        echo "   ✓ town_blocks table exists\n";
    }
    
    // 6. Check and create town_masters table
    echo "\n6. Checking town_masters table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'town_masters'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating town_masters table...\n";
        $db->exec("CREATE TABLE town_masters (
            id INT PRIMARY KEY AUTO_INCREMENT,
            teacher_id INT NOT NULL,
            town_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
            FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
            UNIQUE KEY unique_town_master (town_id)
        )");
        echo "   ✓ town_masters table created\n";
    } else {
        echo "   ✓ town_masters table exists\n";
    }
    
    // 7. Check and create town_students table
    echo "\n7. Checking town_students table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'town_students'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating town_students table...\n";
        $db->exec("CREATE TABLE town_students (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            town_id INT NOT NULL,
            block_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            term_id INT NOT NULL,
            registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            guardian_name VARCHAR(200),
            guardian_phone VARCHAR(20),
            guardian_email VARCHAR(100),
            guardian_address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
            FOREIGN KEY (block_id) REFERENCES town_blocks(id) ON DELETE CASCADE,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_term (student_id, term_id)
        )");
        echo "   ✓ town_students table created\n";
    } else {
        echo "   ✓ town_students table exists\n";
    }
    
    // 8. Check and create town_attendance table
    echo "\n8. Checking town_attendance table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'town_attendance'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating town_attendance table...\n";
        $db->exec("CREATE TABLE town_attendance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            town_student_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
            recorded_by INT NOT NULL,
            notes TEXT,
            parent_notified BOOLEAN DEFAULT FALSE,
            notified_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (town_student_id) REFERENCES town_students(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES teachers(id),
            UNIQUE KEY unique_attendance (town_student_id, attendance_date)
        )");
        echo "   ✓ town_attendance table created\n";
    } else {
        echo "   ✓ town_attendance table exists\n";
    }
    
    // 9. Check and create user_roles table
    echo "\n9. Checking user_roles table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'user_roles'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating user_roles table...\n";
        $db->exec("CREATE TABLE user_roles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            user_type ENUM('admin', 'teacher', 'student') NOT NULL,
            role_name VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id, user_type),
            INDEX idx_role (role_name)
        )");
        echo "   ✓ user_roles table created\n";
    } else {
        echo "   ✓ user_roles table exists\n";
    }
    
    // 10. Check and create urgent_notifications table
    echo "\n10. Checking urgent_notifications table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'urgent_notifications'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating urgent_notifications table...\n";
        $db->exec("CREATE TABLE urgent_notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            notification_id INT NOT NULL,
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            requires_action BOOLEAN DEFAULT TRUE,
            action_taken BOOLEAN DEFAULT FALSE,
            action_taken_by INT NULL,
            action_taken_at TIMESTAMP NULL,
            action_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
            INDEX idx_pending_action (requires_action, action_taken)
        )");
        echo "   ✓ urgent_notifications table created\n";
    } else {
        echo "   ✓ urgent_notifications table exists\n";
    }
    
    // 11. Check and create parents table
    echo "\n11. Checking parents table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'parents'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating parents table...\n";
        $db->exec("CREATE TABLE parents (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        echo "   ✓ parents table created\n";
    } else {
        echo "   ✓ parents table exists\n";
    }
    
    // 12. Check and create parent_student_bindings table
    echo "\n12. Checking parent_student_bindings table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'parent_student_bindings'");
    if ($stmt->rowCount() == 0) {
        echo "   - Creating parent_student_bindings table...\n";
        $db->exec("CREATE TABLE parent_student_bindings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            parent_id INT NOT NULL,
            student_id INT NOT NULL,
            relationship VARCHAR(50),
            verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_parent_student (parent_id, student_id)
        )");
        echo "   ✓ parent_student_bindings table created\n";
    } else {
        echo "   ✓ parent_student_bindings table exists\n";
    }
    
    // 13. Add town_id to teachers table if not exists
    echo "\n13. Checking teachers.town_id column...\n";
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'town_id'");
    if ($stmt->rowCount() == 0) {
        echo "   - Adding town_id column to teachers table...\n";
        $db->exec("ALTER TABLE teachers ADD COLUMN town_id INT NULL AFTER last_name, ADD FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE SET NULL");
        echo "   ✓ town_id column added\n";
    } else {
        echo "   ✓ town_id column exists\n";
    }
    
    echo "\n✅ All migrations completed successfully!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
