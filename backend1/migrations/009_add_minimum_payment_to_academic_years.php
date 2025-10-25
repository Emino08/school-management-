<?php

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Adding minimum payment columns to academic_years table...\n";

    $sql = "ALTER TABLE academic_years 
            ADD COLUMN IF NOT EXISTS term_1_min_payment DECIMAL(10,2) DEFAULT NULL AFTER term_1_fee,
            ADD COLUMN IF NOT EXISTS term_2_min_payment DECIMAL(10,2) DEFAULT NULL AFTER term_2_fee,
            ADD COLUMN IF NOT EXISTS term_3_min_payment DECIMAL(10,2) DEFAULT NULL AFTER term_3_fee";

    $db->exec($sql);

    echo "✓ Minimum payment columns added successfully\n";

    // Update existing records to set min payment as 50% of term fee by default
    echo "Setting default minimum payments (50% of term fee)...\n";

    $updateSql = "UPDATE academic_years SET 
                  term_1_min_payment = term_1_fee * 0.5,
                  term_2_min_payment = term_2_fee * 0.5,
                  term_3_min_payment = CASE WHEN term_3_fee IS NOT NULL THEN term_3_fee * 0.5 ELSE NULL END
                  WHERE term_1_min_payment IS NULL";

    $db->exec($updateSql);

    echo "✓ Default minimum payments set successfully\n";
    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
