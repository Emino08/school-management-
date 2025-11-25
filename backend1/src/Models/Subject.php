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
        // Guard: prevent deletion if dependent records exist in known tables
        foreach (['exam_results', 'grades'] as $table) {
            if ($this->tableExists($table)) {
                $sql = "SELECT COUNT(*) as count FROM {$table} WHERE subject_id = :subject_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':subject_id' => $subjectId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!empty($result['count'])) {
                    throw new \Exception('Cannot delete subject with existing results');
                }
                break;
            }
        }

        // Remove dependent teacher assignments if table exists
        if ($this->tableExists('teacher_assignments')) {
            $stmt = $this->db->prepare("DELETE FROM teacher_assignments WHERE subject_id = :subject_id");
            $stmt->execute([':subject_id' => $subjectId]);
        }

        return $this->delete($subjectId);
    }

    public function getSubjectsWithDetails($adminId, $academicYearId = null)
    {
        $joinAssignments = "";
        $params = [
            ':admin_id' => $adminId,
        ];

        if ($academicYearId) {
            $joinAssignments = "AND ta.academic_year_id = :academic_year_id";
            $params[':academic_year_id'] = $academicYearId;
        }

        $sql = "SELECT s.*,
                       c.class_name,
                       t.name AS teacher_name,
                       t.email AS teacher_email,
                       t.phone AS teacher_phone,
                       (
                           SELECT COUNT(DISTINCT se.student_id)
                           FROM student_enrollments se
                           WHERE se.class_id = s.class_id
                       ) AS student_count
                FROM {$this->table} s
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN teacher_assignments ta ON s.id = ta.subject_id {$joinAssignments}
                LEFT JOIN teachers t ON ta.teacher_id = t.id
                WHERE s.admin_id = :admin_id
                ORDER BY s.subject_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE :table");
            $stmt->execute([':table' => $table]);
            return (bool)$stmt->fetch();
        } catch (\Exception $e) {
            return false;
        }
    }
}
