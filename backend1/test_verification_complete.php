<?php
/**
 * Complete API Test - Simulating the actual request
 */
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Models\ParentUser;
use App\Config\Database;

echo "=== COMPLETE PARENT VERIFICATION TEST ===\n\n";

$db = Database::getInstance();
$pdo = $db->getConnection();
$parentModel = new ParentUser();

// Step 1: Get or create a test parent
echo "Step 1: Getting test parent...\n";
$stmt = $pdo->query("SELECT id, email FROM parents LIMIT 1");
$parent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    echo "  Creating test parent...\n";
    $pdo->exec("INSERT INTO parents (admin_id, name, email, password, phone, relationship) 
                VALUES (1, 'Test Parent', 'testparent@test.com', '" . password_hash('test123', PASSWORD_BCRYPT) . "', '1234567890', 'parent')");
    $parentId = $pdo->lastInsertId();
    echo "  ✓ Created parent ID: $parentId\n";
} else {
    $parentId = $parent['id'];
    echo "  ✓ Using parent ID: $parentId (email: {$parent['email']})\n";
}

// Step 2: Test the verification
echo "\nStep 2: Testing verifyChildRelationship...\n";
$studentId = '32770';
$dateOfBirth = '2000-08-01';

echo "  Input:\n";
echo "    parent_id: $parentId\n";
echo "    student_id: $studentId\n";
echo "    date_of_birth: $dateOfBirth\n\n";

$student = $parentModel->verifyChildRelationship($parentId, $studentId, $dateOfBirth);

echo "  Result:\n";
if ($student) {
    echo "    ✓ SUCCESS!\n";
    echo "    Student ID: {$student['id']}\n";
    echo "    Student Name: {$student['name']}\n";
    echo "    Admin ID: {$student['admin_id']}\n\n";
    
    // Step 3: Check if already linked
    echo "Step 3: Checking if already linked...\n";
    $checkStmt = $pdo->prepare("SELECT id FROM parent_student_relations WHERE parent_id = ? AND student_id = ?");
    $checkStmt->execute([$parentId, $student['id']]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        echo "  ⚠ Already linked (relation ID: {$existing['id']})\n";
        echo "\n  Unlinking for fresh test...\n";
        $pdo->prepare("DELETE FROM parent_student_relations WHERE id = ?")->execute([$existing['id']]);
        echo "  ✓ Unlinked\n";
    } else {
        echo "  ✓ Not linked yet\n";
    }
    
    // Step 4: Test linking
    echo "\nStep 4: Testing linkChild...\n";
    try {
        $parentModel->linkChild($parentId, $student['id'], $student['admin_id']);
        echo "  ✓ Successfully linked!\n\n";
        
        // Verify the link
        $verifyStmt = $pdo->prepare("SELECT * FROM parent_student_relations WHERE parent_id = ? AND student_id = ?");
        $verifyStmt->execute([$parentId, $student['id']]);
        $link = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($link) {
            echo "  Verification:\n";
            echo "    Link ID: {$link['id']}\n";
            echo "    Verified: " . ($link['is_verified'] ? 'YES' : 'NO') . "\n";
            echo "    Created: {$link['verified_at']}\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Link failed: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "    ✗ FAILED - Student not found\n";
    echo "    ERROR: Query returned NULL\n\n";
    
    // Debug the actual query
    echo "  Debug: Testing raw query...\n";
    $debugStmt = $pdo->prepare("
        SELECT s.id, s.name, s.first_name, s.last_name, s.admin_id
        FROM students s
        WHERE (s.id = :student_id OR s.id_number = :student_id_number) 
        AND s.date_of_birth = :date_of_birth
    ");
    $debugStmt->execute([
        ':student_id' => is_numeric($studentId) ? $studentId : 0,
        ':student_id_number' => $studentId,
        ':date_of_birth' => $dateOfBirth
    ]);
    $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($debugResult) {
        echo "    Raw query WORKS! Found:\n";
        foreach ($debugResult as $k => $v) {
            echo "      $k: $v\n";
        }
        echo "\n    ISSUE: Problem is in the ParentUser model!\n";
    } else {
        echo "    Raw query also fails\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
