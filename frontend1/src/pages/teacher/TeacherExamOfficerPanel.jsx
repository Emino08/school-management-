import { useEffect, useMemo, useState } from "react";
import axios from "@/redux/axiosConfig";
import { useSelector } from "react-redux";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { toast } from "sonner";
import { FileDown, Upload, Filter, Check, X } from "lucide-react";

const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

const TeacherExamOfficerPanel = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [pendingResults, setPendingResults] = useState([]);
  const [updateRequests, setUpdateRequests] = useState([]);
  const [pendingLoading, setPendingLoading] = useState(false);
  const [updatesLoading, setUpdatesLoading] = useState(false);
  const [selectedExam, setSelectedExam] = useState("ALL");
  const [selectedSubject, setSelectedSubject] = useState("ALL");
  const [bulkFile, setBulkFile] = useState(null);
  const [uploading, setUploading] = useState(false);

  const exams = useMemo(() => {
    const map = new Map();
    pendingResults.forEach((r) => map.set(r.exam_id, r.exam_name));
    updateRequests.forEach((r) => map.set(r.exam_id, r.exam_name));
    return Array.from(map, ([id, name]) => ({ id, name })).filter((e) => e.id);
  }, [pendingResults, updateRequests]);

  const subjects = useMemo(() => {
    const map = new Map();
    pendingResults.forEach((r) => map.set(r.subject_id, r.subject_name));
    updateRequests.forEach((r) => map.set(r.subject_id, r.subject_name));
    return Array.from(map, ([id, name]) => ({ id, name })).filter((s) => s.id);
  }, [pendingResults, updateRequests]);

  useEffect(() => {
    fetchPending();
    fetchUpdateRequests();
  }, [selectedExam, selectedSubject]);

  const fetchPending = async () => {
    setPendingLoading(true);
    try {
      let url = `${API_URL}/result-management/pending-grades`;
      const params = new URLSearchParams();
      if (selectedExam !== "ALL") params.append("exam_id", selectedExam);
      if (selectedSubject !== "ALL") params.append("subject_id", selectedSubject);
      if (params.toString()) url += `?${params.toString()}`;
      const res = await axios.get(url);
      if (res.data?.success) {
        setPendingResults(res.data.pending_grades || []);
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "Failed to load pending results");
    } finally {
      setPendingLoading(false);
    }
  };

  const fetchUpdateRequests = async () => {
    setUpdatesLoading(true);
    try {
      const res = await axios.get(`${API_URL}/result-management/update-requests`);
      if (res.data?.success) {
        setUpdateRequests(res.data.update_requests || []);
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "Failed to load update requests");
    } finally {
      setUpdatesLoading(false);
    }
  };

  const approveGrade = async (id) => {
    try {
      await axios.post(`${API_URL}/result-management/approve/${id}`);
      toast.success("Grade approved");
      fetchPending();
    } catch (error) {
      toast.error(error.response?.data?.message || "Approve failed");
    }
  };

  const rejectGrade = async (id, reason) => {
    try {
      await axios.post(`${API_URL}/result-management/reject/${id}`, { reason });
      toast.success("Grade rejected");
      fetchPending();
    } catch (error) {
      toast.error(error.response?.data?.message || "Reject failed");
    }
  };

  const approveUpdateRequest = async (id) => {
    try {
      await axios.post(`${API_URL}/result-management/approve-update/${id}`);
      toast.success("Update request approved");
      fetchUpdateRequests();
    } catch (error) {
      toast.error(error.response?.data?.message || "Approve failed");
    }
  };

  const rejectUpdateRequest = async (id, reason) => {
    try {
      await axios.post(`${API_URL}/result-management/reject-update/${id}`, { reason });
      toast.success("Update request rejected");
      fetchUpdateRequests();
    } catch (error) {
      toast.error(error.response?.data?.message || "Reject failed");
    }
  };

  const downloadTemplate = () => {
    const header = "student_id,subject_id,exam_id,score,comment";
    const sample = "123,45,7,85,Good progress";
    const blob = new Blob([`${header}\n${sample}\n`], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "grade_upload_template.csv";
    a.click();
    URL.revokeObjectURL(url);
  };

  const handleBulkUpload = async () => {
    if (!bulkFile) {
      toast.error("Select a CSV file first");
      return;
    }
    setUploading(true);
    try {
      const text = await bulkFile.text();
      const lines = text.split(/\r?\n/).filter(Boolean);
      const [headerLine, ...rows] = lines;
      const headers = headerLine.split(",").map((h) => h.trim());
      const required = ["student_id", "subject_id", "exam_id", "score"];
      if (!required.every((r) => headers.includes(r))) {
        throw new Error("CSV missing required columns: " + required.join(", "));
      }
      const headerIndex = Object.fromEntries(headers.map((h, i) => [h, i]));
      let success = 0;
      let failed = 0;
      for (const row of rows) {
        const cols = row.split(",").map((c) => c.trim());
        if (cols.length < headers.length) continue;
        const payload = {
          student_id: cols[headerIndex["student_id"]],
          subject_id: cols[headerIndex["subject_id"]],
          exam_id: cols[headerIndex["exam_id"]],
          score: cols[headerIndex["score"]],
          comment: cols[headerIndex["comment"]] || null,
        };
        try {
          await axios.post(`${API_URL}/exams/results`, payload);
          success++;
        } catch (err) {
          failed++;
        }
      }
      toast.success(`Upload complete: ${success} ok, ${failed} failed`);
      fetchPending();
    } catch (error) {
      toast.error(error.message || "Bulk upload failed");
    } finally {
      setUploading(false);
    }
  };

  const exportCSV = (data, filename) => {
    if (!data || data.length === 0) {
      toast.info("No data to export");
      return;
    }
    const headers = Object.keys(data[0]);
    const rows = data.map((row) => headers.map((h) => `"${(row[h] ?? "").toString().replace(/"/g, '""')}"`).join(","));
    const blob = new Blob([headers.join(",") + "\n" + rows.join("\n")], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="container mx-auto p-6 max-w-7xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Exam Officer</h1>
          <p className="text-sm text-gray-600">
            Approve submitted grades and handle update requests.
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={downloadTemplate} className="flex items-center gap-1">
            <FileDown className="w-4 h-4" /> Template
          </Button>
          <Button variant="outline" size="sm" onClick={() => exportCSV(pendingResults, "pending_grades.csv")} className="flex items-center gap-1">
            <FileDown className="w-4 h-4" /> Export Pending
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Bulk Upload Grades (CSV)</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-gray-600">Upload grades using the template format. Each row represents one student grade.</p>
          <div className="flex flex-col sm:flex-row gap-3">
            <Input type="file" accept=".csv,text/csv" onChange={(e) => setBulkFile(e.target.files?.[0] || null)} />
            <Button onClick={handleBulkUpload} disabled={uploading} className="flex items-center gap-2">
              <Upload className="w-4 h-4" /> {uploading ? "Uploading..." : "Upload"}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="pt-6">
          <Tabs defaultValue="pending">
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="pending">Pending Grades</TabsTrigger>
              <TabsTrigger value="updates">Update Requests</TabsTrigger>
            </TabsList>

            <TabsContent value="pending" className="mt-4 space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                  <Label>Exam</Label>
                  <Select value={selectedExam} onValueChange={setSelectedExam}>
                    <SelectTrigger><SelectValue placeholder="All exams" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="ALL">All exams</SelectItem>
                      {exams.map((ex) => (
                        <SelectItem key={ex.id} value={String(ex.id)}>{ex.name || ex.exam_name || ex.id}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label>Subject</Label>
                  <Select value={selectedSubject} onValueChange={setSelectedSubject}>
                    <SelectTrigger><SelectValue placeholder="All subjects" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="ALL">All subjects</SelectItem>
                      {subjects.map((su) => (
                        <SelectItem key={su.id} value={String(su.id)}>{su.name || su.subject_name || su.id}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="flex gap-2">
                  <Button variant="outline" onClick={() => { setSelectedExam("ALL"); setSelectedSubject("ALL"); }}>
                    <Filter className="w-4 h-4 mr-1" /> Reset Filters
                  </Button>
                  <Button variant="outline" onClick={() => exportCSV(pendingResults, "pending_grades.csv")} className="flex items-center gap-1">
                    <FileDown className="w-4 h-4" /> Export
                  </Button>
                </div>
              </div>

              {pendingLoading ? (
                <p className="text-gray-500">Loading pending grades...</p>
              ) : pendingResults.length === 0 ? (
                <p className="text-gray-500">No pending grades.</p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full border-collapse border border-gray-200 text-sm">
                    <thead>
                      <tr className="bg-gray-50">
                        <th className="px-3 py-2 border text-left">Student</th>
                        <th className="px-3 py-2 border text-left">Subject</th>
                        <th className="px-3 py-2 border text-left">Exam</th>
                        <th className="px-3 py-2 border text-left">Score</th>
                        <th className="px-3 py-2 border text-left">Submitted By</th>
                        <th className="px-3 py-2 border text-left">Date</th>
                        <th className="px-3 py-2 border text-left">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {pendingResults.map((r) => (
                        <tr key={r.id} className="hover:bg-gray-50">
                          <td className="px-3 py-2 border">{r.student_name}</td>
                          <td className="px-3 py-2 border">{r.subject_name}</td>
                          <td className="px-3 py-2 border">{r.exam_name}</td>
                          <td className="px-3 py-2 border">{r.score ?? r.marks}</td>
                          <td className="px-3 py-2 border">{r.teacher_name || r.submitted_by}</td>
                          <td className="px-3 py-2 border">{r.submitted_at ? new Date(r.submitted_at).toLocaleDateString() : "-"}</td>
                          <td className="px-3 py-2 border">
                            <div className="flex gap-2">
                              <Button size="sm" variant="default" onClick={() => approveGrade(r.id)} className="flex items-center gap-1">
                                <Check className="w-4 h-4" /> Approve
                              </Button>
                              <Button
                                size="sm"
                                variant="destructive"
                                onClick={() => {
                                  const reason = prompt("Enter rejection reason");
                                  if (reason) rejectGrade(r.id, reason);
                                }}
                                className="flex items-center gap-1"
                              >
                                <X className="w-4 h-4" /> Reject
                              </Button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </TabsContent>

            <TabsContent value="updates" className="mt-4 space-y-4">
              <div className="flex items-center justify-between">
                <p className="text-sm text-gray-600">Review grade change requests from teachers.</p>
                <Button variant="outline" onClick={() => exportCSV(updateRequests, "grade_update_requests.csv")} className="flex items-center gap-1">
                  <FileDown className="w-4 h-4" /> Export
                </Button>
              </div>
              {updatesLoading ? (
                <p className="text-gray-500">Loading update requests...</p>
              ) : updateRequests.length === 0 ? (
                <p className="text-gray-500">No update requests.</p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full border-collapse border border-gray-200 text-sm">
                    <thead>
                      <tr className="bg-gray-50">
                        <th className="px-3 py-2 border text-left">Student</th>
                        <th className="px-3 py-2 border text-left">Subject</th>
                        <th className="px-3 py-2 border text-left">Exam</th>
                        <th className="px-3 py-2 border text-left">Current Score</th>
                        <th className="px-3 py-2 border text-left">Requested Score</th>
                        <th className="px-3 py-2 border text-left">Teacher</th>
                        <th className="px-3 py-2 border text-left">Reason</th>
                        <th className="px-3 py-2 border text-left">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {updateRequests.map((r) => (
                        <tr key={r.id} className="hover:bg-gray-50">
                          <td className="px-3 py-2 border">{r.student_name}</td>
                          <td className="px-3 py-2 border">{r.subject_name}</td>
                          <td className="px-3 py-2 border">{r.exam_name}</td>
                          <td className="px-3 py-2 border">{r.current_score}</td>
                          <td className="px-3 py-2 border text-blue-700 font-medium">{r.new_score}</td>
                          <td className="px-3 py-2 border">{r.teacher_name}</td>
                          <td className="px-3 py-2 border max-w-xs truncate" title={r.reason}>{r.reason}</td>
                          <td className="px-3 py-2 border">
                            <div className="flex gap-2">
                              <Button size="sm" onClick={() => approveUpdateRequest(r.id)} className="flex items-center gap-1">
                                <Check className="w-4 h-4" /> Approve
                              </Button>
                              <Button
                                size="sm"
                                variant="destructive"
                                onClick={() => {
                                  const reason = prompt("Enter rejection reason");
                                  if (reason) rejectUpdateRequest(r.id, reason);
                                }}
                                className="flex items-center gap-1"
                              >
                                <X className="w-4 h-4" /> Reject
                              </Button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
};

export default TeacherExamOfficerPanel;
