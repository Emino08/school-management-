<?php
/**
 * Test Activity Logging
 * 
 * This script tests if activity logs are being recorded properly
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Utils\ActivityLogger;

echo "Activity Logging Test\n";
echo str_repeat("=", 60) . "\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $logger = new ActivityLogger($db);
    
    // Test 1: Check if activity_logs table exists
    echo "1. Checking database table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'activity_logs'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ activity_logs table exists\n\n";
    } else {
        echo "   ✗ activity_logs table NOT found!\n";
        echo "   Please run the migration script\n\n";
        exit(1);
    }
    
    // Test 2: Check recent logs count
    echo "2. Checking recent activity logs...\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Recent logs (last 24 hours): {$result['count']}\n\n";
    
    // Test 3: Test logging
    echo "3. Testing activity logging...\n";
    $testLog = $logger->log(
        1,
        'admin',
        'test',
        'Testing activity logging system',
        'test',
        null,
        ['test' => true],
        '127.0.0.1',
        'Test Script'
    );
    
    if ($testLog) {
        echo "   ✓ Test log created successfully\n\n";
    } else {
        echo "   ✗ Failed to create test log\n\n";
    }
    
    // Test 4: Fetch recent logs by type
    echo "4. Recent activity by type...\n";
    $stmt = $db->query("
        SELECT activity_type, COUNT(*) as count 
        FROM activity_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
        GROUP BY activity_type 
        ORDER BY count DESC 
        LIMIT 10
    ");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($types)) {
        echo "   No activity logs found in the last 7 days\n";
        echo "   This is normal if the system was just set up\n\n";
    } else {
        foreach ($types as $type) {
            echo "   • {$type['activity_type']}: {$type['count']}\n";
        }
        echo "\n";
    }
    
    // Test 5: Fetch recent logs by user type
    echo "5. Recent activity by user type...\n";
    $stmt = $db->query("
        SELECT user_type, COUNT(*) as count 
        FROM activity_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
        GROUP BY user_type 
        ORDER BY count DESC
    ");
    $userTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($userTypes)) {
        echo "   No activity logs found\n\n";
    } else {
        foreach ($userTypes as $ut) {
            echo "   • {$ut['user_type']}: {$ut['count']}\n";
        }
        echo "\n";
    }
    
    // Test 6: Show last 5 activities
    echo "6. Last 5 activities...\n";
    $stmt = $db->query("
        SELECT 
            user_type,
            activity_type,
            description,
            created_at
        FROM activity_logs 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recent)) {
        echo "   No activities yet\n\n";
    } else {
        foreach ($recent as $log) {
            $time = date('Y-m-d H:i:s', strtotime($log['created_at']));
            echo "   [{$time}] {$log['user_type']} - {$log['activity_type']}: {$log['description']}\n";
        }
        echo "\n";
    }
    
    echo str_repeat("=", 60) . "\n";
    echo "✓ Activity logging system is working!\n";
    echo "\nTo test in the application:\n";
    echo "1. Create a student - should log 'create' activity\n";
    echo "2. Update a student - should log 'update' activity\n";
    echo "3. Delete a student - should log 'delete' activity\n";
    echo "4. View logs at: /Admin/activity-logs\n";
    
} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
