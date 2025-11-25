<?php
/**
 * Comprehensive HOSTINGER Email Test
 * Tests credentials with different variations
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔════════════════════════════════════════════════╗\n";
echo "║   Comprehensive HOSTINGER Email Connection Test   ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

$testEmail = 'koromaemmanuel66@gmail.com'; // Recipient for test

$configurations = [
    [
        'name' => 'SSL on Port 465',
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'encryption' => PHPMailer::ENCRYPTION_SMTPS, // ssl
        'username' => 'info@boschool.org',
        'password' => '32770&Sabi',
    ],
    [
        'name' => 'STARTTLS on Port 587',
        'host' => 'smtp.hostinger.com',
        'port' => 587,
        'encryption' => PHPMailer::ENCRYPTION_STARTTLS, // tls
        'username' => 'info@boschool.org',
        'password' => '32770&Sabi',
    ],
    [
        'name' => 'TLS on Port 587',
        'host' => 'smtp.hostinger.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'info@boschool.org',
        'password' => '32770&Sabi',
    ],
    [
        'name' => 'SSL on Port 465 (with SMTPAutoTLS)',
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'info@boschool.org',
        'password' => '32770&Sabi',
        'auto_tls' => false,
    ],
];

foreach ($configurations as $index => $config) {
    echo "\n";
    echo str_repeat("─", 60) . "\n";
    echo "Test #" . ($index + 1) . ": {$config['name']}\n";
    echo str_repeat("─", 60) . "\n";

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 2;
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        $mail->Timeout = 15;
        
        if (isset($config['auto_tls'])) {
            $mail->SMTPAutoTLS = $config['auto_tls'];
        }

        // Sender and recipient
        $mail->setFrom('info@boschool.org', 'BO School Test');
        $mail->addAddress($testEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email - Config ' . ($index + 1);
        $mail->Body = '<h2>Success!</h2><p>This configuration works: ' . $config['name'] . '</p>';

        // Try to send
        $sent = $mail->send();

        if ($sent) {
            echo "\n";
            echo "╔════════════════════════════════════════╗\n";
            echo "║     ✓✓✓ SUCCESS! THIS WORKS! ✓✓✓     ║\n";
            echo "╚════════════════════════════════════════╝\n";
            echo "\nWorking Configuration:\n";
            echo "  Host: {$config['host']}\n";
            echo "  Port: {$config['port']}\n";
            echo "  Encryption: {$config['encryption']}\n";
            echo "  Username: {$config['username']}\n";
            echo "  Password: {$config['password']}\n\n";
            echo "✓ Email sent to: $testEmail\n";
            echo "✓ Check inbox (and spam folder)\n\n";
            
            // Found working config, exit
            exit(0);
        }

    } catch (Exception $e) {
        echo "\n✗ Failed: " . $e->getMessage() . "\n";
        if ($mail->ErrorInfo) {
            echo "✗ Error Info: " . $mail->ErrorInfo . "\n";
        }
    }

    echo "\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════╗\n";
echo "║     ✗ All Configurations Failed                ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

echo "Possible Issues:\n";
echo "  1. Password is incorrect\n";
echo "  2. SMTP access not enabled in HOSTINGER Email\n";
echo "  3. Two-factor authentication requiring app password\n";
echo "  4. IP address needs to be whitelisted\n";
echo "  5. Account suspended or inactive\n\n";

echo "Next Steps:\n";
echo "  1. Login to HOSTINGER Email webmail with these credentials\n";
echo "  2. Check if SMTP is enabled in email settings\n";
echo "  3. Generate app-specific password if using 2FA\n";
echo "  4. Contact HOSTINGER Email support for SMTP settings\n\n";


