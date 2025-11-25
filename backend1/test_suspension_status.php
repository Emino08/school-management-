<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Student Suspension Status Distribution ===\n\n";
    
    $stmt = $db->query("
        SELECT suspension_status, COUNT(*) as count
        FROM students
        GROUP BY suspension_status
    ");
    
    printf("%-20s : %-10s\n", "Status", "Count");
    echo str_repeat('=', 40) . "\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("%-20s : %-10d\n", $row['suspension_status'], $row['count']);
    }
    
    echo "\n✅ Most students should have 'active' status\n";
    echo "   This means they are enrolled and attending (NOT suspended)\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

