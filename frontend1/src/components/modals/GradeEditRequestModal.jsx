import { useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, Edit2 } from 'lucide-react';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';

const GradeEditRequestModal = ({ isOpen, onClose, result, onSuccess }) => {
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';
  const [formData, setFormData] = useState({
    field_to_update: '',
    new_value: '',
    reason: ''
  });
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.field_to_update || !formData.new_value || !formData.reason.trim()) {
      toast.error('Please fill in all fields');
      return;
    }

    try {
      setLoading(true);

      const requestData = {
        result_id: result.id,
        reason: formData.reason
      };

      // Add the appropriate score field based on selection
      if (formData.field_to_update === 'test_score') {
        requestData.new_test_score = parseFloat(formData.new_value);
      } else if (formData.field_to_update === 'exam_score') {
        requestData.new_exam_score = parseFloat(formData.new_value);
      } else if (formData.field_to_update === 'marks_obtained') {
        requestData.new_marks_obtained = parseFloat(formData.new_value);
      }

      const res = await axios.post(`${API_URL}/teachers/grade-change-request`, requestData);

      if (res.data?.success) {
        toast.success('Grade change request submitted successfully');
        setFormData({ field_to_update: '', new_value: '', reason: '' });
        if (onSuccess) onSuccess();
        onClose();
      }
    } catch (error) {
      console.error('Failed to submit request:', error);
      toast.error(error.response?.data?.message || 'Failed to submit grade change request');
    } finally {
      setLoading(false);
    }
  };

  const getCurrentValue = () => {
    if (!result || !formData.field_to_update) return 'N/A';

    switch (formData.field_to_update) {
      case 'test_score':
        return result.test_score || 'N/A';
      case 'exam_score':
        return result.exam_score || 'N/A';
      case 'marks_obtained':
        return result.marks_obtained || 'N/A';
      default:
        return 'N/A';
    }
  };

  const handleClose = () => {
    setFormData({ field_to_update: '', new_value: '', reason: '' });
    onClose();
  };

  if (!result) return null;

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Edit2 className="w-5 h-5" />
            Request Grade Change
          </DialogTitle>
          <DialogDescription>
            Submit a request to edit this grade. The request will be reviewed by an exam officer.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Result Information */}
          <div className="p-4 bg-gray-50 rounded-md space-y-2">
            <div className="flex justify-between">
              <span className="font-medium">Student:</span>
              <span>{result.student_name}</span>
            </div>
            <div className="flex justify-between">
              <span className="font-medium">Subject:</span>
              <span>{result.subject_name}</span>
            </div>
            <div className="flex justify-between">
              <span className="font-medium">Exam:</span>
              <span>{result.exam_name}</span>
            </div>
          </div>

          {/* Field Selection */}
          <div className="space-y-2">
            <Label htmlFor="field_to_update">
              Field to Update <span className="text-red-500">*</span>
            </Label>
            <select
              id="field_to_update"
              className="w-full rounded-md border border-gray-300 px-3 py-2"
              value={formData.field_to_update}
              onChange={(e) => setFormData({ ...formData, field_to_update: e.target.value, new_value: '' })}
              required
            >
              <option value="">Select field to update</option>
              {result.test_score !== undefined && result.test_score !== null && (
                <option value="test_score">Test Score</option>
              )}
              {result.exam_score !== undefined && result.exam_score !== null && (
                <option value="exam_score">Exam Score</option>
              )}
              {result.marks_obtained !== undefined && result.marks_obtained !== null && (
                <option value="marks_obtained">Marks Obtained</option>
              )}
            </select>
          </div>

          {/* Current vs New Value */}
          {formData.field_to_update && (
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Current Value</Label>
                <div className="flex items-center h-10 px-3 bg-gray-100 rounded-md border border-gray-300">
                  <Badge variant="outline">{getCurrentValue()}</Badge>
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="new_value">
                  New Value <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="new_value"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  placeholder="Enter new value"
                  value={formData.new_value}
                  onChange={(e) => setFormData({ ...formData, new_value: e.target.value })}
                  required
                />
              </div>
            </div>
          )}

          {/* Reason */}
          <div className="space-y-2">
            <Label htmlFor="reason">
              Reason for Change <span className="text-red-500">*</span>
            </Label>
            <Textarea
              id="reason"
              placeholder="Explain why this grade needs to be changed..."
              value={formData.reason}
              onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
              rows={4}
              required
            />
            <p className="text-xs text-gray-500">
              Provide a clear explanation for the exam officer to review.
            </p>
          </div>

          {/* Warning */}
          <div className="flex gap-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
            <AlertCircle className="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
            <div className="text-sm text-yellow-800">
              <strong>Note:</strong> This request will be sent to an exam officer for approval.
              The change will not take effect until it is approved.
            </div>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              disabled={loading}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={loading || !formData.field_to_update || !formData.new_value || !formData.reason.trim()}
            >
              {loading ? 'Submitting...' : 'Submit Request'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default GradeEditRequestModal;
