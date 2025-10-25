import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { FiMenu, FiChevronLeft } from 'react-icons/fi';
import TeacherSideBar from './TeacherSideBar';
import { Navigate, Route, Routes } from 'react-router-dom';
import Logout from '../Logout'
import AccountMenu from '../../components/AccountMenu';
import { drawerWidth } from '../../components/styles';
import StudentAttendance from '../admin/studentRelated/StudentAttendance';

import TeacherClassDetails from './TeacherClassDetails';
import TeacherComplain from './TeacherComplain';
import TeacherHomePage from './TeacherHomePage';
import TeacherProfile from './TeacherProfile';
import TeacherViewStudent from './TeacherViewStudent';
import StudentExamMarks from '../admin/studentRelated/StudentExamMarks';
import TeacherClasses from "./TeacherClasses";
import TeacherSubmitGrades from "./TeacherSubmitGrades";
import TeacherSubmissions from "./TeacherSubmissions";
import TeacherAttendance from "./TeacherAttendance";
import TeacherTimetable from "./TeacherTimetable";

const TeacherDashboard = () => {
    const [open, setOpen] = useState(true);
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
                    <TeacherSideBar />
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
                            <h1 className="text-xl font-semibold">Teacher Dashboard</h1>
                        </div>
                        <AccountMenu />
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
                            <Route path="/" element={<TeacherHomePage />} />
                            <Route path="Teacher/dashboard" element={<TeacherHomePage />} />
                            <Route path="Teacher/profile" element={<TeacherProfile />} />

                            <Route path="Teacher/complain" element={<TeacherComplain />} />
                            <Route path="Teacher/attendance" element={<TeacherAttendance />} />

                            <Route path="Teacher/class" element={<TeacherClassDetails />} />
                            <Route path="Teacher/classes" element={<TeacherClasses />} />
                            <Route path="Teacher/submit-grades" element={<TeacherSubmitGrades />} />
                            <Route path="Teacher/submissions" element={<TeacherSubmissions />} />
                            <Route path="Teacher/class/student/:id" element={<TeacherViewStudent />} />

                            <Route path="Teacher/class/student/attendance/:studentID/:subjectID" element={<StudentAttendance situation="Subject" />} />
                            <Route path="Teacher/class/student/marks/:studentID/:subjectID" element={<StudentExamMarks situation="Subject" />} />
                            <Route path="Teacher/timetable" element={<TeacherTimetable />} />

                            <Route path="logout" element={<Logout />} />

                            {/* Catch-all route - must be last */}
                            <Route path='*' element={<Navigate to="/Teacher/dashboard" />} />
                        </Routes>
                    </div>
                </main>
            </div>
        </div>
    );
}

export default TeacherDashboard
