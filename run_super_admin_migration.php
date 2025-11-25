<?php
/**
 * Migration Script: Add Super Admin Role
 * 
 * Run this script after starting your database server:
 * php run_super_admin_migration.php
 */

require_once __DIR__ . '/backend1/src/Config/Database.php';

use App\Config\Database;

try {
    echo "================================\n";
    echo "Super Admin Role Migration\n";
    echo "================================\n\n";
    
    $db = Database::getInstance()->getConnection();
    
    echo "✓ Database connected\n\n";
    
    // Read migration SQL
    $sql = file_get_contents(__DIR__ . '/backend1/database/migrations/add_super_admin_role.sql');
    
    // Split into statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "Running migration statements...\n\n";
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                $preview = substr($statement, 0, 60);
                echo "✓ " . $preview . (strlen($statement) > 60 ? '...' : '') . "\n";
            } catch (PDOException $e) {
                // Skip if column already exists
                if (strpos($e->getMessage(), 'Duplicate') !== false || 
                    strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'duplicate') !== false) {
                    $preview = substr($statement, 0, 60);
                    echo "⚠ " . $preview . (strlen($statement) > 60 ? '...' : '') . " (already exists)\n";
                    continue;
                }
                throw $e;
            }
        }
    }
    
    echo "\n================================\n";
    echo "✅ Migration Complete!\n";
    echo "================================\n\n";
    
    // Check if migration was successful
    $stmt = $db->query("SHOW COLUMNS FROM admins LIKE 'is_super_admin'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'is_super_admin' added successfully\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM admins WHERE Field = 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($column && strpos($column['Type'], 'super_admin') !== false) {
        echo "✓ Role ENUM updated to include 'super_admin'\n";
    }
    
    // Check if first admin was upgraded
    $stmt = $db->query("SELECT COUNT(*) as count FROM admins WHERE is_super_admin = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n✓ Super admins found: " . $result['count'] . "\n";
    
    echo "\nNext steps:\n";
    echo "1. The first admin registered is now a super admin\n";
    echo "2. Super admins can create additional admin users\n";
    echo "3. Regular admins and principals cannot create admins\n\n";
    
} catch (Exception $e) {
    echo "\n================================\n";
    echo "❌ Migration Failed!\n";
    echo "================================\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    exit(1);
}
