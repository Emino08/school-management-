@echo off
echo ================================================
echo   BACKEND SERVER STARTUP
echo ================================================
echo.
echo Starting PHP development server on port 8080...
echo.
cd /d "%~dp0backend1\public"
php -S localhost:8080
