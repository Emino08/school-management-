<?php

namespace App\Controllers;

use App\Models\Student;
use App\Models\ParentNotification;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SuspensionController
{
    private $studentModel;
    private $notificationModel;

    public function __construct()
    {
        $this->studentModel = new Student();
        $this->notificationModel = new ParentNotification();
    }

    public function suspendStudent(Request $request, Response $response)
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            $issuedBy = $request->getAttribute('user_id');
            $data = $request->getParsedBody();

            $required = ['student_id', 'reason', 'start_date', 'end_date', 'suspension_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => "Field {$field} is required"
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            $db = \App\Config\Database::getInstance()->getConnection();

            // Update student status
            $updateSql = "UPDATE students 
                         SET suspension_status = 'suspended',
                             suspension_reason = :reason,
                             suspension_start_date = :start_date,
                             suspension_end_date = :end_date
                         WHERE id = :student_id AND admin_id = :admin_id";

            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([
                ':reason' => $data['reason'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'],
                ':student_id' => $data['student_id'],
                ':admin_id' => $adminId
            ]);

            // Log suspension
            $logSql = "INSERT INTO student_suspensions 
                       (student_id, admin_id, reason, suspension_type, start_date, end_date, issued_by, status)
                       VALUES (:student_id, :admin_id, :reason, :suspension_type, :start_date, :end_date, :issued_by, 'active')";

            $logStmt = $db->prepare($logSql);
            $logStmt->execute([
                ':student_id' => $data['student_id'],
                ':admin_id' => $adminId,
                ':reason' => $data['reason'],
                ':suspension_type' => $data['suspension_type'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'],
                ':issued_by' => $issuedBy
            ]);

            // Notify parents
            $this->notificationModel->notifySuspension(
                $data['student_id'],
                $adminId,
                $data['reason'],
                $data['start_date'],
                $data['end_date']
            );

            // Update notification flag
            $db->prepare("UPDATE student_suspensions SET parent_notified = 1, notified_at = NOW() WHERE student_id = :sid AND status = 'active'")
               ->execute([':sid' => $data['student_id']]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student suspended and parents notified'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Suspension failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function liftSuspension(Request $request, Response $response, $args)
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            $studentId = $args['student_id'];

            $db = \App\Config\Database::getInstance()->getConnection();

            // Update student status
            $updateSql = "UPDATE students 
                         SET suspension_status = 'active',
                             suspension_reason = NULL,
                             suspension_start_date = NULL,
                             suspension_end_date = NULL
                         WHERE id = :student_id AND admin_id = :admin_id";

            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([
                ':student_id' => $studentId,
                ':admin_id' => $adminId
            ]);

            // Complete suspension record
            $logSql = "UPDATE student_suspensions 
                      SET status = 'completed'
                      WHERE student_id = :student_id AND status = 'active'";

            $logStmt = $db->prepare($logSql);
            $logStmt->execute([':student_id' => $studentId]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Suspension lifted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to lift suspension: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getSuspensionHistory(Request $request, Response $response, $args)
    {
        try {
            $studentId = $args['student_id'];

            $db = \App\Config\Database::getInstance()->getConnection();
            $sql = "SELECT ss.*, a.school_name as issued_by_school
                    FROM student_suspensions ss
                    LEFT JOIN admins a ON ss.admin_id = a.id
                    WHERE ss.student_id = :student_id
                    ORDER BY ss.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute([':student_id' => $studentId]);
            $history = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'success' => true,
                'suspensions' => $history
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch suspension history: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getActiveSuspensions(Request $request, Response $response)
    {
        try {
            $adminId = $request->getAttribute('admin_id');

            $db = \App\Config\Database::getInstance()->getConnection();
            $sql = "SELECT s.*, ss.reason, ss.start_date, ss.end_date, ss.suspension_type,
                           c.class_name, c.section
                    FROM students s
                    JOIN student_suspensions ss ON s.id = ss.student_id
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    WHERE s.admin_id = :admin_id 
                    AND s.suspension_status = 'suspended'
                    AND ss.status = 'active'
                    ORDER BY ss.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute([':admin_id' => $adminId]);
            $suspensions = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'success' => true,
                'suspensions' => $suspensions
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch active suspensions: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
