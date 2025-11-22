@echo off
echo ================================================
echo School Management System - Complete Migration
echo ================================================
echo.
echo This script will:
echo 1. Fix activity_logs table (action -> activity_type)
echo 2. Add currency columns to system_settings
echo 3. Split teacher and student names
echo 4. Create town master system tables
echo 5. Create user roles tables
echo 6. Enhance notification system
echo 7. Add parent-student binding tables
echo.
echo ================================================
echo.

REM Check if MySQL is running
echo Checking if MySQL is running...
netstat -an | find "3306" >nul
if errorlevel 1 (
    echo [ERROR] MySQL server is not running!
    echo Please start MySQL (XAMPP, WAMP, or standalone) and try again.
    pause
    exit /b 1
)
echo [OK] MySQL server is running
echo.

REM Get database credentials
set /p DB_HOST="Enter MySQL host (default: localhost): "
if "%DB_HOST%"=="" set DB_HOST=localhost

set /p DB_NAME="Enter database name (default: school_management): "
if "%DB_NAME%"=="" set DB_NAME=school_management

set /p DB_USER="Enter MySQL username (default: root): "
if "%DB_USER%"=="" set DB_USER=root

set /p DB_PASS="Enter MySQL password (press Enter if none): "

echo.
echo ================================================
echo Running migration...
echo ================================================
echo.

REM Run the migration
if "%DB_PASS%"=="" (
    mysql -h %DB_HOST% -u %DB_USER% %DB_NAME% < "database\migrations\complete_town_system.sql"
) else (
    mysql -h %DB_HOST% -u %DB_USER% -p%DB_PASS% %DB_NAME% < "database\migrations\complete_town_system.sql"
)

if errorlevel 1 (
    echo.
    echo [ERROR] Migration failed!
    echo Please check the error messages above.
    echo.
    pause
    exit /b 1
)

echo.
echo ================================================
echo Migration completed successfully!
echo ================================================
echo.
echo Next steps:
echo 1. Restart your backend server
echo 2. Test the activity logs page
echo 3. Test the notifications API
echo 4. Check system settings
echo.
echo New features available:
echo - Town Master Management
echo - User Roles Management
echo - Enhanced Notifications
echo - Parent-Student Binding
echo.
pause
