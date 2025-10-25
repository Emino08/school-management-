import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import axios from 'axios';
import { toast } from 'sonner';

const RecordPayment = ({ onPaymentRecorded }) => {
  const [students, setStudents] = useState([]);
  const [feeStructures, setFeeStructures] = useState([]);
  const [loading, setLoading] = useState(false);

  const [formData, setFormData] = useState({
    student_id: '',
    fee_structure_id: '',
    amount_paid: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'Cash',
    reference_number: '',
    term: '1',
    notes: ''
  });

  useEffect(() => {
    fetchStudents();
    fetchFeeStructures();
  }, []);

  const fetchStudents = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/students`,
        { headers: { Authorization: `Bearer ${token}` } }
      );

      if (response.data.success) {
        setStudents(response.data.students);
      }
    } catch (error) {
      console.error('Error fetching students:', error);
    }
  };

  const fetchFeeStructures = async () => {
    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/fee-structures`,
        {
          params: { academic_year_id: currentAcademicYear?.id },
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      if (response.data.success) {
        setFeeStructures(response.data.feeStructures);
      }
    } catch (error) {
      console.error('Error fetching fee structures:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));
      const adminData = JSON.parse(localStorage.getItem('user'));

      const payload = {
        ...formData,
        academic_year_id: currentAcademicYear.id,
        recorded_by: adminData.id,
        status: 'Completed'
      };

      const response = await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/payments`,
        payload,
        { headers: { Authorization: `Bearer ${token}` } }
      );

      if (response.data.success) {
        toast.success(`Payment recorded successfully! Receipt: ${response.data.receipt_number}`);

        // Reset form
        setFormData({
          student_id: '',
          fee_structure_id: '',
          amount_paid: '',
          payment_date: new Date().toISOString().split('T')[0],
          payment_method: 'Cash',
          reference_number: '',
          term: '1',
          notes: ''
        });

        if (onPaymentRecorded) {
          onPaymentRecorded();
        }
      }
    } catch (error) {
      console.error('Error recording payment:', error);
      toast.error(error.response?.data?.message || 'Failed to record payment');
    } finally {
      setLoading(false);
    }
  };

  const selectedFee = feeStructures.find(f => f.id === parseInt(formData.fee_structure_id));

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="student_id">Student *</Label>
          <Select
            value={formData.student_id}
            onValueChange={(value) => setFormData({ ...formData, student_id: value })}
            required
          >
            <SelectTrigger>
              <SelectValue placeholder="Select student" />
            </SelectTrigger>
            <SelectContent>
              {students.map((student) => (
                <SelectItem key={student.id} value={student.id.toString()}>
                  {student.first_name} {student.last_name} ({student.admission_no})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="fee_structure_id">Fee Type *</Label>
          <Select
            value={formData.fee_structure_id}
            onValueChange={(value) => {
              const fee = feeStructures.find(f => f.id === parseInt(value));
              setFormData({
                ...formData,
                fee_structure_id: value,
                amount_paid: fee?.amount || ''
              });
            }}
            required
          >
            <SelectTrigger>
              <SelectValue placeholder="Select fee type" />
            </SelectTrigger>
            <SelectContent>
              {feeStructures.map((fee) => (
                <SelectItem key={fee.id} value={fee.id.toString()}>
                  {fee.fee_name} - ${parseFloat(fee.amount).toFixed(2)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="amount_paid">Amount Paid ($) *</Label>
          <Input
            id="amount_paid"
            type="number"
            step="0.01"
            value={formData.amount_paid}
            onChange={(e) => setFormData({ ...formData, amount_paid: e.target.value })}
            placeholder="0.00"
            required
          />
          {selectedFee && (
            <p className="text-sm text-gray-600">
              Fee amount: ${parseFloat(selectedFee.amount).toFixed(2)}
            </p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="payment_date">Payment Date *</Label>
          <Input
            id="payment_date"
            type="date"
            value={formData.payment_date}
            onChange={(e) => setFormData({ ...formData, payment_date: e.target.value })}
            required
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="payment_method">Payment Method *</Label>
          <Select
            value={formData.payment_method}
            onValueChange={(value) => setFormData({ ...formData, payment_method: value })}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Cash">Cash</SelectItem>
              <SelectItem value="Bank Transfer">Bank Transfer</SelectItem>
              <SelectItem value="Card">Card</SelectItem>
              <SelectItem value="Mobile Money">Mobile Money</SelectItem>
              <SelectItem value="Cheque">Cheque</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="reference_number">Reference Number</Label>
          <Input
            id="reference_number"
            value={formData.reference_number}
            onChange={(e) => setFormData({ ...formData, reference_number: e.target.value })}
            placeholder="Transaction/Check number"
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="term">Term *</Label>
          <Select
            value={formData.term}
            onValueChange={(value) => setFormData({ ...formData, term: value })}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="1">1st Term</SelectItem>
              <SelectItem value="2">2nd Term</SelectItem>
              <SelectItem value="3">3rd Term</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2 md:col-span-2">
          <Label htmlFor="notes">Notes</Label>
          <Input
            id="notes"
            value={formData.notes}
            onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
            placeholder="Additional notes (optional)"
          />
        </div>
      </div>

      <Button type="submit" disabled={loading} className="w-full">
        {loading ? 'Recording Payment...' : 'Record Payment'}
      </Button>
    </form>
  );
};

export default RecordPayment;
