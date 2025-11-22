# Quick Fix Guide - Immediate Actions Required

## ✅ BACKEND FIXES COMPLETED

All backend issues have been resolved. The system is now fully functional on the backend.

---

## 🚀 Immediate Steps to Get System Working

### Step 1: Restart Backend Server

Stop your current backend server and restart it:

```bash
# Kill any running PHP server
# Then start fresh
cd backend1
php -S localhost:8080 -t public
```

**Why?** The .env fix requires a fresh PHP process to reload environment variables.

---

### Step 2: Clear Browser Cache

1. Open browser DevTools (F12)
2. Go to Application tab
3. Clear localStorage
4. Clear all cookies
5. Hard refresh (Ctrl+Shift+R)

**Why?** Old tokens and cached API responses may cause issues.

---

### Step 3: Login Again

1. Go to login page
2. Login with admin credentials
3. New token will be generated
4. All authenticated requests should now work

---

## 🔧 Frontend Fixes Needed

### Critical Fix #1: Double API Path

**Issue:** Frontend is calling `/api/api/notifications` instead of `/api/notifications`

**Where to Fix:** Check your axios/fetch configuration

```javascript
// ❌ Wrong - Causing 404 errors
const apiClient = axios.create({
  baseURL: 'http://localhost:8080/api'
});
apiClient.get('/api/notifications') // Results in /api/api/notifications

// ✅ Correct - Use one of these approaches:
// Option A: No baseURL, full paths
axios.get('http://localhost:8080/api/notifications')

// Option B: BaseURL without /api
const apiClient = axios.create({
  baseURL: 'http://localhost:8080'
});
apiClient.get('/api/notifications')

// Option C: BaseURL with /api, relative paths
const apiClient = axios.create({
  baseURL: 'http://localhost:8080/api'
});
apiClient.get('/notifications') // No leading /api
```

**Files to Check:**
- `frontend1/src/api/*.js` or similar API configuration files
- `frontend1/src/config.js` or `frontend1/src/constants.js`
- Any axios instance creation

---

### Fix #2: System Settings Tabs

**Issue:** Settings endpoint now requires proper authentication

**Test Backend:**
```bash
curl -X GET http://localhost:8080/api/admin/settings \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "success": true,
  "settings": {
    "general": {...},
    "notifications": {...},
    "email": {...},
    "security": {...},
    "maintenance_mode": false
  }
}
```

**Frontend Fix:**
Ensure your settings component:
1. Sends Authorization header with every request
2. Uses correct endpoint: `/api/admin/settings`
3. Handles all 5 settings sections

---

### Fix #3: Teacher Management

**Add View Classes Button:**

```jsx
// In teacher table, add column:
{
  header: 'Classes',
  cell: (teacher) => (
    <button onClick={() => viewTeacherClasses(teacher.id)}>
      View Classes
    </button>
  )
}

// Handler function:
const viewTeacherClasses = async (teacherId) => {
  const response = await api.get(`/api/teachers/${teacherId}/classes`);
  // Show modal with response.data.classes
};
```

---

### Fix #4: Currency Display

**Find and Replace:**
```javascript
// Find all instances of:
₦  // Nigerian Naira symbol

// Replace with:
SLE  // Sierra Leone Leone
```

**Common locations:**
- Financial reports components
- Fee payment forms
- Dashboard financial widgets
- Receipt printing

---

### Fix #5: Teacher Forms

**Update teacher forms to use first_name and last_name:**

```jsx
// Old form:
<input name="name" placeholder="Full Name" />

// New form:
<input name="first_name" placeholder="First Name" />
<input name="last_name" placeholder="Last Name" />

// When submitting:
const data = {
  first_name: formData.first_name,
  last_name: formData.last_name,
  // ... other fields
};
```

**CSV Template Update:**
- Column 1: first_name
- Column 2: last_name
- Remove: name column (or keep for backward compatibility)

---

## 📧 Email Configuration

### Setup SMTP in System Settings

1. Login as Admin
2. Go to System Settings → Email tab
3. Enter your SMTP details:
   - Host: `smtp.gmail.com` (for Gmail)
   - Port: `587`
   - Username: Your email
   - Password: App password (not regular password!)
   - Encryption: `TLS`

### Gmail App Password Setup

1. Go to Google Account settings
2. Security → 2-Step Verification
3. App Passwords → Generate new
4. Use generated password in SMTP settings

### Test Email

