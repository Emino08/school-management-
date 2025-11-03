<?php

namespace App\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Admin;
use App\Models\FinanceUser;
use App\Models\ExamOfficer;
use App\Models\AcademicYear;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserManagementController
{
    private $studentModel;
    private $teacherModel;
    private $adminModel;
    private $financeUserModel;
    private $examOfficerModel;
    private $academicYearModel;

    public function __construct()
    {
        $this->studentModel = new Student();
        $this->teacherModel = new Teacher();
        $this->adminModel = new Admin();
        $this->financeUserModel = new FinanceUser();
        $this->examOfficerModel = new ExamOfficer();
        $this->academicYearModel = new AcademicYear();
    }

    /**
     * Get all users with filters and pagination
     */
    public function getAllUsers(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $adminId = $user->id;
            $query = $request->getQueryParams();
            $userType = $query['user_type'] ?? 'all';
            $search = trim($query['search'] ?? '');
            $status = $query['status'] ?? null; // 'active'|'inactive' or 1/0
            $page = isset($query['page']) ? (int)$query['page'] : 1;
            $limit = isset($query['limit']) ? (int)$query['limit'] : 20;
            $offset = ($page - 1) * $limit;

            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $academicYearId = $currentYear['id'] ?? null;

            $result = [];

            switch ($userType) {
                case 'student':
                    $students = $this->studentModel->getStudentsWithEnrollment($adminId, $academicYearId);
                    if ($search !== '') {
                        $students = array_values(array_filter($students, function ($s) use ($search) {
                            $hay = strtolower(($s['name'] ?? '') . ' ' . ($s['email'] ?? '') . ' ' . ($s['id_number'] ?? ''));
                            return strpos($hay, strtolower($search)) !== false;
                        }));
                    }
                    $total = count($students);
                    $paged = array_slice($students, $offset, $limit);
                    $result = [
                        'users' => $paged,
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'type' => 'student'
                    ];
                    break;

                case 'teacher':
                    $teachers = $this->teacherModel->getTeachersWithSubjects($adminId, $academicYearId, false);
                    if ($search !== '') {
                        $teachers = array_values(array_filter($teachers, function ($t) use ($search) {
                            $hay = strtolower(($t['name'] ?? '') . ' ' . ($t['email'] ?? '') . ' ' . ($t['phone'] ?? ''));
                            return strpos($hay, strtolower($search)) !== false;
                        }));
                    }
                    // Optional status filtering based on is_deleted
                    if ($status !== null) {
                        $active = in_array($status, ['1', 1, 'active'], true);
                        $teachers = array_values(array_filter($teachers, function ($t) use ($active) {
                            $isDeleted = (int)($t['is_deleted'] ?? 0) === 1;
                            return $active ? !$isDeleted : $isDeleted;
                        }));
                    }
                    $total = count($teachers);
                    $paged = array_slice($teachers, $offset, $limit);
                    $result = [
                        'users' => $paged,
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'type' => 'teacher'
                    ];
                    break;

                case 'finance':
                    $filters = [
                        'search' => $search,
                        'limit' => $limit,
                        'offset' => $offset
                    ];
                    if ($status !== null) {
                        $filters['is_active'] = in_array($status, ['1', 1, 'active'], true) ? 1 : 0;
                    }
                    $financeUsers = $this->financeUserModel->getAllByAdmin($adminId, $filters) ?: [];
                    $total = $this->financeUserModel->getCountByAdmin($adminId, $filters);
                    $result = [
                        'users' => $financeUsers,
                        'total' => (int)$total,
                        'page' => $page,
                        'limit' => $limit,
                        'type' => 'finance'
                    ];
                    break;

                case 'all':
                    $students = $this->studentModel->getStudentsWithEnrollment($adminId, $academicYearId);
                    $teachers = $this->teacherModel->getTeachersWithSubjects($adminId, $academicYearId, false);
                    $financeUsers = $this->financeUserModel->getAllByAdmin($adminId, ['limit' => 5, 'offset' => 0]) ?: [];
                    $result = [
                        'students' => [
                            'users' => array_slice($students, 0, 5),
                            'total' => count($students)
                        ],
                        'teachers' => [
                            'users' => array_slice($teachers, 0, 5),
                            'total' => count($teachers)
                        ],
                        'finance' => [
                            'users' => $financeUsers,
                            'total' => (int)$this->financeUserModel->getCountByAdmin($adminId, [])
                        ],
                        'type' => 'all'
                    ];
                    break;

                default:
                    $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid user type']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $response->getBody()->write(json_encode(['success' => true, 'data' => $result, 'message' => 'Users fetched successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch users: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Create a new user
     */
    public function createUser(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $adminId = $user->id;
            $body = $request->getParsedBody() ?? [];
            $userType = $body['user_type'] ?? null;

            if (!$userType) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'User type is required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validate common fields
            $name = trim($body['name'] ?? '');
            $email = trim($body['email'] ?? '');
            $password = $body['password'] ?? '';

            if (empty($name)) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Name is required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if (empty($password) || strlen($password) < 6) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $result = null;

            switch ($userType) {
                case 'student':
                    if (empty($email) && empty($body['id_number'])) {
                        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Email or ID number is required']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }

                    $studentData = [
                        'admin_id' => $adminId,
                        'name' => $name,
                        'id_number' => $body['id_number'] ?? null,
                        'email' => $email,
                        'password' => $password,
                        'date_of_birth' => $body['date_of_birth'] ?? null,
                        'gender' => $body['gender'] ?? null,
                        'address' => $body['address'] ?? null,
                        'phone' => $body['phone'] ?? null,
                        'parent_name' => $body['parent_name'] ?? null,
                        'parent_phone' => $body['parent_phone'] ?? null,
                        'class_id' => $body['class_id'] ?? null
                    ];

                    $newId = $this->studentModel->createStudent($studentData);
                    $result = ['id' => $newId] + $studentData;
                    break;

                case 'teacher':
                    if (empty($email)) {
                        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Email is required for teachers']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }

                    // Check if email already exists
                    if ($this->teacherModel->findByEmail($email)) {
                        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Email already exists']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }

                    $teacherData = [
                        'admin_id' => $adminId,
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'phone' => $body['phone'] ?? null,
                        'address' => $body['address'] ?? null,
                        'qualification' => $body['qualification'] ?? null,
                        'experience_years' => $body['experience_years'] ?? 0,
                        'is_exam_officer' => $body['is_exam_officer'] ?? 0,
                        'can_approve_results' => $body['can_approve_results'] ?? 0
                    ];

                    $newId = $this->teacherModel->createTeacher($teacherData);
                    $result = ['id' => $newId] + $teacherData;

                    // If teacher is set as exam officer, create exam officer record
                    if ($newId && !empty($body['is_exam_officer'])) {
                        $this->createExamOfficerRecord($email, $adminId);
                    }
                    break;

                case 'finance':
                    if (empty($email)) {
                        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Email is required for finance users']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }

                    // Check if email already exists
                    if ($this->financeUserModel->findByEmail($email)) {
                        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Email already exists']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }

                    $financeData = [
                        'admin_id' => $adminId,
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'phone' => $body['phone'] ?? null,
                        'address' => $body['address'] ?? null,
                        'can_approve_payments' => $body['can_approve_payments'] ?? 1,
                        'can_generate_reports' => $body['can_generate_reports'] ?? 1,
                        'can_manage_fees' => $body['can_manage_fees'] ?? 1
                    ];

                    $result = $this->financeUserModel->createFinanceUser($financeData);
                    break;

                default:
                    $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid user type']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if ($result) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => ucfirst($userType) . ' created successfully',
                    'data' => $result
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            }

            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to create user']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, Response $response, $args)
    {
        try {
            $user = $request->getAttribute('user');
            $adminId = $user->id;
            $id = $args['id'] ?? null;
            $body = $request->getParsedBody() ?? [];
            $userType = $body['user_type'] ?? null;

            if (!$userType) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'User type is required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $result = false;

            switch ($userType) {
                case 'student':
                    $result = $this->studentModel->update($id, $body);
                    break;

                case 'teacher':
                    $result = $this->teacherModel->update($id, $body);

                    // Handle exam officer status
                    if (isset($body['is_exam_officer'])) {
                        $teacher = $this->teacherModel->findById($id);
                        if ($teacher) {
                            if ($body['is_exam_officer']) {
                                $this->createExamOfficerRecord($teacher['email'], $adminId);
                            } else {
                                $this->removeExamOfficerRecord($teacher['email']);
                            }
                        }
                    }
                    break;

                case 'finance':
                    $result = $this->financeUserModel->updateFinanceUser($id, $adminId, $body);
                    break;

                default:
                    $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid user type']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if ($result) {
                $response->getBody()->write(json_encode(['success' => true, 'message' => ucfirst($userType) . ' updated successfully', 'id' => $id]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to update user']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(Request $request, Response $response, $args)
    {
        try {
            $user = $request->getAttribute('user');
            $adminId = $user->id;
            $id = $args['id'] ?? null;
            $query = $request->getQueryParams();
            $body = $request->getParsedBody() ?? [];
            $userType = $query['user_type'] ?? $body['user_type'] ?? null;

            if (!$userType) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'User type is required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $result = false;

            switch ($userType) {
                case 'student':
                    $result = $this->studentModel->delete($id);
                    break;

                case 'teacher':
                    $teacher = $this->teacherModel->findById($id);
                    $result = $this->teacherModel->softDelete($id);
                    // Remove exam officer status if exists
                    if ($teacher && $result) {
                        $this->removeExamOfficerRecord($teacher['email']);
                    }
                    break;

                case 'finance':
                    $result = $this->financeUserModel->deleteFinanceUser($id, $adminId);
                    break;

                default:
                    $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid user type']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if ($result) {
                $response->getBody()->write(json_encode(['success' => true, 'message' => ucfirst($userType) . ' deleted successfully']));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to delete user']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Toggle exam officer status for teacher
     */
    public function toggleExamOfficer(Request $request, Response $response, $args)
    {
        try {
            $user = $request->getAttribute('user');
            $adminId = $user->id;
            $teacherId = $args['id'] ?? null;
            $teacher = $this->teacherModel->findById($teacherId);

            if (!$teacher || $teacher['admin_id'] != $adminId) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Teacher not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $examOfficer = $this->examOfficerModel->findByEmail($teacher['email']);

            if ($examOfficer) {
                // Toggle active status
                if ($examOfficer['is_active']) {
                    $this->examOfficerModel->deactivate($examOfficer['id']);
                    $status = 'deactivated';
                } else {
                    $this->examOfficerModel->activate($examOfficer['id']);
                    $status = 'activated';
                }
            } else {
                // Create new exam officer record
                $this->createExamOfficerRecord($teacher['email'], $adminId);
                $status = 'activated';
            }

            // Update teacher record
            $this->teacherModel->update($teacherId, [
                'is_exam_officer' => ($status === 'activated') ? 1 : 0,
                'can_approve_results' => ($status === 'activated') ? 1 : 0
            ]);

            $response->getBody()->write(json_encode(['success' => true, 'status' => $status, 'message' => "Exam officer status $status successfully"]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to toggle exam officer: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $adminId = $user->id;

            $stats = [
                'total_students' => $this->studentModel->count(['admin_id' => $adminId]),
                'total_teachers' => $this->teacherModel->count(['admin_id' => $adminId]),
                'total_finance_users' => $this->financeUserModel->getCountByAdmin($adminId, []),
                'active_teachers' => $this->teacherModel->count(['admin_id' => $adminId, 'is_deleted' => 0]),
                'exam_officers' => count($this->examOfficerModel->findByAdmin($adminId))
            ];

            $response->getBody()->write(json_encode(['success' => true, 'stats' => $stats, 'message' => 'User statistics fetched successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch statistics: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Bulk user operations
     */
    public function bulkOperation(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $adminId = $user->id;
            $body = $request->getParsedBody() ?? [];
            $operation = $body['operation'] ?? null;
            $userType = $body['user_type'] ?? null;
            $userIds = $body['user_ids'] ?? [];

            if (!$operation || !$userType || empty($userIds)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Operation, user type, and user IDs are required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $successCount = 0;
            $failCount = 0;

            foreach ($userIds as $userId) {
                try {
                    switch ($operation) {
                        case 'delete':
                            $result = $this->deleteUserById($userId, $userType, $adminId);
                            break;
                        case 'activate':
                            $result = $this->toggleUserStatus($userId, $userType, $adminId, true);
                            break;
                        case 'deactivate':
                            $result = $this->toggleUserStatus($userId, $userType, $adminId, false);
                            break;
                        default:
                            continue 2;
                    }

                    if ($result) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    error_log("Bulk operation failed for user $userId: " . $e->getMessage());
                }
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'message' => "Bulk operation completed: $successCount succeeded, $failCount failed"
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Bulk operation failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Helper methods

    private function createExamOfficerRecord($email, $adminId)
    {
        try {
            $examOfficer = $this->examOfficerModel->findByEmail($email);
            if (!$examOfficer) {
                $this->examOfficerModel->create([
                    'email' => $email,
                    'admin_id' => $adminId,
                    'is_active' => 1
                ]);
            } else {
                $this->examOfficerModel->activate($examOfficer['id']);
            }
        } catch (\Exception $e) {
            error_log("Error creating exam officer record: " . $e->getMessage());
        }
    }

    private function removeExamOfficerRecord($email)
    {
        try {
            $examOfficer = $this->examOfficerModel->findByEmail($email);
            if ($examOfficer) {
                $this->examOfficerModel->deactivate($examOfficer['id']);
            }
        } catch (\Exception $e) {
            error_log("Error removing exam officer record: " . $e->getMessage());
        }
    }

    private function deleteUserById($userId, $userType, $adminId)
    {
        switch ($userType) {
            case 'student':
                return $this->studentModel->delete($userId);
            case 'teacher':
                return $this->teacherModel->softDelete($userId);
            case 'finance':
                return $this->financeUserModel->deleteFinanceUser($userId, $adminId);
            default:
                return false;
        }
    }

    private function toggleUserStatus($userId, $userType, $adminId, $active)
    {
        // Implement status toggle logic based on user type
        switch ($userType) {
            case 'teacher':
                return $this->teacherModel->update($userId, ['is_deleted' => !$active]);
            case 'finance':
                return $this->financeUserModel->updateFinanceUser($userId, $adminId, ['is_active' => $active]);
            default:
                return false;
        }
    }

    // Note: activity logging can be implemented via a dedicated service if needed
}
