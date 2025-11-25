<?php
/**
 * Email Configuration Test Script
 * Run this directly to test email sending
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Utils\Mailer;
use App\Config\Database;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Email Configuration Test ===\n\n";

// Test 1: Check if Mailer class can be instantiated
echo "1. Testing Mailer instantiation...\n";
try {
    $mailer = new Mailer();
    echo "   ✓ Mailer instantiated successfully\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed to instantiate Mailer: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Test SMTP connection
echo "2. Testing SMTP connection...\n";
$connectionTest = $mailer->testConnection();
if ($connectionTest['success']) {
    echo "   ✓ SMTP connection successful\n\n";
} else {
    echo "   ✗ SMTP connection failed: " . $connectionTest['message'] . "\n";
    exit(1);
}

// Test 3: Send test email
echo "3. Sending test email...\n";
echo "   Enter recipient email address: ";
$recipientEmail = trim(fgets(STDIN));

if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo "   ✗ Invalid email address\n";
    exit(1);
}

$subject = 'Test Email from School Management System';
$body = '
    <div style="font-family: Arial, sans-serif; padding: 20px; max-width: 600px;">
        <h2 style="color: #333;">Email Configuration Test</h2>
        <p>This is a test email to verify your SMTP configuration is working correctly.</p>
        <p>If you received this email, your SMTP settings are configured properly!</p>
        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
        <p style="color: #666; font-size: 12px;">Sent at: ' . date('Y-m-d H:i:s') . '</p>
    </div>
';

try {
    $sent = $mailer->send($recipientEmail, $subject, $body);
    
    if ($sent) {
        echo "   ✓ Test email sent successfully to $recipientEmail\n";
        echo "   Please check the recipient inbox (including spam folder)\n";
    } else {
        echo "   ✗ Failed to send test email\n";
    }
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
