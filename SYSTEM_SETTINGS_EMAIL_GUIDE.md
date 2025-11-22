# System Settings & Email Configuration Guide

## Overview
Complete guide for configuring and using all system settings tabs including email notifications, password reset, and system management.

---

## 🔧 System Settings Tabs

### 1. General Settings
Configure basic school information.

**Fields:**
- School Name
- School Code
- School Address
- School Phone
- School Email
- School Website  
- School Logo
- Academic Year Start Month (1-12)
- Academic Year End Month (1-12)
- Timezone

**API Endpoint:**
```
PUT /api/admin/settings
{
  "type": "general",
  "settings": {
    "school_name": "My School",
    "school_code": "SCH001",
    ...
  }
}
```

---

### 2. Notifications Settings
Control notification preferences for different events.

**Fields:**
- Email Notifications Enabled (boolean)
- SMS Notifications Enabled (boolean)
- Push Notifications Enabled (boolean)
- Notify on Attendance (boolean)
- Notify on Results (boolean)
- Notify on Fees (boolean)
- Notify on Complaints (boolean)

**API Endpoint:**
```
PUT /api/admin/settings
{
  "type": "notifications",
  "settings": {
    "email_enabled": true,
    "notify_attendance": true,
    ...
  }
}
```

**Use Cases:**
- Welcome emails sent when `email_enabled = true`
- Password reset emails sent when `email_enabled = true`
- Attendance notifications when `notify_attendance = true`

---

### 3. Email Settings (SMTP Configuration)
Configure email server for sending emails.

**Fields:**
- SMTP Host (e.g., smtp.gmail.com)
- SMTP Port (587 for TLS, 465 for SSL)
- SMTP Username (your email address)
- SMTP Password (app password)
- SMTP Encryption (tls or ssl)
- From Email (sender email)
- From Name (sender name)

**API Endpoints:**

**Update Settings:**
```
PUT /api/admin/settings
{
  "type": "email",
  "settings": {
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    "smtp_username": "your-email@gmail.com",
    "smtp_password": "your-app-password",
    "smtp_encryption": "tls",
    "from_email": "noreply@school.com",
    "from_name": "School Management System"
  }
}
```

**Test Email Configuration:**
```
POST /api/admin/settings/test-email
{
  "email": "test@example.com"
}
```

**Gmail Setup Instructions:**
1. Go to Google Account settings
2. Enable 2-Step Verification
3. Generate App Password (Security → App passwords)
4. Use the app password in SMTP settings

---

### 4. Security Settings
Configure password policies and security features.

**Fields:**
- Force Password Change (boolean)
- Minimum Password Length (number)
- Require Uppercase (boolean)
- Require Lowercase (boolean)
- Require Numbers (boolean)
- Require Special Characters (boolean)
- Session Timeout (minutes)
- Max Login Attempts (number)
- Two-Factor Authentication Enabled (boolean)

**API Endpoint:**
```
PUT /api/admin/settings
{
  "type": "security",
  "settings": {
    "password_min_length": 8,
    "password_require_uppercase": true,
    "session_timeout": 30,
    "max_login_attempts": 5
  }
}
```

---

### 5. System Settings
System maintenance and backup options.

**Fields:**
- Maintenance Mode (boolean)

**API Endpoints:**

**Enable/Disable Maintenance:**
```
PUT /api/admin/settings
{
  "type": "system",
  "settings": {
    "maintenance_mode": true
  }
}
```

**Create Database Backup:**
```
POST /api/admin/settings/backup
```

**Restore Database:**
```
POST /api/admin/settings/restore
{
  "filename": "backup_2025-01-01_120000.sql"
}
```

---

## 📧 Email Features

### Welcome Emails on Account Creation

**Automatically sent when:**
1. New admin registers (via `/api/admin/register`)
2. New student is registered (via `/api/students/register`)
3. New teacher is registered (via `/api/teachers/register`)
4. New parent is registered (via `/api/parents/register`)
5. Email notifications are enabled in settings

