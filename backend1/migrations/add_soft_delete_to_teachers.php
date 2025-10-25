<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Adding soft delete columns to teachers table...\n";
    
    // Check if deleted_at column exists
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'deleted_at'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE teachers ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");
        echo "✓ Added deleted_at column\n";
    } else {
        echo "✓ deleted_at column already exists\n";
    }
    
    // Check if is_deleted column exists
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'is_deleted'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE teachers ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
        echo "✓ Added is_deleted column\n";
    } else {
        echo "✓ is_deleted column already exists\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
