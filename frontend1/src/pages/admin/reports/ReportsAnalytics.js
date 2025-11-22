import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { FiBarChart2, FiUsers, FiDollarSign, FiAlertCircle, FiTrendingUp, FiDownload, FiActivity } from 'react-icons/fi';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';
import PerformanceCharts from './PerformanceCharts';
import AttendanceReports from './AttendanceReports';
import FinancialReports from './FinancialReports';
import BehaviorReports from './BehaviorReports';

const ReportsAnalytics = () => {
  const [dashboardStats, setDashboardStats] = useState({
    total_students: 0,
    total_teachers: 0,
    total_classes: 0,
    recent_payments: 0,
    average_attendance: 0,
    pending_complaints: 0,
    active_students: 0,
    inactive_students: 0,
    male_students: 0,
    female_students: 0,
    student_teacher_ratio: 0,
    total_subjects: 0
  });
  const [loading, setLoading] = useState(true);
  const [currentTab, setCurrentTab] = useState('overview');

  useEffect(() => {
    fetchDashboardStats();
  }, []);

  const fetchDashboardStats = async () => {
    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/reports/dashboard-stats`,
        {
          params: { academic_year_id: currentAcademicYear?.id },
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      if (response.data.success) {
        const stats = response.data.stats;
        setDashboardStats({
          ...stats,
          student_teacher_ratio: stats.total_students && stats.total_teachers 
            ? (stats.total_students / stats.total_teachers).toFixed(1)
            : 0
        });
      }
      setLoading(false);
    } catch (error) {
      console.error('Error fetching dashboard stats:', error);
      toast.error('Failed to load dashboard statistics');
      setLoading(false);
    }
  };

  const exportToPDF = () => {
    try {
      const printWindow = window.open('', '', 'width=800,height=600');
      const currentDate = new Date().toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
      
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>School Management System - Reports</title>
          <style>
            body { 
              font-family: Arial, sans-serif; 
              padding: 40px; 
              color: #333;
            }
            .header { 
              text-align: center; 
              margin-bottom: 30px;
              border-bottom: 3px solid #2563eb;
              padding-bottom: 20px;
            }
            .header h1 { 
              color: #2563eb; 
              margin: 0;
              font-size: 28px;
            }
            .header p { 
              color: #666; 
              margin: 5px 0;
            }
            .stats-grid { 
              display: grid; 
              grid-template-columns: repeat(3, 1fr); 
              gap: 20px; 
              margin: 30px 0;
            }
            .stat-card { 
              border: 1px solid #e5e7eb; 
              padding: 20px; 
              border-radius: 8px;
              background: #f9fafb;
            }
            .stat-title { 
              font-size: 12px; 
              color: #6b7280; 
              text-transform: uppercase;
              margin-bottom: 8px;
            }
            .stat-value { 
              font-size: 24px; 
              font-weight: bold; 
              color: #1f2937;
            }
            .section-title { 
              font-size: 20px; 
              font-weight: bold; 
              margin: 30px 0 15px 0;
              color: #1f2937;
              border-bottom: 2px solid #e5e7eb;
              padding-bottom: 8px;
            }
            .footer { 
              margin-top: 50px; 
              text-align: center; 
              color: #9ca3af;
              font-size: 12px;
              border-top: 1px solid #e5e7eb;
              padding-top: 20px;
            }
            @media print {
              body { padding: 20px; }
              .no-print { display: none; }
            }
          </style>
        </head>
        <body>
          <div class="header">
            <h1>School Management System</h1>
            <p>Comprehensive Reports & Analytics</p>
            <p>Generated on: ${currentDate}</p>
          </div>

          <div class="section-title">📊 Overview Statistics</div>
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-title">Total Students</div>
              <div class="stat-value" style="color: #2563eb;">${dashboardStats.total_students?.toLocaleString() || 0}</div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Total Teachers</div>
              <div class="stat-value" style="color: #7c3aed;">${dashboardStats.total_teachers?.toLocaleString() || 0}</div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Total Classes</div>
              <div class="stat-value" style="color: #4f46e5;">${dashboardStats.total_classes?.toLocaleString() || 0}</div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Active Students</div>
              <div class="stat-value" style="color: #059669;">${dashboardStats.active_students?.toLocaleString() || 0}</div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Inactive Students</div>
              <div class="stat-value" style="color: #dc2626;">${dashboardStats.inactive_students?.toLocaleString() || 0}</div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Student-Teacher Ratio</div>
              <div class="stat-value" style="color: #ea580c;">${dashboardStats.student_teacher_ratio}:1</div>
            </div>
          </div>

          <div class="section-title">💰 Financial Overview</div>
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-title">Recent Payments (30 Days)</div>
              <div class="stat-value" style="color: #059669;">SLE ${dashboardStats.recent_payments?.toLocaleString() || 0}</div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Average Attendance</div>
              <div class="stat-value" style="color: #0d9488;">${dashboardStats.average_attendance || 0}%</div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Pending Complaints</div>
              <div class="stat-value" style="color: #dc2626;">${dashboardStats.pending_complaints || 0}</div>
            </div>
          </div>

          <div class="section-title">👥 Student Demographics</div>
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-title">Male Students</div>
              <div class="stat-value" style="color: #2563eb;">${dashboardStats.male_students?.toLocaleString() || 0}</div>
              <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                ${dashboardStats.total_students ? ((dashboardStats.male_students / dashboardStats.total_students) * 100).toFixed(1) : 0}% of total
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Female Students</div>
              <div class="stat-value" style="color: #ec4899;">${dashboardStats.female_students?.toLocaleString() || 0}</div>
              <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                ${dashboardStats.total_students ? ((dashboardStats.female_students / dashboardStats.total_students) * 100).toFixed(1) : 0}% of total
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Total Subjects</div>
              <div class="stat-value" style="color: #8b5cf6;">${dashboardStats.total_subjects?.toLocaleString() || 0}</div>
            </div>
          </div>

          <div class="footer">
            <p>School Management System © ${new Date().getFullYear()}</p>
            <p>This report is confidential and intended for internal use only.</p>
          </div>
          
          <script>
            window.onload = function() {
              window.print();
              setTimeout(function() {
                window.close();
              }, 100);
            }
          </script>
        </body>
        </html>
      `);
      printWindow.document.close();
    } catch (error) {
      console.error('Error exporting to PDF:', error);
      toast.error('Failed to export PDF');
    }
  };

  const StatCard = ({ title, value, icon: Icon, color, suffix = '' }) => (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-gray-600">{title}</p>
            <p className={`text-3xl font-bold ${color}`}>
              {typeof value === 'number' ? value.toLocaleString() : value}{suffix}
            </p>
          </div>
          <div className={`p-3 rounded-full ${color.replace('text', 'bg')}-100`}>
            <Icon className={`h-8 w-8 ${color}`} />
          </div>
        </div>
      </CardContent>
    </Card>
  );

  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
          <p className="text-gray-600 mt-1">Comprehensive insights and performance metrics</p>
        </div>
        <Button 
          onClick={exportToPDF}
          className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700"
        >
          <FiDownload className="h-4 w-4" />
          Export to PDF
        </Button>
      </div>

      {/* Dashboard Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Total Students"
          value={dashboardStats.total_students}
          icon={FiUsers}
          color="text-blue-600"
        />
        <StatCard
          title="Active Students"
          value={dashboardStats.active_students}
          icon={FiActivity}
          color="text-green-600"
        />
        <StatCard
          title="Inactive Students"
          value={dashboardStats.inactive_students}
          icon={FiAlertCircle}
          color="text-red-600"
        />
        <StatCard
          title="Male Students"
          value={dashboardStats.male_students}
          icon={FiUsers}
          color="text-blue-500"
        />
        <StatCard
          title="Female Students"
          value={dashboardStats.female_students}
          icon={FiUsers}
          color="text-pink-600"
        />
        <StatCard
          title="Total Teachers"
          value={dashboardStats.total_teachers}
          icon={FiUsers}
          color="text-purple-600"
        />
        <StatCard
          title="Total Classes"
          value={dashboardStats.total_classes}
          icon={FiBarChart2}
          color="text-indigo-600"
        />
        <StatCard
          title="Total Subjects"
          value={dashboardStats.total_subjects}
          icon={FiBarChart2}
          color="text-violet-600"
        />
        <StatCard
          title="Student-Teacher Ratio"
          value={dashboardStats.student_teacher_ratio}
          icon={FiUsers}
          color="text-orange-600"
          suffix=":1"
        />
        <StatCard
          title="Recent Payments (30d)"
          value={`SLE ${dashboardStats.recent_payments?.toLocaleString() || 0}`}
          icon={FiDollarSign}
          color="text-green-600"
        />
        <StatCard
          title="Average Attendance"
          value={dashboardStats.average_attendance || 0}
          icon={FiTrendingUp}
          color="text-teal-600"
          suffix="%"
        />
        <StatCard
          title="Pending Complaints"
          value={dashboardStats.pending_complaints}
          icon={FiAlertCircle}
          color="text-red-600"
        />
      </div>

      {/* Detailed Reports Tabs */}
      <Tabs defaultValue="overview" className="w-full" onValueChange={setCurrentTab}>
        <TabsList className="grid w-full grid-cols-5">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="performance">Performance</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="financial">Financial</TabsTrigger>
          <TabsTrigger value="behavior">Behavior</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="space-y-4 mt-6">
          <Card>
            <CardHeader>
              <CardTitle>System Overview</CardTitle>
              <CardDescription>Key metrics and performance indicators</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-4">
                    <h3 className="font-semibold text-lg">Student Statistics</h3>
                    <div className="space-y-2">
                      <div className="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                        <span className="text-gray-700">Total Enrolled</span>
                        <span className="font-bold text-blue-600">{dashboardStats.total_students?.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <span className="text-gray-700">Active</span>
                        <span className="font-bold text-green-600">{dashboardStats.active_students?.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                        <span className="text-gray-700">Inactive</span>
                        <span className="font-bold text-red-600">{dashboardStats.inactive_students?.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                        <span className="text-gray-700">Male / Female Ratio</span>
                        <span className="font-bold text-purple-600">
                          {dashboardStats.male_students} / {dashboardStats.female_students}
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <div className="space-y-4">
                    <h3 className="font-semibold text-lg">Academic Resources</h3>
                    <div className="space-y-2">
                      <div className="flex justify-between items-center p-3 bg-indigo-50 rounded-lg">
                        <span className="text-gray-700">Total Teachers</span>
                        <span className="font-bold text-indigo-600">{dashboardStats.total_teachers?.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center p-3 bg-violet-50 rounded-lg">
                        <span className="text-gray-700">Total Classes</span>
                        <span className="font-bold text-violet-600">{dashboardStats.total_classes?.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center p-3 bg-pink-50 rounded-lg">
                        <span className="text-gray-700">Total Subjects</span>
                        <span className="font-bold text-pink-600">{dashboardStats.total_subjects?.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                        <span className="text-gray-700">Student-Teacher Ratio</span>
                        <span className="font-bold text-orange-600">{dashboardStats.student_teacher_ratio}:1</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                  <div className="p-4 border-2 border-green-200 rounded-lg bg-green-50">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm text-gray-600">Financial Health</p>
                        <p className="text-2xl font-bold text-green-600">
                          SLE {dashboardStats.recent_payments?.toLocaleString() || 0}
                        </p>
                        <p className="text-xs text-gray-500 mt-1">Last 30 days</p>
                      </div>
                      <FiDollarSign className="h-12 w-12 text-green-600 opacity-20" />
                    </div>
                  </div>

                  <div className="p-4 border-2 border-teal-200 rounded-lg bg-teal-50">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm text-gray-600">Attendance Rate</p>
                        <p className="text-2xl font-bold text-teal-600">
                          {dashboardStats.average_attendance || 0}%
                        </p>
                        <p className="text-xs text-gray-500 mt-1">System average</p>
                      </div>
                      <FiTrendingUp className="h-12 w-12 text-teal-600 opacity-20" />
                    </div>
                  </div>

                  <div className="p-4 border-2 border-red-200 rounded-lg bg-red-50">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm text-gray-600">Pending Issues</p>
                        <p className="text-2xl font-bold text-red-600">
                          {dashboardStats.pending_complaints || 0}
                        </p>
                        <p className="text-xs text-gray-500 mt-1">Requires attention</p>
                      </div>
                      <FiAlertCircle className="h-12 w-12 text-red-600 opacity-20" />
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="performance" className="space-y-4 mt-6">
          <PerformanceCharts />
        </TabsContent>

        <TabsContent value="attendance" className="space-y-4 mt-6">
          <AttendanceReports />
        </TabsContent>

        <TabsContent value="financial" className="space-y-4 mt-6">
          <FinancialReports />
        </TabsContent>

        <TabsContent value="behavior" className="space-y-4 mt-6">
          <BehaviorReports />
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default ReportsAnalytics;
