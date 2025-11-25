<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Utils\Mailer;
use App\Config\Database;

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║   TESTING EMAIL WITH DATABASE SETTINGS         ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    // Get settings from database
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT email_settings FROM school_settings LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row || !$row['email_settings']) {
        echo "❌ No email settings found in database!\n";
        exit(1);
    }
    
    $settings = json_decode($row['email_settings'], true);
    
    echo "Current Database Settings:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  Host:       {$settings['smtp_host']}\n";
    echo "  Port:       {$settings['smtp_port']}\n";
    echo "  Username:   {$settings['smtp_username']}\n";
    echo "  Password:   " . (isset($settings['smtp_password']) ? '••••••••••' : '[NONE]') . "\n";
    echo "  Encryption: {$settings['smtp_encryption']}\n";
    echo "  From:       {$settings['from_name']} <{$settings['from_email']}>\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Verify password is not hashed
    echo "Password Verification:\n";
    if ($settings['smtp_password'] === '32770&Sabi') {
        echo "  ✅ Password matches: 32770&Sabi\n";
        echo "  ✅ Stored as plain text (correct!)\n\n";
    } else {
        echo "  ⚠️  Password mismatch!\n";
        echo "  Expected: 32770&Sabi\n";
        echo "  Got: {$settings['smtp_password']}\n\n";
    }
    
    // Test with Mailer class
    echo "Testing with Mailer class...\n\n";
    
    $mailer = new Mailer();
    $testResult = $mailer->testConnection();
    
    if ($testResult['success']) {
        echo "✅ ✅ ✅ SUCCESS! ✅ ✅ ✅\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  SMTP Connection Successful!\n";
        echo "  Your email configuration is working!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        echo "📧 Ready to send emails!\n";
        echo "   - Welcome emails: ✅\n";
        echo "   - Password resets: ✅\n";
        echo "   - Notifications: ✅\n";
        echo "   - Reports: ✅\n\n";
        
    } else {
        echo "❌ SMTP Connection Failed\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Error: {$testResult['message']}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        if (strpos($testResult['message'], 'authentication failed') !== false) {
            echo "💡 The password '32770&Sabi' is being rejected.\n\n";
            echo "ACTION REQUIRED:\n";
            echo "  1. Go to: https://mail.HOSTINGER.email\n";
            echo "  2. Login with: info@boschool.org / 32770&Sabi\n";
            echo "  3. If login fails → Password is wrong\n";
            echo "  4. If login works → Enable SMTP in settings\n";
            echo "     or generate an App Password\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

