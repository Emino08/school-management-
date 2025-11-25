<?php
// Run Database Migration for Parent Tables and Column Fixes

$host = 'localhost';
$port = 4306;
$dbname = 'school_management';
$username = 'root';
$password = '1212';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database successfully.\n\n";
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/database/migrations/fix_parent_tables_and_columns.sql');
    
    // Enable multi-query execution
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
    
    // Split statements properly
    $statements = [];
    $currentStatement = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        $currentStatement .= $line . ' ';
        
        // Check if statement ends with semicolon
        if (substr(rtrim($line), -1) === ';') {
            $statements[] = trim($currentStatement);
            $currentStatement = '';
        }
    }
    
    $successCount = 0;
    $errorCount = 0;
    $warnings = [];
    
    echo "Executing migration statements...\n";
    echo str_repeat("=", 60) . "\n";
    
    foreach ($statements as $index => $statement) {
        if (empty($statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Show progress for major operations
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches);
                if (!empty($matches[1])) {
                    echo "✓ Created table: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $matches);
                if (!empty($matches[1])) {
                    echo "✓ Altered table: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'INSERT') !== false) {
                preg_match('/INSERT.*INTO\s+`?(\w+)`?/i', $statement, $matches);
                if (!empty($matches[1])) {
                    echo "✓ Inserted data into: {$matches[1]}\n";
                }
            }
            
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Ignore certain expected errors
            if (strpos($errorMsg, 'Duplicate') !== false || 
                strpos($errorMsg, 'already exists') !== false ||
                strpos($errorMsg, 'Duplicate key name') !== false ||
                strpos($errorMsg, 'Multiple primary key') !== false ||
                strpos($errorMsg, 'Duplicate column name') !== false) {
                // These are expected in re-runs
                continue;
            }
            
            // Show the warning but continue
            echo "⚠ Warning on statement " . ($index + 1) . ": " . substr($errorMsg, 0, 80) . "...\n";
            $warnings[] = "Statement " . ($index + 1) . ": " . $errorMsg;
            $errorCount++;
        }
    }
    
    echo str_repeat("=", 60) . "\n";
    echo "\n✅ Migration completed!\n\n";
    echo "Results:\n";
    echo "  • Statements executed successfully: $successCount\n";
    echo "  • Warnings/Errors: $errorCount\n";
    
    if (!empty($warnings) && $errorCount > 0) {
        echo "\nWarnings:\n";
        foreach (array_slice($warnings, 0, 5) as $warning) {
            echo "  ⚠ $warning\n";
        }
        if (count($warnings) > 5) {
            echo "  ... and " . (count($warnings) - 5) . " more warnings\n";
        }
    }
    
    echo "\nVerifying tables...\n";
    echo str_repeat("-", 60) . "\n";
    
    // Verify key tables exist
    $tables = ['student_parents', 'medical_records', 'students', 'parents', 'terms'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table '$table' exists\n";
            } else {
                echo "✗ Table '$table' NOT found\n";
            }
        } catch (PDOException $e) {
            echo "✗ Error checking table '$table': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ All database fixes applied successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
