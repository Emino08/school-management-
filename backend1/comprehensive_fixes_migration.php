<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    
    echo "=== COMPREHENSIVE FIXES MIGRATION ===\n\n";
    
    // 1. Check and add is_super_admin column if not exists
    echo "1. Checking is_super_admin column...\n";
    $stmt = $db->query("SHOW COLUMNS FROM admins LIKE 'is_super_admin'");
    if ($stmt->rowCount() === 0) {
        echo "   Adding is_super_admin column...\n";
        $db->exec("ALTER TABLE admins ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0 AFTER role");
        echo "   ✓ Added is_super_admin column\n";
    } else {
        echo "   ✓ is_super_admin column already exists\n";
    }
    
    // 2. Update first admin to be super admin
    echo "\n2. Setting first admin as super admin...\n";
    $stmt = $db->query("SELECT id FROM admins WHERE parent_admin_id IS NULL ORDER BY id ASC LIMIT 1");
    $firstAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($firstAdmin) {
        $db->exec("UPDATE admins SET is_super_admin = 1 WHERE id = {$firstAdmin['id']}");
        echo "   ✓ Set admin ID {$firstAdmin['id']} as super admin\n";
    }
    
    // 3. Ensure activity_logs table exists with correct schema
    echo "\n3. Checking activity_logs table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'activity_logs'");
    if ($stmt->rowCount() === 0) {
        echo "   Creating activity_logs table...\n";
        $db->exec("CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NULL,
            user_id INT NOT NULL,
            user_type ENUM('admin', 'teacher', 'student', 'exam_officer', 'parent') NOT NULL,
            activity_type VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NULL,
            entity_id INT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            metadata JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_id (admin_id),
            INDEX idx_user (user_id, user_type),
            INDEX idx_activity_type (activity_type),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "   ✓ Created activity_logs table\n";
    } else {
        echo "   ✓ activity_logs table already exists\n";
        
        // Check if parent user_type exists
        $stmt = $db->query("SHOW COLUMNS FROM activity_logs LIKE 'user_type'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column && strpos($column['Type'], 'parent') === false) {
            echo "   Adding 'parent' to user_type enum...\n";
            $db->exec("ALTER TABLE activity_logs MODIFY COLUMN user_type ENUM('admin', 'teacher', 'student', 'exam_officer', 'parent') NOT NULL");
            echo "   ✓ Updated user_type enum\n";
        }
    }
    
    // 4. Check students table has all required columns
    echo "\n4. Checking students table columns...\n";
    $requiredColumns = ['first_name', 'last_name', 'gender'];
    foreach ($requiredColumns as $col) {
        $stmt = $db->query("SHOW COLUMNS FROM students LIKE '$col'");
        if ($stmt->rowCount() === 0) {
            echo "   ✗ Missing column: $col\n";
            if ($col === 'first_name' || $col === 'last_name') {
                $db->exec("ALTER TABLE students ADD COLUMN $col VARCHAR(100) NULL AFTER name");
                echo "   ✓ Added $col column\n";
            }
        } else {
            echo "   ✓ Column $col exists\n";
        }
    }
    
    // 5. Check if gender column is using correct enum values
    echo "\n5. Checking gender enum values...\n";
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'gender'");
    $genderCol = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($genderCol) {
        $enumValues = $genderCol['Type'];
        echo "   Current gender type: $enumValues\n";
        // Standardize to Male, Female, Other
        if (strpos($enumValues, 'Male') === false) {
            echo "   Updating gender enum to use proper casing...\n";
            $db->exec("ALTER TABLE students MODIFY COLUMN gender ENUM('Male', 'Female', 'Other') NULL");
            echo "   ✓ Updated gender enum\n";
        } else {
            echo "   ✓ Gender enum is correct\n";
        }
    }
    
    // 6. Add indexes for better performance
    echo "\n6. Adding performance indexes...\n";
    
    // Check and add index on students.admin_id
    $stmt = $db->query("SHOW INDEX FROM students WHERE Key_name = 'idx_admin_id'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE students ADD INDEX idx_admin_id (admin_id)");
        echo "   ✓ Added index on students.admin_id\n";
    } else {
        echo "   ✓ Index on students.admin_id already exists\n";
    }
    
    // Check and add indexes on student_enrollments
    $stmt = $db->query("SHOW INDEX FROM student_enrollments WHERE Key_name = 'idx_student_class'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE student_enrollments ADD INDEX idx_student_class (student_id, class_id)");
        echo "   ✓ Added composite index on student_enrollments\n";
    } else {
        echo "   ✓ Composite index on student_enrollments already exists\n";
    }
    
    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
    
} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
