<?php

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║   RUNNING SCHEMA FIXES                         ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=school_management;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "Creating students table...\n";
    
    $pdo->exec("DROP TABLE IF EXISTS students");
    $pdo->exec("
        CREATE TABLE students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(50) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            middle_name VARCHAR(100) DEFAULT NULL,
            date_of_birth DATE NOT NULL,
            gender ENUM('Male', 'Female', 'Other') NOT NULL,
            address TEXT,
            phone VARCHAR(20),
            email VARCHAR(100),
            class_id INT,
            roll_number VARCHAR(20),
            admission_date DATE,
            status ENUM('active', 'inactive', 'graduated', 'suspended') DEFAULT 'active',
            parent_id INT DEFAULT NULL,
            house_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_class (class_id),
            INDEX idx_status (status),
            INDEX idx_house (house_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✅ Students table created\n\n";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Re-insert email settings
    echo "Ensuring email settings exist...\n";
    $emailSettings = json_encode([
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465,
        'smtp_username' => 'info@boschool.org',
        'smtp_password' => '32770&Sabi',
        'smtp_encryption' => 'ssl',
        'from_email' => 'info@boschool.org',
        'from_name' => 'Bo Government Secondary School'
    ]);
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM school_settings");
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO school_settings (email_settings) VALUES (?)")->execute([$emailSettings]);
        echo "✅ Email settings inserted\n";
    } else {
        $pdo->prepare("UPDATE school_settings SET email_settings = ? LIMIT 1")->execute([$emailSettings]);
        echo "✅ Email settings updated\n";
    }
    
    echo "\n╔════════════════════════════════════════════════╗\n";
    echo "║   ✅ DATABASE READY                            ║\n";
    echo "╚════════════════════════════════════════════════╝\n\n";
    
    // List all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables: " . count($tables) . "\n\n";
    echo "✅ Database is ready for use!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

