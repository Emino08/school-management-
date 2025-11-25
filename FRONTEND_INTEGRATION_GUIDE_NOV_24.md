# Frontend Integration Guide - November 24, 2025

## Overview
This guide provides the necessary frontend changes to integrate with the backend fixes.

## 1. Admin Dashboard Changes

### Add Admins Tab (Super Admin Only)

**Location**: `frontend1/src/components/Admin/Users` or similar

```jsx
// In AdminDashboard.jsx or UserManagement.jsx
import { useAuth } from '../context/AuthContext';

function UserManagement() {
    const { user } = useAuth();
    const isSuperAdmin = user?.is_super_admin || user?.role === 'super_admin';

    return (
        <div>
            <Tabs>
                <TabList>
                    <Tab>Students</Tab>
                    <Tab>Teachers</Tab>
                    <Tab>Parents</Tab>
                    {isSuperAdmin && <Tab>Admins</Tab>}
                </TabList>

                {/* Existing tabs... */}
                
                {isSuperAdmin && (
                    <TabPanel>
                        <AdminUsersTab />
                    </TabPanel>
                )}
            </Tabs>
        </div>
    );
}
```

### AdminUsersTab Component

```jsx
// components/Admin/Users/AdminUsersTab.jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

function AdminUsersTab() {
    const [admins, setAdmins] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showAddModal, setShowAddModal] = useState(false);

    useEffect(() => {
        fetchAdmins();
    }, []);

    const fetchAdmins = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/api/admin/admin-users');
            if (response.data.success) {
                setAdmins(response.data.admins);
            }
        } catch (error) {
            console.error('Failed to fetch admins:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleAddAdmin = async (formData) => {
        try {
            const response = await axios.post('/api/admin/admin-users', formData);
            if (response.data.success) {
                alert('Admin created successfully!');
                fetchAdmins();
                setShowAddModal(false);
            }
        } catch (error) {
            alert('Failed to create admin: ' + (error.response?.data?.message || error.message));
        }
    };

    return (
        <div className="admins-tab">
            <div className="header">
                <h2>Admin Users</h2>
                <button onClick={() => setShowAddModal(true)} className="btn-primary">
                    + Add Admin
                </button>
            </div>

            {loading ? (
                <div>Loading...</div>
            ) : (
                <table className="admins-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {admins.map(admin => (
                            <tr key={admin.id}>
                                <td>{admin.contact_name}</td>
                                <td>{admin.email}</td>
                                <td>{admin.phone || 'N/A'}</td>
                                <td>{new Date(admin.created_at).toLocaleDateString()}</td>
                                <td>
                                    <button className="btn-edit">Edit</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}

            {showAddModal && (
                <AddAdminModal
                    onClose={() => setShowAddModal(false)}
                    onSubmit={handleAddAdmin}
                />
            )}
        </div>
    );
}

export default AdminUsersTab;
```

### Hide System Settings from Principals

```jsx
// components/Sidebar.jsx or Navigation.jsx
import { useAuth } from '../context/AuthContext';

function Sidebar() {
    const { user } = useAuth();
    const isPrincipal = user?.role === 'Principal';
    const isAdmin = user?.role === 'Admin' || user?.role === 'super_admin';

    return (
        <nav className="sidebar">
            {/* Other menu items */}
            
            {/* Only show System Settings to admins and super admins */}
            {isAdmin && !isPrincipal && (
                <li>
                    <Link to="/admin/settings">
                        <SettingsIcon />
                        System Settings
                    </Link>
                </li>
            )}
        </nav>
    );
}
```

## 2. Parent Dashboard Changes

### Medical Records Component

