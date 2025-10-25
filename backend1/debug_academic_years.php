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

    echo "=== ACADEMIC YEARS DEBUG ===\n\n";
    
    // Get all academic years
    $stmt = $pdo->query('SELECT id, year_name, is_current, start_date, end_date FROM academic_years ORDER BY id DESC');
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total Academic Years: " . count($years) . "\n\n";
    
    foreach ($years as $year) {
        echo "ID: " . $year['id'] . "\n";
        echo "Year Name: " . $year['year_name'] . "\n";
        echo "is_current VALUE: " . var_export($year['is_current'], true) . "\n";
        echo "is_current TYPE: " . gettype($year['is_current']) . "\n";
        echo "Start Date: " . $year['start_date'] . "\n";
        echo "End Date: " . $year['end_date'] . "\n";
        
        // Test checks
        echo "\nChecks:\n";
        echo "  is_current === 1: " . ($year['is_current'] === 1 ? 'TRUE' : 'FALSE') . "\n";
        echo "  is_current === '1': " . ($year['is_current'] === '1' ? 'TRUE' : 'FALSE') . "\n";
        echo "  is_current === true: " . ($year['is_current'] === true ? 'TRUE' : 'FALSE') . "\n";
        echo "  is_current == 1: " . ($year['is_current'] == 1 ? 'TRUE' : 'FALSE') . "\n";
        echo str_repeat('-', 50) . "\n\n";
    }
    
    // Check what API returns
    echo "\n=== SIMULATING API RESPONSE ===\n";
    $jsonData = json_encode(['academic_years' => $years]);
    echo $jsonData . "\n\n";
    
    $decoded = json_decode($jsonData, true);
    echo "After JSON encode/decode:\n";
    foreach ($decoded['academic_years'] as $year) {
        echo "ID {$year['id']}: is_current = " . var_export($year['is_current'], true) . " (type: " . gettype($year['is_current']) . ")\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
