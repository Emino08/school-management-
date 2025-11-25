<?php
/**
 * Link existing students to their parents based on matching email patterns or admin
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
    echo "=== Linking Students to Parents ===\n\n";
    
    // Get all students without parent links
    $stmt = $db->query("
        SELECT s.id, s.first_name, s.last_name, s.admin_id, s.date_of_birth
        FROM students s
        WHERE s.id NOT IN (SELECT student_id FROM student_parents)
        ORDER BY s.admin_id, s.id
    ");
    $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        echo "✓ All students are already linked to parents\n";
        exit(0);
    }
    
    echo "Found " . count($students) . " student(s) without parent links\n\n";
    
    $linkedCount = 0;
    
    foreach ($students as $student) {
        // Try to find a parent with the same admin_id
        $stmt = $db->prepare("
            SELECT id, name, email
            FROM parents
            WHERE admin_id = :admin_id
            LIMIT 1
        ");
        $stmt->execute([':admin_id' => $student['admin_id']]);
        $parent = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($parent) {
            // Link student to parent
            $insertStmt = $db->prepare("
                INSERT INTO student_parents (student_id, parent_id, relationship, verified, verified_at, is_primary)
                VALUES (:student_id, :parent_id, 'mother', 1, NOW(), 1)
            ");
            $insertStmt->execute([
                ':student_id' => $student['id'],
                ':parent_id' => $parent['id']
            ]);
            
            echo "✓ Linked student #{$student['id']} ({$student['first_name']} {$student['last_name']}) ";
            echo "to parent #{$parent['id']} ({$parent['name']})\n";
            $linkedCount++;
        } else {
            echo "⚠ No parent found for student #{$student['id']} ({$student['first_name']} {$student['last_name']}) ";
            echo "with admin_id {$student['admin_id']}\n";
        }
    }
    
    echo "\n=== Linking Completed ===\n";
    echo "Successfully linked $linkedCount student(s) to parents\n";
    
    if ($linkedCount < count($students)) {
        $unlinked = count($students) - $linkedCount;
        echo "\n⚠ Warning: $unlinked student(s) could not be linked automatically\n";
        echo "These students need parents to be created first or manually linked\n";
    }
    
    echo "\nYou can now:\n";
    echo "1. Test parent login and view children\n";
    echo "2. Add medical records for children\n";
    echo "3. Use parent verification feature for additional links\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
