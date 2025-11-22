@echo off
echo ================================================
echo School Management System - API Test Suite
echo ================================================
echo.

echo [1/5] Testing Health Endpoint...
curl -s -X GET http://localhost:8080/api/health
echo.
echo.

echo [2/5] Testing Login (Replace with your admin credentials)...
echo NOTE: Update email and password in this script
curl -s -X POST http://localhost:8080/api/admin/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@school.com\",\"password\":\"password123\"}"
echo.
echo.

echo [3/5] Testing Settings Endpoint (needs valid token)...
echo NOTE: Replace YOUR_TOKEN_HERE with actual token from login
curl -s -X GET http://localhost:8080/api/admin/settings ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
echo.
echo.

echo [4/5] Testing Notifications Endpoint...
curl -s -X GET http://localhost:8080/api/notifications ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
echo.
echo.

echo [5/5] Testing Financial Reports...
curl -s -X GET "http://localhost:8080/api/admin/reports/financial?academic_year_id=1" ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
echo.
echo.

echo ================================================
echo Test Complete!
echo ================================================
pause
