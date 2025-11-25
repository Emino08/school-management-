# Route Ordering Guide - Preventing Route Conflicts

## 🎯 Critical Rule

**SPECIFIC ROUTES MUST ALWAYS COME BEFORE VARIABLE ROUTES**

FastRoute matches routes in the order they are defined. Once a route pattern matches, it stops looking for better matches.

---

## ✅ Route Ordering Priority

1. **Exact static routes** (e.g., `/admin/profile`, `/admin/settings`)
2. **Nested static routes** (e.g., `/admin/settings/general`, `/classes/export/csv`)
3. **Specific parameterized routes** (e.g., `/students/class/{id}`, `/admin/notifications/{id}`)
4. **Generic variable routes** (e.g., `/admin/{id}`, `/students/{id}`)

---

## 🔴 Common Mistakes That Cause Conflicts

### ❌ WRONG - Variable route before static routes:

```php
// This will shadow all /admin/* routes!
$group->delete('/admin/{id}', [AdminController::class, 'deleteAdmin']);

// These will NEVER be reached:
$group->get('/admin/settings', [SettingsController::class, 'getSettings']);
$group->get('/admin/towns', [TownMasterController::class, 'getAllTowns']);
$group->get('/admin/user-roles', [UserRoleController::class, 'getAllRoles']);
```

### ✅ CORRECT - Static routes before variable routes:

```php
// Specific routes first
$group->get('/admin/settings', [SettingsController::class, 'getSettings']);
$group->get('/admin/towns', [TownMasterController::class, 'getAllTowns']);
$group->get('/admin/user-roles', [UserRoleController::class, 'getAllRoles']);

// Variable route LAST (after ALL specific routes)
$group->delete('/admin/{id}', [AdminController::class, 'deleteAdmin']);
```

---

## 📋 Route Ordering Checklist

For each route group (e.g., `/admin/*`, `/students/*`, `/classes/*`):

### Step 1: List ALL routes in that group
```bash
# Example for /classes routes:
GET    /classes
POST   /classes
GET    /classes/export/csv         ← specific
POST   /classes/import/csv         ← specific
GET    /classes/template/csv       ← specific
GET    /classes/{id}               ← variable
GET    /classes/{id}/subjects      ← variable
PUT    /classes/{id}               ← variable
DELETE /classes/{id}               ← variable
```

### Step 2: Categorize routes
- **Category A**: No variables (e.g., `/classes`, `/classes/export/csv`)
- **Category B**: Has variables (e.g., `/classes/{id}`)

### Step 3: Order correctly
1. Category A routes (no variables) first
2. Category B routes (with variables) last

---

## 🛠️ Fixed Issues (November 22, 2025)

### Issue 1: `/admin/{id}` shadowing routes ✅ FIXED

**Problem**: `/admin/{id}` DELETE route at line 727 was defined BEFORE:
- `/admin/towns/*` (lines 738-747)
- `/admin/user-roles/*` (lines 764-769)

**Solution**: Moved `/admin/{id}` to the very end (after line 769)

### Issue 2: `/classes/export/csv` shadowed ✅ FIXED

**Problem**: `/classes/{id}` routes (lines 282-286) were defined BEFORE:
- `/classes/export/csv` (line 289)
- `/classes/import/csv` (line 290)
- `/classes/template/csv` (line 291)

**Solution**: Moved CSV routes before all `/classes/{id}` routes

---

## 📝 Code Examples

### Example 1: Admin Routes (CORRECT ORDER)

