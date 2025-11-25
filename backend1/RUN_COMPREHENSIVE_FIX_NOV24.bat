@echo off
echo ================================================================
echo     COMPREHENSIVE FIX - November 24, 2025
echo ================================================================
echo.
echo This will fix:
echo 1. Database schema issues (students, parents, medical_records)
echo 2. Admin/Principal role separation
echo 3. Super admin setup
echo.
pause

cd /d "%~dp0"

echo.
echo Step 1: Running database schema fixes...
echo ================================================================
php comprehensive_fix_nov24_final.php
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Database schema fix failed!
    pause
    exit /b 1
)

echo.
echo Step 2: Running admin/principal role fixes...
echo ================================================================
php fix_admin_principal_roles_nov24.php
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Admin/Principal role fix failed!
    pause
    exit /b 1
)

echo.
echo ================================================================
echo     ALL FIXES COMPLETED SUCCESSFULLY!
echo ================================================================
echo.
echo Next steps:
echo 1. Test admin login at the admin portal
echo 2. Test principal login at the principal portal  
echo 3. Verify that principals see the same data as their parent admin
echo 4. Test parent medical records functionality
echo.
pause
