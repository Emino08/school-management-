import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

const ParentCommunications = () => {
  const navigate = useNavigate();
  const [communications, setCommunications] = useState([]);
  const [showNewForm, setShowNewForm] = useState(false);
  const [loading, setLoading] = useState(true);
  const [formData, setFormData] = useState({
    recipient_type: 'teacher',
    recipient_id: '',
    subject: '',
    message: '',
    priority: 'normal'
  });
  const [teachers, setTeachers] = useState([]);
  const [selectedComm, setSelectedComm] = useState(null);

  useEffect(() => {
    fetchCommunications();
    fetchTeachers();
  }, []);

  const fetchCommunications = async () => {
    try {
      const token = localStorage.getItem('parent_token');
      const response = await axios.get(
        `${API_URL}/parents/communications`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      setCommunications(response.data.communications || []);
      setLoading(false);
    } catch (error) {
      console.error('Error fetching communications:', error);
      setLoading(false);
    }
  };

  const fetchTeachers = async () => {
    try {
      const token = localStorage.getItem('parent_token');
      // Assuming there's an endpoint to get teachers list
      const response = await axios.get(
        `${API_URL}/teachers`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      setTeachers(response.data.teachers || []);
    } catch (error) {
      console.error('Error fetching teachers:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem('parent_token');
      await axios.post(
        `${API_URL}/parents/communications`,
        formData,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      
      setShowNewForm(false);
      setFormData({
        recipient_type: 'teacher',
        recipient_id: '',
        subject: '',
        message: '',
        priority: 'normal'
      });
      fetchCommunications();
    } catch (error) {
      console.error('Error sending message:', error);
    }
  };

  const viewThread = async (id) => {
    try {
      const token = localStorage.getItem('parent_token');
      const response = await axios.get(
        `${API_URL}/parents/communications/${id}`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      setSelectedComm(response.data);
    } catch (error) {
      console.error('Error fetching thread:', error);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <button
                onClick={() => navigate('/parent/dashboard')}
                className="mr-4 p-2 hover:bg-gray-100 rounded-full"
              >
                <svg className="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
              </button>
              <h1 className="text-xl font-bold text-gray-900">Communications</h1>
            </div>
            <button
              onClick={() => setShowNewForm(true)}
              className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium"
            >
              <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              New Message
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* New Message Form Modal */}
        {showNewForm && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
              <div className="p-6">
                <div className="flex justify-between items-center mb-6">
                  <h2 className="text-2xl font-bold text-gray-900">New Message</h2>
                  <button
                    onClick={() => setShowNewForm(false)}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Send To
                    </label>
                    <select
                      value={formData.recipient_type}
                      onChange={(e) => setFormData({ ...formData, recipient_type: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      required
                    >
                      <option value="teacher">Teacher</option>
                      <option value="admin">Admin</option>
                      <option value="principal">Principal</option>
                    </select>
                  </div>

                  {formData.recipient_type === 'teacher' && (
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Select Teacher
                      </label>
                      <select
                        value={formData.recipient_id}
                        onChange={(e) => setFormData({ ...formData, recipient_id: e.target.value })}
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required
                      >
                        <option value="">Select a teacher...</option>
                        {teachers.map((teacher) => (
                          <option key={teacher.id} value={teacher.id}>
                            {teacher.name} - {teacher.subject}
                          </option>
                        ))}
                      </select>
                    </div>
                  )}

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Priority
                    </label>
                    <select
                      value={formData.priority}
                      onChange={(e) => setFormData({ ...formData, priority: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="low">Low</option>
                      <option value="normal">Normal</option>
                      <option value="high">High</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Subject
                    </label>
                    <input
                      type="text"
                      value={formData.subject}
                      onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Enter subject..."
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Message
                    </label>
                    <textarea
                      value={formData.message}
                      onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                      rows="6"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Type your message here..."
                      required
                    />
                  </div>

                  <div className="flex justify-end space-x-3">
                    <button
                      type="button"
                      onClick={() => setShowNewForm(false)}
                      className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                      Send Message
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        )}

        {/* Thread View Modal */}
        {selectedComm && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
              <div className="p-6">
                <div className="flex justify-between items-start mb-6">
                  <div>
                    <h2 className="text-2xl font-bold text-gray-900">{selectedComm.subject}</h2>
                    <p className="text-sm text-gray-600 mt-1">
                      To: {selectedComm.recipient_name} ({selectedComm.recipient_type})
                    </p>
                  </div>
                  <button
                    onClick={() => setSelectedComm(null)}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <div className="space-y-4">
                  {/* Original Message */}
                  <div className="bg-indigo-50 rounded-lg p-4">
                    <div className="flex items-start space-x-3">
                      <div className="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center">
                        <span className="text-white font-semibold">You</span>
                      </div>
                      <div className="flex-1">
                        <div className="flex justify-between items-start">
                          <p className="font-semibold text-gray-900">You</p>
                          <span className="text-xs text-gray-500">
                            {new Date(selectedComm.created_at).toLocaleString()}
                          </span>
                        </div>
                        <p className="mt-2 text-gray-700">{selectedComm.message}</p>
                      </div>
                    </div>
                  </div>

                  {/* Responses */}
                  {selectedComm.responses && selectedComm.responses.map((response, index) => (
                    <div key={index} className="bg-gray-50 rounded-lg p-4">
                      <div className="flex items-start space-x-3">
                        <div className="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center">
                          <span className="text-white font-semibold">
                            {response.responder_name?.charAt(0).toUpperCase()}
                          </span>
                        </div>
                        <div className="flex-1">
                          <div className="flex justify-between items-start">
                            <p className="font-semibold text-gray-900">{response.responder_name}</p>
                            <span className="text-xs text-gray-500">
                              {new Date(response.created_at).toLocaleString()}
                            </span>
                          </div>
                          <p className="mt-2 text-gray-700">{response.response}</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                {selectedComm.status === 'pending' && (
                  <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p className="text-sm text-yellow-800">
                      <svg className="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Waiting for response...
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Communications List */}
        <div className="space-y-4">
          {communications.length === 0 ? (
            <div className="bg-white rounded-lg shadow p-12 text-center">
              <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
              </svg>
              <h3 className="mt-4 text-lg font-medium text-gray-900">No messages yet</h3>
              <p className="mt-2 text-sm text-gray-500">Start a conversation with a teacher or administrator</p>
            </div>
          ) : (
            communications.map((comm) => (
              <div
                key={comm.id}
                onClick={() => viewThread(comm.id)}
                className="bg-white rounded-lg shadow p-6 hover:shadow-md transition cursor-pointer"
              >
                <div className="flex justify-between items-start">
                  <div className="flex-1">
                    <div className="flex items-center space-x-3">
                      <h3 className="text-lg font-semibold text-gray-900">{comm.subject}</h3>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        comm.priority === 'high' ? 'bg-red-100 text-red-800' :
                        comm.priority === 'normal' ? 'bg-blue-100 text-blue-800' :
                        'bg-gray-100 text-gray-800'
                      }`}>
                        {comm.priority}
                      </span>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        comm.status === 'responded' ? 'bg-green-100 text-green-800' :
                        'bg-yellow-100 text-yellow-800'
                      }`}>
                        {comm.status}
                      </span>
                    </div>
                    <p className="mt-2 text-sm text-gray-600 line-clamp-2">{comm.message}</p>
                    <div className="mt-3 flex items-center space-x-4 text-xs text-gray-500">
                      <span>To: {comm.recipient_name} ({comm.recipient_type})</span>
                      <span>•</span>
                      <span>{new Date(comm.created_at).toLocaleDateString()}</span>
                      {comm.response_count > 0 && (
                        <>
                          <span>•</span>
                          <span className="flex items-center">
                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            {comm.response_count} {comm.response_count === 1 ? 'reply' : 'replies'}
                          </span>
                        </>
                      )}
                    </div>
                  </div>
                  <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            ))
          )}
        </div>
      </main>
    </div>
  );
};

export default ParentCommunications;
