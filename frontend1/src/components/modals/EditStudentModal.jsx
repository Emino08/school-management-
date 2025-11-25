import { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';
import { useSelector } from 'react-redux';

const EditStudentModal = ({ open, onOpenChange, student, onSuccess, classes = [] }) => {
  const { currentUser } = useSelector((state) => state.user);
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [gender, setGender] = useState('');
  const [dateOfBirth, setDateOfBirth] = useState('');
  const [classId, setClassId] = useState('');
  const [parentName, setParentName] = useState('');
  const [parentPhone, setParentPhone] = useState('');
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
      setGender(student.gender || '');
      setDateOfBirth(student.date_of_birth || '');
      setParentName(student.parent_name || '');
      setParentPhone(student.parent_phone || '');
      setPassword('');
      
      // Handle class ID - check different possible field names
      if (student.class_id) {
        setClassId(String(student.class_id));
      } else if (student.sclassName?._id) {
        setClassId(String(student.sclassName._id));
      } else if (student.sclassName?.id) {
        setClassId(String(student.sclassName.id));
      } else {
        setClassId('');
      }
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
        gender,
        date_of_birth: dateOfBirth,
        parent_name: parentName,
        parent_phone: parentPhone,
      };
      
      // Add class_id if selected
      if (classId) {
        payload.class_id = parseInt(classId);
      }
      
      // Only include password if it's been set
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
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Edit Student</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="firstName">First Name *</Label>
              <Input id="firstName" value={firstName} onChange={(e) => setFirstName(e.target.value)} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="lastName">Last Name *</Label>
              <Input id="lastName" value={lastName} onChange={(e) => setLastName(e.target.value)} required />
            </div>
          </div>
          
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone">Phone</Label>
              <Input id="phone" value={phone} onChange={(e) => setPhone(e.target.value)} />
            </div>
          </div>
          
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="gender">Gender</Label>
              <Select value={gender} onValueChange={setGender}>
                <SelectTrigger>
                  <SelectValue placeholder="Select gender" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Male">Male</SelectItem>
                  <SelectItem value="Female">Female</SelectItem>
                  <SelectItem value="Other">Other</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="dateOfBirth">Date of Birth</Label>
              <Input 
                id="dateOfBirth" 
                type="date" 
                value={dateOfBirth} 
                onChange={(e) => setDateOfBirth(e.target.value)} 
              />
            </div>
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="address">Address</Label>
            <Input id="address" value={address} onChange={(e) => setAddress(e.target.value)} />
          </div>
          
          {classes && classes.length > 0 && (
            <div className="space-y-2">
              <Label htmlFor="class">Class</Label>
              <Select value={classId} onValueChange={setClassId}>
                <SelectTrigger>
                  <SelectValue placeholder="Select class" />
                </SelectTrigger>
                <SelectContent>
                  {classes.map((cls) => (
                    <SelectItem key={cls.id || cls._id} value={String(cls.id || cls._id)}>
                      {cls.class_name || cls.sclassName}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}
          
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="parentName">Parent Name</Label>
              <Input id="parentName" value={parentName} onChange={(e) => setParentName(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="parentPhone">Parent Phone</Label>
              <Input id="parentPhone" value={parentPhone} onChange={(e) => setParentPhone(e.target.value)} />
            </div>
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="password">New Password (leave blank to keep current)</Label>
            <Input 
              id="password" 
              type="password" 
              value={password} 
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Enter new password or leave blank"
            />
          </div>
          
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>
              Cancel
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default EditStudentModal;
