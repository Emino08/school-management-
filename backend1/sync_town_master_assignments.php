<?php
/**
 * Synchronize Existing Town Master Assignments
 * Links teachers marked as town masters to the existing town
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

try {
    echo "Synchronizing Town Master Assignments...\n\n";
    
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Get the first/existing town
    $stmt = $pdo->query("SELECT id, name FROM towns ORDER BY id ASC LIMIT 1");
    $town = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$town) {
        echo "No town found. Creating default town...\n";
        
        // Create a default town if none exists
        $pdo->exec("
            INSERT INTO towns (admin_id, name, description, created_at, updated_at)
            VALUES (1, 'Main House', 'Default school house', NOW(), NOW())
        ");
        
        $townId = $pdo->lastInsertId();
        
        // Create blocks A-F
        $blocks = ['A', 'B', 'C', 'D', 'E', 'F'];
        $stmt = $pdo->prepare("
            INSERT INTO blocks (town_id, name, capacity, created_at, updated_at)
            VALUES (?, ?, 50, NOW(), NOW())
        ");
        
        foreach ($blocks as $block) {
            $stmt->execute([$townId, $block]);
        }
        
        echo "✓ Created default town: Main House (ID: {$townId})\n";
        echo "✓ Created blocks A-F\n\n";
    } else {
        $townId = $town['id'];
        echo "✓ Found existing town: {$town['name']} (ID: {$townId})\n\n";
    }
    
    // Find all teachers marked as town masters
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, is_town_master, town_master_of
        FROM teachers
        WHERE is_town_master = TRUE OR town_master_of IS NOT NULL
    ");
    $townMasterTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($townMasterTeachers)) {
        echo "No teachers marked as town masters found.\n";
        exit(0);
    }
    
    echo "Found " . count($townMasterTeachers) . " teacher(s) marked as town master:\n";
    foreach ($townMasterTeachers as $teacher) {
        echo "  - {$teacher['first_name']} {$teacher['last_name']} (ID: {$teacher['id']})\n";
    }
    echo "\n";
    
    $pdo->beginTransaction();
    
    $assignedCount = 0;
    $updatedCount = 0;
    
    foreach ($townMasterTeachers as $teacher) {
        // Check if already assigned in town_masters table
        $stmt = $pdo->prepare("
            SELECT id FROM town_masters 
            WHERE teacher_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$teacher['id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            echo "⚠ Teacher {$teacher['first_name']} {$teacher['last_name']} already assigned\n";
            continue;
        }
        
        // Assign teacher to the town
        $stmt = $pdo->prepare("
            INSERT INTO town_masters (town_id, teacher_id, assigned_by, is_active, assigned_at)
            VALUES (?, ?, 1, TRUE, NOW())
        ");
        $stmt->execute([$townId, $teacher['id']]);
        
        // Update teacher record
        $stmt = $pdo->prepare("
            UPDATE teachers 
            SET is_town_master = TRUE, town_master_of = ?
            WHERE id = ?
        ");
        $stmt->execute([$townId, $teacher['id']]);
        
        echo "✓ Assigned {$teacher['first_name']} {$teacher['last_name']} to town (ID: {$townId})\n";
        $assignedCount++;
    }
    
    $pdo->commit();
    
    echo "\n=== Synchronization Complete ===\n";
    echo "Teachers assigned: {$assignedCount}\n";
    
    // Verify assignments
    echo "\nVerifying assignments...\n";
    $stmt = $pdo->query("
        SELECT tm.id, t.name as town_name, 
               CONCAT(tc.first_name, ' ', tc.last_name) as teacher_name,
               tm.is_active
        FROM town_masters tm
        JOIN towns t ON tm.town_id = t.id
        JOIN teachers tc ON tm.teacher_id = tc.id
        WHERE tm.is_active = TRUE
    ");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($assignments)) {
        echo "⚠ No active assignments found!\n";
    } else {
        echo "Active town master assignments:\n";
        foreach ($assignments as $assignment) {
            echo "  - {$assignment['teacher_name']} → {$assignment['town_name']}\n";
        }
    }
    
    echo "\n✓ Synchronization successful!\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n✗ Synchronization failed: " . $e->getMessage() . "\n";
    exit(1);
}
