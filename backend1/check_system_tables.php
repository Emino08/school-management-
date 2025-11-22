<?php
/**
 * Check System Tables
 * Verify all required tables and columns exist
 */

require_once __DIR__ . '/vendor/autoload.php';

try {
    $database = \App\Config\Database::getInstance();
    $db = $database->getConnection();
    
    echo "Checking system tables...\n\n";
    
    $requiredTables = [
        'activity_logs' => ['id', 'user_id', 'user_type', 'activity_type', 'description'],
        'teachers' => ['id', 'first_name', 'last_name', 'email', 'is_town_master'],
        'students' => ['id', 'first_name', 'last_name', 'id_number'],
        'parents' => ['id', 'email', 'first_name', 'last_name'],
        'parent_student_links' => ['id', 'parent_id', 'student_id'],
        'towns' => ['id', 'name', 'admin_id'],
        'town_blocks' => ['id', 'town_id', 'block_name', 'capacity'],
        'town_master_assignments' => ['id', 'teacher_id', 'town_id', 'academic_year_id'],
        'town_student_registrations' => ['id', 'student_id', 'town_id', 'block_id'],
        'town_attendance' => ['id', 'student_id', 'town_id', 'attendance_date', 'status'],
        'urgent_notifications' => ['id', 'student_id', 'notification_type', 'action_required'],
        'settings' => ['id', 'key', 'value']
    ];
    
    $allOk = true;
    
    foreach ($requiredTables as $table => $columns) {
        // Check if table exists
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            echo "✗ Table '$table' is missing!\n";
            $allOk = false;
            continue;
        }
        
        // Check columns
        $stmt = $db->query("DESCRIBE $table");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $missingColumns = array_diff($columns, $existingColumns);
        if (!empty($missingColumns)) {
            echo "✗ Table '$table' is missing columns: " . implode(', ', $missingColumns) . "\n";
            $allOk = false;
        } else {
            echo "✓ Table '$table' OK\n";
        }
    }
    
    echo "\n";
    
    if ($allOk) {
        echo "✓✓✓ All system tables verified successfully! ✓✓✓\n";
    } else {
        echo "⚠ Some tables or columns are missing. Please run migrations.\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
