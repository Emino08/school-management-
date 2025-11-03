<?php

namespace App\Models;

class ClassModel extends BaseModel
{
    protected $table = 'classes';

    public function getClassesWithStudentCount($adminId, $academicYearId)
    {
        $sql = "SELECT c.*,
                       COUNT(DISTINCT se.student_id) as student_count,
                       COUNT(DISTINCT s.id) as subject_count,
                       (
                           SELECT t.name FROM teachers t
                           WHERE t.is_class_master = 1 AND t.class_master_of = c.id
                           ORDER BY t.id ASC LIMIT 1
                       ) AS class_master_name,
                       (
                           SELECT t.id FROM teachers t
                           WHERE t.is_class_master = 1 AND t.class_master_of = c.id
                           ORDER BY t.id ASC LIMIT 1
                       ) AS class_master_id
                FROM {$this->table} c
                LEFT JOIN student_enrollments se ON c.id = se.class_id
                    AND se.academic_year_id = :academic_year_id
                    AND se.status = 'active'
                LEFT JOIN subjects s ON c.id = s.class_id
                WHERE c.admin_id = :admin_id
                GROUP BY c.id
                ORDER BY c.grade_level, c.class_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':academic_year_id' => $academicYearId
        ]);
        return $stmt->fetchAll();
    }

    public function deleteClass($classId)
    {
        // Check if class has any students
        $sql = "SELECT COUNT(*) as count FROM student_enrollments WHERE class_id = :class_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':class_id' => $classId]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            throw new \Exception('Cannot delete class with enrolled students');
        }

        return $this->delete($classId);
    }
}
