<?php
/**
 * Comprehensive Fix Migration - November 24, 2025
 * 
 * This migration fixes:
 * 1. Student table schema (photo, status columns)
 * 2. Student_parents table creation
 * 3. Medical records table (record_type, status ENUM values)
 * 4. School_settings table (is_current column)
 * 5. Admins table (is_super_admin, role columns)
 * 6. Parents table (status column)
 */

require 'vendor/autoload.php';

$db = \App\Config\Database::getInstance()->getConnection();

echo "=== Starting Comprehensive Fix Migration ===\n\n";

try {
    $db->beginTransaction();
    
    // ==========================================
    // 1. FIX STUDENTS TABLE
    // ==========================================
    echo "1. Fixing students table...\n";
    
    // Check if photo column exists
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'photo'");
    if ($stmt->rowCount() == 0) {
        echo "   - Adding photo column to students...\n";
        $db->exec("ALTER TABLE students ADD COLUMN photo VARCHAR(255) NULL AFTER parent_phone");
    } else {
        echo "   - photo column already exists\n";
    }
    
    // Remove status column if it exists (students don't have status, enrollments do)
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        echo "   - Removing status column from students (moved to enrollments)...\n";
        $db->exec("ALTER TABLE students DROP COLUMN status");
    } else {
        echo "   - status column already removed\n";
    }
    
    // ==========================================
    // 2. CREATE/FIX STUDENT_PARENTS TABLE
    // ==========================================
    echo "\n2. Creating student_parents table...\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS student_parents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        parent_id INT NOT NULL,
        relationship ENUM('father', 'mother', 'guardian', 'other') DEFAULT 'father',
        is_primary BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
        UNIQUE KEY unique_student_parent (student_id, parent_id),
        INDEX idx_student (student_id),
        INDEX idx_parent (parent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "   - student_parents table created/verified\n";
    
    // ==========================================
    // 3. FIX MEDICAL_RECORDS TABLE
    // ==========================================
    echo "\n3. Fixing medical_records table...\n";
    
    // Check and fix record_type ENUM
    $stmt = $db->query("SHOW COLUMNS FROM medical_records LIKE 'record_type'");
    if ($stmt->rowCount() > 0) {
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if (strpos($column['Type'], 'vaccination') === false) {
            echo "   - Updating record_type ENUM values...\n";
            $db->exec("ALTER TABLE medical_records MODIFY COLUMN record_type ENUM('checkup', 'illness', 'injury', 'vaccination', 'allergy', 'medication', 'other') NOT NULL");
        } else {
            echo "   - record_type ENUM already correct\n";
        }
    }
    
    // Check and fix status ENUM
    $stmt = $db->query("SHOW COLUMNS FROM medical_records LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if (strpos($column['Type'], 'ongoing') === false) {
            echo "   - Updating status ENUM values...\n";
            $db->exec("ALTER TABLE medical_records MODIFY COLUMN status ENUM('active', 'resolved', 'ongoing', 'archived') DEFAULT 'active'");
        } else {
            echo "   - status ENUM already correct\n";
        }
    }
    
    // ==========================================
    // 4. FIX SCHOOL_SETTINGS/ACADEMIC_YEARS
    // ==========================================
    echo "\n4. Fixing school_settings and academic_years tables...\n";
    
    // Check if is_current exists in academic_years (it should)
    $stmt = $db->query("SHOW COLUMNS FROM academic_years LIKE 'is_current'");
    if ($stmt->rowCount() == 0) {
        echo "   - Adding is_current to academic_years...\n";
        $db->exec("ALTER TABLE academic_years ADD COLUMN is_current BOOLEAN DEFAULT FALSE AFTER end_date");
    } else {
        echo "   - is_current column exists in academic_years\n";
    }
    
    // Ensure status column exists in academic_years
    $stmt = $db->query("SHOW COLUMNS FROM academic_years LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        echo "   - Adding status to academic_years...\n";
        $db->exec("ALTER TABLE academic_years ADD COLUMN status ENUM('active', 'completed', 'archived') DEFAULT 'active' AFTER is_current");
    } else {
        echo "   - status column exists in academic_years\n";
    }
    
    // ==========================================
    // 5. FIX ADMINS TABLE FOR SUPER ADMIN
    // ==========================================
    echo "\n5. Fixing admins table for super admin support...\n";
    
    // Add is_super_admin column
    $stmt = $db->query("SHOW COLUMNS FROM admins LIKE 'is_super_admin'");
    if ($stmt->rowCount() == 0) {
        echo "   - Adding is_super_admin column...\n";
        $db->exec("ALTER TABLE admins ADD COLUMN is_super_admin BOOLEAN DEFAULT FALSE AFTER role");
    } else {
        echo "   - is_super_admin column already exists\n";
    }
    
    // Update role ENUM to include proper values
    $stmt = $db->query("SHOW COLUMNS FROM admins LIKE 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($column['Type'], 'principal') === false) {
        echo "   - Updating role ENUM to include principal...\n";
        $db->exec("ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin', 'admin', 'principal') NOT NULL DEFAULT 'admin'");
    } else {
        echo "   - role ENUM already correct\n";
    }
    
    // Make the first admin (lowest ID with no parent) a super admin
    echo "   - Setting first admin as super admin...\n";
    $db->exec("UPDATE admins SET is_super_admin = TRUE, role = 'super_admin' 
               WHERE parent_admin_id IS NULL 
               ORDER BY id ASC LIMIT 1");
    
    // ==========================================
    // 6. FIX PARENTS TABLE
    // ==========================================
    echo "\n6. Fixing parents table...\n";
    
    // Remove status column from parents if it exists
    $stmt = $db->query("SHOW COLUMNS FROM parents LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        echo "   - Removing status column from parents (not needed)...\n";
        $db->exec("ALTER TABLE parents DROP COLUMN status");
    } else {
        echo "   - status column already removed from parents\n";
    }
    
    // Ensure verification_status exists
    $stmt = $db->query("SHOW COLUMNS FROM parents LIKE 'verification_status'");
    if ($stmt->rowCount() == 0) {
        echo "   - Adding verification_status column...\n";
        $db->exec("ALTER TABLE parents ADD COLUMN verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' AFTER email");
    } else {
        echo "   - verification_status column exists\n";
    }
    
    // ==========================================
    // 7. FIX STUDENT_ENROLLMENTS TABLE
    // ==========================================
    echo "\n7. Fixing student_enrollments table...\n";
    
    // Ensure status column exists with correct values
    $stmt = $db->query("SHOW COLUMNS FROM student_enrollments LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if (strpos($column['Type'], 'promoted') === false) {
            echo "   - Updating status ENUM in student_enrollments...\n";
            $db->exec("ALTER TABLE student_enrollments MODIFY COLUMN status ENUM('active', 'promoted', 'failed', 'transferred', 'graduated', 'suspended') DEFAULT 'active'");
        } else {
            echo "   - status ENUM in student_enrollments already correct\n";
        }
    }
    
    $db->commit();
    echo "\n=== Migration completed successfully! ===\n";
    
} catch (\Exception $e) {
    $db->rollBack();
    echo "\n!!! Migration FAILED !!!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
