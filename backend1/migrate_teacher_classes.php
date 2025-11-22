<?php

require __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = Database::getInstance()->getConnection();

echo "=== Creating Teacher-Subject-Class Relationship Tables ===" . PHP_EOL . PHP_EOL;

try {
    // Create teacher_subject_assignments table
    $sql1 = "
    CREATE TABLE IF NOT EXISTS teacher_subject_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        subject_id INT NOT NULL,
        class_id INT NOT NULL,
        academic_year_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_assignment (teacher_id, subject_id, class_id, academic_year_id),
        INDEX idx_teacher (teacher_id),
        INDEX idx_subject (subject_id),
        INDEX idx_class (class_id),
        INDEX idx_year (academic_year_id),
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($sql1);
    echo "✓ Created teacher_subject_assignments table" . PHP_EOL;
    
    // Create notification_reads table for tracking who has read notifications
    $sql2 = "
    CREATE TABLE IF NOT EXISTS notification_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notification_id INT NOT NULL,
        user_id INT NOT NULL,
        user_role ENUM('Admin','Teacher','Student','Parent') NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_read (notification_id, user_id, user_role),
        INDEX idx_notification (notification_id),
        INDEX idx_user (user_id, user_role),
        FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($sql2);
    echo "✓ Created notification_reads table" . PHP_EOL;
    
    // Add some sample data if teachers and classes exist
    echo PHP_EOL . "Adding sample teacher-subject-class assignments..." . PHP_EOL;
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM teacher_subject_assignments");
    $existing = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($existing == 0) {
        // Get first teacher
        $stmt = $db->query("SELECT id FROM teachers LIMIT 1");
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get first few classes
        $stmt = $db->query("SELECT id FROM classes LIMIT 3");
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get first few subjects
        $stmt = $db->query("SELECT id FROM subjects LIMIT 3");
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($teacher && !empty($classes) && !empty($subjects)) {
            foreach ($classes as $class) {
                foreach ($subjects as $subject) {
                    try {
                        $stmt = $db->prepare("
                            INSERT IGNORE INTO teacher_subject_assignments 
                            (teacher_id, subject_id, class_id) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$teacher['id'], $subject['id'], $class['id']]);
                    } catch (Exception $e) {
                        // Ignore duplicates
                    }
                }
            }
            echo "✓ Added sample assignments for teacher " . $teacher['id'] . PHP_EOL;
        } else {
            echo "! No teachers/classes/subjects found - skipping sample data" . PHP_EOL;
        }
    } else {
        echo "! Assignments already exist - skipping sample data" . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ Migration complete!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
