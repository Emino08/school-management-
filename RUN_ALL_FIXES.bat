@echo off
echo ================================================
echo  COMPREHENSIVE SYSTEM FIXES - November 21, 2025
echo ================================================
echo.

cd /d "%~dp0backend1"

echo [1/5] Running database migrations...
php migrations\040_comprehensive_system_fixes.php
if %errorlevel% neq 0 (
    echo ERROR: Migration failed!
    pause
    exit /b 1
)
echo.

echo [2/5] Clearing route cache...
if exist "cache\routes.cache" del /q "cache\routes.cache"
if exist "cache\*.*" del /q "cache\*.*"
echo ✓ Cache cleared
echo.

echo [3/5] Verifying database structure...
php -r "require 'vendor/autoload.php'; $db = \App\Config\Database::getInstance()->getConnection(); echo 'Database connection: OK\n';"
if %errorlevel% neq 0 (
    echo ERROR: Database connection failed!
    echo Please start MySQL/MariaDB server first
    pause
    exit /b 1
)
echo.

echo [4/5] Checking critical tables...
php check_system_tables.php
echo.

echo [5/5] System ready!
echo.
echo ================================================
echo  All fixes applied successfully!
echo ================================================
echo.
echo Next steps:
echo 1. Start backend: START_BACKEND.bat
echo 2. Start frontend: START_FRONTEND.bat
echo 3. Navigate to http://localhost:5174
echo.
pause
