<?php

namespace App\Models;

class ParentUser extends BaseModel
{
    protected $table = 'parents';

    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    public function createParent($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->create($data);
    }

    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    public function getParentWithChildren($parentId)
    {
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(DISTINCT s.id) as child_ids,
                       GROUP_CONCAT(DISTINCT s.name) as child_names
                FROM {$this->table} p
                LEFT JOIN parent_student_links psl ON p.id = psl.parent_id AND psl.verified = 1
                LEFT JOIN students s ON psl.student_id = s.id
                WHERE p.id = :parent_id
                GROUP BY p.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        return $stmt->fetch();
    }

    public function getChildren($parentId)
    {
        $sql = "SELECT s.*, psl.verified, psl.created_at as linked_at,
                       c.class_name, c.section
                FROM parent_student_links psl
                JOIN students s ON psl.student_id = s.id
                LEFT JOIN student_enrollments se ON s.id = se.student_id
                LEFT JOIN classes c ON se.class_id = c.id
                WHERE psl.parent_id = :parent_id AND psl.verified = 1
                ORDER BY s.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        return $stmt->fetchAll();
    }

    public function verifyChildRelationship($parentId, $studentId, $dateOfBirth)
    {
        // Check both database ID and id_number
        $sql = "SELECT s.id, s.name, s.first_name, s.last_name, s.admin_id
                FROM students s
                WHERE (s.id = :student_id OR s.id_number = :student_id_number) 
                AND s.date_of_birth = :date_of_birth";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_id' => is_numeric($studentId) ? $studentId : 0,
            ':student_id_number' => $studentId,
            ':date_of_birth' => $dateOfBirth
        ]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function linkChild($parentId, $studentId, $adminId)
    {
        $sql = "INSERT INTO parent_student_links (parent_id, student_id, relationship, verified, verified_at)
                VALUES (:parent_id, :student_id, 'parent', 1, NOW())
                ON DUPLICATE KEY UPDATE verified = 1, verified_at = NOW()";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':parent_id' => $parentId,
            ':student_id' => $studentId
        ]);
    }

    public function getNotifications($parentId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT pn.*, s.name as student_name, s.id_number
                FROM parent_notifications pn
                JOIN students s ON pn.student_id = s.id
                WHERE pn.parent_id = :parent_id
                ORDER BY pn.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':parent_id', $parentId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUnreadNotificationsCount($parentId)
    {
        $sql = "SELECT COUNT(*) as count
                FROM parent_notifications
                WHERE parent_id = :parent_id AND is_read = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function markNotificationAsRead($notificationId, $parentId)
    {
        $sql = "UPDATE parent_notifications
                SET is_read = 1, read_at = NOW()
                WHERE id = :id AND parent_id = :parent_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $notificationId,
            ':parent_id' => $parentId
        ]);
    }

    public function getCommunications($parentId, $status = null)
    {
        $sql = "SELECT pc.*, s.name as student_name,
                       (SELECT COUNT(*) FROM communication_responses WHERE communication_id = pc.id) as response_count
                FROM parent_communications pc
                LEFT JOIN students s ON pc.student_id = s.id
                WHERE pc.parent_id = :parent_id";

        if ($status) {
            $sql .= " AND pc.status = :status";
        }

        $sql .= " ORDER BY pc.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $params = [':parent_id' => $parentId];
        if ($status) {
            $params[':status'] = $status;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCommunicationWithResponses($communicationId, $parentId)
    {
        $sql = "SELECT pc.*, s.name as student_name
                FROM parent_communications pc
                LEFT JOIN students s ON pc.student_id = s.id
                WHERE pc.id = :id AND pc.parent_id = :parent_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $communicationId,
            ':parent_id' => $parentId
        ]);
        $communication = $stmt->fetch();

        if ($communication) {
            $sql = "SELECT cr.*, 
                           CASE 
                               WHEN cr.responder_type = 'teacher' THEN t.name
                               WHEN cr.responder_type = 'admin' THEN a.school_name
                               ELSE 'Principal'
                           END as responder_name
                    FROM communication_responses cr
                    LEFT JOIN teachers t ON cr.responder_type = 'teacher' AND cr.responder_id = t.id
                    LEFT JOIN admins a ON cr.responder_type = 'admin' AND cr.responder_id = a.id
                    WHERE cr.communication_id = :communication_id
                    ORDER BY cr.created_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':communication_id' => $communicationId]);
            $communication['responses'] = $stmt->fetchAll();
        }

        return $communication;
    }

    public function getByAdmin($adminId, array $filters = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE admin_id = :admin_id";
        $params = [':admin_id' => $adminId];

        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(name) LIKE :search OR LOWER(email) LIKE :search OR phone LIKE :search)";
            $params[':search'] = '%' . strtolower($filters['search']) . '%';
        }

        if (isset($filters['is_verified'])) {
            $sql .= " AND is_verified = :is_verified";
            $params[':is_verified'] = (int)$filters['is_verified'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET :offset";
                $params[':offset'] = (int)$filters['offset'];
            }
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByAdmin($adminId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE admin_id = :admin_id";
        $params = [':admin_id' => $adminId];

        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(name) LIKE :search OR LOWER(email) LIKE :search OR phone LIKE :search)";
            $params[':search'] = '%' . strtolower($filters['search']) . '%';
        }

        if (isset($filters['is_verified'])) {
            $sql .= " AND is_verified = :is_verified";
            $params[':is_verified'] = (int)$filters['is_verified'];
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }
}
