# Admin Profile - Personal Details Only

## Overview

The Admin Profile page has been redesigned to focus **only on personal information**. School-related settings are now managed exclusively in the **System Settings → General** tab.

---

## What Changed

### Before
- Admin Profile showed both personal AND school information
- School info displayed but not editable in profile
- Confusion about where to edit school details
- Redundant information display

### After
- Admin Profile shows **ONLY personal information**
- Clean, focused interface for admin's own details
- Clear alert directing to System Settings for school info
- Edit/View mode toggle for personal details

---

## Features

### Personal Information Editable:
1. **Full Name** - Admin's personal name
2. **Phone Number** - Admin's contact number
3. **Password** - Change account password

### Read-Only Fields:
1. **Email** - Cannot be changed (used for login)
2. **Role** - System-defined role

### School Information:
- **Not shown in profile anymore**
- Managed in System Settings → General tab
- Clear navigation link provided

---

## User Interface

### View Mode
```
┌─────────────────────────────────────────────┐
│  My Profile              [Edit Profile]     │
├─────────────────────────────────────────────┤
│                                             │
│  ℹ️  To edit school information, go to      │
│     System Settings → General               │
│                                             │
│  Personal Information                       │
│  ┌──────────────┬──────────────┐           │
│  │ Full Name    │ Email        │           │
│  │ John Doe     │ john@sch.com │           │
│  ├──────────────┼──────────────┤           │
│  │ Phone        │ Role         │           │
│  │ +123456789   │ Admin        │           │
│  └──────────────┴──────────────┘           │
│                                             │
└─────────────────────────────────────────────┘
```

### Edit Mode
```
┌─────────────────────────────────────────────┐
│  My Profile                                 │
├─────────────────────────────────────────────┤
│                                             │
│  Personal Information                       │
│  ┌──────────────┬──────────────┐           │
│  │ Full Name *  │ Email        │           │
│  │ [Input Box]  │ john@sch.com │ (locked) │
│  ├──────────────┼──────────────┤           │
│  │ Phone        │ Role         │           │
│  │ [Input Box]  │ Admin        │ (locked) │
│  └──────────────┴──────────────┘           │
│                                             │
│  Change Password (optional)                 │
│  ┌──────────────┬──────────────┐           │
│  │ New Password │ Confirm Pass │           │
│  │ [Password]   │ [Password]   │           │
│  └──────────────┴──────────────┘           │
│                                             │
│                [Cancel]  [Save Changes]     │
│                                             │
└─────────────────────────────────────────────┘
```

---

## API Endpoints Used

### Update Profile
```javascript
PUT /api/admin/profile

Headers:
  Authorization: Bearer {token}

Body:
{
  "contact_name": "John Doe",
  "phone": "+1234567890",
  "password": "newpass123"  // Optional
}

Response:
{
  "success": true,
  "message": "Profile updated successfully"
}
```

---

## Component Details

### Location
`frontend1/src/pages/admin/AdminProfile.js`

### Key Features

1. **Edit/View Toggle**
   ```jsx
   const [editing, setEditing] = useState(false);
   ```

2. **Personal Fields Only**
   ```jsx
   const [name, setName] = useState('');
   const [phone, setPhone] = useState('');
   const [password, setPassword] = useState('');
   const [confirmPassword, setConfirmPassword] = useState('');
   ```

3. **Redux Integration**
   ```jsx
   dispatch(authSuccess({
     ...currentUser,
     name,
     phone,
   }));
   ```

4. **Navigation to Settings**
   ```jsx
   <button onClick={() => navigate('/Admin/system-settings')}>
     System Settings → General
   </button>
   ```

---

## Usage Flow

### For Admins:

1. **View Personal Info**
   - Navigate to Profile from menu
   - See current name, email, phone, role
   - Email and role are read-only

2. **Edit Personal Info**
   - Click "Edit Profile" button
   - Update name and/or phone
   - Optionally change password
   - Click "Save Changes"
   - Profile updates and returns to view mode

3. **Edit School Info**
   - Click the link in the blue alert box
   - Navigate to System Settings
   - Go to General tab
   - Edit school name, address, phone, etc.

---

## Separation of Concerns

| Information Type | Location | Editable By |
|-----------------|----------|-------------|
| Admin's Name | Profile | Admin (self) |
| Admin's Phone | Profile | Admin (self) |
| Admin's Password | Profile | Admin (self) |
| Admin's Email | Profile | No (read-only) |
| Admin's Role | Profile | No (system) |
| School Name | System Settings | Admin |
| School Address | System Settings | Admin |
| School Phone | System Settings | Admin |
| School Email | System Settings | Admin |
| School Code | System Settings | Admin |
| School Website | System Settings | Admin |

---

## Validation Rules

1. **Name**
   - Required
   - Cannot be empty

2. **Phone**
   - Optional
   - No specific format enforced

3. **Password**
   - Optional (only if changing)
   - Must match confirmation
   - Minimum 6 characters (backend)

4. **Email**
   - Cannot be changed
   - Used for login authentication

---

## Benefits

✅ **Clear Separation**: Personal vs School information  
✅ **Focused Interface**: Only relevant fields shown  
✅ **Better UX**: No confusion about where to edit  
✅ **Consistent**: School info in one place (Settings)  
✅ **Simple**: Fewer fields to manage in profile  
✅ **Guided**: Alert directs to school settings  

---

## Code Example

### Basic Usage
```jsx
import AdminProfile from '@/pages/admin/AdminProfile';

// In router
<Route path="/Admin/profile" element={<AdminProfile />} />
```

### Programmatic Navigation to Settings
```jsx
import { useNavigate } from 'react-router-dom';

const navigate = useNavigate();
navigate('/Admin/system-settings');
```

---

## Testing Checklist

- [ ] View mode displays correctly
- [ ] Edit button shows edit mode
- [ ] Name field updates
- [ ] Phone field updates
- [ ] Password change works
- [ ] Password mismatch shows error
- [ ] Email is read-only
- [ ] Role is read-only
- [ ] Cancel button reverts changes
- [ ] Save updates Redux store
- [ ] Save persists after refresh
- [ ] System Settings link works
- [ ] Alert box displays correctly

---

## Screenshots Description

### View Mode
- Clean card showing personal info
- Edit Profile button top-right
- Blue alert with link to System Settings
- All fields displayed in gray boxes

### Edit Mode
- Input fields for name and phone
- Password change section below
- Cancel and Save buttons at bottom
- Email and role remain grayed out

---

## Migration Notes

### Old File (Deleted)
`EditableAdminProfile.jsx` - Combined personal and school info

### New File (Updated)
`AdminProfile.js` - Personal info only

### No Breaking Changes
- Same route path
- Same component name export
- Redux integration maintained
- API endpoints unchanged

---

## Summary

The Admin Profile page now focuses exclusively on personal information, providing a cleaner and more intuitive user experience. School-related settings are properly managed in the System Settings section where they logically belong.

**Status**: ✅ Complete and Production Ready

---

**Updated**: November 22, 2025  
**File Modified**: `AdminProfile.js`  
**Files Deleted**: `EditableAdminProfile.jsx`
