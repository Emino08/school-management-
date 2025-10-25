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

    echo "Adding position ranking system...\n\n";

    // Step 1: Add subject position to subject_results (actual table name)
    echo "[1/5] Adding subject position to subject_results table...\n";
    
    $subject_result_columns = [
        'subject_position' => "ALTER TABLE subject_results ADD COLUMN subject_position INT NULL COMMENT 'Position in subject within class'",
        'subject_total_students' => "ALTER TABLE subject_results ADD COLUMN subject_total_students INT NULL COMMENT 'Total students taking this subject'",
        'average_score' => "ALTER TABLE subject_results ADD COLUMN average_score DECIMAL(5,2) GENERATED ALWAYS AS ((COALESCE(exam_1_score, 0) + COALESCE(exam_2_score, 0)) / 2) STORED COMMENT 'Average of exam scores'"
    ];

    foreach ($subject_result_columns as $column => $sql) {
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

    // Step 2: Update term_results table for overall class position
    echo "\n[2/5] Updating term_results table with average score and class position...\n";
    
    $term_result_columns = [
        'average_score' => "ALTER TABLE term_results ADD COLUMN average_score DECIMAL(5,2) NULL COMMENT 'Average score across all subjects'",
        'subject_count' => "ALTER TABLE term_results ADD COLUMN subject_count INT DEFAULT 0 COMMENT 'Number of subjects taken'",
        'class_position' => "ALTER TABLE term_results ADD COLUMN class_position INT NULL COMMENT 'Overall position in class'",
        'class_total_students' => "ALTER TABLE term_results ADD COLUMN class_total_students INT NULL COMMENT 'Total students in class'"
    ];

    foreach ($term_result_columns as $column => $sql) {
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

    // Also update result_summary table (used for exams)
    echo "\n   Updating result_summary table...\n";
    $summary_columns = [
        'average_score' => "ALTER TABLE result_summary ADD COLUMN average_score DECIMAL(5,2) NULL COMMENT 'Average score across all subjects'",
        'subject_count' => "ALTER TABLE result_summary ADD COLUMN subject_count INT DEFAULT 0 COMMENT 'Number of subjects taken'",
        'class_position' => "ALTER TABLE result_summary ADD COLUMN class_position INT NULL COMMENT 'Overall position in class'",
        'class_total_students' => "ALTER TABLE result_summary ADD COLUMN class_total_students INT NULL COMMENT 'Total students in class for this exam'"
    ];

    foreach ($summary_columns as $column => $sql) {
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

    // Step 3: Create subject_rankings table for detailed subject position tracking
    echo "\n[3/5] Creating subject_rankings table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subject_rankings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            subject_id INT NOT NULL,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            score DECIMAL(5,2) NOT NULL COMMENT 'Average score for ranking',
            position INT NOT NULL COMMENT 'Position in subject within class',
            total_students INT NOT NULL COMMENT 'Total students taking this subject',
            is_published BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_subject_ranking (exam_id, subject_id, student_id),
            INDEX idx_exam_subject (exam_id, subject_id),
            INDEX idx_position (position),
            INDEX idx_published (is_published)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ Created subject_rankings table\n";

    // Step 4: Create class_rankings table for overall class position tracking
    echo "\n[4/5] Creating class_rankings table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS class_rankings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            average_score DECIMAL(5,2) NOT NULL COMMENT 'Average score across all subjects',
            total_score DECIMAL(7,2) NOT NULL COMMENT 'Total marks obtained',
            subject_count INT NOT NULL COMMENT 'Number of subjects taken',
            position INT NOT NULL COMMENT 'Overall position in class',
            total_students INT NOT NULL COMMENT 'Total students in class',
            is_published BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_class_ranking (exam_id, student_id),
            INDEX idx_exam_class (exam_id, class_id),
            INDEX idx_position (position),
            INDEX idx_published (is_published)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ Created class_rankings table\n";

    // Step 5: Add indexes for performance
    echo "\n[5/5] Adding performance indexes...\n";

    $indexes = [
        "CREATE INDEX idx_subject_position ON subject_results(subject_id, term, average_score DESC)" => "subject_results.subject_position lookup",
        "CREATE INDEX idx_term_average_score ON term_results(academic_year_id, class_id, term, average_score DESC)" => "term_results.average_score lookup",
        "CREATE INDEX idx_result_average_score ON result_summary(exam_id, class_id, average_score DESC)" => "result_summary.average_score lookup"
    ];

    foreach ($indexes as $sql => $description) {
        try {
            $pdo->exec($sql);
            echo "  ✓ Added index: $description\n";
        } catch (PDOException $e) {
            echo "  - Index already exists: $description\n";
        }
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "\n==============================================\n";
    echo "Summary of Changes:\n";
    echo "==============================================\n";
    echo "1. exam_results table:\n";
    echo "   - Added subject_position (position in each subject)\n";
    echo "   - Added subject_total_students\n";
    echo "   - Added average_score (calculated field)\n";
    echo "   - Added academic_year_id\n\n";
    echo "2. result_summary table:\n";
    echo "   - Added average_score (mean across subjects)\n";
    echo "   - Added subject_count\n";
    echo "   - Added class_position (overall ranking)\n";
    echo "   - Added class_total_students\n\n";
    echo "3. New subject_rankings table:\n";
    echo "   - Tracks position per subject per exam\n";
    echo "   - Separate publishing control\n\n";
    echo "4. New class_rankings table:\n";
    echo "   - Tracks overall class position\n";
    echo "   - Average-based ranking\n";
    echo "   - Separate publishing control\n\n";
    echo "5. Performance indexes added for ranking queries\n";
    echo "==============================================\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
