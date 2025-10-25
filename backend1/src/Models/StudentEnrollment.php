<?php

namespace App\Models;

class StudentEnrollment extends BaseModel
{
    protected $table = 'student_enrollments';

    public function enrollStudent($data)
    {
        return $this->create($data);
    }

    public function getEnrollment($studentId, $academicYearId)
    {
        return $this->findOne([
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId
        ]);
    }

    public function updateEnrollmentStatus($enrollmentId, $status, $classAverage = null, $promotedToClassId = null)
    {
        $data = ['status' => $status];

        if ($classAverage !== null) {
            $data['class_average'] = $classAverage;
        }

        if ($promotedToClassId !== null) {
            $data['promoted_to_class_id'] = $promotedToClassId;
        }

        return $this->update($enrollmentId, $data);
    }

    public function calculateClassAverage($studentId, $academicYearId)
    {
        $sql = "SELECT AVG(g.percentage) as average
                FROM grades g
                WHERE g.student_id = :student_id
                  AND g.academic_year_id = :academic_year_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':academic_year_id' => $academicYearId
        ]);

        $result = $stmt->fetch();
        return $result['average'] ?? 0;
    }

    public function calculateAttendancePercentage($studentId, $academicYearId)
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                FROM attendance
                WHERE student_id = :student_id
                  AND academic_year_id = :academic_year_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':academic_year_id' => $academicYearId
        ]);

        $result = $stmt->fetch();

        if ($result['total'] > 0) {
            return ($result['present'] / $result['total']) * 100;
        }

        return 0;
    }

    public function promoteStudents($academicYearId, $passingPercentage = 40)
    {
        // Get all active enrollments for the academic year
        $sql = "SELECT se.*, c.grade_level
                FROM {$this->table} se
                INNER JOIN classes c ON se.class_id = c.id
                WHERE se.academic_year_id = :academic_year_id
                  AND se.status = 'active'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':academic_year_id' => $academicYearId]);
        $enrollments = $stmt->fetchAll();

        $promoted = 0;
        $failed = 0;

        foreach ($enrollments as $enrollment) {
            // Calculate class average
            $average = $this->calculateClassAverage($enrollment['student_id'], $academicYearId);

            // Calculate attendance
            $attendance = $this->calculateAttendancePercentage($enrollment['student_id'], $academicYearId);

            // Update enrollment with calculated values
            $this->update($enrollment['id'], [
                'class_average' => $average,
                'attendance_percentage' => $attendance
            ]);

            // Determine promotion
            if ($average >= $passingPercentage) {
                // Find next class
                $nextClass = $this->getNextClass($enrollment['class_id']);

                if ($nextClass) {
                    $this->updateEnrollmentStatus(
                        $enrollment['id'],
                        'promoted',
                        $average,
                        $nextClass['id']
                    );
                    $promoted++;
                } else {
                    // Graduated (no next class)
                    $this->updateEnrollmentStatus(
                        $enrollment['id'],
                        'graduated',
                        $average
                    );
                    $promoted++;
                }
            } else {
                $this->updateEnrollmentStatus(
                    $enrollment['id'],
                    'failed',
                    $average
                );
                $failed++;
            }
        }

        return [
            'promoted' => $promoted,
            'failed' => $failed,
            'total' => count($enrollments)
        ];
    }

    private function getNextClass($currentClassId)
    {
        $sql = "SELECT c2.*
                FROM classes c1
                INNER JOIN classes c2 ON c1.admin_id = c2.admin_id
                WHERE c1.id = :current_class_id
                  AND c2.grade_level = c1.grade_level + 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':current_class_id' => $currentClassId]);
        return $stmt->fetch();
    }
}
