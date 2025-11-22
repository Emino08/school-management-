# JWT Token Authentication - Quick Fix Reference

## Problem
"Invalid or expired token" error when accessing `/api/admin/settings` or `/api/admin/profile`

## Immediate Solutions

### Solution 1: Clear Cache & Re-login (Quickest)
1. Open browser DevTools (F12)
2. Go to Console tab
3. Run: `localStorage.clear()`
4. Refresh page and login again

### Solution 2: Check Token Status
1. Open: `http://localhost:8080/token-debug.html`
2. Click "Check Token in localStorage"
3. If expired, login again using the form
4. Test the settings endpoint

### Solution 3: Manual Token Check
```javascript
// Check if token exists
const user = JSON.parse(localStorage.getItem('user'));
console.log('Token:', user?.token);

// Decode token (paste in browser console)
function decodeJWT(token) {
  const payload = JSON.parse(atob(token.split('.')[1]));
  const exp = new Date(payload.exp * 1000);
  const now = new Date();
  console.log('Expires:', exp);
  console.log('Expired:', exp < now);
  return payload;
}

// Usage
decodeJWT(user.token);
```

## Error Codes

| Error Code | Meaning | Solution |
|------------|---------|----------|
| `TOKEN_EXPIRED` | Token has expired (>24hrs old) | Login again |
| `INVALID_SIGNATURE` | JWT secret mismatch or corrupted | Clear localStorage & login |
| `INVALID_TOKEN` | Malformed or invalid token | Clear localStorage & login |
| `Authorization header missing` | Token not sent | Check axios config |

## Debug Checklist

- [ ] Backend running: http://localhost:8080/api/health
- [ ] JWT configured: http://localhost:8080/check_env.php
- [ ] Token in localStorage: Check DevTools > Application > Local Storage
- [ ] Token not expired: Use token-debug.html to decode
- [ ] Auth header sent: Check DevTools > Network > Request Headers

## Files Changed

✅ `backend1/src/Middleware/AuthMiddleware.php` - Better error messages
✅ `backend1/src/Utils/JWT.php` - Improved JWT handling
✅ `frontend1/src/redux/axiosConfig.js` - Enhanced token interceptor
✅ `backend1/.env` - Debug mode enabled
✅ Debug tools created

## Quick Commands

```bash
# Clear localStorage (Browser Console)
localStorage.clear()

# Check token (Browser Console)
console.log(JSON.parse(localStorage.getItem('user')))

# Test backend health
curl http://localhost:8080/api/health

# Check JWT config
curl http://localhost:8080/check_env.php
```

## Production Notes

Before deploying to production:
1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Remove or protect debug tools:
   - `token-debug.html`
   - `check_env.php`
   - `test_token_debug.php`

## Still Having Issues?

1. **Token shows as valid but still fails**
   - Check JWT_SECRET hasn't changed
   - Verify backend and frontend use same API URL
   - Check for CORS issues

2. **Token not being sent**
   - Verify axios interceptor is loaded
   - Check browser console for errors
   - Ensure user object has `token` property

3. **401 on all requests**
   - Backend might not be reading JWT_SECRET
   - Check .env file location
   - Restart backend server

4. **Token expires too quickly**
   - Increase JWT_EXPIRY in .env (default: 86400 = 24 hours)
   - Consider implementing refresh tokens

## Support

For detailed information, see: `JWT_TOKEN_FIX_GUIDE.md`

Debug tool: `http://localhost:8080/token-debug.html`
