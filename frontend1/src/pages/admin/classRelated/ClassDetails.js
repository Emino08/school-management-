import { useCallback, useEffect, useMemo, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate, useParams } from "react-router-dom";
import { getClassDetails, getClassStudents, getSubjectList } from "@/redux/sclassRelated/sclassHandle";
import { deleteUser } from "@/redux/userRelated/userHandle";
import { resetSubjects } from "@/redux/sclassRelated/sclassSlice";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Loader2, Users, BookOpen, UserCheck, GraduationCap, ArrowLeft, Calendar, Shield } from "lucide-react";
import { toast } from "sonner";
import axios from "@/redux/axiosConfig";
import SubjectModal from "@/components/modals/SubjectModal";
import StudentModal from "@/components/modals/StudentModal";
import TeacherModal from "@/components/modals/TeacherModal";

const emptyArray = [];

const ClassDetails = () => {
  const params = useParams();
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { subjectsList, sclassStudents, sclassDetails, loading } = useSelector((state) => state.sclass);
  const { currentUser } = useSelector((state) => state.user);

  const classID = params.id;
  const classNumericId = Number(classID);
  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  const [activeTab, setActiveTab] = useState("overview");
  const [subjectModalOpen, setSubjectModalOpen] = useState(false);
  const [studentModalOpen, setStudentModalOpen] = useState(false);
  const [teacherModalOpen, setTeacherModalOpen] = useState(false);
  const [activityLogs, setActivityLogs] = useState([]);
  const [activityLoading, setActivityLoading] = useState(false);
  const [detailModal, setDetailModal] = useState(null);
  const [attendanceModal, setAttendanceModal] = useState(null);

  const refreshClassData = useCallback(() => {
    dispatch(getClassDetails(classID, "Sclass"));
    dispatch(getSubjectList(classID, "ClassSubjects"));
    dispatch(getClassStudents(classID));
  }, [dispatch, classID]);

  useEffect(() => {
    refreshClassData();
  }, [refreshClassData]);

  const fetchActivityLogs = useCallback(async () => {
    if (!currentUser?.token) return;
    setActivityLoading(true);
    try {
      const res = await axios.get(`${API_URL}/admin/activity-logs?entity_type=class&limit=50`, {
        headers: { Authorization: `Bearer ${currentUser.token}` },
      });
      const logs = Array.isArray(res.data?.logs) ? res.data.logs : [];
      const filtered = logs.filter((log) => log.entity_id && Number(log.entity_id) === classNumericId);
      setActivityLogs(filtered.slice(0, 5));
    } catch (error) {
      console.error("Failed to fetch activity logs", error);
    } finally {
      setActivityLoading(false);
    }
  }, [API_URL, classNumericId, currentUser?.token]);

  useEffect(() => {
    fetchActivityLogs();
  }, [fetchActivityLogs]);

  const className = useMemo(
    () => sclassDetails?.class_name || sclassDetails?.sclassName || "Class",
    [sclassDetails]
  );
  const gradeLevel = sclassDetails?.grade_level || sclassDetails?.grade || "N/A";
  const section = sclassDetails?.section || sclassDetails?.room || "Not set";
  const classMaster = sclassDetails?.class_master_name || sclassDetails?.classMaster || "Not Assigned";
  const capacity = sclassDetails?.capacity ?? "—";
  const shift = sclassDetails?.shift || "Day";

  const numberOfSubjects = Array.isArray(subjectsList) ? subjectsList.length : 0;
  const numberOfStudents = Array.isArray(sclassStudents) ? sclassStudents.length : 0;
  const hasSubjects = numberOfSubjects > 0;
  const hasStudents = numberOfStudents > 0;

  const subjectRows = useMemo(() => {
    if (!Array.isArray(subjectsList)) return emptyArray;
    return subjectsList.map((subject) => {
      const teacherSource =
        (subject.teacher && typeof subject.teacher === "object" ? subject.teacher : null) ||
        (subject.assigned_teacher && typeof subject.assigned_teacher === "object" ? subject.assigned_teacher : null) ||
        {};
      const teacherString = typeof subject.teacher === "string" ? subject.teacher : null;
      const teacherName =
        subject.teacher_name ||
        subject.teacherName ||
        teacherSource.name ||
        teacherString ||
        "Unassigned";
      const teacherEmail = subject.teacher_email || teacherSource.email || subject.teacherEmail || null;
      const teacherPhone = subject.teacher_phone || teacherSource.phone || subject.teacherPhone || null;
      return {
        id: subject.id || subject._id,
        name: subject.subject_name || subject.subName || subject.name || "Subject",
        code: subject.subject_code || subject.subCode || subject.code || "-",
        teacherId: subject.teacher_id || teacherSource.id || null,
        teacherName,
        teacherEmail,
        teacherPhone,
        raw: subject,
      };
    });
  }, [subjectsList]);

  const teacherAssignments = useMemo(() => {
    if (!Array.isArray(subjectRows) || subjectRows.length === 0) return [];
    const map = new Map();
    subjectRows.forEach((subject) => {
      const key = subject.teacherId || `unassigned-${subject.id}`;
      if (!map.has(key)) {
        map.set(key, {
          id: subject.teacherId || null,
          name: subject.teacherName || "Unassigned teacher",
          subjects: [],
          subjectDetails: [],
          teacherEmail: subject.teacherEmail || null,
          teacherPhone: subject.teacherPhone || null,
        });
      }
      const entry = map.get(key);
      entry.subjects.push(subject.name);
      entry.subjectDetails.push(subject);
      entry.teacherEmail = entry.teacherEmail || subject.teacherEmail || null;
      entry.teacherPhone = entry.teacherPhone || subject.teacherPhone || null;
    });
    return Array.from(map.values());
  }, [subjectRows]);

  const studentRows = useMemo(() => {
    if (!Array.isArray(sclassStudents)) return emptyArray;
    return sclassStudents.map((student) => ({
      id: student._id || student.id,
      name: student.name,
      rollNum: student.rollNum || student.id_number || student.admission_no,
      guardian: student.parentName || student.guardianName || student.parent_name,
      email: student.email,
      phone: student.phone || student.parentPhone || student.guardianPhone,
      gender: student.gender,
      status: student.status || student.enrollment_status,
      raw: student,
    }));
  }, [sclassStudents]);

  const openDetailModal = (type, data) => setDetailModal({ type, data });
  const closeDetailModal = () => setDetailModal(null);
  const openAttendanceModal = (student) => setAttendanceModal(student);
  const closeAttendanceModal = () => setAttendanceModal(null);

  const confirmRemoval = (label, onConfirm) => {
    toast.warning(`Remove ${label}?`, {
      description: "This action cannot be undone.",
      duration: 6000,
      action: {
        label: "Remove",
        onClick: () => onConfirm(),
      },
    });
  };

  const deleteHandler = async (deleteID, address) => {
    const labelMap = {
      Subject: "Subject",
      Student: "Student",
      SubjectsClass: "All subjects",
      StudentsClass: "All students",
    };
    try {
      await dispatch(deleteUser(deleteID, address));
      toast.success(`${labelMap[address] || "Record"} removed successfully`);
      if (address === "Subject" || address === "SubjectsClass") {
        dispatch(resetSubjects());
      }
      refreshClassData();
      fetchActivityLogs();
    } catch (error) {
      console.error("Delete failed", error);
      toast.error(`Unable to remove ${labelMap[address] || "record"}`);
    }
  };

  const statCards = [
    {
      label: "Students",
      value: numberOfStudents,
      icon: Users,
      accent: "bg-blue-50 text-blue-700",
    },
    {
      label: "Subjects",
      value: numberOfSubjects,
      icon: BookOpen,
      accent: "bg-purple-50 text-purple-700",
    },
    {
      label: "Class Master",
      value: classMaster,
      icon: UserCheck,
      accent: "bg-emerald-50 text-emerald-700",
    },
    {
      label: "Capacity",
      value: capacity,
      icon: GraduationCap,
      accent: "bg-orange-50 text-orange-700",
    },
  ];

  const handleAddSubjects = () => setSubjectModalOpen(true);
  const handleAddStudents = () => setStudentModalOpen(true);
  const handleAddTeacher = () => setTeacherModalOpen(true);

  const handleModalSuccess = () => {
    refreshClassData();
    fetchActivityLogs();
  };

  const formatDateValue = (value) => {
    if (!value) return null;
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      return value;
    }
    return parsed.toLocaleDateString();
  };

  const detailModalConfig = useMemo(() => {
    if (!detailModal) return null;
    const { type, data } = detailModal;
    if (!data) return null;

    if (type === "subject") {
      const subjectInfo = data.raw || {};
      return {
        title: "Subject Details",
        description: `${data.name || "Subject"} overview`,
        fields: [
          { label: "Subject Name", value: data.name },
          { label: "Subject Code", value: data.code },
          { label: "Teacher", value: data.teacherName || "Unassigned" },
          { label: "Teacher Email", value: data.teacherEmail },
          { label: "Teacher Phone", value: data.teacherPhone },
          { label: "Subject ID", value: data.id },
          { label: "Class", value: className },
          { label: "Notes", value: subjectInfo.description },
        ],
        footerButtons: data.id
          ? [
              {
                label: "Open Subject Page",
                onClick: () => {
                  setDetailModal(null);
                  navigate(`/Admin/class/subject/${classID}/${data.id}`);
                },
              },
            ]
          : [],
      };
    }

    if (type === "student") {
      const info = data.raw || {};
      const guardianName = data.guardian || info.guardian || info.parent_name || info.parentName;
      const guardianContact =
        info.parentPhone || info.parent_phone || info.guardianPhone || info.guardian_phone || data.phone;
      return {
        title: "Student Details",
        description: `Profile overview for ${data.name}`,
        fields: [
          { label: "Full Name", value: data.name },
          { label: "ID Number", value: data.rollNum },
          { label: "Gender", value: data.gender || info.gender },
          { label: "Date of Birth", value: formatDateValue(info.date_of_birth) },
          { label: "Status", value: data.status || "Active" },
          { label: "Guardian", value: guardianName },
          { label: "Guardian Contact", value: guardianContact },
          { label: "Email", value: data.email || info.email },
          { label: "Phone", value: data.phone || info.phone },
          { label: "Address", value: info.address },
        ],
        footerButtons: data.id
          ? [
              {
                label: "View Attendance",
                variant: "outline",
                onClick: () => {
                  setDetailModal(null);
                  navigate(`/Admin/students/student/attendance/${data.id}`);
                },
              },
              {
                label: "Open Student Page",
                onClick: () => {
                  setDetailModal(null);
                  navigate(`/Admin/students/student/${data.id}`);
                },
              },
            ]
          : [],
      };
    }

    if (type === "teacher") {
      const subjectNames =
        data.subjectDetails?.map(
          (subject) => `${subject.name}${subject.code ? ` (${subject.code})` : ""}`
        ) ?? data.subjects ?? [];
      return {
        title: "Teacher Details",
        description: data.name || "Teacher assignment overview",
        fields: [
          { label: "Teacher Name", value: data.name },
          { label: "Teacher ID", value: data.id || "Unassigned" },
          { label: "Email", value: data.teacherEmail },
          { label: "Phone", value: data.teacherPhone },
          { label: "Assigned Subjects", value: subjectNames, isList: true },
        ],
        footerButtons:
          data.id && data.id !== null
            ? [
                {
                  label: "Open Teacher Page",
                  onClick: () => {
                    setDetailModal(null);
                    navigate(`/Admin/teachers/teacher/${data.id}`);
                  },
                },
              ]
            : [],
      };
    }

    return null;
  }, [detailModal, className, classID, navigate, formatDateValue]);

  const attendanceModalConfig = useMemo(() => {
    if (!attendanceModal) return null;
    const raw = attendanceModal.raw || {};
    const summarySource = raw.attendanceSummary || raw.attendance_summary || raw.attendanceStats || {};
    const recordsSource =
      raw.attendance_records ||
      raw.attendanceRecords ||
      raw.attendance ||
      raw.attendance_history ||
      [];
    const recordsArray = Array.isArray(recordsSource) ? recordsSource : [];

    const normalizedRecords = recordsArray.map((record, index) => {
      const dateValue = record.date || record.day || record.recorded_at || record.created_at;
      const statusValue =
        record.status ||
        record.attendance_status ||
        record.presence ||
        record.state ||
        record.mark ||
        "unknown";
      return {
        date: formatDateValue(dateValue) || `Record ${index + 1}`,
        status: (statusValue || "").toString().toLowerCase(),
        note: record.note || record.comment || record.reason || null,
      };
    });

    const presentCount =
      summarySource.present ??
      normalizedRecords.filter((rec) => rec.status.includes("present")).length;
    const absentCount =
      summarySource.absent ??
      normalizedRecords.filter((rec) => rec.status.includes("absent")).length;
    const lateCount =
      summarySource.late ??
      normalizedRecords.filter((rec) => rec.status.includes("late")).length;

    const totalCount = summarySource.total ?? normalizedRecords.length;
    const attendanceRate =
      totalCount > 0 ? Math.round(((presentCount || 0) / totalCount) * 100) : null;

    return {
      title: "Attendance Overview",
      description: `${attendanceModal.name || "Student"} • ${className}`,
      stats: [
        { label: "Total Records", value: totalCount },
        { label: "Present", value: presentCount },
        { label: "Absent", value: absentCount },
        { label: "Late", value: lateCount },
        {
          label: "Attendance Rate",
          value: attendanceRate !== null ? `${attendanceRate}%` : "N/A",
        },
      ],
      records: normalizedRecords,
    };
  }, [attendanceModal, className, formatDateValue]);

  if (loading && !sclassDetails) {
    return (
      <div className="flex items-center justify-center h-[60vh]">
        <Loader2 className="h-8 w-8 animate-spin text-purple-600" />
      </div>
    );
  }

  const OverviewTab = () => (
    <div className="space-y-6">
      <Card className="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 text-white border-none shadow-lg">
        <CardHeader className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <CardDescription className="text-white/70 flex items-center gap-2 text-sm uppercase tracking-wide">
              <Calendar className="h-4 w-4" />
              Academic Overview
            </CardDescription>
            <CardTitle className="text-3xl font-bold mt-2">{className}</CardTitle>
            <p className="text-white/80 mt-1">
              Grade {gradeLevel} • Section {section}
            </p>
          </div>
          <div className="flex flex-wrap gap-3">
            <Button variant="secondary" onClick={handleAddStudents}>
              Add Students
            </Button>
            <Button variant="secondary" className="bg-white/15 hover:bg-white/25" onClick={handleAddSubjects}>
              Add Subjects
            </Button>
          </div>
        </CardHeader>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {statCards.map((stat) => {
          const Icon = stat.icon;
          return (
            <Card key={stat.label} className="border border-gray-100 shadow-sm">
              <CardContent className="p-4 flex items-center gap-4">
                <div className={`p-3 rounded-2xl ${stat.accent}`}>
                  <Icon className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">{stat.label}</p>
                  <p className="text-xl font-semibold text-gray-900 break-words">{stat.value}</p>
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Class Information</CardTitle>
          <CardDescription>Quick overview of this class configuration</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <p className="text-sm text-gray-500">Grade Level</p>
              <p className="text-lg font-semibold text-gray-900">{gradeLevel}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Section / Room</p>
              <p className="text-lg font-semibold text-gray-900">{section}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Class Shift</p>
              <p className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <Shield className="h-4 w-4 text-purple-600" />
                {shift}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Current Enrollment</p>
              <p className="text-lg font-semibold text-gray-900">
                {numberOfStudents}
                {capacity !== "—" && <span className="text-sm text-gray-500"> / {capacity}</span>}
              </p>
            </div>
          </div>
          <Separator />
          <div className="flex flex-wrap gap-3">
            <Badge variant="secondary" className="text-sm">
              ID: {classID}
            </Badge>
          <Badge variant="secondary" className="text-sm">
            Academic Year: {sclassDetails?.academic_year || "Current"}
          </Badge>
        </div>
      </CardContent>
    </Card>

      <Card>
        <CardHeader className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <CardTitle>Recent Class Activity</CardTitle>
            <CardDescription>Audit trail powered by the activity log system</CardDescription>
          </div>
          <Button variant="outline" size="sm" onClick={() => navigate("/Admin/activity-logs")}>
            View All Logs
          </Button>
        </CardHeader>
        <CardContent>
          {activityLoading ? (
            <div className="flex items-center justify-center py-6 text-gray-500">
              <Loader2 className="h-5 w-5 animate-spin mr-2" />
              Loading activity...
            </div>
          ) : activityLogs.length === 0 ? (
            <div className="text-center py-6 text-gray-500">
              No recent log entries for this class.
            </div>
          ) : (
            <div className="space-y-4">
              {activityLogs.map((log) => (
                <div key={log.id} className="flex items-start justify-between border rounded-xl p-3">
                  <div>
                    <p className="text-sm font-semibold text-gray-900 capitalize">
                      {log.activity_type?.replace(/_/g, " ") || "Activity"}
                    </p>
                    <p className="text-sm text-gray-600">{log.description}</p>
                  </div>
                  <span className="text-xs text-gray-500 ml-4 whitespace-nowrap">
                    {log.created_at ? new Date(log.created_at).toLocaleString() : ""}
                  </span>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );

  const SubjectsTab = () => (
    <Card className="shadow-sm">
      <CardHeader className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <CardTitle>Subjects ({numberOfSubjects})</CardTitle>
          <CardDescription>Manage all subjects assigned to this class</CardDescription>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button onClick={handleAddSubjects}>Add Subject</Button>
          {hasSubjects && (
            <Button
              variant="outline"
              onClick={() => confirmRemoval("all subjects", () => deleteHandler(classID, "SubjectsClass"))}
            >
              Remove All
            </Button>
          )}
        </div>
      </CardHeader>
      <CardContent>
        {!hasSubjects ? (
          <div className="text-center py-16 text-gray-500">No subjects assigned yet.</div>
        ) : (
          <div className="w-full overflow-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Subject Name</TableHead>
                  <TableHead>Code</TableHead>
                  <TableHead>Teacher</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {subjectRows.map((subject) => (
                  <TableRow key={subject.id}>
                    <TableCell className="font-medium">{subject.name}</TableCell>
                    <TableCell>{subject.code ?? "-"}</TableCell>
                    <TableCell className="text-sm text-gray-600">
                      {subject.teacherId ? (
                        subject.teacherName
                      ) : (
                        <Badge variant="outline">Unassigned</Badge>
                      )}
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-red-600"
                        onClick={() =>
                          confirmRemoval(`subject "${subject.name}"`, () => deleteHandler(subject.id, "Subject"))
                        }
                      >
                        Remove
                      </Button>
                      <Button size="sm" variant="outline" onClick={() => openDetailModal("subject", subject)}>
                        View
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  );

  const StudentsTab = () => (
    <Card className="shadow-sm">
      <CardHeader className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <CardTitle>Students ({numberOfStudents})</CardTitle>
          <CardDescription>Overview of students assigned to this class</CardDescription>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button onClick={handleAddStudents}>Add Students</Button>
          {hasStudents && (
            <Button
              variant="outline"
              onClick={() => confirmRemoval("all students", () => deleteHandler(classID, "StudentsClass"))}
            >
              Remove All
            </Button>
          )}
        </div>
      </CardHeader>
      <CardContent>
        {!hasStudents ? (
          <div className="text-center py-16 text-gray-500">No students enrolled yet.</div>
        ) : (
          <div className="w-full overflow-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>ID Number</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {studentRows.map((student) => (
                  <TableRow key={student.id}>
                    <TableCell className="font-medium">{student.name}</TableCell>
                    <TableCell>{student.rollNum || "—"}</TableCell>
                    <TableCell className="text-right space-x-2">
                      <Button variant="ghost" size="sm" onClick={() => openDetailModal("student", student)}>
                        View
                      </Button>
                      <Button variant="ghost" size="sm" onClick={() => openAttendanceModal(student)}>
                        Attendance
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-red-600"
                        onClick={() =>
                          confirmRemoval(`student "${student.name}"`, () => deleteHandler(student.id, "Student"))
                        }
                      >
                        Remove
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  );

  const TeachersTab = () => (
    <Card className="shadow-sm">
      <CardHeader className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <CardTitle>Teachers ({teacherAssignments.length})</CardTitle>
          <CardDescription>Active teacher assignments for this class and academic year</CardDescription>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button onClick={handleAddTeacher}>Add Teacher</Button>
          <Button variant="outline" onClick={() => navigate("/Admin/teachers-management")}>
            Manage Teachers
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        {teacherAssignments.length === 0 ? (
          <div className="rounded-xl border border-dashed border-gray-300 p-6 text-center text-gray-500">
            No teacher assignments available for the current academic year.
          </div>
        ) : (
          <div className="w-full overflow-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Teacher</TableHead>
                  <TableHead>Assigned Subjects</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {teacherAssignments.map((teacher) => (
                  <TableRow key={teacher.id ?? teacher.name}>
                    <TableCell className="font-medium">{teacher.name}</TableCell>
                    <TableCell className="text-gray-600">
                      {teacher.subjects.join(", ")}
                    </TableCell>
                    <TableCell className="text-right">
                      <Button variant="ghost" size="sm" onClick={() => openDetailModal("teacher", teacher)}>
                        View
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  );

  return (
    <div className="max-w-6xl mx-auto px-4 py-6 space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate(-1)}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back
        </Button>
        <div>
          <p className="text-xs uppercase tracking-wide text-gray-500">Class</p>
          <h1 className="text-2xl font-bold text-gray-900">{className}</h1>
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="grid w-full grid-cols-2 md:w-auto md:grid-cols-4">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="students">Students</TabsTrigger>
          <TabsTrigger value="subjects">Subjects</TabsTrigger>
          <TabsTrigger value="teachers">Teachers</TabsTrigger>
        </TabsList>

        <div className="mt-6 space-y-6">
          <TabsContent value="overview">
            <OverviewTab />
          </TabsContent>
          <TabsContent value="students">
            <StudentsTab />
          </TabsContent>
          <TabsContent value="subjects">
            <SubjectsTab />
          </TabsContent>
          <TabsContent value="teachers">
            <TeachersTab />
          </TabsContent>
        </div>
      </Tabs>

      <SubjectModal
        open={subjectModalOpen}
        onOpenChange={setSubjectModalOpen}
        onSuccess={() => {
          setSubjectModalOpen(false);
          handleModalSuccess();
        }}
        defaultClassId={classID}
      />

      <StudentModal
        open={studentModalOpen}
        onOpenChange={setStudentModalOpen}
        preSelectedClass={classID}
        onSuccess={() => {
          setStudentModalOpen(false);
          handleModalSuccess();
        }}
      />

      <TeacherModal
        open={teacherModalOpen}
        onOpenChange={setTeacherModalOpen}
        preSelectedClass={classID}
        onSuccess={() => {
          setTeacherModalOpen(false);
          handleModalSuccess();
        }}
      />

      <Dialog
        open={Boolean(detailModal)}
        onOpenChange={(open) => {
          if (!open) closeDetailModal();
        }}
      >
        <DialogContent className="max-w-lg max-h-[85vh] overflow-y-auto">
          {detailModalConfig ? (
            <>
              <DialogHeader>
                <DialogTitle>{detailModalConfig.title}</DialogTitle>
                {detailModalConfig.description && (
                  <DialogDescription>{detailModalConfig.description}</DialogDescription>
                )}
              </DialogHeader>
              <div className="space-y-4 py-2">
                {detailModalConfig.fields.map((field) => {
                  const value = field.value;
                  const isList = field.isList;
                  const displayList = Array.isArray(value) ? value : [];
                  return (
                    <div key={field.label} className="rounded-lg border border-gray-100 p-3">
                      <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        {field.label}
                      </p>
                      {isList ? (
                        displayList.length > 0 ? (
                          <div className="mt-2 flex flex-wrap gap-2">
                            {displayList.map((item, index) => (
                              <Badge key={`${item}-${index}`} variant="secondary">
                                {item}
                              </Badge>
                            ))}
                          </div>
                        ) : (
                          <p className="mt-1 text-sm text-gray-500">No records</p>
                        )
                      ) : (
                        <p className="mt-1 text-sm font-medium text-gray-900">{value || "—"}</p>
                      )}
                    </div>
                  );
                })}
              </div>
              <DialogFooter className="gap-2">
                <Button variant="outline" onClick={closeDetailModal}>
                  Close
                </Button>
                {detailModalConfig.footerButtons?.map((btn) => (
                  <Button key={btn.label} variant={btn.variant || "default"} onClick={btn.onClick}>
                    {btn.label}
                  </Button>
                ))}
              </DialogFooter>
            </>
          ) : null}
        </DialogContent>
      </Dialog>

      <Dialog
        open={Boolean(attendanceModal)}
        onOpenChange={(open) => {
          if (!open) closeAttendanceModal();
        }}
      >
        <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
          {attendanceModalConfig ? (
            <>
              <DialogHeader>
                <DialogTitle>{attendanceModalConfig.title}</DialogTitle>
                {attendanceModalConfig.description && (
                  <DialogDescription>{attendanceModalConfig.description}</DialogDescription>
                )}
              </DialogHeader>
              <div className="space-y-6 py-2">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                  {attendanceModalConfig.stats.map((stat) => (
                    <Card key={stat.label}>
                      <CardContent className="py-3">
                        <p className="text-xs uppercase tracking-wide text-gray-500">
                          {stat.label}
                        </p>
                        <p className="mt-1 text-lg font-semibold text-gray-900">
                          {stat.value ?? "—"}
                        </p>
                      </CardContent>
                    </Card>
                  ))}
                </div>

                {attendanceModalConfig.records.length === 0 ? (
                  <div className="rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500">
                    No attendance entries recorded for this student in this class yet.
                  </div>
                ) : (
                  <div className="rounded-lg border">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Date</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead>Note</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {attendanceModalConfig.records.map((record, index) => (
                          <TableRow key={`${record.date}-${index}`}>
                            <TableCell className="font-medium">{record.date}</TableCell>
                            <TableCell>
                              <Badge
                                variant={
                                  record.status.includes("present")
                                    ? "secondary"
                                    : record.status.includes("absent")
                                    ? "destructive"
                                    : "outline"
                                }
                              >
                                {record.status || "—"}
                              </Badge>
                            </TableCell>
                            <TableCell className="text-sm text-gray-600">
                              {record.note || "—"}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={closeAttendanceModal}>
                  Close
                </Button>
              </DialogFooter>
            </>
          ) : null}
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default ClassDetails;
