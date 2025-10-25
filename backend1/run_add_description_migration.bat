@echo off
echo ========================================
echo Adding Description Column to Subjects
echo ========================================
echo.

REM Database credentials from .env
set DB_HOST=localhost
set DB_PORT=4306
set DB_NAME=school_management
set DB_USER=root
set DB_PASS=1212

echo Running migration...
mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p%DB_PASS% %DB_NAME% < migrations\add_description_to_subjects.sql

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS! Description column added
    echo ========================================
    echo.
    echo Table structure updated:
    mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p%DB_PASS% -e "DESCRIBE subjects;" %DB_NAME%
) else (
    echo.
    echo ========================================
    echo ERROR! Migration failed
    echo ========================================
)

echo.
pause
