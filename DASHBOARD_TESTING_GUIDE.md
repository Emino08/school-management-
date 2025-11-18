# Dashboard Testing Guide

## Quick Test Steps

### 1. Clear Browser Data
```javascript
// Open browser console (F12) and run:
localStorage.clear();
sessionStorage.clear();
location.reload();
```

### 2. Login Fresh
1. Navigate to `http://localhost:5174`
2. Login with admin credentials
3. You should be redirected to dashboard

### 3. Verify Network Requests
Open Network Tab (F12 → Network) and check for these requests:

✅ **Expected Requests on Dashboard Load:**
```
GET /api/academic-years          → Should return array of academic years
GET /api/admin/stats             → Should return dashboard statistics
GET /api/admin/charts?days=30    → Should return chart data
GET /api/NoticeList/1            → Should return notices array
GET /api/SclassList/1            → Should return classes array (from Redux action)
GET /api/Students/list           → Should return students array (from Redux action)
GET /api/Teachers/list           → Should return teachers array (from Redux action)
```

### 4. Verify Console Output
Check browser console for these logs:

✅ **Expected Console Logs:**
```
✓ Token is valid with admin_id: 1
✅ Stats loaded: {students: X, classes: Y, teachers: Z, subjects: W}
✅ Charts loaded
```

❌ **Should NOT See:**
```
❌ No admin ID found
❌ academicYearLoading is not defined
❌ noticesList.map is not a function
❌ Undefined property: stdClass::$admin_id
```

---

## Detailed Verification

### A. Stats Cards Should Display:
- **Total Students**: Count from current academic year enrollments
- **Total Classes**: Active classes with students
- **Total Teachers**: Teachers with assignments
- **Total Subjects**: All subjects across classes

### B. Financial Overview Should Show:
- **Total Revenue**: SLE format (e.g., SLE 999.98)
- **Pending Fees**: SLE format with 2 decimal places
- **Collection Rate**: Percentage (e.g., 0.48%)
- **This Month**: Current month revenue

### C. Charts Should Render:
1. **Attendance Trend** - Line chart with Present/Absent/Late/Excused
2. **Students per Class** - Bar chart by class name
3. **Fees Trend** - Line chart of daily payments
4. **Results Publications** - Bar chart of published results
5. **Teacher Load** - Top 10 teachers by subjects
6. **Fees by Term** - Pie chart of term distribution
7. **Attendance by Class** - Today's attendance rates
8. **Average Grades Trend** - Line chart of grade averages

---

## Troubleshooting

### Issue: Stats showing 0 for everything

**Check:**
1. Is there a current academic year set?
   ```sql
   SELECT * FROM academic_years WHERE is_current = 1;
   ```

2. Do students have enrollments in current year?
   ```sql
   SELECT COUNT(*) FROM student_enrollments 
   WHERE academic_year_id = (SELECT id FROM academic_years WHERE is_current = 1);
   ```

3. Do teachers have assignments?
   ```sql
   SELECT COUNT(*) FROM teacher_assignments 
   WHERE academic_year_id = (SELECT id FROM academic_years WHERE is_current = 1);
   ```

**Fix:**
- Set an academic year as current in the database
- Add student enrollments for current year
- Assign teachers to subjects for current year

---

### Issue: "No admin ID found" error

**Cause:** Old JWT token doesn't have `admin_id` field

**Fix:**
1. Log out completely
2. Clear browser data
3. Log back in (new token will have `admin_id`)

---

### Issue: Charts show "width(-1) and height(-1)" error

**Cause:** Chart container doesn't have proper dimensions

**Fix:** 
- Already fixed in code with `minHeight={250}` on ResponsiveContainer
- If still occurs, check if parent Card has proper height

---

### Issue: Multiple re-renders

**Cause:** Dependencies changing unnecessarily in useEffect

**Fix:**
- Already optimized with `useMemo` and `useCallback`
- `hasFetchedInitialData` ref prevents duplicate fetches
- Debouncing on filter changes (500ms)

---

## Backend Health Check

### Test Endpoints Directly:

```bash
# Get academic years (no auth needed for this endpoint)
curl http://localhost:8080/api/academic-years

# Get stats (needs auth token)
curl http://localhost:8080/api/admin/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get charts (needs auth token)
curl "http://localhost:8080/api/admin/charts?days=30" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Database Queries for Verification

### Check Current Academic Year:
```sql
SELECT * FROM academic_years WHERE is_current = 1;
```

### Count Students in Current Year:
```sql
SELECT COUNT(DISTINCT se.student_id) as student_count
FROM student_enrollments se
JOIN students s ON se.student_id = s.id
WHERE se.academic_year_id = (SELECT id FROM academic_years WHERE is_current = 1)
AND s.admin_id = 1;
```

### Count Classes with Enrollments:
```sql
SELECT COUNT(DISTINCT se.class_id) as class_count
FROM student_enrollments se
JOIN classes c ON se.class_id = c.id
WHERE se.academic_year_id = (SELECT id FROM academic_years WHERE is_current = 1)
AND c.admin_id = 1;
```

### Count Teachers with Assignments:
```sql
SELECT COUNT(DISTINCT ta.teacher_id) as teacher_count
FROM teacher_assignments ta
JOIN teachers t ON ta.teacher_id = t.id
WHERE ta.academic_year_id = (SELECT id FROM academic_years WHERE is_current = 1)
AND t.admin_id = 1;
```

### Check Fee Payments:
```sql
SELECT 
    SUM(amount_paid) as total_collected,
    SUM(balance) as total_pending,
    (SUM(amount_paid) / NULLIF(SUM(total_amount), 0)) * 100 as collection_rate
FROM fees_payments
WHERE admin_id = 1;
```

---

## Expected Behavior After Fixes

### On Login:
1. JWT token contains `admin_id` field
2. No PHP warnings in backend logs
3. No JavaScript errors in console

### On Dashboard Load:
1. Single API call to each endpoint (not multiple)
2. Stats display correct counts from database
3. All charts render without errors
4. Currency shows as "SLE" not "₦"

### On Filter Change:
1. Debounced request after 500ms
2. Only charts endpoint called, not stats
3. Charts update with new data

### Performance:
1. Initial load: ~2-3 API calls total
2. Subsequent renders: No additional API calls
3. Filter changes: Debounced single request
4. No unnecessary re-renders

---

## Success Criteria

✅ All stats show correct numbers (not 0)
✅ Currency displays in SLE format
✅ No console errors or warnings
✅ No PHP warnings in backend log
✅ Charts render with data
✅ Notice list displays without errors
✅ Academic year selector works
✅ Filters update charts correctly
✅ No multiple API calls on mount
✅ Token refresh works on re-login

---

**Last Updated**: November 16, 2025
**Backend**: PHP 8.2.12
**Frontend**: React with Vite
**Database**: MySQL/MariaDB
