<?php
/**
 * Direct Gmail Email Test
 * This script tests sending email using Gmail without database dependency
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Gmail Email Test ===\n\n";

// Gmail Configuration
// NOTE: You need to provide the Gmail App Password
$config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_encryption' => 'tls',
    'smtp_username' => 'koromaemmanuel66@gmail.com',
    'smtp_password' => '', // ⚠️ NEED APP PASSWORD HERE
    'from_email' => 'koromaemmanuel66@gmail.com',
    'from_name' => 'School Management System Test',
];

// Check if password is provided
if (empty($config['smtp_password'])) {
    echo "⚠️  ERROR: Gmail App Password is required!\n\n";
    echo "To get a Gmail App Password:\n";
    echo "1. Go to https://myaccount.google.com/security\n";
    echo "2. Enable 2-Step Verification (if not already enabled)\n";
    echo "3. Go to 'App passwords' section\n";
    echo "4. Generate a new app password for 'Mail'\n";
    echo "5. Copy the 16-character password\n";
    echo "6. Update this script with the password\n\n";
    exit(1);
}

// Get recipient email
echo "Enter recipient email address: ";
$recipientEmail = trim(fgets(STDIN));

if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo "✗ Invalid email address\n";
    exit(1);
}

echo "\n";
echo "Configuration:\n";
echo "  SMTP Host: {$config['smtp_host']}\n";
echo "  SMTP Port: {$config['smtp_port']}\n";
echo "  From: {$config['from_email']}\n";
echo "  To: {$recipientEmail}\n\n";

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    echo "1. Configuring SMTP...\n";
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_encryption'];
    $mail->Port = $config['smtp_port'];
    echo "   ✓ SMTP configured\n\n";

    // Recipients
    echo "2. Setting sender and recipient...\n";
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($recipientEmail);
    echo "   ✓ Addresses set\n\n";

    // Content
    echo "3. Preparing email content...\n";
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Test Email from School Management System';
    $mail->Body = '
        <div style="font-family: Arial, sans-serif; padding: 20px; max-width: 600px;">
            <h2 style="color: #333;">Email Configuration Test</h2>
            <p>This is a test email from <strong>koromaemmanuel66@gmail.com</strong></p>
            <p>If you received this email, your Gmail SMTP configuration is working correctly! ✓</p>
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #666; font-size: 12px;">Sent at: ' . date('Y-m-d H:i:s') . '</p>
        </div>
    ';
    $mail->AltBody = 'This is a test email. If you received this email, your SMTP configuration is working!';
    echo "   ✓ Content prepared\n\n";

    // Send
    echo "4. Sending email...\n";
    $result = $mail->send();
    
    if ($result) {
        echo "\n✓✓✓ SUCCESS! ✓✓✓\n";
        echo "Test email sent successfully to {$recipientEmail}\n";
        echo "Please check the recipient inbox (and spam folder)\n";
    }

} catch (Exception $e) {
    echo "\n✗✗✗ FAILED ✗✗✗\n";
    echo "Error: {$mail->ErrorInfo}\n";
    echo "Exception: {$e->getMessage()}\n";
}

echo "\n=== Test Complete ===\n";
