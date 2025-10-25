<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    
    $stmt = $pdo->query('DESCRIBE teachers');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nTeachers Table Columns:\n";
    echo str_repeat('=', 50) . "\n";
    foreach ($columns as $col) {
        echo "  • {$col['Field']} ({$col['Type']})";
        if ($col['Null'] === 'NO') echo " - NOT NULL";
        echo "\n";
    }
    echo str_repeat('=', 50) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
