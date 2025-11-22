# 📡 API ENDPOINTS QUICK REFERENCE

Base URL: `http://localhost:8080/api`

---

## 🔔 NOTIFICATIONS (FIXED ✅)

```
GET    /api/notifications
       Get user notifications
       Auth: Required
       Returns: { success: true, notifications: [...] }

GET    /api/api/notifications
       Alias route (handles double /api/api/ paths)
       Auth: Required
       Returns: Same as above

POST   /api/notifications
       Create notification
       Auth: Required
       Body: { recipient_id, recipient_role, title, message }

GET    /api/notifications/unread-count
       Get unread notification count
       Auth: Required
       Returns: { success: true, count: 5 }

POST   /api/notifications/{id}/mark-read
       Mark notification as read
       Auth: Required
       Returns: { success: true }
```

---

## 📊 ACTIVITY LOGS (FIXED ✅)

```
GET    /api/admin/activity-logs
       Get activity logs with filters
       Auth: Admin
       Query: ?user_id=1&user_type=admin&limit=100
       Returns: { success: true, logs: [...], count: 100 }

GET    /api/admin/activity-logs/stats
       Get activity statistics
       Auth: Admin
       Returns: { success: true, stats: [...] }

GET    /api/admin/activity-logs/export
       Export logs to CSV
       Auth: Admin
       Returns: CSV file download
```

---

## 👨‍🏫 TEACHERS (ENHANCED ✅)

```
GET    /api/teachers
       Get all teachers
       Auth: Required
       Returns: { success: true, teachers: [...] }

GET    /api/teachers/{id}
       Get single teacher
       Auth: Required
       Returns: { success: true, teacher: {...} }

GET    /api/teachers/{id}/classes
       Get teacher's assigned classes ⭐ NEW FEATURE
       Auth: Required
       Returns: { success: true, classes: [...] }

GET    /api/teachers/{id}/subjects
       Get teacher's assigned subjects ⭐ NEW FEATURE
       Auth: Required
       Returns: { success: true, subjects: [...] }

POST   /api/teachers/register
       Create new teacher
       Auth: Admin
       Body: { first_name, last_name, email, password, ... }
       Note: Now accepts first_name and last_name! ⭐

PUT    /api/teachers/{id}
       Update teacher
       Auth: Admin
       Body: { first_name, last_name, email, ... }

DELETE /api/teachers/{id}
       Delete teacher (soft delete)
       Auth: Admin
       Query: ?hard=true (for permanent delete)
```

---

## 🏘️ TOWN MASTER (NEW ✅)

### Admin Town Management

```
GET    /api/admin/towns
       Get all towns
       Auth: Admin
       Returns: { success: true, towns: [...] }

POST   /api/admin/towns
       Create new town
       Auth: Admin
       Body: { name, description }
       Returns: { success: true, town: {...} }

PUT    /api/admin/towns/{id}
       Update town
       Auth: Admin
       Body: { name, description }

DELETE /api/admin/towns/{id}
       Delete town
       Auth: Admin
       Returns: { success: true }

GET    /api/admin/towns/{id}/blocks
       Get blocks for a town (A-F)
       Auth: Admin
       Returns: { success: true, blocks: [...] }

PUT    /api/admin/blocks/{id}
       Update block capacity
       Auth: Admin
       Body: { capacity }

POST   /api/admin/towns/{id}/assign-master
       Assign teacher as town master
       Auth: Admin
       Body: { teacher_id }

DELETE /api/admin/town-masters/{id}
       Remove town master assignment
       Auth: Admin
```

### Town Master Operations

```
GET    /api/town-master/my-town
       Get town master's assigned town
       Auth: Teacher (Town Master)
       Returns: { success: true, town: {...}, blocks: [...] }

GET    /api/town-master/students
       Get students in town master's town
       Auth: Teacher (Town Master)
       Query: ?block_id=1&term_id=1
       Returns: { success: true, students: [...] }

POST   /api/town-master/register-student
       Register student to block
       Auth: Teacher (Town Master)
       Body: { student_id, block_id, term_id }
       Note: Only paid students can be registered

POST   /api/town-master/attendance
       Record attendance for town
       Auth: Teacher (Town Master)
       Body: { student_id, block_id, status, date, notes }
       Note: Sends notification to parent if absent

GET    /api/town-master/attendance
       Get attendance records
       Auth: Teacher (Town Master)
       Query: ?block_id=1&date=2025-11-21
```

