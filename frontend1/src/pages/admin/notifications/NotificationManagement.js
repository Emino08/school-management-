import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { FiPlus, FiBell, FiCheck, FiClock, FiAlertCircle, FiSend, FiTrash2 } from 'react-icons/fi';
import axios from 'axios';
import { useToast } from '@/hooks/use-toast';
import { formatDistanceToNow } from 'date-fns';

const NotificationManagement = () => {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [activeTab, setActiveTab] = useState('all');
  const { toast } = useToast();

  const [formData, setFormData] = useState({
    title: '',
    message: '',
    recipient_type: 'All',
    priority: 'Medium',
    status: 'Sent'
  });

  useEffect(() => {
    fetchNotifications();
  }, []);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/admin/notifications');
      if (response.data.success) {
        setNotifications(response.data.notifications || []);
      }
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
      toast({
        variant: 'destructive',
        title: 'Error',
        description: 'Failed to load notifications'
      });
    } finally {
      setLoading(false);
    }
  };

  const handleCreateNotification = async () => {
    if (!formData.title || !formData.message) {
      toast({
        variant: 'destructive',
        title: 'Validation Error',
        description: 'Please fill in all required fields'
      });
      return;
    }

    try {
      const response = await axios.post('/admin/notifications', formData);
      if (response.data.success) {
        toast({
          title: 'Success',
          description: 'Notification sent successfully'
        });
        setShowCreateDialog(false);
        setFormData({
          title: '',
          message: '',
          recipient_type: 'All',
          priority: 'Medium',
          status: 'Sent'
        });
        fetchNotifications();
      }
    } catch (error) {
      toast({
        variant: 'destructive',
        title: 'Error',
        description: error.response?.data?.message || 'Failed to send notification'
      });
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'Urgent': return 'bg-red-100 text-red-800';
      case 'High': return 'bg-orange-100 text-orange-800';
      case 'Medium': return 'bg-blue-100 text-blue-800';
      case 'Low': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'Sent': return <FiCheck className="h-4 w-4 text-green-600" />;
      case 'Scheduled': return <FiClock className="h-4 w-4 text-blue-600" />;
      case 'Draft': return <FiAlertCircle className="h-4 w-4 text-gray-600" />;
      default: return null;
    }
  };

  const filterNotifications = (notifications) => {
    switch (activeTab) {
      case 'sent':
        return notifications.filter(n => n.status === 'Sent');
      case 'scheduled':
        return notifications.filter(n => n.status === 'Scheduled');
      case 'draft':
        return notifications.filter(n => n.status === 'Draft');
      default:
        return notifications;
    }
  };

  const filteredNotifications = filterNotifications(notifications);

  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Notifications</h1>
          <p className="text-gray-600 mt-1">Send and manage system notifications</p>
        </div>
        <Button onClick={() => setShowCreateDialog(true)}>
          <FiPlus className="mr-2 h-4 w-4" />
          Send Notification
        </Button>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="all">
            All ({notifications.length})
          </TabsTrigger>
          <TabsTrigger value="sent">
            Sent ({notifications.filter(n => n.status === 'Sent').length})
          </TabsTrigger>
          <TabsTrigger value="scheduled">
            Scheduled ({notifications.filter(n => n.status === 'Scheduled').length})
          </TabsTrigger>
          <TabsTrigger value="draft">
            Drafts ({notifications.filter(n => n.status === 'Draft').length})
          </TabsTrigger>
        </TabsList>

        <TabsContent value={activeTab} className="space-y-4">
          {loading ? (
            <Card>
              <CardContent className="py-8">
                <p className="text-center text-gray-600">Loading notifications...</p>
              </CardContent>
            </Card>
          ) : filteredNotifications.length === 0 ? (
            <Card>
              <CardContent className="py-8">
                <div className="text-center text-gray-600">
                  <FiBell className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                  <p className="font-medium">No notifications found</p>
                  <p className="text-sm mt-1">Create your first notification to get started</p>
                </div>
              </CardContent>
            </Card>
          ) : (
            filteredNotifications.map((notification) => (
              <Card key={notification.id} className="hover:shadow-md transition-shadow">
                <CardContent className="p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        {getStatusIcon(notification.status)}
                        <h3 className="font-semibold text-gray-900">{notification.title}</h3>
                        <Badge className={getPriorityColor(notification.priority)}>
                          {notification.priority}
                        </Badge>
                      </div>
                      <p className="text-gray-700 mb-3">{notification.message}</p>
                      <div className="flex items-center gap-4 text-sm text-gray-600">
                        <span className="flex items-center gap-1">
                          <FiSend className="h-3 w-3" />
                          To: {notification.recipient_type}
                        </span>
                        <span>By: {notification.sender_name || 'System'}</span>
                        <span>
                          {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
                        </span>
                        {notification.read_count !== undefined && (
                          <span>Read by: {notification.read_count} users</span>
                        )}
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))
          )}
        </TabsContent>
      </Tabs>

      {/* Create Notification Dialog */}
      <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
        <DialogContent className="sm:max-w-[600px]">
          <DialogHeader>
            <DialogTitle>Send New Notification</DialogTitle>
            <DialogDescription>
              Create and send a notification to users
            </DialogDescription>
          </DialogHeader>
          
          <div className="grid gap-4 py-4">
            <div className="grid gap-2">
              <Label htmlFor="title">Title *</Label>
              <Input
                id="title"
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                placeholder="Enter notification title"
              />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="message">Message *</Label>
              <Textarea
                id="message"
                value={formData.message}
                onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                placeholder="Enter notification message"
                rows={4}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="grid gap-2">
                <Label htmlFor="recipient">Send To</Label>
                <Select
                  value={formData.recipient_type}
                  onValueChange={(value) => setFormData({ ...formData, recipient_type: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="All">All Users</SelectItem>
                    <SelectItem value="Students">All Students</SelectItem>
                    <SelectItem value="Teachers">All Teachers</SelectItem>
                    <SelectItem value="Parents">All Parents</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="grid gap-2">
                <Label htmlFor="priority">Priority</Label>
                <Select
                  value={formData.priority}
                  onValueChange={(value) => setFormData({ ...formData, priority: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Low">Low</SelectItem>
                    <SelectItem value="Medium">Medium</SelectItem>
                    <SelectItem value="High">High</SelectItem>
                    <SelectItem value="Urgent">Urgent</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setShowCreateDialog(false)}>
              Cancel
            </Button>
            <Button onClick={handleCreateNotification}>
              <FiSend className="mr-2 h-4 w-4" />
              Send Notification
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default NotificationManagement;
