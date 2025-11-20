<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Utils\Validator;

class SubjectController
{
    private $subjectModel;
    private $academicYearModel;

    public function __construct()
    {
        $this->subjectModel = new Subject();
        $this->academicYearModel = new AcademicYear();
    }

    public function create(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['subject_name' => 'required', 'class_id' => 'required|numeric', 'subject_code' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $data['admin_id'] = $user->id;
            // Duplicate check: same admin + class + subject_code
            $existing = $this->subjectModel->findOne([
                'admin_id' => $user->id,
                'class_id' => $data['class_id'],
                'subject_code' => $data['subject_code']
            ]);
            if ($existing) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Duplicate subject code for this class']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }
            $subjectId = $this->subjectModel->create(Validator::sanitize($data));

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Subject created successfully', 'subject_id' => $subjectId]));
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
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $subjects = $this->subjectModel->getSubjectsWithDetails(
                $user->id,
                $currentYear['id'] ?? null
            );
            $response->getBody()->write(json_encode(['success' => true, 'subjects' => $subjects]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch subjects: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getSubject(Request $request, Response $response, $args)
    {
        $subject = $this->subjectModel->findById($args['id']);
        if (!$subject) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Subject not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode(['success' => true, 'subject' => $subject]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, $args)
    {
        $data = Validator::sanitize($request->getParsedBody());
        unset($data['id'], $data['admin_id']);

        try {
            $this->subjectModel->update($args['id'], $data);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Subject updated successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        try {
            $this->subjectModel->deleteSubject($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Subject deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
