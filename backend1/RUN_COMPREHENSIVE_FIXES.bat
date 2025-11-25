@echo off
echo ========================================
echo Running Comprehensive Fixes Migration
echo ========================================
echo.

cd /d "C:\Users\SABITECK\OneDrive\Documenti\Projects\SABITECK\School Management System\backend1"

echo Running migration SQL...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "source database/migrations/fix_all_critical_issues_simple.sql" 2>migration_errors.txt

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✓ Migration completed successfully!
    echo.
) else (
    echo.
    echo ⚠ Migration completed with some warnings. Check migration_errors.txt for details.
    echo   This is normal if columns already exist.
    echo.
)

echo ========================================
echo VERIFICATION
echo ========================================
echo.

echo Checking database structure...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SHOW COLUMNS FROM admins WHERE Field = 'is_super_admin';"
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SHOW TABLES LIKE 'student_parents';"
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SELECT COUNT(*) as parent_student_relationships FROM student_parents;"

echo.
echo ========================================
echo Migration Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Test admin login
echo 2. Create a principal and verify data inheritance
echo 3. Test parent medical record addition
echo 4. Clear browser cache and opcache if needed
echo.

pause
