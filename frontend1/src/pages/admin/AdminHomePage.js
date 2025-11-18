import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import SeeNotice from '../../components/SeeNotice';
import SystemGuide from '../../components/SystemGuide';
import AcademicYearSelector from '../../components/AcademicYearSelector';
import Students from "../../assets/img1.png";
import Classes from "../../assets/img2.png";
import Teachers from "../../assets/img3.png";
import Fees from "../../assets/img4.png";
import CountUp from 'react-countup';
import { useDispatch, useSelector } from 'react-redux';
import { ResponsiveContainer, LineChart, Line, CartesianGrid, XAxis, YAxis, Tooltip, Legend, BarChart, Bar, PieChart, Pie, Cell } from 'recharts';
import { useEffect, useState, useMemo, useCallback, useRef } from 'react';
import axios from '@/redux/axiosConfig';
import { getAllAcademicYears } from '@/redux/academicYearRelated/academicYearHandle.js';
import {
  MdSchool,
  MdClass,
  MdPeople,
  MdAttachMoney,
  MdCalendarToday,
  MdTrendingUp,
  MdInfo,
  MdArrowForward,
  MdAssignment,
  MdCheckCircle,
  MdWarning,
  MdNotifications,
  MdBook,
  MdEventNote
} from 'react-icons/md';
import { useNavigate } from 'react-router-dom';

const normalizeCount = (value) => {
    const parsedValue = Number(value);
    return Number.isFinite(parsedValue) ? parsedValue : 0;
};

