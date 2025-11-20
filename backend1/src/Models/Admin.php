<?php

namespace App\Models;

class Admin extends BaseModel
{
    protected $table = 'admins';

    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    public function createAdmin($data, $role = 'admin', $parentAdminId = null)
    {
        $payload = [
            'school_name' => $data['school_name'] ?? '',
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $this->normalizeRole($role),
        ];

        if ($this->columnExists('contact_name')) {
            $payload['contact_name'] = $data['contact_name'] ?? ($data['name'] ?? null);
        }

        if ($this->columnExists('parent_admin_id')) {
            $payload['parent_admin_id'] = $parentAdminId;
        }

        foreach (['phone', 'signature', 'school_address', 'school_logo'] as $optionalField) {
            if ($this->columnExists($optionalField) && array_key_exists($optionalField, $data)) {
                $payload[$optionalField] = $data[$optionalField];
            }
        }

        return $this->create($payload);
    }

    public function createPrincipal(array $data, array $ownerAdmin)
    {
        $this->ensurePrincipalSupport();
        $payload = [
            'school_name' => $ownerAdmin['school_name'] ?? ($data['school_name'] ?? ''),
            'contact_name' => $data['contact_name'] ?? ($data['name'] ?? null),
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'signature' => $data['signature'] ?? null,
            'school_address' => $ownerAdmin['school_address'] ?? null,
            'school_logo' => $ownerAdmin['school_logo'] ?? null,
        ];

        // Principals are always linked back to the owning admin
        return $this->createAdmin($payload, 'principal', $ownerAdmin['id']);
    }

    public function getPrincipalsByAdmin($adminId)
    {
        if (!$this->hasPrincipalSupport()) {
            return [];
        }
        $selectParts = [
            'id',
            $this->columnExists('contact_name') ? 'contact_name' : 'NULL as contact_name',
            'email',
        ];
        $selectParts[] = $this->columnExists('phone') ? 'phone' : 'NULL as phone';
        $selectParts[] = $this->columnExists('signature') ? 'signature' : 'NULL as signature';
        $selectParts[] = 'created_at';
        $selectParts[] = 'updated_at';
        $selectParts[] = $this->columnExists('role') ? 'role' : "'admin' as role";

        $sql = "SELECT " . implode(', ', $selectParts) . "
                FROM {$this->table}
                WHERE parent_admin_id = :admin_id AND role = 'principal'
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findPrincipalById($principalId, $adminId)
    {
        if (!$this->hasPrincipalSupport()) {
            return null;
        }
        $sql = "SELECT * FROM {$this->table}
                WHERE id = :id AND parent_admin_id = :admin_id AND role = 'principal'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $principalId,
            ':admin_id' => $adminId
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updatePrincipal($principalId, $adminId, array $data)
    {
        $this->ensurePrincipalSupport();
        $fields = [];
        $params = [
            ':id' => $principalId,
            ':admin_id' => $adminId
        ];

        $map = [
            'contact_name' => 'contact_name',
            'name' => 'contact_name',
            'email' => 'email',
            'phone' => 'phone',
            'signature' => 'signature'
        ];

        foreach ($map as $input => $column) {
            if (!$this->columnExists($column)) {
                continue;
            }
            if (array_key_exists($input, $data) && $data[$input] !== null) {
                $fields[$column] = $data[$input];
            }
        }

        if (!empty($data['password'])) {
            $fields['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $setClauses = [];
        foreach ($fields as $column => $value) {
            $param = ':' . $column;
            $setClauses[] = "{$column} = {$param}";
            $params[$param] = $value;
        }

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $setClauses) . "
                WHERE id = :id AND parent_admin_id = :admin_id AND role = 'principal'";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function deletePrincipal($principalId, $adminId)
    {
        if (!$this->hasPrincipalSupport()) {
            return false;
        }
        $sql = "DELETE FROM {$this->table}
                WHERE id = :id AND parent_admin_id = :admin_id AND role = 'principal'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $principalId,
            ':admin_id' => $adminId
        ]);
    }

    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    private function normalizeRole($role)
    {
        return strtolower($role) === 'principal' ? 'principal' : 'admin';
    }

    public function hasPrincipalSupport(): bool
    {
        return $this->columnExists('parent_admin_id') && $this->columnExists('role');
    }

    private function ensurePrincipalSupport(): void
    {
        if ($this->hasPrincipalSupport()) {
            return;
        }

        $this->attemptPrincipalSchemaUpgrade();

        if (!$this->hasPrincipalSupport()) {
            throw new \RuntimeException('Principal role columns not found on admins table. Please run the 034_add_principal_role migration.');
        }
    }

    private function attemptPrincipalSchemaUpgrade(): void
    {
        try {
            $this->db->beginTransaction();

            if (!$this->columnExists('contact_name')) {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     ADD COLUMN contact_name VARCHAR(255) NULL AFTER school_name",
                    [1060]
                );
            }

            if (!$this->columnExists('role')) {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     ADD COLUMN role ENUM('admin','principal') NOT NULL DEFAULT 'admin' AFTER password",
                    [1060]
                );
            } else {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     MODIFY COLUMN role ENUM('admin','principal') NOT NULL DEFAULT 'admin'"
                );
            }

            if (!$this->columnExists('parent_admin_id')) {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     ADD COLUMN parent_admin_id INT NULL AFTER role",
                    [1060]
                );
            }

