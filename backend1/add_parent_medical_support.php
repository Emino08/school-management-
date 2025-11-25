<?php
/**
 * Add Parent Medical Record Support
 * Allows parents to add medical records for their linked children
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
    echo "=== Adding Parent Medical Record Support ===\n\n";
    
    echo "Step 1: Updating medical_records table...\n";
    
    // Check if columns exist before adding
    $columns = $db->query("SHOW COLUMNS FROM medical_records")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('added_by_parent', $columns)) {
        $db->exec("ALTER TABLE medical_records 
                   ADD COLUMN added_by_parent TINYINT(1) DEFAULT 0 AFTER medical_staff_id");
        echo "✓ Added added_by_parent column\n";
    } else {
        echo "- added_by_parent column already exists\n";
    }
    
    if (!in_array('parent_id', $columns)) {
        $db->exec("ALTER TABLE medical_records 
                   ADD COLUMN parent_id INT NULL AFTER added_by_parent,
                   ADD INDEX idx_parent_id (parent_id)");
        echo "✓ Added parent_id column\n";
    } else {
        echo "- parent_id column already exists\n";
    }
    
    if (!in_array('date_reported', $columns)) {
        $db->exec("ALTER TABLE medical_records 
                   ADD COLUMN date_reported DATE NULL AFTER status");
        echo "✓ Added date_reported column\n";
    } else {
        echo "- date_reported column already exists\n";
    }
    
    // Modify medical_staff_id to allow NULL (for parent-added records)
    $db->exec("ALTER TABLE medical_records 
               MODIFY COLUMN medical_staff_id INT NULL");
    echo "✓ Modified medical_staff_id to allow NULL\n";
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "\nChanges Made:\n";
    echo "- Parents can now add medical records for their linked children\n";
    echo "- Medical records can be added without medical staff\n";
    echo "- Parent-added records are flagged appropriately\n";
    
    echo "\nNext Steps:\n";
    echo "1. Routes need to be added for parent medical record endpoints\n";
    echo "2. Frontend needs to be updated to show medical record form for parents\n";
    echo "3. Test parent medical record functionality\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed.\n";
    exit(1);
}
