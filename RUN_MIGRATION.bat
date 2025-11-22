@echo off
echo ====================================
echo SCHOOL MANAGEMENT SYSTEM
echo Database Migration Runner
echo ====================================
echo.

echo WARNING: This will modify your database structure.
echo Make sure you have a backup before proceeding!
echo.
pause

echo.
echo Running migration on local database (school_management)...
echo.

mysql -h localhost -P 4306 -u root -p1212 school_management < "database updated files\complete_system_migration.sql"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ====================================
    echo Migration completed successfully!
    echo ====================================
    echo.
    echo Next steps:
    echo 1. Restart your backend server
    echo 2. Test the token authentication
    echo 3. Update frontend components
    echo.
) else (
    echo.
    echo ====================================
    echo Migration failed!
    echo ====================================
    echo Please check the error messages above.
    echo.
)

pause
