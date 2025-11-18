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
            $sanitized = Validator::sanitize($data);
            $adminId = $this->adminModel->createAdmin($sanitized, 'admin');

            // Get the created admin/account
            $adminAccount = $this->sanitizeAdminRecord($this->adminModel->findById($adminId));

            // Generate JWT token
            $token = JWT::encode([
                'id' => $adminAccount['id'],
                'role' => 'Admin',
                'email' => $adminAccount['email'],
                'admin_id' => $adminAccount['id'],
                'account_id' => $adminAccount['id']
            ], $_ENV['JWT_SECRET'], 'HS256');

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Admin registered successfully',
                'role' => 'Admin',
                'schoolName' => $adminAccount['school_name'],
                'token' => $token,
                'admin' => $adminAccount,
                'account' => [
                    'id' => $adminAccount['id'],
                    'email' => $adminAccount['email'],
                    'contact_name' => $adminAccount['contact_name'] ?? null,
                    'role' => 'Admin'
                ],
                'permissions' => $this->formatPermissions('Admin')
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

        $account = $this->adminModel->findByEmail($data['email']);

        if (!$account || !$this->adminModel->verifyPassword($data['password'], $account['password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $isPrincipal = isset($account['role']) && strtolower($account['role']) === 'principal';
        if ($isPrincipal && empty($account['parent_admin_id'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Principal account is not linked to any administrator'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $ownerAdmin = $isPrincipal
            ? $this->adminModel->findById($account['parent_admin_id'])
            : $account;

        if (!$ownerAdmin) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Parent administrator account not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $roleLabel = $isPrincipal ? 'Principal' : 'Admin';
        $ownerAdmin = $this->sanitizeAdminRecord($ownerAdmin);
        $accountRecord = $this->sanitizeAdminRecord($account);

        $token = JWT::encode([
            'id' => $ownerAdmin['id'],
            'role' => $roleLabel,
            'email' => $accountRecord['email'],
            'admin_id' => $ownerAdmin['id'],
            'account_id' => $accountRecord['id']
        ], $_ENV['JWT_SECRET'], 'HS256');

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Login successful',
            'role' => $roleLabel,
            'schoolName' => $ownerAdmin['school_name'],
            'token' => $token,
            'admin' => $ownerAdmin,
            'account' => [
                'id' => $accountRecord['id'],
                'email' => $accountRecord['email'],
                'contact_name' => $accountRecord['contact_name'] ?? null,
                'role' => $roleLabel,
                'phone' => $accountRecord['phone'] ?? null,
                'signature' => $accountRecord['signature'] ?? null
            ],
            'permissions' => $this->formatPermissions($roleLabel)
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getProfile(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $adminId = $this->getScopedAdminId($request, $user);
        $accountId = $this->getAccountId($request, $user);

        $admin = $this->sanitizeAdminRecord($this->adminModel->findById($adminId));
        $account = $this->sanitizeAdminRecord($this->adminModel->findById($accountId));

        if (!$admin) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Admin not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if (!$account) {
            $account = $admin;
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'admin' => $admin,
            'account' => $account,
            'permissions' => $this->formatPermissions($this->getRequestRole($request, $user))
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateProfile(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $adminId = $this->getScopedAdminId($request, $user);
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
            $this->adminModel->update($adminId, $data);

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
        $adminId = $user->admin_id ?? $user->id;
        
        $queryParams = $request->getQueryParams();
        $forceRefresh = isset($queryParams['refresh']) && $queryParams['refresh'] === 'true';

        try {
            $cache = new Cache();
            $cacheKey = 'dashboard_stats_' . $adminId;

            // Force refresh if requested
            if ($forceRefresh) {
                $cache->forget($cacheKey);
            }

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
            // For Admin role, user->id is the admin_id
            $adminId = isset($user->admin_id) ? $user->admin_id : $user->id;
            
            $db = \App\Config\Database::getInstance()->getConnection();
            
            // Get current academic year first
            $yearStmt = $db->prepare("SELECT id FROM academic_years WHERE admin_id = :admin AND is_current = 1 LIMIT 1");
            $yearStmt->execute([':admin' => $adminId]);
            $year = $yearStmt->fetch(\PDO::FETCH_ASSOC);
            $yearId = $year['id'] ?? null;
            
            // Count students enrolled in current academic year
            $studentsCount = 0;
            if ($yearId) {
                $stmt = $db->prepare("SELECT COUNT(DISTINCT se.student_id) as count 
                                      FROM student_enrollments se 
                                      INNER JOIN students s ON se.student_id = s.id
                                      WHERE s.admin_id = :admin AND se.academic_year_id = :year");
                $stmt->execute([':admin' => $adminId, ':year' => $yearId]);
                $studentsCount = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            
            // Count classes for current academic year (only classes with enrollments)
            $classesCount = 0;
            if ($yearId) {
                $stmt = $db->prepare("SELECT COUNT(DISTINCT se.class_id) as count 
                                      FROM student_enrollments se 
                                      INNER JOIN classes c ON se.class_id = c.id
                                      WHERE c.admin_id = :admin AND se.academic_year_id = :year");
                $stmt->execute([':admin' => $adminId, ':year' => $yearId]);
                $classesCount = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            
            // If no classes with enrollments, fallback to all classes
            if ($classesCount === 0 && $yearId) {
                $classesCount = $this->classModel->count(['admin_id' => $adminId]);
            }
            
            // Count teachers assigned in current academic year
            $teachersCount = 0;
            if ($yearId) {
                $stmt = $db->prepare("SELECT COUNT(DISTINCT ta.teacher_id) as count 
                                      FROM teacher_assignments ta 
                                      INNER JOIN teachers t ON ta.teacher_id = t.id
                                      WHERE t.admin_id = :admin AND ta.academic_year_id = :year");
                $stmt->execute([':admin' => $adminId, ':year' => $yearId]);
                $teachersCount = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            
            // If no teachers in assignments or table doesn't exist, count all teachers
            if ($teachersCount === 0) {
                $teachersCount = $this->teacherModel->count(['admin_id' => $adminId]);
            }
            
            $stats = [
                'total_students' => $studentsCount,
                'total_teachers' => $teachersCount,
                'total_classes' => $classesCount,
                'current_academic_year_id' => $yearId
            ];

            // Attendance analytics for today
            $today = date('Y-m-d');

            $present = $absent = $late = $excused = $total = $distinctStudents = 0;
            if ($yearId) {
                $q = "SELECT status, COUNT(*) as cnt FROM attendance a
                      INNER JOIN students s ON a.student_id = s.id
                      WHERE s.admin_id = :admin AND a.academic_year_id = :year AND a.date = :today
                      GROUP BY status";
                $stmt = $db->prepare($q);
                $stmt->execute([':admin' => $adminId, ':year' => $yearId, ':today' => $today]);
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
                $ds->execute([':admin' => $adminId, ':year' => $yearId, ':today' => $today]);
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

            // Add subjects count for current academic year (subjects being taught)
            $subjectsCount = 0;
            if ($yearId) {
                $subStmt = $db->prepare("SELECT COUNT(DISTINCT ta.subject_id) AS c 
                                         FROM teacher_assignments ta 
                                         WHERE ta.academic_year_id = :year");
                $subStmt->execute([':year' => $yearId]);
                $subjectsCount = (int)($subStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
            }
            
            // Fallback to all subjects if no assignments
            if ($subjectsCount === 0) {
                $subStmt = $db->prepare("SELECT COUNT(*) AS c FROM subjects WHERE admin_id = :admin");
                $subStmt->execute([':admin' => $adminId]);
                $subjectsCount = (int)($subStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
            }
            
            $stats['total_subjects'] = $subjectsCount;

            // Enhanced Fees stats (current year)
            if ($yearId) {
                // Get fee structure total and collected amount
                $feesStmt = $db->prepare("SELECT
                            COALESCE(SUM(fp.amount),0) as total_collected,
                            COUNT(DISTINCT fp.student_id) as students_paid
                        FROM fees_payments fp
                        INNER JOIN students s ON fp.student_id = s.id
                        WHERE s.admin_id = :admin AND fp.academic_year_id = :year");
                $feesStmt->execute([':admin' => $adminId, ':year' => $yearId]);
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
                $monthStmt->execute([':admin' => $adminId, ':year' => $yearId, ':start' => $monthStart, ':end' => $monthEnd]);
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
                    $resultsStmt->execute([':admin' => $adminId, ':year' => $yearId]);
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
                $noticesStmt->execute([':admin' => $adminId]);
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
                $complaintsStmt->execute([':admin' => $adminId]);
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
        $targetId = (int)$args['id'];
        $ownAdminId = $this->getScopedAdminId($request, $user);
        $role = $this->getRequestRole($request, $user);

        if (strtolower($role) !== 'admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only super admins can delete administrator accounts'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Prevent self-deletion
        if ($targetId === $ownAdminId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->adminModel->delete($targetId);

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
        $adminId = $user->admin_id ?? $user->id;
        
        $params = $request->getQueryParams();
        $days = isset($params['days']) ? (int)$params['days'] : 30;
        if ($days <= 0 || $days > 180) { $days = 30; }
        $start = $params['start'] ?? null;
        $end = $params['end'] ?? null;
        $term = $params['term'] ?? null; // For fees
        $attDate = $params['date'] ?? null; // For attendance_by_class

        try {
            // Temporarily bypass cache to debug
            $charts = $this->calculateDashboardCharts($user, $days, $start, $end, $term, $attDate);

            $response->getBody()->write(json_encode([
                'success' => true,
                'charts' => $charts,
                'cached' => false
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

            // For Admin role, use admin_id from JWT token
            $adminId = isset($user->admin_id) ? $user->admin_id : $user->id;
            
            if (!$adminId) {
                throw new \Exception("Admin ID not found in user object");
            }

            // Current academic year
            $yearStmt = $db->prepare("SELECT id FROM academic_years WHERE admin_id = ? AND is_current = 1 LIMIT 1");
            $yearStmt->execute([$adminId]);
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
                $startDate = $start ?: date('Y-m-d', strtotime("-{$days} days"));
                $endDate = $end ?: date('Y-m-d');
                
                $trendSql = "SELECT a.date,
                                   SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present,
                                   SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END)  AS absent,
                                   SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END)    AS late,
                                   SUM(CASE WHEN a.status='excused' THEN 1 ELSE 0 END) AS excused
                            FROM attendance a
                            INNER JOIN students s ON a.student_id = s.id
                            WHERE s.admin_id = ? AND a.academic_year_id = ? AND a.date BETWEEN ? AND ?
                            GROUP BY a.date
                            ORDER BY a.date ASC";
                $trendStmt = $db->prepare($trendSql);
                $trendStmt->execute([$adminId, $yearId, $startDate, $endDate]);
                $attendanceTrend = $trendStmt->fetchAll(\PDO::FETCH_ASSOC);

                // Students per class (current year)
                $classSql = "SELECT c.class_name, c.id, COUNT(se.student_id) AS student_count
                             FROM classes c
                             LEFT JOIN student_enrollments se ON se.class_id = c.id AND se.academic_year_id = ?
                             WHERE c.admin_id = ?
                             GROUP BY c.id, c.class_name
                             ORDER BY c.class_name";
                $classStmt = $db->prepare($classSql);
                $classStmt->execute([$yearId, $adminId]);
                $classStudentCounts = $classStmt->fetchAll(\PDO::FETCH_ASSOC);

                // Fees trend last N days
                $feesSql = "SELECT fp.payment_date as date, COALESCE(SUM(fp.amount),0) as amount
                            FROM fees_payments fp
                            INNER JOIN students s ON fp.student_id = s.id
                            WHERE s.admin_id = ? AND fp.academic_year_id = ?" . ($term ? " AND fp.term = ?" : "") . "
                              AND fp.payment_date BETWEEN ? AND ?
                            GROUP BY fp.payment_date
                            ORDER BY fp.payment_date ASC";
                $fs = $db->prepare($feesSql);
                $params = [$adminId, $yearId];
                if ($term) $params[] = $term;
                $params[] = $startDate;
                $params[] = $endDate;
                $fs->execute($params);
                $feesTrend = $fs->fetchAll(\PDO::FETCH_ASSOC);

                // Results publication counts by date (if table exists)
                try {
                    $rp = $db->prepare("SELECT publication_date as date, COUNT(*) as count
                                            FROM result_publications
                                            WHERE publication_date IS NOT NULL
                                              AND publication_date BETWEEN ? AND ?
                                            GROUP BY publication_date ORDER BY publication_date ASC");
                    $rp->execute([$startDate, $endDate]);
                    $resultsPublications = $rp->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Exception $e) {
                    $resultsPublications = [];
                }

                // Teacher load (subjects assigned) top 10
                $tl = $db->prepare("SELECT t.name as teacher_name, COUNT(ta.subject_id) as subjects
                                    FROM teachers t
                                    LEFT JOIN teacher_assignments ta ON ta.teacher_id = t.id AND ta.academic_year_id = ?
                                    WHERE t.admin_id = ?
                                    GROUP BY t.id, t.name
                                    HAVING subjects > 0
                                    ORDER BY subjects DESC, t.name ASC
                                    LIMIT 10");
                $tl->execute([$yearId, $adminId]);
                $teacherLoad = $tl->fetchAll(\PDO::FETCH_ASSOC);

                // Fees by term (current academic year)
                $termSql = "SELECT fp.term, COALESCE(SUM(fp.amount),0) as amount
                            FROM fees_payments fp
                            INNER JOIN students s ON fp.student_id = s.id
                            WHERE s.admin_id = ? AND fp.academic_year_id = ?
                            GROUP BY fp.term";
                $ts = $db->prepare($termSql);
                $ts->execute([$adminId, $yearId]);
                $feesByTerm = $ts->fetchAll(\PDO::FETCH_ASSOC);

                // Attendance by class (rate for today)
                $attDateVal = $attDate ?: date('Y-m-d');
                $attClassSql = "SELECT c.id as class_id, c.class_name,
                                    SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present,
                                    COUNT(a.id) as total
                                 FROM classes c
                                 INNER JOIN student_enrollments se ON se.class_id = c.id AND se.academic_year_id = ?
                                 LEFT JOIN attendance a
                                   ON a.student_id = se.student_id AND a.academic_year_id = ? AND a.date = ?
                                 WHERE c.admin_id = ?
                                 GROUP BY c.id, c.class_name
                                 ORDER BY c.class_name";
                $ac = $db->prepare($attClassSql);
                $ac->execute([$yearId, $yearId, $attDateVal, $adminId]);
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
                                  WHERE e.academic_year_id = ?
                                    AND e.admin_id = ?
                                    AND e.exam_date BETWEEN ? AND ?
                                  GROUP BY e.exam_date
                                  ORDER BY e.exam_date ASC";
                    $gs = $db->prepare($gradesSql);
                    $gs->execute([$yearId, $adminId, $startDate, $endDate]);
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
            // Log error and return empty charts
            error_log("Chart calculation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
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

    private function getScopedAdminId(Request $request, $user)
    {
        return $request->getAttribute('admin_id') ?? ($user->admin_id ?? $user->id);
    }

    private function getAccountId(Request $request, $user)
    {
        return $request->getAttribute('account_id') ?? ($user->account_id ?? $this->getScopedAdminId($request, $user));
    }

    private function getRequestRole(Request $request, $user): string
    {
        return $request->getAttribute('role') ?? ($user->role ?? 'Admin');
    }

    private function sanitizeAdminRecord(?array $record): ?array
    {
        if (!$record) {
            return null;
        }

        if (isset($record['password'])) {
            unset($record['password']);
        }

        return $record;
    }

    private function formatPermissions(string $role): array
    {
        $isAdmin = strtolower($role) === 'admin';
        return [
            'isSuperAdmin' => $isAdmin,
            'canManagePrincipals' => $isAdmin
        ];
    }
}
