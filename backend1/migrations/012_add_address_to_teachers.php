<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "Adding address column to teachers table...\n";

    // Check if address column exists
    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'address'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE teachers ADD COLUMN address TEXT NULL AFTER phone");
        echo "  ✓ Added address column\n";
    } else {
        echo "  - address column already exists\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

