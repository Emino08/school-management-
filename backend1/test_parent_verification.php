<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

echo "Testing Parent Child Verification\n";
echo "===================================\n\n";

$db = Database::getInstance();
$pdo = $db->getConnection();

// Test data
$studentId = '32770';
$dateOfBirth = '2000-08-01';

echo "Input:\n";
echo "  student_id: $studentId\n";
echo "  date_of_birth: $dateOfBirth\n\n";

// Test the exact query from verifyChildRelationship
$sql = "SELECT s.id, s.name, s.first_name, s.last_name, s.admin_id
        FROM students s
        WHERE (s.id = :student_id OR s.id_number = :student_id_number) 
        AND s.date_of_birth = :date_of_birth";

$stmt = $pdo->prepare($sql);
$params = [
    ':student_id' => is_numeric($studentId) ? $studentId : 0,
    ':student_id_number' => $studentId,
    ':date_of_birth' => $dateOfBirth
];

echo "Query parameters:\n";
echo "  student_id (numeric check): " . (is_numeric($studentId) ? $studentId : 0) . "\n";
echo "  student_id_number: $studentId\n";
echo "  date_of_birth: $dateOfBirth\n\n";

$stmt->execute($params);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Result:\n";
if ($result) {
    echo "  ✓ Student found!\n";
    foreach ($result as $key => $value) {
        echo "    $key: $value\n";
    }
} else {
    echo "  ✗ No student found\n\n";
    
    // Let's check what's in the database
    echo "Checking database for student with id_number 32770:\n";
    $checkStmt = $pdo->prepare("SELECT id, name, first_name, last_name, id_number, date_of_birth FROM students WHERE id_number = '32770'");
    $checkStmt->execute();
    $check = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check) {
        echo "  Student exists:\n";
        foreach ($check as $key => $value) {
            echo "    $key: $value\n";
        }
        
        // Check date format
        echo "\n  Comparing dates:\n";
        echo "    Input date: '$dateOfBirth'\n";
        echo "    DB date: '{$check['date_of_birth']}'\n";
        echo "    Match: " . ($dateOfBirth === $check['date_of_birth'] ? "YES" : "NO") . "\n";
    } else {
        echo "  Student does not exist in database\n";
    }
}
