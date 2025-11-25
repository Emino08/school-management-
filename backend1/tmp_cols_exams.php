<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
print_r($db->query('SHOW COLUMNS FROM exams')->fetchAll(PDO::FETCH_ASSOC));
