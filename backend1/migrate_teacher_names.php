<?php

require __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "=== Adding first_name and last_name columns to teachers table ===" . PHP_EOL;

    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM teachers WHERE Field IN ('first_name', 'last_name')");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('first_name', $existing) && in_array('last_name', $existing)) {
        echo "✓ Columns already exist!" . PHP_EOL;
    } else {
        // Add first_name column
        if (!in_array('first_name', $existing)) {
            $db->exec("ALTER TABLE teachers ADD COLUMN first_name VARCHAR(100) NULL AFTER name");
            echo "✓ Added first_name column" . PHP_EOL;
        }

        // Add last_name column
        if (!in_array('last_name', $existing)) {
            $db->exec("ALTER TABLE teachers ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name");
            echo "✓ Added last_name column" . PHP_EOL;
        }
    }

    echo PHP_EOL . "=== Migrating existing data ===" . PHP_EOL;

    // Split existing names into first and last
    $stmt = $db->query("SELECT id, name FROM teachers WHERE name IS NOT NULL AND name != '' AND (first_name IS NULL OR last_name IS NULL)");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($teachers as $teacher) {
        $nameParts = explode(' ', trim($teacher['name']), 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        $updateStmt = $db->prepare("UPDATE teachers SET first_name = :first, last_name = :last WHERE id = :id");
        $updateStmt->execute([
            ':first' => $firstName,
            ':last' => $lastName,
            ':id' => $teacher['id']
        ]);
        $updated++;
    }

    echo "✓ Migrated $updated teacher records" . PHP_EOL;

    echo PHP_EOL . "=== Verification ===" . PHP_EOL;
    $stmt = $db->query("SELECT id, name, first_name, last_name FROM teachers LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($samples)) {
        echo "No teachers found in database (table is empty)" . PHP_EOL;
    } else {
        foreach ($samples as $sample) {
            echo "ID: {$sample['id']} | Name: {$sample['name']} | First: {$sample['first_name']} | Last: {$sample['last_name']}" . PHP_EOL;
        }
    }

    echo PHP_EOL . "✅ Migration complete!" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
