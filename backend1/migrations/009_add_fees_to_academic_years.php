<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adding fees configuration to academic years...\n\n";

    // Add fees columns to academic_years
    $columns_to_add = [
        'term_1_fee' => "ALTER TABLE academic_years ADD COLUMN term_1_fee DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Fee amount for term 1'",
        'term_2_fee' => "ALTER TABLE academic_years ADD COLUMN term_2_fee DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Fee amount for term 2'",
        'term_3_fee' => "ALTER TABLE academic_years ADD COLUMN term_3_fee DECIMAL(10,2) NULL COMMENT 'Fee amount for term 3 (if applicable)'"
    ];

    foreach ($columns_to_add as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "  ✓ Added column: $column\n";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') {
                echo "  - Column already exists: $column\n";
            } else {
                throw $e;
            }
        }
    }

    // Add exam officer flag to teachers table
    echo "\nAdding exam officer flag to teachers table...\n";

    try {
        $pdo->exec("ALTER TABLE teachers ADD COLUMN is_exam_officer BOOLEAN DEFAULT FALSE COMMENT 'Whether teacher is also an exam officer'");
        echo "  ✓ Added column: is_exam_officer\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') {
            echo "  - Column already exists: is_exam_officer\n";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE teachers ADD COLUMN can_approve_results BOOLEAN DEFAULT FALSE COMMENT 'Can approve exam results'");
        echo "  ✓ Added column: can_approve_results\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') {
            echo "  - Column already exists: can_approve_results\n";
        } else {
            throw $e;
        }
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "\n==============================================\n";
    echo "Summary:\n";
    echo "==============================================\n";
    echo "1. Academic years now support fees per term\n";
    echo "2. Teachers can be designated as exam officers\n";
    echo "==============================================\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
