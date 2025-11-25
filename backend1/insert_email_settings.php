<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║   INSERTING HOSTINGER EMAIL SETTINGS TO DATABASE  ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Email settings - NOT HASHED, stored as-is
    $emailSettings = [
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465,
        'smtp_username' => 'info@boschool.org',
        'smtp_password' => '32770&Sabi', // Plain text, NOT hashed
        'smtp_encryption' => 'ssl',
        'from_email' => 'info@boschool.org',
        'from_name' => 'Bo Government Secondary School'
    ];
    
    echo "Email Configuration:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($emailSettings as $key => $value) {
        $displayValue = $key === 'smtp_password' ? '••••••••••' : $value;
        echo "  " . str_pad($key, 20) . ": $displayValue\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Check if school_settings table exists
    echo "Checking database tables...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'school_settings'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "⚠️  Creating school_settings table...\n";
        $db->exec("
            CREATE TABLE IF NOT EXISTS school_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                general_settings JSON DEFAULT NULL,
                notification_settings JSON DEFAULT NULL,
                email_settings JSON DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ Table created\n\n";
    } else {
        echo "✅ Table exists\n\n";
    }
    
    // Check if settings row exists
    echo "Checking for existing settings...\n";
    $stmt = $db->query("SELECT id, email_settings FROM school_settings LIMIT 1");
    $existingSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $emailSettingsJson = json_encode($emailSettings);
    
    if ($existingSettings) {
        echo "✅ Settings row exists (ID: {$existingSettings['id']})\n";
        echo "Updating email settings...\n\n";
        
        $stmt = $db->prepare("UPDATE school_settings SET email_settings = ? WHERE id = ?");
        $stmt->execute([$emailSettingsJson, $existingSettings['id']]);
        
        echo "✅ Email settings updated successfully!\n\n";
    } else {
        echo "Creating new settings row...\n";
        
        $generalSettings = json_encode([
            'school_name' => 'Bo Government Secondary School',
            'school_code' => 'BGSS',
            'school_address' => '',
            'school_phone' => '',
            'school_email' => 'info@boschool.org',
            'timezone' => 'Africa/Freetown'
        ]);
        
        $notificationSettings = json_encode([
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true,
            'notify_attendance' => true,
            'notify_results' => true,
            'notify_fees' => true
        ]);
        
        $stmt = $db->prepare("
            INSERT INTO school_settings (general_settings, notification_settings, email_settings) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$generalSettings, $notificationSettings, $emailSettingsJson]);
        
        echo "✅ New settings created successfully!\n\n";
    }
    
    // Verify settings were saved
    echo "Verifying saved settings...\n";
    $stmt = $db->query("SELECT email_settings FROM school_settings LIMIT 1");
    $saved = $stmt->fetch(PDO::FETCH_ASSOC);
    $savedEmail = json_decode($saved['email_settings'], true);
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Saved Settings:\n";
    foreach ($savedEmail as $key => $value) {
        $displayValue = $key === 'smtp_password' ? '••••••••••' : $value;
        echo "  " . str_pad($key, 20) . ": $displayValue\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Test if password matches
    if ($savedEmail['smtp_password'] === '32770&Sabi') {
        echo "✅ Password stored correctly (NOT hashed)\n";
        echo "✅ Password matches: 32770&Sabi\n\n";
    } else {
        echo "⚠️  Password mismatch!\n";
        echo "Expected: 32770&Sabi\n";
        echo "Got: {$savedEmail['smtp_password']}\n\n";
    }
    
    echo "╔════════════════════════════════════════════════╗\n";
    echo "║   ✅ EMAIL SETTINGS CONFIGURED SUCCESSFULLY   ║\n";
    echo "╚════════════════════════════════════════════════╝\n\n";
    
    echo "Next Steps:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "1. The password '32770&Sabi' is now in the database\n";
    echo "2. Password is stored as plain text (NOT hashed)\n";
    echo "3. You still need to verify the password with HOSTINGER:\n";
    echo "   → Go to: https://mail.HOSTINGER.email\n";
    echo "   → Login with: info@boschool.org / 32770&Sabi\n";
    echo "   → If login fails, the password is incorrect\n";
    echo "   → If login works, check SMTP settings in HOSTINGER\n";
    echo "4. Test email from the system UI or run:\n";
    echo "   → php test_both_ports.php\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}


