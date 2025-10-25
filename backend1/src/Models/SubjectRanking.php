<?php

namespace App\Models;

class SubjectRanking extends BaseModel
{
    protected $table = 'subject_rankings';

    /**
     * Calculate and store subject positions for an exam
     */
    public function calculateSubjectRankings($examId, $subjectId, $classId)
    {
        // Get all students' average scores for this subject in this exam
        $sql = "SELECT er.student_id, er.average_score, er.id as result_id
                FROM exam_results er
                JOIN students s ON er.student_id = s.id
                WHERE er.exam_id = :exam_id
                  AND er.subject_id = :subject_id
                  AND s.sclass_id = :class_id
                  AND er.approval_status = 'approved'
                ORDER BY er.average_score DESC, er.student_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':exam_id' => $examId,
            ':subject_id' => $subjectId,
            ':class_id' => $classId
        ]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalStudents = count($results);
        $position = 1;
        $previousScore = null;
        $sameRankCount = 0;

        // Begin transaction
        $this->db->beginTransaction();

        try {
            foreach ($results as $index => $result) {
                $currentScore = $result['average_score'];

                // Handle tied scores
                if ($previousScore !== null && $currentScore < $previousScore) {
                    $position = $index + 1;
                }

                // Update exam_results table with subject position
                $updateSql = "UPDATE exam_results 
                              SET subject_position = :position,
                                  subject_total_students = :total_students
                              WHERE id = :result_id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([
                    ':position' => $position,
                    ':total_students' => $totalStudents,
                    ':result_id' => $result['result_id']
                ]);

                // Insert or update subject_rankings table
                $rankingSql = "INSERT INTO {$this->table} 
                               (exam_id, subject_id, class_id, student_id, score, position, total_students, is_published)
                               VALUES (:exam_id, :subject_id, :class_id, :student_id, :score, :position, :total_students, FALSE)
                               ON DUPLICATE KEY UPDATE
                               score = VALUES(score),
                               position = VALUES(position),
                               total_students = VALUES(total_students),
                               updated_at = CURRENT_TIMESTAMP";

                $rankingStmt = $this->db->prepare($rankingSql);
                $rankingStmt->execute([
                    ':exam_id' => $examId,
                    ':subject_id' => $subjectId,
                    ':class_id' => $classId,
                    ':student_id' => $result['student_id'],
                    ':score' => $currentScore,
                    ':position' => $position,
                    ':total_students' => $totalStudents
                ]);

                $previousScore = $currentScore;
            }

            $this->db->commit();
            return [
                'success' => true,
                'total_students' => $totalStudents,
                'subject_id' => $subjectId
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get subject rankings for an exam
     */
    public function getSubjectRankings($examId, $subjectId, $classId, $limit = null)
    {
        $sql = "SELECT sr.*, s.name as student_name, s.roll_num, sub.subject_name
                FROM {$this->table} sr
                JOIN students s ON sr.student_id = s.id
                JOIN subjects sub ON sr.subject_id = sub.id
                WHERE sr.exam_id = :exam_id
                  AND sr.subject_id = :subject_id
                  AND sr.class_id = :class_id
                  AND sr.is_published = TRUE
                ORDER BY sr.position ASC";

        if ($limit) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':exam_id', $examId, \PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subjectId, \PDO::PARAM_INT);
        $stmt->bindValue(':class_id', $classId, \PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get student's position in a subject
     */
    public function getStudentSubjectPosition($examId, $subjectId, $studentId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE exam_id = :exam_id
                  AND subject_id = :subject_id
                  AND student_id = :student_id
                  AND is_published = TRUE";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':exam_id' => $examId,
            ':subject_id' => $subjectId,
            ':student_id' => $studentId
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Publish subject rankings for an exam
     */
    public function publishSubjectRankings($examId, $subjectId = null)
    {
        if ($subjectId) {
            $sql = "UPDATE {$this->table}
                    SET is_published = TRUE
                    WHERE exam_id = :exam_id AND subject_id = :subject_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':exam_id' => $examId, ':subject_id' => $subjectId]);
        } else {
            $sql = "UPDATE {$this->table}
                    SET is_published = TRUE
                    WHERE exam_id = :exam_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':exam_id' => $examId]);
        }
        return true;
    }
}
