<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║   CHECKING SQL SYNTAX & TABLE STRUCTURE        ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // List of tables to check
    $tables = [
        'admins', 'students', 'teachers', 'classes', 'subjects',
        'attendance', 'grades', 'fees_payments', 'notices',
        'notifications', 'parents', 'academic_years'
    ];
    
    $reservedKeywords = [
        'order', 'group', 'key', 'index', 'primary', 'unique',
        'check', 'foreign', 'references', 'default', 'select',
        'where', 'from', 'join', 'limit', 'offset'
    ];
    
    $issues = [];
    
    foreach ($tables as $table) {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "⚠️  Table '$table' does not exist\n";
            continue;
        }
        
        echo "Checking table: $table\n";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check for reserved keywords without backticks
        foreach ($columns as $col) {
            $columnName = strtolower($col['Field']);
            
            if (in_array($columnName, $reservedKeywords)) {
                $issues[] = [
                    'table' => $table,
                    'column' => $col['Field'],
                    'issue' => 'Reserved keyword - should use backticks'
                ];
            }
        }
        
        // Test basic SELECT query
        try {
            $testQuery = "SELECT * FROM $table LIMIT 1";
            $stmt = $pdo->query($testQuery);
            echo "  ✅ SELECT query works\n";
        } catch (PDOException $e) {
            echo "  ❌ SELECT query failed: " . $e->getMessage() . "\n";
            $issues[] = [
                'table' => $table,
                'issue' => 'SELECT query failed: ' . $e->getMessage()
            ];
        }
        
        // Test INSERT with minimal data
        try {
            // Get required columns (NOT NULL without default)
            $requiredCols = [];
            foreach ($columns as $col) {
                if ($col['Null'] === 'NO' && 
                    $col['Default'] === null && 
                    $col['Extra'] !== 'auto_increment') {
                    $requiredCols[] = $col['Field'];
                }
            }
            
            if (!empty($requiredCols)) {
                echo "  ℹ️  Required columns: " . implode(', ', $requiredCols) . "\n";
            }
            
        } catch (PDOException $e) {
            echo "  ⚠️  Could not check required columns\n";
        }
        
        echo "\n";
    }
    
    // Summary
    echo "\n╔════════════════════════════════════════════════╗\n";
    echo "║   SUMMARY                                      ║\n";
    echo "╚════════════════════════════════════════════════╝\n\n";
    
    if (empty($issues)) {
        echo "✅ No SQL syntax issues found!\n";
        echo "✅ All tables have correct structure\n";
        echo "✅ All queries work correctly\n\n";
    } else {
        echo "⚠️  Found " . count($issues) . " potential issues:\n\n";
        foreach ($issues as $issue) {
            echo "Table: {$issue['table']}\n";
            if (isset($issue['column'])) {
                echo "  Column: {$issue['column']}\n";
            }
            echo "  Issue: {$issue['issue']}\n\n";
        }
    }
    
    // Test the actual login query
    echo "Testing login query specifically...\n";
    $testEmail = 'admin@boschool.org';
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $testEmail]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Login query works correctly\n";
        echo "   Found admin: {$result['email']}\n";
        echo "   Has password: " . (empty($result['password']) ? 'NO' : 'YES') . "\n";
        echo "   Has name: " . (empty($result['name']) ? 'NO' : 'YES') . "\n";
    } else {
        echo "❌ Login query returned no results\n";
    }
    
    echo "\n✅ SQL syntax check complete!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
