import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Box,
  Paper,
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
  TextField,
  Alert,
  CircularProgress,
  Grid,
  Card,
  CardContent,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Badge
} from '@mui/material';
import {
  Warning as WarningIcon,
  CheckCircle as CheckCircleIcon,
  Info as InfoIcon,
  Error as ErrorIcon,
  Visibility as VisibilityIcon,
  Close as CloseIcon
} from '@mui/icons-material';

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://localhost:8080/api';

const UrgentNotifications = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [notifications, setNotifications] = useState([]);
  const [selectedNotification, setSelectedNotification] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [actionDialogOpen, setActionDialogOpen] = useState(false);
  const [actionNotes, setActionNotes] = useState('');
  const [filters, setFilters] = useState({
    action_taken: '0',
    type: '',
    priority: ''
  });

  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const isPrincipal = user.is_principal === 1;

  useEffect(() => {
    fetchNotifications();
  }, [filters]);

  const fetchNotifications = async () => {
    setLoading(true);
    setError(null);
    try {
      const token = localStorage.getItem('token');
      const params = new URLSearchParams();
      if (filters.action_taken) params.append('action_taken', filters.action_taken);
      if (filters.type) params.append('type', filters.type);
      if (filters.priority) params.append('priority', filters.priority);

      const response = await axios.get(`${API_BASE_URL}/urgent-notifications?${params.toString()}`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.data.success) {
        setNotifications(response.data.notifications);
      }
    } catch (err) {
      setError('Failed to fetch notifications: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const handleViewDetails = (notification) => {
    setSelectedNotification(notification);
    setDetailsOpen(true);
  };

  const handleCloseDetails = () => {
    setDetailsOpen(false);
    setSelectedNotification(null);
  };

  const handleOpenActionDialog = (notification) => {
    setSelectedNotification(notification);
    setActionNotes('');
    setActionDialogOpen(true);
  };

  const handleCloseActionDialog = () => {
    setActionDialogOpen(false);
    setSelectedNotification(null);
    setActionNotes('');
  };

  const handleMarkActionTaken = async () => {
    if (!selectedNotification) return;

    try {
      const token = localStorage.getItem('token');
      const response = await axios.post(
        `${API_BASE_URL}/urgent-notifications/${selectedNotification.id}/action-taken`,
        { notes: actionNotes },
        { headers: { Authorization: `Bearer ${token}` } }
      );

      if (response.data.success) {
        setSuccess('Action marked as taken successfully');
        handleCloseActionDialog();
        fetchNotifications();
      }
    } catch (err) {
      setError('Failed to mark action: ' + (err.response?.data?.message || err.message));
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'critical': return 'error';
      case 'high': return 'warning';
      case 'medium': return 'info';
      case 'low': return 'success';
      default: return 'default';
    }
  };

  const getPriorityIcon = (priority) => {
    switch (priority) {
      case 'critical': return <ErrorIcon />;
      case 'high': return <WarningIcon />;
      case 'medium': return <InfoIcon />;
      case 'low': return <CheckCircleIcon />;
      default: return null;
    }
  };

  const getTypeLabel = (type) => {
    const labels = {
      repeated_absence: 'Repeated Absence',
      payment_overdue: 'Payment Overdue',
      disciplinary: 'Disciplinary',
      health: 'Health',
      academic: 'Academic',
      other: 'Other'
    };
    return labels[type] || type;
  };

  const pendingCount = notifications.filter(n => !n.action_taken).length;

  return (
    <Box sx={{ p: 3 }}>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Badge badgeContent={pendingCount} color="error">
          <Typography variant="h4">
            Urgent Notifications
          </Typography>
        </Badge>
        {!isPrincipal && (
          <Alert severity="info" sx={{ ml: 2 }}>
            Only principals can mark actions as taken
          </Alert>
        )}
      </Box>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
          {error}
        </Alert>
      )}

      {success && (
        <Alert severity="success" sx={{ mb: 2 }} onClose={() => setSuccess(null)}>
          {success}
        </Alert>
      )}

      {/* Summary Cards */}
      <Grid container spacing={2} sx={{ mb: 3 }}>
        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" variant="body2">
                Total Notifications
              </Typography>
              <Typography variant="h4">{notifications.length}</Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" variant="body2">
                Pending Action
              </Typography>
              <Typography variant="h4" color="error">
                {pendingCount}
              </Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" variant="body2">
                Critical Priority
              </Typography>
              <Typography variant="h4" color="error">
                {notifications.filter(n => n.priority === 'critical').length}
              </Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" variant="body2">
                Completed
              </Typography>
              <Typography variant="h4" color="success.main">
                {notifications.filter(n => n.action_taken).length}
              </Typography>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* Filters */}
      <Paper sx={{ p: 2, mb: 2 }}>
        <Grid container spacing={2}>
          <Grid item xs={12} md={4}>
            <FormControl fullWidth>
              <InputLabel>Action Taken</InputLabel>
              <Select
                value={filters.action_taken}
                onChange={(e) => setFilters({ ...filters, action_taken: e.target.value })}
                label="Action Taken"
              >
                <MenuItem value="">All</MenuItem>
                <MenuItem value="0">Pending</MenuItem>
                <MenuItem value="1">Completed</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={4}>
            <FormControl fullWidth>
              <InputLabel>Type</InputLabel>
              <Select
                value={filters.type}
                onChange={(e) => setFilters({ ...filters, type: e.target.value })}
                label="Type"
              >
                <MenuItem value="">All</MenuItem>
                <MenuItem value="repeated_absence">Repeated Absence</MenuItem>
                <MenuItem value="payment_overdue">Payment Overdue</MenuItem>
                <MenuItem value="disciplinary">Disciplinary</MenuItem>
                <MenuItem value="health">Health</MenuItem>
                <MenuItem value="academic">Academic</MenuItem>
                <MenuItem value="other">Other</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={4}>
            <FormControl fullWidth>
              <InputLabel>Priority</InputLabel>
              <Select
                value={filters.priority}
                onChange={(e) => setFilters({ ...filters, priority: e.target.value })}
                label="Priority"
              >
                <MenuItem value="">All</MenuItem>
                <MenuItem value="critical">Critical</MenuItem>
                <MenuItem value="high">High</MenuItem>
                <MenuItem value="medium">Medium</MenuItem>
                <MenuItem value="low">Low</MenuItem>
              </Select>
            </FormControl>
          </Grid>
        </Grid>
      </Paper>

      {/* Notifications List */}
      <Paper>
        {loading ? (
          <Box display="flex" justifyContent="center" p={3}>
            <CircularProgress />
          </Box>
        ) : notifications.length === 0 ? (
          <Box p={3}>
            <Alert severity="info">No urgent notifications found.</Alert>
          </Box>
        ) : (
          <TableContainer>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Priority</TableCell>
                  <TableCell>Type</TableCell>
                  <TableCell>Student</TableCell>
                  <TableCell>Title</TableCell>
                  <TableCell>Created</TableCell>
                  <TableCell>Status</TableCell>
                  <TableCell align="center">Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {notifications.map((notification) => (
                  <TableRow
                    key={notification.id}
                    hover
                    sx={{
                      backgroundColor: notification.action_taken ? 'inherit' : 'rgba(255, 0, 0, 0.02)'
                    }}
                  >
                    <TableCell>
                      <Chip
                        icon={getPriorityIcon(notification.priority)}
                        label={notification.priority.toUpperCase()}
                        color={getPriorityColor(notification.priority)}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={getTypeLabel(notification.type)}
                        variant="outlined"
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      {notification.student_name ? (
                        <Box>
                          <Typography variant="body2" fontWeight="medium">
                            {notification.student_name}
                          </Typography>
                          <Typography variant="caption" color="textSecondary">
                            {notification.id_number}
                          </Typography>
                        </Box>
                      ) : (
                        'N/A'
                      )}
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">{notification.title}</Typography>
                    </TableCell>
                    <TableCell>
                      {new Date(notification.created_at).toLocaleString()}
                    </TableCell>
                    <TableCell>
                      {notification.action_taken ? (
                        <Chip
                          label="Completed"
                          color="success"
                          size="small"
                          icon={<CheckCircleIcon />}
                        />
                      ) : (
                        <Chip
                          label="Pending"
                          color="error"
                          size="small"
                          icon={<WarningIcon />}
                        />
                      )}
                    </TableCell>
                    <TableCell align="center">
                      <IconButton
                        size="small"
                        color="primary"
                        onClick={() => handleViewDetails(notification)}
                      >
                        <VisibilityIcon />
                      </IconButton>
                      {isPrincipal && !notification.action_taken && (
                        <Button
                          size="small"
                          variant="contained"
                          color="primary"
                          onClick={() => handleOpenActionDialog(notification)}
                          sx={{ ml: 1 }}
                        >
                          Mark Action
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        )}
      </Paper>

      {/* Details Dialog */}
      <Dialog open={detailsOpen} onClose={handleCloseDetails} maxWidth="md" fullWidth>
        <DialogTitle>
          Notification Details
          <IconButton
            onClick={handleCloseDetails}
            sx={{ position: 'absolute', right: 8, top: 8 }}
          >
            <CloseIcon />
          </IconButton>
        </DialogTitle>
        <DialogContent dividers>
          {selectedNotification && (
            <Grid container spacing={2}>
              <Grid item xs={12}>
                <Box display="flex" gap={1} mb={2}>
                  <Chip
                    icon={getPriorityIcon(selectedNotification.priority)}
                    label={selectedNotification.priority.toUpperCase()}
                    color={getPriorityColor(selectedNotification.priority)}
                  />
                  <Chip
                    label={getTypeLabel(selectedNotification.type)}
                    variant="outlined"
                  />
                  {selectedNotification.action_taken ? (
                    <Chip label="Completed" color="success" icon={<CheckCircleIcon />} />
                  ) : (
                    <Chip label="Pending" color="error" icon={<WarningIcon />} />
                  )}
                </Box>
              </Grid>
              <Grid item xs={12}>
                <Typography variant="h6" gutterBottom>
                  {selectedNotification.title}
                </Typography>
                <Typography variant="body1" paragraph>
                  {selectedNotification.message}
                </Typography>
              </Grid>
              {selectedNotification.student_name && (
                <>
                  <Grid item xs={12} md={6}>
                    <Typography variant="body2" color="textSecondary">Student</Typography>
                    <Typography variant="body1">{selectedNotification.student_name}</Typography>
                  </Grid>
                  <Grid item xs={12} md={6}>
                    <Typography variant="body2" color="textSecondary">Student ID</Typography>
                    <Typography variant="body1">{selectedNotification.id_number}</Typography>
                  </Grid>
                  <Grid item xs={12} md={6}>
                    <Typography variant="body2" color="textSecondary">Class</Typography>
                    <Typography variant="body1">
                      {selectedNotification.class_name || 'N/A'}
                      {selectedNotification.section && ` - ${selectedNotification.section}`}
                    </Typography>
                  </Grid>
                </>
              )}
              <Grid item xs={12} md={6}>
                <Typography variant="body2" color="textSecondary">Created At</Typography>
                <Typography variant="body1">
                  {new Date(selectedNotification.created_at).toLocaleString()}
                </Typography>
              </Grid>
              {selectedNotification.action_taken && (
                <>
                  <Grid item xs={12} md={6}>
                    <Typography variant="body2" color="textSecondary">Action Taken By</Typography>
                    <Typography variant="body1">
                      {selectedNotification.action_taken_by_name || 'Unknown'}
                    </Typography>
                  </Grid>
                  <Grid item xs={12} md={6}>
                    <Typography variant="body2" color="textSecondary">Action Taken At</Typography>
                    <Typography variant="body1">
                      {new Date(selectedNotification.action_taken_at).toLocaleString()}
                    </Typography>
                  </Grid>
                  {selectedNotification.action_notes && (
                    <Grid item xs={12}>
                      <Typography variant="body2" color="textSecondary">Action Notes</Typography>
                      <Paper variant="outlined" sx={{ p: 2, mt: 1, bgcolor: 'grey.50' }}>
                        <Typography variant="body1">{selectedNotification.action_notes}</Typography>
                      </Paper>
                    </Grid>
                  )}
                </>
              )}
            </Grid>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseDetails}>Close</Button>
        </DialogActions>
      </Dialog>

      {/* Mark Action Dialog */}
      <Dialog open={actionDialogOpen} onClose={handleCloseActionDialog} maxWidth="sm" fullWidth>
        <DialogTitle>Mark Action Taken</DialogTitle>
        <DialogContent dividers>
          {selectedNotification && (
            <>
              <Typography variant="body1" gutterBottom>
                {selectedNotification.title}
              </Typography>
              <TextField
                fullWidth
                multiline
                rows={4}
                label="Action Notes"
                placeholder="Describe the action taken..."
                value={actionNotes}
                onChange={(e) => setActionNotes(e.target.value)}
                sx={{ mt: 2 }}
              />
            </>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseActionDialog}>Cancel</Button>
          <Button
            variant="contained"
            color="primary"
            onClick={handleMarkActionTaken}
            disabled={!actionNotes.trim()}
          >
            Confirm Action Taken
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default UrgentNotifications;
