<?php

namespace App\Utils;

use PDO;

class ActivityLogger
{
    private $db;
    private $userNameCache = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log an activity
     * 
     * @param int $userId User ID
     * @param string $userType admin, teacher, student, exam_officer
     * @param string $activityType login, logout, create, update, delete, view, publish, etc
     * @param string $description Human readable description
     * @param string|null $entityType student, teacher, result, fee, etc
     * @param int|null $entityId Entity ID
     * @param array|null $metadata Additional data
     * @param string|null $ipAddress IP address
     * @param string|null $userAgent User agent
     */
    public function log(
        int $userId,
        string $userType,
        string $activityType,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $userName = null
    ): bool {
        try {
            $normalizedType = $this->normalizeRole($userType);
            $metadataPayload = $this->prepareMetadata(
                $metadata,
                $userId,
                $normalizedType,
                $userName
            );

            $sql = "INSERT INTO activity_logs (
                        user_id, user_type, activity_type, description,
                        entity_type, entity_id, metadata, ip_address, user_agent
                    ) VALUES (
                        :user_id, :user_type, :activity_type, :description,
                        :entity_type, :entity_id, :metadata, :ip_address, :user_agent
                    )";

            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $normalizedType,
                ':activity_type' => $activityType,
                ':description' => $description,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':metadata' => $metadataPayload ? json_encode($metadataPayload) : null,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent
            ]);
        } catch (\Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log from HTTP request
     */
    public function logFromRequest(
        $request,
        int $userId,
        string $userType,
        string $activityType,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null,
        ?string $userName = null
    ): bool {
        $serverParams = $request->getServerParams();
        $ipAddress = $serverParams['REMOTE_ADDR'] ?? null;
        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? null;

        return $this->log(
            $userId,
            $userType,
            $activityType,
            $description,
            $entityType,
            $entityId,
            $metadata,
            $ipAddress,
            $userAgent,
            $userName
        );
    }

    /**
     * Get activity logs
     */
    public function getLogs(
        ?int $userId = null,
        ?string $userType = null,
        ?string $activityType = null,
        ?string $entityType = null,
        ?int $limit = 100,
        ?int $offset = 0
    ): array {
        $sql = "SELECT * FROM activity_logs WHERE 1=1";
        $params = [];

        if ($userId) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($userType) {
            $sql .= " AND user_type = :user_type";
            $params[':user_type'] = $userType;
        }

        if ($activityType) {
            $sql .= " AND activity_type = :activity_type";
            $params[':activity_type'] = $activityType;
        }

        if ($entityType) {
            $sql .= " AND entity_type = :entity_type";
            $params[':entity_type'] = $entityType;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($logs as &$log) {
            $decodedMetadata = $this->decodeMetadata($log['metadata'] ?? null);
            $log['metadata_parsed'] = $decodedMetadata;
            $log['performed_by'] = $decodedMetadata['performed_by'] ?? $this->buildActorSummary($log);
        }
        unset($log);

        return $logs;
    }

    /**
     * Get activity statistics
     */
    public function getStats(?int $userId = null, ?string $userType = null): array
    {
        $sql = "SELECT 
                    activity_type,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM activity_logs
                WHERE 1=1";
        
        $params = [];

        if ($userId) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($userType) {
            $sql .= " AND user_type = :user_type";
            $params[':user_type'] = $userType;
        }

        $sql .= " GROUP BY activity_type, DATE(created_at)
                  ORDER BY date DESC, count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function normalizeRole(string $userType): string
    {
        return strtolower(trim($userType));
    }

    private function prepareMetadata(?array $metadata, int $userId, string $userType, ?string $userName): array
    {
        $metadata = $metadata ?? [];
        $actor = [
            'id' => $userId,
            'type' => $userType,
            'name' => $userName ?: $this->resolveUserName($userId, $userType)
        ];

        if (!isset($metadata['performed_by']) || !is_array($metadata['performed_by'])) {
            $metadata['performed_by'] = $actor;
        } else {
            $metadata['performed_by'] = array_merge($actor, $metadata['performed_by']);
        }

        if (!isset($metadata['timestamp'])) {
            $metadata['timestamp'] = date('c');
        }

        return $metadata;
    }

    private function decodeMetadata(?string $raw): array
    {
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function buildActorSummary(array $log): array
    {
        $userId = isset($log['user_id']) ? (int)$log['user_id'] : null;
        $userType = isset($log['user_type']) ? (string)$log['user_type'] : '';

        return [
            'id' => $userId,
            'type' => $userType,
            'name' => $userId ? $this->resolveUserName($userId, $userType) : null,
        ];
    }

    private function resolveUserName(?int $userId, ?string $userType): ?string
    {
        if (!$userId || !$userType) {
            return null;
        }

        $normalizedType = $this->normalizeRole($userType);
        $cacheKey = $normalizedType . ':' . $userId;
        if (isset($this->userNameCache[$cacheKey])) {
            return $this->userNameCache[$cacheKey];
        }

        $tableMap = [
            'admin' => ['admins', ['contact_name', 'name', 'full_name', 'school_name', 'email']],
            'principal' => ['admins', ['contact_name', 'name', 'full_name', 'school_name', 'email']],
            'teacher' => ['teachers', ['name', 'full_name', 'email']],
            'exam_officer' => ['teachers', ['name', 'full_name', 'email']],
            'student' => ['students', ['name', 'full_name', 'id_number', 'email']],
            'parent' => ['parents', ['name', 'full_name', 'email', 'phone']],
            'finance' => ['finance_users', ['name', 'email']],
            'finance_user' => ['finance_users', ['name', 'email']],
        ];

        $name = null;
        if (isset($tableMap[$normalizedType])) {
            [$table, $candidates] = $tableMap[$normalizedType];
            $name = $this->fetchUserName($table, $candidates, $userId);
        }

        if (!$name && $normalizedType !== 'admin') {
            $name = $this->fetchUserName('admins', ['contact_name', 'name', 'school_name', 'email'], $userId);
        }

        $this->userNameCache[$cacheKey] = $name ?: ("User #{$userId}");
        return $this->userNameCache[$cacheKey];
    }

    private function fetchUserName(string $table, array $fields, int $userId): ?string
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            foreach ($fields as $field) {
                if (!empty($row[$field])) {
                    return (string)$row[$field];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    public static function guessDisplayName($user): ?string
    {
        if (!$user) {
            return null;
        }

        $fields = [
            'name',
            'full_name',
            'fullName',
            'contact_name',
            'contactName',
            'school_name',
            'schoolName',
            'email',
            'username'
        ];

        foreach ($fields as $field) {
            if (is_array($user) && !empty($user[$field])) {
                return (string)$user[$field];
            }
            if (is_object($user) && !empty($user->{$field})) {
                return (string)$user->{$field};
            }
        }

        return null;
    }
}
