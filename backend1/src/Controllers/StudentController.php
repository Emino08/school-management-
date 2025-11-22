<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Utils\JWT;
use App\Utils\Validator;

class StudentController
{
    private $studentModel;
    private $enrollmentModel;
    private $academicYearModel;
    private $gradeModel;
    private $attendanceModel;
    private $classModel;

    public function __construct()
    {
        $this->studentModel = new Student();
        $this->enrollmentModel = new StudentEnrollment();
        $this->academicYearModel = new AcademicYear();
        $this->gradeModel = new Grade();
        $this->attendanceModel = new Attendance();
        $this->classModel = new ClassModel();
    }

    public function register(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        // Normalize common alternative field names from frontend
        if (isset($data['id_number']) && !isset($data['roll_number'])) {
            $data['roll_number'] = $data['id_number'];
        } elseif (isset($data['idNumber']) && !isset($data['roll_number'])) {
            $data['roll_number'] = $data['idNumber'];
        } elseif (isset($data['rollNum']) && !isset($data['roll_number'])) {
            $data['roll_number'] = $data['rollNum'];
        }
        if (isset($data['sclassName']) && !isset($data['class_id'])) {
            $data['class_id'] = $data['sclassName'];
        }

        $nameParts = $this->extractNameParts($data);
        $data['first_name'] = $nameParts['first_name'];
        $data['last_name'] = $nameParts['last_name'];
        $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);

        $errors = Validator::validate($data, [
            'first_name' => 'required',
            'last_name' => 'required',
            'roll_number' => 'required',
            'class_id' => 'required|numeric',
            'password' => 'required|min:6'
        ]);

        // Adjust error keys/messages for frontend consistency (ID Number)
        if (!empty($errors)) {
            if (isset($errors['roll_number'])) {
                $errors['id_number'] = 'ID number is required';
                unset($errors['roll_number']);
            }
            if (isset($errors['class_id']) && stripos($errors['class_id'], 'required') !== false) {
                $errors['class_id'] = 'Class is required';
            }
        }

        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if ID number already exists for this school
        $existing = $this->studentModel->findByIdNumber($user->id, $data['roll_number']);
        if ($existing) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'ID number already exists'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $classId = $data['class_id'];

