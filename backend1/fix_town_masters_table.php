<?php
/**
 * Fix Town Masters Table - Add Missing Columns
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

try {
    echo "Fixing Town Masters Table...\n\n";
    
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Add missing columns to town_masters
    echo "Adding missing columns to town_masters...\n";
    
    try {
        $pdo->exec("ALTER TABLE `town_masters` ADD COLUMN `assigned_by` INT NOT NULL AFTER `teacher_id`");
        echo "✓ Added assigned_by column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ assigned_by column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE `town_masters` ADD COLUMN `is_active` BOOLEAN DEFAULT TRUE AFTER `assigned_by`");
        echo "✓ Added is_active column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ is_active column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE `town_masters` ADD COLUMN `deactivated_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_active`");
        echo "✓ Added deactivated_at column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ deactivated_at column already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✓ Town Masters table fixed successfully!\n\n";
    
    // Show updated structure
    echo "Updated Town Masters Table Structure:\n";
    $stmt = $pdo->query('DESCRIBE town_masters');
    while($row = $stmt->fetch()) {
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ Fix failed: " . $e->getMessage() . "\n";
    exit(1);
}
