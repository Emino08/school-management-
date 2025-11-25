@echo off
echo ========================================
echo Parent-Child Linking Fix - Quick Test
echo ========================================
echo.

cd backend1

echo Step 1: Running verification script...
echo.
php test_parent_link_fix.php

echo.
echo ========================================
echo Test Complete!
echo ========================================
echo.
echo If you saw errors about database connection:
echo 1. Make sure MySQL/MariaDB is running
echo 2. Check your .env file has correct DB credentials
echo 3. Run: START_BACKEND.bat (in another terminal)
echo.
echo If tests passed:
echo 1. Start frontend: npm run dev (in frontend1 folder)
echo 2. Login as parent
echo 3. Try linking a child
echo 4. Check if child appears in dashboard
echo.
pause
