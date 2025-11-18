<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Models\ParentNotification;
use App\Utils\Validator;

class AttendanceController
{
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

        $errors = Validator::validate($data, ['student_id' => 'required|numeric', 'subject_id' => 'required|numeric', 'date' => 'required', 'status' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
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
                    $user->id,
                    $data['date']
                );
            }

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Attendance marked successfully', 'attendance_id' => $attendanceId]));
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
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
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
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
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
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
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
}
