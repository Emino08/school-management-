<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected to database successfully!\n\n";

    $columns = [
        ['name' => 'number_of_terms', 'definition' => "INT DEFAULT 3 COMMENT '2 or 3 terms per year'"],
        ['name' => 'exams_per_term', 'definition' => "INT DEFAULT 1 COMMENT '1 or 2 exams per term'"],
        ['name' => 'grading_type', 'definition' => "ENUM('average', 'gpa_5', 'gpa_4', 'custom') DEFAULT 'average'"],
        ['name' => 'result_publication_date', 'definition' => "DATE NULL"],
        ['name' => 'auto_calculate_position', 'definition' => "BOOLEAN DEFAULT TRUE"],
        ['name' => 'term_1_fee', 'definition' => "DECIMAL(10,2) DEFAULT 0.00"],
        ['name' => 'term_1_min_payment', 'definition' => "DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Minimum payment required'"],
        ['name' => 'term_2_fee', 'definition' => "DECIMAL(10,2) DEFAULT 0.00"],
        ['name' => 'term_2_min_payment', 'definition' => "DECIMAL(10,2) DEFAULT 0.00"],
        ['name' => 'term_3_fee', 'definition' => "DECIMAL(10,2) NULL"],
        ['name' => 'term_3_min_payment', 'definition' => "DECIMAL(10,2) NULL"]
    ];

    foreach ($columns as $column) {
        // Check if column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM academic_years LIKE '{$column['name']}'");
        if ($stmt->rowCount() == 0) {
            try {
                $sql = "ALTER TABLE academic_years ADD COLUMN {$column['name']} {$column['definition']}";
                $pdo->exec($sql);
                echo "✓ Added column: {$column['name']}\n";
            } catch (PDOException $e) {
                echo "✗ Error adding {$column['name']}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⊙ Column already exists: {$column['name']}\n";
        }
    }

    echo "\n========================================\n";
    echo "Academic year columns migration completed!\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
