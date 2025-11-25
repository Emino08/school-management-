<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$adminModel = new App\Models\Admin();
$studentModel = new App\Models\Student();
$teacherModel = new App\Models\Teacher();
$classModel = new App\Models\ClassModel();
$subjectModel = new App\Models\Subject();
$user = (object)["id"=>1,"admin_id"=>1,"role"=>"Admin"];
$stats = [];
try {
    $adminId = isset($user->admin_id) ? $user->admin_id : $user->id;
    $effectiveAdminId = $adminModel->getEffectiveAdminId($adminId);
    $db = App\Config\Database::getInstance()->getConnection();
    $yearStmt = $db->prepare("SELECT id FROM academic_years WHERE admin_id = :admin AND is_current = 1 LIMIT 1");
    $yearStmt->execute([':admin' => $effectiveAdminId]);
    $year = $yearStmt->fetch(PDO::FETCH_ASSOC);
    $yearId = $year['id'] ?? null;
    $studentsCount = 0;
    if ($yearId) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT se.student_id) as count FROM student_enrollments se INNER JOIN students s ON se.student_id = s.id WHERE s.admin_id = :admin AND se.academic_year_id = :year");
        $stmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
        $studentsCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    }
    if ($studentsCount === 0) {
        $studentsCount = $studentModel->count(['admin_id' => $effectiveAdminId]);
    }
    $classesCount = 0;
    if ($yearId) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT se.class_id) as count FROM student_enrollments se INNER JOIN classes c ON se.class_id = c.id WHERE c.admin_id = :admin AND se.academic_year_id = :year");
        $stmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
        $classesCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    }
    if ($classesCount === 0) {
        $classesCount = $classModel->count(['admin_id' => $effectiveAdminId]);
    }
    $teachersCount = 0;
    if ($yearId) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT ta.teacher_id) as count FROM teacher_assignments ta INNER JOIN teachers t ON ta.teacher_id = t.id WHERE t.admin_id = :admin AND ta.academic_year_id = :year");
        $stmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
        $teachersCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    }
    if ($teachersCount === 0) {
        $teachersCount = $teacherModel->count(['admin_id' => $effectiveAdminId]);
    }
    $adminUsers = $adminModel->getAdminsBySchool($effectiveAdminId) ?: [];
    $adminCount = count($adminUsers);
    $principalCount = $adminModel->hasPrincipalSupport()
        ? $adminModel->count(['parent_admin_id' => $effectiveAdminId, 'role' => 'principal'])
        : 0;
    $stats = [
        'total_students' => $studentsCount,
        'total_teachers' => $teachersCount,
        'total_classes' => $classesCount,
        'total_admins' => $adminCount,
        'total_principals' => $principalCount,
        'current_academic_year_id' => $yearId
    ];
    // Attendance block
    try {
        $today = date('Y-m-d');
        $present = $absent = $late = $excused = $total = $distinctStudents = 0;
        if ($yearId) {
            $q = "SELECT status, COUNT(*) as cnt FROM attendance a INNER JOIN students s ON a.student_id = s.id WHERE s.admin_id = :admin AND a.academic_year_id = :year AND a.date = :today GROUP BY status";
            $stmt = $db->prepare($q);
            $stmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId, ':today' => $today]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $distinctStudents = (int)($ds->fetch(PDO::FETCH_ASSOC)['ds'] ?? 0);
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
        echo "attendance ok\n";
    } catch (Throwable $e) {
        echo "attendance error: {$e->getMessage()}\n";
    }
    // subjects block
    $subjectsCount = 0;
    if ($yearId) {
        $subStmt = $db->prepare("SELECT COUNT(DISTINCT ta.subject_id) AS c FROM teacher_assignments ta INNER JOIN subjects s ON ta.subject_id = s.id WHERE ta.academic_year_id = :year AND s.admin_id = :admin");
        $subStmt->execute([':year' => $yearId, ':admin' => $effectiveAdminId]);
        $subjectsCount = (int)($subStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }
    if ($subjectsCount === 0) {
        $subStmt = $db->prepare("SELECT COUNT(*) AS c FROM subjects WHERE admin_id = :admin");
        $subStmt->execute([':admin' => $effectiveAdminId]);
        $subjectsCount = (int)($subStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }
    $stats['total_subjects'] = $subjectsCount;
    $subjectStats = [
        'total' => $subjectsCount,
        'assigned_to_teacher' => 0,
        'unassigned' => $subjectsCount,
        'average_per_class' => $classesCount > 0 ? round($subjectsCount / $classesCount, 2) : 0
    ];
    try {
        $assignSql = "SELECT COUNT(DISTINCT ta.subject_id) AS c FROM teacher_assignments ta INNER JOIN teachers t ON ta.teacher_id = t.id INNER JOIN subjects s ON ta.subject_id = s.id WHERE t.admin_id = :admin AND s.admin_id = :admin";
        $assignParams = [':admin' => $effectiveAdminId];
        if ($yearId) {
            $assignSql .= " AND ta.academic_year_id = :year";
            $assignParams[':year'] = $yearId;
        }
        $assignStmt = $db->prepare($assignSql);
        $assignStmt->execute($assignParams);
        $subjectStats['assigned_to_teacher'] = (int)($assignStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        $subjectStats['unassigned'] = max(0, $subjectsCount - $subjectStats['assigned_to_teacher']);
    } catch (Throwable $e) {
        echo "subject assign error: {$e->getMessage()}\n";
    }
    $stats['subjects'] = $subjectStats;
    // class stats
    $classStats = [
        'with_students' => 0,
        'without_students' => $classesCount,
        'average_size' => 0,
        'with_class_master' => 0,
        'without_class_master' => $classesCount
    ];
    try {
        $classSql = "SELECT c.id, COUNT(se.student_id) AS student_count FROM classes c LEFT JOIN student_enrollments se ON se.class_id = c.id" . ($yearId ? " AND se.academic_year_id = :year" : "") . " WHERE c.admin_id = :admin GROUP BY c.id";
        $classParams = [':admin' => $effectiveAdminId];
        if ($yearId) {
            $classParams[':year'] = $yearId;
        }
        $classStmt = $db->prepare($classSql);
        $classStmt->execute($classParams);
        $classRows = $classStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalStudentsInClasses = 0;
        foreach ($classRows as $row) {
            $count = (int)($row['student_count'] ?? 0);
            $totalStudentsInClasses += $count;
            if ($count > 0) {
                $classStats['with_students']++;
            }
        }
        $classStats['without_students'] = max(0, $classesCount - $classStats['with_students']);
        $classStats['average_size'] = $classStats['with_students'] > 0 ? round($totalStudentsInClasses / $classStats['with_students'], 2) : 0;
        $masterStmt = $db->prepare("SELECT COUNT(DISTINCT t.class_master_of) AS c FROM teachers t WHERE t.admin_id = :admin AND t.is_class_master = 1 AND t.class_master_of IS NOT NULL");
        $masterStmt->execute([':admin' => $effectiveAdminId]);
        $classStats['with_class_master'] = (int)($masterStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        $classStats['without_class_master'] = max(0, $classesCount - $classStats['with_class_master']);
    } catch (Throwable $e) {
        echo "class stats error: {$e->getMessage()}\n";
    }
    $stats['classes'] = $classStats;
    // fees stats
    if ($yearId) {
        try {
            $feesStmt = $db->prepare("SELECT COALESCE(SUM(fp.amount),0) as total_collected, COUNT(DISTINCT fp.student_id) as students_paid FROM fees_payments fp INNER JOIN students s ON fp.student_id = s.id WHERE s.admin_id = :admin AND fp.academic_year_id = :year");
            $feesStmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
            $fees = $feesStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_collected'=>0,'students_paid'=>0];
            $expectedStmt = $db->prepare("SELECT COALESCE(SUM(fs.amount),0) as expected FROM fee_structures fs WHERE fs.academic_year_id = :year");
            $expectedStmt->execute([':year' => $yearId]);
            $expected = $expectedStmt->fetch(PDO::FETCH_ASSOC);
            $expectedAmount = (float)($expected['expected'] ?? 0);
            $totalCollected = (float)$fees['total_collected'];
            $totalPending = max(0, $expectedAmount - $totalCollected);
            $collectionRate = $expectedAmount > 0 ? round(($totalCollected / $expectedAmount) * 100, 2) : 0;
            $monthStart = date('Y-m-01');
            $monthEnd = date('Y-m-t');
            $monthStmt = $db->prepare("SELECT COALESCE(SUM(amount),0) as month_total FROM fees_payments fp INNER JOIN students s ON fp.student_id = s.id WHERE s.admin_id = :admin AND fp.academic_year_id = :year AND fp.payment_date BETWEEN :start AND :end");
            $monthStmt->execute([':admin' => $adminId, ':year' => $yearId, ':start' => $monthStart, ':end' => $monthEnd]);
            $monthData = $monthStmt->fetch(PDO::FETCH_ASSOC);
            $stats['fees'] = [
                'total_collected' => $totalCollected,
                'total_pending' => $totalPending,
                'collection_rate' => $collectionRate,
                'this_month' => (float)($monthData['month_total'] ?? 0),
                'students_paid' => (int)$fees['students_paid'],
                'total_expected' => $expectedAmount
            ];
            echo "fees ok\n";
        } catch (Throwable $e) {
            echo "fees error: {$e->getMessage()}\n";
        }
    }
    // results
    $stats['results'] = ['published' => 0, 'pending' => 0, 'total' => 0];
    if ($yearId) {
        try {
            $resultsStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published, SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as pending FROM exams e WHERE e.admin_id = :admin AND e.academic_year_id = :year");
            $resultsStmt->execute([':admin' => $effectiveAdminId, ':year' => $yearId]);
            $results = $resultsStmt->fetch(PDO::FETCH_ASSOC);
            $stats['results'] = [
                'total' => (int)($results['total'] ?? 0),
                'published' => (int)($results['published'] ?? 0),
                'pending' => (int)($results['pending'] ?? 0)
            ];
            echo "results ok\n";
        } catch (Throwable $e) {
            echo "results error: {$e->getMessage()}\n";
        }
    }
    // notices
    try {
        $noticesStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as active, SUM(CASE WHEN DATEDIFF(CURDATE(), created_at) <= 7 THEN 1 ELSE 0 END) as recent FROM notices WHERE admin_id = :admin");
        $noticesStmt->execute([':admin' => $adminId]);
        $notices = $noticesStmt->fetch(PDO::FETCH_ASSOC);
        $stats['notices'] = [
            'total' => (int)($notices['total'] ?? 0),
            'active' => (int)($notices['active'] ?? 0),
            'recent' => (int)($notices['recent'] ?? 0)
        ];
        echo "notices ok\n";
    } catch (Throwable $e) {
        echo "notices error: {$e->getMessage()}\n";
    }
    // complaints
    try {
        $complaintsStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress, SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved FROM complaints WHERE admin_id = :admin");
        $complaintsStmt->execute([':admin' => $adminId]);
        $complaints = $complaintsStmt->fetch(PDO::FETCH_ASSOC);
        $stats['complaints'] = [
            'total' => (int)($complaints['total'] ?? 0),
            'pending' => (int)($complaints['pending'] ?? 0),
            'in_progress' => (int)($complaints['in_progress'] ?? 0),
            'resolved' => (int)($complaints['resolved'] ?? 0)
        ];
        echo "complaints ok\n";
    } catch (Throwable $e) {
        echo "complaints error: {$e->getMessage()}\n";
    }
    print_r($stats);
} catch (Throwable $e) {
    echo 'fatal ', $e->getMessage(), "\n";
}
