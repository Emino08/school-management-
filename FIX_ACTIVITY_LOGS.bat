@echo off
echo =====================================
echo Fixing Activity Logs Table Structure
echo =====================================
echo.

cd backend1
php fix_activity_logs_table.php

echo.
echo =====================================
echo Press any key to exit...
pause >nul
