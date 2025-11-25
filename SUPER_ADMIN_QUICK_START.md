# Super Admin System - Quick Start Guide

## 🚀 Setup (3 Steps)

### Step 1: Run Database Migration
```bash
# Start your database server first!
php run_super_admin_migration.php
```

### Step 2: Register First Admin (Becomes Super Admin)
1. Navigate to: `http://localhost:3000/Adminregister`
2. Fill in:
   - School Name
   - Email
   - Password
3. Click "Register"
4. **You are now a Super Admin!**

### Step 3: Access Admin Users Management
1. Login to admin dashboard
2. Click **System** in sidebar
3. Click **Admin Users**
4. Click **"Add Admin User"** button

## 📋 Quick Reference

### Admin Hierarchy
```
┌─────────────────────────────────────────┐
│         SUPER ADMIN (You)               │
│  ✓ Create admins                        │
│  ✓ Create principals                    │
│  ✓ Full system access                   │
└──────────┬──────────────────────────────┘
           │
           ├──> Regular Admin
           │    ✓ Create principals
           │    ✓ Manage school
           │    ✗ Cannot create admins
           │
           └──> Principal
                ✓ Manage operations
                ✗ Cannot create admins
                ✗ Cannot create principals
```

### API Endpoints

**Create Admin User:**
```bash
POST /api/admin/admin-users
Authorization: Bearer {token}
Content-Type: application/json

{
  "contact_name": "Admin Name",
  "email": "admin@school.com",
  "password": "secure123",
  "phone": "+1234567890"
}
```

**Get All Admins:**
```bash
GET /api/admin/admin-users
Authorization: Bearer {token}
```

**Check If Super Admin:**
```bash
GET /api/admin/super-admin-status
Authorization: Bearer {token}
```

### Frontend Routes

- **Admin Users Page:** `/Admin/admin-users`
- **Registration:** `/Adminregister`
- **Login:** `/Adminlogin`

### Database Schema

```sql
admins table:
- id
- school_name
- contact_name
- email
- password
- role (super_admin, admin, principal)
- is_super_admin (true/false)
- parent_admin_id
- created_at
- updated_at
```

## 🔍 Verification Commands

### Check Super Admin in Database
```sql
SELECT id, contact_name, email, role, is_super_admin 
FROM admins 
WHERE is_super_admin = 1;
```

### List All Admins for Your School
```sql
SELECT id, contact_name, email, role, is_super_admin 
FROM admins 
WHERE parent_admin_id = YOUR_ID OR id = YOUR_ID;
```

## ⚡ Common Tasks

### Create a New Admin User (Web UI)
1. Login as super admin
2. Sidebar → System → Admin Users
3. Click "Add Admin User"
4. Fill in details
5. Click "Create Admin"

### Generate Secure Password
- Click the **"Generate"** button in the create admin form
- 12-character password with mixed case, numbers, and symbols
- Automatically populated in the password field

### Resend Welcome Email
- New admins receive welcome email automatically
- Includes temporary password
- Configure email settings in System Settings

## 🐛 Troubleshooting

### "Access Denied" when trying to create admin
- **Cause:** You're not a super admin
- **Solution:** Check your role with `/api/admin/super-admin-status`

### Migration fails
- **Cause:** Database not running or wrong credentials
- **Solution:** Check `backend1/src/Config/database.php`

### Super admin flag not set
- **Cause:** Migration didn't run completely
- **Solution:** Re-run `php run_super_admin_migration.php`

### Can't see "Admin Users" in sidebar
- **Cause:** Frontend not updated
- **Solution:** Refresh browser (Ctrl+F5)

## 📞 Quick Help

**Check Role:**
```javascript
// In browser console
console.log(localStorage.getItem('currentRole'));
```

**View JWT Token:**
```javascript
// In browser console
const token = localStorage.getItem('token');
const payload = JSON.parse(atob(token.split('.')[1]));
console.log(payload);
// Look for: is_super_admin: true
```

## ✅ Success Indicators

You're a super admin if:
- ✅ You can see "Admin Users" menu in System section
- ✅ You can click "Add Admin User" button
- ✅ `/api/admin/super-admin-status` returns `is_super_admin: true`
- ✅ Database shows `is_super_admin = 1` for your user

## 🎯 Next Steps

1. ✅ Create additional admin users for your team
2. ✅ Assign roles appropriately
3. ✅ Configure System Settings
4. ✅ Set up email notifications
5. ✅ Start managing your school!

---

**Need more details?** See `SUPER_ADMIN_IMPLEMENTATION_COMPLETE.md`
