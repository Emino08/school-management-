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

    echo "=== CHECKING STUDENTS ===\n\n";

    // Check students count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total students: " . $count['count'] . "\n\n";

    // Check students with details
    $stmt = $pdo->query("
        SELECT s.id, s.name, s.id_number, s.email, s.admin_id,
               se.class_id, c.class_name, se.academic_year_id
        FROM students s
        LEFT JOIN student_enrollments se ON s.id = se.student_id
        LEFT JOIN classes c ON se.class_id = c.id
        LIMIT 5
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Sample students:\n";
    print_r($students);

    echo "\n\n=== CHECKING FEE STRUCTURES ===\n\n";

    // Check fee structures
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fee_structures");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total fee structures: " . $count['count'] . "\n\n";

    $stmt = $pdo->query("SELECT * FROM fee_structures LIMIT 5");
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Sample fee structures:\n";
    print_r($fees);

    echo "\n\n=== CHECKING ACADEMIC YEARS ===\n\n";
    $stmt = $pdo->query("SELECT id, year_name, is_current FROM academic_years");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Academic years:\n";
    print_r($years);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
