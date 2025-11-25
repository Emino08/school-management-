<?php

namespace App\Controllers;

use App\Traits\LogsActivity;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

class PromotionController
{
    use LogsActivity;

    /**
     * Process student promotions for an academic year
     * Only runs when all exams for the last term are published
     */
    public function processPromotions(Request $request, Response $response, array $args)
    {
        try {
            $academicYearId = $args['id'] ?? null;
            $db = Database::getInstance()->getConnection();
            // Ensure placement/capacity columns exist before any SELECTs using them
            \App\Utils\Schema::ensureClassCapacityColumns($db);
            // Ensure enrollment status column exists to avoid missing-column failures
            \App\Utils\Schema::ensureEnrollmentStatusColumn($db);
            $hasEnrollmentStatus = $this->hasEnrollmentStatusColumn($db);

            // Get academic year details
            $stmt = $db->prepare("
                SELECT * FROM academic_years
                WHERE id = :id
            ");
            $stmt->execute([':id' => $academicYearId]);
            $academicYear = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$academicYear) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Academic year not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check if we're at the last term
            if ($academicYear['current_term'] != $academicYear['number_of_terms']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Promotions can only be processed at the end of the academic year (last term)'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Get the last term
            $stmt = $db->prepare("
                SELECT id FROM terms
                WHERE academic_year_id = :year_id
                AND term_number = :term_number
            ");
            $stmt->execute([
                ':year_id' => $academicYearId,
                ':term_number' => $academicYear['number_of_terms']
            ]);
            $lastTerm = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$lastTerm) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Last term not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check if all exams for the last term are published
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_exams,
                       SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published_exams
                FROM exams
                WHERE academic_year_id = :year_id
                AND term_id = :term_id
            ");
            $stmt->execute([
                ':year_id' => $academicYearId,
                ':term_id' => $lastTerm['id']
            ]);
            $examStats = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($examStats['total_exams'] == 0 || $examStats['published_exams'] < $examStats['total_exams']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'All exams for the last term must be published before processing promotions',
                    'stats' => $examStats
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Get all classes for this academic year
            $statusClause = $hasEnrollmentStatus ? " AND se.status = 'active'" : '';
            $stmt = $db->prepare("
                SELECT DISTINCT c.id, c.class_name, c.grade_level
                FROM classes c
                INNER JOIN student_enrollments se ON c.id = se.class_id
                WHERE se.academic_year_id = :year_id
                $statusClause
                ORDER BY c.grade_level
            ");
            $stmt->execute([':year_id' => $academicYearId]);
            $classes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $promotionResults = [];

            // Track assigned counts to enforce class capacity during this run
            $capacityTracker = [];

            foreach ($classes as $class) {
                $classPromotions = $this->promoteClassStudents(
                    $db,
                    $academicYearId,
                    $class['id'],
                    $academicYear['promotion_average'],
                    $academicYear['repeat_average'],
                    $academicYear['drop_average'],
                    $capacityTracker,
                    $hasEnrollmentStatus
                );
                $promotionResults[] = [
                    'class_id' => $class['id'],
                    'class_name' => $class['class_name'],
                    'results' => $classPromotions
                ];
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student promotions processed successfully',
                'data' => $promotionResults
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Promotion error: " . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to process promotions: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Promote students in a specific class based on their performance
     */
    private function promoteClassStudents($db, $academicYearId, $classId, $promotionAvg, $repeatAvg, $dropAvg, array &$capacityTracker, bool $hasEnrollmentStatus)
    {
        // Calculate overall average for each student in the class
        $statusClause = $hasEnrollmentStatus ? " AND se.status = 'active'" : '';
        $stmt = $db->prepare("
            SELECT
                s.id as student_id,
                s.name as student_name,
                s.admission_no,
                AVG(er.marks_obtained) as overall_average,
                COUNT(DISTINCT er.subject_id) as subjects_taken
            FROM students s
            INNER JOIN student_enrollments se ON s.id = se.student_id
            INNER JOIN exam_results er ON s.id = er.student_id
            INNER JOIN exams e ON er.exam_id = e.id
            WHERE se.class_id = :class_id
            AND se.academic_year_id = :year_id
            $statusClause
            AND e.academic_year_id = :year_id2
            AND e.is_published = 1
            AND er.approval_status = 'approved'
            GROUP BY s.id, s.name, s.admission_no
            ORDER BY overall_average DESC
        ");
        $stmt->execute([
            ':class_id' => $classId,
            ':year_id' => $academicYearId,
            ':year_id2' => $academicYearId
        ]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $promoted = [];
        $repeat = [];
        $dropped = [];

        $rank = 1;
        foreach ($students as $student) {
            $average = (float) $student['overall_average'];
            $studentData = [
                'student_id' => $student['student_id'],
                'student_name' => $student['student_name'],
                'admission_no' => $student['admission_no'],
                'average' => round($average, 2),
                'rank' => $rank++
            ];

            if ($average >= $promotionAvg) {
                // Determine destination class for next level based on thresholds and capacity (strict)
                $destClassId = $this->selectDestinationClass($db, $classId, $average, $capacityTracker);
                if ($destClassId !== null) {
                    $promoted[] = $studentData;
                    // Update current enrollment row with status, average and target class
                    if ($hasEnrollmentStatus) {
                        $stmtUp = $db->prepare("UPDATE student_enrollments SET status = 'completed', promotion_status = 'promoted', class_average = :avg, promoted_to_class_id = :dest, updated_at = NOW() WHERE student_id = :sid AND academic_year_id = :yid");
                    } else {
                        $stmtUp = $db->prepare("UPDATE student_enrollments SET promotion_status = 'promoted', class_average = :avg, promoted_to_class_id = :dest, updated_at = NOW() WHERE student_id = :sid AND academic_year_id = :yid");
                    }
                    $stmtUp->execute([':avg' => round($average,2), ':dest' => $destClassId, ':sid' => $student['student_id'], ':yid' => $academicYearId]);
                } else {
                    // No capacity available: mark as waitlist
                    if ($hasEnrollmentStatus) {
                        $waitStmt = $db->prepare("UPDATE student_enrollments SET status = 'completed', promotion_status = 'waitlist', class_average = :avg, promoted_to_class_id = NULL, updated_at = NOW() WHERE student_id = :sid AND academic_year_id = :yid");
                    } else {
                        $waitStmt = $db->prepare("UPDATE student_enrollments SET promotion_status = 'waitlist', class_average = :avg, promoted_to_class_id = NULL, updated_at = NOW() WHERE student_id = :sid AND academic_year_id = :yid");
                    }
                    $waitStmt->execute([':avg' => round($average,2), ':sid' => $student['student_id'], ':yid' => $academicYearId]);
                }
            } elseif ($average >= $repeatAvg) {
                $repeat[] = $studentData;
                // Update student enrollment status to repeat
                $this->updateStudentStatus($db, $student['student_id'], $academicYearId, 'repeat', $hasEnrollmentStatus);
            } else {
                $dropped[] = $studentData;
                // Update student enrollment status to dropped
                $this->updateStudentStatus($db, $student['student_id'], $academicYearId, 'dropped', $hasEnrollmentStatus);
            }
        }

        return [
            'promoted' => $promoted,
            'repeat' => $repeat,
            'dropped' => $dropped,
            'total_students' => count($students)
        ];
    }

    // Select destination class at next grade level by threshold and enforce capacity within this run (strict)
    private function selectDestinationClass($db, $currentClassId, $studentAverage, array &$capacityTracker)
    {
        // Fetch candidate destination classes (next grade level), highest thresholds first
        $stmt = $db->prepare("SELECT c2.id, c2.class_name, c2.capacity, COALESCE(c2.placement_min_average,0) AS min_avg
                              FROM classes c1
                              INNER JOIN classes c2 ON c1.admin_id = c2.admin_id
                              WHERE c1.id = :cid AND c2.grade_level = c1.grade_level + 1
                              ORDER BY min_avg DESC, c2.id ASC");
        $stmt->execute([':cid' => $currentClassId]);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!$candidates) return null;

        // Try to place into highest-threshold class the student qualifies for with available capacity
        foreach ($candidates as $row) {
            $min = (float)$row['min_avg'];
            if ($studentAverage >= $min) {
                $cap = isset($row['capacity']) ? (int)$row['capacity'] : null;
                $assigned = $capacityTracker[$row['id']] ?? 0;
                if ($cap === null || $assigned < $cap) {
                    $capacityTracker[$row['id']] = $assigned + 1;
                    return (int)$row['id'];
                }
            }
        }
        // Strict: no destination available
        return null;
    }

    /**
     * Preview placement breakdown without changing data
     * GET /api/promotions/preview?academic_year_id=ID
     */
    public function preview(Request $request, Response $response)
    {
        try {
            $yearId = $request->getQueryParams()['academic_year_id'] ?? null;
            if (!$yearId) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'academic_year_id is required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $db = Database::getInstance()->getConnection();
            // Ensure placement/capacity columns exist to avoid SQL errors (c2.capacity etc.)
            \App\Utils\Schema::ensureClassCapacityColumns($db);
            \App\Utils\Schema::ensureEnrollmentStatusColumn($db);
            $hasEnrollmentStatus = $this->hasEnrollmentStatusColumn($db);
            $statusClause = $hasEnrollmentStatus ? " AND se.status = 'active'" : '';

            // Get classes for this academic year (source classes)
            $stmt = $db->prepare("SELECT DISTINCT c.id, c.class_name, c.grade_level
                                   FROM classes c
                                   INNER JOIN student_enrollments se ON c.id = se.class_id
                                   WHERE se.academic_year_id = :yid{$statusClause}");
            $stmt->execute([':yid' => $yearId]);
            $classes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $result = [];
            foreach ($classes as $class) {
                // Get student averages like in promoteClassStudents
                $stu = $db->prepare("SELECT s.id as student_id, AVG(er.marks_obtained) as avg
                                      FROM students s
                                      INNER JOIN student_enrollments se ON s.id = se.student_id
                                      INNER JOIN exam_results er ON s.id = er.student_id
                                      INNER JOIN exams e ON er.exam_id = e.id
                                      WHERE se.class_id = :cid AND se.academic_year_id = :yid
                                        {$statusClause} AND e.academic_year_id = :yid2
                                        AND e.is_published = 1 AND er.approval_status = 'approved'
                                      GROUP BY s.id");
                $stu->execute([':cid' => $class['id'], ':yid' => $yearId, ':yid2' => $yearId]);
                $students = $stu->fetchAll(\PDO::FETCH_ASSOC);

                // Build destination candidates
                $cand = $db->prepare("SELECT c2.id, c2.class_name, c2.capacity, COALESCE(c2.placement_min_average,0) AS min_avg
                                      FROM classes c1
                                      INNER JOIN classes c2 ON c1.admin_id = c2.admin_id
                                      WHERE c1.id = :cid AND c2.grade_level = c1.grade_level + 1
                                      ORDER BY min_avg DESC, c2.id ASC");
                $cand->execute([':cid' => $class['id']]);
                $dest = $cand->fetchAll(\PDO::FETCH_ASSOC);

                $cap = [];
                foreach ($dest as $d) { $cap[$d['id']] = 0; }
                $breakdown = [];
                foreach ($dest as $d) { $breakdown[$d['id']] = ['class_id' => (int)$d['id'], 'class_name' => $d['class_name'], 'count' => 0, 'min_avg' => (float)$d['min_avg'], 'capacity' => $d['capacity'] !== null ? (int)$d['capacity'] : null]; }
                $overflow = 0;

                foreach ($students as $s) {
                    $placed = false;
                    foreach ($dest as $d) {
                        $min = (float)$d['min_avg'];
                        $capLimit = $d['capacity'] !== null ? (int)$d['capacity'] : null;
                        if ((float)$s['avg'] >= $min) {
                            if ($capLimit === null || $cap[$d['id']] < $capLimit) {
                                $cap[$d['id']] += 1;
                                $breakdown[$d['id']]['count'] += 1;
                                $placed = true;
                                break;
                            }
                        }
                    }
                    if (!$placed) $overflow++;
                }

                $result[] = [
                    'source_class_id' => (int)$class['id'],
                    'source_class_name' => $class['class_name'],
                    'destinations' => array_values($breakdown),
                    'overflow' => $overflow,
                ];
            }

            $response->getBody()->write(json_encode(['success' => true, 'data' => $result]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Preview failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * List waitlisted students for a source academic year
     * GET /api/promotions/waitlist?academic_year_id=ID
     */
    public function waitlist(Request $request, Response $response)
    {
        try {
            $yearId = $request->getQueryParams()['academic_year_id'] ?? null;
            if (!$yearId) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'academic_year_id is required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT se.student_id, se.class_id, se.class_average, s.name AS student_name, s.admission_no, c.class_name
                                  FROM student_enrollments se
                                  INNER JOIN students s ON se.student_id = s.id
                                  INNER JOIN classes c ON se.class_id = c.id
                                  WHERE se.academic_year_id = :yid AND se.promotion_status = 'waitlist'");
            $stmt->execute([':yid' => $yearId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode(['success' => true, 'data' => $rows]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch waitlist: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * List repeat students for a source academic year
     * GET /api/promotions/repeats?academic_year_id=ID
     */
    public function repeats(Request $request, Response $response)
    {
        try {
            $yearId = $request->getQueryParams()['academic_year_id'] ?? null;
            if (!$yearId) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'academic_year_id is required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT se.student_id, se.class_id, se.class_average, s.name AS student_name, s.admission_no, c.class_name
                                  FROM student_enrollments se
                                  INNER JOIN students s ON se.student_id = s.id
                                  INNER JOIN classes c ON se.class_id = c.id
                                  WHERE se.academic_year_id = :yid AND se.promotion_status = 'repeat'");
            $stmt->execute([':yid' => $yearId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode(['success' => true, 'data' => $rows]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch repeats: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Manually assign a student to a class in a target academic year (principal override supported)
     * POST /api/promotions/assign { student_id, target_academic_year_id, class_id, override_capacity?: bool }
     */
    public function manualAssign(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $studentId = $data['student_id'] ?? null;
            $targetYearId = $data['target_academic_year_id'] ?? null;
            $classId = $data['class_id'] ?? null;
            $override = !empty($data['override_capacity']);
            if (!$studentId || !$targetYearId || !$classId) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'student_id, target_academic_year_id and class_id are required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $db = Database::getInstance()->getConnection();
            // Ensure capacity column exists before capacity checks
            \App\Utils\Schema::ensureClassCapacityColumns($db);
            \App\Utils\Schema::ensureEnrollmentStatusColumn($db);
            $hasEnrollmentStatus = $this->hasEnrollmentStatusColumn($db);

            // Prevent duplicate enrollment in target year
            $stmt = $db->prepare("SELECT id FROM student_enrollments WHERE student_id = :sid AND academic_year_id = :yid");
            $stmt->execute([':sid' => $studentId, ':yid' => $targetYearId]);
            if ($stmt->fetch()) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Student already has enrollment in target year']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Capacity check
            $cap = $db->prepare("SELECT capacity FROM classes WHERE id = :cid");
            $cap->execute([':cid' => $classId]);
            $row = $cap->fetch(\PDO::FETCH_ASSOC);
            $capacity = $row && $row['capacity'] !== null ? (int)$row['capacity'] : null;

            if (!$override && $capacity !== null) {
                $cnt = $db->prepare("SELECT COUNT(*) as c FROM student_enrollments WHERE class_id = :cid AND academic_year_id = :yid");
                $cnt->execute([':cid' => $classId, ':yid' => $targetYearId]);
                $assigned = (int)$cnt->fetchColumn();
                if ($assigned >= $capacity) {
                    $response->getBody()->write(json_encode(['success' => false, 'message' => 'Class is at capacity']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
                }
            }

            // Create target year enrollment
            if ($hasEnrollmentStatus) {
                $ins = $db->prepare("INSERT INTO student_enrollments (student_id, class_id, academic_year_id, status, created_at) VALUES (:sid, :cid, :yid, 'active', NOW())");
                $ins->execute([':sid' => $studentId, ':cid' => $classId, ':yid' => $targetYearId]);
            } else {
                $ins = $db->prepare("INSERT INTO student_enrollments (student_id, class_id, academic_year_id, created_at) VALUES (:sid, :cid, :yid, NOW())");
                $ins->execute([':sid' => $studentId, ':cid' => $classId, ':yid' => $targetYearId]);
            }

            // Activity log (principal/admin override)
            try {
                $user = $request->getAttribute('user');
                $logger = new \App\Utils\ActivityLogger($db);
                $logger->logFromRequest(
                    $request,
                    $user->id,
                    $user->role ?? 'admin',
                    'update',
                    'Manual student assignment to class',
                    'student_enrollment',
                    null,
                    [
                        'student_id' => (int)$studentId,
                        'target_academic_year_id' => (int)$targetYearId,
                        'class_id' => (int)$classId,
                        'override_capacity' => $override
                    ],
                    \App\Utils\ActivityLogger::guessDisplayName($user)
                );
            } catch (\Exception $e) {}

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Student assigned to class']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Assign failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update student enrollment status
     */
    private function updateStudentStatus($db, $studentId, $academicYearId, $status, bool $hasEnrollmentStatus)
    {
        if ($hasEnrollmentStatus) {
            $stmt = $db->prepare("
                UPDATE student_enrollments
                SET status = :status,
                    promotion_status = :promotion_status,
                    updated_at = NOW()
                WHERE student_id = :student_id
                AND academic_year_id = :year_id
            ");
            $stmt->execute([
                ':status' => $status === 'promoted' ? 'completed' : $status,
                ':promotion_status' => $status,
                ':student_id' => $studentId,
                ':year_id' => $academicYearId
            ]);
        } else {
            // Fallback for schemas without status column
            $stmt = $db->prepare("
                UPDATE student_enrollments
                SET promotion_status = :promotion_status,
                    updated_at = NOW()
                WHERE student_id = :student_id
                AND academic_year_id = :year_id
            ");
            $stmt->execute([
                ':promotion_status' => $status,
                ':student_id' => $studentId,
                ':year_id' => $academicYearId
            ]);
        }
    }

    /**
     * Get promotion statistics for a class
     */
    public function getPromotionStats(Request $request, Response $response, array $args)
    {
        try {
            $academicYearId = $request->getQueryParams()['academic_year_id'] ?? null;
            $classId = $request->getQueryParams()['class_id'] ?? null;

            if (!$academicYearId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Academic year ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $db = Database::getInstance()->getConnection();

            // Get academic year details
            $stmt = $db->prepare("SELECT * FROM academic_years WHERE id = :id");
            $stmt->execute([':id' => $academicYearId]);
            $academicYear = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$academicYear) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Academic year not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Build query
            $sql = "
                SELECT
                    se.promotion_status,
                    COUNT(*) as count
                FROM student_enrollments se
                WHERE se.academic_year_id = :year_id
            ";
            $params = [':year_id' => $academicYearId];

            if ($classId) {
                $sql .= " AND se.class_id = :class_id";
                $params[':class_id'] = $classId;
            }

            $sql .= " GROUP BY se.promotion_status";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'academic_year' => $academicYear,
                    'statistics' => $stats
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Promotion stats error: " . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch promotion statistics: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Rollover promoted/repeat students into the target academic year
     * Body: { source_academic_year_id: number, target_academic_year_id: number }
     */
    public function rollover(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $sourceYearId = $data['source_academic_year_id'] ?? null;
            $targetYearId = $data['target_academic_year_id'] ?? null;

            if (!$sourceYearId || !$targetYearId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'source_academic_year_id and target_academic_year_id are required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $db = Database::getInstance()->getConnection();
            // Ensure placement/capacity columns exist for capacity checks/joins
            \App\Utils\Schema::ensureClassCapacityColumns($db);
            \App\Utils\Schema::ensureEnrollmentStatusColumn($db);
            $hasEnrollmentStatus = $this->hasEnrollmentStatusColumn($db);

            // Validate both years exist
            $stmt = $db->prepare('SELECT id FROM academic_years WHERE id IN (:s,:t)');
            $stmt->execute([':s' => $sourceYearId, ':t' => $targetYearId]);
            $rows = $stmt->rowCount();
            if ($rows < 2) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid academic year IDs'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Fetch eligible enrollments from source year
            $stmt = $db->prepare("SELECT se.student_id, se.class_id, se.promotion_status, se.promoted_to_class_id, se.class_average
                                  FROM student_enrollments se
                                  WHERE se.academic_year_id = :src");
            $stmt->execute([':src' => $sourceYearId]);
            $enrollments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $created = 0; $skipped = 0; $errors = 0;
            if ($hasEnrollmentStatus) {
                $ins = $db->prepare("INSERT INTO student_enrollments (student_id, class_id, academic_year_id, status, created_at)
                                  VALUES (:sid, :cid, :yid, 'active', NOW())");
            } else {
                $ins = $db->prepare("INSERT INTO student_enrollments (student_id, class_id, academic_year_id, created_at)
                                  VALUES (:sid, :cid, :yid, NOW())");
            }
            $exists = $db->prepare("SELECT id FROM student_enrollments WHERE student_id = :sid AND academic_year_id = :yid");

            // Preload current counts per class for target year capacity checks
            $counts = [];
            $cntStmt = $db->prepare("SELECT class_id, COUNT(*) as c FROM student_enrollments WHERE academic_year_id = :yid GROUP BY class_id");
            $cntStmt->execute([':yid' => $targetYearId]);
            foreach ($cntStmt->fetchAll(\PDO::FETCH_ASSOC) as $r) { $counts[(int)$r['class_id']] = (int)$r['c']; }

            foreach ($enrollments as $en) {
                $destClassId = null;
                if ($en['promotion_status'] === 'promoted' && !empty($en['promoted_to_class_id'])) {
                    $destClassId = (int)$en['promoted_to_class_id'];
                } elseif ($en['promotion_status'] === 'repeat') {
                    $destClassId = (int)$en['class_id'];
                } elseif (!empty($data['include_waitlist']) && $en['promotion_status'] === 'waitlist') {
                    // Try lowest-threshold destination with capacity
                    $cand = $db->prepare("SELECT c2.id, COALESCE(c2.placement_min_average,0) as min_avg, c2.capacity
                                           FROM classes c1
                                           INNER JOIN classes c2 ON c1.admin_id = c2.admin_id
                                           WHERE c1.id = :cid AND c2.grade_level = c1.grade_level + 1
                                           ORDER BY min_avg ASC, c2.id ASC");
                    $cand->execute([':cid' => $en['class_id']]);
                    $dest = $cand->fetchAll(\PDO::FETCH_ASSOC);
                    $avg = isset($en['class_average']) ? (float)$en['class_average'] : 0.0;
                    foreach ($dest as $d) {
                        $min = (float)$d['min_avg'];
                        $cap = $d['capacity'] !== null ? (int)$d['capacity'] : null;
                        $cur = $counts[(int)$d['id']] ?? 0;
                        if ($avg >= $min && ($cap === null || $cur < $cap)) {
                            $destClassId = (int)$d['id'];
                            $counts[$destClassId] = $cur + 1;
                            break;
                        }
                    }
                    if (!$destClassId) { $skipped++; continue; }
                } else {
                    $skipped++; // dropped/failed/graduated or no destination
                    continue;
                }

                // Skip if already has enrollment in target year
                $exists->execute([':sid' => $en['student_id'], ':yid' => $targetYearId]);
                if ($exists->fetch()) { $skipped++; continue; }

                try {
                    $ins->execute([':sid' => $en['student_id'], ':cid' => $destClassId, ':yid' => $targetYearId]);
                    $created++;
                } catch (\Exception $e) {
                    $errors++;
                }
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Rollover completed',
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Rollover failed: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Check if student_enrollments has a status column (guards deployments missing this field)
     */
    private function hasEnrollmentStatusColumn(\PDO $db): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $db->query("SHOW COLUMNS FROM student_enrollments LIKE 'status'");
            $cached = $stmt && $stmt->rowCount() > 0;
            return $cached;
        } catch (\Exception $e) {
            return false;
        }
    }
}

