import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const API_URL =
  import.meta.env.VITE_API_BASE_URL ||
  import.meta.env.VITE_API_URL ||
  'http://localhost:8080/api';

const MedicalStudentSearch = () => {
  const navigate = useNavigate();
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(false);
  const [recordsLoading, setRecordsLoading] = useState(false);
  const [students, setStudents] = useState([]);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [records, setRecords] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [error, setError] = useState('');

  useEffect(() => {
    const token = localStorage.getItem('medical_token');
    if (!token) {
      navigate('/medical/login');
    }
  }, [navigate]);

  const authConfig = () => ({
    headers: { Authorization: `Bearer ${localStorage.getItem('medical_token')}` }
  });

  const searchStudents = async (e) => {
    e.preventDefault();
    if (!query.trim()) {
      setStudents([]);
      setSelectedStudent(null);
      return;
    }

    setLoading(true);
    setError('');
    try {
      const response = await axios.get(
        `${API_URL}/students`,
        {
          params: { search: query.trim(), limit: 15 },
          ...authConfig()
        }
      );
      setStudents(response.data.students || []);
    } catch (err) {
      setError(err.response?.data?.message || 'Unable to search students right now.');
    } finally {
      setLoading(false);
    }
  };

  const loadRecords = async (student) => {
    setRecordsLoading(true);
    setError('');
    try {
      const response = await axios.get(
        `${API_URL}/medical/records/student/${student.id}`,
        authConfig()
      );
      setRecords(response.data.records || []);
      setDocuments(response.data.documents || []);
      setSelectedStudent({
        ...student,
        ...response.data.student
      });
    } catch (err) {
      setError(err.response?.data?.message || 'Could not load medical records.');
    } finally {
      setRecordsLoading(false);
    }
  };

  const handleSelectStudent = (student) => {
    setSelectedStudent(student);
    loadRecords(student);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <button
                onClick={() => navigate('/medical/dashboard')}
                className="mr-4 p-2 hover:bg-gray-100 rounded-full"
              >
                <svg className="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
              </button>
              <div>
                <h1 className="text-xl font-bold text-gray-900">Search Student Records</h1>
                <p className="text-sm text-gray-600">Find a student by ID or name and review their medical history.</p>
              </div>
            </div>
            <button
              onClick={() => navigate('/medical/create-record')}
              className="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
            >
              New Medical Record
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <form onSubmit={searchStudents} className="grid grid-cols-1 lg:grid-cols-6 gap-4 items-end">
            <div className="lg:col-span-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Student ID or Name
              </label>
              <input
                type="text"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                placeholder="Enter student ID number or name..."
              />
              <p className="text-xs text-gray-500 mt-1">
                Use the school-issued student ID to quickly locate a record.
              </p>
            </div>
            <div className="lg:col-span-2 flex gap-3">
              <button
                type="submit"
                disabled={loading}
                className="flex-1 px-4 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {loading ? 'Searching...' : 'Search'}
              </button>
              <button
                type="button"
                onClick={() => {
                  setQuery('');
                  setStudents([]);
                  setSelectedStudent(null);
                  setRecords([]);
                  setDocuments([]);
                }}
                className="px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
              >
                Clear
              </button>
            </div>
          </form>
          {error && (
            <div className="mt-4 p-3 rounded border border-red-200 bg-red-50 text-sm text-red-700">
              {error}
            </div>
          )}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow overflow-hidden">
              <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 className="text-sm font-semibold text-gray-800">Search Results</h3>
                <span className="text-xs text-gray-500">{students.length} found</span>
              </div>
              <div className="divide-y divide-gray-200">
                {students.length === 0 && (
                  <div className="p-4 text-sm text-gray-500">No students yet. Search to see results.</div>
                )}
                {students.map((student) => (
                  <button
                    key={student.id}
                    onClick={() => handleSelectStudent(student)}
                    className={`w-full text-left p-4 hover:bg-gray-50 transition ${
                      selectedStudent?.id === student.id ? 'bg-teal-50 border-l-4 border-teal-500' : ''
                    }`}
                  >
                    <p className="font-semibold text-gray-900">{student.name}</p>
                    <p className="text-sm text-gray-600">ID: {student.id_number || student.id}</p>
                    <p className="text-xs text-gray-500">
                      {student.class_name ? `${student.class_name} ${student.section || ''}` : 'No class assigned'}
                    </p>
                  </button>
                ))}
              </div>
            </div>
          </div>

          <div className="lg:col-span-2">
            {!selectedStudent && (
              <div className="bg-white rounded-lg shadow p-8 text-center text-gray-600">
                <p>Select a student to view their medical records.</p>
              </div>
            )}

            {selectedStudent && (
              <div className="space-y-4">
                <div className="bg-white rounded-lg shadow p-6">
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="text-sm text-gray-500">Student</p>
                      <h2 className="text-xl font-bold text-gray-900">{selectedStudent.name}</h2>
                      <div className="text-sm text-gray-600 mt-2 space-y-1">
                        <p>ID Number: {selectedStudent.id_number || selectedStudent.id}</p>
                        <p>Date of Birth: {selectedStudent.date_of_birth || 'Not provided'}</p>
                        <p>Class: {selectedStudent.class_name ? `${selectedStudent.class_name} ${selectedStudent.section || ''}` : 'Not assigned'}</p>
                      </div>
                    </div>
                    <div className="flex flex-col gap-2">
                      <span className="px-3 py-1 text-xs rounded-full bg-teal-100 text-teal-700">
                        Records: {records.length}
                      </span>
                      <span className="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                        Parent files: {documents.length}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-lg shadow">
                  <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 className="text-sm font-semibold text-gray-800">Medical Records</h3>
                    {recordsLoading && <span className="text-xs text-gray-500">Refreshing...</span>}
                  </div>
                  {records.length === 0 ? (
                    <div className="p-6 text-sm text-gray-500">No medical records for this student yet.</div>
                  ) : (
                    <div className="divide-y divide-gray-200">
                      {records.map((record) => (
                        <div key={record.id} className="p-6">
                          <div className="flex items-start justify-between">
                            <div>
                              <p className="text-xs text-gray-500">Diagnosis</p>
                              <h4 className="text-lg font-semibold text-gray-900">{record.diagnosis}</h4>
                              <p className="text-sm text-gray-600 mt-1">{record.symptoms}</p>
                              {record.treatment && (
                                <p className="text-sm text-gray-600 mt-2">
                                  <strong>Treatment:</strong> {record.treatment}
                                </p>
                              )}
                              {record.medication && (
                                <p className="text-sm text-gray-600 mt-1">
                                  <strong>Medication:</strong> {record.medication}
                                </p>
                              )}
                            </div>
                            <div className="flex flex-col items-end gap-2">
                              <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                record.severity === 'critical'
                                  ? 'bg-red-100 text-red-700'
                                  : record.severity === 'severe'
                                    ? 'bg-orange-100 text-orange-700'
                                    : record.severity === 'moderate'
                                      ? 'bg-yellow-100 text-yellow-700'
                                      : 'bg-green-100 text-green-700'
                              }`}>
                                {record.severity || 'mild'}
                              </span>
                              <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                record.status === 'completed'
                                  ? 'bg-green-100 text-green-700'
                                  : record.status === 'under_treatment'
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'bg-teal-100 text-teal-700'
                              }`}>
                                {(record.status || 'active').replace('_', ' ')}
                              </span>
                              <span className="text-xs text-gray-500">
                                {new Date(record.created_at).toLocaleDateString()}
                              </span>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="bg-white rounded-lg shadow">
                  <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 className="text-sm font-semibold text-gray-800">Documents from Parents</h3>
                  </div>
                  {documents.length === 0 ? (
                    <div className="p-6 text-sm text-gray-500">No uploaded medical documents.</div>
                  ) : (
                    <div className="divide-y divide-gray-200">
                      {documents.map((doc) => (
                        <div key={doc.id} className="p-4 flex items-center justify-between">
                          <div>
                            <p className="font-medium text-gray-900">{doc.document_name}</p>
                            <p className="text-xs text-gray-500">
                              {doc.uploaded_by_name ? `Uploaded by ${doc.uploaded_by_name}` : 'Parent upload'} • {doc.document_type}
                            </p>
                          </div>
                          <span className="text-xs text-gray-500">
                            {new Date(doc.created_at).toLocaleDateString()}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
};

export default MedicalStudentSearch;
