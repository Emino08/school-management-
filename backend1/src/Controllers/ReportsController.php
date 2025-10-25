<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;

class ReportsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ======= PERFORMANCE REPORTS =======

    // Get overall academic performance by class
    public function getClassPerformance($academicYearId, $term = null)
    {
        try {
            $sql = "
                SELECT
                    c.id as class_id,
                    c.class_name,
                    COUNT(DISTINCT tr.student_id) as total_students,
                    ROUND(AVG(tr.percentage), 2) as average_percentage,
                    ROUND(AVG(tr.grade_point), 2) as average_grade_point,
                    MAX(tr.percentage) as highest_percentage,
                    MIN(tr.percentage) as lowest_percentage
                FROM classes c
                LEFT JOIN term_results tr ON c.id = tr.class_id
                    AND tr.academic_year_id = :academic_year_id
            ";

            $params = [':academic_year_id' => $academicYearId];

            if ($term) {
                $sql .= " AND tr.term = :term";
                $params[':term'] = $term;
            }

            $sql .= " GROUP BY c.id, c.class_name ORDER BY c.class_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'performance' => $performance
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching class performance: ' . $e->getMessage()
            ];
        }
    }

    // Get subject-wise performance
    public function getSubjectPerformance($academicYearId, $term = null, $classId = null)
    {
        try {
            $sql = "
                SELECT
                    s.id as subject_id,
                    s.subject_name,
                    COUNT(DISTINCT sr.student_id) as total_students,
                    ROUND(AVG(sr.percentage), 2) as average_percentage,
                    ROUND(AVG(sr.grade_point), 2) as average_grade_point,
                    MAX(sr.percentage) as highest_score,
                    MIN(sr.percentage) as lowest_score,
                    COUNT(CASE WHEN sr.grade IN ('A', 'A+', 'A-') THEN 1 END) as grade_a_count,
                    COUNT(CASE WHEN sr.grade IN ('B', 'B+', 'B-') THEN 1 END) as grade_b_count,
                    COUNT(CASE WHEN sr.grade IN ('C', 'C+', 'C-') THEN 1 END) as grade_c_count,
                    COUNT(CASE WHEN sr.grade IN ('D', 'D+', 'D-') THEN 1 END) as grade_d_count,
                    COUNT(CASE WHEN sr.grade = 'F' THEN 1 END) as grade_f_count
                FROM subjects s
                LEFT JOIN subject_results sr ON s.id = sr.subject_id
                    AND sr.academic_year_id = :academic_year_id
            ";

            $params = [':academic_year_id' => $academicYearId];

            if ($term) {
                $sql .= " AND sr.term = :term";
                $params[':term'] = $term;
            }

            if ($classId) {
                $sql .= " AND sr.student_id IN (SELECT id FROM students WHERE class_id = :class_id)";
                $params[':class_id'] = $classId;
            }

            $sql .= " GROUP BY s.id, s.subject_name ORDER BY s.subject_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'performance' => $performance
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching subject performance: ' . $e->getMessage()
            ];
        }
    }

    // Get top performing students
    public function getTopPerformers($academicYearId, $term = null, $classId = null, $limit = 10)
    {
        try {
            $sql = "
                SELECT
                    tr.student_id,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    s.admission_no,
                    c.class_name,
                    tr.percentage,
                    tr.grade,
                    tr.position,
                    tr.class_position
                FROM term_results tr
                JOIN students s ON tr.student_id = s.id
                JOIN classes c ON tr.class_id = c.id
                WHERE tr.academic_year_id = :academic_year_id
            ";

            $params = [':academic_year_id' => $academicYearId];

            if ($term) {
                $sql .= " AND tr.term = :term";
                $params[':term'] = $term;
            }

            if ($classId) {
                $sql .= " AND tr.class_id = :class_id";
                $params[':class_id'] = $classId;
            }

            $sql .= " ORDER BY tr.percentage DESC LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $topPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'topPerformers' => $topPerformers
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching top performers: ' . $e->getMessage()
            ];
        }
    }

    // ======= ATTENDANCE REPORTS =======

    // Get attendance summary
    public function getAttendanceSummary($academicYearId, $startDate = null, $endDate = null, $classId = null)
    {
        try {
            $sql = "
                SELECT
                    c.id as class_id,
                    c.class_name,
                    COUNT(DISTINCT a.student_id) as total_students,
                    COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent_count,
                    COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late_count,
                    ROUND((COUNT(CASE WHEN a.status = 'Present' THEN 1 END) * 100.0) / NULLIF(COUNT(*), 0), 2) as attendance_percentage
                FROM classes c
                LEFT JOIN students s ON c.id = s.class_id
                LEFT JOIN attendance a ON s.id = a.student_id
                    AND a.academic_year_id = :academic_year_id
            ";

            $params = [':academic_year_id' => $academicYearId];

            if ($startDate) {
                $sql .= " AND a.date >= :start_date";
                $params[':start_date'] = $startDate;
            }

            if ($endDate) {
                $sql .= " AND a.date <= :end_date";
                $params[':end_date'] = $endDate;
            }

            if ($classId) {
                $sql .= " AND c.id = :class_id";
                $params[':class_id'] = $classId;
            }

            $sql .= " GROUP BY c.id, c.class_name ORDER BY c.class_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'summary' => $summary
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching attendance summary: ' . $e->getMessage()
            ];
        }
    }

    // Get student attendance details
    public function getStudentAttendance($studentId, $academicYearId, $startDate = null, $endDate = null)
    {
        try {
            $sql = "
                SELECT
                    a.date,
                    a.status,
                    a.remarks,
                    s.subject_name
                FROM attendance a
                LEFT JOIN subjects s ON a.subject_id = s.id
                WHERE a.student_id = :student_id
                AND a.academic_year_id = :academic_year_id
            ";

            $params = [
                ':student_id' => $studentId,
                ':academic_year_id' => $academicYearId
            ];

            if ($startDate) {
                $sql .= " AND a.date >= :start_date";
                $params[':start_date'] = $startDate;
            }

            if ($endDate) {
                $sql .= " AND a.date <= :end_date";
                $params[':end_date'] = $endDate;
            }

            $sql .= " ORDER BY a.date DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get summary
            $sqlSummary = "
                SELECT
                    COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
                    COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_days,
                    COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days,
                    COUNT(*) as total_days,
                    ROUND((COUNT(CASE WHEN status = 'Present' THEN 1 END) * 100.0) / NULLIF(COUNT(*), 0), 2) as attendance_percentage
                FROM attendance
                WHERE student_id = :student_id
                AND academic_year_id = :academic_year_id
            ";

            if ($startDate) {
                $sqlSummary .= " AND date >= :start_date";
            }

            if ($endDate) {
                $sqlSummary .= " AND date <= :end_date";
            }

            $stmtSummary = $this->db->prepare($sqlSummary);
            $stmtSummary->execute($params);
            $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'attendance' => $attendance,
                'summary' => $summary
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching student attendance: ' . $e->getMessage()
            ];
        }
    }

    // ======= FINANCIAL REPORTS =======

    // Get financial overview
    public function getFinancialOverview($academicYearId, $term = null)
    {
        try {
            // Total expected revenue (from invoices)
            $sqlExpected = "
                SELECT COALESCE(SUM(total_amount), 0) as expected_revenue
                FROM invoices
                WHERE academic_year_id = :academic_year_id
            ";

            $params = [':academic_year_id' => $academicYearId];

            if ($term) {
                $sqlExpected .= " AND term = :term";
                $params[':term'] = $term;
            }

            $stmtExpected = $this->db->prepare($sqlExpected);
            $stmtExpected->execute($params);
            $expectedRevenue = $stmtExpected->fetchColumn();

            // Total collected revenue
            $sqlCollected = "
                SELECT COALESCE(SUM(amount_paid), 0) as collected_revenue
                FROM payments
                WHERE academic_year_id = :academic_year_id
                AND status = 'Completed'
            ";

            if ($term) {
                $sqlCollected .= " AND term = :term";
            }

            $stmtCollected = $this->db->prepare($sqlCollected);
            $stmtCollected->execute($params);
            $collectedRevenue = $stmtCollected->fetchColumn();

            // Outstanding balance
            $sqlOutstanding = "
                SELECT COALESCE(SUM(balance), 0) as outstanding_balance
                FROM invoices
                WHERE academic_year_id = :academic_year_id
                AND status IN ('Pending', 'Partially Paid', 'Overdue')
            ";

            if ($term) {
                $sqlOutstanding .= " AND term = :term";
            }

            $stmtOutstanding = $this->db->prepare($sqlOutstanding);
            $stmtOutstanding->execute($params);
            $outstandingBalance = $stmtOutstanding->fetchColumn();

            // Payment method breakdown
            $sqlMethods = "
                SELECT
                    payment_method,
                    COUNT(*) as transaction_count,
                    SUM(amount_paid) as total_amount
                FROM payments
                WHERE academic_year_id = :academic_year_id
                AND status = 'Completed'
            ";

            if ($term) {
                $sqlMethods .= " AND term = :term";
            }

            $sqlMethods .= " GROUP BY payment_method";

            $stmtMethods = $this->db->prepare($sqlMethods);
            $stmtMethods->execute($params);
            $paymentMethods = $stmtMethods->fetchAll(PDO::FETCH_ASSOC);

            // Invoice status breakdown
            $sqlInvoices = "
                SELECT
                    status,
                    COUNT(*) as count,
                    SUM(total_amount) as total_amount,
                    SUM(balance) as total_balance
                FROM invoices
                WHERE academic_year_id = :academic_year_id
            ";

            if ($term) {
                $sqlInvoices .= " AND term = :term";
            }

            $sqlInvoices .= " GROUP BY status";

            $stmtInvoices = $this->db->prepare($sqlInvoices);
            $stmtInvoices->execute($params);
            $invoiceStatus = $stmtInvoices->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'overview' => [
                    'expected_revenue' => $expectedRevenue,
                    'collected_revenue' => $collectedRevenue,
                    'outstanding_balance' => $outstandingBalance,
                    'collection_rate' => $expectedRevenue > 0 ? round(($collectedRevenue / $expectedRevenue) * 100, 2) : 0,
                    'payment_methods' => $paymentMethods,
                    'invoice_status' => $invoiceStatus
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching financial overview: ' . $e->getMessage()
            ];
        }
    }

    // Get fee collection by class
    public function getFeeCollectionByClass($academicYearId, $term = null)
    {
        try {
            $sql = "
                SELECT
                    c.id as class_id,
                    c.class_name,
                    COUNT(DISTINCT i.student_id) as total_students,
                    SUM(i.total_amount) as total_billed,
                    SUM(i.amount_paid) as total_collected,
                    SUM(i.balance) as total_outstanding,
                    ROUND((SUM(i.amount_paid) * 100.0) / NULLIF(SUM(i.total_amount), 0), 2) as collection_rate
                FROM classes c
                LEFT JOIN students s ON c.id = s.class_id
                LEFT JOIN invoices i ON s.id = i.student_id
                    AND i.academic_year_id = :academic_year_id
            ";

            $params = [':academic_year_id' => $academicYearId];

            if ($term) {
                $sql .= " AND i.term = :term";
                $params[':term'] = $term;
            }

            $sql .= " GROUP BY c.id, c.class_name ORDER BY c.class_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $collection = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'collection' => $collection
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching fee collection by class: ' . $e->getMessage()
            ];
        }
    }

    // ======= BEHAVIOR REPORTS =======

    // Get student complaints/behavior reports
    public function getBehaviorReports($academicYearId, $classId = null, $status = null)
    {
        try {
            $sql = "
                SELECT
                    co.id,
                    co.complaint,
                    co.date,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    s.admission_no,
                    c.class_name,
                    co.created_at
                FROM complaints co
                JOIN students s ON co.student_id = s.id
                JOIN classes c ON s.class_id = c.id
                WHERE 1=1
            ";

            $params = [];

            if ($classId) {
                $sql .= " AND s.class_id = :class_id";
                $params[':class_id'] = $classId;
            }

            $sql .= " ORDER BY co.date DESC, co.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get summary
            $sqlSummary = "
                SELECT
                    c.class_name,
                    COUNT(co.id) as complaint_count
                FROM complaints co
                JOIN students s ON co.student_id = s.id
                JOIN classes c ON s.class_id = c.id
                WHERE 1=1
            ";

            if ($classId) {
                $sqlSummary .= " AND s.class_id = :class_id";
            }

            $sqlSummary .= " GROUP BY c.id, c.class_name ORDER BY complaint_count DESC";

            $stmtSummary = $this->db->prepare($sqlSummary);
            $stmtSummary->execute($params);
            $summary = $stmtSummary->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'complaints' => $complaints,
                'summary' => $summary
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching behavior reports: ' . $e->getMessage()
            ];
        }
    }

    // ======= GENERAL DASHBOARD STATS =======

    // Get dashboard statistics
    public function getDashboardStats($academicYearId)
    {
        try {
            // Total students
            $stmtStudents = $this->db->query("SELECT COUNT(*) FROM students");
            $totalStudents = $stmtStudents->fetchColumn();

            // Total teachers
            $stmtTeachers = $this->db->query("SELECT COUNT(*) FROM teachers");
            $totalTeachers = $stmtTeachers->fetchColumn();

            // Total classes
            $stmtClasses = $this->db->query("SELECT COUNT(*) FROM classes");
            $totalClasses = $stmtClasses->fetchColumn();

            // Recent payments
            $stmtPayments = $this->db->prepare("
                SELECT SUM(amount_paid) as recent_payments
                FROM payments
                WHERE academic_year_id = :academic_year_id
                AND DATE(payment_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND status = 'Completed'
            ");
            $stmtPayments->execute([':academic_year_id' => $academicYearId]);
            $recentPayments = $stmtPayments->fetchColumn();

            // Average attendance this month
            $stmtAttendance = $this->db->prepare("
                SELECT ROUND((COUNT(CASE WHEN status = 'Present' THEN 1 END) * 100.0) / NULLIF(COUNT(*), 0), 2) as avg_attendance
                FROM attendance
                WHERE academic_year_id = :academic_year_id
                AND date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
            ");
            $stmtAttendance->execute([':academic_year_id' => $academicYearId]);
            $avgAttendance = $stmtAttendance->fetchColumn();

            // Pending complaints
            $stmtComplaints = $this->db->query("SELECT COUNT(*) FROM complaints");
            $pendingComplaints = $stmtComplaints->fetchColumn();

            return [
                'success' => true,
                'stats' => [
                    'total_students' => $totalStudents,
                    'total_teachers' => $totalTeachers,
                    'total_classes' => $totalClasses,
                    'recent_payments' => $recentPayments ?? 0,
                    'average_attendance' => $avgAttendance ?? 0,
                    'pending_complaints' => $pendingComplaints
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching dashboard stats: ' . $e->getMessage()
            ];
        }
    }
}
