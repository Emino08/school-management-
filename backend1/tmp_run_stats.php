<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$controller = new App\Controllers\AdminController();
$user = (object)[
    'id' => 1,
    'admin_id' => 1,
    'role' => 'Admin'
];
$ref = new ReflectionClass($controller);
$method = $ref->getMethod('calculateDashboardStats');
$method->setAccessible(true);
$result = $method->invoke($controller, $user);
var_dump($result);
