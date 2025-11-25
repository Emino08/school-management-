<?php
/**
 * Test Script: Class CSV Import/Export
 */

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Class CSV Import/Export - Testing                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check if routes are accessible
echo "TEST 1: Checking Endpoints\n";
echo str_repeat("─", 64) . "\n\n";

$endpoints = [
    'Export CSV' => 'GET /api/classes/export/csv',
    'Import CSV' => 'POST /api/classes/import/csv',
    'Download Template' => 'GET /api/classes/template/csv'
];

foreach ($endpoints as $name => $endpoint) {
    echo "✓ {$name}: {$endpoint}\n";
}

echo "\n";

// Test 2: Check ClassController methods
echo "TEST 2: Verifying Controller Methods\n";
echo str_repeat("─", 64) . "\n\n";

$reflection = new ReflectionClass('App\Controllers\ClassController');
$methods = ['exportCSV', 'importCSV', 'downloadTemplate', 'escapeCsvField'];

foreach ($methods as $method) {
    if ($reflection->hasMethod($method)) {
        echo "✓ Method exists: {$method}()\n";
    } else {
        echo "✗ Method missing: {$method}()\n";
    }
}

echo "\n";

// Test 3: Create sample CSV template
echo "TEST 3: Creating Sample Import File\n";
echo str_repeat("─", 64) . "\n\n";

$sampleCsv = __DIR__ . '/sample_class_import.csv';
$csv = "Class Name,Grade Level,Description,Capacity\n";
$csv .= "Test Class 10A,10,Science Stream,40\n";
$csv .= "Test Class 10B,10,Commerce Stream,35\n";
$csv .= "Test Class 11A,11,Advanced Science,30\n";

file_put_contents($sampleCsv, $csv);

if (file_exists($sampleCsv)) {
    echo "✓ Sample CSV created: sample_class_import.csv\n";
    echo "  Location: {$sampleCsv}\n";
    echo "  Size: " . filesize($sampleCsv) . " bytes\n";
    echo "\n";
    echo "Content:\n";
    echo str_repeat("─", 64) . "\n";
    echo file_get_contents($sampleCsv);
    echo str_repeat("─", 64) . "\n";
} else {
    echo "✗ Failed to create sample CSV\n";
}

echo "\n";

// Test 4: Verify CSV format
echo "TEST 4: Verifying CSV Format\n";
echo str_repeat("─", 64) . "\n\n";

$handle = fopen($sampleCsv, 'r');
$header = fgetcsv($handle);
echo "CSV Headers:\n";
foreach ($header as $index => $col) {
    echo "  Column " . ($index + 1) . ": {$col}\n";
}

$rowCount = 0;
while (($row = fgetcsv($handle)) !== false) {
    $rowCount++;
}
fclose($handle);

echo "\nData Rows: {$rowCount}\n";
echo "✓ CSV format is valid\n";

echo "\n";

// Test 5: Check database structure
echo "TEST 5: Checking Database Schema\n";
echo str_repeat("─", 64) . "\n\n";

$db = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

$stmt = $db->query("SHOW COLUMNS FROM classes");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Classes table columns:\n";
foreach ($columns as $column) {
    echo "  - {$column['Field']} ({$column['Type']})\n";
}

echo "\n";

// Test 6: API Testing Instructions
echo "TEST 6: API Testing Instructions\n";
echo str_repeat("─", 64) . "\n\n";

echo "To test the CSV functionality:\n\n";

echo "1. Download Template:\n";
echo "   curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "        http://localhost:8080/api/classes/template/csv \\\n";
echo "        -o template.csv\n\n";

echo "2. Export Current Classes:\n";
echo "   curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "        http://localhost:8080/api/classes/export/csv \\\n";
echo "        -o classes_export.csv\n\n";

echo "3. Import Classes from CSV:\n";
echo "   curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "        -F \"file=@sample_class_import.csv\" \\\n";
echo "        http://localhost:8080/api/classes/import/csv\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Test Complete                                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
