<?php

namespace App\Models;

class ResultModel extends BaseModel
{
    protected $table = 'exam_results';

    public function getStudentResults($studentId, $examId)
    {
        $sql = "SELECT er.*, s.subject_name, s.subject_code, e.exam_name, e.exam_type
                FROM {$this->table} er
                JOIN subjects s ON er.subject_id = s.id
                JOIN exams e ON er.exam_id = e.id
                WHERE er.student_id = :student_id AND er.exam_id = :exam_id
                ORDER BY s.subject_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId, ':exam_id' => $examId]);
        return $stmt->fetchAll();
    }

    public function getStudentAllResults($studentId, $academicYearId = null)
    {
        $sql = "SELECT er.*, s.subject_name, s.subject_code, e.exam_name, e.exam_type, e.exam_date
                FROM {$this->table} er
                JOIN subjects s ON er.subject_id = s.id
                JOIN exams e ON er.exam_id = e.id";

        if ($academicYearId) {
            $sql .= " WHERE er.student_id = :student_id AND e.academic_year_id = :year_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $studentId, ':year_id' => $academicYearId]);
        } else {
            $sql .= " WHERE er.student_id = :student_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $studentId]);
        }

        return $stmt->fetchAll();
    }

    public function calculateGrade($percentage)
    {
        $sql = "SELECT grade, remarks FROM grading_system
                WHERE :percentage BETWEEN min_percentage AND max_percentage
                AND is_active = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':percentage' => $percentage]);
        return $stmt->fetch();
    }

    public function bulkInsertResults($results)
    {
        $sql = "INSERT INTO {$this->table}
                (exam_id, student_id, subject_id, test_score, exam_score, grade, remarks)
                VALUES (:exam_id, :student_id, :subject_id, :test_score, :exam_score, :grade, :remarks)
                ON DUPLICATE KEY UPDATE
                test_score = VALUES(test_score),
                exam_score = VALUES(exam_score),
                grade = VALUES(grade),
                remarks = VALUES(remarks)";

        $stmt = $this->db->prepare($sql);

        foreach ($results as $result) {
            $stmt->execute($result);
        }

        return true;
    }

    public function getClassResults($classId, $examId)
    {
        $sql = "SELECT st.id as student_id, st.name as student_name, st.roll_num,
                       er.subject_id, s.subject_name, er.test_score, er.exam_score,
                       er.total_score, er.grade
                FROM students st
                LEFT JOIN {$this->table} er ON st.id = er.student_id AND er.exam_id = :exam_id
                LEFT JOIN subjects s ON er.subject_id = s.id
                WHERE st.sclass_id = :class_id
                ORDER BY st.roll_num, s.subject_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':class_id' => $classId, ':exam_id' => $examId]);
        return $stmt->fetchAll();
    }
}
