# User Roles, Urgent Notifications & Town Master Enhancements

## Complete Implementation Summary

### ✅ 1. User Roles System

#### Database Schema
```sql
ALTER TABLE teachers 
ADD COLUMN is_exam_officer TINYINT(1) DEFAULT 0,
ADD COLUMN is_finance_officer TINYINT(1) DEFAULT 0,
ADD COLUMN is_principal TINYINT(1) DEFAULT 0;
```

**Available Roles:**
- **Town Master** (`is_town_master`)
- **Exam Officer** (`is_exam_officer`)
- **Finance Officer** (`is_finance_officer`)
- **Principal** (`is_principal`)

#### API Endpoints

**1. Get Teachers by Role**
```
GET /api/admin/users/role/{role}
Authorization: Bearer {admin_token}

Roles: town-master, exam-officer, finance, principal

Response:
{
  "success": true,
  "role": "town-master",
  "teachers": [
    {
      "id": 5,
      "first_name": "John",
      "last_name": "Doe",
      "name": "John Doe",
      "email": "john@school.com",
      "phone": "+1234567890",
      "is_town_master": 1,
      "is_exam_officer": 0,
      "is_finance_officer": 0,
      "is_principal": 0,
      "town_id": 1,
      "town_name": "North Town",
      "class_count": 3,
      "subject_count": 2
    }
  ],
  "count": 1
}
```

**2. Get Roles Summary**
```
GET /api/admin/users/roles/summary
Authorization: Bearer {admin_token}

Response:
{
  "success": true,
  "summary": {
    "town_masters": 5,
    "exam_officers": 3,
    "finance_officers": 2,
    "principals": 1,
    "total_teachers": 50
  }
}
```

### ✅ 2. Urgent Notifications System

#### Database Schema
```sql
CREATE TABLE urgent_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    student_id INT NULL,
    type ENUM('repeated_absence', 'payment_overdue', 'disciplinary', 'health', 'academic', 'other'),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    action_required TINYINT(1) DEFAULT 1,
    action_taken TINYINT(1) DEFAULT 0,
    action_taken_by INT NULL COMMENT 'Principal ID',
    action_taken_at TIMESTAMP NULL,
    action_notes TEXT NULL,
    related_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE principal_notification_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    principal_id INT NOT NULL,
    notification_type ENUM('attendance', 'payment', 'disciplinary', 'academic', 'health', 'all'),
    is_active TINYINT(1) DEFAULT 1
);
```

#### Automatic Triggers

**Repeated Absence Alert** (3+ absences in 30 days)
```sql
CREATE TRIGGER check_repeated_absences
AFTER INSERT ON town_attendance
FOR EACH ROW
BEGIN
    -- If student has 3+ absences, create urgent notification
    -- Priority: HIGH
    -- Action required: YES
END;
```

#### API Endpoints

**1. Get Urgent Notifications**
```
GET /api/urgent-notifications?action_taken=0&type=repeated_absence&priority=high
Authorization: Bearer {admin_token}

Response:
{
  "success": true,
  "notifications": [
    {
      "id": 1,
      "student_id": 10,
      "student_name": "John Smith",
      "id_number": "STU001",
      "class_name": "Grade 5",
      "section": "A",
      "type": "repeated_absence",
      "title": "Repeated Absence Alert: John Smith",
      "message": "John Smith has been absent 5 times in the last 30 days. Immediate action required.",
      "priority": "high",
      "action_required": 1,
      "action_taken": 0,
      "action_taken_by": null,
      "action_taken_at": null,
      "action_notes": null,
      "created_at": "2025-11-21 08:30:00"
    }
  ],
  "count": 1
}
```

**2. Mark Action Taken (Principal Only)**
```
POST /api/urgent-notifications/{id}/action-taken
Authorization: Bearer {principal_token}

Request:
{
  "notes": "Met with parents. Student agreed to improve attendance."
}

Response:
{
  "success": true,
  "message": "Action marked as taken"
}

Error (Not Principal):
{
  "success": false,
  "message": "Only principals can mark actions as taken"
}
```

### ✅ 3. Principal Notification Access