```jsx
// components/Parent/MedicalRecords.jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

function MedicalRecords({ childId }) {
    const [records, setRecords] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showAddModal, setShowAddModal] = useState(false);
    const [permissions, setPermissions] = useState({});

    useEffect(() => {
        fetchMedicalRecords();
    }, [childId]);

    const fetchMedicalRecords = async () => {
        setLoading(true);
        try {
            const url = childId 
                ? `/api/parents/children/${childId}/medical-records`
                : '/api/parents/medical-records';
            
            const response = await axios.get(url);
            if (response.data.success) {
                setRecords(response.data.medical_records);
                setPermissions({
                    can_add: response.data.can_add,
                    can_update: response.data.can_update,
                    can_delete: response.data.can_delete
                });
            }
        } catch (error) {
            console.error('Failed to fetch medical records:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleAddRecord = async (formData) => {
        try {
            const response = await axios.post('/api/parents/medical-records', {
                ...formData,
                student_id: childId
            });
            
            if (response.data.success) {
                alert('Medical record added successfully!');
                fetchMedicalRecords();
                setShowAddModal(false);
            }
        } catch (error) {
            alert('Failed to add medical record: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleUpdateRecord = async (recordId, formData) => {
        try {
            const response = await axios.put(`/api/parents/medical-records/${recordId}`, formData);
            
            if (response.data.success) {
                alert('Medical record updated successfully!');
                fetchMedicalRecords();
            }
        } catch (error) {
            alert('Failed to update medical record: ' + (error.response?.data?.message || error.message));
        }
    };

    return (
        <div className="medical-records">
            <div className="header">
                <h2>Medical Records</h2>
                {permissions.can_add && (
                    <button onClick={() => setShowAddModal(true)} className="btn-primary">
                        + Add Medical Record
                    </button>
                )}
            </div>

            {loading ? (
                <div>Loading...</div>
            ) : (
                <table className="medical-records-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Severity</th>
                            <th>Added By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {records.map(record => (
                            <tr key={record.id}>
                                <td>{new Date(record.created_at).toLocaleDateString()}</td>
                                <td>{record.record_type}</td>
                                <td>{record.diagnosis}</td>
                                <td>
                                    <span className={`severity-badge severity-${record.severity}`}>
                                        {record.severity}
                                    </span>
                                </td>
                                <td>
                                    {record.added_by === 'parent' ? 'You (Parent)' : 
                                     record.added_by === 'medical_staff' ? 'Medical Staff' : 
                                     'Admin'}
                                </td>
                                <td>
                                    <span className={`status-badge status-${record.status}`}>
                                        {record.status}
                                    </span>
                                </td>
                                <td>
                                    {record.can_edit_by_parent && permissions.can_update && (
                                        <button 
                                            onClick={() => handleEdit(record)}
                                            className="btn-edit"
                                        >
                                            Edit
                                        </button>
                                    )}
                                    <button onClick={() => handleView(record)} className="btn-view">
                                        View
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}

            {showAddModal && (
                <AddMedicalRecordModal
                    onClose={() => setShowAddModal(false)}
                    onSubmit={handleAddRecord}
                />
            )}
        </div>
    );
}

export default MedicalRecords;
```

### Add Medical Record Modal

```jsx
// components/Parent/AddMedicalRecordModal.jsx
import React, { useState } from 'react';

function AddMedicalRecordModal({ onClose, onSubmit }) {
    const [formData, setFormData] = useState({
        record_type: '',
        description: '',
        symptoms: '',
        treatment: '',
        medication: '',
        severity: 'low',
        notes: ''
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        onSubmit(formData);
    };

    return (
        <div className="modal-overlay">
            <div className="modal medical-record-modal">
                <h2>Add Medical Record</h2>
                <form onSubmit={handleSubmit}>
                    <div className="form-group">
                        <label>Record Type*</label>
                        <select
                            value={formData.record_type}
                            onChange={(e) => setFormData({...formData, record_type: e.target.value})}
                            required
                        >
                            <option value="">Select Type</option>
                            <option value="allergy">Allergy</option>
                            <option value="illness">Illness</option>
                            <option value="injury">Injury</option>
                            <option value="vaccination">Vaccination</option>
                            <option value="chronic">Chronic Condition</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label>Description*</label>
                        <textarea
                            value={formData.description}
                            onChange={(e) => setFormData({...formData, description: e.target.value})}
                            placeholder="Describe the medical condition..."
                            required
                            rows="3"
                        />
                    </div>

                    <div className="form-group">
                        <label>Symptoms</label>
                        <textarea
                            value={formData.symptoms}
                            onChange={(e) => setFormData({...formData, symptoms: e.target.value})}
                            placeholder="List symptoms observed..."
                            rows="2"
                        />
                    </div>

                    <div className="form-group">
                        <label>Treatment</label>
                        <textarea
                            value={formData.treatment}
                            onChange={(e) => setFormData({...formData, treatment: e.target.value})}
                            placeholder="Treatment received or recommended..."
                            rows="2"
                        />
                    </div>

                    <div className="form-group">
                        <label>Medication</label>
                        <input
                            type="text"
                            value={formData.medication}
                            onChange={(e) => setFormData({...formData, medication: e.target.value})}
                            placeholder="Medication names and dosages..."
                        />
                    </div>

                    <div className="form-group">
                        <label>Severity</label>
                        <select
                            value={formData.severity}
                            onChange={(e) => setFormData({...formData, severity: e.target.value})}
                        >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label>Additional Notes</label>
                        <textarea
                            value={formData.notes}
                            onChange={(e) => setFormData({...formData, notes: e.target.value})}
                            placeholder="Any additional information..."
                            rows="2"
                        />
                    </div>

                    <div className="modal-actions">
                        <button type="button" onClick={onClose} className="btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" className="btn-primary">
                            Add Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default AddMedicalRecordModal;
```

