<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting comprehensive database migration...\n\n";

    // 1. Fix student_parents table if missing
    echo "1. Checking student_parents table...\n";
    $checkTable = $db->query("SHOW TABLES LIKE 'student_parents'")->fetch();
    if (!$checkTable) {
        echo "Creating student_parents table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS `student_parents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_id` int(11) NOT NULL,
            `parent_id` int(11) NOT NULL,
            `relationship` enum('father','mother','guardian','other') DEFAULT 'guardian',
            `is_primary` tinyint(1) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_parent_student` (`student_id`, `parent_id`),
            KEY `student_id` (`student_id`),
            KEY `parent_id` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        echo "✓ student_parents table created\n";
    } else {
        echo "✓ student_parents table exists\n";
    }

    // 2. Add missing columns to students table
    echo "\n2. Checking students table columns...\n";
    
    // Check and add photo column
    $checkPhoto = $db->query("SHOW COLUMNS FROM students LIKE 'photo'")->fetch();
    if (!$checkPhoto) {
        echo "Adding photo column to students table...\n";
        $db->exec("ALTER TABLE students ADD COLUMN photo VARCHAR(255) NULL AFTER email");
        echo "✓ photo column added\n";
    } else {
        echo "✓ photo column exists\n";
    }

    // 3. Fix student_enrollments table - remove status column if exists and use students.status
    echo "\n3. Fixing student_enrollments table...\n";
    $checkEnrollmentStatus = $db->query("SHOW COLUMNS FROM student_enrollments LIKE 'status'")->fetch();
    if ($checkEnrollmentStatus) {
        echo "Removing status from student_enrollments (should use students.status)...\n";
        $db->exec("ALTER TABLE student_enrollments DROP COLUMN status");
        echo "✓ status column removed from student_enrollments\n";
    }
    
    // Remove is_current column if exists
    $checkIsCurrent = $db->query("SHOW COLUMNS FROM student_enrollments LIKE 'is_current'")->fetch();
    if ($checkIsCurrent) {
        echo "Removing is_current from student_enrollments...\n";
        $db->exec("ALTER TABLE student_enrollments DROP COLUMN is_current");
        echo "✓ is_current column removed\n";
    }

    // Ensure students table has status column
    $checkStudentStatus = $db->query("SHOW COLUMNS FROM students LIKE 'status'")->fetch();
    if (!$checkStudentStatus) {
        echo "Adding status column to students table...\n";
        $db->exec("ALTER TABLE students ADD COLUMN status ENUM('active', 'inactive', 'graduated', 'transferred', 'withdrawn') DEFAULT 'active' AFTER password");
        echo "✓ status column added to students table\n";
    } else {
        echo "✓ status column exists in students table\n";
    }

    // 4. Fix medical_records table
    echo "\n4. Fixing medical_records table...\n";
    
    // Check record_type column
    $checkRecordType = $db->query("SHOW COLUMNS FROM medical_records LIKE 'record_type'")->fetch();
    if ($checkRecordType && strpos($checkRecordType['Type'], 'enum') !== false) {
        echo "Modifying record_type column...\n";
        $db->exec("ALTER TABLE medical_records MODIFY COLUMN record_type VARCHAR(50) NOT NULL");
        echo "✓ record_type column modified\n";
    }
    
    // Check status column in medical_records
    $checkMedicalStatus = $db->query("SHOW COLUMNS FROM medical_records LIKE 'status'")->fetch();
    if ($checkMedicalStatus && strpos($checkMedicalStatus['Type'], 'enum') !== false) {
        echo "Modifying status column in medical_records...\n";
        $db->exec("ALTER TABLE medical_records MODIFY COLUMN status VARCHAR(50) DEFAULT 'active'");
        echo "✓ status column modified in medical_records\n";
    }

    // Add can_edit_by_parent column if not exists
    $checkCanEdit = $db->query("SHOW COLUMNS FROM medical_records LIKE 'can_edit_by_parent'")->fetch();
    if (!$checkCanEdit) {
        echo "Adding can_edit_by_parent column...\n";
        $db->exec("ALTER TABLE medical_records ADD COLUMN can_edit_by_parent TINYINT(1) DEFAULT 1 AFTER added_by");
        echo "✓ can_edit_by_parent column added\n";
    } else {
        echo "✓ can_edit_by_parent column exists\n";
    }

    // 5. Add school_id column to admins table for proper data isolation
    echo "\n5. Checking admins table...\n";
    $checkSchoolId = $db->query("SHOW COLUMNS FROM admins LIKE 'school_id'")->fetch();
    if (!$checkSchoolId) {
        echo "Adding school_id column to admins table...\n";
        $db->exec("ALTER TABLE admins ADD COLUMN school_id INT NULL AFTER id");
        
        // Set school_id for existing admins
        // Super admin (first admin) will have NULL school_id
        // Other admins will get school_id = their creator's id
        $db->exec("UPDATE admins SET school_id = CASE 
            WHEN id = (SELECT MIN(id) FROM (SELECT id FROM admins) AS temp) THEN NULL 
            ELSE (SELECT MIN(id) FROM (SELECT id FROM admins) AS temp)
            END");
        
        echo "✓ school_id column added and populated\n";
    } else {
        echo "✓ school_id column exists\n";
    }

    // 6. Ensure principals table exists and has proper structure
    echo "\n6. Checking principals table...\n";
    $checkPrincipalsTable = $db->query("SHOW TABLES LIKE 'principals'")->fetch();
    if (!$checkPrincipalsTable) {
        echo "Creating principals table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS `principals` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `admin_id` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `photo` varchar(255) DEFAULT NULL,
            `address` text,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `admin_id` (`admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        echo "✓ principals table created\n";
    } else {
        echo "✓ principals table exists\n";
        
        // Check if admin_id column exists
        $checkPrincipalSchoolId = $db->query("SHOW COLUMNS FROM principals LIKE 'admin_id'")->fetch();
        if (!$checkPrincipalSchoolId) {
            echo "Adding admin_id column to principals table...\n";
            $db->exec("ALTER TABLE principals ADD COLUMN admin_id INT NOT NULL AFTER id");
            $db->exec("ALTER TABLE principals ADD KEY admin_id (admin_id)");
            echo "✓ admin_id column added to principals table\n";
        } else {
            echo "✓ admin_id column exists in principals table\n";
        }
    }

    // 7. Update parents table - fix status
    echo "\n7. Fixing parents table status...\n";
    $db->exec("UPDATE parents SET status = 'active' WHERE status IS NULL OR status = 'suspended' OR status = ''");
    echo "✓ Parents status updated to active\n";

    // 8. Create email templates table if not exists
    echo "\n8. Checking email_templates table...\n";
    $checkEmailTemplates = $db->query("SHOW TABLES LIKE 'email_templates'")->fetch();
    if (!$checkEmailTemplates) {
        echo "Creating email_templates table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS `email_templates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `template_name` varchar(100) NOT NULL,
            `subject` varchar(255) NOT NULL,
            `body_html` text NOT NULL,
            `body_text` text,
            `variables` text COMMENT 'JSON array of available variables',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `template_name` (`template_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Insert password reset email template
        $db->exec("INSERT INTO email_templates (template_name, subject, body_html, variables) VALUES 
        ('password_reset', 'Reset Your Password - BoSchool Management System', 
        '<html><body style=\"font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;\">
            <div style=\"max-width: 600px; margin: 0 auto; background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">
                <div style=\"background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;\">
                    <img src=\"https://your-domain.com/logo.png\" alt=\"BoSchool Logo\" style=\"max-width: 150px; margin-bottom: 15px;\">
                    <h1 style=\"color: white; margin: 0; font-size: 28px;\">Password Reset Request</h1>
                </div>
                <div style=\"padding: 40px 30px;\">
                    <p style=\"font-size: 16px; color: #333; line-height: 1.6;\">Hello <strong>{{name}}</strong>,</p>
                    <p style=\"font-size: 16px; color: #333; line-height: 1.6;\">We received a request to reset your password for your <strong>{{role}}</strong> account. Click the button below to create a new password:</p>
                    <div style=\"text-align: center; margin: 30px 0;\">
                        <a href=\"{{reset_link}}\" style=\"background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-size: 16px; font-weight: bold; display: inline-block;\">Reset Password</a>
                    </div>
                    <p style=\"font-size: 14px; color: #666; line-height: 1.6;\">Or copy and paste this link into your browser:</p>
                    <p style=\"font-size: 14px; color: #667eea; word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 5px;\">{{reset_link}}</p>
                    <p style=\"font-size: 14px; color: #666; line-height: 1.6; margin-top: 20px;\"><strong>This link will expire in {{expiry}} minutes.</strong></p>
                    <p style=\"font-size: 14px; color: #666; line-height: 1.6;\">If you didn\\'t request this password reset, please ignore this email or contact support if you have concerns.</p>
                </div>
                <div style=\"background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e0e0e0;\">
                    <p style=\"font-size: 12px; color: #999; margin: 0;\">© {{year}} BoSchool Management System. All rights reserved.</p>
                    <p style=\"font-size: 12px; color: #999; margin: 5px 0 0 0;\">This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body></html>',
        '[\"name\", \"role\", \"reset_link\", \"expiry\", \"year\"]')");
        
        echo "✓ email_templates table created with password reset template\n";
    } else {
        echo "✓ email_templates table exists\n";
    }

    echo "\n✅ All database migrations completed successfully!\n";

} catch (\Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