**Principal receives ALL notifications:**
- Attendance notifications (class + town)
- Payment notifications
- Urgent notifications
- Disciplinary notifications
- Academic notifications

**Automatic Forwarding:**
When a teacher is marked as `is_principal = 1`, they automatically receive copies of:
- All absence notifications
- All urgent notifications
- All payment alerts
- All disciplinary actions

### ✅ 4. Simplified Student Registration

**Town Master Registration Flow:**

**Step 1: Search for Student**
```
GET /api/town-master/students/eligible?search=STU001

Response shows:
- Student ID
- Student name
- Class
- Payment status
- Already registered or not
```

**Step 2: Select Student & Block**
```
POST /api/town-master/register-student

Request:
{
  "student_id": 10,
  "block_id": 2
}

Response:
{
  "success": true,
  "message": "Student registered to town successfully"
}
```

**NO need to:**
- ❌ Collect student details again
- ❌ Enter student information
- ❌ Fill forms

**Just:**
- ✅ Search student
- ✅ Select from list
- ✅ Choose block
- ✅ Click register

### ✅ 5. Town Master Access to Parent Details

#### API Endpoints

**1. Get All Town Students with Parents**
```
GET /api/town-master/students?block_id=2
Authorization: Bearer {teacher_token}

Response:
{
  "success": true,
  "students": [
    {
      "id": 10,
      "id_number": "STU001",
      "first_name": "John",
      "last_name": "Smith",
      "name": "John Smith",
      "date_of_birth": "2010-05-15",
      "gender": "male",
      "phone": "+1234567890",
      "email": "john.smith@email.com",
      "address": "123 Main St, City",
      "class_name": "Grade 5",
      "section": "A",
      "block_name": "Block A",
      "parents": [
        {
          "id": 5,
          "first_name": "Mary",
          "last_name": "Smith",
          "email": "mary.smith@email.com",
          "phone": "+1234567891",
          "address": "123 Main St, City",
          "relationship": "Mother"
        },
        {
          "id": 6,
          "first_name": "David",
          "last_name": "Smith",
          "email": "david.smith@email.com",
          "phone": "+1234567892",
          "address": "123 Main St, City",
          "relationship": "Father"
        }
      ]
    }
  ],
  "count": 1
}
```

**2. Get Individual Student Details**
```
GET /api/town-master/students/{id}
Authorization: Bearer {teacher_token}

Response:
{
  "success": true,
  "student": {
    "id": 10,
    "id_number": "STU001",
    "first_name": "John",
    "last_name": "Smith",
    "name": "John Smith",
    "date_of_birth": "2010-05-15",
    "gender": "male",
    "phone": "+1234567890",
    "email": "john.smith@email.com",
    "address": "123 Main St, City",
    "class_name": "Grade 5",
    "section": "A",
    "block_name": "Block A",
    "parents": [
      {
        "id": 5,
        "first_name": "Mary",
        "last_name": "Smith",
        "email": "mary.smith@email.com",
        "phone": "+1234567891",
        "address": "123 Main St, City",
        "relationship": "Mother",
        "verified": 1,
        "is_primary_contact": 1
      }
    ]
  }
}
```

**Town Master Can View:**
- ✅ Student full details
- ✅ Student contact info
- ✅ All linked parents/guardians
- ✅ Parent names
- ✅ Parent email addresses
- ✅ Parent phone numbers
- ✅ Parent physical addresses
- ✅ Relationship to student
- ✅ Primary contact indicator

**Security:**
- ✅ Town master can ONLY view students in their town
- ✅ Access denied if student not registered to their town
- ✅ Verified parents only shown

### ✅ 6. Routes to Add

```php
use App\Controllers\UserRoleController;
use App\Controllers\TownController;

// User Roles
$group->get('/admin/users/role/{role}', [UserRoleController::class, 'getTeachersByRole'])->add(new AuthMiddleware());
$group->get('/admin/users/roles/summary', [UserRoleController::class, 'getRolesSummary'])->add(new AuthMiddleware());

// Urgent Notifications
$group->get('/urgent-notifications', [UserRoleController::class, 'getUrgentNotifications'])->add(new AuthMiddleware());
$group->post('/urgent-notifications/{id}/action-taken', [UserRoleController::class, 'markActionTaken'])->add(new AuthMiddleware());

// Town Master - Student & Parent Access
$group->get('/town-master/students', [UserRoleController::class, 'getTownMasterStudents'])->add(new AuthMiddleware());
$group->get('/town-master/students/{id}', [UserRoleController::class, 'getStudentWithParents'])->add(new AuthMiddleware());
```

