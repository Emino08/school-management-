# Parents, Medical, and House System Implementation Guide

## Overview
This comprehensive update adds three major features to the school management system:
1. **Parent System** - Parents can monitor their children's academic progress
2. **Medical System** - Complete medical records management with staff and parent notifications
3. **House/Town System** - Student registration to houses/towns with house masters

---

## 🗄️ Database Changes

### New Tables Created
1. **parents** - Parent user accounts
2. **parent_student_relations** - Links parents to their children
3. **parent_communications** - Messages between parents and staff
4. **communication_responses** - Responses to parent communications
5. **parent_notifications** - Notification system for parents
6. **medical_staff** - Medical staff accounts
7. **medical_records** - Complete medical history for students
8. **medical_documents** - Medical document uploads
9. **houses** - House/Town definitions
10. **house_blocks** - Blocks within houses (A-F)
11. **house_masters** - Teachers assigned as house masters
12. **student_suspensions** - Suspension history tracking
13. **parent_verification_tokens** - Tokens for parent-child linking
14. **house_registration_logs** - Audit log for house registrations

### Updated Tables
- **students** - Added house fields, registration status, suspension fields, medical fields
- **attendance** - Added parent notification tracking
- **fees_payments** - Added tuition fee flag for registration eligibility

---

## 🔐 New User Roles

### 1. Parent Role
**Login Endpoint**: `POST /api/parents/login`
**Registration**: `POST /api/parents/register`

**Capabilities**:
- Link children using student ID + date of birth verification
- View child's attendance records
- View child's exam results  
- View school notices
- Receive notifications for:
  - Missed attendance
  - Suspensions
  - Medical issues
  - Medical recovery
- Send complaints/questions to teachers, principals, or admin
- Upload medical documents for children
- View responses to communications

### 2. Medical Staff Role
**Login Endpoint**: `POST /api/medical/login`
**Registration**: `POST /api/medical/register` (Admin only)

**Capabilities**:
- Create medical records for students
- Update treatment information
- Close medical cases
- View active medical cases
- Access student medical history
- View uploaded medical documents
- Automatic parent notification on:
  - New diagnosis
  - Treatment completion

### 3. House Master (Town Master) Role
**Existing teacher with additional permissions**

**Capabilities**:
- View eligible students (paid tuition, not yet registered)
- Register students to assigned house/town and block
- Cannot register students who haven't paid tuition
- View all students in assigned house
- Automatic registration logging

---

## 📋 Key Features Implemented

### Parent Features

#### 1. Child Verification System
```
POST /api/parents/verify-child
Body: {
  "student_id": 123,
  "date_of_birth": "2010-05-15"
}
```
Parents must verify relationship before accessing child data.

#### 2. Automatic Notifications
- **Attendance Miss**: Sent immediately when child is marked absent
- **Suspension**: Sent when student is suspended with details
- **Medical Alert**: Sent when child visits medical facility
- **Recovery Notice**: Sent when treatment is completed

#### 3. Communication System
Parents can send messages categorized as:
- Complaint
- Question
- Inquiry
- General

Recipients can respond, creating a conversation thread.

### Medical System Features

#### 1. Comprehensive Medical Records
Each record includes:
- Diagnosis
- Symptoms
- Treatment plan
- Medication
- Severity level (mild, moderate, severe, critical)
- Status tracking (active, under_treatment, completed, referred)
- Next checkup date

#### 2. Medical Document Upload
- Parents can upload previous medical records
- Medical staff can access all documents
- Supports PDF, images, and other document types

#### 3. Treatment Lifecycle
1. Medical staff creates record → Parents notified
2. Updates can be made to treatment
3. Staff closes record → Recovery notification sent to parents

### House/Town System Features

#### 1. House Structure
- **6 Houses/Towns** (customizable by admin)
- **6 Blocks per house** (A, B, C, D, E, F)
- Each block has capacity tracking
- Houses have points, colors, and mottos

#### 2. Registration Requirements
```
Student can ONLY be registered IF:
- Tuition fee is paid (is_tuition_fee = 1, status = 'paid')
- Not already registered
- Assigned to valid house and block
```

#### 3. House Master Workflow
1. View eligible students (GET /api/houses/eligible-students)
2. Select student, house, and block
3. Register student (POST /api/houses/register-student)
4. System validates payment status
5. Student marked as registered with timestamp

#### 4. Registration Audit
All registrations are logged with:
- Who registered the student
- When
- Which house and block
- Academic year
- Optional notes

---

## 🔄 API Endpoints Summary

