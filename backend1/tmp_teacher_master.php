<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
print_r($db->query("SELECT id,name,is_class_master,class_master_of,admin_id FROM teachers WHERE admin_id=1")->fetchAll(PDO::FETCH_ASSOC));
