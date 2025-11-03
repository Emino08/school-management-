<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ResultModel;
use App\Models\ResultSummaryModel;
use App\Models\ResultPinModel;
use App\Utils\Validator;

class ResultController
{
    private $resultModel;
    private $summaryModel;
    private $pinModel;

    public function __construct()
    {
        $this->resultModel = new ResultModel();
        $this->summaryModel = new ResultSummaryModel();
        $this->pinModel = new ResultPinModel();
    }

    // Teacher submissions listing
    public function getTeacherSubmissions(Request $request, Response $response, $args)
    {
        $teacherId = $args['id'];
        $user = $request->getAttribute('user');
        // Only the same teacher or admin can view
        if (!$user || ($user->role !== 'Admin' && (string)$user->id !== (string)$teacherId)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $params = $request->getQueryParams();
        $approval = $params['approval_status'] ?? null;
        $examId = $params['exam_id'] ?? null;
        $subjectId = $params['subject_id'] ?? null;
        $classId = $params['class_id'] ?? null;
        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $sql = "SELECT er.*, s.name as student_name, sub.subject_name, e.exam_name, c.class_name
                    FROM exam_results er
                    INNER JOIN students s ON er.student_id = s.id
                    INNER JOIN subjects sub ON er.subject_id = sub.id
                    INNER JOIN exams e ON er.exam_id = e.id
                    LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.academic_year_id = er.academic_year_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    WHERE er.uploaded_by_teacher_id = :tid";
            $bindings = [':tid' => $teacherId];
            if ($approval) { $sql .= " AND er.approval_status = :appr"; $bindings[':appr'] = $approval; }
            if ($examId) { $sql .= " AND er.exam_id = :exam"; $bindings[':exam'] = $examId; }
            if ($subjectId) { $sql .= " AND er.subject_id = :subj"; $bindings[':subj'] = $subjectId; }
            if ($classId) { $sql .= " AND c.id = :cls"; $bindings[':cls'] = $classId; }
            $sql .= " ORDER BY e.exam_date DESC, sub.subject_name";
            $stmt = $db->prepare($sql);
            $stmt->execute($bindings);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode(['success' => true, 'submissions' => $rows]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch submissions: '.$e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get student results for a specific exam (WITH PUBLICATION DATE CHECK)
    public function getStudentResults(Request $request, Response $response, $args)
    {
        $studentId = $args['studentId'];
        $examId = $args['examId'];

        try {
            // Check if results are published and if publication date has passed
            $publicationModel = new \App\Models\ResultPublication();
            $isPublished = $publicationModel->isResultPublished($examId);

            if (!$isPublished) {
                // Get publication info to show when results will be available
                $publication = $publicationModel->getPublicationByExam($examId);

                if ($publication) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Results will be available on ' . $publication['publication_date'],
                        'publication_date' => $publication['publication_date'],
                        'is_published' => false
                    ]));
                } else {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Results are not yet published',
                        'is_published' => false
                    ]));
                }
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Only fetch approved and published results
            $sql = "SELECT er.*, s.subject_name, s.subject_code, e.exam_name, e.exam_type
                    FROM exam_results er
                    JOIN subjects s ON er.subject_id = s.id
                    JOIN exams e ON er.exam_id = e.id
                    WHERE er.student_id = :student_id
                    AND er.exam_id = :exam_id
                    AND er.approval_status = 'approved'
                    AND er.is_published = TRUE
                    ORDER BY s.subject_name";

            $stmt = \App\Config\Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute([':student_id' => $studentId, ':exam_id' => $examId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $summary = $this->summaryModel->getStudentSummary($studentId, $examId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'results' => $results,
                'summary' => $summary,
                'is_published' => true
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get all results for a student
    public function getAllStudentResults(Request $request, Response $response, $args)
    {
        $studentId = $args['studentId'];
        $params = $request->getQueryParams();
        $yearId = $params['year_id'] ?? null;

        try {
            $results = $this->resultModel->getStudentAllResults($studentId, $yearId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'results' => $results
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Check result with PIN (Public endpoint - Student ID + PIN)
    public function checkResultWithPin(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $pin = $data['pin'] ?? '';
        $studentId = $data['student_id'] ?? '';

        $errors = Validator::validate($data, ['pin' => 'required', 'student_id' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Validate and use PIN (increments usage count)
            $pinData = $this->pinModel->validateAndUsePin($pin, $studentId);

            if (!$pinData) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid PIN, Student ID, or PIN has expired/reached maximum checks'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get all published results for this student
            $sql = "SELECT er.*, s.subject_name, s.subject_code, e.exam_name, e.exam_type
                    FROM exam_results er
                    JOIN subjects s ON er.subject_id = s.id
                    JOIN exams e ON er.exam_id = e.id
                    WHERE er.student_id = :student_id
                    AND er.approval_status = 'approved'
                    AND er.is_published = TRUE
                    ORDER BY e.exam_date DESC, s.subject_name";

            $stmt = \App\Config\Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute([':student_id' => $studentId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'student_name' => $pinData['student_name'],
                'student_id' => $studentId,
                'admission_no' => $pinData['admission_no'],
                'remaining_checks' => $pinData['remaining_checks'],
                'results' => $results,
                'message' => "PIN valid. Remaining checks: {$pinData['remaining_checks']}"
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to check result: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Generate PIN for student
    public function generatePin(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, [
            'student_id' => 'required',
            'exam_id' => 'required',
            'academic_year_id' => 'required'
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
            $pin = $this->pinModel->createPinForStudent(
                $user->id,
                $data['student_id'],
                $data['exam_id'],
                $data['academic_year_id'],
                $data['valid_days'] ?? 30
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'PIN generated successfully',
                'pin' => $pin
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate PIN: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Bulk generate PINs for class
    public function bulkGeneratePins(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, [
            'class_id' => 'required',
            'exam_id' => 'required',
            'academic_year_id' => 'required'
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
            $pins = $this->pinModel->bulkCreatePinsForClass(
                $user->id,
                $data['class_id'],
                $data['max_checks'] ?? 5,
                $data['valid_days'] ?? 30
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'PINs generated successfully',
                'pins' => $pins,
                'total' => count($pins)
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate PINs: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Bulk generate PINs for ALL students
    public function bulkGeneratePinsAllStudents(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        try {
            $pins = $this->pinModel->bulkCreatePinsForAllStudents(
                $user->id,
                $data['max_checks'] ?? 5,
                $data['valid_days'] ?? 30
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'PINs generated for all students successfully',
                'pins' => $pins,
                'total' => count($pins)
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate PINs: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Export PINs as CSV
    public function exportPinsCSV(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $classId = $params['class_id'] ?? null;
        $academicYearId = $params['academic_year_id'] ?? null;

        try {
            $pins = $this->pinModel->getPinsForExport($user->id, $classId, $academicYearId);

            // Generate CSV content
            $csv = "PIN Code,Student Name,Admission No,ID Number,Class,Max Checks,Used Checks,Remaining,Status,Expires At,Created At\n";

            foreach ($pins as $pin) {
                $csv .= sprintf(
                    '"%s","%s","%s","%s","%s",%d,%d,%d,"%s","%s","%s"' . "\n",
                    $pin['pin_code'],
                    $pin['student_name'],
                    $pin['admission_no'] ?? '',
                    $pin['id_number'] ?? '',
                    $pin['class_name'] ?? '',
                    $pin['max_checks'],
                    $pin['used_checks'],
                    $pin['remaining_checks'],
                    $pin['status'],
                    $pin['expires_at'] ?? 'Never',
                    $pin['created_at']
                );
            }

            $response->getBody()->write($csv);
            return $response
                ->withHeader('Content-Type', 'text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="result_pins_' . date('Y-m-d_His') . '.csv"');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to export PINs: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get all PINs created by admin
    public function getAdminPins(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $academicYearId = $params['academic_year_id'] ?? null;

        try {
            $pins = $this->pinModel->getPinsByAdmin($user->id, $academicYearId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'pins' => $pins
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch PINs: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Publish results for an exam (ENHANCED WITH APPROVAL CHECK)
    public function publishResults(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        if (!$user || $user->role !== 'Admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only Admin/Principal can publish results'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Validate publication date
        $errors = Validator::validate($data, ['publication_date' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Publication date is required',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Check how many results are approved vs pending
            $sql = "SELECT
                        COUNT(*) as total_results,
                        COUNT(CASE WHEN approval_status = 'approved' THEN 1 END) as approved_count,
                        COUNT(CASE WHEN approval_status = 'pending' THEN 1 END) as pending_count,
                        COUNT(CASE WHEN approval_status = 'rejected' THEN 1 END) as rejected_count
                    FROM exam_results
                    WHERE exam_id = :exam_id";

            $stmt = \App\Config\Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute([':exam_id' => $examId]);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

            // If there are pending results, warn but allow publication of approved only
            if ($stats['pending_count'] > 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => "Cannot publish: {$stats['pending_count']} results are still pending approval by exam officer",
                    'stats' => $stats
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if ($stats['approved_count'] == 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No approved results to publish',
                    'stats' => $stats
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Get exam details
            $exam = \App\Config\Database::getInstance()->getConnection()->prepare("SELECT * FROM exams WHERE id = :exam_id");
            $exam->execute([':exam_id' => $examId]);
            $examData = $exam->fetch(\PDO::FETCH_ASSOC);

            if (!$examData) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Exam not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Create publication record
            $publicationModel = new \App\Models\ResultPublication();

            // Check if already published
            $existing = $publicationModel->getPublicationByExam($examId);
            if ($existing) {
                // Update existing publication
                $publicationModel->update($existing['id'], [
                    'publication_date' => $data['publication_date'],
                    'total_students' => $stats['total_results'],
                    'approved_results' => $stats['approved_count'],
                    'pending_results' => $stats['pending_count'],
                    'is_active' => true,
                    'notes' => $data['notes'] ?? null
                ]);
            } else {
                // Create new publication
                $publicationModel->create([
                    'admin_id' => $user->id,
                    'exam_id' => $examId,
                    'academic_year_id' => $examData['academic_year_id'],
                    'term' => $examData['term'] ?? 1,
                    'publication_date' => $data['publication_date'],
                    'published_by_admin_id' => $user->id,
                    'total_students' => $stats['total_results'],
                    'approved_results' => $stats['approved_count'],
                    'pending_results' => $stats['pending_count'],
                    'is_active' => true,
                    'notes' => $data['notes'] ?? null
                ]);
            }

            // Mark ONLY approved results as published
            $updateSql = "UPDATE exam_results
                          SET is_published = TRUE
                          WHERE exam_id = :exam_id
                          AND approval_status = 'approved'";
            $updateStmt = \App\Config\Database::getInstance()->getConnection()->prepare($updateSql);
            $updateStmt->execute([':exam_id' => $examId]);

            // Update exam status
            $examUpdateSql = "UPDATE exams
                              SET can_be_published = TRUE,
                                  published_date = :publication_date
                              WHERE id = :exam_id";
            $examUpdateStmt = \App\Config\Database::getInstance()->getConnection()->prepare($examUpdateSql);
            $examUpdateStmt->execute([
                ':exam_id' => $examId,
                ':publication_date' => $data['publication_date']
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Results scheduled for publication on {$data['publication_date']}",
                'publication_stats' => [
                    'total_results' => $stats['total_results'],
                    'approved_and_published' => $stats['approved_count'],
                    'rejected' => $stats['rejected_count'],
                    'publication_date' => $data['publication_date']
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to publish results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get class ranking
    public function getClassRanking(Request $request, Response $response, $args)
    {
        $classId = $args['classId'];
        $examId = $args['examId'];
        $params = $request->getQueryParams();
        $limit = $params['limit'] ?? 10;

        try {
            $ranking = $this->summaryModel->getClassRanking($classId, $examId, $limit);

            $response->getBody()->write(json_encode([
                'success' => true,
                'ranking' => $ranking
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch ranking: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Get all results for a specific exam (Admin view)
    public function getExamResults(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $params = $request->getQueryParams();
        $studentId = $params['student_id'] ?? null;
        $approvalStatus = $params['approval_status'] ?? null;

        try {
            $sql = "SELECT er.*,
                           s.name as student_name,
                           s.admission_no,
                           sub.subject_name,
                           sub.subject_code,
                           t.name as teacher_name,
                           gs.grade_label as grade
                    FROM exam_results er
                    JOIN students s ON er.student_id = s.id
                    JOIN subjects sub ON er.subject_id = sub.id
                    LEFT JOIN teachers t ON er.uploaded_by_teacher_id = t.id
                    LEFT JOIN grading_system gs ON (
                        ((er.test_score + er.exam_score) / 2) >= gs.min_score
                        AND ((er.test_score + er.exam_score) / 2) <= gs.max_score
                        AND (gs.academic_year_id = er.academic_year_id OR gs.academic_year_id IS NULL)
                    )
                    WHERE er.exam_id = :exam_id";

            $bindings = [':exam_id' => $examId];

            if ($studentId) {
                $sql .= " AND er.student_id = :student_id";
                $bindings[':student_id'] = $studentId;
            }

            $user = $request->getAttribute('user');
            $includeUnverified = isset($params['include_unverified']) && ($params['include_unverified'] === '1' || $params['include_unverified'] === 'true');
            if ($approvalStatus) {
                $sql .= " AND er.approval_status = :approval_status";
                $bindings[':approval_status'] = $approvalStatus;
            } else {
                if (!($includeUnverified && $user && $user->role === 'Admin')) {
                    $sql .= " AND er.approval_status = 'approved'";
                }
            }

            // Admin/Principal should only see verified grades unless explicitly requested
            if ($user && $user->role === 'Admin') {
                $showUnverified = isset($params['show_unverified']) && ($params['show_unverified'] === '1' || $params['show_unverified'] === 'true');
                if (!$showUnverified) {
                    $sql .= " AND er.is_verified = TRUE";
                }
            }

            $sql .= " ORDER BY s.name, sub.subject_name";

            $stmt = \App\Config\Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($bindings);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'results' => $results,
                'total' => count($results)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch results: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

