<?php

namespace App\Controllers;

use App\Models\ParentUser;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Notice;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ParentController
{
    private $parentModel;
    private $studentModel;
    private $notificationModel;
    private $attendanceModel;
    private $resultModel;
    private $noticeModel;

    public function __construct()
    {
        $this->parentModel = new ParentUser();
        $this->studentModel = new Student();
        $this->notificationModel = new ParentNotification();
        $this->attendanceModel = new Attendance();
        $this->resultModel = new ExamResult();
        $this->noticeModel = new Notice();
    }

    public function register(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody();

            // Validate required fields
            $required = ['name', 'email', 'password', 'phone', 'admin_id'];
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

            // Create parent
            $parentData = [
                'admin_id' => $data['admin_id'],
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
            $this->parentModel->linkChild($parentId, $student['id'], $student['admin_id']);

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

            // Get attendance records
            $attendance = $this->attendanceModel->getStudentAttendance($studentId);

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
}
