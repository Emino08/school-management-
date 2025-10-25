import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { useSelector } from 'react-redux';
import { toast } from 'sonner';

const TeacherAttendance = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [subjects, setSubjects] = useState([]);
  const [selectedSubject, setSelectedSubject] = useState('');
  const [attendanceData, setAttendanceData] = useState([]);
  const [loading, setLoading] = useState(false);

  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';
  const teacherId = currentUser?.teacher?.id || currentUser?.id;

  useEffect(() => {
    if (teacherId) {
      fetchSubjects();
    }
  }, [teacherId]);

  useEffect(() => {
    if (selectedSubject) {
      fetchAttendance();
    }
  }, [selectedSubject]);

  const fetchSubjects = async () => {
    try {
      const res = await axios.get(`${API_URL}/teachers/${teacherId}/subjects`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (res.data?.success) {
        setSubjects(res.data.subjects || []);
      }
    } catch (error) {
      console.error('Failed to fetch subjects:', error);
      toast.error('Failed to load subjects');
    }
  };

  const fetchAttendance = async () => {
    if (!teacherId || !selectedSubject) return;

    setLoading(true);
    try {
      const res = await axios.get(`${API_URL}/teachers/${teacherId}/subjects/${selectedSubject}/attendance`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (res.data?.success) {
        setAttendanceData(res.data.attendance || []);
      }
    } catch (error) {
      console.error('Failed to fetch attendance:', error);
      toast.error('Failed to load attendance data');
    } finally {
      setLoading(false);
    }
  };

  const handleAttendanceChange = async (studentId, status) => {
    if (!teacherId || !selectedSubject) return;

    try {
      const res = await axios.post(
        `${API_URL}/teachers/${teacherId}/subjects/${selectedSubject}/attendance`,
        {
          student_id: studentId,
          status: status,
          date: new Date().toISOString().split('T')[0]
        },
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` }
        }
      );

      if (res.data?.success) {
        toast.success('Attendance marked successfully');
        fetchAttendance();
      }
    } catch (error) {
      console.error('Failed to mark attendance:', error);
      toast.error('Failed to mark attendance');
    }
  };

  return (
    <div className="container mx-auto p-6">
      <Card>
        <CardHeader>
          <CardTitle>Student Attendance</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="mb-6">
            <Label htmlFor="subject">Select Subject</Label>
            <Select value={selectedSubject} onValueChange={setSelectedSubject}>
              <SelectTrigger id="subject">
                <SelectValue placeholder="Choose a subject..." />
              </SelectTrigger>
              <SelectContent>
                {subjects.map((subject) => (
                  <SelectItem key={subject.id} value={subject.id}>
                    {subject.subject_name} - {subject.class_name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {selectedSubject && (
            <>
              {loading ? (
                <div className="text-center py-8">Loading attendance data...</div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full border-collapse border border-gray-300">
                    <thead>
                      <tr className="bg-gray-100">
                        <th className="border border-gray-300 px-4 py-2 text-left">No</th>
                        <th className="border border-gray-300 px-4 py-2 text-left">Name</th>
                        <th className="border border-gray-300 px-4 py-2 text-center">
                          Total Attendance for This Class
                        </th>
                        <th className="border border-gray-300 px-4 py-2 text-center">Percentage</th>
                        <th className="border border-gray-300 px-4 py-2 text-center">
                          Today Attendance (Present / Absent)
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {attendanceData.length === 0 ? (
                        <tr>
                          <td colSpan="5" className="border border-gray-300 px-4 py-8 text-center text-gray-500">
                            No students found for this subject
                          </td>
                        </tr>
                      ) : (
                        attendanceData.map((student, index) => (
                          <tr key={student.student_id} className="hover:bg-gray-50">
                            <td className="border border-gray-300 px-4 py-2">{index + 1}</td>
                            <td className="border border-gray-300 px-4 py-2">{student.student_name}</td>
                            <td className="border border-gray-300 px-4 py-2 text-center">
                              {student.total_present || 0} / {student.total_classes || 0}
                            </td>
                            <td className="border border-gray-300 px-4 py-2 text-center">
                              <span
                                className={`font-medium ${
                                  parseFloat(student.attendance_percentage || 0) >= 75
                                    ? 'text-green-600'
                                    : parseFloat(student.attendance_percentage || 0) >= 50
                                    ? 'text-yellow-600'
                                    : 'text-red-600'
                                }`}
                              >
                                {student.attendance_percentage || 0}%
                              </span>
                            </td>
                            <td className="border border-gray-300 px-4 py-2 text-center">
                              <div className="flex gap-2 justify-center">
                                <Button
                                  size="sm"
                                  variant={student.today_status === 'present' ? 'default' : 'outline'}
                                  onClick={() => handleAttendanceChange(student.student_id, 'present')}
                                  className={
                                    student.today_status === 'present'
                                      ? 'bg-green-600 hover:bg-green-700'
                                      : ''
                                  }
                                >
                                  Present
                                </Button>
                                <Button
                                  size="sm"
                                  variant={student.today_status === 'absent' ? 'default' : 'outline'}
                                  onClick={() => handleAttendanceChange(student.student_id, 'absent')}
                                  className={
                                    student.today_status === 'absent'
                                      ? 'bg-red-600 hover:bg-red-700'
                                      : ''
                                  }
                                >
                                  Absent
                                </Button>
                              </div>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default TeacherAttendance;
