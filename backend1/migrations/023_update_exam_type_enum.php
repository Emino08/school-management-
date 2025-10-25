<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "Adding 'test' to exam_type ENUM...\n";

    // Modify the ENUM to include 'test'
    $sql = "ALTER TABLE exams MODIFY COLUMN exam_type ENUM('test', 'midterm', 'final', 'quiz', 'assignment', 'monthly') NOT NULL DEFAULT 'final'";
    $db->exec($sql);

    echo "✓ Successfully added 'test' to exam_type ENUM\n";

    // Verify the change
    $stmt = $db->query("SHOW COLUMNS FROM exams LIKE 'exam_type'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "New ENUM values: {$col['Type']}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
