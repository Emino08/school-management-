import { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';
import { useSelector } from 'react-redux';

const EditStudentModal = ({ open, onOpenChange, student, onSuccess }) => {
  const { currentUser } = useSelector((state) => state.user);
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [password, setPassword] = useState('');
  const [saving, setSaving] = useState(false);

  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  useEffect(() => {
    if (open && student) {
      const fullName = (student.name || '').trim().split(/\s+/);
      const derivedFirst = student.first_name || fullName.shift() || '';
      const derivedLast = student.last_name || fullName.join(' ');
      setFirstName(derivedFirst);
      setLastName(derivedLast);
      setEmail(student.email || '');
      setPhone(student.phone || '');
      setAddress(student.address || '');
      setPassword('');
    }
  }, [open, student]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!student?.id) return;
    setSaving(true);
    try {
      const payload = {
        first_name: firstName,
        last_name: lastName,
        name: `${firstName} ${lastName}`.trim(),
        email,
        phone,
        address,
      };
      if (password && password.trim() !== '') {
        payload.password = password;
      }
      await axios.put(`${API_URL}/students/${student.id}`, payload, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      toast.success('Student updated successfully');
      onOpenChange(false);
      onSuccess && onSuccess();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to update student');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Edit Student</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="firstName">First Name</Label>
            <Input id="firstName" value={firstName} onChange={(e) => setFirstName(e.target.value)} required />
          </div>
          <div className="space-y-2">
            <Label htmlFor="lastName">Last Name</Label>
            <Input id="lastName" value={lastName} onChange={(e) => setLastName(e.target.value)} required />
          </div>
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="phone">Phone</Label>
            <Input id="phone" value={phone} onChange={(e) => setPhone(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="address">Address</Label>
            <Input id="address" value={address} onChange={(e) => setAddress(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="password">New Password (optional)</Label>
            <Input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>Cancel</Button>
            <Button type="submit" disabled={saving}>{saving ? 'Saving...' : 'Save'}</Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default EditStudentModal;
