<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Ensuring fees_payments table exists...\n";

    $stmt = $db->query("SHOW TABLES LIKE 'fees_payments'");
    if ($stmt->rowCount() === 0) {
        $sql = "CREATE TABLE fees_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            term ENUM('1st Term','2nd Term','Full Year') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            payment_method ENUM('cash','bank_transfer','cheque','online') DEFAULT 'cash',
            receipt_number VARCHAR(100),
            status ENUM('pending','paid','partial','overdue') DEFAULT 'pending',
            remarks TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
            INDEX idx_student (student_id),
            INDEX idx_academic_year (academic_year_id),
            INDEX idx_payment_date (payment_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($sql);
        echo "  ✓ Created fees_payments table\n";
    } else {
        echo "  - fees_payments table already exists\n";
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

