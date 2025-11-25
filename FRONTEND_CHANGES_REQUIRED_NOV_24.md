# FRONTEND CHANGES REQUIRED

## Quick Reference for Frontend Developer

### 1. Parent Dashboard - Medical Records Tab

**File**: `frontend1/src/pages/parent/ParentStudentMedical.jsx` (or similar)

**Add the following button to allow parents to add medical records**:

```jsx
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// In your Medical Records component
const [showAddModal, setShowAddModal] = useState(false);
const [formData, setFormData] = useState({
  record_type: 'general',
  description: '',
  symptoms: '',
  treatment: '',
  severity: 'low',
  notes: ''
});

const handleAddRecord = async () => {
  try {
    const response = await fetch('http://localhost:8080/api/parents/medical-records', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        student_id: selectedStudent.id,
        ...formData
      })
    });
    
    const data = await response.json();
    if (data.success) {
      alert('Medical record added successfully');
      setShowAddModal(false);
      // Refresh medical records list
      fetchMedicalRecords();
    } else {
      alert(data.message);
    }
  } catch (error) {
    console.error('Error adding medical record:', error);
    alert('Failed to add medical record');
  }
};

// UI Component
<div className="flex justify-between items-center mb-4">
  <h3 className="text-lg font-semibold">Medical Records</h3>
  <Button onClick={() => setShowAddModal(true)}>
    + Add Medical Record
  </Button>
</div>

<Dialog open={showAddModal} onOpenChange={setShowAddModal}>
  <DialogContent>
    <DialogHeader>
      <DialogTitle>Add Medical Record for {selectedStudent?.name}</DialogTitle>
    </DialogHeader>
    <div className="space-y-4">
      <div>
        <label className="block text-sm font-medium mb-1">Record Type</label>
        <Select value={formData.record_type} onValueChange={(value) => setFormData({...formData, record_type: value})}>
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="general">General</SelectItem>
            <SelectItem value="illness">Illness</SelectItem>
            <SelectItem value="injury">Injury</SelectItem>
            <SelectItem value="allergy">Allergy</SelectItem>
            <SelectItem value="checkup">Checkup</SelectItem>
            <SelectItem value="vaccination">Vaccination</SelectItem>
            <SelectItem value="chronic">Chronic Condition</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Description *</label>
        <Textarea 
          value={formData.description}
          onChange={(e) => setFormData({...formData, description: e.target.value})}
          placeholder="Brief description of the condition or issue"
          required
        />
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Symptoms</label>
        <Textarea 
          value={formData.symptoms}
          onChange={(e) => setFormData({...formData, symptoms: e.target.value})}
          placeholder="List any symptoms"
        />
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Treatment</label>
        <Textarea 
          value={formData.treatment}
          onChange={(e) => setFormData({...formData, treatment: e.target.value})}
          placeholder="Current treatment or medication"
        />
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Severity</label>
        <Select value={formData.severity} onValueChange={(value) => setFormData({...formData, severity: value})}>
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="low">Low</SelectItem>
            <SelectItem value="medium">Medium</SelectItem>
            <SelectItem value="high">High</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Additional Notes</label>
        <Textarea 
          value={formData.notes}
          onChange={(e) => setFormData({...formData, notes: e.target.value})}
          placeholder="Any additional information"
        />
      </div>

      <div className="flex gap-2 justify-end">
        <Button variant="outline" onClick={() => setShowAddModal(false)}>Cancel</Button>
        <Button onClick={handleAddRecord}>Add Record</Button>
      </div>
    </div>
  </DialogContent>
</Dialog>
```

**Important Notes for Medical Records**:
- Parents can ADD and UPDATE medical records
- Parents CANNOT DELETE medical records (for data integrity)
- Medical staff can view all records added by parents
- Display records with a badge showing who added them (Parent/Medical Staff/Admin)

---

### 2. Admin/Principal Sidebar Access Control

**File**: `frontend1/src/components/layout/Sidebar.jsx` (or similar)

**Update sidebar to check user permissions**:

```jsx
// Assume user object is available from auth context
import { useAuth } from '@/context/AuthContext';

const { user } = useAuth();

// In your sidebar navigation
{user.permissions?.can_access_system_settings && (
  <NavLink 
    to="/admin/system-settings"
    className={({ isActive }) => isActive ? 'active' : ''}
  >
    <Settings className="w-5 h-5" />
    <span>System Settings</span>
  </NavLink>
)}

{user.permissions?.can_create_admins && (
  <NavLink 
    to="/admin/users"
    className={({ isActive }) => isActive ? 'active' : ''}
  >
    <Users className="w-5 h-5" />
    <span>Admin Users</span>
  </NavLink>
)}
```

**Principals should NOT see**:
- ❌ System Settings
- ❌ Admin Users management
- ❌ Create Admin button

**Principals should see**:
- ✅ Dashboard
- ✅ Students
- ✅ Teachers
- ✅ Classes
- ✅ Subjects
- ✅ Attendance
- ✅ Exams & Results
- ✅ Fees
- ✅ Reports
- ✅ Notices
- ✅ Profile

---

