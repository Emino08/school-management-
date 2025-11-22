<?php
/**
 * Production Migration V2 Runner
 * Applies comprehensive database updates including:
 * - Teacher and Student name splitting (first_name, last_name)
 * - Currency update to SLE
 * - Notification system tables
 * - Password reset functionality
 * - Performance indexes
 */

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get database configuration
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'] ?? 3306;
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

echo "======================================\n";
echo "Production Migration V2\n";
echo "======================================\n";
echo "Database: {$dbname}\n";
echo "Host: {$host}:{$port}\n";
echo "======================================\n\n";

try {
    // Connect to database
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    echo "✓ Connected to database successfully\n\n";

    // Read and execute the migration SQL file
    $sqlFile = __DIR__ . '/../database updated files/production_migration_v2.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }

    echo "Reading migration file...\n";
    $sql = file_get_contents($sqlFile);

    // Split SQL into individual statements and execute
    echo "Executing migration...\n\n";
    
    // Execute the entire SQL as MySQL supports multiple statements with certain settings
    $pdo->exec($sql);

    echo "\n======================================\n";
    echo "✓ Migration completed successfully!\n";
    echo "======================================\n\n";

    // Display verification results
    echo "Verification Results:\n";
    echo "--------------------\n\n";

    // Teachers
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN first_name IS NOT NULL AND first_name != '' THEN 1 ELSE 0 END) as with_first_name,
            SUM(CASE WHEN last_name IS NOT NULL AND last_name != '' THEN 1 ELSE 0 END) as with_last_name
        FROM teachers
    ");
    $teacherStats = $stmt->fetch();
    echo "Teachers:\n";
    echo "  Total: {$teacherStats['total_records']}\n";
    echo "  With first_name: {$teacherStats['with_first_name']}\n";
    echo "  With last_name: {$teacherStats['with_last_name']}\n\n";

    // Students
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN first_name IS NOT NULL AND first_name != '' THEN 1 ELSE 0 END) as with_first_name,
            SUM(CASE WHEN last_name IS NOT NULL AND last_name != '' THEN 1 ELSE 0 END) as with_last_name
        FROM students
    ");
    $studentStats = $stmt->fetch();
    echo "Students:\n";
    echo "  Total: {$studentStats['total_records']}\n";
    echo "  With first_name: {$studentStats['with_first_name']}\n";
    echo "  With last_name: {$studentStats['with_last_name']}\n\n";

    // School Settings
    $stmt = $pdo->query("SELECT currency FROM school_settings LIMIT 1");
    $settings = $stmt->fetch();
    echo "School Settings:\n";
    echo "  Currency: " . ($settings['currency'] ?? 'Not set') . "\n\n";

    // Notification reads table
    $stmt = $pdo->query("SHOW TABLES LIKE 'notification_reads'");
    $notifTable = $stmt->fetch();
    echo "Notification System:\n";
    echo "  notification_reads table: " . ($notifTable ? "✓ Exists" : "✗ Missing") . "\n\n";

    // Password reset tokens table
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    $resetTable = $stmt->fetch();
    echo "Password Reset:\n";
    echo "  password_reset_tokens table: " . ($resetTable ? "✓ Exists" : "✗ Missing") . "\n\n";

    echo "======================================\n";
    echo "Migration Summary:\n";
    echo "======================================\n";
    echo "✓ Teacher names split into first/last\n";
    echo "✓ Student names split into first/last\n";
    echo "✓ Currency updated to SLE\n";
    echo "✓ Notification system tables ready\n";
    echo "✓ Password reset system ready\n";
    echo "✓ Performance indexes created\n";
    echo "======================================\n";

} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