### Parent Endpoints
```
POST   /api/parents/register                     - Create parent account
POST   /api/parents/login                        - Parent login
GET    /api/parents/profile                      - Get parent profile
PUT    /api/parents/profile                      - Update profile
POST   /api/parents/verify-child                 - Link child account
GET    /api/parents/children                     - Get all linked children
GET    /api/parents/children/{id}/attendance     - Child attendance
GET    /api/parents/children/{id}/results        - Child results
GET    /api/parents/notices                      - School notices
GET    /api/parents/notifications                - Parent notifications
PUT    /api/parents/notifications/{id}/read      - Mark notification read
POST   /api/parents/communications               - Send message
GET    /api/parents/communications               - Get all communications
GET    /api/parents/communications/{id}          - Get communication thread
```

### Medical Endpoints
```
POST   /api/medical/register                     - Register medical staff (admin)
POST   /api/medical/login                        - Medical staff login
GET    /api/medical/staff                        - Get all medical staff
POST   /api/medical/records                      - Create medical record
PUT    /api/medical/records/{id}                 - Update record
POST   /api/medical/records/{id}/close           - Close/complete treatment
GET    /api/medical/records/student/{id}         - Student medical history
GET    /api/medical/records/active               - All active cases
GET    /api/medical/records/{id}                 - Get specific record
POST   /api/medical/documents/upload             - Upload medical document
```

### House/Town Endpoints
```
POST   /api/houses                               - Create house (admin)
GET    /api/houses                               - Get all houses
GET    /api/houses/{id}                          - Get house details
PUT    /api/houses/{id}                          - Update house
POST   /api/houses/assign-master                 - Assign house master
GET    /api/houses/eligible-students             - Students eligible for registration
POST   /api/houses/register-student              - Register student to house
GET    /api/houses/{id}/students                 - Get house students
```

### Suspension Endpoints
```
POST   /api/suspensions                          - Suspend student
PUT    /api/suspensions/{id}/lift                - Lift suspension
GET    /api/suspensions/student/{id}/history     - Suspension history
GET    /api/suspensions/active                   - All active suspensions
```

---

## 🎨 Frontend Integration Requirements

### 1. New Pages Needed

#### Parent Portal
- `ParentLogin.jsx` - Login page
- `ParentDashboard.jsx` - Main dashboard
- `ParentChildren.jsx` - List of linked children
- `ChildProfile.jsx` - Individual child details
- `ParentNotifications.jsx` - Notification center
- `ParentCommunications.jsx` - Messages/complaints
- `MedicalRecords.jsx` - Child medical history

#### Medical Portal
- `MedicalLogin.jsx` - Medical staff login
- `MedicalDashboard.jsx` - Active cases dashboard
- `CreateMedicalRecord.jsx` - New medical record form
- `MedicalRecordDetail.jsx` - View/update record
- `StudentMedicalHistory.jsx` - Complete history view

#### House Management
- `HouseList.jsx` - View all houses
- `HouseDetail.jsx` - House with blocks and students
- `RegisterStudent.jsx` - House master registration form
- `EligibleStudents.jsx` - List of students eligible for registration

#### Admin Features
- `ManageMedicalStaff.jsx` - Create/manage medical staff
- `ManageHouses.jsx` - Create/manage houses
- `SuspensionManagement.jsx` - Suspend/lift suspensions
- `AssignHouseMaster.jsx` - Assign teachers to houses

### 2. Updated Pages

#### Student Management
- Add house/block assignment fields
- Show registration status
- Display medical information section
- Show suspension status badge

#### Attendance
- Existing functionality enhanced with parent notifications
- No UI changes needed (backend handles notifications)

#### Fee Management
- Add checkbox to mark tuition fees
- Show registration eligibility status

### 3. Notification Components
```jsx
// Example notification types
{
  attendance_miss: {
    icon: 'AlertCircle',
    color: 'warning',
    title: 'Attendance Alert'
  },
  suspension: {
    icon: 'Ban',
    color: 'danger',
    title: 'Suspension Notice'
  },
  medical: {
    icon: 'Activity',
    color: 'info',
    title: 'Medical Alert'
  }
}
```

---

## 🚀 Migration Steps

### 1. Run Database Migration
```bash
cd backend1
php run_parent_medical_migration.php
```

This creates all new tables and adds new columns to existing tables.

### 2. Verify Tables
Check that all tables exist:
```sql
SHOW TABLES LIKE 'parents%';
SHOW TABLES LIKE 'medical%';
SHOW TABLES LIKE 'house%';
SHOW TABLES LIKE 'student_suspensions';
```