## Frontend Components Needed

### 1. Admin - Users by Role Tab
**Path:** `/Admin/users`

**Features:**
- Tabs for each role:
  - Town Masters
  - Exam Officers
  - Finance Officers
  - Principals
- View counts per role
- List teachers with that role
- Show assigned towns (for town masters)
- Show class/subject counts

**UI Layout:**
```
┌─────────────────────────────────────────┐
│ Users & Roles                            │
├─────────────────────────────────────────┤
│ [Town Masters: 5] [Exam: 3] [Finance: 2]│
│ [Principals: 1]                          │
├─────────────────────────────────────────┤
│ Town Masters (5)                         │
│                                          │
│ John Doe - North Town                    │
│ Email: john@school.com                   │
│ Classes: 3 | Subjects: 2                 │
│                                          │
│ Jane Smith - South Town                  │
│ Email: jane@school.com                   │
│ Classes: 2 | Subjects: 1                 │
└─────────────────────────────────────────┘
```

### 2. Principal - Urgent Notifications Dashboard
**Path:** `/Principal/urgent-notifications`

**Features:**
- Filter by:
  - Action taken (Yes/No)
  - Type (absence, payment, etc.)
  - Priority (critical, high, medium, low)
- Color-coded by priority:
  - 🔴 Critical
  - 🟠 High
  - 🟡 Medium
  - 🟢 Low
- Click to view details
- "Mark Action Taken" button
- Add action notes
- View history of actions

**UI Layout:**
```
┌─────────────────────────────────────────┐
│ Urgent Notifications                     │
├─────────────────────────────────────────┤
│ Filters: [Action Taken v] [Type v]      │
│                                          │
│ 🔴 CRITICAL - Repeated Absence           │
│ John Smith (STU001) - Grade 5-A          │
│ "Absent 5 times in last 30 days"        │
│ Created: Nov 21, 2025 8:30 AM           │
│ [View Details] [Mark Action Taken]      │
│                                          │
│ 🟠 HIGH - Payment Overdue                │
│ Jane Doe (STU002) - Grade 6-B            │
│ "Payment overdue by 15 days"            │
│ Created: Nov 20, 2025 9:00 AM           │
│ [View Details] [Mark Action Taken]      │
└─────────────────────────────────────────┘
```

### 3. Town Master - Students List with Parents
**Path:** `/TownMaster/students`

**Features:**
- List all students in town master's blocks
- Filter by block
- View student details inline or modal
- Expandable parent information
- Contact buttons (email, call)
- Export to CSV

**UI Layout:**
```
┌─────────────────────────────────────────┐
│ My Students                              │
├─────────────────────────────────────────┤
│ Block: [All Blocks v]                    │
│                                          │
│ Block A (20 students)                    │
│                                          │
│ ▼ STU001 - John Smith (Grade 5-A)      │
│   📞 +1234567890 | ✉ john@email.com     │
│   📍 123 Main St, City                   │
│                                          │
│   Parents/Guardians:                     │
│   • Mary Smith (Mother) ⭐               │
│     📞 +1234567891                        │
│     ✉ mary.smith@email.com               │
│     📍 123 Main St, City                 │
│                                          │
│   • David Smith (Father)                 │
│     📞 +1234567892                        │
│     ✉ david.smith@email.com              │
│                                          │
│ ▶ STU002 - Jane Doe (Grade 5-A)        │
└─────────────────────────────────────────┘
```

### 4. Town Master - Simplified Registration
**Path:** `/TownMaster/register`

**Features:**
- Search bar (ID or name)
- Shows only paid students
- One-click selection
- Block dropdown
- Register button
- Success/error feedback