### Parent Status Fix

```jsx
// components/Parent/ParentDashboard.jsx
function ParentDashboard() {
    const [parentInfo, setParentInfo] = useState(null);

    useEffect(() => {
        fetchParentInfo();
    }, []);

    const fetchParentInfo = async () => {
        try {
            const response = await axios.get('/api/parents/profile');
            if (response.data.success) {
                setParentInfo(response.data.parent);
            }
        } catch (error) {
            console.error('Failed to fetch parent info:', error);
        }
    };

    return (
        <div className="parent-dashboard">
            <div className="parent-header">
                <h1>Welcome, {parentInfo?.name}</h1>
                <div className="status-badge status-active">
                    {parentInfo?.status || 'Active'}
                </div>
            </div>
            {/* Rest of dashboard */}
        </div>
    );
}
```

## 3. Login Page Updates

### Role-Based Login Validation

```jsx
// components/Login/AdminLogin.jsx
import axios from 'axios';

function AdminLogin() {
    const handleLogin = async (email, password) => {
        try {
            const response = await axios.post('/api/admin/login', {
                email,
                password,
                loginAs: 'admin' // Specify login type
            });

            if (response.data.success) {
                // Store token and redirect
                localStorage.setItem('token', response.data.token);
                localStorage.setItem('user', JSON.stringify(response.data.account));
                window.location.href = '/admin/dashboard';
            }
        } catch (error) {
            // Show appropriate error message
            if (error.response?.status === 403) {
                alert(error.response.data.message);
            } else {
                alert('Login failed. Please check your credentials.');
            }
        }
    };

    // ... rest of component
}
```

## 4. CSS Styling

```css
/* Medical Records Styles */
.medical-records-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.medical-records-table th,
.medical-records-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.severity-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.severity-low {
    background: #d4edda;
    color: #155724;
}

.severity-medium {
    background: #fff3cd;
    color: #856404;
}

.severity-high {
    background: #f8d7da;
    color: #721c24;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: white;
    border-radius: 8px;
    padding: 30px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-secondary {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
}

.btn-edit {
    background: #ffc107;
    color: #000;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
}

.btn-view {
    background: #17a2b8;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
}
```

## Testing the Integration

1. **Admin Features**:
   - Login as super admin (first admin account)
   - Verify "Admins" tab appears in Users section
   - Create a new admin
   - Verify new admin cannot create other admins
   - Create a principal
   - Verify principal sees all admin data

2. **Principal Features**:
   - Login as principal
   - Verify System Settings tab is hidden
   - Verify access to students, teachers, classes
   - Verify cannot create other principals

3. **Parent Features**:
   - Login as parent
   - Verify status shows "Active"
   - Go to Medical Records tab
   - Add a new medical record
   - Edit the record
   - Verify delete button is disabled

## Deployment Notes

- Ensure all backend migrations are run first
- Update API base URL in frontend environment variables
- Test in development before deploying to production
- Clear browser cache after deployment

---

**Created**: November 24, 2025
**Status**: Ready for Frontend Implementation
