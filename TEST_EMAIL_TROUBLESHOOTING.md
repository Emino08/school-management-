# Test Email Troubleshooting Guide

## Current Status
The frontend keeps showing "Sending..." - this means the request is either:
1. Taking too long to process
2. Not reaching the backend
3. Backend is not responding properly

## Fixed Issues
✅ Moved `setSendingTestEmail(false)` inside try-catch-finally to ensure it always executes
✅ Added comprehensive error logging to backend
✅ Added better error handling for failed responses

## How to Debug

### Step 1: Check Browser Console
1. Open browser DevTools (F12)
2. Go to Console tab
3. Try sending test email
4. Look for any errors or the actual API response

### Step 2: Check Network Tab
1. Open browser DevTools (F12)
2. Go to Network tab
3. Try sending test email
4. Look for the request to `/admin/settings/test-email`
5. Check the response status and body

### Step 3: Check Backend Logs
Run this command to see backend errors:
```bash
cd backend1
tail -f logs/app.log
```

Or check PHP error log:
```bash
tail -f /var/log/php_errors.log
```

### Step 4: Test Email Directly with PHP Script
```bash
cd backend1
php test_email.php
```
This will:
- Test SMTP connection
- Send a test email
- Show detailed error messages

## Configuring Email Settings

### Option 1: Via Database (Recommended)
1. Login as Admin
2. Go to System Settings → Email tab
3. Fill in:
   - **SMTP Host**: smtp.gmail.com (or your provider)
   - **SMTP Port**: 587 (for TLS) or 465 (for SSL)
   - **SMTP Username**: your-email@gmail.com
   - **SMTP Password**: Your app password (not regular password!)
   - **From Email**: noreply@yourschool.com
   - **From Name**: Your School Name
4. Click "Save Changes"

### Option 2: Via .env File
Edit `backend1/.env`:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@school.com
SMTP_FROM_NAME='School Management System'
```

## Gmail App Password Setup
If using Gmail, you MUST use an App Password:

1. Go to https://myaccount.google.com/security
2. Enable 2-Step Verification if not already enabled
3. Go to App passwords
4. Generate a new app password for "Mail"
5. Copy the 16-character password
6. Use this password in your SMTP settings

## Common Issues

### Issue 1: "SMTP Connection Failed"
**Cause**: Wrong SMTP settings or firewall blocking
**Solution**: 
- Verify SMTP host and port
- Check if your server/firewall allows outbound SMTP connections
- Try port 465 with SSL instead of 587 with TLS

### Issue 2: "Authentication Failed"
**Cause**: Wrong username/password
**Solution**:
- For Gmail, use App Password (not your regular password)
- Verify credentials are correct

### Issue 3: Request timeout
**Cause**: Server taking too long or not responding
**Solution**:
- Check backend is running: `cd backend1 && php -S localhost:8080 -t public`
- Check backend logs for errors
- Verify API route exists in `backend1/src/Routes/api.php`

### Issue 4: "Failed to send test email"
**Cause**: Email service rejected the email
**Solution**:
- Check backend logs for detailed error
- Verify "From Email" is valid
- Some providers require verified sender addresses

## Testing Checklist

- [ ] Backend server is running
- [ ] Database connection works
- [ ] SMTP settings are saved in database or .env
- [ ] SMTP credentials are correct
- [ ] App password is used for Gmail
- [ ] Port 587/465 is not blocked by firewall
- [ ] Browser console shows no errors
- [ ] Network tab shows successful API response

## Next Steps

1. **Provide your working email configuration** so I can help you set it up properly
2. **Check browser console** for any JavaScript errors
3. **Check network tab** to see the actual API response
4. **Run the test script** (`php test_email.php`) to test directly

Please share:
- Your SMTP provider (Gmail, Outlook, etc.)
- SMTP Host
- SMTP Port
- Whether you're using App Password
- Any error messages you see
