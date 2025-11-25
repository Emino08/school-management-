import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import axios from 'axios';
import BackButton from '@/components/BackButton';
import { toast } from 'sonner';

const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

const ChildProfile = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [child, setChild] = useState(null);
  const [attendance, setAttendance] = useState([]);
  const [results, setResults] = useState([]);
  const [medicalRecords, setMedicalRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview');
  const [showMedicalModal, setShowMedicalModal] = useState(false);
  const [editingRecord, setEditingRecord] = useState(null);
  const [medicalFormData, setMedicalFormData] = useState({
    record_type: 'allergy',
    description: '',
    symptoms: '',
    treatment: '',
    medication: '',
    severity: 'low',
    notes: '',
    next_checkup_date: ''
  });

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

      // Fetch medical records
      try {
        const medicalRes = await axios.get(`${API_URL}/parents/children/${id}/medical-records`, config);
        setMedicalRecords(medicalRes.data.medical_records || []);
      } catch (err) {
        console.error('Error fetching medical records:', err);
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

  const handleOpenMedicalModal = (record = null) => {
    if (record) {
      setEditingRecord(record);
      setMedicalFormData({
        record_type: record.record_type || 'allergy',
        description: record.diagnosis || '',
        symptoms: record.symptoms || '',
        treatment: record.treatment || '',
        medication: record.medication || '',
        severity: record.severity || 'low',
        notes: record.notes || '',
        next_checkup_date: record.next_checkup_date || ''
      });
    } else {
      setEditingRecord(null);
      setMedicalFormData({
        record_type: 'allergy',
        description: '',
        symptoms: '',
        treatment: '',
        medication: '',
        severity: 'low',
        notes: '',
        next_checkup_date: ''
      });
    }
    setShowMedicalModal(true);
  };

  const handleCloseMedicalModal = () => {
    setShowMedicalModal(false);
    setEditingRecord(null);
    setMedicalFormData({
      record_type: 'allergy',
      description: '',
      symptoms: '',
      treatment: '',
      medication: '',
      severity: 'low',
      notes: '',
      next_checkup_date: ''
    });
  };

  const handleMedicalFormChange = (e) => {
    const { name, value } = e.target;
    setMedicalFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmitMedical = async (e) => {
    e.preventDefault();
    
    if (!medicalFormData.description.trim()) {
      toast.error('Description is required');
      return;
    }

    try {
      const token = localStorage.getItem('parent_token');
      const config = { headers: { Authorization: `Bearer ${token}` } };
      
      const payload = {
        student_id: parseInt(id),
        ...medicalFormData
      };

      if (editingRecord) {
        // Update existing record
        await axios.put(`${API_URL}/parents/medical-records/${editingRecord.id}`, payload, config);
        toast.success('Medical record updated successfully');
      } else {
        // Add new record
        await axios.post(`${API_URL}/parents/medical-records`, payload, config);
        toast.success('Medical record added successfully');
      }

      handleCloseMedicalModal();
      
      // Refresh medical records
      const medicalRes = await axios.get(`${API_URL}/parents/children/${id}/medical-records`, config);
      setMedicalRecords(medicalRes.data.medical_records || []);
    } catch (error) {
      console.error('Error saving medical record:', error);
      toast.error(error.response?.data?.message || 'Failed to save medical record');
    }
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
                      child.suspension_status === 'active' ? 'bg-green-100 text-green-800' : 
                      child.suspension_status === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'
                    }`}>
                      {child.suspension_status === 'active' ? 'Active' : 
                       child.suspension_status === 'suspended' ? 'Suspended' : 'Expelled'}
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
                <div className="flex justify-between items-center mb-4">
                  <h3 className="text-lg font-semibold text-gray-900">Medical Records</h3>
                  <button
                    onClick={() => handleOpenMedicalModal()}
                    className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center space-x-2"
                  >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Medical Record</span>
                  </button>
                </div>

                {medicalRecords.length === 0 ? (
                  <div className="text-center py-12 bg-gray-50 rounded-lg">
                    <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p className="mt-4 text-gray-500">No medical records yet</p>
                    <p className="mt-2 text-sm text-gray-400">Click "Add Medical Record" to create one</p>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {medicalRecords.map((record) => (
                      <div key={record.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="flex justify-between items-start mb-3">
                          <div className="flex-1">
                            <div className="flex items-center space-x-2 mb-2">
                              <span className={`px-2 py-1 rounded text-xs font-medium ${
                                record.record_type === 'allergy' ? 'bg-red-100 text-red-800' :
                                record.record_type === 'condition' ? 'bg-blue-100 text-blue-800' :
                                record.record_type === 'medication' ? 'bg-green-100 text-green-800' :
                                record.record_type === 'vaccination' ? 'bg-purple-100 text-purple-800' :
                                record.record_type === 'checkup' ? 'bg-cyan-100 text-cyan-800' :
                                record.record_type === 'injury' ? 'bg-orange-100 text-orange-800' :
                                'bg-yellow-100 text-yellow-800'
                              }`}>
                                {record.record_type.toUpperCase()}
                              </span>
                              <span className={`px-2 py-1 rounded text-xs font-medium ${
                                record.severity === 'high' ? 'bg-red-100 text-red-800' :
                                record.severity === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-green-100 text-green-800'
                              }`}>
                                {record.severity.toUpperCase()}
                              </span>
                              {record.added_by_parent === 1 && (
                                <span className="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                  Parent Added
                                </span>
                              )}
                            </div>
                            <h4 className="font-semibold text-gray-900">{record.diagnosis}</h4>
                            {record.symptoms && (
                              <p className="text-sm text-gray-600 mt-1">
                                <strong>Symptoms:</strong> {record.symptoms}
                              </p>
                            )}
                            {record.treatment && (
                              <p className="text-sm text-gray-600 mt-1">
                                <strong>Treatment:</strong> {record.treatment}
                              </p>
                            )}
                            {record.medication && (
                              <p className="text-sm text-gray-600 mt-1">
                                <strong>Medication:</strong> {record.medication}
                              </p>
                            )}
                            {record.notes && (
                              <p className="text-sm text-gray-500 mt-2 italic">{record.notes}</p>
                            )}
                          </div>
                          
                          {record.added_by_parent === 1 && (
                            <button
                              onClick={() => handleOpenMedicalModal(record)}
                              className="ml-4 px-3 py-1 text-sm text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded transition-colors"
                            >
                              Edit
                            </button>
                          )}
                        </div>
                        
                        <div className="mt-3 pt-3 border-t border-gray-100 flex justify-between text-xs text-gray-500">
                          <span>
                            {record.date_reported ? 
                              `Reported: ${new Date(record.date_reported).toLocaleDateString()}` :
                              `Added: ${new Date(record.created_at).toLocaleDateString()}`
                            }
                          </span>
                          {record.next_checkup_date && (
                            <span>Next Checkup: {new Date(record.next_checkup_date).toLocaleDateString()}</span>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Medical Record Modal */}
        {showMedicalModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
              <div className="p-6">
                <div className="flex justify-between items-center mb-4">
                  <h2 className="text-xl font-bold text-gray-900">
                    {editingRecord ? 'Edit Medical Record' : 'Add Medical Record'}
                  </h2>
                  <button
                    onClick={handleCloseMedicalModal}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <form onSubmit={handleSubmitMedical} className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Record Type <span className="text-red-500">*</span>
                      </label>
                      <select
                        name="record_type"
                        value={medicalFormData.record_type}
                        onChange={handleMedicalFormChange}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        required
                      >
                        <option value="allergy">Allergy</option>
                        <option value="condition">Medical Condition</option>
                        <option value="medication">Medication</option>
                        <option value="vaccination">Vaccination</option>
                        <option value="checkup">Checkup</option>
                        <option value="injury">Injury</option>
                        <option value="illness">Illness</option>
                      </select>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Severity <span className="text-red-500">*</span>
                      </label>
                      <select
                        name="severity"
                        value={medicalFormData.severity}
                        onChange={handleMedicalFormChange}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        required
                      >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                      </select>
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Description <span className="text-red-500">*</span>
                    </label>
                    <textarea
                      name="description"
                      value={medicalFormData.description}
                      onChange={handleMedicalFormChange}
                      rows="3"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="E.g., Peanut allergy with severe reaction"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Symptoms
                    </label>
                    <textarea
                      name="symptoms"
                      value={medicalFormData.symptoms}
                      onChange={handleMedicalFormChange}
                      rows="2"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="E.g., Swelling, difficulty breathing, rash"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Treatment
                    </label>
                    <textarea
                      name="treatment"
                      value={medicalFormData.treatment}
                      onChange={handleMedicalFormChange}
                      rows="2"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="E.g., Avoid peanuts, carry EpiPen"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Medication
                    </label>
                    <input
                      type="text"
                      name="medication"
                      value={medicalFormData.medication}
                      onChange={handleMedicalFormChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="E.g., EpiPen, Antihistamine"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Next Checkup Date
                    </label>
                    <input
                      type="date"
                      name="next_checkup_date"
                      value={medicalFormData.next_checkup_date}
                      onChange={handleMedicalFormChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Additional Notes
                    </label>
                    <textarea
                      name="notes"
                      value={medicalFormData.notes}
                      onChange={handleMedicalFormChange}
                      rows="2"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      placeholder="Any additional information..."
                    />
                  </div>

                  <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button
                      type="button"
                      onClick={handleCloseMedicalModal}
                      className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                      {editingRecord ? 'Update Record' : 'Add Record'}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        )}
      </main>
    </div>
  );
};

export default ChildProfile;