### 3. Initialize Houses (Admin Task)
Create the 6 houses through admin panel or API:
```javascript
const houses = [
  { name: 'Red House', color: '#FF0000', motto: 'Strength and Courage' },
  { name: 'Blue House', color: '#0000FF', motto: 'Wisdom and Truth' },
  { name: 'Green House', color: '#00FF00', motto: 'Growth and Unity' },
  { name: 'Yellow House', color: '#FFFF00', motto: 'Excellence and Pride' },
  { name: 'Purple House', color: '#800080', motto: 'Honor and Dignity' },
  { name: 'Orange House', color: '#FFA500', motto: 'Energy and Spirit' }
];
```

### 4. Assign House Masters
- Admin assigns teachers as house masters
- Each house should have at least one house master
- Teachers can be house masters of multiple houses

### 5. Mark Tuition Fees
Update existing fee payments to mark tuition fees:
```sql
UPDATE fees_payments 
SET is_tuition_fee = 1 
WHERE term = 'Full Year' OR amount > [tuition_threshold];
```

---

## 🔐 Security Considerations

1. **Parent-Child Verification**: Requires exact DOB match to link child
2. **Role-Based Access**: Each role can only access their authorized data
3. **Medical Privacy**: Only authorized medical staff and parents can view records
4. **Communication Privacy**: Parents can only view their own communications
5. **House Registration**: Only assigned house masters can register students

---

## 📊 Data Flow Examples

### Example 1: Parent Linking Child
```
1. Parent registers account
2. Parent enters child's student_id and date_of_birth
3. System verifies DOB matches student record
4. If match: Create parent_student_relation with is_verified = true
5. Parent can now access child data
```

### Example 2: Attendance Miss Notification
```
1. Teacher marks student absent
2. AttendanceController triggers notification
3. ParentNotification model:
   - Finds all verified parents of student
   - Creates notification record for each parent
   - Marks attendance record as parent_notified
4. Parents see notification in their portal
```

### Example 3: House Registration
```
1. Student pays tuition fee (marked as is_tuition_fee = 1)
2. House master logs in and views eligible students
3. House master selects student, house, and block
4. System validates:
   - Is tuition paid?
   - Is student already registered?
   - Is house master assigned to selected house?
5. If valid:
   - Update student record with house and block
   - Set is_registered = true, registered_at = NOW()
   - Create registration log entry
6. Student can now access house activities
```

---

## 🧪 Testing Checklist

### Parent System
- [ ] Parent can register and login
- [ ] Parent can link child with correct DOB
- [ ] Parent receives notification when child is absent
- [ ] Parent can send complaint to teacher
- [ ] Teacher/admin can respond to complaint
- [ ] Parent can view child's results
- [ ] Parent can upload medical documents

### Medical System
- [ ] Medical staff can login
- [ ] Medical staff can create medical record
- [ ] Parents receive notification on new diagnosis
- [ ] Medical staff can update treatment
- [ ] Medical staff can close case
- [ ] Parents receive recovery notification
- [ ] Medical history is preserved after treatment

### House System
- [ ] Admin can create houses with default blocks
- [ ] Admin can assign house masters
- [ ] House master can view only eligible students (paid tuition)
- [ ] House master CANNOT register unpaid students
- [ ] Registration creates proper audit log
- [ ] Student shows as registered after process

### Suspension System
- [ ] Admin can suspend student
- [ ] Parents receive suspension notification
- [ ] Student status shows as suspended
- [ ] Admin can lift suspension
- [ ] Suspension history is maintained

---

## 📝 Notes

1. **Performance**: Notification queries are indexed for fast retrieval
2. **Scalability**: Parent notifications use batch processing
3. **Audit Trail**: All house registrations and suspensions are logged
4. **Extensibility**: System designed to add more notification types easily

---

## 🆘 Support

For issues or questions:
1. Check database tables were created successfully
2. Verify all models are properly imported in controllers
3. Ensure JWT_SECRET is set in .env file
4. Check CORS settings allow frontend origin
5. Review error logs in backend

---

## 📚 Additional Resources

- **Database Schema**: `/backend1/database/migrations/add_parents_medical_houses_system.sql`
- **Models**: `/backend1/src/Models/Parent.php`, `MedicalStaff.php`, `House.php`, etc.
- **Controllers**: `/backend1/src/Controllers/ParentController.php`, `MedicalController.php`, `HouseController.php`
- **Routes**: `/backend1/src/Routes/api.php` (see bottom of file for new routes)

---

**Implementation Date**: 2025
**Version**: 1.0.0
**Status**: ✅ Backend Complete - Frontend Implementation Required
