<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Adding missing term management columns to academic_years...\n";

    // Check and add current_term column
    $stmt = $db->query("SHOW COLUMNS FROM academic_years LIKE 'current_term'");
    if ($stmt->rowCount() === 0) {
        echo "  Adding current_term column...\n";
        $db->exec("ALTER TABLE academic_years ADD COLUMN current_term INT DEFAULT 1 COMMENT 'Current term number (1, 2, 3)'");
        echo "  ✓ Added current_term column\n";
    } else {
        echo "  - current_term column already exists\n";
    }

    // Check and add total_terms column
    $stmt = $db->query("SHOW COLUMNS FROM academic_years LIKE 'total_terms'");
    if ($stmt->rowCount() === 0) {
        echo "  Adding total_terms column...\n";
        $db->exec("ALTER TABLE academic_years ADD COLUMN total_terms INT DEFAULT 3 COMMENT 'Total terms in the academic year'");
        echo "  ✓ Added total_terms column\n";
    } else {
        echo "  - total_terms column already exists\n";
    }

    // Initialize values for existing records
    echo "  Updating existing academic years...\n";
    $db->exec("UPDATE academic_years SET current_term = 1 WHERE current_term IS NULL");
    $db->exec("UPDATE academic_years SET total_terms = number_of_terms WHERE total_terms IS NULL AND number_of_terms IS NOT NULL");
    echo "  ✓ Updated existing records\n";

    echo "Done.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
