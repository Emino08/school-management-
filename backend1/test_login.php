<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Admin;

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║   TESTING LOGIN PROCESS                        ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

// Test credentials
$credentials = [
    ['email' => 'admin@boschool.org', 'password' => 'admin123'],
    ['email' => 'emk32770@gmail.com', 'password' => '32770&Sabi'],
];

$adminModel = new Admin();

foreach ($credentials as $cred) {
    echo "Testing: {$cred['email']}\n";
    echo "Password: {$cred['password']}\n";
    
    try {
        // Step 1: Find by email
        $account = $adminModel->findByEmail($cred['email']);
        
        if (!$account) {
            echo "❌ Account not found\n\n";
            continue;
        }
        
        echo "✅ Account found\n";
        echo "   ID: {$account['id']}\n";
        echo "   Email: {$account['email']}\n";
        echo "   Name: " . ($account['name'] ?? 'N/A') . "\n";
        echo "   Role: {$account['role']}\n";
        
        // Step 2: Verify password
        $passwordValid = $adminModel->verifyPassword($cred['password'], $account['password']);
        
        if ($passwordValid) {
            echo "✅ Password is VALID\n";
            echo "   Login would SUCCEED\n\n";
        } else {
            echo "❌ Password is INVALID\n";
            echo "   Login would FAIL\n";
            
            // Debug: Test direct password_verify
            $directTest = password_verify($cred['password'], $account['password']);
            echo "   Direct password_verify: " . ($directTest ? 'PASS' : 'FAIL') . "\n";
            
            // Show password hash for debugging
            echo "   Password hash: " . substr($account['password'], 0, 30) . "...\n\n";
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "╔════════════════════════════════════════════════╗\n";
echo "║   TEST COMPLETE                                ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

echo "If both tests show '✅ Password is VALID', then the issue\n";
echo "is likely in the API request (CORS, headers, etc.)\n\n";

echo "Try logging in with:\n";
echo "  Email:    admin@boschool.org\n";
echo "  Password: admin123\n\n";
