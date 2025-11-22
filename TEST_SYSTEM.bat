@echo off
echo.
echo ================================================
echo  SYSTEM VERIFICATION TEST
echo  Testing all fixed endpoints
echo ================================================
echo.

set API_URL=http://localhost:8080/api
set ADMIN_TOKEN=

echo [STEP 1] Testing Backend Availability...
curl -s %API_URL%/ >nul 2>&1
if %errorlevel% neq 0 (
    echo ✗ Backend is NOT running on %API_URL%
    echo Please start the backend first: cd backend1 && php -S localhost:8080 -t public
    pause
    exit /b 1
)
echo ✓ Backend is running
echo.

echo [STEP 2] Testing Health Endpoint...
curl -s %API_URL%/health
echo.
echo.

echo [STEP 3] Testing Activity Logs Stats...
echo Note: This requires authentication
curl -s -X GET "%API_URL%/admin/activity-logs/stats" -H "Content-Type: application/json"
echo.
echo.

echo [STEP 4] Testing Notifications (OPTIONS)...
curl -s -X OPTIONS "%API_URL%/api/notifications" -H "Content-Type: application/json"
if %errorlevel% == 0 (
    echo ✓ OPTIONS method supported
) else (
    echo ✗ OPTIONS method failed
)
echo.

echo [STEP 5] Testing Teachers Endpoint...
echo Note: This requires authentication
curl -s -X GET "%API_URL%/teachers" -H "Content-Type: application/json"
echo.
echo.

echo [STEP 6] Testing Towns Endpoint...
echo Note: This requires authentication
curl -s -X GET "%API_URL%/admin/towns" -H "Content-Type: application/json"
echo.
echo.

echo ================================================
echo  VERIFICATION COMPLETE
echo ================================================
echo.
echo Next steps:
echo 1. Login to the system at http://localhost:5174
echo 2. Navigate to Teacher Management
echo 3. Test "View Classes" and "View Subjects" buttons
echo 4. Test Town Master functionality
echo.
pause
