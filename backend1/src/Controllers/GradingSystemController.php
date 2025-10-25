<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\GradingSystem;
use App\Utils\Validator;

class GradingSystemController
{
    private $gradingSystemModel;

    public function __construct()
    {
        $this->gradingSystemModel = new GradingSystem();
    }

    // Get grading scheme for academic year
    public function getGradingScheme(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $academicYearId = $params['academic_year_id'] ?? null;

        try {
            $scheme = $this->gradingSystemModel->getGradingScheme($user->id, $academicYearId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'grading_scheme' => $scheme
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch grading scheme: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Create grade range
    public function createGradeRange(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $rules = [
            'grade_label' => 'required',
            'min_score' => 'required',
            'max_score' => 'required'
        ];
        if (array_key_exists('is_passing', $data) && $data['is_passing'] !== '') {
            $rules['is_passing'] = 'boolean';
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

        // Validate score range
        if ($data['min_score'] >= $data['max_score']) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Minimum score must be less than maximum score'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if ($data['min_score'] < 0 || $data['max_score'] > 100) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Scores must be between 0 and 100'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $gradeData = Validator::sanitize([
                'admin_id' => $user->id,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'grade_label' => strtoupper($data['grade_label']),
                'min_score' => $data['min_score'],
                'max_score' => $data['max_score'],
                'grade_point' => $data['grade_point'] ?? null,
                'description' => $data['description'] ?? null,
                'is_passing' => isset($data['is_passing']) ? (filter_var($data['is_passing'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : 1,
                'is_active' => true
            ]);

            $gradeId = $this->gradingSystemModel->createGradeRange($gradeData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade range created successfully',
                'grade_id' => $gradeId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create grade range: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Update grade range
    public function updateGradeRange(Request $request, Response $response, $args)
    {
        $gradeId = $args['id'];
        $data = $request->getParsedBody();

        // Validate score range if provided
        if (isset($data['min_score']) && isset($data['max_score'])) {
            if ($data['min_score'] >= $data['max_score']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Minimum score must be less than maximum score'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if ($data['min_score'] < 0 || $data['max_score'] > 100) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Scores must be between 0 and 100'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        try {
            $updateData = Validator::sanitize($data);

            if (isset($updateData['grade_label'])) {
                $updateData['grade_label'] = strtoupper($updateData['grade_label']);
            }

            $this->gradingSystemModel->updateGradeRange($gradeId, $updateData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade range updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update grade range: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Delete grade range
    public function deleteGradeRange(Request $request, Response $response, $args)
    {
        $gradeId = $args['id'];

        try {
            $this->gradingSystemModel->deleteGradeRange($gradeId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Grade range deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete grade range: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Calculate grade for a score
    public function calculateGrade(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['score' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Score is required',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $academicYearId = $data['academic_year_id'] ?? null;
            $grade = $this->gradingSystemModel->calculateGrade(
                $data['score'],
                $user->id,
                $academicYearId
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'score' => $data['score'],
                'grade' => $grade
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to calculate grade: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get grade statistics
    public function getGradeStatistics(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $academicYearId = $params['academic_year_id'] ?? null;

        if (!$academicYearId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Academic year ID is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $stats = $this->gradingSystemModel->getGradeStatistics($user->id, $academicYearId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'statistics' => $stats
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch grade statistics: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Create preset grading system
    public function createPreset(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['preset_type' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Preset type is required',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $academicYearId = $data['academic_year_id'] ?? null;
            $presetType = $data['preset_type'];
            $created = 0;

            // Define preset grading systems
            $presets = [
                'gpa_5' => [
                    ['grade_label' => 'A', 'min_score' => 90, 'max_score' => 100, 'grade_point' => 5.0, 'description' => 'Outstanding', 'is_passing' => true],
                    ['grade_label' => 'B', 'min_score' => 80, 'max_score' => 89.99, 'grade_point' => 4.0, 'description' => 'Excellent', 'is_passing' => true],
                    ['grade_label' => 'C', 'min_score' => 70, 'max_score' => 79.99, 'grade_point' => 3.0, 'description' => 'Good', 'is_passing' => true],
                    ['grade_label' => 'D', 'min_score' => 60, 'max_score' => 69.99, 'grade_point' => 2.0, 'description' => 'Satisfactory', 'is_passing' => true],
                    ['grade_label' => 'E', 'min_score' => 50, 'max_score' => 59.99, 'grade_point' => 1.0, 'description' => 'Pass', 'is_passing' => true],
                    ['grade_label' => 'F', 'min_score' => 0, 'max_score' => 49.99, 'grade_point' => 0.0, 'description' => 'Fail', 'is_passing' => false]
                ],
                'gpa_4' => [
                    ['grade_label' => 'A', 'min_score' => 90, 'max_score' => 100, 'grade_point' => 4.0, 'description' => 'Excellent', 'is_passing' => true],
                    ['grade_label' => 'B', 'min_score' => 80, 'max_score' => 89.99, 'grade_point' => 3.0, 'description' => 'Good', 'is_passing' => true],
                    ['grade_label' => 'C', 'min_score' => 70, 'max_score' => 79.99, 'grade_point' => 2.0, 'description' => 'Average', 'is_passing' => true],
                    ['grade_label' => 'D', 'min_score' => 60, 'max_score' => 69.99, 'grade_point' => 1.0, 'description' => 'Below Average', 'is_passing' => true],
                    ['grade_label' => 'F', 'min_score' => 0, 'max_score' => 59.99, 'grade_point' => 0.0, 'description' => 'Fail', 'is_passing' => false]
                ],
                'average' => [
                    ['grade_label' => 'A', 'min_score' => 85, 'max_score' => 100, 'grade_point' => null, 'description' => 'Excellent', 'is_passing' => true],
                    ['grade_label' => 'B', 'min_score' => 70, 'max_score' => 84.99, 'grade_point' => null, 'description' => 'Very Good', 'is_passing' => true],
                    ['grade_label' => 'C', 'min_score' => 60, 'max_score' => 69.99, 'grade_point' => null, 'description' => 'Good', 'is_passing' => true],
                    ['grade_label' => 'D', 'min_score' => 50, 'max_score' => 59.99, 'grade_point' => null, 'description' => 'Pass', 'is_passing' => true],
                    ['grade_label' => 'E', 'min_score' => 40, 'max_score' => 49.99, 'grade_point' => null, 'description' => 'Weak Pass', 'is_passing' => true],
                    ['grade_label' => 'F', 'min_score' => 0, 'max_score' => 39.99, 'grade_point' => null, 'description' => 'Fail', 'is_passing' => false]
                ]
            ];

            if (!isset($presets[$presetType])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid preset type. Available: gpa_5, gpa_4, average'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            foreach ($presets[$presetType] as $gradeData) {
                $gradeData['admin_id'] = $user->id;
                $gradeData['academic_year_id'] = $academicYearId;
                $gradeData['is_active'] = true;

                try {
                    $this->gradingSystemModel->createGradeRange($gradeData);
                    $created++;
                } catch (\Exception $e) {
                    // Continue even if one fails (might be overlap)
                    continue;
                }
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Created $created grade ranges from preset",
                'created_count' => $created
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create preset: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
