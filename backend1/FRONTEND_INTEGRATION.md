# Frontend Integration Guide

## API Base URL Configuration

Update your frontend `.env` file:

```env
REACT_APP_BASE_URL=http://localhost:8080/api
```

## API Response Format

All API responses follow this structure:

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }
}
```

## Authentication Changes

### Old MongoDB Backend vs New PHP Backend

#### Login Endpoint Changes:

**Old:**
- Admin: `POST /AdminLogin`
- Student: `POST /StudentLogin`
- Teacher: `POST /TeacherLogin`

**New:**
- Admin: `POST /api/admin/login`
- Student: `POST /api/students/login`
- Teacher: `POST /api/teachers/login`

#### Register Endpoint Changes:

**Old:**
- Admin: `POST /AdminReg`
- Student: `POST /StudentReg`
- Teacher: `POST /TeacherReg`

**New:**
- Admin: `POST /api/admin/register`
- Student: `POST /api/students/register`
- Teacher: `POST /api/teachers/register`

#### Response Structure Changes:

**Old Response:**
```json
{
  "role": "Admin",
  "schoolName": "ABC School",
  "_id": "123"
}
```

**New Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "admin": {
    "id": 1,
    "school_name": "ABC School",
    "email": "admin@school.com"
  }
}
```

## Frontend Code Updates Required

### 1. Update API Handler Files

Update all files in `frontend/src/redux/*/Handle.js`:

**Example: userHandle.js**

```javascript
// OLD
export const loginUser = (fields, role) => async (dispatch) => {
    dispatch(authRequest());
    try {
        const result = await axios.post(`${process.env.REACT_APP_BASE_URL}/${role}Login`, fields);
        if (result.data.role) {
            dispatch(authSuccess(result.data));
        }
    } catch (error) {
        dispatch(authError(error));
    }
};

// NEW
export const loginUser = (fields, role) => async (dispatch) => {
    dispatch(authRequest());

    const endpoints = {
        'Admin': '/admin/login',
        'Student': '/students/login',
        'Teacher': '/teachers/login'
    };

    try {
        const result = await axios.post(
            `${process.env.REACT_APP_BASE_URL}${endpoints[role]}`,
            fields,
            {
                headers: { 'Content-Type': 'application/json' }
            }
        );

        if (result.data.success && result.data.token) {
            // Store token
            localStorage.setItem('token', result.data.token);

            // Store user data
            const userData = result.data.admin || result.data.student || result.data.teacher;
            userData.role = role;
            dispatch(authSuccess(userData));
        } else {
            dispatch(authFailed(result.data.message));
        }
    } catch (error) {
        dispatch(authError(error.response?.data?.message || error.message));
    }
};
```

### 2. Add Authorization Header to All Requests

Create an axios interceptor in your main App.js or create a separate api.js file:

```javascript
// src/api/axiosConfig.js
import axios from 'axios';

// Add request interceptor
axios.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add response interceptor for error handling
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Token expired or invalid
            localStorage.removeItem('token');
            window.location.href = '/';
        }
        return Promise.reject(error);
    }
);

export default axios;
```

### 3. Update Endpoint Mappings

Create a mapping file for all endpoints:

