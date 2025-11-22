<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Creating system_settings table...\n";

$sql = file_get_contents(__DIR__ . '/database/migrations/create_system_settings.sql');

try {
    $pdo->exec($sql);
    echo "✓ System settings table created successfully!\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "⊙ System settings table already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\nVerifying system_settings table...\n";
$result = $pdo->query("SHOW COLUMNS FROM system_settings");
echo "Columns: " . $result->rowCount() . "\n";

echo "\nChecking for default settings...\n";
$result = $pdo->query("SELECT COUNT(*) FROM system_settings");
$count = $result->fetchColumn();
echo "Settings records: $count\n";

echo "\nDone!\n";
