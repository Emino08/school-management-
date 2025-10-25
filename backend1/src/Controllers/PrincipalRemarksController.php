<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\BaseModel;
use App\Utils\Validator;
use App\Utils\ActivityLogger;

class PrincipalRemarksController
{
    private $remarksModel;
    private $logger;

    public function __construct()
    {
        $this->remarksModel = new BaseModel();
        $this->remarksModel->table = 'principal_remarks';
        
        $database = new \App\Config\Database();
        $this->logger = new ActivityLogger($database->getConnection());
    }

    /**
     * Create or update principal remarks for a class/term
     */
    public function saveRemarks(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        // Only admin/principal can add remarks
        if ($user->role !== 'admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only principal can add remarks'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $errors = Validator::validate($data, [
            'academic_year_id' => 'required|numeric',
            'class_id' => 'required|numeric',
            'term' => 'required|numeric',
            'remarks' => 'required',
            'principal_name' => 'required'
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
            // Check if remark already exists
            $existing = $this\\App\\Config\\Database::getInstance()->getConnection()->prepare(
                "SELECT id FROM principal_remarks 
                 WHERE academic_year_id = :year_id 
                 AND class_id = :class_id 
                 AND term = :term"
            );
            $existing->execute([
                ':year_id' => $data['academic_year_id'],
                ':class_id' => $data['class_id'],
                ':term' => $data['term']
            ]);
            $existingRemark = $existing->fetch(\PDO::FETCH_ASSOC);

            $remarkData = [
                'academic_year_id' => $data['academic_year_id'],
                'class_id' => $data['class_id'],
                'term' => $data['term'],
                'remarks' => $data['remarks'],
                'principal_signature' => $data['principal_signature'] ?? null,
                'principal_name' => $data['principal_name']
            ];

            if ($existingRemark) {
                // Update existing
                $stmt = $this\\App\\Config\\Database::getInstance()->getConnection()->prepare(
                    "UPDATE principal_remarks 
                     SET remarks = :remarks,
                         principal_signature = :signature,
                         principal_name = :name,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':remarks' => $remarkData['remarks'],
                    ':signature' => $remarkData['principal_signature'],
                    ':name' => $remarkData['principal_name'],
                    ':id' => $existingRemark['id']
                ]);
                $remarkId = $existingRemark['id'];
                $action = 'updated';
            } else {
                // Create new
                $remarkId = $this->remarksModel->create(Validator::sanitize($remarkData));
                $action = 'created';
            }

            // Log activity
            $this->logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                $action === 'created' ? 'create' : 'update',
                "Principal {$action} remarks for Term {$data['term']}",
                'principal_remark',
                $remarkId,
                ['term' => $data['term'], 'class_id' => $data['class_id']]
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Remarks {$action} successfully",
                'remark_id' => $remarkId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to save remarks: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get remarks for a class/term
     */
    public function getRemarks(Request $request, Response $response, $args)
    {
        try {
            $academicYearId = $args['academicYearId'];
            $classId = $args['classId'];
            $term = $args['term'];

            $stmt = $this\\App\\Config\\Database::getInstance()->getConnection()->prepare(
                "SELECT * FROM principal_remarks 
                 WHERE academic_year_id = :year_id 
                 AND class_id = :class_id 
                 AND term = :term"
            );
            $stmt->execute([
                ':year_id' => $academicYearId,
                ':class_id' => $classId,
                ':term' => $term
            ]);
            $remark = $stmt->fetch(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'remark' => $remark
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch remarks: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get all remarks for an academic year
     */
    public function getAllRemarks(Request $request, Response $response, $args)
    {
        try {
            $academicYearId = $args['academicYearId'];

            $stmt = $this\\App\\Config\\Database::getInstance()->getConnection()->prepare(
                "SELECT pr.*, c.class_name 
                 FROM principal_remarks pr
                 LEFT JOIN classes c ON pr.class_id = c.id
                 WHERE pr.academic_year_id = :year_id
                 ORDER BY pr.term, c.class_name"
            );
            $stmt->execute([':year_id' => $academicYearId]);
            $remarks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'remarks' => $remarks
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch remarks: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

