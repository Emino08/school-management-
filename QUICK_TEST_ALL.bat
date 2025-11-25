@echo off
echo ========================================
echo COMPREHENSIVE FIXES - QUICK TEST
echo ========================================
echo.

echo [1/3] Running database migration...
cd backend1
php comprehensive_fixes_migration.php
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Migration failed!
    pause
    exit /b 1
)
echo.

echo [2/3] Running system tests...
php test_student_updates.php
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Tests failed!
    pause
    exit /b 1
)
echo.

echo [3/3] Checking routes configuration...
php -r "echo 'Routes file OK: '; echo file_exists('src/Routes/api.php') ? 'YES' : 'NO'; echo PHP_EOL;"
echo.

echo ========================================
echo ALL CHECKS PASSED!
echo ========================================
echo.
echo Next Steps:
echo 1. Start backend: php -S localhost:8080 -t public
echo 2. Start frontend: npm run dev
echo 3. Test admin signup/login
echo 4. Test student create/update
echo 5. Test CSV import/export
echo 6. Check activity logs
echo.
pause
