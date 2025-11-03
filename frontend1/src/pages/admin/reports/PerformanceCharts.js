import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';

const PerformanceCharts = () => {
  const [classPerformance, setClassPerformance] = useState([]);
  const [subjectPerformance, setSubjectPerformance] = useState([]);
  const [topPerformers, setTopPerformers] = useState([]);
  const [selectedTerm, setSelectedTerm] = useState('1');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchPerformanceData();
  }, [selectedTerm]);

  const fetchPerformanceData = async () => {
    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      const params = {
        academic_year_id: currentAcademicYear?.id,
        term: selectedTerm
      };

      const headers = { Authorization: `Bearer ${token}` };

      const [classRes, subjectRes, topRes] = await Promise.all([
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/reports/class-performance`, { params, headers }),
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/reports/subject-performance`, { params, headers }),
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/reports/top-performers`, { params: { ...params, limit: 10 }, headers })
      ]);

      if (classRes.data.success) setClassPerformance(classRes.data.performance);
      if (subjectRes.data.success) setSubjectPerformance(subjectRes.data.performance);
      if (topRes.data.success) setTopPerformers(topRes.data.topPerformers);

      setLoading(false);
    } catch (error) {
      console.error('Error fetching performance data:', error);
      toast.error('Failed to load performance data');
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold">Performance Reports</h2>
        <div className="space-y-2 w-48">
          <Label htmlFor="term">Select Term</Label>
          <Select value={selectedTerm} onValueChange={setSelectedTerm}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="1">1st Term</SelectItem>
              <SelectItem value="2">2nd Term</SelectItem>
              <SelectItem value="3">3rd Term</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Class Performance */}
        <Card>
          <CardHeader>
            <CardTitle>Class Performance</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {loading ? (
                <p className="text-gray-500">Loading...</p>
              ) : classPerformance.length === 0 ? (
                <p className="text-gray-500">No data available</p>
              ) : (
                classPerformance.map((cls) => (
                  <div key={cls.class_id} className="flex justify-between items-center p-3 border rounded-lg">
                    <div>
                      <p className="font-medium">{cls.class_name}</p>
                      <p className="text-sm text-gray-600">{cls.total_students} students</p>
                    </div>
                    <div className="text-right">
                      <p className="text-lg font-bold text-blue-600">
                        {cls.average_percentage?.toFixed(1)}%
                      </p>
                      <p className="text-sm text-gray-600">Avg</p>
                    </div>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>

        {/* Top Performers */}
        <Card>
          <CardHeader>
            <CardTitle>Top 10 Performers</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {loading ? (
                <p className="text-gray-500">Loading...</p>
              ) : topPerformers.length === 0 ? (
                <p className="text-gray-500">No data available</p>
              ) : (
                topPerformers.map((student, index) => (
                  <div key={student.student_id} className="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                    <div className="flex items-center gap-3">
                      <div className={`w-8 h-8 rounded-full flex items-center justify-center font-bold ${
                        index === 0 ? 'bg-yellow-400' :
                        index === 1 ? 'bg-gray-300' :
                        index === 2 ? 'bg-orange-400' :
                        'bg-gray-100'
                      }`}>
                        {index + 1}
                      </div>
                      <div>
                        <p className="font-medium">{student.student_name}</p>
                        <p className="text-sm text-gray-600">{student.class_name}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="font-bold text-green-600">{student.percentage?.toFixed(1)}%</p>
                      <p className="text-sm text-gray-600">Grade {student.grade}</p>
                    </div>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Subject Performance */}
      <Card>
        <CardHeader>
          <CardTitle>Subject Performance</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-2">Subject</th>
                  <th className="text-right py-2">Students</th>
                  <th className="text-right py-2">Avg %</th>
                  <th className="text-right py-2">Highest</th>
                  <th className="text-right py-2">Lowest</th>
                  <th className="text-right py-2">Grade A</th>
                  <th className="text-right py-2">Grade F</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="7" className="text-center py-4 text-gray-500">Loading...</td>
                  </tr>
                ) : subjectPerformance.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="text-center py-4 text-gray-500">No data available</td>
                  </tr>
                ) : (
                  subjectPerformance.map((subject) => (
                    <tr key={subject.subject_id} className="border-b hover:bg-gray-50">
                      <td className="py-3 font-medium">{subject.subject_name}</td>
                      <td className="text-right py-3">{subject.total_students}</td>
                      <td className="text-right py-3 font-semibold text-blue-600">
                        {subject.average_percentage?.toFixed(1)}%
                      </td>
                      <td className="text-right py-3 text-green-600">
                        {subject.highest_score?.toFixed(1)}%
                      </td>
                      <td className="text-right py-3 text-red-600">
                        {subject.lowest_score?.toFixed(1)}%
                      </td>
                      <td className="text-right py-3">{subject.grade_a_count || 0}</td>
                      <td className="text-right py-3">{subject.grade_f_count || 0}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default PerformanceCharts;
