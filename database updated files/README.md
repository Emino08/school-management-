# Database Migration Guide - Production Update

## Overview
This folder contains SQL scripts to migrate your production database (`u232752871_sms`) to the latest structure without losing any data.

---

## 📋 What Gets Updated

### New Tables Created:
1. **teacher_subject_assignments** - Maps teachers to subjects and classes
2. **notification_reads** - Tracks which notifications users have read
3. **password_resets** - Stores password reset tokens for forgot password feature
4. **email_logs** - Logs all sent emails for tracking

### Students Table Updates:
- ✅ Adds `first_name` column (VARCHAR 100)
- ✅ Adds `last_name` column (VARCHAR 100)
- ✅ **Automatically splits existing names** - "John Doe" → first_name: "John", last_name: "Doe"
- ✅ Keeps existing `name` column unchanged

### School Settings Updates:
- ✅ Adds `notification_settings` (JSON) - Email/SMS/Push preferences
- ✅ Adds `email_settings` (JSON) - SMTP configuration
- ✅ Adds `security_settings` (JSON) - Password policies
- ✅ Adds `system_settings` (JSON) - Maintenance mode, etc.

### Performance Improvements:
- ✅ Adds indexes on key foreign key columns
- ✅ Optimizes queries for admin_id lookups

---

## 🔐 Safety Features

- ✅ **Zero Data Loss** - All existing data is preserved
- ✅ **Idempotent** - Can be run multiple times safely
- ✅ **Transactional** - All changes committed together or rolled back
- ✅ **Foreign Key Safe** - Temporarily disables checks during migration
- ✅ **Conditional** - Only adds columns/tables that don't exist

---

## 📁 Files in This Folder

1. **updated.sql** - Main migration script (RUN THIS)
2. **verify_before_migration.sql** - Check database state before migration
3. **verify_after_migration.sql** - Verify migration was successful
4. **README.md** - This file

---

## 🚀 Migration Steps

### Step 1: Backup Your Database

**IMPORTANT:** Always backup before making changes!

```bash
# SSH into your server
ssh your-username@your-server.com

# Create backup
mysqldump -u u232752871_boschool -p u232752871_sms > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup was created
ls -lh backup_*.sql
```

Or use your hosting control panel (cPanel, Plesk, etc.) to create a backup.

---

### Step 2: Verify Current State

Run the pre-migration verification script to check your current database:

```bash
mysql -u u232752871_boschool -p u232752871_sms < verify_before_migration.sql > pre_migration_report.txt

# Review the output
cat pre_migration_report.txt
```

**Save this output!** You'll compare it after migration.

---

### Step 3: Run the Migration

```bash
mysql -u u232752871_boschool -p u232752871_sms < updated.sql > migration_log.txt

# Check for errors
cat migration_log.txt
```

**Expected output should show:**
- ✅ Tables created successfully
- ✅ Columns added successfully
- ✅ Migration completed message
- ✅ No ERROR messages

---

### Step 4: Verify Migration Success

```bash
mysql -u u232752871_boschool -p u232752871_sms < verify_after_migration.sql > post_migration_report.txt

# Review the output
cat post_migration_report.txt
```

**Check for:**
- ✅ All record counts match pre-migration
- ✅ New tables exist and are accessible
- ✅ Student names properly split into first_name and last_name
- ✅ No data loss

---

### Step 5: Compare Reports

```bash
# Compare record counts
diff pre_migration_report.txt post_migration_report.txt
```

**Record counts should be IDENTICAL!** If any data is missing, restore from backup.

---

## 🔍 What to Check After Migration

### 1. Student Names
```sql
SELECT id, name, first_name, last_name FROM students LIMIT 10;
```

**Expected:**
- All students have first_name and last_name populated
- Original name column still intact

### 2. New Tables
```sql
SHOW TABLES LIKE '%teacher_subject%';
SHOW TABLES LIKE '%notification%';
SHOW TABLES LIKE '%password_reset%';
SHOW TABLES LIKE '%email_log%';
```

**Expected:**
- All 4 new tables exist

### 3. School Settings
```sql
SELECT * FROM school_settings\G
```

**Expected:**
- notification_settings, email_settings, security_settings, system_settings columns exist

---

## 🔧 Using cPanel/phpMyAdmin

If you don't have SSH access:

