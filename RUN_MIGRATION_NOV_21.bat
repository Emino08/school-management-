@echo off
echo ================================================
echo School Management System - Database Migration
echo November 21, 2025 Update
echo ================================================
echo.

set MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe
set DB_USER=root
set DB_PASS=1212
set DB_NAME=school_management
set MIGRATION_FILE=database updated files\comprehensive_update_2025.sql

echo Checking if MySQL is accessible...
"%MYSQL_PATH%" --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: MySQL not found at %MYSQL_PATH%
    echo Please update MYSQL_PATH in this script to match your installation
    echo Common paths:
    echo   - C:\xampp\mysql\bin\mysql.exe
    echo   - C:\wamp64\bin\mysql\mysql8.x.x\bin\mysql.exe
    echo   - C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe
    pause
    exit /b 1
)

echo MySQL found!
echo.
echo Database: %DB_NAME%
echo Migration file: %MIGRATION_FILE%
echo.
echo WARNING: This will modify your database structure.
echo Make sure you have a backup if needed.
echo.
pause

echo.
echo Running migration...
echo.

"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% %DB_NAME% < "%MIGRATION_FILE%"

if errorlevel 1 (
    echo.
    echo ================================================
    echo ERROR: Migration failed!
    echo ================================================
    echo Please check:
    echo 1. Database credentials are correct
    echo 2. Database '%DB_NAME%' exists
    echo 3. MySQL server is running
    echo.
    pause
    exit /b 1
)

echo.
echo ================================================
echo ✓ Migration completed successfully!
echo ================================================
echo.
echo Next steps:
echo 1. Restart your backend server
echo 2. Clear browser cache and localStorage
echo 3. Login again to test
echo.
echo Changes made:
echo - Added first_name and last_name to students
echo - Added first_name and last_name to teachers
echo - Updated notifications system
echo - Added currency support (SLE)
echo - Created password resets table
echo - Added proper indexes
echo.
pause
