<?php
/**
 * Admin and Principal Role Fix Script
 * November 24, 2025
 * 
 * This script:
 * 1. Ensures the first admin is a super admin
 * 2. Sets up proper role hierarchy
 * 3. Adds admin creation capability to super admins
 */

require 'vendor/autoload.php';

$db = \App\Config\Database::getInstance()->getConnection();

echo "=== Admin and Principal Role Fix ===\n\n";

try {
    $db->beginTransaction();
    
    // 1. Check and add necessary columns
    echo "1. Checking admins table structure...\n";
    
    $stmt = $db->query("SHOW COLUMNS FROM admins LIKE 'is_super_admin'");
    if ($stmt->rowCount() == 0) {
        echo "   - Adding is_super_admin column...\n";
        $db->exec("ALTER TABLE admins ADD COLUMN is_super_admin BOOLEAN DEFAULT FALSE AFTER role");
    }
    
    // 2. Update role ENUM
    echo "\n2. Updating role ENUM...\n";
    $stmt = $db->query("SHOW COLUMNS FROM admins LIKE 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($column && strpos($column['Type'], 'super_admin') === false) {
        echo "   - Adding super_admin to role ENUM...\n";
        $db->exec("ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin', 'admin', 'principal') NOT NULL DEFAULT 'admin'");
    }
    
    // 3. Make the first admin (with no parent) a super admin
    echo "\n3. Setting first admin as super admin...\n";
    $stmt = $db->query("SELECT id, email, school_name FROM admins WHERE parent_admin_id IS NULL ORDER BY id ASC LIMIT 1");
    $firstAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($firstAdmin) {
        echo "   - Found first admin: {$firstAdmin['email']} (ID: {$firstAdmin['id']})\n";
        $db->exec("UPDATE admins SET is_super_admin = TRUE, role = 'super_admin' WHERE id = {$firstAdmin['id']}");
        echo "   - Set as super admin\n";
    } else {
        echo "   - No admin found to set as super admin\n";
    }
    
    // 4. Show admin hierarchy
    echo "\n4. Current admin hierarchy:\n";
    $stmt = $db->query("SELECT id, email, role, is_super_admin, parent_admin_id, school_name FROM admins ORDER BY id");
    while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $superFlag = $admin['is_super_admin'] ? ' [SUPER]' : '';
        $parentInfo = $admin['parent_admin_id'] ? " (parent: {$admin['parent_admin_id']})" : ' (root)';
        echo "   - ID {$admin['id']}: {$admin['email']} - Role: {$admin['role']}{$superFlag}{$parentInfo}\n";
        echo "     School: {$admin['school_name']}\n";
    }
    
    $db->commit();
    echo "\n=== Role fix completed successfully! ===\n";
    
} catch (\Exception $e) {
    $db->rollBack();
    echo "\n!!! Fix FAILED !!!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
