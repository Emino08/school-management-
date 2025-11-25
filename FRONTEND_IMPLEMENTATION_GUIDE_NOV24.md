# Frontend Implementation Guide - November 24, 2025

## Required Frontend Changes

### 1. Update Login Components

#### Admin Login (src/pages/Admin/Login.jsx)
```jsx
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import axios from 'axios';

const AdminLogin = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { login } = useAuth();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await axios.post('http://localhost:8080/api/admin/login', {
        email,
        password,
        loginAs: 'admin' // Important: Specify login type
      });

      if (response.data.success) {
        // Store token and user data
        login(response.data.token, response.data.account, response.data.permissions);
        navigate('/admin/dashboard');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-container">
      <h2>Admin Login</h2>
      <form onSubmit={handleSubmit}>
        <input
          type="email"
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        <input
          type="password"
          placeholder="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
        {error && <div className="error">{error}</div>}
        <button type="submit" disabled={loading}>
          {loading ? 'Logging in...' : 'Login'}
        </button>
      </form>
    </div>
  );
};

export default AdminLogin;
```

#### Principal Login (src/pages/Principal/Login.jsx)
```jsx
// Same as AdminLogin but with loginAs: 'principal'
const PrincipalLogin = () => {
  // ... same state and setup

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await axios.post('http://localhost:8080/api/admin/login', {
        email,
        password,
        loginAs: 'principal' // Important: Specify principal login
      });

      if (response.data.success) {
        login(response.data.token, response.data.account, response.data.permissions);
        navigate('/principal/dashboard');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  // ... rest same as AdminLogin
};
```

### 2. Update Auth Context

#### src/contexts/AuthContext.jsx
```jsx
import { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [permissions, setPermissions] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Load from localStorage on mount
    const storedToken = localStorage.getItem('token');
    const storedUser = localStorage.getItem('user');
    const storedPermissions = localStorage.getItem('permissions');
    
    if (storedToken && storedUser) {
      setToken(storedToken);
      setUser(JSON.parse(storedUser));
      setPermissions(JSON.parse(storedPermissions || '{}'));
    }
    setLoading(false);
  }, []);

  const login = (newToken, userData, userPermissions) => {
    setToken(newToken);
    setUser(userData);
    setPermissions(userPermissions);
    
    localStorage.setItem('token', newToken);
    localStorage.setItem('user', JSON.stringify(userData));
    localStorage.setItem('permissions', JSON.stringify(userPermissions));
  };

  const logout = () => {
    setToken(null);
    setUser(null);
    setPermissions(null);
    
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('permissions');
  };

  return (
    <AuthContext.Provider value={{ user, token, permissions, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
```

### 3. Update Sidebar Component

#### src/components/Sidebar/AdminSidebar.jsx
```jsx
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';

const AdminSidebar = () => {
  const { permissions, user } = useAuth();

  return (
    <div className="sidebar">
      <div className="sidebar-header">
        <h3>{user?.role === 'Principal' ? 'Principal Portal' : 'Admin Portal'}</h3>
        {permissions?.isSuperAdmin && <span className="badge">Super Admin</span>}
      </div>
      
      <nav>
        <Link to="/admin/dashboard">Dashboard</Link>
        <Link to="/admin/students">Students</Link>
        <Link to="/admin/teachers">Teachers</Link>
        <Link to="/admin/classes">Classes</Link>
        <Link to="/admin/attendance">Attendance</Link>
        <Link to="/admin/results">Results</Link>
        <Link to="/admin/fees">Fees</Link>
        
        {/* Only show for admins and super admins, not principals */}
        {permissions?.canAccessSystemSettings && (
          <>
            <hr />
            <Link to="/admin/settings">System Settings</Link>
          </>
        )}
        
        {/* Only show for admins and super admins, not principals */}
        {permissions?.canViewActivityLogs && (
          <Link to="/admin/activity-logs">Activity Logs</Link>
        )}
        
        {/* Only show for super admin */}
        {permissions?.canCreateAdmins && (
          <>
            <hr />
            <Link to="/admin/users">Admin Users</Link>
          </>
        )}
        
        {/* Show principals management for admins */}
        {permissions?.canManagePrincipals && !permissions?.isPrincipal && (
          <Link to="/admin/principals">Principals</Link>
        )}
      </nav>
    </div>
  );
};

export default AdminSidebar;
```

### 4. Parent Medical Records Component

