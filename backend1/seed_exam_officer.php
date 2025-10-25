<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

echo "==============================================\n";
echo "Creating Demo Exam Officer\n";
echo "==============================================\n\n";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the first admin ID
    $stmt = $pdo->query("SELECT id FROM admins LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo "❌ No admin found. Please create an admin first.\n";
        exit(1);
    }

    $adminId = $admin['id'];
    echo "Found admin ID: $adminId\n\n";

    // Check if exam officer already exists
    $checkStmt = $pdo->prepare("SELECT * FROM exam_officers WHERE email = :email");
    $checkStmt->execute([':email' => 'officer@school.com']);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo "ℹ️  Exam officer already exists:\n";
        echo "   Email: officer@school.com\n";
        echo "   Name: {$existing['name']}\n";
        echo "   Password: officer123 (if not changed)\n\n";
        echo "You can use these credentials to log in.\n";
        exit(0);
    }

    // Create exam officer
    $hashedPassword = password_hash('officer123', PASSWORD_BCRYPT);

    $sql = "INSERT INTO exam_officers (admin_id, name, email, password, phone, is_active, can_approve_results)
            VALUES (:admin_id, :name, :email, :password, :phone, :is_active, :can_approve_results)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':admin_id' => $adminId,
        ':name' => 'Demo Exam Officer',
        ':email' => 'officer@school.com',
        ':password' => $hashedPassword,
        ':phone' => '+1234567890',
        ':is_active' => 1,
        ':can_approve_results' => 1
    ]);

    $officerId = $pdo->lastInsertId();

    echo "✅ Exam Officer created successfully!\n\n";
    echo "==============================================\n";
    echo "Login Credentials:\n";
    echo "==============================================\n";
    echo "Email:    officer@school.com\n";
    echo "Password: officer123\n";
    echo "ID:       $officerId\n";
    echo "==============================================\n\n";

    echo "You can now log in using:\n";
    echo "POST /api/exam-officer/login\n\n";

    echo "Example curl command:\n";
    echo "curl -X POST http://localhost:8080/api/exam-officer/login \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -d '{\"email\":\"officer@school.com\",\"password\":\"officer123\"}'\n\n";

    echo "==============================================\n";
    echo "Next Steps:\n";
    echo "==============================================\n";
    echo "1. Log in as exam officer\n";
    echo "2. View pending results: GET /api/exam-officer/pending-results\n";
    echo "3. Approve results: POST /api/exam-officer/approve/{resultId}\n";
    echo "4. View statistics: GET /api/exam-officer/stats\n";
    echo "==============================================\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
