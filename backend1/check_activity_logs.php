<?php
require 'vendor/autoload.php';

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    
    echo "=== activity_logs table structure ===" . PHP_EOL;
    $stmt = $db->query('DESCRIBE activity_logs');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
    
    echo PHP_EOL . "=== Sample data ===" . PHP_EOL;
    $stmt = $db->query('SELECT * FROM activity_logs LIMIT 3');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
