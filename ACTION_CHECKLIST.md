# Action Checklist - Complete This to Finish All Fixes

## 📋 Your Step-by-Step Guide

---

## PHASE 1: BACKEND (READY TO TEST) ✅

### Task 1.1: Run Database Migration
**Status**: ⏳ **NOT RUN YET**

**Steps**:
```bash
# Option A: Double-click this file
RUN_MIGRATION.bat

# Option B: Use phpMyAdmin
1. Go to http://localhost/phpmyadmin
2. Select 'school_management' database
3. Click Import tab
4. Choose: database updated files\complete_system_migration.sql
5. Click Go
6. Wait for success message
```

**Verification**:
```sql
-- Run these queries to verify:
SELECT COUNT(*) FROM students WHERE first_name IS NOT NULL; -- Should return count
SELECT COUNT(*) FROM teachers WHERE first_name IS NOT NULL; -- Should return count
SHOW TABLES LIKE '%password_resets%'; -- Should show table
SHOW TABLES LIKE '%email_logs%'; -- Should show table
SHOW TABLES LIKE '%notification_reads%'; -- Should show table
```

**Checklist**:
- [ ] Migration executed successfully
- [ ] No errors in output
- [ ] All verification queries pass
- [ ] Existing data still intact

---

### Task 1.2: Test Token Authentication
**Status**: ⏳ **WAITING FOR MIGRATION**

**Steps**:
1. Start backend server:
   ```bash
   cd backend1
   php -S localhost:8080 -t public
   ```

