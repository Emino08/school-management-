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
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adding admission_no column to students table...\n";

    // Check if column already exists
    $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = 'school_management'
                 AND TABLE_NAME = 'students'
                 AND COLUMN_NAME = 'admission_no'";
    $stmt = $db->query($checkSql);

    if ($stmt->rowCount() == 0) {
        // Add admission_no column
        $db->exec("ALTER TABLE students ADD COLUMN admission_no VARCHAR(50) UNIQUE AFTER roll_number");

        // Generate admission numbers for existing students
        // Format: STU{YEAR}{SEQUENTIAL_NUMBER}
        $year = date('Y');
        $updateSql = "UPDATE students
                     SET admission_no = CONCAT('STU', '$year', LPAD(id, 4, '0'))
                     WHERE admission_no IS NULL";
        $db->exec($updateSql);

        echo "✓ Added admission_no column and generated admission numbers\n";
    } else {
        echo "✓ admission_no column already exists\n";
    }

    // Also ensure roll_num column exists (some queries use this)
    $checkRollNum = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = 'school_management'
                     AND TABLE_NAME = 'students'
                     AND COLUMN_NAME = 'roll_num'";
    $stmt = $db->query($checkRollNum);

    if ($stmt->rowCount() == 0) {
        echo "Adding roll_num as alias to roll_number...\n";
        // Create a view or update queries to use roll_number
        // For now, let's add it as a generated column
        $db->exec("ALTER TABLE students ADD COLUMN roll_num VARCHAR(50)
                   GENERATED ALWAYS AS (roll_number) STORED");
        echo "✓ Added roll_num column\n";
    }

    echo "\n✅ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
