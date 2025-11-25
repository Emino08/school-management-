@echo off
echo Adding LogsActivity trait to all controllers...
echo.

cd "C:\Users\SABITECK\OneDrive\Documenti\Projects\SABITECK\School Management System\backend1"

REM Run PowerShell script to add trait to all controllers
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
"$controllers = @( ^
    'AdminController', ^
    'SubjectController', ^
    'AttendanceController', ^
    'FeesPaymentController', ^
    'ResultController', ^
    'NoticeController', ^
    'ComplaintController', ^
    'SettingsController', ^
    'GradeController', ^
    'AcademicYearController', ^
    'NotificationController', ^
    'ParentController', ^
    'TimetableController', ^
    'HouseController', ^
    'MedicalController', ^
    'UserManagementController', ^
    'ReportsController', ^
    'PaymentController', ^
    'PromotionController', ^
    'SuspensionController', ^
    'ExamOfficerController', ^
    'UserRoleController' ^
); ^
$count = 0; ^
foreach ($controller in $controllers) { ^
    $file = \"src\Controllers\$controller.php\"; ^
    if (Test-Path $file) { ^
        $content = Get-Content $file -Raw; ^
        if ($content -notmatch 'use App\\Traits\\LogsActivity;') { ^
            $content = $content -replace '(use App\\Utils\\Validator;)', \"`$1`nuse App\Traits\LogsActivity;\"; ^
            if ($content -notmatch 'use App\\Utils\\Validator;') { ^
                $content = $content -replace '(use App\\Utils\\JWT;)', \"`$1`nuse App\Traits\LogsActivity;\"; ^
            } ^
            if ($content -notmatch 'use App\\Utils\\Validator;' -and $content -notmatch 'use App\\Utils\\JWT;') { ^
                $content = $content -replace '(namespace App\\Controllers;)', \"`$1`n`nuse App\Traits\LogsActivity;\"; ^
            } ^
            $content = $content -replace '(class ' + $controller + '\s*\{)', \"`$1`n    use LogsActivity;`n\"; ^
            Set-Content $file $content; ^
            Write-Host \"  [+] Added to $controller\" -ForegroundColor Green; ^
            $count++; ^
        } else { ^
            Write-Host \"  [=] $controller already has trait\" -ForegroundColor Yellow; ^
        } ^
    } else { ^
        Write-Host \"  [-] $controller not found\" -ForegroundColor Red; ^
    } ^
}; ^
Write-Host \"`n✓ Added LogsActivity to $count controllers\" -ForegroundColor Cyan"

echo.
echo Done!
pause