2. Open frontend (http://localhost:5173)

3. Login as admin

4. Navigate to these pages:
   - System Settings
   - Profile
   - Dashboard

**Expected Result**: All pages load WITHOUT "Invalid or expired token" error

**Checklist**:
- [ ] Backend starts without .env errors
- [ ] Login successful
- [ ] System Settings page loads
- [ ] Profile page loads
- [ ] Dashboard loads
- [ ] No token errors in console

---

### Task 1.3: Configure Email Settings
**Status**: ⏳ **WAITING FOR MIGRATION**

**Steps**:
1. Login to admin dashboard
2. Go to **System Settings** > **Email** tab
3. Enter your SMTP credentials:
   ```
   SMTP Host: smtp.gmail.com
   SMTP Port: 587
   SMTP Username: your-email@gmail.com
   SMTP Password: your-app-password (NOT regular password!)
   SMTP Encryption: tls
   From Email: noreply@school.com
   From Name: School Management System
   ```
4. Click **Test Email** button
5. Enter your email
6. Click **Send**
7. Check your email for test message
8. If successful, click **Save Settings**

**For Gmail Users**:
- Enable 2-Factor Authentication
- Generate App Password: https://myaccount.google.com/apppasswords
- Use App Password (not your Gmail password)

**Checklist**:
- [ ] SMTP settings entered
- [ ] Test email sent successfully
- [ ] Test email received
- [ ] Settings saved
- [ ] No errors in email_logs table

---

### Task 1.4: Test Password Reset
**Status**: ⏳ **WAITING FOR EMAIL CONFIG**

**Steps**:
1. Logout from system
2. Click "Forgot Password"
3. Enter admin email
4. Select role: Admin
5. Click Submit
6. Check email for reset link
7. Click link in email
8. Enter new password
9. Submit
10. Try logging in with new password

**Checklist**:
- [ ] Forgot password page accessible
- [ ] Request submitted successfully
- [ ] Email received with reset link
- [ ] Reset link works
- [ ] Password changed successfully
- [ ] Can login with new password
- [ ] Token cannot be reused

---

### Task 1.5: Test Notifications
**Status**: ⏳ **WAITING FOR MIGRATION**

**Steps**:
1. Login as admin
2. Check notification bell icon
3. Note the count (should be accurate, not fake 3)
4. Create a test notification
5. Check count increases
6. Click a notification to read
7. Check count decreases

**Checklist**:
- [ ] Notification count is accurate
- [ ] Count updates when new notification created
- [ ] Count decreases when marked as read
- [ ] No fake/static count showing

---

## PHASE 2: FRONTEND UPDATES (REQUIRED) ⏳

### Task 2.1: Update Student Form
**Status**: ⏳ **NOT STARTED**

**Files to Modify**:
- `frontend1/src/components/Students/AddStudent.jsx` (or similar)
- `frontend1/src/components/Students/EditStudent.jsx` (or similar)

**Changes Needed**:
```jsx
// BEFORE:
<div className="form-group">
  <label>Name *</label>
  <input type="text" name="name" required />
</div>

// AFTER:
<div className="form-row">
  <div className="form-group">
    <label>First Name *</label>
    <input type="text" name="first_name" required />
  </div>
  <div className="form-group">
    <label>Last Name *</label>
    <input type="text" name="last_name" required />
  </div>
</div>
```

**Checklist**:
- [ ] Add Student form updated
- [ ] Edit Student form updated
- [ ] Form validation updated
- [ ] API calls updated
- [ ] Display full name in lists
- [ ] Tested adding new student
- [ ] Tested editing existing student

---

### Task 2.2: Update Teacher Form
**Status**: ⏳ **NOT STARTED**

**Files to Modify**:
- `frontend1/src/components/Teachers/AddTeacher.jsx` (or similar)
- `frontend1/src/components/Teachers/EditTeacher.jsx` (or similar)

**Changes Needed**:
```jsx
// Same as student form - split name into first_name and last_name
<div className="form-row">
  <div className="form-group">
    <label>First Name *</label>
    <input type="text" name="first_name" required />
  </div>
  <div className="form-group">
    <label>Last Name *</label>
    <input type="text" name="last_name" required />
  </div>
</div>
```

**Checklist**:
- [ ] Add Teacher form updated
- [ ] Edit Teacher form updated
- [ ] Form validation updated
- [ ] API calls updated
- [ ] Display full name in lists
- [ ] Tested adding new teacher
- [ ] Tested editing existing teacher

---

### Task 2.3: Update CSV Templates
**Status**: ✅ **FILES CREATED**, ⏳ **UI NOT INTEGRATED**

**Files Created**:
- `frontend1/src/templates/students_upload_template.csv` ✅
- `frontend1/src/templates/teachers_upload_template.csv` ✅

**Changes Needed**:
- Update download links to point to new templates
- Update upload processing to handle new format
- Update instructions/help text

**Files to Modify**:
- `frontend1/src/components/Students/BulkUpload.jsx` (or similar)
- `frontend1/src/components/Teachers/BulkUpload.jsx` (or similar)

**Checklist**:
- [ ] Download links updated
- [ ] Templates accessible
- [ ] Upload accepts new format
- [ ] Old format still works (backward compatibility)
- [ ] Tested uploading students
- [ ] Tested uploading teachers
- [ ] Instructions updated

---

### Task 2.4: Add Teacher Classes View
**Status**: ⏳ **NOT STARTED**

**What to Create**:
1. New Modal Component: `TeacherClassesModal.jsx`
2. Update Teacher Table with "View" button

**Component Code**:
```jsx
// frontend1/src/components/Teachers/TeacherClassesModal.jsx
import React, { useEffect, useState } from 'react';
import axios from 'axios';
import './TeacherClassesModal.css';

const TeacherClassesModal = ({ teacherId, teacherName, onClose }) => {
  const [classes, setClasses] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchClasses();
  }, [teacherId]);

  const fetchClasses = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(
        `/api/teachers/${teacherId}/classes`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      setClasses(response.data.classes || []);
    } catch (error) {
      console.error('Error fetching classes:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2>{teacherName}'s Classes</h2>
          <button className="close-btn" onClick={onClose}>&times;</button>
        </div>
        <div className="modal-body">
          {loading ? (
            <p>Loading...</p>
          ) : classes.length === 0 ? (
            <p>No classes assigned yet.</p>
          ) : (
            <table className="classes-table">
              <thead>
                <tr>
                  <th>Class</th>
                  <th>Subject</th>
                  <th>Academic Year</th>
                </tr>
              </thead>
              <tbody>
                {classes.map((cls) => (
                  <tr key={cls.id}>
                    <td>{cls.class_name}</td>
                    <td>{cls.subject_name}</td>
                    <td>{cls.academic_year}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
};

export default TeacherClassesModal;
```

**Update Teacher Table**:
```jsx
// In teacher list/table component
import TeacherClassesModal from './TeacherClassesModal';

// Add state
const [showClassesModal, setShowClassesModal] = useState(false);
const [selectedTeacher, setSelectedTeacher] = useState(null);

// Add button in Classes column
<td>
  <button 
    className="btn-view"
    onClick={() => {
      setSelectedTeacher({ id: teacher.id, name: teacher.name });
      setShowClassesModal(true);
    }}
  >
    View
  </button>
</td>

// Add modal
{showClassesModal && (
  <TeacherClassesModal
    teacherId={selectedTeacher.id}
    teacherName={selectedTeacher.name}
    onClose={() => setShowClassesModal(false)}
  />
)}
```

**Checklist**:
- [ ] TeacherClassesModal component created
- [ ] View button added to teacher table
- [ ] Modal opens on click
- [ ] Classes displayed correctly
- [ ] Modal closes properly
- [ ] Tested with teacher with classes
- [ ] Tested with teacher without classes

---

### Task 2.5: Update Currency Display
**Status**: ⏳ **NOT STARTED**

**Goal**: Replace all currency symbols with **SLE**

**Create Utility Function**:
```javascript
// frontend1/src/utils/currency.js
export const formatCurrency = (amount) => {
  const num = Number(amount) || 0;
  return `SLE ${num.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })}`;
};
```

