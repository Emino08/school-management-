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

    echo "Updating 2025-2026 to have 2 exams per term...\n";
    $stmt = $pdo->prepare("UPDATE academic_years SET exams_per_term = 2 WHERE year_name = '2025-2026'");
    $stmt->execute();
    echo "✓ Updated\n\n";

    echo "Current configuration:\n";
    $stmt = $pdo->query("SELECT year_name, number_of_terms, exams_per_term FROM academic_years ORDER BY id");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($years as $year) {
        printf("  %s: %d terms × %d exams/term = %d total exams\n",
            $year['year_name'],
            $year['number_of_terms'],
            $year['exams_per_term'],
            $year['number_of_terms'] * $year['exams_per_term']
        );
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
