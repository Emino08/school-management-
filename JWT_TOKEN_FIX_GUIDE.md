# JWT Authentication Token Fix

## Problem
Users logged in as admin were receiving "Invalid or expired token" error when accessing profile or system settings endpoints, even though they were successfully authenticated.

## Root Cause Analysis
The issue could be caused by several factors:
1. JWT token expiration (24-hour default expiry)
2. Token not being properly sent with requests
3. Token corruption or improper storage in localStorage
4. JWT secret key mismatch

## Solutions Implemented

### 1. Enhanced JWT Error Handling (Backend)

**File: `backend1/src/Middleware/AuthMiddleware.php`**

Added specific exception handling for different JWT errors:
- `ExpiredException`: Token has expired
- `SignatureInvalidException`: Invalid token signature
- Generic exceptions: Other token validation errors

Now returns detailed error messages with error codes:
- `TOKEN_EXPIRED`
- `INVALID_SIGNATURE`
- `INVALID_TOKEN`

Debug information is included when `APP_DEBUG=true`.

### 2. Improved JWT Utility (Backend)

**File: `backend1/src/Utils/JWT.php`**

- Added validation for JWT_SECRET configuration
- Re-throw specific exceptions for better error handling
- Improved error messages

### 3. Enhanced Frontend Axios Interceptor

**File: `frontend1/src/redux/axiosConfig.js`**

Improved response interceptor to:
- Check for specific error codes (`TOKEN_EXPIRED`, `INVALID_TOKEN`, etc.)
- Clear both `user` and `token` from localStorage on auth failure
- Show user-friendly error messages
- Redirect to login page only when necessary

### 4. Debug Tools

**Created: `backend1/public/token-debug.html`**

A comprehensive browser-based debugging tool to:
- Check localStorage for user and token
- Test login endpoint
- Test settings endpoint with token
- Decode JWT tokens to check expiration
- View token payload and expiration times

Access at: `http://localhost:8080/token-debug.html`

## How to Test

### Option 1: Use the Debug Tool
1. Open `http://localhost:8080/token-debug.html` in your browser
2. Click "Check Token in localStorage" to see your current token
3. If no token exists, use the login form to authenticate
4. Click "Test /api/admin/settings" to verify the endpoint works
5. Use "Decode JWT Token" to check if your token is expired

### Option 2: Manual Testing
1. Clear your browser localStorage:
   ```javascript
   localStorage.clear()
   ```
2. Log in fresh as admin
3. Navigate to Profile or System Settings
4. If error persists, check browser console for detailed error message

### Option 3: Check Server Logs
With debug mode enabled, check your server error logs for JWT decode errors:
```
tail -f /path/to/error.log | grep "JWT"
```

## Configuration

### Current JWT Settings (backend1/.env)
```
JWT_SECRET=sabiteck-school-mgmt-secret-key-2025
JWT_EXPIRY=86400  # 24 hours
APP_ENV=development
APP_DEBUG=true
```

### Debug Mode
Debug mode is now enabled to show detailed error messages. Set to `APP_DEBUG=false` in production.

## Common Issues & Solutions

### Issue 1: Token Expired
**Symptom**: Error message "Token has expired" with error code `TOKEN_EXPIRED`

**Solution**: 
- User needs to log in again
- Consider increasing `JWT_EXPIRY` if sessions expire too quickly
- Implement refresh token mechanism for better UX

### Issue 2: Invalid Signature
**Symptom**: Error message "Invalid token signature" with error code `INVALID_SIGNATURE`

**Solution**:
- Ensure JWT_SECRET hasn't changed
- Clear localStorage and log in again
- Check that frontend and backend are using the same API URL

### Issue 3: Token Not Sent
**Symptom**: Error message "Authorization header missing"

**Solution**:
- Check that axios interceptor is configured (already done in axiosConfig.js)
- Verify user object in localStorage has `token` property
- Check browser network tab to confirm Authorization header is present

### Issue 4: Token Corrupted
**Symptom**: Cannot decode token or unexpected format

**Solution**:
- Clear localStorage: `localStorage.clear()`
- Log in again to get fresh token
- Use token-debug.html tool to decode and verify token structure

## Verification Steps

1. **Backend is running**: http://localhost:8080/api/health should return healthy status
2. **JWT config loaded**: http://localhost:8080/check_env.php should show JWT settings
3. **Token valid**: Use token-debug.html to decode and check expiration
4. **Authorization header sent**: Check browser Network tab > Request Headers

## Next Steps

If the issue persists after implementing these fixes:

1. Clear browser cache and localStorage completely
2. Restart the backend server
3. Log in with fresh credentials
4. Use the debug tool to trace the exact error
5. Check that the token is being saved correctly to localStorage after login
6. Verify the Authorization header is being sent with ALL requests

## Files Modified

1. `backend1/src/Middleware/AuthMiddleware.php` - Enhanced error handling
2. `backend1/src/Utils/JWT.php` - Improved JWT decode with specific exceptions
3. `frontend1/src/redux/axiosConfig.js` - Better token expiration handling
4. `backend1/.env` - Enabled debug mode
5. `backend1/public/token-debug.html` - New debug tool (created)
6. `backend1/public/check_env.php` - Environment verification tool (created)
7. `backend1/test_token_debug.php` - CLI token testing tool (created)

## Rollback Instructions

If you need to rollback:

1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
2. Revert AuthMiddleware.php to show generic error messages
3. Remove debug tools if not needed in production

## Additional Notes

- JWT tokens are stateless - backend doesn't store them
- Token expiry is handled by the JWT library, not by database
- Tokens include: id, role, email, admin_id, account_id
- Token format: Header.Payload.Signature (all Base64 encoded)
