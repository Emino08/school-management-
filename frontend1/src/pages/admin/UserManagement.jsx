import { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from '@/redux/axiosConfig';
import UserFormModal from './userManagement/UserFormModal';
import { toast } from 'sonner';
import {
  FiUsers, FiUserPlus, FiEdit2, FiTrash2, FiSearch,
  FiFilter, FiDownload, FiUpload, FiCheckCircle,
  FiXCircle, FiShield, FiDollarSign, FiBook
} from 'react-icons/fi';

const UserManagement = () => {
  const { currentUser } = useSelector((state) => state.user);
  const currentRole = (currentUser?.role || '').toLowerCase();
  const canManagePrincipals = currentRole === 'admin';
  const [activeTab, setActiveTab] = useState('overview');
  const [users, setUsers] = useState([]);
  const [stats, setStats] = useState({});
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [selectedUsers, setSelectedUsers] = useState([]);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const disablePrincipalActions = activeTab === 'principals' && !canManagePrincipals;

  useEffect(() => {
    fetchUserStats();
  }, []);

  useEffect(() => {
    if (activeTab !== 'overview') {
      fetchUsers();
    }
  }, [activeTab, searchTerm, filterStatus, currentPage]);

  const fetchUserStats = async () => {
    try {
      const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/user-management/stats`);
      if (response.data.success) {
        setStats(response.data.stats || response.data.data);
      } else {
        toast.error(response.data.message || 'Failed to load user stats');
      }
    } catch (error) {
      console.error('Error fetching user stats:', error);
      toast.error(error.response?.data?.message || 'Failed to load user stats');
    }
  };

  const fetchUsers = async () => {
    setLoading(true);
    try {
      const userTypeMap = {
        students: 'student',
        teachers: 'teacher',
        finance: 'finance',
        principals: 'principal',
        overview: 'all'
      };

      const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/user-management/users`, {
        params: {
          user_type: userTypeMap[activeTab] || 'all',
          search: searchTerm,
          status: filterStatus === 'all' ? null : filterStatus,
          page: currentPage,
          limit: 20
        }
      });

      if (response.data.success) {
        const payload = response.data.data || {};
        // list tab returns under data.users; overview returns grouped
        const list = payload.users || payload.students?.users || payload.teachers?.users || payload.finance?.users || [];
        const total = payload.total || payload.students?.total || payload.teachers?.total || payload.finance?.total || list.length;
        setUsers(list);
        setTotalPages(Math.max(1, Math.ceil(total / 20)));
      } else {
        toast.error(response.data.message || 'Failed to load users');
      }
    } catch (error) {
      console.error('Error fetching users:', error);
      toast.error(error.response?.data?.message || 'Failed to load users');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateUser = async (formData) => {
    if (activeTab === 'principals' && !canManagePrincipals) {
      toast.error('Only administrators can create principal accounts');
      return;
    }
    try {
      const response = await axios.post(`${import.meta.env.VITE_API_BASE_URL}/user-management/users`, formData);

      if (response.data.success) {
        setShowCreateModal(false);
        fetchUsers();
        fetchUserStats();
        toast.success('User created successfully');
      } else {
        toast.error(response.data.message || 'Failed to create user');
      }
    } catch (error) {
      console.error('Error creating user:', error);
      toast.error(error.response?.data?.message || 'Failed to create user');
    }
  };

  const handleUpdateUser = async (formData) => {
    if (activeTab === 'principals' && !canManagePrincipals) {
      toast.error('Only administrators can update principal accounts');
      return;
    }
    try {
      const response = await axios.put(
        `${import.meta.env.VITE_API_BASE_URL}/user-management/users/${editingUser.id}`,
        formData
      );

      if (response.data.success) {
        setShowEditModal(false);
        setEditingUser(null);
        fetchUsers();
        toast.success('User updated successfully');
      } else {
        toast.error(response.data.message || 'Failed to update user');
      }
    } catch (error) {
      console.error('Error updating user:', error);
      toast.error(error.response?.data?.message || 'Failed to update user');
    }
  };

  const handleDeleteUser = async (userId, userType) => {
    if (userType === 'principal' && !canManagePrincipals) {
      toast.error('Only administrators can delete principal accounts');
      return;
    }
    if (!window.confirm('Are you sure you want to delete this user?')) {
      return;
    }

    try {
      const response = await axios.delete(`${import.meta.env.VITE_API_BASE_URL}/user-management/users/${userId}`, {
        data: { user_type: userType }
      });

      if (response.data.success) {
        fetchUsers();
        fetchUserStats();
        toast.success('User deleted successfully');
      } else {
        toast.error(response.data.message || 'Failed to delete user');
      }
    } catch (error) {
      console.error('Error deleting user:', error);
      toast.error(error.response?.data?.message || 'Failed to delete user');
    }
  };

  const handleToggleExamOfficer = async (teacherId) => {
    try {
      const response = await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/user-management/teachers/${teacherId}/toggle-exam-officer`,
        {}
      );

      if (response.data.success) {
        fetchUsers();
        const status = response.data.status || response.data.data?.status;
        toast.success(`Exam officer status ${status}`);
      } else {
        toast.error(response.data.message || 'Failed to toggle exam officer status');
      }
    } catch (error) {
      console.error('Error toggling exam officer:', error);
      toast.error(error.response?.data?.message || 'Failed to toggle exam officer status');
    }
  };

  const handleBulkOperation = async (operation) => {
    if (selectedUsers.length === 0) {
      alert('Please select users first');
      return;
    }

    if (!window.confirm(`Are you sure you want to ${operation} selected users?`)) {
      return;
    }

    try {
      const userTypeMap = {
        students: 'student',
        teachers: 'teacher',
        finance: 'finance',
        principals: 'principal'
      };

      const response = await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/user-management/bulk-operation`,
        {
          operation,
          user_type: userTypeMap[activeTab],
          user_ids: selectedUsers
        }
      );

      if (response.data.success) {
        setSelectedUsers([]);
        fetchUsers();
        fetchUserStats();
        const sc = response.data.success_count || response.data.data?.success_count || 0;
        toast.success(`${sc} users ${operation}d successfully`);
      } else {
        toast.error(response.data.message || 'Bulk operation failed');
      }
    } catch (error) {
      console.error('Error in bulk operation:', error);
      toast.error(error.response?.data?.message || 'Bulk operation failed');
    }
  };

  const tabs = [
    { id: 'overview', label: 'Overview', icon: FiUsers },
    { id: 'students', label: 'Students', icon: FiBook },
    { id: 'teachers', label: 'Teachers', icon: FiUsers },
    { id: 'finance', label: 'Finance Users', icon: FiDollarSign },
    { id: 'principals', label: 'Principals', icon: FiShield }
  ];

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            User Management
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
            Manage students, teachers, finance users, principals, and permissions
          </p>
        </div>

        {/* Tabs */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md mb-6">
          <div className="border-b border-gray-200 dark:border-gray-700">
            <nav className="flex -mb-px">
              {tabs.map((tab) => {
                const Icon = tab.icon;
                return (
                  <button
                    key={tab.id}
                    onClick={() => {
                      setActiveTab(tab.id);
                      setCurrentPage(1);
                      setSearchTerm('');
                      setFilterStatus('all');
                      setSelectedUsers([]);
                    }}
                    className={`
                      flex items-center gap-2 px-6 py-4 font-medium text-sm
                      border-b-2 transition-colors
                      ${activeTab === tab.id
                        ? 'border-purple-600 text-purple-600 dark:text-purple-400'
                        : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-300'
                      }
                    `}
                  >
                    <Icon className="w-5 h-5" />
                    {tab.label}
                  </button>
                );
              })}
            </nav>
          </div>
        </div>

        {/* Overview Tab */}
        {activeTab === 'overview' && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
              title="Total Students"
              value={stats.total_students || 0}
              icon={FiBook}
              color="blue"
              onClick={() => setActiveTab('students')}
            />
            <StatCard
              title="Total Teachers"
              value={stats.total_teachers || 0}
              icon={FiUsers}
              color="green"
              onClick={() => setActiveTab('teachers')}
            />
            <StatCard
              title="Finance Users"
              value={stats.total_finance_users || 0}
              icon={FiDollarSign}
              color="purple"
              onClick={() => setActiveTab('finance')}
            />
            <StatCard
              title="Exam Officers"
              value={stats.exam_officers || 0}
              icon={FiShield}
              color="orange"
            />
            <StatCard
              title="Principal Accounts"
              value={stats.total_principals || 0}
              icon={FiUserPlus}
              color="amber"
              onClick={() => setActiveTab('principals')}
            />
          </div>
        )}

        {/* User List Tabs */}
        {activeTab !== 'overview' && (
          <>
            {/* Toolbar */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
              <div className="flex flex-col md:flex-row gap-4">
                {/* Search */}
                <div className="flex-1">
                  <div className="relative">
                    <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                    <input
                      type="text"
                      placeholder="Search users..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                        focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                  </div>
                </div>

                {/* Filters */}
                {(activeTab === 'teachers' || activeTab === 'finance') && (
                  <div className="flex items-center gap-2">
                    <FiFilter className="text-gray-400" />
                    <select
                      value={filterStatus}
                      onChange={(e) => setFilterStatus(e.target.value)}
                      className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                        focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                      <option value="all">All Status</option>
                      <option value="1">Active</option>
                      <option value="0">Inactive</option>
                    </select>
                  </div>
                )}

                {/* Actions */}
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => {
                      if (disablePrincipalActions) {
                        toast.error('Only administrators can manage principal accounts');
                        return;
                      }
                      setEditingUser(null);
                      setShowCreateModal(true);
                    }}
                    className="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700
                      text-white rounded-lg transition-colors disabled:opacity-60"
                    disabled={disablePrincipalActions}
                  >
                    <FiUserPlus className="w-5 h-5" />
                    Add User
                  </button>

                  {selectedUsers.length > 0 && !disablePrincipalActions && (
                    <button
                      onClick={() => handleBulkOperation('delete')}
                      className="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700
                        text-white rounded-lg transition-colors"
                    >
                      <FiTrash2 className="w-5 h-5" />
                      Delete ({selectedUsers.length})
                    </button>
                  )}
                </div>
              </div>
            </div>

            {disablePrincipalActions && (
              <div className="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm">
                Principal accounts are managed by the super administrator. Please contact your admin for changes.
              </div>
            )}

            {/* User Table */}
            {loading ? (
              <div className="flex justify-center items-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
              </div>
            ) : (
              <UserTable
                users={users}
                userType={activeTab}
                selectedUsers={selectedUsers}
                onSelectUser={(userId) => {
                  setSelectedUsers((prev) =>
                    prev.includes(userId)
                      ? prev.filter((id) => id !== userId)
                      : [...prev, userId]
                  );
                }}
                onSelectAll={(selectAll) => {
                  setSelectedUsers(selectAll ? users.map((u) => u.id) : []);
                }}
                onEdit={(user) => {
                  if (activeTab === 'principals' && !canManagePrincipals) {
                    toast.error('Only administrators can manage principal accounts');
                    return;
                  }
                  setEditingUser(user);
                  setShowEditModal(true);
                }}
                onDelete={handleDeleteUser}
                onToggleExamOfficer={handleToggleExamOfficer}
                canManagePrincipals={canManagePrincipals}
              />
            )}

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="mt-6 flex justify-center items-center gap-2">
                <button
                  onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                  disabled={currentPage === 1}
                  className="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600
                    rounded-lg disabled:opacity-50 disabled:cursor-not-allowed
                    hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  Previous
                </button>
                <span className="px-4 py-2 text-gray-600 dark:text-gray-400">
                  Page {currentPage} of {totalPages}
                </span>
                <button
                  onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                  disabled={currentPage === totalPages}
                  className="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600
                    rounded-lg disabled:opacity-50 disabled:cursor-not-allowed
                    hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  Next
                </button>
              </div>
            )}
          </>
        )}

        {/* Create/Edit User Modal */}
        {(showCreateModal || showEditModal) && (
          <UserFormModal
            isOpen={showCreateModal || showEditModal}
            userType={activeTab}
            user={editingUser}
            onClose={() => {
              setShowCreateModal(false);
              setShowEditModal(false);
              setEditingUser(null);
            }}
            onSubmit={editingUser ? handleUpdateUser : handleCreateUser}
          />
        )}
      </div>
    </div>
  );
};

