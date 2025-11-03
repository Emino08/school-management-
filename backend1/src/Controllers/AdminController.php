<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Utils\JWT;
use App\Utils\Validator;
use App\Utils\Cache;

class AdminController
{
    private $adminModel;
    private $studentModel;
    private $teacherModel;
    private $classModel;

    public function __construct()
    {
        $this->adminModel = new Admin();
        $this->studentModel = new Student();
        $this->teacherModel = new Teacher();
        $this->classModel = new ClassModel();
    }

    public function register(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        // Normalize field names (support both camelCase and snake_case)
        if (isset($data['schoolName'])) {
            $data['school_name'] = $data['schoolName'];
        }

        $errors = Validator::validate($data, [
            'school_name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if email already exists
        $existing = $this->adminModel->findByEmail($data['email']);
        if ($existing) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email already exists'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $adminId = $this->adminModel->createAdmin(Validator::sanitize($data));

            // Get the created admin
            $admin = $this->adminModel->findById($adminId);
            unset($admin['password']);

            // Generate JWT token
            $token = JWT::encode([
                'id' => $admin['id'],
                'role' => 'Admin',
                'email' => $admin['email']
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Admin registered successfully',
                'role' => 'Admin',
                'schoolName' => $admin['school_name'],
                'token' => $token,
                'admin' => $admin
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $errors = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $admin = $this->adminModel->findByEmail($data['email']);

        if (!$admin || !$this->adminModel->verifyPassword($data['password'], $admin['password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = JWT::encode([
            'id' => $admin['id'],
            'role' => 'Admin',
            'email' => $admin['email']
        ]);

        unset($admin['password']);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Login successful',
            'role' => 'Admin',
            'token' => $token,
            'admin' => $admin
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getProfile(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $admin = $this->adminModel->findById($user->id);

        if (!$admin) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Admin not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        unset($admin['password']);

        $response->getBody()->write(json_encode([
            'success' => true,
            'admin' => $admin
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateProfile(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $data = Validator::sanitize($request->getParsedBody());

        // Remove password from update if not provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }

        // Remove email and id from update
        unset($data['email'], $data['id']);

        try {
            $this->adminModel->update($user->id, $data);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getDashboardStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $cache = new Cache();
            $cacheKey = 'dashboard_stats_' . $user->id;

            // Cache dashboard stats for 5 minutes (300 seconds)
            $stats = $cache->remember($cacheKey, 300, function() use ($user) {
                return $this->calculateDashboardStats($user);
            });

            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => $stats,
                'cached' => $cache->has($cacheKey, 300)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch stats: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function calculateDashboardStats($user)
    {
        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $stats = [
                'total_students' => $this->studentModel->count(['admin_id' => $user->id]),
                'total_teachers' => $this->teacherModel->count(['admin_id' => $user->id]),
                'total_classes' => $this->classModel->count(['admin_id' => $user->id])
            ];

            // Attendance analytics for today
            $db = \App\Config\Database::getInstance()->getConnection();
            $today = date('Y-m-d');
            // Get current academic year
            $yearStmt = $db->prepare("SELECT id FROM academic_years WHERE admin_id = :admin AND is_current = 1 LIMIT 1");
            $yearStmt->execute([':admin' => $user->id]);
            $year = $yearStmt->fetch(\PDO::FETCH_ASSOC);
            $yearId = $year['id'] ?? null;

            $present = $absent = $late = $excused = $total = $distinctStudents = 0;
            if ($yearId) {
                $q = "SELECT status, COUNT(*) as cnt FROM attendance a
                      INNER JOIN students s ON a.student_id = s.id
                      WHERE s.admin_id = :admin AND a.academic_year_id = :year AND a.date = :today
                      GROUP BY status";
                $stmt = $db->prepare($q);
                $stmt->execute([':admin' => $user->id, ':year' => $yearId, ':today' => $today]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    $total += (int)$r['cnt'];
                    switch ($r['status']) {
                        case 'present': $present += (int)$r['cnt']; break;
                        case 'absent': $absent += (int)$r['cnt']; break;
                        case 'late': $late += (int)$r['cnt']; break;
                        case 'excused': $excused += (int)$r['cnt']; break;
                    }
                }
                $ds = $db->prepare("SELECT COUNT(DISTINCT a.student_id) as ds FROM attendance a INNER JOIN students s ON a.student_id = s.id WHERE s.admin_id = :admin AND a.academic_year_id = :year AND a.date = :today");
                $ds->execute([':admin' => $user->id, ':year' => $yearId, ':today' => $today]);
                $distinctStudents = (int)($ds->fetch(\PDO::FETCH_ASSOC)['ds'] ?? 0);
            }

            $stats['attendance'] = [
                'date' => $today,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'total_records' => $total,
                'distinct_students_marked' => $distinctStudents,
                'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];

            // Add actual subjects count for admin
            $subStmt = $db->prepare("SELECT COUNT(*) AS c FROM subjects WHERE admin_id = :admin");
            $subStmt->execute([':admin' => $user->id]);
            $stats['total_subjects'] = (int)($subStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);

            // Enhanced Fees stats (current year)
            if ($yearId) {
                // Get fee structure total and collected amount
                $feesStmt = $db->prepare("SELECT
                            COALESCE(SUM(fp.amount),0) as total_collected,
                            COUNT(DISTINCT fp.student_id) as students_paid
                        FROM fees_payments fp
                        INNER JOIN students s ON fp.student_id = s.id
                        WHERE s.admin_id = :admin AND fp.academic_year_id = :year");
                $feesStmt->execute([':admin' => $user->id, ':year' => $yearId]);
                $fees = $feesStmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_collected'=>0,'students_paid'=>0];

                // Get expected amount from fee structures if table exists
                $expectedAmount = 0;
                try {
                    $expectedStmt = $db->prepare("SELECT COALESCE(SUM(fs.amount),0) as expected
                                                   FROM fee_structures fs
                                                   WHERE fs.academic_year_id = :year");
                    $expectedStmt->execute([':year' => $yearId]);
                    $expected = $expectedStmt->fetch(\PDO::FETCH_ASSOC);
                    $expectedAmount = (float)($expected['expected'] ?? 0);
                } catch (\Exception $e) {
                    // fee_structures table might not exist, use student count estimation
                    $expectedAmount = $stats['total_students'] * 50000; // fallback estimation
                }

                $totalCollected = (float)$fees['total_collected'];
                $totalPending = max(0, $expectedAmount - $totalCollected);
                $collectionRate = $expectedAmount > 0 ? round(($totalCollected / $expectedAmount) * 100, 2) : 0;

                // Get current month payments
                $monthStart = date('Y-m-01');
                $monthEnd = date('Y-m-t');
                $monthStmt = $db->prepare("SELECT COALESCE(SUM(amount),0) as month_total
                                          FROM fees_payments fp
                                          INNER JOIN students s ON fp.student_id = s.id
                                          WHERE s.admin_id = :admin
                                            AND fp.academic_year_id = :year
                                            AND fp.payment_date BETWEEN :start AND :end");
                $monthStmt->execute([':admin' => $user->id, ':year' => $yearId, ':start' => $monthStart, ':end' => $monthEnd]);
                $monthData = $monthStmt->fetch(\PDO::FETCH_ASSOC);

                $stats['fees'] = [
                    'total_collected' => $totalCollected,
                    'total_pending' => $totalPending,
                    'collection_rate' => $collectionRate,
                    'this_month' => (float)($monthData['month_total'] ?? 0),
                    'students_paid' => (int)$fees['students_paid'],
                    'total_expected' => $expectedAmount
                ];
            } else {
                $stats['fees'] = [
                    'total_collected' => 0,
                    'total_pending' => 0,
                    'collection_rate' => 0,
                    'this_month' => 0,
                    'students_paid' => 0,
                    'total_expected' => 0
                ];
            }

            // Results stats (current year)
            $stats['results'] = ['published' => 0, 'pending' => 0, 'total' => 0];
            if ($yearId) {
                try {
                    $resultsStmt = $db->prepare("SELECT
                                COUNT(*) as total,
                                SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published,
                                SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as pending
                            FROM exams e
                            WHERE e.admin_id = :admin AND e.academic_year_id = :year");
                    $resultsStmt->execute([':admin' => $user->id, ':year' => $yearId]);
                    $results = $resultsStmt->fetch(\PDO::FETCH_ASSOC);
                    $stats['results'] = [
                        'total' => (int)($results['total'] ?? 0),
                        'published' => (int)($results['published'] ?? 0),
                        'pending' => (int)($results['pending'] ?? 0)
                    ];
                } catch (\Exception $e) {
                    // Table might not exist, keep defaults
                }
            }

            // Notices stats (active = future or today)
            try {
                $noticesStmt = $db->prepare("SELECT
                            COUNT(*) as total,
                            SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as active,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), created_at) <= 7 THEN 1 ELSE 0 END) as recent
                        FROM notices WHERE admin_id = :admin");
                $noticesStmt->execute([':admin' => $user->id]);
                $notices = $noticesStmt->fetch(\PDO::FETCH_ASSOC);
                $stats['notices'] = [
                    'total' => (int)($notices['total'] ?? 0),
                    'active' => (int)($notices['active'] ?? 0),
                    'recent' => (int)($notices['recent'] ?? 0)
                ];
            } catch (\Exception $e) {
                $stats['notices'] = ['total' => 0, 'active' => 0, 'recent' => 0];
            }

            // Complaints stats
            try {
                $complaintsStmt = $db->prepare("SELECT
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                        FROM complaints WHERE admin_id = :admin");
                $complaintsStmt->execute([':admin' => $user->id]);
                $complaints = $complaintsStmt->fetch(\PDO::FETCH_ASSOC);
                $stats['complaints'] = [
                    'total' => (int)($complaints['total'] ?? 0),
                    'pending' => (int)($complaints['pending'] ?? 0),
                    'in_progress' => (int)($complaints['in_progress'] ?? 0),
                    'resolved' => (int)($complaints['resolved'] ?? 0)
                ];
            } catch (\Exception $e) {
                $stats['complaints'] = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0];
            }

            return $stats;
        } catch (\Exception $e) {
            // Return empty stats on error
            return [
                'total_students' => 0,
                'total_teachers' => 0,
                'total_classes' => 0,
                'total_subjects' => 0,
                'attendance' => ['date' => date('Y-m-d'), 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total_records' => 0, 'distinct_students_marked' => 0, 'attendance_rate' => 0],
                'fees' => ['total_collected' => 0, 'total_pending' => 0, 'collection_rate' => 0, 'this_month' => 0, 'students_paid' => 0, 'total_expected' => 0],
                'results' => ['published' => 0, 'pending' => 0, 'total' => 0],
                'notices' => ['total' => 0, 'active' => 0, 'recent' => 0],
                'complaints' => ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0]
            ];
        }
    }

    public function deleteAdmin(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $adminId = $args['id'];

        // Prevent self-deletion
        if ($user->id == $adminId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->adminModel->delete($adminId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Admin deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Dashboard charts: attendance trend and students per class
    public function getDashboardCharts(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $days = isset($params['days']) ? (int)$params['days'] : 30;
        if ($days <= 0 || $days > 180) { $days = 30; }
        $start = $params['start'] ?? null;
        $end = $params['end'] ?? null;
        $term = $params['term'] ?? null; // For fees
        $attDate = $params['date'] ?? null; // For attendance_by_class

        try {
            $cache = new Cache();
            // Create cache key based on user and filter parameters
            $cacheKey = 'dashboard_charts_' . $user->id . '_' . md5(json_encode($params));

            // Cache dashboard charts for 10 minutes (600 seconds)
            $charts = $cache->remember($cacheKey, 600, function() use ($user, $days, $start, $end, $term, $attDate) {
                return $this->calculateDashboardCharts($user, $days, $start, $end, $term, $attDate);
            });

            $response->getBody()->write(json_encode([
                'success' => true,
                'charts' => $charts,
                'cached' => $cache->has($cacheKey, 600)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch charts: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function calculateDashboardCharts($user, $days, $start, $end, $term, $attDate)
    {
        try {
            $db = \App\Config\Database::getInstance()->getConnection();

            // Current academic year
            $yearStmt = $db->prepare("SELECT id FROM academic_years WHERE admin_id = :admin AND is_current = 1 LIMIT 1");
            $yearStmt->execute([':admin' => $user->id]);
            $year = $yearStmt->fetch(\PDO::FETCH_ASSOC);
            $yearId = $year['id'] ?? null;

            $attendanceTrend = [];
            $classStudentCounts = [];
            $feesTrend = [];
            $resultsPublications = [];
            $teacherLoad = [];
            $feesByTerm = [];
            $attendanceByClass = [];
            $avgGradesTrend = [];

            if ($yearId) {
                // Attendance trend over last N days
                $trendSql = "SELECT a.date,
                                   SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present,
                                   SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END)  AS absent,
                                   SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END)    AS late,
                                   SUM(CASE WHEN a.status='excused' THEN 1 ELSE 0 END) AS excused
                            FROM attendance a
                            INNER JOIN students s ON a.student_id = s.id
                            WHERE s.admin_id = :admin
                              AND a.academic_year_id = :year
                              AND a.date BETWEEN :startDate AND :endDate
                            GROUP BY a.date
                            ORDER BY a.date ASC";
                $trendStmt = $db->prepare($trendSql);
                $trendStmt->bindValue(':admin', $user->id, \PDO::PARAM_INT);
                $trendStmt->bindValue(':year', $yearId, \PDO::PARAM_INT);
                $trendStmt->bindValue(':startDate', $start ?: date('Y-m-d', strtotime("-{$days} days")));
                $trendStmt->bindValue(':endDate', $end ?: date('Y-m-d'));
                $trendStmt->execute();
                $attendanceTrend = $trendStmt->fetchAll(\PDO::FETCH_ASSOC);

                // Students per class (current year)
                $classSql = "SELECT c.class_name, c.id,
                                    COUNT(se.student_id) AS student_count
                             FROM classes c
                             LEFT JOIN student_enrollments se
                               ON se.class_id = c.id AND se.academic_year_id = :year
                             WHERE c.admin_id = :admin
                             GROUP BY c.id, c.class_name
                             ORDER BY c.class_name";
                $classStmt = $db->prepare($classSql);
                $classStmt->execute([':admin' => $user->id, ':year' => $yearId]);
                $classStudentCounts = $classStmt->fetchAll(\PDO::FETCH_ASSOC);

                // Fees trend last N days
                $feesSql = "SELECT fp.payment_date as date, COALESCE(SUM(fp.amount),0) as amount
                            FROM fees_payments fp
                            INNER JOIN students s ON fp.student_id = s.id
                            WHERE s.admin_id = :admin AND fp.academic_year_id = :year
                              AND fp.payment_date BETWEEN :startDate AND :endDate" . ($term ? " AND fp.term = :term" : "") . "
                            GROUP BY fp.payment_date
                            ORDER BY fp.payment_date ASC";
                $fs = $db->prepare($feesSql);
                $fs->bindValue(':admin', $user->id, \PDO::PARAM_INT);
                $fs->bindValue(':year', $yearId, \PDO::PARAM_INT);
                $fs->bindValue(':startDate', $start ?: date('Y-m-d', strtotime("-{$days} days")));
                $fs->bindValue(':endDate', $end ?: date('Y-m-d'));
                if ($term) { $fs->bindValue(':term', $term); }
                $fs->execute();
                $feesTrend = $fs->fetchAll(\PDO::FETCH_ASSOC);

                // Results publication counts by date (if table exists)
                try {
                $rp = $db->prepare("SELECT publication_date as date, COUNT(*) as count
                                        FROM result_publications
                                        WHERE publication_date IS NOT NULL
                                          AND publication_date BETWEEN :startDate AND :endDate
                                        GROUP BY publication_date ORDER BY publication_date ASC");
                    $rp->bindValue(':startDate', $start ?: date('Y-m-d', strtotime("-{$days} days")));
                    $rp->bindValue(':endDate', $end ?: date('Y-m-d'));
                    $rp->execute();
                    $resultsPublications = $rp->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Exception $e) {
                    $resultsPublications = [];
                }

                // Teacher load (subjects assigned) top 10
                $tl = $db->prepare("SELECT t.name as teacher_name, COUNT(ta.subject_id) as subjects
                                    FROM teachers t
                                    LEFT JOIN teacher_assignments ta ON ta.teacher_id = t.id AND ta.academic_year_id = :year
                                    WHERE t.admin_id = :admin
                                    GROUP BY t.id, t.name
                                    ORDER BY subjects DESC, t.name ASC
                                    LIMIT 10");
                $tl->execute([':admin' => $user->id, ':year' => $yearId]);
                $teacherLoad = $tl->fetchAll(\PDO::FETCH_ASSOC);

                // Fees by term (current academic year)
                $termSql = "SELECT fp.term, COALESCE(SUM(fp.amount),0) as amount
                            FROM fees_payments fp
                            INNER JOIN students s ON fp.student_id = s.id
                            WHERE s.admin_id = :admin AND fp.academic_year_id = :year
                            GROUP BY fp.term";
                $ts = $db->prepare($termSql);
                $ts->execute([':admin' => $user->id, ':year' => $yearId]);
                $feesByTerm = $ts->fetchAll(\PDO::FETCH_ASSOC);

                // Attendance by class (rate for today)
                $attClassSql = "SELECT c.id as class_id, c.class_name,
                                    SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present,
                                    COUNT(a.id) as total
                                 FROM classes c
                                 INNER JOIN student_enrollments se ON se.class_id = c.id AND se.academic_year_id = :year
                                 LEFT JOIN attendance a
                                   ON a.student_id = se.student_id AND a.academic_year_id = :year AND a.date = :attDate
                                 WHERE c.admin_id = :admin
                                 GROUP BY c.id, c.class_name
                                 ORDER BY c.class_name";
                $ac = $db->prepare($attClassSql);
                $ac->execute([':admin' => $user->id, ':year' => $yearId, ':attDate' => ($attDate ?: date('Y-m-d'))]);
                $attendanceByClass = array_map(function($r){
                    $r['rate'] = ($r['total'] ?? 0) > 0 ? round(($r['present'] / $r['total']) * 100, 2) : 0;
                    return $r;
                }, $ac->fetchAll(\PDO::FETCH_ASSOC));

                // Average grades trend by exam date (last N days)
                try {
                    $gradesSql = "SELECT e.exam_date as date,
                                         AVG(CASE WHEN er.test_score IS NOT NULL AND er.exam_score IS NOT NULL
                                                  THEN (er.test_score + er.exam_score)/2
                                                  ELSE er.marks_obtained END) as avg_score
                                  FROM exam_results er
                                  INNER JOIN exams e ON er.exam_id = e.id
                                  WHERE e.academic_year_id = :year
                                    AND e.admin_id = :admin
                                    AND e.exam_date BETWEEN :startDate AND :endDate
                                  GROUP BY e.exam_date
                                  ORDER BY e.exam_date ASC";
                    $gs = $db->prepare($gradesSql);
                    $gs->bindValue(':admin', $user->id, \PDO::PARAM_INT);
                    $gs->bindValue(':year', $yearId, \PDO::PARAM_INT);
                    $gs->bindValue(':startDate', $start ?: date('Y-m-d', strtotime("-{$days} days")));
                    $gs->bindValue(':endDate', $end ?: date('Y-m-d'));
                    $gs->execute();
                    $avgGradesTrend = $gs->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Exception $e) {
                    $avgGradesTrend = [];
                }
            }

            return [
                'attendance_trend' => $attendanceTrend,
                'class_student_counts' => $classStudentCounts,
                'fees_trend' => $feesTrend,
                'results_publications' => $resultsPublications,
                'teacher_load' => $teacherLoad,
                'fees_by_term' => $feesByTerm,
                'attendance_by_class' => $attendanceByClass,
                'avg_grades_trend' => $avgGradesTrend
            ];
        } catch (\Exception $e) {
            // Return empty charts on error
            return [
                'attendance_trend' => [],
                'class_student_counts' => [],
                'fees_trend' => [],
                'results_publications' => [],
                'teacher_load' => [],
                'fees_by_term' => [],
                'attendance_by_class' => [],
                'avg_grades_trend' => []
            ];
        }
    }
}
