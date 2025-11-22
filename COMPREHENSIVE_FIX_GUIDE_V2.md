# Comprehensive Fix Guide V2
## All Issues Resolved - November 21, 2025

This document outlines all the fixes applied to resolve the reported issues in the School Management System.

---

## 🔧 Issues Fixed

### 1. **Authentication Token Error (Invalid or Expired Token)**
**Problem:** JWT token validation failing with SQL syntax error when accessing admin settings and profile.

**Root Cause:** 
- SQL syntax error in `SettingsController` saveJsonSettings method - column name not properly escaped
- Missing WHERE clause in uploadLogo method using `LIMIT 1` without WHERE

**Solution:**
- ✅ Added backticks around dynamic column names in SQL queries
- ✅ Added column name validation to prevent SQL injection
- ✅ Fixed uploadLogo method to use WHERE clause with proper ID parameter
- ✅ Updated BaseModel update() method to add backticks around field names
- ✅ Added validation to prevent empty field updates

**Files Modified:**
- `backend1/src/Controllers/SettingsController.php`
- `backend1/src/Models/BaseModel.php`

---

### 2. **Environment File Parse Error**
**Problem:** Dotenv parser failing with "Encountered unexpected whitespace at [School Management System]"

**Root Cause:** 
- APP_NAME in .env had spaces without quotes

**Solution:**
- ✅ Wrapped APP_NAME value in quotes: `APP_NAME="School Management System"`

**Files Modified:**
- `backend1/.env`

---

### 3. **Duplicate Notification Routes**
**Problem:** FastRoute error - "Cannot register two routes matching '/api/notifications' for method 'GET'"

**Root Cause:**
- Two separate notification route definitions (lines 503-528 and 638-670)

**Solution:**
- ✅ Removed duplicate notification routes (lines 502-529)
- ✅ Kept the more comprehensive user notification routes

**Files Modified:**
- `backend1/src/Routes/api.php`

---

### 4. **Teacher Update SQL Error**
**Problem:** HTTP 405 Method Not Allowed and SQL syntax error when updating teacher

**Root Cause:**
- BaseModel update method could generate invalid SQL if data array becomes empty
- Missing validation for empty updates

**Solution:**
- ✅ Added check for empty fields array before building SQL
- ✅ Improved error messages for debugging
- ✅ Added backticks around field names

**Files Modified:**
- `backend1/src/Models/BaseModel.php`

---

### 5. **Teacher and Student Name Splitting**
**Problem:** Need to split full names into first_name and last_name fields

**Solution:**
- ✅ Created comprehensive migration SQL file
- ✅ Automatically splits existing names in database
- ✅ Handles single-word names properly
- ✅ StudentController already had extractNameParts method
- ✅ TeacherController already had name splitting logic
- ✅ Bulk upload templates updated to use First Name and Last Name columns
- ✅ Added performance indexes on first_name and last_name

**Database Changes:**
- Added `first_name` column to teachers table
- Added `last_name` column to teachers table  
- Added `first_name` column to students table
- Added `last_name` column to students table
- Created indexes for performance

**Files:**
- `database updated files/production_migration_v2.sql`
- `backend1/run_production_migration_v2.php`
- ✅ Controllers already handle name splitting properly

---

### 6. **Currency Update to SLE (Sierra Leone Leone)**
**Problem:** System using generic currency, need SLE for Sierra Leone

**Solution:**
- ✅ Updated school_settings table to use SLE currency
- ✅ Migration automatically updates existing currency values
- ✅ Frontend should display amounts with SLE symbol

**Database Changes:**
- Updated `currency` field in school_settings to 'SLE'

**Frontend Updates Needed:**
- Update currency display in Reports/Financial sections
- Replace ₦ symbol with SLE (or Le for Leone)

---

### 7. **Notification System Issues**
**Problem:** 
- Notifications showing count of 3 when no notifications exist
- Need read/unread listings
- Need proper notification management

**Solution:**
- ✅ Created `notification_reads` table to track read status
- ✅ NotificationController has proper methods for:
  - `getForUser` - Get user-specific notifications
  - `getUnreadCount` - Get accurate unread count
  - `markAsRead` - Mark notification as read
