<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "Adding promotion average columns to academic_years table...\n";

    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM academic_years LIKE 'promotion_average'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE academic_years ADD COLUMN promotion_average DECIMAL(5,2) DEFAULT 50.00 COMMENT 'Minimum average to be promoted to next class'");
        echo "✓ Added promotion_average column\n";
    } else {
        echo "ℹ promotion_average column already exists\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM academic_years LIKE 'repeat_average'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE academic_years ADD COLUMN repeat_average DECIMAL(5,2) DEFAULT 40.00 COMMENT 'Students between this and promotion average must repeat'");
        echo "✓ Added repeat_average column\n";
    } else {
        echo "ℹ repeat_average column already exists\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM academic_years LIKE 'drop_average'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE academic_years ADD COLUMN drop_average DECIMAL(5,2) DEFAULT 30.00 COMMENT 'Students below this threshold are dropped'");
        echo "✓ Added drop_average column\n";
    } else {
        echo "ℹ drop_average column already exists\n";
    }

    echo "\n✓ Successfully added promotion average columns\n";

    // Show the columns
    echo "\nVerifying columns:\n";
    $stmt = $db->query("SHOW COLUMNS FROM academic_years WHERE Field LIKE '%average'");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']} (Default: {$col['Default']})\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
