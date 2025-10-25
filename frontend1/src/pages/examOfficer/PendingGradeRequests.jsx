import React, { useEffect, useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { CheckCircle, XCircle, Eye, Clock } from 'lucide-react';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';

const PendingGradeRequests = ({ onUpdate }) => {
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [showRejectDialog, setShowRejectDialog] = useState(false);
  const [rejectionReason, setRejectionReason] = useState('');
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    fetchRequests();
  }, []);

  const fetchRequests = async () => {
    try {
      setLoading(true);
      const res = await axios.get(`${API_URL}/exam-officer/grade-requests/pending?status=pending`);
      if (res.data?.success) {
        setRequests(res.data.requests || []);
      }
    } catch (error) {
      console.error('Failed to fetch requests:', error);
      toast.error('Failed to load grade change requests');
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (requestId) => {
    if (!confirm('Are you sure you want to approve this grade change?')) return;

    try {
      setActionLoading(true);
      const res = await axios.post(`${API_URL}/exam-officer/grade-requests/${requestId}/approve`);
      if (res.data?.success) {
        toast.success('Grade change approved successfully');
        fetchRequests();
        if (onUpdate) onUpdate();
      }
    } catch (error) {
      console.error('Failed to approve request:', error);
      toast.error(error.response?.data?.message || 'Failed to approve grade change');
    } finally {
      setActionLoading(false);
    }
  };

  const handleReject = async () => {
    if (!selectedRequest || !rejectionReason.trim()) {
      toast.error('Please provide a rejection reason');
      return;
    }

    try {
      setActionLoading(true);
      const res = await axios.post(
        `${API_URL}/exam-officer/grade-requests/${selectedRequest.id}/reject`,
        { rejection_reason: rejectionReason }
      );
      if (res.data?.success) {
        toast.success('Grade change rejected');
        setShowRejectDialog(false);
        setSelectedRequest(null);
        setRejectionReason('');
        fetchRequests();
        if (onUpdate) onUpdate();
      }
    } catch (error) {
      console.error('Failed to reject request:', error);
      toast.error(error.response?.data?.message || 'Failed to reject grade change');
    } finally {
      setActionLoading(false);
    }
  };

  const openRejectDialog = (request) => {
    setSelectedRequest(request);
    setShowRejectDialog(true);
    setRejectionReason('');
  };

  if (loading) {
    return (
      <div className="text-center py-8">
        <Clock className="w-8 h-8 animate-spin mx-auto mb-2 text-blue-500" />
        <p className="text-gray-600">Loading requests...</p>
      </div>
    );
  }

  if (requests.length === 0) {
    return (
      <div className="text-center py-12">
        <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-4" />
        <h3 className="text-xl font-semibold mb-2">All Caught Up!</h3>
        <p className="text-gray-600">
          There are no pending grade change requests at the moment.
        </p>
      </div>
    );
  }

  return (
    <>
      <div className="space-y-4">
        <div className="rounded-md border overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Student</TableHead>
                <TableHead>Subject</TableHead>
                <TableHead>Exam</TableHead>
                <TableHead>Teacher</TableHead>
                <TableHead>Current</TableHead>
                <TableHead>New</TableHead>
                <TableHead>Reason</TableHead>
                <TableHead>Date</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {requests.map((request) => (
                <TableRow key={request.id}>
                  <TableCell className="font-medium">
                    <div>
                      <div>{request.student_name}</div>
                      <div className="text-xs text-gray-500">{request.id_number}</div>
                    </div>
                  </TableCell>
                  <TableCell>{request.subject_name}</TableCell>
                  <TableCell>{request.exam_name}</TableCell>
                  <TableCell>{request.teacher_name}</TableCell>
                  <TableCell>
                    <Badge variant="outline">{request.current_score}</Badge>
                  </TableCell>
                  <TableCell>
                    <Badge className="bg-blue-100 text-blue-800">
                      {request.new_score}
                    </Badge>
                  </TableCell>
                  <TableCell className="max-w-xs">
                    <div className="text-sm truncate" title={request.reason}>
                      {request.reason}
                    </div>
                  </TableCell>
                  <TableCell className="text-sm text-gray-600">
                    {new Date(request.requested_at).toLocaleDateString()}
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-2">
                      <Button
                        size="sm"
                        variant="outline"
                        className="bg-green-50 hover:bg-green-100 text-green-700"
                        onClick={() => handleApprove(request.id)}
                        disabled={actionLoading}
                      >
                        <CheckCircle className="w-4 h-4 mr-1" />
                        Approve
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        className="bg-red-50 hover:bg-red-100 text-red-700"
                        onClick={() => openRejectDialog(request)}
                        disabled={actionLoading}
                      >
                        <XCircle className="w-4 h-4 mr-1" />
                        Reject
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </div>

      {/* Reject Dialog */}
      <Dialog open={showRejectDialog} onOpenChange={setShowRejectDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Reject Grade Change Request</DialogTitle>
            <DialogDescription>
              Please provide a reason for rejecting this request. This will be sent to the teacher.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            {selectedRequest && (
              <div className="space-y-2 p-4 bg-gray-50 rounded-md">
                <div className="flex justify-between">
                  <span className="font-medium">Student:</span>
                  <span>{selectedRequest.student_name}</span>
                </div>
                <div className="flex justify-between">
                  <span className="font-medium">Subject:</span>
                  <span>{selectedRequest.subject_name}</span>
                </div>
                <div className="flex justify-between">
                  <span className="font-medium">Change:</span>
                  <span>
                    {selectedRequest.current_score} → {selectedRequest.new_score}
                  </span>
                </div>
              </div>
            )}
            <div className="space-y-2">
              <Label htmlFor="rejection-reason">Rejection Reason *</Label>
              <Textarea
                id="rejection-reason"
                placeholder="Explain why this request is being rejected..."
                value={rejectionReason}
                onChange={(e) => setRejectionReason(e.target.value)}
                rows={4}
              />
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setShowRejectDialog(false);
                setSelectedRequest(null);
                setRejectionReason('');
              }}
              disabled={actionLoading}
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={handleReject}
              disabled={actionLoading || !rejectionReason.trim()}
            >
              {actionLoading ? 'Rejecting...' : 'Reject Request'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default PendingGradeRequests;
