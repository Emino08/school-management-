# QUICK TEST GUIDE
## Test All Fixes Applied

## Prerequisites
- Backend running on http://localhost:8080
- Frontend can be started with `npm run dev` in frontend1 folder

## Backend is Already Running ✓
The backend PHP server is running on port 8080.

## Test Sequence

### 1. Test Backend Health
```bash
curl http://localhost:8080/api/health
```
**Expected:** JSON response with `"status": "healthy"`

### 2. Test Admin Login
Create test request file or use Postman:
```http
POST http://localhost:8080/api/admin/login
Content-Type: application/json

{
  "email": "admin@school.com",
  "password": "admin123"
}
```
**Expected:** Success response with token

### 3. Test System Settings (With Token)
```http
GET http://localhost:8080/api/admin/settings
Authorization: Bearer YOUR_TOKEN_HERE
```
**Expected:** Settings data without "Invalid or expired token" error

### 4. Test Notifications
```http
GET http://localhost:8080/api/notifications/unread-count
Authorization: Bearer YOUR_TOKEN_HERE
```
**Expected:** Unread count (should be 0 if no notifications)

### 5. Frontend Testing

#### Start Frontend
```bash
cd frontend1
npm run dev
```

#### Test in Browser
1. **Login Page**
   - Go to http://localhost:5173
   - Login as admin
   - Should redirect to dashboard

2. **Profile Page**
   - Click on Profile from sidebar
   - Should load without token error

3. **System Settings**
   - Click on System Settings
   - Should show all 5 tabs:
     - General
     - Notifications  
     - Email
     - Security
     - System
   - Try updating each tab
   - Should save without SQL errors

4. **Notification Badge**
   - Check sidebar notification icon
   - Should show correct count (0 if no notifications)
   - Should not show hardcoded "3"

5. **Teacher Management**
   - Go to Teachers section
   - Add a new teacher with first name and last name separate
   - Should save successfully
   - Edit existing teacher
   - Should update without SQL error

6. **Student Management**
   - Go to Students section
   - Add new student with first name and last name
   - Upload CSV with first_name and last_name columns
   - Should import successfully

7. **Financial Reports**
   - Go to Reports/Financial section
   - Check currency display
   - Should show SLE (when implemented)
   - Test PDF export

## Common Issues & Solutions

### Issue: "Invalid or expired token"
**Solution:** 
- Clear browser cache
- Delete localStorage
- Login again

### Issue: SQL syntax error
**Solution:**
- Check error message in browser console
- Verify database column exists
- Check backend error log

### Issue: CORS error
**Solution:**
- Verify axios baseURL is correct
- Check backend CORS middleware
- Ensure frontend URL is in CORS_ORIGIN env variable

### Issue: 404 Not Found
**Solution:**
- Verify route exists in `backend1/src/Routes/api.php`
- Check axios call doesn't have duplicate `/api` prefix
- Ensure backend server is running

## Database Verification

### Check Student Names
```sql
SELECT id, name, first_name, last_name FROM students LIMIT 10;
```

### Check Teacher Names
```sql
SELECT id, name, first_name, last_name FROM teachers LIMIT 10;
```

### Check Notifications
```sql
SELECT COUNT(*) as total, 
       SUM(CASE WHEN status='Sent' THEN 1 ELSE 0 END) as sent
FROM notifications;
```

### Check Notification Reads
```sql
SELECT COUNT(*) FROM notification_reads;
```

## Performance Checks

1. **Page Load Time**
   - Dashboard should load < 2 seconds
   - Lists should load < 1 second

2. **API Response Time**
   - Most endpoints should respond < 500ms
   - Complex queries may take up to 2 seconds

3. **Database Queries**
   - Check for N+1 query problems
   - Use indexes for frequently accessed columns

## Security Checks

1. **Token Expiry**
   - Tokens should expire after 24 hours (86400 seconds)
   - Should redirect to login on expiry

2. **Password Hashing**
   - Passwords should be hashed with bcrypt
   - Never stored in plain text

3. **Input Validation**
   - XSS protection enabled
   - SQL injection prevented via prepared statements
   - CSRF tokens for state-changing operations

## Final Checklist

- [ ] Backend health check passes
- [ ] Admin login works
- [ ] System settings load without error
- [ ] Profile page accessible
- [ ] Notification count correct
- [ ] Teacher CRUD works with names
- [ ] Student CRUD works with names
- [ ] No SQL syntax errors
- [ ] No duplicate route errors
- [ ] No CORS errors
- [ ] Email configuration testable
- [ ] All currency shows SLE (when implemented)

## Next Steps After Testing

1. **If all tests pass:**
   - Deploy to production
   - Run `database updated files/updated.sql` on production DB
   - Update production `.env` file
   - Test in production environment

2. **If tests fail:**
   - Check specific error messages
   - Refer to FINAL_FIX_GUIDE.md
   - Review browser console and network tab
   - Check backend error logs

## Support

If issues persist:
1. Check `FINAL_FIX_GUIDE.md` for detailed solutions
2. Review error logs
3. Verify all migrations ran successfully
4. Ensure environment variables are correct

**Last Updated:** 2025-11-21 15:57 UTC
