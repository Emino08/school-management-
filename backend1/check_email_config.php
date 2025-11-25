<?php
require_once 'vendor/autoload.php';

use App\Config\Database;
use PDO;

// Get admin user details
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
$stmt->execute(['koromaemmanuel66@gmail.com']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "Admin Found:\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Name: " . $admin['contact_name'] . "\n";
} else {
    echo "Admin not found. Checking users table...\n";
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['koromaemmanuel66@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "User Found:\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
    }
}

// Check email settings
$stmt = $db->query("SELECT email_settings FROM school_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
if ($settings && $settings['email_settings']) {
    echo "\nCurrent Email Settings:\n";
    $emailSettings = json_decode($settings['email_settings'], true);
    echo "SMTP Host: " . ($emailSettings['smtp_host'] ?? 'Not set') . "\n";
    echo "SMTP Port: " . ($emailSettings['smtp_port'] ?? 'Not set') . "\n";
    echo "SMTP Username: " . ($emailSettings['smtp_username'] ?? 'Not set') . "\n";
    echo "From Email: " . ($emailSettings['from_email'] ?? 'Not set') . "\n";
    echo "Password Set: " . (empty($emailSettings['smtp_password']) ? 'No' : 'Yes') . "\n";
} else {
    echo "\nNo email settings configured in database.\n";
}
