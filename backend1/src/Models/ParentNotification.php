<?php

namespace App\Models;

class ParentNotification extends BaseModel
{
    protected $table = 'parent_notifications';

    public function createNotification($data)
    {
        return $this->create($data);
    }

    public function notifyAttendanceMiss($studentId, $adminId, $date)
    {
        // Get all parents of this student
        $sql = "SELECT p.id, p.name, p.email, p.notification_preference
                FROM parents p
                JOIN parent_student_relations psr ON p.id = psr.parent_id
                WHERE psr.student_id = :student_id AND psr.is_verified = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $parents = $stmt->fetchAll();

        // Get student info
        $studentSql = "SELECT name, id_number FROM students WHERE id = :id";
        $studentStmt = $this->db->prepare($studentSql);
        $studentStmt->execute([':id' => $studentId]);
        $student = $studentStmt->fetch();

        $notifications = [];
        foreach ($parents as $parent) {
            $notification = $this->create([
                'parent_id' => $parent['id'],
                'student_id' => $studentId,
                'admin_id' => $adminId,
                'type' => 'attendance_miss',
                'title' => 'Attendance Alert',
                'message' => "Your child {$student['name']} ({$student['id_number']}) was absent on {$date}. Please contact the school if this was not expected.",
                'metadata' => json_encode(['date' => $date])
            ]);
            $notifications[] = $notification;
        }

        return $notifications;
    }

    public function notifySuspension($studentId, $adminId, $reason, $startDate, $endDate)
    {
        $sql = "SELECT p.id, p.name, p.email
                FROM parents p
                JOIN parent_student_relations psr ON p.id = psr.parent_id
                WHERE psr.student_id = :student_id AND psr.is_verified = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $parents = $stmt->fetchAll();

        $studentSql = "SELECT name, id_number FROM students WHERE id = :id";
        $studentStmt = $this->db->prepare($studentSql);
        $studentStmt->execute([':id' => $studentId]);
        $student = $studentStmt->fetch();

        $notifications = [];
        foreach ($parents as $parent) {
            $message = "Your child {$student['name']} ({$student['id_number']}) has been suspended from {$startDate} to {$endDate}. Reason: {$reason}. Please contact the principal's office for more information.";
            
            $notification = $this->create([
                'parent_id' => $parent['id'],
                'student_id' => $studentId,
                'admin_id' => $adminId,
                'type' => 'suspension',
                'title' => 'Student Suspension Notice',
                'message' => $message,
                'metadata' => json_encode([
                    'reason' => $reason,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ])
            ]);
            $notifications[] = $notification;
        }

        return $notifications;
    }

    public function notifyMedicalIssue($studentId, $adminId, $diagnosis, $severity)
    {
        $sql = "SELECT p.id, p.name, p.email
                FROM parents p
                JOIN parent_student_relations psr ON p.id = psr.parent_id
                WHERE psr.student_id = :student_id AND psr.is_verified = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $parents = $stmt->fetchAll();

        $studentSql = "SELECT name, id_number FROM students WHERE id = :id";
        $studentStmt = $this->db->prepare($studentSql);
        $studentStmt->execute([':id' => $studentId]);
        $student = $studentStmt->fetch();

        $notifications = [];
        foreach ($parents as $parent) {
            $message = "Your child {$student['name']} ({$student['id_number']}) has been taken to the school medical facility. Diagnosis: {$diagnosis}. Severity: {$severity}. Please contact the school for more details.";
            
            $notification = $this->create([
                'parent_id' => $parent['id'],
                'student_id' => $studentId,
                'admin_id' => $adminId,
                'type' => 'medical',
                'title' => 'Medical Alert',
                'message' => $message,
                'metadata' => json_encode([
                    'diagnosis' => $diagnosis,
                    'severity' => $severity
                ])
            ]);
            $notifications[] = $notification;
        }

        return $notifications;
    }

    public function notifyMedicalRecovery($studentId, $adminId, $diagnosis)
    {
        $sql = "SELECT p.id, p.name, p.email
                FROM parents p
                JOIN parent_student_relations psr ON p.id = psr.parent_id
                WHERE psr.student_id = :student_id AND psr.is_verified = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $parents = $stmt->fetchAll();

        $studentSql = "SELECT name, id_number FROM students WHERE id = :id";
        $studentStmt = $this->db->prepare($studentSql);
        $studentStmt->execute([':id' => $studentId]);
        $student = $studentStmt->fetch();

        $notifications = [];
        foreach ($parents as $parent) {
            $message = "Good news! Your child {$student['name']} ({$student['id_number']}) has recovered from {$diagnosis} and the treatment has been completed. They are cleared to resume normal activities.";
            
            $notification = $this->create([
                'parent_id' => $parent['id'],
                'student_id' => $studentId,
                'admin_id' => $adminId,
                'type' => 'medical',
                'title' => 'Medical Recovery Notice',
                'message' => $message,
                'metadata' => json_encode([
                    'diagnosis' => $diagnosis,
                    'status' => 'recovered'
                ])
            ]);
            $notifications[] = $notification;
        }

        return $notifications;
    }
}
