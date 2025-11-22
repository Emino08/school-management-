<?php

namespace App\Models;

class TeacherAssignment extends BaseModel
{
    protected $table = 'teacher_assignments';

    public function assignTeacher($teacherId, $subjectId, $academicYearId)
    {
        $existing = $this->findOne([
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'academic_year_id' => $academicYearId
        ]);

        if ($existing) {
            return $existing['id'];
        }

        return $this->create([
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'academic_year_id' => $academicYearId
        ]);
    }

    public function getTeacherSubjects($teacherId, $academicYearId)
    {
        $sql = "SELECT s.*, c.class_name, c.section
                FROM {$this->table} ta
                INNER JOIN subjects s ON ta.subject_id = s.id
                INNER JOIN classes c ON s.class_id = c.id
                WHERE ta.teacher_id = :teacher_id
                  AND ta.academic_year_id = :academic_year_id
                ORDER BY c.grade_level, s.subject_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }

    public function getTeacherClasses($teacherId, $academicYearId)
    {
        $sql = "SELECT DISTINCT c.id, c.class_name, c.section, c.grade_level,
                       COUNT(DISTINCT s.id) as subject_count
                FROM {$this->table} ta
                INNER JOIN subjects s ON ta.subject_id = s.id
                INNER JOIN classes c ON s.class_id = c.id
                WHERE ta.teacher_id = :teacher_id
                  AND ta.academic_year_id = :academic_year_id
                GROUP BY c.id, c.class_name, c.section, c.grade_level
                ORDER BY c.grade_level, c.class_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }

    public function removeAssignment($teacherId, $subjectId, $academicYearId)
    {
        $sql = "DELETE FROM {$this->table}
                WHERE teacher_id = :teacher_id
                  AND subject_id = :subject_id
                  AND academic_year_id = :academic_year_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':teacher_id' => $teacherId,
            ':subject_id' => $subjectId,
            ':academic_year_id' => $academicYearId
        ]);
    }
}
