# School Management System - PHP Backend

A robust School Management System built with PHP Slim Framework and MySQL database with comprehensive academic year management and student promotion features.

## Features

- **Multi-tenant System**: Support for multiple schools
- **User Management**: Admin, Teacher, and Student roles with JWT authentication
- **Academic Year Management**: Full academic year lifecycle with automatic student promotion
- **Student Promotion System**: Automatic promotion based on class average performance
- **Comprehensive Modules**:
  - Student Management
  - Teacher Management
  - Class & Subject Management
  - Exam & Grading System
  - Attendance Tracking
  - Fees & Payments
  - Notices & Announcements
  - Complaint Management

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

## Installation

1. **Install Dependencies**
   ```bash
   cd backend1
   composer install
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` and configure your database settings:
   - DB_HOST=localhost
   - DB_PORT=4306
   - DB_NAME=school_management
   - DB_USER=root
   - DB_PASS=1212

3. **Setup Database**
   ```bash
   mysql -h localhost -P 4306 -u root -p1212 -e "CREATE DATABASE school_management;"
   mysql -h localhost -P 4306 -u root -p1212 school_management < database/schema.sql
   ```

4. **Start Development Server**
   ```bash
   composer start
   ```
   Or use:
   ```bash
   php -S localhost:8080 -t public
   ```

## API Endpoints

Base URL: `http://localhost:8080/api`

### Authentication

#### Admin
- POST `/admin/register` - Register new school
- POST `/admin/login` - Admin login
- GET `/admin/profile` - Get admin profile (Auth required)
- PUT `/admin/profile` - Update admin profile (Auth required)
- GET `/admin/stats` - Get dashboard statistics (Auth required)

#### Student
- POST `/students/register` - Register new student (Auth required)
- POST `/students/login` - Student login
- GET `/students` - Get all students (Auth required)
- GET `/students/{id}` - Get student details (Auth required)
- PUT `/students/{id}` - Update student (Auth required)
- DELETE `/students/{id}` - Delete student (Auth required)
- GET `/students/class/{id}` - Get students by class (Auth required)
- GET `/students/{id}/grades` - Get student grades (Auth required)
- GET `/students/{id}/attendance` - Get student attendance (Auth required)

#### Teacher
- POST `/teachers/register` - Register new teacher (Auth required)
- POST `/teachers/login` - Teacher login
- GET `/teachers` - Get all teachers (Auth required)
- GET `/teachers/{id}` - Get teacher details (Auth required)
- PUT `/teachers/{id}` - Update teacher (Auth required)
- DELETE `/teachers/{id}` - Delete teacher (Auth required)
- POST `/teachers/assign-subject` - Assign subject to teacher (Auth required)
- GET `/teachers/{id}/subjects` - Get teacher subjects (Auth required)

#### Classes & Subjects
- POST `/classes` - Create class (Auth required)
- GET `/classes` - Get all classes (Auth required)
- GET `/classes/{id}` - Get class details (Auth required)
- PUT `/classes/{id}` - Update class (Auth required)
- DELETE `/classes/{id}` - Delete class (Auth required)
- GET `/classes/{id}/subjects` - Get class subjects (Auth required)
- POST `/subjects` - Create subject (Auth required)
- GET `/subjects` - Get all subjects (Auth required)
- GET `/subjects/{id}` - Get subject details (Auth required)
- PUT `/subjects/{id}` - Update subject (Auth required)
- DELETE `/subjects/{id}` - Delete subject (Auth required)

#### Academic Year & Promotion
- POST `/academic-years` - Create academic year (Auth required)
- GET `/academic-years` - Get all academic years (Auth required)
- GET `/academic-years/current` - Get current academic year (Auth required)
- PUT `/academic-years/{id}/set-current` - Set current year (Auth required)
- POST `/academic-years/{id}/promote` - Promote students (Auth required)
- PUT `/academic-years/{id}/complete` - Complete academic year (Auth required)
- DELETE `/academic-years/{id}` - Delete academic year (Auth required)

