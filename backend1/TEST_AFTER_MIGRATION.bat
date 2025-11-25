@echo off
echo ========================================
echo TESTING BACKEND AFTER MIGRATION
echo ========================================
echo.

cd /d "C:\Users\SABITECK\OneDrive\Documenti\Projects\SABITECK\School Management System\backend1"

echo Testing database structure...
echo.

echo 1. Checking admins table for is_super_admin column...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='admins' AND COLUMN_NAME='is_super_admin';"
echo.

echo 2. Checking student_parents table exists...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SHOW TABLES LIKE 'student_parents';"
echo.

echo 3. Counting parent-student relationships...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SELECT COUNT(*) as total_relationships FROM student_parents;"
echo.

echo 4. Checking medical_records enhancements...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='medical_records' AND COLUMN_NAME IN ('parent_id', 'added_by', 'can_update', 'can_delete');"
echo.

echo 5. Checking medical_records ENUM values...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SHOW COLUMNS FROM medical_records WHERE Field='record_type';"
echo.

echo ========================================
echo Testing API Endpoints (using curl)
echo ========================================
echo.
echo NOTE: You need to replace TOKEN with actual JWT token
echo.

echo 6. Test admin check super admin status...
echo curl -X GET http://localhost:8080/api/admin/check-super-admin-status -H "Authorization: Bearer YOUR_TOKEN"
echo.

echo 7. Test parent get children...
echo curl -X GET http://localhost:8080/api/parents/children -H "Authorization: Bearer YOUR_TOKEN"
echo.

echo 8. Test parent medical records...
echo curl -X POST http://localhost:8080/api/parents/medical-records -H "Content-Type: application/json" -H "Authorization: Bearer YOUR_TOKEN" -d "{\"student_id\":1,\"record_type\":\"general\",\"description\":\"Test\"}"
echo.

echo ========================================
echo Database Schema Verification
echo ========================================
echo.

echo Showing admins table structure...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "DESCRIBE admins;"
echo.

echo Showing students table structure (checking suspension_status)...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SHOW COLUMNS FROM students WHERE Field IN ('suspension_status', 'photo', 'status');"
echo.

echo Showing medical_records table structure...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "DESCRIBE medical_records;"
echo.

echo Showing student_parents table structure...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "DESCRIBE student_parents;"
echo.

echo ========================================
echo Data Verification
echo ========================================
echo.

echo Listing all admins with roles...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SELECT id, email, role, is_super_admin, parent_admin_id, created_at FROM admins ORDER BY created_at DESC LIMIT 10;"
echo.

echo Listing parent-student relationships...
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SELECT sp.*, p.name as parent_name, s.first_name, s.last_name FROM student_parents sp LEFT JOIN parents p ON sp.parent_id = p.id LEFT JOIN students s ON sp.student_id = s.id LIMIT 10;"
echo.

echo ========================================
echo Testing Complete!
echo ========================================
echo.
echo If all checks passed, your backend is ready!
echo.
echo Next steps:
echo 1. Update frontend code (see FRONTEND_CHANGES_REQUIRED_NOV_24.md)
echo 2. Test with actual user accounts
echo 3. Verify data inheritance works
echo.

pause
