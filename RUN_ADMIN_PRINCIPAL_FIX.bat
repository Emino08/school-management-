@echo off
echo ========================================
echo   Admin and Principal Roles Fix
echo ========================================
echo.
echo This script will:
echo 1. Fix principal data inheritance
echo 2. Setup proper role-based access control
echo 3. Ensure admins see correct data
echo.
echo Press any key to continue or Ctrl+C to cancel...
pause > nul

cd backend1

echo.
echo Running migration...
php run_roles_migration.php

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   Migration Completed Successfully!
    echo ========================================
    echo.
    echo Next Steps:
    echo 1. Test login as super admin
    echo 2. Test login as principal
    echo 3. Verify data inheritance works
    echo 4. Check sidebar permissions
    echo.
) else (
    echo.
    echo ========================================
    echo   Migration Failed!
    echo ========================================
    echo.
    echo Please check the error messages above.
    echo.
)

echo Press any key to exit...
pause > nul
