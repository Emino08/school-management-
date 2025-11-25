<?php

namespace App\Controllers;

use App\Traits\LogsActivity;

use App\Models\MedicalStaff;
use App\Models\MedicalRecord;
use App\Models\ParentNotification;
use App\Models\Student;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

class MedicalController
{
    use LogsActivity;

    private $medicalStaffModel;
    private $medicalRecordModel;
    private $notificationModel;
    private $studentModel;

    public function __construct()
    {
        $this->medicalStaffModel = new MedicalStaff();
        $this->medicalRecordModel = new MedicalRecord();
        $this->notificationModel = new ParentNotification();
        $this->studentModel = new Student();
    }

    // Medical Staff Authentication
    public function register(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody();
            $adminId = $request->getAttribute('admin_id');

            $required = ['name', 'email', 'password', 'phone', 'qualification'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => "Field {$field} is required"
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            $existingStaff = $this->medicalStaffModel->findByEmail($data['email']);
            if ($existingStaff) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email already registered'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $staffData = [
                'admin_id' => $adminId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'],
                'qualification' => $data['qualification'],
                'specialization' => $data['specialization'] ?? null,
                'license_number' => $data['license_number'] ?? null
            ];

            $staffId = $this->medicalStaffModel->createStaff($staffData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Medical staff registered successfully',
                'staff_id' => $staffId
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

            $staff = $this->medicalStaffModel->findByEmail($data['email']);

            if (!$staff || !$this->medicalStaffModel->verifyPassword($data['password'], $staff['password'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            if (!$staff['is_active']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Account is inactive'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $secret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
            $payload = [
                'id' => $staff['id'],
                'email' => $staff['email'],
                'role' => 'medical_staff',
                'admin_id' => $staff['admin_id'],
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            ];

            $token = JWT::encode($payload, $secret, 'HS256');

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'staff' => [
                    'id' => $staff['id'],
                    'name' => $staff['name'],
                    'email' => $staff['email'],
                    'qualification' => $staff['qualification'],
                    'specialization' => $staff['specialization'],
                    'admin_id' => $staff['admin_id']
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

    // Medical Records Management
    public function createRecord(Request $request, Response $response)
    {
        try {
            $staffId = $request->getAttribute('user_id');
            $adminId = $request->getAttribute('admin_id');
            $data = $request->getParsedBody();

            $required = ['student_id', 'diagnosis', 'severity'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => "Field {$field} is required"
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            $student = $this->studentModel->findById($data['student_id']);
            if (!$student || (int)$student['admin_id'] !== (int)$adminId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student not found for this school'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $recordData = [
                'student_id' => $data['student_id'],
                'admin_id' => $adminId,
                'medical_staff_id' => $staffId,
                'record_type' => $data['record_type'] ?? 'diagnosis',
                'diagnosis' => $data['diagnosis'],
                'symptoms' => $data['symptoms'] ?? null,
                'treatment' => $data['treatment'] ?? null,
                'medication' => $data['medication'] ?? null,
                'notes' => $data['notes'] ?? null,
                'severity' => $data['severity'],
                'status' => 'active',
                'next_checkup_date' => $data['next_checkup_date'] ?? null
            ];

            $recordId = $this->medicalRecordModel->create($recordData);

            // Notify parents
            $this->notificationModel->notifyMedicalIssue(
                $data['student_id'],
                $adminId,
                $data['diagnosis'],
                $data['severity']
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Medical record created and parents notified',
                'record_id' => $recordId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create record: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateRecord(Request $request, Response $response, $args)
    {
        try {
            $recordId = $args['id'];
            $data = $request->getParsedBody();

            $updateData = [];
            $allowedFields = ['symptoms', 'treatment', 'medication', 'notes', 'severity', 'status', 'next_checkup_date'];
            
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

            $this->medicalRecordModel->update($recordId, $updateData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Medical record updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update record: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function closeRecord(Request $request, Response $response, $args)
    {
        try {
            $recordId = $args['id'];
            $adminId = $request->getAttribute('admin_id');
            $data = $request->getParsedBody();
            $notes = $data['notes'] ?? 'Treatment completed successfully';

            $record = $this->medicalRecordModel->getRecordById($recordId);
            if (!$record) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Record not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $this->medicalRecordModel->closeRecord($recordId, $notes);

            // Notify parents of recovery
            $this->notificationModel->notifyMedicalRecovery(
                $record['student_id'],
                $adminId,
                $record['diagnosis']
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Medical record closed and parents notified'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to close record: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getStudentRecords(Request $request, Response $response, $args)
    {
        try {
            $studentId = $args['student_id'];
            $queryParams = $request->getQueryParams();
            $status = $queryParams['status'] ?? null;

            $adminId = $request->getAttribute('admin_id');
            $student = $this->studentModel->findById($studentId);

            if (!$student || ($adminId && (int)$student['admin_id'] !== (int)$adminId)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $records = $this->medicalRecordModel->getStudentRecords($studentId, $status);
            $documents = $this->medicalRecordModel->getStudentDocuments($studentId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'student' => [
                    'id' => $student['id'],
                    'name' => $student['name'],
                    'id_number' => $student['id_number'],
                    'date_of_birth' => $student['date_of_birth'] ?? null,
                ],
                'records' => $records,
                'documents' => $documents
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch records: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getActiveRecords(Request $request, Response $response)
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            $records = $this->medicalRecordModel->getActiveRecords($adminId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'records' => $records
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch active records: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getRecordById(Request $request, Response $response, $args)
    {
        try {
            $recordId = $args['id'];
            $record = $this->medicalRecordModel->getRecordById($recordId);

            if (!$record) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Record not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get documents
            $documents = $this->medicalRecordModel->getStudentDocuments($record['student_id']);
            $record['documents'] = $documents;

            $response->getBody()->write(json_encode([
                'success' => true,
                'record' => $record
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch record: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function uploadDocument(Request $request, Response $response)
    {
        try {
            $userId = $request->getAttribute('user_id');
            $userRole = $request->getAttribute('role');
            $adminId = $request->getAttribute('admin_id');
            
            $uploadedFiles = $request->getUploadedFiles();
            $data = $request->getParsedBody();

            if (empty($uploadedFiles['document'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No file uploaded'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $file = $uploadedFiles['document'];
            $studentId = $data['student_id'];

            // Create upload directory
            $uploadDir = __DIR__ . '/../../public/uploads/medical/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $filename;

            // Move file
            $file->moveTo($filePath);

            // Save to database
            $db = \App\Config\Database::getInstance()->getConnection();
            $sql = "INSERT INTO medical_documents 
                    (student_id, parent_id, admin_id, document_name, document_type, file_path, file_size, description, uploaded_by)
                    VALUES (:student_id, :parent_id, :admin_id, :document_name, :document_type, :file_path, :file_size, :description, :uploaded_by)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':student_id' => $studentId,
                ':parent_id' => $userRole === 'parent' ? $userId : null,
                ':admin_id' => $adminId,
                ':document_name' => $file->getClientFilename(),
                ':document_type' => $data['document_type'] ?? 'medical_record',
                ':file_path' => '/uploads/medical/' . $filename,
                ':file_size' => $file->getSize(),
                ':description' => $data['description'] ?? null,
                ':uploaded_by' => $userRole
            ]);

            $documentId = $db->lastInsertId();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document_id' => $documentId,
                'file_path' => '/uploads/medical/' . $filename
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAllStaff(Request $request, Response $response)
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            $staff = $this->medicalStaffModel->getAllByAdmin($adminId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'staff' => $staff
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch staff: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

