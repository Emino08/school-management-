@echo off
echo ================================================
echo Town Master Tables Migration
echo ================================================
echo.

cd /d "%~dp0backend1"

echo Running migration...
php run_town_master_migration.php

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ================================================
    echo Migration completed successfully!
    echo ================================================
) else (
    echo.
    echo ================================================
    echo Migration failed! Please check the errors above.
    echo ================================================
)

pause
