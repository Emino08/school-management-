<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\SubjectRanking;
use App\Models\ClassRanking;

class RankingController
{
    private $subjectRanking;
    private $classRanking;

    public function __construct()
    {
        $this->subjectRanking = new SubjectRanking();
        $this->classRanking = new ClassRanking();
    }

    /**
     * Calculate rankings for a specific subject in an exam
     */
    public function calculateSubjectRankings(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $subjectId = $args['subjectId'];
        $classId = $args['classId'];

        try {
            $result = $this->subjectRanking->calculateSubjectRankings($examId, $subjectId, $classId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Subject rankings calculated successfully',
                'data' => $result
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to calculate subject rankings: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Calculate overall class rankings for an exam
     */
    public function calculateClassRankings(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $classId = $args['classId'];

        try {
            $result = $this->classRanking->calculateClassRankings($examId, $classId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Class rankings calculated successfully',
                'data' => $result
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to calculate class rankings: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Calculate all rankings for an exam (all subjects and all classes)
     */
    public function calculateAllRankings(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];

        try {
            // Get all subject-class combinations for this exam
            $sql = "SELECT DISTINCT er.subject_id, s.sclass_id as class_id
                    FROM exam_results er
                    JOIN students s ON er.student_id = s.id
                    WHERE er.exam_id = :exam_id
                      AND er.approval_status = 'approved'";

            $stmt = $this\\App\\Config\\Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute([':exam_id' => $examId]);
            $combinations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate subject rankings
            $subjectResults = [];
            foreach ($combinations as $combo) {
                $subjectResults[] = $this->subjectRanking->calculateSubjectRankings(
                    $examId, 
                    $combo['subject_id'], 
                    $combo['class_id']
                );
            }

            // Calculate class rankings
            $classResults = $this->classRanking->calculateAllClassRankings($examId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'All rankings calculated successfully',
                'subject_rankings' => $subjectResults,
                'class_rankings' => $classResults
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to calculate rankings: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get subject rankings
     */
    public function getSubjectRankings(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $subjectId = $args['subjectId'];
        $classId = $args['classId'];
        $params = $request->getQueryParams();
        $limit = $params['limit'] ?? null;

        try {
            $rankings = $this->subjectRanking->getSubjectRankings($examId, $subjectId, $classId, $limit);

            $response->getBody()->write(json_encode([
                'success' => true,
                'rankings' => $rankings
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch subject rankings: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get class rankings
     */
    public function getClassRankings(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $classId = $args['classId'];
        $params = $request->getQueryParams();
        $limit = $params['limit'] ?? null;

        try {
            $rankings = $this->classRanking->getClassRankings($examId, $classId, $limit);

            $response->getBody()->write(json_encode([
                'success' => true,
                'rankings' => $rankings
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch class rankings: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get student's subject position
     */
    public function getStudentSubjectPosition(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $subjectId = $args['subjectId'];
        $studentId = $args['studentId'];

        try {
            $position = $this->subjectRanking->getStudentSubjectPosition($examId, $subjectId, $studentId);

            if (!$position) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Position not found or not published'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'position' => $position
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch position: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get student's overall class position
     */
    public function getStudentClassPosition(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $studentId = $args['studentId'];

        try {
            $position = $this->classRanking->getStudentClassPosition($examId, $studentId);

            if (!$position) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Position not found or not published'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'position' => $position
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch position: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Publish subject rankings
     */
    public function publishSubjectRankings(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];
        $data = $request->getParsedBody();
        $subjectId = $data['subject_id'] ?? null;

        try {
            $this->subjectRanking->publishSubjectRankings($examId, $subjectId);

            $message = $subjectId 
                ? 'Subject rankings published successfully' 
                : 'All subject rankings published successfully';

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => $message
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to publish rankings: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Publish class rankings
     */
    public function publishClassRankings(Request $request, Response $response, $args)
    {
        $examId = $args['examId'];

        try {
            $this->classRanking->publishClassRankings($examId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Class rankings published successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to publish rankings: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

