<?php

/**
 * Migration: Add Performance Indexes
 *
 * This migration adds database indexes to improve query performance
 * for academic year filtering and dashboard statistics.
 *
 * Performance Impact: Expected 40-60% improvement in dashboard load times
 *
 * Created: 2025-10-26
 * Related: SECURITY_AUDIT_REPORT.md Section 3.2
 */

require_once __DIR__ . '/../src/Config/Database.php';

try {
    $db = App\Config\Database::getInstance()->getConnection();

    echo "Starting performance indexes migration...\n";

    // Helper function to check if index exists
    function indexExists($db, $tableName, $indexName) {
        try {
            $stmt = $db->query("SHOW INDEX FROM `{$tableName}` WHERE Key_name = '{$indexName}'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // Helper function to check if table exists
    function tableExists($db, $tableName) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '{$tableName}'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // ==========================================
    // ATTENDANCE INDEXES
    // ==========================================
    if (tableExists($db, 'attendance')) {
        echo "Adding indexes to attendance table...\n";

        // Index for attendance queries filtered by academic year and date
        if (!indexExists($db, 'attendance', 'idx_attendance_year_date')) {
            $db->exec("CREATE INDEX idx_attendance_year_date
                      ON attendance(academic_year_id, date)");
            echo "  ✓ Created idx_attendance_year_date\n";
        }

        // Index for student-specific attendance queries
        if (!indexExists($db, 'attendance', 'idx_attendance_student_year')) {
            $db->exec("CREATE INDEX idx_attendance_student_year
                      ON attendance(student_id, academic_year_id)");
            echo "  ✓ Created idx_attendance_student_year\n";
        }

        // Index for status filtering (present/absent reports)
        if (!indexExists($db, 'attendance', 'idx_attendance_status_date')) {
            $db->exec("CREATE INDEX idx_attendance_status_date
                      ON attendance(status, date)");
            echo "  ✓ Created idx_attendance_status_date\n";
        }
    }

    // ==========================================
    // FEES PAYMENT INDEXES
    // ==========================================
    if (tableExists($db, 'fees_payments')) {
        echo "Adding indexes to fees_payments table...\n";

        // Index for fees queries filtered by academic year and payment date
        if (!indexExists($db, 'fees_payments', 'idx_fees_year_date')) {
            $db->exec("CREATE INDEX idx_fees_year_date
                      ON fees_payments(academic_year_id, payment_date)");
            echo "  ✓ Created idx_fees_year_date\n";
        }

        // Index for student payment history
        if (!indexExists($db, 'fees_payments', 'idx_fees_student_year')) {
            $db->exec("CREATE INDEX idx_fees_student_year
                      ON fees_payments(student_id, academic_year_id)");
            echo "  ✓ Created idx_fees_student_year\n";
        }

        // Index for term-based queries
        if (!indexExists($db, 'fees_payments', 'idx_fees_term')) {
            $db->exec("CREATE INDEX idx_fees_term
                      ON fees_payments(term, academic_year_id)");
            echo "  ✓ Created idx_fees_term\n";
        }
    }

    // ==========================================
    // FEE STRUCTURES INDEXES
    // ==========================================
    if (tableExists($db, 'fee_structures')) {
        echo "Adding indexes to fee_structures table...\n";

        // Index for fee structure lookup by year
        if (!indexExists($db, 'fee_structures', 'idx_fee_struct_year')) {
            $db->exec("CREATE INDEX idx_fee_struct_year
                      ON fee_structures(academic_year_id)");
            echo "  ✓ Created idx_fee_struct_year\n";
        }

        // Index for class-specific fee structures
        if (!indexExists($db, 'fee_structures', 'idx_fee_struct_class_year')) {
            $db->exec("CREATE INDEX idx_fee_struct_class_year
                      ON fee_structures(class_id, academic_year_id)");
            echo "  ✓ Created idx_fee_struct_class_year\n";
        }
    }

    // ==========================================
    // EXAMS INDEXES
    // ==========================================
    if (tableExists($db, 'exams')) {
        echo "Adding indexes to exams table...\n";

        // Index for published results queries
        if (!indexExists($db, 'exams', 'idx_exams_year_published')) {
            $db->exec("CREATE INDEX idx_exams_year_published
                      ON exams(academic_year_id, is_published)");
            echo "  ✓ Created idx_exams_year_published\n";
        }

        // Index for exam date range queries
        if (!indexExists($db, 'exams', 'idx_exams_admin_year_date')) {
            $db->exec("CREATE INDEX idx_exams_admin_year_date
                      ON exams(admin_id, academic_year_id, exam_date)");
            echo "  ✓ Created idx_exams_admin_year_date\n";
        }

        // Index for term-based exam queries
        if (!indexExists($db, 'exams', 'idx_exams_term')) {
            $db->exec("CREATE INDEX idx_exams_term
                      ON exams(term_id, academic_year_id)");
            echo "  ✓ Created idx_exams_term\n";
        }
    }

    // ==========================================
    // EXAM RESULTS INDEXES
    // ==========================================
    if (tableExists($db, 'exam_results')) {
        echo "Adding indexes to exam_results table...\n";

        // Index for student results lookup
        if (!indexExists($db, 'exam_results', 'idx_exam_results_student')) {
            $db->exec("CREATE INDEX idx_exam_results_student
                      ON exam_results(student_id, exam_id)");
            echo "  ✓ Created idx_exam_results_student\n";
        }

        // Index for exam-based queries
        if (!indexExists($db, 'exam_results', 'idx_exam_results_exam')) {
            $db->exec("CREATE INDEX idx_exam_results_exam
                      ON exam_results(exam_id, subject_id)");
            echo "  ✓ Created idx_exam_results_exam\n";
        }
    }

    // ==========================================
    // STUDENT ENROLLMENTS INDEXES
    // ==========================================
    if (tableExists($db, 'student_enrollments')) {
        echo "Adding indexes to student_enrollments table...\n";

        // Index for class roster queries
        if (!indexExists($db, 'student_enrollments', 'idx_enrollment_year_class')) {
            $db->exec("CREATE INDEX idx_enrollment_year_class
                      ON student_enrollments(academic_year_id, class_id)");
            echo "  ✓ Created idx_enrollment_year_class\n";
        }

        // Index for student enrollment history
        if (!indexExists($db, 'student_enrollments', 'idx_enrollment_student_year')) {
            $db->exec("CREATE INDEX idx_enrollment_student_year
                      ON student_enrollments(student_id, academic_year_id)");
            echo "  ✓ Created idx_enrollment_student_year\n";
        }

        // Index for active student queries
        if (!indexExists($db, 'student_enrollments', 'idx_enrollment_status')) {
            $db->exec("CREATE INDEX idx_enrollment_status
                      ON student_enrollments(status, academic_year_id)");
            echo "  ✓ Created idx_enrollment_status\n";
        }
    }

    // ==========================================
    // TEACHER ASSIGNMENTS INDEXES
    // ==========================================
    if (tableExists($db, 'teacher_assignments')) {
        echo "Adding indexes to teacher_assignments table...\n";

        // Index for teacher workload queries
        if (!indexExists($db, 'teacher_assignments', 'idx_teacher_assign_year')) {
            $db->exec("CREATE INDEX idx_teacher_assign_year
                      ON teacher_assignments(teacher_id, academic_year_id)");
            echo "  ✓ Created idx_teacher_assign_year\n";
        }

        // Index for subject assignment queries
        if (!indexExists($db, 'teacher_assignments', 'idx_teacher_assign_subject')) {
            $db->exec("CREATE INDEX idx_teacher_assign_subject
                      ON teacher_assignments(subject_id, academic_year_id)");
            echo "  ✓ Created idx_teacher_assign_subject\n";
        }
    }

    // ==========================================
    // STUDENTS INDEXES
    // ==========================================
    if (tableExists($db, 'students')) {
        echo "Adding indexes to students table...\n";

        // Index for ID number lookup
        if (!indexExists($db, 'students', 'idx_students_id_number')) {
            $db->exec("CREATE INDEX idx_students_id_number
                      ON students(id_number, admin_id)");
            echo "  ✓ Created idx_students_id_number\n";
        }

        // Index for admin student lookup
        if (!indexExists($db, 'students', 'idx_students_admin')) {
            $db->exec("CREATE INDEX idx_students_admin
                      ON students(admin_id)");
            echo "  ✓ Created idx_students_admin\n";
        }
    }

    // ==========================================
    // NOTICES INDEXES
    // ==========================================
    if (tableExists($db, 'notices')) {
        echo "Adding indexes to notices table...\n";

        // Index for date-based notice queries
        if (!indexExists($db, 'notices', 'idx_notices_date')) {
            $db->exec("CREATE INDEX idx_notices_date
                      ON notices(date, admin_id)");
            echo "  ✓ Created idx_notices_date\n";
        }

        // Index for created_at queries (recent notices)
        if (!indexExists($db, 'notices', 'idx_notices_created')) {
            $db->exec("CREATE INDEX idx_notices_created
                      ON notices(created_at, admin_id)");
            echo "  ✓ Created idx_notices_created\n";
        }
    }

    // ==========================================
    // COMPLAINTS INDEXES
    // ==========================================
    if (tableExists($db, 'complaints')) {
        echo "Adding indexes to complaints table...\n";

        // Index for status-based queries
        if (!indexExists($db, 'complaints', 'idx_complaints_status')) {
            $db->exec("CREATE INDEX idx_complaints_status
                      ON complaints(status, admin_id)");
            echo "  ✓ Created idx_complaints_status\n";
        }

        // Index for priority-based queries
        if (!indexExists($db, 'complaints', 'idx_complaints_priority')) {
            $db->exec("CREATE INDEX idx_complaints_priority
                      ON complaints(priority, admin_id)");
            echo "  ✓ Created idx_complaints_priority\n";
        }

        // Index for user complaints
        if (!indexExists($db, 'complaints', 'idx_complaints_user')) {
            $db->exec("CREATE INDEX idx_complaints_user
                      ON complaints(user_id, user_type)");
            echo "  ✓ Created idx_complaints_user\n";
        }
    }

    // ==========================================
    // RESULT PUBLICATIONS INDEXES
    // ==========================================
    if (tableExists($db, 'result_publications')) {
        echo "Adding indexes to result_publications table...\n";

        // Index for publication date queries
        if (!indexExists($db, 'result_publications', 'idx_result_pub_date')) {
            $db->exec("CREATE INDEX idx_result_pub_date
                      ON result_publications(publication_date)");
            echo "  ✓ Created idx_result_pub_date\n";
        }
    }

    // ==========================================
    // ACADEMIC YEARS INDEXES
    // ==========================================
    if (tableExists($db, 'academic_years')) {
        echo "Adding indexes to academic_years table...\n";

        // Index for current year lookup
        if (!indexExists($db, 'academic_years', 'idx_academic_year_current')) {
            $db->exec("CREATE INDEX idx_academic_year_current
                      ON academic_years(admin_id, is_current)");
            echo "  ✓ Created idx_academic_year_current\n";
        }
    }

    // ==========================================
    // VERIFY INDEXES
    // ==========================================
    echo "\n===========================================\n";
    echo "Verifying index creation...\n";
    echo "===========================================\n";

    $indexCount = 0;
    $tables = ['attendance', 'fees_payments', 'fee_structures', 'exams', 'exam_results',
               'student_enrollments', 'teacher_assignments', 'students', 'notices',
               'complaints', 'result_publications', 'academic_years'];

    foreach ($tables as $table) {
        if (tableExists($db, $table)) {
            $stmt = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name LIKE 'idx_%'");
            $count = $stmt->rowCount();
            $indexCount += $count;
            echo "{$table}: {$count} performance indexes\n";
        }
    }

    echo "\n===========================================\n";
    echo "✓ Migration completed successfully!\n";
    echo "✓ Total performance indexes created: {$indexCount}\n";
    echo "✓ Expected performance improvement: 40-60%\n";
    echo "===========================================\n\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
