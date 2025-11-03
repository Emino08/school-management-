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

    echo "Creating fee structures for academic year 2 (2025-2026)...\n\n";

    // Fee structures for the current academic year
    $feeStructures = [
        [
            'fee_name' => 'Tuition Fee - Term 1',
            'amount' => 50000,
            'frequency' => 'Termly',
            'academic_year_id' => 2,
            'description' => 'Tuition fee for first term',
            'is_mandatory' => 1
        ],
        [
            'fee_name' => 'Tuition Fee - Term 2',
            'amount' => 50000,
            'frequency' => 'Termly',
            'academic_year_id' => 2,
            'description' => 'Tuition fee for second term',
            'is_mandatory' => 1
        ],
        [
            'fee_name' => 'Tuition Fee - Term 3',
            'amount' => 50000,
            'frequency' => 'Termly',
            'academic_year_id' => 2,
            'description' => 'Tuition fee for third term',
            'is_mandatory' => 1
        ],
        [
            'fee_name' => 'Registration Fee',
            'amount' => 10000,
            'frequency' => 'One-time',
            'academic_year_id' => 2,
            'description' => 'Annual registration fee',
            'is_mandatory' => 1
        ],
        [
            'fee_name' => 'Sports Fee',
            'amount' => 5000,
            'frequency' => 'Yearly',
            'academic_year_id' => 2,
            'description' => 'Sports and athletics fee',
            'is_mandatory' => 0
        ],
        [
            'fee_name' => 'Laboratory Fee',
            'amount' => 8000,
            'frequency' => 'Yearly',
            'academic_year_id' => 2,
            'description' => 'Science laboratory usage fee',
            'is_mandatory' => 1
        ],
        [
            'fee_name' => 'Library Fee',
            'amount' => 3000,
            'frequency' => 'Yearly',
            'academic_year_id' => 2,
            'description' => 'Library access and maintenance fee',
            'is_mandatory' => 1
        ],
        [
            'fee_name' => 'Transport Fee',
            'amount' => 15000,
            'frequency' => 'Termly',
            'academic_year_id' => 2,
            'description' => 'School bus transportation fee',
            'is_mandatory' => 0
        ],
        [
            'fee_name' => 'Exam Fee',
            'amount' => 7000,
            'frequency' => 'Termly',
            'academic_year_id' => 2,
            'description' => 'Examination and assessment fee',
            'is_mandatory' => 1
        ],
        [
            'fee_name' => 'Uniform Fee',
            'amount' => 12000,
            'frequency' => 'One-time',
            'academic_year_id' => 2,
            'description' => 'School uniform purchase',
            'is_mandatory' => 1
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO fee_structures
        (fee_name, amount, frequency, academic_year_id, description, is_mandatory, created_at)
        VALUES (:fee_name, :amount, :frequency, :academic_year_id, :description, :is_mandatory, NOW())
    ");

    foreach ($feeStructures as $fee) {
        $stmt->execute($fee);
        echo "✅ Created: {$fee['fee_name']} - {$fee['amount']}\n";
    }

    echo "\n✅ Successfully created " . count($feeStructures) . " fee structures!\n";

    // Show created fees
    $stmt = $pdo->query("SELECT * FROM fee_structures WHERE academic_year_id = 2");
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nFee structures in database:\n";
    foreach ($fees as $fee) {
        echo "- {$fee['fee_name']}: {$fee['amount']} ({$fee['frequency']})\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
