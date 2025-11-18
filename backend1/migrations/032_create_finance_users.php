<?php

require_once __DIR__ . '/../config/database.php';

class CreateFinanceUsers
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function up()
    {
        try {
            $this->conn->beginTransaction();

            // Create finance_users table
            $sql = "CREATE TABLE IF NOT EXISTS finance_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                address TEXT,
                is_active TINYINT(1) DEFAULT 1,
                can_approve_payments TINYINT(1) DEFAULT 1,
                can_generate_reports TINYINT(1) DEFAULT 1,
                can_manage_fees TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                UNIQUE KEY unique_finance_email (email),
                INDEX idx_admin_id (admin_id),
                INDEX idx_email (email),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->conn->exec($sql);

            // Add permissions columns to teachers table for exam officer management
            $column = $this->conn->query("SHOW COLUMNS FROM teachers LIKE 'is_exam_officer'")->fetch();
            if (!$column) {
                $this->conn->exec("ALTER TABLE teachers ADD COLUMN is_exam_officer TINYINT(1) DEFAULT 0 AFTER experience_years");
            }

            $column = $this->conn->query("SHOW COLUMNS FROM teachers LIKE 'can_approve_results'")->fetch();
            if (!$column) {
                $this->conn->exec("ALTER TABLE teachers ADD COLUMN can_approve_results TINYINT(1) DEFAULT 0 AFTER is_exam_officer");
            }

            $indexExists = $this->conn->query("SHOW INDEX FROM teachers WHERE Key_name = 'idx_is_exam_officer'")->fetch();
            if (!$indexExists) {
                $this->conn->exec("ALTER TABLE teachers ADD INDEX idx_is_exam_officer (is_exam_officer)");
            }

            // Create user_activity_logs table for tracking user management actions
            $activityLog = "CREATE TABLE IF NOT EXISTS user_activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                user_type VARCHAR(20) NOT NULL,
                user_id INT NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                INDEX idx_admin_id (admin_id),
                INDEX idx_user_type_id (user_type, user_id),
                INDEX idx_action_type (action_type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->conn->exec($activityLog);

            if ($this->conn->inTransaction()) {
                $this->conn->commit();
            }
            echo "Migration 032_create_finance_users executed successfully!\n";
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            echo "Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function down()
    {
        try {
            $this->conn->beginTransaction();

            $this->conn->exec("DROP TABLE IF EXISTS user_activity_logs");
            $this->conn->exec("DROP TABLE IF EXISTS finance_users");
            $indexExists = $this->conn->query("SHOW INDEX FROM teachers WHERE Key_name = 'idx_is_exam_officer'")->fetch();
            if ($indexExists) {
                $this->conn->exec("ALTER TABLE teachers DROP INDEX idx_is_exam_officer");
            }

            $column = $this->conn->query("SHOW COLUMNS FROM teachers LIKE 'can_approve_results'")->fetch();
            if ($column) {
                $this->conn->exec("ALTER TABLE teachers DROP COLUMN can_approve_results");
            }

            $column = $this->conn->query("SHOW COLUMNS FROM teachers LIKE 'is_exam_officer'")->fetch();
            if ($column) {
                $this->conn->exec("ALTER TABLE teachers DROP COLUMN is_exam_officer");
            }

            if ($this->conn->inTransaction()) {
                $this->conn->commit();
            }
            echo "Migration 032_create_finance_users rolled back successfully!\n";
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            echo "Rollback failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run migration if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $migration = new CreateFinanceUsers();
    $migration->up();
}
