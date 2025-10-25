<?php

namespace App\Models;

class GradingSystem extends BaseModel
{
    protected $table = 'grading_system';

    public function getGradingScheme($adminId, $academicYearId = null)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE admin_id = :admin_id
                AND is_active = 1";

        $params = [':admin_id' => $adminId];

        if ($academicYearId) {
            $sql .= " AND (academic_year_id = :academic_year_id OR academic_year_id IS NULL)
                      ORDER BY academic_year_id DESC";
            $params[':academic_year_id'] = $academicYearId;
        } else {
            $sql .= " AND academic_year_id IS NULL";
        }

        $sql .= " ORDER BY min_score DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function calculateGrade($score, $adminId, $academicYearId = null)
    {
        $gradingScheme = $this->getGradingScheme($adminId, $academicYearId);

        foreach ($gradingScheme as $grade) {
            if ($score >= $grade['min_score'] && $score <= $grade['max_score']) {
                return [
                    'grade_label' => $grade['grade_label'],
                    'grade_point' => $grade['grade_point'],
                    'description' => $grade['description'],
                    'is_passing' => $grade['is_passing']
                ];
            }
        }

        // Default to F if no match
        return [
            'grade_label' => 'F',
            'grade_point' => 0.00,
            'description' => 'Fail',
            'is_passing' => false
        ];
    }

    public function createGradeRange($data)
    {
        // Validate that score ranges don't overlap
        $overlap = $this->checkScoreOverlap(
            $data['admin_id'],
            $data['min_score'],
            $data['max_score'],
            $data['academic_year_id'] ?? null
        );

        if ($overlap) {
            throw new \Exception('Score range overlaps with existing grade range');
        }

        return $this->create($data);
    }

    public function updateGradeRange($id, $data)
    {
        // Get existing record
        $existing = $this->findById($id);
        if (!$existing) {
            throw new \Exception('Grade range not found');
        }

        // Validate that updated score ranges don't overlap (excluding current record)
        $overlap = $this->checkScoreOverlap(
            $existing['admin_id'],
            $data['min_score'] ?? $existing['min_score'],
            $data['max_score'] ?? $existing['max_score'],
            $existing['academic_year_id'],
            $id
        );

        if ($overlap) {
            throw new \Exception('Score range overlaps with existing grade range');
        }

        return $this->update($id, $data);
    }

    private function checkScoreOverlap($adminId, $minScore, $maxScore, $academicYearId = null, $excludeId = null)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE admin_id = :admin_id
                AND is_active = 1
                AND (
                    (:min_score BETWEEN min_score AND max_score)
                    OR (:max_score BETWEEN min_score AND max_score)
                    OR (min_score BETWEEN :min_score AND :max_score)
                    OR (max_score BETWEEN :min_score AND :max_score)
                )";

        $params = [
            ':admin_id' => $adminId,
            ':min_score' => $minScore,
            ':max_score' => $maxScore
        ];

        if ($academicYearId) {
            $sql .= " AND academic_year_id = :academic_year_id";
            $params[':academic_year_id'] = $academicYearId;
        } else {
            $sql .= " AND academic_year_id IS NULL";
        }

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function deleteGradeRange($id)
    {
        // Soft delete by setting is_active to 0
        return $this->update($id, ['is_active' => 0]);
    }

    public function getGradeStatistics($adminId, $academicYearId)
    {
        $sql = "SELECT
                    gs.grade_label,
                    gs.description,
                    COUNT(er.id) as student_count,
                    ROUND(AVG(er.test_score + er.exam_score), 2) as avg_score
                FROM {$this->table} gs
                LEFT JOIN exam_results er ON er.grade = gs.grade_label
                LEFT JOIN exams e ON er.exam_id = e.id
                WHERE gs.admin_id = :admin_id
                AND gs.is_active = 1
                AND (gs.academic_year_id = :academic_year_id OR gs.academic_year_id IS NULL)
                AND (e.academic_year_id = :academic_year_id OR e.academic_year_id IS NULL)
                GROUP BY gs.grade_label, gs.description
                ORDER BY gs.min_score DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
