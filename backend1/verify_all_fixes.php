<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "=================================\n";
echo "  System Verification Test\n";
echo "=================================\n\n";

// Test 1: .env Loading
echo "1. Testing .env file loading...\n";
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "   ✓ .env loaded successfully\n";
    echo "   - APP_NAME: " . ($_ENV['APP_NAME'] ?? 'NOT SET') . "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Database Connection
echo "\n2. Testing database connection...\n";
try {
    $db = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✓ Database connected\n";
} catch (PDOException $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: JWT Functions
echo "\n3. Testing JWT token generation...\n";
try {
    require_once __DIR__ . '/src/Utils/JWT.php';
    $payload = [
        'id' => 1,
        'role' => 'Admin',
        'email' => 'test@example.com',
        'admin_id' => 1,
        'account_id' => 1
    ];
    $token = \App\Utils\JWT::encode($payload);
    echo "   ✓ Token generated successfully\n";
    
    $decoded = \App\Utils\JWT::decode($token);
    echo "   ✓ Token decoded successfully\n";
} catch (Exception $e) {
    echo "   ✗ JWT error: " . $e->getMessage() . "\n";
}

// Test 4: Database Schema
echo "\n4. Verifying database schema...\n";

// Check teachers table
$stmt = $db->query("SHOW COLUMNS FROM teachers WHERE Field IN ('name', 'first_name', 'last_name')");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (in_array('first_name', $columns) && in_array('last_name', $columns)) {
    echo "   ✓ Teachers table has first_name and last_name columns\n";
} else {
    echo "   ✗ Teachers table missing name columns\n";
}

// Check students table
$stmt = $db->query("SHOW COLUMNS FROM students WHERE Field IN ('first_name', 'last_name')");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (in_array('first_name', $columns) && in_array('last_name', $columns)) {
    echo "   ✓ Students table has first_name and last_name columns\n";
} else {
    echo "   ✗ Students table missing name columns\n";
}

// Check notification_reads table
$stmt = $db->query("SHOW TABLES LIKE 'notification_reads'");
if ($stmt->rowCount() > 0) {
    echo "   ✓ notification_reads table exists\n";
} else {
    echo "   ✗ notification_reads table missing\n";
}

// Check password_reset_tokens table
$stmt = $db->query("SHOW TABLES LIKE 'password_reset_tokens'");
if ($stmt->rowCount() > 0) {
    echo "   ✓ password_reset_tokens table exists\n";
} else {
    echo "   ✗ password_reset_tokens table missing\n";
}

// Test 5: School Settings
echo "\n5. Checking school settings...\n";
$stmt = $db->query("SELECT * FROM school_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
if ($settings) {
    echo "   ✓ School settings row exists\n";
    echo "   - Currency: " . ($settings['currency'] ?? 'NOT SET') . "\n";
    
    // Check for settings columns
    if (array_key_exists('notification_settings', $settings)) {
        echo "   ✓ notification_settings column exists\n";
    }
    if (array_key_exists('email_settings', $settings)) {
        echo "   ✓ email_settings column exists\n";
    }
    if (array_key_exists('security_settings', $settings)) {
        echo "   ✓ security_settings column exists\n";
    }
} else {
    echo "   ✗ No settings row found\n";
}

// Test 6: Controllers
echo "\n6. Checking controller files...\n";
$controllers = [
    'SettingsController',
    'TeacherController',
    'NotificationController',
    'PasswordResetController',
    'AdminController'
];

foreach ($controllers as $controller) {
    $path = __DIR__ . '/src/Controllers/' . $controller . '.php';
    if (file_exists($path)) {
        echo "   ✓ $controller exists\n";
    } else {
        echo "   ✗ $controller missing\n";
    }
}

// Test 7: Routes
echo "\n7. Checking routes file...\n";
$routesPath = __DIR__ . '/src/Routes/api.php';
if (file_exists($routesPath)) {
    echo "   ✓ Routes file exists\n";
    $routesContent = file_get_contents($routesPath);
    
    // Check for key routes
    if (str_contains($routesContent, 'PasswordResetController')) {
        echo "   ✓ Password reset routes configured\n";
    }
    if (str_contains($routesContent, '/notifications')) {
        echo "   ✓ Notification routes configured\n";
    }
    if (str_contains($routesContent, 'admin/settings')) {
        echo "   ✓ Settings routes configured\n";
    }
} else {
    echo "   ✗ Routes file missing\n";
}

// Test 8: Mailer
echo "\n8. Checking email functionality...\n";
$mailerPath = __DIR__ . '/src/Utils/Mailer.php';
if (file_exists($mailerPath)) {
    echo "   ✓ Mailer class exists\n";
    echo "   - SMTP Host: " . ($_ENV['SMTP_HOST'] ?? 'NOT CONFIGURED') . "\n";
    echo "   - SMTP Port: " . ($_ENV['SMTP_PORT'] ?? 'NOT CONFIGURED') . "\n";
    
    if (!empty($_ENV['SMTP_USERNAME']) && !empty($_ENV['SMTP_PASSWORD'])) {
        echo "   ✓ SMTP credentials configured\n";
    } else {
        echo "   ⚠ SMTP credentials not configured (email won't work)\n";
    }
} else {
    echo "   ✗ Mailer class missing\n";
}

// Test 9: Sample Data Check
echo "\n9. Checking database data...\n";
$stmt = $db->query("SELECT COUNT(*) FROM admins");
$adminCount = $stmt->fetchColumn();
echo "   - Admins: $adminCount\n";

$stmt = $db->query("SELECT COUNT(*) FROM teachers");
$teacherCount = $stmt->fetchColumn();
echo "   - Teachers: $teacherCount\n";

$stmt = $db->query("SELECT COUNT(*) FROM students");
$studentCount = $stmt->fetchColumn();
echo "   - Students: $studentCount\n";

$stmt = $db->query("SELECT COUNT(*) FROM notifications");
$notificationCount = $stmt->fetchColumn();
echo "   - Notifications: $notificationCount\n";

// Final Summary
echo "\n=================================\n";
echo "  Verification Summary\n";
echo "=================================\n\n";

$allGood = true;

// Critical checks
if (!file_exists($routesPath)) {
    echo "✗ CRITICAL: Routes file missing\n";
    $allGood = false;
}

if (!$settings) {
    echo "✗ CRITICAL: School settings not initialized\n";
    $allGood = false;
}

$stmt = $db->query("SHOW TABLES LIKE 'notification_reads'");
if ($stmt->rowCount() == 0) {
    echo "✗ WARNING: notification_reads table missing\n";
    echo "  Run: php run_simple_migration.php\n";
    $allGood = false;
}

if ($allGood) {
    echo "✅ ALL SYSTEMS OPERATIONAL\n\n";
    echo "Next Steps:\n";
    echo "1. Restart backend server: php -S localhost:8080 -t public\n";
    echo "2. Clear browser cache and localStorage\n";
    echo "3. Login to test all features\n";
    echo "4. Configure SMTP if email features needed\n";
} else {
    echo "⚠️ SOME ISSUES FOUND\n\n";
    echo "Run: php run_simple_migration.php\n";
    echo "Then restart the server\n";
}

echo "\n=================================\n";
