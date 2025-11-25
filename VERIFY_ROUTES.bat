@echo off
REM Route Order Verification Script
REM Checks that all routes are correctly ordered to prevent shadowing

echo.
echo ========================================
echo   ROUTE ORDER VERIFICATION
echo ========================================
echo.

cd backend1

REM Test 1: Check if API loads without errors
echo [TEST 1] Checking API loads without errors...
php -r "try { require 'public/index.php'; echo PHP_EOL . 'PASS: API loaded successfully' . PHP_EOL; } catch (Exception $e) { echo PHP_EOL . 'FAIL: ' . $e->getMessage() . PHP_EOL; exit(1); }" 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo FAIL: API failed to load!
    exit /b 1
)

REM Test 2: Verify route ordering with PowerShell
echo.
echo [TEST 2] Verifying route order for all groups...
echo.

powershell -Command "$content = Get-Content 'src\Routes\api.php'; $groups = @('admin', 'students', 'teachers', 'classes', 'subjects'); $allGood = $true; foreach ($prefix in $groups) { $routes = @(); for ($i = 0; $i -lt $content.Count; $i++) { if ($content[$i] -cmatch \"group-^>(get^|post^|put^|delete^)\('/\" + $prefix + \"/([^']+)'\" -and $content[$i] -notmatch '//') { $route = $matches[2]; $hasVar = $route -match '\{'; $routes += [PSCustomObject]@{Line = $i+1; Route = \"/$prefix/$route\"; HasVar = $hasVar} } }; if ($routes.Count -eq 0) { continue }; $static = $routes ^| Where-Object { -not $_.HasVar }; $variable = $routes ^| Where-Object { $_.HasVar }; if ($static.Count -gt 0 -and $variable.Count -gt 0) { $lastStatic = ($static ^| Measure-Object -Property Line -Maximum).Maximum; $firstVar = ($variable ^| Measure-Object -Property Line -Minimum).Minimum; if ($lastStatic -lt $firstVar) { Write-Host \"PASS: /$prefix/* routes correctly ordered ($($static.Count) static before $($variable.Count) variable)\" -ForegroundColor Green } else { Write-Host \"FAIL: /$prefix/* routes have ordering issues!\" -ForegroundColor Red; $allGood = $false } } }; if ($allGood) { Write-Host \"`nALL TESTS PASSED: Route ordering is correct!\" -ForegroundColor Green; exit 0 } else { Write-Host \"`nTEST FAILED: Some routes have ordering issues\" -ForegroundColor Red; exit 1 }"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo VERIFICATION FAILED!
    echo Please check route ordering in src\Routes\api.php
    echo See ROUTE_ORDERING_GUIDE.md for proper ordering rules
    cd ..
    exit /b 1
)

cd ..

echo.
echo ========================================
echo   ALL ROUTE VERIFICATION PASSED
echo ========================================
echo.
echo Routes are correctly ordered and API loads successfully.
echo No shadowing conflicts detected.
echo.

exit /b 0
