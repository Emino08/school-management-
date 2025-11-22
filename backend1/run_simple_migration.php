<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "==== Running Database Migrations ====\n\n";
    
    // 1. Add first_name and last_name to teachers table
    echo "1. Adding name columns to teachers table...\n";
    try {
        $db->exec("ALTER TABLE teachers ADD COLUMN first_name VARCHAR(100) NULL AFTER name");
        echo "   - Added first_name column\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            echo "   - first_name column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $db->exec("ALTER TABLE teachers ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name");
        echo "   - Added last_name column\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            echo "   - last_name column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Split existing teacher names
    echo "   - Splitting existing teacher names...\n";
    $db->exec("
        UPDATE teachers 
        SET 
            first_name = IF(first_name IS NULL OR first_name = '', 
                            IF(LOCATE(' ', TRIM(name)) > 0, 
                               SUBSTRING_INDEX(TRIM(name), ' ', 1), 
                               TRIM(name)),
                            first_name),
            last_name = IF(last_name IS NULL OR last_name = '', 
                           IF(LOCATE(' ', TRIM(name)) > 0, 
                              SUBSTRING_INDEX(TRIM(name), ' ', -1), 
                              ''),
                           last_name)
        WHERE name IS NOT NULL AND name != ''
    ");
    echo "   ✓ Teacher name columns configured\n\n";
    
    // 2. Update students name columns
    echo "2. Updating student name columns...\n";
    $db->exec("
        UPDATE students 
        SET 
            first_name = IF((first_name IS NULL OR first_name = '') AND name IS NOT NULL, 
                            IF(LOCATE(' ', TRIM(name)) > 0, 
                               SUBSTRING_INDEX(TRIM(name), ' ', 1), 
                               TRIM(name)),
                            first_name),
            last_name = IF((last_name IS NULL OR last_name = '') AND name IS NOT NULL, 
                           IF(LOCATE(' ', TRIM(name)) > 0, 
                              SUBSTRING_INDEX(TRIM(name), ' ', -1), 
                              ''),
                           last_name)
        WHERE name IS NOT NULL AND name != ''
    ");
    echo "   ✓ Student names updated\n\n";
    
    // 3. Create notification_reads table
    echo "3. Creating notification_reads table...\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS notification_reads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                notification_id INT NOT NULL,
                user_id INT NOT NULL,
                user_role ENUM('Admin', 'Teacher', 'Student', 'Parent') NOT NULL,
                read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_read (notification_id, user_id, user_role),
                INDEX idx_user_notifications (user_id, user_role, read_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "   ✓ notification_reads table created\n\n";
    } catch (PDOException $e) {
        echo "   - Table already exists\n\n";
    }
    
    // 4. Create password_reset_tokens table
    echo "4. Creating password_reset_tokens table...\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                user_role ENUM('Admin', 'Teacher', 'Student', 'Parent') NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_email_role (email, user_role),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "   ✓ password_reset_tokens table created\n\n";
    } catch (PDOException $e) {
        echo "   - Table already exists\n\n";
    }
    
    // 5. Update currency to SLE
    echo "5. Updating currency to SLE...\n";
    $db->exec("UPDATE school_settings SET currency = 'SLE' WHERE currency = 'USD' OR currency IS NULL");
    echo "   ✓ Currency updated\n\n";
    
    // 6. Ensure settings row exists
    echo "6. Ensuring school_settings row exists...\n";
    $stmt = $db->query("SELECT COUNT(*) FROM school_settings");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO school_settings (school_name, school_code, academic_year_start_month, academic_year_end_month, currency, timezone) VALUES ('', '', 9, 6, 'SLE', 'UTC')");
        echo "   ✓ Default settings row created\n\n";
    } else {
        echo "   - Settings row already exists\n\n";
    }
    
    echo "==== Verification ====\n\n";
    
    // Verify teachers table
    $stmt = $db->query("SHOW COLUMNS FROM teachers WHERE Field IN ('name', 'first_name', 'last_name')");
    echo "Teachers name columns:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  ✓ {$row['Field']} ({$row['Type']})\n";
    }
    
    // Verify notification_reads
    $stmt = $db->query("SHOW TABLES LIKE 'notification_reads'");
    echo "\nNotification reads table: " . ($stmt->rowCount() > 0 ? "✓ EXISTS" : "✗ MISSING") . "\n";
    
    // Verify password_reset_tokens
    $stmt = $db->query("SHOW TABLES LIKE 'password_reset_tokens'");
    echo "Password reset tokens table: " . ($stmt->rowCount() > 0 ? "✓ EXISTS" : "✗ MISSING") . "\n";
    
    // Verify currency
    $stmt = $db->query("SELECT currency FROM school_settings LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nCurrency setting: " . ($row['currency'] ?? 'NOT SET') . "\n";
    
    echo "\n==== ✓ Migration Complete! ====\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
