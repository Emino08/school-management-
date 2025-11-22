@echo off
echo.
echo ===============================================
echo   Testing Fixed Endpoints
echo ===============================================
echo.
echo This script will test if all fixes are working
echo Make sure your backend is running on port 8080
echo.
pause
echo.

echo Testing Activity Logs Stats...
curl -s http://localhost:8080/api/admin/activity-logs/stats
echo.
echo.

echo Testing Notifications Endpoint...
curl -s http://localhost:8080/api/api/notifications
echo.
echo.

echo Testing Teachers Endpoint...
curl -s http://localhost:8080/api/teachers
echo.
echo.

echo.
echo ===============================================
echo   Tests Complete
echo ===============================================
echo.
echo If you see JSON responses (not errors), the fixes are working!
echo.
pause
