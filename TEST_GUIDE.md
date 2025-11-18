# 🧪 TESTING GUIDE - ALL SYSTEMS

## ✅ SERVERS ARE RUNNING!

- **Backend**: http://localhost:8080
- **Frontend**: http://localhost:5174

---

## 📝 TEST SCENARIOS

### 1️⃣ **Test Parent Portal** (Priority: HIGH)

#### Step 1: Open Application
```
✅ Go to: http://localhost:5174
✅ You should see "Choose User" page
✅ Verify all 5 user types are visible
```

#### Step 2: Register as Parent
```
1. Click "Parent" button
2. Should navigate to: /parent/login
3. Click "Register here" link
4. Fill registration form:
   - Name: Test Parent
   - Email: parent@test.com
   - Phone: 1234567890
   - Password: password123
   - Confirm Password: password123
5. Click "Register"
6. Should show success message
7. Should redirect to login page
```

#### Step 3: Login as Parent
```
1. Enter email: parent@test.com
2. Enter password: password123
3. Click "Login"
4. Should redirect to: /parent/dashboard
5. Dashboard should display:
   ✅ Welcome message
   ✅ Statistics cards (Children, Notifications, Messages)
   ✅ "Link Child" button
   ✅ Recent notifications section
   ✅ Quick actions
```

#### Step 4: Link a Child
```
1. From dashboard, click "Link Child" or navigate to /parent/link-child
2. Enter Student ID: (Use actual student ID from database)
3. Enter Date of Birth: YYYY-MM-DD format
4. Click "Link Child"
5. Should verify and link successfully
6. Should redirect to dashboard
7. Dashboard should now show linked children
```

#### Step 5: View Child Profile
```
1. From dashboard, click on a child card
2. Should navigate to: /parent/child/{id}
3. Profile should show:
   ✅ Student information
   ✅ Attendance records with percentage
   ✅ Exam results with grades
   ✅ Medical history
   ✅ All data properly formatted
```

#### Step 6: Check Notifications
```
1. Click "Notifications" in navigation
2. Should navigate to: /parent/notifications
3. Should display:
   ✅ Filter buttons (All, Attendance, Medical, etc.)
   ✅ Unread count badge
   ✅ List of notifications
   ✅ "Mark as Read" functionality
4. Test filtering by type
5. Test marking notifications as read
```

#### Step 7: Send Message
```
1. Click "Communications" in navigation
2. Should navigate to: /parent/communications
3. Click "New Message"
4. Fill form:
   - Recipient Type: Teacher or Admin
   - Subject: Test Message
   - Message: This is a test
   - Priority: Medium
5. Click "Send"
6. Should show in message list
7. Should be able to view thread
```

---

### 2️⃣ **Test Medical Portal** (Priority: HIGH)

#### Step 1: Login as Medical Staff
```
Note: Medical staff accounts are created by admin
You'll need admin to create a medical staff account first.

1. Go to: http://localhost:5174
2. Click "Medical Staff"
3. Should navigate to: /medical/login
4. Enter credentials (created by admin)
5. Click "Login"
6. Should redirect to: /medical/dashboard
```

#### Step 2: View Dashboard
```
Dashboard should show:
✅ Welcome message with staff name
✅ Statistics cards:
   - Active Cases
   - Under Treatment
   - Completed Cases
✅ Quick action buttons:
   - New Record
   - Active Cases
   - Search Student
   - Reports
✅ Recent cases list
```

#### Step 3: Create Medical Record
```
1. Click "New Record" or navigate to /medical/create-record
2. Search for student by ID or name
3. Select student from results
4. Fill medical record form:
   - Diagnosis: Common Cold
   - Symptoms: Fever, Cough
   - Treatment Plan: Rest and medication
   - Severity: Moderate
   - Additional Notes: Follow up in 3 days
5. Click "Create Record"
6. Should show success message
7. Parent should receive notification automatically
```

#### Step 4: View Active Cases
```
1. From dashboard, click "Active Cases"
2. Should show all open medical cases
3. Each case should display:
   ✅ Student name
   ✅ Diagnosis
   ✅ Severity level
   ✅ Status
   ✅ Created date
4. Click on case to view details
5. Should be able to update treatment
6. Should be able to close case
```

