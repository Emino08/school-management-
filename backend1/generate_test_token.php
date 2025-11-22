<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Utils\JWT;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = JWT::encode([
    'id' => 1,
    'role' => 'Admin',
    'email' => 'test@test.com',
    'admin_id' => 1,
    'account_id' => 1
]);

echo $token;