**Files to Update**:
Find all files with currency display and update:
- Financial Reports components
- Dashboard financial widgets
- Payment forms
- Invoice displays
- Fee structures
- Any money display

**Search Pattern**:
```bash
# Search for currency symbols in files
grep -r "₦\|NGN\|\$\|USD" frontend1/src/
```

**Usage**:
```jsx
import { formatCurrency } from '@/utils/currency';

// Replace:
<span>₦{amount}</span>

// With:
<span>{formatCurrency(amount)}</span>
```

**Checklist**:
- [ ] Currency utility created
- [ ] All financial displays updated
- [ ] Dashboard widgets updated
- [ ] Reports updated
- [ ] Payment forms updated
- [ ] Invoice displays updated
- [ ] Tested all money displays
- [ ] Consistent SLE format everywhere

---

### Task 2.6: Rename Reports Tab
**Status**: ⏳ **NOT STARTED**

**Changes**:
- Navigation: "Financial Reports" → "Reports"
- Page title: "Financial Reports" → "Reports"
- Breadcrumbs updated
- Route names updated (if needed)

**Files to Check**:
- Navigation/Sidebar component
- Reports page header
- Routes configuration

**Checklist**:
- [ ] Sidebar/navigation updated
- [ ] Page title updated
- [ ] Breadcrumbs updated
- [ ] No broken links
- [ ] Consistent naming

---

### Task 2.7: Enhanced Analytics (Optional)
**Status**: ⏳ **NOT STARTED** (Enhancement)

**Suggested Additions**:
1. Revenue Trends Chart (Line/Bar)
2. Collection Rate by Class (Bar Chart)
3. Payment Method Distribution (Pie Chart)
4. Outstanding Balances Summary
5. Top 5 Paying Classes
6. Top 5 Defaulting Classes

**Libraries Needed**:
- Chart.js or Recharts for charts
- React-PDF or jsPDF for PDF export

**Checklist**:
- [ ] Chart library installed
- [ ] Revenue trends chart added
- [ ] Collection rate chart added
- [ ] Payment method chart added
- [ ] Outstanding summary added
- [ ] Top/bottom lists added
- [ ] Responsive design
- [ ] Loading states
- [ ] Error handling

---

### Task 2.8: PDF Export (Optional)
**Status**: ⏳ **NOT STARTED** (Enhancement)

