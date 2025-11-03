<?php

// Migration: add capacity and placement_min_average to classes

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment for DB connection
if (class_exists('Dotenv\\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$db = \App\Config\Database::getInstance()->getConnection();

echo "Adding class capacity and placement thresholds...\n";

try {
    $stmt = $db->query("SHOW COLUMNS FROM classes LIKE 'capacity'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE classes ADD COLUMN capacity INT NULL AFTER section");
        echo "  ✓ Added column: capacity\n";
    } else {
        echo "  - Column exists: capacity\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM classes LIKE 'placement_min_average'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE classes ADD COLUMN placement_min_average DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER capacity");
        echo "  ✓ Added column: placement_min_average\n";
    } else {
        echo "  - Column exists: placement_min_average\n";
    }

    echo "Migration complete.\n";
} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
