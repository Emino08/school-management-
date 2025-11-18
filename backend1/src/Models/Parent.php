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
                LEFT JOIN parent_student_relations psr ON p.id = psr.parent_id AND psr.is_verified = 1
                LEFT JOIN students s ON psr.student_id = s.id
                WHERE p.id = :parent_id
                GROUP BY p.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        return $stmt->fetch();
    }

    public function getChildren($parentId)
    {
        $sql = "SELECT s.*, psr.is_verified, psr.created_at as linked_at,
                       c.class_name, c.section
                FROM parent_student_relations psr
                JOIN students s ON psr.student_id = s.id
                LEFT JOIN student_enrollments se ON s.id = se.student_id
                LEFT JOIN classes c ON se.class_id = c.id
                WHERE psr.parent_id = :parent_id AND psr.is_verified = 1
                ORDER BY s.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        return $stmt->fetchAll();
    }

    public function verifyChildRelationship($parentId, $studentId, $dateOfBirth)
    {
        $sql = "SELECT s.id, s.name, s.admin_id
                FROM students s
                WHERE s.id = :student_id AND s.date_of_birth = :date_of_birth";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':date_of_birth' => $dateOfBirth
        ]);

        return $stmt->fetch();
    }

    public function linkChild($parentId, $studentId, $adminId)
    {
        $sql = "INSERT INTO parent_student_relations (parent_id, student_id, admin_id, is_verified, verified_at)
                VALUES (:parent_id, :student_id, :admin_id, 1, NOW())
                ON DUPLICATE KEY UPDATE is_verified = 1, verified_at = NOW()";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':parent_id' => $parentId,
            ':student_id' => $studentId,
            ':admin_id' => $adminId
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
}
