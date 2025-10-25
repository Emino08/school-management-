<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== EXAM_RESULTS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE exam_results");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }

    echo "\n=== EXAMS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE exams");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }

    echo "\n=== SUBJECTS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE subjects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }

    echo "\n=== GRADE_UPDATE_REQUESTS TABLE (if exists) ===\n";
    try {
        $stmt = $pdo->query("DESCRIBE grade_update_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']}\n";
        }
    } catch (Exception $e) {
        echo "Table does not exist or error: " . $e->getMessage() . "\n";
    }

    echo "\n=== SAMPLE DATA FROM EXAM_RESULTS ===\n";
    $stmt = $pdo->query("SELECT * FROM exam_results LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        print_r($sample);
    } else {
        echo "No data in exam_results table\n";
    }

    echo "\n=== SAMPLE DATA FROM EXAMS ===\n";
    $stmt = $pdo->query("SELECT * FROM exams LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        print_r($sample);
    } else {
        echo "No data in exams table\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
