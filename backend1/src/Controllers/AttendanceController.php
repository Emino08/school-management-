<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Models\ParentNotification;
use App\Utils\Validator;
use App\Traits\LogsActivity;
use App\Traits\ResolvesAdminId;

class AttendanceController
{
    use LogsActivity;
    use ResolvesAdminId;

    private $attendanceModel;
    private $academicYearModel;
    private $notificationModel;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
        $this->academicYearModel = new AcademicYear();
        $this->notificationModel = new ParentNotification();
    }

    public function markAttendance(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        $adminId = $this->getAdminId($request, $user);

        $errors = Validator::validate($data, ['student_id' => 'required|numeric', 'subject_id' => 'required|numeric', 'date' => 'required', 'status' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $data['academic_year_id'] = $currentYear['id'];

            // Enforce timetable integration: only allow attendance during a scheduled class slot for the student's class and subject
            $db = \App\Config\Database::getInstance()->getConnection();
            // Get student's current class for this academic year
            $en = $db->prepare("SELECT class_id FROM student_enrollments WHERE student_id = :sid AND academic_year_id = :yid AND status = 'active' LIMIT 1");
            $en->execute([':sid' => (int)$data['student_id'], ':yid' => (int)$currentYear['id']]);
            $enrollment = $en->fetch(\PDO::FETCH_ASSOC);
            if (!$enrollment) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Student not enrolled for current academic year']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $classId = (int)$enrollment['class_id'];
            $dayOfWeek = date('l', strtotime($data['date']));
            $time = isset($data['time']) && $data['time'] !== '' ? $data['time'] : date('H:i:s');
            // Check timetable has an active slot covering this time
            $tt = $db->prepare("SELECT COUNT(*) FROM timetable_entries te
                                 WHERE te.admin_id = :admin
                                   AND te.academic_year_id = :yid
                                   AND te.class_id = :cid
                                   AND te.subject_id = :sub
                                   AND te.day_of_week = :dow
                                   AND te.is_active = 1
                                   AND :tm BETWEEN te.start_time AND te.end_time");
            $tt->execute([
                ':admin' => $user->id,
                ':yid' => (int)$currentYear['id'],
                ':cid' => $classId,
                ':sub' => (int)$data['subject_id'],
                ':dow' => $dayOfWeek,
                ':tm' => $time,
            ]);
            if ((int)$tt->fetchColumn() === 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No scheduled class for this subject at the specified date/time'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $attendanceId = $this->attendanceModel->markAttendance(Validator::sanitize($data));

            // Notify parents if student is absent
            if ($data['status'] === 'absent') {
                $this->notificationModel->notifyAttendanceMiss(
                    $data['student_id'],
                    $adminId,
                    $data['date']
                );
            }

            // Handle streaks and principal escalation for consecutive absences
            $this->handleConsecutiveAbsence(
                (int)$data['student_id'],
                (int)$data['subject_id'],
                $data['date'],
                $adminId,
                (int)$currentYear['id']
            );

            // Log attendance marking
            $this->logActivity(
                $request,
                'mark',
                "Marked attendance for class",
                'attendance',
                null
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'attendance_id' => $attendanceId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to mark attendance: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getStudentAttendance(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $subjectId = isset($params['subject_id']) && $params['subject_id'] !== '' ? (int)$params['subject_id'] : null;
            $start = $params['start'] ?? null;
            $end = $params['end'] ?? null;

            if ($start || $end || $subjectId) {
                $attendance = $this->attendanceModel->getStudentAttendanceByFilters(
                    $args['studentId'],
                    $currentYear ? $currentYear['id'] : null,
                    $subjectId,
                    $start,
                    $end
                );
            } else {
                $attendance = $this->attendanceModel->getStudentAttendance(
                    $args['studentId'],
                    $currentYear ? $currentYear['id'] : null
                );
            }

            $response->getBody()->write(json_encode(['success' => true, 'attendance' => $attendance]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch attendance: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAttendanceStats(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $stats = $this->attendanceModel->getAttendanceStats($args['studentId'], $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'stats' => $stats]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch stats: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Class-level attendance for a given date and optional subject
    public function getClassAttendance(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $date = $params['date'] ?? date('Y-m-d');
        $subjectId = isset($params['subject_id']) && $params['subject_id'] !== '' ? (int)$params['subject_id'] : null;

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $yearId = $currentYear ? $currentYear['id'] : null;

            $sql = "SELECT st.id as student_id, st.name, st.id_number, st.email, st.phone,
                           a.status, a.remarks, a.date, a.subject_id
                    FROM students st
                    INNER JOIN student_enrollments se ON se.student_id = st.id AND se.academic_year_id = :year_id
                    LEFT JOIN attendance a
                       ON a.student_id = st.id AND a.academic_year_id = :year_id AND a.date = :date";
            $bindings = [':year_id' => $yearId, ':date' => $date];

            $sql .= " WHERE se.class_id = :class_id AND st.admin_id = :admin_id";
            $bindings[':class_id'] = (int)$args['classId'];
            $bindings[':admin_id'] = $user->id;

            if ($subjectId) {
                $sql .= " AND (a.subject_id = :subject_id OR a.subject_id IS NULL)";
                $bindings[':subject_id'] = $subjectId;
            }

            $sql .= " ORDER BY st.name";
            $stmt = $db->prepare($sql);
            $stmt->execute($bindings);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode(['success' => true, 'attendance' => $rows]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch class attendance: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Track absence streaks and escalate after three consecutive misses
     */
    private function handleConsecutiveAbsence(int $studentId, int $subjectId, string $date, int $adminId, ?int $academicYearId): void
    {
        try {
            if (!$academicYearId) {
                return;
            }

            $db = \App\Config\Database::getInstance()->getConnection();

            $streak = $this->calculateClassAbsenceStreak($db, $studentId, $subjectId);
            $term = 1; // Fallback term for class attendance tracking

            $existing = $this->getStrikeRecord($db, $studentId, $academicYearId, $term);
            $alreadyNotified = $existing && (bool)$existing['notification_sent'];
            $shouldNotify = $streak >= 3 && !$alreadyNotified;
            $markNotified = $streak >= 3 ? ($alreadyNotified || $shouldNotify) : false;

            $this->upsertStrike($db, $studentId, $academicYearId, $term, $streak, $date, $markNotified);

            if ($shouldNotify) {
                $student = $this->getStudentProfile($db, $studentId);
                $studentName = $student['name'] ?? ('ID ' . $studentId);
                $idNumber = $student['id_number'] ?? '';
                $studentLabel = trim($studentName . ($idNumber ? " ({$idNumber})" : ''));
                $message = "Student {$studentLabel} has missed three consecutive classes (latest on {$date}).";

                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, user_role, message, notification_type, requires_action)
                    SELECT id, 'Admin', :message, 'attendance', TRUE
                    FROM admins
                    WHERE role IN ('principal', 'admin')
                ");
                $stmt->execute(['message' => $message]);

                // Alert parents as well
                $this->notificationModel->notifyAttendanceStreak($studentId, $adminId, $date, $streak);
            }
        } catch (\Exception $e) {
            error_log('Failed to handle consecutive absences: ' . $e->getMessage());
        }
    }

    private function calculateClassAbsenceStreak($db, int $studentId, int $subjectId): int
    {
        $stmt = $db->prepare("
            SELECT status 
            FROM attendance 
            WHERE student_id = :student_id AND subject_id = :subject_id
            ORDER BY date DESC
            LIMIT 3
        ");
        $stmt->execute([
            ':student_id' => $studentId,
            ':subject_id' => $subjectId
        ]);

        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $streak = 0;
        foreach ($records as $record) {
            if (strtolower($record['status']) === 'absent') {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    private function getStrikeRecord($db, int $studentId, ?int $academicYearId, int $term): ?array
    {
        $stmt = $db->prepare("
            SELECT * FROM attendance_strikes 
            WHERE student_id = :student_id AND academic_year_id = :year_id AND term = :term
            LIMIT 1
        ");
        $stmt->execute([
            ':student_id' => $studentId,
            ':year_id' => $academicYearId,
            ':term' => $term
        ]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $record ?: null;
    }

    private function upsertStrike($db, int $studentId, ?int $academicYearId, int $term, int $streak, string $date, bool $notificationSent): void
    {
        $stmt = $db->prepare("
            INSERT INTO attendance_strikes 
                (student_id, academic_year_id, term, absence_count, last_absence_date, notification_sent, notification_sent_at)
            VALUES 
                (:student_id, :year_id, :term, :absence_count, :last_absence_date, :notification_sent, 
                 CASE WHEN :notification_sent = TRUE THEN NOW() ELSE NULL END)
            ON DUPLICATE KEY UPDATE
                absence_count = VALUES(absence_count),
                last_absence_date = VALUES(last_absence_date),
                notification_sent = VALUES(notification_sent),
                notification_sent_at = CASE 
                    WHEN VALUES(notification_sent) = TRUE THEN COALESCE(attendance_strikes.notification_sent_at, NOW()) 
                    ELSE NULL 
                END
        ");

        $stmt->execute([
            ':student_id' => $studentId,
            ':year_id' => $academicYearId,
            ':term' => $term,
            ':absence_count' => $streak,
            ':last_absence_date' => $date,
            ':notification_sent' => $notificationSent
        ]);
    }

    private function getStudentProfile($db, int $studentId): array
    {
        $stmt = $db->prepare("SELECT name, id_number FROM students WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $studentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $student ?: [];
    }
}


