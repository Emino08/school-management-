<?php

namespace App\Models;

class Attendance extends BaseModel
{
    protected $table = 'attendance';

    public function markAttendance($data)
    {
        $existing = $this->findOne([
            'student_id' => $data['student_id'],
            'subject_id' => $data['subject_id'],
            'date' => $data['date']
        ]);

        if ($existing) {
            return $this->update($existing['id'], ['status' => $data['status'], 'remarks' => $data['remarks'] ?? null]);
        } else {
            return $this->create($data);
        }
    }

    public function getStudentAttendance($studentId, $academicYearId, $subjectId = null)
    {
        $conditions = [
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId
        ];

        if ($subjectId) {
            $conditions['subject_id'] = $subjectId;
        }

        return $this->findAll($conditions);
    }

    public function getAttendanceStats($studentId, $academicYearId)
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
                FROM {$this->table}
                WHERE student_id = :student_id
                  AND academic_year_id = :academic_year_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':academic_year_id' => $academicYearId
        ]);

        $result = $stmt->fetch();
        $result['percentage'] = $result['total'] > 0 ? ($result['present'] / $result['total']) * 100 : 0;

        return $result;
    }

    public function getStudentAttendanceByFilters($studentId, $academicYearId, $subjectId = null, $startDate = null, $endDate = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
        $params = [
            ':student_id' => $studentId,
            ':academic_year_id' => $academicYearId,
        ];

        if ($subjectId) {
            $sql .= " AND subject_id = :subject_id";
            $params[':subject_id'] = $subjectId;
        }
        if ($startDate) {
            $sql .= " AND date >= :start";
            $params[':start'] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND date <= :end";
            $params[':end'] = $endDate;
        }

        $sql .= " ORDER BY date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
