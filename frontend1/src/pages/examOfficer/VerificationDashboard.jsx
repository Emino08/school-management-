import { useEffect, useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Shield, CheckCircle, Clock } from 'lucide-react';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';

const VerificationDashboard = ({ onUpdate }) => {
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';
  const [unverifiedResults, setUnverifiedResults] = useState([]);
  const [exams, setExams] = useState([]);
  const [selectedExam, setSelectedExam] = useState('');
  const [selectedResults, setSelectedResults] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    fetchExams();
  }, []);

  useEffect(() => {
    if (selectedExam) {
      fetchUnverifiedResults();
    }
  }, [selectedExam]);

  const fetchExams = async () => {
    try {
      const res = await axios.get(`${API_URL}/exams`);
      if (res.data?.success) {
        setExams(res.data.exams || []);
      }
    } catch (error) {
      console.error('Failed to fetch exams:', error);
      toast.error('Failed to load exams');
    }
  };

  const fetchUnverifiedResults = async () => {
    if (!selectedExam) return;

    try {
      setLoading(true);
      const res = await axios.get(
        `${API_URL}/results/exam/${selectedExam}?approval_status=approved&show_unverified=true`
      );
      if (res.data?.success) {
        // Filter to show only unverified results
        const unverified = (res.data.results || []).filter(r => !r.is_verified);
        setUnverifiedResults(unverified);
        setSelectedResults([]);
      }
    } catch (error) {
      console.error('Failed to fetch unverified results:', error);
      toast.error('Failed to load unverified results');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectAll = (checked) => {
    if (checked) {
      setSelectedResults(unverifiedResults.map(r => r.id));
    } else {
      setSelectedResults([]);
    }
  };

  const handleSelectResult = (resultId, checked) => {
    if (checked) {
      setSelectedResults([...selectedResults, resultId]);
    } else {
      setSelectedResults(selectedResults.filter(id => id !== resultId));
    }
  };

  const handleVerifySelected = async () => {
    if (selectedResults.length === 0) {
      toast.error('Please select at least one result to verify');
      return;
    }

    if (!confirm(`Are you sure you want to verify ${selectedResults.length} results?`)) {
      return;
    }

    try {
      setActionLoading(true);
      const res = await axios.post(`${API_URL}/exam-officer/verify-results`, {
        result_ids: selectedResults
      });
      if (res.data?.success) {
        toast.success(`Successfully verified ${res.data.verified_count} results`);
        fetchUnverifiedResults();
        if (onUpdate) onUpdate();
      }
    } catch (error) {
      console.error('Failed to verify results:', error);
      toast.error(error.response?.data?.message || 'Failed to verify results');
    } finally {
      setActionLoading(false);
    }
  };

  const handleBulkVerifyExam = async () => {
    if (!selectedExam) {
      toast.error('Please select an exam');
      return;
    }

    if (!confirm(`This will verify ALL approved results for this exam. Continue?`)) {
      return;
    }

    try {
      setActionLoading(true);
      const res = await axios.post(`${API_URL}/exam-officer/verify-exam/${selectedExam}`);
      if (res.data?.success) {
        toast.success(res.data.message);
        fetchUnverifiedResults();
        if (onUpdate) onUpdate();
      }
    } catch (error) {
      console.error('Failed to bulk verify:', error);
      toast.error(error.response?.data?.message || 'Failed to verify exam results');
    } finally {
      setActionLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header & Controls */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Shield className="w-5 h-5" />
            Grade Verification
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex gap-4 items-end">
            <div className="flex-1 space-y-2">
              <Label>Select Exam</Label>
              <Select value={selectedExam} onValueChange={setSelectedExam}>
                <SelectTrigger>
                  <SelectValue placeholder="Select an exam to verify" />
                </SelectTrigger>
                <SelectContent>
                  {exams.map(exam => (
                    <SelectItem key={exam.id} value={String(exam.id)}>
                      {exam.exam_name} ({exam.exam_type})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            {selectedExam && unverifiedResults.length > 0 && (
              <Button
                onClick={handleBulkVerifyExam}
                disabled={actionLoading}
                className="bg-green-600 hover:bg-green-700"
              >
                <CheckCircle className="w-4 h-4 mr-2" />
                Verify All ({unverifiedResults.length})
              </Button>
            )}
          </div>

          {selectedResults.length > 0 && (
            <div className="flex items-center justify-between bg-blue-50 p-4 rounded-md">
              <span className="text-sm font-medium">
                {selectedResults.length} result(s) selected
              </span>
              <Button
                size="sm"
                onClick={handleVerifySelected}
                disabled={actionLoading}
                className="bg-blue-600 hover:bg-blue-700"
              >
                Verify Selected
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Results Table */}
      {selectedExam && (
        <Card>
          <CardContent className="p-6">
            {loading ? (
              <div className="text-center py-8">
                <Clock className="w-8 h-8 animate-spin mx-auto mb-2 text-blue-500" />
                <p className="text-gray-600">Loading results...</p>
              </div>
            ) : unverifiedResults.length === 0 ? (
              <div className="text-center py-12">
                <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-4" />
                <h3 className="text-xl font-semibold mb-2">All Verified!</h3>
                <p className="text-gray-600">
                  All results for this exam have been verified.
                </p>
              </div>
            ) : (
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Checkbox
                      checked={selectedResults.length === unverifiedResults.length}
                      onCheckedChange={handleSelectAll}
                    />
                    <Label>Select All ({unverifiedResults.length} results)</Label>
                  </div>
                  <Badge variant="outline" className="bg-yellow-50 text-yellow-700">
                    Awaiting Verification
                  </Badge>
                </div>

                <div className="rounded-md border overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-12">
                          <span className="sr-only">Select</span>
                        </TableHead>
                        <TableHead>Student</TableHead>
                        <TableHead>Admission No</TableHead>
                        <TableHead>Subject</TableHead>
                        <TableHead>Test Score</TableHead>
                        <TableHead>Exam Score</TableHead>
                        <TableHead>Total</TableHead>
                        <TableHead>Grade</TableHead>
                        <TableHead>Teacher</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {unverifiedResults.map((result) => (
                        <TableRow key={result.id}>
                          <TableCell>
                            <Checkbox
                              checked={selectedResults.includes(result.id)}
                              onCheckedChange={(checked) => handleSelectResult(result.id, checked)}
                            />
                          </TableCell>
                          <TableCell className="font-medium">
                            {result.student_name}
                          </TableCell>
                          <TableCell className="text-sm text-gray-600">
                            {result.admission_no}
                          </TableCell>
                          <TableCell>
                            <div>
                              <div className="font-medium">{result.subject_name}</div>
                              <div className="text-xs text-gray-500">{result.subject_code}</div>
                            </div>
                          </TableCell>
                          <TableCell>{result.test_score || 'N/A'}</TableCell>
                          <TableCell>{result.exam_score || 'N/A'}</TableCell>
                          <TableCell className="font-semibold">
                            {result.total_score || result.marks_obtained}
                          </TableCell>
                          <TableCell>
                            <Badge>{result.grade || 'N/A'}</Badge>
                          </TableCell>
                          <TableCell className="text-sm text-gray-600">
                            {result.teacher_name || 'N/A'}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default VerificationDashboard;
