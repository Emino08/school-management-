import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Search, UserPlus, Users, Filter } from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080/api';

const StudentRegistration = ({ townData, onSuccess }) => {
  const [students, setStudents] = useState([]);
  const [classes, setClasses] = useState([]);
  const [academicYears, setAcademicYears] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [showRegisterDialog, setShowRegisterDialog] = useState(false);
  const [registrationData, setRegistrationData] = useState({
    block_id: '',
    academic_year_id: '',
    term: '1',
    guardian_name: '',
    guardian_phone: '',
    guardian_email: '',
    guardian_address: '',
  });

  useEffect(() => {
    fetchClasses();
    fetchAcademicYears();
  }, []);

  const fetchClasses = async () => {
    try {
      const response = await axios.get(`${API_URL}/classes`);
      if (response.data.success) {
        setClasses(response.data.classes || []);
      }
    } catch (error) {
      console.error('Error fetching classes:', error);
    }
  };

  const fetchAcademicYears = async () => {
    try {
      const response = await axios.get(`${API_URL}/admin/academic-years`);
      if (response.data.success) {
        setAcademicYears(response.data.academic_years || []);
        // Set default to current/active year
        const activeYear = response.data.academic_years?.find(y => y.is_active);
        if (activeYear) {
          setRegistrationData(prev => ({ ...prev, academic_year_id: activeYear.id }));
        }
      }
    } catch (error) {
      console.error('Error fetching academic years:', error);
    }
  };

  const searchStudents = async () => {
    if (!searchTerm && !selectedClass) {
      toast.error('Please enter a search term or select a class');
      return;
    }

    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (searchTerm) params.append('search', searchTerm);
      if (selectedClass) params.append('class_id', selectedClass);

      const response = await axios.get(`${API_URL}/students?${params.toString()}`);
      if (response.data.success) {
        setStudents(response.data.students || []);
        if (response.data.students?.length === 0) {
          toast.info('No students found matching your criteria');
        }
      }
    } catch (error) {
      console.error('Error searching students:', error);
      toast.error('Failed to search students');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectStudent = (student) => {
    setSelectedStudent(student);
    setShowRegisterDialog(true);
    // Pre-fill guardian info if available
    setRegistrationData(prev => ({
      ...prev,
      guardian_name: student.parent_name || '',
      guardian_phone: student.parent_phone || '',
      guardian_email: student.parent_email || '',
    }));
  };

  const handleRegister = async () => {
    if (!registrationData.block_id) {
      toast.error('Please select a block');
      return;
    }

    if (!registrationData.academic_year_id) {
      toast.error('Please select an academic year');
      return;
    }

    try {
      setLoading(true);
      const response = await axios.post(
        `${API_URL}/teacher/town-master/register-student`,
        {
          student_id: selectedStudent.id,
          ...registrationData,
        }
      );

      if (response.data.success) {
        toast.success('Student registered successfully');
        setShowRegisterDialog(false);
        setSelectedStudent(null);
        setRegistrationData({
          block_id: '',
          academic_year_id: registrationData.academic_year_id,
          term: '1',
          guardian_name: '',
          guardian_phone: '',
          guardian_email: '',
          guardian_address: '',
        });
        setStudents([]);
        setSearchTerm('');
        if (onSuccess) onSuccess();
      }
    } catch (error) {
      console.error('Error registering student:', error);
      toast.error(error.response?.data?.message || 'Failed to register student');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <UserPlus className="w-5 h-5" />
            Register Student to Town/Block
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Search Filters */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>Search by Name/ID</Label>
              <Input
                placeholder="Enter student name or ID..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                onKeyPress={(e) => e.key === 'Enter' && searchStudents()}
              />
            </div>

            <div className="space-y-2">
              <Label>Filter by Class</Label>
              <Select value={selectedClass} onValueChange={setSelectedClass}>
                <SelectTrigger>
                  <SelectValue placeholder="All Classes" />
                </SelectTrigger>
                <SelectContent>
                  {classes.map((cls) => (
                    <SelectItem key={cls.id} value={cls.id.toString()}>
                      {cls.name || cls.sclassName || cls.class_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="flex items-end">
              <Button onClick={searchStudents} disabled={loading} className="w-full">
                <Search className="w-4 h-4 mr-2" />
                Search Students
              </Button>
            </div>
          </div>

          {/* Student Results */}
          {students.length > 0 && (
            <div className="border rounded-lg">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID Number</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Class</TableHead>
                    <TableHead>Action</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {students.map((student) => (
                    <TableRow key={student.id}>
                      <TableCell>{student.id_number || student.rollNum}</TableCell>
                      <TableCell>
                        {student.first_name && student.last_name
                          ? `${student.first_name} ${student.last_name}`
                          : student.name}
                      </TableCell>
                      <TableCell>{student.class_name || student.sclassName?.sclassName || 'N/A'}</TableCell>
                      <TableCell>
                        <Button
                          size="sm"
                          onClick={() => handleSelectStudent(student)}
                          variant="outline"
                        >
                          <UserPlus className="w-4 h-4 mr-1" />
                          Register
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}

          {students.length === 0 && searchTerm && !loading && (
            <div className="text-center py-8 text-gray-500">
              <Users className="w-12 h-12 mx-auto mb-2 opacity-50" />
              <p>No students found. Try different search criteria.</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Registration Dialog */}
      <Dialog open={showRegisterDialog} onOpenChange={setShowRegisterDialog}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Register Student to Block</DialogTitle>
            <DialogDescription>
              Registering{' '}
              <span className="font-semibold">
                {selectedStudent?.first_name && selectedStudent?.last_name
                  ? `${selectedStudent.first_name} ${selectedStudent.last_name}`
                  : selectedStudent?.name}
              </span>{' '}
              to {townData.name}
            </DialogDescription>
          </DialogHeader>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Block *</Label>
              <Select
                value={registrationData.block_id}
                onValueChange={(value) =>
                  setRegistrationData({ ...registrationData, block_id: value })
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select block" />
                </SelectTrigger>
                <SelectContent>
                  {townData.blocks?.map((block) => (
                    <SelectItem key={block.id} value={block.id.toString()}>
                      Block {block.name} ({block.current_occupancy || 0}/{block.capacity})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Academic Year *</Label>
              <Select
                value={registrationData.academic_year_id}
                onValueChange={(value) =>
                  setRegistrationData({ ...registrationData, academic_year_id: value })
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select year" />
                </SelectTrigger>
                <SelectContent>
                  {academicYears.map((year) => (
                    <SelectItem key={year.id} value={year.id.toString()}>
                      {year.year_name || year.name} {year.is_active ? '(Current)' : ''}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Term *</Label>
              <Select
                value={registrationData.term}
                onValueChange={(value) =>
                  setRegistrationData({ ...registrationData, term: value })
                }
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">Term 1</SelectItem>
                  <SelectItem value="2">Term 2</SelectItem>
                  <SelectItem value="3">Term 3</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Guardian Name</Label>
              <Input
                value={registrationData.guardian_name}
                onChange={(e) =>
                  setRegistrationData({ ...registrationData, guardian_name: e.target.value })
                }
                placeholder="Parent/Guardian name"
              />
            </div>

            <div className="space-y-2">
              <Label>Guardian Phone</Label>
              <Input
                value={registrationData.guardian_phone}
                onChange={(e) =>
                  setRegistrationData({ ...registrationData, guardian_phone: e.target.value })
                }
                placeholder="Contact number"
              />
            </div>

            <div className="space-y-2">
              <Label>Guardian Email</Label>
              <Input
                type="email"
                value={registrationData.guardian_email}
                onChange={(e) =>
                  setRegistrationData({ ...registrationData, guardian_email: e.target.value })
                }
                placeholder="Email address"
              />
            </div>

            <div className="col-span-2 space-y-2">
              <Label>Guardian Address</Label>
              <Input
                value={registrationData.guardian_address}
                onChange={(e) =>
                  setRegistrationData({ ...registrationData, guardian_address: e.target.value })
                }
                placeholder="Home address"
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setShowRegisterDialog(false)}>
              Cancel
            </Button>
            <Button onClick={handleRegister} disabled={loading}>
              {loading ? 'Registering...' : 'Register Student'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default StudentRegistration;