const AdminHomePage = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [showGuide, setShowGuide] = useState(false);
    const hasFetchedInitialData = useRef(false);

    const { academicYearData } = useSelector((state) => state.academicYear);
    const { currentUser: reduxUser } = useSelector(state => state.user);

    // Memoize current user to prevent unnecessary re-renders
    const currentUser = useMemo(() => {
        if (reduxUser) return reduxUser;
        
        try {
            const userStr = localStorage.getItem('user');
            if (userStr) return JSON.parse(userStr);
        } catch (e) {
            console.error('Failed to parse user from localStorage:', e);
        }
        return null;
    }, [reduxUser]);

    // Memoize adminID
    const adminID = useMemo(() => 
        currentUser?.admin?.id || currentUser?._id || currentUser?.id,
        [currentUser]
    );

    // Memoize token validation
    const tokenHasAdminId = useMemo(() => {
        if (!currentUser?.token) return false;
        
        try {
            const tokenParts = currentUser.token.split('.');
            if (tokenParts.length === 3) {
                const payload = JSON.parse(atob(tokenParts[1]));
                return 'admin_id' in payload;
            }
        } catch (e) {
            console.error('Failed to decode token:', e);
        }
        return false;
    }, [currentUser?.token]);

    const [dashboardStats, setDashboardStats] = useState(null);
    const [dashboardCharts, setDashboardCharts] = useState(null);
    const [chartStart, setChartStart] = useState('');
    const [chartEnd, setChartEnd] = useState('');
    const [feesTerm, setFeesTerm] = useState('ALL');
    const [attDate, setAttDate] = useState(new Date().toISOString().split('T')[0]);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

    // Memoize fetch function to prevent recreation on every render
    const fetchDashboardData = useCallback(async (forceRefresh = false) => {
        if (!adminID || !currentUser?.token) {
            return;
        }

        setIsRefreshing(true);

        const statsUrl = forceRefresh
            ? `${API_URL}/admin/stats?refresh=true`
            : `${API_URL}/admin/stats`;

        const params = new URLSearchParams();
        if (chartStart) params.set('start', chartStart);
        if (chartEnd) params.set('end', chartEnd);
        if (feesTerm && feesTerm !== 'ALL') params.set('term', feesTerm);
        if (attDate) params.set('date', attDate);
        if (!chartStart && !chartEnd) params.set('days', '30');

        const query = params.toString();
        const chartsUrl = query
            ? `${API_URL}/admin/charts?${query}`
            : `${API_URL}/admin/charts`;

        const headers = { Authorization: `Bearer ${currentUser.token}` };

        try {
            const [statsResult, chartsResult] = await Promise.allSettled([
                axios.get(statsUrl, { headers }),
                axios.get(chartsUrl, { headers })
            ]);

            if (statsResult.status === 'fulfilled') {
                if (statsResult.value.data?.success) {
                    setDashboardStats(statsResult.value.data.stats);
                } else {
                    console.error('Stats API error:', statsResult.value.data?.message || 'Unknown error');
                }
            } else {
                console.error('Stats API failed:', statsResult.reason?.response?.data?.message || statsResult.reason?.message);
            }

            if (chartsResult.status === 'fulfilled') {
                if (chartsResult.value.data?.success) {
                    setDashboardCharts(chartsResult.value.data.charts);
                } else {
                    console.error('Charts API error:', chartsResult.value.data?.message || 'Unknown error');
                }
            } else {
                console.error('Charts API failed:', chartsResult.reason?.response?.data?.message || chartsResult.reason?.message);
            }
        } catch (error) {
            console.error('Dashboard data fetch failed:', error.message);
        } finally {
            setIsRefreshing(false);
        }
    }, [adminID, currentUser?.token, chartStart, chartEnd, feesTerm, attDate, API_URL]);

    // Single initial load effect - runs only once
    useEffect(() => {
        if (hasFetchedInitialData.current || !adminID) return;
        
        hasFetchedInitialData.current = true;
        
        // Dispatch Redux actions
        dispatch(getAllAcademicYears());
        
        // Fetch dashboard data
        fetchDashboardData(true);
    }, [adminID, dispatch, fetchDashboardData]);

    // Separate effect for filter changes - debounced
    useEffect(() => {
        if (!hasFetchedInitialData.current) return;
        
        const timer = setTimeout(() => {
            fetchDashboardData(false);
        }, 500); // 500ms debounce
        
        return () => clearTimeout(timer);
    }, [chartStart, chartEnd, feesTerm, attDate, fetchDashboardData]);

    // Memoize computed statistics
    const numberOfStudents = useMemo(
        () => normalizeCount(dashboardStats?.total_students),
        [dashboardStats?.total_students]
    );

    const numberOfClasses = useMemo(
        () => normalizeCount(dashboardStats?.total_classes),
        [dashboardStats?.total_classes]
    );

    const numberOfTeachers = useMemo(
        () => normalizeCount(dashboardStats?.total_teachers),
        [dashboardStats?.total_teachers]
    );

    // Memoize academic years
    const academicYears = useMemo(() => 
        Array.isArray(academicYearData) ? academicYearData : [],
        [academicYearData]
    );
    
    // Memoize current year lookup
    const currentYear = useMemo(() => {
        let year = academicYears.find(y => y.is_current == 1 || y.is_current === true);
        
        if (!year && academicYears.length === 0) {
            try {
                const storedYear = localStorage.getItem('currentAcademicYear');
                if (storedYear) year = JSON.parse(storedYear);
            } catch (error) {
                console.error('Error reading from localStorage:', error);
            }
        }
        
        return year;
    }, [academicYears]);
    
    // Memoize total subjects calculation
    const totalSubjects = useMemo(
        () => normalizeCount(dashboardStats?.total_subjects),
        [dashboardStats?.total_subjects]
    );

    // Memoize handlers
    const handleNavigate = useCallback((route) => {
        navigate(route);
    }, [navigate]);

    const handleRefresh = useCallback(() => {
        fetchDashboardData(true);
    }, [fetchDashboardData]);

    const handleLogout = useCallback(() => {
        localStorage.clear();
        sessionStorage.clear();
        window.location.href = '/';
    }, []);

    const handleResetFilters = useCallback(() => {
        setChartStart('');
        setChartEnd('');
        setFeesTerm('ALL');
        setAttDate(new Date().toISOString().split('T')[0]);
    }, []);

    // Memoize stats array
    const stats = useMemo(() => [
        {
            title: 'Total Students',
            value: numberOfStudents,
            icon: MdSchool,
            image: Students,
            color: 'bg-blue-600',
            lightColor: 'bg-blue-50',
            textColor: 'text-blue-600',
            route: '/Admin/students-management',
            action: () => handleNavigate('/Admin/students-management'),
            description: 'Enrolled students'
        },
        {
            title: 'Total Classes',
            value: numberOfClasses,
            icon: MdClass,
            image: Classes,
            color: 'bg-purple-600',
            lightColor: 'bg-purple-50',
            textColor: 'text-purple-600',
            route: '/Admin/classes-management',
            action: () => handleNavigate('/Admin/classes-management'),
            description: 'Active classes'
        },
        {
            title: 'Total Teachers',
            value: numberOfTeachers,
            icon: MdPeople,
            image: Teachers,
            color: 'bg-green-600',
            lightColor: 'bg-green-50',
            textColor: 'text-green-600',
            route: '/Admin/teachers-management',
            action: () => handleNavigate('/Admin/teachers-management'),
            description: 'Teaching staff'
        },
        {
            title: 'Total Subjects',
            value: totalSubjects,
            icon: MdPeople,
            image: Fees,
            color: 'bg-orange-600',
            lightColor: 'bg-orange-50',
            textColor: 'text-orange-600',
            route: '/Admin/subjects-management',
            action: () => handleNavigate('/Admin/subjects-management'),
            description: 'Available subjects'
        }
    ], [numberOfStudents, numberOfClasses, numberOfTeachers, totalSubjects, handleNavigate]);

    // Memoize quick actions
    const quickActions = useMemo(() => [
        { title: 'Student Management', route: '/Admin/students-management', icon: MdSchool, color: 'bg-blue-600', action: () => handleNavigate('/Admin/students-management') },
        { title: 'Class Management', route: '/Admin/classes-management', icon: MdClass, color: 'bg-purple-600', action: () => handleNavigate('/Admin/classes-management') },
        { title: 'Teacher Management', route: '/Admin/teachers-management', icon: MdPeople, color: 'bg-green-600', action: () => handleNavigate('/Admin/teachers-management') },
        { title: 'Post Notice', route: '/Admin/addnotice', icon: MdInfo, color: 'bg-red-600', action: () => handleNavigate('/Admin/addnotice') }
    ], [handleNavigate]);

    return (
        <div className="container max-w-7xl mx-auto px-4 space-y-6">
            {/* Token Warning Banner */}
            {!tokenHasAdminId && (
                <Card className="bg-red-50 border-red-300">
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <MdWarning className="h-8 w-8 text-red-600 flex-shrink-0" />
                            <div className="flex-1">
                                <h3 className="font-semibold text-red-900 text-lg">Authentication Update Required</h3>
                                <p className="text-sm text-red-700 mt-1">
                                    Your login token needs to be updated. Please log out and log back in to see correct dashboard statistics.
                                </p>
                                <p className="text-xs text-red-600 mt-2">
                                    <strong>Why?</strong> The system has been updated to improve data security. A fresh login will generate a new authentication token with the required information.
                                </p>
                            </div>
                            <Button
                                onClick={handleLogout}
                                className="bg-red-600 hover:bg-red-700 flex-shrink-0"
                            >
                                Log Out Now
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Welcome Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">
                        Welcome back, {currentUser?.name || 'Admin'}
                    </h1>
                    <p className="text-gray-600 mt-1">
                        Here's what's happening in your school today
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button
                        onClick={handleRefresh}
                        variant="outline"
                        disabled={isRefreshing}
                        className="hidden md:flex"
                    >
                        <MdTrendingUp className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                        {isRefreshing ? 'Refreshing...' : 'Refresh Data'}
                    </Button>
                    <Button
                        onClick={() => setShowGuide(prev => !prev)}
                        variant="outline"
                        className="hidden md:flex"
                    >
                        <MdInfo className="mr-2 h-4 w-4" />
                        {showGuide ? 'Hide Guide' : 'Show Guide'}
                    </Button>
                </div>
            </div>

            {/* Current Academic Year */}
            {!currentYear && academicYears.length === 0 ? (
                <Card className="bg-gray-50 border-gray-200">
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-center">
                            <p className="text-gray-600">Loading academic year data...</p>
                        </div>
                    </CardContent>
                </Card>
            ) : currentYear ? (
                <Card className="bg-gradient-to-r from-purple-600 to-blue-600 text-white border-0">
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                    <MdCalendarToday className="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <p className="text-purple-100 text-sm">Current Academic Year</p>
                                    <h2 className="text-2xl font-bold">{currentYear.year_name}</h2>
                                    <p className="text-purple-100 text-sm">
                                        {new Date(currentYear.start_date).toLocaleDateString()} - {new Date(currentYear.end_date).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>
                            <Button
                                variant="secondary"
                                onClick={() => handleNavigate('/Admin/academic-years')}
                            >
                                Manage
                                <MdArrowForward className="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            ) : (
                <Card className="bg-yellow-50 border-yellow-200">
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <MdInfo className="h-6 w-6 text-yellow-600" />
                                <div>
                                    <h3 className="font-semibold text-yellow-900">No Academic Year Set</h3>
                                    <p className="text-sm text-yellow-700">Please set up an academic year to get started</p>
                                </div>
                            </div>
                            <Button
                                onClick={() => handleNavigate('/Admin/academic-years')}
                                className="bg-yellow-600 hover:bg-yellow-700"
                            >
                                Set Up Now
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* System Guide (Collapsible) */}
            {showGuide && (
                <div className="animate-in slide-in-from-top">
                    <SystemGuide />
                </div>
            )}

            {/* Statistics Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {stats.map((stat, index) => {
                    const Icon = stat.icon;
                    const statValue = normalizeCount(stat.value);
                    return (
                        <Card
                            key={index}
                            className="overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
                            onClick={stat.action}
                        >
                            <CardContent className="p-0">
                                <div className={`${stat.lightColor} p-4 flex justify-end`}>
                                    <img src={stat.image} alt={stat.title} className="w-20 h-20 object-contain opacity-80" />
                                </div>
                                <div className="p-6">
                                    <div className="flex items-center justify-between mb-2">
                                        <p className="text-gray-600 text-sm">{stat.title}</p>
                                        {dashboardStats && (
                                            <Badge className="bg-green-100 text-green-800 text-xs">Live</Badge>
                                        )}
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-3xl sm:text-4xl font-bold">
                                            <CountUp
                                                key={`${stat.title}-${statValue}`}
                                                end={statValue}
                                                duration={1}
                                                separator=","
                                            >
                                                {({ countUpRef }) => (<span ref={countUpRef} />)}
                                            </CountUp>
                                        </span>
                                        <div className={`w-10 h-10 ${stat.color} rounded-full flex items-center justify-center group-hover:scale-110 transition-transform`}>
                                            <Icon className="h-5 w-5 text-white" />
                                        </div>
                                    </div>
                                    <p className="text-gray-500 text-xs mt-2">{stat.description}</p>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            {/* Quick Actions */}
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                    <CardDescription>Common tasks to get you started</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {quickActions.map((action, index) => {
                            const Icon = action.icon;
                            return (
                                <Button
                                    key={index}
                                    variant="outline"
                                    className="h-24 flex flex-col items-center justify-center gap-2 hover:border-purple-500 hover:bg-purple-50"
                                    onClick={action.action}
                                >
                                    <div className={`w-10 h-10 ${action.color} rounded-full flex items-center justify-center`}>
                                        <Icon className="h-5 w-5 text-white" />
                                    </div>
                                    <span className="text-sm font-medium">{action.title}</span>
                                </Button>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            {/* Recent Notices */}
            <Card>
                <CardHeader>
                    <CardTitle>Recent Notices & Announcements</CardTitle>
                    <CardDescription>Stay updated with latest school announcements</CardDescription>
                </CardHeader>
                <CardContent>
                    <SeeNotice />
                </CardContent>
            </Card>

            {/* Enhanced Analytics Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {/* Attendance Rate */}
                <Card className="hover:shadow-lg transition-shadow">
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <MdCheckCircle className="h-6 w-6 text-green-600" />
                            </div>
                            <div className="flex-1">
                                <p className="text-sm text-gray-600">Attendance Today</p>
                                <p className="text-2xl font-bold text-gray-900">{dashboardStats?.attendance?.attendance_rate ?? 0}%</p>
                            </div>
                        </div>
                        <div className="mt-3 pt-3 border-t border-gray-100">
                            <div className="flex justify-between text-xs text-gray-600">
                                <span className="flex items-center gap-1">
                                    <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                                    Present: {dashboardStats?.attendance?.present ?? 0}
                                </span>
                                <span className="flex items-center gap-1">
                                    <div className="w-2 h-2 bg-red-500 rounded-full"></div>
                                    Absent: {dashboardStats?.attendance?.absent ?? 0}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Students Marked */}
                <Card className="hover:shadow-lg transition-shadow">
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <MdAssignment className="h-6 w-6 text-blue-600" />
                            </div>
                            <div className="flex-1">
                                <p className="text-sm text-gray-600">Students Marked</p>
                                <p className="text-2xl font-bold text-gray-900">{dashboardStats?.attendance?.distinct_students_marked ?? 0}</p>
                            </div>
                        </div>
                        <div className="mt-3 pt-3 border-t border-gray-100">
                            <p className="text-xs text-gray-500">Total students: {numberOfStudents}</p>
                        </div>
                    </CardContent>
                </Card>

                {/* Academic Years */}
                <Card className="hover:shadow-lg transition-shadow">
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <MdCalendarToday className="h-6 w-6 text-purple-600" />
                            </div>
                            <div className="flex-1">
                                <p className="text-sm text-gray-600">Academic Years</p>
                                <p className="text-2xl font-bold text-gray-900">{academicYears.length}</p>
                            </div>
                        </div>
                        <div className="mt-3 pt-3 border-t border-gray-100">
                            <p className="text-xs text-gray-500">Current: {currentYear?.year_name || 'Not set'}</p>
                        </div>
                    </CardContent>
                </Card>

                {/* System Status */}
                <Card className="hover:shadow-lg transition-shadow">
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <MdTrendingUp className="h-6 w-6 text-green-600" />
                            </div>
                            <div className="flex-1">
                                <p className="text-sm text-gray-600">System Health</p>
                                <p className="text-2xl font-bold text-green-600">Active</p>
                            </div>
                        </div>
                        <div className="mt-3 pt-3 border-t border-gray-100">
                            <p className="text-xs text-gray-500">All systems operational</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Financial Overview */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <MdAttachMoney className="h-5 w-5 text-green-600" />
                        Financial Overview
                    </CardTitle>
                    <CardDescription>Revenue and payment statistics</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-green-50 p-4 rounded-lg border border-green-100">
                            <div className="flex items-center justify-between mb-2">
                                <p className="text-sm text-green-800 font-medium">Total Revenue</p>
                                <MdAttachMoney className="h-5 w-5 text-green-600" />
                            </div>
                            <p className="text-2xl font-bold text-green-900">
                                SLE {(dashboardStats?.fees?.total_collected ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </p>
                            <p className="text-xs text-green-600 mt-1">All time collections</p>
                        </div>

                        <div className="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                            <div className="flex items-center justify-between mb-2">
                                <p className="text-sm text-yellow-800 font-medium">Pending Fees</p>
                                <MdWarning className="h-5 w-5 text-yellow-600" />
                            </div>
                            <p className="text-2xl font-bold text-yellow-900">
                                SLE {(dashboardStats?.fees?.total_pending ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </p>
                            <p className="text-xs text-yellow-600 mt-1">Outstanding payments</p>
                        </div>

                        <div className="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <div className="flex items-center justify-between mb-2">
                                <p className="text-sm text-blue-800 font-medium">Collection Rate</p>
                                <MdTrendingUp className="h-5 w-5 text-blue-600" />
                            </div>
                            <p className="text-2xl font-bold text-blue-900">
                                {(dashboardStats?.fees?.collection_rate ?? 0).toFixed(2)}%
                            </p>
                            <p className="text-xs text-blue-600 mt-1">Payment completion</p>
                        </div>

                        <div className="bg-purple-50 p-4 rounded-lg border border-purple-100">
                            <div className="flex items-center justify-between mb-2">
                                <p className="text-sm text-purple-800 font-medium">This Month</p>
                                <MdCalendarToday className="h-5 w-5 text-purple-600" />
                            </div>
                            <p className="text-2xl font-bold text-purple-900">
                                SLE {(dashboardStats?.fees?.this_month ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </p>
                            <p className="text-xs text-purple-600 mt-1">Current month revenue</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Academic Performance Metrics */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card className="hover:shadow-lg transition-shadow">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base flex items-center gap-2">
                            <MdBook className="h-5 w-5 text-indigo-600" />
                            Exam Results
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div>
                                <div className="flex justify-between items-center mb-1">
                                    <span className="text-sm text-gray-600">Published</span>
                                    <span className="font-bold text-green-600">{dashboardStats?.results?.published ?? 0}</span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2">
                                    <div className="bg-green-600 h-2 rounded-full" style={{width: `${Math.min(((dashboardStats?.results?.published ?? 0) / Math.max(dashboardStats?.results?.total ?? 1, 1)) * 100, 100)}%`}}></div>
                                </div>
                            </div>
                            <div>
                                <div className="flex justify-between items-center mb-1">
                                    <span className="text-sm text-gray-600">Pending</span>
                                    <span className="font-bold text-yellow-600">{dashboardStats?.results?.pending ?? 0}</span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2">
                                    <div className="bg-yellow-600 h-2 rounded-full" style={{width: `${Math.min(((dashboardStats?.results?.pending ?? 0) / Math.max(dashboardStats?.results?.total ?? 1, 1)) * 100, 100)}%`}}></div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base flex items-center gap-2">
                            <MdEventNote className="h-5 w-5 text-pink-600" />
                            Notices & Alerts
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex items-center justify-between p-2 bg-blue-50 rounded">
                                <span className="text-sm text-gray-700">Active Notices</span>
                                <Badge className="bg-blue-600">{dashboardStats?.notices?.active ?? 0}</Badge>
                            </div>
                            <div className="flex items-center justify-between p-2 bg-purple-50 rounded">
                                <span className="text-sm text-gray-700">Recent Alerts</span>
                                <Badge className="bg-purple-600">{dashboardStats?.notices?.recent ?? 0}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base flex items-center gap-2">
                            <MdNotifications className="h-5 w-5 text-red-600" />
                            Complaints
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex items-center justify-between p-2 bg-red-50 rounded">
                                <span className="text-sm text-gray-700">Pending</span>
                                <Badge className="bg-red-600">{dashboardStats?.complaints?.pending ?? 0}</Badge>
                            </div>
                            <div className="flex items-center justify-between p-2 bg-green-50 rounded">
                                <span className="text-sm text-gray-700">Resolved</span>
                                <Badge className="bg-green-600">{dashboardStats?.complaints?.resolved ?? 0}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Academic Year Selector */}
            <AcademicYearSelector />

            {/* Quick Insights - Key Metrics at a Glance */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <MdTrendingUp className="h-5 w-5 text-blue-600" />
                        Quick Insights
                    </CardTitle>
                    <CardDescription>Key performance indicators at a glance</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div className="text-center p-3 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                            <p className="text-xs text-blue-600 mb-1">Student-Teacher Ratio</p>
                            <p className="text-xl font-bold text-blue-900">
                                {numberOfTeachers > 0 ? Math.round(numberOfStudents / numberOfTeachers) : 0}:1
                            </p>
                        </div>
                        <div className="text-center p-3 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg border border-purple-200">
                            <p className="text-xs text-purple-600 mb-1">Avg Class Size</p>
                            <p className="text-xl font-bold text-purple-900">
                                {numberOfClasses > 0 ? Math.round(numberOfStudents / numberOfClasses) : 0}
                            </p>
                        </div>
                        <div className="text-center p-3 bg-gradient-to-br from-green-50 to-green-100 rounded-lg border border-green-200">
                            <p className="text-xs text-green-600 mb-1">Subjects/Class</p>
                            <p className="text-xl font-bold text-green-900">
                                {numberOfClasses > 0 ? Math.round(totalSubjects / numberOfClasses) : 0}
                            </p>
                        </div>
                        <div className="text-center p-3 bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg border border-orange-200">
                            <p className="text-xs text-orange-600 mb-1">Active Programs</p>
                            <p className="text-xl font-bold text-orange-900">{numberOfClasses}</p>
                        </div>
                        <div className="text-center p-3 bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg border border-pink-200">
                            <p className="text-xs text-pink-600 mb-1">Staff Strength</p>
                            <p className="text-xl font-bold text-pink-900">{numberOfTeachers}</p>
                        </div>
                        <div className="text-center p-3 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg border border-indigo-200">
                            <p className="text-xs text-indigo-600 mb-1">Total Courses</p>
                            <p className="text-xl font-bold text-indigo-900">{totalSubjects}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Chart Filters */}
            <Card>
              <CardContent className="pt-6">
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                  <div>
                    <Label className="text-sm">Start Date</Label>
                    <Input type="date" value={chartStart} onChange={e => setChartStart(e.target.value)} />
                  </div>
                  <div>
                    <Label className="text-sm">End Date</Label>
                    <Input type="date" value={chartEnd} onChange={e => setChartEnd(e.target.value)} />
                  </div>
                  <div>
                    <Label className="text-sm">Fees Term</Label>
                    <Select value={feesTerm} onValueChange={setFeesTerm}>
                      <SelectTrigger>
                        <SelectValue placeholder="All Terms" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="ALL">All Terms</SelectItem>
                        <SelectItem value="1st Term">1st Term</SelectItem>
                        <SelectItem value="2nd Term">2nd Term</SelectItem>
                        <SelectItem value="Full Year">Full Year</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <Label className="text-sm">Attendance Date</Label>
                    <Input type="date" value={attDate} onChange={e => setAttDate(e.target.value)} />
                  </div>
                  <div>
                    <Button variant="outline" onClick={handleResetFilters}>Reset</Button>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Charts */}
            {dashboardCharts && (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Attendance Trend (Last 30 days)</CardTitle>
                    <CardDescription>Daily attendance status counts</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="h-64">
                      {dashboardCharts.attendance_trend && dashboardCharts.attendance_trend.length > 0 ? (
                        <ResponsiveContainer width="100%" height={250} minHeight={250}>
                          <LineChart data={dashboardCharts.attendance_trend}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="date" />
                            <YAxis />
                            <Tooltip />
                            <Legend />
                            <Line type="monotone" dataKey="present" stroke="#16a34a" name="Present" />
                            <Line type="monotone" dataKey="absent" stroke="#dc2626" name="Absent" />
                            <Line type="monotone" dataKey="late" stroke="#f59e0b" name="Late" />
                            <Line type="monotone" dataKey="excused" stroke="#3b82f6" name="Excused" />
                          </LineChart>
                        </ResponsiveContainer>
                      ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                          No attendance data available for the selected period
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader>
                    <CardTitle>Students per Class</CardTitle>
                    <CardDescription>Current academic year enrollment by class</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="h-64">
                      {dashboardCharts.class_student_counts && dashboardCharts.class_student_counts.length > 0 ? (
                        <ResponsiveContainer width="100%" height={250} minHeight={250}>
                          <BarChart data={dashboardCharts.class_student_counts}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="class_name" interval={0} angle={-30} textAnchor="end" height={80} />
                            <YAxis />
                            <Tooltip />
                            <Bar dataKey="student_count" fill="#7c3aed" name="Students" />
                          </BarChart>
                        </ResponsiveContainer>
                      ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                          No class enrollment data available
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </div>
            )}

            {dashboardCharts && (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Fees Trend (Last 30 days)</CardTitle>
                    <CardDescription>Total amount received per day</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="h-64">
                      {dashboardCharts.fees_trend && dashboardCharts.fees_trend.length > 0 ? (
                        <ResponsiveContainer width="100%" height={250} minHeight={250}>
                          <LineChart data={dashboardCharts.fees_trend}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="date" />
                            <YAxis />
                            <Tooltip />
                            <Legend />
                            <Line type="monotone" dataKey="amount" stroke="#10b981" name="Amount" />
                          </LineChart>
                        </ResponsiveContainer>
                      ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                          No fee payment data available for this period
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader>
                    <CardTitle>Results Publications</CardTitle>
                    <CardDescription>Published results count per day</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="h-64">
                      {dashboardCharts.results_publications && dashboardCharts.results_publications.length > 0 ? (
                        <ResponsiveContainer width="100%" height={250} minHeight={250}>
                          <BarChart data={dashboardCharts.results_publications}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="date" />
                            <YAxis />
                            <Tooltip />
                            <Bar dataKey="count" fill="#ef4444" name="Publications" />
                          </BarChart>
                        </ResponsiveContainer>
                      ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                          No result publications in this period
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </div>
            )}

            {dashboardCharts && (
              <div className="grid grid-cols-1 gap-6 mt-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Teacher Load (Top 10)</CardTitle>
                    <CardDescription>Subjects assigned per teacher</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="h-64">
                      {dashboardCharts.teacher_load && dashboardCharts.teacher_load.length > 0 ? (
                        <ResponsiveContainer width="100%" height={250} minHeight={250}>
                          <BarChart data={dashboardCharts.teacher_load}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="teacher_name" interval={0} angle={-20} textAnchor="end" height={80} />
                            <YAxis />
                            <Tooltip />
                            <Bar dataKey="subjects" fill="#3b82f6" name="Subjects" />
                          </BarChart>
                        </ResponsiveContainer>
                      ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                          No teacher assignments available
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </div>
            )}

            {dashboardCharts && (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Fees by Term</CardTitle>
                    <CardDescription>Distribution of fees received</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="h-64">
                      {dashboardCharts.fees_by_term && dashboardCharts.fees_by_term.length > 0 ? (
                        <ResponsiveContainer width="100%" height={250} minHeight={250}>
                          <PieChart>
                            <Pie data={dashboardCharts.fees_by_term}
                                 dataKey="amount"
                                 nameKey="term"
                                 cx="50%" cy="50%" outerRadius={80} label>
                              {dashboardCharts.fees_by_term.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={["#7c3aed", "#16a34a", "#f59e0b"][index % 3]} />
                              ))}
                            </Pie>
                            <Tooltip />
                          </PieChart>
                        </ResponsiveContainer>
                      ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                          No fee payments by term available
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader>
                    <CardTitle>Attendance by Class (Today)</CardTitle>
                    <CardDescription>Present rate per class</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="h-64">
                      {dashboardCharts.attendance_by_class && dashboardCharts.attendance_by_class.length > 0 ? (
                        <ResponsiveContainer width="100%" height={250} minHeight={250}>
                          <BarChart data={dashboardCharts.attendance_by_class}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="class_name" interval={0} angle={-20} textAnchor="end" height={80} />
                            <YAxis />
                            <Tooltip />
                            <Bar dataKey="rate" fill="#10b981" name="Present %" />
                          </BarChart>
                        </ResponsiveContainer>
                      ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                          No attendance data for today
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </div>
            )}

            {dashboardCharts && (
              <div className="grid grid-cols-1 gap-6 mt-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Average Grades Trend</CardTitle>
                    <CardDescription>Average scores across exams</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="h-64">
                      {dashboardCharts.avg_grades_trend && dashboardCharts.avg_grades_trend.length > 0 ? (
                        <ResponsiveContainer width="100%" height={250} minHeight={250}>
                          <LineChart data={dashboardCharts.avg_grades_trend}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="date" />
                            <YAxis domain={[0, 100]} />
                            <Tooltip />
                            <Legend />
                            <Line type="monotone" dataKey="avg_score" stroke="#8b5cf6" name="Average Score" />
                          </LineChart>
                        </ResponsiveContainer>
                      ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                          No exam results available for this period
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </div>
            )}
        </div>
    );
};

export default AdminHomePage;
