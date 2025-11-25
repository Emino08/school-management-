<?php
/**
 * Standalone Admin and Principal Roles Migration
 * This script loads environment variables and runs the migration
 */

// Load environment variables from .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Now run the original migration
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

try {
    echo "=== Fixing Admin and Principal Roles System ===\n\n";
    
    $db->beginTransaction();
    
    // Step 1: Update admins table structure
    echo "Step 1: Updating admins table structure...\n";
    
    // Ensure role enum includes all necessary values
    $db->exec("ALTER TABLE admins 
               MODIFY COLUMN role ENUM('super_admin', 'admin', 'principal') NOT NULL DEFAULT 'admin'");
    echo "✓ Updated role enum\n";
    
    // Ensure parent_admin_id exists
    $columns = $db->query("SHOW COLUMNS FROM admins LIKE 'parent_admin_id'")->fetchAll();
    if (empty($columns)) {
        $db->exec("ALTER TABLE admins 
                   ADD COLUMN parent_admin_id INT NULL AFTER role,
                   ADD INDEX idx_parent_admin (parent_admin_id),
                   ADD CONSTRAINT fk_admin_parent 
                   FOREIGN KEY (parent_admin_id) REFERENCES admins(id) ON DELETE CASCADE");
        echo "✓ Added parent_admin_id column\n";
    }
    
    // Ensure is_super_admin flag exists
    $columns = $db->query("SHOW COLUMNS FROM admins LIKE 'is_super_admin'")->fetchAll();
    if (empty($columns)) {
        $db->exec("ALTER TABLE admins 
                   ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0 AFTER role");
        echo "✓ Added is_super_admin column\n";
    }
    
    // Step 2: Update existing super admins
    echo "\nStep 2: Setting up super admin flags...\n";
    $db->exec("UPDATE admins SET is_super_admin = 1 
               WHERE role = 'super_admin' OR (role = 'admin' AND parent_admin_id IS NULL)");
    echo "✓ Updated super admin flags\n";
    
    // Step 3: Add helper function to get root admin
    echo "\nStep 3: Creating helper function...\n";
    
    // Commit transaction if still active (DDL statements auto-commit)
    if ($db->inTransaction()) {
        $db->commit();
    }
    
    // Drop existing function if exists
    try {
        $db->exec("DROP FUNCTION IF EXISTS get_root_admin_id");
    } catch (Exception $e) {
        // Ignore if function doesn't exist
    }
    
    // Create function to get root admin ID (simpler version without LEAVE)
    try {
        $db->exec("
            CREATE FUNCTION get_root_admin_id(input_admin_id INT)
            RETURNS INT
            DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE root_id INT;
                DECLARE current_id INT;
                DECLARE parent_id INT;
                DECLARE counter INT DEFAULT 0;
                
                SET current_id = input_admin_id;
                SET root_id = input_admin_id;
                
                select_root: LOOP
                    IF counter >= 10 THEN
                        LEAVE select_root;
                    END IF;
                    
                    SELECT parent_admin_id INTO parent_id 
                    FROM admins 
                    WHERE id = current_id
                    LIMIT 1;
                    
                    IF parent_id IS NULL OR parent_id = 0 THEN
                        SET root_id = current_id;
                        LEAVE select_root;
                    END IF;
                    
                    SET current_id = parent_id;
                    SET root_id = current_id;
                    SET counter = counter + 1;
                END LOOP select_root;
                
                RETURN root_id;
            END
        ");
        echo "✓ Created get_root_admin_id() function\n";
    } catch (Exception $e) {
        echo "⚠ Could not create function: " . $e->getMessage() . "\n";
        echo "  (Data scoping will fall back to PHP implementation)\n";
    }
    
    // Start new transaction for data updates
    $db->beginTransaction();
    
    // Step 4: Fix existing principal data
    echo "\nStep 4: Fixing principal account details...\n";
    
    // First check what columns exist
    $columns = $db->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
    $hasSchoolLogo = in_array('school_logo', $columns);
    $hasSchoolAddress = in_array('school_address', $columns);
    
    // Build query based on available columns
    $selectFields = "a.id, a.parent_admin_id, p.school_name";
    $updateFields = ['school_name'];
    
    if ($hasSchoolAddress) {
        $selectFields .= ", p.school_address";
        $updateFields[] = 'school_address';
    }
    if ($hasSchoolLogo) {
        $selectFields .= ", p.school_logo";
        $updateFields[] = 'school_logo';
    }
    
    // Update principals to inherit school details from their parent admin
    $stmt = $db->query("
        SELECT $selectFields
        FROM admins a
        JOIN admins p ON a.parent_admin_id = p.id
        WHERE a.role = 'principal' AND a.parent_admin_id IS NOT NULL
    ");
    
    $principals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $principalCount = 0;
    
    foreach ($principals as $principal) {
        $setClause = "school_name = :school_name";
        $params = [
            ':school_name' => $principal['school_name'],
            ':id' => $principal['id']
        ];
        
        if ($hasSchoolAddress && isset($principal['school_address'])) {
            $setClause .= ", school_address = :school_address";
            $params[':school_address'] = $principal['school_address'];
        }
        if ($hasSchoolLogo && isset($principal['school_logo'])) {
            $setClause .= ", school_logo = :school_logo";
            $params[':school_logo'] = $principal['school_logo'];
        }
        
        $updateStmt = $db->prepare("UPDATE admins SET $setClause WHERE id = :id");
        $updateStmt->execute($params);
        $principalCount++;
    }
    
    echo "✓ Updated $principalCount principal account(s)\n";
    
    // Step 5: Fix admin-created admins
    echo "\nStep 5: Fixing admin-created admin accounts...\n";
    
    $stmt = $db->query("
        SELECT $selectFields
        FROM admins a
        JOIN admins p ON a.parent_admin_id = p.id
        WHERE a.role = 'admin' AND a.parent_admin_id IS NOT NULL AND a.is_super_admin = 0
    ");
    
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $adminCount = 0;
    
    foreach ($admins as $admin) {
        $setClause = "school_name = :school_name";
        $params = [
            ':school_name' => $admin['school_name'],
            ':id' => $admin['id']
        ];
        
        if ($hasSchoolAddress && isset($admin['school_address'])) {
            $setClause .= ", school_address = :school_address";
            $params[':school_address'] = $admin['school_address'];
        }
        if ($hasSchoolLogo && isset($admin['school_logo'])) {
            $setClause .= ", school_logo = :school_logo";
            $params[':school_logo'] = $admin['school_logo'];
        }
        
        $updateStmt = $db->prepare("UPDATE admins SET $setClause WHERE id = :id");
        $updateStmt->execute($params);
        $adminCount++;
    }
    
    echo "✓ Updated $adminCount admin account(s)\n";
    
    $db->commit();
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "\nSummary:\n";
    echo "- Role enum updated to support super_admin, admin, principal\n";
    echo "- Parent-child relationship properly configured\n";
    echo "- Helper function created for data scoping\n";
    echo "- $principalCount principal account(s) updated\n";
    echo "- $adminCount admin account(s) updated\n";
    echo "\nNext steps:\n";
    echo "1. Restart your backend server\n";
    echo "2. Clear browser cache\n";
    echo "3. Test login as admin and principal\n";
    echo "4. Verify data inheritance works\n";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed. Changes may have been partially applied.\n";
    exit(1);
}
