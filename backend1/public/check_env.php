<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

echo json_encode([
    'jwt_secret_loaded' => !empty($_ENV['JWT_SECRET']),
    'jwt_secret_length' => isset($_ENV['JWT_SECRET']) ? strlen($_ENV['JWT_SECRET']) : 0,
    'jwt_expiry' => $_ENV['JWT_EXPIRY'] ?? 'NOT SET',
    'app_env' => $_ENV['APP_ENV'] ?? 'NOT SET',
    'app_debug' => $_ENV['APP_DEBUG'] ?? 'NOT SET',
    'timestamp' => time(),
    'date' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
