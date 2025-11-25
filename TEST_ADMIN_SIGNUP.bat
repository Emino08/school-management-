@echo off
REM Admin Registration Test Script
REM This script tests the admin signup functionality

echo ===================================================
echo   Admin Sign Up Testing Script
echo ===================================================
echo.

REM Test 1: Test Admin Registration API
echo [TEST 1] Testing Admin Registration API...
echo.

powershell -Command "$testEmail = 'newschool$(Get-Random -Minimum 1000 -Maximum 9999)@test.com'; $body = @{ name = 'School Admin'; schoolName = 'New Test School'; email = $testEmail; password = 'SecurePass123!'; role = 'Admin' } | ConvertTo-Json; Write-Host 'Testing with email:' $testEmail -ForegroundColor Cyan; try { $response = Invoke-RestMethod -Uri 'http://localhost:8080/api/admin/register' -Method POST -Body $body -ContentType 'application/json' -TimeoutSec 30; Write-Host 'SUCCESS: Registration completed!' -ForegroundColor Green; Write-Host 'School Name:' $response.schoolName -ForegroundColor Yellow; Write-Host 'Email:' $response.admin.email -ForegroundColor Yellow; Write-Host 'Token Generated:' ($response.token.Length -gt 0) -ForegroundColor Yellow; Write-Host 'Admin ID:' $response.admin.id -ForegroundColor Yellow; } catch { Write-Host 'FAILED: Registration error' -ForegroundColor Red; Write-Host $_.Exception.Message -ForegroundColor Red; }"

echo.
echo.

REM Test 2: Verify database entry
echo [TEST 2] Verifying Database Entry...
echo.
cd backend1
php test_admin_registration.php
cd ..

echo.
echo.

REM Test 3: Test login with registered account
echo [TEST 3] Testing Login with Duplicate Email (Should Fail)...
echo.

powershell -Command "$body = @{ email = 'testadmin3986@school.com'; password = 'password123' } | ConvertTo-Json; try { $response = Invoke-RestMethod -Uri 'http://localhost:8080/api/admin/login' -Method POST -Body $body -ContentType 'application/json' -TimeoutSec 30; Write-Host 'SUCCESS: Login successful!' -ForegroundColor Green; Write-Host 'School:' $response.schoolName -ForegroundColor Yellow; Write-Host 'Role:' $response.role -ForegroundColor Yellow; } catch { Write-Host 'Login failed (expected if testing duplicate)' -ForegroundColor Yellow; }"

echo.
echo.

REM Test 4: Test duplicate email rejection
echo [TEST 4] Testing Duplicate Email Rejection...
echo.

powershell -Command "$body = @{ name = 'Another Admin'; schoolName = 'Another School'; email = 'testadmin3986@school.com'; password = 'password456'; role = 'Admin' } | ConvertTo-Json; try { $response = Invoke-RestMethod -Uri 'http://localhost:8080/api/admin/register' -Method POST -Body $body -ContentType 'application/json' -TimeoutSec 30; Write-Host 'UNEXPECTED: Duplicate email was accepted' -ForegroundColor Red; } catch { Write-Host 'SUCCESS: Duplicate email was rejected!' -ForegroundColor Green; $errorResponse = $_.ErrorDetails.Message | ConvertFrom-Json; Write-Host 'Error Message:' $errorResponse.message -ForegroundColor Yellow; }"

echo.
echo.

REM Test 5: Test validation
echo [TEST 5] Testing Validation (Missing Fields)...
echo.

powershell -Command "$body = @{ name = 'Test'; email = 'test@test.com' } | ConvertTo-Json; try { $response = Invoke-RestMethod -Uri 'http://localhost:8080/api/admin/register' -Method POST -Body $body -ContentType 'application/json' -TimeoutSec 30; Write-Host 'UNEXPECTED: Validation did not catch missing fields' -ForegroundColor Red; } catch { Write-Host 'SUCCESS: Validation caught missing fields!' -ForegroundColor Green; $errorResponse = $_.ErrorDetails.Message | ConvertFrom-Json; Write-Host 'Error Message:' $errorResponse.message -ForegroundColor Yellow; }"

echo.
echo.
echo ===================================================
echo   All Tests Completed!
echo ===================================================
echo.
echo NOTE: Frontend is running at http://localhost:5175/
echo You can manually test at: http://localhost:5175/Adminregister
echo.
pause
