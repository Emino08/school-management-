<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new PDO(
    "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

echo "Tables with 'parent' in name:\n";
$result = $db->query("SHOW TABLES LIKE '%parent%'")->fetchAll(PDO::FETCH_COLUMN);
foreach($result as $table) {
    echo "  - $table\n";
    $columns = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach($columns as $col) {
        echo "    {$col['Field']} ({$col['Type']})\n";
    }
}
