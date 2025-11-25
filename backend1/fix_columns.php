<?php
// Fix Column Issues Migration

$host = 'localhost';
$port = 4306;
$dbname = 'school_management';
$username = 'root';
$password = '1212';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database\n\n";
    echo "Fixing column issues...\n";
    echo str_repeat("=", 60) . "\n";
    
    // 1. Fix medical_records enum values
    echo "\n1. Fixing medical_records enum values...\n";
    try {
        $pdo->exec("ALTER TABLE medical_records MODIFY COLUMN record_type ENUM('illness', 'injury', 'checkup', 'vaccination', 'allergy', 'chronic', 'diagnosis', 'treatment', 'emergency') DEFAULT 'checkup'");
        echo "   ✓ Updated record_type column\n";
    } catch (PDOException $e) {
        echo "   ⚠ record_type: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE medical_records MODIFY COLUMN status ENUM('active', 'resolved', 'ongoing', 'referred', 'under_treatment', 'completed') DEFAULT 'active'");
        echo "   ✓ Updated status column\n";
    } catch (PDOException $e) {
        echo "   ⚠ status: " . $e->getMessage() . "\n";
    }
    
    // 2. Add parent_id to medical_records
    echo "\n2. Adding parent columns to medical_records...\n";
    try {
        $pdo->exec("ALTER TABLE medical_records ADD COLUMN parent_id INT NULL AFTER medical_staff_id");
        echo "   ✓ Added parent_id column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "   ⚠ parent_id: " . $e->getMessage() . "\n";
        } else {
            echo "   ✓ parent_id already exists\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE medical_records ADD COLUMN added_by ENUM('parent', 'medical_staff', 'admin') DEFAULT 'medical_staff' AFTER parent_id");
        echo "   ✓ Added added_by column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "   ⚠ added_by: " . $e->getMessage() . "\n";
        } else {
            echo "   ✓ added_by already exists\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE medical_records ADD CONSTRAINT fk_medical_records_parent FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL");
        echo "   ✓ Added foreign key constraint\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') === false && strpos($e->getMessage(), 'already exists') === false) {
            echo "   ⚠ foreign key: " . substr($e->getMessage(), 0, 80) . "...\n";
        } else {
            echo "   ✓ Foreign key already exists\n";
        }
    }
    
    // 3. Add status to students
    echo "\n3. Adding status column to students...\n";
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN status ENUM('active', 'inactive', 'graduated', 'transferred') DEFAULT 'active' AFTER suspension_status");
        echo "   ✓ Added status column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "   ⚠ status: " . $e->getMessage() . "\n";
        } else {
            echo "   ✓ status already exists\n";
        }
    }
    
    // 4. Add is_super_admin to admin_users
    echo "\n4. Adding is_super_admin to admin_users...\n";
    try {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN is_super_admin BOOLEAN DEFAULT FALSE AFTER role");
        echo "   ✓ Added is_super_admin column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "   ⚠ is_super_admin: " . $e->getMessage() . "\n";
        } else {
            echo "   ✓ is_super_admin already exists\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "\n✅ Column fixes completed!\n\n";
    
    // Verify
    echo "Verification:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '$dbname' AND table_name = 'medical_records' AND column_name = 'parent_id'");
    echo ($stmt->fetch()['cnt'] > 0 ? "✓" : "✗") . " medical_records.parent_id\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '$dbname' AND table_name = 'medical_records' AND column_name = 'added_by'");
    echo ($stmt->fetch()['cnt'] > 0 ? "✓" : "✗") . " medical_records.added_by\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '$dbname' AND table_name = 'students' AND column_name = 'status'");
    echo ($stmt->fetch()['cnt'] > 0 ? "✓" : "✗") . " students.status\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '$dbname' AND table_name = 'admin_users' AND column_name = 'is_super_admin'");
    echo ($stmt->fetch()['cnt'] > 0 ? "✓" : "✗") . " admin_users.is_super_admin\n";
    
    echo "\n✅ All done!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
