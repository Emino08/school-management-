@echo off
REM Ensure class capacity + placement thresholds are present
cd /d "%~dp0"
if not exist migrations\033_class_parallel_and_thresholds.php (
  echo Migration 033 not found. Aborting.
  exit /b 1
)
echo Running migration 033: class capacity and placement thresholds...
php migrations\033_class_parallel_and_thresholds.php
if %errorlevel% neq 0 (
  echo Migration failed. See output above.
  exit /b %errorlevel%
)
echo Done.
