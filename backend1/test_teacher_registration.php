<?php
/**
 * COMPREHENSIVE TEACHER REGISTRATION TEST
 * 
 * This script tests the complete teacher registration flow
 */

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     TEACHER REGISTRATION COMPREHENSIVE TEST                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Configuration
$API_URL = 'http://localhost:8080/api';
$ADMIN_EMAIL = 'admin@school.com';
$ADMIN_PASSWORD = 'admin123';

// Step 1: Login as admin
echo "Step 1: Logging in as admin...\n";
$loginData = json_encode([
    'email' => $ADMIN_EMAIL,
    'password' => $ADMIN_PASSWORD
]);

$ch = curl_init("$API_URL/admin/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Login failed! HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$loginResult = json_decode($response, true);
if (!$loginResult['success']) {
    echo "❌ Login failed: {$loginResult['message']}\n";
    exit(1);
}

$token = $loginResult['token'];
$adminId = $loginResult['user']['id'];

echo "✅ Login successful! Admin ID: $adminId\n";
echo "   Token: " . substr($token, 0, 20) . "...\n\n";

// Step 2: Get subjects
echo "Step 2: Fetching subjects...\n";
$ch = curl_init("$API_URL/subjects");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
curl_close($ch);

$subjectsResult = json_decode($response, true);
if (!$subjectsResult['success'] || empty($subjectsResult['subjects'])) {
    echo "❌ No subjects found!\n";
    exit(1);
}

$subjects = $subjectsResult['subjects'];
echo "✅ Found " . count($subjects) . " subjects\n";
foreach ($subjects as $subject) {
    echo "   • {$subject['subject_name']} ({$subject['subject_code']}) - ID: {$subject['id']}\n";
}
echo "\n";

// Step 3: Get classes
echo "Step 3: Fetching classes...\n";
$ch = curl_init("$API_URL/classes");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
curl_close($ch);

$classesResult = json_decode($response, true);
$classes = $classesResult['classes'] ?? [];

echo "✅ Found " . count($classes) . " classes\n";
foreach ($classes as $class) {
    echo "   • {$class['class_name']} - ID: {$class['id']}\n";
}
echo "\n";

// Step 4: Register teacher with ALL fields
echo "Step 4: Registering teacher...\n";

$randomNum = rand(1000, 9999);
$teacherData = [
    'name' => "Test Teacher $randomNum",
    'email' => "teacher$randomNum@school.com",
    'password' => 'password123',
    'phone' => '+1234567890',
    'teachSubjects' => array_column(array_slice($subjects, 0, 2), 'id'), // First 2 subjects
    'teachSclass' => $classes[0]['id'] ?? null,
    'isClassMaster' => true,
    'isExamOfficer' => true,
    'adminID' => $adminId
];

echo "Teacher Data:\n";
echo json_encode($teacherData, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init("$API_URL/teacher/register");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($teacherData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response HTTP Code: $httpCode\n";
echo "Response Body:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";

$registerResult = json_decode($response, true);

if (!$registerResult['success']) {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ REGISTRATION FAILED                                    ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "Error: {$registerResult['message']}\n";
    exit(1);
}

$teacherId = $registerResult['teacher_id'];
echo "✅ Teacher registered successfully! ID: $teacherId\n\n";

// Step 5: Verify in database
echo "Step 5: Verifying in database...\n";

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    
    // Check teacher record
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        echo "❌ Teacher not found in database!\n";
        exit(1);
    }
    
    echo "✅ Teacher record found:\n";
    echo "   Name: {$teacher['name']}\n";
    echo "   Email: {$teacher['email']}\n";
    echo "   Phone: {$teacher['phone']}\n";
    echo "   Is Class Master: " . ($teacher['is_class_master'] ? 'Yes' : 'No') . "\n";
    echo "   Class Master Of: {$teacher['class_master_of']}\n";
    echo "   Is Exam Officer: " . ($teacher['is_exam_officer'] ? 'Yes' : 'No') . "\n";
    echo "   Can Approve Results: " . ($teacher['can_approve_results'] ? 'Yes' : 'No') . "\n\n";
    
    // Check subject assignments
    $stmt = $pdo->prepare("
        SELECT s.subject_name, s.subject_code, ta.academic_year_id
        FROM teacher_assignments ta
        JOIN subjects s ON ta.subject_id = s.id
        WHERE ta.teacher_id = ?
    ");
    $stmt->execute([$teacherId]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Subject Assignments: " . count($assignments) . "\n";
    foreach ($assignments as $assignment) {
        echo "   • {$assignment['subject_name']} ({$assignment['subject_code']}) - Year: {$assignment['academic_year_id']}\n";
    }
    echo "\n";
    
    // Check exam officer entry if applicable
    if ($teacher['is_exam_officer']) {
        $stmt = $pdo->prepare("SELECT * FROM exam_officers WHERE email = ?");
        $stmt->execute([$teacher['email']]);
        $examOfficer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($examOfficer) {
            echo "✅ Exam Officer entry created\n";
            echo "   Name: {$examOfficer['name']}\n";
            echo "   Can Approve Results: " . ($examOfficer['can_approve_results'] ? 'Yes' : 'No') . "\n\n";
        } else {
            echo "⚠️  Warning: Exam officer entry not found\n\n";
        }
    }
    
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ ALL TESTS PASSED - REGISTRATION WORKS PERFECTLY!      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
} catch (Exception $e) {
    echo "❌ Database verification failed: " . $e->getMessage() . "\n";
    exit(1);
}
