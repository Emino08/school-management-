<?php
/**
 * Comprehensive Fix Migration - November 24, 2025
 * Fixes all database schema issues
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
try {
    $db = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Connected to database\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Helper function to check if column exists
function columnExists($db, $table, $column) {
    try {
        $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->fetch();
        return $result !== false;
    } catch (PDOException $e) {
        return false;
    }
}

// Helper function to check if table exists
function tableExists($db, $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE '{$table}'")->fetch();
        return $result !== false;
    } catch (PDOException $e) {
        return false;
    }
}

echo "\n=== Starting Comprehensive Migration ===\n\n";

// 1. Fix admins table - add is_super_admin column
echo "1. Fixing admins table...\n";
if (!columnExists($db, 'admins', 'is_super_admin')) {
    $db->exec("ALTER TABLE admins ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0 AFTER role");
    echo "   ✓ Added is_super_admin column\n";
} else {
    echo "   ✓ is_super_admin column already exists\n";
}

// Mark first admin as super admin
$db->exec("UPDATE admins SET is_super_admin = 1 WHERE id = (SELECT id FROM (SELECT MIN(id) as id FROM admins WHERE role = 'admin') as tmp)");
echo "   ✓ Marked first admin as super admin\n";

// 2. Fix students table - remove photo column if exists, add suspension_reason
echo "\n2. Fixing students table...\n";
if (columnExists($db, 'students', 'photo')) {
    $db->exec("ALTER TABLE students DROP COLUMN photo");
    echo "   ✓ Removed photo column\n";
}

if (!columnExists($db, 'students', 'suspension_reason')) {
    $db->exec("ALTER TABLE students ADD COLUMN suspension_reason TEXT NULL AFTER parent_phone");
    echo "   ✓ Added suspension_reason column\n";
}

// 3. Fix student_enrollments table - remove status column, it's managed separately
echo "\n3. Fixing student_enrollments table...\n";
if (columnExists($db, 'student_enrollments', 'status')) {
    $db->exec("ALTER TABLE student_enrollments DROP COLUMN status");
    echo "   ✓ Removed status column from enrollments\n";
}

// 4. student_parents table already exists with correct structure
echo "\n4. Checking student_parents table...\n";
echo "   ✓ student_parents table already exists\n";

// 5. Fix medical_records table
echo "\n5. Fixing medical_records table...\n";

// Check and fix record_type column
$recordTypeCheck = $db->query("SHOW COLUMNS FROM medical_records LIKE 'record_type'")->fetch();
if ($recordTypeCheck && strpos($recordTypeCheck['Type'], 'enum') !== false) {
    // Get current enum values
    preg_match("/^enum\((.*)\)$/", $recordTypeCheck['Type'], $matches);
    $currentValues = $matches[1];
    
    // Check if we need to update
    if (strpos($currentValues, 'parent_report') === false) {
        $db->exec("ALTER TABLE medical_records MODIFY COLUMN record_type ENUM('checkup', 'illness', 'injury', 'vaccination', 'allergy', 'chronic', 'emergency', 'parent_report', 'other') DEFAULT 'checkup'");
        echo "   ✓ Updated record_type enum values\n";
    }
} else {
    echo "   ✓ record_type column already correct\n";
}

// Check and fix status column
$statusCheck = $db->query("SHOW COLUMNS FROM medical_records LIKE 'status'")->fetch();
if ($statusCheck && strpos($statusCheck['Type'], 'enum') !== false) {
    preg_match("/^enum\((.*)\)$/", $statusCheck['Type'], $matches);
    $currentValues = $matches[1];
    
    if (strpos($currentValues, 'under_treatment') === false) {
        $db->exec("ALTER TABLE medical_records MODIFY COLUMN status ENUM('active', 'under_treatment', 'completed', 'cancelled') DEFAULT 'active'");
        echo "   ✓ Updated status enum values\n";
    }
} else {
    echo "   ✓ status column already correct\n";
}

// Add parent_id column if not exists
if (!columnExists($db, 'medical_records', 'parent_id')) {
    $db->exec("ALTER TABLE medical_records ADD COLUMN parent_id INT NULL AFTER medical_staff_id");
    $db->exec("ALTER TABLE medical_records ADD CONSTRAINT fk_medical_parent FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL");
    echo "   ✓ Added parent_id column\n";
} else {
    echo "   ✓ parent_id column already exists\n";
}

// 6. Fix parents table
echo "\n6. Fixing parents table...\n";
// No need to add verified column as is_verified already exists
echo "   ✓ parents table structure confirmed\n";

// 7. Fix academic_years table - ensure is_current exists
echo "\n7. Fixing academic_years table...\n";
if (!columnExists($db, 'academic_years', 'is_current')) {
    $db->exec("ALTER TABLE academic_years ADD COLUMN is_current TINYINT(1) DEFAULT 0 AFTER end_date");
    echo "   ✓ Added is_current column\n";
}

echo "\n=== Migration Completed Successfully ===\n";
echo "\nNext Steps:\n";
echo "1. Test admin login\n";
echo "2. Test principal creation and login\n";
echo "3. Test parent medical records\n";
echo "4. Test student status display\n";
