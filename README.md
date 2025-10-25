# School Management System

A comprehensive, full-stack School Management System with automatic student promotion based on academic performance.

## 🌟 Features

### Core Functionality
- **Multi-tenant Architecture** - Support for multiple schools
- **Role-Based Access Control** - Admin, Teacher, and Student roles
- **Academic Year Management** - Complete academic year lifecycle
- **Automatic Student Promotion** ⭐ - Intelligent promotion based on class average
- **Comprehensive Reporting** - Attendance, grades, fees, and performance reports

### Modules
1. **Student Management** - Enrollment, records, progression tracking
2. **Teacher Management** - Staff records, subject assignments
3. **Class & Subject Management** - Grades, sections, subjects
4. **Examination System** - Exams, results, grade calculation
5. **Attendance Tracking** - Daily attendance with statistics
6. **Payment & Finance** ⭐ - Fee structures, payment recording, invoices, receipts
7. **Reports & Analytics** ⭐ - Performance, attendance, financial, behavior reports
8. **Timetable Management** ⭐ - Class scheduling and teacher timetables
9. **Notifications** ⭐ - System-wide notification management
10. **Notice Board** - Announcements for students, teachers, parents
11. **Complaint Management** - Issue reporting and resolution
12. **System Settings** ⭐ - School configuration and activity logs

## 🏗️ Architecture

### Frontend
- **Framework:** React 18.2.0
- **State Management:** Redux Toolkit
- **UI Library:** Material-UI 5 + TailwindCSS
- **Routing:** React Router 6
- **HTTP Client:** Axios

### Backend (NEW - backend1 folder)
- **Framework:** PHP Slim 4
- **Database:** MySQL
- **Authentication:** JWT (JSON Web Tokens)
- **Validation:** Custom + Respect/Validation
- **Dependency Management:** Composer

### Database
- **Type:** MySQL 5.7+
- **Tables:** 35 tables with proper relationships
- **Features:** Foreign keys, indexes, multi-tenant support, auto-generated receipts/invoices

## 📦 Project Structure

```
School Management System/
│
├── backend/              # Original Node.js backend (deprecated)
├── backend1/             # NEW PHP Slim backend ⭐
├── frontend/             # React frontend
└── Documentation files   # Setup and integration guides
```

## 🚀 Quick Start (30 seconds)

### Easy Start
```bash
# Terminal 1 - Backend
START_BACKEND.bat

# Terminal 2 - Frontend
START_FRONTEND.bat

# Browser
http://localhost:5173
```

**Login:** koromaemmanuel66@gmail.com / 11111111

### Manual Setup (First Time)

**Prerequisites:** PHP 7.4+, MySQL 5.7+, Composer, Node.js 14+

**Backend:**
```bash
cd backend1
composer install
cp .env.example .env
# Edit .env with database credentials
php -S localhost:8080 -t public
```

**Frontend:**
```bash
cd frontend1
npm install
npm start
```

## 📚 Documentation

- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Admin features quick guide
- **[backend1/README.md](backend1/README.md)** - Complete API documentation

## 🎓 Student Promotion System

### How It Works

The system automatically promotes students based on their academic performance:

1. **Grade Calculation:**
   - Calculates average across all subjects
   - Considers all exams and assignments

2. **Promotion Criteria:**
   - Default passing percentage: 40% (configurable)
   - Students >= 40%: Promoted to next grade
   - Students < 40%: Marked as failed (repeat year)
   - Final grade students: Graduated

3. **Automatic Process:**
   ```bash
   POST /api/academic-years/{id}/promote
   {
     "passing_percentage": 40
   }
   ```

4. **Result Tracking:**
   - Complete history in `student_enrollments` table
   - Tracks: class average, attendance, promotion status
   - Reports available for analysis

### Example Response:
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

## 🔐 Authentication

All protected endpoints require JWT token:

