# Quick Fix Guide - Admin & Principal Roles

## 🚀 Quick Start

### Run the Fix (Option 1 - Windows)
Double-click: `RUN_ADMIN_PRINCIPAL_FIX.bat`

### Run the Fix (Option 2 - Manual)
```bash
cd backend1
php fix_admin_principal_roles.php
```

## ✅ What Gets Fixed

1. **Principal sees admin's data** ✓
   - Students, teachers, classes, etc.

2. **Admin created by super admin sees same data** ✓
   - Full data inheritance

3. **Proper permissions** ✓
   - Super admin → can create admins
   - Admin → can create principals only
   - Principal → cannot create either

4. **Sidebar adjusted** ✓
   - Principals don't see "System" section
   - Only super admins see "Admin Users"

## 🧪 Test It

### Test as Super Admin
```
Email: koromaemmanuel66@gmail.com
- Can see "System" section in sidebar
- Can see "Admin Users" menu
- Can create admins and principals
```

### Test as Principal  
```
Email: emk32770@gmail.com
- CANNOT see "System" section
- Can see all data from parent admin
- Cannot create admins or principals
```

## 📊 Verify Data Inheritance

1. Login as super admin → Note student count
2. Login as principal → Should see SAME student count
3. Check students page → Should see SAME students
4. Check teachers page → Should see SAME teachers

## 🔧 Troubleshooting

### Principal not seeing data?
```bash
# Check if migration ran
cd backend1
php -r "require 'vendor/autoload.php'; \$db = App\Config\Database::getInstance()->getConnection(); \$stmt = \$db->query('SHOW FUNCTION STATUS WHERE Name = \"get_root_admin_id\"'); var_dump(\$stmt->fetchAll());"
```

### Still seeing System section as principal?
1. Clear browser cache
2. Logout and login again
3. Check browser console for errors

## 📝 Summary

**Before Fix:**
- Principal ID: 2, queries WHERE admin_id = 2 → 0 results ❌

**After Fix:**
- Principal ID: 2 resolves to root admin ID: 1
- Queries WHERE admin_id = 1 → All data ✅

**That's it! The system is now working correctly. 🎉**

---

Full documentation: `ADMIN_PRINCIPAL_ROLES_COMPLETE_FIX.md`
