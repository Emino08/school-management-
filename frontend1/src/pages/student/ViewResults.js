import React, { useState, useEffect } from "react";
import { useSelector } from "react-redux";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../../components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../components/ui/select";
import { Badge } from "../../components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../../components/ui/table";
import { Calendar, Clock, Award, AlertCircle, CheckCircle } from "lucide-react";

const ViewResults = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [exams, setExams] = useState([]);
  const [selectedExam, setSelectedExam] = useState("");
  const [results, setResults] = useState([]);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(false);
  const [publicationInfo, setPublicationInfo] = useState(null);
  const [isPublished, setIsPublished] = useState(false);

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    fetchExams();
  }, []);

  useEffect(() => {
    if (selectedExam) {
      fetchResults();
    } else {
      setResults([]);
      setSummary(null);
      setIsPublished(false);
      setPublicationInfo(null);
    }
  }, [selectedExam]);

  const fetchExams = async () => {
    try {
      // Fetch exams for the student
      const response = await axios.get(`${API_URL}/exams`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (response.data.success) {
        setExams(response.data.exams || []);
      }
    } catch (error) {
      toast.error("Failed to fetch exams");
    }
  };

  const fetchResults = async () => {
    setLoading(true);
    try {
      const response = await axios.get(
        `${API_URL}/results/student/${currentUser?.id}/exam/${selectedExam}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` }
        }
      );

      if (response.data.success) {
        setResults(response.data.results || []);
        setSummary(response.data.summary);
        setIsPublished(response.data.is_published);
        setPublicationInfo(null);
      }
    } catch (error) {
      if (error.response?.status === 403) {
        // Results not published yet
        setIsPublished(false);
        setPublicationInfo({
          message: error.response.data.message,
          publication_date: error.response.data.publication_date
        });
        setResults([]);
        setSummary(null);
      } else {
        toast.error("Failed to fetch results");
      }
    } finally {
      setLoading(false);
    }
  };

  const calculateDaysUntil = (dateString) => {
    if (!dateString) return null;

    const publicationDate = new Date(dateString);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    publicationDate.setHours(0, 0, 0, 0);

    const diffTime = publicationDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return diffDays;
  };

  const formatDate = (dateString) => {
    if (!dateString) return "Not set";
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  return (
    <div className="p-6 max-w-6xl mx-auto space-y-6">
      {/* Header */}
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl flex items-center gap-2">
            <Award className="w-6 h-6" />
            My Exam Results
          </CardTitle>
          <CardDescription>
            View your exam results and performance
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            <label className="text-sm font-medium">Select Exam</label>
            <Select value={selectedExam} onValueChange={setSelectedExam}>
              <SelectTrigger>
                <SelectValue placeholder="Choose an exam to view results" />
              </SelectTrigger>
              <SelectContent>
                {exams.map((exam) => (
                  <SelectItem key={exam.id} value={exam.id.toString()}>
                    {exam.exam_name} - {exam.class_name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Publication Status */}
      {selectedExam && !isPublished && publicationInfo && (
        <Card className="border-yellow-200 bg-yellow-50">
          <CardContent className="pt-6">
            <div className="flex items-start gap-4">
              <Clock className="w-12 h-12 text-yellow-600 flex-shrink-0" />
              <div className="space-y-2">
                <h3 className="text-lg font-semibold text-yellow-900">
                  Results Not Yet Available
                </h3>
                <p className="text-yellow-800">
                  {publicationInfo.message}
                </p>

                {publicationInfo.publication_date && (
                  <>
                    <div className="flex items-center gap-2 text-yellow-900 mt-4">
                      <Calendar className="w-5 h-5" />
                      <span className="font-semibold">
                        Publication Date: {formatDate(publicationInfo.publication_date)}
                      </span>
                    </div>

                    {(() => {
                      const daysUntil = calculateDaysUntil(publicationInfo.publication_date);
                      if (daysUntil !== null && daysUntil > 0) {
                        return (
                          <div className="mt-2 p-3 bg-yellow-100 rounded-md">
                            <p className="text-sm font-medium text-yellow-900">
                              ⏱️ Results will be available in {daysUntil} {daysUntil === 1 ? 'day' : 'days'}
                            </p>
                          </div>
                        );
                      } else if (daysUntil === 0) {
                        return (
                          <div className="mt-2 p-3 bg-green-100 rounded-md">
                            <p className="text-sm font-medium text-green-900">
                              🎉 Results will be available today!
                            </p>
                          </div>
                        );
                      }
                      return null;
                    })()}
                  </>
                )}
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Loading State */}
      {loading && (
        <Card>
          <CardContent className="py-12 text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading results...</p>
          </CardContent>
        </Card>
      )}

      {/* Results Display */}
      {selectedExam && isPublished && !loading && (
        <>
          {/* Summary Card */}
          {summary && (
            <Card className="border-green-200 bg-green-50">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-green-900">
                  <CheckCircle className="w-5 h-5" />
                  Overall Performance
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="text-center p-4 bg-white rounded-lg">
                    <div className="text-3xl font-bold text-gray-900">
                      {summary.total_marks_obtained || 0}
                    </div>
                    <div className="text-sm text-gray-600 mt-1">Total Marks</div>
                  </div>

                  <div className="text-center p-4 bg-white rounded-lg">
                    <div className="text-3xl font-bold text-blue-600">
                      {summary.percentage || 0}%
                    </div>
                    <div className="text-sm text-gray-600 mt-1">Percentage</div>
                  </div>

                  <div className="text-center p-4 bg-white rounded-lg">
                    <div className="text-3xl font-bold text-purple-600">
                      {summary.grade || 'N/A'}
                    </div>
                    <div className="text-sm text-gray-600 mt-1">Grade</div>
                  </div>

                  <div className="text-center p-4 bg-white rounded-lg">
                    <div className="text-3xl font-bold text-orange-600">
                      {summary.position || 'N/A'}
                    </div>
                    <div className="text-sm text-gray-600 mt-1">Position</div>
                  </div>
                </div>

                {summary.remarks && (
                  <div className="mt-4 p-3 bg-white rounded-lg">
                    <p className="text-sm"><strong>Remarks:</strong> {summary.remarks}</p>
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {/* Subject-wise Results */}
          <Card>
            <CardHeader>
              <CardTitle>Subject-wise Results</CardTitle>
              <CardDescription>
                Detailed breakdown of your performance in each subject
              </CardDescription>
            </CardHeader>
            <CardContent>
              {results.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <AlertCircle className="w-12 h-12 mx-auto mb-2 text-gray-400" />
                  <p>No results available for this exam</p>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Subject</TableHead>
                        <TableHead>Subject Code</TableHead>
                        <TableHead className="text-center">Test Score</TableHead>
                        <TableHead className="text-center">Exam Score</TableHead>
                        <TableHead className="text-center">Total</TableHead>
                        <TableHead className="text-center">Grade</TableHead>
                        <TableHead>Remarks</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {results.map((result, index) => (
                        <TableRow key={index}>
                          <TableCell className="font-medium">{result.subject_name}</TableCell>
                          <TableCell>{result.subject_code}</TableCell>
                          <TableCell className="text-center">{result.test_score}</TableCell>
                          <TableCell className="text-center">{result.exam_score}</TableCell>
                          <TableCell className="text-center font-bold">
                            {parseFloat(result.test_score) + parseFloat(result.exam_score)}
                          </TableCell>
                          <TableCell className="text-center">
                            <Badge variant={
                              result.grade === 'A' ? 'success' :
                              result.grade === 'F' ? 'destructive' :
                              'default'
                            }>
                              {result.grade || 'N/A'}
                            </Badge>
                          </TableCell>
                          <TableCell>{result.remarks || '—'}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </>
      )}

      {/* No Exam Selected */}
      {!selectedExam && !loading && (
        <Card>
          <CardContent className="py-12 text-center text-gray-500">
            <Award className="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <p className="text-lg">Select an exam to view your results</p>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default ViewResults;
