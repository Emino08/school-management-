import React, { useEffect, useState } from "react";
import EditClassModal from "@/components/modals/EditClassModal";
import CreateClassModal from "@/components/modals/CreateClassModal";
import { useNavigate } from "react-router-dom";
import { useSelector } from "react-redux";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
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
  const [showCreateClassModal, setShowCreateClassModal] = useState(false);
  const [showPlacementModal, setShowPlacementModal] = useState(false);
  const [placementEdits, setPlacementEdits] = useState({});
  const [showRolloverModal, setShowRolloverModal] = useState(false);
  const [academicYears, setAcademicYears] = useState([]);
  const [selectedSourceYear, setSelectedSourceYear] = useState("");
  const [selectedTargetYear, setSelectedTargetYear] = useState("");
  const [rolloverLoading, setRolloverLoading] = useState(false);
  // Manual assign (principal)
  const [showManualAssignModal, setShowManualAssignModal] = useState(false);
  const [manualStudents, setManualStudents] = useState([]);
  const [manualTargetYear, setManualTargetYear] = useState("");
  const [manualAssignLoading, setManualAssignLoading] = useState(false);
  // Teacher assignment
  const [teachers, setTeachers] = useState([]);
  const [assignOpen, setAssignOpen] = useState(false);
  const [assignTargetClass, setAssignTargetClass] = useState(null);
  const [selectedTeacherId, setSelectedTeacherId] = useState("");
  // Placement preview modal state
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [previewData, setPreviewData] = useState([]);
  // Unified confirmation dialog
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [confirmAction, setConfirmAction] = useState({ title: "", description: "", onConfirm: null });
  // Assignment type (default to class master)
  const assignType = 'class_master';

  const smallSuccess = (msg) => toast.success(msg, { duration: 1500, position: 'bottom-right' });

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
    fetchTeachers();
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
        // initialize placement edits map
        const map = {};
        classData.forEach(c => {
          map[c.id] = {
            capacity: c.capacity ?? '',
            placement_min_average: c.placement_min_average ?? ''
          };
        });
        setPlacementEdits(map);
      }
    } catch (error) {
      toast.error("Failed to fetch classes");
      console.error("Error:", error);
    } finally {
      setLoading(false);
    }
  };

  const fetchAcademicYears = async () => {
    try {
      const response = await axios.get(`${API_URL}/academic-years`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (response.data.success) {
        const years = response.data.academic_years || response.data.academicYears || [];
        setAcademicYears(years);
        // Preselect current and next by heuristic
        const current = years.find((y) => y.is_current);
        if (current && !selectedSourceYear) setSelectedSourceYear(current.id.toString());
      }
    } catch (e) {
      console.error('Failed to fetch academic years', e);
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

  const fetchTeachers = async () => {
    try {
      const response = await axios.get(`${API_URL}/teachers`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (response.data.success) {
        setTeachers(response.data.teachers || []);
      }
    } catch (error) {
      console.error("Failed to fetch teachers", error);
    }
  };

  const openAssignModal = (cls) => {
    setAssignTargetClass(cls);
    setSelectedTeacherId("");
    setAssignOpen(true);
  };

  const submitAssignment = async () => {
    if (!selectedTeacherId) {
      toast.error("Please select a teacher");
      return;
    }
    try {
      const payload = assignType === 'class_master'
        ? { is_class_master: 1, class_master_of: assignTargetClass.id }
        : { is_exam_officer: 1 };
      const response = await axios.put(
        `${API_URL}/teachers/${selectedTeacherId}`,
        payload,
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );
      if (response.data.success) {
        smallSuccess(assignType === 'class_master' ? 'Class master assigned' : 'Exam officer assigned');
        setAssignOpen(false);
        fetchClasses();
        fetchTeachers();
      } else {
        toast.error(response.data.message || 'Failed to assign');
      }
    } catch (error) {
      console.error('Assignment failed', error);
      toast.error(error.response?.data?.message || 'Assignment failed');
    }
  };

  const getClassMasterTeacherForClass = (classId) =>
    teachers.find(
      (t) => t.is_class_master && parseInt(t.class_master_of) === parseInt(classId)
    );

  const unassignClassMaster = async (cls) => {
    try {
      const master = getClassMasterTeacherForClass(cls.id);
      if (!master) {
        toast.error("No class master found for this class");
        return;
      }
      const response = await axios.put(
        `${API_URL}/teachers/${master.id}`,
        { is_class_master: 0, class_master_of: null },
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );
      if (response.data.success) {
        smallSuccess("Class master unassigned");
        fetchClasses();
        fetchTeachers();
      } else {
        toast.error(response.data.message || "Failed to unassign class master");
      }
    } catch (error) {
      console.error("Unassign failed", error);
      toast.error(error.response?.data?.message || "Unassign failed");
    }
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
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setShowPlacementModal(true)} className="gap-2">
            Placement Settings
          </Button>
          <Button
            variant="outline"
            className="gap-2"
            onClick={async () => {
              setShowPreviewModal(true);
              setPreviewLoading(true);
              try {
                const current = JSON.parse(localStorage.getItem('currentAcademicYear'));
                let yearId = current?.id;
                if (!yearId) {
                  await fetchAcademicYears();
                  const currentYear = (academicYears.find((y) => y.is_current) || academicYears[0]);
                  yearId = currentYear?.id;
                }
                if (yearId) {
                  const res = await axios.get(`${API_URL}/promotions/preview`, {
                    params: { academic_year_id: yearId },
                    headers: { Authorization: `Bearer ${currentUser?.token}` },
                  });
                  if (res.data.success) setPreviewData(res.data.data || []);
                }
              } catch (e) {
                console.error(e);
                toast.error('Failed to load preview');
              } finally {
                setPreviewLoading(false);
              }
            }}
          >
            Preview Placement
          </Button>
          <Button variant="outline" onClick={() => { setShowManualAssignModal(true); fetchAcademicYears(); }} className="gap-2">
            Manual Assign
          </Button>
          <Button variant="outline" onClick={() => { setShowRolloverModal(true); fetchAcademicYears(); }} className="gap-2">
            Rollover Students
          </Button>
          <Button onClick={() => { setEditingClass(null); setShowCreateClassModal(true); }} className="gap-2">
            <Plus className="w-4 h-4" />
            Add Class
          </Button>
        </div>
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
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch auto-rows-fr">
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
            <Card key={cls.id} className="hover:shadow-lg transition-shadow h-full flex flex-col overflow-hidden min-w-0 min-h-[380px] sm:min-h-[380px] md:min-h-[400px]">
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
              <CardContent className="space-y-4 flex flex-col h-full min-w-0">
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
                <div className="border-t pt-4 min-w-0">
                  <div className="text-sm text-gray-600 mb-2">Class Master</div>
                  {cls.class_master_name ? (
                    <div
                      className="relative z-0 flex items-center gap-2 rounded-full bg-green-50 px-3 py-1 text-green-700 border border-green-200 w-full min-w-0"
                    >
                      <UserCheck className="w-4 h-4 flex-shrink-0" />
                      <span
                        className="font-medium overflow-hidden text-ellipsis whitespace-nowrap flex-1 min-w-0"
                        title={cls.class_master_name}
                      >
                        {cls.class_master_name}
                      </span>
                    </div>
                  ) : (
                    <span className="text-gray-400 text-sm">Not assigned</span>
                  )}
                </div>

                {/* Actions */}
                <div className="pt-4 border-t relative z-10 mt-auto min-w-0">
                  <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 min-w-0">
                    <Button
                      variant="outline"
                      size="sm"
                      className="w-full"
                      onClick={() => navigate(`/Admin/classes/view/${cls.id}`)}
                    >
                      <Eye className="w-4 h-4 mr-2" />
                      View
                    </Button>

                    <Button
                      variant="outline"
                      size="sm"
                      className="w-full"
                      onClick={() => openAssignModal(cls)}
                      title={cls.class_master_name ? 'Change Class Master' : 'Assign Class Master'}
                    >
                      <UserCheck className="w-4 h-4 mr-2" />
                      {cls.class_master_name ? 'Change Master' : 'Assign Master'}
                    </Button>

                    {cls.class_master_name && (
                      <Button
                        variant="destructive"
                        size="sm"
                        className="w-full"
                        onClick={() => {
                          setConfirmAction({
                            title: 'Remove Class Master',
                            description: `Remove class master for ${cls.class_name}?`,
                            onConfirm: async () => unassignClassMaster(cls),
                          });
                          setConfirmOpen(true);
                        }}
                        title="Remove Class Master"
                      >
                        Remove
                      </Button>
                    )}

                    <div className="flex items-center gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        className="w-full"
                        onClick={() => handleEditClass(cls)}
                        title="Edit Class"
                      >
                        <Edit className="w-4 h-4" />
                      </Button>
                      <Button
                        variant="destructive"
                        size="sm"
                        className="w-full"
                        onClick={() => {
                          setClassToDelete(cls);
                          setShowDeleteDialog(true);
                        }}
                        title="Delete Class"
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
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

      {/* Create Class Modal */}
      <CreateClassModal
        open={showCreateClassModal}
        onOpenChange={setShowCreateClassModal}
        onSuccess={handleClassModalSuccess}
      />

      {/* Assign Modal */}
      <AlertDialog open={assignOpen} onOpenChange={setAssignOpen}>
        <AlertDialogContent className="sm:max-w-[560px]">
          <AlertDialogHeader>
            <AlertDialogTitle>Assign Class Master</AlertDialogTitle>
            <AlertDialogDescription>{`Select a teacher to be Class Master for ${assignTargetClass?.class_name}.`}</AlertDialogDescription>
          </AlertDialogHeader>
          <div className="space-y-3">
            <Select value={selectedTeacherId} onValueChange={setSelectedTeacherId}>
              <SelectTrigger>
                <SelectValue placeholder="Select Teacher" />
              </SelectTrigger>
              <SelectContent className="max-h-64 overflow-auto z-50">
                {teachers
                  .filter((t) => !t.is_class_master || parseInt(t.class_master_of) === parseInt(assignTargetClass?.id))
                  .sort((a,b) => (a.name||'').localeCompare(b.name||''))
                  .map((t) => (
                    <SelectItem key={t.id} value={t.id.toString()}>
                      {t.name} {t.email ? `(${t.email})` : ''}
                    </SelectItem>
                  ))}
              </SelectContent>
            </Select>
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setAssignOpen(false)}>Cancel</AlertDialogCancel>
            <div className="flex gap-2">
              <Button variant="outline" onClick={submitAssignment} disabled={!selectedTeacherId}>Assign Master</Button>
              <Button
                variant="destructive"
                onClick={async () => {
                  if (!assignTargetClass) return;
                  setConfirmAction({
                    title: 'Remove Class Master',
                    description: `Remove class master for ${assignTargetClass.class_name}?`,
                    onConfirm: async () => {
                      await unassignClassMaster(assignTargetClass);
                      setAssignOpen(false);
                    },
                  });
                  setConfirmOpen(true);
                }}
              >
                Remove Master
              </Button>
            </div>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Rollover Modal */}
      <AlertDialog open={showRolloverModal} onOpenChange={setShowRolloverModal}>
        <AlertDialogContent className="max-w-md">
          <AlertDialogHeader>
            <AlertDialogTitle>Rollover Students</AlertDialogTitle>
            <AlertDialogDescription>
              Move promoted and repeat students from a source year to a target year based on placement settings.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="space-y-3">
            <div className="space-y-1">
              <div className="text-sm text-gray-700">Source Academic Year</div>
              <Select value={selectedSourceYear} onValueChange={setSelectedSourceYear}>
                <SelectTrigger>
                  <SelectValue placeholder="Select source year" />
                </SelectTrigger>
                <SelectContent className="max-h-64 overflow-auto">
                  {academicYears.map((y) => (
                    <SelectItem key={y.id} value={y.id.toString()}>
                      {y.year_name} {y.is_current ? '(Current)' : ''}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1">
              <div className="text-sm text-gray-700">Target Academic Year</div>
              <Select value={selectedTargetYear} onValueChange={setSelectedTargetYear}>
                <SelectTrigger>
                  <SelectValue placeholder="Select target year" />
                </SelectTrigger>
                <SelectContent className="max-h-64 overflow-auto">
                  {academicYears.map((y) => (
                    <SelectItem key={y.id} value={y.id.toString()}>
                      {y.year_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            {selectedSourceYear && selectedTargetYear && selectedSourceYear === selectedTargetYear && (
              <div className="text-xs text-red-600">Source and target years must be different.</div>
            )}
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setShowRolloverModal(false)}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              disabled={rolloverLoading || !selectedSourceYear || !selectedTargetYear || selectedSourceYear === selectedTargetYear}
              onClick={async () => {
                setRolloverLoading(true);
                try {
                  const res = await axios.post(`${API_URL}/promotions/rollover`, {
                    source_academic_year_id: parseInt(selectedSourceYear, 10),
                    target_academic_year_id: parseInt(selectedTargetYear, 10),
                    include_waitlist: document.getElementById('include_waitlist_cb')?.checked || false,
                  }, { headers: { Authorization: `Bearer ${currentUser?.token}` } });
                  if (res.data.success) {
                    toast.success(`Rollover done. Created: ${res.data.created}, Skipped: ${res.data.skipped}, Errors: ${res.data.errors}`);
                    setShowRolloverModal(false);
                  } else {
                    toast.error(res.data.message || 'Rollover failed');
                  }
                } catch (e) {
                  console.error(e);
                  toast.error(e.response?.data?.message || 'Rollover failed');
                } finally {
                  setRolloverLoading(false);
                }
              }}
            >
              {rolloverLoading ? 'Rolling over...' : 'Rollover'}
            </AlertDialogAction>
          </AlertDialogFooter>
          <div className="mt-3">
            <label className="inline-flex items-center gap-2 text-sm text-gray-700">
              <input id="include_waitlist_cb" type="checkbox" className="h-4 w-4" />
              Include waitlist (fill lowest threshold class with capacity)
            </label>
          </div>
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

      {/* Placement Settings Modal */}
      <AlertDialog open={showPlacementModal} onOpenChange={setShowPlacementModal}>
        <AlertDialogContent className="max-w-3xl">
          <AlertDialogHeader>
            <AlertDialogTitle>Placement Settings</AlertDialogTitle>
            <AlertDialogDescription>
              Set per-class capacity and minimum average for placement to this class when promoting.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="overflow-auto max-h-[60vh]">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left border-b">
                  <th className="py-2 pr-3">Class</th>
                  <th className="py-2 pr-3">Grade Level</th>
                  <th className="py-2 pr-3">Capacity</th>
                  <th className="py-2 pr-3">Min Average</th>
                </tr>
              </thead>
              <tbody>
                {classes.map((c) => (
                  <tr key={c.id} className="border-b last:border-0">
                    <td className="py-2 pr-3">{c.class_name}</td>
                    <td className="py-2 pr-3">{c.grade_level}</td>
                    <td className="py-2 pr-3">
                      <Input
                        type="number"
                        min="0"
                        value={placementEdits[c.id]?.capacity ?? ''}
                        onChange={(e) => setPlacementEdits((prev) => ({
                          ...prev,
                          [c.id]: { ...(prev[c.id]||{}), capacity: e.target.value }
                        }))}
                      />
                    </td>
                    <td className="py-2 pr-3">
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value={placementEdits[c.id]?.placement_min_average ?? ''}
                        onChange={(e) => setPlacementEdits((prev) => ({
                          ...prev,
                          [c.id]: { ...(prev[c.id]||{}), placement_min_average: e.target.value }
                        }))}
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setShowPlacementModal(false)}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={async () => {
                // Save all updates
                try {
                  await Promise.all(classes.map(async (c) => {
                    const payload = {};
                    const edit = placementEdits[c.id] || {};
                    if (edit.capacity !== undefined && edit.capacity !== c.capacity) {
                      payload.capacity = edit.capacity === '' ? null : parseInt(edit.capacity, 10);
                    }
                    if (edit.placement_min_average !== undefined && edit.placement_min_average !== c.placement_min_average) {
                      payload.placement_min_average = edit.placement_min_average === '' ? 0 : parseFloat(edit.placement_min_average);
                    }
                    if (Object.keys(payload).length > 0) {
                      await axios.put(`${API_URL}/classes/${c.id}`, payload, { headers: { Authorization: `Bearer ${currentUser?.token}` } });
                    }
                  }));
                  toast.success('Placement settings saved');
                  setShowPlacementModal(false);
                  fetchClasses();
                } catch (e) {
                  console.error(e);
                  toast.error('Failed to save settings');
                }
              }}
            >
              Save Settings
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Placement Preview Modal */}
      <AlertDialog open={showPreviewModal} onOpenChange={setShowPreviewModal}>
        <AlertDialogContent className="max-w-4xl">
          <AlertDialogHeader>
            <AlertDialogTitle>Placement Preview (Current Year)</AlertDialogTitle>
            <AlertDialogDescription>
              Estimated destination counts based on current averages, thresholds, and capacities (strict enforcement). No data is modified.
            </AlertDialogDescription>
          </AlertDialogHeader>
          {previewLoading ? (
            <div className="py-12 text-center text-gray-500">Loading preview...</div>
          ) : (
            <div className="overflow-auto max-h-[60vh]">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left border-b">
                    <th className="py-2 pr-3">Source Class</th>
                    <th className="py-2 pr-3">Destinations</th>
                    <th className="py-2 pr-3 text-right">Overflow</th>
                  </tr>
                </thead>
                <tbody>
                  {previewData.map((row) => (
                    <tr key={row.source_class_id} className="border-b last:border-0 align-top">
                      <td className="py-2 pr-3 whitespace-nowrap">{row.source_class_name}</td>
                      <td className="py-2 pr-3">
                        <div className="flex flex-wrap gap-2">
                          {row.destinations.map((d) => (
                            <span
                              key={d.class_id}
                              className="inline-flex items-center rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 text-xs"
                              title={`Min: ${d.min_avg}${d.capacity !== null ? `, Cap: ${d.capacity}`: ''}`}
                            >
                              {d.class_name}: {d.count}
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="py-2 pr-3 text-right whitespace-nowrap">{row.overflow}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <AlertDialogFooter>
            <AlertDialogAction onClick={() => setShowPreviewModal(false)}>Close</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Manual Assign Modal */}
      <AlertDialog open={showManualAssignModal} onOpenChange={setShowManualAssignModal}>
        <AlertDialogContent className="max-w-4xl">
          <AlertDialogHeader>
            <AlertDialogTitle>Manual Assign (Principal)</AlertDialogTitle>
            <AlertDialogDescription>
              Assign students (e.g., waitlist or repeats) to any available class for a selected target academic year. Capacity is enforced unless overridden.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3 pb-3">
            <div className="space-y-1">
              <div className="text-sm text-gray-700">Target Academic Year</div>
              <Select value={manualTargetYear} onValueChange={setManualTargetYear}>
                <SelectTrigger>
                  <SelectValue placeholder="Select target year" />
                </SelectTrigger>
                <SelectContent className="max-h-64 overflow-auto">
                  {academicYears.map((y) => (
                    <SelectItem key={y.id} value={y.id.toString()}>
                      {y.year_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1 md:col-span-2">
              <div className="text-sm text-gray-700">Source Lists</div>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={async () => {
                    try {
                      const current = JSON.parse(localStorage.getItem('currentAcademicYear'));
                      const yearId = current?.id;
                      if (!yearId) { toast.error('No current academic year found'); return; }
                      const res = await axios.get(`${API_URL}/promotions/waitlist`, { params: { academic_year_id: yearId }, headers: { Authorization: `Bearer ${currentUser?.token}` } });
                      if (res.data.success) setManualStudents(res.data.data || []);
                    } catch (e) { console.error(e); toast.error('Failed to load waitlist'); }
                  }}
                >Waitlist</Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={async () => {
                    try {
                      const current = JSON.parse(localStorage.getItem('currentAcademicYear'));
                      const yearId = current?.id;
                      if (!yearId) { toast.error('No current academic year found'); return; }
                      const res = await axios.get(`${API_URL}/promotions/repeats`, { params: { academic_year_id: yearId }, headers: { Authorization: `Bearer ${currentUser?.token}` } });
                      if (res.data.success) setManualStudents(res.data.data || []);
                    } catch (e) { console.error(e); toast.error('Failed to load repeats'); }
                  }}
                >Repeats</Button>
              </div>
            </div>
          </div>
          <div className="overflow-auto max-h-[60vh]">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left border-b">
                  <th className="py-2 pr-3">Student</th>
                  <th className="py-2 pr-3">Admission</th>
                  <th className="py-2 pr-3">Source Class</th>
                  <th className="py-2 pr-3">Avg</th>
                  <th className="py-2 pr-3">Assign To</th>
                  <th className="py-2 pr-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                {manualStudents.length === 0 ? (
                  <tr><td colSpan={6} className="py-8 text-center text-gray-500">Load waitlist or repeats to manage</td></tr>
                ) : manualStudents.map((st) => (
                  <tr key={`${st.student_id}-${st.class_id}`} className="border-b last:border-0">
                    <td className="py-2 pr-3">{st.student_name}</td>
                    <td className="py-2 pr-3">{st.admission_no}</td>
                    <td className="py-2 pr-3">{st.class_name}</td>
                    <td className="py-2 pr-3">{typeof st.class_average !== 'undefined' ? parseFloat(st.class_average).toFixed(2) : '-'}</td>
                    <td className="py-2 pr-3">
                      <Select value={st._assignClassId || ''} onValueChange={(val) => {
                        setManualStudents(prev => prev.map(p => p === st ? { ...p, _assignClassId: val } : p));
                      }}>
                        <SelectTrigger>
                          <SelectValue placeholder="Select class" />
                        </SelectTrigger>
                        <SelectContent className="max-h-64 overflow-auto">
                          {classes.map((c) => (
                            <SelectItem key={c.id} value={c.id.toString()}>{c.class_name}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </td>
                    <td className="py-2 pr-3 text-right">
                      {(currentUser?.role && (currentUser.role.toLowerCase() === 'principal' || currentUser.role.toLowerCase() === 'admin')) && (
                        <label className="inline-flex items-center gap-2 mr-3 text-xs text-gray-700">
                          <input
                            type="checkbox"
                            className="h-4 w-4"
                            checked={!!st._overrideCapacity}
                            onChange={(e) => setManualStudents(prev => prev.map(p => p === st ? { ...p, _overrideCapacity: e.target.checked } : p))}
                          />
                          Override cap
                        </label>
                      )}
                      <Button
                        size="sm"
                        disabled={manualAssignLoading || !manualTargetYear || !st._assignClassId}
                        onClick={async () => {
                          setManualAssignLoading(true);
                          try {
                            const res = await axios.post(`${API_URL}/promotions/assign`, {
                              student_id: st.student_id,
                              target_academic_year_id: parseInt(manualTargetYear, 10),
                              class_id: parseInt(st._assignClassId, 10),
                              override_capacity: !!st._overrideCapacity,
                            }, { headers: { Authorization: `Bearer ${currentUser?.token}` } });
                            if (res.data.success) {
                              if (st._overrideCapacity) {
                                toast.success(
                                  <div className="flex items-center gap-2">
                                    Assigned
                                    <span className="inline-flex items-center rounded-full bg-amber-100 text-amber-700 border border-amber-200 px-2 py-0.5 text-[10px] uppercase tracking-wide">Override used</span>
                                  </div>
                                );
                              } else {
                                toast.success('Assigned');
                              }
                              setManualStudents(prev => prev.filter(p => !(p.student_id === st.student_id && p.class_id === st.class_id)));
                            } else {
                              toast.error(res.data.message || 'Assign failed');
                            }
                          } catch (e) {
                            console.error(e);
                            toast.error(e.response?.data?.message || 'Assign failed');
                          } finally {
                            setManualAssignLoading(false);
                          }
                        }}
                      >Assign</Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <AlertDialogFooter>
            <AlertDialogAction onClick={() => setShowManualAssignModal(false)}>Close</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

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
