import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { FiBarChart2, FiUsers, FiDollarSign, FiAlertCircle, FiTrendingUp } from 'react-icons/fi';
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
    pending_complaints: 0
  });
  const [loading, setLoading] = useState(true);

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
        setDashboardStats(response.data.stats);
      }
      setLoading(false);
    } catch (error) {
      console.error('Error fetching dashboard stats:', error);
      toast.error('Failed to load dashboard statistics');
      setLoading(false);
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
      </div>

      {/* Dashboard Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <StatCard
          title="Total Students"
          value={dashboardStats.total_students}
          icon={FiUsers}
          color="text-blue-600"
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
          title="Recent Payments (30d)"
          value={`$${dashboardStats.recent_payments?.toLocaleString() || 0}`}
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
      <Tabs defaultValue="performance" className="w-full">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="performance">Performance</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="financial">Financial</TabsTrigger>
          <TabsTrigger value="behavior">Behavior</TabsTrigger>
        </TabsList>

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
