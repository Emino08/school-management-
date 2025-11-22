<?php
/**
 * Run Town Master Tables Migration
 * Creates all necessary tables for the Town Master system
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

try {
    echo "Starting Town Master Tables Migration...\n\n";
    
    // Get database connection
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Read the migration file
    $migrationFile = __DIR__ . '/database/migrations/add_town_master_tables.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: {$migrationFile}");
    }
    
    $sql = file_get_contents($migrationFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read migration file");
    }
    
    echo "Executing migration...\n";
    
    // Split by semicolons and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   strlen(trim($stmt)) > 0;
        }
    );
    
    $pdo->beginTransaction();
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        try {
            // Skip comments and empty lines
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            $pdo->exec($statement);
            $successCount++;
            
            // Extract table name for logging
            if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✓ Altered table: {$matches[1]}\n";
            } else {
                echo "✓ Executed statement\n";
            }
        } catch (PDOException $e) {
            // Check if error is because table/column already exists
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠ Skipped (already exists)\n";
            } else {
                $errorCount++;
                echo "✗ Error: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    $pdo->commit();
    
    echo "\n=== Migration Summary ===\n";
    echo "Successful statements: {$successCount}\n";
    echo "Errors: {$errorCount}\n";
    
    if ($errorCount > 0) {
        echo "\nMigration completed with errors. Please review the errors above.\n";
        exit(1);
    }
    
    echo "\n✓ Town Master Tables Migration completed successfully!\n";
    
    // Verify tables were created
    echo "\nVerifying tables...\n";
    $tables = ['towns', 'blocks', 'town_masters', 'student_blocks', 'town_attendance', 'attendance_strikes'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '{$table}' exists\n";
        } else {
            echo "✗ Table '{$table}' NOT found\n";
        }
    }
    
    echo "\nMigration complete!\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
