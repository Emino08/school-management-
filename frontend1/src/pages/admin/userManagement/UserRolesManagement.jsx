import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Box,
  Paper,
  Tabs,
  Tab,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
  IconButton,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Alert,
  CircularProgress,
  Grid,
  Card,
  CardContent
} from '@mui/material';
import {
  Visibility as VisibilityIcon,
  Email as EmailIcon,
  Phone as PhoneIcon,
  School as SchoolIcon,
  Business as BusinessIcon,
  Assignment as AssignmentIcon,
  SupervisorAccount as SupervisorAccountIcon
} from '@mui/icons-material';

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://localhost:8080/api';

const UserRolesManagement = () => {
  const [activeTab, setActiveTab] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [summary, setSummary] = useState(null);
  const [teachers, setTeachers] = useState([]);
  const [selectedTeacher, setSelectedTeacher] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);

  const roles = [
    { key: 'town-master', label: 'Town Masters', icon: <BusinessIcon /> },
    { key: 'exam-officer', label: 'Exam Officers', icon: <AssignmentIcon /> },
    { key: 'finance', label: 'Finance Officers', icon: <SchoolIcon /> },
    { key: 'principal', label: 'Principals', icon: <SupervisorAccountIcon /> }
  ];

  const currentRole = roles[activeTab];

  useEffect(() => {
    fetchSummary();
  }, []);

  useEffect(() => {
    if (currentRole) {
      fetchTeachersByRole(currentRole.key);
    }
  }, [activeTab]);

  const fetchSummary = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(`${API_BASE_URL}/admin/users/roles/summary`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      if (response.data.success) {
        setSummary(response.data.summary);
      }
    } catch (err) {
      console.error('Error fetching summary:', err);
    }
  };

  const fetchTeachersByRole = async (role) => {
    setLoading(true);
    setError(null);
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(`${API_BASE_URL}/admin/users/role/${role}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      if (response.data.success) {
        setTeachers(response.data.teachers);
      }
    } catch (err) {
      setError('Failed to fetch teachers: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const handleViewDetails = (teacher) => {
    setSelectedTeacher(teacher);
    setDetailsOpen(true);
  };

  const handleCloseDetails = () => {
    setDetailsOpen(false);
    setSelectedTeacher(null);
  };

  const getRoleBadges = (teacher) => {
    const badges = [];
    if (teacher.is_town_master) badges.push({ label: 'Town Master', color: 'primary' });
    if (teacher.is_exam_officer) badges.push({ label: 'Exam Officer', color: 'secondary' });
    if (teacher.is_finance_officer) badges.push({ label: 'Finance', color: 'success' });
    if (teacher.is_principal) badges.push({ label: 'Principal', color: 'error' });
    return badges;
  };

  return (
    <Box sx={{ p: 3 }}>
      <Typography variant="h4" gutterBottom sx={{ mb: 3 }}>
        Users & Roles Management
      </Typography>

      {/* Summary Cards */}
      {summary && (
        <Grid container spacing={2} sx={{ mb: 3 }}>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center" justifyContent="space-between">
                  <Box>
                    <Typography color="textSecondary" variant="body2">
                      Town Masters
                    </Typography>
                    <Typography variant="h4">{summary.town_masters}</Typography>
                  </Box>
                  <BusinessIcon color="primary" sx={{ fontSize: 40 }} />
                </Box>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center" justifyContent="space-between">
                  <Box>
                    <Typography color="textSecondary" variant="body2">
                      Exam Officers
                    </Typography>
                    <Typography variant="h4">{summary.exam_officers}</Typography>
                  </Box>
                  <AssignmentIcon color="secondary" sx={{ fontSize: 40 }} />
                </Box>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center" justifyContent="space-between">
                  <Box>
                    <Typography color="textSecondary" variant="body2">
                      Finance Officers
                    </Typography>
                    <Typography variant="h4">{summary.finance_officers}</Typography>
                  </Box>
                  <SchoolIcon color="success" sx={{ fontSize: 40 }} />
                </Box>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center" justifyContent="space-between">
                  <Box>
                    <Typography color="textSecondary" variant="body2">
                      Principals
                    </Typography>
                    <Typography variant="h4">{summary.principals}</Typography>
                  </Box>
                  <SupervisorAccountIcon color="error" sx={{ fontSize: 40 }} />
                </Box>
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      )}

      <Paper sx={{ width: '100%' }}>
        <Tabs
          value={activeTab}
          onChange={(e, newValue) => setActiveTab(newValue)}
          variant="fullWidth"
        >
          {roles.map((role, index) => (
            <Tab
              key={role.key}
              label={role.label}
              icon={role.icon}
              iconPosition="start"
            />
          ))}
        </Tabs>

        <Box sx={{ p: 3 }}>
          {error && (
            <Alert severity="error" sx={{ mb: 2 }}>
              {error}
            </Alert>
          )}

          {loading ? (
            <Box display="flex" justifyContent="center" p={3}>
              <CircularProgress />
            </Box>
          ) : (
            <>
              <Typography variant="h6" gutterBottom>
                {currentRole.label} ({teachers.length})
              </Typography>

              {teachers.length === 0 ? (
                <Alert severity="info">
                  No teachers found with {currentRole.label} role.
                </Alert>
              ) : (
                <TableContainer>
                  <Table>
                    <TableHead>
                      <TableRow>
                        <TableCell>Name</TableCell>
                        <TableCell>Email</TableCell>
                        <TableCell>Phone</TableCell>
                        <TableCell>Town</TableCell>
                        <TableCell>Classes</TableCell>
                        <TableCell>Subjects</TableCell>
                        <TableCell>All Roles</TableCell>
                        <TableCell align="center">Actions</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {teachers.map((teacher) => (
                        <TableRow key={teacher.id} hover>
                          <TableCell>
                            <Typography variant="body1" fontWeight="medium">
                              {teacher.first_name} {teacher.last_name}
                            </Typography>
                          </TableCell>
                          <TableCell>
                            <Box display="flex" alignItems="center" gap={1}>
                              <EmailIcon fontSize="small" color="action" />
                              {teacher.email}
                            </Box>
                          </TableCell>
                          <TableCell>
                            <Box display="flex" alignItems="center" gap={1}>
                              <PhoneIcon fontSize="small" color="action" />
                              {teacher.phone || 'N/A'}
                            </Box>
                          </TableCell>
                          <TableCell>
                            {teacher.town_name || 'Not Assigned'}
                          </TableCell>
                          <TableCell>{teacher.class_count}</TableCell>
                          <TableCell>{teacher.subject_count}</TableCell>
                          <TableCell>
                            <Box display="flex" gap={0.5} flexWrap="wrap">
                              {getRoleBadges(teacher).map((badge, idx) => (
                                <Chip
                                  key={idx}
                                  label={badge.label}
                                  size="small"
                                  color={badge.color}
                                />
                              ))}
                            </Box>
                          </TableCell>
                          <TableCell align="center">
                            <IconButton
                              size="small"
                              color="primary"
                              onClick={() => handleViewDetails(teacher)}
                            >
                              <VisibilityIcon />
                            </IconButton>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>
              )}
            </>
          )}
        </Box>
      </Paper>

      {/* Teacher Details Dialog */}
      <Dialog open={detailsOpen} onClose={handleCloseDetails} maxWidth="md" fullWidth>
        <DialogTitle>
          Teacher Details
        </DialogTitle>
        <DialogContent dividers>
          {selectedTeacher && (
            <Grid container spacing={2}>
              <Grid item xs={12}>
                <Typography variant="h6">
                  {selectedTeacher.first_name} {selectedTeacher.last_name}
                </Typography>
                <Box display="flex" gap={1} mt={1}>
                  {getRoleBadges(selectedTeacher).map((badge, idx) => (
                    <Chip
                      key={idx}
                      label={badge.label}
                      color={badge.color}
                    />
                  ))}
                </Box>
              </Grid>
              <Grid item xs={12} md={6}>
                <Typography variant="body2" color="textSecondary">Email</Typography>
                <Typography variant="body1">{selectedTeacher.email}</Typography>
              </Grid>
              <Grid item xs={12} md={6}>
                <Typography variant="body2" color="textSecondary">Phone</Typography>
                <Typography variant="body1">{selectedTeacher.phone || 'N/A'}</Typography>
              </Grid>
              {selectedTeacher.town_name && (
                <Grid item xs={12} md={6}>
                  <Typography variant="body2" color="textSecondary">Assigned Town</Typography>
                  <Typography variant="body1">{selectedTeacher.town_name}</Typography>
                </Grid>
              )}
              <Grid item xs={12} md={6}>
                <Typography variant="body2" color="textSecondary">Classes Teaching</Typography>
                <Typography variant="body1">{selectedTeacher.class_count}</Typography>
              </Grid>
              <Grid item xs={12} md={6}>
                <Typography variant="body2" color="textSecondary">Subjects Teaching</Typography>
                <Typography variant="body1">{selectedTeacher.subject_count}</Typography>
              </Grid>
            </Grid>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseDetails}>Close</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default UserRolesManagement;
