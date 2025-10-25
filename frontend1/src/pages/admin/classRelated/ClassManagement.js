import React, { useEffect, useState } from "react";
import EditClassModal from "@/components/modals/EditClassModal";
import { useNavigate } from "react-router-dom";
import { useSelector } from "react-redux";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
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
import {
  BookOpen,
  Users,
  UserCheck,
  TrendingUp,
  Search,
  Plus,
  Edit,
  Trash2,
  Eye,
  Download,
  RefreshCw,
  GraduationCap,
  Award,
} from "lucide-react";

const ClassManagement = () => {
  const navigate = useNavigate();
  const { currentUser } = useSelector((state) => state.user);
  const [classes, setClasses] = useState([]);
  const [filteredClasses, setFilteredClasses] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [classToDelete, setClassToDelete] = useState(null);
  const [showClassModal, setShowClassModal] = useState(false);
  const [editingClass, setEditingClass] = useState(null);

  // Analytics state
  const [analytics, setAnalytics] = useState({
    total: 0,
    totalStudents: 0,
    avgPerClass: 0,
    withMasters: 0,
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    fetchClasses();
  }, []);

  useEffect(() => {
    filterClasses();
  }, [classes, searchTerm]);

  const fetchClasses = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API_URL}/classes`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        const classData = response.data.classes || [];
        setClasses(classData);
        calculateAnalytics(classData);
      }
    } catch (error) {
      toast.error("Failed to fetch classes");
      console.error("Error:", error);
    } finally {
      setLoading(false);
    }
  };

  const calculateAnalytics = (data) => {
    const total = data.length;
    const totalStudents = data.reduce((sum, cls) => sum + (cls.student_count || 0), 0);
    const avgPerClass = total > 0 ? Math.round(totalStudents / total) : 0;
    const withMasters = data.filter((cls) => cls.class_master_name).length;

    setAnalytics({
      total,
      totalStudents,
      avgPerClass,
      withMasters,
    });
  };

  const filterClasses = () => {
    let filtered = [...classes];

    // Search filter
    if (searchTerm) {
      filtered = filtered.filter(
        (cls) =>
          cls.class_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          cls.class_master_name?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    setFilteredClasses(filtered);
  };

  const handleEditClass = (cls) => {
    setEditingClass(cls);
    setShowClassModal(true);
  };

  const handleClassModalSuccess = () => {
    setShowClassModal(false);
    setEditingClass(null);
    fetchClasses();
  };

  const handleDelete = async () => {
    if (!classToDelete) return;

    try {
      const response = await axios.delete(
        `${API_URL}/classes/${classToDelete.id}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );

      if (response.data.success) {
        toast.success("Class deleted successfully");
        fetchClasses();
      }
    } catch (error) {
      toast.error("Failed to delete class");
    } finally {
      setShowDeleteDialog(false);
      setClassToDelete(null);
    }
  };

  const exportToCSV = () => {
    const headers = ["Class Name", "Students", "Subjects", "Class Master"];
    const rows = filteredClasses.map((c) => [
      c.class_name,
      c.student_count || 0,
      c.subject_count || 0,
      c.class_master_name || "Not assigned",
    ]);

    const csv = [headers, ...rows].map((row) => row.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `classes_${new Date().toISOString().split("T")[0]}.csv`;
    a.click();
    toast.success("Exported to CSV");
  };

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Class Management</h1>
          <p className="text-gray-500 mt-1">
            Manage classes, students, and class masters
          </p>
        </div>
        <Button onClick={() => navigate("/Admin/addclass")} className="gap-2">
          <Plus className="w-4 h-4" />
          Add Class
        </Button>
      </div>

      {/* Analytics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Total Classes
            </CardTitle>
            <BookOpen className="w-5 h-5 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">{analytics.total}</div>
            <p className="text-xs text-gray-500 mt-1">All active classes</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Total Students
            </CardTitle>
            <GraduationCap className="w-5 h-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.totalStudents}
            </div>
            <p className="text-xs text-gray-500 mt-1">Across all classes</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Avg Per Class
            </CardTitle>
            <Users className="w-5 h-5 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.avgPerClass}
            </div>
            <p className="text-xs text-gray-500 mt-1">Students per class</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              With Masters
            </CardTitle>
            <UserCheck className="w-5 h-5 text-orange-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.withMasters}
            </div>
            <p className="text-xs text-gray-500 mt-1">
              {analytics.total > 0
                ? Math.round((analytics.withMasters / analytics.total) * 100)
                : 0}
              % have class masters
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Filters and Actions */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col md:flex-row gap-4">
            {/* Search */}
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
              <Input
                placeholder="Search by class name or master..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>

            {/* Action Buttons */}
            <Button variant="outline" onClick={fetchClasses} className="gap-2">
              <RefreshCw className="w-4 h-4" />
              Refresh
            </Button>
            <Button variant="outline" onClick={exportToCSV} className="gap-2">
              <Download className="w-4 h-4" />
              Export CSV
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Classes Grid/Table */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {loading ? (
          <div className="col-span-full text-center py-8">
            <RefreshCw className="w-8 h-8 animate-spin mx-auto text-gray-400" />
            <p className="mt-2 text-gray-500">Loading classes...</p>
          </div>
        ) : filteredClasses.length === 0 ? (
          <div className="col-span-full text-center py-8">
            <BookOpen className="w-12 h-12 mx-auto text-gray-300" />
            <p className="mt-2 text-gray-500">No classes found</p>
            <Button
              variant="link"
              onClick={() => navigate("/Admin/addclass")}
              className="mt-2"
            >
              Add your first class
            </Button>
          </div>
        ) : (
          filteredClasses.map((cls) => (
            <Card key={cls.id} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                      <BookOpen className="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                      <CardTitle className="text-lg">{cls.class_name}</CardTitle>
                      <p className="text-sm text-gray-500 mt-1">
                        Class {cls.id}
                      </p>
                    </div>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                {/* Stats */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-gray-50 rounded-lg p-3">
                    <div className="flex items-center gap-2 text-gray-600 text-sm mb-1">
                      <GraduationCap className="w-4 h-4" />
                      Students
                    </div>
                    <div className="text-2xl font-bold text-gray-900">
                      {cls.student_count || 0}
                    </div>
                  </div>
                  <div className="bg-gray-50 rounded-lg p-3">
                    <div className="flex items-center gap-2 text-gray-600 text-sm mb-1">
                      <BookOpen className="w-4 h-4" />
                      Subjects
                    </div>
                    <div className="text-2xl font-bold text-gray-900">
                      {cls.subject_count || 0}
                    </div>
                  </div>
                </div>

                {/* Class Master */}
                <div className="border-t pt-4">
                  <div className="text-sm text-gray-600 mb-2">Class Master</div>
                  {cls.class_master_name ? (
                    <div className="flex items-center gap-2">
                      <UserCheck className="w-4 h-4 text-green-600" />
                      <span className="font-medium">{cls.class_master_name}</span>
                    </div>
                  ) : (
                    <span className="text-gray-400 text-sm">Not assigned</span>
                  )}
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2 pt-4 border-t">
                  <Button
                    variant="outline"
                    size="sm"
                    className="flex-1"
                    onClick={() => navigate(`/Admin/classes/view/${cls.id}`)}
                  >
                    <Eye className="w-4 h-4 mr-2" />
                    View
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleEditClass(cls)}
                  >
                    <Edit className="w-4 h-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setClassToDelete(cls);
                      setShowDeleteDialog(true);
                    }}
                    className="text-red-600 hover:text-red-700"
                  >
                    <Trash2 className="w-4 h-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {/* Edit Class Modal */}
      <EditClassModal
        open={showClassModal}
        onOpenChange={setShowClassModal}
        classData={editingClass}
        onSuccess={handleClassModalSuccess}
      />

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Class?</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete {classToDelete?.class_name}? This
              action cannot be undone and will affect {classToDelete?.student_count || 0}{" "}
              students.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setClassToDelete(null)}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
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

export default ClassManagement;
