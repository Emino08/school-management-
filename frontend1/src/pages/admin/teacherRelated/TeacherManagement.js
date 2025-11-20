import React, { useEffect, useMemo, useRef, useState } from "react";
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Users,
  UserCheck,
  Award,
  TrendingUp,
  Search,
  Plus,
  Edit,
  Trash2,
  Mail,
  Phone,
  BookOpen,
  Eye,
  Filter,
  Download,
  Upload,
  RefreshCw,
} from "lucide-react";
import TeacherModal from "@/components/modals/TeacherModal";
import EditTeacherModal from "@/components/modals/EditTeacherModal";

const parseSubjectList = (subjects) => {
  if (!subjects) return [];
  if (Array.isArray(subjects)) return subjects.filter(Boolean);
  if (typeof subjects === "string") {
    return subjects
      .split(/[,|]/)
      .map((s) => s.trim())
      .filter(Boolean);
  }
  return [];
};

const TeacherManagement = () => {
  const navigate = useNavigate();
  const { currentUser } = useSelector((state) => state.user);
  const [teachers, setTeachers] = useState([]);
  const [filteredTeachers, setFilteredTeachers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterRole, setFilterRole] = useState("all");
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [teacherToDelete, setTeacherToDelete] = useState(null);
  const [classes, setClasses] = useState([]);
  const [assignClassOpen, setAssignClassOpen] = useState(false);
  const [assignClassTeacher, setAssignClassTeacher] = useState(null);
  const [selectedClassId, setSelectedClassId] = useState("");
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [confirmAction, setConfirmAction] = useState({ title: "", description: "", onConfirm: null });
  const [uploading, setUploading] = useState(false);
  const fileInputRef = useRef(null);
  const smallSuccess = (msg) => toast.success(msg, { duration: 1500, position: 'bottom-right' });
  
  // Modal state
  const [showTeacherModal, setShowTeacherModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [teacherToEdit, setTeacherToEdit] = useState(null);
  const [viewTeacher, setViewTeacher] = useState(null);

  // Analytics state
  const [analytics, setAnalytics] = useState({
    total: 0,
    classMasters: 0,
    examOfficers: 0,
    activeRate: 100,
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    fetchTeachers();
    fetchClasses();
  }, []);

  useEffect(() => {
    filterTeachers();
  }, [teachers, searchTerm, filterRole]);

  const fetchTeachers = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API_URL}/teachers`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        const teacherData = response.data.teachers || [];
        setTeachers(teacherData);
        calculateAnalytics(teacherData);
      }
    } catch (error) {
      toast.error("Failed to fetch teachers");
      console.error("Error:", error);
    } finally {
      setLoading(false);
    }
  };

  const calculateAnalytics = (data) => {
    const total = data.length;
    const classMasters = data.filter((t) => t.is_class_master).length;
    const examOfficers = data.filter((t) => t.is_exam_officer).length;
    const activeRate = total > 0 ? Math.round((total / total) * 100) : 100;

    setAnalytics({
      total,
      classMasters,
      examOfficers,
      activeRate,
    });
  };

  const fetchClasses = async () => {
    try {
      const response = await axios.get(`${API_URL}/classes`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (response.data.success) setClasses(response.data.classes || []);
    } catch (e) {
      console.error('Failed to fetch classes', e);
    }
  };

  const handleTemplateDownload = async () => {
    try {
      const res = await fetch(`${API_URL}/teachers/bulk-template`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'teachers_template.csv';
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
      toast.success("Template downloaded");
    } catch (e) {
      console.error('Template download failed', e);
      toast.error("Failed to download template");
    }
  };

  const handleBulkUpload = async (file) => {
    if (!file) return;
    setUploading(true);
    try {
      const form = new FormData();
      form.append('file', file);
      const res = await fetch(`${API_URL}/teachers/bulk-upload`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${currentUser?.token}` },
        body: form,
      });
      const data = await res.json();
      if (data.success) {
        toast.success('Teachers uploaded successfully');
        fetchTeachers();
      } else {
        toast.error(data.message || 'Bulk upload failed');
      }
    } catch (e) {
      console.error('Bulk upload failed', e);
      toast.error('Bulk upload failed');
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  };

  const toggleExamOfficer = async (teacher, make) => {
    try {
      const res = await axios.put(
        `${API_URL}/teachers/${teacher.id}`,
        { is_exam_officer: make ? 1 : 0 },
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );
      if (res.data.success) {
        smallSuccess(make ? 'Exam officer assigned' : 'Exam officer revoked');
        fetchTeachers();
      } else {
        toast.error(res.data.message || 'Action failed');
      }
    } catch (e) {
      console.error(e);
      toast.error(e.response?.data?.message || 'Action failed');
    }
  };

  const openAssignClassMaster = (teacher) => {
    setAssignClassTeacher(teacher);
    setSelectedClassId(teacher.class_master_of ? teacher.class_master_of.toString() : "");
    setAssignClassOpen(true);
  };

  const submitAssignClassMaster = async () => {
    if (!selectedClassId) {
      toast.error('Please select a class');
      return;
    }
    try {
      const res = await axios.put(
        `${API_URL}/teachers/${assignClassTeacher.id}`,
        { is_class_master: 1, class_master_of: selectedClassId },
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );
      if (res.data.success) {
        smallSuccess('Class master assigned');
        setAssignClassOpen(false);
        fetchTeachers();
      } else {
        toast.error(res.data.message || 'Failed to assign');
      }
    } catch (e) {
      console.error(e);
      toast.error(e.response?.data?.message || 'Failed to assign');
    }
  };

  const unassignClassMaster = async (teacher) => {
    try {
      const res = await axios.put(
        `${API_URL}/teachers/${teacher.id}`,
        { is_class_master: 0, class_master_of: null },
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );
      if (res.data.success) {
        smallSuccess('Class master removed');
        fetchTeachers();
      } else {
        toast.error(res.data.message || 'Failed to remove');
      }
    } catch (e) {
      console.error(e);
      toast.error(e.response?.data?.message || 'Failed to remove');
    }
  };

  const viewTeacherDetails = useMemo(() => {
    if (!viewTeacher) return null;
    const subjects = parseSubjectList(viewTeacher.subjects);
    const roles = [];
    if (viewTeacher.is_class_master) roles.push("Class Master");
    if (viewTeacher.is_exam_officer) roles.push("Exam Officer");
    if (viewTeacher.can_approve_results) roles.push("Can Approve Results");
    if (roles.length === 0) roles.push("Teacher");

    const classMasterOf = viewTeacher.class_master_of || viewTeacher.classMasterOf;
    const classMatch =
      classMasterOf &&
      classes.find((cls) => String(cls.id) === String(classMasterOf));
    const classMasterLabel =
      classMatch?.class_name ||
      classMatch?.sclassName ||
      (classMasterOf ? `Class #${classMasterOf}` : "Not assigned");

    const primaryClass =
      viewTeacher.class_name ||
      viewTeacher.className ||
      classMasterLabel ||
      "Not assigned";

    const experience =
      viewTeacher.experience_years ?? viewTeacher.experienceYears ?? null;

    const stats = [
      { label: "Primary Class", value: primaryClass },
      { label: "Subjects", value: subjects.length ? `${subjects.length}` : "None" },
      {
        label: "Experience",
        value:
          experience !== null
            ? `${experience} year${experience === 1 ? "" : "s"}`
            : "Not provided",
      },
      {
        label: "Status",
        value: viewTeacher.is_active === false ? "Inactive" : "Active",
      },
    ];

    const infoGroups = [
      {
        title: "Professional Details",
        rows: [
          { label: "Teacher ID", value: viewTeacher.id },
          { label: "Class Master Of", value: classMasterLabel },
          { label: "Qualification", value: viewTeacher.qualification || "Not provided" },
          { label: "Specialization", value: viewTeacher.specialization || "Not provided" },
        ],
      },
      {
        title: "Contact Details",
        rows: [
          { label: "Email", value: viewTeacher.email || "Not provided" },
          { label: "Phone", value: viewTeacher.phone || "Not provided" },
          { label: "Address", value: viewTeacher.address || "Not provided" },
        ],
      },
      {
        title: "Permissions",
        rows: [
          {
            label: "Exam Officer",
            value: viewTeacher.is_exam_officer ? "Yes" : "No",
          },
          {
            label: "Class Master",
            value: viewTeacher.is_class_master ? "Yes" : "No",
          },
          {
            label: "Can Approve Results",
            value: viewTeacher.can_approve_results ? "Yes" : "No",
          },
        ],
      },
    ];

    return {
      teacher: viewTeacher,
      roles,
      subjects,
      stats,
      infoGroups,
    };
  }, [viewTeacher, classes]);

  const filterTeachers = () => {
    let filtered = [...teachers];

    // Search filter
    if (searchTerm) {
      filtered = filtered.filter(
        (teacher) =>
          teacher.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          teacher.email?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Role filter
    if (filterRole !== "all") {
      if (filterRole === "class_master") {
        filtered = filtered.filter((t) => t.is_class_master);
      } else if (filterRole === "exam_officer") {
        filtered = filtered.filter((t) => t.is_exam_officer);
      } else if (filterRole === "regular") {
        filtered = filtered.filter((t) => !t.is_class_master && !t.is_exam_officer);
      }
    }

    setFilteredTeachers(filtered);
  };

  const handleDelete = async () => {
    if (!teacherToDelete) return;

    try {
      const response = await axios.delete(
        `${API_URL}/teachers/${teacherToDelete.id}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );

      if (response.data.success) {
        toast.success("Teacher deleted successfully");
        fetchTeachers();
      }
    } catch (error) {
      toast.error("Failed to delete teacher");
    } finally {
      setShowDeleteDialog(false);
      setTeacherToDelete(null);
    }
  };

  const exportToCSV = () => {
    const headers = ["Name", "Email", "Phone", "Role", "Class", "Subjects"];
    const rows = filteredTeachers.map((t) => [
      t.name,
      t.email,
      t.phone || "N/A",
      getRoleLabel(t),
      t.class_name || "N/A",
      t.subjects || "N/A",
    ]);

    const csv = [headers, ...rows].map((row) => row.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `teachers_${new Date().toISOString().split("T")[0]}.csv`;
    a.click();
    toast.success("Exported to CSV");
  };

  const getRoleLabel = (teacher) => {
    const roles = [];
    if (teacher.is_class_master) roles.push("Class Master");
    if (teacher.is_exam_officer) roles.push("Exam Officer");
    if (roles.length === 0) roles.push("Teacher");
    return roles.join(" & ");
  };

  const getRoleBadgeColor = (teacher) => {
    if (teacher.is_class_master && teacher.is_exam_officer) return "bg-purple-100 text-purple-800";
    if (teacher.is_class_master) return "bg-green-100 text-green-800";
    if (teacher.is_exam_officer) return "bg-blue-100 text-blue-800";
    return "bg-gray-100 text-gray-800";
  };

  const handleAddTeacher = () => {
    console.log('TeacherManagement - Opening teacher modal');
    setShowTeacherModal(true);
  };

  const handleModalSuccess = () => {
    fetchTeachers();
    setShowTeacherModal(false);
  };

  const handleEditTeacher = (teacher) => {
    console.log('TeacherManagement - Opening edit modal for teacher:', teacher);
    setTeacherToEdit(teacher);
    setShowEditModal(true);
  };

  const handleEditModalSuccess = () => {
    fetchTeachers();
    setShowEditModal(false);
    setTeacherToEdit(null);
  };

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Teacher Management</h1>
          <p className="text-gray-500 mt-1">
            Manage teachers, class masters, and exam officers
          </p>
        </div>
        <div className="flex items-center gap-3">
          <input
            type="file"
            accept=".csv,text/csv"
            ref={fileInputRef}
            className="hidden"
            onChange={(e) => handleBulkUpload(e.target.files?.[0])}
          />
          <Button variant="outline" onClick={handleTemplateDownload} className="gap-2">
            <Download className="w-4 h-4" />
            Template
          </Button>
          <Button
            variant="outline"
            onClick={() => fileInputRef.current?.click()}
            className="gap-2"
            disabled={uploading}
          >
            {uploading ? (
              <>
                <RefreshCw className="w-4 h-4 animate-spin" />
                Uploading...
              </>
            ) : (
              <>
                <Upload className="w-4 h-4" />
                Upload CSV
              </>
            )}
          </Button>
          <Button onClick={handleAddTeacher} className="gap-2">
            <Plus className="w-4 h-4" />
            Add Teacher
          </Button>
        </div>
      </div>

      {/* Analytics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Total Teachers
            </CardTitle>
            <Users className="w-5 h-5 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">{analytics.total}</div>
            <p className="text-xs text-gray-500 mt-1">All registered teachers</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Class Masters
            </CardTitle>
            <UserCheck className="w-5 h-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.classMasters}
            </div>
            <p className="text-xs text-gray-500 mt-1">
              {analytics.total > 0
                ? Math.round((analytics.classMasters / analytics.total) * 100)
                : 0}
              % of total teachers
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Exam Officers
            </CardTitle>
            <Award className="w-5 h-5 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.examOfficers}
            </div>
            <p className="text-xs text-gray-500 mt-1">
              {analytics.total > 0
                ? Math.round((analytics.examOfficers / analytics.total) * 100)
                : 0}
              % of total teachers
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Active Rate
            </CardTitle>
            <TrendingUp className="w-5 h-5 text-orange-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.activeRate}%
            </div>
            <p className="text-xs text-gray-500 mt-1">All teachers active</p>
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
                placeholder="Search by name or email..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>

            {/* Role Filter */}
            <Select value={filterRole} onValueChange={setFilterRole}>
              <SelectTrigger className="w-[200px]">
                <Filter className="w-4 h-4 mr-2" />
                <SelectValue placeholder="Filter by role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Teachers</SelectItem>
                <SelectItem value="class_master">Class Masters</SelectItem>
                <SelectItem value="exam_officer">Exam Officers</SelectItem>
                <SelectItem value="regular">Regular Teachers</SelectItem>
              </SelectContent>
            </Select>

            {/* Action Buttons */}
            <Button variant="outline" onClick={fetchTeachers} className="gap-2">
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

      {/* Teachers Table */}
      <Card>
        <CardHeader>
          <CardTitle>Teachers List ({filteredTeachers.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">
              <RefreshCw className="w-8 h-8 animate-spin mx-auto text-gray-400" />
              <p className="mt-2 text-gray-500">Loading teachers...</p>
            </div>
          ) : filteredTeachers.length === 0 ? (
            <div className="text-center py-8">
              <Users className="w-12 h-12 mx-auto text-gray-300" />
              <p className="mt-2 text-gray-500">No teachers found</p>
              <Button
                variant="link"
                onClick={() => navigate("/Admin/addteacher")}
                className="mt-2"
              >
                Add your first teacher
              </Button>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Contact</TableHead>
                    <TableHead>Role</TableHead>
                    <TableHead>Class</TableHead>
                    <TableHead>Subjects</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredTeachers.map((teacher) => (
                  <TableRow key={teacher.id}>
                      <TableCell>
                        <div>
                          <div className="font-medium">{teacher.name}</div>
                          <div className="text-sm text-gray-500 flex items-center gap-1 mt-1">
                            <Mail className="w-3 h-3" />
                            {teacher.email}
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>
                        {teacher.phone ? (
                          <div className="flex items-center gap-1 text-sm">
                            <Phone className="w-3 h-3 text-gray-400" />
                            {teacher.phone}
                          </div>
                        ) : (
                          <span className="text-gray-400 text-sm">No phone</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <Badge className={getRoleBadgeColor(teacher)}>
                          {getRoleLabel(teacher)}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {teacher.class_name ? (
                          <div className="flex items-center gap-1">
                            <BookOpen className="w-3 h-3 text-gray-400" />
                            <span className="text-sm">{teacher.class_name}</span>
                          </div>
                        ) : (
                          <span className="text-gray-400 text-sm">-</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <span className="text-sm">
                          {teacher.subjects || <span className="text-gray-400">No subjects</span>}
                        </span>
                      </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        {/* Role shortcuts */}
                        {teacher.is_exam_officer ? (
                          <Button variant="outline" size="sm" onClick={() => {
                            setConfirmAction({
                              title: 'Revoke Exam Officer',
                              description: `Revoke exam officer for ${teacher.name}?`,
                              onConfirm: async () => toggleExamOfficer(teacher, false),
                            });
                            setConfirmOpen(true);
                          }}>
                            Revoke Officer
                          </Button>
                        ) : (
                          <Button variant="outline" size="sm" onClick={() => toggleExamOfficer(teacher, true)}>
                            Make Officer
                          </Button>
                        )}
                        {teacher.is_class_master ? (
                          <Button variant="outline" size="sm" onClick={() => {
                            setConfirmAction({
                              title: 'Remove Class Master',
                              description: `Remove class master role from ${teacher.name}?`,
                              onConfirm: async () => unassignClassMaster(teacher),
                            });
                            setConfirmOpen(true);
                          }}>
                            Remove Master
                          </Button>
                        ) : (
                          <Button variant="outline" size="sm" onClick={() => openAssignClassMaster(teacher)} disabled={classes.length === 0}>
                            Make Master
                          </Button>
                        )}
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setViewTeacher(teacher)}
                        >
                            <Eye className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleEditTeacher(teacher)}
                          >
                            <Edit className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setTeacherToDelete(teacher);
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

      <Dialog
        open={Boolean(viewTeacher)}
        onOpenChange={(open) => {
          if (!open) setViewTeacher(null);
        }}
      >
        <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
          {viewTeacherDetails ? (
            <>
              <div className="rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-blue-600 text-white shadow-lg p-5">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                  <div>
                    <p className="text-xs uppercase tracking-widest text-white/80">
                      Teacher Overview
                    </p>
                    <h3 className="text-2xl font-bold mt-1">
                      {viewTeacherDetails.teacher.name}
                    </h3>
                    <p className="text-white/80">
                      {viewTeacherDetails.teacher.email || "Email not provided"}
                    </p>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {viewTeacherDetails.roles.map((role) => (
                      <Badge key={role} variant="secondary" className="bg-white/15 text-white">
                        {role}
                      </Badge>
                    ))}
                  </div>
                </div>
                <div className="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                  {viewTeacherDetails.stats.map((stat) => (
                    <div key={stat.label} className="bg-white/10 rounded-xl p-3">
                      <p className="text-xs uppercase tracking-wide text-white/70">
                        {stat.label}
                      </p>
                      <p className="text-lg font-semibold mt-1">{stat.value || "—"}</p>
                    </div>
                  ))}
                </div>
              </div>

              <div className="space-y-6 py-4">
                {viewTeacherDetails.infoGroups.map((group) => (
                  <div key={group.title}>
                    <p className="text-xs uppercase tracking-wide text-gray-500 mb-2">
                      {group.title}
                    </p>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {group.rows.map((row) => (
                        <div key={row.label} className="rounded-xl border border-gray-100 p-4">
                          <p className="text-[11px] font-semibold uppercase tracking-widest text-gray-500">
                            {row.label}
                          </p>
                          <p className="mt-1 text-sm font-medium text-gray-900">
                            {row.value || "—"}
                          </p>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}

                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500 mb-2">
                    Assigned Subjects
                  </p>
                  {viewTeacherDetails.subjects.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                      {viewTeacherDetails.subjects.map((subject, index) => (
                        <Badge key={`${subject}-${index}`} variant="outline">
                          {subject}
                        </Badge>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-gray-500">No subjects assigned</p>
                  )}
                </div>
              </div>

              <DialogFooter>
                <Button variant="outline" onClick={() => setViewTeacher(null)}>
                  Close
                </Button>
              </DialogFooter>
            </>
          ) : null}
        </DialogContent>
      </Dialog>

      {/* Teacher Modal */}
      <TeacherModal
        open={showTeacherModal}
        onOpenChange={setShowTeacherModal}
        onSuccess={handleModalSuccess}
        adminID={currentUser?._id || currentUser?.id}
      />

      {/* Edit Teacher Modal */}
      <EditTeacherModal
        open={showEditModal}
        onOpenChange={setShowEditModal}
        teacher={teacherToEdit}
        onSuccess={handleEditModalSuccess}
      />

      {/* Assign Class Master Modal */}
      <AlertDialog open={assignClassOpen} onOpenChange={setAssignClassOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Assign Class Master</AlertDialogTitle>
            <AlertDialogDescription>
              Select a class for {assignClassTeacher?.name}.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="space-y-2">
            <Select value={selectedClassId} onValueChange={setSelectedClassId}>
              <SelectTrigger>
                <SelectValue placeholder="Select Class" />
              </SelectTrigger>
              <SelectContent>
                {classes.map((c) => (
                  <SelectItem key={c.id} value={c.id.toString()}>
                    {c.class_name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setAssignClassOpen(false)}>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={submitAssignClassMaster} disabled={!selectedClassId}>Assign</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Unified Confirmation Dialog */}
      <AlertDialog open={confirmOpen} onOpenChange={setConfirmOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{confirmAction.title}</AlertDialogTitle>
            <AlertDialogDescription>{confirmAction.description}</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setConfirmOpen(false)}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={async () => {
                try {
                  if (typeof confirmAction.onConfirm === 'function') {
                    await confirmAction.onConfirm();
                  }
                } finally {
                  setConfirmOpen(false);
                }
              }}
            >
              Confirm
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Teacher?</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete {teacherToDelete?.name}? This action
              cannot be undone and will remove all associated data.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setTeacherToDelete(null)}>
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

export default TeacherManagement;
