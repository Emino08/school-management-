<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\AcademicYear;
use App\Models\GradeUpdateRequest;
use App\Models\ResultModel;
use App\Utils\JWT;
use App\Utils\Validator;

class TeacherController
{
    private $teacherModel;
    private $assignmentModel;
    private $academicYearModel;
    private $gradeUpdateModel;
    private $resultModel;

    public function __construct()
    {
        $this->teacherModel = new Teacher();
        $this->assignmentModel = new TeacherAssignment();
        $this->academicYearModel = new AcademicYear();
        $this->gradeUpdateModel = new GradeUpdateRequest();
        $this->resultModel = new ResultModel();
    }

    public function register(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        // Base validation rules
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6'
        ];

        // Optional numeric validations when provided
        if (isset($data['experience_years']) && $data['experience_years'] !== '') {
            $rules['experience_years'] = 'numeric';
        }
        if (isset($data['class_master_of']) && $data['class_master_of'] !== '') {
            $rules['class_master_of'] = 'numeric';
        }
        // Optional boolean fields when provided
        foreach (['is_exam_officer','isClassMaster','is_class_master','can_approve_results'] as $boolField) {
            if (array_key_exists($boolField, $data) && $data[$boolField] !== '') {
                $rules[$boolField] = isset($rules[$boolField]) ? $rules[$boolField] . '|boolean' : 'boolean';
            }
        }

