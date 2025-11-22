@echo off
echo ========================================
echo QUICK TEST - School Management System
echo ========================================
echo.

set BACKEND_URL=http://localhost:8080

echo [1] Testing Health Endpoint...
curl -s %BACKEND_URL%/api/health
echo.
echo.

echo [2] Testing Activity Logs Stats (requires auth)...
echo Note: You need to add Authorization header with valid token
echo Example: curl -H "Authorization: Bearer YOUR_TOKEN" %BACKEND_URL%/api/admin/activity-logs/stats
echo.

echo [3] Testing Notifications Endpoint (requires auth)...
echo GET: curl -H "Authorization: Bearer YOUR_TOKEN" %BACKEND_URL%/api/api/notifications
echo POST: curl -X POST -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -d "{\"title\":\"Test\"}" %BACKEND_URL%/api/api/notifications
echo.

echo [4] Available Town Master Endpoints:
echo - GET %BACKEND_URL%/api/admin/towns
echo - GET %BACKEND_URL%/api/admin/towns/{id}/blocks
echo - POST %BACKEND_URL%/api/town-master/register-student
echo - POST %BACKEND_URL%/api/town-master/attendance
echo.

echo [5] Available Teacher Endpoints:
echo - GET %BACKEND_URL%/api/teachers/{id}/classes
echo - GET %BACKEND_URL%/api/teachers/{id}/subjects
echo - GET %BACKEND_URL%/api/teachers (list all)
echo.

echo ========================================
echo.
echo To test authenticated endpoints:
echo 1. Login first to get token
echo 2. Copy the token from response
echo 3. Use: curl -H "Authorization: Bearer TOKEN" URL
echo.
pause
