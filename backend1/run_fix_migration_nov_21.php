<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'school_management';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n\n";
    
    // Read and execute the migration
    $migrationFile = __DIR__ . '/database/migrations/fix_activity_logs_and_settings.sql';
    
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile\n");
    }
    
    $sql = file_get_contents($migrationFile);
    
    echo "Executing migration...\n";
    echo "===================\n\n";
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Extract table name for better logging
            if (preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches)) {
                echo "✓ Updated table: {$matches[1]}\n";
            } elseif (preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            } else {
                echo "✓ Executed statement\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            
            // Ignore certain acceptable errors
            if (strpos($errorMsg, "Duplicate column name") !== false ||
                strpos($errorMsg, "already exists") !== false ||
                strpos($errorMsg, "Unknown column") !== false && strpos($statement, "CHANGE COLUMN") !== false) {
                echo "⚠ Skipped (already applied): " . substr($statement, 0, 60) . "...\n";
            } else {
                echo "✗ Error: " . $errorMsg . "\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n===================\n";
    echo "Migration completed!\n";
    echo "Successful: $successCount\n";
    echo "Errors/Skipped: $errorCount\n";
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}
