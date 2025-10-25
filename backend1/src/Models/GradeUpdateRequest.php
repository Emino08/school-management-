<?php

namespace App\Models;

class GradeUpdateRequest extends BaseModel
{
    protected $table = 'grade_update_requests';

    /**
     * Create a new grade update request
     */
    public function createRequest($data)
    {
        return $this->create($data);
    }

    /**
     * Get all requests by status
     */
    public function getRequestsByStatus($status = 'pending')
    {
        $sql = "SELECT
                    gur.*,
                    er.exam_id,
                    er.subject_id,
                    er.student_id,
                    s.name as student_name,
                    s.id_number,
                    sub.subject_name,
                    sub.subject_code,
                    e.exam_name,
                    t.name as teacher_name,
                    c.class_name
                FROM {$this->table} gur
                INNER JOIN exam_results er ON gur.result_id = er.id
                INNER JOIN students s ON er.student_id = s.id
                INNER JOIN subjects sub ON er.subject_id = sub.id
                INNER JOIN exams e ON er.exam_id = e.id
                INNER JOIN teachers t ON gur.teacher_id = t.id
                LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.academic_year_id = e.academic_year_id
                LEFT JOIN classes c ON se.class_id = c.id
                WHERE gur.status = :status
                ORDER BY gur.requested_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => $status]);
        return $stmt->fetchAll();
    }

    /**
     * Get requests by teacher
     */
    public function getRequestsByTeacher($teacherId, $status = null)
    {
        $sql = "SELECT
                    gur.*,
                    er.exam_id,
                    er.subject_id,
                    er.student_id,
                    s.name as student_name,
                    s.id_number,
                    sub.subject_name,
                    e.exam_name,
                    c.class_name
                FROM {$this->table} gur
                INNER JOIN exam_results er ON gur.result_id = er.id
                INNER JOIN students s ON er.student_id = s.id
                INNER JOIN subjects sub ON er.subject_id = sub.id
                INNER JOIN exams e ON er.exam_id = e.id
                LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.academic_year_id = e.academic_year_id
                LEFT JOIN classes c ON se.class_id = c.id
                WHERE gur.teacher_id = :teacher_id";

        $params = [':teacher_id' => $teacherId];

        if ($status) {
            $sql .= " AND gur.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY gur.requested_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get pending requests for a specific result
     */
    public function getPendingRequestForResult($resultId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE result_id = :result_id
                AND status = 'pending'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':result_id' => $resultId]);
        return $stmt->fetch();
    }

    /**
     * Approve a grade change request
     */
    public function approveRequest($requestId, $approvedBy)
    {
        return $this->update($requestId, [
            'status' => 'approved',
            'approved_by_exam_officer' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Reject a grade change request
     */
    public function rejectRequest($requestId, $rejectedBy, $rejectionReason)
    {
        return $this->update($requestId, [
            'status' => 'rejected',
            'rejected_by' => $rejectedBy,
            'rejected_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $rejectionReason
        ]);
    }

    /**
     * Get statistics for grade update requests
     */
    public function getRequestStats($teacherId = null)
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM {$this->table}";

        $params = [];
        if ($teacherId) {
            $sql .= " WHERE teacher_id = :teacher_id";
            $params[':teacher_id'] = $teacherId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Check if there's already a pending request for a result
     */
    public function hasPendingRequest($resultId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE result_id = :result_id AND status = 'pending'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':result_id' => $resultId]);
        $result = $stmt->fetch();

        return $result['count'] > 0;
    }
}
