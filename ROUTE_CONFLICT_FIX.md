# Route Conflict Fix - Quick Guide

## ❌ Problem

```
FastRoute\BadRouteException: Static route "/api/admin/settings" is shadowed 
by previously defined variable route "/api/admin/([^/]+)" for method "PUT"
```

## 🔍 Root Cause

Routes are matched in order. When we added:
```php
$group->put('/admin/{id}', [AdminController::class, 'updateProfile']);
```

It was defined BEFORE:
```php
$group->put('/admin/settings', [SettingsController::class, 'updateSettings']);
```

The router matched `/admin/settings` against the pattern `/admin/{id}` first, causing a conflict.

## ✅ Solution

**Rule**: Specific static routes must come BEFORE variable routes with patterns.

### Before (Broken):
```php
// Line 147-154
$group->post('/admin/register', ...);
$group->post('/admin/login', ...);
$group->get('/admin/profile', ...);
$group->put('/admin/profile', ...);
$group->put('/admin/{id}', ...);  // ❌ TOO EARLY
$group->get('/admin/stats', ...);
$group->delete('/admin/{id}', ...); // ❌ TOO EARLY

// Line 689 (much later)
$group->put('/admin/settings', ...); // ❌ SHADOWED
```

### After (Fixed):
```php
// Line 147-152 - Specific routes first
$group->post('/admin/register', ...);
$group->post('/admin/login', ...);
$group->get('/admin/profile', ...);
$group->put('/admin/profile', ...);
$group->get('/admin/stats', ...);
$group->get('/admin/charts', ...);

// Line 689-691 - More specific routes
$group->get('/admin/settings', ...);
$group->put('/admin/settings', ...);
$group->put('/admin/settings/general', ...);

// Line 720-721 - Variable routes LAST
$group->put('/admin/{id}', ...);  // ✅ AFTER ALL STATIC ROUTES
$group->delete('/admin/{id}', ...); // ✅ AFTER ALL STATIC ROUTES
```

## 📝 Route Order Rules

1. **Exact static routes first**
   ```php
   $group->get('/admin/profile', ...);
   $group->get('/admin/stats', ...);
   $group->get('/admin/settings', ...);
   ```

2. **Nested static routes next**
   ```php
   $group->get('/admin/settings/general', ...);
   $group->get('/admin/reports/overview', ...);
   ```

3. **Variable routes last**
   ```php
   $group->get('/admin/{id}', ...);
   $group->put('/admin/{id}', ...);
   ```

## 🧪 Testing

Test the API loads without error:
```bash
cd backend1
php -r "require 'public/index.php';"
```

Should return JSON with `"status": "running"` (no exceptions).

## 📌 Summary

| Issue | Solution |
|-------|----------|
| Route conflict error | Moved variable routes to end |
| Settings route shadowed | Placed before `{id}` routes |
| Application wouldn't start | Fixed route order |

**Result**: ✅ All routes working correctly

---

**Fixed**: November 22, 2025  
**Files Modified**: `backend1/src/Routes/api.php`  
**Lines Changed**: 2 blocks moved
