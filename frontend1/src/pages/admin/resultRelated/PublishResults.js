import React, { useState, useEffect } from "react";
import { useSelector } from "react-redux";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../../../components/ui/card";
import { Label } from "../../../components/ui/label";
import { Input } from "../../../components/ui/input";
import { Button } from "../../../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../../components/ui/select";
import { Textarea } from "../../../components/ui/textarea";
import { Badge } from "../../../components/ui/badge";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "../../../components/ui/alert-dialog";
import { Send, CheckCircle, XCircle, Clock, AlertTriangle, Calendar } from "lucide-react";

const PublishResults = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [exams, setExams] = useState([]);
  const [selectedExam, setSelectedExam] = useState("");
  const [publicationDate, setPublicationDate] = useState("");
  const [notes, setNotes] = useState("");
  const [loading, setLoading] = useState(false);
  const [examStats, setExamStats] = useState(null);
  const [showConfirmDialog, setShowConfirmDialog] = useState(false);

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    fetchExams();
  }, []);

  useEffect(() => {
    if (selectedExam) {
      fetchExamStats();
    } else {
      setExamStats(null);
    }
  }, [selectedExam]);

  const fetchExams = async () => {
    try {
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

  const fetchExamStats = async () => {
    setLoading(true);
    try {
      // This is a custom endpoint you'll need to create or we can use the publish endpoint
      // For now, we'll simulate by making a HEAD request
      const response = await axios.get(
        `${API_URL}/exam-officer/pending-results?exam_id=${selectedExam}`,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` }
        }
      );

      if (response.data.success) {
        const results = response.data.pending_results || [];

        // Count statuses
        const approved = results.filter(r => r.approval_status === 'approved').length;
        const pending = results.filter(r => r.approval_status === 'pending').length;
        const rejected = results.filter(r => r.approval_status === 'rejected').length;
        const total = results.length;

        setExamStats({
          total_results: total,
          approved_count: approved,
          pending_count: pending,
          rejected_count: rejected
        });
      }
    } catch (error) {
      // Fallback stats
      setExamStats({
        total_results: 0,
        approved_count: 0,
        pending_count: 0,
        rejected_count: 0
      });
    } finally {
      setLoading(false);
    }
  };

  const canPublish = () => {
    if (!examStats) return false;
    return examStats.pending_count === 0 && examStats.approved_count > 0;
  };

  const handlePublish = async () => {
    if (!selectedExam || !publicationDate) {
      toast.error("Please select exam and publication date");
      return;
    }

    if (!canPublish()) {
      toast.error("Cannot publish: Please ensure all results are approved");
      return;
    }

    setLoading(true);
    try {
      const response = await axios.post(
        `${API_URL}/results/publish/${selectedExam}`,
        {
          publication_date: publicationDate,
          notes: notes
        },
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` }
        }
      );

      if (response.data.success) {
        toast.success(response.data.message);

        // Reset form
        setSelectedExam("");
        setPublicationDate("");
        setNotes("");
        setExamStats(null);
        setShowConfirmDialog(false);
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "Failed to publish results");
    } finally {
      setLoading(false);
    }
  };

  const getMinDate = () => {
    const today = new Date();
    return today.toISOString().split('T')[0];
  };

  return (
    <div className="p-6 max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl flex items-center gap-2">
            <Send className="w-6 h-6" />
            Publish Exam Results
          </CardTitle>
          <CardDescription>
            Set publication date and release results to students
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Exam Selection */}
          <div className="space-y-2">
            <Label htmlFor="exam">Select Exam *</Label>
            <Select value={selectedExam} onValueChange={setSelectedExam}>
              <SelectTrigger>
                <SelectValue placeholder="Choose an exam to publish" />
              </SelectTrigger>
              <SelectContent>
                {exams.map((exam) => (
                  <SelectItem key={exam.id} value={exam.id.toString()}>
                    {exam.exam_name} - {exam.class_name} ({exam.year_name})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Publication Date */}
          <div className="space-y-2">
            <Label htmlFor="publicationDate">Publication Date *</Label>
            <div className="relative">
              <Calendar className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
              <Input
                id="publicationDate"
                type="date"
                value={publicationDate}
                onChange={(e) => setPublicationDate(e.target.value)}
                min={getMinDate()}
                className="pl-10"
                required
              />
            </div>
            <p className="text-xs text-gray-500">
              Results will be visible to students on this date
            </p>
          </div>

          {/* Notes */}
          <div className="space-y-2">
            <Label htmlFor="notes">Notes (Optional)</Label>
            <Textarea
              id="notes"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Add any notes about this publication..."
              rows={3}
            />
          </div>
        </CardContent>
      </Card>

      {/* Statistics Card */}
      {selectedExam && examStats && (
        <Card>
          <CardHeader>
            <CardTitle>Approval Statistics</CardTitle>
            <CardDescription>
              Current status of results for selected exam
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="text-center p-4 bg-gray-50 rounded-lg">
                <div className="text-3xl font-bold text-gray-900">
                  {examStats.total_results}
                </div>
                <div className="text-sm text-gray-600 mt-1">Total Results</div>
              </div>

              <div className="text-center p-4 bg-green-50 rounded-lg">
                <div className="flex items-center justify-center gap-2">
                  <CheckCircle className="w-6 h-6 text-green-600" />
                  <div className="text-3xl font-bold text-green-600">
                    {examStats.approved_count}
                  </div>
                </div>
                <div className="text-sm text-gray-600 mt-1">Approved</div>
              </div>

              <div className="text-center p-4 bg-yellow-50 rounded-lg">
                <div className="flex items-center justify-center gap-2">
                  <Clock className="w-6 h-6 text-yellow-600" />
                  <div className="text-3xl font-bold text-yellow-600">
                    {examStats.pending_count}
                  </div>
                </div>
                <div className="text-sm text-gray-600 mt-1">Pending</div>
              </div>

              <div className="text-center p-4 bg-red-50 rounded-lg">
                <div className="flex items-center justify-center gap-2">
                  <XCircle className="w-6 h-6 text-red-600" />
                  <div className="text-3xl font-bold text-red-600">
                    {examStats.rejected_count}
                  </div>
                </div>
                <div className="text-sm text-gray-600 mt-1">Rejected</div>
              </div>
            </div>

            {/* Validation Messages */}
            <div className="mt-6 space-y-3">
              {examStats.pending_count > 0 && (
                <div className="flex items-start gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                  <AlertTriangle className="w-5 h-5 text-yellow-600 mt-0.5" />
                  <div>
                    <p className="font-semibold text-yellow-900">Cannot Publish Yet</p>
                    <p className="text-sm text-yellow-700">
                      {examStats.pending_count} results are still pending approval by exam officers.
                      All results must be approved before publication.
                    </p>
                  </div>
                </div>
              )}

              {examStats.approved_count === 0 && examStats.pending_count === 0 && (
                <div className="flex items-start gap-3 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                  <AlertTriangle className="w-5 h-5 text-gray-600 mt-0.5" />
                  <div>
                    <p className="font-semibold text-gray-900">No Results to Publish</p>
                    <p className="text-sm text-gray-700">
                      There are no approved results for this exam.
                    </p>
                  </div>
                </div>
              )}

              {canPublish() && (
                <div className="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                  <CheckCircle className="w-5 h-5 text-green-600 mt-0.5" />
                  <div>
                    <p className="font-semibold text-green-900">Ready to Publish</p>
                    <p className="text-sm text-green-700">
                      {examStats.approved_count} approved results will be published on {publicationDate || "the selected date"}.
                      {examStats.rejected_count > 0 && ` ${examStats.rejected_count} rejected results will not be published.`}
                    </p>
                  </div>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Publish Button */}
      <div className="flex gap-4">
        <Button
          onClick={() => setShowConfirmDialog(true)}
          disabled={!canPublish() || !publicationDate || loading}
          className="flex-1 gap-2"
          size="lg"
        >
          <Send className="w-5 h-5" />
          {loading ? "Publishing..." : "Publish Results"}
        </Button>
        <Button
          variant="outline"
          onClick={() => {
            setSelectedExam("");
            setPublicationDate("");
            setNotes("");
            setExamStats(null);
          }}
          size="lg"
        >
          Reset
        </Button>
      </div>

      {/* Confirmation Dialog */}
      <AlertDialog open={showConfirmDialog} onOpenChange={setShowConfirmDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirm Result Publication</AlertDialogTitle>
            <AlertDialogDescription className="space-y-2">
              <p>You are about to publish exam results with the following details:</p>
              <div className="bg-gray-50 p-3 rounded-md space-y-1 text-sm">
                <p><strong>Exam:</strong> {exams.find(e => e.id.toString() === selectedExam)?.exam_name}</p>
                <p><strong>Publication Date:</strong> {publicationDate}</p>
                <p><strong>Approved Results:</strong> {examStats?.approved_count}</p>
                {examStats?.rejected_count > 0 && (
                  <p className="text-red-600">
                    <strong>Note:</strong> {examStats.rejected_count} rejected results will NOT be published
                  </p>
                )}
              </div>
              <p className="text-yellow-700 mt-2">
                ⚠️ Students will be able to view their results on {publicationDate}.
              </p>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handlePublish} className="bg-blue-600">
              Confirm & Publish
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default PublishResults;
