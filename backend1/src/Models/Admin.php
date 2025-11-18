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
        if (!$this->hasPrincipalSupport()) {
            throw new \RuntimeException('Principal role columns not found on admins table. Please run the 034_add_principal_role migration.');
        }
    }

    private function columnExists(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache) && $cache[$column] === true) {
            return true;
        }
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE :column");
            $stmt->execute([':column' => $column]);
            $cache[$column] = (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $cache[$column] = false;
        }
        return $cache[$column];
    }
}
