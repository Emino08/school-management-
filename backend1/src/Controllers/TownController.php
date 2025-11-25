<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TownController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all towns (Admin)
     */
    public function getTowns(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            
            $sql = "SELECT t.*, 
                           tm.name as town_master_name,
                           tm.email as town_master_email,
                           COUNT(DISTINCT tr.student_id) as student_count,
                           COUNT(DISTINCT tb.id) as block_count
                    FROM towns t
                    LEFT JOIN teachers tm ON t.town_master_id = tm.id
                    LEFT JOIN town_registrations tr ON t.id = tr.town_id AND tr.status = 'active'
                    LEFT JOIN town_blocks tb ON t.id = tb.town_id AND tb.is_active = 1
                    WHERE t.admin_id = :admin_id
                    GROUP BY t.id
                    ORDER BY t.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':admin_id' => $user->id]);
            $towns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'towns' => $towns
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch towns: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Create a new town (Admin)
     */
    public function createTown(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $data = $request->getParsedBody();
            
            if (empty($data['name'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Town name is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $sql = "INSERT INTO towns (admin_id, name, description, town_master_id, is_active)
                    VALUES (:admin_id, :name, :description, :town_master_id, :is_active)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':admin_id' => $user->id,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':town_master_id' => $data['town_master_id'] ?? null,
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            $townId = $this->db->lastInsertId();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Town created successfully',
                'town_id' => $townId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create town: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update a town (Admin)
     */
    public function updateTown(Request $request, Response $response, array $args)
    {
        try {
            $townId = $args['id'];
            $data = $request->getParsedBody();
            
            $sql = "UPDATE towns 
                    SET name = :name,
                        description = :description,
                        town_master_id = :town_master_id,
                        is_active = :is_active
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $townId,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':town_master_id' => $data['town_master_id'] ?? null,
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Town updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update town: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Delete a town (Admin)
     */
    public function deleteTown(Request $request, Response $response, array $args)
    {
        try {
            $townId = $args['id'];
            
            $sql = "DELETE FROM towns WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $townId]);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Town deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete town: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get blocks for a town (Admin/Town Master)
     */
    public function getTownBlocks(Request $request, Response $response, array $args)
    {
        try {
            $townId = $args['id'];
            
            $sql = "SELECT tb.*,
                           COUNT(tr.id) as student_count
                    FROM town_blocks tb
                    LEFT JOIN town_registrations tr ON tb.id = tr.block_id AND tr.status = 'active'
                    WHERE tb.town_id = :town_id
                    GROUP BY tb.id
                    ORDER BY tb.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':town_id' => $townId]);
            $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'blocks' => $blocks
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch blocks: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Create a block in a town (Admin)
     */
    public function createBlock(Request $request, Response $response, array $args)
    {
        try {
            $townId = $args['id'];
            $data = $request->getParsedBody();
            
            if (empty($data['name'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Block name is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $sql = "INSERT INTO town_blocks (town_id, name, description, capacity, is_active)
                    VALUES (:town_id, :name, :description, :capacity, :is_active)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':town_id' => $townId,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':capacity' => $data['capacity'] ?? 0,
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            $blockId = $this->db->lastInsertId();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Block created successfully',
                'block_id' => $blockId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create block: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update a block (Admin)
     */
    public function updateBlock(Request $request, Response $response, array $args)
    {
        try {
            $blockId = $args['blockId'];
            $data = $request->getParsedBody();
            
            $sql = "UPDATE town_blocks 
                    SET name = :name,
                        description = :description,
                        capacity = :capacity,
                        is_active = :is_active
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $blockId,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':capacity' => $data['capacity'] ?? 0,
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Block updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update block: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Delete a block (Admin)
     */
    public function deleteBlock(Request $request, Response $response, array $args)
    {
        try {
            $blockId = $args['blockId'];
            
            $sql = "DELETE FROM town_blocks WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $blockId]);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Block deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete block: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get students eligible for town registration (Town Master)
     * Only students who have paid
     */
    public function getEligibleStudents(Request $request, Response $response)
    {
        try {
            $params = $request->getQueryParams();
            $searchTerm = $params['search'] ?? '';
            $classId = $params['class_id'] ?? null;
            
            $sql = "SELECT s.id, s.id_number, s.first_name, s.last_name, s.name,
                           c.class_name, c.section,
                           p.amount as paid_amount, p.payment_date,
                           COALESCE(tr.id, 0) as is_registered
                    FROM students s
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN payments p ON s.id = p.student_id AND p.status = 'completed'
                    LEFT JOIN town_registrations tr ON s.id = tr.student_id 
                        AND tr.status = 'active'
                        AND tr.academic_year_id = (SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1)
                        AND tr.term_id = (SELECT id FROM terms WHERE is_current = 1 LIMIT 1)
                    WHERE p.id IS NOT NULL"; // Only students who have paid
            
            if ($searchTerm) {
                $sql .= " AND (s.id_number LIKE :search OR s.name LIKE :search 
                          OR s.first_name LIKE :search OR s.last_name LIKE :search)";
            }
            
            if ($classId) {
                $sql .= " AND c.id = :class_id";
            }
            
            $sql .= " GROUP BY s.id ORDER BY s.name";
            
            $stmt = $this->db->prepare($sql);
            
            if ($searchTerm) {
                $stmt->bindValue(':search', "%$searchTerm%");
            }
            if ($classId) {
                $stmt->bindValue(':class_id', $classId);
            }
            
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'students' => $students
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch students: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Register student to town (Town Master)
     */
    public function registerStudent(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $data = $request->getParsedBody();
            
            // Verify teacher is a town master
            $stmt = $this->db->prepare("SELECT town_id FROM teachers WHERE id = :id AND is_town_master = 1");
            $stmt->execute([':id' => $user->id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$teacher || !$teacher['town_id']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You are not assigned as a town master'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            
            // Verify student has paid
            $stmt = $this->db->prepare("SELECT id FROM payments WHERE student_id = :student_id AND status = 'completed' LIMIT 1");
            $stmt->execute([':student_id' => $data['student_id']]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student has not made payment. Only students who have paid can be registered.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Get current academic year and term
            $stmt = $this->db->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
            $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $this->db->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
            $term = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$academicYear || !$term) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No current academic year or term set'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $sql = "INSERT INTO town_registrations 
                    (student_id, town_id, block_id, academic_year_id, term_id, 
                     registered_by, registration_date, payment_verified, notes, status)
                    VALUES (:student_id, :town_id, :block_id, :academic_year_id, :term_id,
                            :registered_by, CURDATE(), 1, :notes, 'active')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $data['student_id'],
                ':town_id' => $teacher['town_id'],
                ':block_id' => $data['block_id'] ?? null,
                ':academic_year_id' => $academicYear['id'],
                ':term_id' => $term['id'],
                ':registered_by' => $user->id,
                ':notes' => $data['notes'] ?? null
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student registered to town successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student is already registered for this term'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to register student: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get town registrations (Town Master/Admin)
     */
    public function getTownRegistrations(Request $request, Response $response, array $args)
    {
        try {
            $townId = $args['id'];
            $params = $request->getQueryParams();
            $termId = $params['term_id'] ?? null;
            
            $sql = "SELECT tr.*, 
                           s.id_number, s.first_name, s.last_name, s.name as student_name,
                           c.class_name, c.section,
                           tb.name as block_name,
                           t.name as registered_by_name
                    FROM town_registrations tr
                    JOIN students s ON tr.student_id = s.id
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN town_blocks tb ON tr.block_id = tb.id
                    JOIN teachers t ON tr.registered_by = t.id
                    WHERE tr.town_id = :town_id";
            
            if ($termId) {
                $sql .= " AND tr.term_id = :term_id";
            }
            
            $sql .= " ORDER BY tr.registration_date DESC, s.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':town_id', $townId);
            if ($termId) {
                $stmt->bindValue(':term_id', $termId);
            }
            
            $stmt->execute();
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'registrations' => $registrations
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch registrations: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get town masters (teachers who are town masters)
     */
    public function getTownMasters(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            
            $sql = "SELECT t.id, t.name, t.first_name, t.last_name, t.email, t.phone,
                           tw.id as town_id, tw.name as town_name
                    FROM teachers t
                    LEFT JOIN towns tw ON t.town_id = tw.id
                    WHERE t.admin_id = :admin_id AND t.is_town_master = 1
                    ORDER BY t.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':admin_id' => $user->id]);
            $townMasters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'town_masters' => $townMasters
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch town masters: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get students for town roll call (Town Master)
     */
    public function getTownStudentsForRollCall(Request $request, Response $response, array $args)
    {
        try {
            $townId = $args['id'];
            $params = $request->getQueryParams();
            $date = $params['date'] ?? date('Y-m-d');
            $blockId = $params['block_id'] ?? null;
            
            $sql = "SELECT s.id, s.id_number, s.first_name, s.last_name, s.name,
                           c.class_name, c.section,
                           tb.name as block_name,
                           tr.block_id,
                           ta.id as attendance_id,
                           ta.status as attendance_status,
                           ta.time as attendance_time,
                           ta.notes as attendance_notes
                    FROM town_registrations tr
                    JOIN students s ON tr.student_id = s.id
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN town_blocks tb ON tr.block_id = tb.id
                    LEFT JOIN town_attendance ta ON s.id = ta.student_id 
                        AND ta.town_id = :town_id 
                        AND ta.date = :date
                    WHERE tr.town_id = :town_id 
                    AND tr.status = 'active'";
            
            if ($blockId) {
                $sql .= " AND tr.block_id = :block_id";
            }
            
            $sql .= " ORDER BY tb.name, s.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':town_id', $townId);
            $stmt->bindValue(':date', $date);
            if ($blockId) {
                $stmt->bindValue(':block_id', $blockId);
            }
            
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'students' => $students,
                'date' => $date
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch students: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Take town attendance (Town Master)
     */
    public function takeTownAttendance(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $data = $request->getParsedBody();
            
            // Verify teacher is a town master
            $stmt = $this->db->prepare("SELECT town_id FROM teachers WHERE id = :id AND is_town_master = 1");
            $stmt->execute([':id' => $user->id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$teacher || !$teacher['town_id']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You are not assigned as a town master'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            
            $date = $data['date'] ?? date('Y-m-d');
            $time = $data['time'] ?? date('H:i:s');
            $attendance = $data['attendance'] ?? []; // Array of {student_id, status, block_id, notes}
            
            $this->db->beginTransaction();
            
            $recorded = 0;
            $errors = [];
            
            foreach ($attendance as $record) {
                try {
                    $sql = "INSERT INTO town_attendance 
                            (town_id, block_id, student_id, date, time, status, taken_by, notes)
                            VALUES (:town_id, :block_id, :student_id, :date, :time, :status, :taken_by, :notes)
                            ON DUPLICATE KEY UPDATE 
                                status = VALUES(status),
                                time = VALUES(time),
                                notes = VALUES(notes),
                                taken_by = VALUES(taken_by)";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        ':town_id' => $teacher['town_id'],
                        ':block_id' => $record['block_id'] ?? null,
                        ':student_id' => $record['student_id'],
                        ':date' => $date,
                        ':time' => $time,
                        ':status' => $record['status'],
                        ':taken_by' => $user->id,
                        ':notes' => $record['notes'] ?? null
                    ]);
                    
                    // If absent, send notification (trigger handles this)
                    if ($record['status'] === 'absent') {
                        $this->sendAbsenceNotification($record['student_id'], $date, 'town');
                    }
                    
                    $recorded++;
                } catch (PDOException $e) {
                    $errors[] = "Student {$record['student_id']}: " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Attendance recorded for {$recorded} students",
                'recorded' => $recorded,
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to record attendance: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get town attendance history
     */
    public function getTownAttendanceHistory(Request $request, Response $response, array $args)
    {
        try {
            $townId = $args['id'];
            $params = $request->getQueryParams();
            $startDate = $params['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $params['end_date'] ?? date('Y-m-d');
            $studentId = $params['student_id'] ?? null;
            
            $sql = "SELECT ta.*, 
                           s.id_number, s.first_name, s.last_name, s.name as student_name,
                           c.class_name, c.section,
                           tb.name as block_name,
                           t.name as taken_by_name,
                           ta.parent_notified,
                           ta.notification_sent_at
                    FROM town_attendance ta
                    JOIN students s ON ta.student_id = s.id
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN town_blocks tb ON ta.block_id = tb.id
                    JOIN teachers t ON ta.taken_by = t.id
                    WHERE ta.town_id = :town_id
                    AND ta.date BETWEEN :start_date AND :end_date";
            
            if ($studentId) {
                $sql .= " AND ta.student_id = :student_id";
            }
            
            $sql .= " ORDER BY ta.date DESC, ta.time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':town_id', $townId);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);
            if ($studentId) {
                $stmt->bindValue(':student_id', $studentId);
            }
            
            $stmt->execute();
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'history' => $history,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch attendance history: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get attendance statistics for a town
     */
    public function getTownAttendanceStats(Request $request, Response $response, array $args)
    {
        try {
            $townId = $args['id'];
            $params = $request->getQueryParams();
            $date = $params['date'] ?? date('Y-m-d');
            
            // Get today's stats
            $sql = "SELECT 
                        COUNT(DISTINCT ta.student_id) as total_recorded,
                        SUM(CASE WHEN ta.status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN ta.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN ta.status = 'late' THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN ta.status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                        (SELECT COUNT(DISTINCT student_id) FROM town_registrations WHERE town_id = :town_id AND status = 'active') as total_students
                    FROM town_attendance ta
                    WHERE ta.town_id = :town_id AND ta.date = :date";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':town_id' => $townId, ':date' => $date]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate percentages
            $totalRecorded = $stats['total_recorded'] ?? 0;
            if ($totalRecorded > 0) {
                $stats['present_percentage'] = round(($stats['present_count'] / $totalRecorded) * 100, 2);
                $stats['absent_percentage'] = round(($stats['absent_count'] / $totalRecorded) * 100, 2);
            } else {
                $stats['present_percentage'] = 0;
                $stats['absent_percentage'] = 0;
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => $stats,
                'date' => $date
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Send absence notification to parents
     */
    private function sendAbsenceNotification($studentId, $date, $type = 'town')
    {
        try {
            // Get parent links
            $stmt = $this->db->prepare("
                SELECT p.id, p.email, p.phone, p.first_name, p.last_name, p.notification_preference
                FROM parent_student_links psl
                JOIN parents p ON psl.parent_id = p.id
                WHERE psl.student_id = :student_id AND psl.verified = 1
            ");
            $stmt->execute([':student_id' => $studentId]);
            $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($parents)) {
                return; // No parents linked
            }
            
            // Get student info
            $stmt = $this->db->prepare("SELECT id_number, first_name, last_name, name FROM students WHERE id = :id");
            $stmt->execute([':id' => $studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            foreach ($parents as $parent) {
                $message = $type === 'town' 
                    ? "Dear {$parent['first_name']}, your child {$student['name']} ({$student['id_number']}) was marked absent from town attendance on {$date}."
                    : "Dear {$parent['first_name']}, your child {$student['name']} ({$student['id_number']}) was marked absent from class on {$date}.";
                
                // Insert notification record (actual sending handled by background job)
                $stmt = $this->db->prepare("
                    INSERT INTO attendance_notifications 
                    (student_id, parent_id, attendance_type, absence_date, notification_type, status, message)
                    VALUES (:student_id, :parent_id, :type, :date, :notif_type, 'pending', :message)
                ");
                $stmt->execute([
                    ':student_id' => $studentId,
                    ':parent_id' => $parent['id'],
                    ':type' => $type,
                    ':date' => $date,
                    ':notif_type' => $parent['notification_preference'] ?? 'both',
                    ':message' => $message
                ]);
            }
            
        } catch (PDOException $e) {
            // Log error but don't fail the attendance recording
            error_log("Failed to send absence notification: " . $e->getMessage());
        }
    }

    /**
     * Get pending notifications
     */
    public function getPendingNotifications(Request $request, Response $response)
    {
        try {
            $sql = "SELECT an.*, 
                           s.id_number, s.name as student_name,
                           p.email as parent_email, p.phone as parent_phone, p.name as parent_name
                    FROM attendance_notifications an
                    JOIN students s ON an.student_id = s.id
                    LEFT JOIN parents p ON an.parent_id = p.id
                    WHERE an.status = 'pending'
                    ORDER BY an.created_at ASC
                    LIMIT 100";
            
            $stmt = $this->db->query($sql);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'notifications' => $notifications
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch notifications: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