// Stat Card Component
const StatCard = ({ title, value, icon: Icon, color, onClick }) => {
  const colorClasses = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300',
    green: 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-300',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300',
    amber: 'bg-amber-100 text-amber-600 dark:bg-amber-900 dark:text-amber-300'
  };

  return (
    <div
      onClick={onClick}
      className={`bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 ${onClick ? 'cursor-pointer hover:shadow-lg transition-shadow' : ''}`}
    >
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">{title}</p>
          <p className="text-3xl font-bold text-gray-900 dark:text-white">{value}</p>
        </div>
        <div className={`p-3 rounded-lg ${colorClasses[color]}`}>
          <Icon className="w-6 h-6" />
        </div>
      </div>
    </div>
  );
};

// User Table Component
const UserTable = ({
  users,
  userType,
  selectedUsers,
  onSelectUser,
  onSelectAll,
  onEdit,
  onDelete,
  onToggleExamOfficer,
  canManagePrincipals
}) => {
  const allSelected = users.length > 0 && users.every((u) => selectedUsers.includes(u.id));
  const isPrincipalTab = userType === 'principals';
  const allowPrincipalActions = !isPrincipalTab || canManagePrincipals;

  const getUserTypeName = () => {
    switch (userType) {
      case 'students': return 'student';
      case 'teachers': return 'teacher';
      case 'finance': return 'finance';
      case 'principals': return 'principal';
      default: return 'user';
    }
  };

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th className="px-6 py-3 text-left">
                <input
                  type="checkbox"
                  checked={allSelected}
                  onChange={(e) => {
                    if (!allowPrincipalActions && isPrincipalTab) return;
                    onSelectAll(e.target.checked);
                  }}
                  className="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                  disabled={!allowPrincipalActions && isPrincipalTab}
                />
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Name
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Email
              </th>
              {userType === 'principals' && (
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Phone
                </th>
              )}
              {userType === 'students' && (
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  ID Number
                </th>
              )}
              {userType === 'students' && (
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Class
                </th>
              )}
              {userType === 'teachers' && (
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Exam Officer
                </th>
              )}
              {(userType === 'teachers' || userType === 'finance') && (
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Status
                </th>
              )}
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
            {users.map((user) => (
              <tr key={user.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td className="px-6 py-4">
                  <input
                    type="checkbox"
                    checked={selectedUsers.includes(user.id)}
                    onChange={() => {
                      if (!allowPrincipalActions && isPrincipalTab) return;
                      onSelectUser(user.id);
                    }}
                    className="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                    disabled={!allowPrincipalActions && isPrincipalTab}
                  />
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm font-medium text-gray-900 dark:text-white">
                    {user.name}
                  </div>
                  {userType === 'principals' && (
                    <div className="text-xs text-purple-600 dark:text-purple-300">
                      {user.role || 'Principal'}
                    </div>
                  )}
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm text-gray-600 dark:text-gray-400">
                    {user.email || 'N/A'}
                  </div>
                </td>
                {userType === 'principals' && (
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-600 dark:text-gray-400">
                      {user.phone || 'N/A'}
                    </div>
                  </td>
                )}
                {userType === 'students' && (
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-600 dark:text-gray-400">
                      {user.id_number || 'N/A'}
                    </div>
                  </td>
                )}
                {userType === 'students' && (
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-600 dark:text-gray-400">
                      {user.class_name || 'Not Enrolled'}
                    </div>
                  </td>
                )}
                {userType === 'teachers' && (
                  <td className="px-6 py-4 whitespace-nowrap">
                    <button
                      onClick={() => onToggleExamOfficer(user.id)}
                      className={`px-3 py-1 rounded-full text-xs font-semibold ${
                        user.is_exam_officer
                          ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                      }`}
                    >
                      {user.is_exam_officer ? 'Yes' : 'No'}
                    </button>
                  </td>
                )}
                {(userType === 'teachers' || userType === 'finance') && (
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span
                      className={`px-3 py-1 rounded-full text-xs font-semibold ${
                        (user.is_active || user.is_deleted === 0)
                          ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                          : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                      }`}
                    >
                      {(user.is_active || user.is_deleted === 0) ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                )}
                <td className="px-6 py-4 whitespace-nowrap text-sm">
                  {allowPrincipalActions ? (
                    <div className="flex items-center gap-2">
                      <button
                        onClick={() => onEdit(user)}
                        className="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        title="Edit"
                      >
                        <FiEdit2 className="w-5 h-5" />
                      </button>
                      <button
                        onClick={() => onDelete(user.id, getUserTypeName())}
                        className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        title="Delete"
                      >
                        <FiTrash2 className="w-5 h-5" />
                      </button>
                    </div>
                  ) : (
                    <span className="text-xs text-gray-400 dark:text-gray-500">View only</span>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default UserManagement;
