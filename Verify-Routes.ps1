# Route Order Verification Script
# Checks that all routes are correctly ordered to prevent shadowing

Write-Host "`n========================================"
Write-Host "  ROUTE ORDER VERIFICATION" -ForegroundColor Cyan
Write-Host "========================================`n"

# Test 1: Check if API loads without errors
Write-Host "[TEST 1] Checking API loads without errors..." -ForegroundColor Yellow

Push-Location backend1
try {
    $output = php -r "try { require 'public/index.php'; echo PHP_EOL . 'PASS' . PHP_EOL; } catch (Exception `$e) { echo PHP_EOL . 'FAIL: ' . `$e->getMessage() . PHP_EOL; exit(1); }" 2>&1
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "FAIL: API failed to load!" -ForegroundColor Red
        Pop-Location
        exit 1
    }
    Write-Host "PASS: API loaded successfully" -ForegroundColor Green
} catch {
    Write-Host "FAIL: $($_.Exception.Message)" -ForegroundColor Red
    Pop-Location
    exit 1
}

# Test 2: Verify route ordering
Write-Host "`n[TEST 2] Verifying route order for all groups...`n" -ForegroundColor Yellow

$content = Get-Content 'src\Routes\api.php'
$groups = @('admin', 'students', 'teachers', 'classes', 'subjects')
$allGood = $true

foreach ($prefix in $groups) {
    $routes = @()
    
    for ($i = 0; $i -lt $content.Count; $i++) {
        if ($content[$i] -cmatch "group->(get|post|put|delete)\('/$prefix/([^']+)'" -and $content[$i] -notmatch '//') {
            $route = $matches[2]
            $hasVar = $route -match '\{'
            $routes += [PSCustomObject]@{
                Line = $i+1
                Route = "/$prefix/$route"
                HasVar = $hasVar
            }
        }
    }
    
    if ($routes.Count -eq 0) {
        continue
    }
    
    $static = $routes | Where-Object { -not $_.HasVar }
    $variable = $routes | Where-Object { $_.HasVar }
    
    if ($static.Count -gt 0 -and $variable.Count -gt 0) {
        $lastStatic = ($static | Measure-Object -Property Line -Maximum).Maximum
        $firstVar = ($variable | Measure-Object -Property Line -Minimum).Minimum
        
        if ($lastStatic -lt $firstVar) {
            Write-Host "PASS: /$prefix/* routes correctly ordered ($($static.Count) static before $($variable.Count) variable)" -ForegroundColor Green
        } else {
            Write-Host "FAIL: /$prefix/* routes have ordering issues!" -ForegroundColor Red
            $allGood = $false
        }
    } elseif ($static.Count -gt 0) {
        Write-Host "INFO: /$prefix/* has only static routes ($($static.Count) routes)" -ForegroundColor Cyan
    } elseif ($variable.Count -gt 0) {
        Write-Host "INFO: /$prefix/* has only variable routes ($($variable.Count) routes)" -ForegroundColor Cyan
    }
}

Pop-Location

if ($allGood) {
    Write-Host "`n========================================"
    Write-Host "  ALL TESTS PASSED" -ForegroundColor Green
    Write-Host "========================================`n"
    Write-Host "Routes are correctly ordered and API loads successfully." -ForegroundColor Green
    Write-Host "No shadowing conflicts detected.`n" -ForegroundColor Green
    exit 0
} else {
    Write-Host "`n========================================"
    Write-Host "  TEST FAILED" -ForegroundColor Red
    Write-Host "========================================`n"
    Write-Host "Some routes have ordering issues." -ForegroundColor Red
    Write-Host "Please check route ordering in backend1\src\Routes\api.php" -ForegroundColor Yellow
    Write-Host "See ROUTE_ORDERING_GUIDE.md for proper ordering rules.`n" -ForegroundColor Yellow
    exit 1
}
