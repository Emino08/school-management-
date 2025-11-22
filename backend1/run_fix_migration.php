<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'school_management';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    echo "Connecting to database ($host:$port/$dbname)...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully!\n\n";

    echo "Running fix_schema_issues migration...\n";
    $sqlFile = __DIR__ . '/database/migrations/fix_schema_issues.sql';
    
    if (!file_exists($sqlFile)) {
        die("Migration file not found: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Show progress for key operations
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE[^`]*`?([a-z_]+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Created table: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE[^`]*`?([a-z_]+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Altered table: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            // Ignore "column already exists" and "table already exists" errors
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Multiple primary key') !== false) {
                echo "⊙ Skipped (already exists): " . substr($statement, 0, 80) . "...\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n========================================\n";
    echo "Migration completed!\n";
    echo "Success: $successCount statements\n";
    echo "Errors: $errorCount statements\n";
    echo "========================================\n\n";
    
    // Verify key tables/columns exist
    echo "Verifying schema changes...\n";
    
    $checks = [
        "SHOW COLUMNS FROM activity_logs LIKE 'activity_type'" => "activity_logs.activity_type",
        "SHOW COLUMNS FROM system_settings LIKE 'currency_code'" => "system_settings.currency_code",
        "SHOW COLUMNS FROM teachers LIKE 'first_name'" => "teachers.first_name",
        "SHOW COLUMNS FROM teachers LIKE 'last_name'" => "teachers.last_name",
        "SHOW TABLES LIKE 'town_blocks'" => "town_blocks table",
        "SHOW TABLES LIKE 'town_registrations'" => "town_registrations table",
        "SHOW TABLES LIKE 'town_attendance'" => "town_attendance table",
        "SHOW TABLES LIKE 'urgent_notifications'" => "urgent_notifications table"
    ];
    
    foreach ($checks as $query => $label) {
        try {
            $result = $pdo->query($query);
            if ($result->rowCount() > 0) {
                echo "✓ $label exists\n";
            } else {
                echo "✗ $label NOT FOUND\n";
            }
        } catch (PDOException $e) {
            echo "✗ Error checking $label: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nMigration process complete!\n";

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
