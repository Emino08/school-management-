@echo off
echo.
echo ==============================================
echo   Running Schema Fix Migration
echo ==============================================
echo.

cd /d "%~dp0"
php run_schema_fixes.php

echo.
pause