**Email Contains:**
- Welcome message
- User's role
- Login credentials (if applicable)
- Link to login page

**Implementation:**
```php
// In controller after creating user
$mailer = new \App\Utils\Mailer();
$mailer->sendWelcomeEmail(
    $email,
    $name,
    $role,
    $tempPassword // optional
);
```

---

### Password Reset System

Complete forgot password functionality for all user types.

**Supported Roles:**
- Admin
- Principal
- Student
- Teacher
- Parent

#### 1. Request Password Reset

**Endpoint:**
```
POST /api/password/forgot
{
  "email": "user@example.com",
  "role": "admin" // admin, student, teacher, parent
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password reset link has been sent to your email."
}
```

**What Happens:**
1. System generates secure random token
2. Token saved to database (expires in 1 hour)
3. Email sent with reset link
4. Link format: `http://localhost:5173/reset-password?token=TOKEN`

#### 2. Verify Reset Token

**Endpoint:**
```
GET /api/password/verify-token?token=TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "Token is valid",
  "email": "user@example.com",
  "role": "admin"
}
```

#### 3. Reset Password

**Endpoint:**
```
POST /api/password/reset
{
  "token": "TOKEN",
  "password": "newpassword123",
  "confirm_password": "newpassword123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password has been reset successfully."
}
```

**Validation:**
- Passwords must match
- Minimum 6 characters (configurable in security settings)
- Token must be valid and not expired
- Token can only be used once

---

## 🗄️ Database Tables

### password_resets
Stores password reset tokens.

```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin', 'principal', 'student', 'teacher', 'parent') NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email_role (email, role)
);
```

### email_logs
Tracks all sent emails.

```sql
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔐 Environment Configuration

Add to `backend1/.env`:

```env
# Email Configuration (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@school.com
SMTP_FROM_NAME=School Management System

# Frontend URL (for password reset links)
FRONTEND_URL=http://localhost:5173
```

---

## 🧪 Testing

### Test SMTP Connection
```bash
curl -X POST http://localhost:8080/api/email/test \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Test Email Sending
```bash
curl -X POST http://localhost:8080/api/admin/settings/test-email \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

### Test Password Reset Flow
```bash
# 1. Request reset
curl -X POST http://localhost:8080/api/password/forgot \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","role":"admin"}'

# 2. Check email for token, then verify
curl "http://localhost:8080/api/password/verify-token?token=TOKEN"

# 3. Reset password
curl -X POST http://localhost:8080/api/password/reset \
  -H "Content-Type: application/json" \
  -d '{
    "token":"TOKEN",
    "password":"newpass123",
    "confirm_password":"newpass123"
  }'
```

---

## 📱 Frontend Integration

### Password Reset Page
Create `frontend1/src/pages/ResetPassword.jsx`:

```jsx
import { useState, useEffect } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import axios from 'axios';

