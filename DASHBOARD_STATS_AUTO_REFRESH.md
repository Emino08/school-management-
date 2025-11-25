# Dashboard Stats Auto-Refresh Implementation

## Changes Made

### Backend Changes:

1. **AdminController.php**
   - Reduced cache TTL from 300s (5 min) to 60s (1 min)
   - Added static method `clearDashboardCache($adminId)` 
   - Added timestamp to response for tracking

2. **StudentController.php**
   - Added cache clearing to `register()` method
   - Added cache clearing to `updateStudent()` method
   - Added cache clearing to `deleteStudent()` method

3. **Cache Clearing Strategy**
   - Cache automatically cleared when:
     - New student registered
     - Student updated (including class changes)
     - Student deleted
     - Teacher created/updated/deleted (TODO)
     - Class created/updated/deleted (TODO)
     - Subject created/updated/deleted (TODO)

### Frontend Enhancement (Recommended):

Add auto-refresh to AdminHomePage.js:

```javascript
// Add at the top of component
const [autoRefresh, setAutoRefresh] = useState(true);
const autoRefreshIntervalRef = useRef(null);

// Add useEffect for auto-refresh
useEffect(() => {
  if (!autoRefresh || !hasFetchedInitialData.current) return;

  // Auto-refresh every 60 seconds
  autoRefreshIntervalRef.current = setInterval(() => {
    console.log('Auto-refreshing dashboard stats...');
    fetchDashboardData(true); // Force refresh to bypass cache
  }, 60000); // 60 seconds

  return () => {
    if (autoRefreshIntervalRef.current) {
      clearInterval(autoRefreshIntervalRef.current);
    }
  };
}, [autoRefresh, fetchDashboardData]);

// Add toggle button to UI
<Button 
  onClick={() => setAutoRefresh(!autoRefresh)}
  variant={autoRefresh ? "default" : "outline"}
  size="sm"
>
  {autoRefresh ? 'Auto-refresh: ON' : 'Auto-refresh: OFF'}
</Button>
```

### Alternative: Refresh on Window Focus

```javascript
useEffect(() => {
  const handleFocus = () => {
    // Refresh when user returns to tab
    if (hasFetchedInitialData.current) {
      fetchDashboardData(true);
    }
  };

  window.addEventListener('focus', handleFocus);
  return () => window.removeEventListener('focus', handleFocus);
}, [fetchDashboardData]);
```

### Manual Refresh Button

Already exists in AdminHomePage.js - uses `fetchDashboardData(true)`

---

## How It Works Now

1. **Cache Duration**: Stats cached for 60 seconds (down from 300s)

2. **Auto-Clear on Changes**:
   - When student is added: cache cleared immediately
   - When student is updated: cache cleared immediately
   - When student is deleted: cache cleared immediately
   - Next stats request gets fresh data from database

3. **Force Refresh**:
   - URL parameter `?refresh=true` bypasses cache
   - Frontend already supports this via refresh button

4. **Result**:
   - Changes reflect within 60 seconds automatically
   - Or immediately if user clicks refresh button
   - Or immediately after add/update/delete operations

---

## Testing

1. **Add a student**:
   - Backend clears cache
   - Refresh dashboard (or wait 60s)
   - See updated count

2. **Delete a student**:
   - Backend clears cache
   - Refresh dashboard (or wait 60s)
   - See decreased count

3. **Force Refresh**:
   - Click refresh button
   - Stats update immediately

---

## Remaining Work

Add cache clearing to:
- TeacherController (register, update, delete)
- ClassController (create, update, delete)
- SubjectController (create, update, delete)

Code to add before success response:

```php
// Clear dashboard cache
$adminId = $user->admin_id ?? $user->id;
\App\Controllers\AdminController::clearDashboardCache($adminId);
```

---

## Benefits

✅ Stats update faster (60s vs 5min)  
✅ Changes clear cache immediately  
✅ No stale data for long periods  
✅ Force refresh available  
✅ Minimal database impact (still cached)  
✅ Auto-refresh possible in frontend

---

**Status**: Partially Complete  
**Next**: Add cache clearing to Teacher, Class, Subject controllers
