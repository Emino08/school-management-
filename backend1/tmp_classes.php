<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
print_r($db->query("SELECT id,class_name,admin_id FROM classes WHERE admin_id=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC));
