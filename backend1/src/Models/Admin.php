<?php

namespace App\Models;

use PDO;

class Admin extends BaseModel
{
    protected $table = 'admins';

    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Override update to handle field mapping for admins table
     * Maps frontend field names to database column names
     */
    public function update($id, $data)
    {
        if (empty($data)) {
            return true;
        }

        // Map field names to actual database columns
        $fieldMap = [
            'phone' => 'school_phone',
            'name' => 'contact_name',
        ];

        $mappedData = [];
        foreach ($data as $field => $value) {
            // Use mapped name if exists, otherwise use original
            $dbField = $fieldMap[$field] ?? $field;
            
            // Only include if column exists in table
            if ($this->columnExists($dbField)) {
                $mappedData[$dbField] = $value;
            }
        }

        // Call parent update with mapped data
        return parent::update($id, $mappedData);
    }

    public function createAdmin($data, $role = 'admin', $parentAdminId = null)
    {
        // Make sure the admins table supports super admin/principal hierarchy
        $this->ensurePrincipalSupport();

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

        // Set is_super_admin flag
        if ($this->columnExists('is_super_admin')) {
            $payload['is_super_admin'] = ($role === 'super_admin') ? 1 : 0;
        }

        if ($this->columnExists('school_phone') && isset($data['phone'])) {
            $payload['school_phone'] = $data['phone'];
        }

        foreach (['phone', 'signature', 'school_address', 'school_logo'] as $optionalField) {
            if ($this->columnExists($optionalField) && array_key_exists($optionalField, $data)) {
                $payload[$optionalField] = $data[$optionalField];
            }
        }

        return $this->create($payload);
    }

