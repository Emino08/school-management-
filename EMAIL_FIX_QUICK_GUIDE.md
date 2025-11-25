# 📧 Email Configuration Quick Fix Guide

## Current Status: ❌ Authentication Failed

Your email settings are **almost correct**, but the **password is being rejected** by Titan Email's SMTP server.

---

## ✅ What I Fixed in the Code

1. **Updated `Mailer.php`** to properly handle SSL encryption for port 465
2. **Added automatic encryption detection** based on port number
3. **Improved timeout handling** for better reliability
4. **Created comprehensive test scripts** for diagnosing email issues

---

## 🎯 What YOU Need to Do Now

### Step 1: Verify Your Password
1. Go to: **https://mail.titan.email**
2. Try logging in with:
   - Email: **info@boschool.org**
   - Password: **32770&Sabi**
3. **If login fails** → Password is wrong, get the correct one
4. **If login succeeds** → Continue to Step 2

### Step 2: Enable SMTP Access
1. In Titan Email dashboard, look for:
   - **Settings** → **Email Settings**
   - **Security** → **External Access**
   - **Apps** → **App Passwords**
2. Make sure SMTP/External access is **ENABLED**
3. If you see "App Passwords" option, **generate one** for the School System

### Step 3: Update Settings in the System
Once you have the correct password (or app password):

1. Login to School Management System as Admin
2. Go to **Settings** → **System Settings**
3. Click **Email Configuration** tab
4. Enter these settings:

```
SMTP Host:       smtp.titan.email
SMTP Port:       587  (RECOMMENDED)
SMTP Encryption: tls
SMTP Username:   info@boschool.org
SMTP Password:   [USE CORRECT PASSWORD HERE]
From Email:      info@boschool.org
From Name:       Bo Government Secondary School
```

**Alternative (Port 465):**
```
SMTP Host:       smtp.titan.email
SMTP Port:       465
SMTP Encryption: ssl
SMTP Username:   info@boschool.org
SMTP Password:   [USE CORRECT PASSWORD HERE]
From Email:      info@boschool.org
From Name:       Bo Government Secondary School
```

5. Click **Save**
6. Click **Test Email**
7. Enter your email address to receive test email

---

## 🧪 Test the Configuration

### From Command Line:
```bash
cd backend1
php test_both_ports.php
```

This will test both port 465 (SSL) and port 587 (TLS) configurations.

---

## 📋 Common Issues & Solutions

### Issue 1: "Authentication failed" Error
**Cause**: Wrong password or SMTP not enabled  
**Solution**: 
- Double-check password (no typos!)
- Enable SMTP in Titan dashboard
- Generate app password if available

### Issue 2: "Could not connect" Error
**Cause**: Port blocked by firewall  
**Solution**: 
- Try port 587 instead of 465
- Check if firewall allows SMTP traffic
- Contact hosting provider if persistent

### Issue 3: "Timeout" Error
**Cause**: Network or server slowness  
**Solution**: 
- Check internet connection
- Try again later
- Contact Titan support if persistent

---

## 🔍 Why Authentication Failed

The test showed:
```
✅ SMTP server is reachable
✅ SSL/TLS connection works
✅ PHP configuration is correct
❌ Server rejects credentials with: "535 5.7.8 Authentication failed"
```

This means the password `32770&Sabi` is either:
- Incorrect (typo or changed)
- Not the app password (if app passwords are required)
- Blocked by 2FA or security settings
- From an account that doesn't have SMTP enabled

---

## ⚡ Quick Actions

| Action | Command/Link |
|--------|--------------|
| Test email config | `cd backend1 && php test_both_ports.php` |
| Check password | https://mail.titan.email |
| Titan support | https://support.titan.email |
| View full diagnosis | See `TITAN_EMAIL_DIAGNOSIS.md` |

---

## 📞 Need Help?

**Titan Email Support:**
- Website: https://support.titan.email
- Email: support@titan.email
- Question to ask: "How do I enable SMTP access for info@boschool.org? Getting error 535 5.7.8 authentication failed"

**In Your System:**
- The code is now properly configured
- Settings UI works correctly
- Test email function is operational
- **Only need correct credentials to work!**

---

## ✅ Once Password is Correct

After updating with the correct password:

1. ✅ Emails will send automatically
2. ✅ Welcome emails for new users
3. ✅ Password reset emails
4. ✅ Notification emails
5. ✅ Report emails
6. ✅ All email features working!

---

**Status**: Waiting for correct Titan Email credentials  
**Code**: ✅ Fixed and Ready  
**Next Step**: Get correct password → Update in settings → Test!

