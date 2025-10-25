import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { FiMenu, FiChevronLeft } from 'react-icons/fi';
import StudentSideBar from './StudentSideBar';
import { Navigate, Route, Routes } from 'react-router-dom';
import StudentHomePage from './StudentHomePage';
import StudentProfile from './StudentProfile';
import StudentSubjects from './StudentSubjects';
import ViewStdAttendance from './ViewStdAttendance';
import StudentComplain from './StudentComplain';
import StudentResults from './StudentResults';
import StudentTimetable from "./StudentTimetable";
import Logout from '../Logout'
import AccountMenu from '../../components/AccountMenu';

const StudentDashboard = () => {
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
                    <StudentSideBar />
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
                            <h1 className="text-xl font-semibold">Student Dashboard</h1>
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
                            <Route path="/" element={<StudentHomePage />} />
                            <Route path="Student/dashboard" element={<StudentHomePage />} />
                            <Route path="Student/profile" element={<StudentProfile />} />

                            <Route path="Student/subjects" element={<StudentSubjects />} />
                            <Route path="Student/attendance" element={<ViewStdAttendance />} />
                            <Route path="Student/complain" element={<StudentComplain />} />
                            <Route path="Student/timetable" element={<StudentTimetable />} />
                            <Route path="Student/results" element={<StudentResults />} />

                            <Route path="logout" element={<Logout />} />
                            
                            {/* Catch-all route - must be last */}
                            <Route path='*' element={<Navigate to="/Student/dashboard" />} />
                        </Routes>
                    </div>
                </main>
            </div>
        </div>
    );
}

export default StudentDashboard
