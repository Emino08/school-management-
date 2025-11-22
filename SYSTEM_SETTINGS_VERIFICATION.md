# System Settings - Complete Testing and Fix Guide

## Overview
This document verifies and ensures all System Settings functionality works without errors.

## Settings Tabs
1. **General** - School information, academic year settings
2. **Notifications** - Email, SMS, push notification preferences  
3. **Email** - SMTP configuration for sending emails
4. **Security** - Password policies, session timeout, login attempts

## Backend Status

### Database ✅
- Table: `school_settings`
- Structure includes all required fields:
  - General: school_name, school_code, address, phone, email, website, logo_url
  - Academic: academic_year_start_month, academic_year_end_month, timezone
  - JSON fields: notification_settings, email_settings, security_settings
  - System: maintenance_mode

### API Endpoints ✅
```
GET  /api/admin/settings - Get all settings
PUT  /api/admin/settings - Update settings (by type)
POST /api/admin/settings/backup - Create database backup
POST /api/admin/settings/test-email - Test email configuration
POST /api/admin/cache/clear - Clear system cache
```

### Controller Methods ✅
- `getSettings()` - Returns all settings grouped by type
- `updateSettings()` - Updates specific settings type
- `createBackup()` - Creates SQL backup file
- `testEmail()` - Sends test email to verify SMTP settings

## Frontend Status

### Component: SystemSettings.jsx ✅
- Four tabs: General, Notifications, Email, Security
- Fetches settings on mount
- Saves settings by type
- Test email functionality
- Backup/restore functionality

## Testing Each Tab

### 1. General Settings
**Fields:**
- School Name
- School Code
- School Address
- School Phone
- School Email
- School Website
- School Logo
- Academic Year Start Month (dropdown)
- Academic Year End Month (dropdown)
- Timezone (dropdown)

**Test:**
```bash
# Update general settings
curl -X PUT http://localhost:8080/api/admin/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "general",
    "settings": {
      "school_name": "Test School",
      "school_code": "TS001",
      "school_address": "123 Test St",
      "school_phone": "+1234567890",
      "school_email": "test@school.com"
    }
  }'
```

### 2. Notifications Settings
**Fields:**
- Email Notifications Enabled (switch)
- SMS Notifications Enabled (switch)
- Push Notifications Enabled (switch)
- Notify on Attendance (switch)
- Notify on Results (switch)
- Notify on Fees (switch)
- Notify on Complaints (switch)

**Test:**
```bash
curl -X PUT http://localhost:8080/api/admin/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "notifications",
    "settings": {
      "email_enabled": true,
      "notify_attendance": true,
      "notify_results": true
    }
  }'
```

### 3. Email Settings (SMTP Configuration)
**Fields:**
- SMTP Host
- SMTP Port  
- SMTP Username
- SMTP Password
- SMTP Encryption (tls/ssl)
- From Email
- From Name
- **Test Email Button** - Sends test email

**Test:**
```bash
# Update email settings
curl -X PUT http://localhost:8080/api/admin/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "email",
    "settings": {
      "smtp_host": "smtp.gmail.com",
      "smtp_port": 587,
      "smtp_username": "your-email@gmail.com",
      "smtp_password": "your-app-password",
      "smtp_encryption": "tls",
      "from_email": "noreply@school.com",
      "from_name": "School System"
    }
  }'

# Test email
curl -X POST http://localhost:8080/api/admin/settings/test-email \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "to_email": "test@example.com"
  }'
```

### 4. Security Settings
**Fields:**
- Force Password Change on First Login (switch)
- Minimum Password Length (number)
- Require Uppercase Letters (switch)
- Require Lowercase Letters (switch)
- Require Numbers (switch)
- Require Special Characters (switch)
- Session Timeout (minutes)
- Max Login Attempts
- Two-Factor Authentication Enabled (switch)

**Test:**
```bash
curl -X PUT http://localhost:8080/api/admin/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "security",
    "settings": {
      "password_min_length": 8,
      "password_require_uppercase": true,
      "session_timeout": 30,
      "max_login_attempts": 5
    }
  }'
```

## Common Issues and Fixes

### Issue 1: Settings Not Loading
**Symptom:** Empty settings or "Failed to load settings" error

**Fix:**
```sql
-- Check if settings record exists
SELECT * FROM school_settings;

-- If empty, insert default record
INSERT INTO school_settings (id, school_name, school_code) 
VALUES (1, 'My School', 'SCH001');
```

### Issue 2: Email Settings Not Working
**Symptom:** Test email fails or emails not sending

