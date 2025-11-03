<?php

namespace App\Models;

use PDO;

class Complaint extends BaseModel
{
    protected $table = 'complaints';

    public function getComplaintsByAdmin($adminId)
    {
        return $this->findAll(['admin_id' => $adminId]);
    }

    public function getComplaintsWithDetails($adminId, $status = null, $category = null, $priority = null)
    {
        $sql = "SELECT c.*,
                       s.name as student_name, s.id_number as student_id_number,
                       t.name as teacher_name, t.email as teacher_email,
                       complained_teacher.name as complained_teacher_name
                FROM {$this->table} c
                LEFT JOIN students s ON c.user_id = s.id AND c.user_type = 'student'
                LEFT JOIN teachers t ON c.user_id = t.id AND c.user_type = 'teacher'
                LEFT JOIN teachers complained_teacher ON c.teacher_id = complained_teacher.id
                WHERE c.admin_id = :admin_id";

        $params = [':admin_id' => $adminId];

        if ($status) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $status;
        }

        if ($category) {
            $sql .= " AND c.category = :category";
            $params[':category'] = $category;
        }

        if ($priority) {
            $sql .= " AND c.priority = :priority";
            $params[':priority'] = $priority;
        }

        $sql .= " ORDER BY
                  CASE c.priority
                      WHEN 'urgent' THEN 1
                      WHEN 'high' THEN 2
                      WHEN 'medium' THEN 3
                      WHEN 'low' THEN 4
                  END,
                  c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getComplaintsByUser($userId, $userType)
    {
        return $this->findAll([
            'user_id' => $userId,
            'user_type' => $userType
        ]);
    }

    public function getComplaintsByUserWithDetails($userId, $userType)
    {
        $sql = "SELECT c.*,
                       t.name as teacher_name
                FROM {$this->table} c
                LEFT JOIN teachers t ON c.teacher_id = t.id
                WHERE c.user_id = :user_id AND c.user_type = :user_type
                ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_type' => $userType
        ]);
        return $stmt->fetchAll();
    }

    public function getComplaintWithDetails($complaintId)
    {
        $sql = "SELECT c.*,
                       s.name as student_name, s.email as student_email, s.id_number as student_id_number,
                       t.name as teacher_name, t.email as teacher_email,
                       complained_teacher.name as complained_teacher_name, complained_teacher.email as complained_teacher_email
                FROM {$this->table} c
                LEFT JOIN students s ON c.user_id = s.id AND c.user_type = 'student'
                LEFT JOIN teachers t ON c.user_id = t.id AND c.user_type = 'teacher'
                LEFT JOIN teachers complained_teacher ON c.teacher_id = complained_teacher.id
                WHERE c.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $complaintId]);
        return $stmt->fetch();
    }

    public function getComplaintStats($adminId)
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN category = 'teacher' THEN 1 ELSE 0 END) as teacher_complaints,
                    SUM(CASE WHEN category = 'academic' THEN 1 ELSE 0 END) as academic_complaints,
                    SUM(CASE WHEN category = 'disciplinary' THEN 1 ELSE 0 END) as disciplinary_complaints,
                    SUM(CASE WHEN category = 'facilities' THEN 1 ELSE 0 END) as facilities_complaints
                FROM {$this->table}
                WHERE admin_id = :admin_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetch();
    }

    public function updateComplaintStatus($complaintId, $status, $response = null)
    {
        $data = ['status' => $status];
        if ($response) {
            $data['response'] = $response;
        }
        return $this->update($complaintId, $data);
    }
}
