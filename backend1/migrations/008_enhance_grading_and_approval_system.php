<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Enhancing grading and approval system...\n\n";

    // Step 1: Add new columns to academic_years table
    echo "[1/8] Updating academic_years table with term and grading configurations...\n";

    // Check if columns exist before adding
    $columns_to_add = [
        'number_of_terms' => "ALTER TABLE academic_years ADD COLUMN number_of_terms TINYINT DEFAULT 3 COMMENT '2 or 3 terms per year'",
        'exams_per_term' => "ALTER TABLE academic_years ADD COLUMN exams_per_term TINYINT DEFAULT 1 COMMENT 'Number of exams per term (1 or 2)'",
        'grading_type' => "ALTER TABLE academic_years ADD COLUMN grading_type ENUM('average', 'gpa_5', 'gpa_4') DEFAULT 'average' COMMENT 'Grading calculation method'",
        'result_publication_date' => "ALTER TABLE academic_years ADD COLUMN result_publication_date DATE NULL COMMENT 'Date when results can be published'",
        'auto_calculate_position' => "ALTER TABLE academic_years ADD COLUMN auto_calculate_position BOOLEAN DEFAULT TRUE COMMENT 'Auto calculate student positions'"
    ];

    foreach ($columns_to_add as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "  ✓ Added column: $column\n";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') { // Column already exists
                echo "  - Column already exists: $column\n";
            } else {
                throw $e;
            }
        }
    }

    // Step 2: Create exam_officers table
    echo "\n[2/8] Creating exam_officers table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS exam_officers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            is_active BOOLEAN DEFAULT TRUE,
            can_approve_results BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            INDEX idx_email (email),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ Created exam_officers table\n";

    // Step 3: Update exam_results table for approval workflow
    echo "\n[3/8] Updating exam_results table with approval workflow...\n";

    $exam_result_columns = [
        'uploaded_by_teacher_id' => "ALTER TABLE exam_results ADD COLUMN uploaded_by_teacher_id INT NULL COMMENT 'Teacher who uploaded the grade'",
        'approval_status' => "ALTER TABLE exam_results ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT 'Exam officer approval status'",
        'approved_by_officer_id' => "ALTER TABLE exam_results ADD COLUMN approved_by_officer_id INT NULL COMMENT 'Exam officer who approved'",
        'approved_at' => "ALTER TABLE exam_results ADD COLUMN approved_at TIMESTAMP NULL COMMENT 'When the result was approved'",
        'rejection_reason' => "ALTER TABLE exam_results ADD COLUMN rejection_reason TEXT NULL COMMENT 'Reason for rejection if rejected'",
        'is_published' => "ALTER TABLE exam_results ADD COLUMN is_published BOOLEAN DEFAULT FALSE COMMENT 'Whether result is published to student'"
    ];

    foreach ($exam_result_columns as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "  ✓ Added column: $column\n";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') {
                echo "  - Column already exists: $column\n";
            } else {
                throw $e;
            }
        }
    }

    // Add foreign key for uploaded_by_teacher_id
    try {
        $pdo->exec("ALTER TABLE exam_results ADD FOREIGN KEY fk_teacher_upload (uploaded_by_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL");
        echo "  ✓ Added foreign key: uploaded_by_teacher_id -> teachers\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42000' || strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  - Foreign key already exists: uploaded_by_teacher_id\n";
        } else {
            throw $e;
        }
    }

    // Add foreign key for approved_by_officer_id
    try {
        $pdo->exec("ALTER TABLE exam_results ADD FOREIGN KEY fk_officer_approval (approved_by_officer_id) REFERENCES exam_officers(id) ON DELETE SET NULL");
        echo "  ✓ Added foreign key: approved_by_officer_id -> exam_officers\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42000' || strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  - Foreign key already exists: approved_by_officer_id\n";
        } else {
            throw $e;
        }
    }

    // Step 4: Create result_publications table
    echo "\n[4/8] Creating result_publications table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS result_publications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            exam_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            term TINYINT NOT NULL COMMENT 'Term number (1, 2, or 3)',
            publication_date DATE NOT NULL COMMENT 'Date when results will be visible to students',
            published_by_admin_id INT NOT NULL,
            total_students INT DEFAULT 0,
            approved_results INT DEFAULT 0,
            pending_results INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE COMMENT 'If false, results are hidden again',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            FOREIGN KEY (published_by_admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            UNIQUE KEY unique_exam_publication (exam_id),
            INDEX idx_publication_date (publication_date),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ Created result_publications table\n";

    // Step 5: Update grading_system table
    echo "\n[5/8] Updating grading_system table...\n";

    // Drop the old grading_system table and recreate with better structure
    $pdo->exec("DROP TABLE IF EXISTS grading_system");

    $pdo->exec("
        CREATE TABLE grading_system (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            academic_year_id INT NULL COMMENT 'NULL means default for all years',
            grade_label VARCHAR(5) NOT NULL COMMENT 'A, B, C, D, F, etc',
            min_score DECIMAL(5,2) NOT NULL COMMENT 'Minimum score for this grade',
            max_score DECIMAL(5,2) NOT NULL COMMENT 'Maximum score for this grade',
            grade_point DECIMAL(3,2) NULL COMMENT 'For GPA systems (e.g., 4.0, 3.0)',
            description VARCHAR(100) NULL COMMENT 'Excellent, Good, Pass, etc',
            is_passing BOOLEAN DEFAULT TRUE COMMENT 'Whether this grade is considered passing',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            INDEX idx_score_range (min_score, max_score),
            INDEX idx_active (is_active),
            INDEX idx_year (academic_year_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ Recreated grading_system table with enhanced structure\n";

    // Step 6: Insert default grading systems
    echo "\n[6/8] Inserting default grading systems...\n";

    // Average-based system (default)
    $pdo->exec("
        INSERT INTO grading_system (admin_id, grade_label, min_score, max_score, grade_point, description, is_passing) VALUES
        (1, 'A', 85.00, 100.00, 5.00, 'Excellent', TRUE),
        (1, 'B', 70.00, 84.99, 4.00, 'Very Good', TRUE),
        (1, 'C', 60.00, 69.99, 3.00, 'Good', TRUE),
        (1, 'D', 50.00, 59.99, 2.00, 'Pass', TRUE),
        (1, 'E', 40.00, 49.99, 1.00, 'Weak Pass', TRUE),
        (1, 'F', 0.00, 39.99, 0.00, 'Fail', FALSE)
        ON DUPLICATE KEY UPDATE admin_id=admin_id
    ");
    echo "  ✓ Inserted default average-based grading system\n";

    // Step 7: Add indexes for performance
    echo "\n[7/8] Adding performance indexes...\n";

    try {
        $pdo->exec("CREATE INDEX idx_approval_status ON exam_results(approval_status)");
        echo "  ✓ Added index on exam_results.approval_status\n";
    } catch (PDOException $e) {
        echo "  - Index already exists: exam_results.approval_status\n";
    }

    try {
        $pdo->exec("CREATE INDEX idx_published ON exam_results(is_published)");
        echo "  ✓ Added index on exam_results.is_published\n";
    } catch (PDOException $e) {
        echo "  - Index already exists: exam_results.is_published\n";
    }

    // Step 8: Update exams table with term information
    echo "\n[8/8] Updating exams table with term and publication info...\n";

    $exam_columns = [
        'term' => "ALTER TABLE exams ADD COLUMN term TINYINT NULL COMMENT 'Term number (1, 2, or 3)'",
        'exam_number' => "ALTER TABLE exams ADD COLUMN exam_number TINYINT NULL COMMENT 'Exam number within term (1 or 2)'",
        'can_be_published' => "ALTER TABLE exams ADD COLUMN can_be_published BOOLEAN DEFAULT FALSE COMMENT 'Admin sets this when ready to publish'",
        'published_date' => "ALTER TABLE exams ADD COLUMN published_date DATE NULL COMMENT 'Date when results were published'"
    ];

    foreach ($exam_columns as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "  ✓ Added column: $column\n";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') {
                echo "  - Column already exists: $column\n";
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "\n==============================================\n";
    echo "Summary of Changes:\n";
    echo "==============================================\n";
    echo "1. Academic years now support:\n";
    echo "   - Configurable number of terms (2 or 3)\n";
    echo "   - Exams per term (1 or 2)\n";
    echo "   - Grading type (average, GPA 4.0, GPA 5.0)\n";
    echo "   - Result publication date\n\n";
    echo "2. Exam officers table created for approval workflow\n\n";
    echo "3. Exam results now have approval workflow:\n";
    echo "   - Pending/Approved/Rejected status\n";
    echo "   - Tracked by exam officer\n";
    echo "   - Separate publication flag\n\n";
    echo "4. Result publications table for date-based publishing\n\n";
    echo "5. Enhanced grading system with:\n";
    echo "   - Customizable grade ranges per academic year\n";
    echo "   - GPA point mapping\n";
    echo "   - Pass/Fail indicators\n\n";
    echo "6. Exams table enhanced with term tracking\n";
    echo "==============================================\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
