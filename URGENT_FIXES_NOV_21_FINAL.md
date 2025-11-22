# URGENT FIXES - November 21, 2025

**Time**: 5:48 PM  
**Priority**: CRITICAL - Fix before production use

---

## 🔴 CRITICAL ISSUES TO FIX NOW

### Issue 1: Activity Logs API Fails
**URL**: `http://localhost:8080/api/admin/activity-logs/stats`  
**Error**: `Unknown column 'activity_type' in 'field list'`

**Root Cause**: Database column is named `action` but code expects `activity_type`

**Fix**: Run this SQL:
```sql
ALTER TABLE activity_logs 
CHANGE COLUMN `action` `activity_type` VARCHAR(255) NOT NULL;

ALTER TABLE activity_logs 
ADD COLUMN IF NOT EXISTS `metadata` TEXT NULL;
```

---

### Issue 2: Notifications API Method Not Allowed
**URL**: `http://localhost:8080/api/api/notifications`  
**Error**: `Method not allowed. Must be one of: OPTIONS`

**Root Cause**: Frontend calling wrong path (`/api/api/` instead of `/api/`)

**Quick Fix Option 1** - Fix Frontend (RECOMMENDED):
Change all frontend API calls from `/api/api/notifications` to `/api/notifications`

**Quick Fix Option 2** - Keep Backend Alias:
The backend already has `/api/api/` alias routes, but they need AUTH middleware properly attached.

---

### Issue 3: System Settings Currency Error
**Error**: `Unknown column 'currency_code' in 'field list'`

**Fix**: Run this SQL:
```sql
ALTER TABLE system_settings 
ADD COLUMN IF NOT EXISTS `currency_code` VARCHAR(10) DEFAULT 'GHS',
ADD COLUMN IF NOT EXISTS `currency_symbol` VARCHAR(5) DEFAULT '₵',
ADD COLUMN IF NOT EXISTS `date_format` VARCHAR(20) DEFAULT 'Y-m-d',
ADD COLUMN IF NOT EXISTS `time_format` VARCHAR(20) DEFAULT 'H:i:s',
ADD COLUMN IF NOT EXISTS `timezone` VARCHAR(50) DEFAULT 'Africa/Accra';
```

---

## 📝 COMPLETE DATABASE FIX SCRIPT

**File Created**: `backend1/database/migrations/fix_activity_logs_and_settings.sql`

**To Run**:
```bash
# 1. Start your MySQL server (XAMPP/WAMP)
# 2. Run this command:
cd "C:\Users\SABITECK\OneDrive\Documenti\Projects\SABITECK\School Management System\backend1"
php run_fix_migration_nov_21.php
```

---

## 🔧 TEACHER MANAGEMENT ENHANCEMENTS

### Required Changes:

#### 1. Database - Split Teacher Names
```sql
ALTER TABLE teachers
ADD COLUMN first_name VARCHAR(100) NULL AFTER id,
ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name;

-- Migrate existing data
UPDATE teachers 
SET first_name = SUBSTRING_INDEX(name, ' ', 1),
    last_name = SUBSTRING_INDEX(name, ' ', -1)
WHERE name IS NOT NULL AND first_name IS NULL;
```

#### 2. Update Teacher Forms
- Split full name field into first_name and last_name
- Update CSV template headers: `First Name, Last Name, Email, Password, Phone, Address...`

#### 3. Add View Classes/Subjects Buttons
In Teacher Management page listing:
- **Classes Column**: Show "View Classes" button → opens modal with list
- **Subjects Column**: Show "View Subjects" button → opens modal with list

**API Endpoints** (already exist):
- GET `/api/teachers/{id}/classes`
- GET `/api/teachers/{id}/subjects`

---

## 🏘️ TOWN MASTER SYSTEM

### Database Tables Needed:

```sql
-- 1. Towns
CREATE TABLE IF NOT EXISTS towns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_town_name (admin_id, name)
);

-- 2. Blocks (A, B, C, D, E, F per town)
CREATE TABLE IF NOT EXISTS blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    town_id INT NOT NULL,
    name VARCHAR(10) NOT NULL,
    capacity INT DEFAULT 50,
    current_occupancy INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
    UNIQUE KEY unique_block (town_id, name)
);

-- 3. Town Masters (Teacher assignments)
CREATE TABLE IF NOT EXISTS town_masters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    town_id INT NOT NULL,
    teacher_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES admins(id),
    UNIQUE KEY unique_active_town_master (town_id, is_active)
);

-- 4. Student Block Assignments
CREATE TABLE IF NOT EXISTS student_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    block_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    term ENUM('1st Term', '2nd Term', '3rd Term') NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NOT NULL,
    guardian_name VARCHAR(200) NULL,
    guardian_phone VARCHAR(20) NULL,
    guardian_email VARCHAR(100) NULL,
    guardian_address TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_term (student_id, academic_year_id, term, is_active)
);

-- 5. Town Attendance
CREATE TABLE IF NOT EXISTS town_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_block_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    taken_by INT NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_block_id) REFERENCES student_blocks(id) ON DELETE CASCADE,
    FOREIGN KEY (taken_by) REFERENCES teachers(id),
    UNIQUE KEY unique_attendance (student_block_id, date)
);

-- Update teachers table
ALTER TABLE teachers
ADD COLUMN is_town_master BOOLEAN DEFAULT FALSE,
ADD COLUMN town_master_of INT NULL,
ADD CONSTRAINT fk_town_master FOREIGN KEY (town_master_of) REFERENCES towns(id) ON DELETE SET NULL;
```

### Features Required:

#### Admin Panel - Town Master Tab
- Create/manage towns
- Create blocks (A-F) for each town with capacity
- Assign teacher as town master to specific town
- View all town master assignments

