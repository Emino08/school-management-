import React, { useState, useEffect } from "react";
import { useSelector } from "react-redux";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../../../components/ui/card";
import { Label } from "../../../components/ui/label";
import { Button } from "../../../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../../components/ui/select";
import { Input } from "../../../components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../../../components/ui/table";
import { Badge } from "../../../components/ui/badge";
import { FileText, Download, Filter, Search, CheckCircle, XCircle, Clock } from "lucide-react";

const ViewAllResults = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [results, setResults] = useState([]);
  const [classes, setClasses] = useState([]);
  const [academicYears, setAcademicYears] = useState([]);
  const [exams, setExams] = useState([]);
  const [students, setStudents] = useState([]);
  
  const [selectedClass, setSelectedClass] = useState("");
  const [selectedYear, setSelectedYear] = useState("all");
  const [selectedExam, setSelectedExam] = useState("");
  const [selectedStudent, setSelectedStudent] = useState("all");
  const [approvalFilter, setApprovalFilter] = useState("all");
  const [includeUnverified, setIncludeUnverified] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  
  const [loading, setLoading] = useState(false);
  const [stats, setStats] = useState({
    total: 0,
    approved: 0,
    pending: 0,
    rejected: 0,
    published: 0
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    fetchClasses();
    fetchAcademicYears();
  }, []);

  useEffect(() => {
    if (selectedClass) {
      fetchExams();
      fetchStudents();
    }
  }, [selectedClass, selectedYear]);

  useEffect(() => {
    if (selectedClass && selectedExam) { fetchResults(); }
  }, [selectedClass, selectedExam, selectedStudent, approvalFilter]);

  const fetchClasses = async () => {
    try {
      const response = await axios.get(`${API_URL}/classes`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (response.data.success) {
        setClasses(response.data.classes || []);
      }
    } catch (error) {
      console.error("Error fetching classes:", error);
    }
  };

  const fetchAcademicYears = async () => {
    try {
      const response = await axios.get(`${API_URL}/academic-years`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (response.data.success) {
        setAcademicYears(response.data.academic_years || []);
      }
    } catch (error) {
      console.error("Error fetching academic years:", error);
    }
  };

  const fetchExams = async () => {
    try {
      let url = `${API_URL}/exams?class_id=${selectedClass}`;
      if (selectedYear && selectedYear !== "all") {
        url += `&academic_year_id=${selectedYear}`;
      }
      
      const response = await axios.get(url, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (response.data.success) {
        setExams(response.data.exams || []);
      }
    } catch (error) {
      console.error("Error fetching exams:", error);
    }
  };

  const fetchStudents = async () => {
    try {
      const response = await axios.get(`${API_URL}/students?class_id=${selectedClass}`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (response.data.success) {
        setStudents(response.data.students || []);
      }
    } catch (error) {
      console.error("Error fetching students:", error);
    }
  };

  const fetchResults = async () => {
    setLoading(true);
    try {
      let url = `${API_URL}/results/exam/${selectedExam}`;
      const params = new URLSearchParams();
      if (selectedStudent && selectedStudent !== "all") {
        params.append("student_id", selectedStudent);
      }
      if (approvalFilter !== "all") {
        params.append("approval_status", approvalFilter);
      }
      if (includeUnverified) params.append("include_unverified","1");
      if (params.toString()) url += `?${params.toString()}`;

      const response = await axios.get(url, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });

      if (response.data.success) {
        const fetchedResults = response.data.results || [];
        setResults(fetchedResults);
        
        // Calculate stats
        const total = fetchedResults.length;
        const approved = fetchedResults.filter(r => r.approval_status === 'approved').length;
        const pending = fetchedResults.filter(r => r.approval_status === 'pending').length;
        const rejected = fetchedResults.filter(r => r.approval_status === 'rejected').length;
        const published = fetchedResults.filter(r => r.is_published === 1 || r.is_published === true).length;
        
        setStats({ total, approved, pending, rejected, published });
      }
    } catch (error) {
      toast.error("Failed to fetch results");
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'approved':
        return <Badge className="bg-green-100 text-green-800"><CheckCircle className="w-3 h-3 mr-1" />Approved</Badge>;
      case 'pending':
        return <Badge className="bg-yellow-100 text-yellow-800"><Clock className="w-3 h-3 mr-1" />Pending</Badge>;
      case 'rejected':
        return <Badge className="bg-red-100 text-red-800"><XCircle className="w-3 h-3 mr-1" />Rejected</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const calculateAverage = (result) => {
    const testScore = parseFloat(result.test_score) || 0;
    const examScore = parseFloat(result.exam_score) || 0;
    return ((testScore + examScore) / 2).toFixed(2);
  };

  const exportToCSV = () => {
    if (results.length === 0) {
      toast.error("No results to export");
      return;
    }

    const headers = ["Student Name", "Admission No", "Subject", "Test Score", "Exam Score", "Average", "Grade", "Status", "Published"];
    const rows = results.map(r => [
      r.student_name,
      r.admission_no,
      r.subject_name,
      r.test_score,
      r.exam_score,
      calculateAverage(r),
      r.grade || "N/A",
      r.approval_status,
      r.is_published ? "Yes" : "No"
    ]);

    const csv = [headers, ...rows].map(row => row.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `results_${selectedExam}_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    URL.revokeObjectURL(url);
    toast.success("Results exported successfully");
  };

  const filteredResults = results.filter(result => {
    if (!searchTerm) return true;
    const search = searchTerm.toLowerCase();
    return (
      result.student_name?.toLowerCase().includes(search) ||
      result.admission_no?.toLowerCase().includes(search) ||
      result.subject_name?.toLowerCase().includes(search)
    );
  });

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      {/* Header */}
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl flex items-center gap-2">
            <FileText className="w-6 h-6" />
            View All Results
          </CardTitle>
          <CardDescription>
            View and manage all student results across classes
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Filters */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>Academic Year</Label>
              <Select value={selectedYear} onValueChange={setSelectedYear}>
                <SelectTrigger>
                  <SelectValue placeholder="All Years" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Years</SelectItem>
                  {academicYears.map((year) => (
                    <SelectItem key={year.id} value={year.id.toString()}>
                      {year.year_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Class *</Label>
              <Select value={selectedClass} onValueChange={setSelectedClass}>
                <SelectTrigger>
                  <SelectValue placeholder="Select Class" />
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
              <Label>Exam *</Label>
              <Select 
                value={selectedExam} 
                onValueChange={setSelectedExam}
                disabled={!selectedClass}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select Exam" />
                </SelectTrigger>
                <SelectContent>
                  {exams.map((exam) => (
                    <SelectItem key={exam.id} value={exam.id.toString()}>
                      {exam.exam_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Student (Optional)</Label>
              <Select 
                value={selectedStudent} 
                onValueChange={setSelectedStudent}
                disabled={!selectedClass}
              >
                <SelectTrigger>
                  <SelectValue placeholder="All Students" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Students</SelectItem>
                  {students.map((student) => (
                    <SelectItem key={student.id} value={student.id.toString()}>
                      {student.name} ({student.admission_no})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Approval Status</Label>
              <Select value={approvalFilter} onValueChange={setApprovalFilter}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="approved">Approved Only</SelectItem>
                  <SelectItem value="pending">Pending Only</SelectItem>
                  <SelectItem value="rejected">Rejected Only</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Search</Label>
              <div className="relative">
                <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                <Input
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="Search students, subjects..."
                  className="pl-10"
                />
              </div>
            </div>
          </div>

          <div className="flex gap-2">
            <Button 
              onClick={fetchResults}
              disabled={!selectedClass || !selectedExam || loading}
              className="gap-2"
            >
              <Filter className="w-4 h-4" />
              {loading ? "Loading..." : "Apply Filters"}
            </Button>
            <Button 
              onClick={exportToCSV}
              variant="outline"
              disabled={results.length === 0}
              className="gap-2"
            >
              <Download className="w-4 h-4" />
              Export CSV
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Statistics */}
      {results.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Statistics</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
              <div className="text-center p-4 bg-gray-50 rounded-lg">
                <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
                <div className="text-sm text-gray-600 mt-1">Total</div>
              </div>
              <div className="text-center p-4 bg-green-50 rounded-lg">
                <div className="text-3xl font-bold text-green-600">{stats.approved}</div>
                <div className="text-sm text-gray-600 mt-1">Approved</div>
              </div>
              <div className="text-center p-4 bg-yellow-50 rounded-lg">
                <div className="text-3xl font-bold text-yellow-600">{stats.pending}</div>
                <div className="text-sm text-gray-600 mt-1">Pending</div>
              </div>
              <div className="text-center p-4 bg-red-50 rounded-lg">
                <div className="text-3xl font-bold text-red-600">{stats.rejected}</div>
                <div className="text-sm text-gray-600 mt-1">Rejected</div>
              </div>
              <div className="text-center p-4 bg-blue-50 rounded-lg">
                <div className="text-3xl font-bold text-blue-600">{stats.published}</div>
                <div className="text-sm text-gray-600 mt-1">Published</div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Results Table */}
      <Card>
        <CardHeader>
          <CardTitle>Results</CardTitle>
          <CardDescription>
            {filteredResults.length} result(s) found
          </CardDescription>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">Loading results...</div>
          ) : filteredResults.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              {selectedClass && selectedExam 
                ? "No results found with current filters" 
                : "Please select a class and exam to view results"}
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Student</TableHead>
                    <TableHead>Admission No</TableHead>
                    <TableHead>Subject</TableHead>
                    <TableHead className="text-right">Test</TableHead>
                    <TableHead className="text-right">Exam</TableHead>
                    <TableHead className="text-right">Average</TableHead>
                    <TableHead>Grade</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Published</TableHead>
                    <TableHead>Teacher</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredResults.map((result) => (
                    <TableRow key={result.id}>
                      <TableCell className="font-medium">{result.student_name}</TableCell>
                      <TableCell>{result.admission_no}</TableCell>
                      <TableCell>{result.subject_name}</TableCell>
                      <TableCell className="text-right">{result.test_score}</TableCell>
                      <TableCell className="text-right">{result.exam_score}</TableCell>
                      <TableCell className="text-right font-semibold">
                        {calculateAverage(result)}
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">{result.grade || "N/A"}</Badge>
                      </TableCell>
                      <TableCell>{getStatusBadge(result.approval_status)}</TableCell>
                      <TableCell>
                        {result.is_published ? (
                          <Badge className="bg-blue-100 text-blue-800">Yes</Badge>
                        ) : (
                          <Badge variant="secondary">No</Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-sm text-gray-600">
                        {result.teacher_name || "N/A"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default ViewAllResults;


