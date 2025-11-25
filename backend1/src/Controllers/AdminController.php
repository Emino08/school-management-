<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Utils\JWT;
use App\Utils\Validator;
use App\Traits\LogsActivity;
use App\Utils\Cache;
use App\Config\Database;

class AdminController
{
    use LogsActivity;

    private $adminModel;
    private $studentModel;
    private $teacherModel;
    private $classModel;
    private $subjectModel;

    public function __construct()
    {
        $this->adminModel = new Admin();
        $this->studentModel = new Student();
        $this->teacherModel = new Teacher();
        $this->classModel = new ClassModel();
        $this->subjectModel = new Subject();
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
            
            // First admin to register is automatically a super admin
            $adminId = $this->adminModel->createAdmin($sanitized, 'super_admin');

            // Get the created admin/account
            $adminAccount = $this->sanitizeAdminRecord($this->adminModel->findById($adminId));

            // Send welcome email if notifications are enabled
            try {
                $settings = $this->getNotificationSettings();
                if ($settings && $settings['email_enabled']) {
                    $mailer = new \App\Utils\Mailer();
                    $mailer->sendWelcomeEmail(
                        $adminAccount['email'],
                        $adminAccount['school_name'] ?? $adminAccount['contact_name'] ?? 'Admin',
                        'Admin',
                        null // No temp password for self-registration
                    );
                }
            } catch (\Exception $e) {
                error_log('Failed to send welcome email: ' . $e->getMessage());
                // Don't fail registration if email fails
            }

            // Generate JWT token
            $token = JWT::encode([
                'id' => $adminAccount['id'],
                'role' => 'Admin',
                'email' => $adminAccount['email'],
                'admin_id' => $adminAccount['id'],
                'account_id' => $adminAccount['id'],
                'is_super_admin' => true
            ]);

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
            
            // Log admin registration
            $this->logActivity(
                $request,
                'create',
                "New admin registered: {$data['email']} - School: {$adminAccount['school_name']}",
                'admin',
                $adminAccount['id'],
                ['school_name' => $adminAccount['school_name'], 'email' => $data['email']]
            );
            
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

        // Check if loginAs parameter is provided for role validation
        $loginAs = strtolower($data['loginAs'] ?? 'admin');
        $accountRole = strtolower($account['role'] ?? 'admin');
        
        // Validate role-based login
        if ($loginAs === 'principal' && $accountRole !== 'principal') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'You cannot login as Principal with this account. Please use the Admin login.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        if ($loginAs === 'admin' && $accountRole === 'principal') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only admin accounts can use the Admin login. Please use the Principal login.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $isPrincipal = $accountRole === 'principal';
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
        
        // Check if user is super admin
        $isSuperAdmin = $this->adminModel->isSuperAdmin($accountRecord['id']);

        $token = JWT::encode([
            'id' => $ownerAdmin['id'],
            'role' => $roleLabel,
            'email' => $accountRecord['email'],
            'admin_id' => $ownerAdmin['id'],
            'account_id' => $accountRecord['id'],
            'is_super_admin' => $isSuperAdmin
        ]);

        // Log admin login
        $this->logActivity(
            $request,
            'login',
            "Admin logged in: {$accountRecord['email']} as $roleLabel",
            'admin',
            $accountRecord['id'],
            ['role' => $roleLabel, 'school' => $ownerAdmin['school_name'], 'is_super_admin' => $isSuperAdmin]
        );

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
                'signature' => $accountRecord['signature'] ?? null,
                'is_super_admin' => $isSuperAdmin
            ],
            'permissions' => $this->formatPermissions($roleLabel, $isSuperAdmin)
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
        $effectiveAdminId = $this->adminModel->getEffectiveAdminId($adminId);
        
        $queryParams = $request->getQueryParams();
        $forceRefresh = isset($queryParams['refresh']) && $queryParams['refresh'] === 'true';

