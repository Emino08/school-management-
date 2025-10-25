<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== ACADEMIC YEARS with admin_id ===\n";
    echo str_repeat('=', 60) . "\n";
    
    $stmt = $pdo->query('SELECT id, year_name, admin_id, is_current FROM academic_years ORDER BY id DESC');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf(
            "ID: %d | Year: %-15s | admin_id: %-5s | is_current: %d\n",
            $row['id'],
            $row['year_name'],
            $row['admin_id'] ?? 'NULL',
            $row['is_current']
        );
    }
    
    echo "\n=== ADMINS in system ===\n";
    echo str_repeat('=', 60) . "\n";
    
    $stmt2 = $pdo->query('SELECT * FROM admins LIMIT 5');
    $cols = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($cols) {
        echo "Admin columns: " . implode(', ', array_keys($cols)) . "\n\n";
        // Reset and show all admins
        $stmt2 = $pdo->query('SELECT id, email FROM admins');
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            printf(
                "Admin ID: %d | Email: %s\n",
                $row['id'],
                $row['email']
            );
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
