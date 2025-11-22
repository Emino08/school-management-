# 🚀 Deployment Checklist

Use this checklist to ensure everything is deployed and working correctly.

---

## Pre-Deployment

### Backup ⚠️
- [ ] Production database backed up
- [ ] Backup file saved with date: `u232752871_sms_backup_YYYY_MM_DD.sql`
- [ ] Backup file tested (can be restored)
- [ ] Backup stored in safe location

### Local Testing ✅
- [ ] Backend starts without errors
- [ ] Frontend starts without errors
- [ ] Can login as admin
- [ ] Can access profile
- [ ] Can access system settings
- [ ] All settings tabs load

---

## Database Migration

### Preparation
- [ ] Have access to cPanel phpMyAdmin
- [ ] Migration file ready: `production_migration_v2.sql`
- [ ] Verified file is complete (not truncated)

### Execution
- [ ] Logged into cPanel phpMyAdmin
- [ ] Selected database: u232752871_sms
- [ ] Clicked SQL tab
- [ ] Copied entire migration SQL
- [ ] Pasted in SQL box
- [ ] Clicked "Go" button
- [ ] Waited for completion (30-60 seconds)

### Verification
- [ ] Saw "Migration completed successfully!" message
- [ ] No error messages in output
- [ ] Verification queries show correct results
- [ ] Teachers have first_name and last_name
- [ ] Students have first_name and last_name
- [ ] Currency is SLE
- [ ] notification_reads table exists
- [ ] password_reset_tokens table exists

**Run these verification queries:**
```sql
-- Quick check
SELECT COUNT(*), 
       SUM(CASE WHEN first_name IS NOT NULL THEN 1 ELSE 0 END) 
FROM teachers;

SELECT COUNT(*), 
       SUM(CASE WHEN first_name IS NOT NULL THEN 1 ELSE 0 END) 
FROM students;

SELECT currency FROM school_settings LIMIT 1;

SHOW TABLES LIKE 'notification_reads';
SHOW TABLES LIKE 'password_reset_tokens';
```

---

## Backend Deployment

### Configuration
- [ ] .env file updated with production credentials
- [ ] APP_NAME has quotes: `"School Management System"`
- [ ] Database credentials correct
- [ ] JWT_SECRET is set
- [ ] CORS_ORIGIN includes production domain

