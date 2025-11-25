<?php
/**
 * Comprehensive Fix Script
 * This script applies all critical database fixes and verifies the changes
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "========================================\n";
echo "COMPREHENSIVE FIX SCRIPT\n";
echo "========================================\n\n";

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Connected to database\n\n";
    
    // Run the migration SQL file
    echo "Running migration SQL file...\n";
    $migrationFile = __DIR__ . '/database/migrations/fix_all_critical_issues.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split by semicolons but keep the prepared statements together
    $statements = [];
    $currentStatement = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        // Check if statement is complete
        if (substr(trim($line), -1) === ';') {
            $statements[] = trim($currentStatement);
            $currentStatement = '';
        }
    }
    
    // Execute each statement
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            // Some errors are expected (like ALTER TABLE IF COLUMN EXISTS)
            if (strpos($e->getMessage(), 'Duplicate column') === false &&
                strpos($e->getMessage(), 'already exists') === false) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n✓ Migration completed\n";
    echo "  - Successful statements: $successCount\n";
    echo "  - Warnings: $errorCount\n\n";
    
    // Verify changes
    echo "========================================\n";
    echo "VERIFICATION\n";
    echo "========================================\n\n";
    
    // 1. Check admins table
    echo "1. Checking admins table...\n";
    $stmt = $db->query("SHOW COLUMNS FROM admins WHERE Field = 'is_super_admin'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ is_super_admin column exists\n";
    } else {
        echo "   ✗ is_super_admin column missing\n";
    }
    
    // 2. Check student_parents table
    echo "\n2. Checking student_parents table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'student_parents'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ student_parents table exists\n";
        
        // Count records
        $stmt = $db->query("SELECT COUNT(*) as count FROM student_parents");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   ✓ $count parent-student relationships found\n";
    } else {
        echo "   ✗ student_parents table missing\n";
    }
    
    // 3. Check students table
    echo "\n3. Checking students table...\n";
    $stmt = $db->query("SHOW COLUMNS FROM students WHERE Field = 'photo'");
    if ($stmt->rowCount() === 0) {
        echo "   ✓ photo column correctly removed\n";
    } else {
        echo "   ⚠ photo column still exists (will be ignored)\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM students WHERE Field = 'suspension_status'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ suspension_status column exists\n";
    } else {
        echo "   ✗ suspension_status column missing\n";
    }
    
    // 4. Check medical_records table
    echo "\n4. Checking medical_records table...\n";
    $stmt = $db->query("SHOW COLUMNS FROM medical_records WHERE Field = 'parent_id'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ parent_id column exists\n";
    } else {
        echo "   ✗ parent_id column missing\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM medical_records WHERE Field = 'added_by'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ added_by column exists\n";
    } else {
        echo "   ✗ added_by column missing\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM medical_records WHERE Field = 'can_update'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ can_update column exists\n";
    } else {
        echo "   ✗ can_update column missing\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM medical_records WHERE Field = 'can_delete'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ can_delete column exists\n";
    } else {
        echo "   ✗ can_delete column missing\n";
    }
    
    // Check ENUM values
    $stmt = $db->query("SHOW COLUMNS FROM medical_records WHERE Field = 'record_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($column['Type'], 'general') !== false) {
        echo "   ✓ record_type ENUM updated with 'general'\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM medical_records WHERE Field = 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($column['Type'], 'archived') !== false) {
        echo "   ✓ status ENUM updated with 'archived'\n";
    }
    
    // 5. Check school_events table
    echo "\n5. Checking school_events table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'school_events'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM school_events WHERE Field = 'is_current'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ is_current column exists\n";
        } else {
            echo "   ⚠ is_current column missing (may not be needed)\n";
        }
    } else {
        echo "   ⚠ school_events table doesn't exist\n";
    }
    
    // 6. Update parent-student relationships from parent_student_links
    echo "\n6. Syncing parent-student relationships...\n";
    try {
        // Check if parent_student_links exists
        $stmt = $db->query("SHOW TABLES LIKE 'parent_student_links'");
        if ($stmt->rowCount() > 0) {
            // Sync data
            $db->exec("
                INSERT IGNORE INTO student_parents (parent_id, student_id, admin_id, relationship, verified, verified_at, created_at, updated_at)
                SELECT psl.parent_id, psl.student_id, s.admin_id, 
                       COALESCE(psl.relationship, 'mother'), 
                       psl.verified, psl.verified_at, 
                       psl.created_at, psl.updated_at 
                FROM parent_student_links psl
                JOIN students s ON psl.student_id = s.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM student_parents sp 
                    WHERE sp.parent_id = psl.parent_id AND sp.student_id = psl.student_id
                )
            ");
            
            $stmt = $db->query("SELECT ROW_COUNT() as count");
            $synced = $db->query("SELECT COUNT(*) as count FROM student_parents")->fetch(PDO::FETCH_ASSOC)['count'];
            echo "   ✓ Synced parent-student relationships ($synced total)\n";
        } else {
            echo "   ⚠ parent_student_links table doesn't exist\n";
        }
    } catch (PDOException $e) {
        echo "   ⚠ Sync warning: " . $e->getMessage() . "\n";
    }
    
    echo "\n========================================\n";
    echo "FIXES COMPLETE\n";
    echo "========================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Clear PHP opcache if enabled\n";
    echo "2. Test admin login and principal creation\n";
    echo "3. Test parent medical record addition\n";
    echo "4. Test parent viewing children\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