**UI Layout:**
```
┌─────────────────────────────────────────┐
│ Register Students to Town                │
├─────────────────────────────────────────┤
│ Search: [Student ID or Name______]  [🔍]│
│                                          │
│ Eligible Students (Paid)                 │
│                                          │
│ ☐ STU001 - John Smith (Grade 5-A)      │
│   Paid: SLE 5,000 on Nov 1, 2025        │
│   Block: [Block A v]                     │
│                                          │
│ ☐ STU002 - Jane Doe (Grade 5-B)        │
│   Paid: SLE 5,000 on Nov 5, 2025        │
│   Block: [Block B v]                     │
│                                          │
│ [Register Selected Students]             │
└─────────────────────────────────────────┘
```

## Workflows

### Workflow 1: Admin Views Users by Role

```
1. Admin logs in
2. Navigates to "Users & Roles" tab
3. Clicks "Town Masters" tab
4. System shows:
   - List of 5 town masters
   - Each with town assignment
   - Contact info
   - Class/subject counts
5. Can click to view full profile
6. Can filter/search
```

### Workflow 2: Principal Manages Urgent Notifications

```
1. Principal logs in
2. Dashboard shows urgent notification badge (5)
3. Clicks "Urgent Notifications"
4. Sees list sorted by priority:
   🔴 Critical: 2
   🟠 High: 3
5. Clicks on "Repeated Absence - John Smith"
6. Views details:
   - Student: John Smith (STU001)
   - Absences: 5 in last 30 days
   - Dates: Nov 1, 5, 10, 15, 20
   - Parent contacted: No
7. Clicks "Mark Action Taken"
8. Enters notes: "Met with parents. Agreed on plan."
9. Submits
10. Notification marked complete
11. Badge count decreases to 4
```

### Workflow 3: Town Master Views Student Parents

```
1. Town master logs in
2. Navigates to "My Students"
3. Sees list of all students in their blocks
4. Filters by "Block A"
5. Clicks on "John Smith"
6. Expands to see:
   - Student contact: +1234567890
   - Student email: john@email.com
   - Student address: 123 Main St
   - Parent 1: Mary Smith (Mother) ⭐
     - Phone: +1234567891
     - Email: mary.smith@email.com
     - Same address
   - Parent 2: David Smith (Father)
     - Phone: +1234567892
     - Email: david.smith@email.com
7. Clicks email icon to send message
8. Can call directly from list
```

### Workflow 4: Town Master Registers Student (Simplified)

```
1. Town master logs in
2. Clicks "Register Students"
3. Types "STU001" in search
4. Student appears: John Smith (Grade 5-A)
5. Shows: ✅ Paid: SLE 5,000 on Nov 1
6. Selects "Block A" from dropdown
7. Clicks "Register"
8. Success: "Student registered successfully"
9. Student now appears in "My Students" list
```

### Workflow 5: Automatic Repeated Absence Alert

```
1. Student absent 3rd time in 30 days
2. Town master marks attendance: Absent
3. Database trigger fires
4. Counts absences: 3
5. Creates urgent notification:
   - Type: repeated_absence
   - Priority: HIGH
   - Title: "Repeated Absence Alert: John Smith"
   - Message: "Absent 3 times in last 30 days"
   - Action required: YES
6. Notification appears in:
   - Principal dashboard
   - Admin dashboard
   - Urgent notifications list
7. Principal receives email alert
8. Principal takes action
9. Marks as complete with notes
```

## All Complete! 🎉

**Implemented Features:**
- ✅ User roles system (Town Master, Exam Officer, Finance, Principal)
- ✅ Role-based user listings
- ✅ Urgent notifications system
- ✅ Automatic repeated absence alerts (3+ absences)
- ✅ Principal action tracking
- ✅ Simplified student registration (search & select)
- ✅ Town master access to parent details
- ✅ Student list with parent info
- ✅ Security and access control

**Database:**
- ✅ Role columns added to teachers
- ✅ urgent_notifications table created
- ✅ principal_notification_subscriptions table
- ✅ Automatic triggers for alerts
- ✅ Indexes for performance

**Backend:**
- ✅ UserRoleController with all methods
- ✅ Get teachers by role
- ✅ Roles summary
- ✅ Urgent notifications management
- ✅ Mark action taken (principal only)
- ✅ Town master student access
- ✅ Parent details retrieval

**Ready for frontend implementation!**
