<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "\n=== SUBJECTS TABLE ===\n";
    $stmt = $pdo->query('SELECT id, subject_name, subject_code, class_id, admin_id FROM subjects LIMIT 20');
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subjects)) {
        echo "❌ No subjects found in database!\n";
    } else {
        echo "✅ Found " . count($subjects) . " subjects:\n\n";
        foreach ($subjects as $subject) {
            echo "  ID: {$subject['id']}\n";
            echo "  Name: {$subject['subject_name']}\n";
            echo "  Code: {$subject['subject_code']}\n";
            echo "  Class ID: {$subject['class_id']}\n";
            echo "  Admin ID: {$subject['admin_id']}\n";
            echo "  " . str_repeat('-', 50) . "\n";
        }
    }
    
    echo "\n=== ADMIN USERS ===\n";
    $stmt = $pdo->query('SELECT id, name, email FROM admins LIMIT 5');
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($admins)) {
        echo "Found " . count($admins) . " admins:\n";
        foreach ($admins as $admin) {
            echo "  - ID: {$admin['id']}, Name: {$admin['name']}, Email: {$admin['email']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
