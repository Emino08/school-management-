<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "JWT_EXPIRY: " . ($_ENV['JWT_EXPIRY'] ?? 'NOT SET') . PHP_EOL;

// If not set, let's add it to .env
if (!isset($_ENV['JWT_EXPIRY'])) {
    echo "JWT_EXPIRY is not set! Adding it to .env file..." . PHP_EOL;
    $envFile = __DIR__ . '/.env';
    $content = file_get_contents($envFile);
    if (strpos($content, 'JWT_EXPIRY') === false) {
        file_put_contents($envFile, PHP_EOL . "JWT_EXPIRY=86400" . PHP_EOL, FILE_APPEND);
        echo "Added JWT_EXPIRY=86400 (24 hours) to .env" . PHP_EOL;
    }
}
