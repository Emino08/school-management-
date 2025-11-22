<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserRoleController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get teachers by role
     */
    public function getTeachersByRole(Request $request, Response $response, array $args)
    {
        try {
            $user = $request->getAttribute('user');
            $role = $args['role'];
            
            $roleColumn = match($role) {
                'town-master' => 'is_town_master',
                'exam-officer' => 'is_exam_officer',
                'finance' => 'is_finance_officer',
                'principal' => 'is_principal',
                default => null
            };
            
            if (!$roleColumn) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid role'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $sql = "SELECT t.id, t.first_name, t.last_name, t.name, t.email, t.phone,
                           t.is_town_master, t.is_exam_officer, t.is_finance_officer, t.is_principal,
                           tw.id as town_id, tw.name as town_name,
                           COUNT(DISTINCT tc.class_id) as class_count,
                           COUNT(DISTINCT ts.subject_id) as subject_count
                    FROM teachers t
                    LEFT JOIN towns tw ON t.town_id = tw.id
                    LEFT JOIN teacher_classes tc ON t.id = tc.teacher_id
                    LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
                    WHERE t.admin_id = :admin_id AND t.$roleColumn = 1
                    GROUP BY t.id
                    ORDER BY t.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':admin_id' => $user->id]);
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'role' => $role,
                'teachers' => $teachers,
                'count' => count($teachers)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch teachers: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get all roles summary
     */
    public function getRolesSummary(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            
            $sql = "SELECT 
                        COUNT(CASE WHEN is_town_master = 1 THEN 1 END) as town_masters,
                        COUNT(CASE WHEN is_exam_officer = 1 THEN 1 END) as exam_officers,
                        COUNT(CASE WHEN is_finance_officer = 1 THEN 1 END) as finance_officers,
                        COUNT(CASE WHEN is_principal = 1 THEN 1 END) as principals,
                        COUNT(*) as total_teachers
                    FROM teachers
                    WHERE admin_id = :admin_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':admin_id' => $user->id]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'summary' => $summary
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch summary: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get urgent notifications
     */
    public function getUrgentNotifications(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $params = $request->getQueryParams();
            
            $actionTaken = $params['action_taken'] ?? null;
            $type = $params['type'] ?? null;
            $priority = $params['priority'] ?? null;
            
            $sql = "SELECT un.*, 
                           s.id_number, s.first_name, s.last_name, s.name as student_name,
                           c.class_name, c.section,
                           t.name as action_taken_by_name
                    FROM urgent_notifications un
                    LEFT JOIN students s ON un.student_id = s.id
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN teachers t ON un.action_taken_by = t.id
                    WHERE un.admin_id = :admin_id";
            
            if ($actionTaken !== null) {
                $sql .= " AND un.action_taken = :action_taken";
            }
            if ($type) {
                $sql .= " AND un.type = :type";
            }
            if ($priority) {
                $sql .= " AND un.priority = :priority";
            }
            
            $sql .= " ORDER BY 
                        FIELD(un.priority, 'critical', 'high', 'medium', 'low'),
                        un.action_taken ASC,
                        un.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':admin_id', $user->id);
            if ($actionTaken !== null) {
                $stmt->bindValue(':action_taken', $actionTaken);
            }
            if ($type) {
                $stmt->bindValue(':type', $type);
            }
            if ($priority) {
                $stmt->bindValue(':priority', $priority);
            }
            
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON data
            foreach ($notifications as &$notif) {
                if ($notif['related_data']) {
                    $notif['related_data'] = json_decode($notif['related_data'], true);
                }
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
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

    /**
     * Mark urgent notification as action taken (Principal)
     */
    public function markActionTaken(Request $request, Response $response, array $args)
    {
        try {
            $user = $request->getAttribute('user');
            $notificationId = $args['id'];
            $data = $request->getParsedBody();
            
            // Verify user is principal
            $stmt = $this->db->prepare("SELECT id FROM teachers WHERE id = :id AND is_principal = 1");
            $stmt->execute([':id' => $user->id]);
            
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Only principals can mark actions as taken'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            
            $sql = "UPDATE urgent_notifications 
                    SET action_taken = 1,
                        action_taken_by = :principal_id,
                        action_taken_at = CURRENT_TIMESTAMP,
                        action_notes = :notes
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $notificationId,
                ':principal_id' => $user->id,
                ':notes' => $data['notes'] ?? null
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Action marked as taken'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update notification: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get town master's students with parent details
     */
    public function getTownMasterStudents(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $params = $request->getQueryParams();
            $blockId = $params['block_id'] ?? null;
            
            // Verify teacher is a town master and get their town
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
            
            $sql = "SELECT DISTINCT s.id, s.id_number, s.first_name, s.last_name, s.name,
                           s.date_of_birth, s.gender, s.phone, s.email, s.address,
                           c.class_name, c.section,
                           tb.name as block_name, tr.block_id,
                           GROUP_CONCAT(
                               DISTINCT CONCAT_WS('|',
                                   p.id,
                                   p.first_name,
                                   p.last_name,
                                   p.email,
                                   p.phone,
                                   p.address,
                                   psl.relationship
                               ) SEPARATOR ';;'
                           ) as parents_info
                    FROM town_registrations tr
                    JOIN students s ON tr.student_id = s.id
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN town_blocks tb ON tr.block_id = tb.id
                    LEFT JOIN parent_student_links psl ON s.id = psl.student_id AND psl.verified = 1
                    LEFT JOIN parents p ON psl.parent_id = p.id
                    WHERE tr.town_id = :town_id 
                    AND tr.status = 'active'";
            
            if ($blockId) {
                $sql .= " AND tr.block_id = :block_id";
            }
            
            $sql .= " GROUP BY s.id ORDER BY tb.name, s.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':town_id', $teacher['town_id']);
            if ($blockId) {
                $stmt->bindValue(':block_id', $blockId);
            }
            
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse parent info
            foreach ($students as &$student) {
                $parents = [];
                if ($student['parents_info']) {
                    $parentsList = explode(';;', $student['parents_info']);
                    foreach ($parentsList as $parentData) {
                        $parts = explode('|', $parentData);
                        if (count($parts) === 7) {
                            $parents[] = [
                                'id' => $parts[0],
                                'first_name' => $parts[1],
                                'last_name' => $parts[2],
                                'email' => $parts[3],
                                'phone' => $parts[4],
                                'address' => $parts[5],
                                'relationship' => $parts[6]
                            ];
                        }
                    }
                }
                $student['parents'] = $parents;
                unset($student['parents_info']);
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'students' => $students,
                'count' => count($students)
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
     * Get student details with parent info (Town Master)
     */
    public function getStudentWithParents(Request $request, Response $response, array $args)
    {
        try {
            $user = $request->getAttribute('user');
            $studentId = $args['id'];
            
            // Verify town master has access to this student
            $stmt = $this->db->prepare("
                SELECT tr.id 
                FROM town_registrations tr
                JOIN teachers t ON tr.town_id = t.town_id
                WHERE t.id = :teacher_id 
                AND tr.student_id = :student_id
                AND t.is_town_master = 1
                AND tr.status = 'active'
            ");
            $stmt->execute([':teacher_id' => $user->id, ':student_id' => $studentId]);
            
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Access denied: Student not in your town'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            
            // Get student details
            $sql = "SELECT s.*, c.class_name, c.section,
                           tb.name as block_name
                    FROM students s
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN town_registrations tr ON s.id = tr.student_id AND tr.status = 'active'
                    LEFT JOIN town_blocks tb ON tr.block_id = tb.id
                    WHERE s.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            // Get parents/guardians
            $sql = "SELECT p.*, psl.relationship, psl.verified, psl.is_primary_contact
                    FROM parent_student_links psl
                    JOIN parents p ON psl.parent_id = p.id
                    WHERE psl.student_id = :student_id
                    ORDER BY psl.is_primary_contact DESC, psl.verified DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $studentId]);
            $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $student['parents'] = $parents;
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'student' => $student
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch student details: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