```php
// 1. Authentication routes (no params)
$group->post('/admin/register', [AdminController::class, 'register']);
$group->post('/admin/login', [AdminController::class, 'login']);
$group->get('/admin/profile', [AdminController::class, 'getProfile']);
$group->put('/admin/profile', [AdminController::class, 'updateProfile']);

// 2. Dashboard routes (no params)
$group->get('/admin/stats', [AdminController::class, 'getDashboardStats']);
$group->get('/admin/charts', [AdminController::class, 'getDashboardCharts']);

// 3. Specific feature routes (no params)
$group->get('/admin/settings', [SettingsController::class, 'getSettings']);
$group->put('/admin/settings', [SettingsController::class, 'updateSettings']);

// 4. Nested specific routes (no params in main segment)
$group->get('/admin/settings/general', [SettingsController::class, 'updateSettings']);
$group->post('/admin/settings/backup', [SettingsController::class, 'createBackup']);

// 5. Collection routes
$group->get('/admin/towns', [TownMasterController::class, 'getAllTowns']);
$group->post('/admin/towns', [TownMasterController::class, 'createTown']);

// 6. Specific resource routes (with params in nested paths)
$group->get('/admin/notifications/{id}', [NotificationController::class, 'getNotification']);
$group->put('/admin/towns/{id}', [TownMasterController::class, 'updateTown']);

// 7. Variable catch-all routes (LAST!)
$group->delete('/admin/{id}', [AdminController::class, 'deleteAdmin']);
```

### Example 2: Classes Routes (CORRECT ORDER)

```php
// 1. Collection routes
$group->post('/classes', [ClassController::class, 'create']);
$group->get('/classes', [ClassController::class, 'getAll']);

// 2. Action routes (specific paths)
$group->get('/classes/export/csv', [ClassController::class, 'exportCSV']);
$group->post('/classes/import/csv', [ClassController::class, 'importCSV']);
$group->get('/classes/template/csv', [ClassController::class, 'downloadTemplate']);

// 3. Variable resource routes (LAST!)
$group->get('/classes/{id}', [ClassController::class, 'getClass']);
$group->get('/classes/{id}/subjects', [ClassController::class, 'getClassSubjects']);
$group->put('/classes/{id}', [ClassController::class, 'update']);
$group->delete('/classes/{id}', [ClassController::class, 'delete']);
```

---

## 🧪 Testing Route Order

### Test 1: Check API loads without errors
```bash
cd backend1
php -r "require 'public/index.php';"
```

Should return JSON with `"status": "running"` and NO exceptions.

### Test 2: Test specific routes work
```bash
# Test that /admin/settings is NOT shadowed by /admin/{id}
curl http://localhost:8000/api/admin/settings

# Test that /classes/export/csv is NOT shadowed by /classes/{id}
curl http://localhost:8000/api/classes/export/csv
```

---

## 🚨 When Adding New Routes

### Checklist:
- [ ] Does the new route have a variable parameter (e.g., `{id}`)?
- [ ] If YES, is it placed AFTER all static routes in the same group?
- [ ] If NO, is it placed BEFORE all variable routes in the same group?
- [ ] Test: Does `php -r "require 'public/index.php';"` run without errors?
- [ ] Test: Can you access both the new route and existing routes?

### Quick Reference:

**Adding a static route** (e.g., `/admin/backup`):
→ Place it BEFORE any `/admin/{id}` routes

**Adding a variable route** (e.g., `/admin/reports/{id}`):
→ Place it AFTER static `/admin/reports/*` routes but BEFORE `/admin/{id}`

**Adding a catch-all route** (e.g., `/admin/{id}`):
→ Place it at the VERY END of all `/admin/*` routes

---

## 📚 Route Pattern Specificity

From most specific to least specific:

1. `/admin/settings/general` (exact path, 3 segments)
2. `/admin/settings` (exact path, 2 segments)
3. `/admin/notifications/{id}` (2 static + 1 variable)
4. `/admin/{id}` (1 static + 1 variable) ← LEAST SPECIFIC

**Rule**: More specific patterns must be defined first!

---

## ✅ Summary

| Rule | Example |
|------|---------|
| Static before variable | `/admin/settings` before `/admin/{id}` |
| Longer paths before shorter | `/admin/settings/general` before `/admin/settings` |
| Specific params before generic | `/admin/towns/{id}` before `/admin/{id}` |
| Test after changes | `php -r "require 'public/index.php';"` |

---

**Last Updated**: November 22, 2025  
**Files Modified**: `backend1/src/Routes/api.php`  
**Issues Fixed**: 2 route shadowing conflicts

**Remember**: When in doubt, put variable routes LAST! 🎯
