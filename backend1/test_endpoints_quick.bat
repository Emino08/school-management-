@echo off
echo Testing School Management System APIs...
echo.

REM Test health endpoint
echo ========================================
echo Testing: Health Check
echo ========================================
curl -s http://localhost:8080/api/health
echo.
echo.

REM Test activity logs stats (needs a valid token, this will fail but shows if endpoint is reachable)
echo ========================================
echo Testing: Activity Logs Stats
echo ========================================
curl -s http://localhost:8080/api/admin/activity-logs/stats
echo.
echo.

REM Test notifications endpoint
echo ========================================
echo Testing: Notifications (without auth)
echo ========================================
curl -s http://localhost:8080/api/api/notifications
echo.
echo.

echo ========================================
echo Tests completed!
echo ========================================
pause
