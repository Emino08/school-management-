@echo off
echo ===============================================
echo Production Database Migration V2
echo ===============================================
echo.
echo This will apply the following updates:
echo - Split teacher and student names
echo - Update currency to SLE
echo - Setup notification system
echo - Enable password reset functionality
echo - Add performance indexes
echo.
pause

cd backend1
php run_production_migration_v2.php

echo.
pause
