<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
print_r($db->query('SELECT * FROM teacher_assignments')->fetchAll(PDO::FETCH_ASSOC));
