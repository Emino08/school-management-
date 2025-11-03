import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';

const BehaviorReports = () => {
  const [behaviorData, setBehaviorData] = useState({ complaints: [], summary: [] });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchBehaviorReports();
  }, []);

  const fetchBehaviorReports = async () => {
    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/reports/behavior`,
        {
          params: { academic_year_id: currentAcademicYear?.id },
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      if (response.data.success) {
        setBehaviorData(response.data);
      }
      setLoading(false);
    } catch (error) {
      console.error('Error fetching behavior reports:', error);
      toast.error('Failed to load behavior reports');
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold">Behavior Reports</h2>

      {/* Summary by Class */}
      <Card>
        <CardHeader>
          <CardTitle>Complaints by Class</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {loading ? (
              <p className="text-gray-500">Loading...</p>
            ) : behaviorData.summary.length === 0 ? (
              <p className="text-gray-500">No complaints recorded</p>
            ) : (
              behaviorData.summary.map((cls) => (
                <div key={cls.class_name} className="p-4 border rounded-lg">
                  <p className="font-medium">{cls.class_name}</p>
                  <p className="text-2xl font-bold text-red-600">{cls.complaint_count}</p>
                  <p className="text-sm text-gray-600">complaints</p>
                </div>
              ))
            )}
          </div>
        </CardContent>
      </Card>

      {/* Recent Complaints */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Complaints</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-2">Date</th>
                  <th className="text-left py-2">Student</th>
                  <th className="text-left py-2">Class</th>
                  <th className="text-left py-2">Complaint</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="4" className="text-center py-4 text-gray-500">Loading...</td>
                  </tr>
                ) : behaviorData.complaints.length === 0 ? (
                  <tr>
                    <td colSpan="4" className="text-center py-4 text-gray-500">No complaints found</td>
                  </tr>
                ) : (
                  behaviorData.complaints.map((complaint) => (
                    <tr key={complaint.id} className="border-b hover:bg-gray-50">
                      <td className="py-3">{new Date(complaint.date).toLocaleDateString()}</td>
                      <td className="py-3 font-medium">{complaint.student_name}</td>
                      <td className="py-3">{complaint.class_name}</td>
                      <td className="py-3 text-gray-600">{complaint.complaint}</td>
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

export default BehaviorReports;
