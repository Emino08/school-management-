<?php

require_once __DIR__ . '/../config/database.php';

class AddPrincipalRoleToAdmins
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

            // Add contact_name column for storing account display name
            $contactColumn = $this->conn
                ->query("SHOW COLUMNS FROM admins LIKE 'contact_name'")
                ->fetch();
            if (!$contactColumn) {
                $this->conn->exec(
                    "ALTER TABLE admins
                     ADD COLUMN contact_name VARCHAR(255) NULL AFTER school_name"
                );
            }

            // Add role column if missing
            $roleColumn = $this->conn
                ->query("SHOW COLUMNS FROM admins LIKE 'role'")
                ->fetch();
            if (!$roleColumn) {
                $this->conn->exec(
                    "ALTER TABLE admins
                     ADD COLUMN role ENUM('admin','principal') NOT NULL DEFAULT 'admin' AFTER password"
                );
            } else {
                // Ensure enum contains principal option
                $this->conn->exec(
                    "ALTER TABLE admins
                     MODIFY COLUMN role ENUM('admin','principal') NOT NULL DEFAULT 'admin'"
                );
            }

            // Add parent_admin_id to link principals back to their super admin
            $parentColumn = $this->conn
                ->query("SHOW COLUMNS FROM admins LIKE 'parent_admin_id'")
                ->fetch();
            if (!$parentColumn) {
                $this->conn->exec(
                    "ALTER TABLE admins
                     ADD COLUMN parent_admin_id INT NULL AFTER role"
                );
            }

            // Add index to speed up lookups by parent id
            $parentIndex = $this->conn
                ->query("SHOW INDEX FROM admins WHERE Key_name = 'idx_parent_admin'")
                ->fetch();
            if (!$parentIndex) {
                $this->conn->exec(
                    "ALTER TABLE admins
                     ADD INDEX idx_parent_admin (parent_admin_id)"
                );
            }

            // Add foreign key if it does not already exist
            $databaseName = $this->conn->query('SELECT DATABASE()')->fetchColumn();
            $fkExistsStmt = $this->conn->prepare(
                "SELECT CONSTRAINT_NAME
                   FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_SCHEMA = :schema
                    AND TABLE_NAME = 'admins'
                    AND COLUMN_NAME = 'parent_admin_id'
                    AND REFERENCED_TABLE_NAME = 'admins'"
            );
            $fkExistsStmt->execute([':schema' => $databaseName]);
            $fkExists = $fkExistsStmt->fetch();

            if (!$fkExists) {
                $this->conn->exec(
                    "ALTER TABLE admins
                     ADD CONSTRAINT fk_admin_parent
                     FOREIGN KEY (parent_admin_id) REFERENCES admins(id) ON DELETE CASCADE"
                );
            }

            // Backfill role data for existing rows
            $this->conn->exec(
                "UPDATE admins SET role = 'admin'
                 WHERE role IS NULL OR role = ''"
            );
            $this->conn->exec(
                "UPDATE admins SET parent_admin_id = NULL
                 WHERE role = 'admin'"
            );

            if ($this->conn->inTransaction()) {
                $this->conn->commit();
            }

            echo "Migration 034_add_principal_role executed successfully!\n";
            return true;
        } catch (\PDOException $e) {
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

            // Drop FK if it exists
            $databaseName = $this->conn->query('SELECT DATABASE()')->fetchColumn();
            $fkStmt = $this->conn->prepare(
                "SELECT CONSTRAINT_NAME
                   FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_SCHEMA = :schema
                    AND TABLE_NAME = 'admins'
                    AND COLUMN_NAME = 'parent_admin_id'
                    AND REFERENCED_TABLE_NAME = 'admins'"
            );
            $fkStmt->execute([':schema' => $databaseName]);
            $fk = $fkStmt->fetch(\PDO::FETCH_ASSOC);
            if ($fk) {
                $this->conn->exec(
                    "ALTER TABLE admins DROP FOREIGN KEY {$fk['CONSTRAINT_NAME']}"
                );
            }

            // Drop index
            $parentIndex = $this->conn
                ->query("SHOW INDEX FROM admins WHERE Key_name = 'idx_parent_admin'")
                ->fetch();
            if ($parentIndex) {
                $this->conn->exec(
                    "ALTER TABLE admins DROP INDEX idx_parent_admin"
                );
            }

            // Drop columns if they exist
            $contactColumn = $this->conn
                ->query("SHOW COLUMNS FROM admins LIKE 'contact_name'")
                ->fetch();
            if ($contactColumn) {
                $this->conn->exec(
                    "ALTER TABLE admins DROP COLUMN contact_name"
                );
            }

            $parentColumn = $this->conn
                ->query("SHOW COLUMNS FROM admins LIKE 'parent_admin_id'")
                ->fetch();
            if ($parentColumn) {
                $this->conn->exec(
                    "ALTER TABLE admins DROP COLUMN parent_admin_id"
                );
            }

            $roleColumn = $this->conn
                ->query("SHOW COLUMNS FROM admins LIKE 'role'")
                ->fetch();
            if ($roleColumn) {
                $this->conn->exec(
                    "ALTER TABLE admins DROP COLUMN role"
                );
            }

            if ($this->conn->inTransaction()) {
                $this->conn->commit();
            }

            echo "Migration 034_add_principal_role rolled back successfully!\n";
            return true;
        } catch (\PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            echo "Rollback failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $migration = new AddPrincipalRoleToAdmins();
    $migration->up();
}
