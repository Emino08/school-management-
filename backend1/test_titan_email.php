<?php
/**
 * Direct HOSTINGER Email Test
 * Testing with info@boschool.org credentials
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== HOSTINGER Email Test ===\n\n";

// HOSTINGER Email Configuration
$config = [
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => 465,
    'smtp_encryption' => 'ssl',
    'smtp_username' => 'info@boschool.org',
    'smtp_password' => '32770&Sabi',
    'from_email' => 'info@boschool.org',
    'from_name' => 'BO School Management System',
];

echo "Configuration:\n";
echo "  SMTP Host: {$config['smtp_host']}\n";
echo "  SMTP Port: {$config['smtp_port']}\n";
echo "  Encryption: {$config['smtp_encryption']}\n";
echo "  Username: {$config['smtp_username']}\n";
echo "  From: {$config['from_email']}\n\n";

// Get recipient email
echo "Enter recipient email address: ";
$recipientEmail = trim(fgets(STDIN));

if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo "✗ Invalid email address\n";
    exit(1);
}

echo "\n";
echo "Recipient: {$recipientEmail}\n\n";

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
    $mail->Subject = 'Test Email from BO School Management System';
    $mail->Body = '
        <div style="font-family: Arial, sans-serif; padding: 20px; max-width: 600px; background-color: #f9f9f9;">
            <div style="background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="color: #6366f1; margin-top: 0;">Email Configuration Test ✓</h2>
                <p style="font-size: 16px; color: #333; line-height: 1.6;">
                    This is a test email from <strong>BO School Management System</strong>
                </p>
                <p style="font-size: 16px; color: #333; line-height: 1.6;">
                    Sent from: <strong>info@boschool.org</strong>
                </p>
                <div style="background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 12px; margin: 20px 0;">
                    <p style="margin: 0; color: #166534; font-weight: 500;">
                        ✓ If you received this email, your HOSTINGER SMTP configuration is working correctly!
                    </p>
                </div>
                <p style="font-size: 14px; color: #666; margin-top: 30px;">
                    Email Server: smtp.hostinger.com<br>
                    Port: 465 (SSL)
                </p>
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                <p style="color: #999; font-size: 12px; margin-bottom: 0;">
                    Sent at: ' . date('Y-m-d H:i:s') . '<br>
                    BO School Management System
                </p>
            </div>
        </div>
    ';
    $mail->AltBody = 'This is a test email from BO School Management System. If you received this email, your SMTP configuration is working!';
    echo "   ✓ Content prepared\n\n";

    // Send
    echo "4. Sending email...\n";
    echo "----------------------------------------\n";
    $result = $mail->send();
    echo "----------------------------------------\n\n";
    
    if ($result) {
        echo "\n";
        echo "╔════════════════════════════════════════╗\n";
        echo "║          ✓✓✓ SUCCESS! ✓✓✓             ║\n";
        echo "╚════════════════════════════════════════╝\n";
        echo "\n";
        echo "Test email sent successfully to: {$recipientEmail}\n";
        echo "From: {$config['from_email']}\n";
        echo "\n";
        echo "Please check:\n";
        echo "  - Recipient inbox\n";
        echo "  - Spam/Junk folder\n";
        echo "\n";
        echo "If received, your email configuration is working perfectly!\n";
    }

} catch (Exception $e) {
    echo "\n";
    echo "╔════════════════════════════════════════╗\n";
    echo "║           ✗✗✗ FAILED ✗✗✗              ║\n";
    echo "╚════════════════════════════════════════╝\n";
    echo "\n";
    echo "Error: {$mail->ErrorInfo}\n";
    echo "Exception: {$e->getMessage()}\n";
    echo "\n";
    echo "Common issues:\n";
    echo "  1. Wrong password - verify: 32770&Sabi\n";
    echo "  2. Firewall blocking port 465\n";
    echo "  3. SSL certificate issues\n";
    echo "  4. Account not configured for SMTP access\n";
}

echo "\n=== Test Complete ===\n";


