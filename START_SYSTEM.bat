@echo off
title School Management System - Startup
color 0A

echo.
echo ========================================
echo   SCHOOL MANAGEMENT SYSTEM
echo   Complete System Startup
echo ========================================
echo.

:menu
echo Please select an option:
echo.
echo 1. Run Database Migrations (First Time Setup)
echo 2. Start Backend Server
echo 3. Start Frontend Server
echo 4. Start Both Servers
echo 5. Test API Endpoints
echo 6. View Documentation
echo 7. Exit
echo.
set /p choice="Enter your choice (1-7): "

if "%choice%"=="1" goto migrations
if "%choice%"=="2" goto backend
if "%choice%"=="3" goto frontend
if "%choice%"=="4" goto both
if "%choice%"=="5" goto test
if "%choice%"=="6" goto docs
if "%choice%"=="7" goto exit

echo Invalid choice. Please try again.
pause
cls
goto menu

:migrations
cls
echo ========================================
echo Running Database Migrations...
echo ========================================
echo.
cd /d "%~dp0backend1"
echo [1/2] Running schema fixes...
php run_fix_migration.php
echo.
echo [2/2] Creating system settings...
php create_settings_table.php
echo.
echo ========================================
echo Migrations Complete!
echo ========================================
echo.
echo Next steps:
echo - Start the backend server (option 2)
echo - Start the frontend server (option 3)
echo.
pause
cls
goto menu

:backend
cls
echo ========================================
echo Starting Backend Server...
echo ========================================
echo.
echo Backend will run on: http://localhost:8080
echo Press Ctrl+C to stop the server
echo.
cd /d "%~dp0backend1"
php -S localhost:8080 -t public
pause
goto menu

:frontend
cls
echo ========================================
echo Starting Frontend Server...
echo ========================================
echo.
echo Frontend will run on: http://localhost:5174
echo Press Ctrl+C to stop the server
echo.
cd /d "%~dp0frontend1"
npm run dev
pause
goto menu

:both
cls
echo ========================================
echo Starting Both Servers...
echo ========================================
echo.
echo Starting Backend on http://localhost:8080...
cd /d "%~dp0backend1"
start "Backend Server" cmd /k "php -S localhost:8080 -t public"
echo.
echo Starting Frontend on http://localhost:5174...
cd /d "%~dp0frontend1"
start "Frontend Server" cmd /k "npm run dev"
echo.
echo ========================================
echo Both servers are starting...
echo ========================================
echo.
echo Backend: http://localhost:8080
echo Frontend: http://localhost:5174
echo.
echo Close the server windows to stop them.
echo.
pause
cls
goto menu

:test
cls
echo ========================================
echo Testing API Endpoints...
echo ========================================
echo.
echo Testing Backend Health...
curl -s http://localhost:8080/api/health
echo.
echo.
echo ========================================
echo Test Complete!
echo ========================================
echo.
echo For authenticated endpoints, you need to:
echo 1. Login to get a token
echo 2. Use: curl -H "Authorization: Bearer TOKEN" URL
echo.
echo See QUICK_TEST_ENDPOINTS.bat for more examples
echo.
pause
cls
goto menu

:docs
cls
echo ========================================
echo Documentation Files
echo ========================================
echo.
echo Quick Start:
echo   - ACTION_REQUIRED_NOW.md (Overview)
echo   - FRONTEND_EXAMPLE_CODE.jsx (Code examples)
echo.
echo Implementation Guides:
echo   - COMPLETE_IMPLEMENTATION_GUIDE.md (Full guide)
echo   - START_HERE_FIXES_NOV_21.md (Detailed fixes)
echo.
echo Technical Details:
echo   - COMPLETE_FIX_SUMMARY_NOV_21.md (All changes)
echo.
echo Testing:
echo   - QUICK_TEST_ENDPOINTS.bat (API testing)
echo   - RUN_SCHEMA_FIX.bat (Migrations)
echo.
pause
cls
goto menu

:exit
cls
echo.
echo ========================================
echo Thank you for using School Management System!
echo ========================================
echo.
echo Remember:
echo - Backend runs on http://localhost:8080
echo - Frontend runs on http://localhost:5174
echo - All documentation is in the project root
echo.
echo Have a great day!
echo.
timeout /t 3
exit

