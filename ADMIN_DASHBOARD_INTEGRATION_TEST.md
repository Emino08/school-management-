# Admin Dashboard Integration Test

## Check These Things in Browser Console (F12):

### 1. User State Check
Look for:
```
=== USER STATE CHECK ===
Redux User: {id: 1, email: "...", token: "..."}
Current User: {id: 1, email: "...", token: "..."}
Admin ID: 1
Has Token: true
```

**If you see:**
- `Current User: null` → User not loaded from Redux or localStorage
- `Admin ID: undefined` → User object doesn't have id or _id
- `Has Token: false` → Token missing from user object

### 2. Fetch Dashboard Data Call
Look for:
```
=== fetchDashboardData CALLED ===
adminID: 1
currentUser: {id: 1, ...}
has token: true
✓ Prerequisites met, proceeding with fetch...
```

**If you see:**
- `❌ Cannot fetch dashboard data` → Prerequisites not met
- Check what's missing (adminID or token)

### 3. API Request
Look for:
```
📡 Fetching from: http://localhost:8080/api/admin/stats?refresh=true
🔑 Token (first 20 chars): eyJ0eXAiOiJKV1QiLCJh...
```

**Then in Network tab:**
- Should see `admin/stats` request
- Status should be `200 OK`
- Response should have `total_students: 4`

### 4. Success Response
Look for:
```
📥 Stats API Response: {success: true, stats: {...}}
✅ Dashboard stats updated successfully: {total_students: 4, ...}
🎯 DASHBOARD STATS UPDATED! {...}
  Students: 4
  Classes: 2
  Teachers: 3
  Subjects: 3
```

---

## If No API Request Is Made

### Scenario A: User Not Loaded
**Console shows:** `Current User: null`

**Solution:**
1. Check Redux store - open Redux DevTools
2. Look for `user` state
3. If empty, user didn't login properly
4. Log out and log in again

### Scenario B: adminID Missing  
**Console shows:** `Admin ID: undefined`

**Solution:**
1. Check if user object has `id` or `_id` property
2. Backend returns `id`, not `_id`
3. Code checks both: `currentUser?._id || currentUser?.id`

### Scenario C: Token Missing
**Console shows:** `Has Token: false`

**Solution:**
1. Token not in user object
2. Check localStorage: `localStorage.getItem('user')`
3. Should have `token` property
4. Log out and log in to get fresh token

### Scenario D: Function Not Called
**Console doesn't show:** `fetchDashboardData CALLED`

**Solution:**
1. useEffect dependencies might be wrong
2. Check if `adminID` and `currentUser` are truthy
3. Look for error preventing function call

---

## Quick Diagnostic Commands

### Run in Browser Console:

```javascript
// Check user data
console.log('localStorage user:', JSON.parse(localStorage.getItem('user') || 'null'));

// Check Redux state (if Redux DevTools not available)
// This won't work directly, but you can see in React DevTools

// Manually trigger fetch (if function is accessible)
// This depends on React component scope

// Check if API is reachable
fetch('http://localhost:8080/api/admin/stats', {
    headers: {
        'Authorization': 'Bearer ' + JSON.parse(localStorage.getItem('user')).token
    }
})
.then(r => r.json())
.then(d => console.log('Manual API test:', d))
.catch(e => console.error('Manual API test failed:', e));
```

---

## Expected Flow

1. **Page Loads** → AdminHomePage mounts
2. **Get User** → From Redux or localStorage  
3. **Check Prerequisites** → adminID and token present
4. **Call API** → GET /api/admin/stats?refresh=true
5. **Receive Response** → {success: true, stats: {...}}
6. **Update State** → setDashboardStats(data)
7. **Re-render** → Display new values
8. **Show Badge** → Green "Live" badge appears

---

## Common Fixes

### Fix 1: User Not in Redux
```javascript
// Check if user is in localStorage
const user = JSON.parse(localStorage.getItem('user'));
if (user) {
    // User exists in localStorage
    // Redux should load it on app init
    // Check Redux reducer initialization
}
```

### Fix 2: Force Manual Fetch
If function exists but doesn't run, add a button:
```jsx
<Button onClick={() => fetchDashboardData(true)}>
    Force Fetch Now
</Button>
```

### Fix 3: Check Axios Config
```javascript
// Verify axios has correct baseURL
import axios from '@/redux/axiosConfig';
console.log('Axios baseURL:', axios.defaults.baseURL);
// Should show: http://localhost:8080/api
```

---

## Success Checklist

- [ ] Console shows "USER STATE CHECK" with valid data
- [ ] Console shows "fetchDashboardData CALLED"
- [ ] Console shows "Prerequisites met"
- [ ] Console shows "Fetching from: http://localhost..."
- [ ] Network tab shows request to `/api/admin/stats`
- [ ] Network tab shows 200 status
- [ ] Network tab response has `total_students: 4`
- [ ] Console shows "Dashboard stats updated successfully"
- [ ] Console shows "DASHBOARD STATS UPDATED!"
- [ ] Dashboard displays: 4, 2, 3, 3
- [ ] Green "Live" badge visible on cards

---

## If Everything Fails

1. **Restart both servers:**
   ```bash
   # Backend
   cd backend1
   # Kill process on port 8080
   php -S localhost:8080 -t public
   
   # Frontend
   cd frontend1
   # Kill process on port 5173
   npm run dev
   ```

2. **Clear everything:**
   ```javascript
   // In browser console
   localStorage.clear();
   sessionStorage.clear();
   location.reload();
   ```

3. **Login fresh:**
   - Go to login page
   - Enter credentials
   - Login
   - Go to dashboard
   - Check console logs

4. **Check backend logs:**
   - Look at terminal running PHP server
   - Should see incoming requests
   - Check for any PHP errors

---

## Report Format

If still not working, provide:

1. **Console output:** Copy all console logs
2. **Network tab:** Screenshot showing requests (or lack thereof)
3. **Redux state:** Screenshot of Redux DevTools user state
4. **localStorage:** Result of `localStorage.getItem('user')`
5. **Component state:** Any errors or warnings

This will help diagnose exactly where the data flow breaks.
