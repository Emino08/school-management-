import React, { useState, useEffect } from 'react';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { CheckSquare, Save, AlertCircle, Calendar } from 'lucide-react';
import { Label } from '@/components/ui/label';

const TownAttendance = ({ townData }) => {
  const [students, setStudents] = useState([]);
  const [attendance, setAttendance] = useState({});
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [selectedBlock, setSelectedBlock] = useState('');
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);

  useEffect(() => {
    fetchStudents();
  }, [selectedBlock]);

  useEffect(() => {
    // Check if attendance already taken for today
    checkExistingAttendance();
  }, [selectedDate, selectedBlock]);

  const fetchStudents = async () => {
    try {
      setLoading(true);
      const params = selectedBlock ? `?block_id=${selectedBlock}` : '';
      const response = await axios.get(`/teacher/town-master/students${params}`);
      if (response.data.success) {
        const studentList = response.data.students || [];
        setStudents(studentList);
        
        // Initialize attendance as "present" for all
        const initialAttendance = {};
        studentList.forEach((student) => {
          initialAttendance[student.student_block_id] = {
            status: 'present',
            notes: '',
          };
        });
        setAttendance(initialAttendance);
      }
    } catch (error) {
      console.error('Error fetching students:', error);
      toast.error('Failed to fetch students');
    } finally {
      setLoading(false);
    }
  };

  const checkExistingAttendance = async () => {
    try {
      const response = await axios.get(
        `/teacher/town-master/attendance?date=${selectedDate}`
      );
      if (response.data.success && response.data.attendance?.length > 0) {
        // Load existing attendance
        const existingAttendance = {};
        response.data.attendance.forEach((record) => {
          existingAttendance[record.student_block_id] = {
            status: record.status,
            notes: record.notes || '',
          };
        });
        setAttendance(existingAttendance);
        toast.info('Loaded existing attendance for this date');
      }
    } catch (error) {
      console.error('Error checking attendance:', error);
    }
  };

  const handleStatusChange = (studentBlockId, status) => {
    setAttendance({
      ...attendance,
      [studentBlockId]: {
        ...attendance[studentBlockId],
        status,
      },
    });
  };

  const handleNotesChange = (studentBlockId, notes) => {
    setAttendance({
      ...attendance,
      [studentBlockId]: {
        ...attendance[studentBlockId],
        notes,
      },
    });
  };

  const handleSubmit = async () => {
    if (students.length === 0) {
      toast.error('No students to record attendance for');
      return;
    }

    try {
      setSaving(true);
      
      const attendanceRecords = students.map((student) => ({
        student_block_id: student.student_block_id,
        status: attendance[student.student_block_id]?.status || 'present',
        notes: attendance[student.student_block_id]?.notes || '',
      }));

      const response = await axios.post('/teacher/town-master/attendance', {
        attendance: attendanceRecords,
      });

      if (response.data.success) {
        toast.success(
          `Attendance recorded successfully! ${response.data.absent_count || 0} absent`
        );
        if (response.data.absent_count > 0) {
          toast.info('Notifications sent to parents of absent students');
        }
      }
    } catch (error) {
      console.error('Error recording attendance:', error);
      toast.error(error.response?.data?.message || 'Failed to record attendance');
    } finally {
      setSaving(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'present':
        return 'bg-green-100 text-green-800';
      case 'absent':
        return 'bg-red-100 text-red-800';
      case 'late':
        return 'bg-yellow-100 text-yellow-800';
      case 'excused':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const summary = {
    total: students.length,
    present: Object.values(attendance).filter((a) => a.status === 'present').length,
    absent: Object.values(attendance).filter((a) => a.status === 'absent').length,
    late: Object.values(attendance).filter((a) => a.status === 'late').length,
    excused: Object.values(attendance).filter((a) => a.status === 'excused').length,
  };

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <CheckSquare className="w-5 h-5" />
            Roll Call / Attendance
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Date and Block Filter */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>Date</Label>
              <div className="relative">
                <input
                  type="date"
                  value={selectedDate}
                  onChange={(e) => setSelectedDate(e.target.value)}
                  className="w-full px-3 py-2 border rounded-md"
                  max={new Date().toISOString().split('T')[0]}
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label>Block Filter</Label>
              <Select value={selectedBlock} onValueChange={setSelectedBlock}>
                <SelectTrigger>
                  <SelectValue placeholder="All Blocks" />
                </SelectTrigger>
                <SelectContent>
                  {townData.blocks?.map((block) => (
                    <SelectItem key={block.id} value={block.id.toString()}>
                      Block {block.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Quick Actions</Label>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    const allPresent = {};
                    students.forEach((s) => {
                      allPresent[s.student_block_id] = { status: 'present', notes: '' };
                    });
                    setAttendance(allPresent);
                  }}
                >
                  Mark All Present
                </Button>
              </div>
            </div>
          </div>

          {/* Summary Cards */}
          <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div className="p-3 border rounded-lg bg-gray-50">
              <div className="text-sm text-gray-600">Total</div>
              <div className="text-2xl font-bold">{summary.total}</div>
            </div>
            <div className="p-3 border rounded-lg bg-green-50">
              <div className="text-sm text-green-600">Present</div>
              <div className="text-2xl font-bold text-green-700">{summary.present}</div>
            </div>
            <div className="p-3 border rounded-lg bg-red-50">
              <div className="text-sm text-red-600">Absent</div>
              <div className="text-2xl font-bold text-red-700">{summary.absent}</div>
            </div>
            <div className="p-3 border rounded-lg bg-yellow-50">
              <div className="text-sm text-yellow-600">Late</div>
              <div className="text-2xl font-bold text-yellow-700">{summary.late}</div>
            </div>
            <div className="p-3 border rounded-lg bg-blue-50">
              <div className="text-sm text-blue-600">Excused</div>
              <div className="text-2xl font-bold text-blue-700">{summary.excused}</div>
            </div>
          </div>

          {/* Attendance Table */}
          {loading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
            </div>
          ) : students.length > 0 ? (
            <div className="border rounded-lg">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Block</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Notes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {students.map((student) => (
                    <TableRow key={student.student_block_id}>
                      <TableCell className="font-medium">{student.id_number}</TableCell>
                      <TableCell>
                        {student.first_name && student.last_name
                          ? `${student.first_name} ${student.last_name}`
                          : student.name}
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">Block {student.block_name}</Badge>
                      </TableCell>
                      <TableCell>
                        <Select
                          value={attendance[student.student_block_id]?.status || 'present'}
                          onValueChange={(value) =>
                            handleStatusChange(student.student_block_id, value)
                          }
                        >
                          <SelectTrigger className="w-32">
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="present">Present</SelectItem>
                            <SelectItem value="absent">Absent</SelectItem>
                            <SelectItem value="late">Late</SelectItem>
                            <SelectItem value="excused">Excused</SelectItem>
                          </SelectContent>
                        </Select>
                      </TableCell>
                      <TableCell>
                        <Textarea
                          value={attendance[student.student_block_id]?.notes || ''}
                          onChange={(e) =>
                            handleNotesChange(student.student_block_id, e.target.value)
                          }
                          placeholder="Optional notes..."
                          className="w-full h-10 text-sm"
                        />
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">
              <AlertCircle className="w-12 h-12 mx-auto mb-2 opacity-50" />
              <p>No students found. Register students first.</p>
            </div>
          )}

          {/* Submit Button */}
          {students.length > 0 && (
            <div className="flex justify-end gap-3">
              <Button onClick={handleSubmit} disabled={saving} size="lg">
                <Save className="w-4 h-4 mr-2" />
                {saving ? 'Saving...' : 'Save Attendance'}
              </Button>
            </div>
          )}

          {summary.absent > 0 && (
            <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg">
              <div className="flex items-start gap-2">
                <AlertCircle className="w-5 h-5 text-amber-600 mt-0.5" />
                <div className="text-sm text-amber-800">
                  <strong>Note:</strong> {summary.absent} student(s) marked as absent. Parents will be
                  automatically notified. Students with 3 consecutive absences will trigger urgent
                  notifications to the principal.
                </div>
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default TownAttendance;
