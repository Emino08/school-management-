<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

echo "==============================================\n";
echo "Admin Account Seeder\n";
echo "==============================================\n\n";

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "[1/3] Connected to database successfully!\n\n";

    // Admin credentials
    $email = 'koromaemmanuel66@gmail.com';
    $password = '11111111';
    $schoolName = 'School Management';

    // Hash the password using bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    echo "[2/3] Inserting admin account...\n";
    echo "Email: $email\n";
    echo "School Name: $schoolName\n";
    echo "Password: $password (will be hashed)\n\n";

    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "⚠ Admin with this email already exists!\n";
        echo "Updating password instead...\n\n";

        $stmt = $pdo->prepare("UPDATE admins SET password = ?, school_name = ?, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$hashedPassword, $schoolName, $email]);

        echo "✓ Admin account updated successfully!\n";
    } else {
        // Insert new admin
        $stmt = $pdo->prepare("
            INSERT INTO admins (school_name, email, password, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$schoolName, $email, $hashedPassword]);

        echo "✓ Admin account created successfully!\n";
    }

    echo "\n[3/3] Verification...\n";
    $stmt = $pdo->prepare("SELECT id, school_name, email, created_at FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        echo "✓ Admin found in database:\n";
        echo "  ID: {$admin['id']}\n";
        echo "  School Name: {$admin['school_name']}\n";
        echo "  Email: {$admin['email']}\n";
        echo "  Created At: {$admin['created_at']}\n";
    }

    echo "\n==============================================\n";
    echo "Admin Account Ready!\n";
    echo "==============================================\n";
    echo "You can now login with:\n";
    echo "  Email: $email\n";
    echo "  Password: $password\n";
    echo "==============================================\n";

} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
