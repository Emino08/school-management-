<?php

namespace App\Controllers;

use App\Traits\LogsActivity;

use App\Models\House;
use App\Models\Student;
use App\Models\FeesPayment;
use App\Models\AcademicYear;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HouseController
{
    use LogsActivity;

    private $houseModel;
    private $studentModel;
    private $feesModel;
    private $academicYearModel;

    public function __construct()
    {
        $this->houseModel = new House();
        $this->studentModel = new Student();
        $this->feesModel = new FeesPayment();
        $this->academicYearModel = new AcademicYear();
    }

    public function createHouse(Request $request, Response $response)
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            $data = $request->getParsedBody();

            if (empty($data['house_name'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'House name is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $houseData = [
                'admin_id' => $adminId,
                'house_name' => $data['house_name'],
                'house_color' => $data['house_color'] ?? null,
                'house_motto' => $data['house_motto'] ?? null,
                'points' => 0
            ];

            $houseId = $this->houseModel->create($houseData);

            // Create default blocks (A-F)
            $this->houseModel->createDefaultBlocks($houseId, $adminId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'House created with default blocks',
                'house_id' => $houseId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create house: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAllHouses(Request $request, Response $response)
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            $houses = $this->houseModel->getAllByAdmin($adminId);

            // Get stats for each house
            foreach ($houses as &$house) {
                $houseData = $this->houseModel->getHouseWithBlocks($house['id']);
                $house['total_students'] = $houseData['total_students'] ?? 0;
                $house['house_master_count'] = $houseData['house_master_count'] ?? 0;
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'houses' => $houses
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch houses: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getHouseDetails(Request $request, Response $response, $args)
    {
        try {
            $houseId = $args['id'];
            $house = $this->houseModel->getHouseWithBlocks($houseId);

            if (!$house) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'House not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get house masters
            $house['house_masters'] = $this->houseModel->getHouseMasters($houseId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'house' => $house
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch house details: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function assignHouseMaster(Request $request, Response $response)
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            $data = $request->getParsedBody();

            $required = ['teacher_id', 'house_id'];
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
            $sql = "INSERT INTO house_masters (teacher_id, house_id, admin_id, is_active)
                    VALUES (:teacher_id, :house_id, :admin_id, 1)
                    ON DUPLICATE KEY UPDATE is_active = 1, assigned_at = NOW()";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':teacher_id' => $data['teacher_id'],
                ':house_id' => $data['house_id'],
                ':admin_id' => $adminId
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'House master assigned successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to assign house master: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getEligibleStudents(Request $request, Response $response)
    {
        try {
            $adminId = $request->getAttribute('admin_id');
            $teacherId = $request->getAttribute('user_id');

            // Get houses this teacher is a house master of
            $db = \App\Config\Database::getInstance()->getConnection();
            $houseSql = "SELECT house_id FROM house_masters WHERE teacher_id = :teacher_id AND is_active = 1";
            $houseStmt = $db->prepare($houseSql);
            $houseStmt->execute([':teacher_id' => $teacherId]);
            $houses = $houseStmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($houses)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You are not assigned as house master to any house'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Get students who have paid tuition but not registered
            $sql = "SELECT s.*, c.class_name, c.section,
                           fp.amount, fp.payment_date, fp.payment_status
                    FROM students s
                    LEFT JOIN student_enrollments se ON s.id = se.student_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    LEFT JOIN fees_payments fp ON s.id = fp.student_id AND fp.is_tuition_fee = 1
                    WHERE s.admin_id = :admin_id 
                    AND s.is_registered = 0
                    AND s.house_id IS NULL
                    AND fp.payment_status = 'paid'
                    ORDER BY s.name";

            $stmt = $db->prepare($sql);
            $stmt->execute([':admin_id' => $adminId]);
            $students = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'success' => true,
                'students' => $students,
                'house_ids' => $houses
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch eligible students: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function registerStudent(Request $request, Response $response)
    {
        try {
            $teacherId = $request->getAttribute('user_id');
            $adminId = $request->getAttribute('admin_id');
            $data = $request->getParsedBody();

            $required = ['student_id', 'house_id', 'house_block_id'];
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

            // Verify teacher is house master of this house
            $houseMasterSql = "SELECT id FROM house_masters 
                              WHERE teacher_id = :teacher_id AND house_id = :house_id AND is_active = 1";
            $houseMasterStmt = $db->prepare($houseMasterSql);
            $houseMasterStmt->execute([
                ':teacher_id' => $teacherId,
                ':house_id' => $data['house_id']
            ]);

            if (!$houseMasterStmt->fetch()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'You are not authorized to register students to this house'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Check if student has paid tuition
            $paymentSql = "SELECT id FROM fees_payments 
                          WHERE student_id = :student_id AND is_tuition_fee = 1 AND payment_status = 'paid'";
            $paymentStmt = $db->prepare($paymentSql);
            $paymentStmt->execute([':student_id' => $data['student_id']]);

            if (!$paymentStmt->fetch()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student has not paid tuition fee. Registration not allowed.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Get current academic year
            $academicYear = $this->academicYearModel->getCurrentAcademicYear($adminId);

            // Register student
            $updateSql = "UPDATE students 
                         SET house_id = :house_id, 
                             house_block_id = :house_block_id,
                             is_registered = 1,
                             registered_at = NOW(),
                             registration_academic_year_id = :academic_year_id
                         WHERE id = :student_id AND admin_id = :admin_id AND is_registered = 0";

            $updateStmt = $db->prepare($updateSql);
            $result = $updateStmt->execute([
                ':house_id' => $data['house_id'],
                ':house_block_id' => $data['house_block_id'],
                ':academic_year_id' => $academicYear['id'] ?? null,
                ':student_id' => $data['student_id'],
                ':admin_id' => $adminId
            ]);

            if ($updateStmt->rowCount() === 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student is already registered or not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Log the registration
            $logSql = "INSERT INTO house_registration_logs 
                       (student_id, house_id, house_block_id, registered_by, admin_id, academic_year_id, action, notes)
                       VALUES (:student_id, :house_id, :house_block_id, :registered_by, :admin_id, :academic_year_id, 'registered', :notes)";

            $logStmt = $db->prepare($logSql);
            $logStmt->execute([
                ':student_id' => $data['student_id'],
                ':house_id' => $data['house_id'],
                ':house_block_id' => $data['house_block_id'],
                ':registered_by' => $teacherId,
                ':admin_id' => $adminId,
                ':academic_year_id' => $academicYear['id'] ?? null,
                ':notes' => $data['notes'] ?? null
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student registered to house successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getHouseStudents(Request $request, Response $response, $args)
    {
        try {
            $houseId = $args['id'];
            $queryParams = $request->getQueryParams();
            $blockId = $queryParams['block_id'] ?? null;

            $students = $this->houseModel->getHouseStudents($houseId, $blockId);

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

    public function updateHouse(Request $request, Response $response, $args)
    {
        try {
            $houseId = $args['id'];
            $data = $request->getParsedBody();

            $updateData = [];
            $allowedFields = ['house_name', 'house_color', 'house_motto', 'points'];
            
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

            $this->houseModel->update($houseId, $updateData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'House updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update house: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

