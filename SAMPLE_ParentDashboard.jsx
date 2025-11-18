// Sample Parent Dashboard Component
// Location: frontend1/src/pages/parent/ParentDashboard.jsx

import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Users, Bell, MessageSquare, Activity, AlertCircle, CheckCircle } from 'lucide-react';
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080/api';

const ParentDashboard = () => {
  const [children, setChildren] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    totalChildren: 0,
    unreadNotifications: 0,
    pendingCommunications: 0
  });

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      const token = localStorage.getItem('parent_token');
      const config = { headers: { Authorization: `Bearer ${token}` } };

      // Fetch children
      const childrenRes = await axios.get(`${API_URL}/parents/children`, config);
      setChildren(childrenRes.data.children || []);

      // Fetch notifications
      const notifRes = await axios.get(`${API_URL}/parents/notifications?limit=5`, config);
      setNotifications(notifRes.data.notifications || []);
      setUnreadCount(notifRes.data.unread_count || 0);

      // Update stats
      setStats({
        totalChildren: childrenRes.data.children?.length || 0,
        unreadNotifications: notifRes.data.unread_count || 0,
        pendingCommunications: 0 // Could fetch from communications endpoint
      });

      setLoading(false);
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
      setLoading(false);
    }
  };

  const getNotificationIcon = (type) => {
    const icons = {
      attendance_miss: <AlertCircle className="h-5 w-5 text-orange-500" />,
      suspension: <AlertCircle className="h-5 w-5 text-red-500" />,
      medical: <Activity className="h-5 w-5 text-blue-500" />,
      academic: <CheckCircle className="h-5 w-5 text-green-500" />
    };
    return icons[type] || <Bell className="h-5 w-5" />;
  };

  const markNotificationRead = async (notificationId) => {
    try {
      const token = localStorage.getItem('parent_token');
      await axios.put(
        `${API_URL}/parents/notifications/${notificationId}/read`,
        {},
        { headers: { Authorization: `Bearer ${token}` } }
      );
      fetchDashboardData(); // Refresh
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Parent Dashboard</h1>
          <p className="text-gray-600">Welcome back! Here's what's happening with your children.</p>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">My Children</p>
                <h3 className="text-2xl font-bold">{stats.totalChildren}</h3>
              </div>
              <Users className="h-10 w-10 text-blue-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Unread Notifications</p>
                <h3 className="text-2xl font-bold">{stats.unreadNotifications}</h3>
              </div>
              <Bell className="h-10 w-10 text-orange-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Messages</p>
                <h3 className="text-2xl font-bold">{stats.pendingCommunications}</h3>
              </div>
              <MessageSquare className="h-10 w-10 text-green-600" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Children List */}
      <Card>
        <CardHeader>
          <CardTitle>My Children</CardTitle>
        </CardHeader>
        <CardContent>
          {children.length === 0 ? (
            <Alert>
              <AlertDescription>
                No children linked yet. Please link your children using their Student ID and Date of Birth.
              </AlertDescription>
            </Alert>
          ) : (
            <div className="space-y-4">
              {children.map((child) => (
                <div
                  key={child.id}
                  className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-center space-x-4">
                    <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                      <span className="text-blue-600 font-semibold text-lg">
                        {child.name?.charAt(0).toUpperCase()}
                      </span>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-900">{child.name}</h4>
                      <p className="text-sm text-gray-600">
                        {child.class_name} {child.section} • ID: {child.id_number}
                      </p>
                    </div>
                  </div>
                  <Button
                    onClick={() => window.location.href = `/parent/child/${child.id}`}
                    variant="outline"
                  >
                    View Details
                  </Button>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Recent Notifications */}
      <Card>
        <CardHeader>
          <div className="flex justify-between items-center">
            <CardTitle>Recent Notifications</CardTitle>
            {unreadCount > 0 && (
              <Badge variant="destructive">{unreadCount} Unread</Badge>
            )}
          </div>
        </CardHeader>
        <CardContent>
          {notifications.length === 0 ? (
            <Alert>
              <AlertDescription>No notifications yet.</AlertDescription>
            </Alert>
          ) : (
            <div className="space-y-3">
              {notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`flex items-start space-x-3 p-4 border rounded-lg ${
                    !notification.is_read ? 'bg-blue-50 border-blue-200' : 'bg-white'
                  }`}
                >
                  <div className="mt-1">{getNotificationIcon(notification.type)}</div>
                  <div className="flex-1">
                    <div className="flex justify-between items-start">
                      <div>
                        <h5 className="font-semibold text-gray-900">{notification.title}</h5>
                        <p className="text-sm text-gray-600 mt-1">{notification.message}</p>
                        <p className="text-xs text-gray-500 mt-2">
                          {notification.student_name} • {new Date(notification.created_at).toLocaleDateString()}
                        </p>
                      </div>
                      {!notification.is_read && (
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => markNotificationRead(notification.id)}
                        >
                          Mark Read
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
          {notifications.length > 0 && (
            <Button
              variant="link"
              className="w-full mt-4"
              onClick={() => window.location.href = '/parent/notifications'}
            >
              View All Notifications
            </Button>
          )}
        </CardContent>
      </Card>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <Button
              variant="outline"
              className="h-24 flex-col"
              onClick={() => window.location.href = '/parent/children/link'}
            >
              <Users className="h-6 w-6 mb-2" />
              Link Child
            </Button>
            <Button
              variant="outline"
              className="h-24 flex-col"
              onClick={() => window.location.href = '/parent/communications'}
            >
              <MessageSquare className="h-6 w-6 mb-2" />
              Send Message
            </Button>
            <Button
              variant="outline"
              className="h-24 flex-col"
              onClick={() => window.location.href = '/parent/notices'}
            >
              <Bell className="h-6 w-6 mb-2" />
              View Notices
            </Button>
            <Button
              variant="outline"
              className="h-24 flex-col"
              onClick={() => window.location.href = '/parent/medical-upload'}
            >
              <Activity className="h-6 w-6 mb-2" />
              Upload Medical
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ParentDashboard;
