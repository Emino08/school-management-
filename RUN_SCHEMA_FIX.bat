@echo off
echo ============================================
echo Running Schema Fixes Migration
echo ============================================
echo.

cd /d "%~dp0backend1"

echo [1/2] Running database fix migration...
php run_fix_migration.php
if errorlevel 1 (
    echo ERROR: Migration failed!
    pause
    exit /b 1
)

echo.
echo [2/2] Creating system_settings table...
php -r "require 'vendor/autoload.php'; $env = Dotenv\Dotenv::createImmutable(__DIR__); $env->load(); $pdo = new PDO('mysql:host='.$_ENV['DB_HOST'].';port='.$_ENV['DB_PORT'].';dbname='.$_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']); $sql = file_get_contents('database/migrations/create_system_settings.sql'); try { $pdo->exec($sql); echo 'System settings table created successfully!'; } catch (Exception $e) { echo 'Error: ' . $e->getMessage(); }"

echo.
echo ============================================
echo Migration Complete!
echo ============================================
echo.
echo You can now:
echo 1. Restart the backend server
echo 2. Test the activity logs endpoint
echo 3. Test the notifications endpoint
echo.
pause
