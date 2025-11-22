import React, { useEffect, useState } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { useSelector } from 'react-redux';
import axios from '../../redux/axiosConfig';

const TeacherProfile = () => {
  const { currentUser, response, error } = useSelector((state) => state.user);
  const [examLoading, setExamLoading] = useState(false);
  const [pendingResults, setPendingResults] = useState([]);
  const [exams, setExams] = useState([]);
  const [subjectsMap, setSubjectsMap] = useState([]);
  const [selectedExam, setSelectedExam] = useState('ALL');
  const [selectedSubject, setSelectedSubject] = useState('ALL');
  const [rejectingId, setRejectingId] = useState(null);
  const [rejectionReason, setRejectionReason] = useState('');
  const [pendingGrades, setPendingGrades] = useState([]);
  const [pendingGradesLoading, setPendingGradesLoading] = useState(false);
  const [selectedResultSubject, setSelectedResultSubject] = useState('ALL');
  const [selectedResultExam, setSelectedResultExam] = useState('ALL');
  const [resultExams, setResultExams] = useState([]);
  const [resultSubjects, setResultSubjects] = useState([]);
  const [updateRequests, setUpdateRequests] = useState([]);
  const [updateRequestsLoading, setUpdateRequestsLoading] = useState(false);
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  // Define profile at the top to avoid initialization error
  const profile = currentUser?.teacher || currentUser || {};

  // Fetch pending grades for result management
  useEffect(() => {
    const fetchPendingGrades = async () => {
      if (!profile?.is_exam_officer || !profile?.can_approve_results) return;
      setPendingGradesLoading(true);
      try {
        let url = `${API_URL}/result-management/pending-grades`;
        const params = new URLSearchParams();
        if (selectedResultExam && selectedResultExam !== 'ALL') params.append('exam_id', selectedResultExam);
        if (selectedResultSubject && selectedResultSubject !== 'ALL') params.append('subject_id', selectedResultSubject);
        if (params.toString()) url += `?${params.toString()}`;
        const res = await axios.get(url);
        if (res.data?.success) {
          const list = res.data.pending_grades || [];
          setPendingGrades(list);
          // Build filters
          const uniqExams = [...new Map(list.map(r => [r.exam_id, { id: r.exam_id, name: r.exam_name }])).values()];
          const uniqSubjects = [...new Map(list.map(r => [r.subject_id, { id: r.subject_id, name: r.subject_name }])).values()];
          setResultExams(uniqExams);
          setResultSubjects(uniqSubjects);
        }
      } catch (e) {
        console.error('Failed to fetch pending grades', e);
      } finally {
        setPendingGradesLoading(false);
      }
    };
    fetchPendingGrades();
  }, [profile?.is_exam_officer, profile?.can_approve_results, selectedResultExam, selectedResultSubject]);

  // Fetch update requests
  useEffect(() => {
    const fetchUpdateRequests = async () => {
      if (!profile?.is_exam_officer || !profile?.can_approve_results) return;
      setUpdateRequestsLoading(true);
      try {
        const res = await axios.get(`${API_URL}/result-management/update-requests`);
        if (res.data?.success) {
          setUpdateRequests(res.data.update_requests || []);
        }
      } catch (e) {
        console.error('Failed to fetch update requests', e);
      } finally {
        setUpdateRequestsLoading(false);
      }
    };
    fetchUpdateRequests();
  }, [profile?.is_exam_officer, profile?.can_approve_results]);

  // Exam officer: pending results
  useEffect(() => {
    const fetchPending = async () => {
      if (!profile?.is_exam_officer) return;
      setExamLoading(true);
      try {
        let url = `${API_URL}/exam-officer/pending-results`;
        const params = new URLSearchParams();
        if (selectedExam && selectedExam !== 'ALL') params.append('exam_id', selectedExam);
        if (selectedSubject && selectedSubject !== 'ALL') params.append('subject_id', selectedSubject);
        if (params.toString()) url += `?${params.toString()}`;
        const res = await axios.get(url);
        if (res.data?.success) {
          const list = res.data.pending_results || [];
          setPendingResults(list);
          // Build filters from results
          const uniqExams = [...new Map(list.map(r => [r.exam_id, { id: r.exam_id, name: r.exam_name }])).values()];
          const uniqSubjects = [...new Map(list.map(r => [r.subject_id, { id: r.subject_id, name: r.subject_name }])).values()];
          setExams(uniqExams);
          setSubjectsMap(uniqSubjects);
        }
      } catch (e) {
        console.error('Failed to fetch pending results', e);
      } finally {
        setExamLoading(false);
      }
    };
    fetchPending();
  }, [profile?.is_exam_officer, selectedExam, selectedSubject]);

  const approveResult = async (id) => {
    try {
      await axios.post(`${API_URL}/exam-officer/approve/${id}`);
      setPendingResults(prev => prev.filter(r => r.id !== id));
    } catch (e) {
      console.error('Approve failed', e);
    }
  };

  const rejectResult = async () => {
    if (!rejectingId || !rejectionReason.trim()) return;
    try {
      await axios.post(`${API_URL}/exam-officer/reject/${rejectingId}`, { rejection_reason: rejectionReason });
      setPendingResults(prev => prev.filter(r => r.id !== rejectingId));
      setRejectingId(null); setRejectionReason('');
    } catch (e) {
      console.error('Reject failed', e);
    }
  };

  const approveGrade = async (resultId) => {
    try {
      await axios.post(`${API_URL}/result-management/approve/${resultId}`);
      setPendingGrades(prev => prev.filter(g => g.id !== resultId));
    } catch (e) {
      console.error('Approve grade failed', e);
    }
  };

  const rejectGrade = async (resultId, reason) => {
    try {
      await axios.post(`${API_URL}/result-management/reject/${resultId}`, { rejection_reason: reason });
      setPendingGrades(prev => prev.filter(g => g.id !== resultId));
    } catch (e) {
      console.error('Reject grade failed', e);
    }
  };

  const approveUpdateRequest = async (requestId) => {
    try {
      await axios.post(`${API_URL}/result-management/approve-update/${requestId}`);
      setUpdateRequests(prev => prev.filter(r => r.id !== requestId));
    } catch (e) {
      console.error('Approve update request failed', e);
    }
  };

  const rejectUpdateRequest = async (requestId, reason) => {
    try {
      await axios.post(`${API_URL}/result-management/reject-update/${requestId}`, { rejection_reason: reason });
      setUpdateRequests(prev => prev.filter(r => r.id !== requestId));
    } catch (e) {
      console.error('Reject update request failed', e);
    }
  };

  return (
    <div className="container mx-auto p-6 max-w-6xl">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl">Teacher Dashboard</CardTitle>
          <div className="mt-2 flex gap-2 flex-wrap">
            {profile.is_class_master && (
              <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                Class Master
              </span>
            )}
            {profile.is_exam_officer && (
              <span className="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                Exam Officer
              </span>
            )}
            {!profile.is_class_master && !profile.is_exam_officer && (
              <span className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">
                Teacher
              </span>
            )}
          </div>
        </CardHeader>
        <CardContent>
          <Tabs defaultValue="profile">
            <TabsList className="flex flex-wrap gap-2">
              <TabsTrigger value="profile">Profile</TabsTrigger>
              {profile.is_exam_officer && profile.can_approve_results && (
                <TabsTrigger value="result-management">Result Management</TabsTrigger>
              )}
              {profile.is_exam_officer && (
                <TabsTrigger value="exam">Exam Officer</TabsTrigger>
              )}
            </TabsList>

            <TabsContent value="profile">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                  <p className="text-sm text-gray-500">Name</p>
                  <p className="text-base font-medium">{profile.name || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Email</p>
                  <p className="text-base font-medium">{profile.email || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Phone</p>
                  <p className="text-base font-medium">{profile.phone || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Address</p>
                  <p className="text-base font-medium">{profile.address || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Qualification</p>
                  <p className="text-base font-medium">{profile.qualification || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Experience (years)</p>
                  <p className="text-base font-medium">{profile.experience_years ?? '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Class Master</p>
                  <p className="text-base font-medium">{profile.is_class_master ? 'Yes' : 'No'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Exam Officer</p>
                  <p className="text-base font-medium">{profile.is_exam_officer ? 'Yes' : 'No'}</p>
                </div>
              </div>
            </TabsContent>

            

            

            

            {profile.is_exam_officer && profile.can_approve_results ? (
              <TabsContent value="result-management">
                <div className="mt-4 space-y-6">
                  <h3 className="text-lg font-semibold">Result Management</h3>

                  {/* Pending Grades Section */}
                  <div className="space-y-4">
                    <h4 className="font-medium">Pending Grade Verifications</h4>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                      <div>
                        <Label>Exam</Label>
                        <Select value={selectedResultExam} onValueChange={setSelectedResultExam}>
                          <SelectTrigger><SelectValue placeholder="All Exams" /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="ALL">All Exams</SelectItem>
                            {resultExams.map(ex => (
                              <SelectItem key={ex.id} value={String(ex.id)}>{ex.name}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div>
                        <Label>Subject</Label>
                        <Select value={selectedResultSubject} onValueChange={setSelectedResultSubject}>
                          <SelectTrigger><SelectValue placeholder="All Subjects" /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="ALL">All Subjects</SelectItem>
                            {resultSubjects.map(su => (
                              <SelectItem key={su.id} value={String(su.id)}>{su.name}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div>
                        <Button variant="outline" onClick={() => { setSelectedResultExam('ALL'); setSelectedResultSubject('ALL'); }}>Reset Filters</Button>
                      </div>
                    </div>

                    {pendingGradesLoading ? (
                      <p>Loading pending grades...</p>
                    ) : pendingGrades.length === 0 ? (
                      <p className="text-gray-500">No pending grades to verify.</p>
                    ) : (
                      <div className="overflow-x-auto">
                        <table className="w-full border-collapse border border-gray-300">
                          <thead>
                            <tr className="bg-gray-100">
                              <th className="border border-gray-300 px-4 py-2 text-left">No</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Student</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Subject</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Exam</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Score</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Teacher</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Submitted</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            {pendingGrades.map((grade, index) => (
                              <tr key={grade.id} className="hover:bg-gray-50">
                                <td className="border border-gray-300 px-4 py-2">{index + 1}</td>
                                <td className="border border-gray-300 px-4 py-2">{grade.student_name}</td>
                                <td className="border border-gray-300 px-4 py-2">{grade.subject_name}</td>
                                <td className="border border-gray-300 px-4 py-2">{grade.exam_name}</td>
                                <td className="border border-gray-300 px-4 py-2">
                                  <span className="font-medium">{grade.score}</span>
                                </td>
                                <td className="border border-gray-300 px-4 py-2">{grade.teacher_name}</td>
                                <td className="border border-gray-300 px-4 py-2">{new Date(grade.submitted_at).toLocaleDateString()}</td>
                                <td className="border border-gray-300 px-4 py-2">
                                  <div className="flex gap-2">
                                    <Button size="sm" onClick={() => approveGrade(grade.id)}>Approve</Button>
                                    <Button size="sm" variant="destructive" onClick={() => {
                                      const reason = prompt('Enter rejection reason:');
                                      if (reason) rejectGrade(grade.id, reason);
                                    }}>Reject</Button>
                                  </div>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </div>

                  {/* Update Requests Section */}
                  <div className="space-y-4 pt-6 border-t">
                    <h4 className="font-medium">Grade Update Requests</h4>
                    {updateRequestsLoading ? (
                      <p>Loading update requests...</p>
                    ) : updateRequests.length === 0 ? (
                      <p className="text-gray-500">No pending update requests.</p>
                    ) : (
                      <div className="overflow-x-auto">
                        <table className="w-full border-collapse border border-gray-300">
                          <thead>
                            <tr className="bg-gray-100">
                              <th className="border border-gray-300 px-4 py-2 text-left">No</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Student</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Subject</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Exam</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Current Score</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">New Score</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Teacher</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Reason</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Requested</th>
                              <th className="border border-gray-300 px-4 py-2 text-left">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            {updateRequests.map((request, index) => (
                              <tr key={request.id} className="hover:bg-gray-50">
                                <td className="border border-gray-300 px-4 py-2">{index + 1}</td>
                                <td className="border border-gray-300 px-4 py-2">{request.student_name}</td>
                                <td className="border border-gray-300 px-4 py-2">{request.subject_name}</td>
                                <td className="border border-gray-300 px-4 py-2">{request.exam_name}</td>
                                <td className="border border-gray-300 px-4 py-2">{request.current_score}</td>
                                <td className="border border-gray-300 px-4 py-2">
                                  <span className="font-medium text-blue-600">{request.new_score}</span>
                                </td>
                                <td className="border border-gray-300 px-4 py-2">{request.teacher_name}</td>
                                <td className="border border-gray-300 px-4 py-2">
                                  <p className="text-sm max-w-xs truncate" title={request.reason}>{request.reason}</p>
                                </td>
                                <td className="border border-gray-300 px-4 py-2">{new Date(request.requested_at).toLocaleDateString()}</td>
                                <td className="border border-gray-300 px-4 py-2">
                                  <div className="flex gap-2">
                                    <Button size="sm" onClick={() => approveUpdateRequest(request.id)}>Approve</Button>
                                    <Button size="sm" variant="destructive" onClick={() => {
                                      const reason = prompt('Enter rejection reason:');
                                      if (reason) rejectUpdateRequest(request.id, reason);
                                    }}>Reject</Button>
                                  </div>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </div>
                </div>
              </TabsContent>
            ) : null}

            {profile.is_exam_officer ? (
              <TabsContent value="exam">
                <div className="mt-4 space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                      <Label>Exam</Label>
                      <Select value={selectedExam} onValueChange={setSelectedExam}>
                        <SelectTrigger><SelectValue placeholder="All Exams" /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="ALL">All Exams</SelectItem>
                          {exams.map(ex => (
                            <SelectItem key={ex.id} value={String(ex.id)}>{ex.name}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                    <div>
                      <Label>Subject</Label>
                      <Select value={selectedSubject} onValueChange={setSelectedSubject}>
                        <SelectTrigger><SelectValue placeholder="All Subjects" /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="ALL">All Subjects</SelectItem>
                          {subjectsMap.map(su => (
                            <SelectItem key={su.id} value={String(su.id)}>{su.name}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="flex gap-2">
                      <Button variant="outline" onClick={() => { setSelectedExam('ALL'); setSelectedSubject('ALL'); }}>Reset</Button>
                      <Button asChild><a href="/ExamOfficer">Open Full Dashboard</a></Button>
                    </div>
                  </div>

                  {examLoading ? (
                    <p>Loading pending results...</p>
                  ) : pendingResults.length === 0 ? (
                    <p>No pending results.</p>
                  ) : (
                    <div className="space-y-2">
                      {pendingResults.map(r => (
                        <div key={r.id} className="p-3 border rounded-md flex items-center justify-between">
                          <div>
                            <p className="font-medium">{r.student_name} - {r.subject_name}</p>
                            <p className="text-sm text-gray-500">Exam: {r.exam_name} · Date: {r.exam_date}</p>
                          </div>
                          <div className="flex gap-2 items-center">
                            <Button size="sm" onClick={() => approveResult(r.id)}>Approve</Button>
                            <Button size="sm" variant="destructive" onClick={() => setRejectingId(r.id)}>Reject</Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                  {rejectingId && (
                    <div className="mt-3 p-3 border rounded-md">
                      <Label htmlFor="reason">Rejection Reason</Label>
                      <Input id="reason" value={rejectionReason} onChange={e => setRejectionReason(e.target.value)} placeholder="Enter reason..." />
                      <div className="flex gap-2 mt-2">
                        <Button size="sm" onClick={rejectResult}>Submit</Button>
                        <Button size="sm" variant="outline" onClick={() => { setRejectingId(null); setRejectionReason(''); }}>Cancel</Button>
                      </div>
                    </div>
                  )}
                </div>
              </TabsContent>
            ) : null}
          </Tabs>
        </CardContent>
      </Card>
    </div>
  )
}

export default TeacherProfile
