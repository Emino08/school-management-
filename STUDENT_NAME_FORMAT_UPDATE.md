# Student Name Format Update - First Name & Last Name

## Overview
Updated student registration and bulk upload to use separate First Name and Last Name fields instead of a single Name field.

---

## Database Changes

### Migration Completed ✅

**New Columns Added to `students` table:**
- `first_name` VARCHAR(100) NULL
- `last_name` VARCHAR(100) NULL

**Existing Column Retained:**
- `name` VARCHAR(255) - Now auto-generated from first_name + last_name

**Migration Script:**
```bash
php backend1/migrate_student_names.php
```

### Schema:
```sql
ALTER TABLE students ADD COLUMN first_name VARCHAR(100) NULL AFTER name;
ALTER TABLE students ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name;
```

**All existing student records were automatically migrated:**
- Full names were split at the first space
- "John Doe" → first_name: "John", last_name: "Doe"
- "Mary Jane Smith" → first_name: "Mary", last_name: "Jane Smith"

---

## API Changes

### Student Registration

**Endpoint:** `POST /api/students/register`

**Old Format:**
```json
{
  "name": "John Doe",
  "roll_number": "ID001",
  "password": "pass123",
  "class_id": 1
}
```

**New Format (Recommended):**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "roll_number": "ID001",
  "password": "pass123",
  "class_id": 1
}
```

**Backward Compatible:**
```json
{
  "name": "John Doe",
  "roll_number": "ID001",
  "password": "pass123",
  "class_id": 1
}
```
*Note: If you provide `name` only, it will automatically split into first_name and last_name*

### Validation Rules:
- `first_name`: Required
- `last_name`: Required
- `roll_number`: Required
- `class_id`: Required
- `password`: Required (min 6 characters)

---

## CSV Bulk Upload

### Template Download

**Endpoint:** `GET /api/students/bulk-template`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Download Template:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  http://localhost:8080/api/students/bulk-template \
  -o students_template.csv
```

### New CSV Format

**Headers (in order):**
1. First Name *(required)*
2. Last Name *(required)*
3. ID Number *(required)*
4. Password *(required)*
5. Class Name *(optional)*
6. Email *(optional)*
7. Phone *(optional)*
8. Address *(optional)*
9. Date of Birth *(optional, format: YYYY-MM-DD)*
10. Gender *(optional: male/female/other)*
11. Parent Name *(optional)*
12. Parent Phone *(optional)*

### Sample CSV:
```csv
First Name,Last Name,ID Number,Password,Class Name,Email,Phone,Address,Date of Birth,Gender,Parent Name,Parent Phone
John,Doe,ID2025001,secret123,Grade 1 A,john@example.com,5551234,10 Main St,2012-05-01,male,Jane Doe,5559999
Mary,Smith,ID2025002,pass456,Grade 2 B,mary@example.com,5555678,20 Oak Ave,2011-03-15,female,Bob Smith,5551111
```

### Bulk Upload Endpoint

**Endpoint:** `POST /api/students/bulk-upload`

**Request:**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@students.csv" \
  http://localhost:8080/api/students/bulk-upload
```

**Response:**
```json
{
  "success": true,
  "message": "Bulk upload completed",
  "summary": {
    "created": 45,
    "failed": 2,
    "total": 47
  },
  "errors": [
    {
      "row": 10,
      "error": "First name is required"
    },
    {
      "row": 23,
      "error": "ID number already exists"
    }
  ]
}
```

---

## Backward Compatibility

### The system still supports old formats:

#### 1. Single "Name" field in API
If you send only `name` without `first_name` and `last_name`:
```json
{
  "name": "John Doe",
  "roll_number": "ID001"
}
```

**Auto-converts to:**
- first_name: "John"
- last_name: "Doe"  
- name: "John Doe" (auto-generated)

#### 2. CSV with "Name" column
If your CSV still has a "Name" column instead of "First Name" and "Last Name", it will still work.

**Old CSV (still works):**
```csv
Name,ID Number,Password,Class Name
John Doe,ID001,pass123,Grade 1 A
```

**How it works:**
- System detects "name" column
- Automatically splits at first space
- Stores as first_name + last_name

---

## Name Splitting Logic

The `extractNameParts()` method handles various formats:

### Scenarios:

**1. Both first_name and last_name provided:**
```json
Input: { "first_name": "John", "last_name": "Doe" }
Result: first_name="John", last_name="Doe", name="John Doe"
```

**2. Only name provided:**
```json
Input: { "name": "John Doe Smith" }
Result: first_name="John", last_name="Doe Smith", name="John Doe Smith"
```

**3. Single name provided:**
```json
Input: { "name": "John" }
Result: first_name="John", last_name="", name="John"
```

**4. first_name only:**
```json
Input: { "first_name": "John" }
Result: first_name="John", last_name="", name="John"
```

---

## Frontend Updates Required

### Student Registration Form

**Before:**
```jsx
<input 
  name="name" 
  placeholder="Full Name" 
  required 
