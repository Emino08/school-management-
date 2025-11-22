<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "JWT_SECRET loaded: " . (isset($_ENV['JWT_SECRET']) ? 'YES' : 'NO') . PHP_EOL;
echo "JWT_SECRET length: " . (isset($_ENV['JWT_SECRET']) ? strlen($_ENV['JWT_SECRET']) : '0') . PHP_EOL;
echo "JWT_SECRET value: " . ($_ENV['JWT_SECRET'] ?? 'NOT SET') . PHP_EOL;
