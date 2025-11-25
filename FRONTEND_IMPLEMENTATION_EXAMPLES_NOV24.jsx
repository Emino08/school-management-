// ============================================================================
// FRONTEND IMPLEMENTATION EXAMPLES
// November 24, 2025
// ============================================================================

// ============================================================================
// 1. ADMIN USER MANAGEMENT - Add Admin Tab
// File: frontend1/src/pages/admin/UserManagement.jsx
// ============================================================================

import React, { useState, useEffect } from 'react';
import { Tabs, Tab, Button, Dialog, DialogTitle, DialogContent, DialogActions, TextField } from '@mui/material';

const UserManagement = () => {
  const [currentTab, setCurrentTab] = useState('student');
  const [users, setUsers] = useState([]);
  const [isSuperAdmin, setIsSuperAdmin] = useState(false);
  const [adminModalOpen, setAdminModalOpen] = useState(false);

  // Check if current user is super admin
  useEffect(() => {
    const checkSuperAdmin = async () => {
      const response = await fetch('/api/admin/check-super-admin', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      const data = await response.json();
      setIsSuperAdmin(data.is_super_admin);
    };
    checkSuperAdmin();
  }, []);

  // Fetch users based on tab
  const fetchUsers = async (type) => {
    const response = await fetch(`/api/user-management/users?user_type=${type}`, {
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    const data = await response.json();
    setUsers(data.users || []);
  };

  // Tab configuration
  const tabs = [
    { label: 'Students', value: 'student' },
    { label: 'Teachers', value: 'teacher' },
    { label: 'Parents', value: 'parent' },
    // Only show Admin tab to super admins
    ...(isSuperAdmin ? [{ label: 'Admins', value: 'admin' }] : []),
    { label: 'Finance', value: 'finance' },
    { label: 'Exam Officers', value: 'exam_officer' },
    { label: 'Medical Staff', value: 'medical' },
  ];

  return (
    <div>
      <Tabs value={currentTab} onChange={(e, val) => { setCurrentTab(val); fetchUsers(val); }}>
        {tabs.map(tab => (
          <Tab key={tab.value} label={tab.label} value={tab.value} />
        ))}
      </Tabs>

      {/* Add Admin button - only for super admins on Admin tab */}
      {isSuperAdmin && currentTab === 'admin' && (
        <Button 
          variant="contained" 
          color="primary" 
          onClick={() => setAdminModalOpen(true)}
        >
          Add Admin User
        </Button>
      )}

      {/* User list */}
      <UserList users={users} type={currentTab} />

      {/* Admin creation modal */}
      <AdminCreateModal 
        open={adminModalOpen} 
        onClose={() => setAdminModalOpen(false)}
        onSuccess={() => { fetchUsers('admin'); setAdminModalOpen(false); }}
      />
    </div>
  );
};

// ============================================================================
// 2. ADMIN CREATE MODAL
// Component to create new admin users
// ============================================================================

const AdminCreateModal = ({ open, onClose, onSuccess }) => {
  const [formData, setFormData] = useState({
    contact_name: '',
    email: '',
    password: '',
    phone: ''
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    setError('');
    setLoading(true);

    try {
      const response = await fetch('/api/admin/create-admin', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();

      if (response.ok) {
        onSuccess();
        setFormData({ contact_name: '', email: '', password: '', phone: '' });
      } else {
        setError(data.message || 'Failed to create admin');
      }
    } catch (err) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle>Create Admin User</DialogTitle>
      <DialogContent>
        {error && <Alert severity="error">{error}</Alert>}
        
        <TextField
          label="Full Name"
          value={formData.contact_name}
          onChange={(e) => setFormData({...formData, contact_name: e.target.value})}
          fullWidth
          margin="normal"
          required
        />
        <TextField
          label="Email Address"
          type="email"
          value={formData.email}
          onChange={(e) => setFormData({...formData, email: e.target.value})}
          fullWidth
          margin="normal"
          required
        />
        <TextField
          label="Password"
          type="password"
          value={formData.password}
          onChange={(e) => setFormData({...formData, password: e.target.value})}
          fullWidth
          margin="normal"
          required
          helperText="Minimum 6 characters"
        />
        <TextField
          label="Phone Number"
          value={formData.phone}
          onChange={(e) => setFormData({...formData, phone: e.target.value})}
          fullWidth
          margin="normal"
        />
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose} disabled={loading}>Cancel</Button>
        <Button 
          onClick={handleSubmit} 
          variant="contained" 
          color="primary"
          disabled={loading || !formData.contact_name || !formData.email || !formData.password}
        >
          {loading ? 'Creating...' : 'Create Admin'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

// ============================================================================
// 3. PRINCIPAL SIDEBAR - Remove System Tab
// File: frontend1/src/components/layout/PrincipalSidebar.jsx
// ============================================================================

const PrincipalSidebar = () => {
  const menuItems = [
    { label: 'Dashboard', icon: <DashboardIcon />, path: '/principal/dashboard' },
    { label: 'Students', icon: <PeopleIcon />, path: '/principal/students' },
    { label: 'Teachers', icon: <SchoolIcon />, path: '/principal/teachers' },
    { label: 'Classes', icon: <ClassIcon />, path: '/principal/classes' },
    { label: 'Attendance', icon: <CheckIcon />, path: '/principal/attendance' },
    { label: 'Exams', icon: <AssignmentIcon />, path: '/principal/exams' },
    { label: 'Reports', icon: <BarChartIcon />, path: '/principal/reports' },
    { label: 'Notices', icon: <NotificationsIcon />, path: '/principal/notices' },
    // System Settings removed - principals don't have access
  ];

  return (
    <div>
      {menuItems.map(item => (
        <MenuItem key={item.path} {...item} />
      ))}
    </div>
  );
};

// ============================================================================
// 4. PARENT MEDICAL RECORDS - Add Record Button
// File: frontend1/src/pages/parent/StudentMedicalRecords.jsx
// ============================================================================

const StudentMedicalRecords = ({ studentId }) => {
  const [records, setRecords] = useState([]);
  const [addModalOpen, setAddModalOpen] = useState(false);
  const [editRecord, setEditRecord] = useState(null);

  useEffect(() => {
    fetchRecords();
  }, [studentId]);

  const fetchRecords = async () => {
    const response = await fetch(`/api/parents/medical-records?student_id=${studentId}`, {
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    const data = await response.json();
    setRecords(data.records || []);
  };

  const handleAddRecord = async (recordData) => {
    const response = await fetch('/api/parents/medical-records', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: JSON.stringify({ ...recordData, student_id: studentId })
    });

    if (response.ok) {
      fetchRecords();
      setAddModalOpen(false);
    }
  };

  const handleUpdateRecord = async (recordId, recordData) => {
    const response = await fetch(`/api/parents/medical-records/${recordId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: JSON.stringify(recordData)
    });

    if (response.ok) {
      fetchRecords();
      setEditRecord(null);
    }
  };

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h5">Medical Records</Typography>
        <Button
          variant="contained"
          color="primary"
          startIcon={<AddIcon />}
          onClick={() => setAddModalOpen(true)}
        >
          Add Medical Record
        </Button>
      </Box>

      {/* Records Table */}
      <TableContainer>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Date</TableCell>
              <TableCell>Type</TableCell>
              <TableCell>Description</TableCell>
              <TableCell>Doctor</TableCell>
              <TableCell>Status</TableCell>
              <TableCell>Actions</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {records.map(record => (
              <TableRow key={record.id}>
                <TableCell>{new Date(record.date).toLocaleDateString()}</TableCell>
                <TableCell>{record.record_type}</TableCell>
                <TableCell>{record.description}</TableCell>
                <TableCell>{record.doctor_name}</TableCell>
                <TableCell>
                  <Chip 
                    label={record.status} 
                    color={record.status === 'active' ? 'primary' : 'default'}
                    size="small"
                  />
                </TableCell>
                <TableCell>
                  <IconButton onClick={() => setEditRecord(record)} size="small">
                    <EditIcon />
                  </IconButton>
                  {/* Note: No delete button - parents can only add/update */}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>

      {/* Add/Edit Modal */}
      <MedicalRecordModal
        open={addModalOpen || editRecord !== null}
        onClose={() => { setAddModalOpen(false); setEditRecord(null); }}
        onSubmit={editRecord ? (data) => handleUpdateRecord(editRecord.id, data) : handleAddRecord}
        initialData={editRecord}
      />
    </Box>
  );
};

// ============================================================================
// 5. MEDICAL RECORD MODAL
// Component for adding/editing medical records
// ============================================================================

const MedicalRecordModal = ({ open, onClose, onSubmit, initialData = null }) => {
  const [formData, setFormData] = useState({
    record_type: 'checkup',
    date: new Date().toISOString().split('T')[0],
    description: '',
    diagnosis: '',
    treatment: '',
    doctor_name: '',
    status: 'active'
  });

  useEffect(() => {
    if (initialData) {
      setFormData(initialData);
    }
  }, [initialData]);

  const recordTypes = [
    'checkup', 'illness', 'injury', 'vaccination', 
    'allergy', 'medication', 'other'
  ];

  const statusOptions = ['active', 'resolved', 'ongoing', 'archived'];

  return (
    <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
      <DialogTitle>
        {initialData ? 'Edit Medical Record' : 'Add Medical Record'}
      </DialogTitle>
      <DialogContent>
        <Grid container spacing={2} sx={{ mt: 1 }}>
          <Grid item xs={12} sm={6}>
            <FormControl fullWidth>
              <InputLabel>Record Type</InputLabel>
              <Select
                value={formData.record_type}
                onChange={(e) => setFormData({...formData, record_type: e.target.value})}
                label="Record Type"
              >
                {recordTypes.map(type => (
                  <MenuItem key={type} value={type}>
                    {type.charAt(0).toUpperCase() + type.slice(1)}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField
              label="Date"
              type="date"
              value={formData.date}
              onChange={(e) => setFormData({...formData, date: e.target.value})}
              fullWidth
              InputLabelProps={{ shrink: true }}
            />
          </Grid>
          <Grid item xs={12}>
            <TextField
              label="Description"
              value={formData.description}
              onChange={(e) => setFormData({...formData, description: e.target.value})}
              fullWidth
              multiline
              rows={3}
              required
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField
              label="Diagnosis"
              value={formData.diagnosis}
              onChange={(e) => setFormData({...formData, diagnosis: e.target.value})}
              fullWidth
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField
              label="Doctor Name"
              value={formData.doctor_name}
              onChange={(e) => setFormData({...formData, doctor_name: e.target.value})}
              fullWidth
            />
          </Grid>
          <Grid item xs={12}>
            <TextField
              label="Treatment"
              value={formData.treatment}
              onChange={(e) => setFormData({...formData, treatment: e.target.value})}
              fullWidth
              multiline
              rows={2}
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <FormControl fullWidth>
              <InputLabel>Status</InputLabel>
              <Select
                value={formData.status}
                onChange={(e) => setFormData({...formData, status: e.target.value})}
                label="Status"
              >
                {statusOptions.map(status => (
                  <MenuItem key={status} value={status}>
                    {status.charAt(0).toUpperCase() + status.slice(1)}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
        </Grid>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>Cancel</Button>
        <Button 
          onClick={() => onSubmit(formData)} 
          variant="contained" 
          color="primary"
          disabled={!formData.description}
        >
          {initialData ? 'Update' : 'Add'} Record
        </Button>
      </DialogActions>
    </Dialog>
  );
};

// ============================================================================
// 6. PARENT DASHBOARD - Fix Status Display
// File: frontend1/src/pages/parent/ParentDashboard.jsx
// ============================================================================

const ParentDashboard = () => {
  const [children, setChildren] = useState([]);

  useEffect(() => {
    fetchChildren();
  }, []);

  const fetchChildren = async () => {
    const response = await fetch('/api/parents/children', {
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    const data = await response.json();
    setChildren(data.children || []);
  };

  return (
    <div>
      <Typography variant="h4" gutterBottom>My Children</Typography>
      
      <Grid container spacing={3}>
        {children.map(child => (
          <Grid item xs={12} md={6} key={child.id}>
            <Card>
              <CardContent>
                <Avatar 
                  src={child.photo || '/default-avatar.png'} 
                  sx={{ width: 80, height: 80, mb: 2 }}
                />
                <Typography variant="h6">{child.name}</Typography>
                <Typography color="textSecondary">ID: {child.id_number}</Typography>
                <Typography color="textSecondary">Class: {child.class_name}</Typography>
                
                {/* Fixed: Use enrollment_status instead of status */}
                <Chip 
                  label={child.enrollment_status || 'Active'} 
                  color={
                    child.enrollment_status === 'active' ? 'success' :
                    child.enrollment_status === 'suspended' ? 'error' : 'default'
                  }
                  sx={{ mt: 2 }}
                />
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>
    </div>
  );
};

// ============================================================================
// 7. LOGIN FORMS - Add loginAs Parameter
// ============================================================================

// Admin Login
const AdminLogin = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = async () => {
    const response = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email,
        password,
        loginAs: 'admin' // Enforce admin login
      })
    });

    const data = await response.json();
    if (data.success) {
      localStorage.setItem('token', data.token);
      navigate('/admin/dashboard');
    } else {
      alert(data.message);
    }
  };

  // ... rest of component
};

// Principal Login
const PrincipalLogin = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = async () => {
    const response = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email,
        password,
        loginAs: 'principal' // Enforce principal login
      })
    });

    const data = await response.json();
    if (data.success) {
      localStorage.setItem('token', data.token);
      navigate('/principal/dashboard');
    } else {
      alert(data.message);
    }
  };

  // ... rest of component
};

// ============================================================================
// END OF FRONTEND IMPLEMENTATION EXAMPLES
// ============================================================================
