# QUICK START - System Fixes

## Step 1: Run Database Migration

### Windows (Easiest)
```batch
cd backend1
RUN_SCHEMA_FIXES.bat
```

### Manual PHP
```bash
cd backend1
php run_schema_fixes.php
```

## Step 2: Restart Backend Server

```batch
# Stop current server (Ctrl+C)
cd backend1
php -S localhost:8080 -t public
```

## Step 3: Test Fixed Endpoints

### Test Activity Logs
```
GET http://localhost:8080/api/admin/activity-logs/stats
```

### Test Notifications
```
GET http://localhost:8080/api/notifications
```

### Test Teachers
```
GET http://localhost:8080/api/teachers
GET http://localhost:8080/api/teachers/1/classes
GET http://localhost:8080/api/teachers/1/subjects
```

## What Was Fixed?

✅ Activity logs `activity_type` column added  
✅ System settings `currency_code` column added  
✅ Duplicate teacher routes removed  
✅ Teacher names split to first_name/last_name  
✅ Town master system tables created  
✅ User roles table created  
✅ Urgent notifications table created  

## Next Steps

1. Update frontend to use new teacher name fields
2. Add "View Classes" and "View Subjects" buttons
3. Create town master management interface
4. Create parent registration page

## Need Help?

See `COMPLETE_FIXES_NOV_21_2025.md` for full documentation.
