import React, { useEffect, useState } from "react";
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
import {
  BookOpen,
  Users,
  TrendingUp,
  Award,
  Search,
  Plus,
  Edit,
  Trash2,
  Eye,
  Download,
  RefreshCw,
  BookMarked,
  Filter,
} from "lucide-react";
import SubjectModal from "@/components/modals/SubjectModal";

const SubjectManagement = () => {
  const navigate = useNavigate();
  const { currentUser } = useSelector((state) => state.user);
  const [subjects, setSubjects] = useState([]);
  const [filteredSubjects, setFilteredSubjects] = useState([]);
  const [classes, setClasses] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterClass, setFilterClass] = useState("all");
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [subjectToDelete, setSubjectToDelete] = useState(null);
  
  // Modal state
  const [showSubjectModal, setShowSubjectModal] = useState(false);
  const [editingSubject, setEditingSubject] = useState(null);
  const [isEditMode, setIsEditMode] = useState(false);

  // Analytics state
  const [analytics, setAnalytics] = useState({
    total: 0,
    withTeachers: 0,
    avgPerClass: 0,
    totalEnrollments: 0,
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    fetchSubjects();
    fetchClasses();
  }, []);

  useEffect(() => {
    filterSubjects();
  }, [subjects, searchTerm, filterClass]);

  const fetchSubjects = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API_URL}/subjects`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        const subjectData = response.data.subjects || [];
        setSubjects(subjectData);
        calculateAnalytics(subjectData);
      }
    } catch (error) {
      toast.error("Failed to fetch subjects");
      console.error("Error:", error);
    } finally {
      setLoading(false);
    }
  };

  const fetchClasses = async () => {
    try {
      const response = await axios.get(`${API_URL}/classes`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        setClasses(response.data.classes || []);
      }
    } catch (error) {
      console.error("Error fetching classes:", error);
    }
  };

  const calculateAnalytics = (data) => {
    const total = data.length;
    const withTeachers = data.filter((s) => s.teacher_name).length;
    
    // Group by class to calculate average
    const classGroups = {};
    data.forEach((subject) => {
      if (subject.class_id) {
        if (!classGroups[subject.class_id]) {
          classGroups[subject.class_id] = 0;
        }
        classGroups[subject.class_id]++;
      }
    });
    
    const avgPerClass = Object.keys(classGroups).length > 0
      ? Math.round(
          Object.values(classGroups).reduce((a, b) => a + b, 0) /
            Object.keys(classGroups).length
        )
      : 0;

    const totalEnrollments = data.reduce((sum, s) => sum + (s.student_count || 0), 0);

    setAnalytics({
      total,
      withTeachers,
      avgPerClass,
      totalEnrollments,
    });
  };

  const filterSubjects = () => {
    let filtered = [...subjects];

    // Search filter
    if (searchTerm) {
      filtered = filtered.filter(
        (subject) =>
          subject.subject_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          subject.subject_code?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          subject.teacher_name?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Class filter
    if (filterClass !== "all") {
      filtered = filtered.filter((s) => s.class_id?.toString() === filterClass);
    }

    setFilteredSubjects(filtered);
  };

  const handleDelete = async () => {
    if (!subjectToDelete) return;

    try {
      const response = await axios.delete(
        `${API_URL}/subjects/${subjectToDelete.id}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );

      if (response.data.success) {
        toast.success("Subject deleted successfully");
        fetchSubjects();
      }
    } catch (error) {
      toast.error("Failed to delete subject");
    } finally {
      setShowDeleteDialog(false);
      setSubjectToDelete(null);
    }
  };

  const exportToCSV = () => {
    const headers = [
      "Subject Code",
      "Subject Name",
      "Class",
      "Teacher",
      "Students",
    ];
    const rows = filteredSubjects.map((s) => [
      s.subject_code || "N/A",
      s.subject_name,
      s.class_name || "N/A",
      s.teacher_name || "Not assigned",
      s.student_count || 0,
    ]);

    const csv = [headers, ...rows].map((row) => row.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `subjects_${new Date().toISOString().split("T")[0]}.csv`;
    a.click();
    toast.success("Exported to CSV");
  };

  const handleAddSubject = () => {
    setIsEditMode(false);
    setEditingSubject(null);
    setShowSubjectModal(true);
  };

  const handleEditSubject = (subject) => {
    setIsEditMode(true);
    setEditingSubject(subject);
    setShowSubjectModal(true);
  };

  const handleModalSuccess = () => {
    fetchSubjects();
    setShowSubjectModal(false);
    setEditingSubject(null);
    setIsEditMode(false);
  };

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Subject Management</h1>
          <p className="text-gray-500 mt-1">
            Manage subjects, assignments, and teacher allocation
          </p>
        </div>
        <Button onClick={handleAddSubject} className="gap-2">
          <Plus className="w-4 h-4" />
          Add Subject
        </Button>
      </div>

      {/* Analytics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Total Subjects
            </CardTitle>
            <BookMarked className="w-5 h-5 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">{analytics.total}</div>
            <p className="text-xs text-gray-500 mt-1">All subjects</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              With Teachers
            </CardTitle>
            <Users className="w-5 h-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.withTeachers}
            </div>
            <p className="text-xs text-gray-500 mt-1">
              {analytics.total > 0
                ? Math.round((analytics.withTeachers / analytics.total) * 100)
                : 0}
              % assigned to teachers
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Avg Per Class
            </CardTitle>
            <Award className="w-5 h-5 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.avgPerClass}
            </div>
            <p className="text-xs text-gray-500 mt-1">Subjects per class</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Total Enrollments
            </CardTitle>
            <TrendingUp className="w-5 h-5 text-orange-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.totalEnrollments}
            </div>
            <p className="text-xs text-gray-500 mt-1">Student enrollments</p>
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
                placeholder="Search by subject name, code, or teacher..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>

            {/* Class Filter */}
            <Select value={filterClass} onValueChange={setFilterClass}>
              <SelectTrigger className="w-[200px]">
                <Filter className="w-4 h-4 mr-2" />
                <SelectValue placeholder="Filter by class" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Classes</SelectItem>
                {classes.map((cls) => (
                  <SelectItem key={cls.id} value={cls.id.toString()}>
                    {cls.class_name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            {/* Action Buttons */}
            <Button variant="outline" onClick={fetchSubjects} className="gap-2">
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

      {/* Subjects Table */}
      <Card>
        <CardHeader>
          <CardTitle>Subjects List ({filteredSubjects.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">
              <RefreshCw className="w-8 h-8 animate-spin mx-auto text-gray-400" />
              <p className="mt-2 text-gray-500">Loading subjects...</p>
            </div>
          ) : filteredSubjects.length === 0 ? (
            <div className="text-center py-8">
              <BookMarked className="w-12 h-12 mx-auto text-gray-300" />
              <p className="mt-2 text-gray-500">No subjects found</p>
              <Button
                variant="link"
                onClick={handleAddSubject}
                className="mt-2"
              >
                Add your first subject
              </Button>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Subject Code</TableHead>
                    <TableHead>Subject Name</TableHead>
                    <TableHead>Class</TableHead>
                    <TableHead>Teacher</TableHead>
                    <TableHead>Students</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredSubjects.map((subject) => (
                    <TableRow key={subject.id}>
                      <TableCell>
                        <div className="font-mono text-sm font-medium">
                          {subject.subject_code || "-"}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <BookOpen className="w-4 h-4 text-gray-400" />
                          <span className="font-medium">{subject.subject_name}</span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm">
                          {subject.class_name || (
                            <span className="text-gray-400">-</span>
                          )}
                        </span>
                      </TableCell>
                      <TableCell>
                        {subject.teacher_name ? (
                          <div className="flex items-center gap-1">
                            <Users className="w-3 h-3 text-gray-400" />
                            <span className="text-sm">{subject.teacher_name}</span>
                          </div>
                        ) : (
                          <Badge variant="outline" className="text-xs">
                            Not assigned
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell>
                        <span className="text-sm">{subject.student_count || 0}</span>
                      </TableCell>
                      <TableCell>
                        {subject.teacher_name ? (
                          <Badge className="bg-green-100 text-green-800">Active</Badge>
                        ) : (
                          <Badge className="bg-yellow-100 text-yellow-800">
                            Pending
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                              navigate(`/Admin/subjects/view/${subject.id}`)
                            }
                          >
                            <Eye className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleEditSubject(subject)}
                          >
                            <Edit className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setSubjectToDelete(subject);
                              setShowDeleteDialog(true);
                            }}
                            className="text-red-600 hover:text-red-700"
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Subject Modal */}
      <SubjectModal
        open={showSubjectModal}
        onOpenChange={setShowSubjectModal}
        onSuccess={handleModalSuccess}
        editMode={isEditMode}
        subjectData={editingSubject}
      />

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Subject?</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete {subjectToDelete?.subject_name}? This
              action cannot be undone and will remove all grades and assignments.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setSubjectToDelete(null)}>
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

export default SubjectManagement;



