<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Models\ParentUser;
use App\Config\Database;
use PDO;

echo "=== DEBUGGING ParentUser Model ===\n\n";

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "Step 1: Check PDO connection\n";
echo "  PDO class: " . get_class($pdo) . "\n";
echo "  Connected: " . ($pdo ? 'YES' : 'NO') . "\n\n";

echo "Step 2: Create ParentUser instance\n";
$parentModel = new ParentUser();
echo "  Instance created\n";
echo "  Class: " . get_class($parentModel) . "\n\n";

echo "Step 3: Check internal DB connection\n";
$reflection = new ReflectionClass($parentModel);
$dbProperty = $reflection->getProperty('db');
$dbProperty->setAccessible(true);
$modelDb = $dbProperty->getValue($parentModel);
echo "  Model DB: " . get_class($modelDb) . "\n";
echo "  Same as global: " . ($modelDb === $pdo ? 'YES' : 'NO') . "\n\n";

echo "Step 4: Test query directly on model's DB\n";
$sql = "SELECT s.id, s.name FROM students s WHERE s.id_number = '32770' AND s.date_of_birth = '2000-08-01'";
$stmt = $modelDb->prepare($sql);
$stmt->execute();
$directResult = $stmt->fetch(PDO::FETCH_ASSOC);
echo "  Direct query result: " . ($directResult ? json_encode($directResult) : 'NULL') . "\n\n";

echo "Step 5: Call verifyChildRelationship\n";
$result = $parentModel->verifyChildRelationship(1, '32770', '2000-08-01');
echo "  Method result: " . ($result ? json_encode($result) : 'NULL') . "\n\n";

if (!$result) {
    echo "Step 6: Debug - Check if method exists\n";
    echo "  Method exists: " . (method_exists($parentModel, 'verifyChildRelationship') ? 'YES' : 'NO') . "\n";
    
    echo "\nStep 7: Call method again and catch any errors\n";
    try {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        $result2 = $parentModel->verifyChildRelationship(1, '32770', '2000-08-01');
        echo "  Result: " . ($result2 ? 'Found' : 'NULL') . "\n";
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
        echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

echo "\n=== END DEBUG ===\n";
