# Class CSV Import/Export - Complete Documentation

## ✅ Implementation Complete

CSV import and export functionality has been added to the Class management system.

---

## Features Added

### 1. **Export Classes to CSV**
Export all classes to a CSV file with current data including student counts and class masters.

### 2. **Import Classes from CSV**
Bulk import classes from a CSV file with validation and duplicate detection.

### 3. **Download CSV Template**
Get a pre-formatted CSV template with sample data for easy import preparation.

---

## API Endpoints

### 1. Export Classes to CSV

**Endpoint**: `GET /api/classes/export/csv`  
**Authentication**: Required (Bearer Token)  
**Response**: CSV File Download

**CSV Columns**:
- Class Name
- Grade Level
- Description  
- Capacity
- Student Count (current enrollment)
- Subject Count
- Class Master (teacher name)

**Example Request**:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8080/api/classes/export/csv \
     -o classes_export.csv
```

**Example Response** (File Content):
```csv
Class Name,Grade Level,Description,Capacity,Student Count,Subject Count,Class Master
Grade 10A,10,Science class,40,35,8,John Smith
Grade 10B,10,Arts class,35,32,6,Jane Doe
Grade 11A,11,Advanced Mathematics,30,28,7,Bob Johnson
```

---

### 2. Import Classes from CSV

**Endpoint**: `POST /api/classes/import/csv`  
**Authentication**: Required (Bearer Token)  
**Content-Type**: `multipart/form-data`  
**Request**: File upload with field name `file`

**CSV Format** (Required Columns):
1. **Class Name** (required) - Unique class name
2. **Grade Level** (optional) - Numeric grade level (0-12)
3. **Description** (optional) - Class description
4. **Capacity** (optional) - Maximum number of students

**Example Request**:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -F "file=@classes_import.csv" \
     http://localhost:8080/api/classes/import/csv
```

**Success Response**:
```json
{
  "success": true,
  "message": "Import completed",
  "imported": 15,
  "skipped": 2,
  "errors": []
}
```

**Error Response**:
```json
{
  "success": true,
  "message": "Import completed",
  "imported": 10,
  "skipped": 3,
  "errors": [
    "Row 5: Class name is required",
    "Row 12: Database error"
  ]
}
```

**Import Behavior**:
- ✅ Validates each row before import
- ✅ Skips duplicate classes (same name for same admin)
- ✅ Skips empty rows automatically
- ✅ Continues processing even if some rows fail
- ✅ Returns detailed report of imported, skipped, and failed rows

---

### 3. Download CSV Template

**Endpoint**: `GET /api/classes/template/csv`  
**Authentication**: Required (Bearer Token)  
**Response**: CSV File Download

**Example Request**:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8080/api/classes/template/csv \
     -o class_template.csv
```

**Template Content**:
```csv
Class Name,Grade Level,Description,Capacity
Grade 10A,10,Science class,40
Grade 10B,10,Arts class,35
Grade 11A,11,Advanced Mathematics,30
```

---

## Frontend Implementation

### React Component Example

```jsx
import React, { useState } from 'react';
import axios from 'axios';

