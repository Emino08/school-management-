import React, { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from '@/redux/axiosConfig';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { toast } from 'sonner';
import {
  FiBell,
  FiPlus,
  FiSend,
  FiTrash2,
  FiEdit,
  FiClock,
  FiUsers,
  FiUser,
  FiCheck,
  FiAlertCircle,
} from 'react-icons/fi';

const NotificationsManagement = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [notifications, setNotifications] = useState([]);
  const [classes, setClasses] = useState([]);
  const [students, setStudents] = useState([]);
  const [teachers, setTeachers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingNotification, setEditingNotification] = useState(null);

  const [formData, setFormData] = useState({
    title: '',
    message: '',
    recipient_type: 'All',
    recipient_id: null,
    class_id: null,
    priority: 'Medium',
    status: 'Sent',
    scheduled_at: null,
  });

  useEffect(() => {
    fetchNotifications();
    fetchClasses();
    fetchStudents();
    fetchTeachers();
  }, []);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/admin/notifications`);
      if (response.data.success) {
        setNotifications(response.data.notifications || []);
      }
    } catch (error) {
      console.error('Error fetching notifications:', error);
      toast.error('Failed to load notifications');
    } finally {
      setLoading(false);
    }
  };

  const fetchClasses = async () => {
    try {
      const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/classes`);
      if (response.data.success) {
        setClasses(response.data.classes || []);
      }
    } catch (error) {
      console.error('Error fetching classes:', error);
    }
  };

  const fetchStudents = async () => {
    try {
      const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/students`);
      if (response.data.success) {
        setStudents(response.data.students || []);
      }
    } catch (error) {
      console.error('Error fetching students:', error);
    }
  };

  const fetchTeachers = async () => {
    try {
      const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/teachers`);
      if (response.data.success) {
        setTeachers(response.data.teachers || []);
      }
    } catch (error) {
      console.error('Error fetching teachers:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const payload = {
        ...formData,
        sender_id: currentUser.id,
        sender_role: 'Admin',
      };

      if (editingNotification) {
        await axios.put(
          `${import.meta.env.VITE_API_BASE_URL}/admin/notifications/${editingNotification.id}`,
          payload
        );
        toast.success('Notification updated successfully');
      } else {
        await axios.post(
          `${import.meta.env.VITE_API_BASE_URL}/admin/notifications`,
          payload
        );
        toast.success('Notification sent successfully');
      }

      resetForm();
      setIsDialogOpen(false);
      fetchNotifications();
    } catch (error) {
      console.error('Error saving notification:', error);
      toast.error(error.response?.data?.message || 'Failed to save notification');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this notification?')) return;

    try {
      await axios.delete(`${import.meta.env.VITE_API_BASE_URL}/admin/notifications/${id}`);
      toast.success('Notification deleted successfully');
      fetchNotifications();
    } catch (error) {
      console.error('Error deleting notification:', error);
      toast.error('Failed to delete notification');
    }
  };

  const handleEdit = (notification) => {
    setEditingNotification(notification);
    setFormData({
      title: notification.title,
      message: notification.message,
      recipient_type: notification.recipient_type,
      recipient_id: notification.recipient_id,
      class_id: notification.class_id,
      priority: notification.priority,
      status: notification.status,
      scheduled_at: notification.scheduled_at,
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setFormData({
      title: '',
      message: '',
      recipient_type: 'All',
      recipient_id: null,
      class_id: null,
      priority: 'Medium',
      status: 'Sent',
      scheduled_at: null,
    });
    setEditingNotification(null);
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'High':
        return 'bg-red-100 text-red-800';
      case 'Medium':
        return 'bg-yellow-100 text-yellow-800';
      case 'Low':
        return 'bg-green-100 text-green-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'Sent':
        return 'bg-green-100 text-green-800';
      case 'Scheduled':
        return 'bg-blue-100 text-blue-800';
      case 'Draft':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const sentNotifications = notifications.filter((n) => n.status === 'Sent');
  const scheduledNotifications = notifications.filter((n) => n.status === 'Scheduled');
  const draftNotifications = notifications.filter((n) => n.status === 'Draft');

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Notifications Management</h1>
          <p className="text-gray-600 mt-1">Create and manage system notifications</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <FiPlus className="mr-2 h-4 w-4" />
              Create Notification
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>
                {editingNotification ? 'Edit Notification' : 'Create New Notification'}
              </DialogTitle>
              <DialogDescription>
                Send notifications to students, teachers, or specific groups
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="title">Title *</Label>
                <Input
                  id="title"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  placeholder="Notification title"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="message">Message *</Label>
                <Textarea
                  id="message"
                  value={formData.message}
                  onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                  placeholder="Notification message"
                  rows={4}
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="recipient_type">Recipient Type *</Label>
                  <Select
                    value={formData.recipient_type}
                    onValueChange={(value) =>
                      setFormData({ ...formData, recipient_type: value, recipient_id: null, class_id: null })
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="All">All Users</SelectItem>
                      <SelectItem value="Students">All Students</SelectItem>
                      <SelectItem value="Teachers">All Teachers</SelectItem>
                      <SelectItem value="Parents">All Parents</SelectItem>
                      <SelectItem value="Specific Class">Specific Class</SelectItem>
                      <SelectItem value="Individual">Individual User</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="priority">Priority *</Label>
                  <Select
                    value={formData.priority}
                    onValueChange={(value) => setFormData({ ...formData, priority: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="High">High</SelectItem>
                      <SelectItem value="Medium">Medium</SelectItem>
                      <SelectItem value="Low">Low</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              {formData.recipient_type === 'Specific Class' && (
                <div className="space-y-2">
                  <Label htmlFor="class_id">Select Class *</Label>
                  <Select
                    value={formData.class_id?.toString()}
                    onValueChange={(value) => setFormData({ ...formData, class_id: parseInt(value) })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Choose a class" />
                    </SelectTrigger>
                    <SelectContent>
                      {classes.map((cls) => (
                        <SelectItem key={cls.id} value={cls.id.toString()}>
                          {cls.class_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}

              {formData.recipient_type === 'Individual' && (
                <div className="space-y-2">
                  <Label htmlFor="recipient_id">Select User *</Label>
                  <Select
                    value={formData.recipient_id?.toString()}
                    onValueChange={(value) => setFormData({ ...formData, recipient_id: parseInt(value) })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Choose a user" />
                    </SelectTrigger>
                    <SelectContent>
                      <div className="px-2 py-1 text-xs font-semibold text-gray-500">Students</div>
                      {students.map((student) => (
                        <SelectItem key={`student-${student.id}`} value={student.id.toString()}>
                          {student.name} ({student.id_number})
                        </SelectItem>
                      ))}
                      <div className="px-2 py-1 text-xs font-semibold text-gray-500">Teachers</div>
                      {teachers.map((teacher) => (
                        <SelectItem key={`teacher-${teacher.id}`} value={teacher.id.toString()}>
                          {teacher.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="status">Status *</Label>
                  <Select
                    value={formData.status}
                    onValueChange={(value) => setFormData({ ...formData, status: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Sent">Send Now</SelectItem>
                      <SelectItem value="Scheduled">Schedule</SelectItem>
                      <SelectItem value="Draft">Save as Draft</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                {formData.status === 'Scheduled' && (
                  <div className="space-y-2">
                    <Label htmlFor="scheduled_at">Schedule Time *</Label>
                    <Input
                      id="scheduled_at"
                      type="datetime-local"
                      value={formData.scheduled_at || ''}
                      onChange={(e) => setFormData({ ...formData, scheduled_at: e.target.value })}
                      required={formData.status === 'Scheduled'}
                    />
                  </div>
                )}
              </div>

              <div className="flex justify-end gap-2 pt-4">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => {
                    resetForm();
                    setIsDialogOpen(false);
                  }}
                >
                  Cancel
                </Button>
                <Button type="submit" disabled={loading}>
                  {loading ? (
                    'Processing...'
                  ) : (
                    <>
                      <FiSend className="mr-2 h-4 w-4" />
                      {editingNotification ? 'Update' : formData.status === 'Sent' ? 'Send' : 'Save'}
                    </>
                  )}
                </Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <FiCheck className="h-6 w-6 text-green-600" />
              </div>
              <div>
                <p className="text-2xl font-bold">{sentNotifications.length}</p>
                <p className="text-sm text-gray-600">Sent</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <FiClock className="h-6 w-6 text-blue-600" />
              </div>
              <div>
                <p className="text-2xl font-bold">{scheduledNotifications.length}</p>
                <p className="text-sm text-gray-600">Scheduled</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <FiEdit className="h-6 w-6 text-gray-600" />
              </div>
              <div>
                <p className="text-2xl font-bold">{draftNotifications.length}</p>
                <p className="text-sm text-gray-600">Drafts</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <FiBell className="h-6 w-6 text-purple-600" />
              </div>
              <div>
                <p className="text-2xl font-bold">{notifications.length}</p>
                <p className="text-sm text-gray-600">Total</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Notifications Tabs */}
      <Tabs defaultValue="all" className="w-full">
        <TabsList>
          <TabsTrigger value="all">All Notifications</TabsTrigger>
          <TabsTrigger value="sent">Sent</TabsTrigger>
          <TabsTrigger value="scheduled">Scheduled</TabsTrigger>
          <TabsTrigger value="drafts">Drafts</TabsTrigger>
        </TabsList>

        <TabsContent value="all">
          <NotificationTable
            notifications={notifications}
            onEdit={handleEdit}
            onDelete={handleDelete}
            getPriorityColor={getPriorityColor}
            getStatusColor={getStatusColor}
          />
        </TabsContent>

        <TabsContent value="sent">
          <NotificationTable
            notifications={sentNotifications}
            onEdit={handleEdit}
            onDelete={handleDelete}
            getPriorityColor={getPriorityColor}
            getStatusColor={getStatusColor}
          />
        </TabsContent>

        <TabsContent value="scheduled">
          <NotificationTable
            notifications={scheduledNotifications}
            onEdit={handleEdit}
            onDelete={handleDelete}
            getPriorityColor={getPriorityColor}
            getStatusColor={getStatusColor}
          />
        </TabsContent>

        <TabsContent value="drafts">
          <NotificationTable
            notifications={draftNotifications}
            onEdit={handleEdit}
            onDelete={handleDelete}
            getPriorityColor={getPriorityColor}
            getStatusColor={getStatusColor}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
};

const NotificationTable = ({ notifications, onEdit, onDelete, getPriorityColor, getStatusColor }) => {
  if (notifications.length === 0) {
    return (
      <Card>
        <CardContent className="flex flex-col items-center justify-center py-12">
          <FiBell className="h-12 w-12 text-gray-400 mb-4" />
          <p className="text-gray-600">No notifications found</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardContent className="p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Title</TableHead>
              <TableHead>Recipients</TableHead>
              <TableHead>Priority</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Read Count</TableHead>
              <TableHead>Date</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {notifications.map((notification) => (
              <TableRow key={notification.id}>
                <TableCell className="font-medium">{notification.title}</TableCell>
                <TableCell>
                  <div className="flex items-center gap-1">
                    {notification.recipient_type === 'All' && <FiUsers className="h-4 w-4" />}
                    {notification.recipient_type === 'Individual' && <FiUser className="h-4 w-4" />}
                    <span className="text-sm">{notification.recipient_type}</span>
                    {notification.class_name && (
                      <span className="text-xs text-gray-500">({notification.class_name})</span>
                    )}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge className={getPriorityColor(notification.priority)}>{notification.priority}</Badge>
                </TableCell>
                <TableCell>
                  <Badge className={getStatusColor(notification.status)}>{notification.status}</Badge>
                </TableCell>
                <TableCell>{notification.read_count || 0}</TableCell>
                <TableCell className="text-sm text-gray-600">
                  {new Date(notification.created_at).toLocaleDateString()}
                </TableCell>
                <TableCell className="text-right">
                  <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" onClick={() => onEdit(notification)}>
                      <FiEdit className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => onDelete(notification.id)}>
                      <FiTrash2 className="h-4 w-4 text-red-600" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
};

export default NotificationsManagement;
