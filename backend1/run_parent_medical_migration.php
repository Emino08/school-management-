<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    // Database connection
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'] ?? '';

    $dsn = "mysql:host=$host;port={$_ENV['DB_PORT']};dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected to database successfully.\n\n";

    // Read the migration file
    $migrationFile = __DIR__ . '/database/migrations/add_parents_medical_houses_system.sql';
    
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile\n");
    }

    $sql = file_get_contents($migrationFile);
    
    echo "Running migration: add_parents_medical_houses_system.sql\n";
    echo "========================================\n\n";

    // Split the SQL file by semicolons (handling DELIMITER changes)
    $statements = [];
    $currentStatement = '';
    $delimiter = ';';
    $inDelimiterBlock = false;
    
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Skip comments and empty lines
        if (empty($trimmedLine) || strpos($trimmedLine, '--') === 0) {
            continue;
        }
        
        // Handle DELIMITER changes
        if (stripos($trimmedLine, 'DELIMITER') === 0) {
            $parts = preg_split('/\s+/', $trimmedLine);
            if (count($parts) >= 2) {
                $delimiter = $parts[1];
                $inDelimiterBlock = ($delimiter !== ';');
            }
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        // Check if statement is complete
        if (strpos($currentStatement, $delimiter) !== false) {
            // Extract the statement before the delimiter
            $parts = explode($delimiter, $currentStatement);
            $statement = trim($parts[0]);
            
            if (!empty($statement)) {
                $statements[] = $statement;
            }
            
            // Keep any remainder for next statement
            $currentStatement = isset($parts[1]) ? $parts[1] : '';
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }

    // Execute each statement
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            // Skip CREATE PROCEDURE if already exists
            if (stripos($statement, 'CREATE PROCEDURE') !== false) {
                // Try to drop first
                preg_match('/CREATE PROCEDURE\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $statement, $matches);
                if (!empty($matches[1])) {
                    $procedureName = $matches[1];
                    try {
                        $pdo->exec("DROP PROCEDURE IF EXISTS $procedureName");
                    } catch (PDOException $e) {
                        // Ignore errors on drop
                    }
                }
            }
            
            $pdo->exec($statement);
            $successCount++;
            
            // Show progress for major operations
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✓ Created table: $tableName\n";
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✓ Altered table: $tableName\n";
            } elseif (stripos($statement, 'CREATE INDEX') !== false || stripos($statement, 'CREATE UNIQUE INDEX') !== false) {
                echo "✓ Created index\n";
            }
            
        } catch (PDOException $e) {
            $errorCount++;
            // Only show errors that are not "already exists"
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "✗ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n========================================\n";
    echo "Migration completed!\n";
    echo "Successful statements: $successCount\n";
    echo "Errors (non-critical): $errorCount\n\n";

    // Verify key tables were created
    echo "Verifying tables...\n";
    $requiredTables = [
        'parents',
        'parent_student_relations',
        'parent_communications',
        'parent_notifications',
        'medical_staff',
        'medical_records',
        'medical_documents',
        'houses',
        'house_blocks',
        'house_masters',
        'student_suspensions'
    ];

    $missingTables = [];
    foreach ($requiredTables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            echo "✓ Table exists: $table\n";
        } else {
            echo "✗ Missing table: $table\n";
            $missingTables[] = $table;
        }
    }

    if (empty($missingTables)) {
        echo "\n✓ All required tables created successfully!\n";
    } else {
        echo "\n✗ Warning: Some tables are missing: " . implode(', ', $missingTables) . "\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMigration script completed.\n";
