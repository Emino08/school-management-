<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    
    echo "=== TESTING STUDENT UPDATE AND GENDER ===\n\n";
    
    // 1. Check gender enum values
    echo "1. Checking gender enum...\n";
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'gender'");
    $genderCol = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Gender column type: {$genderCol['Type']}\n";
    
    // 2. Get a test student
    echo "\n2. Getting test student...\n";
    $stmt = $db->query("SELECT id, name, first_name, last_name, gender, email FROM students LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "   Student ID: {$student['id']}\n";
        echo "   Name: {$student['name']}\n";
        echo "   First Name: {$student['first_name']}\n";
        echo "   Last Name: {$student['last_name']}\n";
        echo "   Gender: {$student['gender']}\n";
        echo "   Email: {$student['email']}\n";
        
        // 3. Test update with proper gender value
        echo "\n3. Testing gender update...\n";
        $newGender = $student['gender'] === 'Male' ? 'Female' : 'Male';
        echo "   Updating gender from '{$student['gender']}' to '$newGender'...\n";
        
        $stmt = $db->prepare("UPDATE students SET gender = :gender WHERE id = :id");
        $result = $stmt->execute([':gender' => $newGender, ':id' => $student['id']]);
        
        if ($result) {
            echo "   ✓ Update successful\n";
            
            // Verify the update
            $stmt = $db->prepare("SELECT gender FROM students WHERE id = :id");
            $stmt->execute([':id' => $student['id']]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   ✓ Verified: Gender is now '{$updated['gender']}'\n";
            
            // Restore original value
            $stmt = $db->prepare("UPDATE students SET gender = :gender WHERE id = :id");
            $stmt->execute([':gender' => $student['gender'], ':id' => $student['id']]);
            echo "   ✓ Restored original gender value\n";
        } else {
            echo "   ✗ Update failed\n";
        }
    } else {
        echo "   ✗ No students found in database\n";
    }
    
    // 4. Check student_enrollments for class updates
    echo "\n4. Checking student_enrollments table...\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM student_enrollments");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total enrollments: {$count['count']}\n";
    
    if ($student && $count['count'] > 0) {
        $stmt = $db->prepare("SELECT * FROM student_enrollments WHERE student_id = :student_id LIMIT 1");
        $stmt->execute([':student_id' => $student['id']]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($enrollment) {
            echo "   Student {$student['id']} enrollment:\n";
            echo "     - Class ID: {$enrollment['class_id']}\n";
            echo "     - Academic Year ID: {$enrollment['academic_year_id']}\n";
            echo "     - Status: {$enrollment['status']}\n";
        }
    }
    
    // 5. Check admins table structure for profile update
    echo "\n5. Checking admins table for profile updates...\n";
    $stmt = $db->query("SHOW COLUMNS FROM admins WHERE Field IN ('contact_name', 'school_phone', 'phone')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   - {$row['Field']} ({$row['Type']})\n";
    }
    
    // 6. Check activity_logs
    echo "\n6. Checking recent activity logs...\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM activity_logs");
    $logCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total activity logs: {$logCount['count']}\n";
    
    $stmt = $db->query("SELECT activity_type, entity_type, description, created_at 
                        FROM activity_logs 
                        ORDER BY created_at DESC 
                        LIMIT 5");
    echo "   Recent activities:\n";
    while ($log = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "     - [{$log['activity_type']}] {$log['entity_type']}: {$log['description']} ({$log['created_at']})\n";
    }
    
    echo "\n=== ALL TESTS COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