#### Town Master Dashboard
1. **Student Registration**:
   - Search student by ID
   - Register to block (only if student has paid fees)
   - Collect guardian info: name, phone, email, address
   - View all registered students in blocks

2. **Roll Call/Attendance**:
   - Take daily attendance for all blocks
   - Mark: Present, Absent, Late, Excused
   - Dated and timed records
   - Auto-send notification to parents if student is absent

3. **Student Details Modal**:
   - View student info
   - View guardian/parent details
   - View attendance history

#### Notifications System
- **3-Strike Rule**: If student misses 3 attendances → urgent notification to principal
- **Parent Notifications**: Auto-send SMS/email to parents when student is absent
- **General Attendance Integration**: Apply same notification logic to class attendance

---

## 👥 USER ROLES MANAGEMENT

### Database Table:
```sql
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('admin', 'teacher', 'staff') NOT NULL,
    role_name ENUM('principal', 'exam_officer', 'finance', 'town_master', 'class_master', 'librarian') NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (assigned_by) REFERENCES admins(id),
    UNIQUE KEY unique_user_role (user_id, user_type, role_name, is_active)
);
```

### Admin UI - User Roles Tab
Show tabs for:
- All Users
- Principals
- Exam Officers  
- Finance Officers
- Town Masters
- Class Masters

Each tab filters and displays users with that specific role.

---

## 🚨 URGENT NOTIFICATIONS

### Database Table:
```sql
CREATE TABLE IF NOT EXISTS urgent_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    student_id INT NOT NULL,
    incident_type ENUM('attendance_3_strikes', 'disciplinary', 'health', 'payment', 'other') NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_by INT NULL,
    acknowledged_at TIMESTAMP NULL,
    action_notes TEXT NULL,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES admins(id)
);
```

### Features:
- Principal/Admin dashboard shows urgent notifications count
- Click to view list of urgent items
- Mark as "Action Taken" with notes
- Automatic creation when:
  - Student misses 3+ attendances
  - Disciplinary issues
  - Payment overdue
  - Other flagged incidents

---

## 👨‍👩‍👧 PARENT FUNCTIONALITY

### Requirements:
1. **Self-Registration**:
   - Parents create own accounts
   - Bind to students using: Student ID + Date of Birth
   - Can bind multiple children

2. **Dashboard Features**:
   - View all children
   - View each child's:
     - Attendance records
     - Results
     - Fee payments
     - Notices
     - Disciplinary records
   - Receive notifications for:
     - Absences
     - Payment reminders
     - Exam schedules
     - General announcements

3. **Database**:
```sql
-- Parent-Student binding
CREATE TABLE IF NOT EXISTS parent_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    student_id INT NOT NULL,
    relationship ENUM('father', 'mother', 'guardian', 'other') NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    bound_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parent_student (parent_id, student_id)
);
```

---

## ⚙️ SYSTEM SETTINGS

### Required Working Features:

#### 1. General Settings
- School name, address, phone, email
- Logo upload
- Academic year configuration
- Term dates

#### 2. Email Settings
- SMTP configuration
- Test email functionality
- Email templates for notifications

#### 3. Notifications Settings  
- Enable/disable notification types
- Parent notification preferences
- SMS gateway integration (future)

#### 4. Security Settings
- Password policies
- Session timeout
- Two-factor authentication (future)

### Ensure All Settings Save Properly
- Test each settings tab
- Verify database updates
- Check settings retrieval on page reload

---

## 📋 IMPLEMENTATION ORDER

### Phase 1: IMMEDIATE (Do First - Database) ✅
1. Run migration to fix activity_logs column
2. Add currency columns to system_settings  
3. Split teacher/student names in database

### Phase 2: HIGH PRIORITY (Backend)
1. Create TownMasterController
2. Create TownMaster models
3. Update TeacherController (add getTeacherClasses/Subjects endpoints)
4. Update NotificationController (urgent notifications)
5. Create UserRoleController

### Phase 3: HIGH PRIORITY (Frontend)
1. Update Teacher Management page:
   - Add first_name/last_name fields
   - Add View Classes/Subjects buttons with modals
   - Update CSV template
2. Create Town Master Management page (Admin)
3. Create Town Master Dashboard
4. Add User Roles tab to User Management

### Phase 4: MEDIUM PRIORITY
1. Create Urgent Notifications page
2. Update Parent registration flow
3. Enhance Parent dashboard
4. Test all System Settings tabs

### Phase 5: TESTING
1. Test activity logs
2. Test notifications (all paths)
3. Test teacher management
4. Test town master full workflow
5. Test parent binding and notifications
6. Test system settings save/load

---

## 🔥 QUICK START - Run These NOW

### Step 1: Fix Database
```bash
# Start MySQL server
# Then run:
cd "C:\Users\SABITECK\OneDrive\Documenti\Projects\SABITECK\School Management System\backend1"
php run_fix_migration_nov_21.php
```

### Step 2: Create Complete Migration
Save this to: `backend1/database/migrations/complete_town_system.sql`

Then run:
```bash
mysql -u root -p school_management < backend1/database/migrations/complete_town_system.sql
```

### Step 3: Test Fixed Endpoints
```bash
# Test activity logs
curl http://localhost:8080/api/admin/activity-logs/stats

# Test notifications  
curl http://localhost:8080/api/notifications

# Test system settings
curl http://localhost:8080/api/admin/settings
```

---

## 📞 SUPPORT

If you encounter errors:
1. Check error logs: `backend1/logs/` or PHP error log
2. Verify database tables exist: `SHOW TABLES;`
3. Check column exists: `DESCRIBE activity_logs;`
4. Verify backend server running: `php -S localhost:8080 -t public`
5. Check frontend API base URL in `.env` file

---

**END OF DOCUMENT**
