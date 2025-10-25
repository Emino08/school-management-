<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

echo "==============================================\n";
echo "Database Verification Script\n";
echo "==============================================\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $dbname\n";
echo "User: $user\n";
echo "==============================================\n\n";

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Connected to database successfully!\n\n";

    // Check if database exists and list tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Tables found in database:\n";
    echo "==============================================\n";

    if (empty($tables)) {
        echo "⚠ WARNING: No tables found in database!\n";
        echo "Migration may have failed.\n";
    } else {
        foreach ($tables as $index => $table) {
            echo ($index + 1) . ". $table\n";
        }
        echo "\nTotal tables: " . count($tables) . "\n";
    }

} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nThis means the database connection failed.\n";
    echo "Please check your .env configuration.\n";
    exit(1);
}

echo "\n==============================================\n";
