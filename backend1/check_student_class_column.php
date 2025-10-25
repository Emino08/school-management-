<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "Checking students table for class-related columns:\n";
    $stmt = $db->query("SHOW COLUMNS FROM students");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if (stripos($col['Field'], 'class') !== false) {
            echo "  ✓ {$col['Field']} ({$col['Type']})\n";
        }
    }

    echo "\nChecking subjects table for class-related columns:\n";
    $stmt = $db->query("SHOW COLUMNS FROM subjects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if (stripos($col['Field'], 'class') !== false || stripos($col['Field'], 'sclass') !== false) {
            echo "  ✓ {$col['Field']} ({$col['Type']})\n";
        }
    }

    echo "\nChecking enrollments table (if exists):\n";
    $stmt = $db->query("SHOW TABLES LIKE 'enrollments'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM enrollments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "  - No enrollments table found\n";
    }

    echo "\nChecking student_enrollments table (if exists):\n";
    $stmt = $db->query("SHOW TABLES LIKE 'student_enrollments'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM student_enrollments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "  - No student_enrollments table found\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
