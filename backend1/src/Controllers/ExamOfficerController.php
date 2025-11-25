<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ExamOfficer;
use App\Models\ResultModel;
use App\Models\Teacher;
use App\Models\GradeUpdateRequest;
use App\Utils\Validator;
use App\Traits\LogsActivity;
use Firebase\JWT\JWT;

class ExamOfficerController
{
    use LogsActivity;

    private $examOfficerModel;
    private $resultModel;
    private $teacherModel;
    private $gradeUpdateModel;

    public function __construct()
    {
        $this->examOfficerModel = new ExamOfficer();
        $this->resultModel = new ResultModel();
        $this->teacherModel = new Teacher();
        $this->gradeUpdateModel = new GradeUpdateRequest();
    }

    // Exam Officer Login
    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $errors = Validator::validate($data, ['email' => 'required', 'password' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $officer = $this->examOfficerModel->findByEmail($data['email']);

            if (!$officer || !password_verify($data['password'], $officer['password'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            if (!$officer['is_active']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact the administrator.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Generate JWT token
            $payload = [
                'id' => $officer['id'],
                'role' => 'ExamOfficer',
                'email' => $officer['email'],
                'admin_id' => $officer['admin_id'],
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60) // 24 hours
            ];

            $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'officer' => [
                    'id' => $officer['id'],
                    'name' => $officer['name'],
                    'email' => $officer['email'],
                    'can_approve_results' => $officer['can_approve_results']
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

    // Create Exam Officer (Admin only)
    public function create(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $rules = [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'
        ];
        if (array_key_exists('can_approve_results', $data) && $data['can_approve_results'] !== '') {
            $rules['can_approve_results'] = 'boolean';
        }
        if (array_key_exists('is_active', $data) && $data['is_active'] !== '') {
            $rules['is_active'] = 'boolean';
        }
        $errors = Validator::validate($data, $rules);

        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Check if email already exists
            $existing = $this->examOfficerModel->findByEmail($data['email']);
            if ($existing) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email already exists'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $officerData = Validator::sanitize([
                'admin_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'phone' => $data['phone'] ?? null,
                'can_approve_results' => isset($data['can_approve_results']) ? (filter_var($data['can_approve_results'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : 1,
                'is_active' => true
            ]);

            $officerId = $this->examOfficerModel->create($officerData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Exam officer created successfully',
                'officer_id' => $officerId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create exam officer: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get all exam officers (Admin only)
    public function getAll(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $officers = $this->examOfficerModel->findByAdmin($user->id);

            $response->getBody()->write(json_encode([
                'success' => true,
                'officers' => $officers
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch exam officers: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get pending results for approval
    public function getPendingResults(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $examId = $params['exam_id'] ?? null;
        $subjectId = $params['subject_id'] ?? null;

        try {
            $sql = "SELECT
                        er.*,
                        s.name as student_name,
                        s.id_number as id_number,
                        sub.subject_name,
                        e.exam_name,
                        t.name as teacher_name,
                        c.class_name
                    FROM exam_results er
                    JOIN students s ON er.student_id = s.id
                    JOIN subjects sub ON er.subject_id = sub.id
                    JOIN exams e ON er.exam_id = e.id
                    LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.academic_year_id = e.academic_year_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN teachers t ON er.uploaded_by_teacher_id = t.id
                    WHERE er.approval_status = 'pending'
                    AND s.admin_id = :admin_id";

            $queryParams = [':admin_id' => $user->id];

            if ($examId) {
                $sql .= " AND er.exam_id = :exam_id";
                $queryParams[':exam_id'] = $examId;
            }

            if ($subjectId) {
                $sql .= " AND er.subject_id = :subject_id";
                $queryParams[':subject_id'] = $subjectId;
            }

            $sql .= " ORDER BY er.created_at DESC";

            $stmt = \App\Config\Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($queryParams);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'pending_results' => $results,
                'total' => count($results)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch pending results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Approve result
    public function approveResult(Request $request, Response $response, $args)
    {
        $resultId = $args['resultId'];
        $user = $request->getAttribute('user');

        try {
            // Check if officer has approval rights
            $officer = $this->examOfficerModel->findById($user->id);
            if (!$officer['can_approve_results']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to approve results'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Update result
            $updateData = [
                'approval_status' => 'approved',
                'approved_by_officer_id' => $user->id,
                'approved_at' => date('Y-m-d H:i:s')
            ];

            $this->resultModel->update($resultId, $updateData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Result approved successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to approve result: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Reject result
    public function rejectResult(Request $request, Response $response, $args)
    {
        $resultId = $args['resultId'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['rejection_reason' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Rejection reason is required',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Check if officer has approval rights
            $officer = $this->examOfficerModel->findById($user->id);
            if (!$officer['can_approve_results']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to reject results'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Update result
            $updateData = [
                'approval_status' => 'rejected',
                'approved_by_officer_id' => $user->id,
                'approved_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $data['rejection_reason']
            ];

            $this->resultModel->update($resultId, $updateData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Result rejected successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to reject result: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Bulk approve results
    public function bulkApprove(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['result_ids' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Result IDs are required',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Check if officer has approval rights
            $officer = $this->examOfficerModel->findById($user->id);
            if (!$officer['can_approve_results']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to approve results'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $resultIds = $data['result_ids'];
            $approvedCount = 0;

            foreach ($resultIds as $resultId) {
                $updateData = [
                    'approval_status' => 'approved',
                    'approved_by_officer_id' => $user->id,
                    'approved_at' => date('Y-m-d H:i:s')
                ];

                $this->resultModel->update($resultId, $updateData);
                $approvedCount++;
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Successfully approved $approvedCount results",
                'approved_count' => $approvedCount
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to approve results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get approval statistics
    public function getApprovalStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $academicYearId = $params['academic_year_id'] ?? null;

        try {
            $stats = $this->examOfficerModel->getApprovalStats($user->id, $academicYearId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => $stats
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch stats: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Update exam officer
    public function update(Request $request, Response $response, $args)
    {
        $officerId = $args['id'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        try {
            $updateData = Validator::sanitize($data);

            // Optional boolean validations on update
            $rules = [];
            foreach (['can_approve_results','is_active'] as $b) {
                if (array_key_exists($b, $data) && $data[$b] !== '') {
                    $rules[$b] = 'boolean';
                }
            }
            if (!empty($rules)) {
                $errors = Validator::validate($data, $rules);
                if (!empty($errors)) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $errors
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Don't allow password update through this endpoint
            unset($updateData['password']);

            // Only admin/principal can update can_approve_results and is_active
            $role = strtolower($user->role ?? '');
            if (!in_array($role, ['admin', 'principal'], true)) {
                unset($updateData['can_approve_results']);
                unset($updateData['is_active']);
            }

            // Normalize booleans to 0/1
            foreach (['can_approve_results','is_active'] as $b) {
                if (array_key_exists($b, $updateData)) {
                    $updateData[$b] = filter_var($updateData[$b], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                }
            }

            $this->examOfficerModel->update($officerId, $updateData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Exam officer updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update exam officer: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Delete exam officer (Admin only)
    public function delete(Request $request, Response $response, $args)
    {
        $officerId = $args['id'];

        try {
            $this->examOfficerModel->delete($officerId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Exam officer deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete exam officer: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // ==================== TEACHER-BASED EXAM OFFICER METHODS ====================

    // Get all pending grade change requests (Exam Officers only)
    public function getPendingGradeRequests(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user || $user->role !== 'Teacher') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized. Only teachers can access this.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $teacher = $this->teacherModel->findById($user->id);
        if (!$teacher || !$teacher['is_exam_officer']) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized. You are not an exam officer.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $params = $request->getQueryParams();
            $status = $params['status'] ?? 'pending';

            $requests = $this->gradeUpdateModel->getRequestsByStatus($status);

            $response->getBody()->write(json_encode([
                'success' => true,
                'requests' => $requests,
                'total' => count($requests)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch requests: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Approve grade change request (Exam Officer)
    public function approveGradeChangeRequest(Request $request, Response $response, $args)
    {
        $requestId = $args['id'];
        $user = $request->getAttribute('user');

        if (!$user || $user->role !== 'Teacher') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $teacher = $this->teacherModel->findById($user->id);
        if (!$teacher || !$teacher['is_exam_officer']) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only exam officers can approve grade changes.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $changeRequest = $this->gradeUpdateModel->findById($requestId);

            if (!$changeRequest) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Grade change request not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($changeRequest['status'] !== 'pending') {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'This request has already been processed'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Update the actual grade in exam_results
            $updateData = [];
            if ($changeRequest['field_to_update'] === 'test_score') {
                $updateData['test_score'] = $changeRequest['new_test_score'];
            } elseif ($changeRequest['field_to_update'] === 'exam_score') {
                $updateData['exam_score'] = $changeRequest['new_exam_score'];
            } elseif ($changeRequest['field_to_update'] === 'marks_obtained') {
                $updateData['marks_obtained'] = $changeRequest['new_score'];
            }

            // Update the result
            if (!empty($updateData)) {
                $this->resultModel->update($changeRequest['result_id'], $updateData);
            }

            // Mark request as approved
            $this->gradeUpdateModel->update($requestId, [
                'status' => 'approved',
                'approved_by_exam_officer' => $user->id,
                'approved_at' => date('Y-m-d H:i:s')
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade change approved successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to approve grade change: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Reject grade change request (Exam Officer)
    public function rejectGradeChangeRequest(Request $request, Response $response, $args)
    {
        $requestId = $args['id'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        if (!$user || $user->role !== 'Teacher') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $teacher = $this->teacherModel->findById($user->id);
        if (!$teacher || !$teacher['is_exam_officer']) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only exam officers can reject grade changes.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

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
            $changeRequest = $this->gradeUpdateModel->findById($requestId);

            if (!$changeRequest) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Grade change request not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($changeRequest['status'] !== 'pending') {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'This request has already been processed'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Mark request as rejected
            $this->gradeUpdateModel->update($requestId, [
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $data['rejection_reason']
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade change rejected'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to reject grade change: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Verify results (Head of Exam Office only)
    public function verifyExamResults(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        if (!$user || $user->role !== 'Teacher') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $teacher = $this->teacherModel->findById($user->id);
        if (!$teacher || !$teacher['is_head_of_exam_office']) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only the Head of Exam Office can verify grades.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $errors = Validator::validate($data, ['result_ids' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $resultIds = $data['result_ids'];

            if (!is_array($resultIds) || empty($resultIds)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'result_ids must be a non-empty array'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $verifiedCount = 0;
            foreach ($resultIds as $resultId) {
                $this->resultModel->update($resultId, [
                    'is_verified' => true,
                    'verified_by' => $user->id,
                    'verified_at' => date('Y-m-d H:i:s')
                ]);
                $verifiedCount++;
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Successfully verified $verifiedCount results",
                'verified_count' => $verifiedCount
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to verify results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Bulk verify all approved results for an exam (Head of Exam Office only)
    public function bulkVerifyExamResults(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $user = $request->getAttribute('user');

        if (!$user || $user->role !== 'Teacher') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $teacher = $this->teacherModel->findById($user->id);
        if (!$teacher || !$teacher['is_head_of_exam_office']) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only the Head of Exam Office can verify grades.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();

            // Verify all approved and non-verified results for this exam
            $sql = "UPDATE exam_results
                    SET is_verified = TRUE,
                        verified_by = :verified_by,
                        verified_at = NOW()
                    WHERE exam_id = :exam_id
                    AND approval_status = 'approved'
                    AND (is_verified = FALSE OR is_verified IS NULL)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':verified_by' => $user->id,
                ':exam_id' => $examId
            ]);

            $verifiedCount = $stmt->rowCount();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Successfully verified $verifiedCount results for this exam",
                'verified_count' => $verifiedCount
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to verify results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get exam officer dashboard statistics
    public function getTeacherExamOfficerStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user || $user->role !== 'Teacher') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $teacher = $this->teacherModel->findById($user->id);
        if (!$teacher || !$teacher['is_exam_officer']) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only exam officers can access this.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();

            // Get pending grade change requests
            $pendingRequests = $this->gradeUpdateModel->getRequestsByStatus('pending');

            // Get results awaiting verification (if head of exam office)
            $awaitingVerification = 0;
            if ($teacher['is_head_of_exam_office']) {
                $sql = "SELECT COUNT(*) as count
                        FROM exam_results
                        WHERE approval_status = 'approved'
                        AND (is_verified = FALSE OR is_verified IS NULL)";
                $stmt = $db->query($sql);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $awaitingVerification = $result['count'];
            }

            // Get total verified results
            $sql = "SELECT COUNT(*) as count FROM exam_results WHERE is_verified = TRUE";
            $stmt = $db->query($sql);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $totalVerified = $result['count'];

            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => [
                    'pending_grade_changes' => count($pendingRequests),
                    'awaiting_verification' => $awaitingVerification,
                    'total_verified' => $totalVerified,
                    'is_head_of_exam_office' => (bool)$teacher['is_head_of_exam_office'],
                    'is_exam_officer' => (bool)$teacher['is_exam_officer']
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}



