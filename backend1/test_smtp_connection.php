<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "\n╔═══════════════════════════════════════════════╗\n";
echo "║   HOSTINGER EMAIL CONNECTION TEST                 ║\n";
echo "╚═══════════════════════════════════════════════╝\n\n";

// HOSTINGER Email Configuration
$config = [
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => 465,
    'smtp_username' => 'info@boschool.org',
    'smtp_password' => '32770&Sabi',
    'smtp_encryption' => 'ssl', // Port 465 requires SSL
    'from_email' => 'info@boschool.org',
    'from_name' => 'Bo Government Secondary School'
];

echo "📧 Configuration Details:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  Host:       {$config['smtp_host']}\n";
echo "  Port:       {$config['smtp_port']}\n";
echo "  Username:   {$config['smtp_username']}\n";
echo "  Encryption: {$config['smtp_encryption']} (SSL)\n";
echo "  From:       {$config['from_name']}\n";
echo "              <{$config['from_email']}>\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "🔍 [TEST] Testing SMTP Connection...\n\n";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 2; // Verbose debug output
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL for port 465
    $mail->Port = $config['smtp_port'];
    $mail->Timeout = 30;
    
    echo "⏳ Attempting connection...\n\n";
    $mail->smtpConnect();
    $mail->smtpClose();
    
    echo "\n\n╔═══════════════════════════════════════════════╗\n";
    echo "║   ✅ SUCCESS: SMTP CONNECTION ESTABLISHED    ║\n";
    echo "╚═══════════════════════════════════════════════╝\n\n";
    
    echo "✓ Connection Details:\n";
    echo "  - Successfully authenticated with HOSTINGER Email\n";
    echo "  - SMTP server is reachable\n";
    echo "  - Credentials are valid\n";
    echo "  - Ready to send emails\n\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n\n╔═══════════════════════════════════════════════╗\n";
    echo "║   ❌ FAILED: CONNECTION ERROR                ║\n";
    echo "╚═══════════════════════════════════════════════╝\n\n";
    
    echo "✗ Error Details:\n";
    echo "  {$mail->ErrorInfo}\n\n";
    
    echo "🔧 Troubleshooting Steps:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  1. Verify SMTP host: smtp.hostinger.com\n";
    echo "  2. Confirm port 465 is not blocked by firewall\n";
    echo "  3. Check username: info@boschool.org\n";
    echo "  4. Verify password is correct\n";
    echo "  5. Ensure SSL/TLS is enabled in PHP\n";
    echo "  6. Check if extension=openssl is enabled in php.ini\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    exit(1);
}


