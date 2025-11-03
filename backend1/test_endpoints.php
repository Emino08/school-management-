<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;

// Test database connection
try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Database connection successful\n\n";

    // Check if activity_logs table exists
    $stmt = $db->query("SHOW TABLES LIKE 'activity_logs'");
    if ($stmt->rowCount() > 0) {
        echo "✓ activity_logs table exists\n";

        // Check table structure
        $stmt = $db->query("DESCRIBE activity_logs");
        echo "  Columns: ";
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        echo implode(', ', $columns) . "\n";
    } else {
        echo "✗ activity_logs table DOES NOT exist\n";
    }

    echo "\n";

    // Check if school_settings table exists
    $stmt = $db->query("SHOW TABLES LIKE 'school_settings'");
    if ($stmt->rowCount() > 0) {
        echo "✓ school_settings table exists\n";

        // Check if there are any settings
        $stmt = $db->query("SELECT COUNT(*) FROM school_settings");
        $count = $stmt->fetchColumn();
        echo "  Records: $count\n";
    } else {
        echo "✗ school_settings table DOES NOT exist\n";
    }

    echo "\n";

    // Check if academic_years table exists
    $stmt = $db->query("SHOW TABLES LIKE 'academic_years'");
    if ($stmt->rowCount() > 0) {
        echo "✓ academic_years table exists\n";

        // Check if there's a current year
        $stmt = $db->query("SELECT COUNT(*) FROM academic_years WHERE is_current = 1");
        $count = $stmt->fetchColumn();
        echo "  Current academic years: $count\n";
    } else {
        echo "✗ academic_years table DOES NOT exist\n";
    }

    echo "\n";

    // Test ActivityLogger
    try {
        $logger = new \App\Utils\ActivityLogger($db);
        echo "✓ ActivityLogger class loaded successfully\n";

        // Try to fetch logs
        $logs = $logger->getLogs(null, null, null, null, 1, 0);
        echo "✓ ActivityLogger->getLogs() works (returned " . count($logs) . " logs)\n";
    } catch (\Exception $e) {
        echo "✗ ActivityLogger error: " . $e->getMessage() . "\n";
    }

} catch (\Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}