        $errors = Validator::validate($data, $rules);
        
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $existing = $this->teacherModel->findByEmail($data['email']);
        if ($existing) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Email already exists']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Validate class master assignment (support snake_case and camelCase)
            $isClassMasterIn = !empty($data['is_class_master']) || !empty($data['isClassMaster']);
            $classMasterOfIn = $data['class_master_of'] ?? ($data['teachSclass'] ?? null);
            if ($isClassMasterIn && empty($classMasterOfIn)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Class must be selected when assigning as class master'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Normalize numeric/boolean fields
            $expYears = isset($data['experience_years']) && $data['experience_years'] !== ''
                ? (int)$data['experience_years']
                : null;

            $classMasterOf = null;
            if ($isClassMasterIn) {
                if (!empty($classMasterOfIn) && $classMasterOfIn !== '') {
                    $classMasterOf = (int)$classMasterOfIn;
                }
            }

            // Handle name splitting
            $fullName = $data['name'] ?? '';
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            
            // If first_name/last_name not provided but name is, split it
            if (empty($firstName) && empty($lastName) && !empty($fullName)) {
                $nameParts = explode(' ', trim($fullName), 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
            }
            
            // Build full name if not provided
            if (empty($fullName) && (!empty($firstName) || !empty($lastName))) {
                $fullName = trim($firstName . ' ' . $lastName);
            }

            $teacherData = Validator::sanitize([
                'admin_id' => $user->id,
                'name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'experience_years' => $expYears,
                'is_exam_officer' => (!empty($data['is_exam_officer']) || !empty($data['isExamOfficer'])) ? 1 : 0,
                'can_approve_results' => !empty($data['can_approve_results']) ? 1 : 0,
                'is_town_master' => !empty($data['is_town_master']) ? 1 : 0,
                'is_class_master' => $isClassMasterIn ? 1 : 0,
                'class_master_of' => $classMasterOf
            ]);

            $teacherId = $this->teacherModel->createTeacher($teacherData);

            // Guard: only one class master per class at registration time
            if ($teacherData['is_class_master'] && $teacherData['class_master_of']) {
                $db = \App\Config\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT id, name FROM teachers WHERE admin_id = :admin_id AND is_class_master = 1 AND class_master_of = :class_id");
                $stmt->execute([':admin_id' => $user->id, ':class_id' => $teacherData['class_master_of']]);
                $existingMaster = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($existingMaster && (int)$existingMaster['id'] !== (int)$teacherId) {
                    // Revert role to prevent conflict
                    $this->teacherModel->update($teacherId, ['is_class_master' => 0, 'class_master_of' => null]);
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Another teacher (' . $existingMaster['name'] . ') is already class master for this class'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Assign subjects if provided (support single 'teachSubject' or array 'teachSubjects')
            $subjects = [];
            if (!empty($data['teachSubjects']) && is_array($data['teachSubjects'])) {
                $subjects = $data['teachSubjects'];
            } elseif (!empty($data['teachSubject'])) {
                $subjects = [(int)$data['teachSubject']];
            }
            if (!empty($subjects)) {
                // Get current academic year
                $currentYear = $this->academicYearModel->getCurrentYear($user->id);
                
                if ($currentYear) {
                    // Insert teacher-subject assignments
                    $db = \App\Config\Database::getInstance()->getConnection();
                    $stmt = $db->prepare("
                        INSERT INTO teacher_assignments (teacher_id, subject_id, academic_year_id, created_at)
                        VALUES (:teacher_id, :subject_id, :academic_year_id, NOW())
                    ");
                    
                    foreach ($subjects as $subjectId) {
                        $stmt->execute([
                            ':teacher_id' => $teacherId,
                            ':subject_id' => $subjectId,
                            ':academic_year_id' => $currentYear['id']
                        ]);
                    }
                }
            }

            // If teacher is designated as exam officer, create corresponding exam_officers entry
            if ($teacherData['is_exam_officer']) {
                $examOfficerModel = new \App\Models\ExamOfficer();
                $examOfficerModel->create([
                    'admin_id' => $user->id,
                    'name' => $teacherData['name'],
                    'email' => $teacherData['email'],
                    'password' => password_hash($teacherData['password'], PASSWORD_BCRYPT),
                    'phone' => $teacherData['phone'],
                    'is_active' => true,
                    'can_approve_results' => $teacherData['can_approve_results']
                ]);
            }

            // Log activity
            $logger = new \App\Utils\ActivityLogger(\App\Config\Database::getInstance()->getConnection());
            $description = "Created teacher: {$teacherData['name']}";
            if ($teacherData['is_class_master']) {
                $description .= " (Class Master)";
            }
            if ($teacherData['is_exam_officer']) {
                $description .= " (Exam Officer)";
            }
            
            $logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'create',
                $description,
                'teacher',
                $teacherId,
                [
                    'is_class_master' => $teacherData['is_class_master'],
                    'class_master_of' => $teacherData['class_master_of'],
                    'is_exam_officer' => $teacherData['is_exam_officer']
                ],
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Teacher registered successfully' . 
                    ($teacherData['is_class_master'] ? ' as Class Master' : '') .
                    ($teacherData['is_exam_officer'] ? ' as Exam Officer' : ''),
                'teacher_id' => $teacherId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $errors = Validator::validate($data, ['email' => 'required|email', 'password' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $teacher = $this->teacherModel->findByEmail($data['email']);
        if (!$teacher || !$this->teacherModel->verifyPassword($data['password'], $teacher['password'])) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid credentials']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = JWT::encode(['id' => $teacher['id'], 'role' => 'Teacher', 'admin_id' => $teacher['admin_id']]);
        unset($teacher['password']);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Login successful', 'token' => $token, 'teacher' => $teacher]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getAllTeachers(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $teachers = $this->teacherModel->getTeachersWithSubjects($user->id, $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'teachers' => $teachers]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch teachers: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getTeacher(Request $request, Response $response, $args)
    {
        $teacher = $this->teacherModel->findById($args['id']);
        if (!$teacher) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Teacher not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        unset($teacher['password']);
        $response->getBody()->write(json_encode(['success' => true, 'teacher' => $teacher]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get submissions for a teacher (grades/results they have submitted).
     * Lightweight implementation to unblock the frontend; returns empty array if no data.
     */
    public function getSubmissions(Request $request, Response $response, $args)
    {
        try {
            $teacherId = (int)($args['id'] ?? 0);
            if ($teacherId <= 0) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid teacher id']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // If there's a result table to pull from, hook it here. For now, return empty list.
            $submissions = [];

            $response->getBody()->write(json_encode([
                'success' => true,
                'submissions' => $submissions
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch submissions: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateTeacher(Request $request, Response $response, $args)
    {
        $data = Validator::sanitize($request->getParsedBody());
        $raw = $request->getParsedBody();
        $teachSubjects = isset($data['teachSubjects']) ? $data['teachSubjects'] : null;
        $user = $request->getAttribute('user');
        
        // Verify teacher exists and belongs to admin
        $teacher = $this->teacherModel->findById($args['id']);
        if (!$teacher) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Teacher not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        if ($teacher['admin_id'] != $user->id) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Validate optional numeric/boolean fields when provided
        $updateRules = [];
        if (isset($raw['experience_years']) && $raw['experience_years'] !== '') {
            $updateRules['experience_years'] = 'numeric';
        }
        if (isset($raw['class_master_of']) && $raw['class_master_of'] !== '') {
            $updateRules['class_master_of'] = 'numeric';
        }
        foreach (['is_exam_officer','is_class_master','can_approve_results','is_town_master'] as $boolField) {
            if (array_key_exists($boolField, $raw) && $raw[$boolField] !== '') {
                $updateRules[$boolField] = isset($updateRules[$boolField]) ? $updateRules[$boolField] . '|boolean' : 'boolean';
            }
        }
        if (!empty($updateRules)) {
            $valErrors = Validator::validate($raw, $updateRules);
            if (!empty($valErrors)) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $valErrors]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        // Whitelist updatable columns to avoid SQL errors on unknown fields
        $allowedFields = [
            'name',
            'first_name',
            'last_name',
            'password',
            'phone',
            'address',
            'qualification',
            'experience_years',
            'is_exam_officer',
            'can_approve_results',
            'is_town_master',
            'is_class_master',
            'class_master_of'
        ];
        $data = array_intersect_key($data, array_flip($allowedFields));

        // Handle name splitting if provided
        if (isset($data['name']) && !empty($data['name'])) {
            $nameParts = explode(' ', trim($data['name']), 2);
            $data['first_name'] = $nameParts[0];
            $data['last_name'] = isset($nameParts[1]) ? $nameParts[1] : '';
        } elseif (isset($data['first_name']) || isset($data['last_name'])) {
            // If first_name or last_name provided, reconstruct name
            $firstName = isset($data['first_name']) ? trim($data['first_name']) : '';
            $lastName = isset($data['last_name']) ? trim($data['last_name']) : '';
            $data['name'] = trim($firstName . ' ' . $lastName);
        }

        // Handle password
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        
        // Don't allow changing these fields (ensure not present)
        unset($data['id'], $data['admin_id'], $data['email']);
        
        // Normalize numeric fields that may arrive as empty strings
        if (array_key_exists('experience_years', $data)) {
            $data['experience_years'] = ($data['experience_years'] === '' || $data['experience_years'] === null)
                ? null
                : (int)$data['experience_years'];
        }
        if (array_key_exists('class_master_of', $data)) {
            $data['class_master_of'] = ($data['class_master_of'] === '' || $data['class_master_of'] === null)
                ? null
                : (int)$data['class_master_of'];
        }

        // Handle class master assignment
        if (isset($data['is_class_master'])) {
            $data['is_class_master'] = !empty($data['is_class_master']) ? 1 : 0;
            if ($data['is_class_master'] && empty($data['class_master_of'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Class must be selected when assigning as class master'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            if (!$data['is_class_master']) {
                $data['class_master_of'] = null;
            }
            // Guard: if assigning, ensure no other teacher is master of same class
            if ($data['is_class_master'] && !empty($data['class_master_of'])) {
                $db = \App\Config\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT id, name FROM teachers WHERE admin_id = :admin_id AND is_class_master = 1 AND class_master_of = :class_id AND id <> :teacher_id");
                $stmt->execute([
                    ':admin_id' => $user->id,
                    ':class_id' => (int)$data['class_master_of'],
                    ':teacher_id' => (int)$args['id']
                ]);
                $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($existing) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Class already has a master: ' . $existing['name']
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }
        }

        // Handle exam officer status
        if (isset($data['is_exam_officer'])) {
            $data['is_exam_officer'] = !empty($data['is_exam_officer']) ? 1 : 0;
        }
        if (isset($data['can_approve_results'])) {
            $data['can_approve_results'] = !empty($data['can_approve_results']) ? 1 : 0;
        }
        
        try {
            $this->teacherModel->update($args['id'], $data);
            
            // Update subject assignments if provided (from original payload)
            if (!empty($teachSubjects) && is_array($teachSubjects)) {
                $currentYear = $this->academicYearModel->getCurrentYear($user->id);
                
                if ($currentYear) {
                    // Delete existing assignments for current year
                    $stmt = \App\Config\Database::getInstance()->getConnection()->prepare(" 
                        DELETE FROM teacher_assignments 
                        WHERE teacher_id = :teacher_id AND academic_year_id = :academic_year_id
                    ");
                    $stmt->execute([
                        ':teacher_id' => $args['id'],
                        ':academic_year_id' => $currentYear['id']
                    ]);
                    
                    // Insert new assignments
                    $stmt = \App\Config\Database::getInstance()->getConnection()->prepare("
                        INSERT INTO teacher_assignments (teacher_id, subject_id, academic_year_id, created_at)
                        VALUES (:teacher_id, :subject_id, :academic_year_id, NOW())
                    ");
                    
                    foreach ($teachSubjects as $subjectId) {
                        $stmt->execute([
                            ':teacher_id' => $args['id'],
                            ':subject_id' => $subjectId,
                            ':academic_year_id' => $currentYear['id']
                        ]);
                    }
                }
            }
            
            // Log activity
            $logger = new \App\Utils\ActivityLogger(\App\Config\Database::getInstance()->getConnection());
            $logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'update',
                "Updated teacher: {$teacher['name']}",
                'teacher',
                $args['id'],
                $data,
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );
            
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Teacher updated successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function deleteTeacher(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $queryParams = $request->getQueryParams();
        $hardDelete = isset($queryParams['hard']) && $queryParams['hard'] === 'true';
        
        // Verify teacher exists and belongs to admin
        $teacher = $this->teacherModel->findById($args['id']);
        if (!$teacher) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Teacher not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        if ($teacher['admin_id'] != $user->id) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        try {
            if ($hardDelete) {
                // Hard delete - permanently remove teacher
                $this->teacherModel->delete($args['id']);
                $message = 'Teacher permanently deleted successfully';
                $action = 'hard_delete';
            } else {
                // Soft delete - mark as deleted
                $this->teacherModel->softDelete($args['id']);
                $message = 'Teacher deleted successfully (can be restored)';
                $action = 'soft_delete';
            }
            
            // Log activity
            $logger = new \App\Utils\ActivityLogger(\App\Config\Database::getInstance()->getConnection());
            $logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                $action,
                "{$action} teacher: {$teacher['name']}",
                'teacher',
                $args['id'],
                ['delete_type' => $hardDelete ? 'hard' : 'soft'],
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );
            
            $response->getBody()->write(json_encode(['success' => true, 'message' => $message]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function restoreTeacher(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        
        // Verify teacher exists and belongs to admin
        $teacher = $this->teacherModel->findById($args['id']);
        if (!$teacher) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Teacher not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        if ($teacher['admin_id'] != $user->id) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        try {
            $this->teacherModel->restore($args['id']);
            
            // Log activity
            $logger = new \App\Utils\ActivityLogger(\App\Config\Database::getInstance()->getConnection());
            $logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'restore',
                "Restored teacher: {$teacher['name']}",
                'teacher',
                $args['id'],
                null,
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );
            
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Teacher restored successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function getDeletedTeachers(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        
        try {
            $teachers = $this->teacherModel->getDeletedTeachers($user->id);
            $response->getBody()->write(json_encode(['success' => true, 'teachers' => $teachers]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch deleted teachers: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function assignSubject(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['teacher_id' => 'required|numeric', 'subject_id' => 'required|numeric']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $assignmentId = $this->assignmentModel->assignTeacher($data['teacher_id'], $data['subject_id'], $currentYear['id']);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Subject assigned successfully', 'assignment_id' => $assignmentId]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Assignment failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getTeacherSubjects(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $subjects = $this->assignmentModel->getTeacherSubjects($args['id'], $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'subjects' => $subjects]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch subjects: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getTeacherClasses(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $classes = $this->assignmentModel->getTeacherClasses($args['id'], $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'classes' => $classes]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch classes: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function removeSubjectAssignment(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $this->assignmentModel->removeAssignment($args['teacherId'], $args['subjectId'], $currentYear['id']);

            // Log activity
            $logger = new \App\Utils\ActivityLogger(\App\Config\Database::getInstance()->getConnection());
            $logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'delete',
                "Removed subject assignment",
                'teacher_assignment',
                null,
                ['teacher_id' => $args['teacherId'], 'subject_id' => $args['subjectId'], 'academic_year_id' => $currentYear['id']],
                \App\Utils\ActivityLogger::guessDisplayName($user)
            );

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Subject removed from teacher']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to remove assignment: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getSubjectAttendance(Request $request, Response $response, $args)
    {
        $teacherId = $args['teacherId'];
        $subjectId = $args['subjectId'];
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $db = \App\Config\Database::getInstance()->getConnection();

            // Get all students in classes taught by this teacher for this subject
            $stmt = $db->prepare("
                SELECT DISTINCT
                    s.id as student_id,
                    s.name as student_name,
                    s.admission_no,
                    COUNT(DISTINCT a.id) as total_classes,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as total_present,
                    ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT a.id), 0)) * 100, 2) as attendance_percentage,
                    (SELECT status FROM attendance WHERE student_id = s.id AND subject_id = :subject_id_today AND date = CURDATE() LIMIT 1) as today_status
                FROM students s
                INNER JOIN student_enrollments se ON s.id = se.student_id AND se.academic_year_id = :academic_year_id
                INNER JOIN subjects sub ON se.class_id = sub.class_id
                LEFT JOIN attendance a ON s.id = a.student_id AND a.subject_id = :subject_id AND a.academic_year_id = :academic_year_id2
                WHERE sub.id = :subject_id2 AND se.status = 'active'
                GROUP BY s.id, s.name, s.admission_no
                ORDER BY s.name
            ");

            $stmt->execute([
                ':subject_id' => $subjectId,
                ':subject_id2' => $subjectId,
                ':subject_id_today' => $subjectId,
                ':academic_year_id' => $currentYear['id'],
                ':academic_year_id2' => $currentYear['id']
            ]);

            $attendance = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Set default today_status if not marked yet
            foreach ($attendance as &$record) {
                if ($record['today_status'] === null) {
                    $record['today_status'] = 'absent';
                }
                $record['attendance_percentage'] = $record['attendance_percentage'] ?? 0;
            }

            $response->getBody()->write(json_encode(['success' => true, 'attendance' => $attendance]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch attendance: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function markAttendance(Request $request, Response $response, $args)
    {
        $teacherId = $args['teacherId'];
        $subjectId = $args['subjectId'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, [
            'student_id' => 'required|numeric',
            'status' => 'required',
            'date' => 'required'
        ]);

        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $db = \App\Config\Database::getInstance()->getConnection();

            // Enforce timetable: ensure this teacher has a scheduled slot for the student's class and subject at this date/time
            $en = $db->prepare("SELECT class_id FROM student_enrollments WHERE student_id = :sid AND academic_year_id = :yid AND status = 'active' LIMIT 1");
            $en->execute([':sid' => (int)$data['student_id'], ':yid' => (int)$currentYear['id']]);
            $enrollment = $en->fetch(\PDO::FETCH_ASSOC);
            if (!$enrollment) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Student not enrolled for current academic year']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $classId = (int)$enrollment['class_id'];
            $dayOfWeek = date('l', strtotime($data['date']));
            $time = isset($data['time']) && $data['time'] !== '' ? $data['time'] : date('H:i:s');

            $tt = $db->prepare("SELECT COUNT(*) FROM timetable_entries te
                                 WHERE te.admin_id = :admin
                                   AND te.academic_year_id = :yid
                                   AND te.class_id = :cid
                                   AND te.subject_id = :sub
                                   AND te.teacher_id = :tid
                                   AND te.day_of_week = :dow
                                   AND te.is_active = 1
                                   AND :tm BETWEEN te.start_time AND te.end_time");
            $tt->execute([
                ':admin' => $user->id,
                ':yid' => (int)$currentYear['id'],
                ':cid' => $classId,
                ':sub' => (int)$subjectId,
                ':tid' => (int)$teacherId,
                ':dow' => $dayOfWeek,
                ':tm' => $time,
            ]);
            if ((int)$tt->fetchColumn() === 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No scheduled class for this subject at the specified date/time for this teacher'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            // Check if attendance already exists for this student, subject, and date
            $stmt = $db->prepare("
                SELECT id FROM attendance
                WHERE student_id = :student_id
                AND subject_id = :subject_id
                AND date = :date
            ");
            $stmt->execute([
                ':student_id' => $data['student_id'],
                ':subject_id' => $subjectId,
                ':date' => $data['date']
            ]);

            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing attendance
                $stmt = $db->prepare("
                    UPDATE attendance
                    SET status = :status, remarks = :remarks
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':status' => $data['status'],
                    ':remarks' => $data['remarks'] ?? null,
                    ':id' => $existing['id']
                ]);
            } else {
                // Insert new attendance
                $stmt = $db->prepare("
                    INSERT INTO attendance (student_id, subject_id, academic_year_id, date, status, remarks)
                    VALUES (:student_id, :subject_id, :academic_year_id, :date, :status, :remarks)
                ");
                $stmt->execute([
                    ':student_id' => $data['student_id'],
                    ':subject_id' => $subjectId,
                    ':academic_year_id' => $currentYear['id'],
                    ':date' => $data['date'],
                    ':status' => $data['status'],
                    ':remarks' => $data['remarks'] ?? null
                ]);
            }

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Attendance marked successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to mark attendance: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getSubjectStudents(Request $request, Response $response, $args)
    {
        $teacherId = $args['teacherId'];
        $subjectId = $args['subjectId'];
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No active academic year found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $db = \App\Config\Database::getInstance()->getConnection();

            // Get all students for this subject with attendance data
            $stmt = $db->prepare("
                SELECT DISTINCT
                    s.id as student_id,
                    s.name as student_name,
                    s.admission_no,
                    s.email,
                    (SELECT AVG(COALESCE(er.marks_obtained, er.total_score, 0))
                     FROM exam_results er
                     WHERE er.student_id = s.id
                     AND er.subject_id = :subject_id_grade
                     AND er.approval_status = 'approved') as current_grade,
                    COUNT(DISTINCT a.id) as total_classes,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as total_present,
                    ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT a.id), 0)) * 100, 2) as attendance_percentage
                FROM students s
                INNER JOIN student_enrollments se ON s.id = se.student_id AND se.academic_year_id = :academic_year_id
                INNER JOIN subjects sub ON se.class_id = sub.class_id
                LEFT JOIN attendance a ON s.id = a.student_id AND a.subject_id = :subject_id AND a.academic_year_id = :academic_year_id2
                WHERE sub.id = :subject_id2 AND se.status = 'active'
                GROUP BY s.id, s.name, s.admission_no, s.email
                ORDER BY s.name
            ");

            $stmt->execute([
                ':subject_id' => $subjectId,
                ':subject_id2' => $subjectId,
                ':subject_id_grade' => $subjectId,
                ':academic_year_id' => $currentYear['id'],
                ':academic_year_id2' => $currentYear['id']
            ]);
            $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Format grades and set default attendance values
            foreach ($students as &$student) {
                if ($student['current_grade']) {
                    $student['current_grade'] = round($student['current_grade'], 2) . '%';
                }
                $student['total_classes'] = $student['total_classes'] ?? 0;
                $student['total_present'] = $student['total_present'] ?? 0;
                $student['attendance_percentage'] = $student['attendance_percentage'] ?? 0;
            }

            $response->getBody()->write(json_encode(['success' => true, 'students' => $students]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch students: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function submitGrade(Request $request, Response $response, $args)
    {
        $teacherId = $args['teacherId'];
        $subjectId = $args['subjectId'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, [
            'student_id' => 'required|numeric',
            'exam_id' => 'required|numeric',
            'score' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();

            // Insert or update result
            $stmt = $db->prepare("
                INSERT INTO results (student_id, exam_id, score, teacher_id, status, submitted_at)
                VALUES (:student_id, :exam_id, :score, :teacher_id, 'pending', NOW())
                ON DUPLICATE KEY UPDATE
                    score = :score,
                    teacher_id = :teacher_id,
                    status = 'pending',
                    submitted_at = NOW()
            ");

            $stmt->execute([
                ':student_id' => $data['student_id'],
                ':exam_id' => $data['exam_id'],
                ':score' => $data['score'],
                ':teacher_id' => $teacherId
            ]);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Grade submitted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to submit grade: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // ==================== GRADE CHANGE REQUEST METHODS ====================

    /**
     * Request a grade change for already submitted results
     * Teachers can request edits which need exam officer approval
     */
    public function requestGradeChange(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, [
            'result_id' => 'required|numeric',
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
            $resultId = $data['result_id'];

            // Check if result exists and belongs to this teacher
            $result = $this->resultModel->findById($resultId);
            if (!$result) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Result not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($result['uploaded_by_teacher_id'] != $user->id) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You can only request changes to your own submissions'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Check if there's already a pending request for this result
            if ($this->gradeUpdateModel->hasPendingRequest($resultId)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'There is already a pending change request for this result'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Create the change request
            $requestData = [
                'result_id' => $resultId,
                'teacher_id' => $user->id,
                'reason' => $data['reason'],
                'status' => 'pending'
            ];

            // Handle different field updates
            if (isset($data['new_test_score'])) {
                $requestData['field_to_update'] = 'test_score';
                $requestData['current_test_score'] = $result['test_score'];
                $requestData['new_test_score'] = $data['new_test_score'];
                $requestData['current_score'] = $result['test_score'];
                $requestData['new_score'] = $data['new_test_score'];
            } elseif (isset($data['new_exam_score'])) {
                $requestData['field_to_update'] = 'exam_score';
                $requestData['current_exam_score'] = $result['exam_score'];
                $requestData['new_exam_score'] = $data['new_exam_score'];
                $requestData['current_score'] = $result['exam_score'];
                $requestData['new_score'] = $data['new_exam_score'];
            } elseif (isset($data['new_marks_obtained'])) {
                $requestData['field_to_update'] = 'marks_obtained';
                $requestData['current_score'] = $result['marks_obtained'];
                $requestData['new_score'] = $data['new_marks_obtained'];
            } else {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Please specify which score to update (new_test_score, new_exam_score, or new_marks_obtained)'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $requestId = $this->gradeUpdateModel->create($requestData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade change request submitted successfully. Awaiting exam officer approval.',
                'request_id' => $requestId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create grade change request: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get all grade change requests for the logged-in teacher
     */
    public function getMyGradeChangeRequests(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $status = $params['status'] ?? null;

        try {
            $requests = $this->gradeUpdateModel->getRequestsByTeacher($user->id, $status);

            $response->getBody()->write(json_encode([
                'success' => true,
                'requests' => $requests,
                'total' => count($requests)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch grade change requests: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get statistics about teacher's grade change requests
     */
    public function getGradeChangeStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $stats = $this->gradeUpdateModel->getRequestStats($user->id);

            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => $stats
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

    /**
     * Get teacher dashboard statistics
     * Returns: number of students, lessons, tests taken, and total hours
     */
    public function getDashboardStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No active academic year found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $db = \App\Config\Database::getInstance()->getConnection();

            // Get teacher's subjects and class
            $teacherData = $this->teacherModel->findById($user->id);
            $classId = $teacherData['class_master_of'] ?? null;

            // Get number of students (from teacher's class if they're a class master)
            $numStudents = 0;
            if ($classId) {
                $stmt = $db->prepare("
                    SELECT COUNT(DISTINCT s.id) as total
                    FROM students s
                    INNER JOIN student_enrollments se ON s.id = se.student_id
                    WHERE se.class_id = :class_id
                    AND se.academic_year_id = :academic_year_id
                    AND se.status = 'active'
                ");
                $stmt->execute([
                    ':class_id' => $classId,
                    ':academic_year_id' => $currentYear['id']
                ]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $numStudents = (int)($result['total'] ?? 0);
            }

            // Get teacher's subjects
            $stmt = $db->prepare("
                SELECT s.id, s.subject_name, s.sessions
                FROM subjects s
                INNER JOIN teacher_assignments ta ON s.id = ta.subject_id
                WHERE ta.teacher_id = :teacher_id
                AND ta.academic_year_id = :academic_year_id
            ");
            $stmt->execute([
                ':teacher_id' => $user->id,
                ':academic_year_id' => $currentYear['id']
            ]);
            $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate total lessons/sessions
            $totalLessons = 0;
            $subjectIds = [];
            $numSubjects = count($subjects);
            foreach ($subjects as $subject) {
                $totalLessons += (int)($subject['sessions'] ?? 0);
                $subjectIds[] = $subject['id'];
            }

            // Get number of tests/exams for teacher's subjects
            $numTests = 0;
            $totalHours = 0;
            $graded = 0;
            $pendingGrades = 0;
            $classAverage = null;

            if (!empty($subjectIds)) {
                $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));

                // Count exams for teacher's subjects
                $stmt = $db->prepare("
                    SELECT COUNT(DISTINCT e.id) as total
                    FROM exams e
                    INNER JOIN exam_results er ON e.id = er.exam_id
                    WHERE er.subject_id IN ($placeholders)
                    AND e.academic_year_id = ?
                ");
                $params = array_merge($subjectIds, [$currentYear['id']]);
                $stmt->execute($params);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $numTests = (int)($result['total'] ?? 0);

                // Calculate total hours (estimate: sessions * 1 hour per session)
                $totalHours = $totalLessons;

                // Get grading statistics
                $stmt = $db->prepare("
                    SELECT
                        COUNT(*) as total_results,
                        SUM(CASE WHEN total_score > 0 THEN 1 ELSE 0 END) as graded_count,
                        SUM(CASE WHEN total_score = 0 OR total_score IS NULL THEN 1 ELSE 0 END) as pending_count,
                        AVG(CASE WHEN total_score > 0 THEN total_score ELSE NULL END) as avg_score
                    FROM exam_results er
                    INNER JOIN exams e ON er.exam_id = e.id
                    WHERE er.subject_id IN ($placeholders)
                    AND e.academic_year_id = ?
                    AND e.is_published = 1
                ");
                $stmt->execute($params);
                $gradingData = $stmt->fetch(\PDO::FETCH_ASSOC);

                $graded = (int)($gradingData['graded_count'] ?? 0);
                $pendingGrades = (int)($gradingData['pending_count'] ?? 0);
                $classAverage = $gradingData['avg_score'] ? round($gradingData['avg_score'], 2) : null;
            }

            $stats = [
                'students' => $numStudents,
                'subjects' => $numSubjects,
                'lessons' => $totalLessons,
                'tests' => $numTests,
                'hours' => $totalHours,
                'graded' => $graded,
                'pending_grades' => $pendingGrades,
                'class_average' => $classAverage
            ];

            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => $stats
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Bulk upload teachers via CSV
     */
    public function bulkUpload(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $uploadedFiles = $request->getUploadedFiles();

        if (empty($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'No file uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $file = $uploadedFiles['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'File upload error']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $tmpPath = $file->getStream()->getMetadata('uri');
        $rows = $this->parseCSV($tmpPath);

        if (empty($rows)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'CSV file is empty or invalid']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $header = array_shift($rows);
        $expectedHeaders = ['First Name', 'Last Name', 'Email', 'Password', 'Phone', 'Address', 'Qualification', 'Experience Years'];
        
        // Normalize headers (trim, lowercase)
        $normalizedHeader = array_map(fn($h) => strtolower(trim($h)), $header);
        $expectedNormalized = array_map(fn($h) => strtolower(trim($h)), $expectedHeaders);

        // Check if headers match (order-insensitive)
        foreach ($expectedNormalized as $expected) {
            if (!in_array($expected, $normalizedHeader)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => "Missing required header: $expected. Expected headers: " . implode(', ', $expectedHeaders)
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        // Map header positions
        $headerMap = [];
        foreach ($normalizedHeader as $index => $headerName) {
            $headerMap[$headerName] = $index;
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            $rowNum = $rowIndex + 2; // +2 for header + 0-index

            try {
                $firstName = trim($row[$headerMap['first name']] ?? '');
                $lastName = trim($row[$headerMap['last name']] ?? '');
                $email = trim($row[$headerMap['email']] ?? '');
                $password = trim($row[$headerMap['password']] ?? '');
                $phone = trim($row[$headerMap['phone']] ?? '');
                $address = trim($row[$headerMap['address']] ?? '');
                $qualification = trim($row[$headerMap['qualification']] ?? '');
                $experienceYears = trim($row[$headerMap['experience years']] ?? '');

                // Validation
                if (empty($firstName) || empty($lastName)) {
                    $errors[] = "Row $rowNum: First name and last name are required";
                    $errorCount++;
                    continue;
                }

                if (empty($email)) {
                    $errors[] = "Row $rowNum: Email is required";
                    $errorCount++;
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row $rowNum: Invalid email format";
                    $errorCount++;
                    continue;
                }

                if (empty($password)) {
                    $errors[] = "Row $rowNum: Password is required";
                    $errorCount++;
                    continue;
                }

                if (strlen($password) < 6) {
                    $errors[] = "Row $rowNum: Password must be at least 6 characters";
                    $errorCount++;
                    continue;
                }

                // Check if email already exists
                $existing = $this->teacherModel->findByEmail($email);
                if ($existing) {
                    $errors[] = "Row $rowNum: Email $email already exists";
                    $errorCount++;
                    continue;
                }

                // Build full name
                $fullName = trim($firstName . ' ' . $lastName);

                // Create teacher
                $teacherData = [
                    'admin_id' => $user->id,
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'phone' => $phone ?: null,
                    'address' => $address ?: null,
                    'qualification' => $qualification ?: null,
                    'experience_years' => $experienceYears !== '' ? (int)$experienceYears : null,
                ];

                $this->teacherModel->create($teacherData);
                $successCount++;

            } catch (\Exception $e) {
                $errors[] = "Row $rowNum: " . $e->getMessage();
                $errorCount++;
            }
        }

        $message = "Bulk upload completed. Success: $successCount, Errors: $errorCount";
        $responseData = [
            'success' => $successCount > 0,
            'message' => $message,
            'successCount' => $successCount,
            'errorCount' => $errorCount,
            'errors' => array_slice($errors, 0, 10) // Limit to first 10 errors
        ];

        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Download CSV template for bulk teacher upload
     */
    public function bulkTemplate(Request $request, Response $response)
    {
        $headers = ['First Name', 'Last Name', 'Email', 'Password', 'Phone', 'Address', 'Qualification', 'Experience Years'];
        $csv = implode(',', $headers) . "\n";
        $csv .= "John,Doe,john.doe@school.com,password123,555-1234,123 Main St,Bachelor's in Education,5\n";
        $csv .= "Jane,Smith,jane.smith@school.com,password456,555-5678,456 Oak Ave,Master's in Mathematics,8\n";

        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="teachers_template.csv"');
    }

    /**
     * Parse CSV file
     */
    private function parseCSV($filePath)
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        // Remove empty rows
        if (empty($rows) || empty(array_filter($rows[0], fn($x) => trim((string)$x) !== ''))) {
            return null;
        }
        return $rows;
    }
}