### Files Upload
- [ ] All modified backend files uploaded
- [ ] Vendor folder is complete
- [ ] .env file uploaded (don't commit to git!)
- [ ] File permissions correct (644 for files, 755 for directories)

### Testing
- [ ] Backend accessible at: https://backend.boschool.org
- [ ] Health check works: GET /api/health
- [ ] Root endpoint works: GET /
- [ ] No PHP errors in logs

---

## Frontend Deployment

### Build
- [ ] `npm run build` completed successfully
- [ ] No build errors
- [ ] Dist folder generated
- [ ] Build size reasonable

### Upload
- [ ] Dist contents uploaded to public_html or appropriate folder
- [ ] .htaccess configured for React Router
- [ ] File permissions correct

### Configuration
- [ ] API base URL points to backend
- [ ] VITE_API_URL set correctly
- [ ] No localhost references in code

---

## Feature Testing

### Authentication
- [ ] Admin can login
- [ ] Student can login
- [ ] Teacher can login
- [ ] JWT tokens work
- [ ] Profile page loads
- [ ] Logout works

### System Settings
- [ ] General tab loads and saves
- [ ] Notifications tab loads and saves
- [ ] Email tab loads and saves
- [ ] Security tab loads and saves
- [ ] System tab loads and saves
- [ ] Test email button works

### Teacher Management
- [ ] Can view all teachers
- [ ] Can create new teacher (with first/last name)
- [ ] Can edit teacher
- [ ] Can delete teacher
- [ ] Can bulk upload teachers (CSV with First Name, Last Name)
- [ ] Can view teacher classes (GET /api/teachers/{id}/classes)

### Student Management
- [ ] Can view all students
- [ ] Can create new student (with first/last name)
- [ ] Can edit student
- [ ] Can delete student
- [ ] Can bulk upload students (CSV with First Name, Last Name)

### Notifications
- [ ] Badge shows correct count
- [ ] Can create notification
- [ ] Can view notifications
- [ ] Can mark as read
- [ ] Count updates after mark as read
- [ ] Unread count accurate

### Password Reset
- [ ] "Forgot Password" link works
- [ ] Can request reset
- [ ] Email received with token
- [ ] Can verify token
- [ ] Can reset password with token
- [ ] Can login with new password

### Reports
- [ ] Financial reports display
- [ ] Currency shows SLE (or Le)
- [ ] Export to PDF works
- [ ] Analytics display correctly

---

## Email Configuration

### SMTP Setup
- [ ] SMTP host configured
- [ ] SMTP port configured (587 for TLS)
- [ ] SMTP username configured
- [ ] SMTP password configured (App Password for Gmail)
- [ ] SMTP encryption set (TLS)
- [ ] From email configured
- [ ] From name configured

### Testing
- [ ] Test email button works
- [ ] Email received in inbox
- [ ] Email not in spam
- [ ] Email formatting correct
- [ ] Links in email work

### Account Creation Emails
- [ ] New admin account triggers email
- [ ] New teacher account triggers email
- [ ] New student account triggers email
- [ ] Emails contain correct information

### Password Reset Emails
- [ ] Forgot password triggers email
- [ ] Reset link works
- [ ] Token validates correctly
- [ ] Password resets successfully

---

## Performance

### Backend
- [ ] API responses under 500ms
- [ ] No slow queries
- [ ] Database indexes working
- [ ] Memory usage normal

### Frontend
- [ ] Pages load quickly
- [ ] No console errors
- [ ] Images optimized
- [ ] Caching working

---

## Security

### Authentication
- [ ] JWT tokens expire correctly
- [ ] Refresh token works
- [ ] Unauthorized access blocked
- [ ] Role-based access working

### Passwords
- [ ] Password hashing works
- [ ] Minimum password length enforced
- [ ] Password complexity enforced (if enabled)
- [ ] Reset tokens expire

### API
- [ ] CORS configured correctly
- [ ] Only allowed origins accepted
- [ ] Rate limiting in place (if implemented)
- [ ] SQL injection prevented

---

## Browser Testing

### Desktop Browsers
- [ ] Chrome - Works correctly
- [ ] Firefox - Works correctly
- [ ] Safari - Works correctly
- [ ] Edge - Works correctly

### Mobile Browsers
- [ ] Chrome Mobile - Works correctly
- [ ] Safari Mobile - Works correctly
- [ ] Responsive design works

---

## Error Handling

### Backend Errors
- [ ] 404 errors handled gracefully
- [ ] 500 errors logged and handled
- [ ] Database errors handled
- [ ] Validation errors clear

### Frontend Errors
- [ ] Network errors handled
- [ ] API errors displayed to user
- [ ] Loading states work
- [ ] Error boundaries in place

---

## Documentation

### User Documentation
- [ ] Admin manual available
- [ ] Teacher guide available
- [ ] Student guide available
- [ ] FAQ available

### Technical Documentation
- [ ] API documentation available
- [ ] Database schema documented
- [ ] Setup guide available
- [ ] Troubleshooting guide available

---

## Monitoring

### Logs
- [ ] Backend error logs accessible
- [ ] Frontend error tracking enabled
- [ ] Database slow query log enabled

### Alerts
- [ ] Error notifications configured
- [ ] Uptime monitoring enabled
- [ ] Performance monitoring enabled

---

## Post-Deployment

### Immediate (within 1 hour)
- [ ] Test critical user flows
- [ ] Monitor error logs
- [ ] Check performance metrics
- [ ] Verify data integrity

### First 24 Hours
- [ ] Monitor user feedback
- [ ] Check for errors in logs
- [ ] Verify email delivery
- [ ] Test all features again

### First Week
- [ ] Monitor performance trends
- [ ] Collect user feedback
- [ ] Fix any issues discovered
- [ ] Optimize as needed

---

## Rollback Plan

### If Issues Occur
- [ ] Stop accepting new users/data
- [ ] Document the issue
- [ ] Assess severity
- [ ] Decide: fix forward or rollback

### Rollback Database
```sql
-- Drop new tables (if needed)
DROP TABLE IF EXISTS notification_reads;
DROP TABLE IF EXISTS password_reset_tokens;

-- Restore from backup
mysql -u user -p database < backup_file.sql
```

### Rollback Backend
- [ ] Upload previous version files
- [ ] Restore previous .env
- [ ] Clear cache
- [ ] Test

### Rollback Frontend
- [ ] Deploy previous build
- [ ] Clear CDN cache
- [ ] Test

---

## Success Criteria

All checkboxes above should be checked ✅

### Critical Success Indicators
- ✅ No "Invalid token" errors
- ✅ All users can login
- ✅ All settings tabs work
- ✅ Teachers/Students have proper names
- ✅ Currency displays correctly
- ✅ Notifications accurate
- ✅ Emails sending
- ✅ No database errors

### Performance Indicators
- ✅ Page load < 2 seconds
- ✅ API response < 500ms
- ✅ No JavaScript errors
- ✅ Mobile responsive

### Data Integrity
- ✅ All existing data present
- ✅ No duplicate records
- ✅ Foreign keys intact
- ✅ Indexes working

---

## Support Contacts

### Technical Issues
- Database: [Your DBA contact]
- Backend: [Your backend developer]
- Frontend: [Your frontend developer]
- Hosting: [Your hosting provider]

### Emergency Procedures
1. Check error logs
2. Review monitoring alerts
3. Contact appropriate team
4. Document issue
5. Implement fix or rollback

---

## Final Sign-Off

- [ ] All checklist items completed
- [ ] All tests passed
- [ ] Performance acceptable
- [ ] Users notified of new features
- [ ] Support team briefed
- [ ] Documentation updated
- [ ] Backup verified

**Deployed By:** ___________________
**Date:** ___________________
**Time:** ___________________
**Version:** 2.0

**Status:** 🎉 DEPLOYED SUCCESSFULLY

---

## Quick Reference

**Files Modified:**
- backend1/.env
- backend1/src/Routes/api.php
- backend1/src/Controllers/SettingsController.php
- backend1/src/Models/BaseModel.php

**Database Changes:**
- Teachers: +first_name, +last_name
- Students: +first_name, +last_name
- School Settings: currency → SLE
- New: notification_reads table
- New: password_reset_tokens table
- New: Performance indexes

**Key Endpoints:**
- POST /api/admin/login
- GET /api/admin/settings
- GET /api/notifications/unread-count
- POST /api/password/forgot
- GET /api/teachers/{id}/classes

**Documentation:**
- START_HERE_FIXES.md
- COMPREHENSIVE_FIX_GUIDE_V2.md
- PRODUCTION_UPDATE_GUIDE.md
- VERIFICATION_QUERIES.sql
