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

    echo "=== TESTING EXAM GENERATION WITH DIFFERENT CONFIGURATIONS ===\n\n";

    // Test 1: Update 2025-2026 to have 2 exams per term
    echo "Test 1: Setting 2025-2026 to have 2 exams per term (Midterm + Final)...\n";
    $stmt = $pdo->prepare("UPDATE academic_years SET exams_per_term = 2 WHERE year_name = '2025-2026'");
    $stmt->execute();
    echo "✓ Updated configuration\n\n";

    // Run the generation script
    echo "Running exam generation script...\n";
    include __DIR__ . '/migrations/027_create_exams_for_terms.php';

    echo "\n\nTest 2: Setting 2024-2025 to have 3 exams per term (Test + Midterm + Final)...\n";
    $stmt = $pdo->prepare("UPDATE academic_years SET exams_per_term = 3 WHERE year_name = '2024-2025'");
    $stmt->execute();
    echo "✓ Updated configuration\n\n";

    // Run the generation script again
    echo "Running exam generation script again...\n";
    include __DIR__ . '/migrations/027_create_exams_for_terms.php';

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
