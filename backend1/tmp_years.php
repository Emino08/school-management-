<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
$years = $db->query("SELECT * FROM academic_years")->fetchAll(PDO::FETCH_ASSOC);
print_r($years);
