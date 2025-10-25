@echo off
echo ================================================
echo School Management System - Backend Setup
echo ================================================
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Composer is not installed!
    echo Please install Composer from https://getcomposer.org/
    pause
    exit /b 1
)

REM Install dependencies
echo [1/4] Installing Composer dependencies...
call composer install
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)
echo Dependencies installed successfully!
echo.

REM Check if .env exists
if not exist ".env" (
    echo [2/4] Creating .env file...
    copy .env.example .env
    echo .env file created! Please configure your database settings.
    echo.
) else (
    echo [2/4] .env file already exists
    echo.
)

REM Check if MySQL is running on port 4306
echo [3/4] Checking MySQL connection...
mysql -h localhost -P 4306 -u root -p1212 -e "SELECT 1;" >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo WARNING: Cannot connect to MySQL on port 4306
    echo Please ensure MySQL is running with the correct credentials
    echo Host: localhost
    echo Port: 4306
    echo User: root
    echo Password: 1212
    echo.
) else (
    echo MySQL connection successful!
    echo.

    REM Create database
    echo [4/4] Setting up database...
    mysql -h localhost -P 4306 -u root -p1212 -e "CREATE DATABASE IF NOT EXISTS school_management;" 2>nul
    mysql -h localhost -P 4306 -u root -p1212 school_management < database\schema.sql 2>nul
    if %ERRORLEVEL% EQU 0 (
        echo Database setup completed successfully!
    ) else (
        echo WARNING: Database setup encountered issues
    )
    echo.
)

echo ================================================
echo Setup Complete!
echo ================================================
echo.
echo To start the development server, run:
echo     composer start
echo Or:
echo     php -S localhost:8080 -t public
echo.
echo API will be available at: http://localhost:8080/api
echo.
pause
