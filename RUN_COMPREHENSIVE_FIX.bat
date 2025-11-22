@echo off
echo ========================================
echo  Running Comprehensive Database Migration
echo ========================================
echo.

cd /d "%~dp0backend1"

echo Starting migration...
echo.

php run_comprehensive_fix_migration.php

echo.
echo ========================================
echo  Migration Complete
echo ========================================
echo.
pause
