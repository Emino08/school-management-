<?php

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║   IMPORTING DATABASE SCHEMA                    ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    echo "Creating database...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS school_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE school_management");
    echo "✅ Database ready\n\n";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database/school_management.sql';
    
    if (!file_exists($sqlFile)) {
        echo "❌ SQL file not found: $sqlFile\n";
        exit(1);
    }
    
    echo "Reading SQL file...\n";
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^(--|\/\*|#)/', $stmt) &&
                   strlen($stmt) > 10;
        }
    );
    
    echo "Found " . count($statements) . " SQL statements\n";
    echo "Executing...\n\n";
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Show progress every 10 statements
            if ($executed % 10 == 0) {
                echo "  Executed $executed statements...\n";
            }
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if ($e->getCode() != '42S01') {
                $errors++;
                echo "  ⚠️  Error in statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n╔════════════════════════════════════════════════╗\n";
    echo "║   ✅ IMPORT COMPLETE                           ║\n";
    echo "╚════════════════════════════════════════════════╝\n\n";
    
    echo "Summary:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  Executed: $executed statements\n";
    echo "  Errors: $errors\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // List created tables
    echo "Created tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "  ✅ $table\n";
    }
    
    echo "\n✅ Database schema imported successfully!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
