import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Textarea } from '@/components/ui/textarea';
import {
  FiClock,
  FiPlus,
  FiTrash2,
  FiEdit2,
  FiGrid,
  FiList,
  FiCalendar,
} from 'react-icons/fi';
import { toast } from 'sonner';
import {
  createTimetableEntry,
  getClassTimetable,
  updateTimetableEntry,
  deleteTimetableEntry,
} from '../../../redux/timetableRelated/timetableHandle';
import { getAllSclasses } from '../../../redux/sclassRelated/sclassHandle';
import { getAllTeachers } from '../../../redux/teacherRelated/teacherHandle';
import { getAllAcademicYears } from '../../../redux/academicYearRelated/academicYearHandle';
import TimetableGrid from '../../../components/timetable/TimetableGrid';

const DAYS_OF_WEEK = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const TimetableManagement = () => {
  const dispatch = useDispatch();
  const { currentUser } = useSelector((state) => state.user);
  const { sclassesList } = useSelector((state) => state.sclass);
  const { teachersList } = useSelector((state) => state.teacher);
  const { academicYearData: academicYearsList } = useSelector((state) => state.academicYear);
  const { groupedTimetable, loading, error, response } = useSelector(
    (state) => state.timetable
  );

  const [selectedClass, setSelectedClass] = useState('');
  const [selectedAcademicYear, setSelectedAcademicYear] = useState('');
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [showEditDialog, setShowEditDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [selectedEntry, setSelectedEntry] = useState(null);

  const [formData, setFormData] = useState({
    class_id: '',
    subject_id: '',
    teacher_id: '',
    day_of_week: '',
    start_time: '',
    end_time: '',
    room_number: '',
    notes: '',
  });

  useEffect(() => {
    dispatch(getAllSclasses());
    dispatch(getAllTeachers());
    dispatch(getAllAcademicYears());
  }, [dispatch]);

  useEffect(() => {
    if (selectedClass && selectedAcademicYear) {
      dispatch(getClassTimetable(selectedClass));
    }
  }, [dispatch, selectedClass, selectedAcademicYear]);

  useEffect(() => {
    if (response) {
      if (response.success) {
        toast.success(response.message || 'Operation completed successfully');
        if (selectedClass) {
          dispatch(getClassTimetable(selectedClass));
        }
      } else if (response.message) {
        toast.error(response.message);
      }
    }
    if (error) {
      toast.error(error);
    }
  }, [response, error, dispatch, selectedClass]);

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const resetForm = () => {
    setFormData({
      class_id: selectedClass || '',
      subject_id: '',
      teacher_id: '',
      day_of_week: '',
      start_time: '',
      end_time: '',
      room_number: '',
      notes: '',
    });
  };

  const handleCreateEntry = async () => {
    if (
      !formData.class_id ||
      !formData.subject_id ||
      !formData.teacher_id ||
      !formData.day_of_week ||
      !formData.start_time ||
      !formData.end_time
    ) {
      toast.error('Please fill in all required fields');
      return;
    }

    const entryData = {
      admin_id: currentUser.id,
      academic_year_id: selectedAcademicYear,
      class_id: parseInt(formData.class_id),
      subject_id: parseInt(formData.subject_id),
      teacher_id: parseInt(formData.teacher_id),
      day_of_week: formData.day_of_week,
      start_time: formData.start_time,
      end_time: formData.end_time,
      room_number: formData.room_number,
      notes: formData.notes,
    };

    await dispatch(createTimetableEntry(entryData));
    setShowCreateDialog(false);
    resetForm();
  };

  const handleEditEntry = (entry) => {
    setSelectedEntry(entry);
    setFormData({
      class_id: entry.class_id?.toString() || '',
      subject_id: entry.subject_id?.toString() || '',
      teacher_id: entry.teacher_id?.toString() || '',
      day_of_week: entry.day_of_week,
      start_time: entry.start_time.substring(0, 5),
      end_time: entry.end_time.substring(0, 5),
      room_number: entry.room_number || '',
      notes: entry.notes || '',
    });
    setShowEditDialog(true);
  };

  const handleUpdateEntry = async () => {
    if (!selectedEntry) return;

    const entryData = {
      admin_id: currentUser.id,
      academic_year_id: selectedAcademicYear,
      class_id: parseInt(formData.class_id),
      subject_id: parseInt(formData.subject_id),
      teacher_id: parseInt(formData.teacher_id),
      day_of_week: formData.day_of_week,
      start_time: formData.start_time,
      end_time: formData.end_time,
      room_number: formData.room_number,
      notes: formData.notes,
    };

    await dispatch(updateTimetableEntry(selectedEntry.id, entryData));
    setShowEditDialog(false);
    setSelectedEntry(null);
    resetForm();
  };

  const handleDeleteEntry = async () => {
    if (!selectedEntry) return;
    await dispatch(deleteTimetableEntry(selectedEntry.id));
    setShowDeleteDialog(false);
    setSelectedEntry(null);
  };

  const openDeleteDialog = (entry) => {
    setSelectedEntry(entry);
    setShowDeleteDialog(true);
  };

  // Get subjects for selected class
  const getClassSubjects = () => {
    if (!selectedClass || !sclassesList) return [];
    const classObj = sclassesList.find((c) => c.id?.toString() === selectedClass.toString());
    return classObj?.subjects || [];
  };

  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Timetable Management</h1>
        <p className="text-gray-600 mt-1">Create and manage class schedules</p>
      </div>

      {/* Class & Academic Year Selection */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FiCalendar className="h-5 w-5" />
            Select Class and Academic Year
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label>Academic Year</Label>
              <Select value={selectedAcademicYear} onValueChange={setSelectedAcademicYear}>
                <SelectTrigger>
                  <SelectValue placeholder="Select Academic Year" />
                </SelectTrigger>
                <SelectContent>
                  {academicYearsList?.map((year) => (
                    <SelectItem key={year.id} value={year.id?.toString()}>
                      {year.year_name} {year.is_current ? '(Current)' : ''}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Class</Label>
              <Select value={selectedClass} onValueChange={setSelectedClass}>
                <SelectTrigger>
                  <SelectValue placeholder="Select Class" />
                </SelectTrigger>
                <SelectContent>
                  {sclassesList?.map((cls) => (
                    <SelectItem key={cls.id} value={cls.id?.toString()}>
                      {cls.sclassName}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {selectedClass && selectedAcademicYear && (
            <Button
              onClick={() => {
                setFormData({ ...formData, class_id: selectedClass });
                setShowCreateDialog(true);
              }}
              className="w-full md:w-auto"
            >
              <FiPlus className="mr-2 h-4 w-4" />
              Add Timetable Entry
            </Button>
          )}
        </CardContent>
      </Card>

      {/* Timetable Display */}
      {selectedClass && selectedAcademicYear && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FiClock className="h-5 w-5" />
              Class Timetable
            </CardTitle>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
              </div>
            ) : (
              <Tabs defaultValue="grid" className="w-full">
                <TabsList className="grid w-full max-w-md grid-cols-2 mb-4">
                  <TabsTrigger value="grid" className="flex items-center gap-2">
                    <FiGrid className="w-4 h-4" />
                    Grid View
                  </TabsTrigger>
                  <TabsTrigger value="list" className="flex items-center gap-2">
                    <FiList className="w-4 h-4" />
                    List View
                  </TabsTrigger>
                </TabsList>

                <TabsContent value="grid">
                  <TimetableGrid groupedTimetable={groupedTimetable} isTeacher={false} />
                </TabsContent>

                <TabsContent value="list">
                  <div className="space-y-6">
                    {DAYS_OF_WEEK.map((day) => {
                      const dayClasses = groupedTimetable[day] || [];
                      return (
                        <div key={day}>
                          <h3 className="text-lg font-semibold mb-3 text-gray-900">{day}</h3>
                          {dayClasses.length > 0 ? (
                            <div className="space-y-2">
                              {dayClasses.map((entry, index) => (
                                <div
                                  key={index}
                                  className="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200"
                                >
                                  <div className="flex-1">
                                    <div className="font-semibold text-lg">
                                      {entry.subject_name}
                                    </div>
                                    <div className="text-sm text-gray-600 mt-1">
                                      <span>Teacher: {entry.teacher_name}</span>
                                      {entry.room_number && (
                                        <span className="ml-4">Room: {entry.room_number}</span>
                                      )}
                                    </div>
                                    <div className="text-sm text-gray-700 mt-1">
                                      {entry.start_time.substring(0, 5)} -{' '}
                                      {entry.end_time.substring(0, 5)}
                                    </div>
                                    {entry.notes && (
                                      <div className="text-sm text-gray-500 mt-1 italic">
                                        {entry.notes}
                                      </div>
                                    )}
                                  </div>
                                  <div className="flex gap-2">
                                    <Button
                                      size="sm"
                                      variant="outline"
                                      onClick={() => handleEditEntry(entry)}
                                    >
                                      <FiEdit2 className="h-4 w-4" />
                                    </Button>
                                    <Button
                                      size="sm"
                                      variant="destructive"
                                      onClick={() => openDeleteDialog(entry)}
                                    >
                                      <FiTrash2 className="h-4 w-4" />
                                    </Button>
                                  </div>
                                </div>
                              ))}
                            </div>
                          ) : (
                            <p className="text-gray-500 text-sm italic">No classes scheduled</p>
                          )}
                        </div>
                      );
                    })}
                  </div>
                </TabsContent>
              </Tabs>
            )}
          </CardContent>
        </Card>
      )}

      {/* Create Entry Dialog */}
      <AlertDialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
        <AlertDialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <AlertDialogHeader>
            <AlertDialogTitle>Add Timetable Entry</AlertDialogTitle>
            <AlertDialogDescription>
              Create a new timetable entry for the selected class.
            </AlertDialogDescription>
          </AlertDialogHeader>

          <div className="space-y-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Day of Week</Label>
                <Select
                  value={formData.day_of_week}
                  onValueChange={(value) => handleInputChange('day_of_week', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select Day" />
                  </SelectTrigger>
                  <SelectContent>
                    {DAYS_OF_WEEK.map((day) => (
                      <SelectItem key={day} value={day}>
                        {day}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label>Subject</Label>
                <Select
                  value={formData.subject_id}
                  onValueChange={(value) => handleInputChange('subject_id', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select Subject" />
                  </SelectTrigger>
                  <SelectContent>
                    {getClassSubjects().map((subject) => (
                      <SelectItem key={subject.id} value={subject.id?.toString()}>
                        {subject.subName}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label>Teacher</Label>
              <Select
                value={formData.teacher_id}
                onValueChange={(value) => handleInputChange('teacher_id', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select Teacher" />
                </SelectTrigger>
                <SelectContent>
                  {teachersList?.map((teacher) => (
                    <SelectItem key={teacher.id} value={teacher.id?.toString()}>
                      {teacher.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Start Time</Label>
                <Input
                  type="time"
                  value={formData.start_time}
                  onChange={(e) => handleInputChange('start_time', e.target.value)}
                />
              </div>

              <div>
                <Label>End Time</Label>
                <Input
                  type="time"
                  value={formData.end_time}
                  onChange={(e) => handleInputChange('end_time', e.target.value)}
                />
              </div>
            </div>

            <div>
              <Label>Room Number (Optional)</Label>
              <Input
                value={formData.room_number}
                onChange={(e) => handleInputChange('room_number', e.target.value)}
                placeholder="e.g., Room 101"
              />
            </div>

            <div>
              <Label>Notes (Optional)</Label>
              <Textarea
                value={formData.notes}
                onChange={(e) => handleInputChange('notes', e.target.value)}
                placeholder="Additional notes or instructions"
                rows={3}
              />
            </div>
          </div>

          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => {
              setShowCreateDialog(false);
              resetForm();
            }}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction onClick={handleCreateEntry}>Create Entry</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Edit Entry Dialog */}
      <AlertDialog open={showEditDialog} onOpenChange={setShowEditDialog}>
        <AlertDialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <AlertDialogHeader>
            <AlertDialogTitle>Edit Timetable Entry</AlertDialogTitle>
            <AlertDialogDescription>
              Update the selected timetable entry.
            </AlertDialogDescription>
          </AlertDialogHeader>

          <div className="space-y-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Day of Week</Label>
                <Select
                  value={formData.day_of_week}
                  onValueChange={(value) => handleInputChange('day_of_week', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select Day" />
                  </SelectTrigger>
                  <SelectContent>
                    {DAYS_OF_WEEK.map((day) => (
                      <SelectItem key={day} value={day}>
                        {day}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label>Subject</Label>
                <Select
                  value={formData.subject_id}
                  onValueChange={(value) => handleInputChange('subject_id', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select Subject" />
                  </SelectTrigger>
                  <SelectContent>
                    {getClassSubjects().map((subject) => (
                      <SelectItem key={subject.id} value={subject.id?.toString()}>
                        {subject.subName}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label>Teacher</Label>
              <Select
                value={formData.teacher_id}
                onValueChange={(value) => handleInputChange('teacher_id', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select Teacher" />
                </SelectTrigger>
                <SelectContent>
                  {teachersList?.map((teacher) => (
                    <SelectItem key={teacher.id} value={teacher.id?.toString()}>
                      {teacher.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Start Time</Label>
                <Input
                  type="time"
                  value={formData.start_time}
                  onChange={(e) => handleInputChange('start_time', e.target.value)}
                />
              </div>

              <div>
                <Label>End Time</Label>
                <Input
                  type="time"
                  value={formData.end_time}
                  onChange={(e) => handleInputChange('end_time', e.target.value)}
                />
              </div>
            </div>

            <div>
              <Label>Room Number (Optional)</Label>
              <Input
                value={formData.room_number}
                onChange={(e) => handleInputChange('room_number', e.target.value)}
                placeholder="e.g., Room 101"
              />
            </div>

            <div>
              <Label>Notes (Optional)</Label>
              <Textarea
                value={formData.notes}
                onChange={(e) => handleInputChange('notes', e.target.value)}
                placeholder="Additional notes or instructions"
                rows={3}
              />
            </div>
          </div>

          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => {
              setShowEditDialog(false);
              setSelectedEntry(null);
              resetForm();
            }}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction onClick={handleUpdateEntry}>Update Entry</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Timetable Entry</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this timetable entry? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => {
              setShowDeleteDialog(false);
              setSelectedEntry(null);
            }}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteEntry}
              className="bg-red-600 hover:bg-red-700"
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default TimetableManagement;
