# Soft Specification - School Management System
Version: 2025-11-21  
Audience: Product, Engineering, QA  
Scope: Current full-stack implementation (backend1 PHP Slim API + frontend1 React)

## Purpose
- Provide a concise, implementation-aware specification for the School Management System that captures functional scope, roles, data responsibilities, and non-functional requirements.
- Serve as the reference for alignment between stakeholders, developers, and QA for the November 2025 release.

## System Overview
- Multi-tenant school management platform with RBAC, scoped data by school (admin_id) and academic year.
- Modules: authentication, academic year lifecycle with automatic student promotion, student/teacher management, class/subject management, exams and grading, attendance, finance and payments, notices and complaints, notifications (standard and urgent), town master operations, parent portal, reports/analytics, and system settings.
- Tech stack: Backend PHP 8.2 (Slim 4, MySQL/PDO, JWT), Frontend React 18 (Vite, Redux Toolkit, MUI + Tailwind), MySQL 5.7+ database.

## User Roles and Access
- **Admin (School owner)**: Full control of school data, configuration, fee structures, academic years, user roles.
- **Principal**: Receives all notifications (including urgent), can mark actions taken on urgent items.
- **Finance Officer**: Manages fees, invoices, payment reconciliation, and financial reports.
- **Exam Officer**: Oversees exams, publishing results, and grading workflows.
- **Town Master (Teacher role flag)**: Manages assigned town blocks, registers eligible students, records town attendance, views parent contacts.
- **Teacher**: Class assignments, subject teaching, attendance marking, grading (where permitted).
- **Student**: Access to personal grades, attendance, notices, and limited self-service.
- **Parent/Guardian**: Self-registers, links children via Student ID + DOB, views attendance/results/notices, communicates with school.
- **Medical/Health staff (where enabled)**: Authenticated access to health-related notices and medical portal logins.

## Functional Requirements
### Authentication and Authorization
- JWT-based authentication for all protected endpoints; tokens expire per .env configuration (default 24h).
- Role-based access enforced in controllers; ownership checks prevent cross-school data access.
- Login flows for Admin, Teacher, Student, Parent, Town Master, and specialized portals (Exam Officer, Medical, Principal).

### Multi-Tenant and Academic Year Context
- Every transactional table stores admin_id; queries filter by admin_id to isolate schools.
- Academic year lifecycle: create, set current, complete year, delete (with constraints).
- Automatic student promotion endpoint `/api/academic-years/{id}/promote` with configurable passing_percentage (default 40%); promotions update enrollments and graduation status.

### User and Role Management
- Teacher flags for is_town_master, is_exam_officer, is_finance_officer, is_principal.
- APIs to list users by role and role summaries for admin dashboards.
- Student identities use id_number (replaces roll_number); enrollment tied to current academic year.

### Student Lifecycle
- Register, update, deactivate/delete students (soft delete where applicable).
- Bulk upload via CSV and downloadable template.
- Enrollment tracks class/section, academic year, promotion status, and history.

### Teacher and Staff Management
- Register, update, and remove teachers; assign subjects and classes.
- Retrieve teacher assignments, classes, and subjects per teacher.
- Role-specific dashboards (exam officer, finance officer, town master, principal).

### Classes, Subjects, and Timetable
- CRUD for classes and subjects; class-to-subject associations.
- Timetable creation and retrieval by class; teacher timetable views.

### Exams, Grades, and Results
- Define exams, record exam results, compute subject grades, and aggregate grades per student.
- Publish/pending status tracking; access controlled by role.
- Results available to students and parents for linked children.

### Attendance
- Class attendance: mark daily attendance and view statistics per student.
- Town attendance: town masters record attendance by block; absence can trigger notifications.
- Attendance stats endpoints for dashboards; academic year filtering enforced.

### Finance and Payments
- Fee structures per academic year and class; expected amounts tracked.
- Record payments, update/delete with audit constraints; auto-generate receipts/invoices.
- Payment history with filters (term/date/student); fee collection analytics.

### Notifications and Urgent Alerts
- Standard notifications CRUD with unread counts and mark-read support.
- Urgent notifications table with priority and action tracking; principals can mark action taken with notes.
- Automatic repeated absence alert (>=3 absences in 30 days) creates urgent notification; principals receive all alerts.

### Notices and Complaints
- Notice board CRUD scoped to school; audience targeting.
- Complaint submission, status updates, and ownership checks to prevent cross-school access.

### Parent Portal
- Parent self-registration and login (JWT).
- Child linking via Student ID + exact DOB; multiple children per parent; duplicate linking prevented.
- Parent dashboard: attendance, exam results, notices, notifications, communications, and profile management.

