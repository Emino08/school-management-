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

    echo "Creating result management tables...\n\n";

    // Create exams table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            class_id INT NOT NULL,
            exam_name VARCHAR(255) NOT NULL,
            exam_type ENUM('test', 'midterm', 'final', 'assessment') DEFAULT 'test',
            exam_date DATE,
            total_marks INT DEFAULT 100,
            passing_marks INT DEFAULT 40,
            status ENUM('scheduled', 'ongoing', 'completed', 'published') DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            INDEX idx_class_year (class_id, academic_year_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created exams table\n";

    // Create exam_results table (stores individual subject marks)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS exam_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            student_id INT NOT NULL,
            subject_id INT NOT NULL,
            test_score DECIMAL(5,2) DEFAULT 0,
            exam_score DECIMAL(5,2) DEFAULT 0,
            total_score DECIMAL(5,2) GENERATED ALWAYS AS (test_score + exam_score) STORED,
            grade VARCHAR(2),
            remarks TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            UNIQUE KEY unique_result (exam_id, student_id, subject_id),
            INDEX idx_student_exam (student_id, exam_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created exam_results table\n";

    // Create result_summary table (aggregated student performance)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS result_summary (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            exam_id INT NOT NULL,
            class_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            total_marks_obtained DECIMAL(7,2),
            total_possible_marks DECIMAL(7,2),
            percentage DECIMAL(5,2),
            grade VARCHAR(2),
            position INT,
            total_students INT,
            remarks TEXT,
            is_published BOOLEAN DEFAULT FALSE,
            published_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            UNIQUE KEY unique_summary (student_id, exam_id),
            INDEX idx_class_exam (class_id, exam_id),
            INDEX idx_published (is_published)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created result_summary table\n";

    // Create result_pins table (for public result checking)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS result_pins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            pin_code VARCHAR(20) UNIQUE NOT NULL,
            student_id INT,
            exam_id INT,
            class_id INT,
            academic_year_id INT NOT NULL,
            pin_type ENUM('single', 'class', 'exam') DEFAULT 'single',
            usage_limit INT DEFAULT 1,
            usage_count INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            valid_from DATE,
            valid_until DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            used_at TIMESTAMP NULL,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE SET NULL,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            INDEX idx_pin_active (pin_code, is_active),
            INDEX idx_student (student_id),
            INDEX idx_exam (exam_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created result_pins table\n";

    // Create grading_system table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS grading_system (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            academic_year_id INT,
            grade VARCHAR(2) NOT NULL,
            min_percentage DECIMAL(5,2) NOT NULL,
            max_percentage DECIMAL(5,2) NOT NULL,
            grade_point DECIMAL(3,2),
            remarks VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            INDEX idx_percentage (min_percentage, max_percentage),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created grading_system table\n";

    // Insert default grading system
    echo "\nInserting default grading system...\n";
    $pdo->exec("
        INSERT INTO grading_system (admin_id, grade, min_percentage, max_percentage, grade_point, remarks) VALUES
        (1, 'A', 85.00, 100.00, 4.00, 'Excellent'),
        (1, 'B', 70.00, 84.99, 3.00, 'Very Good'),
        (1, 'C', 55.00, 69.99, 2.00, 'Good'),
        (1, 'D', 40.00, 54.99, 1.00, 'Pass'),
        (1, 'F', 0.00, 39.99, 0.00, 'Fail')
        ON DUPLICATE KEY UPDATE admin_id=admin_id
    ");
    echo "✓ Inserted default grading system\n";

    echo "\n✅ All result management tables created successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
