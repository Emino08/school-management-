<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use App\Utils\Validator;

class TermController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCurrentTerm(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            // Get current academic year
            $stmt = $this->db->prepare("
                SELECT id, current_term, exams_per_term, total_terms, year_name
                FROM academic_years
                WHERE admin_id = :admin_id AND is_current = 1
                LIMIT 1
            ");
            $stmt->execute([':admin_id' => $user->id]);
            $academicYear = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$academicYear) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No active academic year found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get current term details
            $stmt = $this->db->prepare("
                SELECT * FROM terms
                WHERE academic_year_id = :year_id AND term_number = :term_number
                LIMIT 1
            ");
            $stmt->execute([
                ':year_id' => $academicYear['id'],
                ':term_number' => $academicYear['current_term']
            ]);
            $currentTerm = $stmt->fetch(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'academic_year' => $academicYear,
                'current_term' => $currentTerm
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch current term: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createTermsForYear(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, [
            'academic_year_id' => 'required|numeric',
            'exams_per_term' => 'required|numeric'
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
            $academicYearId = $data['academic_year_id'];
            $examsPerTerm = $data['exams_per_term'];
            $totalTerms = $data['total_terms'] ?? 3;

            // Get academic year dates
            $stmt = $this->db->prepare("SELECT start_date, end_date FROM academic_years WHERE id = :id");
            $stmt->execute([':id' => $academicYearId]);
            $year = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$year) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Academic year not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Calculate term dates (divide year into equal parts)
            $startDate = new \DateTime($year['start_date']);
            $endDate = new \DateTime($year['end_date']);
            $totalDays = $startDate->diff($endDate)->days;
            $daysPerTerm = floor($totalDays / $totalTerms);

            // Delete existing terms for this year
            $stmt = $this->db->prepare("DELETE FROM terms WHERE academic_year_id = :year_id");
            $stmt->execute([':year_id' => $academicYearId]);

            // Create terms
            $terms = [];
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

                $stmt = $this->db->prepare("
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

                $terms[] = [
                    'id' => $this->db->lastInsertId(),
                    'term_number' => $i,
                    'term_name' => 'Term ' . $i,
                    'start_date' => $termStart->format('Y-m-d'),
                    'end_date' => $termEnd->format('Y-m-d'),
                    'is_current' => ($i === 1),
                    'exams_required' => $examsPerTerm
                ];
            }

            // Update academic year
            $stmt = $this->db->prepare("
                UPDATE academic_years
                SET exams_per_term = :exams_per_term,
                    total_terms = :total_terms,
                    current_term = 1
                WHERE id = :id
            ");
            $stmt->execute([
                ':exams_per_term' => $examsPerTerm,
                ':total_terms' => $totalTerms,
                ':id' => $academicYearId
            ]);

            // Log activity
            $logger = new \App\Utils\ActivityLogger($this->db);
            $logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'create',
                "Created {$totalTerms} terms for academic year ID: {$academicYearId}",
                'term',
                null,
                ['academic_year_id' => $academicYearId, 'total_terms' => $totalTerms, 'exams_per_term' => $examsPerTerm]
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Terms created successfully',
                'terms' => $terms
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create terms: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function checkAndToggleTerm(Request $request, Response $response, $args)
    {
        $academicYearId = $args['yearId'];
        $user = $request->getAttribute('user');

        try {
            // Get academic year
            $stmt = $this->db->prepare("
                SELECT id, current_term, total_terms, exams_per_term
                FROM academic_years
                WHERE id = :id AND admin_id = :admin_id
            ");
            $stmt->execute([':id' => $academicYearId, ':admin_id' => $user->id]);
            $academicYear = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$academicYear) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Academic year not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $currentTerm = $academicYear['current_term'];
            $totalTerms = $academicYear['total_terms'];
            $examsPerTerm = $academicYear['exams_per_term'];

            // Get current term
            $stmt = $this->db->prepare("
                SELECT * FROM terms
                WHERE academic_year_id = :year_id AND term_number = :term_number
            ");
            $stmt->execute([
                ':year_id' => $academicYearId,
                ':term_number' => $currentTerm
            ]);
            $term = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$term) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Current term not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Check if we should toggle to next term
            $shouldToggle = false;
            $examsPublished = $term['exams_published'];

            if ($examsPublished >= $examsPerTerm && $currentTerm < $totalTerms) {
                $shouldToggle = true;
                $nextTerm = $currentTerm + 1;

                // Update current term to not current
                $stmt = $this->db->prepare("UPDATE terms SET is_current = 0 WHERE id = :id");
                $stmt->execute([':id' => $term['id']]);

                // Set next term as current
                $stmt = $this->db->prepare("
                    UPDATE terms
                    SET is_current = 1
                    WHERE academic_year_id = :year_id AND term_number = :term_number
                ");
                $stmt->execute([
                    ':year_id' => $academicYearId,
                    ':term_number' => $nextTerm
                ]);

                // Update academic year
                $stmt = $this->db->prepare("
                    UPDATE academic_years
                    SET current_term = :current_term
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':current_term' => $nextTerm,
                    ':id' => $academicYearId
                ]);

                // Log activity
                $logger = new \App\Utils\ActivityLogger($this->db);
                $logger->logFromRequest(
                    $request,
                    $user->id,
                    'system',
                    'update',
                    "Auto-toggled from Term {$currentTerm} to Term {$nextTerm}",
                    'academic_year',
                    $academicYearId,
                    ['from_term' => $currentTerm, 'to_term' => $nextTerm, 'reason' => 'exams_published_threshold_reached']
                );

                $response->getBody()->write(json_encode([
                    'success' => true,
                    'toggled' => true,
                    'message' => "Toggled to Term {$nextTerm}",
                    'previous_term' => $currentTerm,
                    'current_term' => $nextTerm
                ]));
            } else {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'toggled' => false,
                    'message' => 'No term toggle needed',
                    'exams_published' => $examsPublished,
                    'exams_required' => $examsPerTerm,
                    'current_term' => $currentTerm
                ]));
            }

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to check term toggle: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function publishExam(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $user = $request->getAttribute('user');

        try {
            // Get exam details
            $stmt = $this->db->prepare("SELECT * FROM exams WHERE id = :id");
            $stmt->execute([':id' => $examId]);
            $exam = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$exam) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Exam not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($exam['is_published']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Exam already published'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Mark exam as published
            $stmt = $this->db->prepare("
                UPDATE exams
                SET is_published = 1, published_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([':id' => $examId]);

            // Get the term for this exam
            if ($exam['term_id']) {
                // Increment exams_published counter
                $stmt = $this->db->prepare("
                    UPDATE terms
                    SET exams_published = exams_published + 1
                    WHERE id = :term_id
                ");
                $stmt->execute([':term_id' => $exam['term_id']]);

                // Get term details to check for toggle
                $stmt = $this->db->prepare("SELECT * FROM terms WHERE id = :id");
                $stmt->execute([':id' => $exam['term_id']]);
                $term = $stmt->fetch(\PDO::FETCH_ASSOC);

                // Check if we should toggle to next term
                if ($term && $term['exams_published'] >= $term['exams_required']) {
                    // Trigger term toggle check
                    $stmt = $this->db->prepare("SELECT academic_year_id FROM terms WHERE id = :id");
                    $stmt->execute([':id' => $exam['term_id']]);
                    $termData = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($termData) {
                        // Create a new request to trigger toggle
                        $toggleRequest = $request->withAttribute('academic_year_id', $termData['academic_year_id']);
                        $this->checkAndToggleTerm($toggleRequest, $response, ['yearId' => $termData['academic_year_id']]);
                    }
                }
            }

            // Log activity
            $logger = new \App\Utils\ActivityLogger($this->db);
            $logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'publish',
                "Published exam ID: {$examId}",
                'exam',
                $examId
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Exam published successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to publish exam: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function manualToggleTerm(Request $request, Response $response, $args)
    {
        $academicYearId = $args['yearId'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['term_number' => 'required|numeric']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $newTermNumber = $data['term_number'];

            // Set all terms to not current
            $stmt = $this->db->prepare("UPDATE terms SET is_current = 0 WHERE academic_year_id = :year_id");
            $stmt->execute([':year_id' => $academicYearId]);

            // Set specified term as current
            $stmt = $this->db->prepare("
                UPDATE terms
                SET is_current = 1
                WHERE academic_year_id = :year_id AND term_number = :term_number
            ");
            $stmt->execute([
                ':year_id' => $academicYearId,
                ':term_number' => $newTermNumber
            ]);

            // Update academic year
            $stmt = $this->db->prepare("UPDATE academic_years SET current_term = :term WHERE id = :id");
            $stmt->execute([':term' => $newTermNumber, ':id' => $academicYearId]);

            // Log activity
            $logger = new \App\Utils\ActivityLogger($this->db);
            $logger->logFromRequest(
                $request,
                $user->id,
                'admin',
                'update',
                "Manually toggled to Term {$newTermNumber}",
                'academic_year',
                $academicYearId,
                ['new_term' => $newTermNumber]
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Successfully switched to Term {$newTermNumber}"
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to toggle term: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