### Town Master Module
- Town management: towns/blocks CRUD and teacher assignment as town master.
- Simplified student registration: search eligible paid students, assign to block without re-entering data.
- Town master views student details with linked parents’ contact info; access limited to assigned town.

### Reports and Analytics
- Dashboard statistics: attendance trends, class counts, fees collected/pending, results published/pending, notices, complaints.
- Performance, attendance, and financial overview reports; fee collection by class/term.
- Export capability for activity logs and selected reports (CSV).

### System Settings and Configuration
- School settings (name, currency, email settings), CORS origins, JWT expiry via .env.
- Email test endpoint; system-wide configuration stored in settings table.

### Activity Logs and Audit
- Activity log endpoints with filters and stats; CSV export.
- Ownership and admin scoping enforced; logs include user, action, timestamp, and resource.

## Data Model (High Level)
- Core tables: admins, teachers, students, classes, subjects, academic_years, student_enrollments, teacher_assignments, exams, exam_results, grades, attendance, fee_structures, payments/invoices, notices, complaints, settings.
- Extensions: parent accounts and parent_student_links, urgent_notifications, principal_notification_subscriptions, town_master assignments and blocks, activity_logs, user_roles summary views.
- All transactional tables store admin_id; most store academic_year_id for year-specific scoping.

## Interfaces
- REST API base: `http://localhost:8080/api` (Slim 4). Consistent response envelope: `{ success, message?, data? }`.
- Frontend: React (Vite) SPA with redesigned authentication flows (login/register per role, password reset, choose portal, dashboards) and responsive glass-morphism UI.
- CLI/automation: batch scripts for running backend/front-end, migrations, and quick endpoint tests.

## Non-Functional Requirements
- **Security**: JWT auth on all protected routes; bcrypt password hashing; input validation and sanitization; prepared statements for SQL; ownership checks on updates/deletes; CORS configuration; tokens expire per policy.
- **Performance**: Dashboard endpoints optimized; database indexes on frequent filters (academic_year_id, dates, student_id, teacher_id); target sub-100ms API responses for typical queries; debounce client filters to avoid API spam.
- **Reliability**: Database migrations idempotent; backups recommended before major releases; automatic receipt/invoice generation must be atomic with payment records.
- **Usability & Accessibility**: Responsive layouts for mobile/desktop; consistent navigation and toasts; clear validation errors; password reset self-service.
- **Compatibility**: Supported browsers Chrome/Firefox/Safari/Edge (latest); API uses JSON over HTTPS in production.
- **Maintainability**: Layered backend (Controllers/Services/Models/Middleware); code documented; environment config via .env; activity logging for traceability.
- **Observability**: Dashboard stats endpoints, activity logs, admin charts; error responses avoid leaking sensitive data.

## Operational and Environment
- Development prerequisites: PHP 7.4+ (target 8.2), MySQL 5.7+, Composer, Node.js 14+.
- Quick start: `START_BACKEND.bat` (PHP server) and `START_FRONTEND.bat` (Vite dev server at http://localhost:5173).
- Backend manual: `composer install`, copy `.env`, set DB creds, run `php -S localhost:8080 -t public`.
- Environment variables: DB host/port/name/user/pass, JWT secret/expiry, CORS origin, app env/debug flags.
- Deployment: see `backend1/DEPLOYMENT_GUIDE.md` for Apache/Nginx, HTTPS, performance tuning, and backups.

## Acceptance Criteria (Release 2025-11-21)
- Authentication succeeds for all roles; protected endpoints reject unauthorized/forbidden requests.
- Academic year operations and promotion endpoint work with correct thresholds and audit of promoted/failed counts.
- Parent portal: self-registration, login, child linking via ID+DOB, and viewing attendance/results succeed.
- Town master: can view assigned students/parents, register eligible paid students to blocks, and record attendance.
- Urgent notifications auto-create on repeated absences; principals can mark action taken with notes.
- Payments generate receipts/invoices; financial stats reflect collected vs pending amounts.
- Activity logs export and filter correctly; notices/complaints enforce admin ownership.
- Frontend delivers responsive, consistent UI for login/register/forgot-reset flows and dashboards without console errors.

## Out of Scope / Future Enhancements
- ASR microservice integration (Python) for speech-to-text endpoint.
- Mobile app, library/hostel management, biometric attendance, SMS/email gateways, PDF/Excel exports, and advanced caching.
- Two-factor authentication and rate limiting for login and sensitive actions.
