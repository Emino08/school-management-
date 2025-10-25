<?php

namespace App\Models;

class ClassRanking extends BaseModel
{
    protected $table = 'class_rankings';

    /**
     * Calculate and store overall class positions for an exam
     * Uses average score (not letter grades)
     */
    public function calculateClassRankings($examId, $classId)
    {
        // Get all students' average scores across all subjects for this exam
        // Average is calculated as: total score / number of subjects
        $sql = "SELECT 
                    er.student_id,
                    AVG(er.average_score) as overall_average,
                    SUM(er.total_score) as total_obtained,
                    COUNT(DISTINCT er.subject_id) as subject_count
                FROM exam_results er
                JOIN students s ON er.student_id = s.id
                WHERE er.exam_id = :exam_id
                  AND s.sclass_id = :class_id
                  AND er.approval_status = 'approved'
                GROUP BY er.student_id
                ORDER BY overall_average DESC, total_obtained DESC, er.student_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':exam_id' => $examId,
            ':class_id' => $classId
        ]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalStudents = count($results);
        $position = 1;
        $previousAverage = null;

        // Begin transaction
        $this->db->beginTransaction();

        try {
            foreach ($results as $index => $result) {
                $currentAverage = $result['overall_average'];

                // Handle tied scores
                if ($previousAverage !== null && $currentAverage < $previousAverage) {
                    $position = $index + 1;
                }

                // Update result_summary table with class position
                $updateSql = "UPDATE result_summary 
                              SET class_position = :position,
                                  class_total_students = :total_students,
                                  average_score = :average_score,
                                  subject_count = :subject_count
                              WHERE exam_id = :exam_id 
                                AND student_id = :student_id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([
                    ':position' => $position,
                    ':total_students' => $totalStudents,
                    ':average_score' => $currentAverage,
                    ':subject_count' => $result['subject_count'],
                    ':exam_id' => $examId,
                    ':student_id' => $result['student_id']
                ]);

                // Insert or update class_rankings table
                $rankingSql = "INSERT INTO {$this->table} 
                               (exam_id, class_id, student_id, average_score, total_score, subject_count, position, total_students, is_published)
                               VALUES (:exam_id, :class_id, :student_id, :average_score, :total_score, :subject_count, :position, :total_students, FALSE)
                               ON DUPLICATE KEY UPDATE
                               average_score = VALUES(average_score),
                               total_score = VALUES(total_score),
                               subject_count = VALUES(subject_count),
                               position = VALUES(position),
                               total_students = VALUES(total_students),
                               updated_at = CURRENT_TIMESTAMP";

                $rankingStmt = $this->db->prepare($rankingSql);
                $rankingStmt->execute([
                    ':exam_id' => $examId,
                    ':class_id' => $classId,
                    ':student_id' => $result['student_id'],
                    ':average_score' => $currentAverage,
                    ':total_score' => $result['total_obtained'],
                    ':subject_count' => $result['subject_count'],
                    ':position' => $position,
                    ':total_students' => $totalStudents
                ]);

                $previousAverage = $currentAverage;
            }

            $this->db->commit();
            return [
                'success' => true,
                'total_students' => $totalStudents,
                'class_id' => $classId
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get class rankings for an exam
     */
    public function getClassRankings($examId, $classId, $limit = null)
    {
        $sql = "SELECT cr.*, s.name as student_name, s.roll_num, s.admission_no
                FROM {$this->table} cr
                JOIN students s ON cr.student_id = s.id
                WHERE cr.exam_id = :exam_id
                  AND cr.class_id = :class_id
                  AND cr.is_published = TRUE
                ORDER BY cr.position ASC";

        if ($limit) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':exam_id', $examId, \PDO::PARAM_INT);
        $stmt->bindValue(':class_id', $classId, \PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get student's overall class position
     */
    public function getStudentClassPosition($examId, $studentId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE exam_id = :exam_id
                  AND student_id = :student_id
                  AND is_published = TRUE";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':exam_id' => $examId,
            ':student_id' => $studentId
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Publish class rankings for an exam
     */
    public function publishClassRankings($examId)
    {
        $sql = "UPDATE {$this->table}
                SET is_published = TRUE
                WHERE exam_id = :exam_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':exam_id' => $examId]);
        return true;
    }

    /**
     * Calculate rankings for all classes in an exam
     */
    public function calculateAllClassRankings($examId)
    {
        // Get all classes that have results for this exam
        $sql = "SELECT DISTINCT s.sclass_id as class_id
                FROM exam_results er
                JOIN students s ON er.student_id = s.id
                WHERE er.exam_id = :exam_id
                  AND er.approval_status = 'approved'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':exam_id' => $examId]);
        $classes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        foreach ($classes as $class) {
            $results[] = $this->calculateClassRankings($examId, $class['class_id']);
        }

        return $results;
    }
}
