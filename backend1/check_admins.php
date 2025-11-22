<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

echo "=== Checking Admin Accounts ===" . PHP_EOL;
$stmt = $db->query('SELECT id, email, school_name, role FROM admins LIMIT 5');
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($admins)) {
    echo "No admin accounts found!" . PHP_EOL;
} else {
    echo "Found " . count($admins) . " admin(s):" . PHP_EOL;
    foreach ($admins as $admin) {
        echo "  - ID: {$admin['id']}, Email: {$admin['email']}, School: {$admin['school_name']}, Role: " . ($admin['role'] ?? 'admin') . PHP_EOL;
    }
}

echo PHP_EOL;
echo "You can use any of these emails to login." . PHP_EOL;
echo "If you don't know the password, you may need to reset it." . PHP_EOL;
