import { useEffect, useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Edit2, Clock } from 'lucide-react';
import axios from '../../redux/axiosConfig';
import { useSelector } from 'react-redux';
import GradeEditRequestModal from '../../components/modals/GradeEditRequestModal';

const TeacherSubmissions = () => {
  const { currentUser } = useSelector((state) => state.user);
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';
  const teacherId = currentUser?.teacher?.id || currentUser?.id;

  const [submissions, setSubmissions] = useState([]);
  const [approvalFilter, setApprovalFilter] = useState('all');
  const [examId, setExamId] = useState('');
  const [subjectId, setSubjectId] = useState('');
  const [classId, setClassId] = useState('');
  const [exams, setExams] = useState([]);
  const [subjects, setSubjects] = useState([]);
  const [classes, setClasses] = useState([]);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedResult, setSelectedResult] = useState(null);

  useEffect(() => {
    const fetchFilters = async () => {
      try {
        const [ex, subs, cls] = await Promise.all([
          axios.get(`${API_URL}/exams`),
          axios.get(`${API_URL}/teachers/${teacherId}/subjects`),
          axios.get(`${API_URL}/classes`),
        ]);
        if (ex.data?.success) setExams(ex.data.exams || []);
        if (subs.data?.success) setSubjects(subs.data.subjects || []);
        if (cls.data?.success) setClasses(cls.data.classes || []);
      } catch (e) {
        console.error('Failed to fetch filters', e);
      }
    };
    fetchFilters();
  }, [teacherId]);

  const fetchSubmissions = async () => {
    try {
      let url = `${API_URL}/teachers/${teacherId}/submissions`;
      const params = new URLSearchParams();
      if (approvalFilter !== 'all') params.append('approval_status', approvalFilter);
      if (examId) params.append('exam_id', examId);
      if (subjectId) params.append('subject_id', subjectId);
      if (classId) params.append('class_id', classId);
      if (params.toString()) url += `?${params.toString()}`;
      const res = await axios.get(url);
      if (res.data?.success) setSubmissions(res.data.submissions || []);
    } catch (e) {
      console.error('Failed to fetch submissions', e);
    }
  };

  useEffect(() => { fetchSubmissions(); }, [approvalFilter, examId, subjectId, classId]);

  const getStatusBadge = (status) => {
    switch (status) {
      case 'approved': return <Badge className="bg-green-100 text-green-800">Approved</Badge>;
      case 'pending': return <Badge className="bg-yellow-100 text-yellow-800">Pending</Badge>;
      case 'rejected': return <Badge className="bg-red-100 text-red-800">Rejected</Badge>;
      default: return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const handleEditClick = (result) => {
    setSelectedResult(result);
    setShowEditModal(true);
  };

  const handleEditSuccess = () => {
    fetchSubmissions();
  };

  const canEdit = (result) => {
    // Can edit if approved and not published
    return result.approval_status === 'approved' && !result.is_published;
  };

  return (
    <div className="container mx-auto p-6 max-w-6xl space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl">My Submissions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={approvalFilter} onValueChange={setApprovalFilter}>
                <SelectTrigger><SelectValue placeholder="All" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="approved">Approved</SelectItem>
                  <SelectItem value="rejected">Rejected</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Exam</Label>
              <Select value={examId} onValueChange={setExamId}>
                <SelectTrigger><SelectValue placeholder="All Exams" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Exams</SelectItem>
                  {exams.map(ex => (<SelectItem key={ex.id} value={String(ex.id)}>{ex.exam_name}</SelectItem>))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Subject</Label>
              <Select value={subjectId} onValueChange={setSubjectId}>
                <SelectTrigger><SelectValue placeholder="All Subjects" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Subjects</SelectItem>
                  {subjects.map(su => (<SelectItem key={su.id} value={String(su.id)}>{su.subject_name}</SelectItem>))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Class</Label>
              <Select value={classId} onValueChange={setClassId}>
                <SelectTrigger><SelectValue placeholder="All Classes" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Classes</SelectItem>
                  {classes.map(cls => (<SelectItem key={cls.id} value={String(cls.id)}>{cls.class_name}</SelectItem>))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="mt-6 overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Exam</TableHead>
                  <TableHead>Subject</TableHead>
                  <TableHead>Class</TableHead>
                  <TableHead>Student</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Published</TableHead>
                  <TableHead>Verified</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {submissions.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} className="text-center py-8 text-gray-500">
                      No submissions found
                    </TableCell>
                  </TableRow>
                ) : (
                  submissions.map((r) => (
                    <TableRow key={r.id}>
                      <TableCell>{r.exam_name}</TableCell>
                      <TableCell>{r.subject_name}</TableCell>
                      <TableCell>{r.class_name || '-'}</TableCell>
                      <TableCell>{r.student_name}</TableCell>
                      <TableCell>{getStatusBadge(r.approval_status)}</TableCell>
                      <TableCell>
                        {r.is_published ? (
                          <Badge variant="outline" className="bg-blue-50 text-blue-700">Yes</Badge>
                        ) : (
                          <Badge variant="outline">No</Badge>
                        )}
                      </TableCell>
                      <TableCell>
                        {r.is_verified ? (
                          <Badge variant="outline" className="bg-green-50 text-green-700">Verified</Badge>
                        ) : (
                          <Badge variant="outline" className="bg-gray-50 text-gray-600">
                            <Clock className="w-3 h-3 mr-1" />
                            Pending
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-right">
                        {canEdit(r) ? (
                          <TooltipProvider>
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  className="bg-blue-50 hover:bg-blue-100 text-blue-700"
                                  onClick={() => handleEditClick(r)}
                                >
                                  <Edit2 className="w-4 h-4 mr-1" />
                                  Edit
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>
                                <p>Request grade change</p>
                              </TooltipContent>
                            </Tooltip>
                          </TooltipProvider>
                        ) : (
                          <TooltipProvider>
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  disabled
                                >
                                  <Edit2 className="w-4 h-4 mr-1" />
                                  Edit
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>
                                <p>
                                  {r.is_published
                                    ? 'Cannot edit published results'
                                    : r.approval_status === 'pending'
                                    ? 'Wait for approval first'
                                    : 'Cannot edit rejected results'}
                                </p>
                              </TooltipContent>
                            </Tooltip>
                          </TooltipProvider>
                        )}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Grade Edit Request Modal */}
      <GradeEditRequestModal
        isOpen={showEditModal}
        onClose={() => setShowEditModal(false)}
        result={selectedResult}
        onSuccess={handleEditSuccess}
      />
    </div>
  );
};

export default TeacherSubmissions;