1. **Login to cPanel**
2. **Open phpMyAdmin**
3. **Select database:** `u232752871_sms`
4. **Click "Import" tab**
5. **Choose file:** `updated.sql`
6. **Click "Go"**
7. **Wait for completion**
8. **Check for success message**

---

## ⚠️ Troubleshooting

### Error: "Table already exists"
**Solution:** This is safe! The script checks if tables exist before creating them.

### Error: "Column already exists"
**Solution:** This is safe! The script checks if columns exist before adding them.

### Error: "Duplicate entry"
**Solution:** This is safe! The script uses INSERT IGNORE for default data.

### Error: "Foreign key constraint fails"
**Solution:** 
- Check that all referenced tables exist
- Ensure parent records exist before child records
- Contact support if issue persists

### No errors but data missing
**Solution:**
1. STOP! Don't make more changes
2. Restore from backup immediately
3. Contact support with error logs

---

## 📊 Expected Migration Results

### Before Migration:
```
students: 150 records
teachers: 25 records
classes: 30 records
students.first_name: Column doesn't exist
students.last_name: Column doesn't exist
teacher_subject_assignments: Table doesn't exist
```

### After Migration:
```
students: 150 records (SAME)
teachers: 25 records (SAME)
classes: 30 classes (SAME)
students.first_name: Column exists, 150 records populated
students.last_name: Column exists, 150 records populated
teacher_subject_assignments: Table exists, 0+ records
notification_reads: Table exists, 0 records
password_resets: Table exists, 0 records
email_logs: Table exists, 0 records
```

---

## 🔄 Rollback (If Needed)

If something goes wrong:

### Option 1: Restore from Backup
```bash
mysql -u u232752871_boschool -p u232752871_sms < backup_YYYYMMDD_HHMMSS.sql
```

### Option 2: Manual Rollback (if only new tables were added)
```sql
DROP TABLE IF EXISTS teacher_subject_assignments;
DROP TABLE IF EXISTS notification_reads;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS email_logs;

ALTER TABLE students DROP COLUMN IF EXISTS first_name;
ALTER TABLE students DROP COLUMN IF EXISTS last_name;

ALTER TABLE school_settings DROP COLUMN IF EXISTS notification_settings;
ALTER TABLE school_settings DROP COLUMN IF EXISTS email_settings;
ALTER TABLE school_settings DROP COLUMN IF EXISTS security_settings;
ALTER TABLE school_settings DROP COLUMN IF EXISTS system_settings;
```

---

## ✅ Post-Migration Checklist

- [ ] Backup created and verified
- [ ] Pre-migration report saved
- [ ] Migration script executed successfully
- [ ] Post-migration report reviewed
- [ ] Record counts match
- [ ] Sample queries return expected data
- [ ] Student names properly split
- [ ] New tables accessible
- [ ] Application connects successfully
- [ ] Test login with existing accounts
- [ ] Test creating new student
- [ ] Test teacher class view
- [ ] Test notification system

---

## 🎯 What This Enables

After successful migration, your application will support:

1. **✅ Separate First/Last Names** - Better data organization
2. **✅ Teacher Class Management** - View all classes a teacher teaches
3. **✅ Real-time Notifications** - Web notifications for all updates
4. **✅ Password Reset** - Forgot password via email
5. **✅ Email System** - Welcome emails, password resets, notifications
6. **✅ System Settings** - Email config, security policies, maintenance mode

---

## 📞 Support

If you encounter any issues:

1. **Check migration logs** - Look for ERROR messages
2. **Run verification scripts** - Compare before/after
3. **Review this README** - Check troubleshooting section
4. **Restore from backup** - If data is lost
5. **Contact developer** - With error logs and reports

---

## 🔒 Security Notes

- ✅ Use strong database password
- ✅ Keep backups in secure location
- ✅ Don't commit database credentials to git
- ✅ Run migrations during low-traffic periods
- ✅ Test on staging environment first (if available)

---

## 📝 Migration History

- **2025-11-21** - Initial migration script created
  - Added student name splitting
  - Added teacher-subject-class assignments
  - Added notification system
  - Added password reset system
  - Added email logging
  - Added school settings enhancements

---

## ✨ Success!

If all checks pass, your production database is now fully updated and compatible with the latest backend code!

**No data was lost, and all new features are now available.** 🎉
