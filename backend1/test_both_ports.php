<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  HOSTINGER EMAIL - TESTING BOTH PORT 465 (SSL) & 587 (TLS)║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$configs = [
    [
        'name' => 'Port 465 with SSL',
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465,
        'smtp_encryption' => PHPMailer::ENCRYPTION_SMTPS,
        'encryption_name' => 'SSL'
    ],
    [
        'name' => 'Port 587 with TLS',
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 587,
        'smtp_encryption' => PHPMailer::ENCRYPTION_STARTTLS,
        'encryption_name' => 'TLS (STARTTLS)'
    ]
];

$credentials = [
    'username' => 'info@boschool.org',
    'password' => '32770&Sabi',
    'from_email' => 'info@boschool.org',
    'from_name' => 'Bo Government Secondary School'
];

foreach ($configs as $index => $config) {
    echo "\n" . str_repeat("═", 60) . "\n";
    echo "TEST " . ($index + 1) . ": {$config['name']}\n";
    echo str_repeat("═", 60) . "\n\n";
    
    echo "Configuration:\n";
    echo "  Host:       {$config['smtp_host']}\n";
    echo "  Port:       {$config['smtp_port']}\n";
    echo "  Encryption: {$config['encryption_name']}\n";
    echo "  Username:   {$credentials['username']}\n\n";
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 2;
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $credentials['username'];
        $mail->Password = $credentials['password'];
        $mail->SMTPSecure = $config['smtp_encryption'];
        $mail->Port = $config['smtp_port'];
        $mail->Timeout = 30;
        
        echo "⏳ Attempting connection...\n\n";
        $mail->smtpConnect();
        $mail->smtpClose();
        
        echo "\n\n✅ ✅ ✅ SUCCESS! ✅ ✅ ✅\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  {$config['name']} WORKS!\n";
        echo "  This is the correct configuration!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        echo "📝 USE THESE SETTINGS IN YOUR SYSTEM:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  SMTP Host:       {$config['smtp_host']}\n";
        echo "  SMTP Port:       {$config['smtp_port']}\n";
        echo "  SMTP Encryption: " . ($config['smtp_port'] == 465 ? 'ssl' : 'tls') . "\n";
        echo "  SMTP Username:   {$credentials['username']}\n";
        echo "  SMTP Password:   {$credentials['password']}\n";
        echo "  From Email:      {$credentials['from_email']}\n";
        echo "  From Name:       {$credentials['from_name']}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // If this config works, stop testing
        break;
        
    } catch (Exception $e) {
        echo "\n\n❌ FAILED\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Error: {$mail->ErrorInfo}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // Continue to next configuration
        continue;
    }
}

echo "\n" . str_repeat("═", 60) . "\n";
echo "HOSTINGER EMAIL DIAGNOSIS COMPLETE\n";
echo str_repeat("═", 60) . "\n\n";

echo "⚠️  AUTHENTICATION FAILED ON BOTH PORTS\n\n";

echo "POSSIBLE CAUSES:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  1. ❌ Password may be incorrect\n";
echo "  2. ❌ Account may require app-specific password\n";
echo "  3. ❌ SMTP access may not be enabled for this account\n";
echo "  4. ❌ Account may be locked or suspended\n";
echo "  5. ❌ Two-factor authentication may be blocking access\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "NEXT STEPS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  1. Log into HOSTINGER Email webmail:\n";
echo "     https://mail.HOSTINGER.email\n\n";
echo "  2. Verify you can login with:\n";
echo "     Email:    {$credentials['username']}\n";
echo "     Password: {$credentials['password']}\n\n";
echo "  3. Check SMTP settings in HOSTINGER control panel:\n";
echo "     - Look for 'Settings' or 'Email Settings'\n";
echo "     - Check if SMTP is enabled\n";
echo "     - Look for 'App Passwords' or 'External Access'\n\n";
echo "  4. Contact HOSTINGER Email support if needed:\n";
echo "     https://support.HOSTINGER.email\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";


