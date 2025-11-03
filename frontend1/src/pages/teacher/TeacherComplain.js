import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import axios from "../../redux/axiosConfig";
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogCancel,
} from "@/components/ui/alert-dialog";
import {
  MdFilterList,
  MdRefresh,
  MdAdd,
  MdSearch,
  MdWarning,
  MdCheckCircle,
  MdPending,
  MdCancel,
} from "react-icons/md";
import { useSelector } from "react-redux";

const TeacherComplain = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [complaints, setComplaints] = useState([]);
  const [filteredComplaints, setFilteredComplaints] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedComplaint, setSelectedComplaint] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [createDialogOpen, setCreateDialogOpen] = useState(false);

  // Filters
  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [priorityFilter, setPriorityFilter] = useState("all");
  const [categoryFilter, setCategoryFilter] = useState("all");

  // New complaint form
  const [newComplaint, setNewComplaint] = useState({
    title: "",
    complaint: "",
    category: "general",
    priority: "medium",
  });

  useEffect(() => {
    fetchComplaints();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [complaints, searchTerm, statusFilter, priorityFilter, categoryFilter]);

  const fetchComplaints = async () => {
    try {
      setLoading(true);
      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/complaints/my`
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

  const applyFilters = () => {
    let filtered = [...complaints];

    // Search filter
    if (searchTerm) {
      filtered = filtered.filter(
        (c) =>
          c.title?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          c.complaint?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Status filter
    if (statusFilter !== "all") {
      filtered = filtered.filter((c) => c.status === statusFilter);
    }

    // Priority filter
    if (priorityFilter !== "all") {
      filtered = filtered.filter((c) => c.priority === priorityFilter);
    }

    // Category filter
    if (categoryFilter !== "all") {
      filtered = filtered.filter((c) => c.category === categoryFilter);
    }

    setFilteredComplaints(filtered);
  };

  const handleSubmitComplaint = async (e) => {
    e.preventDefault();

    if (!newComplaint.complaint.trim()) {
      toast.error("Please enter a complaint description");
      return;
    }

    try {
      const response = await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/complaints`,
        newComplaint
      );

      if (response.data.success) {
        toast.success("Complaint submitted successfully");
        setCreateDialogOpen(false);
        setNewComplaint({
          title: "",
          complaint: "",
          category: "general",
          priority: "medium",
        });
        fetchComplaints();
      }
    } catch (error) {
      toast.error("Failed to submit complaint", {
        description: error.response?.data?.message || "Network error",
      });
    }
  };

  const getStatusBadge = (status) => {
    const variants = {
      pending: { bg: "bg-yellow-100 text-yellow-800 border-yellow-300", icon: MdPending },
      in_progress: { bg: "bg-blue-100 text-blue-800 border-blue-300", icon: MdRefresh },
      resolved: { bg: "bg-green-100 text-green-800 border-green-300", icon: MdCheckCircle },
      closed: { bg: "bg-gray-100 text-gray-800 border-gray-300", icon: MdCancel },
    };

    const labels = {
      pending: "Pending",
      in_progress: "In Progress",
      resolved: "Resolved",
      closed: "Closed",
    };

    const { bg, icon: Icon } = variants[status] || variants.pending;

    return (
      <Badge className={bg}>
        <Icon className="w-3 h-3 mr-1" />
        {labels[status] || status}
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
      administrative: "bg-purple-100 text-purple-800",
      other: "bg-gray-100 text-gray-800",
    };

    return <Badge className={variants[category]}>{category.toUpperCase()}</Badge>;
  };

  const handleViewDetails = (complaint) => {
    setSelectedComplaint(complaint);
    setDetailsOpen(true);
  };

  const clearFilters = () => {
    setSearchTerm("");
    setStatusFilter("all");
    setPriorityFilter("all");
    setCategoryFilter("all");
  };

  const stats = {
    total: complaints.length,
    pending: complaints.filter((c) => c.status === "pending").length,
    inProgress: complaints.filter((c) => c.status === "in_progress").length,
    resolved: complaints.filter((c) => c.status === "resolved").length,
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Complaints Management</h1>
          <p className="text-gray-600 mt-1">View and manage your complaints</p>
        </div>
        <Button onClick={() => setCreateDialogOpen(true)} className="flex items-center gap-2">
          <MdAdd className="w-5 h-5" />
          Submit Complaint
        </Button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600 flex items-center gap-2">
              <MdWarning className="w-4 h-4" />
              Total Complaints
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-800">{stats.total}</div>
          </CardContent>
        </Card>

        <Card className="hover:shadow-lg transition-shadow border-l-4 border-l-yellow-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600 flex items-center gap-2">
              <MdPending className="w-4 h-4 text-yellow-600" />
              Pending
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-yellow-600">{stats.pending}</div>
          </CardContent>
        </Card>

        <Card className="hover:shadow-lg transition-shadow border-l-4 border-l-blue-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600 flex items-center gap-2">
              <MdRefresh className="w-4 h-4 text-blue-600" />
              In Progress
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-blue-600">{stats.inProgress}</div>
          </CardContent>
        </Card>

        <Card className="hover:shadow-lg transition-shadow border-l-4 border-l-green-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600 flex items-center gap-2">
              <MdCheckCircle className="w-4 h-4 text-green-600" />
              Resolved
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-green-600">{stats.resolved}</div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MdFilterList className="w-5 h-5" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {/* Search */}
            <div className="lg:col-span-2">
              <Label htmlFor="search">Search</Label>
              <div className="relative">
                <MdSearch className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <Input
                  id="search"
                  placeholder="Search by title or description..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>

            {/* Status Filter */}
            <div>
              <Label>Status</Label>
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Status</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="in_progress">In Progress</SelectItem>
                  <SelectItem value="resolved">Resolved</SelectItem>
                  <SelectItem value="closed">Closed</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Priority Filter */}
            <div>
              <Label>Priority</Label>
              <Select value={priorityFilter} onValueChange={setPriorityFilter}>
                <SelectTrigger>
                  <SelectValue />
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

            {/* Category Filter */}
            <div>
              <Label>Category</Label>
              <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Categories</SelectItem>
                  <SelectItem value="general">General</SelectItem>
                  <SelectItem value="academic">Academic</SelectItem>
                  <SelectItem value="disciplinary">Disciplinary</SelectItem>
                  <SelectItem value="facilities">Facilities</SelectItem>
                  <SelectItem value="administrative">Administrative</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="flex gap-2 mt-4">
            <Button variant="outline" onClick={clearFilters} size="sm">
              Clear Filters
            </Button>
            <Button variant="outline" onClick={fetchComplaints} size="sm">
              <MdRefresh className="w-4 h-4 mr-2" />
              Refresh
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Complaints List */}
      <Card>
        <CardHeader>
          <div className="flex justify-between items-center">
            <CardTitle>
              Complaints ({filteredComplaints.length})
            </CardTitle>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-12">
              <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
              <p className="mt-4 text-gray-600">Loading complaints...</p>
            </div>
          ) : filteredComplaints.length === 0 ? (
            <div className="text-center py-12 text-gray-500">
              <MdWarning className="w-16 h-16 mx-auto text-gray-300 mb-4" />
              <p className="text-lg font-medium mb-2">No complaints found</p>
              <p className="text-sm mb-4">
                {complaints.length === 0
                  ? "You haven't submitted any complaints yet."
                  : "No complaints match your current filters."}
              </p>
              {complaints.length === 0 && (
                <Button onClick={() => setCreateDialogOpen(true)}>
                  Submit Your First Complaint
                </Button>
              )}
            </div>
          ) : (
            <div className="space-y-4">
              {filteredComplaints.map((complaint) => (
                <Card
                  key={complaint.id}
                  className={`cursor-pointer hover:shadow-lg transition-all ${
                    complaint.priority === "urgent"
                      ? "border-l-4 border-l-red-500"
                      : complaint.priority === "high"
                      ? "border-l-4 border-l-orange-500"
                      : "border-l-4 border-l-blue-500"
                  }`}
                  onClick={() => handleViewDetails(complaint)}
                >
                  <CardContent className="pt-6">
                    <div className="flex justify-between items-start mb-3">
                      <div className="flex gap-2 flex-wrap">
                        {getStatusBadge(complaint.status)}
                        {getPriorityBadge(complaint.priority)}
                        {getCategoryBadge(complaint.category)}
                      </div>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={(e) => {
                          e.stopPropagation();
                          handleViewDetails(complaint);
                        }}
                      >
                        View Details
                      </Button>
                    </div>

                    {complaint.title && (
                      <h3 className="font-semibold text-lg mb-2">
                        {complaint.title}
                      </h3>
                    )}

                    <p className="text-gray-700 mb-3 line-clamp-2">
                      {complaint.complaint}
                    </p>

                    <div className="grid grid-cols-2 gap-2 text-sm text-gray-600">
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
                        <p className="text-sm font-medium text-green-900 flex items-center gap-2">
                          <MdCheckCircle className="w-4 h-4" />
                          Admin Response:
                        </p>
                        <p className="text-sm text-green-800 mt-1">
                          {complaint.response}
                        </p>
                      </div>
                    )}
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Create Complaint Dialog */}
      <AlertDialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
        <AlertDialogContent className="max-w-2xl">
          <AlertDialogHeader>
            <AlertDialogTitle>Submit New Complaint</AlertDialogTitle>
            <AlertDialogDescription>
              Fill out the form below to submit a new complaint. All fields marked with * are required.
            </AlertDialogDescription>
          </AlertDialogHeader>

          <form onSubmit={handleSubmitComplaint} className="space-y-4">
            <div>
              <Label htmlFor="title">Title (Optional)</Label>
              <Input
                id="title"
                placeholder="Brief title for your complaint"
                value={newComplaint.title}
                onChange={(e) =>
                  setNewComplaint({ ...newComplaint, title: e.target.value })
                }
              />
            </div>

            <div>
              <Label htmlFor="complaint">Complaint Description *</Label>
              <Textarea
                id="complaint"
                placeholder="Describe your complaint in detail..."
                value={newComplaint.complaint}
                onChange={(e) =>
                  setNewComplaint({ ...newComplaint, complaint: e.target.value })
                }
                rows={5}
                required
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="category">Category</Label>
                <Select
                  value={newComplaint.category}
                  onValueChange={(value) =>
                    setNewComplaint({ ...newComplaint, category: value })
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="general">General</SelectItem>
                    <SelectItem value="academic">Academic</SelectItem>
                    <SelectItem value="disciplinary">Disciplinary</SelectItem>
                    <SelectItem value="facilities">Facilities</SelectItem>
                    <SelectItem value="administrative">Administrative</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label htmlFor="priority">Priority</Label>
                <Select
                  value={newComplaint.priority}
                  onValueChange={(value) =>
                    setNewComplaint({ ...newComplaint, priority: value })
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">Low</SelectItem>
                    <SelectItem value="medium">Medium</SelectItem>
                    <SelectItem value="high">High</SelectItem>
                    <SelectItem value="urgent">Urgent</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <AlertDialogFooter>
              <AlertDialogCancel>Cancel</AlertDialogCancel>
              <Button type="submit">Submit Complaint</Button>
            </AlertDialogFooter>
          </form>
        </AlertDialogContent>
      </AlertDialog>

      {/* Details Dialog */}
      <AlertDialog open={detailsOpen} onOpenChange={setDetailsOpen}>
        <AlertDialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
          <AlertDialogHeader>
            <AlertDialogTitle>Complaint Details</AlertDialogTitle>
          </AlertDialogHeader>

          {selectedComplaint && (
            <div className="space-y-4">
              <div className="flex gap-2 flex-wrap">
                {getStatusBadge(selectedComplaint.status)}
                {getPriorityBadge(selectedComplaint.priority)}
                {getCategoryBadge(selectedComplaint.category)}
              </div>

              {selectedComplaint.title && (
                <div>
                  <h3 className="font-semibold text-sm text-gray-600">Title</h3>
                  <p className="text-lg font-medium">{selectedComplaint.title}</p>
                </div>
              )}

              <div>
                <h3 className="font-semibold text-sm text-gray-600 mb-1">
                  Complaint Description
                </h3>
                <p className="text-gray-800 whitespace-pre-wrap">{selectedComplaint.complaint}</p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <h3 className="font-semibold text-sm text-gray-600 mb-1">
                    Submitted On
                  </h3>
                  <p className="text-gray-800">
                    {new Date(selectedComplaint.created_at).toLocaleString()}
                  </p>
                </div>
                <div>
                  <h3 className="font-semibold text-sm text-gray-600 mb-1">
                    Last Updated
                  </h3>
                  <p className="text-gray-800">
                    {new Date(selectedComplaint.updated_at).toLocaleString()}
                  </p>
                </div>
              </div>

              {selectedComplaint.response && (
                <div className="p-4 bg-green-50 border border-green-200 rounded">
                  <h3 className="font-semibold text-sm text-green-900 mb-2 flex items-center gap-2">
                    <MdCheckCircle className="w-4 h-4" />
                    Admin Response
                  </h3>
                  <p className="text-green-800 whitespace-pre-wrap">{selectedComplaint.response}</p>
                </div>
              )}

              {!selectedComplaint.response && selectedComplaint.status === "pending" && (
                <div className="p-4 bg-yellow-50 border border-yellow-200 rounded">
                  <p className="text-sm text-yellow-800 flex items-center gap-2">
                    <MdPending className="w-4 h-4" />
                    Your complaint is pending review. You will be notified once the admin provides a response.
                  </p>
                </div>
              )}
            </div>
          )}

          <AlertDialogFooter>
            <Button onClick={() => setDetailsOpen(false)}>Close</Button>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default TeacherComplain;