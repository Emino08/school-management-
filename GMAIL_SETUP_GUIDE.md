# Setting Up Email with koromaemmanuel66@gmail.com

## ⚠️ Important: Gmail App Password Required

To send emails using Gmail, you MUST use an **App Password**, not your regular Gmail password.

## Steps to Get Gmail App Password:

### 1. Enable 2-Step Verification (if not already enabled)
   - Go to https://myaccount.google.com/security
   - Look for "2-Step Verification"
   - Click and follow instructions to enable it

### 2. Generate App Password
   - After enabling 2-Step Verification
   - Go back to https://myaccount.google.com/security
   - Scroll down to "2-Step Verification"
   - Click on "App passwords"
   - Select "Mail" as the app
   - Select "Other" as the device and name it "School Management System"
   - Click "Generate"
   - Copy the 16-character password (it looks like: xxxx xxxx xxxx xxxx)

### 3. Configure in the System

#### Option A: Via Web Interface (Easiest)
1. Login to the system as Admin using koromaemmanuel66@gmail.com
2. Go to **System Settings** → **Email** tab
3. Fill in:
   - **SMTP Host**: `smtp.gmail.com`
   - **SMTP Port**: `587`
   - **SMTP Username**: `koromaemmanuel66@gmail.com`
   - **SMTP Password**: `[Your 16-character App Password]`
   - **SMTP Encryption**: `tls`
   - **From Email**: `koromaemmanuel66@gmail.com`
   - **From Name**: `School Management System`
4. Click "Save Changes"
5. Click "Test Email"

#### Option B: Update Database Directly
Run this SQL (replace APP_PASSWORD with your actual app password):

```sql
UPDATE school_settings 
SET email_settings = JSON_OBJECT(
    'smtp_host', 'smtp.gmail.com',
    'smtp_port', 587,
    'smtp_username', 'koromaemmanuel66@gmail.com',
    'smtp_password', 'YOUR_APP_PASSWORD_HERE',
    'smtp_encryption', 'tls',
    'from_email', 'koromaemmanuel66@gmail.com',
    'from_name', 'School Management System'
)
WHERE id = 1;
```

#### Option C: Update .env File
Edit `backend1/.env`:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=koromaemmanuel66@gmail.com
SMTP_PASSWORD=your-16-char-app-password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=koromaemmanuel66@gmail.com
SMTP_FROM_NAME='School Management System'
```

## Testing

### Test 1: Via Web Interface
1. Login as Admin
2. Go to System Settings → Email
3. Click "Test Email" button
4. Enter recipient email
5. Click "Send Test Email"

### Test 2: Via Command Line
```bash
cd backend1
php test_gmail_direct.php
```
Enter recipient email when prompted.

## Common Gmail Issues

### Issue: "Username and Password not accepted"
**Solution**: You're using your regular password instead of App Password. Generate and use an App Password.

### Issue: "Less secure app access"
**Solution**: Google deprecated this. You MUST use App Password with 2-Step Verification.

### Issue: "Daily sending limit exceeded"
**Solution**: Gmail has a limit of 500 emails per day for free accounts.

## Security Notes

⚠️ **Never share your App Password publicly**
⚠️ **App Passwords bypass 2-Step Verification for that specific app**
⚠️ **You can revoke App Passwords anytime from your Google Account**

## What I Need From You

To help you test, please:

1. **Generate the App Password** following the steps above
2. **Either**:
   - Give me the App Password (via secure method) so I can test it
   - OR configure it yourself via the web interface
   - OR update the database/env file yourself

3. **Then we can test** to confirm emails are working

Once configured, the test email feature will work perfectly!