#### Grades & Exams
- POST `/exams` - Create exam (Auth required)
- GET `/exams` - Get all exams (Auth required)
- POST `/exams/results` - Record exam result (Auth required)
- GET `/exams/{examId}/students/{studentId}/results` - Get exam results (Auth required)
- POST `/grades` - Update student grade (Auth required)
- GET `/grades/students/{studentId}` - Get student grades (Auth required)

#### Attendance
- POST `/attendance` - Mark attendance (Auth required)
- GET `/attendance/students/{studentId}` - Get student attendance (Auth required)
- GET `/attendance/students/{studentId}/stats` - Get attendance statistics (Auth required)

#### Fees & Payments
- POST `/fees` - Record payment (Auth required)
- GET `/fees/students/{studentId}` - Get student payments (Auth required)
- GET `/fees/term/{term}` - Get payments by term (Auth required)
- GET `/fees/stats` - Get payment statistics (Auth required)
- PUT `/fees/{id}` - Update payment (Auth required)
- DELETE `/fees/{id}` - Delete payment (Auth required)

#### Notices
- POST `/notices` - Create notice (Auth required)
- GET `/notices` - Get all notices (Auth required)
- GET `/notices/audience/{audience}` - Get notices by audience (Auth required)
- PUT `/notices/{id}` - Update notice (Auth required)
- DELETE `/notices/{id}` - Delete notice (Auth required)

#### Complaints
- POST `/complaints` - Submit complaint (Auth required)
- GET `/complaints` - Get all complaints (Auth required)
- GET `/complaints/my` - Get user complaints (Auth required)
- PUT `/complaints/{id}/status` - Update complaint status (Auth required)

## Student Promotion System

The system includes an intelligent student promotion feature that:

1. **Calculates Class Average**: Automatically calculates each student's average across all subjects
2. **Checks Passing Criteria**: Default passing percentage is 40% (configurable)
3. **Promotes Students**: Students with average >= passing percentage are promoted to next grade level
4. **Handles Graduation**: Students in final grade are marked as "graduated"
5. **Tracks Failed Students**: Students below passing percentage are marked as "failed"

### Promotion Endpoint

```bash
POST /api/academic-years/{id}/promote
{
  "passing_percentage": 40
}
```

Response:
```json
{
  "success": true,
  "message": "Student promotion completed",
  "result": {
    "promoted": 150,
    "failed": 10,
    "total": 160
  }
}
```

## Authentication

All protected routes require a JWT token in the Authorization header:

```
Authorization: Bearer <your_jwt_token>
```

Token is returned upon successful login and is valid for 24 hours (configurable in `.env`).

## Database Schema

The database consists of 15 tables:
- admins
- students
- teachers
- classes
- subjects
- academic_years
- student_enrollments (tracks student progression)
- teacher_assignments
- exams
- exam_results
- grades
- attendance
- fees_payments
- notices
- complaints

## Development

### Code Structure
```
backend1/
├── public/
│   └── index.php          # Entry point
├── src/
│   ├── Config/
│   │   └── Database.php   # Database configuration
│   ├── Controllers/       # API controllers
│   ├── Models/            # Data models
│   ├── Middleware/        # Middleware (Auth, CORS)
│   ├── Routes/
│   │   └── api.php        # API routes
│   ├── Services/          # Business logic
│   └── Utils/             # Helper classes
├── database/
│   ├── schema.sql         # Database schema
│   ├── migrations/        # Database migrations
│   └── seeds/             # Database seeders
├── .env                   # Environment configuration
├── composer.json          # Dependencies
└── README.md
```

## Testing

Run tests with PHPUnit:
```bash
composer test
```

## License

MIT License


## Migration: Roll Number → ID Number

- The students table now uses id_number instead of oll_number.
- A migration script migrations/014_rename_roll_number_to_id_number.php is provided and idempotent.
- Verify migration via: GET /api/migration-status (Admin token required) which returns JSON flags for the students column and index.
