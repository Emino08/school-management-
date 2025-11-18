import { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from '@/redux/axiosConfig';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { toast } from 'sonner';
import {
  FiActivity,
  FiUser,
  FiUsers,
  FiRefreshCw,
  FiDownload,
  FiFilter,
  FiCalendar,
  FiClock,
  FiAlertCircle,
  FiCheckCircle,
  FiEdit,
  FiTrash2,
  FiPlus,
} from 'react-icons/fi';

const ActivityLogs = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [logs, setLogs] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(false);
  const [filters, setFilters] = useState({
    user_type: 'all',
    activity_type: 'all',
    start_date: '',
    end_date: '',
    limit: 50,
    offset: 0,
  });

  useEffect(() => {
    fetchLogs();
    fetchStats();
  }, []);

  const fetchLogs = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      Object.keys(filters).forEach(key => {
        if (filters[key] && filters[key] !== 'all') params.append(key, filters[key]);
      });

      const response = await axios.get(
        `${process.env.REACT_APP_BASE_URL}/admin/activity-logs?${params.toString()}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );

      if (response.data.success) {
        setLogs(response.data.logs || []);
      }
    } catch (error) {
      console.error('Error fetching logs:', error);
      toast.error('Failed to load activity logs');
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await axios.get(
        `${process.env.REACT_APP_BASE_URL}/admin/activity-logs/stats`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );

      if (response.data.success) {
        setStats(response.data.stats);
      }
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value, offset: 0 }));
  };

  const handleApplyFilters = () => {
    fetchLogs();
  };

  const handleResetFilters = () => {
    setFilters({
      user_type: 'all',
      activity_type: 'all',
      start_date: '',
      end_date: '',
      limit: 50,
      offset: 0,
    });
    setTimeout(() => fetchLogs(), 100);
  };

  const handleExport = async () => {
    try {
      const params = new URLSearchParams();
      Object.keys(filters).forEach(key => {
        if (filters[key]) params.append(key, filters[key]);
      });

      const response = await axios.get(
        `${process.env.REACT_APP_BASE_URL}/admin/activity-logs/export?${params.toString()}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
          responseType: 'blob',
        }
      );

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `activity-logs-${new Date().toISOString()}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success('Logs exported successfully');
    } catch (error) {
      console.error('Error exporting logs:', error);
      toast.error('Failed to export logs');
    }
  };

  const getActivityIcon = (activityType) => {
    switch (activityType?.toLowerCase()) {
      case 'create':
      case 'add':
        return <FiPlus className="h-4 w-4 text-green-600" />;
      case 'update':
      case 'edit':
        return <FiEdit className="h-4 w-4 text-blue-600" />;
      case 'delete':
      case 'remove':
        return <FiTrash2 className="h-4 w-4 text-red-600" />;
      case 'login':
      case 'logout':
        return <FiUser className="h-4 w-4 text-purple-600" />;
      default:
        return <FiActivity className="h-4 w-4 text-gray-600" />;
    }
  };

  const getActivityColor = (activityType) => {
    switch (activityType?.toLowerCase()) {
      case 'create':
      case 'add':
        return 'bg-green-100 text-green-800';
      case 'update':
      case 'edit':
        return 'bg-blue-100 text-blue-800';
      case 'delete':
      case 'remove':
        return 'bg-red-100 text-red-800';
      case 'login':
        return 'bg-purple-100 text-purple-800';
      case 'logout':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getUserRoleColor = (role) => {
    switch (role?.toLowerCase()) {
      case 'admin':
        return 'bg-purple-100 text-purple-800';
      case 'teacher':
        return 'bg-blue-100 text-blue-800';
      case 'student':
        return 'bg-green-100 text-green-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const loadMore = () => {
    setFilters(prev => ({ ...prev, offset: prev.offset + prev.limit }));
    setTimeout(() => fetchLogs(), 100);
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Activity Logs</h1>
          <p className="text-gray-600 mt-1">Monitor system activity and user actions</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={fetchLogs} disabled={loading}>
            <FiRefreshCw className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
            Refresh
          </Button>
          <Button variant="outline" onClick={handleExport}>
            <FiDownload className="mr-2 h-4 w-4" />
            Export
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                  <FiActivity className="h-6 w-6 text-blue-600" />
                </div>
                <div>
                  <p className="text-2xl font-bold">{stats.total || 0}</p>
                  <p className="text-sm text-gray-600">Total Activities</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                  <FiPlus className="h-6 w-6 text-green-600" />
                </div>
                <div>
                  <p className="text-2xl font-bold">{stats.creates || 0}</p>
                  <p className="text-sm text-gray-600">Creates</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                  <FiEdit className="h-6 w-6 text-blue-600" />
                </div>
                <div>
                  <p className="text-2xl font-bold">{stats.updates || 0}</p>
                  <p className="text-sm text-gray-600">Updates</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                  <FiTrash2 className="h-6 w-6 text-red-600" />
                </div>
                <div>
                  <p className="text-2xl font-bold">{stats.deletes || 0}</p>
                  <p className="text-sm text-gray-600">Deletes</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                  <FiUsers className="h-6 w-6 text-purple-600" />
                </div>
                <div>
                  <p className="text-2xl font-bold">{stats.unique_users || 0}</p>
                  <p className="text-sm text-gray-600">Active Users</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FiFilter className="h-5 w-5" />
            Filters
          </CardTitle>
          <CardDescription>Filter activity logs by user type, action, or date</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div className="space-y-2">
              <Label>User Type</Label>
              <Select
                value={filters.user_type}
                onValueChange={(value) => handleFilterChange('user_type', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="All Users" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Users</SelectItem>
                  <SelectItem value="Admin">Admin</SelectItem>
                  <SelectItem value="Teacher">Teacher</SelectItem>
                  <SelectItem value="Student">Student</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Activity Type</Label>
              <Select
                value={filters.activity_type}
                onValueChange={(value) => handleFilterChange('activity_type', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="All Actions" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Actions</SelectItem>
                  <SelectItem value="create">Create</SelectItem>
                  <SelectItem value="update">Update</SelectItem>
                  <SelectItem value="delete">Delete</SelectItem>
                  <SelectItem value="login">Login</SelectItem>
                  <SelectItem value="logout">Logout</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Start Date</Label>
              <Input
                type="date"
                value={filters.start_date}
                onChange={(e) => handleFilterChange('start_date', e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label>End Date</Label>
              <Input
                type="date"
                value={filters.end_date}
                onChange={(e) => handleFilterChange('end_date', e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label>Limit</Label>
              <Select
                value={filters.limit.toString()}
                onValueChange={(value) => handleFilterChange('limit', parseInt(value))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="25">25</SelectItem>
                  <SelectItem value="50">50</SelectItem>
                  <SelectItem value="100">100</SelectItem>
                  <SelectItem value="200">200</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="flex gap-2 mt-4">
            <Button onClick={handleApplyFilters}>
              <FiFilter className="mr-2 h-4 w-4" />
              Apply Filters
            </Button>
            <Button variant="outline" onClick={handleResetFilters}>
              Reset
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Activity Logs Table */}
      <Card>
        <CardHeader>
          <CardTitle>Activity History</CardTitle>
          <CardDescription>
            Showing {logs.length} of {stats?.total || 0} activities
          </CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <FiRefreshCw className="h-8 w-8 text-gray-400 animate-spin" />
            </div>
          ) : logs.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12">
              <FiActivity className="h-12 w-12 text-gray-400 mb-4" />
              <p className="text-gray-600">No activity logs found</p>
            </div>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-[50px]"></TableHead>
                    <TableHead>User</TableHead>
                    <TableHead>Role</TableHead>
                    <TableHead>Activity</TableHead>
                    <TableHead>Entity</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead>IP Address</TableHead>
                    <TableHead>Timestamp</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {logs.map((log, index) => (
                    <TableRow key={log.id || index}>
                      <TableCell>{getActivityIcon(log.activity_type)}</TableCell>
                      <TableCell className="font-medium">{log.user_name || log.user_id}</TableCell>
                      <TableCell>
                        <Badge className={getUserRoleColor(log.user_type)}>{log.user_type}</Badge>
                      </TableCell>
                      <TableCell>
                        <Badge className={getActivityColor(log.activity_type)}>
                          {log.activity_type}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm text-gray-600">
                          {log.entity_type}
                          {log.entity_id && <span className="text-gray-400"> #{log.entity_id}</span>}
                        </span>
                      </TableCell>
                      <TableCell className="max-w-xs truncate" title={log.description}>
                        <div className="flex items-center gap-2">
                          <span className="truncate">{log.description || '-'}</span>
                          {/* Override badge if metadata indicates override_capacity true */}
                          {(() => {
                            try {
                              const meta = log.metadata ? (typeof log.metadata === 'string' ? JSON.parse(log.metadata) : log.metadata) : null;
                              if (meta && (meta.override_capacity === true || meta.override_capacity === 1 || meta.override_capacity === 'true')) {
                                return (
                                  <span className="inline-flex items-center rounded-full bg-amber-100 text-amber-700 border border-amber-200 px-2 py-0.5 text-[10px] uppercase tracking-wide">Override used</span>
                                );
                              }
                            } catch (e) {}
                            return null;
                          })()}
                        </div>
                      </TableCell>
                      <TableCell className="text-sm text-gray-600">{log.ip_address || '-'}</TableCell>
                      <TableCell className="text-sm text-gray-600">
                        <div className="flex items-center gap-1">
                          <FiClock className="h-3 w-3" />
                          {new Date(log.created_at).toLocaleString()}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {logs.length >= filters.limit && (
                <div className="p-4 border-t flex justify-center">
                  <Button variant="outline" onClick={loadMore}>
                    Load More
                  </Button>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      {/* Real-time Activity Monitor */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FiActivity className="h-5 w-5 text-green-600 animate-pulse" />
            Recent Activity
          </CardTitle>
          <CardDescription>Live feed of recent system activities</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {logs.slice(0, 5).map((log, index) => (
              <div key={index} className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                <div className="flex-shrink-0 mt-1">{getActivityIcon(log.activity_type)}</div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900">
                    {log.user_name || log.user_id}
                    <Badge className={`ml-2 ${getUserRoleColor(log.user_type)}`}>
                      {log.user_type}
                    </Badge>
                  </p>
                  <p className="text-sm text-gray-600 mt-1">{log.description}</p>
                  <p className="text-xs text-gray-500 mt-1 flex items-center gap-1">
                    <FiClock className="h-3 w-3" />
                    {new Date(log.created_at).toLocaleString()}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ActivityLogs;
