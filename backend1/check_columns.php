<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();

    // Check academic_years columns
    $stmt = $db->query("SHOW COLUMNS FROM academic_years");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns in academic_years table:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
