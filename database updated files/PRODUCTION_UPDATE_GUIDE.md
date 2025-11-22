# Production Database Update Guide (u232752871_sms)

This guide explains how to update your hosted production database (u232752871_sms) with all the new features and fixes without losing any existing data.

## 📋 Overview

The `production_migration_v2.sql` file safely updates your production database to:
- Split teacher and student names into first_name and last_name
- Update currency to SLE (Sierra Leone Leone)
- Add notification tracking system
- Enable password reset functionality
- Add performance indexes
- All existing data is preserved!

## ⚠️ Important: Backup First!

Before running any migration, **ALWAYS backup your database**:

### Using cPanel
1. Login to your hosting cPanel
2. Go to phpMyAdmin
3. Select database: `u232752871_sms`
4. Click "Export" tab
5. Select "Quick" export method
6. Click "Go" to download backup
7. Save file with date: `u232752871_sms_backup_2025_11_21.sql`

### Using Command Line
```bash
mysqldump -u u232752871_boschool -p u232752871_sms > backup_2025_11_21.sql
```

## 🚀 Option 1: Using cPanel phpMyAdmin (Recommended)

### Step-by-Step Instructions

1. **Login to cPanel**
   - Go to your hosting control panel
   - Open phpMyAdmin

2. **Select Your Database**
   - Click on `u232752871_sms` in the left sidebar

3. **Open SQL Tab**
   - Click the "SQL" tab at the top

4. **Copy Migration SQL**
   - Open file: `production_migration_v2.sql`
   - Copy ALL the contents (Ctrl+A, Ctrl+C)

5. **Paste and Execute**
   - Paste into the SQL query box
   - Click "Go" button
   - Wait for execution (may take 30-60 seconds)

6. **Verify Results**
   - You should see multiple "Query OK" messages
   - Check for any error messages
   - If errors occur, restore from backup

7. **Check Verification Output**
   - Scroll to bottom of results
   - You should see:
     ```
     Teachers: Total: X, with_first_name: X, with_last_name: X
     Students: Total: Y, with_first_name: Y, with_last_name: Y
     School Settings: Currency: SLE
     ```

## 🚀 Option 2: Using PHP Script (Alternative)

If you can upload files to your server:

1. **Update .env File**
   ```bash
   # Edit backend1/.env
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=u232752871_sms
   DB_USER=u232752871_boschool
   DB_PASS=Boschool&25
   ```

2. **Upload Files**
   - Upload `backend1/run_production_migration_v2.php`
   - Upload `database updated files/production_migration_v2.sql`

3. **Run via Browser**
   ```
   https://backend.boschool.org/run_production_migration_v2.php
   ```

4. **Or via SSH**
   ```bash
   cd /path/to/backend1
   php run_production_migration_v2.php
   ```

## ✅ Verification Steps

After migration, verify these changes:

### 1. Check Teacher Names
```sql
SELECT id, name, first_name, last_name 
FROM teachers 
LIMIT 5;
```
Expected: All teachers should have first_name and last_name populated

### 2. Check Student Names
```sql
SELECT id, name, first_name, last_name 
FROM students 
LIMIT 5;
```
Expected: All students should have first_name and last_name populated

### 3. Check Currency
```sql
SELECT currency FROM school_settings LIMIT 1;
```
Expected: Should show 'SLE'

### 4. Check New Tables
```sql
SHOW TABLES LIKE 'notification_reads';
SHOW TABLES LIKE 'password_reset_tokens';
```
Expected: Both tables should exist

### 5. Check Settings Columns
```sql
SHOW COLUMNS FROM school_settings 
WHERE Field IN ('email_settings', 'notification_settings', 'security_settings');
```
Expected: All three columns should exist

## 🔍 What the Migration Does

### Safe Updates (No Data Loss)
- ✅ Checks if columns exist before adding
- ✅ Only updates empty first_name/last_name fields
- ✅ Preserves all existing data
- ✅ Uses transactions for safety
- ✅ Creates tables only if they don't exist

### Data Transformations
1. **Teacher Names**
   - Takes existing `name` field
   - Splits on first space
   - Populates `first_name` and `last_name`
   - Example: "John Smith" → first: "John", last: "Smith"

2. **Student Names**
   - Same as teachers
   - Handles single names: "John" → first: "John", last: ""

3. **Currency**
   - Updates school_settings.currency to 'SLE'
   - Only if different from current value

