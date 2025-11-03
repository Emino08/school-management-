import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { toast } from "sonner";
import axios from "@/redux/axiosConfig";
import {
  Bell,
  Plus,
  Edit,
  Trash2,
  Eye,
  Download,
  Search,
  Filter,
  Clock,
  Users,
  TrendingUp,
  Calendar,
  AlertCircle,
  CheckCircle,
  XCircle,
} from "lucide-react";

const NoticesManagement = () => {
  const navigate = useNavigate();
  const [notices, setNotices] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [filterPriority, setFilterPriority] = useState("all");
  const [filterCategory, setFilterCategory] = useState("all");

  // Dialog states
  const [viewDialogOpen, setViewDialogOpen] = useState(false);
  const [addDialogOpen, setAddDialogOpen] = useState(false);
  const [editDialogOpen, setEditDialogOpen] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [selectedNotice, setSelectedNotice] = useState(null);

  // Form states
  const [formData, setFormData] = useState({
    title: "",
    details: "",
    date: new Date().toISOString().split("T")[0],
    priority: "normal",
    category: "general",
    target_audience: "all",
    expiry_date: "",
  });

  useEffect(() => {
    fetchNotices();
    fetchStats();
  }, [filterPriority, filterCategory]);

  const fetchNotices = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (filterCategory && filterCategory !== "all") params.append("category", filterCategory);
      if (filterPriority && filterPriority !== "all") params.append("priority", filterPriority);

      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/notices?${params}`
      );

      if (response.data.success) {
        setNotices(response.data.notices || []);
      }
    } catch (error) {
      toast.error("Failed to fetch notices", {
        description: error.response?.data?.message || "Network error",
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/notices/stats`
      );

      if (response.data.success) {
        setStats(response.data.stats);
      }
    } catch (error) {
      console.error("Failed to fetch stats:", error);
    }
  };

  const handleAddNotice = async () => {
    if (!formData.title || !formData.details) {
      toast.error("Please fill in all required fields");
      return;
    }

    try {
      const response = await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/notices`,
        formData
      );

      if (response.data.success) {
        toast.success("Notice added successfully");
        setAddDialogOpen(false);
        resetForm();
        fetchNotices();
        fetchStats();
      }
    } catch (error) {
      toast.error("Failed to add notice", {
        description: error.response?.data?.message || "Network error",
      });
    }
  };

  const handleEditNotice = async () => {
    if (!formData.title || !formData.details) {
      toast.error("Please fill in all required fields");
      return;
    }

    try {
      const response = await axios.put(
        `${import.meta.env.VITE_API_BASE_URL}/notices/${selectedNotice.id}`,
        formData
      );

      if (response.data.success) {
        toast.success("Notice updated successfully");
        setEditDialogOpen(false);
        setSelectedNotice(null);
        resetForm();
        fetchNotices();
      }
    } catch (error) {
      toast.error("Failed to update notice", {
        description: error.response?.data?.message || "Network error",
      });
    }
  };

  const handleDeleteNotice = async () => {
    try {
      const response = await axios.delete(
        `${import.meta.env.VITE_API_BASE_URL}/notices/${selectedNotice.id}`
      );

      if (response.data.success) {
        toast.success("Notice deleted successfully");
        setDeleteDialogOpen(false);
        setSelectedNotice(null);
        fetchNotices();
        fetchStats();
      }
    } catch (error) {
      toast.error("Failed to delete notice", {
        description: error.response?.data?.message || "Network error",
      });
    }
  };

  const exportToCSV = () => {
    if (filteredNotices.length === 0) {
      toast.error("No notices to export");
      return;
    }

    const headers = [
      "Title",
      "Category",
      "Priority",
      "Date",
      "Target Audience",
      "Details",
    ];
    const rows = filteredNotices.map((n) => [
      n.title,
      n.category || "general",
      n.priority || "normal",
      new Date(n.date).toLocaleDateString(),
      n.target_audience || "all",
      n.details,
    ]);

    const csv = [headers, ...rows].map((row) => row.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `notices_${new Date().toISOString().split("T")[0]}.csv`;
    link.click();
    URL.revokeObjectURL(url);
    toast.success("Notices exported successfully");
  };

  const resetForm = () => {
    setFormData({
      title: "",
      details: "",
      date: new Date().toISOString().split("T")[0],
      priority: "normal",
      category: "general",
      target_audience: "all",
      expiry_date: "",
    });
  };

  const getPriorityBadge = (priority) => {
    const variants = {
      low: "bg-gray-100 text-gray-800",
      normal: "bg-blue-100 text-blue-800",
      high: "bg-orange-100 text-orange-800",
      urgent: "bg-red-100 text-red-800",
    };

    return <Badge className={variants[priority || "normal"]}>{(priority || "normal").toUpperCase()}</Badge>;
  };

  const getCategoryBadge = (category) => {
    const variants = {
      general: "bg-gray-100 text-gray-800",
      academic: "bg-blue-100 text-blue-800",
      event: "bg-purple-100 text-purple-800",
      holiday: "bg-green-100 text-green-800",
      exam: "bg-orange-100 text-orange-800",
      other: "bg-gray-100 text-gray-800",
    };

    return <Badge className={variants[category || "general"]}>{(category || "general").toUpperCase()}</Badge>;
  };

  const getStatusBadge = (notice) => {
    const now = new Date();
    const noticeDate = new Date(notice.date);
    const expiryDate = notice.expiry_date ? new Date(notice.expiry_date) : null;

    if (expiryDate && now > expiryDate) {
      return (
        <Badge className="bg-gray-100 text-gray-800 flex items-center gap-1">
          <XCircle className="w-3 h-3" />
          Expired
        </Badge>
      );
    } else if (now >= noticeDate) {
      return (
        <Badge className="bg-green-100 text-green-800 flex items-center gap-1">
          <CheckCircle className="w-3 h-3" />
          Active
        </Badge>
      );
    } else {
      return (
        <Badge className="bg-blue-100 text-blue-800 flex items-center gap-1">
          <Clock className="w-3 h-3" />
          Scheduled
        </Badge>
      );
    }
  };

  const filteredNotices = notices.filter((notice) => {
    if (searchQuery) {
      const search = searchQuery.toLowerCase();
      return (
        notice.title?.toLowerCase().includes(search) ||
        notice.details?.toLowerCase().includes(search)
      );
    }
    return true;
  });

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-3">
            <div className="p-2 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg">
              <Bell className="w-8 h-8 text-white" />
            </div>
            Notices Management
          </h1>
          <p className="text-gray-600 mt-1">Create and manage school notices</p>
        </div>

        <div className="flex gap-2">
          <Button onClick={exportToCSV} variant="outline" className="gap-2">
            <Download className="w-4 h-4" />
            Export
          </Button>
          <Button onClick={() => setAddDialogOpen(true)} className="gap-2">
            <Plus className="w-4 h-4" />
            Add Notice
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-blue-600">Total Notices</p>
                  <h3 className="text-2xl font-bold text-blue-900">
                    {stats.total || notices.length}
                  </h3>
                </div>
                <div className="p-3 bg-blue-500 rounded-lg">
                  <Bell className="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-green-600">Active</p>
                  <h3 className="text-2xl font-bold text-green-900">
                    {stats.active || notices.filter(n => {
                      const now = new Date();
                      const noticeDate = new Date(n.date);
                      const expiry = n.expiry_date ? new Date(n.expiry_date) : null;
                      return now >= noticeDate && (!expiry || now <= expiry);
                    }).length}
                  </h3>
                </div>
                <div className="p-3 bg-green-500 rounded-lg">
                  <CheckCircle className="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-orange-600">This Week</p>
                  <h3 className="text-2xl font-bold text-orange-900">
                    {notices.filter(n => {
                      const weekAgo = new Date();
                      weekAgo.setDate(weekAgo.getDate() - 7);
                      return new Date(n.date) >= weekAgo;
                    }).length}
                  </h3>
                </div>
                <div className="p-3 bg-orange-500 rounded-lg">
                  <Calendar className="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-purple-600">Urgent</p>
                  <h3 className="text-2xl font-bold text-purple-900">
                    {notices.filter(n => n.priority === "urgent").length}
                  </h3>
                </div>
                <div className="p-3 bg-purple-500 rounded-lg">
                  <AlertCircle className="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filters and Search */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="w-5 h-5" />
            Filters & Search
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label>Search</Label>
              <div className="relative">
                <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search notices..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>

            <div>
              <Label>Category</Label>
              <Select value={filterCategory} onValueChange={setFilterCategory}>
                <SelectTrigger>
                  <SelectValue placeholder="All Categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Categories</SelectItem>
                  <SelectItem value="general">General</SelectItem>
                  <SelectItem value="academic">Academic</SelectItem>
                  <SelectItem value="event">Event</SelectItem>
                  <SelectItem value="holiday">Holiday</SelectItem>
                  <SelectItem value="exam">Exam</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Priority</Label>
              <Select value={filterPriority} onValueChange={setFilterPriority}>
                <SelectTrigger>
                  <SelectValue placeholder="All Priorities" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Priorities</SelectItem>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="normal">Normal</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Notices List */}
      <Card>
        <CardHeader>
          <CardTitle>All Notices ({filteredNotices.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">Loading notices...</div>
          ) : filteredNotices.length === 0 ? (
            <div className="text-center py-12 text-gray-500">
              <Bell className="w-12 h-12 mx-auto mb-4 opacity-30" />
              <p className="text-lg font-medium">No notices found</p>
              <Button
                variant="outline"
                className="mt-4"
                onClick={() => setAddDialogOpen(true)}
              >
                <Plus className="w-4 h-4 mr-2" />
                Add Your First Notice
              </Button>
            </div>
          ) : (
            <div className="space-y-4">
              {filteredNotices.map((notice) => (
                <Card
                  key={notice.id}
                  className="border-l-4 hover:shadow-md transition-shadow"
                  style={{
                    borderLeftColor:
                      notice.priority === "urgent"
                        ? "#ef4444"
                        : notice.priority === "high"
                        ? "#f97316"
                        : "#3b82f6",
                  }}
                >
                  <CardContent className="pt-6">
                    <div className="flex justify-between items-start mb-3">
                      <div className="flex gap-2 flex-wrap">
                        {getStatusBadge(notice)}
                        {getPriorityBadge(notice.priority)}
                        {getCategoryBadge(notice.category)}
                      </div>
                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => {
                            setSelectedNotice(notice);
                            setViewDialogOpen(true);
                          }}
                        >
                          <Eye className="w-4 h-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => {
                            setSelectedNotice(notice);
                            setFormData({
                              title: notice.title,
                              details: notice.details,
                              date: notice.date,
                              priority: notice.priority || "normal",
                              category: notice.category || "general",
                              target_audience: notice.target_audience || "all",
                              expiry_date: notice.expiry_date || "",
                            });
                            setEditDialogOpen(true);
                          }}
                        >
                          <Edit className="w-4 h-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => {
                            setSelectedNotice(notice);
                            setDeleteDialogOpen(true);
                          }}
                        >
                          <Trash2 className="w-4 h-4 text-red-600" />
                        </Button>
                      </div>
                    </div>

                    <h3 className="font-semibold text-lg mb-2">{notice.title}</h3>
                    <p className="text-gray-700 mb-3 line-clamp-2">{notice.details}</p>

                    <div className="flex items-center gap-4 text-sm text-gray-600">
                      <div className="flex items-center gap-1">
                        <Calendar className="w-4 h-4" />
                        {new Date(notice.date).toLocaleDateString()}
                      </div>
                      {notice.target_audience && (
                        <div className="flex items-center gap-1">
                          <Users className="w-4 h-4" />
                          {notice.target_audience}
                        </div>
                      )}
                      {notice.expiry_date && (
                        <div className="flex items-center gap-1">
                          <Clock className="w-4 h-4" />
                          Expires: {new Date(notice.expiry_date).toLocaleDateString()}
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Add Notice Dialog */}
      <AlertDialog open={addDialogOpen} onOpenChange={setAddDialogOpen}>
        <AlertDialogContent className="max-w-2xl">
          <AlertDialogHeader>
            <AlertDialogTitle>Add New Notice</AlertDialogTitle>
            <AlertDialogDescription>
              Create a new notice to inform students and staff
            </AlertDialogDescription>
          </AlertDialogHeader>

          <div className="space-y-4 py-4">
            <div>
              <Label>Title *</Label>
              <Input
                placeholder="Notice title"
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              />
            </div>

            <div>
              <Label>Details *</Label>
              <Textarea
                placeholder="Notice details"
                value={formData.details}
                onChange={(e) => setFormData({ ...formData, details: e.target.value })}
                rows={4}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Category</Label>
                <Select
                  value={formData.category}
                  onValueChange={(value) => setFormData({ ...formData, category: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="general">General</SelectItem>
                    <SelectItem value="academic">Academic</SelectItem>
                    <SelectItem value="event">Event</SelectItem>
                    <SelectItem value="holiday">Holiday</SelectItem>
                    <SelectItem value="exam">Exam</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label>Priority</Label>
                <Select
                  value={formData.priority}
                  onValueChange={(value) => setFormData({ ...formData, priority: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">Low</SelectItem>
                    <SelectItem value="normal">Normal</SelectItem>
                    <SelectItem value="high">High</SelectItem>
                    <SelectItem value="urgent">Urgent</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Date *</Label>
                <Input
                  type="date"
                  value={formData.date}
                  onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                />
              </div>

              <div>
                <Label>Expiry Date (Optional)</Label>
                <Input
                  type="date"
                  value={formData.expiry_date}
                  onChange={(e) => setFormData({ ...formData, expiry_date: e.target.value })}
                />
              </div>
            </div>

            <div>
              <Label>Target Audience</Label>
              <Select
                value={formData.target_audience}
                onValueChange={(value) => setFormData({ ...formData, target_audience: value })}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All</SelectItem>
                  <SelectItem value="students">Students Only</SelectItem>
                  <SelectItem value="teachers">Teachers Only</SelectItem>
                  <SelectItem value="parents">Parents Only</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <AlertDialogFooter>
            <AlertDialogCancel onClick={resetForm}>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleAddNotice}>Add Notice</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Edit Notice Dialog */}
      <AlertDialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
        <AlertDialogContent className="max-w-2xl">
          <AlertDialogHeader>
            <AlertDialogTitle>Edit Notice</AlertDialogTitle>
            <AlertDialogDescription>Update the notice details</AlertDialogDescription>
          </AlertDialogHeader>

          <div className="space-y-4 py-4">
            <div>
              <Label>Title *</Label>
              <Input
                placeholder="Notice title"
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              />
            </div>

            <div>
              <Label>Details *</Label>
              <Textarea
                placeholder="Notice details"
                value={formData.details}
                onChange={(e) => setFormData({ ...formData, details: e.target.value })}
                rows={4}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Category</Label>
                <Select
                  value={formData.category}
                  onValueChange={(value) => setFormData({ ...formData, category: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="general">General</SelectItem>
                    <SelectItem value="academic">Academic</SelectItem>
                    <SelectItem value="event">Event</SelectItem>
                    <SelectItem value="holiday">Holiday</SelectItem>
                    <SelectItem value="exam">Exam</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label>Priority</Label>
                <Select
                  value={formData.priority}
                  onValueChange={(value) => setFormData({ ...formData, priority: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">Low</SelectItem>
                    <SelectItem value="normal">Normal</SelectItem>
                    <SelectItem value="high">High</SelectItem>
                    <SelectItem value="urgent">Urgent</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Date *</Label>
                <Input
                  type="date"
                  value={formData.date}
                  onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                />
              </div>

              <div>
                <Label>Expiry Date (Optional)</Label>
                <Input
                  type="date"
                  value={formData.expiry_date}
                  onChange={(e) => setFormData({ ...formData, expiry_date: e.target.value })}
                />
              </div>
            </div>

            <div>
              <Label>Target Audience</Label>
              <Select
                value={formData.target_audience}
                onValueChange={(value) => setFormData({ ...formData, target_audience: value })}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All</SelectItem>
                  <SelectItem value="students">Students Only</SelectItem>
                  <SelectItem value="teachers">Teachers Only</SelectItem>
                  <SelectItem value="parents">Parents Only</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => {
              setEditDialogOpen(false);
              resetForm();
            }}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction onClick={handleEditNotice}>Update Notice</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* View Notice Dialog */}
      <AlertDialog open={viewDialogOpen} onOpenChange={setViewDialogOpen}>
        <AlertDialogContent className="max-w-2xl">
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2">
              <Eye className="w-5 h-5" />
              Notice Details
            </AlertDialogTitle>
          </AlertDialogHeader>

          {selectedNotice && (
            <div className="space-y-4">
              <div className="flex gap-2 flex-wrap">
                {getStatusBadge(selectedNotice)}
                {getPriorityBadge(selectedNotice.priority)}
                {getCategoryBadge(selectedNotice.category)}
              </div>

              <div>
                <Label>Title</Label>
                <p className="font-semibold text-lg">{selectedNotice.title}</p>
              </div>

              <div>
                <Label>Details</Label>
                <p className="text-gray-700 whitespace-pre-wrap">{selectedNotice.details}</p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label>Date</Label>
                  <p>{new Date(selectedNotice.date).toLocaleDateString()}</p>
                </div>

                {selectedNotice.expiry_date && (
                  <div>
                    <Label>Expires On</Label>
                    <p>{new Date(selectedNotice.expiry_date).toLocaleDateString()}</p>
                  </div>
                )}
              </div>

              {selectedNotice.target_audience && (
                <div>
                  <Label>Target Audience</Label>
                  <p className="capitalize">{selectedNotice.target_audience}</p>
                </div>
              )}
            </div>
          )}

          <AlertDialogFooter>
            <AlertDialogCancel>Close</AlertDialogCancel>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Delete Notice Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Notice</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this notice? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>

          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteNotice}
              className="bg-red-600 hover:bg-red-700"
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default NoticesManagement;
