<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Utils\JWT;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== JWT Token Debug Test ===" . PHP_EOL;
echo "JWT_SECRET: " . ($_ENV['JWT_SECRET'] ?? 'NOT SET') . PHP_EOL;
echo "JWT_EXPIRY: " . ($_ENV['JWT_EXPIRY'] ?? 'NOT SET') . PHP_EOL;
echo PHP_EOL;

// Test 1: Create a new token
echo "Test 1: Creating a new token..." . PHP_EOL;
try {
    $payload = [
        'id' => 1,
        'role' => 'Admin',
        'email' => 'admin@test.com',
        'admin_id' => 1,
        'account_id' => 1
    ];
    
    $token = JWT::encode($payload);
    echo "✓ Token created successfully" . PHP_EOL;
    echo "Token: " . substr($token, 0, 50) . "..." . PHP_EOL;
    echo PHP_EOL;
    
    // Test 2: Decode the token
    echo "Test 2: Decoding the token..." . PHP_EOL;
    $decoded = JWT::decode($token);
    echo "✓ Token decoded successfully" . PHP_EOL;
    echo "Decoded payload:" . PHP_EOL;
    print_r($decoded);
    echo PHP_EOL;
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

// Test 3: Try to decode a potentially expired token (if you have one)
echo "Test 3: Paste a token from your browser localStorage to test it:" . PHP_EOL;
echo "(Press Enter to skip this test)" . PHP_EOL;

if (php_sapi_name() === 'cli') {
    $testToken = trim(fgets(STDIN));
    
    if (!empty($testToken)) {
        echo "Testing provided token..." . PHP_EOL;
        try {
            $decoded = JWT::decode($testToken);
            echo "✓ Token is valid!" . PHP_EOL;
            echo "Payload:" . PHP_EOL;
            print_r($decoded);
            
            // Check expiration
            if (isset($decoded->exp)) {
                $expiresAt = date('Y-m-d H:i:s', $decoded->exp);
                $now = date('Y-m-d H:i:s');
                $isExpired = $decoded->exp < time();
                
                echo "Issued at: " . date('Y-m-d H:i:s', $decoded->iat ?? 0) . PHP_EOL;
                echo "Expires at: " . $expiresAt . PHP_EOL;
                echo "Current time: " . $now . PHP_EOL;
                echo "Status: " . ($isExpired ? "EXPIRED" : "VALID") . PHP_EOL;
            }
        } catch (\Firebase\JWT\ExpiredException $e) {
            echo "✗ Token has expired!" . PHP_EOL;
            echo "Error: " . $e->getMessage() . PHP_EOL;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            echo "✗ Invalid token signature!" . PHP_EOL;
            echo "Error: " . $e->getMessage() . PHP_EOL;
        } catch (\Exception $e) {
            echo "✗ Token is invalid!" . PHP_EOL;
            echo "Error: " . $e->getMessage() . PHP_EOL;
        }
    }
}

echo PHP_EOL;
echo "=== Test Complete ===" . PHP_EOL;
