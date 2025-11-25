<?php
require 'vendor/autoload.php';

$db = \App\Config\Database::getInstance()->getConnection();

echo "=== STUDENTS TABLE ===\n";
$stmt = $db->query('DESCRIBE students');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== PARENT TABLES ===\n";
$stmt = $db->query("SHOW TABLES LIKE '%parent%'");
while($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "\n";
}

echo "\n=== MEDICAL_RECORDS TABLE ===\n";
$stmt = $db->query('DESCRIBE medical_records');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== ADMINS TABLE ===\n";
$stmt = $db->query('DESCRIBE admins');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== SCHOOL_SETTINGS TABLE ===\n";
$stmt = $db->query('DESCRIBE school_settings');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
