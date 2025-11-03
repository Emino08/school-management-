<?php

/**
 * Migration Runner Script
 *
 * This script runs a specific migration file.
 *
 * Usage:
 *   php run_migration.php 031_add_performance_indexes.php
 *   php run_migration.php all  (runs all migrations in order)
 */

// Check if migration file is provided
if ($argc < 2) {
    echo "Usage: php run_migration.php <migration_file_name>\n";
    echo "   or: php run_migration.php all\n";
    echo "\nExample: php run_migration.php 031_add_performance_indexes.php\n";
    exit(1);
}

$migrationName = $argv[1];
$migrationsDir = __DIR__ . '/migrations';

if ($migrationName === 'all') {
    // Run all migrations
    echo "Running all migrations...\n";
    echo "===========================================\n\n";

    $files = glob($migrationsDir . '/*.php');
    sort($files);

    $success = 0;
    $failed = 0;

    foreach ($files as $file) {
        $filename = basename($file);
        echo "Running migration: {$filename}\n";
        echo "-------------------------------------------\n";

        ob_start();
        try {
            include $file;
            $output = ob_get_clean();
            echo $output;
            echo "\n✓ Completed: {$filename}\n\n";
            $success++;
        } catch (Exception $e) {
            $output = ob_get_clean();
            echo $output;
            echo "\n❌ Failed: {$filename}\n";
            echo "   Error: " . $e->getMessage() . "\n\n";
            $failed++;
        }
    }

    echo "===========================================\n";
    echo "Migration Summary:\n";
    echo "  ✓ Successful: {$success}\n";
    echo "  ❌ Failed: {$failed}\n";
    echo "  📁 Total: " . count($files) . "\n";
    echo "===========================================\n";

    exit($failed > 0 ? 1 : 0);
}

// Run single migration
$migrationFile = $migrationsDir . '/' . $migrationName;

if (!file_exists($migrationFile)) {
    echo "❌ Error: Migration file not found: {$migrationName}\n";
    echo "Looking in: {$migrationsDir}\n\n";
    echo "Available migrations:\n";

    $files = glob($migrationsDir . '/*.php');
    sort($files);

    foreach ($files as $file) {
        echo "  - " . basename($file) . "\n";
    }

    exit(1);
}

echo "Running migration: {$migrationName}\n";
echo "===========================================\n\n";

try {
    include $migrationFile;
    echo "\n===========================================\n";
    echo "✓ Migration completed successfully!\n";
    echo "===========================================\n";
    exit(0);
} catch (Exception $e) {
    echo "\n===========================================\n";
    echo "❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "===========================================\n";
    exit(1);
}
