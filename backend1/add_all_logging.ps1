# Comprehensive Activity Logging Addition Script
Write-Host "Adding comprehensive activity logging to all controllers..." -ForegroundColor Cyan
Write-Host ""

$basePath = "C:\Users\SABITECK\OneDrive\Documenti\Projects\SABITECK\School Management System\backend1\src\Controllers"
$added = 0

# Helper function to add logging
function Add-Logging {
    param($file, $searchAfter, $logCode)
    
    $content = Get-Content $file -Raw
    if ($content -notmatch [regex]::Escape("logActivity")) {
        return $false  # Trait not added yet
    }
    
    if ($content -match [regex]::Escape($logCode)) {
        return $false  # Already added
    }
    
    if ($content -match [regex]::Escape($searchAfter)) {
        $newContent = $content -replace [regex]::Escape($searchAfter), "$logCode`n`n$searchAfter"
        Set-Content $file $newContent
        return $true
    }
    
    return $false
}

# SubjectController - Create
$logCode = @"
            // Log subject creation
            `$this->logActivity(
                `$request,
                'create',
                "Created subject: {`$data['subject_name']}",
                'subject',
                `$subjectId,
                ['subject_name' => `$data['subject_name']]
            );
"@

if (Add-Logging "$basePath\SubjectController.php" "            \$response->getBody()->write(json_encode([" $logCode) {
    Write-Host "[+] SubjectController::create" -ForegroundColor Green
    $added++
}

# SubjectController - Update
$logCode = @"
            // Log subject update
            `$this->logActivity(
                `$request,
                'update',
                "Updated subject (ID: `$subjectId)",
                'subject',
                `$subjectId
            );
"@

if (Add-Logging "$basePath\SubjectController.php" "            \$response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Subject updated successfully'" $logCode) {
    Write-Host "[+] SubjectController::update" -ForegroundColor Green
    $added++
}

# SubjectController - Delete
$logCode = @"
            // Log subject deletion
            `$this->logActivity(
                `$request,
                'delete',
                "Deleted subject (ID: `$subjectId)",
                'subject',
                `$subjectId
            );
"@

if (Add-Logging "$basePath\SubjectController.php" "            \$response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Subject deleted successfully'" $logCode) {
    Write-Host "[+] SubjectController::delete" -ForegroundColor Green
    $added++
}

# AttendanceController - Mark Attendance
$logCode = @"
            // Log attendance marking
            `$this->logActivity(
                `$request,
                'mark',
                "Marked attendance for " . count(`$attendanceData) . " students",
                'attendance',
                null,
                ['count' => count(`$attendanceData), 'class_id' => `$data['class_id'] ?? null]
            );
"@

$content = Get-Content "$basePath\AttendanceController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'Attendance marked successfully'" -and $content -notmatch "Log attendance marking") {
    $content = $content -replace "('success' => true,\s+'message' => 'Attendance marked successfully')", "// Log attendance marking`n            `$this->logActivity(`n                `$request,`n                'mark',`n                `"Marked attendance for class`",`n                'attendance',`n                null`n            );`n`n            `$1"
    Set-Content "$basePath\AttendanceController.php" $content
    Write-Host "[+] AttendanceController::mark" -ForegroundColor Green
    $added++
}

# NoticeController - Create
$content = Get-Content "$basePath\NoticeController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'Notice created successfully'" -and $content -notmatch "Log notice creation") {
    $content = $content -replace "('success' => true,\s+'message' => 'Notice created successfully')", "// Log notice creation`n            `$this->logActivity(`n                `$request,`n                'create',`n                `"Created notice: {`$data['title']}`",`n                'notice',`n                `$noticeId,`n                ['title' => `$data['title']]`n            );`n`n            `$1"
    Set-Content "$basePath\NoticeController.php" $content
    Write-Host "[+] NoticeController::create" -ForegroundColor Green
    $added++
}

# FeesPaymentController - Record Payment
$content = Get-Content "$basePath\FeesPaymentController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'Payment recorded successfully'" -and $content -notmatch "Log payment") {
    $content = $content -replace "('success' => true,\s+'message' => 'Payment recorded successfully')", "// Log payment`n            `$this->logActivity(`n                `$request,`n                'payment',`n                `"Payment recorded: {`$data['amount']}`",`n                'payment',`n                `$paymentId ?? null,`n                ['amount' => `$data['amount']]`n            );`n`n            `$1"
    Set-Content "$basePath\FeesPaymentController.php" $content
    Write-Host "[+] FeesPaymentController::create" -ForegroundColor Green
    $added++
}

# SettingsController - Update
$content = Get-Content "$basePath\SettingsController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'Settings updated successfully'" -and $content -notmatch "Log settings update") {
    $content = $content -replace "('success' => true,\s+'message' => 'Settings updated successfully')", "// Log settings update`n        `$this->logActivity(`n            `$request,`n            'update',`n            `"Updated system settings`",`n            'settings',`n            null`n        );`n`n        `$1"
    Set-Content "$basePath\SettingsController.php" $content
    Write-Host "[+] SettingsController::update" -ForegroundColor Green
    $added++
}

