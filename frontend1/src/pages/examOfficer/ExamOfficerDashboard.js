import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../../components/ui/card";
import { Label } from "../../components/ui/label";
import { Button } from "../../components/ui/button";
import { Input } from "../../components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../components/ui/select";
import { Textarea } from "../../components/ui/textarea";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../../components/ui/table";
import { Badge } from "../../components/ui/badge";
import { Checkbox } from "../../components/ui/checkbox";
import {
  Shield,
  CheckCircle,
  XCircle,
  Clock,
  LogOut,
  Filter,
  AlertCircle
} from "lucide-react";

const ExamOfficerDashboard = () => {
  const navigate = useNavigate();
  const [officer, setOfficer] = useState(null);
  const [pendingResults, setPendingResults] = useState([]);
  const [exams, setExams] = useState([]);
  const [subjects, setSubjects] = useState([]);
  const [selectedExam, setSelectedExam] = useState("ALL");
  const [selectedSubject, setSelectedSubject] = useState("ALL");
  const [loading, setLoading] = useState(false);
  const [stats, setStats] = useState({
    total_approvals: 0,
    approved_count: 0,
    rejected_count: 0
  });
  const [selectedResults, setSelectedResults] = useState([]);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectingResult, setRejectingResult] = useState(null);
  const [rejectionReason, setRejectionReason] = useState("");

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    // Check authentication
    const token = localStorage.getItem("examOfficerToken");
    const storedOfficer = localStorage.getItem("examOfficer");

    if (!token || !storedOfficer) {
      navigate("/ExamOfficer/login");
      return;
    }

    setOfficer(JSON.parse(storedOfficer));
    fetchPendingResults();
    fetchStats();
  }, [selectedExam, selectedSubject]);

  const getAuthHeaders = () => {
    const token = localStorage.getItem("examOfficerToken");
    return {
      headers: { Authorization: `Bearer ${token}` }
    };
  };

  const fetchPendingResults = async () => {
    setLoading(true);
    try {
      let url = `${API_URL}/exam-officer/pending-results`;
      const params = new URLSearchParams();
      if (selectedExam && selectedExam !== 'ALL') params.append("exam_id", selectedExam);
      if (selectedSubject && selectedSubject !== 'ALL') params.append("subject_id", selectedSubject);
      if (params.toString()) url += `?${params.toString()}`;

      const response = await axios.get(url, getAuthHeaders());

      if (response.data.success) {
        setPendingResults(response.data.pending_results || []);

        // Extract unique exams and subjects for filters
        const uniqueExams = [...new Map(
          response.data.pending_results.map(r => [r.exam_id, { id: r.exam_id, name: r.exam_name }])
        ).values()];
        const uniqueSubjects = [...new Map(
          response.data.pending_results.map(r => [r.subject_id, { id: r.subject_id, name: r.subject_name }])
        ).values()];

        setExams(uniqueExams);
        setSubjects(uniqueSubjects);
      }
    } catch (error) {
      toast.error("Failed to fetch pending results");
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await axios.get(`${API_URL}/exam-officer/stats`, getAuthHeaders());
      if (response.data.success) {
        setStats(response.data.stats);
      }
    } catch (error) {
      console.error("Failed to fetch stats:", error);
    }
  };

  const handleApprove = async (resultId) => {
    try {
      const response = await axios.post(
        `${API_URL}/exam-officer/approve/${resultId}`,
        {},
        getAuthHeaders()
      );

      if (response.data.success) {
        toast.success("Result approved successfully");
        fetchPendingResults();
        fetchStats();
        setSelectedResults(selectedResults.filter(id => id !== resultId));
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "Failed to approve result");
    }
  };

  const handleReject = async () => {
    if (!rejectingResult || !rejectionReason.trim()) {
      toast.error("Please provide a rejection reason");
      return;
    }

    try {
      const response = await axios.post(
        `${API_URL}/exam-officer/reject/${rejectingResult}`,
        { rejection_reason: rejectionReason },
        getAuthHeaders()
      );

      if (response.data.success) {
        toast.success("Result rejected successfully");
        fetchPendingResults();
        fetchStats();
        setShowRejectModal(false);
        setRejectingResult(null);
        setRejectionReason("");
      }
    } catch (error) {
      toast.error("Failed to reject result");
    }
  };

  const handleBulkApprove = async () => {
    if (selectedResults.length === 0) {
      toast.error("Please select results to approve");
      return;
    }

    try {
      const response = await axios.post(
        `${API_URL}/exam-officer/bulk-approve`,
        { result_ids: selectedResults },
        getAuthHeaders()
      );

      if (response.data.success) {
        toast.success(`Approved ${response.data.approved_count} results`);
        fetchPendingResults();
        fetchStats();
        setSelectedResults([]);
      }
    } catch (error) {
      toast.error("Failed to bulk approve");
    }
  };

  const toggleSelectResult = (resultId) => {
    if (selectedResults.includes(resultId)) {
      setSelectedResults(selectedResults.filter(id => id !== resultId));
    } else {
      setSelectedResults([...selectedResults, resultId]);
    }
  };

  const toggleSelectAll = () => {
    if (selectedResults.length === pendingResults.length) {
      setSelectedResults([]);
    } else {
      setSelectedResults(pendingResults.map(r => r.id));
    }
  };

  const handleLogout = () => {
    localStorage.removeItem("examOfficerToken");
    localStorage.removeItem("examOfficer");
    navigate("/ExamOfficer/login");
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <Shield className="w-8 h-8 text-blue-600" />
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Exam Officer Dashboard</h1>
                <p className="text-sm text-gray-600">Welcome, {officer?.name}</p>
              </div>
            </div>
            <Button variant="outline" onClick={handleLogout} className="gap-2">
              <LogOut className="w-4 h-4" />
              Logout
            </Button>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Total Reviews</p>
                  <p className="text-3xl font-bold text-gray-900">{stats.total_approvals}</p>
                </div>
                <Clock className="w-10 h-10 text-blue-500" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Approved</p>
                  <p className="text-3xl font-bold text-green-600">{stats.approved_count}</p>
                </div>
                <CheckCircle className="w-10 h-10 text-green-500" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Rejected</p>
                  <p className="text-3xl font-bold text-red-600">{stats.rejected_count}</p>
                </div>
                <XCircle className="w-10 h-10 text-red-500" />
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Filters and Actions */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Filter className="w-5 h-5" />
              Filters & Actions
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label>Filter by Exam</Label>
                <Select value={selectedExam} onValueChange={setSelectedExam}>
                  <SelectTrigger>
                    <SelectValue placeholder="All Exams" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">All Exams</SelectItem>
                    {exams.map((exam) => (
                      <SelectItem key={exam.id} value={exam.id.toString()}>
                        {exam.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Filter by Subject</Label>
                <Select value={selectedSubject} onValueChange={setSelectedSubject}>
                  <SelectTrigger>
                    <SelectValue placeholder="All Subjects" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">All Subjects</SelectItem>
                    {subjects.map((subject) => (
                      <SelectItem key={subject.id} value={subject.id.toString()}>
                        {subject.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>&nbsp;</Label>
                <Button
                  onClick={handleBulkApprove}
                  disabled={selectedResults.length === 0}
                  className="w-full gap-2"
                >
                  <CheckCircle className="w-4 h-4" />
                  Approve Selected ({selectedResults.length})
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Pending Results Table */}
        <Card>
          <CardHeader>
            <CardTitle>Pending Results ({pendingResults.length})</CardTitle>
            <CardDescription>
              Review and approve exam results uploaded by teachers
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="text-center py-8">Loading...</div>
            ) : pendingResults.length === 0 ? (
              <div className="text-center py-8 text-gray-500">
                <AlertCircle className="w-12 h-12 mx-auto mb-2 text-gray-400" />
                <p>No pending results to review</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-12">
                        <Checkbox
                          checked={selectedResults.length === pendingResults.length}
                          onCheckedChange={toggleSelectAll}
                        />
                      </TableHead>
                      <TableHead>Student</TableHead>
                    <TableHead>ID Number</TableHead>
                      <TableHead>Class</TableHead>
                      <TableHead>Subject</TableHead>
                      <TableHead>Exam</TableHead>
                      <TableHead>Test Score</TableHead>
                      <TableHead>Exam Score</TableHead>
                      <TableHead>Total</TableHead>
                      <TableHead>Teacher</TableHead>
                      <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {pendingResults.map((result) => (
                      <TableRow key={result.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedResults.includes(result.id)}
                            onCheckedChange={() => toggleSelectResult(result.id)}
                          />
                        </TableCell>
                        <TableCell className="font-medium">{result.student_name}</TableCell>
                        <TableCell>{result.id_number || result.roll_number}</TableCell>
                        <TableCell>{result.class_name}</TableCell>
                        <TableCell>{result.subject_name}</TableCell>
                        <TableCell>{result.exam_name}</TableCell>
                        <TableCell>{result.test_score}</TableCell>
                        <TableCell>{result.exam_score}</TableCell>
                        <TableCell className="font-bold">
                          {parseFloat(result.test_score) + parseFloat(result.exam_score)}
                        </TableCell>
                        <TableCell>{result.teacher_name || "N/A"}</TableCell>
                        <TableCell className="text-right">
                          <div className="flex justify-end gap-2">
                            <Button
                              size="sm"
                              variant="outline"
                              className="text-green-600 hover:text-green-700"
                              onClick={() => handleApprove(result.id)}
                            >
                              <CheckCircle className="w-4 h-4" />
                            </Button>
                            <Button
                              size="sm"
                              variant="outline"
                              className="text-red-600 hover:text-red-700"
                              onClick={() => {
                                setRejectingResult(result.id);
                                setShowRejectModal(true);
                              }}
                            >
                              <XCircle className="w-4 h-4" />
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
      </div>

      {/* Rejection Modal */}
      {showRejectModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <Card className="max-w-md w-full">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-red-600">
                <XCircle className="w-5 h-5" />
                Reject Result
              </CardTitle>
              <CardDescription>
                Please provide a reason for rejecting this result
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="rejection_reason">Rejection Reason *</Label>
                <Textarea
                  id="rejection_reason"
                  value={rejectionReason}
                  onChange={(e) => setRejectionReason(e.target.value)}
                  placeholder="e.g., Score seems incorrect, please verify with student's answer sheet"
                  rows={4}
                  required
                />
              </div>

              <div className="flex gap-2">
                <Button
                  onClick={handleReject}
                  disabled={!rejectionReason.trim()}
                  variant="destructive"
                  className="flex-1"
                >
                  Reject Result
                </Button>
                <Button
                  variant="outline"
                  onClick={() => {
                    setShowRejectModal(false);
                    setRejectingResult(null);
                    setRejectionReason("");
                  }}
                >
                  Cancel
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
};

export default ExamOfficerDashboard;
