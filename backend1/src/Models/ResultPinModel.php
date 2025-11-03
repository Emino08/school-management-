<?php

namespace App\Models;

class ResultPinModel extends BaseModel
{
    protected $table = 'result_pins';

    public function generatePin($length = 12)
    {
        $characters = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $pin = '';
        for ($i = 0; $i < $length; $i++) {
            $pin .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $pin;
    }

    public function createPinForStudent($adminId, $studentId, $maxChecks = 5, $expiryDays = null)
    {
        $pin = $this->generatePin();
        $expiresAt = $expiryDays ? date('Y-m-d H:i:s', strtotime("+$expiryDays days")) : null;

        $sql = "INSERT INTO {$this->table}
                (admin_id, student_id, pin_code, max_checks, used_checks, is_active, expires_at)
                VALUES (:admin_id, :student_id, :pin, :max_checks, 0, 1, :expires_at)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':student_id' => $studentId,
            ':pin' => $pin,
            ':max_checks' => $maxChecks,
            ':expires_at' => $expiresAt
        ]);

        return [
            'pin' => $pin,
            'student_id' => $studentId,
            'max_checks' => $maxChecks,
            'expires_at' => $expiresAt
        ];
    }

    public function bulkCreatePins($adminId, $studentIds, $maxChecks = 5, $expiryDays = null)
    {
        $pins = [];
        foreach ($studentIds as $studentId) {
            $pinData = $this->createPinForStudent($adminId, $studentId, $maxChecks, $expiryDays);
            $pins[] = $pinData;
        }
        return $pins;
    }

    public function bulkCreatePinsForClass($adminId, $classId, $maxChecks = 5, $expiryDays = null)
    {
        // Get all students in class through enrollments
        $stmt = $this->db->prepare("
            SELECT DISTINCT s.id
            FROM students s
            INNER JOIN student_enrollments se ON s.id = se.student_id
            WHERE se.class_id = ? AND se.status = 'active'
        ");
        $stmt->execute([$classId]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $studentIds = array_column($students, 'id');
        return $this->bulkCreatePins($adminId, $studentIds, $maxChecks, $expiryDays);
    }

    public function bulkCreatePinsForAllStudents($adminId, $maxChecks = 5, $expiryDays = null)
    {
        // Get all active students
        $stmt = $this->db->prepare("
            SELECT id FROM students WHERE admin_id = :admin_id
        ");
        $stmt->execute([':admin_id' => $adminId]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $studentIds = array_column($students, 'id');
        return $this->bulkCreatePins($adminId, $studentIds, $maxChecks, $expiryDays);
    }

    public function getPinsForExport($adminId, $classId = null, $academicYearId = null)
    {
        $sql = "SELECT rp.pin_code,
                       st.name as student_name,
                       st.admission_no,
                       st.id_number,
                       c.class_name,
                       rp.max_checks,
                       rp.used_checks,
                       (rp.max_checks - rp.used_checks) as remaining_checks,
                       rp.expires_at,
                       rp.created_at,
                       CASE
                           WHEN rp.is_active = 0 THEN 'Inactive'
                           WHEN rp.used_checks >= rp.max_checks THEN 'Expired'
                           WHEN rp.expires_at IS NOT NULL AND rp.expires_at < NOW() THEN 'Expired'
                           ELSE 'Active'
                       END as status
                FROM {$this->table} rp
                JOIN students st ON rp.student_id = st.id
                LEFT JOIN student_enrollments se ON st.id = se.student_id
                LEFT JOIN classes c ON se.class_id = c.id
                WHERE rp.admin_id = :admin_id";

        $params = [':admin_id' => $adminId];

        if ($classId) {
            $sql .= " AND se.class_id = :class_id";
            $params[':class_id'] = $classId;
        }

        if ($academicYearId) {
            $sql .= " AND se.academic_year_id = :academic_year_id";
            $params[':academic_year_id'] = $academicYearId;
        }

        $sql .= " ORDER BY c.class_name, st.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function validateAndUsePin($pin, $studentId)
    {
        $sql = "SELECT rp.*, st.name as student_name, st.id_number as id_number, st.admission_no
                FROM {$this->table} rp
                JOIN students st ON rp.student_id = st.id
                WHERE rp.pin_code = :pin
                AND rp.student_id = :student_id
                AND rp.is_active = 1
                AND rp.used_checks < rp.max_checks
                AND (rp.expires_at IS NULL OR rp.expires_at > NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pin' => $pin, ':student_id' => $studentId]);
        $pinData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($pinData) {
            // Increment usage count
            $newUsedChecks = $pinData['used_checks'] + 1;
            $updateSql = "UPDATE {$this->table}
                         SET used_checks = :used_checks,
                             last_used_at = NOW(),
                             is_active = CASE
                                WHEN :used_checks >= max_checks THEN 0
                                ELSE 1
                             END
                         WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([
                ':used_checks' => $newUsedChecks,
                ':id' => $pinData['id']
            ]);

            $pinData['remaining_checks'] = $pinData['max_checks'] - $newUsedChecks;
        }

        return $pinData;
    }

    public function checkPin($pin, $studentId)
    {
        $sql = "SELECT rp.*,
                       (rp.max_checks - rp.used_checks) as remaining_checks,
                       CASE
                           WHEN rp.is_active = 0 THEN 'inactive'
                           WHEN rp.used_checks >= rp.max_checks THEN 'expired'
                           WHEN rp.expires_at IS NOT NULL AND rp.expires_at < NOW() THEN 'expired'
                           ELSE 'active'
                       END as status
                FROM {$this->table} rp
                WHERE rp.pin_code = :pin
                AND rp.student_id = :student_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pin' => $pin, ':student_id' => $studentId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getPinsByAdmin($adminId, $limit = 100)
    {
        $sql = "SELECT rp.*,
                       st.name as student_name,
                       st.admission_no,
                       st.id_number as id_number,
                       (rp.max_checks - rp.used_checks) as remaining_checks,
                       CASE
                           WHEN rp.is_active = 0 THEN 'inactive'
                           WHEN rp.used_checks >= rp.max_checks THEN 'expired'
                           WHEN rp.expires_at IS NOT NULL AND rp.expires_at < NOW() THEN 'expired'
                           ELSE 'active'
                       END as status
                FROM {$this->table} rp
                LEFT JOIN students st ON rp.student_id = st.id
                WHERE rp.admin_id = :admin_id
                ORDER BY rp.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':admin_id', $adminId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function deactivatePin($pinId)
    {
        return $this->update($pinId, ['is_active' => 0]);
    }
}

