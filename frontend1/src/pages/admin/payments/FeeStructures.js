import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { FiPlus, FiEdit2, FiTrash2, FiDollarSign, FiUpload } from 'react-icons/fi';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';

const FeeStructures = () => {
  const [feeStructures, setFeeStructures] = useState([]);
  const [classes, setClasses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingFee, setEditingFee] = useState(null);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [feeToDelete, setFeeToDelete] = useState(null);
  const [currentYear, setCurrentYear] = useState(null);
  const [lockTuitionPreset, setLockTuitionPreset] = useState(false);

  const [formData, setFormData] = useState({
    fee_name: '',
    class_id: 'all',
    amount: '',
    frequency: 'Termly',
    description: '',
    is_mandatory: true
  });

  useEffect(() => {
    fetchFeeStructures();
    fetchClasses();
    const cy = JSON.parse(localStorage.getItem('currentAcademicYear'));
    if (cy) setCurrentYear(cy);
  }, []);

  const fetchFeeStructures = async () => {
    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/fee-structures`,
        {
          params: { academic_year_id: currentAcademicYear?.id }
        }
      );

      if (response.data.success) {
        setFeeStructures(response.data.feeStructures);
      }
      setLoading(false);
    } catch (error) {
      console.error('Error fetching fee structures:', error);
      toast.error('Failed to load fee structures');
      setLoading(false);
    }
  };

  const fetchClasses = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/classes`
      );

      if (response.data.success) {
        setClasses(response.data.classes);
      }
    } catch (error) {
      console.error('Error fetching classes:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      const payload = {
        ...formData,
        class_id: formData.class_id && formData.class_id !== 'all' ? formData.class_id : null,
        academic_year_id: currentAcademicYear.id
      };

      if (editingFee) {
        // Update
        await axios.put(
          `${import.meta.env.VITE_API_BASE_URL}/fee-structures/${editingFee.id}`,
          payload
        );
        toast.success('Fee structure updated successfully');
      } else {
        // Create
        await axios.post(
          `${import.meta.env.VITE_API_BASE_URL}/fee-structures`,
          payload
        );
        toast.success('Fee structure created successfully');
      }

      resetForm();
      fetchFeeStructures();
    } catch (error) {
      console.error('Error saving fee structure:', error);
      toast.error(error.response?.data?.message || 'Failed to save fee structure');
    }
  };

  const handleEdit = (fee) => {
    setEditingFee(fee);
    setFormData({
      fee_name: fee.fee_name,
      class_id: fee.class_id ? fee.class_id.toString() : 'all',
      amount: fee.amount,
      frequency: fee.frequency,
      description: fee.description || '',
      is_mandatory: fee.is_mandatory
    });
    setShowForm(true);
  };

  const handleDelete = async () => {
    try {
      const token = localStorage.getItem('token');
      await axios.delete(
        `${import.meta.env.VITE_API_BASE_URL}/fee-structures/${feeToDelete.id}`
      );

      toast.success('Fee structure deleted successfully');
      setDeleteDialogOpen(false);
      setFeeToDelete(null);
      fetchFeeStructures();
    } catch (error) {
      console.error('Error deleting fee structure:', error);
      toast.error('Failed to delete fee structure');
    }
  };

  const importFromAcademicYear = async () => {
    try {
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));
      if (!currentAcademicYear?.id) {
        toast.error('Please select an academic year first');
        return;
      }
      const res = await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/fee-structures/import-from-year`,
        { academic_year_id: currentAcademicYear.id }
      );
      if (res.data.success) {
        const created = res.data.created?.length || 0;
        const updated = res.data.updated?.length || 0;
        toast.success(`Imported ${created} and updated ${updated} fee structures`);
        fetchFeeStructures();
      } else {
        toast.error(res.data.message || 'Failed to import defaults');
      }
    } catch (error) {
      console.error('Error importing fee structures:', error);
      toast.error(error.response?.data?.message || 'Failed to import defaults');
    }
  };

  const resetForm = () => {
    setFormData({
      fee_name: '',
      class_id: 'all',
      amount: '',
      frequency: 'Termly',
      description: '',
      is_mandatory: true
    });
    setEditingFee(null);
    setShowForm(false);
  };

  const applyTermFee = (termNumber) => {
    if (!currentYear) return;
    const map = {
      1: currentYear.term_1_fee,
      2: currentYear.term_2_fee,
      3: currentYear.term_3_fee,
    };
    const amount = map[termNumber];
    if (amount !== undefined && amount !== null && amount !== '') {
      setFormData((prev) => ({
        ...prev,
        fee_name: `Tuition Fee - Term ${termNumber}`,
        amount: amount,
        frequency: 'Termly',
        class_id: 'all',
        is_mandatory: true,
      }));
      setLockTuitionPreset(true);
    }
  };

  const handleToggleForm = () => {
    const next = !showForm;
    setShowForm(next);
    if (next && !editingFee && currentYear) {
      // Prefill with Term 1 fee by default when opening create form
      if (currentYear.term_1_fee) {
        setFormData((prev) => ({
          ...prev,
          fee_name: prev.fee_name || 'Tuition Fee - Term 1',
          amount: prev.amount || currentYear.term_1_fee,
          frequency: prev.frequency || 'Termly',
          class_id: 'all',
          is_mandatory: true,
        }));
        setLockTuitionPreset(true);
      }
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold">Fee Structures</h2>
        <div className="flex gap-2">
          <Button variant="outline" onClick={importFromAcademicYear}>
            <FiUpload className="mr-2 h-4 w-4" />
            Import From Academic Year
          </Button>
          <Button onClick={handleToggleForm}>
            <FiPlus className="mr-2 h-4 w-4" />
            {showForm ? 'Cancel' : 'Add Fee Structure'}
          </Button>
        </div>
      </div>

      {showForm && (
        <Card>
          <CardHeader>
            <CardTitle>{editingFee ? 'Edit Fee Structure' : 'Add Fee Structure'}</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="fee_name">Fee Name *</Label>
                  <Input
                    id="fee_name"
                    value={formData.fee_name}
                    onChange={(e) => setFormData({ ...formData, fee_name: e.target.value })}
                    placeholder="e.g., Tuition Fee"
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="amount">Amount ($) *</Label>
                  <Input
                    id="amount"
                    type="number"
                    step="0.01"
                    value={formData.amount}
                    onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                    placeholder="0.00"
                    required
                  />
                  {currentYear && (
                    <div className="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                      <span>Quick pick:</span>
                      {currentYear.term_1_fee ? (
                        <Button type="button" variant="outline" size="sm" className="h-7 px-2"
                          onClick={() => applyTermFee(1)}>
                          Term 1 (${parseFloat(currentYear.term_1_fee).toLocaleString()})
                        </Button>
                      ) : null}
                      {currentYear.term_2_fee ? (
                        <Button type="button" variant="outline" size="sm" className="h-7 px-2"
                          onClick={() => applyTermFee(2)}>
                          Term 2 (${parseFloat(currentYear.term_2_fee).toLocaleString()})
                        </Button>
                      ) : null}
                      {currentYear.number_of_terms === 3 && currentYear.term_3_fee ? (
                        <Button type="button" variant="outline" size="sm" className="h-7 px-2"
                          onClick={() => applyTermFee(3)}>
                          Term 3 (${parseFloat(currentYear.term_3_fee).toLocaleString()})
                        </Button>
                      ) : null}
                    </div>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="class_id">Class (Optional)</Label>
                  <Select
                    value={formData.class_id}
                    onValueChange={(value) => setFormData({ ...formData, class_id: value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="All Classes" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Classes</SelectItem>
                      {classes.map((cls) => (
                        <SelectItem key={cls.id} value={cls.id.toString()}>
                          {cls.class_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="frequency">Frequency *</Label>
                  <Select
                    value={formData.frequency}
                    onValueChange={(value) => setFormData({ ...formData, frequency: value })}
                    disabled={lockTuitionPreset}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="One-time">One-time</SelectItem>
                      <SelectItem value="Monthly">Monthly</SelectItem>
                      <SelectItem value="Termly">Termly</SelectItem>
                      <SelectItem value="Yearly">Yearly</SelectItem>
                    </SelectContent>
                  </Select>
                  {lockTuitionPreset && (
                    <p className="text-xs text-gray-500">Frequency locked to Termly for tuition preset.</p>
                  )}
                </div>

                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="description">Description</Label>
                  <Input
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder="Optional description"
                  />
                </div>

                <div className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    id="is_mandatory"
                    checked={formData.is_mandatory}
                    onChange={(e) => setFormData({ ...formData, is_mandatory: e.target.checked })}
                    className="h-4 w-4"
                  />
                  <Label htmlFor="is_mandatory">Mandatory Fee</Label>
                </div>
              </div>

              <div className="flex gap-2">
                <Button type="submit">
                  {editingFee ? 'Update' : 'Create'} Fee Structure
                </Button>
                <Button type="button" variant="outline" onClick={resetForm}>
                  Cancel
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {/* Fee Structures List */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {loading ? (
          <p>Loading...</p>
        ) : feeStructures.length === 0 ? (
          <p className="text-gray-500 col-span-full text-center py-8">
            No fee structures found. Create one to get started.
          </p>
        ) : (
          feeStructures.map((fee) => (
            <Card key={fee.id}>
              <CardHeader>
                <div className="flex justify-between items-start">
                  <div>
                    <CardTitle className="text-lg">{fee.fee_name}</CardTitle>
                    <p className="text-sm text-gray-600">{fee.class_name || 'All Classes'}</p>
                  </div>
                  <Badge variant={fee.is_mandatory ? 'default' : 'secondary'}>
                    {fee.is_mandatory ? 'Mandatory' : 'Optional'}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600">Amount:</span>
                    <span className="text-2xl font-bold text-green-600">
                      ${parseFloat(fee.amount).toFixed(2)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600">Frequency:</span>
                    <span className="font-medium">{fee.frequency}</span>
                  </div>
                  {fee.description && (
                    <p className="text-sm text-gray-600 mt-2">{fee.description}</p>
                  )}
                  <div className="flex gap-2 mt-4">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleEdit(fee)}
                      className="flex-1"
                    >
                      <FiEdit2 className="mr-2 h-4 w-4" />
                      Edit
                    </Button>
                    <Button
                      variant="destructive"
                      size="sm"
                      onClick={() => {
                        setFeeToDelete(fee);
                        setDeleteDialogOpen(true);
                      }}
                      className="flex-1"
                    >
                      <FiTrash2 className="mr-2 h-4 w-4" />
                      Delete
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
            <AlertDialogDescription>
              This will permanently delete the fee structure "{feeToDelete?.fee_name}".
              This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setFeeToDelete(null)}>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-red-600">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default FeeStructures;