    public function getFirstAdmin()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin ?: null;
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
        $role = strtolower($role);
        if ($role === 'principal') {
            return 'principal';
        } elseif ($role === 'super_admin' || $role === 'superadmin') {
            return 'super_admin';
        }
        return 'admin';
    }

    public function isSuperAdmin($adminId)
    {
        // Ensure schema is up to date before checking role flags
        $this->ensurePrincipalSupport();

        $admin = $this->findById($adminId);
        if (!$admin) {
            return false;
        }
        $superEmails = $this->getSuperAdminEmails();
        if (!empty($superEmails) && !empty($admin['email'])) {
            $email = mb_strtolower(trim($admin['email']));
            if ($email !== '' && in_array($email, $superEmails, true)) {
                return true;
            }
        }
        
        // Check is_super_admin flag first if column exists
        if (isset($admin['is_super_admin'])) {
            return (bool) $admin['is_super_admin'];
        }
        
        // Fallback: check role
        return isset($admin['role']) && $admin['role'] === 'super_admin';
    }

    public function getAdminsBySchool($schoolId)
    {
        // Get all admins created by the super admin (same school)
        $sql = "SELECT * FROM {$this->table} 
                WHERE (parent_admin_id = :parent_id OR id = :self_id) 
                AND role IN ('super_admin', 'admin')
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':parent_id' => $schoolId,
            ':self_id' => $schoolId
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createAdminUser(array $data, int $creatorAdminId)
    {
        // Only super admins can create other admins
        if (!$this->isSuperAdmin($creatorAdminId)) {
            throw new \RuntimeException('Only super admins can create other admin users');
        }

        $creator = $this->findById($creatorAdminId);
        
        $payload = [
            'school_name' => $creator['school_name'], // Inherit from creator
            'contact_name' => $data['contact_name'] ?? ($data['name'] ?? null),
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'signature' => $data['signature'] ?? null,
            'school_address' => $creator['school_address'] ?? null,
            'school_logo' => $creator['school_logo'] ?? null,
        ];

        // New admin is linked to super admin and is a regular admin
        return $this->createAdmin($payload, 'admin', $creatorAdminId);
    }

    public function hasPrincipalSupport(): bool
    {
        return $this->columnExists('parent_admin_id')
            && $this->columnExists('role')
            && $this->columnExists('is_super_admin')
            && $this->roleSupportsSuperAdmin();
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

            // Ensure role column exists and supports super admins, admins, and principals
            if (!$this->columnExists('role')) {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     ADD COLUMN role ENUM('super_admin','admin','principal') NOT NULL DEFAULT 'admin' AFTER password",
                    [1060]
                );
            } else {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     MODIFY COLUMN role ENUM('super_admin','admin','principal') NOT NULL DEFAULT 'admin'"
                );
            }

            // Add is_super_admin flag for fast checks
            if (!$this->columnExists('is_super_admin')) {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0 AFTER role",
                    [1060]
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

            // Add supporting indexes
            $indexRole = $this->db->prepare("SHOW INDEX FROM {$this->table} WHERE Key_name = 'idx_role_admin'");
            $indexRole->execute();
            if (!$indexRole->fetch(\PDO::FETCH_ASSOC)) {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     ADD INDEX idx_role_admin (role)",
                    [1061]
                );
            }

            $indexSuperAdmin = $this->db->prepare("SHOW INDEX FROM {$this->table} WHERE Key_name = 'idx_is_super_admin'");
            $indexSuperAdmin->execute();
            if (!$indexSuperAdmin->fetch(\PDO::FETCH_ASSOC)) {
                $this->execSchemaChange(
                    "ALTER TABLE {$this->table}
                     ADD INDEX idx_is_super_admin (is_super_admin)",
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

            // Ensure role defaults and super admin flag integrity
            $this->execSchemaChange(
                "UPDATE {$this->table}
                 SET role = 'admin'
                 WHERE role IS NULL OR role = ''"
            );

            // Mark the first/root admin as super admin
            $rootStmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE parent_admin_id IS NULL ORDER BY id ASC LIMIT 1");
            $rootStmt->execute();
            $rootAdminId = $rootStmt->fetchColumn();
            if ($rootAdminId) {
                $this->execSchemaChange(
                    "UPDATE {$this->table}
                     SET role = 'super_admin', is_super_admin = 1
                     WHERE id = " . (int)$rootAdminId
                );
            }

            // Align is_super_admin flag with roles
            $this->execSchemaChange(
                "UPDATE {$this->table}
                 SET is_super_admin = 1
                 WHERE role = 'super_admin'"
            );
            $this->execSchemaChange(
                "UPDATE {$this->table}
                 SET is_super_admin = 0
                 WHERE role IN ('admin', 'principal')"
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

    private function roleSupportsSuperAdmin(): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE 'role'");
            $stmt->execute();
            $column = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$column || empty($column['Type'])) {
                return false;
            }
            return strpos(strtolower($column['Type']), 'super_admin') !== false
                && strpos(strtolower($column['Type']), 'principal') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the root admin ID for data scoping
     * Principals and admins created by super admin should see their parent's data
     * 
     * @param int $adminId The admin/principal ID
     * @return int The root admin ID (super admin)
     */
    public function getRootAdminId(int $adminId): int
    {
        try {
            // Try using the database function first (if migration was run)
            $stmt = $this->db->prepare("SELECT get_root_admin_id(:admin_id) as root_id");
            $stmt->execute([':admin_id' => $adminId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result && $result['root_id']) {
                return (int) $result['root_id'];
            }
        } catch (\Exception $e) {
            // Function doesn't exist, fall back to PHP implementation
        }

        // PHP implementation - traverse up the parent chain
        $currentId = $adminId;
        $visited = [$adminId => true];
        $maxDepth = 10; // Prevent infinite loops
        
        for ($i = 0; $i < $maxDepth; $i++) {
            $admin = $this->findById($currentId);
            
            if (!$admin) {
                return $adminId; // Return original if not found
            }
            
            // If no parent or is super admin, this is the root
            if (empty($admin['parent_admin_id']) || !empty($admin['is_super_admin'])) {
                return $currentId;
            }
            
            // Move to parent
            $parentId = (int) $admin['parent_admin_id'];
            
            // Prevent circular references
            if (isset($visited[$parentId])) {
                return $currentId;
            }
            
            $visited[$parentId] = true;
            $currentId = $parentId;
        }
        
        return $currentId;
    }

    private function getSuperAdminEmails(): array
    {
        $raw = getenv('SUPER_ADMIN_EMAILS') ?: getenv('SUPER_ADMIN_EMAIL');
        if (!$raw) {
            return [];
        }

        $parts = array_filter(array_map('trim', explode(',', $raw)));
        $lowered = array_unique(array_map('mb_strtolower', $parts));
        return array_filter($lowered);
    }

    /**
     * Check if an admin can create other admins
     * Only super admins can create other admins
     */
    public function canCreateAdmins(int $adminId): bool
    {
        return $this->isSuperAdmin($adminId);
    }

    /**
     * Check if an admin can create principals
     * Super admins and regular admins can create principals
     */
    public function canCreatePrincipals(int $adminId): bool
    {
        $admin = $this->findById($adminId);
        if (!$admin) {
            return false;
        }
        
        $role = strtolower($admin['role'] ?? 'admin');
        // Super admins and admins can create principals, principals cannot
        return in_array($role, ['super_admin', 'admin'], true);
    }

    /**
     * Get permissions for an admin based on their role
     */
    public function getPermissions(int $adminId): array
    {
        $admin = $this->findById($adminId);
        if (!$admin) {
            return [];
        }
        
        $role = strtolower($admin['role'] ?? 'admin');
        $isSuperAdmin = !empty($admin['is_super_admin']);
        
        return [
            'can_create_admins' => $isSuperAdmin,
            'can_create_principals' => in_array($role, ['super_admin', 'admin'], true),
            'can_access_system_settings' => $isSuperAdmin || $role === 'admin',
            'can_view_activity_logs' => $isSuperAdmin || $role === 'admin',
            'can_manage_all_users' => true,
            'can_manage_students' => true,
            'can_manage_teachers' => true,
            'can_manage_classes' => true,
            'can_manage_subjects' => true,
            'can_manage_exams' => true,
            'can_manage_fees' => true,
            'can_view_reports' => true,
            'role' => $role,
            'is_super_admin' => $isSuperAdmin,
            'is_principal' => $role === 'principal',
            'is_admin' => $role === 'admin'
        ];
    }

    /**
     * Get the effective admin ID for querying data
     * Principals and sub-admins should use the root admin's ID
     */
    public function getEffectiveAdminId($adminId)
    {
        return $this->getRootAdminId($adminId);
    }
}