#### src/components/Parent/MedicalRecordForm.jsx
```jsx
import { useState } from 'react';
import axios from 'axios';

const MedicalRecordForm = ({ studentId, onSuccess, onCancel }) => {
  const [formData, setFormData] = useState({
    student_id: studentId,
    record_type: 'illness',
    description: '',
    symptoms: '',
    treatment: '',
    medication: '',
    severity: 'low',
    notes: ''
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const token = localStorage.getItem('token');
      const response = await axios.post(
        'http://localhost:8080/api/parents/medical-records',
        formData,
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      if (response.data.success) {
        onSuccess();
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to add medical record');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="medical-record-form">
      <h3>Add Medical Record</h3>
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label>Record Type</label>
          <select name="record_type" value={formData.record_type} onChange={handleChange} required>
            <option value="illness">Illness</option>
            <option value="injury">Injury</option>
            <option value="allergy">Allergy</option>
            <option value="vaccination">Vaccination</option>
            <option value="parent_report">Parent Report</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div className="form-group">
          <label>Description *</label>
          <textarea
            name="description"
            value={formData.description}
            onChange={handleChange}
            required
            placeholder="Describe the medical issue..."
            rows="4"
          />
        </div>

        <div className="form-group">
          <label>Symptoms</label>
          <textarea
            name="symptoms"
            value={formData.symptoms}
            onChange={handleChange}
            placeholder="List any symptoms..."
            rows="3"
          />
        </div>

        <div className="form-group">
          <label>Treatment</label>
          <textarea
            name="treatment"
            value={formData.treatment}
            onChange={handleChange}
            placeholder="Treatment received or recommended..."
            rows="3"
          />
        </div>

        <div className="form-group">
          <label>Medication</label>
          <input
            type="text"
            name="medication"
            value={formData.medication}
            onChange={handleChange}
            placeholder="Any medications prescribed..."
          />
        </div>

        <div className="form-group">
          <label>Severity</label>
          <select name="severity" value={formData.severity} onChange={handleChange}>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>

        <div className="form-group">
          <label>Additional Notes</label>
          <textarea
            name="notes"
            value={formData.notes}
            onChange={handleChange}
            placeholder="Any additional information..."
            rows="2"
          />
        </div>

        {error && <div className="error-message">{error}</div>}

        <div className="form-actions">
          <button type="button" onClick={onCancel} disabled={loading}>
            Cancel
          </button>
          <button type="submit" disabled={loading}>
            {loading ? 'Saving...' : 'Add Medical Record'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default MedicalRecordForm;
```

