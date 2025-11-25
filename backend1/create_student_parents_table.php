<?php
/**
 * Create student_parents table for parent-child relationships
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

use App\Config\Database;

$db = Database::getInstance()->getConnection();

try {
    echo "=== Creating student_parents Table ===\n\n";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'student_parents'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        // Create student_parents table
        $db->exec("
            CREATE TABLE student_parents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                parent_id INT NOT NULL,
                relationship VARCHAR(50) DEFAULT 'mother' COMMENT 'mother, father, guardian',
                is_primary TINYINT(1) DEFAULT 0,
                verified TINYINT(1) DEFAULT 0,
                verified_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_student_parent (student_id, parent_id),
                INDEX idx_student_id (student_id),
                INDEX idx_parent_id (parent_id),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✓ Created student_parents table\n";
    } else {
        echo "- student_parents table already exists\n";
    }
    
    // Check if we have any orphan data to migrate
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM students s 
        WHERE s.id NOT IN (SELECT student_id FROM student_parents)
        AND EXISTS (SELECT 1 FROM parents p LIMIT 1)
    ");
    $orphanCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    
    if ($orphanCount > 0) {
        echo "\nFound $orphanCount student(s) without parent links.\n";
        echo "You may need to manually link students to parents using:\n";
        echo "INSERT INTO student_parents (student_id, parent_id, relationship, verified) VALUES (?, ?, 'mother', 1);\n";
    }
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "\nTable Structure:\n";
    echo "- student_parents: Links students to their parents\n";
    echo "- Supports multiple parents per student\n";
    echo "- Includes verification status\n";
    echo "- Has relationship type (mother, father, guardian)\n";
    echo "\nNext Steps:\n";
    echo "1. Link existing students to parents if needed\n";
    echo "2. Use parent verification feature to add new links\n";
    echo "3. Test parent medical record functionality\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed.\n";
    exit(1);
}
