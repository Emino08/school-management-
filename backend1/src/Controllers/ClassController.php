<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Utils\Validator;
use App\Traits\LogsActivity;
use App\Traits\ResolvesAdminId;

class ClassController
{
    use LogsActivity;
    use ResolvesAdminId;
    
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

            // Log activity
            $this->logActivity(
                $request,
                'create',
                "Created new class: {$data['class_name']}",
                'class',
                $classId,
                ['class_name' => $data['class_name']]
            );

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
        $adminId = $this->getAdminId($request, $user);

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $classes = $this->classModel->getClassesWithStudentCount($adminId, $currentYear ? $currentYear['id'] : null);

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
        $adminId = $this->getAdminId($request, $user);

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
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

    public function exportCSV(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $adminId = $this->getAdminId($request, $user);

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $classes = $this->classModel->getClassesWithStudentCount($user->id, $currentYear ? $currentYear['id'] : null);

            // Create CSV content
            $csv = "Class Name,Grade Level,Description,Capacity,Student Count,Subject Count,Class Master\n";
            
            foreach ($classes as $class) {
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s,%s,%s\n",
                    $this->escapeCsvField($class['class_name'] ?? ''),
                    $class['grade_level'] ?? '',
                    $this->escapeCsvField($class['description'] ?? ''),
                    $class['capacity'] ?? '',
                    $class['student_count'] ?? 0,
                    $class['subject_count'] ?? 0,
                    $this->escapeCsvField($class['class_master_name'] ?? '')
                );
            }

            // Set headers for file download
            $filename = 'classes_export_' . date('Y-m-d_His') . '.csv';
            
            $response = $response
                ->withHeader('Content-Type', 'text/csv; charset=utf-8')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Cache-Control', 'no-cache, must-revalidate')
                ->withHeader('Expires', '0');
            
            $response->getBody()->write($csv);
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function importCSV(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'No file uploaded'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $uploadedFile = $uploadedFiles['file'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'File upload error'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Move uploaded file to temporary location
            $tempFile = sys_get_temp_dir() . '/' . uniqid('class_import_') . '.csv';
            $uploadedFile->moveTo($tempFile);

            // Read and parse CSV
            $handle = fopen($tempFile, 'r');
            if (!$handle) {
                throw new \Exception('Could not open uploaded file');
            }

            $header = fgetcsv($handle); // Skip header row
            $imported = 0;
            $errors = [];
            $skipped = 0;

            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Map CSV columns: Class Name, Grade Level, Description, Capacity
                    $className = trim($row[0] ?? '');
                    $gradeLevel = isset($row[1]) ? (int)$row[1] : 0;
                    $description = trim($row[2] ?? '');
                    $capacity = isset($row[3]) && is_numeric($row[3]) ? (int)$row[3] : null;

                    if (empty($className)) {
                        $skipped++;
                        continue;
                    }

                    // Check if class already exists
                    $existing = $this->classModel->findOne([
                        'class_name' => $className,
                        'admin_id' => $adminId
                    ]);

                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    // Create class
                    $classData = [
                        'class_name' => $className,
                        'grade_level' => $gradeLevel,
                        'description' => $description,
                        'admin_id' => $adminId
                    ];

                    if ($capacity !== null) {
                        $classData['capacity'] = $capacity;
                    }

                    $this->classModel->create($classData);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($imported + $skipped + 1) . ": " . $e->getMessage();
                }
            }

            fclose($handle);
            unlink($tempFile); // Clean up temp file

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Import completed',
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            // Clean up temp file if it exists
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }

            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function downloadTemplate(Request $request, Response $response)
    {
        // Create CSV template with sample data
        $csv = "Class Name,Grade Level,Description,Capacity\n";
        $csv .= "Grade 10A,10,Science class,40\n";
        $csv .= "Grade 10B,10,Arts class,35\n";
        $csv .= "Grade 11A,11,Advanced Mathematics,30\n";

        $filename = 'class_import_template.csv';
        
        $response = $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'no-cache, must-revalidate')
            ->withHeader('Expires', '0');
        
        $response->getBody()->write($csv);
        return $response;
    }

    private function escapeCsvField($field)
    {
        // Escape double quotes and wrap in quotes if contains comma, quote, or newline
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }

    public function getClassSubjects(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $adminId = $this->getAdminId($request, $user);

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
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
        $adminId = $this->getAdminId($request, $user);

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($adminId);
            $subjects = $this->subjectModel->getFreeSubjectsByClass($args['id'], $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'subjects' => $subjects]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch free subjects: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
