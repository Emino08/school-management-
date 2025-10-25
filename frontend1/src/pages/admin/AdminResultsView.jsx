import React, { useEffect, useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Shield, Eye, EyeOff, Download, Filter } from 'lucide-react';
import axios from '../../redux/axiosConfig';
import { useSelector } from 'react-redux';
import { toast } from 'sonner';

const AdminResultsView = () => {
  const { currentUser } = useSelector((state) => state.user);
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  const [results, setResults] = useState([]);
  const [exams, setExams] = useState([]);
  const [selectedExam, setSelectedExam] = useState('');
  const [showUnverified, setShowUnverified] = useState(false);
  const [loading, setLoading] = useState(false);
  const [stats, setStats] = useState({
    total: 0,
    verified: 0,
    unverified: 0,
    approved: 0,
    pending: 0
  });

  useEffect(() => {
    fetchExams();
  }, []);

  useEffect(() => {
    if (selectedExam) {
      fetchResults();
    }
  }, [selectedExam, showUnverified]);

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

  const fetchResults = async () => {
    if (!selectedExam) return;

    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (showUnverified) {
        params.append('show_unverified', 'true');
      }

      const res = await axios.get(
        `${API_URL}/results/exam/${selectedExam}?${params.toString()}`
      );

      if (res.data?.success) {
        const allResults = res.data.results || [];
        setResults(allResults);

        // Calculate stats
        const total = allResults.length;
        const verified = allResults.filter(r => r.is_verified).length;
        const unverified = total - verified;
        const approved = allResults.filter(r => r.approval_status === 'approved').length;
        const pending = allResults.filter(r => r.approval_status === 'pending').length;

        setStats({ total, verified, unverified, approved, pending });
      }
    } catch (error) {
      console.error('Failed to fetch results:', error);
      toast.error('Failed to load results');
    } finally {
      setLoading(false);
    }
  };

  const getGradeColor = (grade) => {
    if (!grade) return 'bg-gray-100 text-gray-800';

    const upperGrade = grade.toUpperCase();
    if (upperGrade === 'A') return 'bg-green-100 text-green-800';
    if (upperGrade === 'B') return 'bg-blue-100 text-blue-800';
    if (upperGrade === 'C') return 'bg-yellow-100 text-yellow-800';
    if (upperGrade === 'D') return 'bg-orange-100 text-orange-800';
    if (upperGrade === 'F') return 'bg-red-100 text-red-800';

    return 'bg-gray-100 text-gray-800';
  };

  const exportToCSV = () => {
    if (results.length === 0) {
      toast.error('No results to export');
      return;
    }

    const headers = ['Student Name', 'Admission No', 'Subject', 'Test Score', 'Exam Score', 'Total', 'Grade', 'Verified', 'Teacher'];
    const rows = results.map(r => [
      r.student_name,
      r.admission_no || 'N/A',
      r.subject_name,
      r.test_score || 'N/A',
      r.exam_score || 'N/A',
      r.total_score || r.marks_obtained || 'N/A',
      r.grade || 'N/A',
      r.is_verified ? 'Yes' : 'No',
      r.teacher_name || 'N/A'
    ]);

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `exam_results_${selectedExam}_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);

    toast.success('Results exported successfully');
  };

  return (
    <div className="container mx-auto p-6 max-w-7xl space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Shield className="w-8 h-8 text-blue-600" />
          <div>
            <h1 className="text-3xl font-bold">Exam Results</h1>
            <p className="text-gray-600">View and manage verified exam results</p>
          </div>
        </div>
      </div>

      {/* Statistics Cards */}
      {selectedExam && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <Card>
            <CardContent className="p-4">
              <div className="text-sm text-gray-600">Total Results</div>
              <div className="text-2xl font-bold">{stats.total}</div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="text-sm text-gray-600">Verified</div>
              <div className="text-2xl font-bold text-green-600">{stats.verified}</div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="text-sm text-gray-600">Unverified</div>
              <div className="text-2xl font-bold text-yellow-600">{stats.unverified}</div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="text-sm text-gray-600">Approved</div>
              <div className="text-2xl font-bold text-blue-600">{stats.approved}</div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="text-sm text-gray-600">Pending</div>
              <div className="text-2xl font-bold text-orange-600">{stats.pending}</div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filters and Controls */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="w-5 h-5" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>Select Exam</Label>
              <Select value={selectedExam} onValueChange={setSelectedExam}>
                <SelectTrigger>
                  <SelectValue placeholder="Select an exam" />
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

            <div className="flex items-end">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="show-unverified"
                  checked={showUnverified}
                  onCheckedChange={setShowUnverified}
                />
                <Label htmlFor="show-unverified" className="cursor-pointer flex items-center gap-2">
                  {showUnverified ? <Eye className="w-4 h-4" /> : <EyeOff className="w-4 h-4" />}
                  Show Unverified Results
                </Label>
              </div>
            </div>

            {selectedExam && results.length > 0 && (
              <div className="flex items-end justify-end">
                <Button onClick={exportToCSV} variant="outline">
                  <Download className="w-4 h-4 mr-2" />
                  Export to CSV
                </Button>
              </div>
            )}
          </div>

          {!showUnverified && selectedExam && (
            <div className="flex items-center gap-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
              <Shield className="w-5 h-5 text-blue-600" />
              <p className="text-sm text-blue-800">
                <strong>Verified Only Mode:</strong> You are viewing only verified results.
                Enable "Show Unverified Results" to see all results.
              </p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Results Table */}
      {selectedExam && (
        <Card>
          <CardContent className="p-6">
            {loading ? (
              <div className="text-center py-12">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p className="text-gray-600">Loading results...</p>
              </div>
            ) : results.length === 0 ? (
              <div className="text-center py-12">
                <Shield className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 className="text-xl font-semibold mb-2">No Results Found</h3>
                <p className="text-gray-600">
                  {showUnverified
                    ? 'No results available for this exam yet.'
                    : 'No verified results available. Enable "Show Unverified Results" to see all results.'}
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Student</TableHead>
                      <TableHead>Admission No</TableHead>
                      <TableHead>Subject</TableHead>
                      <TableHead>Test Score</TableHead>
                      <TableHead>Exam Score</TableHead>
                      <TableHead>Total</TableHead>
                      <TableHead>Grade</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Verified</TableHead>
                      <TableHead>Teacher</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {results.map((result) => (
                      <TableRow key={result.id}>
                        <TableCell className="font-medium">{result.student_name}</TableCell>
                        <TableCell className="text-sm text-gray-600">
                          {result.admission_no || 'N/A'}
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
                          {result.total_score || result.marks_obtained || 'N/A'}
                        </TableCell>
                        <TableCell>
                          <Badge className={getGradeColor(result.grade)}>
                            {result.grade || 'N/A'}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          {result.approval_status === 'approved' ? (
                            <Badge variant="outline" className="bg-green-50 text-green-700">
                              Approved
                            </Badge>
                          ) : result.approval_status === 'pending' ? (
                            <Badge variant="outline" className="bg-yellow-50 text-yellow-700">
                              Pending
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="bg-red-50 text-red-700">
                              Rejected
                            </Badge>
                          )}
                        </TableCell>
                        <TableCell>
                          {result.is_verified ? (
                            <Badge variant="outline" className="bg-green-50 text-green-700">
                              <Shield className="w-3 h-3 mr-1" />
                              Verified
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="bg-gray-50 text-gray-600">
                              Pending
                            </Badge>
                          )}
                        </TableCell>
                        <TableCell className="text-sm text-gray-600">
                          {result.teacher_name || 'N/A'}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default AdminResultsView;
