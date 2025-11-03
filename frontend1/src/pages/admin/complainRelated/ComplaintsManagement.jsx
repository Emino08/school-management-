import { useEffect, useState } from "react";
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
  MessageSquare,
  AlertCircle,
  CheckCircle,
  Clock,
  XCircle,
  Download,
  Search,
  Filter,
  TrendingUp,
  Users,
  AlertTriangle,
  Eye,
  Send,
  Trash2,
} from "lucide-react";

const ComplaintsManagement = () => {
  const [complaints, setComplaints] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("all");
  const [filterStatus, setFilterStatus] = useState("all");
  const [filterCategory, setFilterCategory] = useState("all");
  const [filterPriority, setFilterPriority] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedComplaint, setSelectedComplaint] = useState(null);
  const [viewDialogOpen, setViewDialogOpen] = useState(false);
  const [updateDialogOpen, setUpdateDialogOpen] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [newStatus, setNewStatus] = useState("");
  const [responseText, setResponseText] = useState("");

  useEffect(() => {
    fetchComplaints();
    fetchStats();
  }, [filterStatus, filterCategory, filterPriority]);

  const fetchComplaints = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (filterStatus && filterStatus !== "all") params.append("status", filterStatus);
      if (filterCategory && filterCategory !== "all") params.append("category", filterCategory);
      if (filterPriority && filterPriority !== "all") params.append("priority", filterPriority);

      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/complaints?${params}`
      );

      if (response.data.success) {
        setComplaints(response.data.complaints);
      }
    } catch (error) {
      toast.error("Failed to fetch complaints", {
        description: error.response?.data?.message || "Network error",
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/complaints/stats`
      );

      if (response.data.success) {
        setStats(response.data.stats);
      }
    } catch (error) {
      console.error("Failed to fetch stats:", error);
    }
  };

  const handleUpdateStatus = async () => {
    if (!newStatus) {
      toast.error("Please select a status");
      return;
    }

    try {
      const response = await axios.put(
        `${import.meta.env.VITE_API_BASE_URL}/complaints/${selectedComplaint.id}/status`,
        {
          status: newStatus,
          response: responseText.trim() || null,
        }
      );

      if (response.data.success) {
        toast.success("Complaint status updated successfully");
        setUpdateDialogOpen(false);
        setSelectedComplaint(null);
        setNewStatus("");
        setResponseText("");
        fetchComplaints();
        fetchStats();
      }
    } catch (error) {
      toast.error("Failed to update complaint", {
        description: error.response?.data?.message || "Network error",
      });
    }
  };

  const handleDelete = async () => {
    try {
      const response = await axios.delete(
        `${import.meta.env.VITE_API_BASE_URL}/complaints/${selectedComplaint.id}`
      );

      if (response.data.success) {
        toast.success("Complaint deleted successfully");
        setDeleteDialogOpen(false);
        setSelectedComplaint(null);
        fetchComplaints();
        fetchStats();
      }
    } catch (error) {
      toast.error("Failed to delete complaint", {
        description: error.response?.data?.message || "Network error",
      });
    }
  };

  const exportToCSV = () => {
    if (filteredComplaints.length === 0) {
      toast.error("No complaints to export");
      return;
    }

    const headers = [
      "ID",
      "Title",
      "Category",
      "Priority",
      "Status",
      "From",
      "Submitted",
      "Description",
    ];
    const rows = filteredComplaints.map((c) => [
      c.id,
      c.title || "N/A",
      c.category,
      c.priority,
      c.status,
      c.student_name || c.teacher_name,
      new Date(c.created_at).toLocaleDateString(),
      c.complaint,
    ]);

    const csv = [headers, ...rows].map((row) => row.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `complaints_${new Date().toISOString().split("T")[0]}.csv`;
    link.click();
    URL.revokeObjectURL(url);
    toast.success("Complaints exported successfully");
  };

  const getStatusIcon = (status) => {
    const icons = {
      pending: <Clock className="w-4 h-4" />,
      in_progress: <AlertCircle className="w-4 h-4" />,
      resolved: <CheckCircle className="w-4 h-4" />,
      closed: <XCircle className="w-4 h-4" />,
    };
    return icons[status];
  };

  const getStatusBadge = (status) => {
    const variants = {
      pending: "bg-yellow-100 text-yellow-800 border-yellow-300",
      in_progress: "bg-blue-100 text-blue-800 border-blue-300",
      resolved: "bg-green-100 text-green-800 border-green-300",
      closed: "bg-gray-100 text-gray-800 border-gray-300",
    };

    return (
      <Badge className={`${variants[status]} flex items-center gap-1`}>
        {getStatusIcon(status)}
        {status.replace("_", " ").toUpperCase()}
      </Badge>
    );
  };

  const getPriorityBadge = (priority) => {
    const variants = {
      low: "bg-gray-100 text-gray-800",
      medium: "bg-blue-100 text-blue-800",
      high: "bg-orange-100 text-orange-800",
      urgent: "bg-red-100 text-red-800",
    };

    return <Badge className={variants[priority]}>{priority.toUpperCase()}</Badge>;
  };

  const getCategoryBadge = (category) => {
    const variants = {
      general: "bg-gray-100 text-gray-800",
      academic: "bg-blue-100 text-blue-800",
      disciplinary: "bg-red-100 text-red-800",
      facilities: "bg-green-100 text-green-800",
      teacher: "bg-purple-100 text-purple-800",
      other: "bg-gray-100 text-gray-800",
    };

    return <Badge className={variants[category]}>{category.toUpperCase()}</Badge>;
  };

  const filteredComplaints = complaints.filter((complaint) => {
    if (activeTab !== "all" && complaint.status !== activeTab) return false;
    if (searchQuery) {
      const search = searchQuery.toLowerCase();
      return (
        complaint.complaint?.toLowerCase().includes(search) ||
        complaint.title?.toLowerCase().includes(search) ||
        complaint.student_name?.toLowerCase().includes(search) ||
        complaint.teacher_name?.toLowerCase().includes(search)
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
            <div className="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg">
              <MessageSquare className="w-8 h-8 text-white" />
            </div>
            Complaints Management
          </h1>
          <p className="text-gray-600 mt-1">Monitor and manage student & teacher complaints</p>
        </div>

        <Button onClick={exportToCSV} variant="outline" className="gap-2">
          <Download className="w-4 h-4" />
          Export CSV
        </Button>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-blue-600">Total</p>
                  <h3 className="text-2xl font-bold text-blue-900">{stats.total}</h3>
                </div>
                <div className="p-3 bg-blue-500 rounded-lg">
                  <MessageSquare className="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-200">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-yellow-600">Pending</p>
                  <h3 className="text-2xl font-bold text-yellow-900">{stats.pending}</h3>
                </div>
                <div className="p-3 bg-yellow-500 rounded-lg">
                  <Clock className="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-blue-50 to-cyan-100 border-blue-200">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-blue-600">In Progress</p>
                  <h3 className="text-2xl font-bold text-blue-900">{stats.in_progress}</h3>
                </div>
                <div className="p-3 bg-blue-500 rounded-lg">
                  <TrendingUp className="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-red-600">Urgent</p>
                  <h3 className="text-2xl font-bold text-red-900">{stats.urgent}</h3>
                </div>
                <div className="p-3 bg-red-500 rounded-lg">
                  <AlertTriangle className="w-6 h-6 text-white" />
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
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <Label>Search</Label>
              <div className="relative">
                <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search complaints..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>

            <div>
              <Label>Status</Label>
              <Select value={filterStatus} onValueChange={setFilterStatus}>
                <SelectTrigger>
                  <SelectValue placeholder="All Statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="in_progress">In Progress</SelectItem>
                  <SelectItem value="resolved">Resolved</SelectItem>
                  <SelectItem value="closed">Closed</SelectItem>
                </SelectContent>
              </Select>
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
                  <SelectItem value="disciplinary">Disciplinary</SelectItem>
                  <SelectItem value="facilities">Facilities</SelectItem>
                  <SelectItem value="teacher">Teacher</SelectItem>
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
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Complaints Tabs */}
      <Card>
        <CardContent className="pt-6">
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <TabsList className="grid w-full grid-cols-5">
              <TabsTrigger value="all">All ({complaints.length})</TabsTrigger>
              <TabsTrigger value="pending">
                Pending ({complaints.filter((c) => c.status === "pending").length})
              </TabsTrigger>
              <TabsTrigger value="in_progress">
                In Progress ({complaints.filter((c) => c.status === "in_progress").length})
              </TabsTrigger>
              <TabsTrigger value="resolved">
                Resolved ({complaints.filter((c) => c.status === "resolved").length})
              </TabsTrigger>
              <TabsTrigger value="closed">
                Closed ({complaints.filter((c) => c.status === "closed").length})
              </TabsTrigger>
            </TabsList>

            <TabsContent value={activeTab} className="mt-6">
              {loading ? (
                <div className="text-center py-8">Loading complaints...</div>
              ) : filteredComplaints.length === 0 ? (
                <div className="text-center py-12 text-gray-500">
                  <MessageSquare className="w-12 h-12 mx-auto mb-4 opacity-30" />
                  <p className="text-lg font-medium">No complaints found</p>
                  <p className="text-sm">Try adjusting your filters</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {filteredComplaints.map((complaint) => (
                    <Card
                      key={complaint.id}
                      className="border-l-4 hover:shadow-md transition-shadow"
                      style={{
                        borderLeftColor:
                          complaint.priority === "urgent"
                            ? "#ef4444"
                            : complaint.priority === "high"
                            ? "#f97316"
                            : complaint.priority === "medium"
                            ? "#3b82f6"
                            : "#9ca3af",
                      }}
                    >
                      <CardContent className="pt-6">
                        <div className="flex justify-between items-start mb-3">
                          <div className="flex gap-2 flex-wrap">
                            {getStatusBadge(complaint.status)}
                            {getPriorityBadge(complaint.priority)}
                            {getCategoryBadge(complaint.category)}
                          </div>
                          <div className="flex gap-2">
                            <Button
                              size="sm"
                              variant="ghost"
                              onClick={() => {
                                setSelectedComplaint(complaint);
                                setViewDialogOpen(true);
                              }}
                            >
                              <Eye className="w-4 h-4" />
                            </Button>
                            <Button
                              size="sm"
                              variant="ghost"
                              onClick={() => {
                                setSelectedComplaint(complaint);
                                setNewStatus(complaint.status);
                                setResponseText(complaint.response || "");
                                setUpdateDialogOpen(true);
                              }}
                            >
                              <Send className="w-4 h-4" />
                            </Button>
                            <Button
                              size="sm"
                              variant="ghost"
                              onClick={() => {
                                setSelectedComplaint(complaint);
                                setDeleteDialogOpen(true);
                              }}
                            >
                              <Trash2 className="w-4 h-4 text-red-600" />
                            </Button>
                          </div>
                        </div>

                        {complaint.title && (
                          <h3 className="font-semibold text-lg mb-2">{complaint.title}</h3>
                        )}

                        <p className="text-gray-700 mb-3 line-clamp-2">{complaint.complaint}</p>

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-gray-600">
                          <div>
                            <span className="font-medium">From:</span>{" "}
                            {complaint.student_name || complaint.teacher_name}
                          </div>
                          {complaint.complained_teacher_name && (
                            <div>
                              <span className="font-medium">About:</span>{" "}
                              {complaint.complained_teacher_name}
                            </div>
                          )}
                          <div>
                            <span className="font-medium">Submitted:</span>{" "}
                            {new Date(complaint.created_at).toLocaleDateString()}
                          </div>
                          <div>
                            <span className="font-medium">Last Updated:</span>{" "}
                            {new Date(complaint.updated_at).toLocaleDateString()}
                          </div>
                        </div>

                        {complaint.response && (
                          <div className="mt-3 p-3 bg-green-50 border border-green-200 rounded">
                            <p className="text-sm font-medium text-green-700 flex items-center gap-2">
                              <CheckCircle className="w-4 h-4" />
                              Admin Response:
                            </p>
                            <p className="text-sm text-gray-700 mt-1">{complaint.response}</p>
                          </div>
                        )}
                      </CardContent>
                    </Card>
                  ))}
                </div>
              )}
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      {/* View Details Dialog */}
      <AlertDialog open={viewDialogOpen} onOpenChange={setViewDialogOpen}>
        <AlertDialogContent className="max-w-2xl">
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2">
              <Eye className="w-5 h-5" />
              Complaint Details
            </AlertDialogTitle>
          </AlertDialogHeader>

          {selectedComplaint && (
            <div className="space-y-4">
              <div className="flex gap-2">
                {getStatusBadge(selectedComplaint.status)}
                {getPriorityBadge(selectedComplaint.priority)}
                {getCategoryBadge(selectedComplaint.category)}
              </div>

              {selectedComplaint.title && (
                <div>
                  <Label>Title</Label>
                  <p className="font-semibold text-lg">{selectedComplaint.title}</p>
                </div>
              )}

              <div>
                <Label>Description</Label>
                <p className="text-gray-700 whitespace-pre-wrap">{selectedComplaint.complaint}</p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label>Submitted By</Label>
                  <p>{selectedComplaint.student_name || selectedComplaint.teacher_name}</p>
                  <p className="text-sm text-gray-500">
                    {selectedComplaint.student_id_number || selectedComplaint.teacher_id_number}
                  </p>
                </div>

                {selectedComplaint.complained_teacher_name && (
                  <div>
                    <Label>Regarding Teacher</Label>
                    <p>{selectedComplaint.complained_teacher_name}</p>
                  </div>
                )}
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label>Submitted On</Label>
                  <p>{new Date(selectedComplaint.created_at).toLocaleString()}</p>
                </div>
                <div>
                  <Label>Last Updated</Label>
                  <p>{new Date(selectedComplaint.updated_at).toLocaleString()}</p>
                </div>
              </div>

              {selectedComplaint.response && (
                <div>
                  <Label>Admin Response</Label>
                  <div className="p-3 bg-green-50 border border-green-200 rounded mt-2">
                    <p className="text-gray-700">{selectedComplaint.response}</p>
                  </div>
                </div>
              )}
            </div>
          )}

          <AlertDialogFooter>
            <AlertDialogCancel>Close</AlertDialogCancel>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Update Status Dialog */}
      <AlertDialog open={updateDialogOpen} onOpenChange={setUpdateDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Update Complaint Status</AlertDialogTitle>
            <AlertDialogDescription>
              Update the status and provide a response to this complaint.
            </AlertDialogDescription>
          </AlertDialogHeader>

          <div className="space-y-4 py-4">
            <div>
              <Label>Status</Label>
              <Select value={newStatus} onValueChange={setNewStatus}>
                <SelectTrigger>
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="in_progress">In Progress</SelectItem>
                  <SelectItem value="resolved">Resolved</SelectItem>
                  <SelectItem value="closed">Closed</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Response (Optional)</Label>
              <Textarea
                placeholder="Provide a response to the complainant..."
                value={responseText}
                onChange={(e) => setResponseText(e.target.value)}
                rows={4}
              />
            </div>
          </div>

          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleUpdateStatus}>Update</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Delete Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Complaint</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this complaint? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>

          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-red-600 hover:bg-red-700">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default ComplaintsManagement;
