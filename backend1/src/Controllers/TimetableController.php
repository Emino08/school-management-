<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\AcademicYear;
use App\Utils\Validator;

class TimetableController
{
    private $db;
    private $academicYearModel;

    public function __construct()
    {
        $this->db = \App\Config\Database::getInstance()->getConnection();
        $this->academicYearModel = new AcademicYear();
    }

    // Create timetable entry
    public function createEntry(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, [
            'class_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'teacher_id' => 'required|numeric',
            'day_of_week' => 'required',
            'start_time' => 'required',
            'end_time' => 'required'
        ]);

        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Check for conflicts
            $conflicts = $this->checkConflicts(
                $data['class_id'],
                $data['teacher_id'],
                $data['day_of_week'],
                $data['start_time'],
                $data['end_time'],
                $currentYear['id']
            );

            if ($conflicts['hasConflicts']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Schedule conflict detected',
                    'conflicts' => $conflicts
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $stmt = $this->db->prepare("
                INSERT INTO timetable_entries (
                    admin_id, academic_year_id, class_id, subject_id, teacher_id,
                    day_of_week, start_time, end_time, room_number, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user->id,
                $currentYear['id'],
                $data['class_id'],
                $data['subject_id'],
                $data['teacher_id'],
                $data['day_of_week'],
                $data['start_time'],
                $data['end_time'],
                $data['room_number'] ?? null,
                $data['notes'] ?? null
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Timetable entry created successfully',
                'id' => $this->db->lastInsertId()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to create entry: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get timetable for a class
    public function getClassTimetable(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $stmt = $this->db->prepare("
                SELECT te.*, s.subject_name, s.subject_code, t.name as teacher_name,
                       c.class_name
                FROM timetable_entries te
                INNER JOIN subjects s ON te.subject_id = s.id
                INNER JOIN teachers t ON te.teacher_id = t.id
                INNER JOIN classes c ON te.class_id = c.id
                WHERE te.class_id = ?
                AND te.academic_year_id = ?
                AND te.is_active = 1
                ORDER BY FIELD(te.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                         te.start_time
            ");

            $stmt->execute([$args['classId'], $currentYear['id']]);
            $timetable = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Group by day
            $groupedTimetable = [];
            foreach ($timetable as $entry) {
                $day = $entry['day_of_week'];
                if (!isset($groupedTimetable[$day])) {
                    $groupedTimetable[$day] = [];
                }
                $groupedTimetable[$day][] = $entry;
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'timetable' => $timetable,
                'grouped' => $groupedTimetable
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch timetable: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get timetable for a teacher
    public function getTeacherTimetable(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $stmt = $this->db->prepare("
                SELECT te.*, s.subject_name, s.subject_code, c.class_name
                FROM timetable_entries te
                INNER JOIN subjects s ON te.subject_id = s.id
                INNER JOIN classes c ON te.class_id = c.id
                WHERE te.teacher_id = ?
                AND te.academic_year_id = ?
                AND te.is_active = 1
                ORDER BY FIELD(te.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                         te.start_time
            ");

            $stmt->execute([$args['teacherId'], $currentYear['id']]);
            $timetable = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Group by day
            $groupedTimetable = [];
            foreach ($timetable as $entry) {
                $day = $entry['day_of_week'];
                if (!isset($groupedTimetable[$day])) {
                    $groupedTimetable[$day] = [];
                }
                $groupedTimetable[$day][] = $entry;
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'timetable' => $timetable,
                'grouped' => $groupedTimetable
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch timetable: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get upcoming classes for teacher
    public function getUpcomingClasses(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $queryParams = $request->getQueryParams();
        $limit = $queryParams['limit'] ?? 5;

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $currentDay = date('l'); // Monday, Tuesday, etc.
            $currentTime = date('H:i:s');

            $stmt = $this->db->prepare("
                SELECT te.*, s.subject_name, s.subject_code, c.class_name,
                       TIMEDIFF(te.start_time, ?) as time_until
                FROM timetable_entries te
                INNER JOIN subjects s ON te.subject_id = s.id
                INNER JOIN classes c ON te.class_id = c.id
                WHERE te.teacher_id = ?
                AND te.academic_year_id = ?
                AND te.is_active = 1
                AND te.day_of_week = ?
                AND te.start_time >= ?
                ORDER BY te.start_time
                LIMIT ?
            ");

            $stmt->execute([$currentTime, $user->id, $currentYear['id'], $currentDay, $currentTime, (int)$limit]);
            $upcoming = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'upcoming_classes' => $upcoming,
                'current_day' => $currentDay,
                'current_time' => $currentTime
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch upcoming classes: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get timetable for student (based on their class)
    public function getStudentTimetable(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Get student's class
            $stmt = $this->db->prepare("
                SELECT class_id FROM student_enrollments
                WHERE student_id = ? AND academic_year_id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$args['studentId'], $currentYear['id']]);
            $enrollment = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$enrollment) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Student not enrolled in any class']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $stmt = $this->db->prepare("
                SELECT te.*, s.subject_name, s.subject_code, t.name as teacher_name,
                       c.class_name
                FROM timetable_entries te
                INNER JOIN subjects s ON te.subject_id = s.id
                INNER JOIN teachers t ON te.teacher_id = t.id
                INNER JOIN classes c ON te.class_id = c.id
                WHERE te.class_id = ?
                AND te.academic_year_id = ?
                AND te.is_active = 1
                ORDER BY FIELD(te.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                         te.start_time
            ");

            $stmt->execute([$enrollment['class_id'], $currentYear['id']]);
            $timetable = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Group by day
            $groupedTimetable = [];
            foreach ($timetable as $entry) {
                $day = $entry['day_of_week'];
                if (!isset($groupedTimetable[$day])) {
                    $groupedTimetable[$day] = [];
                }
                $groupedTimetable[$day][] = $entry;
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'timetable' => $timetable,
                'grouped' => $groupedTimetable
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch timetable: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Update timetable entry
    public function updateEntry(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);

            // Check for conflicts (excluding current entry)
            $conflicts = $this->checkConflicts(
                $data['class_id'],
                $data['teacher_id'],
                $data['day_of_week'],
                $data['start_time'],
                $data['end_time'],
                $currentYear['id'],
                $args['id']
            );

            if ($conflicts['hasConflicts']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Schedule conflict detected',
                    'conflicts' => $conflicts
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $stmt = $this->db->prepare("
                UPDATE timetable_entries
                SET class_id = ?, subject_id = ?, teacher_id = ?,
                    day_of_week = ?, start_time = ?, end_time = ?,
                    room_number = ?, notes = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['class_id'],
                $data['subject_id'],
                $data['teacher_id'],
                $data['day_of_week'],
                $data['start_time'],
                $data['end_time'],
                $data['room_number'] ?? null,
                $data['notes'] ?? null,
                $args['id']
            ]);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Timetable entry updated successfully']));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to update entry: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Delete timetable entry
    public function deleteEntry(Request $request, Response $response, $args)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM timetable_entries WHERE id = ?");
            $stmt->execute([$args['id']]);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Timetable entry deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to delete entry: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Check for scheduling conflicts
    private function checkConflicts($classId, $teacherId, $dayOfWeek, $startTime, $endTime, $academicYearId, $excludeId = null)
    {
        // Check class conflicts
        $sql = "
            SELECT COUNT(*) FROM timetable_entries
            WHERE class_id = ?
            AND day_of_week = ?
            AND academic_year_id = ?
            AND is_active = 1
            AND ((start_time < ? AND end_time > ?)
                 OR (start_time < ? AND end_time > ?)
                 OR (start_time >= ? AND end_time <= ?))
        ";
        if ($excludeId) $sql .= " AND id != ?";

        $stmt = $this->db->prepare($sql);
        $params = [$classId, $dayOfWeek, $academicYearId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
        if ($excludeId) $params[] = $excludeId;
        $stmt->execute($params);
        $classConflicts = $stmt->fetchColumn() > 0;

        // Check teacher conflicts
        $sql = "
            SELECT COUNT(*) FROM timetable_entries
            WHERE teacher_id = ?
            AND day_of_week = ?
            AND academic_year_id = ?
            AND is_active = 1
            AND ((start_time < ? AND end_time > ?)
                 OR (start_time < ? AND end_time > ?)
                 OR (start_time >= ? AND end_time <= ?))
        ";
        if ($excludeId) $sql .= " AND id != ?";

        $stmt = $this->db->prepare($sql);
        $params = [$teacherId, $dayOfWeek, $academicYearId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
        if ($excludeId) $params[] = $excludeId;
        $stmt->execute($params);
        $teacherConflicts = $stmt->fetchColumn() > 0;

        return [
            'hasConflicts' => $classConflicts || $teacherConflicts,
            'classConflicts' => $classConflicts,
            'teacherConflicts' => $teacherConflicts
        ];
    }

    // Bulk create entries
    public function bulkCreate(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $entries = $data['entries'] ?? [];
            $created = 0;
            $errors = [];

            $this->db->beginTransaction();

            foreach ($entries as $entry) {
                try {
                    // Check conflicts
                    $conflicts = $this->checkConflicts(
                        $entry['class_id'],
                        $entry['teacher_id'],
                        $entry['day_of_week'],
                        $entry['start_time'],
                        $entry['end_time'],
                        $currentYear['id']
                    );

                    if ($conflicts['hasConflicts']) {
                        $errors[] = "Conflict for {$entry['day_of_week']} {$entry['start_time']}";
                        continue;
                    }

                    $stmt = $this->db->prepare("
                        INSERT INTO timetable_entries (
                            admin_id, academic_year_id, class_id, subject_id, teacher_id,
                            day_of_week, start_time, end_time, room_number, notes
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $user->id,
                        $currentYear['id'],
                        $entry['class_id'],
                        $entry['subject_id'],
                        $entry['teacher_id'],
                        $entry['day_of_week'],
                        $entry['start_time'],
                        $entry['end_time'],
                        $entry['room_number'] ?? null,
                        $entry['notes'] ?? null
                    ]);

                    $created++;
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            $this->db->commit();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Created {$created} entries",
                'created' => $created,
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->db->rollBack();
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Bulk create failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
