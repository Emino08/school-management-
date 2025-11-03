import React, { useEffect, useState } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useDispatch, useSelector } from 'react-redux';
import { calculateOverallAttendancePercentage } from '../../components/attendanceCalculator';
import CustomPieChart from '../../components/CustomPieChart';
import { getUserDetails } from '../../redux/userRelated/userHandle';
import SeeNotice from '../../components/SeeNotice';
import CountUp from 'react-countup';
import Subject from "../../assets/subjects.svg";
import Assignment from "../../assets/assignment.svg";
import { getSubjectList } from '../../redux/sclassRelated/sclassHandle';
import { MdBook, MdAssignment, MdCheckCircle, MdPendingActions, MdAnnouncement, MdBarChart, MdTrendingUp, MdWarning } from 'react-icons/md';
import axios from '../../redux/axiosConfig';

const StudentHomePage = () => {
    const dispatch = useDispatch();

    const { userDetails, currentUser, loading, response } = useSelector((state) => state.user);
    const { subjectsList } = useSelector((state) => state.sclass);

    const [subjectAttendance, setSubjectAttendance] = useState([]);
    const [dashboardStats, setDashboardStats] = useState(null);
    const [statsLoading, setStatsLoading] = useState(true);

    // Safely get classID with optional chaining
    const classID = currentUser?.sclassName?._id || currentUser?.class_id;

    useEffect(() => {
        // Only fetch data if we have valid IDs
        if (currentUser?.id || currentUser?._id) {
            const studentId = currentUser.id || currentUser._id;
            dispatch(getUserDetails(studentId, "Student"));

            if (classID) {
                dispatch(getSubjectList(classID, "ClassSubjects"));
            }

            // Fetch dashboard stats
            fetchDashboardStats();
        }
    }, [dispatch, currentUser?.id, currentUser?._id, classID]);

    const fetchDashboardStats = async () => {
        try {
            setStatsLoading(true);
            const response = await axios.get(
                `${import.meta.env.VITE_API_BASE_URL}/students/dashboard-stats`
            );

            if (response.data.success) {
                setDashboardStats(response.data.stats);
            } else {
                console.warn('Dashboard stats request unsuccessful:', response.data);
            }
        } catch (error) {
            console.error('Failed to fetch dashboard stats:', error);
            // Don't show error to user, just log it - we'll fall back to showing 0s
        } finally {
            setStatsLoading(false);
        }
    };

    const numberOfSubjects = dashboardStats?.subjects_count || (subjectsList && subjectsList.length) || 0;

    useEffect(() => {
        if (userDetails) {
            setSubjectAttendance(userDetails.attendance || []);
        }
    }, [userDetails])

    const overallAttendancePercentage = dashboardStats?.attendance_percentage || calculateOverallAttendancePercentage(subjectAttendance);
    const overallAbsentPercentage = 100 - overallAttendancePercentage;

    const chartData = [
        { name: 'Present', value: overallAttendancePercentage },
        { name: 'Absent', value: overallAbsentPercentage }
    ];

    return (
        <div className="container max-w-7xl mx-auto mt-8 mb-8 px-4">
            {/* Welcome Section */}
            <div className="mb-6">
                <h1 className="text-3xl font-bold text-gray-800">
                    Welcome back, {currentUser?.name || 'Student'}!
                </h1>
                <p className="text-gray-600 mt-1">Here's your academic overview</p>
            </div>

            {/* Analytics Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                {/* Total Subjects */}
                <Card className="hover:shadow-lg transition-shadow">
                    <CardContent className="flex flex-col items-center justify-between h-[180px] p-6 text-center">
                        <div className="bg-blue-100 p-3 rounded-full">
                            <MdBook className="w-8 h-8 text-blue-600" />
                        </div>
                        <div>
                            <p className="text-gray-600 text-sm mb-1">Total Subjects</p>
                            {statsLoading ? (
                                <span className="text-2xl font-bold text-gray-400">Loading...</span>
                            ) : (
                                <span className="text-3xl font-bold text-blue-600">
                                    <CountUp
                                        end={Number(numberOfSubjects)}
                                        duration={1.2}
                                        preserveValue
                                        redraw
                                    >
                                        {({ countUpRef }) => <span ref={countUpRef} />}
                                    </CountUp>
                                </span>
                            )}
                            {!statsLoading && numberOfSubjects === 0 && (
                                <p className="text-xs text-gray-500 mt-1">No subjects enrolled yet</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Exams/Results */}
                <Card className="hover:shadow-lg transition-shadow">
                    <CardContent className="flex flex-col items-center justify-between h-[180px] p-6 text-center">
                        <div className="bg-purple-100 p-3 rounded-full">
                            <MdAssignment className="w-8 h-8 text-purple-600" />
                        </div>
                        <div>
                            <p className="text-gray-600 text-sm mb-1">Exams Taken</p>
                            {statsLoading ? (
                                <span className="text-2xl font-bold text-gray-400">Loading...</span>
                            ) : (
                                <span className="text-3xl font-bold text-purple-600">
                                    <CountUp
                                        end={dashboardStats?.exams_count || 0}
                                        duration={1.2}
                                        preserveValue
                                        redraw
                                    >
                                        {({ countUpRef }) => <span ref={countUpRef} />}
                                    </CountUp>
                                </span>
                            )}
                            {!statsLoading && (dashboardStats?.exams_count || 0) === 0 && (
                                <p className="text-xs text-gray-500 mt-1">No exams yet</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Attendance Percentage */}
                <Card className="hover:shadow-lg transition-shadow">
                    <CardContent className="flex flex-col items-center justify-between h-[180px] p-6 text-center">
                        <div className={`p-3 rounded-full ${
                            overallAttendancePercentage >= 75 ? 'bg-green-100' :
                            overallAttendancePercentage >= 50 ? 'bg-yellow-100' : 'bg-red-100'
                        }`}>
                            <MdCheckCircle className={`w-8 h-8 ${
                                overallAttendancePercentage >= 75 ? 'text-green-600' :
                                overallAttendancePercentage >= 50 ? 'text-yellow-600' : 'text-red-600'
                            }`} />
                        </div>
                        <div>
                            <p className="text-gray-600 text-sm mb-1">Attendance</p>
                            {statsLoading ? (
                                <span className="text-2xl font-bold text-gray-400">Loading...</span>
                            ) : (
                                <>
                                    <span className={`text-3xl font-bold ${
                                        overallAttendancePercentage >= 75 ? 'text-green-600' :
                                        overallAttendancePercentage >= 50 ? 'text-yellow-600' : 'text-red-600'
                                    }`}>
                                        <CountUp
                                            end={overallAttendancePercentage}
                                            duration={1.2}
                                            decimals={1}
                                            suffix="%"
                                            preserveValue
                                            redraw
                                        >
                                            {({ countUpRef }) => <span ref={countUpRef} />}
                                        </CountUp>
                                    </span>
                                    {overallAttendancePercentage < 75 && overallAttendancePercentage > 0 && (
                                        <p className="text-xs text-orange-600 mt-1 flex items-center justify-center gap-1">
                                            <MdWarning className="w-3 h-3" /> Below 75%
                                        </p>
                                    )}
                                    {overallAttendancePercentage === 0 && (
                                        <p className="text-xs text-gray-500 mt-1">No records yet</p>
                                    )}
                                </>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Notices */}
                <Card className="hover:shadow-lg transition-shadow">
                    <CardContent className="flex flex-col items-center justify-between h-[180px] p-6 text-center">
                        <div className="bg-orange-100 p-3 rounded-full">
                            <MdAnnouncement className="w-8 h-8 text-orange-600" />
                        </div>
                        <div>
                            <p className="text-gray-600 text-sm mb-1">New Notices</p>
                            {statsLoading ? (
                                <span className="text-2xl font-bold text-gray-400">Loading...</span>
                            ) : (
                                <span className="text-3xl font-bold text-orange-600">
                                    <CountUp
                                        end={dashboardStats?.notices_count || 0}
                                        duration={1.2}
                                        preserveValue
                                        redraw
                                    >
                                        {({ countUpRef }) => <span ref={countUpRef} />}
                                    </CountUp>
                                </span>
                            )}
                            {!statsLoading && (dashboardStats?.notices_count || 0) === 0 && (
                                <p className="text-xs text-gray-500 mt-1">No new notices</p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Secondary Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                {/* Complaints Status */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <MdPendingActions className="w-5 h-5" />
                            Complaints
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {statsLoading ? (
                            <div className="text-center py-4">
                                <p className="text-gray-400">Loading...</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Pending</p>
                                    <p className="text-2xl font-bold text-yellow-600">
                                        {dashboardStats?.complaints_pending || 0}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Resolved</p>
                                    <p className="text-2xl font-bold text-green-600">
                                        {dashboardStats?.complaints_resolved || 0}
                                    </p>
                                </div>
                            </div>
                        )}
                        {!statsLoading && !dashboardStats?.complaints_total && (
                            <p className="text-xs text-gray-500 mt-2 text-center">No complaints submitted</p>
                        )}
                    </CardContent>
                </Card>

                {/* Average Performance */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <MdBarChart className="w-5 h-5" />
                            Average Score
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {statsLoading ? (
                            <div className="text-center py-4">
                                <p className="text-gray-400">Loading...</p>
                            </div>
                        ) : (
                            <div className="text-center">
                                {dashboardStats?.average_marks ? (
                                    <>
                                        <p className={`text-4xl font-bold ${
                                            dashboardStats.average_marks >= 75 ? 'text-green-600' :
                                            dashboardStats.average_marks >= 50 ? 'text-blue-600' : 'text-orange-600'
                                        }`}>
                                            {dashboardStats.average_marks.toFixed(1)}%
                                        </p>
                                        <p className="text-sm text-gray-600 mt-1">Recent Exams</p>
                                        {dashboardStats.average_marks >= 75 && (
                                            <p className="text-xs text-green-600 mt-1 flex items-center justify-center gap-1">
                                                <MdTrendingUp className="w-3 h-3" /> Excellent!
                                            </p>
                                        )}
                                    </>
                                ) : (
                                    <p className="text-gray-500 py-2">No results yet</p>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Attendance Details */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <MdCheckCircle className="w-5 h-5" />
                            Attendance Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {statsLoading ? (
                            <div className="text-center py-4">
                                <p className="text-gray-400">Loading...</p>
                            </div>
                        ) : dashboardStats?.attendance_total > 0 ? (
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600">Present Days:</span>
                                    <span className="font-semibold text-green-600">
                                        {dashboardStats.attendance_present}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600">Absent Days:</span>
                                    <span className="font-semibold text-red-600">
                                        {dashboardStats.attendance_total - dashboardStats.attendance_present}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600">Total Days:</span>
                                    <span className="font-semibold">
                                        {dashboardStats.attendance_total}
                                    </span>
                                </div>
                            </div>
                        ) : (
                            <p className="text-gray-500 text-sm text-center py-4">No attendance recorded yet</p>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Academic Info Section */}
            {currentUser?.sclassName && (
                <Card className="mb-6 bg-gradient-to-r from-purple-50 to-blue-50 border-purple-200">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <MdBook className="w-5 h-5 text-purple-600" />
                            Academic Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Class</p>
                                <p className="text-lg font-semibold text-purple-700">
                                    {currentUser.sclassName?.sclassName || 'Not Assigned'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Student ID</p>
                                <p className="text-lg font-semibold text-purple-700">
                                    {currentUser.rollNum || currentUser.id_number || 'N/A'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Grade Level</p>
                                <p className="text-lg font-semibold text-purple-700">
                                    {currentUser.sclassName?.grade_level ? `Grade ${currentUser.sclassName.grade_level}` : 'N/A'}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Notices Section */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <MdAnnouncement className="w-6 h-6" />
                        Recent Notices
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <SeeNotice />
                </CardContent>
            </Card>
        </div>
    )
}

export default StudentHomePage
