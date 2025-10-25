<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Utils\Validator;

class AttendanceController
{
    private $attendanceModel;
    private $academicYearModel;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
        $this->academicYearModel = new AcademicYear();
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

            $attendanceId = $this->attendanceModel->markAttendance(Validator::sanitize($data));

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
