import { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Dialog,
  DialogContent,
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
import { Checkbox } from '@/components/ui/checkbox';
import { MdPerson, MdSave, MdClose, MdEmail, MdLock, MdPhone } from 'react-icons/md';
import { Users } from 'lucide-react';
import { toast } from 'sonner';
import { useSelector } from 'react-redux';
import { MdRotateRight } from 'react-icons/md';

const EditTeacherModal = ({ open, onOpenChange, teacher, onSuccess }) => {
  const { currentUser } = useSelector((state) => state.user);
  
  // Form fields matching AddTeacher pattern
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [phone, setPhone] = useState('');
  const [qualification, setQualification] = useState('');
  const [experienceYears, setExperienceYears] = useState('');
  const [address, setAddress] = useState('');
  
  // Class Master fields
  const [isClassMaster, setIsClassMaster] = useState(false);
  const [classMasterOf, setClassMasterOf] = useState('');
  const [classes, setClasses] = useState([]);
  
  // Exam Officer fields
  const [isExamOfficer, setIsExamOfficer] = useState(false);
  const [canApproveResults, setCanApproveResults] = useState(false);

  // Town Master
  const [isTownMaster, setIsTownMaster] = useState(false);
  
  // Subjects
  const [allSubjects, setAllSubjects] = useState([]);
  const [selectedSubjects, setSelectedSubjects] = useState([]);
  
  const [loader, setLoader] = useState(false);

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  // Load teacher data when modal opens
  useEffect(() => {
    console.log('=== MODAL EFFECT ===');
    console.log('Open:', open);
    console.log('Teacher:', teacher);
    console.log('Current URL in modal:', window.location.href);
    
    if (open && teacher) {
      console.log('Loading teacher data:', teacher);
      
      // Parse subject IDs if they're a string
      let subjectIds = [];
      if (teacher.subject_ids) {
        subjectIds = typeof teacher.subject_ids === 'string' 
          ? teacher.subject_ids.split(',').map(id => id.trim())
          : teacher.subject_ids;
      }
      
      // Set form fields
      setFirstName(teacher.first_name || teacher.name?.split(' ')[0] || '');
      setLastName(teacher.last_name || teacher.name?.split(' ').slice(1).join(' ') || '');
      setEmail(teacher.email || '');
      setPhone(teacher.phone || '');
      setQualification(teacher.qualification || '');
      setExperienceYears(teacher.experience_years || '');
      setAddress(teacher.address || '');
      setPassword(''); // Never pre-fill password
      
      // Set roles
      setIsClassMaster(Boolean(teacher.is_class_master));
      setClassMasterOf(teacher.class_master_of?.toString() || '');
      setIsExamOfficer(Boolean(teacher.is_exam_officer));
      setCanApproveResults(Boolean(teacher.can_approve_results));
      setIsTownMaster(Boolean(teacher.is_town_master));
      
      // Set subjects
      setSelectedSubjects(subjectIds);
      
      fetchClasses();
      fetchSubjects();
      
      console.log('Teacher data loaded successfully');
    }
  }, [open, teacher]);

  const fetchSubjects = async () => {
    try {
      const response = await fetch(`${API_URL}/subjects`, {
        headers: { 
          Authorization: `Bearer ${currentUser?.token}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setAllSubjects(data.subjects || []);
      }
    } catch (error) {
      console.error('Error fetching subjects:', error);
      toast.error("Failed to fetch subjects");
    }
  };

  const fetchClasses = async () => {
    try {
      const response = await fetch(`${API_URL}/classes`, {
        headers: { 
          Authorization: `Bearer ${currentUser?.token}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setClasses(data.classes || []);
      }
    } catch (error) {
      console.error("Error fetching classes:", error);
    }
  };

  const handleSubjectToggle = (subjectId) => {
    setSelectedSubjects(prev => {
      if (prev.includes(subjectId.toString())) {
        return prev.filter(id => id !== subjectId.toString());
      } else {
        return [...prev, subjectId.toString()];
      }
    });
  };

  const submitHandler = async (event) => {
    event.preventDefault();
    
    if (selectedSubjects.length === 0) {
      toast.error('Please select at least one subject');
      return;
    }
    
    setLoader(true);

    const updateData = {
      first_name: firstName,
      last_name: lastName,
      phone,
      qualification,
      experience_years: experienceYears,
      address,
      teachSubjects: selectedSubjects,
      is_class_master: isClassMaster,
      class_master_of: isClassMaster ? classMasterOf : null,
      is_exam_officer: isExamOfficer,
      can_approve_results: canApproveResults,
      is_town_master: isTownMaster,
    };

    // Only include password if it's been changed
    if (password && password.trim() !== '') {
      updateData.password = password;
    }

    try {
      const response = await axios.put(
        `${API_URL}/teachers/${teacher.id || teacher._id}`,
        updateData,
        {
          headers: {
            Authorization: `Bearer ${currentUser?.token}`,
            'Content-Type': 'application/json',
          },
        }
      );

      if (response.data.success) {
        toast.success('Teacher updated successfully!');
        handleClose();
        if (onSuccess) onSuccess();
      } else {
        toast.error(response.data.message || 'Failed to update teacher');
      }
    } catch (error) {
      console.error('Update error:', error);
      toast.error(error.response?.data?.message || 'Failed to update teacher');
    } finally {
      setLoader(false);
    }
  };

  const handleClose = () => {
    // Reset all form fields
    setFirstName('');
    setLastName('');
    setEmail('');
    setPassword('');
    setPhone('');
    setQualification('');
    setExperienceYears('');
    setAddress('');
    setIsClassMaster(false);
    setClassMasterOf('');
    setIsExamOfficer(false);
    setCanApproveResults(false);
    setIsTownMaster(false);
    setSelectedSubjects([]);
    setLoader(false);
    
    // Close the modal via the callback
    if (onOpenChange) {
      onOpenChange(false);
    }
  };

  // Also handle escape key and overlay click through onOpenChange
  const handleOpenChange = (isOpen) => {
    console.log('=== MODAL OPEN CHANGE ===');
    console.log('isOpen:', isOpen);
    console.log('Current URL:', window.location.href);
    
    if (!isOpen) {
      console.log('Modal closing...');
      handleClose();
    } else if (onOpenChange) {
      console.log('Modal opening...');
      onOpenChange(isOpen);
    }
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-2xl font-bold">Edit Teacher</DialogTitle>
        </DialogHeader>

        <form onSubmit={submitHandler} className="space-y-6 mt-4">
          {/* Basic Information */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="firstName">First Name *</Label>
              <Input
                id="firstName"
                type="text"
                placeholder="Enter first name..."
                value={firstName}
                onChange={(event) => setFirstName(event.target.value)}
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="lastName">Last Name *</Label>
              <Input
                id="lastName"
                type="text"
                placeholder="Enter last name..."
                value={lastName}
                onChange={(event) => setLastName(event.target.value)}
                required
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              value={email}
              disabled
              className="bg-gray-100 cursor-not-allowed"
            />
            <p className="text-xs text-gray-500">Email cannot be changed</p>
          </div>

          <div className="space-y-2">
            <Label htmlFor="password">New Password (leave empty to keep current)</Label>
            <Input
              id="password"
              type="password"
              placeholder="Enter new password..."
              value={password}
              onChange={(event) => setPassword(event.target.value)}
            />
          </div>

          {/* Additional Contact Info */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="phone">Phone (Optional)</Label>
              <Input
                id="phone"
                type="tel"
                placeholder="Enter phone number..."
                value={phone}
                onChange={(event) => setPhone(event.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="address">Address (Optional)</Label>
              <Input
                id="address"
                type="text"
                placeholder="Enter address..."
                value={address}
                onChange={(event) => setAddress(event.target.value)}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="qualification">Qualification (Optional)</Label>
              <Input
                id="qualification"
                type="text"
                placeholder="e.g., MSc Education"
                value={qualification}
                onChange={(event) => setQualification(event.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="experience">Experience (Years)</Label>
              <Input
                id="experience"
                type="number"
                placeholder="e.g., 5"
                value={experienceYears}
                onChange={(event) => setExperienceYears(event.target.value)}
              />
            </div>
          </div>

          {/* Subjects Assignment */}
          <div className="space-y-3 p-4 bg-gray-50 rounded-md border border-gray-200">
            <h4 className="font-semibold text-sm text-gray-900">Assign Subjects *</h4>
            <p className="text-xs text-gray-600">Select subjects this teacher will teach ({selectedSubjects.length} selected)</p>
            <div className="max-h-60 overflow-y-auto space-y-2">
              {allSubjects.map((subject) => (
                <div key={subject.id} className="flex items-center space-x-2">
                  <Checkbox
                    id={`subject-${subject.id}`}
                    checked={selectedSubjects.includes(subject.id.toString())}
                    onCheckedChange={() => handleSubjectToggle(subject.id)}
                  />
                  <Label htmlFor={`subject-${subject.id}`} className="cursor-pointer text-sm flex-1">
                    {subject.subject_name} ({classes.find(c => c.id === subject.class_id)?.class_name || 'Unknown Class'})
                  </Label>
                </div>
              ))}
            </div>
            {allSubjects.length === 0 && (
              <p className="text-sm text-gray-500">No subjects available. Please create subjects first.</p>
            )}
          </div>

          {/* Class Master Options */}
          <div className="space-y-3 p-4 bg-green-50 rounded-md border border-green-200">
            <h4 className="font-semibold text-sm text-green-900">Class Master Designation (Optional)</h4>
            <div className="flex items-center space-x-2">
              <Checkbox
                id="isClassMaster"
                checked={isClassMaster}
                onCheckedChange={(value) => {
                  setIsClassMaster(value);
                  if (!value) setClassMasterOf("");
                }}
              />
              <Label htmlFor="isClassMaster" className="cursor-pointer text-sm">
                Assign as Class Master
              </Label>
            </div>

            {isClassMaster && (
              <div className="space-y-2 ml-6">
                <Label>Select Class</Label>
                <Select value={classMasterOf} onValueChange={setClassMasterOf} required={isClassMaster}>
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Select a class" />
                  </SelectTrigger>
                  <SelectContent>
                    {classes.map((cls) => (
                      <SelectItem key={cls.id} value={cls.id.toString()}>
                        {cls.class_name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}

            <p className="text-xs text-green-700">
              Class masters can mark attendance for their assigned class.
            </p>
          </div>

          {/* Exam Officer Options */}
          <div className="space-y-3 p-4 bg-blue-50 rounded-md border border-blue-200">
            <h4 className="font-semibold text-sm text-blue-900">Exam Officer Designation (Optional)</h4>
            <div className="flex items-center space-x-2">
              <Checkbox
                id="isExamOfficer"
                checked={isExamOfficer}
                onCheckedChange={(value) => {
                  setIsExamOfficer(value);
                  if (!value) setCanApproveResults(false);
                }}
              />
              <Label htmlFor="isExamOfficer" className="cursor-pointer text-sm">
                Designate as Exam Officer
              </Label>
            </div>

            {isExamOfficer && (
              <div className="flex items-center space-x-2 ml-6">
                <Checkbox
                  id="canApproveResults"
                  checked={canApproveResults}
                  onCheckedChange={(value) => setCanApproveResults(value)}
                />
                <Label htmlFor="canApproveResults" className="cursor-pointer text-sm">
                  Can approve exam results
                </Label>
              </div>
            )}

            <p className="text-xs text-blue-700">
              Exam officers review and approve grades uploaded by teachers.
            </p>
          </div>

          {/* Town Master Options */}
          <div className="space-y-3 p-4 bg-indigo-50 rounded-md border border-indigo-200">
            <h4 className="font-semibold text-sm text-indigo-900">Town Master Designation (Optional)</h4>
            <div className="flex items-center space-x-2">
              <Checkbox
                id="isTownMaster"
                checked={isTownMaster}
                onCheckedChange={(value) => setIsTownMaster(Boolean(value))}
              />
              <Label htmlFor="isTownMaster" className="cursor-pointer text-sm">
                Mark as Town Master
              </Label>
            </div>
            <p className="text-xs text-indigo-700">
              Town masters can add and manage students in their towns/blocks.
            </p>
          </div>

          <DialogFooter className="gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              disabled={loader}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={loader}>
              {loader ? (
                <MdRotateRight className="w-5 h-5 animate-spin" />
              ) : (
                "Update Teacher"
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default EditTeacherModal;
