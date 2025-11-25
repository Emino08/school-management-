# Titan Email Configuration - Diagnosis Report

## ❌ ISSUE IDENTIFIED: Authentication Failed

**Date**: November 22, 2025  
**Email Account**: info@boschool.org  
**Provider**: Titan Email (smtp.titan.email)

---

## 🔍 Test Results

### Connection Tests Performed

#### Test 1: Port 465 with SSL
- **Status**: ❌ FAILED - Authentication Error
- **Server Response**: `535 5.7.8 Error: authentication failed`
- **Details**: SMTP server is reachable, but credentials rejected

#### Test 2: Port 587 with TLS (STARTTLS)
- **Status**: ❌ FAILED - Authentication Error
- **Server Response**: `535 5.7.8 Error: authentication failed`
- **Details**: SMTP server is reachable, but credentials rejected

---

## ✅ What's Working

1. ✅ **SMTP Server is reachable** - smtp.titan.email responds on both ports
2. ✅ **PHP OpenSSL extension is working** - SSL/TLS connections successful
3. ✅ **Network/Firewall allows SMTP** - No connection timeouts
4. ✅ **Code configuration is correct** - PHPMailer setup is proper

---

## ❌ What's NOT Working

**AUTHENTICATION IS FAILING**

The SMTP server explicitly rejects the credentials with error:
```
535 5.7.8 Error: authentication failed: UGFzc3dvcmQ6
```

---

## 🎯 ROOT CAUSE

The authentication failure on **BOTH ports** indicates one of these issues:

### 1. **Incorrect Password** (Most Likely)
- The password `32770&Sabi` may not be correct
- There may be a typo or extra characters
- The password may have been changed

### 2. **SMTP Access Not Enabled**
- Titan Email account may have SMTP disabled
- External/third-party app access may be blocked
- Account settings may require explicit SMTP enablement

### 3. **App-Specific Password Required**
- Titan may require generating an app-specific password
- Regular account password may not work for SMTP
- Check Titan dashboard for "App Passwords" feature

### 4. **Two-Factor Authentication** 
- If 2FA is enabled, regular password won't work
- Must use app password or authentication token
- Check Titan settings for 2FA status

### 5. **Account Status Issues**
- Account may be suspended or locked
- Billing/subscription may have lapsed
- Account may be in trial mode with SMTP disabled

---

## 📋 IMMEDIATE ACTION REQUIRED

### Step 1: Verify Password
```
1. Go to: https://mail.titan.email
2. Try logging in with:
   Email:    info@boschool.org
   Password: 32770&Sabi
3. If login FAILS → Password is incorrect, get the correct one
4. If login SUCCEEDS → Continue to Step 2
```

### Step 2: Check SMTP Settings in Titan Dashboard
```
1. Log into Titan Email control panel
2. Navigate to Settings → Email Settings
3. Look for:
   - "SMTP Access" or "External Access"
   - "Enable SMTP" checkbox
   - "App Passwords" or "Application Passwords"
4. Enable SMTP if it's disabled
5. Generate an App Password if required
```

### Step 3: Create App Password (if available)
```
1. In Titan dashboard, find "App Passwords" or "External Apps"
2. Create new app password for "School Management System"
3. Copy the generated password
4. Use THIS password instead of the regular account password
```

### Step 4: Contact Titan Support (if needed)
```
Support: https://support.titan.email
Question: "How do I enable SMTP access for info@boschool.org?"
Mention: Getting authentication error 535 5.7.8
```

---

## 🔧 System Configuration

Once you have the **correct** credentials, use these settings:

### Recommended: Port 587 with TLS
```
SMTP Host:       smtp.titan.email
SMTP Port:       587
SMTP Encryption: tls
SMTP Username:   info@boschool.org
SMTP Password:   [GET CORRECT PASSWORD OR APP PASSWORD]
From Email:      info@boschool.org
From Name:       Bo Government Secondary School
```

### Alternative: Port 465 with SSL
```
SMTP Host:       smtp.titan.email
SMTP Port:       465
SMTP Encryption: ssl
SMTP Username:   info@boschool.org
SMTP Password:   [GET CORRECT PASSWORD OR APP PASSWORD]
From Email:      info@boschool.org
From Name:       Bo Government Secondary School
```

---

## 🧪 How to Test After Getting Correct Credentials

Run this command from project root:
```bash
php backend1/test_both_ports.php
```

Or test through the system UI:
```
1. Login as Admin
2. Go to Settings → System Settings
3. Click "Email Configuration" tab
4. Enter the correct credentials
5. Click "Test Email" button
6. Enter a test email address
7. Check if email is received
```

---

## 📊 Technical Details

### Server Information
- **SMTP Server**: smtp-out.flockmail.com (Titan's backend)
- **Supported Auth Methods**: PLAIN, LOGIN
- **Max Message Size**: 52,428,800 bytes (50 MB)
- **Supported Features**: PIPELINING, STARTTLS, 8BITMIME, CHUNKING

### Connection Flow (Both Ports)
1. ✅ TCP connection established
2. ✅ SMTP handshake successful
3. ✅ TLS/SSL negotiation successful (port 587/465)
4. ✅ AUTH LOGIN initiated
5. ❌ **Credentials rejected with 535 error**

---

## 💡 Common Titan Email Issues

### Issue: "Authentication failed" error
**Solutions**:
- Verify password is correct (no typos)
- Check if SMTP is enabled in Titan dashboard
- Generate and use app-specific password
- Disable 2FA temporarily to test

### Issue: Password with special characters
**Note**: The `&` character in `32770&Sabi` is NOT the issue
- SMTP AUTH uses base64 encoding
- Special characters are properly encoded
- The password format is valid

### Issue: Account suspended/locked
**Check**:
- Log into webmail to verify account is active
- Check billing status
- Contact Titan support if account is locked

---

## 📝 Summary

| Item | Status |
|------|--------|
| SMTP Server Reachable | ✅ YES |
| Port 465 (SSL) | ✅ Working |
| Port 587 (TLS) | ✅ Working |
| PHP Configuration | ✅ Correct |
| Code Implementation | ✅ Correct |
| **Authentication** | ❌ **FAILING** |

**NEXT STEP**: Get the correct password or app password from Titan Email, then test again.

---

**Created**: November 22, 2025  
**Status**: Awaiting correct credentials