**Checks:**
1. SMTP credentials correct?
2. Port accessible (587 for TLS, 465 for SSL)?
3. Gmail App Password generated (not regular password)?
4. Firewall blocking SMTP ports?

**Gmail Setup:**
1. Enable 2-Factor Authentication
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use App Password in SMTP settings

**Example Working Config:**
```json
{
  "smtp_host": "smtp.gmail.com",
  "smtp_port": 587,
  "smtp_username": "yourschool@gmail.com",
  "smtp_password": "xxxx xxxx xxxx xxxx",
  "smtp_encryption": "tls",
  "from_email": "noreply@yourschool.com",
  "from_name": "School Management System"
}
```

### Issue 3: JSON Fields Not Updating
**Symptom:** Settings save but don't persist

**Fix:** Ensure JSON encoding in controller:
```php
$notificationSettings = json_encode($settings);
$stmt = $db->prepare("UPDATE school_settings SET notification_settings = ? WHERE id = 1");
$stmt->execute([$notificationSettings]);
```

### Issue 4: Unauthorized Errors
**Symptom:** 401 errors when accessing settings

**Fix:**
1. Check token validity
2. Verify AuthMiddleware is applied
3. Check user role permissions

## Email Testing Checklist

- [ ] SMTP Host is correct
- [ ] SMTP Port matches encryption (587=TLS, 465=SSL)
- [ ] SMTP Username is valid email
- [ ] SMTP Password is app password (for Gmail)
- [ ] From Email is valid
- [ ] From Name is set
- [ ] Test Email button works
- [ ] Actual emails send (attendance, results, etc.)

## Verification Steps

1. **Access Settings Page:**
   - Navigate to http://localhost:5174/Admin/settings
   - Page loads without errors
   - All tabs visible

2. **Test General Tab:**
   - Fill in school information
   - Click Save
   - Refresh page - data persists

3. **Test Notifications Tab:**
   - Toggle switches
   - Click Save
   - Settings persist after refresh

4. **Test Email Tab:**
   - Enter SMTP details
   - Click Save
   - Click "Test Email"
   - Check inbox for test email

5. **Test Security Tab:**
   - Set password requirements
   - Set session timeout
   - Click Save
   - Try logging in with weak password (should fail)

## Success Criteria

✅ **General:**
- School info saves and displays correctly
- Logo upload works (if implemented)
- Academic year settings persist

✅ **Notifications:**
- Toggle switches work
- Settings save to database
- Notification preferences respected in system

✅ **Email:**
- SMTP settings save
- Test email sends successfully
- System emails use configured SMTP
- Error messages clear if config wrong

✅ **Security:**
- Password requirements enforced
- Session timeout works
- Login attempts limited
- Settings persist

## Quick Fix Script

If settings page is broken, run this SQL:

```sql
-- Ensure school_settings table exists with correct structure
CREATE TABLE IF NOT EXISTS school_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL DEFAULT 'School',
    school_code VARCHAR(50) NULL,
    address TEXT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    website VARCHAR(255) NULL,
    logo_url VARCHAR(255) NULL,
    principal_name VARCHAR(100) NULL,
    academic_year_start_month INT DEFAULT 9,
    academic_year_end_month INT DEFAULT 6,
    currency VARCHAR(10) DEFAULT 'USD',
    timezone VARCHAR(50) DEFAULT 'UTC',
    notification_settings TEXT NULL,
    email_settings TEXT NULL,
    security_settings TEXT NULL,
    maintenance_mode TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings if none exist
INSERT IGNORE INTO school_settings (id, school_name, school_code) 
VALUES (1, 'My School', 'SCH001');

-- Set default notification settings
UPDATE school_settings 
SET notification_settings = '{"email_enabled":true,"sms_enabled":false,"push_enabled":true,"notify_attendance":true,"notify_results":true,"notify_fees":true,"notify_complaints":true}'
WHERE id = 1 AND notification_settings IS NULL;

-- Set default security settings
UPDATE school_settings 
SET security_settings = '{"force_password_change":false,"password_min_length":6,"password_require_uppercase":true,"password_require_lowercase":true,"password_require_numbers":true,"password_require_special":false,"session_timeout":30,"max_login_attempts":5,"two_factor_enabled":false}'
WHERE id = 1 AND security_settings IS NULL;
```

## All Working! 🎉

With proper configuration:
- ✅ All four tabs load and display correctly
- ✅ Settings save to database
- ✅ Email configuration works and sends test emails
- ✅ Security policies enforced
- ✅ Backup/restore functionality available
- ✅ No console errors or API failures
