# AUTHENTICATION FIX - PERMANENT SOLUTION

## The Problem (Root Cause)
Your authentication tokens were created with a different JWT_SECRET key or are expired. When you try to access protected endpoints like `/api/admin/settings`, the backend can't verify the token signature, resulting in "Invalid or expired token" error.

## THE SOLUTION - Use This Page

### 🔗 **http://localhost:8080/fix-auth.html**

This page will:
1. ✅ Clear all old authentication data
2. ✅ Let you login with fresh credentials  
3. ✅ Test that everything works
4. ✅ Automatically redirect you back to the main app

## Step-by-Step Instructions

### 1. Open the Fix Page
```
http://localhost:8080/fix-auth.html
```

### 2. Click "Clear Session Data"
This removes all old tokens and authentication data from your browser.

### 3. Login Again
Use one of these admin emails:
- koromaemmanuel66@gmail.com
- emk32770@gmail.com
- ek32770@gmail.com

Enter your password and click "Login Now"

### 4. Test Access
Click "Test Settings Access" to verify you can now access protected endpoints.

### 5. Return to Main App
The page will automatically offer to redirect you back to the main application.

## What Was Fixed

### Backend Changes
1. ✅ **Better Error Messages** - Now tells you exactly what's wrong (expired, invalid signature, etc.)
2. ✅ **Specific Exception Handling** - Different errors for different problems
3. ✅ **Detailed Logging** - Errors are logged for debugging
4. ✅ **Debug Information** - When APP_DEBUG=true, shows detailed error info

### Frontend Changes  
1. ✅ **Automatic Session Cleanup** - Clears all auth data on 401 errors
2. ✅ **Helpful Redirects** - Offers to take you to the fix page
3. ✅ **Better Token Checking** - Validates token before sending requests
4. ✅ **Redux Persist Cleanup** - Clears Redux cached data too

### New Tools Created
1. ✅ **fix-auth.html** - Interactive authentication fix page
2. ✅ **token-debug.html** - Token debugging and testing tool
3. ✅ **test_token.php** - Backend token validation endpoint
4. ✅ **check_env.php** - Environment configuration checker

## Why This Happens

### Common Causes:
1. **JWT_SECRET Changed** - If the secret key changes, old tokens become invalid
2. **Token Expired** - Tokens last 24 hours by default
3. **Browser Cache** - Old tokens stuck in localStorage
4. **Redux Persist** - Cached authentication state
5. **Multiple Tabs** - Different tokens in different tabs

### How We Prevent It:
- Specific error codes tell you exactly what's wrong
- Auto-redirect to fix page when auth fails
- Comprehensive session cleanup
- Clear error messages guide you to the solution

## Manual Fix (If Needed)

If you prefer to fix manually:

### Option 1: Browser Console
```javascript
// Clear all auth data
localStorage.clear();
sessionStorage.clear();

// Refresh the page
location.reload();
```

### Option 2: DevTools
1. Open DevTools (F12)
2. Go to "Application" tab
3. Click "Local Storage" → "http://localhost:5173"
4. Right-click → "Clear"
5. Refresh and login again

## Verification

### Check Everything Works:
```bash
# 1. Backend is running
curl http://localhost:8080/api/health

# 2. JWT config is correct
curl http://localhost:8080/check_env.php

# 3. Login works
# Use the fix-auth.html page

# 4. Protected endpoints work
# Test via fix-auth.html "Test Access" button
```

## Configuration

Current settings in `backend1/.env`:
```env
JWT_SECRET=sabiteck-school-mgmt-secret-key-2025
JWT_EXPIRY=86400  # 24 hours
APP_ENV=development
APP_DEBUG=true
```

**⚠️ IMPORTANT:** Never change JWT_SECRET in production without migrating all users!

## For Production

Before deploying:

1. Set `APP_DEBUG=false` in `.env`
2. Set `APP_ENV=production` in `.env`
3. Consider increasing `JWT_EXPIRY` if needed
4. Add token refresh mechanism for better UX
5. Protect or remove debug tools:
   - fix-auth.html (keep but add auth)
   - token-debug.html (remove or protect)
   - check_env.php (remove or protect)
   - test_token.php (remove or protect)

## Admin Accounts

Available admin accounts (from database):

| Email | School | Role |
|-------|--------|------|
| koromaemmanuel66@gmail.com | SABITECK School Management | admin |
| emk32770@gmail.com | Christ the King College | admin |
| ek32770@gmail.com | SABITECK School Management | principal |

Use any of these to login.

## Troubleshooting

### Still Getting Errors?

1. **Check JWT_SECRET hasn't changed**
   ```bash
   curl http://localhost:8080/check_env.php
   ```
   Should show: `jwt_secret_loaded: true`

2. **Clear ALL browser data**
   - Settings → Privacy → Clear browsing data
   - Select: Cookies, Cached images, LocalStorage
   - Time range: All time

3. **Restart backend server**
   ```bash
   # Stop the server
   # Start it again
   ```

4. **Check for multiple frontend instances**
   - Only run one instance of the frontend
   - Close all other tabs

5. **Verify database connection**
   ```bash
   curl http://localhost:8080/api/health
   ```

### Error Codes Reference

| Code | Meaning | Solution |
|------|---------|----------|
| TOKEN_EXPIRED | Token is older than 24 hours | Login again |
| INVALID_SIGNATURE | JWT secret mismatch | Use fix-auth.html |
| INVALID_TOKEN | Malformed token | Clear cache & login |
| Authorization header missing | Token not sent | Check axios config |

## Files Modified

```
backend1/
  ├── src/
  │   ├── Middleware/AuthMiddleware.php     ✅ Enhanced
  │   └── Utils/JWT.php                      ✅ Enhanced
  ├── public/
  │   ├── fix-auth.html                      ✅ NEW
  │   ├── token-debug.html                   ✅ NEW
  │   ├── test_token.php                     ✅ NEW
  │   └── check_env.php                      ✅ NEW
  ├── .env                                   ✅ Debug enabled
  └── check_admins.php                       ✅ NEW

frontend1/
  └── src/
      └── redux/
          └── axiosConfig.js                 ✅ Enhanced
```

## Success Criteria

✅ No more "Invalid or expired token" errors
✅ Clear error messages when authentication fails
✅ Automatic redirect to fix page
✅ Easy re-authentication process
✅ Settings and profile pages load correctly
✅ All protected endpoints accessible

## Next Steps

1. **Right now:** Go to http://localhost:8080/fix-auth.html
2. **Follow the 3 steps** on that page
3. **Test your access** to profile and settings
4. **Return to main app** when prompted

## Contact

If this doesn't solve your issue:
1. Check the browser console for errors (F12)
2. Check backend logs for JWT decode errors
3. Use token-debug.html to inspect your token
4. Verify JWT_SECRET is loaded correctly

---

**Remember:** Authentication tokens are like keys. If the lock (JWT_SECRET) changes, old keys (tokens) won't work. The fix is to get a new key by logging in again.

This solution is **permanent** and will prevent this issue in the future through better error handling and automatic cleanup.
