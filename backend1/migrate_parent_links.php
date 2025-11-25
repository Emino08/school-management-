<?php
/**
 * Fix parent_student_links table migration
 * Handles the relationship field mismatch
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
    echo "=== Migrating Old Parent-Student Links ===\n\n";

    // Check if old table exists
    $stmt = $db->query("SHOW TABLES LIKE 'parent_student_links'");
    $oldTableExists = $stmt->fetch();
    
    if (!$oldTableExists) {
        echo "No old parent_student_links table found. Nothing to migrate.\n";
        exit(0);
    }

    // Check if there's data
    $stmt = $db->query("SELECT COUNT(*) as count FROM parent_student_links");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "Old table is empty. Dropping it...\n";
        $db->exec("DROP TABLE parent_student_links");
        echo "✓ Old table dropped successfully\n";
        exit(0);
    }

    echo "Found $count record(s) in old table. Migrating...\n\n";

    // Get all records from old table
    $stmt = $db->query("SELECT * FROM parent_student_links");
    $oldRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $migrated = 0;
    $skipped = 0;

    foreach ($oldRecords as $record) {
        // Map relationship field - default to 'mother' if not standard
        $relationship = in_array($record['relationship'], ['mother', 'father', 'guardian']) 
            ? $record['relationship'] 
            : 'mother';

        try {
            // Insert into new table
            $stmt = $db->prepare("
                INSERT INTO student_parents (parent_id, student_id, relationship, verified, verified_at, created_at)
                VALUES (:parent_id, :student_id, :relationship, :verified, :verified_at, :created_at)
                ON DUPLICATE KEY UPDATE 
                    verified = VALUES(verified),
                    verified_at = VALUES(verified_at),
                    relationship = VALUES(relationship)
            ");
            
            $stmt->execute([
                ':parent_id' => $record['parent_id'],
                ':student_id' => $record['student_id'],
                ':relationship' => $relationship,
                ':verified' => $record['verified'] ?? 1,
                ':verified_at' => $record['verified_at'] ?? date('Y-m-d H:i:s'),
                ':created_at' => $record['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            $migrated++;
            echo "✓ Migrated: Parent {$record['parent_id']} -> Student {$record['student_id']}\n";
        } catch (Exception $e) {
            $skipped++;
            echo "✗ Skipped: Parent {$record['parent_id']} -> Student {$record['student_id']} - " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== Migration Complete ===\n";
    echo "Migrated: $migrated record(s)\n";
    echo "Skipped: $skipped record(s)\n";

    if ($migrated > 0) {
        echo "\nDo you want to drop the old parent_student_links table? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) == 'y') {
            $db->exec("DROP TABLE parent_student_links");
            echo "✓ Old table dropped successfully\n";
        } else {
            echo "Old table kept. You can manually drop it later with:\n";
            echo "DROP TABLE parent_student_links;\n";
        }
        fclose($handle);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
