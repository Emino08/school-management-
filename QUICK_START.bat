@echo off
cls
echo.
echo ========================================
echo  SCHOOL MANAGEMENT SYSTEM
echo  Quick Start Script
echo ========================================
echo.
echo This script will help you start the system quickly.
echo.
echo Prerequisites:
echo  - MySQL/MariaDB running on port 4306
echo  - PHP installed
echo  - Node.js installed
echo.
pause
echo.

:menu
cls
echo.
echo ========================================
echo  SELECT AN OPTION
echo ========================================
echo.
echo  1. Run All Fixes (Database Migration + Cache Clear)
echo  2. Start Backend Only (Port 8080)
echo  3. Start Frontend Only (Port 5174)
echo  4. Start Both Backend and Frontend
echo  5. Check System Status
echo  6. Clear All Caches
echo  7. Exit
echo.
set /p choice="Enter your choice (1-7): "

if "%choice%"=="1" goto run_fixes
if "%choice%"=="2" goto start_backend
if "%choice%"=="3" goto start_frontend
if "%choice%"=="4" goto start_both
if "%choice%"=="5" goto check_status
if "%choice%"=="6" goto clear_cache
if "%choice%"=="7" goto end

echo Invalid choice. Please try again.
timeout /t 2 >nul
goto menu

:run_fixes
echo.
echo Running all fixes...
echo.
call RUN_ALL_FIXES.bat
echo.
echo Fixes completed!
echo.
pause
goto menu

:start_backend
echo.
echo Starting Backend Server on http://localhost:8080...
echo.
cd backend1
start "Backend Server" cmd /k "php -S localhost:8080 -t public"
echo.
echo Backend server started!
echo Check the new window for server output.
echo.
timeout /t 3
goto menu

:start_frontend
echo.
echo Starting Frontend on http://localhost:5174...
echo.
cd frontend1
start "Frontend Server" cmd /k "npm run dev"
echo.
echo Frontend server starting...
echo Check the new window for server output.
echo.
timeout /t 3
goto menu

:start_both
echo.
echo Starting both Backend and Frontend...
echo.
cd backend1
start "Backend Server" cmd /k "php -S localhost:8080 -t public"
cd..
timeout /t 2
cd frontend1
start "Frontend Server" cmd /k "npm run dev"
cd..
echo.
echo Both servers are starting!
echo Backend: http://localhost:8080
echo Frontend: http://localhost:5174
echo.
echo Check the new windows for server output.
echo.
pause
goto menu

:check_status
echo.
echo Checking System Status...
echo.
cd backend1
echo [1/3] Checking Database Connection...
php -r "try { $db = new PDO('mysql:host=localhost:4306;dbname=school_management', 'root', '1212'); echo 'Database: OK\n'; } catch(Exception $e) { echo 'Database: FAILED - ' . $e->getMessage() . '\n'; }"
echo.
echo [2/3] Checking Critical Tables...
php check_system_tables.php
echo.
echo [3/3] Checking Backend...
curl -s http://localhost:8080/ >nul 2>&1
if %errorlevel% == 0 (
    echo Backend Server: RUNNING on http://localhost:8080
) else (
    echo Backend Server: NOT RUNNING
)
echo.
curl -s http://localhost:5174/ >nul 2>&1
if %errorlevel% == 0 (
    echo Frontend Server: RUNNING on http://localhost:5174
) else (
    echo Frontend Server: NOT RUNNING
)
cd..
echo.
pause
goto menu

:clear_cache
echo.
echo Clearing all caches...
echo.
if exist "backend1\cache\*.*" (
    del /q "backend1\cache\*.*"
    echo Backend cache cleared!
) else (
    echo No backend cache found.
)
echo.
if exist "frontend1\.vite\*.*" (
    rmdir /s /q "frontend1\.vite"
    echo Frontend Vite cache cleared!
) else (
    echo No frontend cache found.
)
echo.
if exist "frontend1\node_modules\.cache\*.*" (
    del /q /s "frontend1\node_modules\.cache\*.*"
    echo Frontend node cache cleared!
)
echo.
echo All caches cleared!
echo.
pause
goto menu

:end
echo.
echo Thank you for using the School Management System!
echo.
exit
