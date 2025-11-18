<?php

namespace App\Models;

class MedicalRecord extends BaseModel
{
    protected $table = 'medical_records';

    public function getStudentRecords($studentId, $status = null)
    {
        $sql = "SELECT mr.*, ms.name as medical_staff_name, ms.qualification,
                       s.name as student_name, s.id_number
                FROM {$this->table} mr
                LEFT JOIN medical_staff ms ON mr.medical_staff_id = ms.id
                LEFT JOIN students s ON mr.student_id = s.id
                WHERE mr.student_id = :student_id";

        if ($status) {
            $sql .= " AND mr.status = :status";
        }

        $sql .= " ORDER BY mr.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $params = [':student_id' => $studentId];
        if ($status) {
            $params[':status'] = $status;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActiveRecords($adminId)
    {
        $sql = "SELECT mr.*, ms.name as medical_staff_name,
                       s.name as student_name, s.id_number, s.phone,
                       c.class_name
                FROM {$this->table} mr
                LEFT JOIN medical_staff ms ON mr.medical_staff_id = ms.id
                LEFT JOIN students s ON mr.student_id = s.id
                LEFT JOIN student_enrollments se ON s.id = se.student_id
                LEFT JOIN classes c ON se.class_id = c.id
                WHERE mr.admin_id = :admin_id 
                AND mr.status IN ('active', 'under_treatment')
                ORDER BY mr.severity DESC, mr.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetchAll();
    }

    public function closeRecord($recordId, $notes = null)
    {
        $sql = "UPDATE {$this->table}
                SET status = 'completed', 
                    completed_at = NOW(),
                    notes = CONCAT(IFNULL(notes, ''), '\n\nClosed: ', :notes)
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $recordId,
            ':notes' => $notes ?? 'Treatment completed'
        ]);
    }

    public function getRecordById($id)
    {
        $sql = "SELECT mr.*, ms.name as medical_staff_name, ms.qualification,
                       s.name as student_name, s.id_number, s.date_of_birth,
                       s.gender, s.blood_group, s.allergies
                FROM {$this->table} mr
                LEFT JOIN medical_staff ms ON mr.medical_staff_id = ms.id
                LEFT JOIN students s ON mr.student_id = s.id
                WHERE mr.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getStudentDocuments($studentId)
    {
        $sql = "SELECT md.*, p.name as uploaded_by_name
                FROM medical_documents md
                LEFT JOIN parents p ON md.parent_id = p.id
                WHERE md.student_id = :student_id
                ORDER BY md.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        return $stmt->fetchAll();
    }
}