---

## 👥 USER ROLES (NEW ✅)

```
GET    /api/admin/user-roles
       Get all user roles in system
       Auth: Admin
       Returns: { success: true, roles: [...] }

GET    /api/admin/user-roles/available
       Get available role types
       Auth: Admin
       Returns: { success: true, roles: ['town_master', ...] }

GET    /api/admin/user-roles/{role}
       Get users with specific role
       Auth: Admin
       Example: /api/admin/user-roles/town_master
       Returns: { success: true, users: [...] }

POST   /api/admin/user-roles
       Assign role to user
       Auth: Admin
       Body: { user_id, user_type, role }

DELETE /api/admin/user-roles/{id}
       Remove role assignment
       Auth: Admin
```

---

## 📚 STUDENTS

```
GET    /api/students
       Get all students
       Auth: Required
       Returns: { success: true, students: [...] }

GET    /api/students/{id}
       Get single student
       Auth: Required

POST   /api/students/register
       Create new student
       Auth: Admin
       Body: { id_number, name, email, ... }

POST   /api/students/bulk-upload
       Bulk upload students via CSV
       Auth: Admin
       Body: FormData with CSV file

GET    /api/students/bulk-template
       Download CSV template
       Auth: Admin
       Returns: CSV file
```

---

## 🎓 CLASSES

```
GET    /api/classes
       Get all classes
       Auth: Required

GET    /api/classes/{id}
       Get single class
       Auth: Required

POST   /api/classes
       Create class
       Auth: Admin
       Body: { class_name, grade_level, section }
```

---

## 📖 SUBJECTS

```
GET    /api/subjects
       Get all subjects
       Auth: Required

GET    /api/subjects/{id}
       Get single subject
       Auth: Required

POST   /api/subjects
       Create subject
       Auth: Admin
       Body: { subject_name, subject_code }
```

---

## 🔐 AUTHENTICATION

```
POST   /api/admin/login
       Admin login
       Body: { email, password }
       Returns: { success: true, token: "...", user: {...} }

POST   /api/teachers/login
       Teacher login
       Body: { email, password }

POST   /api/students/login
       Student login
       Body: { id_number, password }
```

---

## ⚙️ SYSTEM SETTINGS

```
GET    /api/settings
       Get system settings
       Auth: Admin

PUT    /api/settings
       Update system settings
       Auth: Admin
       Body: { school_name, currency_code, email_*, ... }

POST   /api/email/test
       Test email configuration
       Auth: Admin
       Returns: { success: true, message: "Test email sent" }
```

---

## 🔑 AUTHENTICATION HEADER

All protected endpoints require:
```
Authorization: Bearer <your_jwt_token>
```

Get token from login response and include in all subsequent requests.

---

## 📝 RESPONSE FORMAT

All endpoints return:
```json
{
  "success": true|false,
  "message": "Optional message",
  "data": { ... }  // or specific keys like "teachers", "students", etc.
}
```

Error response:
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }  // Validation errors if applicable
}
```

---

## 🧪 TESTING ENDPOINTS

Use tools like:
- **Postman** - GUI for API testing
- **curl** - Command line
- **Browser DevTools** - Network tab
- **Thunder Client** - VS Code extension

Example curl:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8080/api/teachers
```

---

## 🚨 COMMON ERRORS

| Code | Meaning | Solution |
|------|---------|----------|
| 401 | Unauthorized | Check token, re-login |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Check endpoint URL |
| 405 | Method Not Allowed | Check HTTP method (GET/POST/etc) |
| 422 | Validation Error | Check request body format |
| 500 | Server Error | Check backend logs |

---

**Last Updated:** November 21, 2025
**API Version:** 1.0.0
**Status:** Production Ready ✅
