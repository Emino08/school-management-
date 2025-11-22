<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
use App\Config\Database;
$db = Database::getInstance();
$pdo = $db->getConnection();

echo "Towns table structure:\n";
$stmt = $pdo->query('DESCRIBE towns');
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nAdmins table id type:\n";
$stmt = $pdo->query('DESCRIBE admins');
while($row = $stmt->fetch()) {
    if ($row['Field'] == 'id') {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
}