---

### 3️⃣ **Test House Registration** (Priority: MEDIUM)

#### Step 1: Navigate to House Registration
```
1. Login as Teacher or Admin
2. Navigate to: /house/register-student
3. Should see house registration form
```

#### Step 2: Register Student to House
```
1. Select House from dropdown (6 options)
2. Select Block from dropdown (A-F)
3. Select Student from dropdown
   ✅ Should only show PAID students
   ✅ Label should indicate "Eligible Students (Paid Only)"
4. Click "Register Student"
5. Should validate:
   ✅ Payment status
   ✅ House capacity
   ✅ Block capacity
6. Should show success message
7. Should log registration in database
```

#### Step 3: Verify Payment Validation
```
1. Try to register unpaid student (should not appear in list)
2. Verify only paid students are shown
3. Message should clearly indicate payment requirement
```

---

### 4️⃣ **Test Automatic Notifications**

#### Test Attendance Notification
```
1. Mark a student absent (using existing attendance system)
2. Parent should receive notification automatically
3. Notification should appear in:
   ✅ Parent dashboard (recent notifications)
   ✅ Parent notifications page
   ✅ Notification bell icon (unread count)
4. Type should be: "attendance"
5. Should contain student name and date
```

#### Test Medical Notification
```
1. Medical staff creates a record
2. Parent should receive notification automatically
3. Notification should show:
   ✅ Student name
   ✅ Diagnosis
   ✅ Severity
   ✅ Link to view details
4. Type should be: "medical"
```

#### Test Suspension Notification
```
1. Admin suspends a student (using existing system)
2. Parent should receive notification automatically
3. Notification should show:
   ✅ Suspension reason
   ✅ Start date
   ✅ End date
   ✅ Notes
4. Type should be: "suspension"
```

#### Test Medical Recovery Notification
```
1. Medical staff closes a treatment
2. Parent should receive notification
3. Should indicate child is recovered
4. Should show final notes
```

---

### 5️⃣ **Test Responsive Design**

#### Desktop (1920x1080)
```
✅ All elements properly spaced
✅ Cards in grid layout
✅ Navigation clear and accessible
✅ Forms centered and readable
```

#### Tablet (768px)
```
✅ Grid adjusts to 2 columns
✅ Navigation still accessible
✅ Forms responsive
✅ No horizontal scroll
```

#### Mobile (375px)
```
✅ Single column layout
✅ Cards stack vertically
✅ Touch-friendly buttons
✅ Readable text size
✅ No content cutoff
```

---

### 6️⃣ **Test Error Handling**

#### Parent Portal Errors
```
1. Login with wrong credentials → Should show error
2. Try to link child with wrong DOB → Should show error
3. Try to link child with wrong ID → Should show error
4. Send message with empty fields → Should validate
```

#### Medical Portal Errors
```
1. Try to create record without student → Should validate
2. Try to create record with empty diagnosis → Should validate
3. Login with wrong credentials → Should show error
```

#### House Registration Errors
```
1. Try to register without selecting house → Should validate
2. Try to register without selecting block → Should validate
3. Try to register without selecting student → Should validate
4. Try to register when house is full → Should show error
```

---

### 7️⃣ **Test Data Persistence**

#### Check Database
```
1. After registering parent → Check parents table
2. After linking child → Check parent_students table
3. After creating notification → Check parent_notifications table
4. After creating medical record → Check student_medical_records table
5. After house registration → Check houses and house_registrations tables
```

#### Check Redux/State
```
1. Parent data persists on page refresh
2. Token stored in localStorage
3. User data available across pages
4. Logout clears all data
```

---

## 🔍 DEBUGGING TIPS

### Check Browser Console
```
F12 → Console Tab
Look for:
❌ Red errors → API connection issues
⚠️ Yellow warnings → Non-critical issues
✅ Green logs → Successful operations
```

### Check Network Tab
```
F12 → Network Tab → XHR
Monitor:
✅ API calls to http://localhost:8080/api
✅ Response status (200 = success, 400/500 = error)
✅ Response data
✅ Request payload
```

