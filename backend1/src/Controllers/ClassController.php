<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Utils\Validator;

class ClassController
{
    private $classModel;
    private $subjectModel;
    private $academicYearModel;

    public function __construct()
    {
        $this->classModel = new ClassModel();
        $this->subjectModel = new Subject();
        $this->academicYearModel = new AcademicYear();
    }

    public function create(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        // Support legacy frontend format (sclassName) by mapping it to class_name
        if (isset($data['sclassName']) && !isset($data['class_name'])) {
            $data['class_name'] = $data['sclassName'];
        }

        // Support legacy adminID format
        if (isset($data['adminID']) && !isset($data['admin_id'])) {
            $data['admin_id'] = $data['adminID'];
        }

        // If grade_level is not provided, extract from class_name or default to 0
        if (!isset($data['grade_level'])) {
            // Try to extract numeric grade from class name (e.g., "Grade 10A" -> 10)
            if (preg_match('/\d+/', $data['class_name'] ?? '', $matches)) {
                $data['grade_level'] = (int)$matches[0];
            } else {
                $data['grade_level'] = 0; // Default grade level
            }
        }

        $errors = Validator::validate($data, ['class_name' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $data['admin_id'] = $user->id;

            // Remove legacy field names before creating
            unset($data['sclassName'], $data['adminID']);

            $classId = $this->classModel->create(Validator::sanitize($data));

            // Fetch the created class to return its details
            $createdClass = $this->classModel->findById($classId);

            // Format for frontend compatibility
            $createdClass['_id'] = $classId;
            $createdClass['sclassName'] = $createdClass['class_name'];

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Class created successfully',
                'class_id' => $classId,
                '_id' => $classId,
                'sclassName' => $createdClass['class_name'],
                'class' => $createdClass
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
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $classes = $this->classModel->getClassesWithStudentCount($user->id, $currentYear ? $currentYear['id'] : null);

            // Add frontend compatibility fields to each class
            foreach ($classes as &$class) {
                $class['_id'] = $class['id'];
                $class['sclassName'] = $class['class_name'];
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'classes' => $classes
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch classes: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getClass(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $class = $this->classModel->getClassWithDetails(
                $args['id'],
                $user->id ?? null,
                $currentYear ? $currentYear['id'] : null
            );

            if (!$class) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Class not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Add frontend compatibility fields
            $class['_id'] = $class['id'];
            $class['sclassName'] = $class['class_name'];
            $class['classMaster'] = $class['class_master_name'] ?? ($class['classMaster'] ?? null);
            $class['studentCount'] = $class['student_count'] ?? 0;
            $class['subjectCount'] = $class['subject_count'] ?? 0;

            $response->getBody()->write(json_encode([
                'success' => true,
                'class' => $class
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch class: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        $data = Validator::sanitize($request->getParsedBody());
        unset($data['id'], $data['admin_id']);

        try {
            // Ensure new placement/capacity columns exist before update
            $db = \App\Config\Database::getInstance()->getConnection();
            \App\Utils\Schema::ensureClassCapacityColumns($db);
            $this->classModel->update($args['id'], $data);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Class updated successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        try {
            $this->classModel->deleteClass($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Class deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getClassSubjects(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $subjects = $this->subjectModel->getSubjectsWithTeacher($args['id'], $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'subjects' => $subjects]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch subjects: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getFreeSubjects(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $subjects = $this->subjectModel->getFreeSubjectsByClass($args['id'], $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'subjects' => $subjects]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch free subjects: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
