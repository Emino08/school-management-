<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class EnhanceComplaintsTable {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function up() {
        try {
            // Add teacher_id, category, and priority fields to complaints table
            $this->pdo->exec("
                ALTER TABLE complaints
                ADD COLUMN teacher_id INT NULL AFTER user_type,
                ADD COLUMN category ENUM('general', 'academic', 'disciplinary', 'facilities', 'teacher', 'other') DEFAULT 'general' AFTER complaint,
                ADD COLUMN priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' AFTER category,
                ADD COLUMN title VARCHAR(255) NULL AFTER user_type,
                ADD FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
                ADD INDEX idx_category (category),
                ADD INDEX idx_priority (priority),
                ADD INDEX idx_teacher (teacher_id)
            ");

            echo "✓ Complaints table enhanced successfully!\n";
            echo "  - Added teacher_id field (nullable, for complaints about specific teachers)\n";
            echo "  - Added category field (general, academic, disciplinary, facilities, teacher, other)\n";
            echo "  - Added priority field (low, medium, high, urgent)\n";
            echo "  - Added title field for complaint subject\n";

        } catch (PDOException $e) {
            echo "Error enhancing complaints table: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down() {
        try {
            $this->pdo->exec("
                ALTER TABLE complaints
                DROP FOREIGN KEY complaints_ibfk_2,
                DROP COLUMN teacher_id,
                DROP COLUMN category,
                DROP COLUMN priority,
                DROP COLUMN title
            ");

            echo "✓ Complaints table reverted successfully!\n";

        } catch (PDOException $e) {
            echo "Error reverting complaints table: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}

// Run migration
if (php_sapi_name() === 'cli') {
    $migration = new EnhanceComplaintsTable();
    $migration->up();
}
