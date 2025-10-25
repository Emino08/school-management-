<?php

namespace App\Models;

class Exam extends BaseModel
{
    protected $table = 'exams';

    public function getExamsByAcademicYear($academicYearId, $classId = null)
    {
        if ($classId) {
            // Get exams that have results for students in the specified class
            $sql = "SELECT DISTINCT e.*, ay.year_name, c.class_name
                    FROM {$this->table} e
                    LEFT JOIN academic_years ay ON e.academic_year_id = ay.id
                    LEFT JOIN exam_results er ON e.id = er.exam_id
                    LEFT JOIN student_enrollments se ON er.student_id = se.student_id
                        AND se.academic_year_id = e.academic_year_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    WHERE e.academic_year_id = :academic_year_id
                    AND (se.class_id = :class_id OR er.id IS NULL)
                    ORDER BY e.exam_date DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':academic_year_id' => $academicYearId,
                ':class_id' => $classId
            ]);
            return $stmt->fetchAll();
        }

        return $this->findAll(['academic_year_id' => $academicYearId]);
    }

    public function getExamWithResults($examId)
    {
        $sql = "SELECT e.*,
                       COUNT(DISTINCT er.student_id) as students_appeared
                FROM {$this->table} e
                LEFT JOIN exam_results er ON e.id = er.exam_id
                WHERE e.id = :exam_id
                GROUP BY e.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':exam_id' => $examId]);
        return $stmt->fetch();
    }
}
