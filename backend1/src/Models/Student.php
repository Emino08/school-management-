<?php

namespace App\Models;

class Student extends BaseModel
{
    protected $table = 'students';

    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    public function findByIdNumber($adminId, $idNumber)
    {
        return $this->findOne(['admin_id' => $adminId, 'id_number' => $idNumber]);
    }

    public function createStudent($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->create($data);
    }

    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    public function getStudentsWithEnrollment($adminId, $academicYearId)
    {
        $sql = "SELECT s.*, se.class_id, s.status, se.class_average, se.attendance_percentage,
                       c.class_name, c.section
                FROM {$this->table} s
                LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.academic_year_id = :academic_year_id
                LEFT JOIN classes c ON se.class_id = c.id
                WHERE s.admin_id = :admin_id
                ORDER BY c.grade_level, s.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }

    public function getStudentsByClass($classId, $academicYearId)
    {
        $sql = "SELECT s.*, s.status, se.class_average, se.attendance_percentage
                FROM {$this->table} s
                INNER JOIN student_enrollments se ON s.id = se.student_id
                WHERE se.class_id = :class_id AND se.academic_year_id = :academic_year_id
                ORDER BY s.id_number";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }
}
