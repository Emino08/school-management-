@echo off
echo ========================================
echo School Management System - Quick Start
echo ========================================
echo.

REM Check if database is running
echo [1/4] Checking database connection...
netstat -ano | findstr ":4306" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Database is not running on port 4306
    echo Please start your MySQL/MariaDB server first
    pause
    exit /b 1
)
echo [OK] Database is running
echo.

REM Run migration
echo [2/4] Running database migrations...
cd backend1
php migrations\comprehensive_fix_nov_21_2025.php
if errorlevel 1 (
    echo [ERROR] Migration failed
    pause
    exit /b 1
)
echo [OK] Migrations completed
echo.

REM Start backend
echo [3/4] Starting backend server...
start "Backend Server" cmd /k "cd /d %CD% && php -S localhost:8080 -t public"
timeout /t 3 >nul
echo [OK] Backend server started on http://localhost:8080
echo.

REM Check frontend
echo [4/4] Checking frontend...
cd ..\frontend1
if exist "node_modules" (
    echo [OK] Frontend dependencies installed
    echo.
    echo ========================================
    echo SYSTEM READY!
    echo ========================================
    echo.
    echo Backend:  http://localhost:8080
    echo Frontend: Start with 'npm run dev' in frontend1 folder
    echo Database: localhost:4306
    echo.
    echo To start frontend, open a new terminal and run:
    echo   cd frontend1
    echo   npm run dev
    echo.
) else (
    echo [WARNING] Frontend dependencies not installed
    echo Please run 'npm install' in the frontend1 folder
    echo.
)

echo Press any key to view API health check...
pause >nul

echo.
echo Testing API...
curl -s http://localhost:8080/api/health
echo.
echo.

pause
