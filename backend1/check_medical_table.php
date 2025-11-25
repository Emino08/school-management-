<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$pdo = new PDO('mysql:host=localhost;port=4306;dbname=school_management', 'root', '1212');
$result = $pdo->query('DESCRIBE medical_records');
echo "Medical Records Table Structure:\n";
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
}
