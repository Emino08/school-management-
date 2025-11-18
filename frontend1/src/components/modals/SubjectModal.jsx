import { useState, useEffect } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { MdAdd, MdDelete, MdSave, MdClose, MdBook, MdCode, MdPerson, MdClass } from 'react-icons/md';
import { BookOpen, Users, GraduationCap } from 'lucide-react';
import { toast } from 'sonner';
import axios from '../../redux/axiosConfig';
import { useSelector, useDispatch } from 'react-redux';
import { getAllSclasses } from '../../redux/sclassRelated/sclassHandle';

const SubjectModal = ({ open, onOpenChange, onSuccess, editMode = false, subjectData = null, defaultClassId = '' }) => {
  const dispatch = useDispatch();
  const { currentUser } = useSelector((state) => state.user);
  const adminID = currentUser?._id || currentUser?.id;

  const [subjects, setSubjects] = useState([
    { subName: '', subCode: '', sessions: '', sclassName: '', description: '' }
  ]);
  const [loading, setLoading] = useState(false);
  const [classes, setClasses] = useState([]);
  const [loadingClasses, setLoadingClasses] = useState(false);

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    if (open) {
      console.log('SubjectModal - Modal opened');
      console.log('SubjectModal - Admin ID:', adminID);
      console.log('SubjectModal - API URL:', API_URL);
      fetchClasses();
      
      if (editMode && subjectData) {
        // Support both internal modal shape and API subject shape
        const isApiShape = !!subjectData.subject_name || !!subjectData.subject_code;
        setSubjects([
          isApiShape
            ? {
                subName: subjectData.subject_name || '',
                subCode: subjectData.subject_code || '',
                sessions: subjectData.sessions || '',
                sclassName: subjectData.class_id?.toString() || '',
                description: subjectData.description || '',
              }
            : {
                subName: subjectData.subName || '',
                subCode: subjectData.subCode || '',
                sessions: subjectData.sessions || '',
                sclassName: subjectData.sclassName || '',
                description: subjectData.description || '',
              },
        ]);
      } else if (defaultClassId) {
        setSubjects((prev) =>
          prev.map((subject) =>
            subject.sclassName
              ? subject
              : { ...subject, sclassName: defaultClassId.toString() }
          )
        );
      }
    }
  }, [open, adminID, editMode, subjectData, defaultClassId]);

  const fetchClasses = async () => {
    setLoadingClasses(true);
    console.log('SubjectModal - Fetching classes from:', `${API_URL}/classes`);
    
    try {
      const response = await axios.get(`${API_URL}/classes`, {
        headers: {
          'Authorization': `Bearer ${currentUser?.token}`,
          'Content-Type': 'application/json'
        }
      });
      
      console.log('SubjectModal - API Response:', response.data);
      
      if (response.data.success && response.data.classes) {
        const fetchedClasses = response.data.classes;
        console.log('SubjectModal - Classes fetched:', fetchedClasses);
        console.log('SubjectModal - Classes count:', fetchedClasses.length);
        if (fetchedClasses.length > 0) {
          console.log('SubjectModal - First class:', fetchedClasses[0]);
        }
        setClasses(fetchedClasses);
      } else {
        console.warn('SubjectModal - No classes in response or success=false');
        setClasses([]);
      }
    } catch (error) {
      console.error('SubjectModal - Error fetching classes:', error);
      console.error('SubjectModal - Error details:', error.response?.data);
      toast.error('Failed to load classes');
      setClasses([]);
    } finally {
      setLoadingClasses(false);
    }
  };

  const handleAddSubject = () => {
    setSubjects([...subjects, { subName: '', subCode: '', sessions: '', sclassName: '', description: '' }]);
  };

  const handleRemoveSubject = (index) => {
    if (subjects.length > 1) {
      const newSubjects = subjects.filter((_, i) => i !== index);
      setSubjects(newSubjects);
    }
  };

  const handleSubjectChange = (index, field, value) => {
    console.log(`SubjectModal - Changing field ${field} to:`, value);
    const newSubjects = [...subjects];
    newSubjects[index][field] = value;
    setSubjects(newSubjects);
    console.log('SubjectModal - Updated subjects:', newSubjects);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validation
    const invalidSubjects = subjects.filter(
      (s) => !s.subName || !s.subCode || !s.sessions || !s.sclassName
    );
    if (invalidSubjects.length > 0) {
      toast.error('Please fill all required fields for all subjects');
      return;
    }

    setLoading(true);

    try {
      if (editMode && subjectData) {
        // Update existing subject
        const subject = subjects[0];
        const payload = {
          subject_name: subject.subName,
          subject_code: subject.subCode,
          sessions: parseInt(subject.sessions) || 0,
          class_id: parseInt(subject.sclassName),
          description: subject.description || '',
          admin_id: parseInt(adminID),
        };
        
        console.log('SubjectModal - Updating subject with payload:', payload);
        
        const subjectId = subjectData.id || subjectData._id;
        const response = await axios.put(
          `${import.meta.env.VITE_API_BASE_URL}/subjects/${subjectId}`,
          payload
        );

        if (response.data.success || response.status === 200) {
          toast.success('Subject updated successfully!');
          handleClose();
          if (onSuccess) onSuccess();
        }
      } else {
        // Create new subjects
        const promises = subjects.map((subject) => {
          const payload = {
            subject_name: subject.subName,
            subject_code: subject.subCode,
            sessions: parseInt(subject.sessions) || 0,
            class_id: parseInt(subject.sclassName),
            description: subject.description || '',
            admin_id: parseInt(adminID),
          };
          
          console.log('SubjectModal - Creating subject with payload:', payload);
          
          return axios.post(
            `${import.meta.env.VITE_API_BASE_URL}/subjects`,
            payload
          );
        });

        const results = await Promise.all(promises);
        const allSuccess = results.every((r) => r.data.success || r.status === 200);

        if (allSuccess) {
          toast.success(`${subjects.length} subject(s) created successfully!`);
          handleClose();
          if (onSuccess) onSuccess();
        } else {
          const failed = results.filter((r) => !r.data.success && r.status !== 200);
          console.error('SubjectModal - Failed results:', failed);
          toast.error('Some subjects failed to create');
        }
      }
    } catch (error) {
      console.error('SubjectModal - Error saving subjects:', error);
      console.error('SubjectModal - Error response:', error.response?.data);
      toast.error(error.response?.data?.message || 'Failed to save subjects');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setSubjects([{ subName: '', subCode: '', sessions: '', sclassName: '', description: '' }]);
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-2xl font-bold flex items-center gap-3">
            <div className="w-12 h-12 bg-gradient-to-br from-purple-500 via-purple-600 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
              <BookOpen className="w-6 h-6 text-white" />
            </div>
            <div>
              <div className="text-gray-900">{editMode ? 'Edit Subject' : 'Create Subjects'}</div>
              <p className="text-sm font-normal text-gray-500 mt-0.5">
                {editMode ? 'Update subject information' : 'Add subjects to enhance your curriculum'}
              </p>
            </div>
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-5 mt-4">
          <div className="space-y-4">
            {subjects.map((subject, index) => (
              <div
                key={index}
                className="relative p-5 border-2 border-gray-200 rounded-xl hover:border-purple-400 hover:shadow-md transition-all space-y-4 bg-gradient-to-br from-white via-purple-50/30 to-blue-50/30"
              >
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-2">
                    <div className="w-8 h-8 bg-gradient-to-br from-purple-500 to-blue-500 rounded-lg flex items-center justify-center">
                      <MdBook className="w-4 h-4 text-white" />
                    </div>
                    <Badge className="bg-purple-100 text-purple-700 hover:bg-purple-100">
                      Subject {index + 1}
                    </Badge>
                  </div>
                  {subjects.length > 1 && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() => handleRemoveSubject(index)}
                      className="text-red-600 hover:text-red-700 hover:bg-red-50 h-8"
                    >
                      <MdDelete className="w-5 h-5" />
                    </Button>
                  )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor={`subName-${index}`} className="text-sm font-semibold flex items-center gap-1.5">
                      <MdBook className="w-4 h-4 text-purple-600" />
                      Subject Name <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id={`subName-${index}`}
                      placeholder="e.g., Mathematics"
                      value={subject.subName}
                      onChange={(e) =>
                        handleSubjectChange(index, 'subName', e.target.value)
                      }
                      required
                      className="h-11 border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor={`subCode-${index}`} className="text-sm font-semibold flex items-center gap-1.5">
                      <MdCode className="w-4 h-4 text-purple-600" />
                      Subject Code <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id={`subCode-${index}`}
                      placeholder="e.g., MTH101"
                      value={subject.subCode}
                      onChange={(e) =>
                        handleSubjectChange(index, 'subCode', e.target.value)
                      }
                      required
                      className="h-11 border-gray-300 focus:border-purple-500 focus:ring-purple-500 font-mono"
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor={`sessions-${index}`} className="text-sm font-semibold">
                      Sessions Per Week <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id={`sessions-${index}`}
                      type="number"
                      min="1"
                      max="50"
                      placeholder="e.g., 5"
                      value={subject.sessions}
                      onChange={(e) =>
                        handleSubjectChange(index, 'sessions', e.target.value)
                      }
                      required
                      className="h-11 border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor={`class-${index}`} className="text-sm font-semibold flex items-center gap-1.5">
                      <MdClass className="w-4 h-4 text-purple-600" />
                      Class <span className="text-red-500">*</span>
                    </Label>
                    {/* Debug Info */}
                    <div className="text-xs text-gray-500 mb-1">
                      Selected: {subject.sclassName || 'None'} | Classes available: {classes?.length || 0}
                      {loadingClasses && ' | Loading...'}
                    </div>
                    <Select
                      value={subject.sclassName?.toString() || ''}
                      onValueChange={(value) => {
                        console.log('SubjectModal - Class selected:', value);
                        handleSubjectChange(index, 'sclassName', value);
                      }}
                      disabled={loadingClasses}
                    >
                      <SelectTrigger className="h-11">
                        <SelectValue placeholder={loadingClasses ? "Loading classes..." : "Select class for this subject"} />
                      </SelectTrigger>
                      <SelectContent>
                        {loadingClasses ? (
                          <SelectItem value="loading" disabled>
                            Loading classes...
                          </SelectItem>
                        ) : classes && classes.length > 0 ? (
                          classes.map((classItem) => {
                            const itemId = classItem._id || classItem.id;
                            const itemName = classItem.sclassName || classItem.class_name;
                            console.log('SubjectModal - Rendering class item:', { 
                              itemId, 
                              itemName, 
                              fullItem: classItem 
                            });
                            return (
                              <SelectItem 
                                key={itemId} 
                                value={itemId?.toString()}
                              >
                                {itemName}
                              </SelectItem>
                            );
                          })
                        ) : (
                          <SelectItem value="no-class" disabled>
                            No classes available - Please create classes first
                          </SelectItem>
                        )}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-2 md:col-span-2">
                    <Label htmlFor={`desc-${index}`} className="text-sm font-semibold">
                      Description (Optional)
                    </Label>
                    <Textarea
                      id={`desc-${index}`}
                      placeholder="Brief description about the subject..."
                      value={subject.description || ''}
                      onChange={(e) =>
                        handleSubjectChange(index, 'description', e.target.value)
                      }
                      rows={2}
                      className="resize-none border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>

          {!editMode && (
            <Button
              type="button"
              variant="outline"
              onClick={handleAddSubject}
              className="w-full h-12 border-dashed border-2 border-purple-300 hover:border-purple-500 hover:bg-purple-50 text-purple-600 font-semibold"
            >
              <MdAdd className="w-5 h-5 mr-2" />
              Add Another Subject
            </Button>
          )}

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
              className="h-11 bg-gradient-to-r from-purple-600 via-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 shadow-lg"
            >
              {loading ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                  {editMode ? 'Updating...' : 'Creating...'}
                </>
              ) : (
                <>
                  <MdSave className="w-5 h-5 mr-2" />
                  {editMode ? 'Update Subject' : `Create ${subjects.length} Subject${subjects.length > 1 ? 's' : ''}`}
                </>
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default SubjectModal;