```bash
# Login
curl -X POST http://localhost:8080/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@school.com","password":"password123"}'

# Use token in subsequent requests
curl -X GET http://localhost:8080/api/students \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## 📊 API Endpoints

### Authentication
- `POST /api/admin/register` - Register school
- `POST /api/admin/login` - Admin login
- `POST /api/students/login` - Student login
- `POST /api/teachers/login` - Teacher login

### Student Management
- `POST /api/students/register` - Register student
- `GET /api/students` - List students
- `GET /api/students/{id}` - Get student
- `PUT /api/students/{id}` - Update student
- `DELETE /api/students/{id}` - Delete student

### Academic Year & Promotion
- `POST /api/academic-years` - Create year
- `GET /api/academic-years/current` - Get current year
- `POST /api/academic-years/{id}/promote` - Promote students
- `PUT /api/academic-years/{id}/complete` - Complete year

### Grades & Exams
- `POST /api/exams` - Create exam
- `POST /api/grades` - Add/update grade
- `GET /api/grades/students/{id}` - Get student grades

### Attendance
- `POST /api/attendance` - Mark attendance
- `GET /api/attendance/students/{id}/stats` - Get statistics

### Payment & Finance ⭐
- `GET /api/fee-structures` - List fee structures
- `POST /api/fee-structures` - Create fee structure
- `POST /api/payments` - Record payment (auto-generates receipt)
- `GET /api/payments/history` - Payment history with filters
- `POST /api/invoices` - Create invoice
- `GET /api/invoices` - List invoices

### Reports & Analytics ⭐
- `GET /api/reports/dashboard-stats` - Dashboard overview
- `GET /api/reports/class-performance` - Class performance analysis
- `GET /api/reports/subject-performance` - Subject performance
- `GET /api/reports/top-performers` - Top 10 students
- `GET /api/reports/attendance-summary` - Attendance statistics
- `GET /api/reports/financial-overview` - Financial analytics
- `GET /api/reports/fee-collection-by-class` - Collection rates

### Timetable & Notifications ⭐
- `POST /api/timetable` - Create timetable entry
- `GET /api/timetable/class/{id}` - Get class schedule
- `POST /api/notifications` - Send notification
- `GET /api/notifications` - List notifications

**See [backend1/README.md](backend1/README.md) for complete API documentation (80+ endpoints)**

## 🗄️ Database Schema

### Core Tables
- **admins** - School administrators
- **academic_years** - Academic year definitions
- **classes** - Grade levels and sections
- **subjects** - Subject definitions
- **students** - Student records
- **teachers** - Teacher records

### Tracking Tables
- **student_enrollments** - Student progression
- **teacher_assignments** - Subject assignments
- **grades** - Overall subject grades
- **exam_results** - Individual exam results
- **attendance** - Attendance records

### Finance & Payment ⭐
- **fee_structures** - Fee configuration
- **payments** - Payment records with auto-receipt
- **invoices** - Invoice management
- **invoice_items** - Invoice line items

### Admin Features ⭐
- **timetables** - Class scheduling
- **notifications** - System notifications
- **notification_reads** - Read tracking
- **school_settings** - School configuration

### Communication Tables
- **notices** - Announcements
- **complaints** - Issue tracking

## 🧪 Testing

### Quick API Test
```bash
cd backend1
test_api.bat
```

### Manual Testing
```bash
# Register admin
curl -X POST http://localhost:8080/api/admin/register \
  -H "Content-Type: application/json" \
  -d '{"school_name":"Test School","email":"test@school.com","password":"password123"}'

# Login
curl -X POST http://localhost:8080/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@school.com","password":"password123"}'

# Get profile (use token from login)
curl -X GET http://localhost:8080/api/admin/profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🚢 Deployment

### Development
```bash
cd backend1
php -S localhost:8080 -t public
```

### Production

See [backend1/DEPLOYMENT_GUIDE.md](backend1/DEPLOYMENT_GUIDE.md) for:
- Apache configuration
- Nginx configuration
- Security checklist
- Performance optimization
- Backup strategy

## 🔒 Security Features

- ✅ Password hashing (bcrypt)
- ✅ JWT authentication
- ✅ Input validation & sanitization
- ✅ SQL injection prevention (PDO)
- ✅ XSS protection
- ✅ CORS configuration
- ✅ Token expiration

## 📈 Key Improvements

### Over Original System
1. **Academic Year Context** - Everything operates within academic year
2. **Automatic Promotion** - Intelligent student promotion system
3. **Better Data Integrity** - Foreign keys, proper relationships
4. **Enhanced Security** - JWT, password hashing, validation
5. **Improved Performance** - Indexed queries, efficient joins
6. **Complete Documentation** - Comprehensive guides
7. **Easy Setup** - Automated scripts

## 🛠️ Technology Stack

### Backend
- PHP 8.2
- Slim Framework 4
- MySQL/PDO
- JWT Authentication
- Composer

### Frontend
- React 18
- Redux Toolkit
- Material-UI 5
- TailwindCSS 3
- Axios

## 📝 Configuration

### Backend (.env)
```env
# Application
APP_NAME="School Management System"
APP_ENV=development
APP_DEBUG=true

# Database
DB_HOST=localhost
DB_PORT=4306
DB_NAME=school_management
DB_USER=root
DB_PASS=1212

# JWT
JWT_SECRET=your-secret-key
JWT_EXPIRY=86400

# CORS
CORS_ORIGIN=http://localhost:3000
```

### Frontend (.env)
```env
REACT_APP_BASE_URL=http://localhost:8080/api
```

## 🤝 Contributing

This is a closed-source project for SABITECK School Management System.

## 📄 License

Proprietary - All rights reserved

## 👨‍💻 Author

SABITECK - School Management Solutions

## 🆘 Support

For issues and questions:
1. Check [QUICK_REFERENCE.md](QUICK_REFERENCE.md) for admin features guide
2. Review [backend1/README.md](backend1/README.md) for complete API docs

## 🎯 Roadmap

### Current Version (v2.5) ⭐
- ✅ PHP backend with MySQL
- ✅ Academic year management
- ✅ Automatic student promotion
- ✅ Complete CRUD operations
- ✅ JWT authentication
- ✅ Payment & finance management with auto-receipts
- ✅ Reports & analytics dashboard
- ✅ Timetable management
- ✅ Notification system
- ✅ System settings & activity logs

### Future Enhancements
- [ ] Parent portal
- [ ] Mobile app (React Native)
- [ ] Report card PDF export
- [ ] Email/SMS notifications
- [ ] Excel export for reports
- [ ] Biometric attendance
- [ ] Online exam module
- [ ] Library management
- [ ] Hostel management

## ⚡ Performance

- Fast response times (<100ms for most endpoints)
- Optimized database queries with indexes
- Efficient join operations
- Connection pooling support
- Caching ready (Redis compatible)

## 🌐 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## 📞 Contact

For business inquiries: [Your contact information]

---

**Built with ❤️ by SABITECK**

Last Updated: October 2025
Version: 2.0.0
#   s c h o o l - m a n a g e m e n t -  
 #   s c h o o l - m a n a g e m e n t -  
 