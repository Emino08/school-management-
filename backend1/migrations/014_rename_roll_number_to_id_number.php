<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Renaming students.roll_number to id_number...\n";

    // Check if id_number already exists
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'id_number'");
    if ($stmt->rowCount() === 0) {
        // Ensure roll_number exists before renaming
        $check = $db->query("SHOW COLUMNS FROM students LIKE 'roll_number'");
        if ($check->rowCount() === 0) {
            echo "  - Neither roll_number nor id_number found; skipping column rename.\n";
        } else {
            try {
                $db->exec("ALTER TABLE students CHANGE COLUMN roll_number id_number VARCHAR(50) NOT NULL");
                echo "  ✓ Renamed column to id_number\n";
            } catch (Exception $e) {
                echo "  - Direct rename failed ({$e->getMessage()}), attempting add+copy...\n";
                // Fallback: add, copy, drop
                $db->exec("ALTER TABLE students ADD COLUMN id_number VARCHAR(50) NULL AFTER admin_id");
                $db->exec("UPDATE students SET id_number = roll_number WHERE id_number IS NULL");
                // Drop old unique index if present (on roll_number)
                try { $db->exec("ALTER TABLE students DROP INDEX unique_roll_per_school"); } catch (Exception $e2) {}
                // Create new unique index on (admin_id, id_number)
                try { $db->exec("ALTER TABLE students ADD UNIQUE KEY unique_id_number_per_school (admin_id, id_number)"); } catch (Exception $e3) {}
                // Make id_number NOT NULL
                $db->exec("ALTER TABLE students MODIFY COLUMN id_number VARCHAR(50) NOT NULL");
                // Finally drop roll_number
                try { $db->exec("ALTER TABLE students DROP COLUMN roll_number"); } catch (Exception $e4) { echo "  - Could not drop roll_number: {$e4->getMessage()}\n"; }
                echo "  ✓ Added id_number and migrated data\n";
            }
        }
    } else {
        echo "  - id_number column already exists\n";
    }

    // Drop old unique index if present
    try {
        $db->exec("ALTER TABLE students DROP INDEX unique_roll_per_school");
        echo "  ✓ Dropped index unique_roll_per_school\n";
    } catch (\Exception $e) {
        echo "  - Could not drop unique_roll_per_school (may not exist)\n";
    }

    // Add new unique index on (admin_id, id_number)
    try {
        $db->exec("ALTER TABLE students ADD UNIQUE KEY unique_id_number_per_school (admin_id, id_number)");
        echo "  ✓ Added unique_id_number_per_school\n";
    } catch (\Exception $e) {
        echo "  - unique_id_number_per_school may already exist\n";
    }

    echo "Done.\n";
} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
