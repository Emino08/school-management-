import {useState} from "react";
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { FiMenu, FiChevronLeft } from 'react-icons/fi';
import {Navigate, Route, Routes, useLocation} from "react-router-dom";
import Logout from "../Logout";
import SideBar from "./SideBar";
import AdminProfile from "./AdminProfile";
import AdminHomePage from "./AdminHomePage";
import Fees from "./feesPaymentRelated/FeesPayment";
import EnhancedFeesManagement from "./feesPaymentRelated/EnhancedFeesManagement";

import AddStudent from "./studentRelated/AddStudent";
import SeeComplains from "./studentRelated/SeeComplains";
import ShowStudents from "./studentRelated/ShowStudents";
import StudentAttendance from "./studentRelated/StudentAttendance";
import StudentExamMarks from "./studentRelated/StudentExamMarks";
import ViewStudent from "./studentRelated/ViewStudent";
import StudentManagement from "./studentRelated/StudentManagement";
import AttendanceManagement from "./studentRelated/AttendanceManagement";

import AddNotice from "./noticeRelated/AddNotice";
import ShowNotices from "./noticeRelated/ShowNotices";

import ShowSubjects from "./subjectRelated/ShowSubjects";
import SubjectForm from "./subjectRelated/SubjectForm";
import ViewSubject from "./subjectRelated/ViewSubject";
import SubjectManagement from "./subjectRelated/SubjectManagement";

import AddTeacher from "./teacherRelated/AddTeacher";
import ChooseClass from "./teacherRelated/ChooseClass";
import ChooseSubject from "./teacherRelated/ChooseSubject";
import ShowTeachers from "./teacherRelated/ShowTeachers";
import TeacherDetails from "./teacherRelated/TeacherDetails";
import TeacherManagement from "./teacherRelated/TeacherManagement";

import AddClass from "./classRelated/AddClass";
import ClassDetails from "./classRelated/ClassDetails";
import ShowClasses from "./classRelated/ShowClasses";
import ClassManagement from "./classRelated/ClassManagement";
import AccountMenu from "../../components/AccountMenu";
import AcademicYear from "./academicYearRelated/AcademicYear";
import ManageAcademicYears from "./academicYearRelated/ManageAcademicYears";
import CreateAcademicYear from "./academicYearRelated/CreateAcademicYear";
import ManageResultPins from "./resultRelated/ManageResultPins";
import GradingSystemConfig from "./gradingSystemRelated/GradingSystemConfig";
import PublishResults from "./resultRelated/PublishResults";
import ViewAllResults from "./resultRelated/ViewAllResults";

// New Admin Features
import PaymentManagement from "./payments/PaymentManagement";
import ReportsAnalytics from "./reports/ReportsAnalytics";
import NotificationManagement from "./notifications/NotificationManagement";
import TimetableManagement from "./timetable/TimetableManagement";
import SystemSettings from "./settings/SystemSettings";
import ActivityLogs from "./logs/ActivityLogs";