            $indexStmt = $this->db->prepare("SHOW INDEX FROM {$this->table} WHERE Key_name = 'idx_parent_admin'");
            $indexStmt->execute();
            if (!$indexStmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     ADD INDEX idx_parent_admin (parent_admin_id)",
                    [1061]
                );
            }

            $databaseName = $this->db->query('SELECT DATABASE()')->fetchColumn();
            if ($databaseName) {
                $fkStmt = $this->db->prepare(
                    "SELECT CONSTRAINT_NAME
                       FROM information_schema.KEY_COLUMN_USAGE
                      WHERE TABLE_SCHEMA = :schema
                        AND TABLE_NAME = :table_name
                        AND COLUMN_NAME = 'parent_admin_id'
                        AND REFERENCED_TABLE_NAME = :ref_table
                      LIMIT 1"
                );
                $fkStmt->execute([
                    ':schema' => $databaseName,
                    ':table_name' => $this->table,
                    ':ref_table' => $this->table
                ]);
                if (!$fkStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $this->execSchemaChange(
                        "ALTER TABLE {$this->table}
                         ADD CONSTRAINT fk_admin_parent_admin
                         FOREIGN KEY (parent_admin_id) REFERENCES {$this->table}(id) ON DELETE CASCADE",
                        [1826, 1828, 1832]
                    );
                }
            }

            $this->execSchemaChange(
                "UPDATE {$this->table}
                 SET role = 'admin'
                 WHERE role IS NULL OR role = ''"
            );
            $this->execSchemaChange(
                "UPDATE {$this->table}
                 SET parent_admin_id = NULL
                 WHERE role = 'admin'"
            );

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new \RuntimeException(
                'Principal role columns not found on admins table and automatic migration failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function execSchemaChange(string $sql, array $ignoreErrorCodes = []): void
    {
        try {
            $this->db->exec($sql);
        } catch (\PDOException $e) {
            $errorCode = $e->errorInfo[1] ?? null;
            if ($errorCode !== null && in_array($errorCode, $ignoreErrorCodes, true)) {
                return;
            }
            throw $e;
        }
    }

    private function columnExists(string $column): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT 1
                   FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = :table_name
                    AND COLUMN_NAME = :column
                  LIMIT 1"
            );
            $stmt->execute([
                ':table_name' => $this->table,
                ':column' => $column
            ]);
            return (bool) $stmt->fetchColumn();
        } catch (\Exception $e) {
            return false;
        }
    }
}
