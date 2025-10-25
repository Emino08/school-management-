<?php

namespace App\Models;

class Teacher extends BaseModel
{
    protected $table = 'teachers';

    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    public function createTeacher($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->create($data);
    }

    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    public function getTeachersWithSubjects($adminId, $academicYearId, $includeDeleted = false)
    {
        $deletedCondition = $includeDeleted ? "" : "AND t.is_deleted = 0";
        
        $sql = "SELECT t.*,
                       GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') as subjects,
                       GROUP_CONCAT(DISTINCT s.id SEPARATOR ',') as subject_ids
                FROM {$this->table} t
                LEFT JOIN teacher_assignments ta ON t.id = ta.teacher_id AND ta.academic_year_id = :academic_year_id
                LEFT JOIN subjects s ON ta.subject_id = s.id
                WHERE t.admin_id = :admin_id {$deletedCondition}
                GROUP BY t.id
                ORDER BY t.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }
    
    public function softDelete($id)
    {
        $sql = "UPDATE {$this->table} SET is_deleted = 1, deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function restore($id)
    {
        $sql = "UPDATE {$this->table} SET is_deleted = 0, deleted_at = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function getDeletedTeachers($adminId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE admin_id = :admin_id AND is_deleted = 1 ORDER BY deleted_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetchAll();
    }
}
