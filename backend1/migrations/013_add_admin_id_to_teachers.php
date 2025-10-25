<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    echo "Checking/adding admin_id column to teachers...\n";

    $stmt = $db->query("SHOW COLUMNS FROM teachers LIKE 'admin_id'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE teachers ADD COLUMN admin_id INT NOT NULL AFTER id");
        echo "  ✓ Added admin_id column\n";
        try {
            $db->exec("ALTER TABLE teachers ADD CONSTRAINT fk_teachers_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE");
            echo "  ✓ Added foreign key fk_teachers_admin\n";
        } catch (Exception $e) {
            echo "  ! Could not add foreign key: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  - admin_id column already exists\n";
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

