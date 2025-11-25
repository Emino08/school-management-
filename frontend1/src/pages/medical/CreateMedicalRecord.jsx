import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import BackButton from '@/components/BackButton';

const API_URL =
  import.meta.env.VITE_API_BASE_URL ||
  import.meta.env.VITE_API_BASE_URL_LOCAL ||
  'http://localhost:8080/api';

const CreateMedicalRecord = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    student_id: '',
    diagnosis: '',
    symptoms: '',
    treatment: '',
    severity: 'mild',
    notes: ''
  });
  const [searchQuery, setSearchQuery] = useState('');
  const [students, setStudents] = useState([]);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);

  const searchStudents = async (query) => {
    if (query.length < 2) {
      setStudents([]);
      return;
    }

    try {
      const token = localStorage.getItem('medical_token');
      const response = await axios.get(
        `${API_URL}/students?search=${query}`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      setStudents(response.data.students || []);
    } catch (error) {
      console.error('Error searching students:', error);
    }
  };

  const selectStudent = (student) => {
    setSelectedStudent(student);
    setFormData({ ...formData, student_id: student.id });
    setSearchQuery(student.name);
    setStudents([]);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const token = localStorage.getItem('medical_token');
      await axios.post(
        `${API_URL}/medical/records`,
        formData,
        { headers: { Authorization: `Bearer ${token}` } }
      );

      setSuccess(true);
      setTimeout(() => {
        navigate('/medical/dashboard');
      }, 2000);
    } catch (error) {
      console.error('Error creating medical record:', error);
      alert('Failed to create medical record. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-xl p-8 text-center">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Medical Record Created!</h2>
          <p className="text-gray-600 mb-4">Parent has been notified automatically.</p>
          <p className="text-sm text-gray-500">Redirecting to dashboard...</p>
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
            <BackButton to="/medical/dashboard" label="Back to Dashboard" variant="outline" className="mb-0" />
            <h1 className="text-xl font-bold text-gray-900">Create Medical Record</h1>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-lg shadow-xl overflow-hidden">
          <div className="bg-teal-50 border-b border-teal-100 px-6 py-4">
            <div className="flex items-start">
              <div className="flex-shrink-0">
                <svg className="h-6 w-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-teal-800">Important</h3>
                <p className="mt-2 text-sm text-teal-700">
                  Parent will be automatically notified via the system after you create this record.
                </p>
              </div>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="p-6 space-y-6">
            {/* Student Search */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Search Student *
              </label>
              <div className="relative">
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => {
                    setSearchQuery(e.target.value);
                    searchStudents(e.target.value);
                  }}
                  placeholder="Type student name or ID..."
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  required
                />
                {students.length > 0 && (
                  <div className="absolute z-10 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    {students.map((student) => (
                      <button
                        key={student.id}
                        type="button"
                        onClick={() => selectStudent(student)}
                        className="w-full px-4 py-3 text-left hover:bg-gray-50 border-b border-gray-100 last:border-0"
                      >
                        <p className="font-medium text-gray-900">{student.name}</p>
                        <p className="text-sm text-gray-600">
                          {student.id_number} • {student.class_name} {student.section}
                        </p>
                      </button>
                    ))}
                  </div>
                )}
              </div>
              {selectedStudent && (
                <div className="mt-3 p-3 bg-teal-50 border border-teal-200 rounded-lg">
                  <p className="text-sm text-teal-800">
                    Selected: <strong>{selectedStudent.name}</strong> ({selectedStudent.id_number})
                  </p>
                </div>
              )}
            </div>

            {/* Diagnosis */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Diagnosis *
              </label>
              <input
                type="text"
                value={formData.diagnosis}
                onChange={(e) => setFormData({ ...formData, diagnosis: e.target.value })}
                placeholder="E.g., Common Cold, Fever, etc."
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                required
              />
            </div>

            {/* Symptoms */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Symptoms *
              </label>
              <textarea
                value={formData.symptoms}
                onChange={(e) => setFormData({ ...formData, symptoms: e.target.value })}
                placeholder="Describe the symptoms observed..."
                rows="3"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                required
              />
            </div>

            {/* Treatment */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Treatment Plan *
              </label>
              <textarea
                value={formData.treatment}
                onChange={(e) => setFormData({ ...formData, treatment: e.target.value })}
                placeholder="Prescribed treatment, medication, and care instructions..."
                rows="3"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                required
              />
            </div>

            {/* Severity */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Severity Level *
              </label>
              <select
                value={formData.severity}
                onChange={(e) => setFormData({ ...formData, severity: e.target.value })}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                required
              >
                <option value="mild">Mild - Minor condition, minimal intervention</option>
                <option value="moderate">Moderate - Requires monitoring and treatment</option>
                <option value="severe">Severe - Serious condition, immediate care needed</option>
                <option value="critical">Critical - Life-threatening, urgent action required</option>
              </select>
            </div>

            {/* Additional Notes */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Additional Notes (Optional)
              </label>
              <textarea
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                placeholder="Any additional observations or comments..."
                rows="3"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              />
            </div>

            {/* Submit Buttons */}
            <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
              <button
                type="button"
                onClick={() => navigate('/medical/dashboard')}
                className="px-6 py-3 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={loading || !selectedStudent}
                className="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {loading ? 'Creating Record...' : 'Create Medical Record'}
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
  );
};

export default CreateMedicalRecord;
