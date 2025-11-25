<?php
/**
 * Fix Parent Status - Set all parents to active
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

use App\Config\Database;

$db = Database::getInstance()->getConnection();

try {
    echo "=== Fixing Parent Status ===\n\n";
    
    // Check if status column exists in parents table
    $columns = $db->query("SHOW COLUMNS FROM parents")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('status', $columns)) {
        // Update all parents to active status
        $stmt = $db->prepare("UPDATE parents SET status = 'active' WHERE status IS NULL OR status = '' OR status = 'suspended'");
        $stmt->execute();
        $updated = $stmt->rowCount();
        
        echo "✓ Updated $updated parent account(s) to 'active' status\n";
    } else {
        // Add status column if it doesn't exist
        $db->exec("ALTER TABLE parents ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER notification_preference");
        echo "✓ Added status column to parents table\n";
        
        // Set all existing parents to active
        $stmt = $db->exec("UPDATE parents SET status = 'active' WHERE status IS NULL");
        echo "✓ Set all existing parents to 'active' status\n";
    }
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "\nChanges Made:\n";
    echo "- All parent accounts now have 'active' status\n";
    echo "- Parent dashboard will no longer show 'suspended'\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed.\n";
    exit(1);
}
