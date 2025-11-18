import { useState, useEffect } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MdPersonAdd, MdSave, MdClose, MdPerson, MdBadge, MdLock, MdSchool, MdEmail, MdPhone } from 'react-icons/md';
import { GraduationCap, User, Mail, Phone } from 'lucide-react';
import { toast } from 'sonner';
import { useDispatch, useSelector } from 'react-redux';
import { registerUser } from '../../redux/userRelated/userHandle';
import { underControl } from '../../redux/userRelated/userSlice';
import { getAllSclasses } from '../../redux/sclassRelated/sclassHandle';

const StudentModal = ({ open, onOpenChange, preSelectedClass, onSuccess }) => {
  const dispatch = useDispatch();
  const { currentUser, status, response } = useSelector((state) => state.user);
  
  const [formData, setFormData] = useState({
    name: '',
    idNumber: '',
    password: '',
    classId: preSelectedClass || '',
    email: '',
    phone: '',
    address: '',
    dateOfBirth: '',
  });
  const [loading, setLoading] = useState(false);
  const [classes, setClasses] = useState([]);
  const [loadingClasses, setLoadingClasses] = useState(false);

  const adminID = currentUser?._id || currentUser?.id;
  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    if (open && adminID) {
      console.log('StudentModal - Fetching classes');
      fetchClasses();
    }
  }, [open, adminID]);

  const fetchClasses = async () => {
    setLoadingClasses(true);
    try {
      const response = await fetch(`${API_URL}/classes`, {
        headers: {
          'Authorization': `Bearer ${currentUser?.token}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      
      if (data.success && data.classes) {
        console.log('StudentModal - Classes loaded:', data.classes.length);
        setClasses(data.classes);
      }
    } catch (error) {
      console.error('StudentModal - Error fetching classes:', error);
      toast.error('Failed to load classes');
    } finally {
      setLoadingClasses(false);
    }
  };

  useEffect(() => {
    if (preSelectedClass) {
      setFormData((prev) => ({ ...prev, classId: preSelectedClass }));
    }
  }, [preSelectedClass]);

  useEffect(() => {
    if (status === 'added') {
      toast.success('Student added successfully!');
      handleClose();
      if (onSuccess) onSuccess();
      dispatch(underControl());
    } else if (status === 'failed') {
      toast.error(response || 'Failed to add student');
      setLoading(false);
      dispatch(underControl());
    } else if (status === 'error') {
      toast.error('Network error occurred');
      setLoading(false);
      dispatch(underControl());
    }
  }, [status, response, dispatch, onSuccess]);

  const handleChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.classId) {
      toast.error('Please select a class');
      return;
    }

    setLoading(true);

    const fields = {
      name: formData.name,
      id_number: formData.idNumber,
      password: formData.password,
      class_id: parseInt(formData.classId, 10),
      adminID,
      role: 'Student',
      attendance: [],
    };

    dispatch(registerUser(fields, 'Student'));
  };

  const handleClose = () => {
    setFormData({
      name: '',
      rollNum: '',
      password: '',
      sclassName: preSelectedClass || '',
      email: '',
      phone: '',
      address: '',
      dateOfBirth: '',
    });
    setLoading(false);
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-2xl font-bold flex items-center gap-3">
            <div className="w-12 h-12 bg-gradient-to-br from-green-500 via-emerald-600 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
              <GraduationCap className="w-6 h-6 text-white" />
            </div>
            <div>
              <div className="text-gray-900">Add New Student</div>
              <p className="text-sm font-normal text-gray-500 mt-0.5">Create a new student account with complete details</p>
            </div>
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-6 mt-4">
          {/* Basic Information Section */}
          <div className="space-y-4">
            <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
              <User className="w-5 h-5 text-green-600" />
              <h3 className="text-lg font-semibold text-gray-900">Basic Information</h3>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdPerson className="w-4 h-4 text-green-600" />
                  Full Name <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="name"
                  placeholder="e.g., John Doe"
                  value={formData.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  required
                  className="h-11 border-gray-300 focus:border-green-500 focus:ring-green-500"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="idNumber" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdBadge className="w-4 h-4 text-green-600" />
                  ID Number <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="idNumber"
                  placeholder="e.g., 2024001"
                  value={formData.idNumber}
                  onChange={(e) => handleChange('idNumber', e.target.value)}
                  required
                  className="h-11 border-gray-300 focus:border-green-500 focus:ring-green-500 font-mono"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="dateOfBirth" className="text-sm font-semibold">
                  Date of Birth
                </Label>
                <Input
                  id="dateOfBirth"
                  type="date"
                  value={formData.dateOfBirth}
                  onChange={(e) => handleChange('dateOfBirth', e.target.value)}
                  className="h-11 border-gray-300 focus:border-green-500 focus:ring-green-500"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="class" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdSchool className="w-4 h-4 text-green-600" />
                  Class <span className="text-red-500">*</span>
                </Label>
                <Select
                  value={formData.classId}
                  onValueChange={(value) => handleChange('classId', value)}
                  disabled={!!preSelectedClass || loadingClasses}
                >
                  <SelectTrigger className="h-11 border-gray-300 focus:border-green-500 focus:ring-green-500">
                    <SelectValue placeholder={loadingClasses ? "Loading classes..." : "Select a class"} />
                  </SelectTrigger>
                  <SelectContent>
                    {loadingClasses ? (
                      <SelectItem value="loading" disabled>
                        Loading classes...
                      </SelectItem>
                    ) : classes && classes.length > 0 ? (
                      classes.map((classItem) => (
                        <SelectItem
                          key={classItem._id || classItem.id}
                          value={(classItem._id || classItem.id)?.toString()}
                        >
                          {classItem.sclassName || classItem.class_name}
                        </SelectItem>
                      ))
                    ) : (
                      <SelectItem value="no-class" disabled>
                        No classes available
                      </SelectItem>
                    )}
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          {/* Contact Information Section */}
          <div className="space-y-4">
            <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
              <Mail className="w-5 h-5 text-green-600" />
              <h3 className="text-lg font-semibold text-gray-900">Contact Information</h3>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="email" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdEmail className="w-4 h-4 text-green-600" />
                  Email Address
                </Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="e.g., john.doe@example.com"
                  value={formData.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  className="h-11 border-gray-300 focus:border-green-500 focus:ring-green-500"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="phone" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdPhone className="w-4 h-4 text-green-600" />
                  Phone Number
                </Label>
                <Input
                  id="phone"
                  type="tel"
                  placeholder="e.g., +1234567890"
                  value={formData.phone}
                  onChange={(e) => handleChange('phone', e.target.value)}
                  className="h-11 border-gray-300 focus:border-green-500 focus:ring-green-500"
                />
              </div>

              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="address" className="text-sm font-semibold">
                  Address
                </Label>
                <Input
                  id="address"
                  placeholder="e.g., 123 Main Street, City, Country"
                  value={formData.address}
                  onChange={(e) => handleChange('address', e.target.value)}
                  className="h-11 border-gray-300 focus:border-green-500 focus:ring-green-500"
                />
              </div>
            </div>
          </div>

          {/* Security Section */}
          <div className="space-y-4">
            <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
              <MdLock className="w-5 h-5 text-green-600" />
              <h3 className="text-lg font-semibold text-gray-900">Security</h3>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="password" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdLock className="w-4 h-4 text-green-600" />
                  Password <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="password"
                  type="password"
                  placeholder="Create a secure password"
                  value={formData.password}
                  onChange={(e) => handleChange('password', e.target.value)}
                  required
                  className="h-11 border-gray-300 focus:border-green-500 focus:ring-green-500"
                />
              </div>
            </div>
          </div>

          <div className="bg-gradient-to-r from-blue-50 to-green-50 border border-green-200 rounded-xl p-4">
            <div className="flex items-start gap-3">
              <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <MdLock className="w-4 h-4 text-green-600" />
              </div>
              <div>
                <p className="text-sm font-semibold text-green-900 mb-1">Login Credentials</p>
                <p className="text-sm text-green-800">
                  The student will use their <strong>ID number</strong> as username and the <strong>password</strong> you set to log in to the system.
                </p>
              </div>
            </div>
          </div>

          <DialogFooter className="gap-2 pt-4 border-t">
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              disabled={loading}
              className="h-11"
            >
              <MdClose className="w-4 h-4 mr-2" />
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={loading}
              className="h-11 bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 hover:from-green-700 hover:to-teal-700 shadow-lg"
            >
              {loading ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                  Creating Student...
                </>
              ) : (
                <>
                  <MdSave className="w-5 h-5 mr-2" />
                  Create Student Account
                </>
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default StudentModal;
