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

    echo "=== EXAMS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE exams");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo sprintf("%s | %s | %s\n", $col['Field'], $col['Type'], $col['Null']);
    }

    echo "\n=== EXAMS DATA ===\n";
    $stmt = $pdo->query("SELECT * FROM exams LIMIT 5");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($exams)) {
        echo "No exams in database.\n";
    } else {
        foreach ($exams as $exam) {
            print_r($exam);
        }
    }

    echo "\n=== EXAM COUNT ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM exams");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total exams: " . $count['total'] . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
