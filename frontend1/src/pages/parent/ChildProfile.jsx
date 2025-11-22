import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import axios from 'axios';
import BackButton from '@/components/BackButton';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080/api';

const ChildProfile = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [child, setChild] = useState(null);
  const [attendance, setAttendance] = useState([]);
  const [results, setResults] = useState([]);
  const [medicalRecords, setMedicalRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview');

  useEffect(() => {
    fetchChildData();
  }, [id]);

  const fetchChildData = async () => {
    try {
      const token = localStorage.getItem('parent_token');
      const config = { headers: { Authorization: `Bearer ${token}` } };

      // Fetch child details
      const childrenRes = await axios.get(`${API_URL}/parents/children`, config);
      const childData = childrenRes.data.children.find(c => c.id === parseInt(id));
      setChild(childData);

      // Fetch attendance
      const attendanceRes = await axios.get(`${API_URL}/parents/children/${id}/attendance`, config);
      setAttendance(attendanceRes.data.attendance || []);

      // Fetch results
      const resultsRes = await axios.get(`${API_URL}/parents/children/${id}/results`, config);
      setResults(resultsRes.data.results || []);

      // Fetch medical records (if available)
      try {
        const medicalRes = await axios.get(`${API_URL}/medical/records/student/${id}`, config);
        setMedicalRecords(medicalRes.data.records || []);
      } catch (err) {
        // Medical records might not be available
        setMedicalRecords([]);
      }

      setLoading(false);
    } catch (error) {
      console.error('Error fetching child data:', error);
      setLoading(false);
    }
  };

  const calculateAttendanceRate = () => {
    if (attendance.length === 0) return 0;
    const present = attendance.filter(a => a.status === 'present').length;
    return ((present / attendance.length) * 100).toFixed(1);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  if (!child) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900">Child not found</h2>
          <button
            onClick={() => navigate('/parent/dashboard')}
            className="mt-4 text-indigo-600 hover:text-indigo-700"
          >
            Back to Dashboard
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center space-x-4">
            <BackButton to="/parent/dashboard" label="Back to Dashboard" variant="outline" className="mb-0" />
            <h1 className="text-xl font-bold text-gray-900">Student Profile</h1>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Student Info Card */}
        <div className="bg-white rounded-lg shadow mb-6">
          <div className="p-6">
            <div className="flex items-center space-x-6">
              <div className="w-24 h-24 bg-indigo-100 rounded-full flex items-center justify-center">
                <span className="text-indigo-600 font-bold text-3xl">
                  {child.name?.charAt(0).toUpperCase()}
                </span>
              </div>
              <div className="flex-1">
                <h2 className="text-2xl font-bold text-gray-900">{child.name}</h2>
                <div className="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                  <div>
                    <p className="text-gray-500">Student ID</p>
                    <p className="font-semibold text-gray-900">{child.id_number}</p>
                  </div>
                  <div>
                    <p className="text-gray-500">Class</p>
                    <p className="font-semibold text-gray-900">{child.class_name} {child.section}</p>
                  </div>
                  <div>
                    <p className="text-gray-500">House</p>
                    <p className="font-semibold text-gray-900">{child.house_name || 'Not Assigned'}</p>
                  </div>
                  <div>
                    <p className="text-gray-500">Status</p>
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                      child.suspension_status === 'active' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                    }`}>
                      {child.suspension_status === 'active' ? 'Suspended' : 'Active'}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Stats Row */}
          <div className="border-t border-gray-200 grid grid-cols-3 divide-x divide-gray-200">
            <div className="px-6 py-4 text-center">
              <p className="text-2xl font-bold text-indigo-600">{calculateAttendanceRate()}%</p>
              <p className="text-sm text-gray-600 mt-1">Attendance Rate</p>
            </div>
            <div className="px-6 py-4 text-center">
              <p className="text-2xl font-bold text-green-600">{results.length}</p>
              <p className="text-sm text-gray-600 mt-1">Exam Results</p>
            </div>
            <div className="px-6 py-4 text-center">
              <p className="text-2xl font-bold text-blue-600">{medicalRecords.length}</p>
              <p className="text-sm text-gray-600 mt-1">Medical Records</p>
            </div>
          </div>
        </div>

        {/* Tabs */}
        <div className="bg-white rounded-lg shadow mb-6">
          <div className="border-b border-gray-200">
            <nav className="flex -mb-px">
              {[
                { value: 'overview', label: 'Overview' },
                { value: 'attendance', label: 'Attendance' },
                { value: 'results', label: 'Results' },
                { value: 'medical', label: 'Medical' }
              ].map((tab) => (
                <button
                  key={tab.value}
                  onClick={() => setActiveTab(tab.value)}
                  className={`px-6 py-3 text-sm font-medium ${
                    activeTab === tab.value
                      ? 'border-b-2 border-indigo-600 text-indigo-600'
                      : 'text-gray-500 hover:text-gray-700'
                  }`}
                >
                  {tab.label}
                </button>
              ))}
            </nav>
          </div>

          <div className="p-6">
            {activeTab === 'overview' && (
              <div className="space-y-6">
                <div>
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <p className="text-sm text-gray-500">Date of Birth</p>
                      <p className="font-medium text-gray-900">{child.date_of_birth}</p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Blood Group</p>
                      <p className="font-medium text-gray-900">{child.blood_group || 'Not Specified'}</p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Registration Status</p>
                      <p className="font-medium text-gray-900">
                        {child.is_registered ? 'Registered' : 'Not Registered'}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Medical Condition</p>
                      <p className="font-medium text-gray-900">
                        {child.has_medical_condition ? 'Yes' : 'None'}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'attendance' && (
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Attendance Records</h3>
                {attendance.length === 0 ? (
                  <p className="text-gray-500 text-center py-8">No attendance records yet</p>
                ) : (
                  <div className="space-y-2">
                    {attendance.slice(0, 10).map((record, index) => (
                      <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div className="flex items-center space-x-3">
                          <span className={`w-3 h-3 rounded-full ${
                            record.status === 'present' ? 'bg-green-500' :
                            record.status === 'absent' ? 'bg-red-500' : 'bg-yellow-500'
                          }`}></span>
                          <span className="font-medium text-gray-900">
                            {new Date(record.date).toLocaleDateString()}
                          </span>
                        </div>
                        <span className={`text-sm font-medium ${
                          record.status === 'present' ? 'text-green-600' :
                          record.status === 'absent' ? 'text-red-600' : 'text-yellow-600'
                        }`}>
                          {record.status.charAt(0).toUpperCase() + record.status.slice(1)}
                        </span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {activeTab === 'results' && (
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Exam Results</h3>
                {results.length === 0 ? (
                  <p className="text-gray-500 text-center py-8">No exam results yet</p>
                ) : (
                  <div className="space-y-4">
                    {results.map((result, index) => (
                      <div key={index} className="border border-gray-200 rounded-lg p-4">
                        <div className="flex justify-between items-start mb-2">
                          <div>
                            <h4 className="font-semibold text-gray-900">{result.exam_name}</h4>
                            <p className="text-sm text-gray-600">{result.subject}</p>
                          </div>
                          <span className={`text-2xl font-bold ${
                            result.marks >= 80 ? 'text-green-600' :
                            result.marks >= 60 ? 'text-blue-600' :
                            result.marks >= 40 ? 'text-yellow-600' : 'text-red-600'
                          }`}>
                            {result.marks}%
                          </span>
                        </div>
                        <div className="flex justify-between text-sm text-gray-500">
                          <span>Grade: {result.grade}</span>
                          <span>{new Date(result.exam_date).toLocaleDateString()}</span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {activeTab === 'medical' && (
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Medical Records</h3>
                {medicalRecords.length === 0 ? (
                  <p className="text-gray-500 text-center py-8">No medical records</p>
                ) : (
                  <div className="space-y-4">
                    {medicalRecords.map((record, index) => (
                      <div key={index} className="border border-gray-200 rounded-lg p-4">
                        <div className="flex justify-between items-start">
                          <div>
                            <h4 className="font-semibold text-gray-900">{record.diagnosis}</h4>
                            <p className="text-sm text-gray-600 mt-1">{record.symptoms}</p>
                            <p className="text-sm text-gray-600 mt-2">
                              <strong>Treatment:</strong> {record.treatment}
                            </p>
                          </div>
                          <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                            record.status === 'completed' ? 'bg-green-100 text-green-800' :
                            record.status === 'under_treatment' ? 'bg-blue-100 text-blue-800' :
                            'bg-yellow-100 text-yellow-800'
                          }`}>
                            {record.status.replace('_', ' ').toUpperCase()}
                          </span>
                        </div>
                        <div className="mt-3 flex justify-between text-xs text-gray-500">
                          <span>Severity: {record.severity}</span>
                          <span>{new Date(record.created_at).toLocaleDateString()}</span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
};

export default ChildProfile;
