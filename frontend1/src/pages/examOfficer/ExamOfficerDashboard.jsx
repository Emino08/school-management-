import { useEffect, useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { AlertCircle, CheckCircle, Clock, Shield } from 'lucide-react';
import axios from '../../redux/axiosConfig';
import { useSelector } from 'react-redux';
import { toast } from 'sonner';
import PendingGradeRequests from './PendingGradeRequests';
import VerificationDashboard from './VerificationDashboard';

const ExamOfficerDashboard = () => {
  const { currentUser } = useSelector((state) => state.user);
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';
  const [stats, setStats] = useState({
    pending_grade_changes: 0,
    awaiting_verification: 0,
    total_verified: 0,
    is_head_of_exam_office: false,
    is_exam_officer: false
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      setLoading(true);
      const res = await axios.get(`${API_URL}/exam-officer/teacher-stats`);
      if (res.data?.success) {
        setStats(res.data.stats);
      }
    } catch (error) {
      console.error('Failed to fetch stats:', error);
      toast.error('Failed to load dashboard statistics');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="container mx-auto p-6 flex items-center justify-center min-h-screen">
        <div className="text-center">
          <Clock className="w-12 h-12 animate-spin mx-auto mb-4 text-blue-500" />
          <p className="text-gray-600">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  if (!stats.is_exam_officer) {
    return (
      <div className="container mx-auto p-6">
        <Card>
          <CardContent className="p-12 text-center">
            <AlertCircle className="w-16 h-16 text-red-500 mx-auto mb-4" />
            <h2 className="text-2xl font-bold mb-2">Access Denied</h2>
            <p className="text-gray-600">
              You don't have exam officer permissions. Please contact the administrator.
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="container mx-auto p-6 max-w-7xl space-y-6">
      {/* Header */}
      <div className="flex items-center gap-3">
        <Shield className="w-8 h-8 text-blue-600" />
        <div>
          <h1 className="text-3xl font-bold">Exam Officer Dashboard</h1>
          <p className="text-gray-600">
            {stats.is_head_of_exam_office ? 'Head of Exam Office' : 'Exam Officer'}
          </p>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Pending Grade Changes
            </CardTitle>
            <Clock className="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.pending_grade_changes}</div>
            <p className="text-xs text-gray-500 mt-1">
              Requires your review
            </p>
          </CardContent>
        </Card>

        {stats.is_head_of_exam_office && (
          <Card className="hover:shadow-lg transition-shadow">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                Awaiting Verification
              </CardTitle>
              <AlertCircle className="h-4 w-4 text-orange-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.awaiting_verification}</div>
              <p className="text-xs text-gray-500 mt-1">
                Approved grades pending verification
              </p>
            </CardContent>
          </Card>
        )}

        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Total Verified
            </CardTitle>
            <CheckCircle className="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.total_verified}</div>
            <p className="text-xs text-gray-500 mt-1">
              All time verified grades
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Tabs */}
      <Card>
        <CardContent className="p-6">
          <Tabs defaultValue="pending-requests" className="w-full">
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="pending-requests">
                Pending Requests ({stats.pending_grade_changes})
              </TabsTrigger>
              {stats.is_head_of_exam_office && (
                <TabsTrigger value="verification">
                  Verification ({stats.awaiting_verification})
                </TabsTrigger>
              )}
            </TabsList>

            <TabsContent value="pending-requests" className="mt-6">
              <PendingGradeRequests onUpdate={fetchStats} />
            </TabsContent>

            {stats.is_head_of_exam_office && (
              <TabsContent value="verification" className="mt-6">
                <VerificationDashboard onUpdate={fetchStats} />
              </TabsContent>
            )}
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
};

export default ExamOfficerDashboard;
