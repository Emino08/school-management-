@echo off
echo ================================================
echo   SCHOOL MANAGEMENT SYSTEM - BACKEND SERVER
echo ================================================
echo.
echo Starting PHP Backend Server on localhost:8080...
echo.
echo IMPORTANT: Keep this window open!
echo Press Ctrl+C to stop the server
echo.
echo ================================================
echo.

cd backend1
php -S localhost:8080 -t public

pause
