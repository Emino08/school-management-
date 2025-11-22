<?php
/**
 * Test Database Connection
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Testing Database Connection...\n\n";

// Get database settings
$host = $_ENV['DB_HOST_DEVELOPMENT'] ?? $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT_DEVELOPMENT'] ?? $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME_DEVELOPMENT'] ?? $_ENV['DB_NAME'];
$user = $_ENV['DB_USER_DEVELOPMENT'] ?? $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS_DEVELOPMENT'] ?? $_ENV['DB_PASS'];

echo "Host: {$host}\n";
echo "Port: {$port}\n";
echo "Database: {$dbname}\n";
echo "User: {$user}\n\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "✓ Connection successful!\n\n";
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "MySQL Version: {$result['version']}\n";
    
    // Check if blocks table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'blocks'");
    if ($stmt->rowCount() > 0) {
        echo "\n✓ 'blocks' table already exists\n";
    } else {
        echo "\n⚠ 'blocks' table does NOT exist - migration needed\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
