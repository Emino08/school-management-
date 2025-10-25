import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useSelector } from "react-redux";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Calendar,
  Users,
  CheckCircle2,
  XCircle,
  Clock,
  TrendingUp,
  Search,
  Plus,
  Filter,
  Download,
  RefreshCw,
  BookOpen,
  UserCheck,
} from "lucide-react";

const AttendanceManagement = () => {
  const navigate = useNavigate();
  const { currentUser } = useSelector((state) => state.user);
  const [classes, setClasses] = useState([]);
  const [subjects, setSubjects] = useState([]);
  const [students, setStudents] = useState([]);
  const [attendanceRecords, setAttendanceRecords] = useState([]);
  const [filteredRecords, setFilteredRecords] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showMarkDialog, setShowMarkDialog] = useState(false);

  // Filters
  const [selectedClass, setSelectedClass] = useState("");
  const [selectedSubject, setSelectedSubject] = useState("");
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [searchTerm, setSearchTerm] = useState("");

  // Mark attendance form
  const [markingDate, setMarkingDate] = useState(new Date().toISOString().split('T')[0]);
  const [attendanceData, setAttendanceData] = useState({});

  // Analytics state
  const [analytics, setAnalytics] = useState({
    totalStudents: 0,
    presentToday: 0,
    absentToday: 0,
    attendanceRate: 0,
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    fetchClasses();
  }, []);

  useEffect(() => {
    if (selectedClass) {
      fetchSubjects(selectedClass);
      fetchStudentsByClass(selectedClass);
    }
  }, [selectedClass]);

  useEffect(() => {
    if (selectedClass && selectedDate) {
      fetchAttendanceRecords();
    }
  }, [selectedClass, selectedDate, selectedSubject]);

  useEffect(() => {
    filterRecords();
  }, [attendanceRecords, searchTerm]);

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
      toast.error("Failed to fetch classes");
    }
  };

  const fetchSubjects = async (classId) => {
    try {
      const response = await axios.get(`${API_URL}/classes/${classId}/subjects`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        setSubjects(response.data.subjects || []);
      }
    } catch (error) {
      console.error("Error fetching subjects:", error);
    }
  };

  const fetchStudentsByClass = async (classId) => {
    try {
      const response = await axios.get(`${API_URL}/students/class/${classId}`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        const studentData = response.data.students || [];
        setStudents(studentData);
        
        // Initialize attendance data for marking
        const initialData = {};
        studentData.forEach(student => {
          initialData[student.id] = 'present';
        });
        setAttendanceData(initialData);
      }
    } catch (error) {
      console.error("Error fetching students:", error);
      toast.error("Failed to fetch students");
    }
  };

  const fetchAttendanceRecords = async () => {
    setLoading(true);
    try {
      // For now, we'll show student list with their attendance status
      // In a real implementation, you'd fetch actual attendance records from the API
      const response = await axios.get(
        `${API_URL}/students/class/${selectedClass}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` },
        }
      );

      if (response.data.success) {
        const studentData = response.data.students || [];
        
        // Fetch attendance for each student on the selected date
        const recordsWithAttendance = await Promise.all(
          studentData.map(async (student) => {
            try {
              const params = new URLSearchParams();
              params.set('start', selectedDate);
              params.set('end', selectedDate);
              if (selectedSubject) params.set('subject_id', selectedSubject);
              const attResponse = await axios.get(
                `${API_URL}/attendance/students/${student.id}?${params.toString()}`,
                { headers: { Authorization: `Bearer ${currentUser?.token}` } }
              );
              
              const todayRecord = attResponse.data.attendance?.[0];
              
              return {
                ...student,
                status: todayRecord?.status || 'not_marked',
                remarks: todayRecord?.remarks || '',
              };
            } catch (error) {
              return {
                ...student,
                status: 'not_marked',
                remarks: '',
              };
            }
          })
        );

        setAttendanceRecords(recordsWithAttendance);
        calculateAnalytics(recordsWithAttendance);
      }
    } catch (error) {
      console.error("Error fetching attendance:", error);
      toast.error("Failed to fetch attendance records");
    } finally {
      setLoading(false);
    }
  };

  const calculateAnalytics = (records) => {
    const total = records.length;
    const present = records.filter(r => r.status === 'present').length;
    const absent = records.filter(r => r.status === 'absent').length;
    const rate = total > 0 ? Math.round((present / total) * 100) : 0;

    setAnalytics({
      totalStudents: total,
      presentToday: present,
      absentToday: absent,
      attendanceRate: rate,
    });
  };

  const filterRecords = () => {
    if (!searchTerm) {
      setFilteredRecords(attendanceRecords);
      return;
    }

    const filtered = attendanceRecords.filter(
      (record) =>
        record.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (record.id_number || record.roll_number)?.toLowerCase().includes(searchTerm.toLowerCase())
    );
    setFilteredRecords(filtered);
  };

  const handleMarkAttendance = async () => {
    if (!selectedClass || !selectedSubject) {
      toast.error("Please select class and subject");
      return;
    }

    setLoading(true);
    try {
      const attendancePromises = students.map(student => {
        return axios.post(
          `${API_URL}/attendance`,
          {
            student_id: student.id,
            subject_id: selectedSubject,
            date: markingDate,
            status: attendanceData[student.id] || 'present',
          },
          {
            headers: { Authorization: `Bearer ${currentUser?.token}` },
          }
        );
      });

      await Promise.all(attendancePromises);
      toast.success("Attendance marked successfully");
      setShowMarkDialog(false);
      fetchAttendanceRecords();
    } catch (error) {
      console.error("Error marking attendance:", error);
      toast.error("Failed to mark attendance");
    } finally {
      setLoading(false);
    }
  };

  const handleAttendanceChange = (studentId, status) => {
    setAttendanceData(prev => ({
      ...prev,
      [studentId]: status,
    }));
  };

  const exportToCSV = () => {
    const headers = ["ID Number", "Name", "Status", "Remarks"];
    const rows = filteredRecords.map((record) => [
      (record.id_number || record.roll_number),
      record.name,
      record.status,
      record.remarks || "",
    ]);

    const csvContent = [
      headers.join(","),
      ...rows.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `attendance_${selectedDate}_${Date.now()}.csv`;
    a.click();
    toast.success("Exported to CSV");
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      present: { variant: "default", icon: CheckCircle2, label: "Present", className: "bg-green-100 text-green-800" },
      absent: { variant: "destructive", icon: XCircle, label: "Absent", className: "bg-red-100 text-red-800" },
      late: { variant: "secondary", icon: Clock, label: "Late", className: "bg-yellow-100 text-yellow-800" },
      excused: { variant: "outline", icon: CheckCircle2, label: "Excused", className: "bg-blue-100 text-blue-800" },
      not_marked: { variant: "outline", icon: Clock, label: "Not Marked", className: "bg-gray-100 text-gray-800" },
    };

    const config = statusConfig[status] || statusConfig.not_marked;
    const Icon = config.icon;

    return (
      <Badge variant={config.variant} className={config.className}>
        <Icon className="w-3 h-3 mr-1" />
        {config.label}
      </Badge>
    );
  };

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Attendance Management</h1>
          <p className="text-gray-600 mt-1">
            Mark and track student attendance records
          </p>
        </div>
        <Button
          onClick={() => setShowMarkDialog(true)}
          className="bg-purple-600 hover:bg-purple-700"
          disabled={!selectedClass || !selectedSubject}
        >
          <Plus className="w-4 h-4 mr-2" />
          Mark Attendance
        </Button>
      </div>

      {/* Analytics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Students</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{analytics.totalStudents}</div>
            <p className="text-xs text-muted-foreground">In selected class</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Present Today</CardTitle>
            <CheckCircle2 className="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">
              {analytics.presentToday}
            </div>
            <p className="text-xs text-muted-foreground">
              {analytics.totalStudents > 0 
                ? `${Math.round((analytics.presentToday / analytics.totalStudents) * 100)}% of total`
                : "No data"}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Absent Today</CardTitle>
            <XCircle className="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-600">
              {analytics.absentToday}
            </div>
            <p className="text-xs text-muted-foreground">
              {analytics.totalStudents > 0
                ? `${Math.round((analytics.absentToday / analytics.totalStudents) * 100)}% of total`
                : "No data"}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Attendance Rate</CardTitle>
            <TrendingUp className="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-blue-600">
              {analytics.attendanceRate}%
            </div>
            <p className="text-xs text-muted-foreground">For selected date</p>
          </CardContent>
        </Card>
      </div>

      {/* Filters Card */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="space-y-2">
              <Label>Class</Label>
              <Select value={selectedClass} onValueChange={setSelectedClass}>
                <SelectTrigger>
                  <SelectValue placeholder="Select class" />
                </SelectTrigger>
                <SelectContent>
                  {classes.map((cls) => (
                    <SelectItem key={cls.id} value={cls.id.toString()}>
                      {cls.class_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Subject</Label>
              <Select
                value={selectedSubject}
                onValueChange={setSelectedSubject}
                disabled={!selectedClass}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select subject" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Subjects</SelectItem>
                  {subjects.map((subject) => (
                    <SelectItem key={subject.id} value={subject.id.toString()}>
                      {subject.subject_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Date</Label>
              <Input
                type="date"
                value={selectedDate}
                onChange={(e) => setSelectedDate(e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label>Search</Label>
              <div className="relative">
                <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search by name or roll no"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-8"
                />
              </div>
            </div>
          </div>

          <div className="flex gap-2 mt-4">
            <Button
              variant="outline"
              size="sm"
              onClick={fetchAttendanceRecords}
              disabled={loading || !selectedClass}
            >
              <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
              Refresh
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={exportToCSV}
              disabled={filteredRecords.length === 0}
            >
              <Download className="w-4 h-4 mr-2" />
              Export CSV
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Attendance Records Table */}
      <Card>
        <CardHeader>
          <CardTitle>
            Attendance Records ({filteredRecords.length})
          </CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">
              <RefreshCw className="w-8 h-8 animate-spin mx-auto text-purple-600" />
              <p className="text-gray-600 mt-2">Loading attendance records...</p>
            </div>
          ) : filteredRecords.length === 0 ? (
            <div className="text-center py-8">
              <UserCheck className="w-12 h-12 mx-auto text-gray-400" />
              <p className="text-gray-600 mt-2">
                {selectedClass
                  ? "No attendance records found for the selected filters"
                  : "Please select a class to view attendance records"}
              </p>
            </div>
          ) : (
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID Number</TableHead>
                    <TableHead>Student Name</TableHead>
                    <TableHead>Class</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Remarks</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredRecords.map((record) => (
                    <TableRow key={record.id}>
                      <TableCell className="font-mono">
                        {record.id_number || record.roll_number}
                      </TableCell>
                      <TableCell>
                        <div>
                          <p className="font-medium">{record.name}</p>
                          <p className="text-sm text-gray-500">{record.email}</p>
                        </div>
                      </TableCell>
                      <TableCell>{record.class_name}</TableCell>
                      <TableCell>{getStatusBadge(record.status)}</TableCell>
                      <TableCell className="text-sm text-gray-600">
                        {record.remarks || "-"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Mark Attendance Dialog */}
      <Dialog open={showMarkDialog} onOpenChange={setShowMarkDialog}>
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Mark Attendance</DialogTitle>
            <DialogDescription>
              Mark attendance for all students in the selected class and subject
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Class</Label>
                <Input
                  value={classes.find(c => c.id.toString() === selectedClass)?.class_name || ''}
                  disabled
                />
              </div>
              <div className="space-y-2">
                <Label>Subject</Label>
                <Input
                  value={subjects.find(s => s.id.toString() === selectedSubject)?.subject_name || ''}
                  disabled
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label>Date</Label>
              <Input
                type="date"
                value={markingDate}
                onChange={(e) => setMarkingDate(e.target.value)}
              />
            </div>

            <div className="border rounded-lg p-4 space-y-2 max-h-96 overflow-y-auto">
              {students.map((student) => (
                <div key={student.id} className="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                  <div className="flex-1">
                    <p className="font-medium">{student.name}</p>
                    <p className="text-sm text-gray-500">{student.id_number || student.roll_number}</p>
                  </div>
                  <Select
                    value={attendanceData[student.id] || 'present'}
                    onValueChange={(value) => handleAttendanceChange(student.id, value)}
                  >
                    <SelectTrigger className="w-32">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="present">Present</SelectItem>
                      <SelectItem value="absent">Absent</SelectItem>
                      <SelectItem value="late">Late</SelectItem>
                      <SelectItem value="excused">Excused</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              ))}
            </div>
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setShowMarkDialog(false)}
              disabled={loading}
            >
              Cancel
            </Button>
            <Button
              onClick={handleMarkAttendance}
              disabled={loading}
              className="bg-purple-600 hover:bg-purple-700"
            >
              {loading ? (
                <>
                  <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                  Marking...
                </>
              ) : (
                <>
                  <CheckCircle2 className="w-4 h-4 mr-2" />
                  Mark Attendance
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default AttendanceManagement;
