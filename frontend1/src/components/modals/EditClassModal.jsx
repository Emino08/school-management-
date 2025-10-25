import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';
import { useSelector } from 'react-redux';

const EditClassModal = ({ open, onOpenChange, classData, onSuccess }) => {
  const { currentUser } = useSelector((state) => state.user);
  const [className, setClassName] = useState('');
  const [gradeLevel, setGradeLevel] = useState('');
  const [section, setSection] = useState('');
  const [saving, setSaving] = useState(false);

  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  useEffect(() => {
    if (open && classData) {
      setClassName(classData.class_name || '');
      setGradeLevel(classData.grade_level?.toString() || '');
      setSection(classData.section || '');
    }
  }, [open, classData]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!classData?.id) return;

    setSaving(true);
    try {
      const payload = {
        class_name: className,
        grade_level: gradeLevel === '' ? undefined : parseInt(gradeLevel, 10),
        section: section || undefined,
      };

      await axios.put(`${API_URL}/classes/${classData.id}`, payload, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      toast.success('Class updated successfully');
      onOpenChange(false);
      onSuccess && onSuccess();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to update class');
    } finally {
      setSaving(false);
    }
  };

  const handleClose = () => {
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Edit Class</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="class_name">Class Name</Label>
            <Input id="class_name" value={className} onChange={(e) => setClassName(e.target.value)} required />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-2">
              <Label htmlFor="grade_level">Grade Level</Label>
              <Input id="grade_level" type="number" min="0" value={gradeLevel} onChange={(e) => setGradeLevel(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="section">Section</Label>
              <Input id="section" value={section} onChange={(e) => setSection(e.target.value)} />
            </div>
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={handleClose} disabled={saving}>Cancel</Button>
            <Button type="submit" disabled={saving}>{saving ? 'Saving...' : 'Save'}</Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default EditClassModal;

