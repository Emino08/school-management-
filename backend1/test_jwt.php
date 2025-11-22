<?php
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$secret = $_ENV['JWT_SECRET'];

echo "Testing JWT encoding and decoding..." . PHP_EOL;
echo "Secret: " . $secret . PHP_EOL . PHP_EOL;

// Create a test token
$payload = [
    'id' => 1,
    'role' => 'Admin',
    'email' => 'test@example.com',
    'admin_id' => 1,
    'account_id' => 1,
    'iat' => time(),
    'exp' => time() + (24 * 60 * 60)
];

echo "Payload:" . PHP_EOL;
print_r($payload);
echo PHP_EOL;

// Encode
$token = JWT::encode($payload, $secret, 'HS256');
echo "Generated Token: " . $token . PHP_EOL . PHP_EOL;

// Decode
try {
    $decoded = JWT::decode($token, new Key($secret, 'HS256'));
    echo "Decoded successfully!" . PHP_EOL;
    print_r($decoded);
} catch (Exception $e) {
    echo "Decode failed: " . $e->getMessage() . PHP_EOL;
}
