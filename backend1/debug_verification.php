<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "=== CHECKING STUDENT 32770 ===\n\n";

$stmt = $pdo->prepare('SELECT id, name, id_number, date_of_birth FROM students WHERE id_number = ?');
$stmt->execute(['32770']);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo "✓ Student FOUND:\n";
    foreach ($student as $k => $v) {
        echo "  $k: '$v'\n";
    }
    
    echo "\nDate comparison:\n";
    echo "  DB date: '{$student['date_of_birth']}'\n";
    echo "  Input date: '2000-08-01'\n";
    echo "  Match: " . ($student['date_of_birth'] === '2000-08-01' ? 'YES' : 'NO') . "\n";
    echo "  Length DB: " . strlen($student['date_of_birth']) . "\n";
    echo "  Length Input: " . strlen('2000-08-01') . "\n";
} else {
    echo "✗ Student NOT FOUND\n";
}

echo "\n\n=== CHECKING PARENT.PHP FILE ===\n\n";

// Check if the model file exists and what class it contains
$parentFile = __DIR__ . '/src/Models/Parent.php';
if (file_exists($parentFile)) {
    $content = file_get_contents($parentFile);
    if (preg_match('/class\s+(\w+)/', $content, $matches)) {
        echo "Class name in Parent.php: {$matches[1]}\n";
    }
    
    if (strpos($content, 'verifyChildRelationship') !== false) {
        echo "✓ verifyChildRelationship method exists\n";
        
        // Extract the method
        if (preg_match('/public function verifyChildRelationship.*?\{.*?return.*?;.*?\}/s', $content, $matches)) {
            echo "\nMethod code:\n";
            echo $matches[0] . "\n";
        }
    } else {
        echo "✗ verifyChildRelationship method NOT found\n";
    }
}

echo "\n\n=== CHECKING API ROUTES ===\n\n";

$routesFile = __DIR__ . '/src/Routes/api.php';
if (file_exists($routesFile)) {
    $routes = file_get_contents($routesFile);
    
    if (preg_match("/.*verify-child.*/", $routes, $matches)) {
        echo "Route found:\n";
        echo $matches[0] . "\n";
    } else {
        echo "Route NOT found\n";
    }
}
