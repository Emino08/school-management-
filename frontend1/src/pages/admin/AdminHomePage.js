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
import { useEffect, useState } from 'react';
import { getAllSclasses } from '../../redux/sclassRelated/sclassHandle';
import axios from 'axios';
import { getAllStudents } from '../../redux/studentRelated/studentHandle';
import { getAllTeachers } from '../../redux/teacherRelated/teacherHandle';
import { getAllAcademicYears } from '../../redux/academicYearRelated/academicYearHandle';
import {
  MdSchool,
  MdClass,
  MdPeople,
  MdAttachMoney,
  MdCalendarToday,
  MdTrendingUp,
  MdInfo,
  MdArrowForward
} from 'react-icons/md';
import { useNavigate } from 'react-router-dom';

const AdminHomePage = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [showGuide, setShowGuide] = useState(false);

    const { studentsList } = useSelector((state) => state.student);
    const { sclassesList } = useSelector((state) => state.sclass);
    const { teachersList } = useSelector((state) => state.teacher);
    const { academicYearData, academicYearLoading } = useSelector((state) => state.academicYear);
    const { currentUser } = useSelector(state => state.user);

    const adminID = currentUser?._id || currentUser?.id;

    const [dashboardStats, setDashboardStats] = useState(null);
    const [dashboardCharts, setDashboardCharts] = useState(null);
    const [chartStart, setChartStart] = useState('');
    const [chartEnd, setChartEnd] = useState('');
    const [feesTerm, setFeesTerm] = useState('ALL');
    const [attDate, setAttDate] = useState(new Date().toISOString().split('T')[0]);
    const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

    useEffect(() => {
        console.log('=== INITIAL LOAD ===');
        console.log('Current User:', currentUser);
        console.log('Admin ID:', adminID);
        console.log('academicYearLoading:', academicYearLoading);
        console.log('academicYearData:', academicYearData);
        if (adminID) {
            console.log('Dispatching getAllAcademicYears...');
            dispatch(getAllStudents(adminID));
            dispatch(getAllSclasses(adminID, "Sclass"));
            dispatch(getAllTeachers(adminID));
            dispatch(getAllAcademicYears());
            // Fetch backend dashboard stats including attendance
            (async () => {
              try {
                const res = await axios.get(`${API_URL}/admin/stats`, { headers: { Authorization: `Bearer ${currentUser?.token}` } });
                if (res.data?.success) setDashboardStats(res.data.stats);
              } catch (e) {
                console.warn('Failed to fetch admin stats', e);
              }
              try {
                const params = new URLSearchParams();
                if (chartStart) params.set('start', chartStart);
                if (chartEnd) params.set('end', chartEnd);
                if (feesTerm && feesTerm !== 'ALL') params.set('term', feesTerm);
                if (attDate) params.set('date', attDate);
                if (!chartStart && !chartEnd) params.set('days', '30');
                const charts = await axios.get(`${API_URL}/admin/charts?${params.toString()}`, { headers: { Authorization: `Bearer ${currentUser?.token}` } });
                if (charts.data?.success) setDashboardCharts(charts.data.charts);
              } catch (e) {
                console.warn('Failed to fetch admin charts', e);
              }
            })();
        } else {
            console.warn('No admin ID found - user might not be logged in');
        }
    }, [adminID, dispatch, chartStart, chartEnd, feesTerm, attDate]);

    const numberOfStudents = dashboardStats?.total_students ?? (Array.isArray(studentsList) ? studentsList.length : 0);
    const numberOfClasses = dashboardStats?.total_classes ?? (Array.isArray(sclassesList) ? sclassesList.length : 0);
    const numberOfTeachers = dashboardStats?.total_teachers ?? (Array.isArray(teachersList) ? teachersList.length : 0);

    // Get academic years from Redux or empty array
    const academicYears = Array.isArray(academicYearData) ? academicYearData : [];
    
    console.log('=== PROCESSING ACADEMIC YEARS ===');
    console.log('Raw academicYearData:', academicYearData);
    console.log('Is Array?', Array.isArray(academicYearData));
    console.log('academicYears length:', academicYears.length);
    
    // Check for current year - use loose equality to match AcademicYearSelector logic
    let currentYear = academicYears.find(year => {
        console.log('Checking year:', year);
        // Use loose equality like in AcademicYearSelector (line 32)
        const isCurrent = year.is_current == 1 || year.is_current === true;
        console.log(`Year ${year.year_name}: is_current=${year.is_current}, matches=${isCurrent}`);
        return isCurrent;
    });
    
    // Fallback: If no current year found in Redux, try localStorage
    if (!currentYear && academicYears.length === 0) {
        console.log('No data in Redux, checking localStorage...');
        try {
            const storedYear = localStorage.getItem('currentAcademicYear');
            if (storedYear) {
                currentYear = JSON.parse(storedYear);
                console.log('Found current year in localStorage:', currentYear);
            }
        } catch (error) {
            console.error('Error reading from localStorage:', error);
        }
    }
    
    console.log('Final currentYear:', currentYear);

    // Debug logging
    useEffect(() => {
        console.log('=== ACADEMIC YEAR DEBUG ===');
        console.log('academicYearData:', academicYearData);
        console.log('academicYears array:', academicYears);
        console.log('currentYear found:', currentYear);
        if (academicYears.length > 0) {
            academicYears.forEach((year, index) => {
                const isCurrentNum = Number(year.is_current);
                console.log(`Year ${index}:`, {
                    id: year.id,
                    name: year.year_name,
                    is_current: year.is_current,
                    type: typeof year.is_current,
                    is_current_as_number: isCurrentNum,
                    will_be_selected: isCurrentNum === 1
                });
            });
        }
        console.log('=== END DEBUG ===');
    }, [academicYearData, academicYears, currentYear]);

    // Calculate additional statistics
    const totalSubjects = dashboardStats?.total_subjects ?? (
        Array.isArray(sclassesList)
            ? sclassesList.reduce((sum, sclass) => sum + (sclass.subject_count || 0), 0)
            : 0
    );

    const stats = [
        {
            title: 'Total Students',
            value: numberOfStudents,
            icon: MdSchool,
            image: Students,
            color: 'bg-blue-600',
            lightColor: 'bg-blue-50',
            textColor: 'text-blue-600',
            route: '/Admin/students-management',
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
            description: 'Available subjects'
        }
    ];

    const quickActions = [
        { title: 'Student Management', route: '/Admin/students-management', icon: MdSchool, color: 'bg-blue-600' },
        { title: 'Class Management', route: '/Admin/classes-management', icon: MdClass, color: 'bg-purple-600' },
        { title: 'Teacher Management', route: '/Admin/teachers-management', icon: MdPeople, color: 'bg-green-600' },
        { title: 'Post Notice', route: '/Admin/addnotice', icon: MdInfo, color: 'bg-red-600' }
    ];

    return (
        <div className="container max-w-7xl mx-auto px-4 space-y-6">
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
                <Button
                    onClick={() => setShowGuide(!showGuide)}
                    variant="outline"
                    className="hidden md:flex"
                >
                    <MdInfo className="mr-2 h-4 w-4" />
                    {showGuide ? 'Hide Guide' : 'Show Guide'}
                </Button>
            </div>

            {/* Current Academic Year */}
            {academicYearLoading && !currentYear ? (
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
                                onClick={() => navigate('/Admin/academic-years')}
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
                                onClick={() => navigate('/Admin/academic-years')}
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
                    return (
                        <Card
                            key={index}
                            className="overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
                            onClick={() => navigate(stat.route)}
                        >
                            <CardContent className="p-0">
                                <div className={`${stat.lightColor} p-4 flex justify-end`}>
                                    <img src={stat.image} alt={stat.title} className="w-20 h-20 object-contain opacity-80" />
                                </div>
                                <div className="p-6">
                                    <p className="text-gray-600 text-sm mb-2">{stat.title}</p>
                                    <div className="flex items-center justify-between">`
                                        <span className="text-3xl sm:text-4xl font-bold"><CountUp end={Number(stat?.value ?? 0)} duration={1.2} separator="," preserveValue redraw>{({ countUpRef }) => (<span ref={countUpRef} />)}</CountUp></span>
                                        <div className={`w-10 h-10 ${stat.color} rounded-full flex items-center justify-center group-hover:scale-110 transition-transform`}>
                                            <Icon className="h-5 w-5 text-white" />
                                        </div>
                                    </div>
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
                                    onClick={() => navigate(action.route)}
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

            {/* System Status and Academic Year Selector */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <MdTrendingUp className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{numberOfClasses > 0 ? '100%' : '0%'}</p>
                                <p className="text-sm text-gray-600">System Setup</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <MdSchool className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">Active</p>
                                <p className="text-sm text-gray-600">System Status</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <MdCalendarToday className="h-5 w-5 text-purple-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{academicYears.length}</p>
                                <p className="text-sm text-gray-600">Academic Years</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <MdTrendingUp className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{dashboardStats?.attendance?.attendance_rate ?? 0}%</p>
                                <p className="text-sm text-gray-600">Attendance Rate Today</p>
                            </div>
                        </div>
                        <div className="mt-2 text-sm text-gray-600">
                          Present: {dashboardStats?.attendance?.present ?? 0} · Absent: {dashboardStats?.attendance?.absent ?? 0}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <MdInfo className="h-5 w-5 text-yellow-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{dashboardStats?.attendance?.distinct_students_marked ?? 0}</p>
                                <p className="text-sm text-gray-600">Students Marked Today</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Academic Year Selector */}
            <AcademicYearSelector />

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
                    <Button variant="outline" onClick={() => { setChartStart(''); setChartEnd(''); setFeesTerm('ALL'); setAttDate(new Date().toISOString().split('T')[0]); }}>Reset</Button>
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
                      <ResponsiveContainer width="100%" height="100%">
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
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={dashboardCharts.class_student_counts}>
                          <CartesianGrid strokeDasharray="3 3" />
                          <XAxis dataKey="class_name" interval={0} angle={-30} textAnchor="end" height={80} />
                          <YAxis />
                          <Tooltip />
                          <Bar dataKey="student_count" fill="#7c3aed" name="Students" />
                        </BarChart>
                      </ResponsiveContainer>
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
                      <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={dashboardCharts.fees_trend}>
                          <CartesianGrid strokeDasharray="3 3" />
                          <XAxis dataKey="date" />
                          <YAxis />
                          <Tooltip />
                          <Legend />
                          <Line type="monotone" dataKey="amount" stroke="#10b981" name="Amount" />
                        </LineChart>
                      </ResponsiveContainer>
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
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={dashboardCharts.results_publications}>
                          <CartesianGrid strokeDasharray="3 3" />
                          <XAxis dataKey="date" />
                          <YAxis />
                          <Tooltip />
                          <Bar dataKey="count" fill="#ef4444" name="Publications" />
                        </BarChart>
                      </ResponsiveContainer>
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
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={dashboardCharts.teacher_load}>
                          <CartesianGrid strokeDasharray="3 3" />
                          <XAxis dataKey="teacher_name" interval={0} angle={-20} textAnchor="end" height={80} />
                          <YAxis />
                          <Tooltip />
                          <Bar dataKey="subjects" fill="#3b82f6" name="Subjects" />
                        </BarChart>
                      </ResponsiveContainer>
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
                      <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                          <Pie data={dashboardCharts.fees_by_term}
                               dataKey="amount"
                               nameKey="term"
                               cx="50%" cy="50%" outerRadius={80} label>
                            {(dashboardCharts.fees_by_term || []).map((entry, index) => (
                              <Cell key={`cell-${index}`} fill={["#7c3aed", "#16a34a", "#f59e0b"][index % 3]} />
                            ))}
                          </Pie>
                          <Tooltip />
                        </PieChart>
                      </ResponsiveContainer>
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
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={dashboardCharts.attendance_by_class}>
                          <CartesianGrid strokeDasharray="3 3" />
                          <XAxis dataKey="class_name" interval={0} angle={-20} textAnchor="end" height={80} />
                          <YAxis />
                          <Tooltip />
                          <Bar dataKey="rate" fill="#10b981" name="Present %" />
                        </BarChart>
                      </ResponsiveContainer>
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
                      <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={dashboardCharts.avg_grades_trend}>
                          <CartesianGrid strokeDasharray="3 3" />
                          <XAxis dataKey="date" />
                          <YAxis domain={[0, 100]} />
                          <Tooltip />
                          <Legend />
                          <Line type="monotone" dataKey="avg_score" stroke="#8b5cf6" name="Average Score" />
                        </LineChart>
                      </ResponsiveContainer>
                    </div>
                  </CardContent>
                </Card>
              </div>
            )}
        </div>
    );
};

export default AdminHomePage;

