@echo off
setlocal enabledelayedexpansion

echo ================================================
echo School Management System - API Test Suite
echo ================================================
echo.

REM Check if curl is available
where curl >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: curl is not installed!
    echo Please install curl to run API tests
    pause
    exit /b 1
)

set BASE_URL=http://localhost:8080/api
set TOKEN=

echo Testing API at: %BASE_URL%
echo.

echo [TEST 1] Admin Registration
echo -------------------------
curl -X POST "%BASE_URL%/admin/register" ^
-H "Content-Type: application/json" ^
-d "{\"school_name\":\"Test School\",\"email\":\"test@school.com\",\"password\":\"password123\"}"
echo.
echo.

echo [TEST 2] Admin Login
echo -------------------------
curl -X POST "%BASE_URL%/admin/login" ^
-H "Content-Type: application/json" ^
-d "{\"email\":\"test@school.com\",\"password\":\"password123\"}" > response.json
echo.

REM Extract token (simplified - would need better JSON parsing in production)
echo NOTE: Please copy the token from the response above for further tests
echo.
pause

echo [TEST 3] Get Admin Profile (requires token)
echo -------------------------
echo Enter your token:
set /p TOKEN=
curl -X GET "%BASE_URL%/admin/profile" ^
-H "Authorization: Bearer %TOKEN%"
echo.
echo.

echo [TEST 4] Create Academic Year
echo -------------------------
curl -X POST "%BASE_URL%/academic-years" ^
-H "Authorization: Bearer %TOKEN%" ^
-H "Content-Type: application/json" ^
-d "{\"year_name\":\"2024-2025\",\"start_date\":\"2024-01-01\",\"end_date\":\"2024-12-31\",\"is_current\":true}"
echo.
echo.

echo [TEST 5] Create Class
echo -------------------------
curl -X POST "%BASE_URL%/classes" ^
-H "Authorization: Bearer %TOKEN%" ^
-H "Content-Type: application/json" ^
-d "{\"class_name\":\"Grade 1\",\"grade_level\":1,\"section\":\"A\"}"
echo.
echo.

echo [TEST 6] Get All Classes
echo -------------------------
curl -X GET "%BASE_URL%/classes" ^
-H "Authorization: Bearer %TOKEN%"
echo.
echo.

echo ================================================
echo API Tests Completed!
echo ================================================
echo.
echo For more detailed testing, use Postman or similar tools
echo API Documentation: See README.md
echo.
pause
