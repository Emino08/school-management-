<?php

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Creating activity_logs table...\n";

    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(50) NOT NULL COMMENT 'admin, teacher, student, exam_officer',
        activity_type VARCHAR(100) NOT NULL COMMENT 'login, logout, create, update, delete, view, publish, approve, etc',
        description TEXT NOT NULL,
        entity_type VARCHAR(50) NULL COMMENT 'student, teacher, result, fee, etc',
        entity_id INT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        metadata JSON NULL COMMENT 'Additional data about the activity',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id, user_type),
        INDEX idx_activity (activity_type),
        INDEX idx_entity (entity_type, entity_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);

    echo "✓ activity_logs table created successfully\n";

    // Create table for principal remarks
    echo "\nCreating principal_remarks table...\n";

    $sql = "CREATE TABLE IF NOT EXISTS principal_remarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        academic_year_id INT NOT NULL,
        class_id INT NOT NULL,
        term INT NOT NULL COMMENT '1, 2, or 3',
        remarks TEXT NOT NULL,
        principal_signature VARCHAR(255) NULL COMMENT 'Path to signature image',
        principal_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_remark (academic_year_id, class_id, term),
        INDEX idx_year_term (academic_year_id, term)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);

    echo "✓ principal_remarks table created successfully\n";

    // Add teacher profile fields
    echo "\nAdding teacher profile fields...\n";

    $alterSql = "ALTER TABLE teachers 
                 ADD COLUMN IF NOT EXISTS signature VARCHAR(255) NULL COMMENT 'Path to signature image' AFTER email,
                 ADD COLUMN IF NOT EXISTS is_class_master BOOLEAN DEFAULT FALSE AFTER signature,
                 ADD COLUMN IF NOT EXISTS class_master_of INT NULL AFTER is_class_master,
                 ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER class_master_of,
                 ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER phone,
                 ADD COLUMN IF NOT EXISTS qualification VARCHAR(255) NULL AFTER address,
                 ADD COLUMN IF NOT EXISTS specialization VARCHAR(255) NULL AFTER qualification";

    $db->exec($alterSql);

    echo "✓ Teacher profile fields added successfully\n";

    // Add principal profile fields to admin table
    echo "\nAdding principal/admin profile fields...\n";

    $adminAlterSql = "ALTER TABLE admins 
                      ADD COLUMN IF NOT EXISTS signature VARCHAR(255) NULL COMMENT 'Path to signature image' AFTER email,
                      ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER signature,
                      ADD COLUMN IF NOT EXISTS school_name VARCHAR(255) NULL AFTER phone,
                      ADD COLUMN IF NOT EXISTS school_address TEXT NULL AFTER school_name,
                      ADD COLUMN IF NOT EXISTS school_logo VARCHAR(255) NULL AFTER school_address";

    $db->exec($adminAlterSql);

    echo "✓ Principal/Admin profile fields added successfully\n";

    // Add foreign key for class_master_of
    echo "\nAdding foreign key constraint...\n";

    try {
        $fkSql = "ALTER TABLE teachers 
                  ADD CONSTRAINT fk_class_master 
                  FOREIGN KEY (class_master_of) REFERENCES classes(id) ON DELETE SET NULL";
        $db->exec($fkSql);
        echo "✓ Foreign key constraint added successfully\n";
    } catch (\Exception $e) {
        echo "Note: Foreign key might already exist or table structure incompatible\n";
    }

    echo "\n✓ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