### 3. Fix Student Status Display

**Problem**: Code was using `student.status` but database has `student.suspension_status`

**Fix all occurrences**:

```jsx
// WRONG ❌
<Badge variant={student.status === 'active' ? 'success' : 'warning'}>
  {student.status}
</Badge>

// CORRECT ✅
<Badge variant={student.suspension_status === 'active' ? 'success' : 'warning'}>
  {student.suspension_status}
</Badge>
```

**Files to update**:
- Parent Dashboard (child status display)
- Student List pages
- Student Profile pages
- Any component showing student status

**Status Values**:
- `active` - Student is active (green badge)
- `suspended` - Student is suspended (yellow/warning badge)
- `expelled` - Student is expelled (red badge)

---

### 4. Update API Calls

**Parent Children List**:
```javascript
// GET /api/parents/children
// Response includes:
{
  success: true,
  children: [
    {
      id: 1,
      name: "John Doe",
      suspension_status: "active", // NOT 'status'
      enrollment_status: "active", // From student_enrollments
      class_name: "Form 1A",
      ...
    }
  ]
}
```

**Parent Medical Records**:
```javascript
// POST /api/parents/medical-records
{
  student_id: 1,
  record_type: "allergy", // illness, injury, checkup, vaccination, allergy, chronic, general
  description: "Peanut allergy",
  symptoms: "Rash, difficulty breathing",
  treatment: "EpiPen prescribed",
  severity: "high", // low, medium, high
  notes: "Avoid all nuts"
}

// Response:
{
  success: true,
  message: "Medical record added successfully",
  record_id: 123
}
```

---

### 5. Admin User Creation

**Only Super Admins can access this**:

```jsx
// In AdminUsers page
import { useAuth } from '@/context/AuthContext';

const { user } = useAuth();

// Check if user is super admin
if (!user.is_super_admin || !user.permissions?.can_create_admins) {
  return <div>Access Denied</div>;
}

// Form for creating admin
const handleCreateAdmin = async (formData) => {
  const response = await fetch('http://localhost:8080/api/admin/users', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      email: formData.email,
      password: formData.password,
      contact_name: formData.name,
      phone: formData.phone
    })
  });
  
  const data = await response.json();
  if (data.success) {
    alert('Admin user created successfully');
    // Refresh admin list
  } else {
    alert(data.message);
  }
};
```

---

### 6. Testing Checklist

#### As Admin (koromaemmanuel66@gmail.com):
- [  ] Can see System Settings in sidebar
- [  ] Can create new Admin users (if super admin)
- [  ] Can create Principals
- [  ] Dashboard shows all students, teachers, classes
- [  ] Can manage all features

#### As Principal (emk32770@gmail.com):
- [  ] CANNOT see System Settings in sidebar
- [  ] CANNOT see Admin Users option
- [  ] CAN see all students created by the admin
- [  ] CAN see all teachers created by the admin
- [  ] CAN see all classes, fees, attendance
- [  ] Dashboard stats match admin's data

#### As Parent:
- [  ] Can view linked children
- [  ] Children show correct `suspension_status` (not "Suspended" erroneously)
- [  ] Can view children's attendance
- [  ] Can view children's medical records
- [  ] Can ADD medical records
- [  ] Can UPDATE medical records they added
- [  ] CANNOT DELETE medical records
- [  ] Medical records show who added them (badge/label)

---

### 7. API Base URL

Make sure your frontend is using the correct API base URL:

```javascript
// In your API config or .env
const API_BASE_URL = 'http://localhost:8080/api';

// Or in .env file
VITE_API_BASE_URL=http://localhost:8080/api
```

---

### 8. Auth Context Updates

Make sure to include permissions in user object:

```javascript
// After login, fetch user permissions
const response = await fetch(`${API_BASE_URL}/admin/check-super-admin-status`, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const data = await response.json();
if (data.success) {
  setUser({
    ...user,
    is_super_admin: data.is_super_admin,
    role: data.role,
    permissions: {
      can_create_admins: data.can_create_admins,
      can_access_system_settings: data.is_super_admin || data.role === 'admin'
    }
  });
}
```

---

## Quick Deploy Steps

1. **Update all instances of `student.status` to `student.suspension_status`**
   ```bash
   # Search in VSCode: student.status
   # Replace with: student.suspension_status
   ```

2. **Add Medical Records form to Parent Dashboard**
   - Add "+ Add Medical Record" button
   - Create modal/dialog with form
   - Implement handleAddRecord function

3. **Update Sidebar component**
   - Add permission checks for System Settings
   - Hide Admin Users option for non-super-admins
   - Show/hide tabs based on user.permissions

4. **Test thoroughly**
   - Test as admin
   - Test as principal
   - Test as parent
   - Verify data inheritance works

---

## Need Help?

If you encounter issues:
1. Check browser console for errors
2. Check network tab for failed API calls
3. Verify backend is running on port 8080
4. Verify database migration was successful
5. Clear browser cache and reload

---

**Last Updated**: November 24, 2025
**Backend Changes**: ✅ Complete
**Frontend Changes**: 🔄 Pending Implementation
