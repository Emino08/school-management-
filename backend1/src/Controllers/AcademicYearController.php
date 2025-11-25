<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Utils\Validator;
use App\Traits\LogsActivity;

class AcademicYearController
{
    use LogsActivity;

    private $academicYearModel;
    private $enrollmentModel;

    public function __construct()
    {
        $this->academicYearModel = new AcademicYear();
        $this->enrollmentModel = new StudentEnrollment();
    }

    public function create(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $rules = ['year_name' => 'required', 'start_date' => 'required', 'end_date' => 'required'];
        // Optional booleans when provided
        foreach (['is_current','auto_calculate_position'] as $b) {
            if (array_key_exists($b, $data) && $data[$b] !== '') {
                $rules[$b] = isset($rules[$b]) ? $rules[$b] . '|boolean' : 'boolean';
            }
        }
        // Optional numeric when provided
        foreach (['number_of_terms','exams_per_term'] as $n) {
            if (array_key_exists($n, $data) && $data[$n] !== '') {
                $rules[$n] = isset($rules[$n]) ? $rules[$n] . '|numeric' : 'numeric';
            }
        }
        $errors = Validator::validate($data, $rules);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $numberOfTerms = $data['number_of_terms'] ?? 3;

            // Convert boolean values properly
            $isCurrent = isset($data['is_current']) ? filter_var($data['is_current'], FILTER_VALIDATE_BOOLEAN) : false;
            $autoCalculatePosition = isset($data['auto_calculate_position']) ? filter_var($data['auto_calculate_position'], FILTER_VALIDATE_BOOLEAN) : true;

            $yearData = [
                'admin_id' => $user->id,
                'year_name' => $data['year_name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => $isCurrent ? 1 : 0,
                'status' => $data['status'] ?? 'active',
                // New fields for grading configuration
                'number_of_terms' => $numberOfTerms,
                'exams_per_term' => $data['exams_per_term'] ?? 1,
                'grading_type' => $data['grading_type'] ?? 'average',
                'result_publication_date' => !empty($data['result_publication_date']) ? $data['result_publication_date'] : null,
                'auto_calculate_position' => $autoCalculatePosition ? 1 : 0,
                // Fees per term
                'term_1_fee' => $data['term_1_fee'] ?? 0.00,
                'term_1_min_payment' => $data['term_1_min_payment'] ?? ($data['term_1_fee'] * 0.5) ?? 0.00,
                'term_2_fee' => $data['term_2_fee'] ?? 0.00,
                'term_2_min_payment' => $data['term_2_min_payment'] ?? ($data['term_2_fee'] * 0.5) ?? 0.00,
                'term_3_fee' => ($numberOfTerms == 3) ? ($data['term_3_fee'] ?? 0.00) : null,
                'term_3_min_payment' => ($numberOfTerms == 3) ? ($data['term_3_min_payment'] ?? ($data['term_3_fee'] * 0.5) ?? 0.00) : null
            ];

            // Validate number_of_terms
            if (!in_array($yearData['number_of_terms'], [2, 3])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Number of terms must be 2 or 3'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validate exams_per_term
            if (!in_array($yearData['exams_per_term'], [1, 2])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Exams per term must be 1 or 2'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validate grading_type
            if (!in_array($yearData['grading_type'], ['average', 'gpa_5', 'gpa_4', 'custom'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Grading type must be: average, gpa_5, gpa_4, or custom'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $yearId = $this->academicYearModel->create(Validator::sanitize($yearData));

            // Automatically create terms for this academic year
            $this->autoCreateTerms($yearId, $yearData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Academic year and terms created successfully',
                'academic_year_id' => $yearId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Creation failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAll(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $years = $this->academicYearModel->findAll(['admin_id' => $user->id]);
            $response->getBody()->write(json_encode(['success' => true, 'academic_years' => $years]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch academic years: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getCurrent(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            if (!$currentYear) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'No current academic year set']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode(['success' => true, 'academic_year' => $currentYear]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch current year: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function setCurrent(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // Get year ID from args or POST body
        $yearId = $args['id'] ?? $data['id'] ?? $data['year_id'] ?? null;

        if (!$yearId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Academic year ID is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->academicYearModel->setCurrentYear($user->id, $yearId);
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Current academic year set successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to set current year: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function promoteStudents(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $passingPercentage = $data['passing_percentage'] ?? 40;

        try {
            // Ensure placement/capacity columns exist for downstream queries
            $db = \App\Config\Database::getInstance()->getConnection();
            \App\Utils\Schema::ensureClassCapacityColumns($db);
            $result = $this->enrollmentModel->promoteStudents($args['id'], $passingPercentage);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student promotion completed',
                'result' => $result
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Promotion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function completeYear(Request $request, Response $response, $args)
    {
        try {
            // Ensure placement/capacity columns exist for downstream queries
            $db = \App\Config\Database::getInstance()->getConnection();
            \App\Utils\Schema::ensureClassCapacityColumns($db);
            // First promote students
            $result = $this->enrollmentModel->promoteStudents($args['id']);

            // Then complete the year
            $this->academicYearModel->completeYear($args['id']);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Academic year completed successfully',
                'promotion_result' => $result
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to complete year: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        try {
            $this->academicYearModel->delete($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Academic year deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function autoCreateTerms($academicYearId, $yearData)
    {
        try {
            $db = \App\Config\Database::getInstance()->getConnection();

            $totalTerms = $yearData['number_of_terms'];
            $examsPerTerm = $yearData['exams_per_term'];
            $startDate = new \DateTime($yearData['start_date']);
            $endDate = new \DateTime($yearData['end_date']);

            // Calculate days per term
            $totalDays = $startDate->diff($endDate)->days;
            $daysPerTerm = floor($totalDays / $totalTerms);

            // Create terms and exams
            for ($i = 1; $i <= $totalTerms; $i++) {
                $termStart = clone $startDate;
                if ($i > 1) {
                    $termStart->add(new \DateInterval('P' . ($daysPerTerm * ($i - 1)) . 'D'));
                }

                $termEnd = clone $termStart;
                if ($i < $totalTerms) {
                    $termEnd->add(new \DateInterval('P' . ($daysPerTerm - 1) . 'D'));
                } else {
                    $termEnd = clone $endDate;
                }

                // Insert term
                $stmt = $db->prepare("
                    INSERT INTO terms (academic_year_id, term_number, term_name, start_date, end_date,
                                      is_current, exams_required, exams_published)
                    VALUES (:year_id, :term_number, :term_name, :start_date, :end_date, :is_current, :exams_required, 0)
                ");

                $stmt->execute([
                    ':year_id' => $academicYearId,
                    ':term_number' => $i,
                    ':term_name' => 'Term ' . $i,
                    ':start_date' => $termStart->format('Y-m-d'),
                    ':end_date' => $termEnd->format('Y-m-d'),
                    ':is_current' => ($i === 1) ? 1 : 0,
                    ':exams_required' => $examsPerTerm
                ]);

                $termId = $db->lastInsertId();

                // Auto-create exams for this term
                // If examsPerTerm = 1, create "final" exam
                // If examsPerTerm = 2, create "test" and "final" exams
                if ($examsPerTerm == 1) {
                    // Single exam
                    $this->createTermExam($db, $academicYearId, $termId, $i, 'final', $termEnd, $yearData['admin_id']);
                } elseif ($examsPerTerm == 2) {
                    // Two exams: test (mid-term) and final (end-term)
                    $daysInTerm = $termStart->diff($termEnd)->days;
                    $midTermDate = clone $termStart;
                    $midTermDate->add(new \DateInterval('P' . floor($daysInTerm / 2) . 'D'));

                    $this->createTermExam($db, $academicYearId, $termId, $i, 'test', $midTermDate, $yearData['admin_id']);
                    $this->createTermExam($db, $academicYearId, $termId, $i, 'final', $termEnd, $yearData['admin_id']);
                }
            }

            // Update academic year with term info
            $stmt = $db->prepare("
                UPDATE academic_years
                SET current_term = 1, total_terms = :total_terms
                WHERE id = :id
            ");
            $stmt->execute([
                ':total_terms' => $totalTerms,
                ':id' => $academicYearId
            ]);

            return true;
        } catch (\Exception $e) {
            // Log error but don't fail the academic year creation
            error_log("Failed to create terms: " . $e->getMessage());
            return false;
        }
    }

    private function createTermExam($db, $academicYearId, $termId, $termNumber, $examType, $examDate, $adminId)
    {
        try {
            $stmt = $db->prepare("
                INSERT INTO exams (admin_id, academic_year_id, term_id, exam_name, exam_type, exam_date, is_published, created_at)
                VALUES (:admin_id, :academic_year_id, :term_id, :exam_name, :exam_type, :exam_date, 0, NOW())
            ");

            // Format exam name nicely (capitalize first letter for display)
            $examTypeDisplay = ucfirst($examType);
            $examName = "Term {$termNumber} - {$examTypeDisplay}";

            $stmt->execute([
                ':admin_id' => $adminId,
                ':academic_year_id' => $academicYearId,
                ':term_id' => $termId,
                ':exam_name' => $examName,
                ':exam_type' => $examType,
                ':exam_date' => $examDate->format('Y-m-d')
            ]);

            return $db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Failed to create exam: " . $e->getMessage());
            return false;
        }
    }
}