const AdminDashboard = () => {
    const [open, setOpen] = useState(false);
    const location = useLocation();
    
    console.log('=== ADMIN DASHBOARD RENDER ===');
    console.log('Current location:', location.pathname);
    
    const toggleDrawer = () => {
        setOpen(!open);
    };

    return (
        <div className="flex min-h-screen bg-gray-100">
            {/* Sidebar */}
            <aside
                className={`fixed left-0 top-0 h-full bg-white border-r border-gray-200 transition-all duration-300 z-20 ${
                    open ? 'w-60' : 'w-0'
                } overflow-hidden`}
            >
                <div className="flex items-center justify-end p-4 border-b border-gray-200">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={toggleDrawer}
                        className="h-8 w-8"
                    >
                        <FiChevronLeft className="h-5 w-5" />
                    </Button>
                </div>
                <Separator />
                <nav className="p-2">
                    <SideBar/>
                </nav>
            </aside>

            {/* Main Content */}
            <div className="flex-1 flex flex-col">
                {/* Header */}
                <header
                    className={`fixed top-0 right-0 bg-purple-700 text-white shadow-md z-10 transition-all duration-300 ${
                        open ? 'left-60' : 'left-0'
                    }`}
                >
                    <div className="flex items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-4">
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={toggleDrawer}
                                className={`text-white hover:bg-purple-600 ${open ? 'hidden' : 'flex'}`}
                            >
                                <FiMenu className="h-6 w-6" />
                            </Button>
                            <h1 className="text-xl font-semibold">Admin Dashboard</h1>
                        </div>
                        <AccountMenu/>
                    </div>
                </header>

                {/* Page Content */}
                <main
                    className={`flex-1 pt-20 transition-all duration-300 ${
                        open ? 'ml-60' : 'ml-0'
                    }`}
                >
                    <div className="p-6">
                        <Routes>
                            <Route path="/" element={<AdminHomePage/>}/>
                            <Route path="Admin/dashboard" element={<AdminHomePage/>}/>
                            <Route path="Admin/profile" element={<AdminProfile/>}/>
                            <Route path="Admin/complains" element={<SeeComplains/>}/>

                            {/* Notice */}
                            <Route path="Admin/addnotice" element={<AddNotice/>}/>
                            <Route path="Admin/notices" element={<ShowNotices/>}/>

                            {/* Subject */}
                            <Route path="Admin/subjects-management" element={<SubjectManagement/>}/>
                            <Route path="Admin/subjects" element={<ShowSubjects/>}/>
                            <Route
                                path="Admin/subjects/subject/:classID/:subjectID"
                                element={<ViewSubject/>}
                            />
                            <Route
                                path="Admin/subjects/chooseclass"
                                element={<ChooseClass situation="Subject"/>}
                            />

                            <Route path="Admin/addsubject/:id" element={
                                <>
                                    {console.log('=== ADD SUBJECT ROUTE MATCHED ===')}
                                    <SubjectForm/>
                                </>
                            }/>
                            <Route
                                path="Admin/class/subject/:classID/:subjectID"
                                element={<ViewSubject/>}
                            />

                            <Route
                                path="Admin/subject/student/attendance/:studentID/:subjectID"
                                element={<StudentAttendance situation="Subject"/>}
                            />
                            <Route
                                path="Admin/subject/student/marks/:studentID/:subjectID"
                                element={<StudentExamMarks situation="Subject"/>}
                            />

                            {/* Class */}
                            <Route path="Admin/addclass" element={<AddClass/>}/>
                            <Route path="Admin/classes-management" element={<ClassManagement/>}/>
                            <Route path="Admin/classes" element={<ShowClasses/>}/>
                            <Route path="Admin/classes/class/:id" element={<ClassDetails/>}/>
                            <Route
                                path="Admin/class/addstudents/:id"
                                element={<AddStudent situation="Class"/>}
                            />

                            {/* Student */}
                            <Route path="Admin/students-management" element={<StudentManagement/>}/>
                            <Route
                                path="Admin/addstudents"
                                element={<AddStudent situation="Student"/>}
                            />
                            <Route path="Admin/students" element={<ShowStudents/>}/>
                            <Route
                                path="Admin/students/student/:id"
                                element={<ViewStudent/>}
                            />
                            <Route
                                path="Admin/students/student/attendance/:id"
                                element={<StudentAttendance situation="Student"/>}
                            />
                            <Route
                                path="Admin/students/student/marks/:id"
                                element={<StudentExamMarks situation="Student"/>}
                            />
                            
                            {/* Attendance */}
                            <Route path="Admin/attendance" element={<AttendanceManagement/>}/>

                            {/* Teacher */}
                            <Route path="Admin/teachers-management" element={<TeacherManagement/>}/>
                            <Route path="Admin/teachers" element={<ShowTeachers/>}/>
                            <Route
                                path="Admin/teachers/teacher/:id"
                                element={<TeacherDetails/>}
                            />
                            <Route
                                path="Admin/teachers/chooseclass"
                                element={<ChooseClass situation="Teacher"/>}
                            />
                            <Route
                                path="Admin/teachers/choosesubject/:id"
                                element={<ChooseSubject situation="Norm"/>}
                            />
                            <Route
                                path="Admin/teachers/choosesubject/:classID/:teacherID"
                                element={<ChooseSubject situation="Teacher"/>}
                            />
                            <Route
                                path="Admin/teachers/addteacher/:id"
                                element={<AddTeacher/>}
                            />

                            {/*Fees*/}
                            <Route path="Admin/fees" element={<EnhancedFeesManagement/>}/>
                            <Route path="Admin/fees-legacy" element={<Fees/>}/>

                            {/*Results & PINs*/}
                            <Route path="Admin/result-pins" element={<ManageResultPins/>}/>
                            <Route path="Admin/grading-system" element={<GradingSystemConfig/>}/>
                            <Route path="Admin/publish-results" element={<PublishResults/>}/>
                            <Route path="Admin/all-results" element={<ViewAllResults/>}/>

                            <Route path="logout" element={<Logout/>}/>

                            {/*Academic Year*/}
                            <Route path="Admin/academicYear" element={<CreateAcademicYear/>}/>
                            <Route path="Admin/academic-years" element={<CreateAcademicYear/>}/>

                            {/* New Admin Features */}
                            <Route path="Admin/payments/*" element={<PaymentManagement/>}/>
                            <Route path="Admin/reports/*" element={<ReportsAnalytics/>}/>
                            <Route path="Admin/notifications/*" element={<NotificationManagement/>}/>
                            <Route path="Admin/timetable/*" element={<TimetableManagement/>}/>
                            <Route path="Admin/settings/*" element={<SystemSettings/>}/>
                            <Route path="Admin/activity-logs" element={<ActivityLogs/>}/>

                            {/* Catch-all route - must be last */}
                            <Route path="*" element={<Navigate to="/Admin/dashboard"/>}/>
                        </Routes>
                    </div>
                </main>
            </div>
        </div>
    );
};

export default AdminDashboard;

