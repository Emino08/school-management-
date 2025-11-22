import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useSelector } from 'react-redux';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';
import { FileDown, Upload } from 'lucide-react';

const TeacherSubmitGrades = () => {
  const { currentUser } = useSelector((state) => state.user);
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  const teacherId = currentUser?.teacher?.id || currentUser?.id;

  const [subjects, setSubjects] = useState([]);
  const [students, setStudents] = useState([]);
  const [exams, setExams] = useState([]);
  const [selectedSubject, setSelectedSubject] = useState('');
  const [selectedClassId, setSelectedClassId] = useState('');
  const [selectedExam, setSelectedExam] = useState('');
  const [gradesData, setGradesData] = useState({});
  const [loading, setLoading] = useState(false);
  const [bulkFile, setBulkFile] = useState(null);

  // Fetch teacher's subjects
  useEffect(() => {
    const fetch = async () => {
      try {
        const res = await axios.get(`${API_URL}/teachers/${teacherId}/subjects`);
        if (res.data?.success) setSubjects(res.data.subjects || []);
      } catch (e) {
        console.error('Failed to load subjects', e);
        toast.error('Failed to load subjects');
      }
    };
    if (teacherId) fetch();
  }, [teacherId]);

  // When subject changes, fetch students with attendance and grades
  useEffect(() => {
    const fetchStudentsData = async () => {
      if (!selectedSubject || !teacherId) return;
      try {
        setLoading(true);
        // Fetch students with attendance data
        const res = await axios.get(`${API_URL}/teachers/${teacherId}/subjects/${selectedSubject}/students`, {
          headers: { Authorization: `Bearer ${currentUser?.token}` }
        });
        if (res.data?.success) {
          setStudents(res.data.students || []);
          // Initialize grades data
          const initialGrades = {};
          (res.data.students || []).forEach(student => {
            initialGrades[student.student_id] = {
              marks_obtained: student.marks_obtained || '',
              test_score: student.test_score || '',
              exam_score: student.exam_score || '',
              remarks: student.remarks || ''
            };
          });
          setGradesData(initialGrades);
        }

        // Fetch exams for the class
        const subjObj = subjects.find(s => String(s.id) === String(selectedSubject));
        if (subjObj?.class_id) {
          const examRes = await axios.get(`${API_URL}/exams`, {
            params: { class_id: subjObj.class_id }
          });
          if (examRes.data?.success) setExams(examRes.data.exams || []);
        }
      } catch (e) {
        console.error('Failed loading student data', e);
        toast.error('Failed to load students data');
      } finally {
        setLoading(false);
      }
    };
    fetchStudentsData();
  }, [selectedSubject, teacherId]);

  const onSubjectChange = (val) => {
    setSelectedSubject(val);
    const subj = subjects.find(s => String(s.id) === String(val));
    setSelectedClassId(subj?.class_id ? String(subj.class_id) : '');
  };

  const downloadTemplate = () => {
    const header = "student_id,subject_id,exam_id,marks_obtained,test_score,exam_score,remarks";
    const sample = "123,45,7,78,20,58,Good work";
    const blob = new Blob([`${header}\n${sample}\n`], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "grade_submission_template.csv";
    a.click();
    URL.revokeObjectURL(url);
  };

  const handleBulkUpload = async () => {
    if (!bulkFile) {
      toast.error('Select a CSV file first');
      return;
    }
    if (!selectedSubject || !selectedExam) {
      toast.error('Select subject and exam before uploading');
      return;
    }
    setLoading(true);
    try {
      const text = await bulkFile.text();
      const lines = text.split(/\r?\n/).filter(Boolean);
      const [headerLine, ...rows] = lines;
      const headers = headerLine.split(',').map(h => h.trim());
      const needed = ['student_id', 'marks_obtained'];
      if (!needed.every(n => headers.includes(n))) {
        throw new Error('CSV must include student_id and marks_obtained columns');
      }
      const idx = Object.fromEntries(headers.map((h, i) => [h, i]));
      let success = 0;
      let failed = 0;
      for (const row of rows) {
        const cols = row.split(',').map(c => c.trim());
        if (cols.length < headers.length) continue;
        const payload = {
          student_id: Number(cols[idx['student_id']]),
          subject_id: Number(selectedSubject),
          exam_id: Number(selectedExam),
          marks_obtained: Number(cols[idx['marks_obtained']]),
        };
        if (idx['test_score'] !== undefined && cols[idx['test_score']]) payload.test_score = Number(cols[idx['test_score']]);
        if (idx['exam_score'] !== undefined && cols[idx['exam_score']]) payload.exam_score = Number(cols[idx['exam_score']]);
        if (idx['remarks'] !== undefined && cols[idx['remarks']]) payload.remarks = cols[idx['remarks']];
        try {
          await axios.post(`${API_URL}/exams/results`, payload, {
            headers: { 'Content-Type': 'application/json' }
          });
          success++;
        } catch (e) {
          failed++;
        }
      }
      toast.success(`Upload complete: ${success} ok, ${failed} failed`);
      // refresh students/grids
      setSelectedSubject(selectedSubject);
    } catch (error) {
      toast.error(error.message || 'Bulk upload failed');
    } finally {
      setLoading(false);
    }
  };

  const handleGradeChange = (studentId, field, value) => {
    setGradesData(prev => ({
      ...prev,
      [studentId]: {
        ...prev[studentId],
        [field]: value
      }
    }));
  };

  const submitGradeForStudent = async (studentId) => {
    if (!selectedSubject || !selectedExam) {
      toast.error('Please select subject and exam');
      return;
    }

    const gradeData = gradesData[studentId];
    if (!gradeData?.marks_obtained) {
      toast.error('Please enter marks obtained');
      return;
    }

    setLoading(true);
    try {
      const payload = {
        student_id: Number(studentId),
        subject_id: Number(selectedSubject),
        exam_id: Number(selectedExam),
        marks_obtained: Number(gradeData.marks_obtained),
      };
      if (gradeData.test_score !== '') payload.test_score = Number(gradeData.test_score);
      if (gradeData.exam_score !== '') payload.exam_score = Number(gradeData.exam_score);
      if (gradeData.remarks) payload.remarks = gradeData.remarks;

      await axios.post(`${API_URL}/exams/results`, payload, {
        headers: { 'Content-Type': 'application/json' }
      });
      toast.success('Grade submitted for approval');
    } catch (e) {
      console.error('Submit failed', e);
      toast.error(e.response?.data?.message || 'Failed to submit');
    } finally {
      setLoading(false);
    }
  };

  const submitAllGrades = async () => {
    if (!selectedSubject || !selectedExam) {
      toast.error('Please select subject and exam');
      return;
    }

    const studentsWithGrades = students.filter(s => gradesData[s.student_id]?.marks_obtained);
    if (studentsWithGrades.length === 0) {
      toast.error('Please enter at least one grade');
      return;
    }

    setLoading(true);
    let successCount = 0;
    let errorCount = 0;

    for (const student of studentsWithGrades) {
      try {
        const gradeData = gradesData[student.student_id];
        const payload = {
          student_id: Number(student.student_id),
          subject_id: Number(selectedSubject),
          exam_id: Number(selectedExam),
          marks_obtained: Number(gradeData.marks_obtained),
        };
        if (gradeData.test_score !== '') payload.test_score = Number(gradeData.test_score);
        if (gradeData.exam_score !== '') payload.exam_score = Number(gradeData.exam_score);
        if (gradeData.remarks) payload.remarks = gradeData.remarks;

        await axios.post(`${API_URL}/exams/results`, payload, {
          headers: { 'Content-Type': 'application/json' }
        });
        successCount++;
      } catch (e) {
        console.error('Submit failed for student', student.student_id, e);
        errorCount++;
      }
    }

    setLoading(false);
    if (successCount > 0) {
      toast.success(`Successfully submitted ${successCount} grade(s)`);
    }
    if (errorCount > 0) {
      toast.error(`Failed to submit ${errorCount} grade(s)`);
    }
  };

  return (
    <div className="container mx-auto p-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl flex items-center justify-between">
            <span>Submit Grades</span>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={downloadTemplate} className="flex items-center gap-1">
                <FileDown className="w-4 h-4" /> Template
              </Button>
              <Button
                variant="outline"
                size="sm"
                className="flex items-center gap-1"
                onClick={() => document.getElementById('bulk-upload-input')?.click()}
              >
                <Upload className="w-4 h-4" /> Import CSV
              </Button>
              <input
                id="bulk-upload-input"
                type="file"
                accept=".csv,text/csv"
                className="hidden"
                onChange={(e) => setBulkFile(e.target.files?.[0] || null)}
              />
              <Button size="sm" disabled={loading || !bulkFile} onClick={handleBulkUpload}>
                {loading ? 'Uploading...' : 'Upload'}
              </Button>
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="subject">Select Subject</Label>
              <Select value={selectedSubject} onValueChange={onSubjectChange}>
                <SelectTrigger id="subject">
                  <SelectValue placeholder="Choose a subject..." />
                </SelectTrigger>
                <SelectContent>
                  {subjects.map(s => (
                    <SelectItem key={s.id} value={String(s.id)}>
                      {s.subject_name} - {s.class_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label htmlFor="exam">Select Exam</Label>
              <Select value={selectedExam} onValueChange={setSelectedExam}>
                <SelectTrigger id="exam">
                  <SelectValue placeholder="Choose an exam..." />
                </SelectTrigger>
                <SelectContent>
                  {exams.map(ex => (
                    <SelectItem key={ex.id} value={String(ex.id)}>
                      {ex.exam_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {selectedSubject && (
            <>
              {loading ? (
                <div className="text-center py-8">Loading students data...</div>
              ) : (
                <>
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
                            Marks Obtained
                          </th>
                          <th className="border border-gray-300 px-4 py-2 text-center">
                            Test Score
                          </th>
                          <th className="border border-gray-300 px-4 py-2 text-center">
                            Exam Score
                          </th>
                          <th className="border border-gray-300 px-4 py-2 text-center">
                            Remarks
                          </th>
                          <th className="border border-gray-300 px-4 py-2 text-center">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        {students.length === 0 ? (
                          <tr>
                            <td colSpan="9" className="border border-gray-300 px-4 py-8 text-center text-gray-500">
                              No students found for this subject
                            </td>
                          </tr>
                        ) : (
                          students.map((student, index) => (
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
                              <td className="border border-gray-300 px-2 py-2">
                                <Input
                                  type="number"
                                  placeholder="0"
                                  className="w-20"
                                  value={gradesData[student.student_id]?.marks_obtained || ''}
                                  onChange={(e) => handleGradeChange(student.student_id, 'marks_obtained', e.target.value)}
                                />
                              </td>
                              <td className="border border-gray-300 px-2 py-2">
                                <Input
                                  type="number"
                                  placeholder="0"
                                  className="w-20"
                                  value={gradesData[student.student_id]?.test_score || ''}
                                  onChange={(e) => handleGradeChange(student.student_id, 'test_score', e.target.value)}
                                />
                              </td>
                              <td className="border border-gray-300 px-2 py-2">
                                <Input
                                  type="number"
                                  placeholder="0"
                                  className="w-20"
                                  value={gradesData[student.student_id]?.exam_score || ''}
                                  onChange={(e) => handleGradeChange(student.student_id, 'exam_score', e.target.value)}
                                />
                              </td>
                              <td className="border border-gray-300 px-2 py-2">
                                <Input
                                  type="text"
                                  placeholder="Remarks"
                                  className="w-32"
                                  value={gradesData[student.student_id]?.remarks || ''}
                                  onChange={(e) => handleGradeChange(student.student_id, 'remarks', e.target.value)}
                                />
                              </td>
                              <td className="border border-gray-300 px-2 py-2 text-center">
                                <Button
                                  size="sm"
                                  onClick={() => submitGradeForStudent(student.student_id)}
                                  disabled={loading || !gradesData[student.student_id]?.marks_obtained}
                                >
                                  Submit
                                </Button>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                  {students.length > 0 && (
                    <div className="mt-4 flex justify-end">
                      <Button
                        onClick={submitAllGrades}
                        disabled={loading}
                        className="bg-blue-600 hover:bg-blue-700"
                      >
                        {loading ? 'Submitting...' : 'Submit All Grades'}
                      </Button>
                    </div>
                  )}
                </>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default TeacherSubmitGrades;