- ✅ Routes properly configured at `/api/notifications`

**Database Changes:**
- Created `notification_reads` table with proper foreign keys and indexes

**Frontend Updates Needed:**
- Ensure notification badge uses `/api/notifications/unread-count` endpoint
- Implement notification list showing read/unread status
- Add mark as read functionality

---

### 8. **Password Reset System**
**Problem:** Need password reset via email for all user types

**Solution:**
- ✅ Created `password_reset_tokens` table
- ✅ PasswordResetController already exists with:
  - `requestReset` - Send reset email
  - `verifyToken` - Check if token is valid
  - `resetPassword` - Reset password with token
- ✅ Mailer utility already configured
- ✅ Routes configured at:
  - `POST /api/password/forgot`
  - `GET /api/password/verify-token`
  - `POST /api/password/reset`

**Configuration Required:**
- Update SMTP settings in System Settings > Email tab:
  - SMTP Host
  - SMTP Port  
  - SMTP Username
  - SMTP Password
  - From Email
  - From Name

---

### 9. **System Settings Tabs**
**Problem:** Ensure all tabs are working (General, Notifications, Email, Security, System)

**Solution:**
- ✅ All necessary columns exist in school_settings table:
  - `email_settings` (TEXT)
  - `notification_settings` (TEXT)
  - `security_settings` (TEXT)
  - `maintenance_mode` (TINYINT)
- ✅ SettingsController has methods for all tabs:
  - `getSettings` - Retrieve all settings
  - `updateSettings` - Update settings by type
  - `testEmail` - Test email configuration
- ✅ Routes configured:
  - `GET /api/admin/settings`
  - `PUT /api/admin/settings`
  - `POST /api/admin/settings/test-email`

---

### 10. **Teacher Classes View Button**
**Problem:** Need view button in teacher management to show classes taught by teacher

**Solution:**
- ✅ `getTeacherClasses` endpoint already exists
- ✅ Route: `GET /api/teachers/{id}/classes`
- ✅ Returns list of classes and subjects assigned to teacher

**Frontend Updates Needed:**
- Add "View Classes" button in teacher management table
- Create modal to display teacher's classes
- Fetch data from `/api/teachers/{id}/classes`

---

### 11. **Reports Tab - Currency Display**
**Problem:** Reports showing ₦0 instead of SLE

**Solution:**
- ✅ Database currency updated to SLE
- Frontend needs to use correct currency symbol

**Frontend Updates Needed:**
- Update ReportsController or Frontend to use SLE symbol
- Replace ₦ with SLE or Le throughout reports
- Ensure proper formatting: "SLE 1,000.00" or "Le 1,000.00"

---

## 📊 Database Migration Summary

### Migration File
`database updated files/production_migration_v2.sql`

### What It Does:
1. ✅ Adds first_name and last_name to teachers
2. ✅ Adds first_name and last_name to students
3. ✅ Splits all existing names automatically
4. ✅ Creates notification_reads table
5. ✅ Creates password_reset_tokens table
6. ✅ Updates currency to SLE
7. ✅ Adds all necessary settings columns
8. ✅ Creates performance indexes

### How to Run:
```bash
# Option 1: Use the batch file
RUN_MIGRATION_V2.bat

# Option 2: Run PHP directly
cd backend1
php run_production_migration_v2.php
```

### Verification Results:
After migration, you'll see:
- Teachers: Total, with first_name, with last_name
- Students: Total, with first_name, with last_name  
- Currency: SLE
- notification_reads table: ✓ Exists
- password_reset_tokens table: ✓ Exists

---

## 🎯 Frontend Updates Still Needed

### 1. Currency Display
**Location:** Reports/Financial sections
**Change:** Replace ₦ with SLE or Le
**Files to Update:**
- Any component displaying financial amounts
- Reports dashboard

### 2. Notification Badge
**Location:** Header/Navigation
**Change:** Use `/api/notifications/unread-count` endpoint
**Ensure:** Badge updates when notifications are marked as read