**What to Add**:
- Export button on reports
- PDF generation with:
  - School logo/header
  - Report title
  - Date range
  - Data tables
  - Charts (as images)
  - Footer with generated date

**Libraries**:
```bash
npm install jspdf jspdf-autotable
# or
npm install @react-pdf/renderer
```

**Checklist**:
- [ ] PDF library installed
- [ ] Export button added
- [ ] PDF template created
- [ ] Header/footer included
- [ ] Tables formatted
- [ ] Charts included
- [ ] Tested PDF generation
- [ ] Download works

---

## PHASE 3: TESTING (REQUIRED AFTER UPDATES) ⏳

### Task 3.1: Backend Testing
**Status**: ⏳ **PENDING PHASE 1 COMPLETION**

**Test Cases**:
- [ ] Login works
- [ ] All protected routes accessible
- [ ] No token errors
- [ ] System settings save/load
- [ ] Email sending works
- [ ] Password reset works
- [ ] Notifications accurate
- [ ] API responses correct

---

### Task 3.2: Frontend Testing
**Status**: ⏳ **PENDING PHASE 2 COMPLETION**

**Test Cases**:

**Students**:
- [ ] Add student with first/last name
- [ ] Edit student shows split names
- [ ] Student list shows full names
- [ ] CSV upload with new format works
- [ ] Search works with new names

**Teachers**:
- [ ] Add teacher with first/last name
- [ ] Edit teacher shows split names
- [ ] Teacher list shows full names
- [ ] View classes button works
- [ ] Classes modal displays correctly
- [ ] CSV upload with new format works

**Financial**:
- [ ] All amounts show SLE currency
- [ ] Consistent format everywhere
- [ ] Charts display (if added)
- [ ] PDF export works (if added)

**General**:
- [ ] No console errors
- [ ] Responsive design intact
- [ ] Loading states work
- [ ] Error handling works

---

### Task 3.3: Integration Testing
**Status**: ⏳ **PENDING PREVIOUS TESTS**

**Test Workflows**:
- [ ] Full student registration (with email)
- [ ] Full teacher registration (with email)
- [ ] Password reset end-to-end
- [ ] Notification creation and reading
- [ ] Fee payment recording
- [ ] Report generation
- [ ] CSV bulk upload
- [ ] Teacher class assignment

---

## PHASE 4: PRODUCTION DEPLOYMENT 🚀

### Task 4.1: Backup Production
**Status**: ⏳ **NOT DONE YET**

**Steps**:
```bash
# CRITICAL: Always backup first!
mysqldump -h [hostname] -u u232752871_boschool -p u232752871_sms > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup created
ls -lh backup_*.sql
```

**Checklist**:
- [ ] Production database backed up
- [ ] Backup file exists
- [ ] Backup file not empty
- [ ] Backup stored safely

---

### Task 4.2: Deploy Backend
**Status**: ⏳ **NOT DONE YET**

**Files to Upload**:
- `backend1/.env` (with production values!)
- `backend1/src/Controllers/SettingsController.php`

**Steps**:
1. Update `.env` with production database credentials
2. Upload modified files via FTP/cPanel
3. Restart backend service (if applicable)

**Checklist**:
- [ ] .env has production credentials
- [ ] Files uploaded
- [ ] Backend restarted
- [ ] Test login on production

---

### Task 4.3: Run Production Migration
**Status**: ⏳ **NOT DONE YET**

**Steps**:
```bash
# Via SSH
mysql -h localhost -u u232752871_boschool -p u232752871_sms < complete_system_migration.sql

# Or via phpMyAdmin (cPanel)
1. Login to cPanel
2. Open phpMyAdmin
3. Select u232752871_sms database
4. Import > complete_system_migration.sql
5. Execute
```

**Verification**:
```sql
SELECT COUNT(*) FROM students WHERE first_name IS NOT NULL;
SELECT COUNT(*) FROM teachers WHERE first_name IS NOT NULL;
SHOW TABLES LIKE '%password_resets%';
```

