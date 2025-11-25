<?php
/**
 * HOSTINGER Email Test - Try Multiple Configurations
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Multiple HOSTINGER Configurations ===\n\n";

$configurations = [
    [
        'name' => 'Config 1: Port 465 with SSL',
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465,
        'smtp_encryption' => 'ssl',
        'smtp_username' => 'info@boschool.org',
        'smtp_password' => '32770&Sabi',
    ],
    [
        'name' => 'Config 2: Port 587 with TLS',
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => 'info@boschool.org',
        'smtp_password' => '32770&Sabi',
    ],
    [
        'name' => 'Config 3: Port 587 with STARTTLS',
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'starttls',
        'smtp_username' => 'info@boschool.org',
        'smtp_password' => '32770&Sabi',
    ],
];

foreach ($configurations as $index => $config) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Testing: {$config['name']}\n";
    echo str_repeat("=", 60) . "\n";
    echo "Host: {$config['smtp_host']}\n";
    echo "Port: {$config['smtp_port']}\n";
    echo "Encryption: {$config['smtp_encryption']}\n";
    echo "Username: {$config['smtp_username']}\n\n";

    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 0; // Minimal debug
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_encryption'];
        $mail->Port = $config['smtp_port'];
        $mail->Timeout = 10;

        // Test connection
        echo "Testing SMTP connection...\n";
        $connected = $mail->smtpConnect();
        
        if ($connected) {
            echo "✓ SMTP Connection SUCCESSFUL!\n";
            echo "✓ Authentication SUCCESSFUL!\n";
            $mail->smtpClose();
            
            echo "\n";
            echo "╔════════════════════════════════════════╗\n";
            echo "║    ✓✓✓ THIS CONFIG WORKS! ✓✓✓        ║\n";
            echo "╚════════════════════════════════════════╝\n";
            echo "\nUse these settings in your System Settings:\n";
            echo "  SMTP Host: {$config['smtp_host']}\n";
            echo "  SMTP Port: {$config['smtp_port']}\n";
            echo "  Encryption: {$config['smtp_encryption']}\n";
            echo "  Username: {$config['smtp_username']}\n";
            echo "  Password: {$config['smtp_password']}\n";
            
            // Found working config, no need to test others
            break;
        } else {
            echo "✗ Connection failed\n";
        }
    } catch (Exception $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Test Complete\n";
echo str_repeat("=", 60) . "\n";


