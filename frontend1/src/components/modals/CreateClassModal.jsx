import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';
import { useSelector } from 'react-redux';

const CreateClassModal = ({ open, onOpenChange, onSuccess }) => {
  const { currentUser } = useSelector((state) => state.user);
  const [className, setClassName] = useState('');
  const [gradeLevel, setGradeLevel] = useState('');
  const [section, setSection] = useState('');
  const [capacity, setCapacity] = useState('');
  const [placementMinAvg, setPlacementMinAvg] = useState('');
  const [saving, setSaving] = useState(false);

  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  useEffect(() => {
    if (open) {
      // Reset fields when opening create modal
      setClassName('');
      setGradeLevel('');
      setSection('');
      setCapacity('');
      setPlacementMinAvg('');
    }
  }, [open]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!className) return;

    setSaving(true);
    try {
      const payload = {
        class_name: className,
        grade_level: gradeLevel === '' ? undefined : parseInt(gradeLevel, 10),
        section: section || undefined,
        capacity: capacity === '' ? undefined : parseInt(capacity, 10),
        placement_min_average: placementMinAvg === '' ? undefined : parseFloat(placementMinAvg),
      };

      await axios.post(`${API_URL}/classes`, payload, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      toast.success('Class created successfully');
      onOpenChange(false);
      onSuccess && onSuccess();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to create class');
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
          <DialogTitle>Add Class</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="class_name_new">Class Name</Label>
            <Input id="class_name_new" value={className} onChange={(e) => setClassName(e.target.value)} required />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-2">
              <Label htmlFor="grade_level_new">Grade Level</Label>
              <Input id="grade_level_new" type="number" min="0" value={gradeLevel} onChange={(e) => setGradeLevel(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="section_new">Section</Label>
              <Input id="section_new" value={section} onChange={(e) => setSection(e.target.value)} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-2">
              <Label htmlFor="capacity_new">Capacity (optional)</Label>
              <Input id="capacity_new" type="number" min="0" value={capacity} onChange={(e) => setCapacity(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="placement_new">Min Average for Placement</Label>
              <Input id="placement_new" type="number" step="0.01" min="0" max="100" value={placementMinAvg} onChange={(e) => setPlacementMinAvg(e.target.value)} />
            </div>
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={handleClose} disabled={saving}>Cancel</Button>
            <Button type="submit" disabled={saving}>{saving ? 'Saving...' : 'Create'}</Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default CreateClassModal;

