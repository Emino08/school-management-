<?php

namespace App\Controllers;

use App\Traits\LogsActivity;

use App\Models\Admin;
use App\Models\ParentUser;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Notice;
use App\Models\AcademicYear;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ParentController
{
    use LogsActivity;

    private $parentModel;
    private $studentModel;
    private $notificationModel;
    private $attendanceModel;
    private $resultModel;
    private $noticeModel;
    private $academicYearModel;
    private $adminModel;

    public function __construct()
    {
        $this->parentModel = new ParentUser();
        $this->studentModel = new Student();
        $this->notificationModel = new ParentNotification();
        $this->attendanceModel = new Attendance();
        $this->resultModel = new ExamResult();
        $this->noticeModel = new Notice();
        $this->academicYearModel = new AcademicYear();
        $this->adminModel = new Admin();
    }

    public function register(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody();

            // Validate required fields
            $required = ['name', 'email', 'password', 'phone'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => "Field {$field} is required"
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Check if email already exists
            $existingParent = $this->parentModel->findByEmail($data['email']);
            if ($existingParent) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email already registered'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $adminId = isset($data['admin_id']) ? (int) $data['admin_id'] : null;
            $admin = null;
            if ($adminId) {
                $admin = $this->adminModel->findById($adminId);
            }

            if (!$admin) {
                $admin = $this->adminModel->getFirstAdmin();
                if (!$admin) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'No admin account available. Ask the school to register an admin first.'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
                }
            }

            $adminId = (int) $admin['id'];

            // Create parent
            $parentData = [
                'admin_id' => $adminId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'relationship' => $data['relationship'] ?? 'mother',
                'notification_preference' => $data['notification_preference'] ?? 'both'
            ];

            $parentId = $this->parentModel->createParent($parentData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Parent account created successfully',
                'parent_id' => $parentId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function login(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody();

            if (empty($data['email']) || empty($data['password'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email and password are required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $parent = $this->parentModel->findByEmail($data['email']);

            if (!$parent || !$this->parentModel->verifyPassword($data['password'], $parent['password'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            // Generate JWT token
            $secret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
            $payload = [
                'id' => $parent['id'],
                'email' => $parent['email'],
                'role' => 'parent',
                'admin_id' => $parent['admin_id'],
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60) // 7 days
            ];

            $token = JWT::encode($payload, $secret, 'HS256');

            // Get children count
            $children = $this->parentModel->getChildren($parent['id']);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'parent' => [
                    'id' => $parent['id'],
                    'name' => $parent['name'],
                    'email' => $parent['email'],
                    'phone' => $parent['phone'],
                    'relationship' => $parent['relationship'],
                    'admin_id' => $parent['admin_id'],
                    'children_count' => count($children)
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function verifyAndLinkChild(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $data = $request->getParsedBody();

            // Debug logging
            error_log("Parent verification - Parent ID: " . $parentId);
            error_log("Parent verification - Data: " . json_encode($data));

            if (empty($data['student_id']) || empty($data['date_of_birth'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student ID and date of birth are required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Verify the relationship
            $student = $this->parentModel->verifyChildRelationship(
                $parentId,
                $data['student_id'],
                $data['date_of_birth']
            );

            // Debug logging
            error_log("Parent verification - Student found: " . ($student ? json_encode($student) : 'NULL'));

            if (!$student) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid student ID or date of birth'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Link the child
            $relationship = $data['relationship'] ?? 'guardian';
            $this->parentModel->linkChild($parentId, $student['id'], $student['admin_id'], $relationship);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Child linked successfully',
                'student' => [
                    'id' => $student['id'],
                    'name' => $student['name']
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getChildren(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $children = $this->parentModel->getChildren($parentId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'children' => $children
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch children: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getChildAttendance(Request $request, Response $response, $args)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $studentId = $args['student_id'];

            // Verify parent has access to this child
            $children = $this->parentModel->getChildren($parentId);
            $hasAccess = false;
            foreach ($children as $child) {
                if ($child['id'] == $studentId) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access to student data'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Get student's admin_id and current academic year
            $student = $this->studentModel->findById($studentId);
            $adminId = $student['admin_id'];
            
            // Get current academic year
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $academicYearId = $currentYear ? $currentYear['id'] : null;

            // Get attendance records
            if ($academicYearId) {
                $attendance = $this->attendanceModel->getStudentAttendance($studentId, $academicYearId);
            } else {
                $attendance = [];
            }

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

    public function getChildResults(Request $request, Response $response, $args)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $studentId = $args['student_id'];

            // Verify access
            $children = $this->parentModel->getChildren($parentId);
            $hasAccess = false;
            foreach ($children as $child) {
                if ($child['id'] == $studentId) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access to student data'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Get exam results
            $results = $this->resultModel->getStudentResults($studentId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'results' => $results
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getNotices(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $adminId = $request->getAttribute('admin_id');

            // Get notices for the school
            $notices = $this->noticeModel->findAll(['admin_id' => $adminId], 'created_at DESC');

            $response->getBody()->write(json_encode([
                'success' => true,
                'notices' => $notices
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch notices: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getNotifications(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $queryParams = $request->getQueryParams();
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50;
            $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0;

            $notifications = $this->parentModel->getNotifications($parentId, $limit, $offset);
            $unreadCount = $this->parentModel->getUnreadNotificationsCount($parentId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch notifications: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function markNotificationRead(Request $request, Response $response, $args)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $notificationId = $args['id'];

            $this->parentModel->markNotificationAsRead($notificationId, $parentId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update notification: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createCommunication(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $adminId = $request->getAttribute('admin_id');
            $data = $request->getParsedBody();

            $required = ['recipient_type', 'subject', 'message', 'type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => "Field {$field} is required"
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            $communicationData = [
                'parent_id' => $parentId,
                'admin_id' => $adminId,
                'student_id' => $data['student_id'] ?? null,
                'recipient_type' => $data['recipient_type'],
                'recipient_id' => $data['recipient_id'] ?? null,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'type' => $data['type'],
                'priority' => $data['priority'] ?? 'medium'
            ];

            $db = \App\Config\Database::getInstance()->getConnection();
            $sql = "INSERT INTO parent_communications 
                    (parent_id, admin_id, student_id, recipient_type, recipient_id, subject, message, type, priority)
                    VALUES 
                    (:parent_id, :admin_id, :student_id, :recipient_type, :recipient_id, :subject, :message, :type, :priority)";

            $stmt = $db->prepare($sql);
            $stmt->execute($communicationData);
            $communicationId = $db->lastInsertId();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Communication sent successfully',
                'communication_id' => $communicationId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to send communication: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getCommunications(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $queryParams = $request->getQueryParams();
            $status = $queryParams['status'] ?? null;

            $communications = $this->parentModel->getCommunications($parentId, $status);

            $response->getBody()->write(json_encode([
                'success' => true,
                'communications' => $communications
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch communications: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getCommunicationDetails(Request $request, Response $response, $args)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $communicationId = $args['id'];

            $communication = $this->parentModel->getCommunicationWithResponses($communicationId, $parentId);

            if (!$communication) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Communication not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Mark as read if it's pending
            if ($communication['status'] === 'pending') {
                $db = \App\Config\Database::getInstance()->getConnection();
                $sql = "UPDATE parent_communications SET status = 'read' WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':id' => $communicationId]);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'communication' => $communication
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch communication: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getProfile(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $parent = $this->parentModel->findById($parentId);

            if (!$parent) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Parent not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            unset($parent['password']);
            
            // Ensure status is 'active' if not set or if it's 'suspended' incorrectly
            if (!isset($parent['status']) || empty($parent['status'])) {
                $parent['status'] = 'active';
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'parent' => $parent
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch profile: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateProfile(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $data = $request->getParsedBody();

            $updateData = [];
            $allowedFields = ['name', 'phone', 'address', 'notification_preference'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No valid fields to update'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $this->parentModel->update($parentId, $updateData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Add medical record for linked child
     * Parents can add basic medical information for their children
     */
    public function addMedicalRecord(Request $request, Response $response)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $data = $request->getParsedBody();

            // Validate required fields
            $required = ['student_id', 'record_type', 'description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Verify student belongs to this parent
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT s.id, s.admin_id, s.first_name, s.last_name 
                FROM students s
                INNER JOIN student_parents sp ON s.id = sp.student_id
                WHERE sp.parent_id = :parent_id AND s.id = :student_id
            ");
            $stmt->execute([
                ':parent_id' => $parentId,
                ':student_id' => $data['student_id']
            ]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$student) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student not linked to your account or not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Create medical record
            $medicalRecordModel = new \App\Models\MedicalRecord();
            
            $recordData = [
                'student_id' => $data['student_id'],
                'admin_id' => $student['admin_id'],
                'medical_staff_id' => null, // Parent-added records don't have staff ID
                'parent_id' => $parentId,
                'added_by' => 'parent',
                'added_by_parent' => 1,
                'can_edit_by_parent' => 1, // Parents can update their own records
                'record_type' => $data['record_type'], // e.g., 'allergy', 'illness', 'injury', 'vaccination'
                'diagnosis' => $data['description'],
                'symptoms' => $data['symptoms'] ?? null,
                'treatment' => $data['treatment'] ?? null,
                'medication' => $data['medication'] ?? null,
                'notes' => $data['notes'] ?? 'Added by parent',
                'severity' => $data['severity'] ?? 'low',
                'status' => 'active', // Use valid value
                'date_reported' => date('Y-m-d'),
                'next_checkup_date' => $data['next_checkup_date'] ?? null
            ];

            $recordId = $medicalRecordModel->create($recordData);

            // Log activity
            $this->logActivity(
                $request,
                'create',
                "Parent added medical record for {$student['first_name']} {$student['last_name']} - {$data['record_type']}",
                'parent',
                $parentId,
                ['student_id' => $data['student_id'], 'record_type' => $data['record_type']]
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Medical record added successfully',
                'record_id' => $recordId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to add medical record: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get medical records for linked children
     */
    public function getMedicalRecords(Request $request, Response $response, array $args)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $studentId = $args['student_id'] ?? null;

            $db = \App\Config\Database::getInstance()->getConnection();
            
            if ($studentId) {
                // Verify student belongs to this parent
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count
                    FROM student_parents
                    WHERE parent_id = :parent_id AND student_id = :student_id
                ");
                $stmt->execute([
                    ':parent_id' => $parentId,
                    ':student_id' => $studentId
                ]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($result['count'] == 0) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Student not linked to your account'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
                }

                // Get medical records for specific student
                $stmt = $db->prepare("
                    SELECT mr.*, 
                           ms.name as staff_name,
                           s.first_name, s.last_name
                    FROM medical_records mr
                    LEFT JOIN medical_staff ms ON mr.medical_staff_id = ms.id
                    INNER JOIN students s ON mr.student_id = s.id
                    WHERE mr.student_id = :student_id
                    ORDER BY mr.created_at DESC
                ");
                $stmt->execute([':student_id' => $studentId]);
            } else {
                // Get medical records for all linked children
                $stmt = $db->prepare("
                    SELECT mr.*, 
                           ms.name as staff_name,
                           s.first_name, s.last_name
                    FROM medical_records mr
                    LEFT JOIN medical_staff ms ON mr.medical_staff_id = ms.id
                    INNER JOIN students s ON mr.student_id = s.id
                    INNER JOIN student_parents sp ON s.id = sp.student_id
                    WHERE sp.parent_id = :parent_id
                    ORDER BY mr.created_at DESC
                ");
                $stmt->execute([':parent_id' => $parentId]);
            }

            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'medical_records' => $records,
                'count' => count($records),
                'can_add' => true, // Parents can always add new records
                'can_update' => true, // Parents can update their own records
                'can_delete' => false // Parents cannot delete records
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch medical records: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update medical record (parent can only update their own records)
     */
    public function updateMedicalRecord(Request $request, Response $response, array $args)
    {
        try {
            $parentId = $request->getAttribute('user_id');
            $recordId = $args['id'];
            $data = $request->getParsedBody();

            $db = \App\Config\Database::getInstance()->getConnection();
            
            // Verify this record was added by this parent
            $stmt = $db->prepare("
                SELECT mr.* 
                FROM medical_records mr
                WHERE mr.id = :record_id AND mr.parent_id = :parent_id AND mr.added_by = 'parent'
            ");
            $stmt->execute([
                ':record_id' => $recordId,
                ':parent_id' => $parentId
            ]);
            $record = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$record) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Record not found or you do not have permission to update it'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Build update query
            $updateFields = [];
            $params = [':record_id' => $recordId];

            if (isset($data['record_type'])) {
                $updateFields[] = 'record_type = :record_type';
                $params[':record_type'] = $data['record_type'];
            }
            if (isset($data['description'])) {
                $updateFields[] = 'diagnosis = :description';
                $params[':description'] = $data['description'];
            }
            if (isset($data['symptoms'])) {
                $updateFields[] = 'symptoms = :symptoms';
                $params[':symptoms'] = $data['symptoms'];
            }
            if (isset($data['treatment'])) {
                $updateFields[] = 'treatment = :treatment';
                $params[':treatment'] = $data['treatment'];
            }
            if (isset($data['medication'])) {
                $updateFields[] = 'medication = :medication';
                $params[':medication'] = $data['medication'];
            }
            if (isset($data['severity'])) {
                $updateFields[] = 'severity = :severity';
                $params[':severity'] = $data['severity'];
            }
            if (isset($data['notes'])) {
                $updateFields[] = 'notes = :notes';
                $params[':notes'] = $data['notes'];
            }
            if (isset($data['next_checkup_date'])) {
                $updateFields[] = 'next_checkup_date = :next_checkup_date';
                $params[':next_checkup_date'] = $data['next_checkup_date'];
            }

            if (empty($updateFields)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No fields to update'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $sql = "UPDATE medical_records SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :record_id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Log activity
            $this->logActivity(
                $request,
                'update',
                "Parent updated medical record #{$recordId}",
                'parent',
                $parentId,
                ['record_id' => $recordId]
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Medical record updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update medical record: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

