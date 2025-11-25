<?php
/**
 * Fix medical_records table - Add missing record_type values
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

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== Fixing medical_records Table ===\n\n";
    
    // Check current table structure
    $stmt = $db->query("SHOW COLUMNS FROM medical_records LIKE 'record_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Current record_type column: " . $column['Type'] . "\n\n";
        
        // Modify the record_type column to accept the correct values
        $db->exec("
            ALTER TABLE medical_records 
            MODIFY COLUMN record_type ENUM('allergy', 'condition', 'medication', 'vaccination', 'checkup', 'injury', 'illness') 
            DEFAULT 'condition'
        ");
        echo "✓ Updated record_type column to include all types\n";
        
        // Fix status column to include parent_reported
        $stmt = $db->query("SHOW COLUMNS FROM medical_records LIKE 'status'");
        $statusCol = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($statusCol) {
            echo "Current status column: " . $statusCol['Type'] . "\n";
            $db->exec("
                ALTER TABLE medical_records 
                MODIFY COLUMN status ENUM('active', 'pending', 'completed', 'cancelled', 'under_treatment', 'parent_reported') 
                DEFAULT 'active'
            ");
            echo "✓ Updated status column to include parent_reported\n";
        }
        
        // Check if other required columns exist
        $columns = $db->query("SHOW COLUMNS FROM medical_records")->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'added_by_parent' => "ADD COLUMN added_by_parent TINYINT(1) DEFAULT 0 AFTER status",
            'parent_id' => "ADD COLUMN parent_id INT NULL AFTER added_by_parent",
            'date_reported' => "ADD COLUMN date_reported DATE NULL AFTER parent_id",
            'next_checkup_date' => "ADD COLUMN next_checkup_date DATE NULL AFTER treatment"
        ];
        
        foreach ($requiredColumns as $colName => $alterSql) {
            if (!in_array($colName, $columns)) {
                $db->exec("ALTER TABLE medical_records " . $alterSql);
                echo "✓ Added column: $colName\n";
            }
        }
        
        // Ensure severity column is correct
        $stmt = $db->query("SHOW COLUMNS FROM medical_records LIKE 'severity'");
        $severityCol = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($severityCol && strpos($severityCol['Type'], 'low') === false) {
            $db->exec("
                ALTER TABLE medical_records 
                MODIFY COLUMN severity ENUM('low', 'medium', 'high') DEFAULT 'low'
            ");
            echo "✓ Updated severity column\n";
        }
        
        echo "\n=== Migration Completed Successfully ===\n";
        echo "\nTable Structure Updated:\n";
        echo "- record_type: allergy, condition, medication, vaccination, checkup, injury, illness\n";
        echo "- severity: low, medium, high\n";
        echo "- status: active, pending, completed, cancelled, under_treatment, parent_reported\n";
        echo "- added_by_parent: tracks if parent added the record\n";
        echo "- parent_id: links to parent who added it\n";
        echo "- date_reported: when parent reported it\n";
        echo "- next_checkup_date: for follow-up appointments\n";
        
    } else {
        echo "ERROR: record_type column not found!\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