const ResetPassword = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [token, setToken] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  useEffect(() => {
    const tokenParam = searchParams.get('token');
    if (tokenParam) {
      setToken(tokenParam);
      verifyToken(tokenParam);
    }
  }, [searchParams]);

  const verifyToken = async (token) => {
    try {
      await axios.get(`/api/password/verify-token?token=${token}`);
    } catch (err) {
      setError(err.response?.data?.message || 'Invalid or expired token');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (password !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }
    
    setLoading(true);
    setError('');
    
    try {
      const response = await axios.post('/api/password/reset', {
        token,
        password,
        confirm_password: confirmPassword
      });
      
      setSuccess(true);
      setTimeout(() => navigate('/'), 3000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to reset password');
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="success-message">
        Password reset successfully! Redirecting to login...
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit}>
      <h2>Reset Password</h2>
      {error && <div className="error">{error}</div>}
      
      <input
        type="password"
        placeholder="New Password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        required
      />
      
      <input
        type="password"
        placeholder="Confirm Password"
        value={confirmPassword}
        onChange={(e) => setConfirmPassword(e.target.value)}
        required
      />
      
      <button type="submit" disabled={loading}>
        {loading ? 'Resetting...' : 'Reset Password'}
      </button>
    </form>
  );
};

export default ResetPassword;
```

### Forgot Password Link
Add to login pages:

```jsx
<a href="/forgot-password">Forgot Password?</a>
```

---

## 🔒 Security Best Practices

1. **Use App Passwords:** Never use actual email password in SMTP settings
2. **HTTPS Only:** Always use HTTPS in production
3. **Token Expiry:** Tokens expire in 1 hour (configurable)
4. **One-Time Use:** Tokens can only be used once
5. **Rate Limiting:** Consider adding rate limiting to prevent abuse
6. **Email Validation:** Always validate email addresses
7. **Password Strength:** Enforce minimum password requirements

---

## 🐛 Troubleshooting

### Email Not Sending

**Check:**
1. SMTP credentials are correct
2. SMTP port is not blocked by firewall
3. "Less secure app access" is enabled (for Gmail)
4. Check `email_logs` table for error messages
5. Verify email notifications are enabled in settings

**Test Connection:**
```php
$mailer = new \App\Utils\Mailer();
$result = $mailer->testConnection();
print_r($result);
```

### Password Reset Not Working

**Check:**
1. Email is being sent (check email_logs)
2. Token is not expired (check password_resets table)
3. Token has not been used already
4. Frontend URL is configured correctly in .env
5. User exists with that email and role

**Debug Query:**
```sql
SELECT * FROM password_resets WHERE email = 'user@example.com' ORDER BY created_at DESC LIMIT 1;
```

### Settings Not Saving

**Check:**
1. `school_settings` table exists
2. Required columns exist (run migrations)
3. User has admin permissions
4. JSON is valid in request body

---

## 📊 Monitoring

### Check Email Logs
```sql
-- Recent emails
SELECT * FROM email_logs ORDER BY created_at DESC LIMIT 50;

-- Failed emails
SELECT * FROM email_logs WHERE status = 'failed' ORDER BY created_at DESC;

-- Email statistics
SELECT 
  status,
  COUNT(*) as count,
  DATE(created_at) as date
FROM email_logs
GROUP BY status, DATE(created_at)
ORDER BY date DESC;
```

### Check Password Resets
```sql
-- Active reset tokens
SELECT * FROM password_resets WHERE expires_at > NOW() AND used = 0;

-- Expired tokens to clean up
SELECT * FROM password_resets WHERE expires_at < NOW() OR used = 1;
```

### Cleanup Expired Tokens
```bash
curl -X POST http://localhost:8080/api/password/cleanup \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## ✅ Feature Checklist

- [x] General settings CRUD
- [x] Notification preferences
- [x] Email SMTP configuration
- [x] Security settings
- [x] System maintenance mode
- [x] Database backup/restore
- [x] Welcome emails on registration
- [x] Password reset for all roles
- [x] Token-based reset system
- [x] Email logging
- [x] Test email functionality
- [x] Token expiry and cleanup
- [x] One-time use tokens
- [x] Multi-role support

---

## 🚀 Next Steps

1. Configure SMTP settings in System Settings tab
2. Test email by sending test email
3. Enable email notifications
4. Test password reset flow
5. Set up frontend password reset pages
6. Configure security settings
7. Set up automated token cleanup (cron job)

---

## 📞 Support

For issues or questions:
1. Check error logs: `backend1/logs/`
2. Check email logs: `SELECT * FROM email_logs`
3. Verify .env configuration
4. Test SMTP connection
5. Review this documentation

**Files Modified/Created:**
- `backend1/src/Utils/Mailer.php` - Email utility
- `backend1/src/Controllers/PasswordResetController.php` - Password reset logic
- `backend1/src/Controllers/SettingsController.php` - Enhanced with test email
- `backend1/src/Controllers/AdminController.php` - Welcome email integration
- `backend1/src/Routes/api.php` - New routes added
- `backend1/.env` - Email configuration added
