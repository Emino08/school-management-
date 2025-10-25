<?php

namespace App\Utils;

use PDO;

class ActivityLogger
{
    private $db;

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
        ?string $userAgent = null
    ): bool {
        try {
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
                ':user_type' => $userType,
                ':activity_type' => $activityType,
                ':description' => $description,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':metadata' => $metadata ? json_encode($metadata) : null,
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
        ?array $metadata = null
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
            $userAgent
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}