### Check Backend Logs
```
Backend terminal shows:
✅ Incoming requests
✅ SQL queries
✅ Error messages
✅ Response codes
```

### Common Issues & Fixes

**Issue**: Can't connect to API
```
Fix: Check .env file in frontend1
VITE_API_URL=http://localhost:8080/api
```

**Issue**: CORS errors
```
Fix: Backend should have CORS headers enabled
Check: backend1/public/index.php
```

**Issue**: Can't link child
```
Fix: 
1. Verify student exists in database
2. DOB must match EXACTLY (YYYY-MM-DD format)
3. Check console for error details
```

**Issue**: Notifications not appearing
```
Fix:
1. Check notifications are created in database
2. Verify parent_id matches
3. Check API endpoint: GET /api/parent/notifications
```

---

## 📊 EXPECTED RESULTS

### After Complete Testing

✅ **Parent Portal**
- [x] Parents can register
- [x] Parents can login
- [x] Parents can link children
- [x] Parents can view child details
- [x] Parents receive notifications
- [x] Parents can send messages
- [x] All data displays correctly

✅ **Medical Portal**
- [x] Medical staff can login
- [x] Can create medical records
- [x] Parents get notified
- [x] Dashboard shows statistics
- [x] Can view active cases
- [x] Can close treatments

✅ **House System**
- [x] Can register students to houses
- [x] Payment validation works
- [x] Only paid students appear
- [x] Capacity checking works
- [x] Registration logged properly

✅ **Notifications**
- [x] Attendance miss → Parent notified
- [x] Medical record → Parent notified
- [x] Suspension → Parent notified
- [x] Recovery → Parent notified
- [x] Notifications marked as read

✅ **Communication**
- [x] Parents can send messages
- [x] Messages stored in database
- [x] Threads display correctly
- [x] Priority levels work
- [x] Can view responses

---

## 🎯 TESTING CHECKLIST

### Critical Features (Must Work)
- [ ] Parent registration and login
- [ ] Child linking with verification
- [ ] Parent dashboard loads
- [ ] Notifications display
- [ ] Medical staff login
- [ ] Medical record creation
- [ ] House registration with payment check
- [ ] Automatic notifications sent

### Important Features (Should Work)
- [ ] Child profile shows all data
- [ ] Attendance displayed correctly
- [ ] Results displayed correctly
- [ ] Medical history shown
- [ ] Message sending works
- [ ] Message threads display
- [ ] House capacity checking
- [ ] Payment validation

### Nice to Have (Good to Check)
- [ ] Responsive on mobile
- [ ] Loading states show
- [ ] Error messages clear
- [ ] Success messages appear
- [ ] UI looks good
- [ ] Smooth navigation
- [ ] Fast performance

---

## 📝 REPORT ISSUES

If you find any issues:

1. **Check the console** - Look for errors
2. **Check the network** - Verify API calls
3. **Check the backend logs** - See server errors
4. **Document the issue**:
   - What were you doing?
   - What happened?
   - What should have happened?
   - Error messages?
   - Screenshots?

---

## 🎉 SUCCESS CRITERIA

✅ All 5 user types accessible  
✅ Parent portal fully functional  
✅ Medical system working  
✅ House registration operational  
✅ Notifications automatic  
✅ No console errors  
✅ No broken links  
✅ Responsive design works  
✅ Data persists correctly  

---

## 🚀 NEXT STEPS AFTER TESTING

1. **Fix any bugs found** during testing
2. **Add real student data** to database
3. **Create medical staff accounts** via admin
4. **Configure house capacities** in database
5. **Train users** on new features
6. **Monitor logs** for issues
7. **Collect feedback** from real users
8. **Deploy to production** when ready

---

**Start Testing Now!**  
Open http://localhost:5174 and follow the scenarios above! 🚀

---

**Status**: ✅ Ready for Testing  
**Servers**: 🟢 Running  
**Features**: 🟢 Complete  
**Documentation**: 🟢 Available  

Good luck with testing! 🎊