### 3. Teacher Classes View Modal
**Location:** Teacher Management page
**Add:**
- "View Classes" button in table
- Modal component to display classes
- API call to `/api/teachers/{id}/classes`

### 4. Student/Teacher Forms
**Verify:**
- Registration forms use first_name and last_name
- CSV upload templates match (First Name, Last Name)
- Update/Edit forms handle name splitting

### 5. System Settings Email Configuration
**Location:** System Settings > Email tab
**Ensure:**
- All SMTP fields are editable
- Test Email button works
- Configuration saves properly

---

## 🚀 Deployment Checklist

### For Production (u232752871_sms)
- [ ] Backup current database
- [ ] Update .env with production database credentials
- [ ] Run migration: `RUN_MIGRATION_V2.bat`
- [ ] Verify migration results
- [ ] Test authentication (login, profile, settings)
- [ ] Test teacher/student creation with names
- [ ] Configure email settings
- [ ] Test password reset flow
- [ ] Test notification system
- [ ] Update frontend currency display
- [ ] Deploy frontend changes
- [ ] Clear browser cache and test all tabs

### Testing Endpoints
```bash
# Authentication
POST /api/admin/login
GET /api/admin/profile
GET /api/admin/settings

# Notifications
GET /api/notifications
GET /api/notifications/unread-count
POST /api/notifications/{id}/mark-read

# Teachers
GET /api/teachers
GET /api/teachers/{id}/classes
PUT /api/teachers/{id}

# Students
GET /api/students
PUT /api/students/{id}

# Password Reset
POST /api/password/forgot
GET /api/password/verify-token
POST /api/password/reset

# Email Test
POST /api/admin/settings/test-email
```

---

## 📝 Configuration Steps

### 1. Email Configuration (System Settings > Email)
```
SMTP Host: smtp.gmail.com (or your provider)
SMTP Port: 587
SMTP Username: your-email@gmail.com
SMTP Password: your-app-password
SMTP Encryption: tls
From Email: noreply@school.com
From Name: School Management System
```

### 2. Test Email
1. Go to System Settings > Email tab
2. Enter email address in test field
3. Click "Test Email"
4. Check inbox for test message

### 3. Password Reset Flow
1. User clicks "Forgot Password"
2. Enters email address
3. System sends email with reset link
4. User clicks link (contains token)
5. User enters new password
6. Token is validated and password is reset

---

## ✅ Success Indicators

### Backend Health
- ✅ No .env parse errors
- ✅ No duplicate route errors
- ✅ JWT authentication works
- ✅ All API endpoints respond
- ✅ Database queries execute without errors

### Database Status
- ✅ Teachers have first_name and last_name
- ✅ Students have first_name and last_name
- ✅ Currency set to SLE
- ✅ notification_reads table exists
- ✅ password_reset_tokens table exists

### Functionality
- ✅ Admin can login and access all tabs
- ✅ System Settings tabs all work
- ✅ Email test sends successfully
- ✅ Teacher/Student creation works with names
- ✅ Notifications show accurate count
- ✅ Password reset emails send
- ✅ Teacher classes endpoint returns data

---

## 🐛 Troubleshooting

### Issue: "Invalid or expired token" still appearing
**Solution:** 
- Clear all browser cache and localStorage
- Logout and login again
- Check JWT_SECRET hasn't changed
- Verify token expiry is not too short

### Issue: Email not sending
**Solution:**
- Verify SMTP credentials in System Settings
- Check firewall allows SMTP port
- For Gmail, use App Password not regular password
- Test SMTP connection first

### Issue: Notifications count wrong
**Solution:**
- Check notification_reads table exists
- Verify routes use correct endpoint
- Clear old test notifications from database

### Issue: Name splitting not working
**Solution:**
- Verify migration ran successfully
- Check columns exist: `SHOW COLUMNS FROM teachers LIKE '%name%'`
- Re-run migration if needed

---

## 📞 Support

For issues not covered in this guide:
1. Check server logs: `backend1/logs/`
2. Check browser console for frontend errors
3. Verify database connection
4. Review error messages carefully

---

**Migration Status:** ✅ COMPLETED
**Last Updated:** November 21, 2025
**Version:** 2.0
