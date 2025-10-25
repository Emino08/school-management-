<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

class PromotionController
{
    /**
     * Process student promotions for an academic year
     * Only runs when all exams for the last term are published
     */
    public function processPromotions(Request $request, Response $response, array $args)
    {
        try {
            $academicYearId = $args['id'] ?? null;
            $db = Database::getInstance()->getConnection();

            // Get academic year details
            $stmt = $db->prepare("
                SELECT * FROM academic_years
                WHERE id = :id
            ");
            $stmt->execute([':id' => $academicYearId]);
            $academicYear = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$academicYear) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Academic year not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check if we're at the last term
            if ($academicYear['current_term'] != $academicYear['number_of_terms']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Promotions can only be processed at the end of the academic year (last term)'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Get the last term
            $stmt = $db->prepare("
                SELECT id FROM terms
                WHERE academic_year_id = :year_id
                AND term_number = :term_number
            ");
            $stmt->execute([
                ':year_id' => $academicYearId,
                ':term_number' => $academicYear['number_of_terms']
            ]);
            $lastTerm = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$lastTerm) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Last term not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check if all exams for the last term are published
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_exams,
                       SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published_exams
                FROM exams
                WHERE academic_year_id = :year_id
                AND term_id = :term_id
            ");
            $stmt->execute([
                ':year_id' => $academicYearId,
                ':term_id' => $lastTerm['id']
            ]);
            $examStats = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($examStats['total_exams'] == 0 || $examStats['published_exams'] < $examStats['total_exams']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'All exams for the last term must be published before processing promotions',
                    'stats' => $examStats
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Get all classes for this academic year
            $stmt = $db->prepare("
                SELECT DISTINCT c.id, c.class_name, c.grade_level
                FROM classes c
                INNER JOIN student_enrollments se ON c.id = se.class_id
                WHERE se.academic_year_id = :year_id
                AND se.status = 'active'
                ORDER BY c.grade_level
            ");
            $stmt->execute([':year_id' => $academicYearId]);
            $classes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $promotionResults = [];

            foreach ($classes as $class) {
                $classPromotions = $this->promoteClassStudents(
                    $db,
                    $academicYearId,
                    $class['id'],
                    $academicYear['promotion_average'],
                    $academicYear['repeat_average'],
                    $academicYear['drop_average']
                );
                $promotionResults[] = [
                    'class_id' => $class['id'],
                    'class_name' => $class['class_name'],
                    'results' => $classPromotions
                ];
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Student promotions processed successfully',
                'data' => $promotionResults
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Promotion error: " . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to process promotions: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Promote students in a specific class based on their performance
     */
    private function promoteClassStudents($db, $academicYearId, $classId, $promotionAvg, $repeatAvg, $dropAvg)
    {
        // Calculate overall average for each student in the class
        $stmt = $db->prepare("
            SELECT
                s.id as student_id,
                s.name as student_name,
                s.admission_no,
                AVG(er.marks_obtained) as overall_average,
                COUNT(DISTINCT er.subject_id) as subjects_taken
            FROM students s
            INNER JOIN student_enrollments se ON s.id = se.student_id
            INNER JOIN exam_results er ON s.id = er.student_id
            INNER JOIN exams e ON er.exam_id = e.id
            WHERE se.class_id = :class_id
            AND se.academic_year_id = :year_id
            AND se.status = 'active'
            AND e.academic_year_id = :year_id2
            AND e.is_published = 1
            AND er.approval_status = 'approved'
            GROUP BY s.id, s.name, s.admission_no
            ORDER BY overall_average DESC
        ");
        $stmt->execute([
            ':class_id' => $classId,
            ':year_id' => $academicYearId,
            ':year_id2' => $academicYearId
        ]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $promoted = [];
        $repeat = [];
        $dropped = [];

        $rank = 1;
        foreach ($students as $student) {
            $average = (float) $student['overall_average'];
            $studentData = [
                'student_id' => $student['student_id'],
                'student_name' => $student['student_name'],
                'admission_no' => $student['admission_no'],
                'average' => round($average, 2),
                'rank' => $rank++
            ];

            if ($average >= $promotionAvg) {
                $promoted[] = $studentData;
                // Update student enrollment status to promoted
                $this->updateStudentStatus($db, $student['student_id'], $academicYearId, 'promoted');
            } elseif ($average >= $repeatAvg) {
                $repeat[] = $studentData;
                // Update student enrollment status to repeat
                $this->updateStudentStatus($db, $student['student_id'], $academicYearId, 'repeat');
            } else {
                $dropped[] = $studentData;
                // Update student enrollment status to dropped
                $this->updateStudentStatus($db, $student['student_id'], $academicYearId, 'dropped');
            }
        }

        return [
            'promoted' => $promoted,
            'repeat' => $repeat,
            'dropped' => $dropped,
            'total_students' => count($students)
        ];
    }

    /**
     * Update student enrollment status
     */
    private function updateStudentStatus($db, $studentId, $academicYearId, $status)
    {
        $stmt = $db->prepare("
            UPDATE student_enrollments
            SET status = :status,
                promotion_status = :promotion_status,
                updated_at = NOW()
            WHERE student_id = :student_id
            AND academic_year_id = :year_id
        ");
        $stmt->execute([
            ':status' => $status === 'promoted' ? 'completed' : $status,
            ':promotion_status' => $status,
            ':student_id' => $studentId,
            ':year_id' => $academicYearId
        ]);
    }

    /**
     * Get promotion statistics for a class
     */
    public function getPromotionStats(Request $request, Response $response, array $args)
    {
        try {
            $academicYearId = $request->getQueryParams()['academic_year_id'] ?? null;
            $classId = $request->getQueryParams()['class_id'] ?? null;

            if (!$academicYearId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Academic year ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $db = Database::getInstance()->getConnection();

            // Get academic year details
            $stmt = $db->prepare("SELECT * FROM academic_years WHERE id = :id");
            $stmt->execute([':id' => $academicYearId]);
            $academicYear = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$academicYear) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Academic year not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Build query
            $sql = "
                SELECT
                    se.promotion_status,
                    COUNT(*) as count
                FROM student_enrollments se
                WHERE se.academic_year_id = :year_id
            ";
            $params = [':year_id' => $academicYearId];

            if ($classId) {
                $sql .= " AND se.class_id = :class_id";
                $params[':class_id'] = $classId;
            }

            $sql .= " GROUP BY se.promotion_status";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'academic_year' => $academicYear,
                    'statistics' => $stats
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Promotion stats error: " . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch promotion statistics: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