/>
```

**After:**
```jsx
<input 
  name="first_name" 
  placeholder="First Name" 
  required 
/>
<input 
  name="last_name" 
  placeholder="Last Name" 
  required 
/>
```

### Display Student Names

**Before:**
```jsx
<span>{student.name}</span>
```

**After (recommended):**
```jsx
<span>{student.first_name} {student.last_name}</span>
```

Or use the full name field:
```jsx
<span>{student.name}</span>
```
*Note: The `name` field is still populated for backward compatibility*

---

## Student Response Format

**API Response includes all three fields:**
```json
{
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "name": "John Doe",
  "roll_number": "ID001",
  "email": "john@example.com",
  ...
}
```

---

## Excel/XLSX Upload Support

Both CSV and XLSX (Excel) formats are supported:

**Upload Excel file:**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@students.xlsx" \
  http://localhost:8080/api/students/bulk-upload
```

**Excel Requirements:**
- Must have headers in first row
- Column headers must match template (case-insensitive)
- Use first worksheet only

---

## Testing

### 1. Test CSV Template Download
```bash
curl -H "Authorization: Bearer TOKEN" \
  http://localhost:8080/api/students/bulk-template \
  -o template.csv

cat template.csv
```

### 2. Test Single Student Registration
```bash
curl -X POST http://localhost:8080/api/students/register \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Jane",
    "last_name": "Smith",
    "roll_number": "ID2025050",
    "password": "test123",
    "class_id": 1
  }'
```

### 3. Test Bulk Upload
Create `test_students.csv`:
```csv
First Name,Last Name,ID Number,Password,Class Name
Alice,Johnson,ID2025051,pass123,Grade 1 A
Bob,Williams,ID2025052,pass456,Grade 2 B
```

Upload:
```bash
curl -X POST http://localhost:8080/api/students/bulk-upload \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@test_students.csv"
```

### 4. Verify Database
```sql
SELECT id, first_name, last_name, name, roll_number 
FROM students 
ORDER BY id DESC 
LIMIT 10;
```

---

## Error Handling

### Common Errors:

**1. Missing first_name or last_name:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "first_name": "First name is required",
    "last_name": "Last name is required"
  }
}
```

**2. CSV row missing name:**
```json
{
  "success": true,
  "summary": { "failed": 1 },
  "errors": [
    {
      "row": 5,
      "error": "First name is required (provide first_name and last_name OR name)"
    }
  ]
}
```

**3. Duplicate ID Number:**
```json
{
  "success": false,
  "message": "ID number already exists"
}
```

---

## Migration Summary

✅ **Completed:**
- Database columns added (first_name, last_name)
- Existing data migrated automatically
- API endpoints updated
- CSV template updated
- Bulk upload logic updated
- Backward compatibility maintained

✅ **Features:**
- Split name automatically from full name
- Support both formats (old and new)
- Validation for required fields
- Error reporting per row in bulk upload
- Excel (.xlsx) support

---

## Files Modified

### Backend:
- ✅ `backend1/src/Controllers/StudentController.php` - Updated register, bulkUpload, bulkTemplate
- ✅ `backend1/src/Models/Student.php` - Already compatible (no changes needed)
- ✅ `backend1/migrate_student_names.php` - Migration script
- ✅ Database: `students` table - Added first_name, last_name columns

### Documentation:
- ✅ This file: `STUDENT_NAME_FORMAT_UPDATE.md`

---

## Rollback (if needed)

If you need to rollback:

```sql
-- 1. Remove new columns
ALTER TABLE students DROP COLUMN first_name;
ALTER TABLE students DROP COLUMN last_name;

-- 2. Revert code changes using git
git checkout HEAD -- backend1/src/Controllers/StudentController.php
```

---

## Next Steps

1. ✅ Database migration completed
2. ✅ Backend updated
3. ⏳ Update frontend registration forms (split name field)
4. ⏳ Update frontend student display (show first_name + last_name)
5. ⏳ Test bulk upload with new CSV format
6. ⏳ Update any reports/exports to include first_name and last_name

---

## Support

**Test the new format:**
1. Download new CSV template from System
2. Fill in First Name and Last Name columns
3. Upload and verify students are created correctly

**For questions:**
- Check validation errors in API response
- Verify CSV headers match template exactly
- Ensure first_name and last_name are both provided
- Check database with: `SELECT * FROM students WHERE id = X`
