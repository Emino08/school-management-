<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$a = new App\Models\Admin();
var_dump($a->getEffectiveAdminId(1));
