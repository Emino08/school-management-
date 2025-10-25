<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Checking teachers table structure:\n\n";
    
    $stmt = $db->query("DESCRIBE teachers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo sprintf("%-20s %-20s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
    }
    
    echo "\n✅ Verification complete!\n";
    
    // Check if soft delete columns exist
    $hasIsDeleted = false;
    $hasDeletedAt = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'is_deleted') $hasIsDeleted = true;
        if ($col['Field'] === 'deleted_at') $hasDeletedAt = true;
    }
    
    echo "\nSoft Delete Columns:\n";
    echo ($hasIsDeleted ? "✓" : "✗") . " is_deleted\n";
    echo ($hasDeletedAt ? "✓" : "✗") . " deleted_at\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
