<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
$cols = $db->query('SHOW COLUMNS FROM fees_payments')->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
