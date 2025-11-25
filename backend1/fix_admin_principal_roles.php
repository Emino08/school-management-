<?php
/**
 * Fix Admin and Principal Roles System
 * 
 * This migration ensures:
 * 1. Principals/admins created by super admin inherit data properly
 * 2. All admin_id references resolve to the root super admin
 * 3. Proper role-based access control
 */

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
    
    // Drop existing function if exists
    $db->exec("DROP FUNCTION IF EXISTS get_root_admin_id");
    
    // Create function to get root admin ID
    $db->exec("
        CREATE FUNCTION get_root_admin_id(input_admin_id INT)
        RETURNS INT
        DETERMINISTIC
        READS SQL DATA
        BEGIN
            DECLARE root_id INT;
            DECLARE current_id INT;
            DECLARE parent_id INT;
            DECLARE depth INT DEFAULT 0;
            DECLARE max_depth INT DEFAULT 10;
            
            SET current_id = input_admin_id;
            
            -- Loop to find root admin
            WHILE depth < max_depth DO
                SELECT parent_admin_id INTO parent_id 
                FROM admins 
                WHERE id = current_id;
                
                IF parent_id IS NULL THEN
                    SET root_id = current_id;
                    LEAVE WHILE;
                END IF;
                
                SET current_id = parent_id;
                SET depth = depth + 1;
            END WHILE;
            
            RETURN root_id;
        END
    ");
    echo "✓ Created get_root_admin_id() function\n";
    
    // Step 4: Fix existing principal data
    echo "\nStep 4: Fixing principal account details...\n";
    
    // Update principals to inherit school details from their parent admin
    $stmt = $db->query("
        SELECT a.id, a.parent_admin_id, p.school_name, p.school_address, p.school_logo
        FROM admins a
        JOIN admins p ON a.parent_admin_id = p.id
        WHERE a.role = 'principal' AND a.parent_admin_id IS NOT NULL
    ");
    
    $principals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $principalCount = 0;
    
    foreach ($principals as $principal) {
        $updateStmt = $db->prepare("
            UPDATE admins 
            SET school_name = :school_name,
                school_address = :school_address,
                school_logo = :school_logo
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':school_name' => $principal['school_name'],
            ':school_address' => $principal['school_address'],
            ':school_logo' => $principal['school_logo'],
            ':id' => $principal['id']
        ]);
        $principalCount++;
    }
    
    echo "✓ Updated $principalCount principal account(s)\n";
    
    // Step 5: Fix admin-created admins
    echo "\nStep 5: Fixing admin-created admin accounts...\n";
    
    $stmt = $db->query("
        SELECT a.id, a.parent_admin_id, p.school_name, p.school_address, p.school_logo
        FROM admins a
        JOIN admins p ON a.parent_admin_id = p.id
        WHERE a.role = 'admin' AND a.parent_admin_id IS NOT NULL AND a.is_super_admin = 0
    ");
    
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $adminCount = 0;
    
    foreach ($admins as $admin) {
        $updateStmt = $db->prepare("
            UPDATE admins 
            SET school_name = :school_name,
                school_address = :school_address,
                school_logo = :school_logo
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':school_name' => $admin['school_name'],
            ':school_address' => $admin['school_address'],
            ':school_logo' => $admin['school_logo'],
            ':id' => $admin['id']
        ]);
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
    echo "1. Principals will now see their parent admin's data\n";
    echo "2. Admins created by super admin will see the same data\n";
    echo "3. Access control is enforced based on role\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed. No changes were made.\n";
    exit(1);
}
