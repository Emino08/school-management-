import { useState, useEffect, useMemo } from 'react';
import axios from 'axios';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { MdPerson, MdSave, MdClose, MdEmail, MdLock, MdSchool, MdPhone, MdBook } from 'react-icons/md';
import { Users, BookOpen, Award, Search } from 'lucide-react';
import { toast } from 'sonner';
import { useDispatch, useSelector } from 'react-redux';
import { registerUser } from '../../redux/userRelated/userHandle';
import { underControl } from '../../redux/userRelated/userSlice';
import { getSubjectList } from '../../redux/sclassRelated/sclassHandle';

const TeacherModal = ({ open, onOpenChange, preSelectedClass, onSuccess, adminID: propAdminID }) => {
  const dispatch = useDispatch();
  const { currentUser, status, response } = useSelector((state) => state.user);
  
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    phone: '',
    teachSclass: '',
    teachSubjects: [],
    isClassMaster: false,
    isExamOfficer: false,
  });
  const [loading, setLoading] = useState(false);
  const [classes, setClasses] = useState([]);
  const [allSubjects, setAllSubjects] = useState([]);
  const [loadingClasses, setLoadingClasses] = useState(false);
  const [loadingSubjects, setLoadingSubjects] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  // Get adminID and API_URL
  const adminID = propAdminID || currentUser?._id || currentUser?.id;
  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  // Fetch subjects using EXACT same method as SubjectManagement
  useEffect(() => {
    if (open) {
      fetchSubjects();
      fetchClasses();
    }
  }, [open]);

  const fetchSubjects = async () => {
    console.log('🔄 fetchSubjects called - using SubjectManagement method');
    setLoadingSubjects(true);
    try {
      const response = await axios.get(`${API_URL}/subjects`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      console.log('📦 Response from /subjects:', response.data);

      if (response.data.success) {
        const subjectData = response.data.subjects || [];
        console.log(`✅ SUCCESS: Received ${subjectData.length} subjects`);
        
        // Map to the format expected by the component
        const mappedSubjects = subjectData.map(subject => ({
          id: subject.id,
          _id: subject.id,
          subName: subject.subject_name,
          subCode: subject.subject_code,
          sclassName: subject.class_id,
          sessions: subject.sessions,
          description: subject.description,
        }));
        
        console.log('✅ Mapped subjects:', mappedSubjects);
        setAllSubjects(mappedSubjects);
      } else {
        console.error('❌ Response not successful:', response.data);
        setAllSubjects([]);
      }
    } catch (error) {
      console.error('❌ Error fetching subjects:', error);
      toast.error("Failed to fetch subjects");
      setAllSubjects([]);
    } finally {
      setLoadingSubjects(false);
    }
  };

  const fetchClasses = async () => {
    console.log('🔄 fetchClasses called');
    setLoadingClasses(true);
    try {
      const response = await axios.get(`${API_URL}/classes`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        console.log(`✅ Classes loaded: ${response.data.classes?.length || 0}`);
        setClasses(response.data.classes || []);
      }
    } catch (error) {
      console.error('❌ Error fetching classes:', error);
    } finally {
      setLoadingClasses(false);
    }
  };

  useEffect(() => {
    if (formData.teachSclass) {
      dispatch(getSubjectList(formData.teachSclass, 'ClassSubjects'));
    }
  }, [formData.teachSclass, dispatch]);

  useEffect(() => {
    if (preSelectedClass) {
      setFormData((prev) => ({ ...prev, teachSclass: preSelectedClass }));
    }
  }, [preSelectedClass]);

  useEffect(() => {
    if (status === 'added') {
      toast.success('Teacher added successfully!');
      handleClose();
      if (onSuccess) onSuccess();
      dispatch(underControl());
    } else if (status === 'failed') {
      toast.error(response || 'Failed to add teacher');
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

    if (!formData.teachSubjects || formData.teachSubjects.length === 0) {
      toast.error('Please select at least one subject');
      return;
    }

    setLoading(true);

    const fields = {
      name: formData.name,
      email: formData.email,
      password: formData.password,
      phone: formData.phone,
      role: 'Teacher',
      teachSubjects: formData.teachSubjects, // Array of subject IDs
      teachSclass: formData.teachSclass,
      isClassMaster: formData.isClassMaster,
      isExamOfficer: formData.isExamOfficer,
      adminID,
    };

    console.log('TeacherModal - Submitting teacher with data:', fields);

    dispatch(registerUser(fields, 'Teacher'));
  };

  const handleClose = () => {
    setFormData({
      name: '',
      email: '',
      password: '',
      phone: '',
      teachSclass: '',
      teachSubjects: [],
      isClassMaster: false,
      isExamOfficer: false,
    });
    setLoading(false);
    onOpenChange(false);
  };

  const handleSubjectToggle = (subjectId, nextChecked) => {
    setFormData((prev) => {
      const currentSubjects = prev.teachSubjects || [];
      const isSelected = currentSubjects.includes(subjectId);
      const shouldSelect =
        typeof nextChecked === 'boolean' ? nextChecked : !isSelected;

      const updatedSubjects = shouldSelect
        ? isSelected
          ? currentSubjects
          : [...currentSubjects, subjectId]
        : currentSubjects.filter((id) => id !== subjectId);

      const updates = { teachSubjects: updatedSubjects };

      // Auto-assign class if not set (use first selected subject's class)
      if (!prev.teachSclass && shouldSelect) {
        const selectedSubject = allSubjects?.find(
          (s) => (s._id || s.id)?.toString() === subjectId
        );
        if (selectedSubject?.sclassName) {
          updates.teachSclass = selectedSubject.sclassName.toString();
        }
      }

      return { ...prev, ...updates };
    });
  };

  // Filter subjects based on search query
  const filteredSubjects = useMemo(() => {
    console.log('TeacherModal - Filtering subjects, total count:', allSubjects.length);
    console.log('TeacherModal - Search query:', searchQuery);
    
    if (!allSubjects || allSubjects.length === 0) {
      console.log('TeacherModal - No subjects to filter');
      return [];
    }
    
    if (!searchQuery) {
      console.log('TeacherModal - No search query, returning all subjects');
      return allSubjects;
    }
    
    const query = searchQuery.toLowerCase();
    const filtered = allSubjects.filter(subject => {
      const name = (subject.subName || subject.subject_name || '').toLowerCase();
      const code = (subject.subCode || subject.subject_code || '').toLowerCase();
      return name.includes(query) || code.includes(query);
    });
    
    console.log('TeacherModal - Filtered subjects count:', filtered.length);
    return filtered;
  }, [allSubjects, searchQuery]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-2xl font-bold flex items-center gap-3">
            <div className="w-12 h-12 bg-gradient-to-br from-orange-500 via-red-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
              <Users className="w-6 h-6 text-white" />
            </div>
            <div>
              <div className="text-gray-900">Add New Teacher</div>
              <p className="text-sm font-normal text-gray-500 mt-0.5">Create a teacher account and assign subjects</p>
            </div>
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-6 mt-4">
          {/* Basic Information Section */}
          <div className="space-y-4">
            <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
              <MdPerson className="w-5 h-5 text-orange-600" />
              <h3 className="text-lg font-semibold text-gray-900">Basic Information</h3>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="name" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdPerson className="w-4 h-4 text-orange-600" />
                  Full Name <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="name"
                  placeholder="e.g., Jane Smith"
                  value={formData.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  required
                  className="h-11 border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="email" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdEmail className="w-4 h-4 text-orange-600" />
                  Email Address <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="e.g., jane.smith@school.com"
                  value={formData.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  required
                  className="h-11 border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="phone" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdPhone className="w-4 h-4 text-orange-600" />
                  Phone Number
                </Label>
                <Input
                  id="phone"
                  type="tel"
                  placeholder="e.g., +1234567890"
                  value={formData.phone}
                  onChange={(e) => handleChange('phone', e.target.value)}
                  className="h-11 border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password" className="text-sm font-semibold flex items-center gap-1.5">
                  <MdLock className="w-4 h-4 text-orange-600" />
                  Password <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="password"
                  type="password"
                  placeholder="Create a secure password"
                  value={formData.password}
                  onChange={(e) => handleChange('password', e.target.value)}
                  required
                  className="h-11 border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                />
              </div>
            </div>
          </div>

          {/* Teaching Assignment Section */}
          <div className="space-y-4">
            <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
              <BookOpen className="w-5 h-5 text-orange-600" />
              <h3 className="text-lg font-semibold text-gray-900">Teaching Assignment</h3>
            </div>
            
            <div className="grid grid-cols-1 gap-4">
              <div className="space-y-2">
                <Label className="text-sm font-semibold flex items-center gap-1.5">
                  <MdBook className="w-4 h-4 text-orange-600" />
                  Select Subjects <span className="text-red-500">*</span>
                  <span className="text-xs text-gray-500 font-normal ml-2">
                    ({formData.teachSubjects?.length || 0} selected)
                  </span>
                </Label>

                {/* Search Input */}
                {!loadingSubjects && allSubjects.length > 0 && (
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input
                      type="text"
                      placeholder="Search subjects by name or code..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="pl-10 h-11 border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                    />
                    {searchQuery && (
                      <button
                        type="button"
                        onClick={() => setSearchQuery('')}
                        className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        <MdClose className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                )}
                
                {loadingSubjects ? (
                  <div className="p-4 border-2 border-dashed border-gray-300 rounded-xl text-center">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-600 mx-auto mb-2"></div>
                    <p className="text-sm text-gray-500">Loading subjects...</p>
                  </div>
                ) : allSubjects.length === 0 ? (
                  <div className="p-4 border-2 border-dashed border-gray-300 rounded-xl text-center">
                    <MdBook className="w-12 h-12 text-gray-400 mx-auto mb-2" />
                    <p className="text-sm text-gray-500">No subjects available</p>
                    <p className="text-xs text-gray-400 mt-1">Please create subjects first</p>
                    <p className="text-xs text-red-400 mt-2 font-mono">
                      Debug: Total={allSubjects.length}, Filtered={filteredSubjects.length}, Loading={loadingSubjects.toString()}
                    </p>
                  </div>
                ) : filteredSubjects.length > 0 ? (
                  <>
                    <p className="text-xs text-gray-500 mb-2">
                      Found {filteredSubjects.length} subject{filteredSubjects.length !== 1 ? 's' : ''}
                    </p>
                    <div className="border-2 border-gray-200 rounded-xl p-4 max-h-80 overflow-y-auto space-y-2">
                    {filteredSubjects.map((subject) => {
                      const subjectId = (subject._id || subject.id)?.toString();
                      const isSelected = formData.teachSubjects?.includes(subjectId);
                      const className = classes?.find(c => (c._id || c.id)?.toString() === subject.sclassName?.toString())?.sclassName 
                                     || classes?.find(c => (c._id || c.id)?.toString() === subject.sclassName?.toString())?.class_name
                                     || `Class ${subject.sclassName}`;
                      
                      console.log('TeacherModal - Rendering subject:', subject.subName, 'ID:', subjectId);
                      
                      const handleToggle = () => handleSubjectToggle(subjectId, !isSelected);

                      return (
                        <div
                          key={subjectId}
                          className={`w-full text-left flex items-center space-x-3 p-3 rounded-lg border-2 transition-all hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 ${
                            isSelected
                              ? 'border-orange-500 bg-orange-50'
                              : 'border-gray-200 hover:border-orange-300'
                          }`}
                          role="button"
                          tabIndex={0}
                          onClick={handleToggle}
                          onKeyDown={(e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                              e.preventDefault();
                              handleToggle();
                            }
                          }}
                          aria-pressed={isSelected}
                        >
                          <input
                            type="checkbox"
                            id={`subject-${subjectId}`}
                            checked={isSelected}
                            className="w-5 h-5 accent-orange-600 cursor-pointer"
                            onChange={(e) => handleSubjectToggle(subjectId, e.target.checked)}
                            onClick={(e) => e.stopPropagation()}
                          />
                          <div className="flex-1">
                            <div className="text-sm font-semibold flex items-center justify-between">
                              <span>{subject.subName || subject.subject_name}</span>
                              <Badge variant="outline" className="text-xs ml-2">
                                {className}
                              </Badge>
                            </div>
                            {(subject.subCode || subject.subject_code) && (
                              <p className="text-xs text-gray-500 mt-0.5 font-mono">
                                {subject.subCode || subject.subject_code}
                              </p>
                            )}
                          </div>
                        </div>
                      );
                    })}
                    </div>
                  </>
                ) : searchQuery ? (
                  <div className="p-4 border-2 border-dashed border-gray-300 rounded-xl text-center">
                    <Search className="w-12 h-12 text-gray-400 mx-auto mb-2" />
                    <p className="text-sm text-gray-500">No subjects found for "{searchQuery}"</p>
                    <button
                      type="button"
                      onClick={() => setSearchQuery('')}
                      className="text-xs text-orange-600 hover:text-orange-700 mt-2"
                    >
                      Clear search
                    </button>
                  </div>
                ) : null}

                {/* Show count when searching */}
                {searchQuery && filteredSubjects.length > 0 && allSubjects.length > 0 && (
                  <p className="text-xs text-gray-500 mt-1">
                    Showing {filteredSubjects.length} of {allSubjects.length} subjects
                  </p>
                )}

                {/* Debug info */}
                <div className="mt-2 p-2 bg-gray-50 rounded text-xs font-mono text-gray-600">
                  State: total={allSubjects.length}, filtered={filteredSubjects.length}, loading={loadingSubjects.toString()}, search="{searchQuery}"
                </div>
              </div>

              {formData.teachSclass && (
                <div className="p-4 bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-xl">
                  <div className="flex items-center gap-2">
                    <MdSchool className="w-5 h-5 text-orange-600" />
                    <div>
                      <p className="text-sm font-semibold text-orange-900">Primary Class</p>
                      <p className="text-sm text-orange-700">
                        {classes?.find(c => (c._id || c.id)?.toString() === formData.teachSclass?.toString())?.sclassName || 
                         classes?.find(c => (c._id || c.id)?.toString() === formData.teachSclass?.toString())?.class_name || 
                         'Auto-assigned from first selected subject'}
                      </p>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Roles & Responsibilities Section */}
          <div className="space-y-4">
            <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
              <Award className="w-5 h-5 text-orange-600" />
              <h3 className="text-lg font-semibold text-gray-900">Roles & Responsibilities</h3>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-xl hover:border-orange-300 transition-colors">
                <Checkbox
                  id="isClassMaster"
                  checked={formData.isClassMaster}
                  onCheckedChange={(checked) => handleChange('isClassMaster', checked)}
                  className="w-5 h-5"
                />
                <div className="flex-1" onClick={() => handleChange('isClassMaster', !formData.isClassMaster)} style={{cursor: 'pointer'}}>
                  <div className="text-sm font-semibold">
                    Class Master
                  </div>
                  <p className="text-xs text-gray-500 mt-0.5">
                    Responsible for managing class activities
                  </p>
                </div>
              </div>

              <div className="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-xl hover:border-orange-300 transition-colors">
                <Checkbox
                  id="isExamOfficer"
                  checked={formData.isExamOfficer}
                  onCheckedChange={(checked) => handleChange('isExamOfficer', checked)}
                  className="w-5 h-5"
                />
                <div className="flex-1" onClick={() => handleChange('isExamOfficer', !formData.isExamOfficer)} style={{cursor: 'pointer'}}>
                  <div className="text-sm font-semibold">
                    Exam Officer
                  </div>
                  <p className="text-xs text-gray-500 mt-0.5">
                    Manages exams and grading system
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-gradient-to-r from-blue-50 to-orange-50 border border-orange-200 rounded-xl p-4">
            <div className="flex items-start gap-3">
              <div className="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <MdLock className="w-4 h-4 text-orange-600" />
              </div>
              <div>
                <p className="text-sm font-semibold text-orange-900 mb-1">Login Credentials & Assignment</p>
                <ul className="text-sm text-orange-800 space-y-1">
                  <li>• Teacher uses <strong>email address</strong> and <strong>password</strong> to login</li>
                  <li>• Can teach <strong>multiple subjects</strong> across different classes</li>
                  <li>• Primary class is auto-assigned from first selected subject</li>
                </ul>
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
              className="h-11 bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 hover:from-orange-700 hover:to-pink-700 shadow-lg"
            >
              {loading ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                  Creating Teacher...
                </>
              ) : (
                <>
                  <MdSave className="w-5 h-5 mr-2" />
                  Create Teacher Account
                </>
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default TeacherModal;
