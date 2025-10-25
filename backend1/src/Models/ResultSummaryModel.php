<?php

namespace App\Models;

class ResultSummaryModel extends BaseModel
{
    protected $table = 'result_summary';

    public function calculateAndStoreSummary($studentId, $examId, $classId, $academicYearId)
    {
        // Get all results for this student in this exam
        // Calculate average score (mean of all subjects)
        $sql = "SELECT 
                    SUM(total_score) as total_obtained,
                    COUNT(*) * 100 as total_possible,
                    AVG(average_score) as mean_score,
                    COUNT(*) as subject_count
                FROM exam_results
                WHERE student_id = :student_id AND exam_id = :exam_id
                AND approval_status = 'approved'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId, ':exam_id' => $examId]);
        $result = $stmt->fetch();

        if (!$result || $result['total_obtained'] === null) {
            return false;
        }

        $totalObtained = $result['total_obtained'];
        $totalPossible = $result['total_possible'];
        $averageScore = $result['mean_score']; // This is the mean average across all subjects
        $subjectCount = $result['subject_count'];
        
        // For percentage, we still calculate based on total marks
        $percentage = ($totalObtained / $totalPossible) * 100;

        // Get grade based on average score (not percentage)
        // Average is what matters for grading, not total percentage
        $gradeInfo = $this->calculateGrade($averageScore);
        $grade = $gradeInfo['grade'] ?? 'F';
        $remarks = $gradeInfo['remarks'] ?? 'Fail';

        // Calculate position based on average score
        $position = $this->calculatePosition($classId, $examId, $averageScore);

        // Get total students in class
        $totalStudents = $this->getTotalStudentsInClass($classId, $examId);

        // Insert or update summary
        $sql = "INSERT INTO {$this->table}
                (student_id, exam_id, class_id, academic_year_id, total_marks_obtained,
                 total_possible_marks, percentage, average_score, subject_count, grade, 
                 position, total_students, remarks)
                VALUES (:student_id, :exam_id, :class_id, :year_id, :obtained, :possible,
                        :percentage, :average_score, :subject_count, :grade, :position, :total, :remarks)
                ON DUPLICATE KEY UPDATE
                total_marks_obtained = VALUES(total_marks_obtained),
                total_possible_marks = VALUES(total_possible_marks),
                percentage = VALUES(percentage),
                average_score = VALUES(average_score),
                subject_count = VALUES(subject_count),
                grade = VALUES(grade),
                position = VALUES(position),
                total_students = VALUES(total_students),
                remarks = VALUES(remarks)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':student_id' => $studentId,
            ':exam_id' => $examId,
            ':class_id' => $classId,
            ':year_id' => $academicYearId,
            ':obtained' => $totalObtained,
            ':possible' => $totalPossible,
            ':percentage' => $percentage,
            ':average_score' => $averageScore,
            ':subject_count' => $subjectCount,
            ':grade' => $grade,
            ':position' => $position,
            ':total' => $totalStudents,
            ':remarks' => $remarks
        ]);
    }

    private function calculateGrade($averageScore)
    {
        // Grade is based on average score, not letter grades
        $sql = "SELECT grade_label as grade, description as remarks 
                FROM grading_system
                WHERE :score BETWEEN min_score AND max_score
                AND is_active = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':score' => $averageScore]);
        return $stmt->fetch() ?: ['grade' => 'F', 'remarks' => 'Fail'];
    }

    private function calculatePosition($classId, $examId, $currentAverageScore)
    {
        // Position is based on average score, not percentage
        $sql = "SELECT COUNT(*) + 1 as position
                FROM result_summary
                WHERE class_id = :class_id
                AND exam_id = :exam_id
                AND average_score > :average_score";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':exam_id' => $examId,
            ':average_score' => $currentAverageScore
        ]);
        $result = $stmt->fetch();
        return $result['position'] ?? 1;
    }

    private function getTotalStudentsInClass($classId, $examId)
    {
        $sql = "SELECT COUNT(DISTINCT er.student_id) as total
                FROM exam_results er
                INNER JOIN student_enrollments se ON er.student_id = se.student_id
                WHERE er.exam_id = :exam_id
                AND se.class_id = :class_id
                AND se.status = 'active'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':class_id' => $classId, ':exam_id' => $examId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getStudentSummary($studentId, $examId)
    {
        $sql = "SELECT rs.*, e.exam_name, e.exam_type, e.exam_date,
                       c.class_name, ay.year_name
                FROM {$this->table} rs
                JOIN exams e ON rs.exam_id = e.id
                JOIN classes c ON rs.class_id = c.id
                JOIN academic_years ay ON rs.academic_year_id = ay.id
                WHERE rs.student_id = :student_id AND rs.exam_id = :exam_id
                AND rs.is_published = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId, ':exam_id' => $examId]);
        return $stmt->fetch();
    }

    public function publishResults($examId)
    {
        $sql = "UPDATE {$this->table}
                SET is_published = 1, published_at = NOW()
                WHERE exam_id = :exam_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':exam_id' => $examId]);
    }

    public function getClassRanking($classId, $examId, $limit = 10)
    {
        $sql = "SELECT rs.*, st.name, st.roll_num
                FROM {$this->table} rs
                JOIN students st ON rs.student_id = st.id
                WHERE rs.class_id = :class_id AND rs.exam_id = :exam_id
                AND rs.is_published = 1
                ORDER BY rs.position ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':class_id', $classId, \PDO::PARAM_INT);
        $stmt->bindValue(':exam_id', $examId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
