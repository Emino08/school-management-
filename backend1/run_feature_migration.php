<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected to database successfully!\n\n";

    // Read migration file
    $migrationFile = __DIR__ . '/database/migrations/add_comprehensive_features.sql';
    $sql = file_get_contents($migrationFile);

    // Remove comments
    $sql = preg_replace('/^--.*$/m', '', $sql);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $successCount = 0;
    $errorCount = 0;
    $skippedCount = 0;

    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }

        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Extract table name for better logging
            if (preg_match('/(?:CREATE TABLE|ALTER TABLE)\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Executed successfully: {$matches[1]}\n";
            } else {
                echo "✓ Statement executed\n";
            }
        } catch (PDOException $e) {
            // Ignore duplicate column/table errors
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'Duplicate key') !== false ||
                strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), '1060') !== false ||
                strpos($e->getMessage(), '1061') !== false ||
                strpos($e->getMessage(), '1050') !== false) {
                $skippedCount++;
                if (preg_match('/(?:CREATE TABLE|ALTER TABLE)\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                    echo "⊙ Skipped (already exists): {$matches[1]}\n";
                }
                continue;
            }
            $errorCount++;
            echo "✗ Error: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
        }
    }

    echo "\n========================================\n";
    echo "Migration completed!\n";
    echo "Success: $successCount statements\n";
    echo "Skipped: $skippedCount statements\n";
    echo "Errors: $errorCount statements\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