Click "Test Email" button in settings to verify configuration.

---

## 🔔 Notification System

### Frontend Implementation:

```jsx
// Get unread count (for notification bell badge)
const getUnreadCount = async () => {
  const response = await api.get('/api/notifications/unread-count');
  setUnreadCount(response.data.unread_count);
};

// Get all notifications
const getNotifications = async () => {
  const response = await api.get('/api/notifications');
  setNotifications(response.data.notifications);
};

// Mark as read
const markAsRead = async (notificationId) => {
  await api.post(`/api/notifications/${notificationId}/mark-read`);
  getUnreadCount(); // Refresh count
};
```

**Display Logic:**
- Show red badge with count if `unread_count > 0`
- Highlight unread notifications (where `is_read === 0`)
- Update count when notification is clicked

---

## 🔐 Password Reset Flow

### Add Forgot Password Link

```jsx
// On login page:
<Link to="/forgot-password">Forgot Password?</Link>

// Forgot password page:
const requestReset = async (email, role) => {
  const response = await api.post('/api/password/forgot', {
    email,
    role  // 'Admin', 'Teacher', 'Student', 'Parent'
  });
  // Show success message
};

// Reset password page (with token from email):
const resetPassword = async (token, newPassword) => {
  const response = await api.post('/api/password/reset', {
    token,
    password: newPassword
  });
  // Redirect to login
};
```

---

## 🧪 Testing Checklist

### Backend Tests (All Passing ✅)

- [x] Server starts without errors
- [x] JWT token generation works
- [x] Settings endpoint returns data
- [x] Teacher CRUD operations work
- [x] Notifications API functional
- [x] Password reset endpoints ready

### Frontend Tests (Need to Complete)

- [ ] Login works and stores token
- [ ] Settings page loads all tabs
- [ ] Settings can be saved
- [ ] Teacher list shows with split names
- [ ] Teacher add/edit forms work
- [ ] View Classes button appears
- [ ] Notification bell shows count
- [ ] Notifications can be marked as read
- [ ] Currency shows as SLE
- [ ] Forgot password link exists
- [ ] Password reset flow works

---

## 🐛 Troubleshooting

### Issue: "Invalid or expired token"

**Solution:**
1. Clear browser localStorage
2. Login again to get fresh token
3. Check token is being sent in Authorization header

### Issue: Settings page blank/error

**Solution:**
1. Check browser console for errors
2. Verify API call is to `/api/admin/settings` (not `/api/settings`)
3. Ensure token is valid

### Issue: Email not sending

**Solution:**
1. Check SMTP settings are configured
2. Test email from System Settings → Email tab
3. Check PHP error logs for mail errors
4. Verify firewall allows SMTP port

### Issue: Notification count always shows 3

**Solution:**
1. Backend is fixed - issue is frontend caching
2. Clear localStorage
3. Refresh notification count from API: `/api/notifications/unread-count`

---

## 📝 Summary of Changes

| Component | Status | Action Required |
|-----------|--------|-----------------|
| Backend | ✅ Complete | None - working |
| Database | ✅ Migrated | None - complete |
| .env Config | ✅ Fixed | Restart server |
| Auth System | ✅ Working | Re-login |
| Settings API | ✅ Fixed | Update frontend |
| Notifications | ✅ Ready | Implement frontend |
| Password Reset | ✅ Ready | Add UI |
| Teacher Names | ✅ Split | Update forms |
| Currency | ✅ Changed | Update display |

---

## 🎯 Priority Order

**Do These First:**
1. Restart backend server
2. Clear browser cache and login
3. Fix double `/api` path issue
4. Test settings page

**Then Do:**
5. Update teacher forms for name splitting
6. Add View Classes button
7. Implement notification UI
8. Change currency to SLE
9. Add forgot password UI
10. Configure SMTP email

---

## 💡 Tips

- **Always check browser console** for API errors
- **Use Network tab** to see actual API requests
- **Token format:** `Bearer <token>` in Authorization header
- **All endpoints return JSON** with `success` field
- **Error responses** include `message` field

---

## 📞 Next Steps

1. **Immediate:** Restart backend, clear cache, re-login
2. **Short Term:** Fix frontend API paths and forms
3. **Medium Term:** Implement new features (notifications, password reset)
4. **Long Term:** Add PDF export and enhanced analytics

---

**Status:** ✅ Backend Ready | ⚠️ Frontend Updates Needed

**Last Updated:** 2025-11-21
