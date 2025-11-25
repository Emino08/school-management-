<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $env = strtolower($_ENV['APP_ENV'] ?? 'production');
        $isDevelopment = in_array($env, ['development', 'local', 'testing'], true);

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_NAME'] ?? 'school_management';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        if ($isDevelopment) {
            $host = $_ENV['DB_HOST_DEVELOPMENT'] ?? $host;
            $port = $_ENV['DB_PORT_DEVELOPMENT'] ?? $port;
            $dbname = $_ENV['DB_NAME_DEVELOPMENT'] ?? $dbname;
            $username = $_ENV['DB_USER_DEVELOPMENT'] ?? $username;
            $password = $_ENV['DB_PASS_DEVELOPMENT'] ?? $password;
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    // Reset instance for testing (use carefully)
    public static function resetInstance()
    {
        self::$instance = null;
    }
}