            // Build safe insert payload
            $studentData = [
                'admin_id'      => $user->id,
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'name'          => $data['name'],
                'id_number'     => $data['roll_number'], // normalized to roll_number above
                'email'         => $data['email'] ?? null,
                'password'      => $data['password'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender'        => $data['gender'] ?? null,
                'address'       => $data['address'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'parent_name'   => $data['parent_name'] ?? null,
                'parent_phone'  => $data['parent_phone'] ?? null,
            ];

            // Backward-compat: if old roll_number column still exists, include it
            $studentData['roll_number'] = $studentData['id_number'];

            $studentId = $this->studentModel->createStudent(Validator::sanitize($studentData));

            // Enroll student in current academic year
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if ($currentYear) {
                $this->enrollmentModel->enrollStudent([
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'academic_year_id' => $currentYear['id'],
                    'status' => 'active'
                ]);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student registered successfully',
                'student_id' => $studentId
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
        $data = $request->getParsedBody();

        // Allow id_number as alias for roll_number
        if (isset($data['id_number']) && !isset($data['roll_number'])) {
            $data['roll_number'] = $data['id_number'];
        } elseif (isset($data['idNumber']) && !isset($data['roll_number'])) {
            $data['roll_number'] = $data['idNumber'];
        } elseif (isset($data['rollNum']) && !isset($data['roll_number'])) {
            $data['roll_number'] = $data['rollNum'];
        }

        $errors = Validator::validate($data, [
            'roll_number' => 'required',
            'password' => 'required'
        ]);
        if (!empty($errors) && isset($errors['roll_number'])) {
            $errors['id_number'] = 'ID number is required';
            unset($errors['roll_number']);
        }

        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Find student by email or roll number
        $student = isset($data['email']) ?
            $this->studentModel->findByEmail($data['email']) :
            $this->studentModel->findOne(['id_number' => $data['roll_number']]);

        if (!$student || !$this->studentModel->verifyPassword($data['password'], $student['password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Get current enrollment and class information
        $currentYear = $this->academicYearModel->getCurrentYear($student['admin_id']);
        $classInfo = null;

        if ($currentYear) {
            $enrollment = $this->enrollmentModel->findOne([
                'student_id' => $student['id'],
                'academic_year_id' => $currentYear['id']
            ]);

            if ($enrollment) {
                $classInfo = $this->classModel->findById($enrollment['class_id']);
            }
        }

        // Add class information to student object (frontend expects 'sclassName' with '_id')
        if ($classInfo) {
            $student['sclassName'] = [
                '_id' => $classInfo['id'],
                'sclassName' => $classInfo['class_name'],
                'grade_level' => $classInfo['grade_level'] ?? null,
                'section' => $classInfo['section'] ?? null
            ];
        }

        $token = JWT::encode([
            'id' => $student['id'],
            'role' => 'Student',
            'admin_id' => $student['admin_id']
        ]);

        unset($student['password']);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'student' => $student,
            '_id' => $student['id'],  // Frontend compatibility
            'name' => $student['name'],
            'rollNum' => $student['id_number'],
            'sclassName' => $student['sclassName'] ?? null,
            'school' => $student['admin_id']
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getAllStudents(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $query = $request->getQueryParams();
        $search = trim($query['search'] ?? '');
        $limit = isset($query['limit']) ? (int)$query['limit'] : null;

        try {
            $adminId = $request->getAttribute('admin_id') ?? ($user->admin_id ?? $user->id);
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $students = $this->studentModel->getStudentsWithEnrollment(
                $adminId,
                $currentYear ? $currentYear['id'] : null
            );

            if ($search !== '') {
                $students = array_values(array_filter($students, function ($student) use ($search) {
                    $haystack = strtolower(
                        ($student['name'] ?? '') . ' ' .
                        ($student['email'] ?? '') . ' ' .
                        ($student['id_number'] ?? '') . ' ' .
                        ($student['class_name'] ?? '')
                    );

                    return strpos($haystack, strtolower($search)) !== false
                        || (isset($student['id']) && (string)$student['id'] === $search)
                        || (isset($student['id_number']) && (string)$student['id_number'] === $search);
                }));
            }

            $total = count($students);
            if ($limit) {
                $students = array_slice($students, 0, $limit);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'students' => $students,
                'total' => $total
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

    public function getStudent(Request $request, Response $response, $args)
    {
        $studentId = $args['id'];

        try {
            $student = $this->studentModel->findById($studentId);

            if (!$student) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get current enrollment and class information
            $currentYear = $this->academicYearModel->getCurrentYear($student['admin_id']);
            $classInfo = null;

            if ($currentYear) {
                $enrollment = $this->enrollmentModel->findOne([
                    'student_id' => $student['id'],
                    'academic_year_id' => $currentYear['id']
                ]);

                if ($enrollment) {
                    $classInfo = $this->classModel->findById($enrollment['class_id']);
                }
            }

            // Add class information to student object (frontend expects 'sclassName' with '_id')
            if ($classInfo) {
                $student['sclassName'] = [
                    '_id' => $classInfo['id'],
                    'sclassName' => $classInfo['class_name'],
                    'grade_level' => $classInfo['grade_level'] ?? null,
                    'section' => $classInfo['section'] ?? null
                ];
                $student['class_id'] = $classInfo['id'];
                $student['class_name'] = $classInfo['class_name'];
            }

            unset($student['password']);

            $response->getBody()->write(json_encode([
                'success' => true,
                'student' => $student
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch student: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateStudent(Request $request, Response $response, $args)
    {
        $studentId = $args['id'];
        $data = Validator::sanitize($request->getParsedBody());
        $student = $this->studentModel->findById($studentId);
        if (!$student) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Student not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Remove password from update if not provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }

        // Remove protected fields
        unset($data['id'], $data['admin_id'], $data['roll_number'], $data['id_number']);

        if (!empty($data['name']) && (empty($data['first_name']) || empty($data['last_name']))) {
            $nameParts = $this->extractNameParts($data);
            $data['first_name'] = $nameParts['first_name'];
            $data['last_name'] = $nameParts['last_name'];
            $data['name'] = trim($nameParts['first_name'] . ' ' . $nameParts['last_name']);
        } elseif (isset($data['first_name']) || isset($data['last_name'])) {
            $first = $data['first_name'] ?? $student['first_name'] ?? '';
            $last = $data['last_name'] ?? $student['last_name'] ?? '';
            $data['name'] = trim("$first $last");
        }

        try {
            $this->studentModel->update($studentId, $data);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getDashboardStats(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $studentId = $user->id;

        try {
            $db = \App\Config\Database::getInstance()->getConnection();

            // Get student info with class
            $student = $this->studentModel->findById($studentId);
            if (!$student) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get current enrollment
            $currentYear = $this->academicYearModel->getCurrentYear($student['admin_id']);
            $classId = null;

            if ($currentYear) {
                $enrollment = $this->enrollmentModel->findOne([
                    'student_id' => $studentId,
                    'academic_year_id' => $currentYear['id']
                ]);
                if ($enrollment) {
                    $classId = $enrollment['class_id'];
                }
            }

            // Count subjects (from class enrollment)
            $subjectsCount = 0;
            if ($classId) {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM subjects WHERE class_id = :class_id");
                $stmt->execute([':class_id' => $classId]);
                $subjectsCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
            }

            // Count exam results (join with exams table to check if published)
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT er.exam_id) as count
                FROM exam_results er
                INNER JOIN exams e ON er.exam_id = e.id
                WHERE er.student_id = :student_id
                AND e.is_published = 1
            ");
            $stmt->execute([':student_id' => $studentId]);
            $examsCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

            // Get attendance stats
            $stmt = $db->prepare("
                SELECT
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count
                FROM attendance
                WHERE student_id = :student_id
            ");
            $stmt->execute([':student_id' => $studentId]);
            $attendanceData = $stmt->fetch(\PDO::FETCH_ASSOC);

            $attendancePercentage = 0;
            if ($attendanceData['total_records'] > 0) {
                $attendancePercentage = round(($attendanceData['present_count'] / $attendanceData['total_records']) * 100, 2);
            }

            // Count complaints
            $stmt = $db->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                FROM complaints
                WHERE user_id = :user_id AND user_type = 'student'
            ");
            $stmt->execute([':user_id' => $studentId]);
            $complaintsData = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Count notices for student
            $stmt = $db->prepare("
                SELECT COUNT(*) as count
                FROM notices
                WHERE admin_id = :admin_id
                AND (target_audience = 'students' OR target_audience = 'all')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([':admin_id' => $student['admin_id']]);
            $noticesCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

            // Get average marks from recent published exams
            $stmt = $db->prepare("
                SELECT
                    AVG(er.total_score) as average_marks,
                    COUNT(*) as subjects_count
                FROM exam_results er
                INNER JOIN exams e ON er.exam_id = e.id
                WHERE er.student_id = :student_id
                AND e.is_published = 1
                ORDER BY er.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([':student_id' => $studentId]);
            $recentResults = $stmt->fetch(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => [
                    'subjects_count' => (int)$subjectsCount,
                    'exams_count' => (int)$examsCount,
                    'attendance_percentage' => $attendancePercentage,
                    'attendance_total' => (int)$attendanceData['total_records'],
                    'attendance_present' => (int)$attendanceData['present_count'],
                    'complaints_total' => (int)$complaintsData['total'],
                    'complaints_pending' => (int)$complaintsData['pending'],
                    'complaints_resolved' => (int)$complaintsData['resolved'],
                    'notices_count' => (int)$noticesCount,
                    'average_marks' => $recentResults['average_marks'] ? round($recentResults['average_marks'], 2) : null,
                    'class_id' => $classId,
                    'academic_year_id' => $currentYear['id'] ?? null
                ]
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

    public function deleteStudent(Request $request, Response $response, $args)
    {
        $studentId = $args['id'];

        try {
            $this->studentModel->delete($studentId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Bulk upload students via CSV (Excel-compatible)
    public function bulkUpload(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $uploadedFiles = $request->getUploadedFiles();

        if (empty($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'No file uploaded. Use form field "file".'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $file = $uploadedFiles['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'File upload failed'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Support CSV or Excel .xlsx (basic reader)
        $clientFilename = $file->getClientFilename();
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv','xlsx'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unsupported file type. Please upload a CSV or XLSX (Excel).'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stream = $file->getStream();
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('students_', true) . '.' . $ext;
        file_put_contents($tempPath, $stream);

        $rows = [];
        if ($ext === 'csv') {
            $handle = fopen($tempPath, 'r');
            if (!$handle) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Unable to read uploaded file'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        } else {
            $rows = $this->parseXlsxRows($tempPath);
            if ($rows === null) {
                unlink($tempPath);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Failed to parse XLSX file. Please ensure it has a first worksheet with headers.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        if (empty($rows)) {
            unlink($tempPath);
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'File is empty'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Header row
        $header = array_shift($rows);
        // Normalize header keys (lowercase + underscores)
        $norm = function ($s) { return strtolower(trim(preg_replace('/\s+/', '_', $s))); };
        $header = array_map($norm, $header);

        $required = ['id_number','password'];
        $optional = ['first_name','last_name','name','class_id','class_name','email','phone','address','date_of_birth','gender','parent_name','parent_phone'];
        $allowed = array_merge($required, $optional);

        $rowNum = 1; // start after header
        $created = 0; $failed = 0; $errors = [];

        // Determine current academic year for enrollment
        $currentYear = $this->academicYearModel->getCurrentYear($user->id);
        $academicYearId = $currentYear['id'] ?? null;

        foreach ($rows as $row) {
            $rowNum++;
            if (!is_array($row) || (count($row) === 1 && trim((string)$row[0]) === '')) { continue; }
            $data = array_combine($header, $row);
            if ($data === false) {
                $failed++; $errors[] = [ 'row' => $rowNum, 'error' => 'Column mismatch' ]; continue;
            }

            // Validate required fields
            foreach ($required as $req) {
                if (!isset($data[$req]) || trim($data[$req]) === '') {
                    $failed++; $errors[] = [ 'row' => $rowNum, 'error' => "$req is required" ]; continue 2;
                }
            }

            // Handle name splitting: first_name + last_name or full name
            $nameParts = $this->extractNameParts($data);
            $firstName = $nameParts['first_name'];
            $lastName = $nameParts['last_name'];
            $fullName = trim($firstName . ' ' . $lastName);

            // Validate that we have at least a first name
            if (empty($firstName)) {
                $failed++; $errors[] = [ 'row' => $rowNum, 'error' => 'First name is required (provide first_name and last_name OR name)' ]; continue;
            }

            // Resolve class_id
            $classId = null;
            if (!empty($data['class_id'])) {
                $classId = (int)$data['class_id'];
            } elseif (!empty($data['class_name'])) {
                // Find class by name for this admin
                $classes = $this->classModel->findAll(['admin_id' => $user->id]);
                $match = null;
                foreach ($classes as $cls) {
                    if (strcasecmp($cls['class_name'], $data['class_name']) === 0) { $match = $cls; break; }
                }
                $classId = $match['id'] ?? null;
            }

            try {
                // Create student
                $studentPayload = [
                    'admin_id'      => $user->id,
                    'first_name'    => $firstName,
                    'last_name'     => $lastName,
                    'name'          => $fullName,
                    'id_number'     => trim($data['id_number']),
                    'email'         => $data['email'] ?? null,
                    'password'      => $data['password'],
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender'        => $data['gender'] ?? null,
                    'address'       => $data['address'] ?? null,
                    'phone'         => $data['phone'] ?? null,
                    'parent_name'   => $data['parent_name'] ?? null,
                    'parent_phone'  => $data['parent_phone'] ?? null,
                    // Backward compat: roll_number column may exist
                    'roll_number'   => trim($data['id_number']),
                ];

                $studentId = $this->studentModel->createStudent($studentPayload);

                // Enroll
                if ($academicYearId && $classId) {
                    $this->enrollmentModel->enrollStudent([
                        'student_id' => $studentId,
                        'class_id' => $classId,
                        'academic_year_id' => $academicYearId,
                        'status' => 'active'
                    ]);
                }

                $created++;
            } catch (\Exception $e) {
                $failed++; $errors[] = [ 'row' => $rowNum, 'error' => $e->getMessage() ];
            }
        }

        unlink($tempPath);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Bulk upload completed',
            'summary' => [ 'created' => $created, 'failed' => $failed, 'total' => ($created + $failed) ],
            'errors' => $errors
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Minimal XLSX parser for first worksheet and shared strings
    private function parseXlsxRows(string $filePath): ?array
    {
        if (!class_exists('ZipArchive')) { return null; }
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) { return null; }
        // Read shared strings
        $sharedStrings = [];
        $ssIdx = $zip->locateName('xl/sharedStrings.xml');
        if ($ssIdx !== false) {
            $ssXml = simplexml_load_string($zip->getFromIndex($ssIdx));
            if ($ssXml && isset($ssXml->si)) {
                foreach ($ssXml->si as $si) {
                    // concatenate t nodes
                    $text = '';
                    if (isset($si->t)) { $text = (string)$si->t; }
                    elseif (isset($si->r)) { foreach ($si->r as $r) { $text .= (string)$r->t; } }
                    $sharedStrings[] = $text;
                }
            }
        }
        // Read first sheet
        $sheetPath = 'xl/worksheets/sheet1.xml';
        $sheetIdx = $zip->locateName($sheetPath);
        if ($sheetIdx === false) { $zip->close(); return null; }
        $sheetXml = simplexml_load_string($zip->getFromIndex($sheetIdx));
        if (!$sheetXml) { $zip->close(); return null; }

        $rows = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $rowVals = [];
            foreach ($row->c as $c) {
                $type = (string)$c['t'];
                $v = isset($c->v) ? (string)$c->v : '';
                if ($type === 's') {
                    $idx = (int)$v;
                    $rowVals[] = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $rowVals[] = (string)$c->is->t;
                } else {
                    $rowVals[] = $v;
                }
            }
            // Normalize trailing empties
            $rows[] = $rowVals;
        }
        $zip->close();
        // Ensure we have a header row
        if (empty($rows) || empty(array_filter($rows[0], fn($x) => trim((string)$x) !== ''))) { return null; }
        return $rows;
    }

    // Downloadable CSV template for bulk upload
    public function bulkTemplate(Request $request, Response $response)
    {
        $headers = ['First Name','Last Name','ID Number','Password','Class Name','Email','Phone','Address','Date of Birth','Gender','Parent Name','Parent Phone'];
        $csv = implode(',', $headers) . "\n";
        $csv .= "John,Doe,ID2025001,secret123,Grade 1 A,john@example.com,5551234,10 Main St,2012-05-01,male,Jane Doe,5559999\n";

        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="students_template.csv"');
    }

    public function getStudentsByClass(Request $request, Response $response, $args)
    {
        $classId = $args['id'];
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $students = $this->studentModel->getStudentsByClass(
                $classId,
                $currentYear ? $currentYear['id'] : null
            );

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

    public function getStudentGrades(Request $request, Response $response, $args)
    {
        $studentId = $args['id'];
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $grades = $this->gradeModel->getStudentGrades(
                $studentId,
                $currentYear ? $currentYear['id'] : null
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'grades' => $grades
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch grades: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getStudentAttendance(Request $request, Response $response, $args)
    {
        $studentId = $args['id'];
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $stats = $this->attendanceModel->getAttendanceStats(
                $studentId,
                $currentYear ? $currentYear['id'] : null
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'attendance' => $stats
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

    private function extractNameParts(array $data): array
    {
        $first = trim($data['first_name'] ?? '');
        $last = trim($data['last_name'] ?? '');

        if ((!$first || !$last) && !empty($data['name'])) {
            $full = trim($data['name']);
            if ($full !== '') {
                $pieces = preg_split('/\s+/', $full, 2);
                if (!$first && isset($pieces[0])) {
                    $first = $pieces[0];
                }
                if (!$last) {
                    $last = $pieces[1] ?? '';
                }
            }
        }

        if ($first === '' && isset($data['roll_number'])) {
            $first = $data['roll_number'];
        }
        if ($last === '') {
            $last = 'Student';
        }

        return [
            'first_name' => $first,
            'last_name' => $last,
        ];
    }
}