**Checklist**:
- [ ] Migration uploaded
- [ ] Migration executed
- [ ] No errors
- [ ] Verification queries pass
- [ ] Data intact

---

### Task 4.4: Deploy Frontend
**Status**: ⏳ **NOT DONE YET**

**Steps**:
1. Build frontend: `npm run build`
2. Upload `dist/` folder to production
3. Update API base URL (if needed)

**Checklist**:
- [ ] Frontend built
- [ ] Files uploaded
- [ ] API URL correct
- [ ] Test on production URL

---

### Task 4.5: Production Testing
**Status**: ⏳ **NOT DONE YET**

**Test on Production**:
- [ ] Login works
- [ ] System settings accessible
- [ ] Email sending works
- [ ] Password reset works
- [ ] Student CRUD works
- [ ] Teacher CRUD works
- [ ] Reports accessible
- [ ] Notifications work
- [ ] No errors in logs

---

## 📊 OVERALL PROGRESS

### Phase 1: Backend (0% Complete)
- [ ] Task 1.1: Run migration (CRITICAL)
- [ ] Task 1.2: Test authentication
- [ ] Task 1.3: Configure email
- [ ] Task 1.4: Test password reset
- [ ] Task 1.5: Test notifications

### Phase 2: Frontend (0% Complete)
- [ ] Task 2.1: Update student form
- [ ] Task 2.2: Update teacher form
- [ ] Task 2.3: Update CSV templates
- [ ] Task 2.4: Add teacher classes view
- [ ] Task 2.5: Update currency
- [ ] Task 2.6: Rename reports tab
- [ ] Task 2.7: Enhanced analytics (optional)
- [ ] Task 2.8: PDF export (optional)

### Phase 3: Testing (0% Complete)
- [ ] Task 3.1: Backend testing
- [ ] Task 3.2: Frontend testing
- [ ] Task 3.3: Integration testing

### Phase 4: Production (0% Complete)
- [ ] Task 4.1: Backup production
- [ ] Task 4.2: Deploy backend
- [ ] Task 4.3: Run migration
- [ ] Task 4.4: Deploy frontend
- [ ] Task 4.5: Production testing

---

## 🎯 PRIORITY ORDER

### DO FIRST (Critical):
1. ✅ Task 1.1: Run database migration
2. ✅ Task 1.2: Test token authentication
3. ✅ Task 1.3: Configure email

### DO NEXT (Important):
4. Task 2.1: Update student form
5. Task 2.2: Update teacher form
6. Task 2.5: Update currency

### DO LATER (Nice to Have):
7. Task 2.4: Teacher classes view
8. Task 2.7: Enhanced analytics
9. Task 2.8: PDF export

---

## ⏱️ TIME ESTIMATES

| Task | Estimated Time |
|------|---------------|
| Phase 1 Complete | 1 hour |
| Student Form Update | 2 hours |
| Teacher Form Update | 2 hours |
| CSV Integration | 1 hour |
| Teacher Classes Modal | 3 hours |
| Currency Update | 2 hours |
| Reports Rename | 30 minutes |
| Enhanced Analytics | 8 hours |
| PDF Export | 4 hours |
| Testing | 4 hours |
| Production Deploy | 2 hours |
| **TOTAL** | **29.5 hours** |

**Minimum Viable** (without enhancements): ~13 hours

---

## 📞 GETTING HELP

If stuck:
1. Check `QUICK_START_FIXES.md` for fast setup
2. Check `FIXES_APPLIED_SUMMARY.md` for details
3. Check `ISSUES_RESOLVED.md` for what's fixed
4. Check backend error logs
5. Check browser console

---

## ✨ DONE INDICATOR

You'll know you're done when:
- ✅ No token authentication errors
- ✅ All system settings tabs work
- ✅ Email sending works
- ✅ Password reset works
- ✅ Students/teachers have first/last names
- ✅ All currency shows as SLE
- ✅ No fake notification counts
- ✅ Everything tested and working

---

**START HERE**: Task 1.1 - Run Database Migration!

Last Updated: November 21, 2025