#### src/components/Parent/MedicalRecordsList.jsx
```jsx
import { useState, useEffect } from 'react';
import axios from 'axios';

const MedicalRecordsList = ({ studentId }) => {
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchRecords();
  }, [studentId]);

  const fetchRecords = async () => {
    try {
      const token = localStorage.getItem('token');
      const url = studentId
        ? `http://localhost:8080/api/parents/children/${studentId}/medical-records`
        : 'http://localhost:8080/api/parents/medical-records';
      
      const response = await axios.get(url, {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.data.success) {
        setRecords(response.data.medical_records || []);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to fetch records');
    } finally {
      setLoading(false);
    }
  };

  const getSeverityBadge = (severity) => {
    const colors = {
      low: 'green',
      medium: 'yellow',
      high: 'orange',
      critical: 'red'
    };
    return <span className={`badge badge-${colors[severity]}`}>{severity}</span>;
  };

  if (loading) return <div>Loading medical records...</div>;
  if (error) return <div className="error">{error}</div>;

  return (
    <div className="medical-records-list">
      {records.length === 0 ? (
        <p>No medical records found.</p>
      ) : (
        <div className="records-grid">
          {records.map((record) => (
            <div key={record.id} className="record-card">
              <div className="record-header">
                <h4>{record.record_type}</h4>
                {getSeverityBadge(record.severity)}
              </div>
              <div className="record-body">
                <p><strong>Description:</strong> {record.diagnosis}</p>
                {record.symptoms && (
                  <p><strong>Symptoms:</strong> {record.symptoms}</p>
                )}
                {record.treatment && (
                  <p><strong>Treatment:</strong> {record.treatment}</p>
                )}
                {record.medication && (
                  <p><strong>Medication:</strong> {record.medication}</p>
                )}
                <p className="record-date">
                  Added: {new Date(record.created_at).toLocaleDateString()}
                </p>
                {record.added_by && (
                  <p className="record-source">Added by: {record.added_by}</p>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default MedicalRecordsList;
```

#### src/pages/Parent/Dashboard.jsx
```jsx
import { useState } from 'react';
import MedicalRecordForm from '../../components/Parent/MedicalRecordForm';
import MedicalRecordsList from '../../components/Parent/MedicalRecordsList';

const ParentDashboard = () => {
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [showAddMedical, setShowAddMedical] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');

  const handleMedicalSuccess = () => {
    setShowAddMedical(false);
    // Refresh the medical records list
    window.location.reload(); // Or use a better state management solution
  };

  return (
    <div className="parent-dashboard">
      <h1>Parent Dashboard</h1>
      
      {/* Tabs */}
      <div className="tabs">
        <button onClick={() => setActiveTab('overview')}>Overview</button>
        <button onClick={() => setActiveTab('attendance')}>Attendance</button>
        <button onClick={() => setActiveTab('results')}>Results</button>
        <button onClick={() => setActiveTab('medical')}>Medical Records</button>
        <button onClick={() => setActiveTab('fees')}>Fees</button>
      </div>

      {/* Tab Content */}
      {activeTab === 'medical' && (
        <div className="medical-tab">
          <div className="tab-header">
            <h2>Medical Records</h2>
            <button onClick={() => setShowAddMedical(true)}>
              Add Medical Record
            </button>
          </div>

          {showAddMedical && (
            <div className="modal">
              <div className="modal-content">
                <MedicalRecordForm
                  studentId={selectedStudent?.id}
                  onSuccess={handleMedicalSuccess}
                  onCancel={() => setShowAddMedical(false)}
                />
              </div>
            </div>
          )}

          <MedicalRecordsList studentId={selectedStudent?.id} />
        </div>
      )}

      {/* Other tabs... */}
    </div>
  );
};

export default ParentDashboard;
```

### 5. Student Status Display Fix

#### src/components/StudentStatusBadge.jsx
```jsx
const StudentStatusBadge = ({ student }) => {
  // Use suspension_status, not just "status"
  const status = student.suspension_status || 'active';
  
  const statusConfig = {
    active: { color: 'green', text: 'Active' },
    suspended: { color: 'red', text: 'Suspended' },
    expelled: { color: 'darkred', text: 'Expelled' },
    on_probation: { color: 'orange', text: 'On Probation' },
    transferred: { color: 'blue', text: 'Transferred' },
    graduated: { color: 'purple', text: 'Graduated' }
  };

  const config = statusConfig[status] || statusConfig.active;

  return (
    <span className={`status-badge status-${config.color}`}>
      {config.text}
    </span>
  );
};

export default StudentStatusBadge;
```

## CSS Styles (Add to your global CSS or component styles)

```css
/* Medical Record Form */
.medical-record-form {
  max-width: 600px;
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}

/* Medical Records List */
.records-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.record-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  background: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.record-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-green { background: #d4edda; color: #155724; }
.badge-yellow { background: #fff3cd; color: #856404; }
.badge-orange { background: #ffe5d0; color: #8B4513; }
.badge-red { background: #f8d7da; color: #721c24; }

/* Status Badge */
.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-green { background: #d4edda; color: #155724; }
.status-red { background: #f8d7da; color: #721c24; }
.status-orange { background: #fff3cd; color: #856404; }
.status-blue { background: #d1ecf1; color: #0c5460; }
.status-purple { background: #e2d9f3; color: #432874; }

/* Modal */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
```

## Testing Steps

1. **Test Admin Login**
   - Open admin login page
   - Try logging in with admin credentials
   - Should succeed and redirect to admin dashboard
   - Check sidebar - should see system settings and admin users (if super admin)

2. **Test Principal Login**
   - Open principal login page
   - Try logging in with principal credentials
   - Should succeed and redirect to principal dashboard
   - Check sidebar - should NOT see system settings or admin users

3. **Test Cross-Login Prevention**
   - Try logging into admin portal with principal credentials
   - Should show error message
   - Try logging into principal portal with admin credentials
   - Should show error message

4. **Test Parent Medical Records**
   - Login as parent
   - Navigate to medical records tab
   - Click "Add Medical Record"
   - Fill in form and submit
   - Should see success message and new record in list

5. **Test Student Status**
   - View students in parent dashboard
   - Check status is not showing "Suspended" by default
   - Should show correct actual status

## Deployment

1. Build frontend:
   ```bash
   npm run build
   ```

2. Update environment variables
3. Deploy to production server
4. Test all endpoints
5. Monitor error logs

## Support

If you encounter any issues:
1. Check browser console for errors
2. Check network tab for API responses
3. Verify token is being sent in Authorization header
4. Check backend logs for detailed error messages