### New Features Added
1. **Notification Tracking**
   - `notification_reads` table
   - Tracks which users have read which notifications
   - Enables accurate unread counts

2. **Password Reset**
   - `password_reset_tokens` table
   - Stores temporary tokens for password resets
   - Enables forgot password functionality

3. **Performance Indexes**
   - Indexes on first_name and last_name
   - Faster search and queries
   - Better performance with large datasets

## 🐛 Troubleshooting

### Error: "Table already exists"
**Solution:** This is fine! The script checks before creating.

### Error: "Column already exists"
**Solution:** This is fine! The script checks before adding.

### Error: "Access denied"
**Solution:** 
- Check database credentials in .env
- Ensure user has ALTER and CREATE permissions

### Error: "Syntax error"
**Solution:**
- Make sure you copied the ENTIRE SQL file
- Check for any missing characters
- Try running in smaller sections

### Some Names Not Split
**Solution:**
```sql
-- Check which names need splitting
SELECT id, name, first_name, last_name 
FROM teachers 
WHERE first_name IS NULL OR first_name = '';

-- Manually fix if needed
UPDATE teachers 
SET first_name = 'John', last_name = 'Doe' 
WHERE id = 123;
```

## 🔄 Rolling Back (If Needed)

If something goes wrong:

1. **Restore from Backup**
   ```bash
   mysql -u u232752871_boschool -p u232752871_sms < backup_2025_11_21.sql
   ```

2. **Or using phpMyAdmin**
   - Go to Import tab
   - Choose your backup file
   - Click "Go"

## 📊 Expected Results

After successful migration:

### Database Structure
```
teachers table:
  - id
  - name (preserved)
  - first_name (NEW)
  - last_name (NEW)
  - email
  - ... other columns

students table:
  - id
  - name (preserved)
  - first_name (NEW)
  - last_name (NEW)
  - id_number
  - ... other columns

school_settings table:
  - currency = 'SLE' (UPDATED)
  - email_settings (NEW)
  - notification_settings (NEW)
  - security_settings (NEW)

notification_reads table (NEW)
  - id
  - notification_id
  - user_id
  - user_role
  - read_at

password_reset_tokens table (NEW)
  - id
  - user_type
  - user_id
  - email
  - token
  - expires_at
```

### Data Integrity
- ✅ All teachers preserved
- ✅ All students preserved
- ✅ All enrollments preserved
- ✅ All grades preserved
- ✅ All payments preserved
- ✅ All notifications preserved
- ✅ All other data preserved

## 🎯 Post-Migration Tasks

After successful migration:

1. **Update Backend .env (if not done)**
   - Ensure correct database credentials
   - Test connection

2. **Test Authentication**
   ```bash
   POST https://backend.boschool.org/api/admin/login
   ```

3. **Test Settings Access**
   ```bash
   GET https://backend.boschool.org/api/admin/settings
   ```

4. **Configure Email Settings**
   - Login to admin panel
   - Go to System Settings > Email
   - Enter SMTP credentials
   - Test email

5. **Test Notifications**
   - Create a test notification
   - Check unread count
   - Mark as read
   - Verify count updates

6. **Test Password Reset**
   - Try "Forgot Password"
   - Check email received
   - Reset password
   - Login with new password

## 📞 Support

If you encounter issues:

1. **Check Error Logs**
   - cPanel > Error Logs
   - Look for recent errors

2. **Check Database Logs**
   - phpMyAdmin > SQL History
   - Review executed queries

3. **Restore from Backup**
   - If something is seriously wrong
   - Restore and try again

4. **Contact Support**
   - Provide error messages
   - Share backup status
   - Describe what happened

## ✅ Migration Checklist

- [ ] Backup database completed
- [ ] Backup file saved safely
- [ ] Migration SQL copied
- [ ] Executed in phpMyAdmin
- [ ] No error messages
- [ ] Verification queries passed
- [ ] Teachers have first/last names
- [ ] Students have first/last names
- [ ] Currency shows SLE
- [ ] New tables exist
- [ ] Backend authentication works
- [ ] Settings page accessible
- [ ] Email test works
- [ ] Notifications work
- [ ] Password reset works

---

**Production Database:** u232752871_sms
**Migration File:** production_migration_v2.sql
**Status:** Ready for deployment
**Safe:** Yes, preserves all data
**Reversible:** Yes, with backup
**Tested:** Yes, on local database

**Last Updated:** November 21, 2025
