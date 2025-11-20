<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use App\Utils\Validator;

class ResultManagementController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getPendingGrades(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $queryParams = $request->getQueryParams();
        $examId = $queryParams['exam_id'] ?? null;
        $subjectId = $queryParams['subject_id'] ?? null;

        try {
            $sql = "SELECT
                        r.id,
                        r.marks_obtained as score,
                        r.created_at as submitted_at,
                        s.name as student_name,
                        s.id as student_id,
                        sub.subject_name,
                        sub.id as subject_id,
                        e.exam_name,
                        e.id as exam_id,
                        t.name as teacher_name,
                        t.id as teacher_id
                    FROM exam_results r
                    INNER JOIN students s ON r.student_id = s.id
                    INNER JOIN exams e ON r.exam_id = e.id
                    INNER JOIN subjects sub ON r.subject_id = sub.id
                    LEFT JOIN teachers t ON r.uploaded_by_teacher_id = t.id
                    WHERE r.approval_status = 'pending'
                    AND s.admin_id = :admin_id";

            $params = [':admin_id' => $user->id];

            if ($examId) {
                $sql .= " AND e.id = :exam_id";
                $params[':exam_id'] = $examId;
            }

            if ($subjectId) {
                $sql .= " AND sub.id = :subject_id";
                $params[':subject_id'] = $subjectId;
            }

            $sql .= " ORDER BY r.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $pendingGrades = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'pending_grades' => $pendingGrades
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch pending grades: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function approveGrade(Request $request, Response $response, $args)
    {
        $resultId = $args['id'];
        $user = $request->getAttribute('user');

        try {
            // Update result status to approved
            $stmt = $this->db->prepare("
                UPDATE exam_results
                SET approval_status = 'approved',
                    approved_by = :approved_by,
                    approved_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $resultId,
                ':approved_by' => $user->id
            ]);

            // Log activity
            $logger = new \App\Utils\ActivityLogger($this->db);
            $logger->logFromRequest(
                $request,
                $user->id,
                'exam_officer',
                'approve',
                "Approved grade for result ID: {$resultId}",
                'result',
                $resultId,
                null,
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade approved successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to approve grade: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function rejectGrade(Request $request, Response $response, $args)
    {
        $resultId = $args['id'];
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        $errors = Validator::validate($data, ['rejection_reason' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Update result status to rejected
            $stmt = $this->db->prepare("
                UPDATE exam_results
                SET approval_status = 'rejected',
                    rejection_reason = :rejection_reason,
                    rejected_by = :rejected_by,
                    rejected_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $resultId,
                ':rejection_reason' => $data['rejection_reason'],
                ':rejected_by' => $user->id
            ]);

            // Log activity
            $logger = new \App\Utils\ActivityLogger($this->db);
            $logger->logFromRequest(
                $request,
                $user->id,
                'exam_officer',
                'reject',
                "Rejected grade for result ID: {$resultId}",
                'result',
                $resultId,
                ['rejection_reason' => $data['rejection_reason']],
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade rejected successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to reject grade: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getUpdateRequests(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $sql = "SELECT
                        ur.id,
                        ur.current_score,
                        ur.new_score,
                        ur.reason,
                        ur.requested_at,
                        s.name as student_name,
                        s.id as student_id,
                        sub.subject_name,
                        sub.id as subject_id,
                        e.exam_name,
                        e.id as exam_id,
                        t.name as teacher_name,
                        t.id as teacher_id
                    FROM grade_update_requests ur
                    INNER JOIN exam_results r ON ur.result_id = r.id
                    INNER JOIN students s ON r.student_id = s.id
                    INNER JOIN exams e ON r.exam_id = e.id
                    INNER JOIN subjects sub ON r.subject_id = sub.id
                    INNER JOIN teachers t ON ur.teacher_id = t.id
                    WHERE ur.status = 'pending'
                    AND s.admin_id = :admin_id
                    ORDER BY ur.requested_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':admin_id' => $user->id]);
            $updateRequests = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'update_requests' => $updateRequests
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch update requests: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function approveUpdateRequest(Request $request, Response $response, $args)
    {
        $requestId = $args['id'];
        $user = $request->getAttribute('user');

        try {
            // Get the update request details
            $stmt = $this->db->prepare("SELECT * FROM grade_update_requests WHERE id = :id");
            $stmt->execute([':id' => $requestId]);
            $updateRequest = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$updateRequest) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Update request not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Update the result with new score
            $stmt = $this->db->prepare("
                UPDATE exam_results
                SET marks_obtained = :new_score,
                    updated_at = NOW()
                WHERE id = :result_id
            ");
            $stmt->execute([
                ':new_score' => $updateRequest['new_score'],
                ':result_id' => $updateRequest['result_id']
            ]);

            // Update request status
            $stmt = $this->db->prepare("
                UPDATE grade_update_requests
                SET status = 'approved',
                    approved_by = :approved_by,
                    approved_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $requestId,
                ':approved_by' => $user->id
            ]);

            // Log activity
            $logger = new \App\Utils\ActivityLogger($this->db);
            $logger->logFromRequest(
                $request,
                $user->id,
                'exam_officer',
                'approve',
                "Approved grade update request ID: {$requestId}",
                'grade_update_request',
                $requestId,
                null,
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Update request approved successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to approve update request: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function rejectUpdateRequest(Request $request, Response $response, $args)
    {
        $requestId = $args['id'];
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        $errors = Validator::validate($data, ['rejection_reason' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Update request status
            $stmt = $this->db->prepare("
                UPDATE grade_update_requests
                SET status = 'rejected',
                    rejection_reason = :rejection_reason,
                    rejected_by = :rejected_by,
                    rejected_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $requestId,
                ':rejection_reason' => $data['rejection_reason'],
                ':rejected_by' => $user->id
            ]);

            // Log activity
            $logger = new \App\Utils\ActivityLogger($this->db);
            $logger->logFromRequest(
                $request,
                $user->id,
                'exam_officer',
                'reject',
                "Rejected grade update request ID: {$requestId}",
                'grade_update_request',
                $requestId,
                ['rejection_reason' => $data['rejection_reason']],
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Update request rejected successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to reject update request: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function requestGradeUpdate(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        $errors = Validator::validate($data, [
            'result_id' => 'required|numeric',
            'new_score' => 'required|numeric',
            'reason' => 'required'
        ]);

        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Get current score
            $stmt = $this->db->prepare("SELECT marks_obtained as score FROM exam_results WHERE id = :id");
            $stmt->execute([':id' => $data['result_id']]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$result) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Result not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Create update request
            $stmt = $this->db->prepare("
                INSERT INTO grade_update_requests
                (result_id, teacher_id, current_score, new_score, reason, status, requested_at)
                VALUES (:result_id, :teacher_id, :current_score, :new_score, :reason, 'pending', NOW())
            ");
            $stmt->execute([
                ':result_id' => $data['result_id'],
                ':teacher_id' => $user->id,
                ':current_score' => $result['score'],
                ':new_score' => $data['new_score'],
                ':reason' => $data['reason']
            ]);

            $requestId = $this->db->lastInsertId();

            // Log activity
            $logger = new \App\Utils\ActivityLogger($this->db);
            $logger->logFromRequest(
                $request,
                $user->id,
                'teacher',
                'create',
                "Requested grade update for result ID: {$data['result_id']}",
                'grade_update_request',
                $requestId,
                null,
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade update request submitted successfully',
                'request_id' => $requestId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to submit update request: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
