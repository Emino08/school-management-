<?php

namespace App\Models;

class Subject extends BaseModel
{
    protected $table = 'subjects';

    public function getSubjectsByClass($classId)
    {
        return $this->findAll(['class_id' => $classId]);
    }

    public function getSubjectsWithTeacher($classId, $academicYearId)
    {
        $sql = "SELECT s.*,
                       t.name as teacher_name,
                       t.id as teacher_id
                FROM {$this->table} s
                LEFT JOIN teacher_assignments ta ON s.id = ta.subject_id
                    AND ta.academic_year_id = :academic_year_id
                LEFT JOIN teachers t ON ta.teacher_id = t.id
                WHERE s.class_id = :class_id
                ORDER BY s.subject_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }

    public function getFreeSubjectsByClass($classId, $academicYearId)
    {
        $sql = "SELECT s.*
                FROM {$this->table} s
                LEFT JOIN teacher_assignments ta ON s.id = ta.subject_id
                    AND ta.academic_year_id = :academic_year_id
                WHERE s.class_id = :class_id
                  AND ta.subject_id IS NULL
                ORDER BY s.subject_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }

    public function deleteSubject($subjectId)
    {
        // Check if subject has any grades
        $sql = "SELECT COUNT(*) as count FROM grades WHERE subject_id = :subject_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':subject_id' => $subjectId]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            throw new \Exception('Cannot delete subject with existing grades');
        }

        return $this->delete($subjectId);
    }
}
