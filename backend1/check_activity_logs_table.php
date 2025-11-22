<?php
require_once __DIR__ . '/vendor/autoload.php';

$db = new PDO('mysql:host=localhost;dbname=school_management_system', 'root', '');
$stmt = $db->query('DESCRIBE activity_logs');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Activity Logs Table Structure:\n";
echo str_repeat("=", 50) . "\n";
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
