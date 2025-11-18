# PowerShell script to fix React imports across all JSX files
# This removes unnecessary "React" from import statements when using automatic JSX runtime

$files = Get-ChildItem -Recurse -Filter "*.jsx" | Where-Object { $_.FullName -notlike "*node_modules*" }

$totalFiles = $files.Count
$modifiedCount = 0
$errorCount = 0

Write-Host "Found $totalFiles JSX files to process..." -ForegroundColor Cyan
Write-Host ""

foreach ($file in $files) {
    try {
        $content = Get-Content $file.FullName -Raw
        $originalContent = $content
        
        # Pattern: import React, { ... } from 'react'; -> import { ... } from 'react';
        $content = $content -replace "import React, \{([^}]+)\} from ['""]react['""];", "import {`$1} from 'react';"
        
        if ($content -ne $originalContent) {
            Set-Content -Path $file.FullName -Value $content -NoNewline
            $modifiedCount++
            Write-Host "Fixed: $($file.Name)" -ForegroundColor Green
        }
    }
    catch {
        $errorCount++
        Write-Host "Error in: $($file.Name) - $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "Processing Complete!" -ForegroundColor Green
Write-Host "Total files: $totalFiles" -ForegroundColor White
Write-Host "Modified: $modifiedCount" -ForegroundColor Green
Write-Host "Errors: $errorCount" -ForegroundColor $(if ($errorCount -gt 0) { 'Red' } else { 'Green' })
Write-Host "================================" -ForegroundColor Cyan
