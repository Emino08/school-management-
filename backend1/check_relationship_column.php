<?php
require_once __DIR__ . '/vendor/autoload.php';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

use App\Config\Database;
$db = Database::getInstance()->getConnection();

echo "Checking student_parents.relationship column...\n\n";
$stmt = $db->query("SHOW COLUMNS FROM student_parents WHERE Field = 'relationship'");
$column = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($column);
