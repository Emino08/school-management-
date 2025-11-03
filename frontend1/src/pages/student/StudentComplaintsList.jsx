import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";

const StudentComplaintsList = () => {
  const [complaints, setComplaints] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedComplaint, setSelectedComplaint] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    fetchComplaints();
  }, []);

  const fetchComplaints = async () => {
    try {
      setLoading(true);
      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/complaints/my`,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        }
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

  const getStatusBadge = (status) => {
    const variants = {
      pending: "bg-yellow-100 text-yellow-800 border-yellow-300",
      in_progress: "bg-blue-100 text-blue-800 border-blue-300",
      resolved: "bg-green-100 text-green-800 border-green-300",
      closed: "bg-gray-100 text-gray-800 border-gray-300",
    };

    const labels = {
      pending: "Pending",
      in_progress: "In Progress",
      resolved: "Resolved",
      closed: "Closed",
    };

    return (
      <Badge className={variants[status] || "bg-gray-100"}>
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
      teacher: "bg-purple-100 text-purple-800",
      other: "bg-gray-100 text-gray-800",
    };

    return <Badge className={variants[category]}>{category.toUpperCase()}</Badge>;
  };

  const handleViewDetails = (complaint) => {
    setSelectedComplaint(complaint);
    setDetailsOpen(true);
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">My Complaints</h1>
        <Button onClick={() => navigate("/Student/complain")}>
          Submit New Complaint
        </Button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Total
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{complaints.length}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Pending
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-yellow-600">
              {complaints.filter((c) => c.status === "pending").length}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              In Progress
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-blue-600">
              {complaints.filter((c) => c.status === "in_progress").length}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Resolved
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">
              {complaints.filter((c) => c.status === "resolved").length}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Complaints List */}
      <Card>
        <CardHeader>
          <CardTitle>All My Complaints</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">Loading complaints...</div>
          ) : complaints.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <p className="mb-4">You haven't submitted any complaints yet.</p>
              <Button onClick={() => navigate("/Student/complain")}>
                Submit Your First Complaint
              </Button>
            </div>
          ) : (
            <div className="space-y-4">
              {complaints.map((complaint) => (
                <Card
                  key={complaint.id}
                  className={`cursor-pointer hover:shadow-md transition-shadow ${
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
                      {complaint.teacher_name && (
                        <div>
                          <span className="font-medium">Regarding Teacher:</span>{" "}
                          {complaint.teacher_name}
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
                      <div className="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
                        <p className="text-sm font-medium text-blue-900">
                          Admin Response:
                        </p>
                        <p className="text-sm text-blue-800 mt-1">
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

      {/* Details Dialog */}
      <AlertDialog open={detailsOpen} onOpenChange={setDetailsOpen}>
        <AlertDialogContent className="max-w-2xl">
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
                  Complaint
                </h3>
                <p className="text-gray-800">{selectedComplaint.complaint}</p>
              </div>

              {selectedComplaint.teacher_name && (
                <div>
                  <h3 className="font-semibold text-sm text-gray-600 mb-1">
                    Regarding Teacher
                  </h3>
                  <p className="text-gray-800">{selectedComplaint.teacher_name}</p>
                </div>
              )}

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
                <div className="p-4 bg-blue-50 border border-blue-200 rounded">
                  <h3 className="font-semibold text-sm text-blue-900 mb-2">
                    Admin Response
                  </h3>
                  <p className="text-blue-800">{selectedComplaint.response}</p>
                </div>
              )}

              {!selectedComplaint.response &&
                selectedComplaint.status === "pending" && (
                  <div className="p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <p className="text-sm text-yellow-800">
                      Your complaint is pending review. You will be notified once
                      the admin provides a response.
                    </p>
                  </div>
                )}
            </div>
          )}

          <div className="flex justify-end">
            <Button onClick={() => setDetailsOpen(false)}>Close</Button>
          </div>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default StudentComplaintsList;
