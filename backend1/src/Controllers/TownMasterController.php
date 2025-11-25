<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use App\Utils\ActivityLogger;
use PDO;

class TownMasterController
{
    private $db;
    private $logger;

    public function __construct()
    {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
        $this->logger = new ActivityLogger($this->db);
    }

    // ==================== ADMIN FUNCTIONS ====================

    /**
     * Get all towns for admin
     */
    public function getAllTowns(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.*,
                    COALESCE(bc.block_count, 0) AS block_count,
                    COALESCE(sc.student_count, 0) AS student_count,
                    CONCAT(tm.first_name, ' ', tm.last_name) AS town_master_name,
                    COALESCE(att.present_30d, 0) AS attendance_present_30d,
                    COALESCE(att.absent_30d, 0) AS attendance_absent_30d,
                    COALESCE(att.total_30d, 0) AS attendance_total_30d,
                    COALESCE(att.attendance_rate_30d, 0) AS attendance_rate_30d
                FROM towns t
                LEFT JOIN town_masters tmaster ON t.id = tmaster.town_id AND tmaster.is_active = TRUE
                LEFT JOIN teachers tm ON tmaster.teacher_id = tm.id
                LEFT JOIN (
                    SELECT town_id, COUNT(*) AS block_count 
                    FROM blocks 
                    GROUP BY town_id
                ) bc ON bc.town_id = t.id
                LEFT JOIN (
                    SELECT b.town_id, COUNT(DISTINCT sb.student_id) AS student_count
                    FROM student_blocks sb
                    INNER JOIN blocks b ON sb.block_id = b.id
                    WHERE sb.is_active = TRUE
                    GROUP BY b.town_id
                ) sc ON sc.town_id = t.id
                LEFT JOIN (
                    SELECT 
                        town_id,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_30d,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_30d,
                        COUNT(*) AS total_30d,
                        ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100) / NULLIF(COUNT(*), 0), 2) AS attendance_rate_30d
                    FROM town_attendance
                    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY town_id
                ) att ON att.town_id = t.id
                WHERE t.admin_id = :admin_id
                ORDER BY t.name
            ");
            $stmt->execute(['admin_id' => $user->id]);
            $towns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'towns' => $towns
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch towns: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Create a new town
     */
    public function createTown(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        if (empty($data['name'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Town name is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO towns (admin_id, name, description)
                VALUES (:admin_id, :name, :description)
            ");
            $stmt->execute([
                'admin_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null
            ]);

            $townId = $this->db->lastInsertId();

            // Create default blocks (A-F) for the town
            $blocks = ['A', 'B', 'C', 'D', 'E', 'F'];
            $stmt = $this->db->prepare("
                INSERT INTO blocks (town_id, name, capacity)
                VALUES (:town_id, :name, :capacity)
            ");

            foreach ($blocks as $blockName) {
                $stmt->execute([
                    'town_id' => $townId,
                    'name' => $blockName,
                    'capacity' => $data['block_capacity'] ?? 50
                ]);
            }

            // Log activity
            $this->logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'create',
                "Created town: {$data['name']}",
                'town',
                $townId,
                null,
                ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Town created successfully',
                'town_id' => $townId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Town with this name already exists'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create town: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update a town
     */
    public function updateTown(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();
        $townId = $args['id'];

        try {
            $stmt = $this->db->prepare("
                UPDATE towns 
                SET name = :name, description = :description
                WHERE id = :id AND admin_id = :admin_id
            ");
            $stmt->execute([
                'id' => $townId,
                'admin_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null
            ]);

            // Log activity
            $this->logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'update',
                "Updated town: {$data['name']}",
                'town',
                $townId,
                null,
                ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Town updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update town: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Delete a town
     */
    public function deleteTown(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $townId = $args['id'];

        try {
            $stmt = $this->db->prepare("
                DELETE FROM towns 
                WHERE id = :id AND admin_id = :admin_id
            ");
            $stmt->execute([
                'id' => $townId,
                'admin_id' => $user->id
            ]);

            // Log activity
            $this->logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'delete',
                "Deleted town ID: {$townId}",
                'town',
                $townId,
                null,
                ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Town deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete town: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get blocks for a town
     */
    public function getBlocks(Request $request, Response $response, $args)
    {
        $townId = $args['id'];

        try {
            $stmt = $this->db->prepare("
                SELECT b.*, 
                    COUNT(sb.id) as current_occupancy
                FROM blocks b
                LEFT JOIN student_blocks sb ON b.id = sb.block_id AND sb.is_active = TRUE
                WHERE b.town_id = :town_id
                GROUP BY b.id
                ORDER BY b.name
            ");
            $stmt->execute(['town_id' => $townId]);
            $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'blocks' => $blocks
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch blocks: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update block capacity
     */
    public function updateBlock(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();
        $blockId = $args['id'];

        try {
            $stmt = $this->db->prepare("
                UPDATE blocks 
                SET capacity = :capacity
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $blockId,
                'capacity' => $data['capacity']
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Block updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update block: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Assign a teacher as town master
     */
    public function assignTownMaster(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();
        $townId = $args['id'];

        if (empty($data['teacher_id'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Teacher ID is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->db->beginTransaction();

            // Deactivate existing town master
            $stmt = $this->db->prepare("
                UPDATE town_masters 
                SET is_active = FALSE 
                WHERE town_id = :town_id AND is_active = TRUE
            ");
            $stmt->execute(['town_id' => $townId]);

            // Assign new town master
            $stmt = $this->db->prepare("
                INSERT INTO town_masters (town_id, teacher_id, assigned_by, is_active)
                VALUES (:town_id, :teacher_id, :assigned_by, TRUE)
            ");
            $stmt->execute([
                'town_id' => $townId,
                'teacher_id' => $data['teacher_id'],
                'assigned_by' => $user->id
            ]);

            // Update teacher record
            $stmt = $this->db->prepare("
                UPDATE teachers 
                SET is_town_master = TRUE, town_master_of = :town_id
                WHERE id = :teacher_id
            ");
            $stmt->execute([
                'town_id' => $townId,
                'teacher_id' => $data['teacher_id']
            ]);

            $this->db->commit();

            // Log activity
            $this->logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'assign',
                "Assigned teacher ID {$data['teacher_id']} as town master",
                'town_master',
                $townId,
                null,
                ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Town master assigned successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to assign town master: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Remove town master assignment
     */
    public function removeTownMaster(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $assignmentId = $args['id'];

        try {
            $this->db->beginTransaction();

            // Get assignment details
            $stmt = $this->db->prepare("
                SELECT teacher_id, town_id FROM town_masters WHERE id = :id
            ");
            $stmt->execute(['id' => $assignmentId]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$assignment) {
                throw new \Exception('Assignment not found');
            }

            // Deactivate assignment
            $stmt = $this->db->prepare("
                UPDATE town_masters SET is_active = FALSE WHERE id = :id
            ");
            $stmt->execute(['id' => $assignmentId]);

            // Update teacher record
            $stmt = $this->db->prepare("
                UPDATE teachers 
                SET is_town_master = FALSE, town_master_of = NULL
                WHERE id = :teacher_id
            ");
            $stmt->execute(['teacher_id' => $assignment['teacher_id']]);

            $this->db->commit();

            // Log activity
            $this->logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'remove',
                "Removed town master assignment",
                'town_master',
                $assignmentId,
                null,
                ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Town master removed successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to remove town master: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // ==================== TOWN MASTER FUNCTIONS ====================

    /**
     * Get town master's assigned town and blocks
     */
    public function getMyTown(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $stmt = $this->db->prepare("
                SELECT t.*, tm.id as assignment_id
                FROM towns t
                INNER JOIN town_masters tm ON t.id = tm.town_id
                WHERE tm.teacher_id = :teacher_id AND tm.is_active = TRUE
            ");
            $stmt->execute(['teacher_id' => $user->id]);
            $town = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$town) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You are not assigned to any town'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get blocks with student counts
            $stmt = $this->db->prepare("
                SELECT b.*, 
                    COUNT(sb.id) as current_occupancy
                FROM blocks b
                LEFT JOIN student_blocks sb ON b.id = sb.block_id AND sb.is_active = TRUE
                WHERE b.town_id = :town_id
                GROUP BY b.id
                ORDER BY b.name
            ");
            $stmt->execute(['town_id' => $town['id']]);
            $town['blocks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'town' => $town
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch town: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get all students in town master's blocks
     */
    public function getMyStudents(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        try {
            // Get town master's town
            $stmt = $this->db->prepare("
                SELECT town_id FROM town_masters 
                WHERE teacher_id = :teacher_id AND is_active = TRUE
            ");
            $stmt->execute(['teacher_id' => $user->id]);
            $townMaster = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$townMaster) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You are not assigned to any town'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $query = "
                SELECT s.id, s.name, s.first_name, s.last_name, s.id_number,
                    NULL as class_name,
                    b.name as block_name,
                    sb.guardian_name, sb.guardian_phone, sb.guardian_email, sb.guardian_address,
                    sb.academic_year_id, sb.term, sb.id as student_block_id
                FROM students s
                INNER JOIN student_blocks sb ON s.id = sb.student_id
                INNER JOIN blocks b ON sb.block_id = b.id
                WHERE b.town_id = :town_id AND sb.is_active = TRUE
            ";

            if (!empty($params['block_id'])) {
                $query .= " AND sb.block_id = :block_id";
            }

            $query .= " ORDER BY b.name, s.name";

            $stmt = $this->db->prepare($query);
            $execParams = ['town_id' => $townMaster['town_id']];
            if (!empty($params['block_id'])) {
                $execParams['block_id'] = $params['block_id'];
            }
            $stmt->execute($execParams);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'students' => $students
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch students: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Register a student to a block
     */
    public function registerStudent(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // Validate required fields
        $required = ['student_id', 'block_id', 'academic_year_id', 'term'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        try {
            // Check if student has paid fees (optional - can be simplified)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as payment_count
                FROM fees_payments
                WHERE student_id = :student_id 
                    AND academic_year_id = :academic_year_id
                    AND allows_registration = TRUE
                    AND status = 'paid'
            ");
            $stmt->execute([
                'student_id' => $data['student_id'],
                'academic_year_id' => $data['academic_year_id']
            ]);
            $paymentCheck = $stmt->fetch(PDO::FETCH_ASSOC);

            // Note: Fee verification is optional - can be disabled if needed
            // Uncomment below to enforce fee payment
            /*
            if ($paymentCheck['payment_count'] == 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student has not paid required fees for registration'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            */

            // Check block capacity
            $stmt = $this->db->prepare("
                SELECT b.capacity, COUNT(sb.id) as current_occupancy
                FROM blocks b
                LEFT JOIN student_blocks sb ON b.id = sb.block_id AND sb.is_active = TRUE
                WHERE b.id = :block_id
                GROUP BY b.id
            ");
            $stmt->execute(['block_id' => $data['block_id']]);
            $block = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($block['current_occupancy'] >= $block['capacity']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Block is at full capacity'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Register student
            $stmt = $this->db->prepare("
                INSERT INTO student_blocks 
                (student_id, block_id, academic_year_id, term, assigned_by, 
                 guardian_name, guardian_phone, guardian_email, guardian_address, is_active)
                VALUES 
                (:student_id, :block_id, :academic_year_id, :term, :assigned_by,
                 :guardian_name, :guardian_phone, :guardian_email, :guardian_address, TRUE)
            ");
            $stmt->execute([
                'student_id' => $data['student_id'],
                'block_id' => $data['block_id'],
                'academic_year_id' => $data['academic_year_id'],
                'term' => $data['term'],
                'assigned_by' => $user->id,
                'guardian_name' => $data['guardian_name'] ?? null,
                'guardian_phone' => $data['guardian_phone'] ?? null,
                'guardian_email' => $data['guardian_email'] ?? null,
                'guardian_address' => $data['guardian_address'] ?? null
            ]);

            // Log activity
            $this->logger->logFromRequest(
                $request,
                $user->id,
                'teacher',
                'register',
                "Registered student ID {$data['student_id']} to block",
                'student_block',
                $this->db->lastInsertId(),
                null,
                ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student registered successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student is already registered for this term'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to register student: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Record attendance (roll call)
     */
    public function recordAttendance(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        if (empty($data['attendance']) || !is_array($data['attendance'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Attendance data is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Get teacher's town
            $stmt = $this->db->prepare("
                SELECT town_id FROM town_masters 
                WHERE teacher_id = :teacher_id AND is_active = TRUE
            ");
            $stmt->execute(['teacher_id' => $user->id]);
            $townMaster = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$townMaster) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You are not assigned to any town'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get current academic year if not provided
            $academicYearId = $data['academic_year_id'] ?? null;
            $term = $data['term'] ?? null;
            
            if (!$academicYearId) {
                $stmt = $this->db->query("SELECT id FROM academic_years WHERE is_active = TRUE LIMIT 1");
                $activeYear = $stmt->fetch(PDO::FETCH_ASSOC);
                $academicYearId = $activeYear['id'] ?? null;
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO town_attendance 
                (town_id, block_id, student_id, academic_year_id, term, date, time, status, taken_by, notes)
                VALUES (:town_id, :block_id, :student_id, :academic_year_id, :term, :date, :time, :status, :taken_by, :notes)
                ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                time = VALUES(time),
                notes = VALUES(notes)
            ");

            $date = $data['date'] ?? date('Y-m-d');
            $time = date('H:i:s');
            $absentStudents = [];

            foreach ($data['attendance'] as $record) {
                // Get student_id and block_id from student_block_id
                $stmtBlock = $this->db->prepare("
                    SELECT 
                        sb.student_id, 
                        sb.block_id,
                        s.name AS student_name,
                        s.id_number
                    FROM student_blocks sb
                    INNER JOIN students s ON sb.student_id = s.id
                    WHERE sb.id = :id
                ");
                $stmtBlock->execute(['id' => $record['student_block_id']]);
                $studentBlock = $stmtBlock->fetch(PDO::FETCH_ASSOC);

                if (!$studentBlock) {
                    continue;
                }

                $stmt->execute([
                    'town_id' => $townMaster['town_id'],
                    'block_id' => $studentBlock['block_id'],
                    'student_id' => $studentBlock['student_id'],
                    'academic_year_id' => $academicYearId,
                    'term' => $term,
                    'date' => $date,
                    'time' => $time,
                    'status' => $record['status'],
                    'taken_by' => $user->id,
                    'notes' => $record['notes'] ?? null
                ]);

                if ($record['status'] === 'absent') {
                    $absentStudents[] = [
                        'student_id' => $studentBlock['student_id'],
                        'student_block_id' => $record['student_block_id'],
                        'student_name' => $studentBlock['student_name'] ?? null,
                        'id_number' => $studentBlock['id_number'] ?? null
                    ];
                }
            }

            // Send notifications to parents of absent students
            if (!empty($absentStudents)) {
                $this->notifyParentsOfAbsence($absentStudents, $date);
                $this->trackAttendanceStrikes($absentStudents, $date, $academicYearId, $term, $townMaster['town_id']);
            }

            $this->db->commit();

            // Log activity
            $this->logger->logFromRequest(
                $request,
                $user->id,
                'teacher',
                'attendance',
                "Recorded town attendance for " . count($data['attendance']) . " students",
                'town_attendance',
                null,
                null,
                ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Attendance recorded successfully',
                'absent_count' => count($absentStudents)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to record attendance: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get attendance records
     */
    public function getAttendance(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        try {
            // Get teacher's town
            $stmt = $this->db->prepare("
                SELECT town_id FROM town_masters 
                WHERE teacher_id = :teacher_id AND is_active = TRUE
            ");
            $stmt->execute(['teacher_id' => $user->id]);
            $townMaster = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$townMaster) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You are not assigned to any town'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $query = "
                SELECT ta.*, 
                    s.name as student_name, 
                    s.first_name,
                    s.last_name,
                    s.id_number,
                    b.name as block_name,
                    b.id as block_id,
                    ta.academic_year_id,
                    ta.term,
                    CONCAT(t.first_name, ' ', t.last_name) as taken_by_name
                FROM town_attendance ta
                INNER JOIN students s ON ta.student_id = s.id
                INNER JOIN blocks b ON ta.block_id = b.id
                INNER JOIN teachers t ON ta.taken_by = t.id
                WHERE ta.town_id = :town_id
            ";

            $execParams = ['town_id' => $townMaster['town_id']];

            if (!empty($params['date'])) {
                $query .= " AND ta.date = :date";
                $execParams['date'] = $params['date'];
            }

            if (!empty($params['start_date'])) {
                $query .= " AND ta.date >= :start_date";
                $execParams['start_date'] = $params['start_date'];
            }

            if (!empty($params['end_date'])) {
                $query .= " AND ta.date <= :end_date";
                $execParams['end_date'] = $params['end_date'];
            }

            if (!empty($params['status'])) {
                $query .= " AND ta.status = :status";
                $execParams['status'] = $params['status'];
            }

            if (!empty($params['academic_year_id'])) {
                $query .= " AND ta.academic_year_id = :academic_year_id";
                $execParams['academic_year_id'] = $params['academic_year_id'];
            }

            if (!empty($params['term'])) {
                $query .= " AND ta.term = :term";
                $execParams['term'] = $params['term'];
            }

            if (!empty($params['block_id'])) {
                $query .= " AND ta.block_id = :block_id";
                $execParams['block_id'] = $params['block_id'];
            }

            $query .= " ORDER BY ta.date DESC, ta.time DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($execParams);
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'attendance' => $attendance
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch attendance: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // ==================== HELPER FUNCTIONS ====================

    /**
     * Notify parents of student absence
     */
    private function notifyParentsOfAbsence(array $absentStudents, string $date): void
    {
        try {
            if (empty($absentStudents)) {
                return;
            }

            $studentIds = array_unique(array_map(function ($student) {
                return $student['student_id'];
            }, $absentStudents));

            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            
            $stmt = $this->db->prepare("
                SELECT s.id AS student_id, s.name as student_name, s.id_number,
                    sb.guardian_name, sb.guardian_email, sb.guardian_phone,
                    p.id as parent_id, p.email as parent_email
                FROM students s
                LEFT JOIN student_blocks sb ON sb.student_id = s.id AND sb.is_active = TRUE
                LEFT JOIN parent_students ps ON s.id = ps.student_id AND ps.verified = TRUE
                LEFT JOIN parents p ON ps.parent_id = p.id
                WHERE s.id IN ($placeholders)
            ");
            $stmt->execute($studentIds);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($students as $student) {
                // Create notification
                $studentName = $student['student_name'] ?? 'student';
                $message = "Your child {$studentName} was absent from town roll call on {$date}.";
                
                // Insert notification for parent if exists
                if ($student['parent_id']) {
                    $stmt = $this->db->prepare("
                        INSERT INTO notifications 
                        (user_id, user_role, message, notification_type, parent_notified, parent_notification_sent_at)
                        VALUES (:user_id, 'Parent', :message, 'attendance', TRUE, NOW())
                    ");
                    $stmt->execute([
                        'user_id' => $student['parent_id'],
                        'message' => $message
                    ]);
                }
            }
        } catch (\Exception $e) {
            error_log("Failed to notify parents: " . $e->getMessage());
        }
    }

    /**
     * Track attendance strikes and create urgent notifications
     */
    private function trackAttendanceStrikes(array $absentStudents, string $date, ?int $academicYearId, ?int $term, ?int $townId): void
    {
        try {
            $yearId = $academicYearId ?? $this->getActiveAcademicYearId();
            if (!$yearId || empty($absentStudents)) {
                return;
            }
            $termValue = $term ?? $this->getTermFromStudentBlocks($absentStudents);

            foreach ($absentStudents as $student) {
                $studentId = (int)$student['student_id'];
                $studentName = $student['student_name'] ?? ('ID ' . $studentId);

                // Calculate consecutive absences (latest first)
                $streak = $this->calculateTownAbsenceStreak($studentId, $townId);

                // Get existing strike record to avoid duplicate notifications for same streak
                $existingStrike = $this->getStrikeRecord($studentId, $yearId, $termValue);
                $alreadyNotified = $existingStrike && (bool)$existingStrike['notification_sent'];
                $shouldNotify = $streak >= 3 && !$alreadyNotified;
                $markNotified = $streak >= 3 ? ($alreadyNotified || $shouldNotify) : false;

                $this->upsertStrikeRecord($studentId, $yearId, $termValue, $streak, $date, $markNotified);

                if ($shouldNotify) {
                    // Notify principal/admin
                    $message = "Student {$studentName} has missed three consecutive town roll calls. Please follow up.";
                    $stmt = $this->db->prepare("
                        INSERT INTO notifications (user_id, user_role, message, notification_type, requires_action)
                        SELECT id, 'Admin', :message, 'urgent', TRUE
                        FROM admins 
                        WHERE role IN ('principal', 'admin')
                    ");
                    $stmt->execute(['message' => $message]);

                    $notificationId = $this->db->lastInsertId();

                    // Track in urgent_notifications table for auditability
                    $stmt = $this->db->prepare("
                        INSERT INTO urgent_notifications 
                        (notification_id, student_id, incident_type, description)
                        VALUES (:notification_id, :student_id, 'attendance_3_strikes', 
                            'Student has been absent {$streak} consecutive times in town attendance')
                    ");
                    $stmt->execute([
                        'notification_id' => $notificationId,
                        'student_id' => $studentId
                    ]);
                }
            }
        } catch (\Exception $e) {
            error_log("Failed to track attendance strikes: " . $e->getMessage());
        }
    }

    /**
     * Get the current active academic year id
     */
    private function getActiveAcademicYearId(): ?int
    {
        $stmt = $this->db->query("SELECT id FROM academic_years WHERE is_active = TRUE LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    /**
     * Get a term value from the provided student block data
     */
    private function getTermFromStudentBlocks(array $absentStudents): int
    {
        if (empty($absentStudents)) {
            return 1;
        }

        $studentId = $absentStudents[0]['student_id'];
        $stmt = $this->db->prepare("SELECT term FROM student_blocks WHERE student_id = :student_id ORDER BY assigned_at DESC LIMIT 1");
        $stmt->execute(['student_id' => $studentId]);
        $term = $stmt->fetchColumn();
        return (int)($term ?: 1);
    }

    /**
     * Calculate consecutive absence streak for a town student
     */
    private function calculateTownAbsenceStreak(int $studentId, ?int $townId): int
    {
        $query = "
            SELECT status 
            FROM town_attendance 
            WHERE student_id = :student_id
        ";

        $params = ['student_id' => $studentId];

        if ($townId) {
            $query .= " AND town_id = :town_id";
            $params['town_id'] = $townId;
        }

        $query .= " ORDER BY date DESC, time DESC LIMIT 3";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    /**
     * Fetch an attendance strike record for a student/year/term
     */
    private function getStrikeRecord(int $studentId, ?int $yearId, int $term): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM attendance_strikes 
            WHERE student_id = :student_id AND academic_year_id = :year_id AND term = :term
            LIMIT 1
        ");
        $stmt->execute([
            'student_id' => $studentId,
            'year_id' => $yearId,
            'term' => $term
        ]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        return $record ?: null;
    }

    /**
     * Upsert strike record with latest streak and notification state
     */
    private function upsertStrikeRecord(int $studentId, ?int $yearId, int $term, int $streak, string $date, bool $notificationSent): void
    {
        $stmt = $this->db->prepare("
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
            'student_id' => $studentId,
            'year_id' => $yearId,
            'term' => $term,
            'absence_count' => $streak,
            'last_absence_date' => $date,
            'notification_sent' => $notificationSent
        ]);
    }

    /**
     * Auto-assign teacher to existing town when marked as town master
     * This is called when teacher's is_town_master flag is set to true
     */
    public static function autoAssignToTown($teacherId, $pdo)
    {
        try {
            // Check if teacher is already assigned
            $stmt = $pdo->prepare("
                SELECT id FROM town_masters 
                WHERE teacher_id = ? AND is_active = TRUE
            ");
            $stmt->execute([$teacherId]);
            if ($stmt->fetch()) {
                return; // Already assigned
            }

            // Get the first/main town (or create one if none exists)
            $stmt = $pdo->query("SELECT id FROM towns ORDER BY id ASC LIMIT 1");
            $town = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$town) {
                // Create default town if none exists
                $stmt = $pdo->prepare("
                    INSERT INTO towns (admin_id, name, description, created_at, updated_at)
                    VALUES (1, 'Main House', 'Default school house', NOW(), NOW())
                ");
                $stmt->execute();
                $townId = $pdo->lastInsertId();
                
                // Create blocks A-F
                $blocks = ['A', 'B', 'C', 'D', 'E', 'F'];
                $stmt = $pdo->prepare("
                    INSERT INTO blocks (town_id, name, capacity, created_at, updated_at)
                    VALUES (?, ?, 50, NOW(), NOW())
                ");
                foreach ($blocks as $block) {
                    $stmt->execute([$townId, $block]);
                }
            } else {
                $townId = $town['id'];
            }

            // Assign teacher to town
            $stmt = $pdo->prepare("
                INSERT INTO town_masters (town_id, teacher_id, assigned_by, is_active, assigned_at)
                VALUES (?, ?, 1, TRUE, NOW())
            ");
            $stmt->execute([$townId, $teacherId]);

            // Update teacher record
            $stmt = $pdo->prepare("
                UPDATE teachers 
                SET is_town_master = TRUE, town_master_of = ?
                WHERE id = ?
            ");
            $stmt->execute([$townId, $teacherId]);

            error_log("Auto-assigned teacher ID $teacherId to town ID $townId");
            
        } catch (\Exception $e) {
            error_log("Failed to auto-assign teacher to town: " . $e->getMessage());
        }
    }
}
