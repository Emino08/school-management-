<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
use App\Config\Database;

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "Updating town_attendance table structure...\n\n";

// Add missing columns
try {
    $pdo->exec("ALTER TABLE town_attendance ADD COLUMN academic_year_id INT NULL AFTER block_id");
    echo "✓ Added academic_year_id column\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ academic_year_id column already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("ALTER TABLE town_attendance ADD COLUMN term INT NULL AFTER academic_year_id");
    echo "✓ Added term column\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ term column already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

// Update attendance_strikes table if needed
try {
    $pdo->exec("ALTER TABLE attendance_strikes ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "✓ Updated attendance_strikes table\n";
} catch (Exception $e) {
    echo "⚠ attendance_strikes: " . $e->getMessage() . "\n";
}

echo "\nVerifying table structure...\n";
$stmt = $pdo->query("DESCRIBE town_attendance");
echo "town_attendance columns:\n";
while($row = $stmt->fetch()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\n✓ Done!\n";
