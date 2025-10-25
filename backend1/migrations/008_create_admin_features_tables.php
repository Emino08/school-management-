<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class CreateAdminFeaturesTables {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function up() {
        try {
            // Create timetables table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS timetables (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    class_id INT NOT NULL,
                    academic_year_id INT NOT NULL,
                    term INT NOT NULL DEFAULT 1,
                    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
                    subject_id INT NOT NULL,
                    teacher_id INT NOT NULL,
                    start_time TIME NOT NULL,
                    end_time TIME NOT NULL,
                    room VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
                    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
                    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
                    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create fee_structures table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS fee_structures (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fee_name VARCHAR(100) NOT NULL,
                    class_id INT,
                    amount DECIMAL(10, 2) NOT NULL,
                    frequency ENUM('One-time', 'Monthly', 'Termly', 'Yearly') NOT NULL DEFAULT 'Termly',
                    academic_year_id INT NOT NULL,
                    description TEXT,
                    is_mandatory BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
                    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create payments table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS payments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_id INT NOT NULL,
                    fee_structure_id INT NOT NULL,
                    amount_paid DECIMAL(10, 2) NOT NULL,
                    payment_date DATE NOT NULL,
                    payment_method ENUM('Cash', 'Bank Transfer', 'Card', 'Mobile Money', 'Cheque') NOT NULL,
                    reference_number VARCHAR(100),
                    receipt_number VARCHAR(50) UNIQUE,
                    term INT NOT NULL DEFAULT 1,
                    academic_year_id INT NOT NULL,
                    status ENUM('Pending', 'Completed', 'Failed', 'Refunded') DEFAULT 'Completed',
                    notes TEXT,
                    recorded_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE CASCADE,
                    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
                    FOREIGN KEY (recorded_by) REFERENCES admins(id) ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create invoices table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS invoices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    invoice_number VARCHAR(50) UNIQUE NOT NULL,
                    student_id INT NOT NULL,
                    academic_year_id INT NOT NULL,
                    term INT NOT NULL DEFAULT 1,
                    total_amount DECIMAL(10, 2) NOT NULL,
                    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
                    balance DECIMAL(10, 2) NOT NULL,
                    due_date DATE,
                    status ENUM('Pending', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') DEFAULT 'Pending',
                    notes TEXT,
                    issued_by INT NOT NULL,
                    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
                    FOREIGN KEY (issued_by) REFERENCES admins(id) ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create invoice_items table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS invoice_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    invoice_id INT NOT NULL,
                    fee_structure_id INT NOT NULL,
                    description VARCHAR(255),
                    quantity INT DEFAULT 1,
                    unit_price DECIMAL(10, 2) NOT NULL,
                    total_price DECIMAL(10, 2) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
                    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create notifications table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    sender_id INT NOT NULL,
                    sender_role ENUM('Admin', 'Teacher') NOT NULL,
                    recipient_type ENUM('All', 'Students', 'Teachers', 'Parents', 'Specific Class', 'Individual') NOT NULL,
                    recipient_id INT,
                    class_id INT,
                    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
                    status ENUM('Draft', 'Sent', 'Scheduled') DEFAULT 'Sent',
                    scheduled_at TIMESTAMP NULL,
                    sent_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (sender_id) REFERENCES admins(id) ON DELETE CASCADE,
                    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create notification_reads table (track who has read notifications)
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS notification_reads (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    notification_id INT NOT NULL,
                    user_id INT NOT NULL,
                    user_role ENUM('Admin', 'Teacher', 'Student', 'Parent') NOT NULL,
                    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_read (notification_id, user_id, user_role)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create school_settings table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS school_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    school_name VARCHAR(255) NOT NULL,
                    school_code VARCHAR(50),
                    address TEXT,
                    phone VARCHAR(20),
                    email VARCHAR(100),
                    website VARCHAR(255),
                    logo_url VARCHAR(255),
                    principal_name VARCHAR(100),
                    academic_year_start_month INT DEFAULT 9,
                    currency VARCHAR(10) DEFAULT 'USD',
                    timezone VARCHAR(50) DEFAULT 'UTC',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create activity_logs table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS activity_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    user_role ENUM('Admin', 'Teacher', 'Student') NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    entity_type VARCHAR(50),
                    entity_id INT,
                    description TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id, user_role),
                    INDEX idx_entity (entity_type, entity_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Insert default school settings
            $this->pdo->exec("
                INSERT INTO school_settings (school_name, school_code, currency, timezone)
                VALUES ('Your School Name', 'SCH001', 'USD', 'UTC')
                ON DUPLICATE KEY UPDATE id=id
            ");

            echo "✓ All admin feature tables created successfully!\n";

        } catch (PDOException $e) {
            echo "Error creating tables: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down() {
        try {
            // Note: activity_logs table already exists, so we don't drop it
            $this->pdo->exec("DROP TABLE IF EXISTS school_settings");
            $this->pdo->exec("DROP TABLE IF EXISTS notification_reads");
            $this->pdo->exec("DROP TABLE IF EXISTS notifications");
            $this->pdo->exec("DROP TABLE IF EXISTS invoice_items");
            $this->pdo->exec("DROP TABLE IF EXISTS invoices");
            $this->pdo->exec("DROP TABLE IF EXISTS payments");
            $this->pdo->exec("DROP TABLE IF EXISTS fee_structures");
            $this->pdo->exec("DROP TABLE IF EXISTS timetables");

            echo "✓ All admin feature tables dropped successfully!\n";

        } catch (PDOException $e) {
            echo "Error dropping tables: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}

// Run migration
if (php_sapi_name() === 'cli') {
    $migration = new CreateAdminFeaturesTables();
    $migration->up();
}
