<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;

echo "\n=== TESTING DATABASE CONNECTION ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "✅ Database connected\n\n";
    
    // Check which database we're connected to
    $stmt = $db->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();
    echo "Current database: $currentDb\n\n";
    
    // List tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables: " . count($tables) . "\n";
    
    // Check for admins table
    if (in_array('admins', $tables)) {
        echo "✅ admins table EXISTS\n";
        
        $count = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        echo "   Admin records: $count\n\n";
        
        // Show admin records
        if ($count > 0) {
            $stmt = $db->query("SELECT id, email, name, role FROM admins");
            echo "Admin accounts:\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "  - ID: {$row['id']}, Email: {$row['email']}, Name: {$row['name']}, Role: {$row['role']}\n";
            }
        }
    } else {
        echo "❌ admins table DOES NOT EXIST\n";
        echo "\nAvailable tables:\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }
    
    echo "\n✅ Connection test complete\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
