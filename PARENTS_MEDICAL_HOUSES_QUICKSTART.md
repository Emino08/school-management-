# Quick Start Guide - Parents, Medical & Houses System

## 🚀 What Was Added

### 3 New Major Features:

1. **👨‍👩‍👧 Parent Portal**
   - Parents monitor their children's academic progress
   - Receive notifications for attendance, suspensions, medical issues
   - Communicate with teachers/admin
   - Upload medical documents

2. **🏥 Medical System**
   - Medical staff manage student health records
   - Complete treatment tracking
   - Parent notifications for medical events
   - Medical document management

3. **🏠 House/Town System**
   - 6 customizable houses with blocks A-F
   - House masters register students
   - Students must pay tuition before registration
   - Complete audit trail

---

## 📦 Installation Steps

### 1. Run Database Migration
```bash
cd backend1
php run_parent_medical_migration.php
```

### 2. Initialize Houses (One-time Setup)
```bash
php initialize_houses.php
# Enter your admin ID when prompted
```

### 3. Update Existing Fees (Mark Tuition)
```sql
-- Run this SQL to mark existing tuition fees
UPDATE fees_payments 
SET is_tuition_fee = 1, allows_registration = 1
WHERE term = 'Full Year';
```

---

## 🔐 New Login Endpoints

| Role | Endpoint | Credentials |
|------|----------|-------------|
| Parent | `POST /api/parents/login` | Email & Password |
| Medical Staff | `POST /api/medical/login` | Email & Password |
| House Master | Use existing teacher login | Teacher credentials |

---

## 👥 User Roles & Permissions

### Parent
- ✅ Link children (requires student ID + DOB)
- ✅ View child attendance & results
- ✅ Receive auto-notifications
- ✅ Send complaints/questions
- ✅ Upload medical documents

### Medical Staff  
- ✅ Create/update medical records
- ✅ Close treatment cases
- ✅ View student medical history
- ✅ Auto-notify parents

### House Master (Teacher)
- ✅ View eligible students (paid tuition)
- ✅ Register students to houses/blocks
- ✅ View assigned house students
- ❌ Cannot register unpaid students

---

## 🎯 Key Workflows

### Parent Links Child
```
1. Parent registers: POST /api/parents/register
2. Parent logs in: POST /api/parents/login
3. Verify child: POST /api/parents/verify-child
   Body: { "student_id": 123, "date_of_birth": "2010-05-15" }
4. Access granted to child data
```

### Student Registration to House
```
1. Student pays tuition (mark is_tuition_fee = 1)
2. House master views eligible: GET /api/houses/eligible-students
3. Register student: POST /api/houses/register-student
   Body: {
     "student_id": 123,
     "house_id": 1,
     "house_block_id": 5
   }
4. Student marked as registered ✅
```

### Medical Record Creation
```
1. Student visits medical facility
2. Medical staff creates record: POST /api/medical/records
   Body: {
     "student_id": 123,
     "diagnosis": "Fever",
     "severity": "mild",
     "treatment": "Rest and hydration"
   }
3. Parents automatically notified 📧
4. Treatment can be updated
5. Close record: POST /api/medical/records/{id}/close
6. Parents notified of recovery 🎉
```

---

## 🔔 Automatic Notifications

Parents receive notifications for:

| Event | Trigger | Notification Type |
|-------|---------|-------------------|
| Attendance Miss | Student marked absent | `attendance_miss` |
| Suspension | Student suspended | `suspension` |
| Medical Issue | New medical record | `medical` |
| Recovery | Medical case closed | `medical` |

---

## 📁 Files Created

### Backend Models
- `Parent.php` - Parent data management
- `MedicalStaff.php` - Medical staff management
- `MedicalRecord.php` - Medical records
- `House.php` - House/town management
- `ParentNotification.php` - Notification system

### Backend Controllers
- `ParentController.php` - Parent endpoints
- `MedicalController.php` - Medical endpoints
- `HouseController.php` - House management
- `SuspensionController.php` - Suspension handling

### Migrations
- `add_parents_medical_houses_system.sql` - Main migration
- `add_column_updates.sql` - Column additions
- `run_parent_medical_migration.php` - Migration runner
- `initialize_houses.php` - House setup script

---

## 🎨 Frontend Required

### New Pages Needed

**Parent Portal**
- ParentLogin.jsx
- ParentDashboard.jsx
- ChildProfile.jsx
- ParentNotifications.jsx
- ParentCommunications.jsx

**Medical Portal**
- MedicalLogin.jsx
- MedicalDashboard.jsx
- CreateMedicalRecord.jsx
- StudentMedicalHistory.jsx

**House Management**
- HouseList.jsx
- RegisterStudent.jsx
- EligibleStudents.jsx

**Admin Updates**
- ManageMedicalStaff.jsx
- ManageHouses.jsx
- SuspensionManagement.jsx

---

## 🧪 Quick Test

### Test Parent System
```bash
# Register parent
curl -X POST http://localhost:8080/api/parents/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "phone": "1234567890",
    "admin_id": 1
  }'

# Login
curl -X POST http://localhost:8080/api/parents/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Test House Creation
```bash
# Create house (use admin token)
curl -X POST http://localhost:8080/api/houses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "house_name": "Red House",
    "house_color": "#DC143C",
    "house_motto": "Strength and Courage"
  }'
```

---

## ⚠️ Important Notes

1. **Tuition Requirement**: Students MUST have `is_tuition_fee = 1` and `status = 'paid'` to be registered
2. **Parent Verification**: Parents need exact DOB match to link children
3. **House Masters**: Teachers must be assigned as house masters before registering students
4. **Medical Privacy**: Only authorized users can view medical records
5. **Audit Trail**: All house registrations and suspensions are logged

---

## 🔗 API Documentation

Full API documentation in: `PARENTS_MEDICAL_HOUSES_IMPLEMENTATION.md`

Quick reference:
- Parents: `/api/parents/*`
- Medical: `/api/medical/*`
- Houses: `/api/houses/*`
- Suspensions: `/api/suspensions/*`

---

## 📊 Database Tables Added

11 new tables:
- parents, parent_student_relations, parent_communications
- communication_responses, parent_notifications
- medical_staff, medical_records, medical_documents
- houses, house_blocks, house_masters
- student_suspensions

Plus updated columns in:
- students (house fields, registration status, medical fields)
- attendance (parent notification tracking)
- fees_payments (tuition flag)

---

## ✅ Checklist

Before going live:

- [ ] Run migrations successfully
- [ ] Initialize houses for your school
- [ ] Mark existing tuition fees
- [ ] Assign house masters
- [ ] Test parent registration
- [ ] Test medical record creation
- [ ] Test house registration workflow
- [ ] Build frontend pages
- [ ] Test notification system
- [ ] Train staff on new features

---

## 🆘 Troubleshooting

**Students not showing as eligible?**
- Check tuition fee is marked: `is_tuition_fee = 1`
- Check payment status: `status = 'paid'`

**Parent can't link child?**
- Verify DOB is correct
- Check student exists in database
- Ensure student_id is valid

**House registration fails?**
- Verify teacher is assigned as house master
- Check student has paid tuition
- Ensure student not already registered

---

## 📞 Support

For detailed implementation guide, see:
**PARENTS_MEDICAL_HOUSES_IMPLEMENTATION.md**

---

**Status**: ✅ Backend Complete | ⏳ Frontend Pending
**Version**: 1.0.0
**Date**: 2025