const ClassCSVManager = () => {
  const [file, setFile] = useState(null);
  const [importing, setImporting] = useState(false);

  // Export classes
  const handleExport = async () => {
    try {
      const response = await axios.get(
        `${API_BASE_URL}/classes/export/csv`,
        {
          headers: { Authorization: `Bearer ${token}` },
          responseType: 'blob'
        }
      );
      
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `classes_${new Date().toISOString()}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      
      toast.success('Classes exported successfully');
    } catch (error) {
      toast.error('Export failed');
    }
  };

  // Download template
  const handleDownloadTemplate = async () => {
    try {
      const response = await axios.get(
        `${API_BASE_URL}/classes/template/csv`,
        {
          headers: { Authorization: `Bearer ${token}` },
          responseType: 'blob'
        }
      );
      
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'class_import_template.csv');
      document.body.appendChild(link);
      link.click();
      link.remove();
      
      toast.success('Template downloaded');
    } catch (error) {
      toast.error('Download failed');
    }
  };

  // Import classes
  const handleImport = async (e) => {
    e.preventDefault();
    
    if (!file) {
      toast.error('Please select a file');
      return;
    }

    const formData = new FormData();
    formData.append('file', file);

    setImporting(true);
    try {
      const response = await axios.post(
        `${API_BASE_URL}/classes/import/csv`,
        formData,
        {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'multipart/form-data'
          }
        }
      );

      const { imported, skipped, errors } = response.data;
      
      toast.success(
        `Import completed: ${imported} imported, ${skipped} skipped`
      );
      
      if (errors.length > 0) {
        console.error('Import errors:', errors);
      }
      
      setFile(null);
      // Refresh class list
      fetchClasses();
    } catch (error) {
      toast.error('Import failed: ' + error.response?.data?.message);
    } finally {
      setImporting(false);
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex gap-2">
        <button onClick={handleDownloadTemplate} className="btn btn-secondary">
          Download Template
        </button>
        <button onClick={handleExport} className="btn btn-primary">
          Export Classes
        </button>
      </div>

      <form onSubmit={handleImport} className="space-y-2">
        <input
          type="file"
          accept=".csv"
          onChange={(e) => setFile(e.target.files[0])}
          className="file-input"
        />
        <button 
          type="submit" 
          disabled={!file || importing}
          className="btn btn-success"
        >
          {importing ? 'Importing...' : 'Import Classes'}
        </button>
      </form>
    </div>
  );
};

export default ClassCSVManager;
```

---

## CSV Format Specifications

### Import CSV Format

```csv
Class Name,Grade Level,Description,Capacity
Grade 10A,10,Science stream with lab facilities,40
Grade 10B,10,Commerce stream,35
Grade 11A,11,Advanced science,30
```

**Field Specifications**:

| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| Class Name | String | Yes | Unique class identifier | "Grade 10A" |
| Grade Level | Integer | No | Numeric grade (0-12) | 10 |
| Description | String | No | Class description | "Science class" |
| Capacity | Integer | No | Max students | 40 |

**Validation Rules**:
- Class Name: Must be unique per admin
- Grade Level: Must be numeric (0-12), defaults to 0
- Capacity: Must be positive integer if provided
- Empty rows are automatically skipped

---

## Code Implementation

### Controller Methods (ClassController.php)

```php
public function exportCSV(Request $request, Response $response)
{
    // Get all classes with current data
    // Generate CSV with proper escaping
    // Return file download response
}

public function importCSV(Request $request, Response $response)
{
    // Validate file upload
    // Parse CSV with error handling
    // Validate and import each row
    // Return detailed import report
}

public function downloadTemplate(Request $request, Response $response)
{
    // Generate sample CSV template
    // Return file download response
}

private function escapeCsvField($field)
{
    // Properly escape CSV fields with quotes/commas
}
```

### Route Configuration (api.php)

```php
// Class CSV Import/Export routes
$group->get('/classes/export/csv', [ClassController::class, 'exportCSV'])
    ->add(new AuthMiddleware());
$group->post('/classes/import/csv', [ClassController::class, 'importCSV'])
    ->add(new AuthMiddleware());
$group->get('/classes/template/csv', [ClassController::class, 'downloadTemplate'])
    ->add(new AuthMiddleware());
```

---

## Testing

### Test Script
```bash
# Run automated tests
cd backend1
php test_class_csv.php
```

### Manual Testing

1. **Download Template**:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8080/api/classes/template/csv \
     -o template.csv
```

2. **Export Classes**:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8080/api/classes/export/csv \
     -o classes_export.csv
```

3. **Import Classes**:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -F "file=@classes_import.csv" \
     http://localhost:8080/api/classes/import/csv
```

---

## Error Handling

### Import Errors

**Common Errors**:
- Missing class name → Row skipped
- Duplicate class name → Row skipped
- Invalid grade level → Defaults to 0
- Invalid capacity → Ignored (NULL)
- File format error → Import stops

**Response Format**:
```json
{
  "success": true,
  "message": "Import completed",
  "imported": 10,
  "skipped": 2,
  "errors": [
    "Row 5: Class name is required",
    "Row 8: Failed to create class: Database error"
  ]
}
```

---

## Security Features

✅ **Authentication Required**: All endpoints require valid JWT token  
✅ **Admin Scoping**: Classes scoped to authenticated admin  
✅ **File Validation**: Only CSV files accepted  
✅ **Duplicate Prevention**: Existing classes are skipped  
✅ **Input Sanitization**: All CSV data sanitized before database insertion  
✅ **Temporary File Cleanup**: Uploaded files deleted after processing

---

## Performance Considerations

- **Batch Import**: Processes rows individually for better error reporting
- **Memory Efficient**: Reads CSV line-by-line, not loading entire file
- **Transaction Safety**: Each class creation is atomic
- **Duplicate Check**: Pre-checks before insertion to avoid errors

---

## Limitations

- Maximum file size: Determined by PHP `upload_max_filesize` (default 2MB)
- CSV encoding: UTF-8 recommended
- Row limit: No hard limit, but large files may timeout
- Duplicate handling: Based on class_name + admin_id uniqueness

---

## Files Modified

### Backend:
1. **ClassController.php**
   - Added `exportCSV()` method
   - Added `importCSV()` method
   - Added `downloadTemplate()` method
   - Added `escapeCsvField()` helper

2. **api.php**
   - Added CSV export route
   - Added CSV import route
   - Added template download route

### New Files:
1. **test_class_csv.php** - Test script for CSV functionality
2. **sample_class_import.csv** - Sample import file

---

## Future Enhancements

Potential improvements:
- [ ] Add progress callback for large imports
- [ ] Support Excel (.xlsx) format
- [ ] Add class update capability (not just insert)
- [ ] Add preview before import confirmation
- [ ] Support bulk delete via CSV
- [ ] Add export filters (by grade level, etc.)
- [ ] Add import validation preview
- [ ] Support custom field mapping

---

## Conclusion

✅ **CSV Import/Export Fully Implemented**  
✅ **All Tests Passing**  
✅ **No Breaking Changes**  
✅ **Ready for Production**

The class CSV import/export functionality is now available and tested. Admins can bulk import classes, export existing data, and download templates through the API endpoints.

---

**Implementation Date**: November 22, 2025  
**Status**: ✅ Complete and Tested  
**Breaking Changes**: None
