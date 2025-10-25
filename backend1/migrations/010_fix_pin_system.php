<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Fixing PIN system...\n\n";

    // Drop old result_pins table if exists and recreate
    $pdo->exec("DROP TABLE IF EXISTS result_pins");

    // Create new result_pins table with proper structure
    $pdo->exec("
        CREATE TABLE result_pins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            student_id INT NOT NULL,
            pin_code VARCHAR(20) UNIQUE NOT NULL,
            max_checks INT DEFAULT 5,
            used_checks INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            expires_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP NULL,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            INDEX idx_pin_code (pin_code),
            INDEX idx_student (student_id),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "✅ PIN system fixed successfully!\n";
    echo "\nNew PIN System Features:\n";
    echo "- Each PIN tied to one student only\n";
    echo "- Configurable max checks (default: 5)\n";
    echo "- Tracks usage count\n";
    echo "- Auto-expires after max checks\n";
    echo "- Optional expiration date\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
