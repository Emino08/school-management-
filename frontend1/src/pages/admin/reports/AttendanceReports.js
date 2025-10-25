import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from 'axios';
import { toast } from 'sonner';

const AttendanceReports = () => {
  const [attendanceSummary, setAttendanceSummary] = useState([]);
  const [loading, setLoading] = useState(true);
  const [dateRange, setDateRange] = useState({
    start_date: '',
    end_date: ''
  });

  useEffect(() => {
    fetchAttendanceSummary();
  }, []);

  const fetchAttendanceSummary = async () => {
    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/reports/attendance-summary`,
        {
          params: {
            academic_year_id: currentAcademicYear?.id,
            ...dateRange
          },
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      if (response.data.success) {
        setAttendanceSummary(response.data.summary);
      }
      setLoading(false);
    } catch (error) {
      console.error('Error fetching attendance summary:', error);
      toast.error('Failed to load attendance summary');
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold">Attendance Reports</h2>
      </div>

      {/* Date Range Filter */}
      <Card>
        <CardHeader>
          <CardTitle>Filter by Date Range</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label htmlFor="start_date">Start Date</Label>
              <Input
                id="start_date"
                type="date"
                value={dateRange.start_date}
                onChange={(e) => setDateRange({ ...dateRange, start_date: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="end_date">End Date</Label>
              <Input
                id="end_date"
                type="date"
                value={dateRange.end_date}
                onChange={(e) => setDateRange({ ...dateRange, end_date: e.target.value })}
              />
            </div>
            <div className="flex items-end">
              <Button onClick={fetchAttendanceSummary} className="w-full">
                Apply Filter
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Attendance Summary by Class */}
      <Card>
        <CardHeader>
          <CardTitle>Attendance by Class</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-2">Class</th>
                  <th className="text-right py-2">Total Students</th>
                  <th className="text-right py-2">Present</th>
                  <th className="text-right py-2">Absent</th>
                  <th className="text-right py-2">Late</th>
                  <th className="text-right py-2">Attendance %</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="6" className="text-center py-4 text-gray-500">Loading...</td>
                  </tr>
                ) : attendanceSummary.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="text-center py-4 text-gray-500">No data available</td>
                  </tr>
                ) : (
                  attendanceSummary.map((cls) => (
                    <tr key={cls.class_id} className="border-b hover:bg-gray-50">
                      <td className="py-3 font-medium">{cls.class_name}</td>
                      <td className="text-right py-3">{cls.total_students}</td>
                      <td className="text-right py-3 text-green-600">{cls.present_count}</td>
                      <td className="text-right py-3 text-red-600">{cls.absent_count}</td>
                      <td className="text-right py-3 text-yellow-600">{cls.late_count}</td>
                      <td className="text-right py-3 font-semibold">
                        <span className={`${
                          parseFloat(cls.attendance_percentage) >= 90 ? 'text-green-600' :
                          parseFloat(cls.attendance_percentage) >= 75 ? 'text-blue-600' :
                          parseFloat(cls.attendance_percentage) >= 60 ? 'text-yellow-600' :
                          'text-red-600'
                        }`}>
                          {cls.attendance_percentage?.toFixed(1)}%
                        </span>
                      </td>
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

export default AttendanceReports;