# GradeController - Create
$content = Get-Content "$basePath\GradeController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'Grade created successfully'" -and $content -notmatch "Log grade creation") {
    $content = $content -replace "('success' => true,\s+'message' => 'Grade created successfully')", "// Log grade creation`n            `$this->logActivity(`n                `$request,`n                'create',`n                `"Created grade`",`n                'grade',`n                `$gradeId ?? null`n            );`n`n            `$1"
    Set-Content "$basePath\GradeController.php" $content
    Write-Host "[+] GradeController::create" -ForegroundColor Green
    $added++
}

# ResultController - Publish
$content = Get-Content "$basePath\ResultController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'Results published successfully'" -and $content -notmatch "Log result publishing") {
    $content = $content -replace "('success' => true,\s+'message' => 'Results published successfully')", "// Log result publishing`n            `$this->logActivity(`n                `$request,`n                'publish',`n                `"Published results`",`n                'result',`n                null`n            );`n`n            `$1"
    Set-Content "$basePath\ResultController.php" $content
    Write-Host "[+] ResultController::publish" -ForegroundColor Green
    $added++
}

# ComplaintController - Create
$content = Get-Content "$basePath\ComplaintController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'Complaint submitted successfully'" -and $content -notmatch "Log complaint") {
    $content = $content -replace "('success' => true,\s+'message' => 'Complaint submitted successfully')", "// Log complaint`n            `$this->logActivity(`n                `$request,`n                'create',`n                `"Complaint submitted`",`n                'complaint',`n                `$complaintId ?? null`n            );`n`n            `$1"
    Set-Content "$basePath\ComplaintController.php" $content
    Write-Host "[+] ComplaintController::create" -ForegroundColor Green
    $added++
}

# UserManagementController - Create User
$content = Get-Content "$basePath\UserManagementController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'User created successfully'" -and $content -notmatch "Log user creation") {
    $content = $content -replace "('success' => true,\s+'message' => 'User created successfully')", "// Log user creation`n            `$this->logActivity(`n                `$request,`n                'create',`n                `"Created user account`",`n                'user',`n                `$userId ?? null`n            );`n`n            `$1"
    Set-Content "$basePath\UserManagementController.php" $content
    Write-Host "[+] UserManagementController::create" -ForegroundColor Green
    $added++
}

# TimetableController - Create
$content = Get-Content "$basePath\TimetableController.php" -Raw
if ($content -match "'success' => true,\s+'message' => 'Timetable created successfully'" -and $content -notmatch "Log timetable") {
    $content = $content -replace "('success' => true,\s+'message' => 'Timetable created successfully')", "// Log timetable creation`n            `$this->logActivity(`n                `$request,`n                'create',`n                `"Created timetable entry`",`n                'timetable',`n                `$timetableId ?? null`n            );`n`n            `$1"
    Set-Content "$basePath\TimetableController.php" $content
    Write-Host "[+] TimetableController::create" -ForegroundColor Green
    $added++
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Gray
Write-Host "✓ Added $added logging calls" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Gray
