<?php
/**
 * Apply All Schema Fixes
 * This script fixes all database schema issues including:
 * - activity_logs.activity_type column
 * - system_settings.currency_code column
 * - teacher name split (first_name, last_name)
 * - notifications table structure
 * - teacher_classes and teacher_subjects tables
 * - town master system tables
 * - urgent notifications table
 * - user roles table
 */

require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "==============================================\n";
    echo "  Schema Fix Migration\n";
    echo "==============================================\n\n";

    // Get database connection
    $database = \App\Config\Database::getInstance();
    $db = $database->getConnection();

    // Read the SQL file
    $sqlFile = __DIR__ . '/database/fix_all_schema_issues.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }

    echo "Reading SQL file...\n";
    $sql = file_get_contents($sqlFile);

    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^SELECT.*as message$/', $stmt);
        }
    );

    echo "Found " . count($statements) . " SQL statements to execute\n\n";

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    // Execute each statement
    foreach ($statements as $index => $statement) {
        $statementNum = $index + 1;
        
        // Skip comments and empty statements
        if (empty(trim($statement))) {
            continue;
        }

        // Extract first line for progress display
        $firstLine = strtok($statement, "\n");
        $firstLine = trim(preg_replace('/^--/', '', $firstLine));
        $firstLine = substr($firstLine, 0, 80);
        
        echo "[$statementNum/" . count($statements) . "] Executing: $firstLine...\n";

        try {
            $db->exec($statement . ';');
            $successCount++;
            echo "  ✓ Success\n";
        } catch (\PDOException $e) {
            // Check if error is benign (column already exists, etc.)
            $errorMsg = $e->getMessage();
            
            if (strpos($errorMsg, 'Duplicate column') !== false ||
                strpos($errorMsg, 'Duplicate key') !== false ||
                strpos($errorMsg, 'already exists') !== false ||
                strpos($errorMsg, 'Duplicate entry') !== false) {
                echo "  ⚠ Warning (already exists): " . $errorMsg . "\n";
                $successCount++;
            } else {
                echo "  ✗ Error: " . $errorMsg . "\n";
                $errorCount++;
                $errors[] = [
                    'statement' => $statementNum,
                    'query' => substr($statement, 0, 100) . '...',
                    'error' => $errorMsg
                ];
            }
        }
        echo "\n";
    }

    echo "==============================================\n";
    echo "  Migration Summary\n";
    echo "==============================================\n";
    echo "Total Statements: " . count($statements) . "\n";
    echo "Successful: $successCount\n";
    echo "Errors: $errorCount\n\n";

    if ($errorCount > 0) {
        echo "Errors encountered:\n";
        foreach ($errors as $error) {
            echo "\nStatement #" . $error['statement'] . ":\n";
            echo "Query: " . $error['query'] . "\n";
            echo "Error: " . $error['error'] . "\n";
        }
        echo "\n⚠ Migration completed with errors\n";
    } else {
        echo "✓ Migration completed successfully!\n";
    }

    echo "\n==============================================\n";
    echo "  Verification\n";
    echo "==============================================\n\n";

    // Verify key tables and columns
    $verifications = [
        ['activity_logs', 'activity_type'],
        ['system_settings', 'currency_code'],
        ['teachers', 'first_name'],
        ['teachers', 'last_name'],
        ['notifications', 'recipient_id'],
        ['teacher_classes', null],
        ['teacher_subjects', null],
        ['towns', null],
        ['town_blocks', null],
        ['town_students', null],
        ['town_attendance', null],
        ['urgent_notifications', null],
        ['user_roles', null]
    ];

    foreach ($verifications as $check) {
        list($table, $column) = $check;
        
        if ($column === null) {
            // Check if table exists
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->fetch();
            echo ($exists ? "✓" : "✗") . " Table: $table\n";
        } else {
            // Check if column exists
            $stmt = $db->query("SHOW COLUMNS FROM $table LIKE '$column'");
            $exists = $stmt->fetch();
            echo ($exists ? "✓" : "✗") . " Column: $table.$column\n";
        }
    }

    echo "\n==============================================\n";
    echo "  Done!\n";
    echo "==============================================\n";

} catch (\Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
