import React, { useEffect, useMemo, useState, useRef } from "react";
import { useNavigate } from "react-router-dom";
import { useSelector } from "react-redux";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
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
  TrendingUp,
  DollarSign,
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
  RefreshCw,
  GraduationCap,
} from "lucide-react";
import StudentModal from "@/components/modals/StudentModal";
import EditStudentModal from "@/components/modals/EditStudentModal";

const StudentManagement = () => {
  const navigate = useNavigate();
  const { currentUser } = useSelector((state) => state.user);
  const [students, setStudents] = useState([]);
  const [classes, setClasses] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterClass, setFilterClass] = useState("all");
  const [filterStatus, setFilterStatus] = useState("all");
  const [exportOptions, setExportOptions] = useState({
    includeContact: true,
    includeGuardian: false,
    includeStatus: true,
    includeFees: true,
  });
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [studentToDelete, setStudentToDelete] = useState(null);
  
  // Modal state
  const [showStudentModal, setShowStudentModal] = useState(false);
  const [uploading, setUploading] = useState(false);
  const fileInputRef = useRef(null);
  const [showEditStudentModal, setShowEditStudentModal] = useState(false);
  const [editingStudent, setEditingStudent] = useState(null);
  const [viewStudent, setViewStudent] = useState(null);

  // Analytics state
  const [analytics, setAnalytics] = useState({
    total: 0,
    active: 0,
    inactive: 0,
    feesPaid: 0,
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";
  const handleTemplateDownload = async () => {
    try {
      const res = await fetch(`${API_URL}/students/bulk-template`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'students_template.csv';
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

  const formatDate = (value) => {
    if (!value) return "—";
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;
    return parsed.toLocaleDateString();
  };

  const studentDetail = useMemo(() => {
    if (!viewStudent) return null;
    const student = viewStudent;
    const raw = student.raw || student;

    const stats = [
      { label: "Class", value: student.displayClassName || "Not assigned" },
      { label: "Status", value: student.displayStatus || "Active" },
      { label: "Fees Status", value: student.displayFeesStatus || "Pending" },
      { label: "Average Score", value: raw.class_average ?? "—" },
    ];

    const personalRows = [
      { label: "First Name", value: student.displayFirstName || "—" },
      { label: "Last Name", value: student.displayLastName || "—" },
      { label: "ID / Admission", value: student.displayRollNumber || "—" },
      { label: "Gender", value: raw.gender || "Not specified" },
      { label: "Date of Birth", value: formatDate(raw.date_of_birth || raw.dob) },
    ];

    const contactRows = [
      { label: "Email", value: student.displayEmail || "Not provided" },
      { label: "Phone", value: student.displayPhone || "Not provided" },
      { label: "Address", value: student.displayAddress || "Not provided" },
    ];

    const guardianRows = [
      { label: "Guardian Name", value: student.displayGuardianName || "Not provided" },
      { label: "Guardian Phone", value: student.displayGuardianPhone || "Not provided" },
      { label: "Guardian Email", value: student.displayGuardianEmail || "Not provided" },
    ];

    const tags = [];
    if (raw.shift) tags.push(`${raw.shift} Shift`);
    if (raw.section) tags.push(`Section ${raw.section}`);
    if (raw.special_need) tags.push("Special Need");

    return {
      student,
      stats,
      personalRows,
      contactRows,
      guardianRows,
      tags,
    };
  }, [viewStudent]);

  const handleBulkUpload = async () => {
    if (!fileInputRef.current?.files?.length) return;
    setUploading(true);
    try {
      const form = new FormData();
      form.append('file', fileInputRef.current.files[0]);
      const res = await fetch(`${API_URL}/students/bulk-upload`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${currentUser?.token}` },
        body: form,
      });
      const data = await res.json();
      if (data.success) {
        fetchStudents();
        toast.success('Students uploaded successfully');
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

  useEffect(() => {
    fetchStudents();
    fetchClasses();
  }, []);

  const fetchStudents = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API_URL}/students`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        const studentData = response.data.students || [];
        setStudents(studentData);
        calculateAnalytics(studentData);
      }
    } catch (error) {
      toast.error("Failed to fetch students");
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
      toast.error("Failed to load classes");
    }
  };

  const handleEditStudent = (student) => {
    setEditingStudent(student);
    setShowEditStudentModal(true);
  };

  const handleEditStudentSuccess = () => {
    setShowEditStudentModal(false);
    setEditingStudent(null);
    fetchStudents();
  };

  const calculateAnalytics = (data) => {
    const total = data.length;
    const active = data.filter((s) => s.status === "active").length;
    const inactive = total - active;
    const feesPaid = data.filter((s) => s.fees_status === "paid").length;

    setAnalytics({
      total,
      active,
      inactive,
      feesPaid: feesPaid > 0 ? Math.round((feesPaid / total) * 100) : 0,
    });
  };

  const normalizedStudents = useMemo(() => {
    return students.map((student) => {
      const raw = student;
      const classMatch =
        raw.class_id && classes.length
          ? classes.find((cls) => String(cls.id) === String(raw.class_id))
          : null;

      const [derivedFirst, derivedLast] = (() => {
        const full = (raw.name || "").trim();
        if (!full) return ["", ""];
        const parts = full.split(/\s+/);
        const first = parts.shift() || "";
        const last = parts.join(" ");
        return [first, last];
      })();

      const firstName = raw.first_name || derivedFirst || "";
      const lastName = raw.last_name || derivedLast || "";
      const fullName = raw.name || [firstName, lastName].filter(Boolean).join(" ").trim();

      const className =
        raw.class_name ||
        classMatch?.class_name ||
        classMatch?.sclassName ||
        raw.sclassName?.sclassName ||
        "Not assigned";

      const guardianName = raw.parent_name || raw.guardian_name || "Not provided";
      const guardianPhone = raw.parent_phone || raw.guardian_phone || "Not provided";
      const guardianEmail = raw.parent_email || raw.guardian_email || "Not provided";

      return {
        ...raw,
        raw,
        displayFirstName: firstName,
        displayLastName: lastName,
        fullName,
        displayClassName: className,
        displayRollNumber: raw.roll_number || raw.id_number || "N/A",
        displayStatus: (raw.status || "active").toString(),
        displayFeesStatus: raw.fees_status || "pending",
        displayEmail: raw.email || "Not provided",
        displayPhone: raw.phone || "Not provided",
        displayAddress: raw.address || "Not provided",
        displayGuardianName: guardianName,
        displayGuardianPhone: guardianPhone,
        displayGuardianEmail: guardianEmail,
      };
    });
  }, [students, classes]);

  const filteredStudents = useMemo(() => {
    let filtered = [...normalizedStudents];

    if (searchTerm) {
      const query = searchTerm.toLowerCase();
      filtered = filtered.filter(
        (student) =>
          student.fullName.toLowerCase().includes(query) ||
          student.displayEmail.toLowerCase().includes(query) ||
          student.displayRollNumber.toLowerCase().includes(query)
      );
    }

    if (filterClass !== "all") {
      filtered = filtered.filter(
        (s) => (s.class_id || s.raw?.class_id)?.toString() === filterClass
      );
    }

    if (filterStatus !== "all") {
      filtered = filtered.filter((s) => s.displayStatus === filterStatus);
    }

    return filtered;
  }, [normalizedStudents, searchTerm, filterClass, filterStatus]);

  const handleDelete = async () => {
    if (!studentToDelete) return;

    try {
      const response = await axios.delete(
        `${API_URL}/students/${studentToDelete.id}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );

      if (response.data.success) {
        toast.success("Student deleted successfully");
        fetchStudents();
      }
    } catch (error) {
      toast.error("Failed to delete student");
    } finally {
      setShowDeleteDialog(false);
      setStudentToDelete(null);
    }
  };

  const exportToCSV = () => {
    if (filteredStudents.length === 0) {
      toast.error("No students to export");
      return;
    }

    const headers = ["Roll Number", "First Name", "Last Name", "Class"];
    if (exportOptions.includeContact) {
      headers.push("Email", "Phone", "Address");
    }
    if (exportOptions.includeGuardian) {
      headers.push("Guardian Name", "Guardian Phone");
    }
    if (exportOptions.includeStatus) {
      headers.push("Status");
    }
    if (exportOptions.includeFees) {
      headers.push("Fees Status");
    }

    const rows = filteredStudents.map((student) => {
      const row = [
        student.displayRollNumber || "N/A",
        student.displayFirstName || "",
        student.displayLastName || "",
        student.displayClassName || "N/A",
      ];

      if (exportOptions.includeContact) {
        row.push(
          student.displayEmail || "N/A",
          student.displayPhone || "N/A",
          student.displayAddress || "N/A"
        );
      }

      if (exportOptions.includeGuardian) {
        row.push(
          student.displayGuardianName || "N/A",
          student.displayGuardianPhone || "N/A"
        );
      }

      if (exportOptions.includeStatus) {
        row.push(student.displayStatus || "active");
      }

      if (exportOptions.includeFees) {
        row.push(student.displayFeesStatus || "pending");
      }

      return row;
    });

    const csv = [headers, ...rows].map((row) => row.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `students_${new Date().toISOString().split("T")[0]}.csv`;
    a.click();
    toast.success("Student export ready");
  };

  const getStatusBadge = (status) => {
    if (status === "active") {
      return <Badge className="bg-green-100 text-green-800">Active</Badge>;
    }
    return <Badge className="bg-gray-100 text-gray-800">Inactive</Badge>;
  };

  const handleExportToggle = (key, checked) => {
    setExportOptions((prev) => ({ ...prev, [key]: Boolean(checked) }));
  };

  const getFeesBadge = (status) => {
    if (status === "paid") {
      return <Badge className="bg-blue-100 text-blue-800">Paid</Badge>;
    } else if (status === "partial") {
      return <Badge className="bg-yellow-100 text-yellow-800">Partial</Badge>;
    }
    return <Badge className="bg-red-100 text-red-800">Pending</Badge>;
  };

  const handleAddStudent = () => {
    setShowStudentModal(true);
  };

  const handleModalSuccess = () => {
    fetchStudents();
    setShowStudentModal(false);
  };

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Student Management</h1>
          <p className="text-gray-500 mt-1">
            Manage students, track attendance and fees
          </p>
        </div>
        <Button onClick={handleAddStudent} className="gap-2">
          <Plus className="w-4 h-4" />
          Add Student
        </Button>
      </div>

      {/* Bulk Upload */}
      <Card>
        <CardHeader>
          <CardTitle>Bulk Upload Students (CSV)</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
              <Label htmlFor="csvfile">CSV File</Label>
              <Input id="csvfile" type="file" accept=".csv,.xlsx" ref={fileInputRef} />
              <p className="text-xs text-gray-500 mt-1">Use the template to ensure correct headers.</p>
            </div>
            <div className="flex gap-2">
              <Button variant="outline" type="button" onClick={handleTemplateDownload}>Download Template</Button>
              <Button type="button" onClick={handleBulkUpload} disabled={uploading}>{uploading ? 'Uploading...' : 'Upload CSV'}</Button>
            </div>
          </div>
          <div className="mt-4 border-t pt-4">
            <p className="text-sm font-semibold text-gray-700 mb-2">Export Columns</p>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="export-contact"
                  checked={exportOptions.includeContact}
                  onCheckedChange={(checked) => handleExportToggle("includeContact", checked)}
                />
                <Label htmlFor="export-contact" className="text-sm font-normal">
                  Contact details
                </Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="export-guardian"
                  checked={exportOptions.includeGuardian}
                  onCheckedChange={(checked) => handleExportToggle("includeGuardian", checked)}
                />
                <Label htmlFor="export-guardian" className="text-sm font-normal">
                  Guardian info
                </Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="export-status"
                  checked={exportOptions.includeStatus}
                  onCheckedChange={(checked) => handleExportToggle("includeStatus", checked)}
                />
                <Label htmlFor="export-status" className="text-sm font-normal">
                  Status column
                </Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="export-fees"
                  checked={exportOptions.includeFees}
                  onCheckedChange={(checked) => handleExportToggle("includeFees", checked)}
                />
                <Label htmlFor="export-fees" className="text-sm font-normal">
                  Fees column
                </Label>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Analytics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Total Students
            </CardTitle>
            <GraduationCap className="w-5 h-5 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">{analytics.total}</div>
            <p className="text-xs text-gray-500 mt-1">All enrolled students</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Active Students
            </CardTitle>
            <UserCheck className="w-5 h-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.active}
            </div>
            <p className="text-xs text-gray-500 mt-1">
              {analytics.total > 0
                ? Math.round((analytics.active / analytics.total) * 100)
                : 0}
              % of total students
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Fees Completion
            </CardTitle>
            <DollarSign className="w-5 h-5 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.feesPaid}%
            </div>
            <p className="text-xs text-gray-500 mt-1">Students with paid fees</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Enrollment Rate
            </CardTitle>
            <TrendingUp className="w-5 h-5 text-orange-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-gray-900">
              {analytics.total > 0 ? "+12%" : "0%"}
            </div>
            <p className="text-xs text-gray-500 mt-1">From last month</p>
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
                placeholder="Search by name, email, or roll number..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>

            {/* Class Filter */}
            <Select value={filterClass} onValueChange={setFilterClass}>
              <SelectTrigger className="w-[180px]">
                <BookOpen className="w-4 h-4 mr-2" />
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

            {/* Status Filter */}
            <Select value={filterStatus} onValueChange={setFilterStatus}>
              <SelectTrigger className="w-[180px]">
                <Filter className="w-4 h-4 mr-2" />
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
              </SelectContent>
            </Select>

            {/* Action Buttons */}
            <Button variant="outline" onClick={fetchStudents} className="gap-2">
              <RefreshCw className="w-4 h-4" />
              Refresh
            </Button>
            <Button variant="outline" onClick={exportToCSV} className="gap-2">
              <Download className="w-4 h-4" />
              Export
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Students Table */}
      <Card>
        <CardHeader>
          <CardTitle>Students List ({filteredStudents.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">
              <RefreshCw className="w-8 h-8 animate-spin mx-auto text-gray-400" />
              <p className="mt-2 text-gray-500">Loading students...</p>
            </div>
          ) : filteredStudents.length === 0 ? (
            <div className="text-center py-8">
              <GraduationCap className="w-12 h-12 mx-auto text-gray-300" />
              <p className="mt-2 text-gray-500">No students found</p>
              <Button
                variant="link"
                onClick={() => navigate("/Admin/addstudents")}
                className="mt-2"
              >
                Add your first student
              </Button>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID Number</TableHead>
                    <TableHead>First Name</TableHead>
                    <TableHead>Last Name</TableHead>
                    <TableHead>Contact</TableHead>
                    <TableHead>Class</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Fees</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredStudents.map((student) => (
                    <TableRow key={student.id}>
                      <TableCell>
                        <div className="font-mono text-sm font-medium">
                          {student.displayRollNumber || "-"}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="font-medium">{student.displayFirstName || "-"}</div>
                      </TableCell>
                      <TableCell>
                        <div className="font-medium">{student.displayLastName || "-"}</div>
                      </TableCell>
                      <TableCell>
                        <div>
                          <div className="text-sm text-gray-900 flex items-center gap-1">
                            <Mail className="w-3 h-3" />
                            {student.displayEmail}
                          </div>
                          {student.displayPhone && student.displayPhone !== "Not provided" ? (
                            <div className="text-sm text-gray-500 flex items-center gap-1 mt-1">
                              <Phone className="w-3 h-3 text-gray-400" />
                              {student.displayPhone}
                            </div>
                          ) : (
                            <span className="text-gray-400 text-sm">No phone</span>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        {student.displayClassName && student.displayClassName !== "Not assigned" ? (
                          <div className="flex items-center gap-1 text-sm">
                            <BookOpen className="w-3 h-3 text-gray-400" />
                            {student.displayClassName}
                          </div>
                        ) : (
                          <span className="text-gray-400 text-sm">-</span>
                        )}
                      </TableCell>
                      <TableCell>{getStatusBadge(student.displayStatus)}</TableCell>
                      <TableCell>{getFeesBadge(student.displayFeesStatus)}</TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setViewStudent(student)}
                          >
                            <Eye className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleEditStudent(student.raw || student)}
                          >
                            <Edit className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setStudentToDelete(student.raw || student);
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

      {/* Student Modal */}
      <StudentModal
        open={showStudentModal}
        onOpenChange={setShowStudentModal}
        onSuccess={handleModalSuccess}
      />

      <EditStudentModal
        open={showEditStudentModal}
        onOpenChange={setShowEditStudentModal}
        student={editingStudent}
        onSuccess={handleEditStudentSuccess}
      />

      <Dialog
        open={Boolean(viewStudent)}
        onOpenChange={(open) => {
          if (!open) setViewStudent(null);
        }}
      >
        <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
          {studentDetail ? (
            <>
              <DialogHeader>
                <DialogTitle className="sr-only">Student Details</DialogTitle>
              </DialogHeader>
              <div className="rounded-2xl bg-gradient-to-r from-teal-600 via-cyan-600 to-blue-500 text-white shadow-lg p-5">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                  <div>
                    <p className="text-xs uppercase tracking-widest text-white/80">
                      Student Profile
                    </p>
                    <h3 className="text-2xl font-bold mt-1">
                      {studentDetail.student.fullName || "Student"}
                    </h3>
                    <p className="text-white/80">
                      ID: {studentDetail.student.displayRollNumber || "—"}
                    </p>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {studentDetail.tags.length > 0 ? (
                      studentDetail.tags.map((tag) => (
                        <Badge key={tag} variant="secondary" className="bg-white/15 text-white">
                          {tag}
                        </Badge>
                      ))
                    ) : (
                      <Badge variant="secondary" className="bg-white/15 text-white">
                        {studentDetail.stats[1].value}
                      </Badge>
                    )}
                  </div>
                </div>
                <div className="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                  {studentDetail.stats.map((stat) => (
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
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500 mb-2">
                    Personal Information
                  </p>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {studentDetail.personalRows.map((row) => (
                      <div key={row.label} className="rounded-xl border border-gray-100 p-4">
                        <p className="text-[11px] font-semibold uppercase tracking-widest text-gray-500">
                          {row.label}
                        </p>
                        <p className="mt-1 text-sm font-medium text-gray-900">
                          {row.value}
                        </p>
                      </div>
                    ))}
                  </div>
                </div>

                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500 mb-2">
                    Contact Information
                  </p>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {studentDetail.contactRows.map((row) => (
                      <div key={row.label} className="rounded-xl border border-gray-100 p-4">
                        <p className="text-[11px] font-semibold uppercase tracking-widest text-gray-500">
                          {row.label}
                        </p>
                        <p className="mt-1 text-sm font-medium text-gray-900">
                          {row.value}
                        </p>
                      </div>
                    ))}
                  </div>
                </div>

                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500 mb-2">
                    Guardian Information
                  </p>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {studentDetail.guardianRows.map((row) => (
                      <div key={row.label} className="rounded-xl border border-gray-100 p-4">
                        <p className="text-[11px] font-semibold uppercase tracking-widest text-gray-500">
                          {row.label}
                        </p>
                        <p className="mt-1 text-sm font-medium text-gray-900">
                          {row.value}
                        </p>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              <DialogFooter>
                <Button variant="outline" onClick={() => setViewStudent(null)}>
                  Close
                </Button>
              </DialogFooter>
            </>
          ) : null}
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Student?</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete {studentToDelete?.name}? This action
              cannot be undone and will remove all associated data including grades
              and fees records.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setStudentToDelete(null)}>
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

export default StudentManagement;









