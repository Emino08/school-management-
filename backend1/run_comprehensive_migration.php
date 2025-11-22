<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Running comprehensive database migration...\n\n";
    
    $sql = file_get_contents(__DIR__ . '/../database updated files/comprehensive_fix.sql');
    
    // Split SQL into individual statements and execute
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || str_starts_with($statement, '--')) {
            continue;
        }
        
        try {
            $db->exec($statement . ';');
            $successCount++;
        } catch (PDOException $e) {
            // Some statements may fail if already applied, that's okay
            if (str_contains($e->getMessage(), 'already exists') || 
                str_contains($e->getMessage(), 'Duplicate')) {
                echo "Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
            } else {
                $errorCount++;
                echo "Error: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
            }
        }
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Successful: $successCount\n";
    echo "Errors: $errorCount\n";
    echo "\n=== Verification ===\n\n";
    
    // Verify teacher columns
    $stmt = $db->query("SHOW COLUMNS FROM teachers WHERE Field IN ('name', 'first_name', 'last_name')");
    echo "Teachers table name columns:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    // Verify notification_reads table
    $stmt = $db->query("SHOW TABLES LIKE 'notification_reads'");
    echo "\nNotification reads table: " . ($stmt->rowCount() > 0 ? "EXISTS" : "MISSING") . "\n";
    
    // Verify password_reset_tokens table
    $stmt = $db->query("SHOW TABLES LIKE 'password_reset_tokens'");
    echo "Password reset tokens table: " . ($stmt->rowCount() > 0 ? "EXISTS" : "MISSING") . "\n";
    
    // Check currency
    $stmt = $db->query("SELECT currency FROM school_settings LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nCurrency: " . ($row['currency'] ?? 'NOT SET') . "\n";
    
    echo "\n=== Migration Complete! ===\n";
    
} catch (PDOException $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
