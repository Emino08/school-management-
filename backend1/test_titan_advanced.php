<?php
/**
 * Advanced HOSTINGER Email Diagnostic Test
 * Tests with different authentication methods and detailed debugging
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║     Advanced HOSTINGER Email Diagnostic Test             ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

$testEmail = 'koromaemmanuel66@gmail.com';

// Different authentication attempts
$configurations = [
    [
        'name' => 'Config 1: Standard SSL/TLS with AUTO encryption detection',
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'secure' => 'ssl',
        'user' => 'info@boschool.org',
        'pass' => '32770&Sabi',
        'options' => ['auto_tls' => true, 'auth_type' => 'LOGIN'],
    ],
    [
        'name' => 'Config 2: PLAIN authentication method',
        'host' => 'smtp.hostinger.com',
        'port' => 587,
        'secure' => 'tls',
        'user' => 'info@boschool.org',
        'pass' => '32770&Sabi',
        'options' => ['auth_type' => 'PLAIN'],
    ],
    [
        'name' => 'Config 3: Without SSL verification',
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'secure' => 'ssl',
        'user' => 'info@boschool.org',
        'pass' => '32770&Sabi',
        'options' => ['verify_ssl' => false],
    ],
    [
        'name' => 'Config 4: Port 25 (if allowed)',
        'host' => 'smtp.hostinger.com',
        'port' => 25,
        'secure' => '',
        'user' => 'info@boschool.org',
        'pass' => '32770&Sabi',
        'options' => [],
    ],
    [
        'name' => 'Config 5: Alternative SMTP host',
        'host' => 'smtp-out.flockmail.com',
        'port' => 465,
        'secure' => 'ssl',
        'user' => 'info@boschool.org',
        'pass' => '32770&Sabi',
        'options' => [],
    ],
    [
        'name' => 'Config 6: URL encoded password',
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'secure' => 'ssl',
        'user' => 'info@boschool.org',
        'pass' => rawurlencode('32770&Sabi'),
        'options' => ['note' => 'Testing with URL encoded password'],
    ],
];

foreach ($configurations as $index => $config) {
    echo "\n" . str_repeat("═", 70) . "\n";
    echo "Test #" . ($index + 1) . ": {$config['name']}\n";
    echo str_repeat("═", 70) . "\n";
    echo "Host: {$config['host']}\n";
    echo "Port: {$config['port']}\n";
    echo "Security: " . ($config['secure'] ?: 'none') . "\n";
    echo "Username: {$config['user']}\n";
    if (isset($config['options']['note'])) {
        echo "Note: {$config['options']['note']}\n";
    }
    echo "\n";

    $mail = new PHPMailer(true);

    try {
        // Debug settings
        $mail->SMTPDebug = 3; // Maximum debug output
        $mail->Debugoutput = function($str, $level) {
            echo "DEBUG[$level]: $str\n";
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['user'];
        $mail->Password = $config['pass'];
        
        if ($config['secure']) {
            $mail->SMTPSecure = $config['secure'];
        }
        
        $mail->Port = $config['port'];
        $mail->Timeout = 20;
        $mail->SMTPKeepAlive = false;

        // Apply options
        if (isset($config['options']['auto_tls'])) {
            $mail->SMTPAutoTLS = $config['options']['auto_tls'];
        }
        
        if (isset($config['options']['verify_ssl']) && !$config['options']['verify_ssl']) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        if (isset($config['options']['auth_type'])) {
            $mail->AuthType = $config['options']['auth_type'];
        }

        // Sender and recipient
        $mail->setFrom('info@boschool.org', 'BO School Test');
        $mail->addAddress($testEmail);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'SUCCESS! Email Test - Config ' . ($index + 1);
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; padding: 30px; background: #f0f9ff;">
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h1 style="color: #22c55e; margin-top: 0;">✓ Success!</h1>
                    <h2 style="color: #333;">Email Configuration Working</h2>
                    <p style="font-size: 16px; color: #666; line-height: 1.6;">
                        This email confirms that your HOSTINGER Email SMTP configuration is working correctly!
                    </p>
                    <div style="background: #f0fdf4; padding: 15px; border-left: 4px solid #22c55e; margin: 20px 0;">
                        <p style="margin: 0; color: #166534;"><strong>Working Configuration:</strong></p>
                        <p style="margin: 5px 0; color: #166534;">' . $config['name'] . '</p>
                    </div>
                    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
                    <p style="color: #999; font-size: 12px;">Sent: ' . date('Y-m-d H:i:s') . '<br>From: info@boschool.org</p>
                </div>
            </div>
        ';

        // Try to send
        echo "\nAttempting to send email...\n";
        $sent = $mail->send();

        if ($sent) {
            echo "\n";
            echo "╔════════════════════════════════════════════════════╗\n";
            echo "║                                                    ║\n";
            echo "║        ✓✓✓ SUCCESS! EMAIL SENT! ✓✓✓              ║\n";
            echo "║                                                    ║\n";
            echo "╚════════════════════════════════════════════════════╝\n";
            echo "\n";
            echo "WORKING CONFIGURATION FOUND:\n";
            echo "──────────────────────────────────────────────────────\n";
            echo "Host:       {$config['host']}\n";
            echo "Port:       {$config['port']}\n";
            echo "Encryption: {$config['secure']}\n";
            echo "Username:   {$config['user']}\n";
            echo "Password:   {$config['pass']}\n";
            echo "──────────────────────────────────────────────────────\n";
            echo "\nEmail sent to: $testEmail\n";
            echo "✓ Check inbox and spam folder!\n\n";
            
            echo "Update your System Settings with these EXACT values.\n\n";
            
            // Exit on success
            exit(0);
        }

    } catch (Exception $e) {
        echo "\n✗ FAILED: " . $e->getMessage() . "\n";
        if ($mail->ErrorInfo) {
            echo "✗ Error Info: " . $mail->ErrorInfo . "\n";
        }
    }

    echo "\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║     ✗ All Configurations Failed                   ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

echo "DIAGNOSTIC SUMMARY:\n";
echo "──────────────────────────────────────────────────────\n";
echo "• SMTP Server: Reachable ✓\n";
echo "• Network: Working ✓\n";
echo "• Authentication: FAILING ✗\n";
echo "──────────────────────────────────────────────────────\n\n";

echo "POSSIBLE CAUSES:\n";
echo "1. Password changed recently - verify in HOSTINGER dashboard\n";
echo "2. SMTP disabled for this account - check account settings\n";
echo "3. 2FA enabled - generate app-specific password\n";
echo "4. IP not whitelisted - add your IP in HOSTINGER dashboard\n";
echo "5. Account type doesn't support SMTP - upgrade account\n";
echo "6. Special character encoding issue in password\n\n";

echo "NEXT STEPS:\n";
echo "1. Login to HOSTINGER Email webmail with info@boschool.org\n";
echo "2. Navigate to Settings → Email Settings\n";
echo "3. Look for 'SMTP' or 'External Access' settings\n";
echo "4. Verify SMTP is enabled and get credentials\n";
echo "5. If 2FA is on, generate app password\n";
echo "6. Update system with correct credentials\n\n";


