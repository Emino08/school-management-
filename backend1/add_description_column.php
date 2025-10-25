<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "========================================\n";
echo "Adding Description Column to Subjects\n";
echo "========================================\n\n";

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    // Database connection
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '4306';
    $dbname = $_ENV['DB_NAME'] ?? 'school_management';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '1212';
    $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ Connected to database\n\n";

    // Check if column already exists
    $stmt = $pdo->query("DESCRIBE subjects");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('description', $columns)) {
        echo "⚠️  Description column already exists!\n\n";
    } else {
        // Add description column
        echo "Adding description column...\n";
        $pdo->exec("
            ALTER TABLE subjects 
            ADD COLUMN description TEXT NULL AFTER sessions
        ");
        echo "✓ Description column added successfully!\n\n";
    }

    // Show current table structure
    echo "Current table structure:\n";
    echo "------------------------\n";
    $stmt = $pdo->query("DESCRIBE subjects");
    $structure = $stmt->fetchAll();
    
    printf("%-20s %-15s %-10s %-10s %-10s\n", 
        'Field', 'Type', 'Null', 'Key', 'Default');
    echo str_repeat('-', 70) . "\n";
    
    foreach ($structure as $column) {
        printf("%-20s %-15s %-10s %-10s %-10s\n",
            $column['Field'],
            $column['Type'],
            $column['Null'],
            $column['Key'],
            $column['Default'] ?? 'NULL'
        );
    }

    echo "\n========================================\n";
    echo "SUCCESS! Migration completed\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
