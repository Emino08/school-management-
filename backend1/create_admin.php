<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║   CREATING TEST ADMIN ACCOUNT                  ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Create a new admin with known credentials
    $email = 'admin@boschool.org';
    $password = 'admin123';
    $name = 'Administrator';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing
        echo "Admin exists, updating password...\n";
        $stmt = $pdo->prepare("UPDATE admins SET password = ?, name = ?, contact_name = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $name, $name, $email]);
        echo "✅ Admin updated\n\n";
    } else {
        // Create new
        echo "Creating new admin...\n";
        $stmt = $pdo->prepare("INSERT INTO admins (email, password, name, contact_name, role, created_at) VALUES (?, ?, ?, ?, 'admin', NOW())");
        $stmt->execute([$email, $hashedPassword, $name, $name]);
        echo "✅ Admin created\n\n";
    }
    
    // Also update the existing admin with a known password
    echo "Updating existing admin (emk32770@gmail.com)...\n";
    $testPassword = '32770&Sabi';
    $hashedTestPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE admins SET password = ?, name = ?, contact_name = ? WHERE email = ?");
    $stmt->execute([$hashedTestPassword, 'Emmanuel Koroma', 'Emmanuel Koroma', 'emk32770@gmail.com']);
    echo "✅ Updated existing admin\n\n";
    
    echo "╔════════════════════════════════════════════════╗\n";
    echo "║   ✅ ADMIN ACCOUNTS READY                      ║\n";
    echo "╚════════════════════════════════════════════════╝\n\n";
    
    echo "You can now login with:\n\n";
    echo "Option 1:\n";
    echo "  Email:    admin@boschool.org\n";
    echo "  Password: admin123\n\n";
    
    echo "Option 2:\n";
    echo "  Email:    emk32770@gmail.com\n";
    echo "  Password: 32770&Sabi\n\n";
    
    // Test password verification
    echo "Testing password verification...\n";
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE email = ?");
    $stmt->execute(['admin@boschool.org']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify('admin123', $row['password'])) {
        echo "✅ Password verification works for admin@boschool.org\n";
    } else {
        echo "❌ Password verification failed\n";
    }
    
    $stmt->execute(['emk32770@gmail.com']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify('32770&Sabi', $row['password'])) {
        echo "✅ Password verification works for emk32770@gmail.com\n\n";
    } else {
        echo "❌ Password verification failed\n\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
