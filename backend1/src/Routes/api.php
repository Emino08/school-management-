<?php

use Slim\Routing\RouteCollectorProxy;
use App\Middleware\AuthMiddleware;
use App\Controllers\AdminController;
use App\Controllers\StudentController;
use App\Controllers\TeacherController;
use App\Controllers\ClassController;
use App\Controllers\SubjectController;
use App\Controllers\AcademicYearController;
use App\Controllers\GradeController;
use App\Controllers\AttendanceController;
use App\Controllers\FeesPaymentController;
use App\Controllers\NoticeController;
use App\Controllers\ComplaintController;
use App\Controllers\ResultController;
use App\Controllers\ExamOfficerController;
use App\Controllers\GradingSystemController;
use App\Controllers\RankingController;
use App\Controllers\ResultManagementController;
use App\Controllers\TermController;
use App\Controllers\PromotionController;
use App\Controllers\TimetableController;
use App\Controllers\PaymentController;
use App\Controllers\NotificationController;
use App\Controllers\ReportsController;
use App\Controllers\SettingsController;
use App\Controllers\UserManagementController;
use App\Controllers\ParentController;
use App\Controllers\MedicalController;
use App\Controllers\HouseController;
use App\Controllers\SuspensionController;
use App\Controllers\PasswordResetController;