```javascript
// src/api/endpoints.js
const BASE_URL = process.env.REACT_APP_BASE_URL;

export const endpoints = {
    // Admin
    adminLogin: `${BASE_URL}/admin/login`,
    adminRegister: `${BASE_URL}/admin/register`,
    adminProfile: `${BASE_URL}/admin/profile`,
    adminStats: `${BASE_URL}/admin/stats`,

    // Students
    studentLogin: `${BASE_URL}/students/login`,
    studentRegister: `${BASE_URL}/students/register`,
    students: `${BASE_URL}/students`,
    studentById: (id) => `${BASE_URL}/students/${id}`,
    studentsByClass: (id) => `${BASE_URL}/students/class/${id}`,
    studentGrades: (id) => `${BASE_URL}/students/${id}/grades`,
    studentAttendance: (id) => `${BASE_URL}/students/${id}/attendance`,

    // Teachers
    teacherLogin: `${BASE_URL}/teachers/login`,
    teacherRegister: `${BASE_URL}/teachers/register`,
    teachers: `${BASE_URL}/teachers`,
    teacherById: (id) => `${BASE_URL}/teachers/${id}`,
    teacherSubjects: (id) => `${BASE_URL}/teachers/${id}/subjects`,
    assignSubject: `${BASE_URL}/teachers/assign-subject`,

    // Classes
    classes: `${BASE_URL}/classes`,
    classById: (id) => `${BASE_URL}/classes/${id}`,
    classSubjects: (id) => `${BASE_URL}/classes/${id}/subjects`,

    // Subjects
    subjects: `${BASE_URL}/subjects`,
    subjectById: (id) => `${BASE_URL}/subjects/${id}`,

    // Academic Years
    academicYears: `${BASE_URL}/academic-years`,
    currentYear: `${BASE_URL}/academic-years/current`,
    setCurrentYear: (id) => `${BASE_URL}/academic-years/${id}/set-current`,
    promoteStudents: (id) => `${BASE_URL}/academic-years/${id}/promote`,
    completeYear: (id) => `${BASE_URL}/academic-years/${id}/complete`,

    // Exams & Grades
    exams: `${BASE_URL}/exams`,
    examResults: `${BASE_URL}/exams/results`,
    studentExamResults: (examId, studentId) =>
        `${BASE_URL}/exams/${examId}/students/${studentId}/results`,
    grades: `${BASE_URL}/grades`,
    studentGradesList: (studentId) => `${BASE_URL}/grades/students/${studentId}`,

    // Attendance
    attendance: `${BASE_URL}/attendance`,
    studentAttendanceData: (studentId) => `${BASE_URL}/attendance/students/${studentId}`,
    studentAttendanceStats: (studentId) => `${BASE_URL}/attendance/students/${studentId}/stats`,

    // Fees
    fees: `${BASE_URL}/fees`,
    studentFees: (studentId) => `${BASE_URL}/fees/students/${studentId}`,
    feesByTerm: (term) => `${BASE_URL}/fees/term/${term}`,
    feesStats: `${BASE_URL}/fees/stats`,

    // Notices
    notices: `${BASE_URL}/notices`,
    noticeById: (id) => `${BASE_URL}/notices/${id}`,
    noticesByAudience: (audience) => `${BASE_URL}/notices/audience/${audience}`,

    // Complaints
    complaints: `${BASE_URL}/complaints`,
    myComplaints: `${BASE_URL}/complaints/my`,
    complaintStatus: (id) => `${BASE_URL}/complaints/${id}/status`,
};
```

### 4. Update Field Names

Some field names have changed:

| Old Field | New Field |
|-----------|-----------|
| `_id` | `id` |
| `schoolName` | `school_name` |
| `rollNum` | `roll_number` |
| `sclassName` | `class_name` |
| `examDate` | `exam_date` |
| `marksObtained` | `marks_obtained` |
| `paymentDate` | `payment_date` |

### 5. Student Login Field Change

**Old:** Students logged in with `email` and `password`
**New:** Students log in with `roll_number` and `password`

Update the student login form:

```javascript
// OLD
<TextField
    label="Email"
    type="email"
    name="email"
/>

// NEW
<TextField
    label="Roll Number"
    type="text"
    name="roll_number"
/>
```

## Testing the Integration

### 1. Start the Backend
```bash
cd backend1
composer install
php -S localhost:8080 -t public
```

### 2. Update Frontend .env
```env
REACT_APP_BASE_URL=http://localhost:8080/api
```

### 3. Start the Frontend
```bash
cd frontend
npm install
npm start
```

### 4. Test Each Module
- [ ] Admin login/registration
- [ ] Student login/registration
- [ ] Teacher login/registration
- [ ] Class creation
- [ ] Subject creation
- [ ] Student enrollment
- [ ] Grade entry
- [ ] Attendance marking
- [ ] Fee payment recording
- [ ] Notice creation
- [ ] Complaint submission
- [ ] Student promotion

## Common Issues and Solutions

### CORS Errors
**Issue:** "Access-Control-Allow-Origin" error
**Solution:** Backend already configured for CORS. Ensure frontend is running on `http://localhost:3000`

### 401 Unauthorized
**Issue:** Token expired or invalid
**Solution:** Check that token is being sent in Authorization header: `Bearer <token>`

### 404 Not Found
**Issue:** Endpoint not found
**Solution:** Verify endpoint URL matches the new API structure

### Field Validation Errors
**Issue:** Required fields missing
**Solution:** Check new required fields in API documentation

## Migration Checklist

- [ ] Update `.env` with new BASE_URL
- [ ] Create axios interceptor for auth headers
- [ ] Update all login/register endpoints
- [ ] Update all CRUD endpoints
- [ ] Update field names (snake_case)
- [ ] Test admin flows
- [ ] Test student flows
- [ ] Test teacher flows
- [ ] Test all CRUD operations
- [ ] Test student promotion feature