        try {
            $cache = new Cache();
            $cacheKey = 'dashboard_stats_' . $adminId;
            $cached = false;

            // Force refresh if requested
            if ($forceRefresh) {
                $cache->forget($cacheKey);
            }

            // Cache dashboard stats for 1 minute (60 seconds) for faster updates
            $stats = $cache->remember($cacheKey, 60, function() use ($user) {
                return $this->calculateDashboardStats($user);
            });
            $cached = $cache->has($cacheKey, 60);

            // Auto-heal stale zero stats when live data exists (common after schema/token fixes)
            if (!$forceRefresh && $cached && $this->isPrimaryStatsEmpty($stats)) {
                try {
                    $liveCounts = [
                        'students' => $this->studentModel->count(['admin_id' => $effectiveAdminId]),
                        'teachers' => $this->teacherModel->count(['admin_id' => $effectiveAdminId]),
                        'classes' => $this->classModel->count(['admin_id' => $effectiveAdminId]),
                        'subjects' => $this->subjectModel->count(['admin_id' => $effectiveAdminId]),
                        'admins' => count($this->adminModel->getAdminsBySchool($effectiveAdminId)),
                        'principals' => $this->adminModel->hasPrincipalSupport()
                            ? $this->adminModel->count(['parent_admin_id' => $effectiveAdminId, 'role' => 'principal'])
                            : 0
                    ];

                    if (max($liveCounts) > 0) {
                        $stats = $this->calculateDashboardStats($user);
                        $cache->set($cacheKey, $stats, 60);
                        $cached = false; // indicate fresh compute
                    }
                } catch (\Exception $e) {
                    // If quick validation fails, return cached stats as-is
                }
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => $stats,
                'cached' => $cached,
                'timestamp' => time()
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
            
            // Get effective admin ID (for principals and sub-admins, use root admin's ID)
            $effectiveAdminId = $this->adminModel->getEffectiveAdminId($adminId);
            
            $db = \App\Config\Database::getInstance()->getConnection();
            
            // Get current academic year first
            $yearStmt = $db->prepare("SELECT id FROM academic_years WHERE admin_id = :admin AND is_current = 1 LIMIT 1");
            $yearStmt->execute([':admin' => $effectiveAdminId]);
            $year = $yearStmt->fetch(\PDO::FETCH_ASSOC);
            $yearId = $year['id'] ?? null;

            // Always compute total counts by admin (matches user stats)
            $totalStudents = $this->studentModel->count(['admin_id' => $effectiveAdminId]);
            $totalTeachers = $this->teacherModel->count(['admin_id' => $effectiveAdminId]);
            $totalClasses = $this->classModel->count(['admin_id' => $effectiveAdminId]);
            $totalSubjects = $this->subjectModel->count(['admin_id' => $effectiveAdminId]);
            
            // Count students enrolled in current academic year
            $enrolledStudents = 0;
            if ($yearId) {
                $stmt = $db->prepare("SELECT COUNT(DISTINCT se.student_id) as count 
                                      FROM student_enrollments se 
                                      INNER JOIN students s ON se.student_id = s.id
                                      WHERE s.admin_id = :admin AND se.academic_year_id = :year");
                $stmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
                $enrolledStudents = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            // Use total students as source of truth, fall back to enrollments when empty
            $studentsCount = $enrolledStudents > 0 ? $enrolledStudents : $totalStudents;
            
            // Count classes for current academic year (only classes with enrollments)
            $classesWithEnrollment = 0;
            if ($yearId) {
                $stmt = $db->prepare("SELECT COUNT(DISTINCT se.class_id) as count 
                                      FROM student_enrollments se 
                                      INNER JOIN classes c ON se.class_id = c.id
                                      WHERE c.admin_id = :admin AND se.academic_year_id = :year");
                $stmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
                $classesWithEnrollment = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            
            // Total classes for the admin (matches user stats)
            $classesCount = $totalClasses;
            
            // Count teachers assigned in current academic year
            $assignedTeachers = 0;
            if ($yearId) {
                $stmt = $db->prepare("SELECT COUNT(DISTINCT ta.teacher_id) as count 
                                      FROM teacher_assignments ta 
                                      INNER JOIN teachers t ON ta.teacher_id = t.id
                                      WHERE t.admin_id = :admin AND ta.academic_year_id = :year");
                $stmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
                $assignedTeachers = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            
            // Use total teachers as primary count, but preserve assignments when totals are empty
            $teachersCount = max($totalTeachers, $assignedTeachers);

            // Admin and principal counts
            $adminUsers = $this->adminModel->getAdminsBySchool($effectiveAdminId) ?: [];
            $adminCount = count($adminUsers);
            $principalCount = $this->adminModel->hasPrincipalSupport()
                ? $this->adminModel->count(['parent_admin_id' => $effectiveAdminId, 'role' => 'principal'])
                : 0;
            
            $stats = [
                'total_students' => $studentsCount,
                'total_teachers' => $teachersCount,
                'total_classes' => $classesCount,
                'total_admins' => $adminCount,
                'total_principals' => $principalCount,
                'current_academic_year_id' => $yearId
            ];

            // Attendance analytics for today
            $today = date('Y-m-d');

            $present = $absent = $late = $excused = $total = $distinctStudents = 0;
            if ($yearId) {
                $q = "SELECT a.status AS status, COUNT(*) as cnt FROM attendance a
                      INNER JOIN students s ON a.student_id = s.id
                      WHERE s.admin_id = :admin AND a.academic_year_id = :year AND a.date = :today
                      GROUP BY a.status";
                $stmt = $db->prepare($q);
                $stmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId, ':today' => $today]);
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
                $ds->execute([':admin' => $effectiveAdminId, ':year' => $yearId, ':today' => $today]);
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
            $subjectsCount = $totalSubjects;
            $subjectsTaught = 0;
            if ($yearId) {
                $subStmt = $db->prepare("SELECT COUNT(DISTINCT ta.subject_id) AS c 
                                         FROM teacher_assignments ta
                                         INNER JOIN subjects s ON ta.subject_id = s.id
                                         WHERE ta.academic_year_id = :year AND s.admin_id = :admin");
                $subStmt->execute([':year' => $yearId, ':admin' => $effectiveAdminId]);
                $subjectsTaught = (int)($subStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
            }
            
            $stats['total_subjects'] = $subjectsCount;

            // Subject assignment coverage (current year if available)
            $subjectStats = [
                'total' => $subjectsCount,
                'assigned_to_teacher' => 0,
                'unassigned' => $subjectsCount,
                'average_per_class' => $classesCount > 0 ? round($subjectsCount / $classesCount, 2) : 0
            ];
            try {
                $assignSql = "SELECT COUNT(DISTINCT ta.subject_id) AS c
                              FROM teacher_assignments ta
                              INNER JOIN teachers t ON ta.teacher_id = t.id
                              INNER JOIN subjects s ON ta.subject_id = s.id
                              WHERE t.admin_id = :teacher_admin AND s.admin_id = :subject_admin";
                $assignParams = [
                    ':teacher_admin' => $effectiveAdminId,
                    ':subject_admin' => $effectiveAdminId
                ];
                if ($yearId) {
                    $assignSql .= " AND ta.academic_year_id = :year";
                    $assignParams[':year'] = $yearId;
                }
                $assignStmt = $db->prepare($assignSql);
                $assignStmt->execute($assignParams);
                $subjectStats['assigned_to_teacher'] = (int)($assignStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                // Prefer current-year teaching coverage when available
                if ($subjectsTaught > 0) {
                    $subjectStats['assigned_to_teacher'] = $subjectsTaught;
                }
                $subjectStats['unassigned'] = max(0, $subjectsCount - $subjectStats['assigned_to_teacher']);
            } catch (\Exception $e) {
                // Leave defaults if assignment table is unavailable
            }

            // Class occupancy and staffing snapshot
            $classStats = [
                'with_students' => 0,
                'without_students' => $classesCount,
                'average_size' => 0,
                'with_class_master' => 0,
                'without_class_master' => $classesCount
            ];
            try {
                $classSql = "SELECT c.id, COUNT(se.student_id) AS student_count
                             FROM classes c
                             LEFT JOIN student_enrollments se ON se.class_id = c.id" . ($yearId ? " AND se.academic_year_id = :year" : "") . "
                             WHERE c.admin_id = :admin
                             GROUP BY c.id";
                $classParams = [':admin' => $effectiveAdminId];
                if ($yearId) {
                    $classParams[':year'] = $yearId;
                }
                $classStmt = $db->prepare($classSql);
                $classStmt->execute($classParams);
                $classRows = $classStmt->fetchAll(\PDO::FETCH_ASSOC);

                $totalStudentsInClasses = 0;
                foreach ($classRows as $row) {
                    $count = (int)($row['student_count'] ?? 0);
                    $totalStudentsInClasses += $count;
                    if ($count > 0) {
                        $classStats['with_students']++;
                    }
                }
                $classStats['without_students'] = max(0, $classesCount - $classStats['with_students']);
                $classStats['average_size'] = $classStats['with_students'] > 0
                    ? round($totalStudentsInClasses / $classStats['with_students'], 2)
                    : 0;
            } catch (\Exception $e) {
                // Leave defaults if enrollment tables are unavailable
            }

            try {
                $masterStmt = $db->prepare("SELECT COUNT(DISTINCT t.class_master_of) AS c
                                            FROM teachers t
                                            WHERE t.admin_id = :admin AND t.is_class_master = 1 AND t.class_master_of IS NOT NULL");
                $masterStmt->execute([':admin' => $effectiveAdminId]);
                $classStats['with_class_master'] = (int)($masterStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                $classStats['without_class_master'] = max(0, $classesCount - $classStats['with_class_master']);
            } catch (\Exception $e) {
                // Leave defaults if teacher table is unavailable
            }

            $stats['subjects'] = $subjectStats;
            $stats['classes'] = $classStats;

            // Enhanced Fees stats (current year)
            if ($yearId) {
                // Get fee structure total and collected amount
                $feesStmt = $db->prepare("SELECT
                            COALESCE(SUM(fp.amount),0) as total_collected,
                            COUNT(DISTINCT fp.student_id) as students_paid
                        FROM fees_payments fp
                        INNER JOIN students s ON fp.student_id = s.id
                        WHERE s.admin_id = :admin AND fp.academic_year_id = :year");
                $feesStmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
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
                $monthStmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId, ':start' => $monthStart, ':end' => $monthEnd]);
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
                // Fallback aggregate across all records when no current academic year is set
                try {
                    $feesStmt = $db->prepare("SELECT
                                COALESCE(SUM(fp.amount),0) as total_collected,
                                COUNT(DISTINCT fp.student_id) as students_paid
                            FROM fees_payments fp
                            INNER JOIN students s ON fp.student_id = s.id
                            WHERE s.admin_id = :admin");
                    $feesStmt->execute([':admin' => $effectiveAdminId]);
                    $fees = $feesStmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_collected'=>0,'students_paid'=>0];

                    $expectedStmt = $db->prepare("SELECT COALESCE(SUM(fs.amount),0) as expected
                                                   FROM fee_structures fs
                                                   WHERE fs.admin_id = :admin");
                    $expectedStmt->execute([':admin' => $effectiveAdminId]);
                    $expected = $expectedStmt->fetch(\PDO::FETCH_ASSOC);
                    $expectedAmount = (float)($expected['expected'] ?? 0);

                    $totalCollected = (float)$fees['total_collected'];
                    $totalPending = max(0, $expectedAmount - $totalCollected);
                    $collectionRate = $expectedAmount > 0 ? round(($totalCollected / $expectedAmount) * 100, 2) : 0;

                    $stats['fees'] = [
                        'total_collected' => $totalCollected,
                        'total_pending' => $totalPending,
                        'collection_rate' => $collectionRate,
                        'this_month' => $totalCollected, // best-effort aggregate when no year context
                        'students_paid' => (int)$fees['students_paid'],
                        'total_expected' => $expectedAmount
                    ];
                } catch (\Exception $e) {
                $stats['fees'] = [
                    'total_collected' => 0,
                    'total_pending' => 0,
                    'collection_rate' => 0,
                    'this_month' => 0,
                    'students_paid' => 0,
                    'total_expected' => 0
                ];
                }
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
                    $resultsStmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
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
                'total_admins' => 0,
                'total_principals' => 0,
                'total_subjects' => 0,
                'subjects' => ['total' => 0, 'assigned_to_teacher' => 0, 'unassigned' => 0, 'average_per_class' => 0],
                'classes' => ['with_students' => 0, 'without_students' => 0, 'average_size' => 0, 'with_class_master' => 0, 'without_class_master' => 0],
                'attendance' => ['date' => date('Y-m-d'), 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total_records' => 0, 'distinct_students_marked' => 0, 'attendance_rate' => 0],
                'fees' => ['total_collected' => 0, 'total_pending' => 0, 'collection_rate' => 0, 'this_month' => 0, 'students_paid' => 0, 'total_expected' => 0],
                'results' => ['published' => 0, 'pending' => 0, 'total' => 0],
                'notices' => ['total' => 0, 'active' => 0, 'recent' => 0],
                'complaints' => ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0]
            ];
        }
    }

    /**
     * Detects if top-level counts look empty so we can auto-refresh stale caches
     */
    private function isPrimaryStatsEmpty($stats): bool
    {
        if (!is_array($stats)) {
            return true;
        }

        $keys = ['total_students', 'total_teachers', 'total_classes', 'total_admins', 'total_principals', 'total_subjects'];
        foreach ($keys as $key) {
            if (!isset($stats[$key])) {
                continue;
            }
            if ((int)$stats[$key] > 0) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Clear dashboard cache for an admin
     * Should be called whenever data that affects dashboard stats is modified
     */
    public static function clearDashboardCache($adminId)
    {
        try {
            $cache = new Cache();
            $cacheKey = 'dashboard_stats_' . $adminId;
            $cache->forget($cacheKey);
        } catch (\Exception $e) {
            // Silently fail - cache clearing is not critical
            error_log('Failed to clear dashboard cache: ' . $e->getMessage());
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
        $adminId = $request->getAttribute('admin_id') ?? ($user->admin_id ?? $user->id);
        
        // Resolve to root admin for principals and sub-admins
        // This ensures they see their parent admin's data
        try {
            $rootAdminId = $this->adminModel->getRootAdminId($adminId);
            return $rootAdminId;
        } catch (\Exception $e) {
            // Fallback to original ID if resolution fails
            error_log('Failed to resolve root admin ID: ' . $e->getMessage());
            return $adminId;
        }
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

    private function formatPermissions(string $role, bool $isSuperAdmin = false): array
    {
        $roleLower = strtolower($role);
        $isPrincipal = $roleLower === 'principal';
        $isAdmin = $roleLower === 'admin';
        
        return [
            'isSuperAdmin' => $isSuperAdmin,
            'isAdmin' => $isAdmin && !$isSuperAdmin,
            'isPrincipal' => $isPrincipal,
            'canManagePrincipals' => $isAdmin || $isSuperAdmin,
            'canCreateAdmins' => $isSuperAdmin, // Only super admin can create other admins
            'canAccessSystemSettings' => !$isPrincipal, // Principals cannot access system settings
            'canViewActivityLogs' => !$isPrincipal,
            'canManageAllUsers' => true,
            'role' => $roleLower
        ];
    }

    /**
     * Get current user permissions
     */
    public function getPermissions(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $adminId = $this->getAccountId($request, $user);

        try {
            $permissions = $this->adminModel->getPermissions($adminId);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'permissions' => $permissions
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to get permissions: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Check if current user is super admin
     */
    public function checkSuperAdminStatus(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $adminId = $this->getAccountId($request, $user);

        try {
            $isSuperAdmin = $this->adminModel->isSuperAdmin($adminId);
            $admin = $this->adminModel->findById($adminId);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'is_super_admin' => $isSuperAdmin,
                'role' => $admin['role'] ?? 'admin',
                'can_create_admins' => $isSuperAdmin
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to check status: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function getNotificationSettings(): ?array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT notification_settings FROM school_settings LIMIT 1");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row && $row['notification_settings']) {
                return json_decode($row['notification_settings'], true);
            }

            // Return default: email enabled
            return ['email_enabled' => true];
        } catch (\Exception $e) {
            return ['email_enabled' => true];
        }
    }

    /**
     * Create an admin user (only super admins can do this)
     */
    public function createAdminUser(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $currentUser = $request->getAttribute('user');
        $currentAccountId = $this->getAccountId($request, $currentUser);

        // Check if current user is super admin
        if (!$this->adminModel->isSuperAdmin($currentAccountId)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only super admins can create admin users'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Validate input
        $errors = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'contact_name' => 'required'
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
            
            // Create admin user
            $newAdminId = $this->adminModel->createAdminUser($sanitized, $currentAccountId);
            $newAdmin = $this->sanitizeAdminRecord($this->adminModel->findById($newAdminId));

            // Send welcome email with temporary password
            try {
                $settings = $this->getNotificationSettings();
                if ($settings && $settings['email_enabled']) {
                    $mailer = new \App\Utils\Mailer();
                    $mailer->sendWelcomeEmail(
                        $newAdmin['email'],
                        $newAdmin['contact_name'] ?? 'Admin',
                        'Admin',
                        $data['password'] // Send temporary password
                    );
                }
            } catch (\Exception $e) {
                error_log('Failed to send welcome email: ' . $e->getMessage());
            }

            // Log activity
            $this->logActivity(
                $request,
                'create',
                'Created admin user: ' . ($newAdmin['contact_name'] ?? $newAdmin['email']),
                'admin',
                $newAdminId,
                ['admin_email' => $newAdmin['email']]
            );

            // Clear dashboard cache
            AdminController::clearDashboardCache($this->adminModel->getRootAdminId($currentAccountId));

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Admin user created successfully',
                'admin' => $newAdmin
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            error_log('Create admin user error: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create admin user: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get all admins for the current school
     */
    public function getAdminUsers(Request $request, Response $response)
    {
        $currentUser = $request->getAttribute('user');
        $currentAdminId = $this->getAccountId($request, $currentUser);

        // Check if current user is super admin
        if (!$this->adminModel->isSuperAdmin($currentAdminId)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Only super admins can view admin users'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $admins = $this->adminModel->getAdminsBySchool($currentAdminId);
            
            // Sanitize passwords
            $admins = array_map([$this, 'sanitizeAdminRecord'], $admins);

            $response->getBody()->write(json_encode([
                'success' => true,
                'admins' => $admins
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('Get admin users error: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to retrieve admin users'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

