<?php
/**
 * Complete Parent Verification Test
 * Tests the actual endpoint logic
 */

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Models\Parent as ParentModel;
use App\Config\Database;

echo "Complete Parent Verification Test\n";
echo "==================================\n\n";

$db = Database::getInstance();
$parentModel = new ParentModel($db->getConnection());

// Test with actual parent (create a test parent if needed)
echo "Step 1: Check if we have any parents\n";
$pdo = $db->getConnection();
$stmt = $pdo->query("SELECT COUNT(*) as count FROM parents");
$parentCount = $stmt->fetch()['count'];
echo "  Parents in database: $parentCount\n\n";

if ($parentCount == 0) {
    echo "  Creating a test parent...\n";
    $testParentData = [
        'admin_id' => 1,
        'name' => 'Test Parent',
        'email' => 'testparent@test.com',
        'password' => password_hash('password123', PASSWORD_BCRYPT),
        'phone' => '1234567890',
        'relationship' => 'parent'
    ];
    
    $stmt = $pdo->prepare("INSERT INTO parents (admin_id, name, email, password, phone, relationship) VALUES (:admin_id, :name, :email, :password, :phone, :relationship)");
    $stmt->execute($testParentData);
    $parentId = $pdo->lastInsertId();
    echo "  ✓ Test parent created with ID: $parentId\n\n";
} else {
    $stmt = $pdo->query("SELECT id FROM parents LIMIT 1");
    $parentId = $stmt->fetch()['id'];
    echo "  Using existing parent ID: $parentId\n\n";
}

// Test verification
echo "Step 2: Test verifyChildRelationship\n";
$studentId = '32770';
$dateOfBirth = '2000-08-01';

echo "  Input:\n";
echo "    parent_id: $parentId\n";
echo "    student_id: $studentId\n";
echo "    date_of_birth: $dateOfBirth\n\n";

$student = $parentModel->verifyChildRelationship($parentId, $studentId, $dateOfBirth);

echo "  Result:\n";
if ($student) {
    echo "    ✓ SUCCESS - Student verified!\n";
    echo "    Student ID: {$student['id']}\n";
    echo "    Student Name: {$student['name']}\n";
    echo "    Admin ID: {$student['admin_id']}\n\n";
    
    // Test linking
    echo "Step 3: Test linking child\n";
    try {
        // Check if already linked
        $checkStmt = $pdo->prepare("SELECT id FROM parent_student_relations WHERE parent_id = ? AND student_id = ?");
        $checkStmt->execute([$parentId, $student['id']]);
        
        if ($checkStmt->fetch()) {
            echo "  ⚠ Child already linked\n";
        } else {
            $parentModel->linkChild($parentId, $student['id'], $student['admin_id']);
            echo "  ✓ Child linked successfully!\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Link failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "    ✗ FAILED - No student found\n";
    echo "    This should not happen based on previous tests!\n\n";
    
    // Debug: Check the actual data
    echo "  Debug: Checking students table...\n";
    $debugStmt = $pdo->prepare("SELECT id, name, id_number, date_of_birth FROM students WHERE id_number = ?");
    $debugStmt->execute([$studentId]);
    $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($debugResult) {
        echo "    Found student in DB:\n";
        foreach ($debugResult as $key => $value) {
            echo "      $key: '$value'\n";
        }
    }
}

echo "\n✓ Test complete!\n";
