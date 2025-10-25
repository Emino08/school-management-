<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "Adding promotion_status column to student_enrollments table...\n";

    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM student_enrollments LIKE 'promotion_status'");
    if ($stmt->rowCount() == 0) {
        $db->exec("
            ALTER TABLE student_enrollments
            ADD COLUMN promotion_status ENUM('pending', 'promoted', 'repeat', 'dropped') DEFAULT 'pending'
            COMMENT 'Promotion status at end of academic year'
        ");
        echo "✓ Added promotion_status column\n";
    } else {
        echo "ℹ promotion_status column already exists\n";
    }

    echo "\n✓ Successfully added promotion_status column\n";

    // Verify
    $stmt = $db->query("SHOW COLUMNS FROM student_enrollments LIKE 'promotion_status'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Column: {$col['Field']}, Type: {$col['Type']}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
