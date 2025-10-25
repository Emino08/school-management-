<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== subject_results table structure ===\n";
    $stmt = $pdo->query('DESCRIBE subject_results');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $col) {
        echo sprintf("%-30s %s\n", $col['Field'], $col['Type']);
    }

    echo "\n=== term_results table structure ===\n";
    $stmt = $pdo->query('DESCRIBE term_results');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $col) {
        echo sprintf("%-30s %s\n", $col['Field'], $col['Type']);
    }

    echo "\n=== result_summary table structure ===\n";
    $stmt = $pdo->query('DESCRIBE result_summary');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $col) {
        echo sprintf("%-30s %s\n", $col['Field'], $col['Type']);
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
