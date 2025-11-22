@echo off
echo ================================================
echo Synchronize Town Master Assignments
echo ================================================
echo.
echo This will link existing town master teachers
echo to the existing town in the system.
echo.

cd /d "%~dp0backend1"

php sync_town_master_assignments.php

echo.
pause
