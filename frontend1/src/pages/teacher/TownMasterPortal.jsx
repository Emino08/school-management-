import React, { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Shield, Users, CheckSquare, BarChart3, AlertCircle } from 'lucide-react';
import StudentRegistration from './townMaster/StudentRegistration';
import TownAttendance from './townMaster/TownAttendance';
import AttendanceAnalytics from './townMaster/AttendanceAnalytics';
import TownStudents from './townMaster/TownStudents';

const TownMasterPortal = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [townData, setTownData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview');

  useEffect(() => {
    fetchTownData();
  }, []);

  const fetchTownData = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/teacher/town-master/my-town');
      if (response.data.success) {
        setTownData(response.data.town);
      } else {
        toast.error(response.data.message || 'Failed to fetch town data');
      }
    } catch (error) {
      console.error('Error fetching town data:', error);
      toast.error(error.response?.data?.message || 'Failed to fetch town data');
    } finally {
      setLoading(false);
    }
  };

  const isTownMaster =
    currentUser?.teacher?.is_town_master ||
    currentUser?.is_town_master ||
    currentUser?.roles?.includes?.('town_master');

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading Town Master Portal...</p>
        </div>
      </div>
    );
  }

  if (!isTownMaster) {
    return (
      <div className="container mx-auto p-6 max-w-4xl">
        <Card className="border-amber-200 bg-amber-50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-amber-800">
              <AlertCircle className="w-5 h-5" />
              Access Restricted
            </CardTitle>
          </CardHeader>
          <CardContent className="text-amber-800">
            <p>You are not assigned as a Town Master. Please contact an administrator.</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (!townData) {
    return (
      <div className="container mx-auto p-6 max-w-4xl">
        <Card className="border-amber-200 bg-amber-50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-amber-800">
              <AlertCircle className="w-5 h-5" />
              No Town Assigned
            </CardTitle>
          </CardHeader>
          <CardContent className="text-amber-800">
            <p>You have not been assigned to any town yet. Please contact an administrator.</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  const totalStudents = townData.blocks?.reduce((sum, block) => sum + (block.current_occupancy || 0), 0) || 0;
  const totalCapacity = townData.blocks?.reduce((sum, block) => sum + (block.capacity || 0), 0) || 0;

  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div className="flex items-center gap-3">
        <Shield className="w-8 h-8 text-purple-600" />
        <div>
          <h1 className="text-3xl font-bold">Town Master Portal</h1>
          <p className="text-gray-600">
            Managing: <span className="font-semibold text-purple-600">{townData.name}</span>
          </p>
        </div>
      </div>

      {/* Overview Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">Total Blocks</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{townData.blocks?.length || 0}</div>
            <p className="text-xs text-gray-500 mt-1">Blocks A-F</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">Total Students</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalStudents}</div>
            <p className="text-xs text-gray-500 mt-1">
              {totalCapacity - totalStudents} spaces available
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">Occupancy Rate</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {totalCapacity > 0 ? Math.round((totalStudents / totalCapacity) * 100) : 0}%
            </div>
            <p className="text-xs text-gray-500 mt-1">
              {totalStudents} / {totalCapacity} capacity
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="overview" className="flex items-center gap-2">
            <Shield className="w-4 h-4" />
            <span className="hidden sm:inline">Overview</span>
          </TabsTrigger>
          <TabsTrigger value="register" className="flex items-center gap-2">
            <Users className="w-4 h-4" />
            <span className="hidden sm:inline">Register Student</span>
          </TabsTrigger>
          <TabsTrigger value="attendance" className="flex items-center gap-2">
            <CheckSquare className="w-4 h-4" />
            <span className="hidden sm:inline">Roll Call</span>
          </TabsTrigger>
          <TabsTrigger value="analytics" className="flex items-center gap-2">
            <BarChart3 className="w-4 h-4" />
            <span className="hidden sm:inline">Analytics</span>
          </TabsTrigger>
        </TabsList>

        <TabsContent value="overview">
          <TownStudents townData={townData} onRefresh={fetchTownData} />
        </TabsContent>

        <TabsContent value="register">
          <StudentRegistration townData={townData} onSuccess={fetchTownData} />
        </TabsContent>

        <TabsContent value="attendance">
          <TownAttendance townData={townData} />
        </TabsContent>

        <TabsContent value="analytics">
          <AttendanceAnalytics townData={townData} />
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default TownMasterPortal;
