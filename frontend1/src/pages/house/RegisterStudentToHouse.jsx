import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080/api';

const RegisterStudentToHouse = () => {
  const navigate = useNavigate();
  const [houses, setHouses] = useState([]);
  const [blocks, setBlocks] = useState([]);
  const [eligibleStudents, setEligibleStudents] = useState([]);
  const [selectedHouse, setSelectedHouse] = useState('');
  const [selectedBlock, setSelectedBlock] = useState('');
  const [selectedStudent, setSelectedStudent] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState(null);

  useEffect(() => {
    fetchData();
  }, []);

  useEffect(() => {
    if (selectedHouse) {
      fetchBlocks(selectedHouse);
    }
  }, [selectedHouse]);

  const fetchData = async () => {
    try {
      const token = localStorage.getItem('teacher_token') || localStorage.getItem('admin_token');
      const config = { headers: { Authorization: `Bearer ${token}` } };

      // Fetch houses
      const housesRes = await axios.get(`${API_URL}/houses`, config);
      setHouses(housesRes.data.houses || []);

      // Fetch eligible students (paid tuition)
      const studentsRes = await axios.get(`${API_URL}/houses/eligible-students`, config);
      setEligibleStudents(studentsRes.data.students || []);

      setLoading(false);
    } catch (error) {
      console.error('Error fetching data:', error);
      setLoading(false);
    }
  };

  const fetchBlocks = async (houseId) => {
    try {
      const token = localStorage.getItem('teacher_token') || localStorage.getItem('admin_token');
      const config = { headers: { Authorization: `Bearer ${token}` } };

      const response = await axios.get(`${API_URL}/houses/${houseId}`, config);
      setBlocks(response.data.house.blocks || []);
    } catch (error) {
      console.error('Error fetching blocks:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);

    try {
      const token = localStorage.getItem('teacher_token') || localStorage.getItem('admin_token');
      await axios.post(
        `${API_URL}/houses/register-student`,
        {
          student_id: selectedStudent,
          house_id: selectedHouse,
          block_id: selectedBlock
        },
        { headers: { Authorization: `Bearer ${token}` } }
      );

      const student = eligibleStudents.find(s => s.id === parseInt(selectedStudent));
      setSuccess(student);

      setTimeout(() => {
        setSelectedHouse('');
        setSelectedBlock('');
        setSelectedStudent('');
        setSuccess(null);
        fetchData();
      }, 3000);
    } catch (error) {
      console.error('Error registering student:', error);
      alert(error.response?.data?.message || 'Failed to register student');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center">
            <button
              onClick={() => navigate(-1)}
              className="mr-4 p-2 hover:bg-gray-100 rounded-full"
            >
              <svg className="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </button>
            <h1 className="text-xl font-bold text-gray-900">Register Student to House</h1>
          </div>
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Success Message */}
        {success && (
          <div className="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div className="flex items-center">
              <svg className="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p className="text-green-800">
                <strong>{success.name}</strong> has been successfully registered to the house!
              </p>
            </div>
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Registration Form */}
          <div className="bg-white rounded-lg shadow">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Registration Form</h2>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-6">
              {/* House Selection */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Select House *
                </label>
                <select
                  value={selectedHouse}
                  onChange={(e) => setSelectedHouse(e.target.value)}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                  required
                >
                  <option value="">Choose a house...</option>
                  {houses.map((house) => (
                    <option key={house.id} value={house.id}>
                      {house.name} ({house.color})
                    </option>
                  ))}
                </select>
              </div>

              {/* Block Selection */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Select Block *
                </label>
                <select
                  value={selectedBlock}
                  onChange={(e) => setSelectedBlock(e.target.value)}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                  required
                  disabled={!selectedHouse}
                >
                  <option value="">Choose a block...</option>
                  {blocks.map((block) => (
                    <option key={block.id} value={block.id}>
                      Block {block.name} ({block.capacity - block.current_occupancy} slots available)
                    </option>
                  ))}
                </select>
                {selectedHouse && blocks.length === 0 && (
                  <p className="mt-2 text-sm text-gray-500">No blocks available for this house</p>
                )}
              </div>

              {/* Student Selection */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Select Student *
                </label>
                <select
                  value={selectedStudent}
                  onChange={(e) => setSelectedStudent(e.target.value)}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                  required
                >
                  <option value="">Choose a student...</option>
                  {eligibleStudents.map((student) => (
                    <option key={student.id} value={student.id}>
                      {student.name} ({student.id_number}) - {student.class_name} {student.section}
                      {student.is_registered && ' - Already Registered'}
                    </option>
                  ))}
                </select>
                <p className="mt-2 text-xs text-gray-500">
                  ✓ Only students with paid tuition fees are shown
                </p>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                disabled={submitting || !selectedHouse || !selectedBlock || !selectedStudent}
                className="w-full px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {submitting ? 'Registering...' : 'Register Student'}
              </button>
            </form>
          </div>

          {/* Info Panel */}
          <div className="space-y-6">
            {/* Eligible Students Count */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Eligible Students</h3>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-3xl font-bold text-purple-600">{eligibleStudents.length}</p>
                  <p className="text-sm text-gray-600 mt-1">Students with paid tuition</p>
                </div>
                <div className="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
                  <svg className="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
              </div>
            </div>

            {/* Houses Overview */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Available Houses</h3>
              <div className="space-y-3">
                {houses.map((house) => (
                  <div key={house.id} className="border border-gray-200 rounded-lg p-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-3">
                        <div
                          className="w-8 h-8 rounded-full"
                          style={{ backgroundColor: house.color }}
                        ></div>
                        <div>
                          <p className="font-semibold text-gray-900">{house.name}</p>
                          <p className="text-xs text-gray-600">{house.description}</p>
                        </div>
                      </div>
                      <span className="text-sm font-medium text-gray-600">
                        {house.student_count || 0} students
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Important Notes */}
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h4 className="text-sm font-semibold text-blue-900 mb-2">Important Notes</h4>
              <ul className="text-sm text-blue-800 space-y-2">
                <li className="flex items-start">
                  <svg className="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Only students with paid tuition can be registered
                </li>
                <li className="flex items-start">
                  <svg className="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Each house has 6 blocks (A-F)
                </li>
                <li className="flex items-start">
                  <svg className="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Check block capacity before registering
                </li>
                <li className="flex items-start">
                  <svg className="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Registration is logged automatically
                </li>
              </ul>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default RegisterStudentToHouse;
