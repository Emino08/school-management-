import { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { toast } from 'sonner';
import {
  FiBarChart2,
  FiDownload,
  FiFilter,
  FiCalendar,
  FiUsers,
  FiDollarSign,
  FiClipboard,
  FiAward,
  FiTrendingUp,
  FiTrendingDown,
  FiRefreshCw,
} from 'react-icons/fi';

const ReportsAnalytics = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');
  const [academicYears, setAcademicYears] = useState([]);
  const [selectedYear, setSelectedYear] = useState('');
  const [selectedTerm, setSelectedTerm] = useState('all');
  const [dateRange, setDateRange] = useState({
    start: '',
    end: '',
  });

  const [overviewData, setOverviewData] = useState(null);
  const [academicData, setAcademicData] = useState(null);
  const [financialData, setFinancialData] = useState(null);
  const [attendanceData, setAttendanceData] = useState(null);

  // Use Vite env style consistently across app
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  useEffect(() => {
    fetchAcademicYears();
    fetchOverviewData();
  }, []);

  // When academic year changes, attempt to prefill date range from that year's metadata
  useEffect(() => {
    if (!selectedYear) return;
    const yearObj = academicYears.find((y) => y.id?.toString() === selectedYear);
    if (!yearObj) return;
    const start = yearObj.start_date || yearObj.start || yearObj.startDate;
    const end = yearObj.end_date || yearObj.end || yearObj.endDate;
    if (start && end) {
      const norm = (d) => (typeof d === 'string' ? d.split('T')[0] : d);
      setDateRange({ start: norm(start), end: norm(end) });
    }
  }, [selectedYear, academicYears]);

  const fetchAcademicYears = async () => {
    try {
      const response = await axios.get(`${API_URL}/academic-years`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (response.data.success) {
        const years = response.data.academicYears || response.data.academic_years || [];
        setAcademicYears(years);
        const current = years.find((y) => y.is_current);
        if (current) {
          setSelectedYear(current.id.toString());
        } else if (!selectedYear && years.length > 0) {
          // Fallback: select the first available year to avoid empty state
          setSelectedYear(years[0].id.toString());
        }
      }
    } catch (error) {
      console.error('Error fetching academic years:', error);
    }
  };

  const fetchOverviewData = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API_URL}/admin/reports/overview`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (response.data.success) {
        setOverviewData(response.data.data || response.data.stats || null);
      }
    } catch (error) {
      console.error('Error fetching overview:', error);
      toast.error('Failed to load overview data');
    } finally {
      setLoading(false);
    }
  };

  const fetchAcademicReport = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (selectedYear) params.append('academic_year_id', selectedYear);
      if (selectedTerm && selectedTerm !== 'all') params.append('term', selectedTerm);

      const response = await axios.get(
        `${API_URL}/admin/reports/academic?${params.toString()}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );
      if (response.data.success) {
        setAcademicData(response.data.data);
      }
    } catch (error) {
      console.error('Error fetching academic report:', error);
      toast.error('Failed to load academic report');
    } finally {
      setLoading(false);
    }
  };

  const fetchFinancialReport = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (selectedYear) params.append('academic_year_id', selectedYear);
      if (dateRange.start) params.append('start_date', dateRange.start);
      if (dateRange.end) params.append('end_date', dateRange.end);

      const response = await axios.get(
        `${API_URL}/admin/reports/financial?${params.toString()}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );
      if (response.data.success) {
        setFinancialData(response.data.data || response.data.overview || null);
      }
    } catch (error) {
      console.error('Error fetching financial report:', error);
      toast.error('Failed to load financial report');
    } finally {
      setLoading(false);
    }
  };

  const fetchAttendanceReport = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (selectedYear) params.append('academic_year_id', selectedYear);
      if (dateRange.start) params.append('start_date', dateRange.start);
      if (dateRange.end) params.append('end_date', dateRange.end);

      const response = await axios.get(
        `${API_URL}/admin/reports/attendance?${params.toString()}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );
      if (response.data.success) {
        setAttendanceData(response.data.data || response.data.summary || null);
      }
    } catch (error) {
      console.error('Error fetching attendance report:', error);
      toast.error('Failed to load attendance report');
    } finally {
      setLoading(false);
    }
  };

  const handleExportReport = async (reportType) => {
    try {
      toast.loading('Generating report...');
      const params = new URLSearchParams();
      if (selectedYear) params.append('academic_year_id', selectedYear);
      if (selectedTerm && selectedTerm !== 'all') params.append('term', selectedTerm);
      if (dateRange.start) params.append('start_date', dateRange.start);
      if (dateRange.end) params.append('end_date', dateRange.end);
      params.append('format', 'pdf');

      const response = await axios.get(
        `${API_URL}/admin/reports/${reportType}/export?${params.toString()}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
          responseType: 'blob',
        }
      );

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${reportType}-report-${new Date().toISOString()}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.dismiss();
      toast.success('Report exported successfully');
    } catch (error) {
      toast.dismiss();
      console.error('Error exporting report:', error);
      toast.error('Failed to export report');
    }
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
          <p className="text-gray-600 mt-1">Comprehensive reporting and data analysis</p>
        </div>
        <Button variant="outline" onClick={fetchOverviewData} disabled={loading}>
          <FiRefreshCw className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
          Refresh
        </Button>
      </div>

      {/* Filter Section */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FiFilter className="h-5 w-5" />
            Filters
          </CardTitle>
          <CardDescription>Select academic year, term, and date range for reports</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="space-y-2">
              <Label>Academic Year</Label>
              <Select value={selectedYear} onValueChange={setSelectedYear}>
                <SelectTrigger>
                  <SelectValue placeholder="Select year" />
                </SelectTrigger>
                <SelectContent>
                  {academicYears.map((year) => (
                    <SelectItem key={year.id} value={year.id.toString()}>
                      {year.year_name} {year.is_current && '(Current)'}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Term</Label>
              <Select value={selectedTerm} onValueChange={setSelectedTerm}>
                <SelectTrigger>
                  <SelectValue placeholder="All Terms" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Terms</SelectItem>
                  <SelectItem value="1">First Term</SelectItem>
                  <SelectItem value="2">Second Term</SelectItem>
                  <SelectItem value="3">Third Term</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Start Date</Label>
              <Input
                type="date"
                value={dateRange.start}
                onChange={(e) => setDateRange({ ...dateRange, start: e.target.value })}
              />
            </div>

            <div className="space-y-2">
              <Label>End Date</Label>
              <Input
                type="date"
                value={dateRange.end}
                onChange={(e) => setDateRange({ ...dateRange, end: e.target.value })}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Reports Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="overview">
            <FiBarChart2 className="mr-2 h-4 w-4" />
            Overview
          </TabsTrigger>
          <TabsTrigger value="academic" onClick={fetchAcademicReport}>
            <FiAward className="mr-2 h-4 w-4" />
            Academic
          </TabsTrigger>
          <TabsTrigger value="financial" onClick={fetchFinancialReport}>
            <FiDollarSign className="mr-2 h-4 w-4" />
            Financial
          </TabsTrigger>
          <TabsTrigger value="attendance" onClick={fetchAttendanceReport}>
            <FiClipboard className="mr-2 h-4 w-4" />
            Attendance
          </TabsTrigger>
        </TabsList>

        {/* Overview Tab */}
        <TabsContent value="overview" className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <FiUsers className="h-6 w-6 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{overviewData?.total_students || 0}</p>
                    <p className="text-sm text-gray-600">Total Students</p>
                  </div>
                </div>
                <div className="mt-4 flex items-center text-sm">
                  <FiTrendingUp className="h-4 w-4 text-green-600 mr-1" />
                  <span className="text-green-600">+{overviewData?.student_growth || 0}%</span>
                  <span className="text-gray-600 ml-2">vs last year</span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <FiDollarSign className="h-6 w-6 text-green-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">₦{(overviewData?.total_revenue || 0).toLocaleString()}</p>
                    <p className="text-sm text-gray-600">Total Revenue</p>
                  </div>
                </div>
                <div className="mt-4 flex items-center text-sm">
                  <FiTrendingUp className="h-4 w-4 text-green-600 mr-1" />
                  <span className="text-green-600">{overviewData?.collection_rate || 0}%</span>
                  <span className="text-gray-600 ml-2">collection rate</span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <FiClipboard className="h-6 w-6 text-purple-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{overviewData?.avg_attendance || 0}%</p>
                    <p className="text-sm text-gray-600">Avg Attendance</p>
                  </div>
                </div>
                <div className="mt-4 flex items-center text-sm">
                  <FiTrendingUp className="h-4 w-4 text-green-600 mr-1" />
                  <span className="text-green-600">Excellent</span>
                  <span className="text-gray-600 ml-2">performance</span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <FiAward className="h-6 w-6 text-yellow-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{overviewData?.pass_rate || 0}%</p>
                    <p className="text-sm text-gray-600">Pass Rate</p>
                  </div>
                </div>
                <div className="mt-4 flex items-center text-sm">
                  <FiTrendingUp className="h-4 w-4 text-green-600 mr-1" />
                  <span className="text-green-600">+{overviewData?.pass_rate_change || 0}%</span>
                  <span className="text-gray-600 ml-2">vs last term</span>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Charts */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Student Enrollment Trend</CardTitle>
                <CardDescription>Monthly enrollment over the past year</CardDescription>
              </CardHeader>
              <CardContent>
                {/* Placeholder - Replace with actual chart */}
                <div className="h-64 flex items-center justify-center bg-gray-50 rounded">
                  <p className="text-gray-500">Chart: Student Enrollment Trend</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Revenue Breakdown</CardTitle>
                <CardDescription>Revenue sources distribution</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="h-64 flex items-center justify-center bg-gray-50 rounded">
                  <p className="text-gray-500">Chart: Revenue Breakdown</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Class Performance</CardTitle>
                <CardDescription>Average scores by class</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="h-64 flex items-center justify-center bg-gray-50 rounded">
                  <p className="text-gray-500">Chart: Class Performance</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Attendance Overview</CardTitle>
                <CardDescription>Daily attendance rates</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="h-64 flex items-center justify-center bg-gray-50 rounded">
                  <p className="text-gray-500">Chart: Attendance Overview</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Academic Tab */}
        <TabsContent value="academic" className="space-y-4">
          <div className="flex justify-end">
            <Button onClick={() => handleExportReport('academic')}>
              <FiDownload className="mr-2 h-4 w-4" />
              Export Report
            </Button>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Academic Performance Report</CardTitle>
              <CardDescription>
                Detailed analysis of student performance and achievements
              </CardDescription>
            </CardHeader>
            <CardContent>
              {loading ? (
                <div className="flex items-center justify-center py-12">
                  <FiRefreshCw className="h-8 w-8 text-gray-400 animate-spin" />
                </div>
              ) : (
                <div className="space-y-6">
                  <div className="grid grid-cols-3 gap-4">
                    <div className="p-4 bg-green-50 rounded-lg">
                      <p className="text-sm text-gray-600">Students Passed</p>
                      <p className="text-2xl font-bold text-green-700">
                        {academicData?.passed || 0}
                      </p>
                    </div>
                    <div className="p-4 bg-red-50 rounded-lg">
                      <p className="text-sm text-gray-600">Students Failed</p>
                      <p className="text-2xl font-bold text-red-700">{academicData?.failed || 0}</p>
                    </div>
                    <div className="p-4 bg-blue-50 rounded-lg">
                      <p className="text-sm text-gray-600">Average Score</p>
                      <p className="text-2xl font-bold text-blue-700">
                        {academicData?.average_score || 0}%
                      </p>
                    </div>
                  </div>

                  <div className="h-80 flex items-center justify-center bg-gray-50 rounded">
                    <p className="text-gray-500">Academic Performance Charts</p>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Financial Tab */}
        <TabsContent value="financial" className="space-y-4">
          <div className="flex justify-end">
            <Button onClick={() => handleExportReport('financial')}>
              <FiDownload className="mr-2 h-4 w-4" />
              Export Report
            </Button>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Financial Report</CardTitle>
              <CardDescription>Revenue, expenses, and financial analysis</CardDescription>
            </CardHeader>
            <CardContent>
              {loading ? (
                <div className="flex items-center justify-center py-12">
                  <FiRefreshCw className="h-8 w-8 text-gray-400 animate-spin" />
                </div>
              ) : (
                <div className="space-y-6">
                  <div className="grid grid-cols-4 gap-4">
                    <div className="p-4 bg-green-50 rounded-lg">
                      <p className="text-sm text-gray-600">Total Revenue</p>
                      <p className="text-2xl font-bold text-green-700">
                        ₦{(financialData?.total_revenue || 0).toLocaleString()}
                      </p>
                    </div>
                    <div className="p-4 bg-yellow-50 rounded-lg">
                      <p className="text-sm text-gray-600">Pending Fees</p>
                      <p className="text-2xl font-bold text-yellow-700">
                        ₦{(financialData?.pending_fees || 0).toLocaleString()}
                      </p>
                    </div>
                    <div className="p-4 bg-blue-50 rounded-lg">
                      <p className="text-sm text-gray-600">Collection Rate</p>
                      <p className="text-2xl font-bold text-blue-700">
                        {financialData?.collection_rate || 0}%
                      </p>
                    </div>
                    <div className="p-4 bg-purple-50 rounded-lg">
                      <p className="text-sm text-gray-600">This Month</p>
                      <p className="text-2xl font-bold text-purple-700">
                        ₦{(financialData?.this_month || 0).toLocaleString()}
                      </p>
                    </div>
                  </div>

                  <div className="h-80 flex items-center justify-center bg-gray-50 rounded">
                    <p className="text-gray-500">Financial Charts</p>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Attendance Tab */}
        <TabsContent value="attendance" className="space-y-4">
          <div className="flex justify-end">
            <Button onClick={() => handleExportReport('attendance')}>
              <FiDownload className="mr-2 h-4 w-4" />
              Export Report
            </Button>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Attendance Report</CardTitle>
              <CardDescription>Student attendance analysis and trends</CardDescription>
            </CardHeader>
            <CardContent>
              {loading ? (
                <div className="flex items-center justify-center py-12">
                  <FiRefreshCw className="h-8 w-8 text-gray-400 animate-spin" />
                </div>
              ) : (
                <div className="space-y-6">
                  <div className="grid grid-cols-4 gap-4">
                    <div className="p-4 bg-green-50 rounded-lg">
                      <p className="text-sm text-gray-600">Present</p>
                      <p className="text-2xl font-bold text-green-700">
                        {attendanceData?.present || 0}
                      </p>
                    </div>
                    <div className="p-4 bg-red-50 rounded-lg">
                      <p className="text-sm text-gray-600">Absent</p>
                      <p className="text-2xl font-bold text-red-700">
                        {attendanceData?.absent || 0}
                      </p>
                    </div>
                    <div className="p-4 bg-yellow-50 rounded-lg">
                      <p className="text-sm text-gray-600">Late</p>
                      <p className="text-2xl font-bold text-yellow-700">
                        {attendanceData?.late || 0}
                      </p>
                    </div>
                    <div className="p-4 bg-blue-50 rounded-lg">
                      <p className="text-sm text-gray-600">Attendance Rate</p>
                      <p className="text-2xl font-bold text-blue-700">
                        {attendanceData?.rate || 0}%
                      </p>
                    </div>
                  </div>

                  <div className="h-80 flex items-center justify-center bg-gray-50 rounded">
                    <p className="text-gray-500">Attendance Charts</p>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default ReportsAnalytics;
