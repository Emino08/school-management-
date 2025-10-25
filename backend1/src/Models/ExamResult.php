<?php

namespace App\Models;

class ExamResult extends BaseModel
{
    protected $table = 'exam_results';

    public function getStudentResults($studentId, $examId)
    {
        $sql = "SELECT er.*, s.subject_name, s.subject_code, e.total_marks
                FROM {$this->table} er
                INNER JOIN subjects s ON er.subject_id = s.id
                INNER JOIN exams e ON er.exam_id = e.id
                WHERE er.student_id = :student_id AND er.exam_id = :exam_id
                ORDER BY s.subject_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':exam_id' => $examId
        ]);
        return $stmt->fetchAll();
    }

    public function recordResult($data)
    {
        $existing = $this->findOne([
            'student_id' => $data['student_id'],
            'exam_id' => $data['exam_id'],
            'subject_id' => $data['subject_id']
        ]);

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->create($data);
        }
    }
}
