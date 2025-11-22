<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== JWT DEBUG TEST ===" . PHP_EOL . PHP_EOL;

// Test 1: Check environment variables
echo "1. Environment Variables:" . PHP_EOL;
echo "   JWT_SECRET: " . ($_ENV['JWT_SECRET'] ?? 'NOT SET') . PHP_EOL;
echo "   JWT_EXPIRY: " . ($_ENV['JWT_EXPIRY'] ?? 'NOT SET') . PHP_EOL . PHP_EOL;

// Test 2: Generate a token using the wrapper
echo "2. Generating token using App\\Utils\\JWT:" . PHP_EOL;
$payload = [
    'id' => 1,
    'role' => 'Admin',
    'email' => 'test@example.com',
    'admin_id' => 1,
    'account_id' => 1
];

$token = \App\Utils\JWT::encode($payload);
echo "   Token: " . substr($token, 0, 50) . "..." . PHP_EOL . PHP_EOL;

// Test 3: Decode the token
echo "3. Decoding token using App\\Utils\\JWT:" . PHP_EOL;
try {
    $decoded = \App\Utils\JWT::decode($token);
    echo "   ✓ Decode successful!" . PHP_EOL;
    echo "   Decoded payload:" . PHP_EOL;
    print_r($decoded);
    echo PHP_EOL;
} catch (Exception $e) {
    echo "   ✗ Decode failed: " . $e->getMessage() . PHP_EOL . PHP_EOL;
}

// Test 4: Simulate what happens in AuthMiddleware
echo "4. Simulating AuthMiddleware decode:" . PHP_EOL;
try {
    $decoded2 = \App\Utils\JWT::decode($token);
    echo "   ✓ AuthMiddleware simulation successful!" . PHP_EOL;
    echo "   User ID: " . ($decoded2->id ?? 'N/A') . PHP_EOL;
    echo "   Role: " . ($decoded2->role ?? 'N/A') . PHP_EOL;
    echo "   Issued at: " . ($decoded2->iat ?? 'N/A') . " (" . (isset($decoded2->iat) ? date('Y-m-d H:i:s', $decoded2->iat) : 'N/A') . ")" . PHP_EOL;
    echo "   Expires at: " . ($decoded2->exp ?? 'N/A') . " (" . (isset($decoded2->exp) ? date('Y-m-d H:i:s', $decoded2->exp) : 'N/A') . ")" . PHP_EOL;
} catch (Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== END DEBUG TEST ===" . PHP_EOL;