// Root welcome route
$app->get('/', function ($request, $response) {
    $data = [
        'success' => true,
        'message' => 'Welcome to the School Management System API',
        'version' => '1.0.0',
        'status' => 'running',
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoints' => [
            'api_base' => '/api',
            'health' => '/api/health',
            'admin_register' => '/api/admin/register',
            'admin_login' => '/api/admin/login',
            'student_login' => '/api/students/login',
            'teacher_login' => '/api/teachers/login'
        ]
    ];
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// Alias routes to handle double /api/api/ paths (temporary fix for frontend misconfiguration)
$app->group('/api/api', function (RouteCollectorProxy $group) {
    // Redirect notification requests to the correct endpoint
    $group->get('/notifications', function ($request, $response) {
        $user = $request->getAttribute('user');
        $controller = new NotificationController();
        
        $userId = $user->id ?? $user->account_id ?? null;
        $userRole = ucfirst($user->role ?? 'Student');
        
        $result = $controller->getForUser($userId, $userRole);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());
    
    $group->post('/notifications', function ($request, $response) {
        $user = $request->getAttribute('user');
        $controller = new NotificationController();
        
        $data = $request->getParsedBody();
        $data['sender_id'] = $user->id ?? $user->account_id;
        $data['sender_role'] = ucfirst($user->role ?? 'Admin');
        
        $result = $controller->create($data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());
    
    $group->get('/notifications/unread-count', function ($request, $response) {
        $user = $request->getAttribute('user');
        $controller = new NotificationController();
        
        $userId = $user->id ?? $user->account_id ?? null;
        $userRole = ucfirst($user->role ?? 'Student');
        
        $result = $controller->getUnreadCount($userId, $userRole);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());
    
    $group->post('/notifications/{id}/mark-read', function ($request, $response, $args) {
        $user = $request->getAttribute('user');
        $controller = new NotificationController();
        
        $userId = $user->id ?? $user->account_id ?? null;
        $userRole = ucfirst($user->role ?? 'Student');
        
        $result = $controller->markAsRead($args['id'], $userId, $userRole);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());
});

$app->group('/api', function (RouteCollectorProxy $group) {

    // Health check endpoint
    $group->get('/health', function ($request, $response) {
        $data = [
            'success' => true,
            'status' => 'healthy',
            'message' => 'School Management System API is running',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => 'connected',
            'version' => '1.0.0'
        ];
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Migration status endpoint (admin auth)
    $group->get('/migration-status', function ($request, $response) {
        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'id_number'");
            $hasIdNumber = $stmt->rowCount() > 0;
            $idxStmt = $db->query("SHOW INDEX FROM students WHERE Key_name = 'unique_id_number_per_school'");
            $hasUniqueIdx = $idxStmt->rowCount() > 0;
            $payload = [
                'success' => true,
                'students_id_number' => $hasIdNumber,
                'students_unique_idx' => $hasUniqueIdx,
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new \App\Middleware\AuthMiddleware());

    // Admin routes
    $group->post('/admin/register', [AdminController::class, 'register']);
    $group->post('/admin/login', [AdminController::class, 'login']);
    $group->get('/admin/profile', [AdminController::class, 'getProfile'])->add(new AuthMiddleware());
    $group->put('/admin/profile', [AdminController::class, 'updateProfile'])->add(new AuthMiddleware());
    $group->get('/admin/stats', [AdminController::class, 'getDashboardStats'])->add(new AuthMiddleware());
    $group->get('/admin/charts', [AdminController::class, 'getDashboardCharts'])->add(new AuthMiddleware());
    $group->delete('/admin/{id}', [AdminController::class, 'deleteAdmin'])->add(new AuthMiddleware());
    
    // Admin-prefixed academic year routes (alias)
    $group->get('/admin/academic-years', [AcademicYearController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/admin/academic-years/current', [AcademicYearController::class, 'getCurrent'])->add(new AuthMiddleware());

    // Password Reset routes (no auth required)
    $group->post('/password/forgot', [\App\Controllers\PasswordResetController::class, 'requestReset']);
    $group->get('/password/verify-token', [\App\Controllers\PasswordResetController::class, 'verifyToken']);
    $group->post('/password/reset', [\App\Controllers\PasswordResetController::class, 'resetPassword']);
    $group->post('/password/cleanup', [\App\Controllers\PasswordResetController::class, 'cleanupExpiredTokens'])->add(new AuthMiddleware());

    // Test email configuration (admin only)
    $group->post('/email/test', function ($request, $response) {
        try {
            $mailer = new \App\Utils\Mailer();
            $result = $mailer->testConnection();
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new AuthMiddleware());

    // Notification routes (for all authenticated users)
    $group->get('/notifications', function ($request, $response) {
        $user = $request->getAttribute('user');
        $controller = new NotificationController();
        
        $userId = $user->id ?? $user->account_id ?? null;
        $userRole = ucfirst($user->role ?? 'Student');
        
        $result = $controller->getForUser($userId, $userRole);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());
    
    $group->post('/notifications', function ($request, $response) {
        $user = $request->getAttribute('user');
        $controller = new NotificationController();
        
        $data = $request->getParsedBody();
        $data['sender_id'] = $user->id ?? $user->account_id;
        $data['sender_role'] = ucfirst($user->role ?? 'Admin');
        
        $result = $controller->create($data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());
    
    $group->get('/notifications/unread-count', function ($request, $response) {
        $user = $request->getAttribute('user');
        $controller = new NotificationController();
        
        $userId = $user->id ?? $user->account_id ?? null;
        $userRole = ucfirst($user->role ?? 'Student');
        
        $result = $controller->getUnreadCount($userId, $userRole);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());
    
    $group->post('/notifications/{id}/mark-read', function ($request, $response, $args) {
        $user = $request->getAttribute('user');
        $controller = new NotificationController();
        
        $userId = $user->id ?? $user->account_id ?? null;
        $userRole = ucfirst($user->role ?? 'Student');
        
        $result = $controller->markAsRead($args['id'], $userId, $userRole);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    // Student routes
    // IMPORTANT: Specific routes must come before parameterized routes
    $group->post('/students/register', [StudentController::class, 'register'])->add(new AuthMiddleware());
    $group->post('/students/login', [StudentController::class, 'login']);
    $group->post('/students/bulk-upload', [StudentController::class, 'bulkUpload'])->add(new AuthMiddleware());
    $group->get('/students/bulk-template', [StudentController::class, 'bulkTemplate'])->add(new AuthMiddleware());
    $group->get('/students/dashboard-stats', [StudentController::class, 'getDashboardStats'])->add(new AuthMiddleware());
    $group->get('/students', [StudentController::class, 'getAllStudents'])->add(new AuthMiddleware());
    $group->get('/students/class/{id}', [StudentController::class, 'getStudentsByClass'])->add(new AuthMiddleware());
    $group->get('/students/{id}/grades', [StudentController::class, 'getStudentGrades'])->add(new AuthMiddleware());
    $group->get('/students/{id}/attendance', [StudentController::class, 'getStudentAttendance'])->add(new AuthMiddleware());
    $group->get('/students/{id}', [StudentController::class, 'getStudent'])->add(new AuthMiddleware());
    $group->put('/students/{id}', [StudentController::class, 'updateStudent'])->add(new AuthMiddleware());
    $group->delete('/students/{id}', [StudentController::class, 'deleteStudent'])->add(new AuthMiddleware());

    // Teacher routes
    // IMPORTANT: Specific routes must come before parameterized routes
    $group->post('/teachers/register', [TeacherController::class, 'register'])->add(new AuthMiddleware());
    $group->post('/teachers/login', [TeacherController::class, 'login']);
    $group->post('/teachers/bulk-upload', [TeacherController::class, 'bulkUpload'])->add(new AuthMiddleware());
    $group->get('/teachers/bulk-template', [TeacherController::class, 'bulkTemplate'])->add(new AuthMiddleware());
    $group->post('/teachers/assign-subject', [TeacherController::class, 'assignSubject'])->add(new AuthMiddleware());
    $group->get('/teachers', [TeacherController::class, 'getAllTeachers'])->add(new AuthMiddleware());
    $group->get('/teachers/deleted', [TeacherController::class, 'getDeletedTeachers'])->add(new AuthMiddleware());
    // Teacher grade change request routes (specific routes before parameterized)
    $group->post('/teachers/grade-change-request', [TeacherController::class, 'requestGradeChange'])->add(new AuthMiddleware());
    $group->get('/teachers/grade-change-requests', [TeacherController::class, 'getMyGradeChangeRequests'])->add(new AuthMiddleware());
    $group->get('/teachers/grade-change-stats', [TeacherController::class, 'getGradeChangeStats'])->add(new AuthMiddleware());
    $group->get('/teachers/dashboard-stats', [TeacherController::class, 'getDashboardStats'])->add(new AuthMiddleware());
    $group->get('/teachers/{id}/classes', [TeacherController::class, 'getTeacherClasses'])->add(new AuthMiddleware());
    $group->get('/teachers/{id}/submissions', [TeacherController::class, 'getSubmissions'])->add(new AuthMiddleware());
    $group->delete('/teachers/{teacherId}/subjects/{subjectId}', [TeacherController::class, 'removeSubjectAssignment'])->add(new AuthMiddleware());
    $group->get('/teachers/{id}/subjects', [TeacherController::class, 'getTeacherSubjects'])->add(new AuthMiddleware());
    $group->post('/teachers/{id}/restore', [TeacherController::class, 'restoreTeacher'])->add(new AuthMiddleware());
    $group->get('/teachers/{id}', [TeacherController::class, 'getTeacher'])->add(new AuthMiddleware());
    $group->put('/teachers/{id}', [TeacherController::class, 'updateTeacher'])->add(new AuthMiddleware());
    $group->delete('/teachers/{id}', [TeacherController::class, 'deleteTeacher'])->add(new AuthMiddleware());

    // Teacher attendance and grading routes
    $group->get('/teachers/{teacherId}/subjects/{subjectId}/attendance', [TeacherController::class, 'getSubjectAttendance'])->add(new AuthMiddleware());
    $group->post('/teachers/{teacherId}/subjects/{subjectId}/attendance', [TeacherController::class, 'markAttendance'])->add(new AuthMiddleware());
    $group->get('/teachers/{teacherId}/subjects/{subjectId}/students', [TeacherController::class, 'getSubjectStudents'])->add(new AuthMiddleware());
    $group->post('/teachers/{teacherId}/subjects/{subjectId}/grades', [TeacherController::class, 'submitGrade'])->add(new AuthMiddleware());

    // Class routes
    $group->post('/classes', [ClassController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/classes', [ClassController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/classes/{id}', [ClassController::class, 'getClass'])->add(new AuthMiddleware());
    $group->get('/classes/{id}/subjects/free', [ClassController::class, 'getFreeSubjects'])->add(new AuthMiddleware());
    $group->put('/classes/{id}', [ClassController::class, 'update'])->add(new AuthMiddleware());
    $group->delete('/classes/{id}', [ClassController::class, 'delete'])->add(new AuthMiddleware());
    $group->get('/classes/{id}/subjects', [ClassController::class, 'getClassSubjects'])->add(new AuthMiddleware());

    // Subject routes
    $group->post('/subjects', [SubjectController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/subjects', [SubjectController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/subjects/{id}', [SubjectController::class, 'getSubject'])->add(new AuthMiddleware());
    $group->put('/subjects/{id}', [SubjectController::class, 'update'])->add(new AuthMiddleware());
    $group->delete('/subjects/{id}', [SubjectController::class, 'delete'])->add(new AuthMiddleware());

    // Academic Year routes
    $group->post('/academic-years', [AcademicYearController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/academic-years', [AcademicYearController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/academic-years/current', [AcademicYearController::class, 'getCurrent'])->add(new AuthMiddleware());
    $group->put('/academic-years/{id}/set-current', [AcademicYearController::class, 'setCurrent'])->add(new AuthMiddleware());
    $group->post('/academic-years/{id}/promote', [AcademicYearController::class, 'promoteStudents'])->add(new AuthMiddleware());

    // Promotion Management routes
    $group->post('/promotions/process/{id}', [PromotionController::class, 'processPromotions'])->add(new AuthMiddleware());
    $group->get('/promotions/stats', [PromotionController::class, 'getPromotionStats'])->add(new AuthMiddleware());
    $group->post('/promotions/rollover', [PromotionController::class, 'rollover'])->add(new AuthMiddleware());
    $group->get('/promotions/preview', [PromotionController::class, 'preview'])->add(new AuthMiddleware());
    $group->get('/promotions/waitlist', [PromotionController::class, 'waitlist'])->add(new AuthMiddleware());
    $group->get('/promotions/repeats', [PromotionController::class, 'repeats'])->add(new AuthMiddleware());
    $group->post('/promotions/assign', [PromotionController::class, 'manualAssign'])->add(new AuthMiddleware());

    // Term Management routes
    $group->get('/terms/current', [TermController::class, 'getCurrentTerm'])->add(new AuthMiddleware());
    $group->post('/terms/create', [TermController::class, 'createTermsForYear'])->add(new AuthMiddleware());
    $group->get('/terms/check-toggle/{yearId}', [TermController::class, 'checkAndToggleTerm'])->add(new AuthMiddleware());
    $group->post('/terms/toggle/{yearId}', [TermController::class, 'manualToggleTerm'])->add(new AuthMiddleware());
    $group->post('/exams/{examId}/publish', [TermController::class, 'publishExam'])->add(new AuthMiddleware());
    $group->put('/academic-years/{id}/complete', [AcademicYearController::class, 'completeYear'])->add(new AuthMiddleware());
    $group->delete('/academic-years/{id}', [AcademicYearController::class, 'delete'])->add(new AuthMiddleware());

    // Grade & Exam routes
    $group->post('/exams', [GradeController::class, 'createExam'])->add(new AuthMiddleware());
    $group->get('/exams', [GradeController::class, 'getAllExams'])->add(new AuthMiddleware());
    $group->post('/exams/results', [GradeController::class, 'recordExamResult'])->add(new AuthMiddleware());
    $group->get('/exams/{examId}/students/{studentId}/results', [GradeController::class, 'getExamResults'])->add(new AuthMiddleware());
    $group->post('/grades', [GradeController::class, 'updateGrade'])->add(new AuthMiddleware());
    $group->get('/grades/students/{studentId}', [GradeController::class, 'getStudentGrades'])->add(new AuthMiddleware());

    // Attendance routes
    $group->post('/attendance', [AttendanceController::class, 'markAttendance'])->add(new AuthMiddleware());
    $group->get('/attendance/students/{studentId}', [AttendanceController::class, 'getStudentAttendance'])->add(new AuthMiddleware());
    $group->get('/attendance/students/{studentId}/stats', [AttendanceController::class, 'getAttendanceStats'])->add(new AuthMiddleware());
    $group->get('/attendance/class/{classId}', [AttendanceController::class, 'getClassAttendance'])->add(new AuthMiddleware());

    // Fees Payment routes
    $group->post('/fees', [FeesPaymentController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/fees/all', [FeesPaymentController::class, 'getAllPayments'])->add(new AuthMiddleware());
    $group->get('/fees/students/{studentId}', [FeesPaymentController::class, 'getStudentPayments'])->add(new AuthMiddleware());
    $group->get('/fees/term/{term}', [FeesPaymentController::class, 'getPaymentsByTerm'])->add(new AuthMiddleware());
    $group->get('/fees/stats', [FeesPaymentController::class, 'getPaymentStats'])->add(new AuthMiddleware());
    $group->put('/fees/{id}', [FeesPaymentController::class, 'updatePayment'])->add(new AuthMiddleware());
    $group->delete('/fees/{id}', [FeesPaymentController::class, 'deletePayment'])->add(new AuthMiddleware());

    // Notice routes
    $group->post('/notices', [NoticeController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/notices', [NoticeController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/notices/stats', [NoticeController::class, 'getStats'])->add(new AuthMiddleware());
    $group->get('/notices/audience/{audience}', [NoticeController::class, 'getByAudience'])->add(new AuthMiddleware());
    $group->put('/notices/{id}', [NoticeController::class, 'update'])->add(new AuthMiddleware());
    $group->delete('/notices/{id}', [NoticeController::class, 'delete'])->add(new AuthMiddleware());

    // Complaint routes
    $group->post('/complaints', [ComplaintController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/complaints', [ComplaintController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/complaints/stats', [ComplaintController::class, 'getStats'])->add(new AuthMiddleware());
    $group->get('/complaints/my', [ComplaintController::class, 'getUserComplaints'])->add(new AuthMiddleware());
    $group->get('/complaints/{id}', [ComplaintController::class, 'getById'])->add(new AuthMiddleware());
    $group->put('/complaints/{id}/status', [ComplaintController::class, 'updateStatus'])->add(new AuthMiddleware());
    $group->delete('/complaints/{id}', [ComplaintController::class, 'delete'])->add(new AuthMiddleware());

    // Result Management routes
    $group->get('/results/student/{studentId}/exam/{examId}', [ResultController::class, 'getStudentResults'])->add(new AuthMiddleware());
    $group->get('/results/student/{studentId}', [ResultController::class, 'getAllStudentResults'])->add(new AuthMiddleware());
    $group->get('/results/exam/{examId}', [ResultController::class, 'getExamResults'])->add(new AuthMiddleware());
    $group->post('/results/check-with-pin', [ResultController::class, 'checkResultWithPin']); // Public - no auth required
    $group->post('/results/generate-pin', [ResultController::class, 'generatePin'])->add(new AuthMiddleware());
    $group->post('/results/bulk-generate-pins', [ResultController::class, 'bulkGeneratePins'])->add(new AuthMiddleware());
    $group->post('/results/bulk-generate-pins-all', [ResultController::class, 'bulkGeneratePinsAllStudents'])->add(new AuthMiddleware());
    $group->get('/results/pins/export-csv', [ResultController::class, 'exportPinsCSV'])->add(new AuthMiddleware());
    $group->post('/results/publish/{examId}', [ResultController::class, 'publishResults'])->add(new AuthMiddleware());
    $group->get('/results/ranking/{classId}/{examId}', [ResultController::class, 'getClassRanking'])->add(new AuthMiddleware());
    $group->get('/results/pins', [ResultController::class, 'getAdminPins'])->add(new AuthMiddleware());

    // Ranking routes - Calculate and manage subject/class rankings
    $group->post('/rankings/exam/{examId}/calculate', [RankingController::class, 'calculateAllRankings'])->add(new AuthMiddleware());
    $group->post('/rankings/exam/{examId}/subject/{subjectId}/class/{classId}/calculate', [RankingController::class, 'calculateSubjectRankings'])->add(new AuthMiddleware());
    $group->post('/rankings/exam/{examId}/class/{classId}/calculate', [RankingController::class, 'calculateClassRankings'])->add(new AuthMiddleware());
    $group->get('/rankings/exam/{examId}/subject/{subjectId}/class/{classId}', [RankingController::class, 'getSubjectRankings'])->add(new AuthMiddleware());
    $group->get('/rankings/exam/{examId}/class/{classId}', [RankingController::class, 'getClassRankings'])->add(new AuthMiddleware());
    $group->get('/rankings/exam/{examId}/subject/{subjectId}/student/{studentId}', [RankingController::class, 'getStudentSubjectPosition'])->add(new AuthMiddleware());
    $group->get('/rankings/exam/{examId}/student/{studentId}', [RankingController::class, 'getStudentClassPosition'])->add(new AuthMiddleware());
    $group->post('/rankings/exam/{examId}/subject/publish', [RankingController::class, 'publishSubjectRankings'])->add(new AuthMiddleware());
    $group->post('/rankings/exam/{examId}/class/publish', [RankingController::class, 'publishClassRankings'])->add(new AuthMiddleware());

    // Exam Officer routes
    $group->post('/exam-officer/login', [ExamOfficerController::class, 'login']);
    $group->post('/exam-officers', [ExamOfficerController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/exam-officers', [ExamOfficerController::class, 'getAll'])->add(new AuthMiddleware());
    $group->put('/exam-officers/{id}', [ExamOfficerController::class, 'update'])->add(new AuthMiddleware());
    $group->delete('/exam-officers/{id}', [ExamOfficerController::class, 'delete'])->add(new AuthMiddleware());
    $group->get('/exam-officer/pending-results', [ExamOfficerController::class, 'getPendingResults'])->add(new AuthMiddleware());
    $group->post('/exam-officer/approve/{resultId}', [ExamOfficerController::class, 'approveResult'])->add(new AuthMiddleware());
    $group->post('/exam-officer/reject/{resultId}', [ExamOfficerController::class, 'rejectResult'])->add(new AuthMiddleware());
    $group->post('/exam-officer/bulk-approve', [ExamOfficerController::class, 'bulkApprove'])->add(new AuthMiddleware());
    $group->get('/exam-officer/stats', [ExamOfficerController::class, 'getApprovalStats'])->add(new AuthMiddleware());

    // Teacher-based Exam Officer routes (for teachers who are exam officers)
    $group->get('/exam-officer/grade-requests/pending', [ExamOfficerController::class, 'getPendingGradeRequests'])->add(new AuthMiddleware());
    $group->post('/exam-officer/grade-requests/{id}/approve', [ExamOfficerController::class, 'approveGradeChangeRequest'])->add(new AuthMiddleware());
    $group->post('/exam-officer/grade-requests/{id}/reject', [ExamOfficerController::class, 'rejectGradeChangeRequest'])->add(new AuthMiddleware());
    $group->post('/exam-officer/verify-results', [ExamOfficerController::class, 'verifyExamResults'])->add(new AuthMiddleware());
    $group->post('/exam-officer/verify-exam/{examId}', [ExamOfficerController::class, 'bulkVerifyExamResults'])->add(new AuthMiddleware());
    $group->get('/exam-officer/teacher-stats', [ExamOfficerController::class, 'getTeacherExamOfficerStats'])->add(new AuthMiddleware());

    // User Management routes
    $group->get('/user-management/users', [UserManagementController::class, 'getAllUsers'])->add(new AuthMiddleware());
    $group->get('/user-management/stats', [UserManagementController::class, 'getUserStats'])->add(new AuthMiddleware());
    $group->post('/user-management/users', [UserManagementController::class, 'createUser'])->add(new AuthMiddleware());
    $group->put('/user-management/users/{id}', [UserManagementController::class, 'updateUser'])->add(new AuthMiddleware());
    $group->delete('/user-management/users/{id}', [UserManagementController::class, 'deleteUser'])->add(new AuthMiddleware());
    $group->post('/user-management/teachers/{id}/toggle-exam-officer', [UserManagementController::class, 'toggleExamOfficer'])->add(new AuthMiddleware());
    $group->post('/user-management/bulk-operation', [UserManagementController::class, 'bulkOperation'])->add(new AuthMiddleware());

    // Finance User routes
    $group->post('/finance-users/login', [\App\Controllers\FinanceUserController::class, 'login']);
    $group->get('/finance-users/profile', [\App\Controllers\FinanceUserController::class, 'getProfile'])->add(new AuthMiddleware());
    $group->put('/finance-users/profile', [\App\Controllers\FinanceUserController::class, 'updateProfile'])->add(new AuthMiddleware());

    // Result Management routes (for exam officers with approval privileges)
    $group->get('/result-management/pending-grades', [ResultManagementController::class, 'getPendingGrades'])->add(new AuthMiddleware());
    $group->post('/result-management/approve/{id}', [ResultManagementController::class, 'approveGrade'])->add(new AuthMiddleware());
    $group->post('/result-management/reject/{id}', [ResultManagementController::class, 'rejectGrade'])->add(new AuthMiddleware());
    $group->get('/result-management/update-requests', [ResultManagementController::class, 'getUpdateRequests'])->add(new AuthMiddleware());
    $group->post('/result-management/approve-update/{id}', [ResultManagementController::class, 'approveUpdateRequest'])->add(new AuthMiddleware());
    $group->post('/result-management/reject-update/{id}', [ResultManagementController::class, 'rejectUpdateRequest'])->add(new AuthMiddleware());
    $group->post('/result-management/request-update', [ResultManagementController::class, 'requestGradeUpdate'])->add(new AuthMiddleware());

    // Grading System routes
    $group->get('/grading-system', [GradingSystemController::class, 'getGradingScheme'])->add(new AuthMiddleware());
    $group->post('/grading-system', [GradingSystemController::class, 'createGradeRange'])->add(new AuthMiddleware());
    $group->put('/grading-system/{id}', [GradingSystemController::class, 'updateGradeRange'])->add(new AuthMiddleware());
    $group->delete('/grading-system/{id}', [GradingSystemController::class, 'deleteGradeRange'])->add(new AuthMiddleware());
    $group->post('/grading-system/calculate', [GradingSystemController::class, 'calculateGrade'])->add(new AuthMiddleware());
    $group->get('/grading-system/statistics', [GradingSystemController::class, 'getGradeStatistics'])->add(new AuthMiddleware());
    $group->post('/grading-system/preset', [GradingSystemController::class, 'createPreset'])->add(new AuthMiddleware());

    // Activity Log routes
    $group->get('/activity-logs', [\App\Controllers\ActivityLogController::class, 'getLogs'])->add(new AuthMiddleware());
    $group->get('/activity-logs/stats', [\App\Controllers\ActivityLogController::class, 'getStats'])->add(new AuthMiddleware());
    $group->get('/activity-logs/my', [\App\Controllers\ActivityLogController::class, 'getMyLogs'])->add(new AuthMiddleware());

    // Principal Remarks routes
    $group->post('/principal-remarks', [\App\Controllers\PrincipalRemarksController::class, 'saveRemarks'])->add(new AuthMiddleware());
    $group->get('/principal-remarks/{academicYearId}/{classId}/{term}', [\App\Controllers\PrincipalRemarksController::class, 'getRemarks'])->add(new AuthMiddleware());
    $group->get('/principal-remarks/{academicYearId}', [\App\Controllers\PrincipalRemarksController::class, 'getAllRemarks'])->add(new AuthMiddleware());

    // Timetable routes
    $group->post('/timetable', [TimetableController::class, 'createEntry'])->add(new AuthMiddleware());
    $group->post('/timetable/bulk', [TimetableController::class, 'bulkCreate'])->add(new AuthMiddleware());
    $group->get('/timetable/class/{classId}', [TimetableController::class, 'getClassTimetable'])->add(new AuthMiddleware());
    $group->get('/timetable/teacher/{teacherId}', [TimetableController::class, 'getTeacherTimetable'])->add(new AuthMiddleware());
    $group->get('/timetable/student/{studentId}', [TimetableController::class, 'getStudentTimetable'])->add(new AuthMiddleware());
    $group->get('/timetable/upcoming', [TimetableController::class, 'getUpcomingClasses'])->add(new AuthMiddleware());
    $group->get('/timetable/in-session', [TimetableController::class, 'isInSession'])->add(new AuthMiddleware());
    $group->put('/timetable/{id}', [TimetableController::class, 'updateEntry'])->add(new AuthMiddleware());
    $group->delete('/timetable/{id}', [TimetableController::class, 'deleteEntry'])->add(new AuthMiddleware());

    // Legacy route aliases for frontend compatibility (capitalized paths)
    $group->get('/ComplainList/{id}', [ComplaintController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/NoticeList/{id}', [NoticeController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/SclassList/{id}', [ClassController::class, 'getAll'])->add(new AuthMiddleware());
    // Note: /Students/{id} and /Teachers routes removed to avoid conflicts with /students/{id} and /teachers routes
    $group->get('/Sclass/Students/{id}', [StudentController::class, 'getStudentsByClass'])->add(new AuthMiddleware());

    // Academic Year legacy routes
    $group->get('/AllAcademicYears', [AcademicYearController::class, 'getAll'])->add(new AuthMiddleware());
    $group->post('/AcademicYear', [AcademicYearController::class, 'create'])->add(new AuthMiddleware());
    $group->post('/SelectedAcademicYear', [AcademicYearController::class, 'setCurrent'])->add(new AuthMiddleware());

    // Subject legacy routes
    $group->get('/AllSubjects', [SubjectController::class, 'getAll'])->add(new AuthMiddleware());
    $group->get('/SubjectList/{id}', [SubjectController::class, 'getAll'])->add(new AuthMiddleware());

    // Fees Payment legacy routes
    $group->get('/AllFeesPayments', [FeesPaymentController::class, 'getPaymentStats'])->add(new AuthMiddleware());
    $group->post('/FeesPayment', [FeesPaymentController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/FeePaymentReport/{studentId}', [FeesPaymentController::class, 'getStudentPayments'])->add(new AuthMiddleware());
    $group->get('/StudentPaymentReport/{studentId}', [FeesPaymentController::class, 'getStudentPayments'])->add(new AuthMiddleware());

    // Exam legacy routes
    $group->get('/exams/{id}', [GradeController::class, 'getAllExams'])->add(new AuthMiddleware());

    // Teacher legacy routes
    $group->post('/TeacherAssign', [TeacherController::class, 'assignSubject'])->add(new AuthMiddleware());
    $group->post('/TeacherSubject', [TeacherController::class, 'assignSubject'])->add(new AuthMiddleware());
    $group->get('/Teacher/{id}', [TeacherController::class, 'getTeacher'])->add(new AuthMiddleware());
    $group->put('/Teacher/{id}', [TeacherController::class, 'updateTeacher'])->add(new AuthMiddleware());

    // Generic address-based routes (used dynamically by frontend)
    $group->get('/Sclass/{id}', [ClassController::class, 'getClass'])->add(new AuthMiddleware());
    $group->get('/Student/{id}', [StudentController::class, 'getStudent'])->add(new AuthMiddleware());
    $group->get('/Subject/{id}', [SubjectController::class, 'getSubject'])->add(new AuthMiddleware());
    $group->get('/ClassSubjects/{id}', [ClassController::class, 'getClassSubjects'])->add(new AuthMiddleware());
    $group->get('/FreeSubjectList/{id}', [ClassController::class, 'getFreeSubjects'])->add(new AuthMiddleware());
    $group->get('/Admin/{id}', [AdminController::class, 'getProfile'])->add(new AuthMiddleware());

    // DELETE routes with address variable
    $group->delete('/Sclass/{id}', [ClassController::class, 'delete'])->add(new AuthMiddleware());
    $group->delete('/Student/{id}', [StudentController::class, 'deleteStudent'])->add(new AuthMiddleware());
    $group->delete('/Teacher/{id}', [TeacherController::class, 'deleteTeacher'])->add(new AuthMiddleware());
    $group->delete('/Subject/{id}', [SubjectController::class, 'delete'])->add(new AuthMiddleware());
    $group->delete('/Notice/{id}', [NoticeController::class, 'delete'])->add(new AuthMiddleware());
    $group->delete('/AcademicYear/{id}', [AcademicYearController::class, 'delete'])->add(new AuthMiddleware());

    // PUT routes with address variable
    $group->put('/Student/{id}', [StudentController::class, 'updateStudent'])->add(new AuthMiddleware());

    // POST Create routes with address variable
    $group->post('/SclassCreate', [ClassController::class, 'create'])->add(new AuthMiddleware());
    $group->post('/NoticeCreate', [NoticeController::class, 'create'])->add(new AuthMiddleware());
    $group->post('/SubjectCreate', [SubjectController::class, 'create'])->add(new AuthMiddleware());
    $group->post('/ComplainCreate', [ComplaintController::class, 'create'])->add(new AuthMiddleware());

    // ===== TIMETABLE ROUTES =====
    $group->post('/timetables', [TimetableController::class, 'create'])->add(new AuthMiddleware());
    $group->get('/timetables/class/{classId}', [TimetableController::class, 'getByClass'])->add(new AuthMiddleware());
    $group->get('/timetables/teacher/{teacherId}', [TimetableController::class, 'getByTeacher'])->add(new AuthMiddleware());
    $group->put('/timetables/{id}', [TimetableController::class, 'update'])->add(new AuthMiddleware());
    $group->delete('/timetables/{id}', [TimetableController::class, 'delete'])->add(new AuthMiddleware());
    $group->post('/timetables/check-conflicts', [TimetableController::class, 'checkConflicts'])->add(new AuthMiddleware());

    // ===== PAYMENT & FINANCE ROUTES =====
    // Fee Structures
    $group->post('/fee-structures', [PaymentController::class, 'createFeeStructure'])->add(new AuthMiddleware());
    $group->get('/fee-structures', [PaymentController::class, 'getAllFeeStructures'])->add(new AuthMiddleware());
    $group->put('/fee-structures/{id}', [PaymentController::class, 'updateFeeStructure'])->add(new AuthMiddleware());
    $group->delete('/fee-structures/{id}', [PaymentController::class, 'deleteFeeStructure'])->add(new AuthMiddleware());
    // Import defaults from academic year
    $group->post('/fee-structures/import-from-year', function ($request, $response) {
        $controller = new PaymentController();
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody() ?? [];
        $query = $request->getQueryParams();
        $academicYearId = $data['academic_year_id'] ?? $query['academic_year_id'] ?? null;
        if (!$academicYearId) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'academic_year_id is required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $result = $controller->importFeeStructuresFromAcademicYear($user->id, $academicYearId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    // Payments (use closures to adapt non-PSR-7 signatures)
    $group->post('/payments', function ($request, $response) {
        $controller = new PaymentController();
        $user = $request->getAttribute('user');
        $data = (array) ($request->getParsedBody() ?? []);
        if (!isset($data['recorded_by']) && $user) { $data['recorded_by'] = $user->id ?? null; }
        $result = $controller->recordPayment($data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    $group->get('/payments/student/{studentId}', function ($request, $response, $args) {
        $controller = new PaymentController();
        $query = $request->getQueryParams();
        $academicYearId = $query['academic_year_id'] ?? null;
        $result = $controller->getStudentPaymentHistory($args['studentId'], $academicYearId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    $group->get('/payments', function ($request, $response) {
        $controller = new PaymentController();
        $filters = $request->getQueryParams();
        $result = $controller->getAllPayments($filters);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    $group->get('/payments/summary', function ($request, $response) {
        $controller = new PaymentController();
        $query = $request->getQueryParams();
        $academicYearId = $query['academic_year_id'] ?? null;
        $term = $query['term'] ?? null;
        if (!$academicYearId) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'academic_year_id is required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $result = $controller->getPaymentSummary($academicYearId, $term);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    // Invoices (use closures to adapt non-PSR-7 signatures)
    $group->post('/invoices', function ($request, $response) {
        $controller = new PaymentController();
        $user = $request->getAttribute('user');
        $data = (array) ($request->getParsedBody() ?? []);
        if (!isset($data['issued_by']) && $user) { $data['issued_by'] = $user->id ?? null; }
        $result = $controller->createInvoice($data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    $group->get('/invoices/{id}', function ($request, $response, $args) {
        $controller = new PaymentController();
        $result = $controller->getInvoice($args['id']);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    $group->get('/invoices', function ($request, $response) {
        $controller = new PaymentController();
        $filters = $request->getQueryParams();
        $result = $controller->getAllInvoices($filters);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    // ===== REPORTS & ANALYTICS ROUTES =====
    $group->get('/reports/class-performance', [ReportsController::class, 'getClassPerformance'])->add(new AuthMiddleware());
    $group->get('/reports/subject-performance', [ReportsController::class, 'getSubjectPerformance'])->add(new AuthMiddleware());
    $group->get('/reports/top-performers', [ReportsController::class, 'getTopPerformers'])->add(new AuthMiddleware());

    // ===== PARENT ROUTES =====
    $group->post('/parents/register', [ParentController::class, 'register']);
    $group->post('/parents/login', [ParentController::class, 'login']);
    $group->get('/parents/profile', [ParentController::class, 'getProfile'])->add(new AuthMiddleware());
    $group->put('/parents/profile', [ParentController::class, 'updateProfile'])->add(new AuthMiddleware());
    $group->post('/parents/verify-child', [ParentController::class, 'verifyAndLinkChild'])->add(new AuthMiddleware());
    $group->get('/parents/children', [ParentController::class, 'getChildren'])->add(new AuthMiddleware());
    $group->get('/parents/children/{student_id}/attendance', [ParentController::class, 'getChildAttendance'])->add(new AuthMiddleware());
    $group->get('/parents/children/{student_id}/results', [ParentController::class, 'getChildResults'])->add(new AuthMiddleware());
    $group->get('/parents/notices', [ParentController::class, 'getNotices'])->add(new AuthMiddleware());
    $group->get('/parents/notifications', [ParentController::class, 'getNotifications'])->add(new AuthMiddleware());
    $group->put('/parents/notifications/{id}/read', [ParentController::class, 'markNotificationRead'])->add(new AuthMiddleware());
    $group->post('/parents/communications', [ParentController::class, 'createCommunication'])->add(new AuthMiddleware());
    $group->get('/parents/communications', [ParentController::class, 'getCommunications'])->add(new AuthMiddleware());
    $group->get('/parents/communications/{id}', [ParentController::class, 'getCommunicationDetails'])->add(new AuthMiddleware());

    // ===== MEDICAL STAFF ROUTES =====
    $group->post('/medical/register', [MedicalController::class, 'register'])->add(new AuthMiddleware());
    $group->post('/medical/login', [MedicalController::class, 'login']);
    $group->get('/medical/staff', [MedicalController::class, 'getAllStaff'])->add(new AuthMiddleware());
    $group->post('/medical/records', [MedicalController::class, 'createRecord'])->add(new AuthMiddleware());
    $group->put('/medical/records/{id}', [MedicalController::class, 'updateRecord'])->add(new AuthMiddleware());
    $group->post('/medical/records/{id}/close', [MedicalController::class, 'closeRecord'])->add(new AuthMiddleware());
    $group->get('/medical/records/student/{student_id}', [MedicalController::class, 'getStudentRecords'])->add(new AuthMiddleware());
    $group->get('/medical/records/active', [MedicalController::class, 'getActiveRecords'])->add(new AuthMiddleware());
    $group->get('/medical/records/{id}', [MedicalController::class, 'getRecordById'])->add(new AuthMiddleware());
    $group->post('/medical/documents/upload', [MedicalController::class, 'uploadDocument'])->add(new AuthMiddleware());

    // ===== HOUSE/TOWN SYSTEM ROUTES =====
    // IMPORTANT: Specific routes must come before parameterized routes
    $group->post('/houses', [HouseController::class, 'createHouse'])->add(new AuthMiddleware());
    $group->get('/houses', [HouseController::class, 'getAllHouses'])->add(new AuthMiddleware());
    $group->get('/houses/eligible-students', [HouseController::class, 'getEligibleStudents'])->add(new AuthMiddleware());
    $group->post('/houses/assign-master', [HouseController::class, 'assignHouseMaster'])->add(new AuthMiddleware());
    $group->post('/houses/register-student', [HouseController::class, 'registerStudent'])->add(new AuthMiddleware());
    $group->get('/houses/{id}/students', [HouseController::class, 'getHouseStudents'])->add(new AuthMiddleware());
    $group->get('/houses/{id}', [HouseController::class, 'getHouseDetails'])->add(new AuthMiddleware());
    $group->put('/houses/{id}', [HouseController::class, 'updateHouse'])->add(new AuthMiddleware());

    // ===== SUSPENSION MANAGEMENT ROUTES =====
    $group->post('/suspensions', [SuspensionController::class, 'suspendStudent'])->add(new AuthMiddleware());
    $group->put('/suspensions/{student_id}/lift', [SuspensionController::class, 'liftSuspension'])->add(new AuthMiddleware());
    $group->get('/suspensions/student/{student_id}/history', [SuspensionController::class, 'getSuspensionHistory'])->add(new AuthMiddleware());
    $group->get('/suspensions/active', [SuspensionController::class, 'getActiveSuspensions'])->add(new AuthMiddleware());
    $group->get('/reports/attendance-summary', [ReportsController::class, 'getAttendanceSummary'])->add(new AuthMiddleware());
    $group->get('/reports/student-attendance/{studentId}', [ReportsController::class, 'getStudentAttendance'])->add(new AuthMiddleware());
    $group->get('/reports/financial-overview', [ReportsController::class, 'getFinancialOverview'])->add(new AuthMiddleware());
    $group->get('/reports/fee-collection', [ReportsController::class, 'getFeeCollection'])->add(new AuthMiddleware());
    $group->get('/reports/behavior', [ReportsController::class, 'getBehaviorReports'])->add(new AuthMiddleware());
    $group->get('/reports/dashboard-stats', [ReportsController::class, 'getDashboardStats'])->add(new AuthMiddleware());

    // ===== SYSTEM SETTINGS ROUTES =====
    $group->get('/settings', [SettingsController::class, 'get'])->add(new AuthMiddleware());
    $group->put('/settings', [SettingsController::class, 'update'])->add(new AuthMiddleware());
    $group->post('/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->add(new AuthMiddleware());
    $group->post('/settings/backup', [SettingsController::class, 'createBackup'])->add(new AuthMiddleware());
    $group->post('/settings/restore', [SettingsController::class, 'restoreBackup'])->add(new AuthMiddleware());

    // ===== ADMIN-SPECIFIC ENHANCED ROUTES =====
    // Activity Logs (Admin)
    $group->get('/admin/activity-logs', [\App\Controllers\ActivityLogController::class, 'getLogs'])->add(new AuthMiddleware());
    $group->get('/admin/activity-logs/stats', [\App\Controllers\ActivityLogController::class, 'getStats'])->add(new AuthMiddleware());
    $group->get('/admin/activity-logs/export', [\App\Controllers\ActivityLogController::class, 'export'])->add(new AuthMiddleware());

    // Notifications (Admin)
    $group->post('/admin/notifications', [NotificationController::class, 'createNotification'])->add(new AuthMiddleware());
    $group->get('/admin/notifications', [NotificationController::class, 'getAllNotifications'])->add(new AuthMiddleware());
    $group->put('/admin/notifications/{id}', [NotificationController::class, 'updateNotification'])->add(new AuthMiddleware());
    $group->delete('/admin/notifications/{id}', [NotificationController::class, 'deleteNotification'])->add(new AuthMiddleware());

    // System Settings (Admin)
    $group->get('/admin/settings', [SettingsController::class, 'getSettings'])->add(new AuthMiddleware());
    $group->put('/admin/settings', [SettingsController::class, 'updateSettings'])->add(new AuthMiddleware());
    $group->post('/admin/settings/backup', [SettingsController::class, 'createBackup'])->add(new AuthMiddleware());
    $group->post('/admin/settings/test-email', [SettingsController::class, 'testEmail'])->add(new AuthMiddleware());

    // Reports (Admin)
    $group->get('/admin/reports/overview', [ReportsController::class, 'getOverview'])->add(new AuthMiddleware());
    $group->get('/admin/reports/academic', [ReportsController::class, 'getAcademicReport'])->add(new AuthMiddleware());
    $group->get('/admin/reports/financial', [ReportsController::class, 'getFinancialReport'])->add(new AuthMiddleware());
    $group->get('/admin/reports/attendance', [ReportsController::class, 'getAttendanceReport'])->add(new AuthMiddleware());
    $group->get('/admin/reports/{type}/export', [ReportsController::class, 'exportReport'])->add(new AuthMiddleware());

    // Cache Management
    $group->post('/admin/cache/clear', function ($request, $response) {
        try {
            $cache = new \App\Utils\Cache();
            $count = $cache->flush();
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Cleared $count cache files"
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new AuthMiddleware());

    // ===== USER NOTIFICATIONS ROUTES handled in /api/api alias section (lines 56-107) =====
    // Note: Main notification routes are in the alias section to handle /api/api/ paths
    
    // Password Reset routes (public, no auth required)
    $group->post('/password-reset/request', [PasswordResetController::class, 'requestReset']);
    $group->post('/password-reset/verify', [PasswordResetController::class, 'verifyToken']);
    $group->post('/password-reset/reset', [PasswordResetController::class, 'resetPassword']);

    // ===== TOWN MASTER MANAGEMENT (Admin) =====
    $group->get('/admin/towns', [\App\Controllers\TownMasterController::class, 'getAllTowns'])->add(new AuthMiddleware());
    $group->post('/admin/towns', [\App\Controllers\TownMasterController::class, 'createTown'])->add(new AuthMiddleware());
    $group->put('/admin/towns/{id}', [\App\Controllers\TownMasterController::class, 'updateTown'])->add(new AuthMiddleware());
    $group->delete('/admin/towns/{id}', [\App\Controllers\TownMasterController::class, 'deleteTown'])->add(new AuthMiddleware());
    
    $group->get('/admin/towns/{id}/blocks', [\App\Controllers\TownMasterController::class, 'getBlocks'])->add(new AuthMiddleware());
    $group->put('/admin/blocks/{id}', [\App\Controllers\TownMasterController::class, 'updateBlock'])->add(new AuthMiddleware());
    
    $group->post('/admin/towns/{id}/assign-master', [\App\Controllers\TownMasterController::class, 'assignTownMaster'])->add(new AuthMiddleware());
    $group->delete('/admin/town-masters/{id}', [\App\Controllers\TownMasterController::class, 'removeTownMaster'])->add(new AuthMiddleware());

    // ===== TOWN MASTER ROUTES (Teacher with Town Master role) =====
    $group->get('/town-master/my-town', [\App\Controllers\TownMasterController::class, 'getMyTown'])->add(new AuthMiddleware());
    $group->get('/town-master/students', [\App\Controllers\TownMasterController::class, 'getMyStudents'])->add(new AuthMiddleware());
    $group->post('/town-master/register-student', [\App\Controllers\TownMasterController::class, 'registerStudent'])->add(new AuthMiddleware());
    $group->post('/town-master/attendance', [\App\Controllers\TownMasterController::class, 'recordAttendance'])->add(new AuthMiddleware());
    $group->get('/town-master/attendance', [\App\Controllers\TownMasterController::class, 'getAttendance'])->add(new AuthMiddleware());

    // Teacher-prefixed aliases for town master routes
    $group->get('/teacher/town-master/my-town', [\App\Controllers\TownMasterController::class, 'getMyTown'])->add(new AuthMiddleware());
    $group->get('/teacher/town-master/students', [\App\Controllers\TownMasterController::class, 'getMyStudents'])->add(new AuthMiddleware());
    $group->post('/teacher/town-master/register-student', [\App\Controllers\TownMasterController::class, 'registerStudent'])->add(new AuthMiddleware());
    $group->post('/teacher/town-master/attendance', [\App\Controllers\TownMasterController::class, 'recordAttendance'])->add(new AuthMiddleware());
    $group->get('/teacher/town-master/attendance', [\App\Controllers\TownMasterController::class, 'getAttendance'])->add(new AuthMiddleware());

    // ===== USER ROLES MANAGEMENT (Admin) =====
    $group->get('/admin/user-roles', [\App\Controllers\UserRoleController::class, 'getAllRoles'])->add(new AuthMiddleware());
    $group->get('/admin/user-roles/available', [\App\Controllers\UserRoleController::class, 'getAvailableRoles'])->add(new AuthMiddleware());
    $group->get('/admin/user-roles/{role}', [\App\Controllers\UserRoleController::class, 'getUsersByRole'])->add(new AuthMiddleware());
    $group->get('/admin/user-roles/{user_type}/{user_id}', [\App\Controllers\UserRoleController::class, 'getUserRoles'])->add(new AuthMiddleware());
    $group->post('/admin/user-roles', [\App\Controllers\UserRoleController::class, 'assignRole'])->add(new AuthMiddleware());
    $group->delete('/admin/user-roles/{id}', [\App\Controllers\UserRoleController::class, 'removeRole'])->add(new AuthMiddleware());
});
