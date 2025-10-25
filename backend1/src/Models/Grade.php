<?php

namespace App\Models;

class Grade extends BaseModel
{
    protected $table = 'grades';

    public function getStudentGrades($studentId, $academicYearId)
    {
        $sql = "SELECT g.*, s.subject_name, s.subject_code
                FROM {$this->table} g
                INNER JOIN subjects s ON g.subject_id = s.id
                WHERE g.student_id = :student_id
                  AND g.academic_year_id = :academic_year_id
                ORDER BY s.subject_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }

    public function calculateGrade($percentage)
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B+';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C+';
        if ($percentage >= 40) return 'C';
        return 'F';
    }

    public function updateOrCreateGrade($studentId, $subjectId, $academicYearId, $percentage, $remarks = null)
    {
        $grade = $this->calculateGrade($percentage);

        $existing = $this->findOne([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'academic_year_id' => $academicYearId
        ]);

        $data = [
            'percentage' => $percentage,
            'grade' => $grade,
            'remarks' => $remarks
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['student_id'] = $studentId;
            $data['subject_id'] = $subjectId;
            $data['academic_year_id'] = $academicYearId;
            return $this->create($data);
        }
    }
}
