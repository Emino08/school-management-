# Command Reference - Quick Actions

## 1️⃣ RUN DATABASE MIGRATION
```batch
cd backend1
RUN_SCHEMA_FIXES.bat
```

## 2️⃣ RESTART BACKEND SERVER
```batch
cd backend1
php -S localhost:8080 -t public
```

## 3️⃣ TEST ENDPOINTS
```batch
cd backend1
TEST_FIXES.bat
```

## 4️⃣ MANUAL API TESTS

### Test Activity Logs
```bash
curl http://localhost:8080/api/admin/activity-logs/stats
```

### Test Notifications
```bash
curl http://localhost:8080/api/notifications
```

### Test Teacher Classes
```bash
curl http://localhost:8080/api/teachers/1/classes
```

### Test Teacher Subjects
```bash
curl http://localhost:8080/api/teachers/1/subjects
```

## 5️⃣ VERIFY DATABASE CHANGES

Connect to MySQL and run:
```sql
-- Check activity_logs
SHOW COLUMNS FROM activity_logs LIKE 'activity_type';

-- Check system_settings
SHOW COLUMNS FROM system_settings LIKE 'currency_code';

-- Check teachers
SHOW COLUMNS FROM teachers LIKE 'first_name';
SHOW COLUMNS FROM teachers LIKE 'last_name';

-- Check new tables
SHOW TABLES LIKE 'teacher_classes';
SHOW TABLES LIKE 'towns';
SHOW TABLES LIKE 'urgent_notifications';
SHOW TABLES LIKE 'user_roles';
```

## 6️⃣ FIND FRONTEND API ISSUES

Search for double API prefix:
```bash
cd frontend1
grep -r "/api/api/" src/
```

## 7️⃣ VIEW DOCUMENTATION

### Quick Start
```
QUICKSTART_FIXES_NOV_21.md
```

### Full Details
```
COMPLETE_FIXES_NOV_21_2025.md
```

### Summary
```
FIXES_SUMMARY_NOV_21.md
```

### Checklist
```
IMPLEMENTATION_CHECKLIST_NOV_21.md
```

### Visual Overview
```
VISUAL_SUMMARY_NOV_21.txt
```

### Before/After
```
BEFORE_AFTER_COMPARISON_NOV_21.md
```

## 🎯 THAT'S IT!

All files are ready. Just run the migration and you're good to go!
