<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
$rows = $db->query("SELECT id,email,role,parent_admin_id,is_super_admin FROM admins")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
