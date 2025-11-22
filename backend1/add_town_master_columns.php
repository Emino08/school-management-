<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
use App\Config\Database;

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "Adding town master columns to teachers table...\n";

try {
    $pdo->exec("ALTER TABLE teachers ADD COLUMN is_town_master BOOLEAN DEFAULT FALSE");
    echo "✓ Added is_town_master column\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ is_town_master column already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("ALTER TABLE teachers ADD COLUMN town_master_of INT NULL DEFAULT NULL");
    echo "✓ Added town_master_of column\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ town_master_of column already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\nChecking teachers table structure...\n";
$stmt = $pdo->query("DESCRIBE teachers");
$hasIsTownMaster = false;
$hasTownMasterOf = false;

while($row = $stmt->fetch()) {
    if ($row['Field'] == 'is_town_master') $hasIsTownMaster = true;
    if ($row['Field'] == 'town_master_of') $hasTownMasterOf = true;
}

echo "is_town_master column: " . ($hasIsTownMaster ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "town_master_of column: " . ($hasTownMasterOf ? "✓ EXISTS" : "✗ MISSING") . "\n";

echo "\n✓ Done!\n";
