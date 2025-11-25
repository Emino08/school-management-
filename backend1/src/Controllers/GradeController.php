<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Grade;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\AcademicYear;
use App\Utils\Validator;
use App\Traits\LogsActivity;

class GradeController
{
    use LogsActivity;

    private $gradeModel;
    private $examModel;
    private $examResultModel;
    private $academicYearModel;

    public function __construct()
    {
        $this->gradeModel = new Grade();
        $this->examModel = new Exam();
        $this->examResultModel = new ExamResult();
        $this->academicYearModel = new AcademicYear();
    }

    public function createExam(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['exam_name' => 'required', 'exam_type' => 'required', 'exam_date' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $data['admin_id'] = $user->id;
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $data['academic_year_id'] = $currentYear['id'];

            $examId = $this->examModel->create(Validator::sanitize($data));

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Exam created successfully', 'exam_id' => $examId]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Creation failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAllExams(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $classId = $params['class_id'] ?? null;
        $academicYearId = $params['academic_year_id'] ?? null;

        try {
            if (!$academicYearId) {
                $currentYear = $this->academicYearModel->getCurrentYear($user->id);
                $academicYearId = $currentYear ? $currentYear['id'] : null;
            }
            
            $exams = $this->examModel->getExamsByAcademicYear($academicYearId, $classId);

            $response->getBody()->write(json_encode(['success' => true, 'exams' => $exams]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch exams: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function recordExamResult(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['student_id' => 'required|numeric', 'exam_id' => 'required|numeric', 'subject_id' => 'required|numeric', 'marks_obtained' => 'required|numeric']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // If submitted by a Teacher, mark as pending and attribute uploader
            if ($user && isset($user->role) && $user->role === 'Teacher') {
                $data['approval_status'] = 'pending';
                $data['uploaded_by_teacher_id'] = $user->id;
            }
            $resultId = $this->examResultModel->recordResult(Validator::sanitize($data));

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Exam result recorded successfully', 'result_id' => $resultId]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Recording failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateGrade(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['student_id' => 'required|numeric', 'subject_id' => 'required|numeric', 'percentage' => 'required|numeric']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $this->gradeModel->updateOrCreateGrade(
                $data['student_id'],
                $data['subject_id'],
                $currentYear['id'],
                $data['percentage'],
                $data['remarks'] ?? null
            );

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Grade updated successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getStudentGrades(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $grades = $this->gradeModel->getStudentGrades($args['studentId'], $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'grades' => $grades]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch grades: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getExamResults(Request $request, Response $response, $args)
    {
        try {
            $results = $this->examResultModel->getStudentResults($args['studentId'], $args['examId']);

            $response->getBody()->write(json_encode(['success' => true, 'results' => $results]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch results: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

