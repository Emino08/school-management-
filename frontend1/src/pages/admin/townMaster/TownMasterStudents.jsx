import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Box,
  Paper,
  Typography,
  TextField,
  Button,
  IconButton,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Alert,
  CircularProgress,
  Grid,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  Chip,
  InputAdornment,
  FormControl,
  InputLabel,
  Select,
  MenuItem
} from '@mui/material';
import {
  Search as SearchIcon,
  ExpandMore as ExpandMoreIcon,
  Email as EmailIcon,
  Phone as PhoneIcon,
  LocationOn as LocationOnIcon,
  Star as StarIcon,
  Visibility as VisibilityIcon,
  FilterList as FilterListIcon
} from '@mui/icons-material';

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://localhost:8080/api';

const TownMasterStudents = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [students, setStudents] = useState([]);
  const [filteredStudents, setFilteredStudents] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedBlockId, setSelectedBlockId] = useState('');
  const [expandedStudentId, setExpandedStudentId] = useState(null);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [blocks, setBlocks] = useState([]);

  useEffect(() => {
    fetchStudents();
  }, [selectedBlockId]);

  useEffect(() => {
    filterStudents();
  }, [students, searchTerm]);

  const fetchStudents = async () => {
    setLoading(true);
    setError(null);
    try {
      const token = localStorage.getItem('token');
      const params = selectedBlockId ? `?block_id=${selectedBlockId}` : '';
      const response = await axios.get(`${API_BASE_URL}/town-master/students${params}`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.data.success) {
        setStudents(response.data.students);
        
        const uniqueBlocks = [...new Set(
          response.data.students
            .filter(s => s.block_id && s.block_name)
            .map(s => JSON.stringify({ id: s.block_id, name: s.block_name }))
        )].map(b => JSON.parse(b));
        setBlocks(uniqueBlocks);
      }
    } catch (err) {
      setError('Failed to fetch students: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const filterStudents = () => {
    if (!searchTerm.trim()) {
      setFilteredStudents(students);
      return;
    }

    const term = searchTerm.toLowerCase();
    const filtered = students.filter(student =>
      student.id_number?.toLowerCase().includes(term) ||
      student.first_name?.toLowerCase().includes(term) ||
      student.last_name?.toLowerCase().includes(term) ||
      student.name?.toLowerCase().includes(term)
    );
    setFilteredStudents(filtered);
  };

  const handleViewFullDetails = async (studentId) => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(`${API_BASE_URL}/town-master/students/${studentId}`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.data.success) {
        setSelectedStudent(response.data.student);
        setDetailsOpen(true);
      }
    } catch (err) {
      setError('Failed to fetch student details: ' + (err.response?.data?.message || err.message));
    }
  };

  const handleCloseDetails = () => {
    setDetailsOpen(false);
    setSelectedStudent(null);
  };

  const handleExpandStudent = (studentId) => {
    setExpandedStudentId(expandedStudentId === studentId ? null : studentId);
  };

  const groupByBlock = (studentsList) => {
    const grouped = {};
    studentsList.forEach(student => {
      const blockName = student.block_name || 'Unassigned';
      if (!grouped[blockName]) {
        grouped[blockName] = [];
      }
      grouped[blockName].push(student);
    });
    return grouped;
  };

  const groupedStudents = groupByBlock(filteredStudents);

  return (
    <Box sx={{ p: 3 }}>
      <Typography variant="h4" gutterBottom>
        My Students
      </Typography>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
          {error}
        </Alert>
      )}

      <Paper sx={{ p: 2, mb: 2 }}>
        <Grid container spacing={2}>
          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              placeholder="Search by ID or Name..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              InputProps={{
                startAdornment: (
                  <InputAdornment position="start">
                    <SearchIcon />
                  </InputAdornment>
                )
              }}
            />
          </Grid>
          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <InputLabel>Filter by Block</InputLabel>
              <Select
                value={selectedBlockId}
                onChange={(e) => setSelectedBlockId(e.target.value)}
                label="Filter by Block"
              >
                <MenuItem value="">All Blocks</MenuItem>
                {blocks.map(block => (
                  <MenuItem key={block.id} value={block.id}>
                    {block.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
        </Grid>
      </Paper>

      {loading ? (
        <Box display="flex" justifyContent="center" p={3}>
          <CircularProgress />
        </Box>
      ) : filteredStudents.length === 0 ? (
        <Alert severity="info">
          {searchTerm ? 'No students found matching your search.' : 'No students registered in your town yet.'}
        </Alert>
      ) : (
        <Box>
          {Object.entries(groupedStudents).map(([blockName, blockStudents]) => (
            <Paper key={blockName} sx={{ mb: 2 }}>
              <Box sx={{ p: 2, bgcolor: 'primary.main', color: 'white' }}>
                <Typography variant="h6">
                  {blockName} ({blockStudents.length} students)
                </Typography>
              </Box>
              
              {blockStudents.map((student) => (
                <Accordion
                  key={student.id}
                  expanded={expandedStudentId === student.id}
                  onChange={() => handleExpandStudent(student.id)}
                >
                  <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Box sx={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'space-between', pr: 2 }}>
                      <Box>
                        <Typography variant="subtitle1" fontWeight="medium">
                          {student.id_number} - {student.first_name} {student.last_name}
                        </Typography>
                        <Typography variant="body2" color="textSecondary">
                          {student.class_name && `${student.class_name}${student.section ? `-${student.section}` : ''}`}
                        </Typography>
                      </Box>
                      <Chip
                        label={`${student.parents?.length || 0} Parent(s)`}
                        size="small"
                        color="primary"
                        variant="outlined"
                      />
                    </Box>
                  </AccordionSummary>
                  
                  <AccordionDetails>
                    <Grid container spacing={2}>
                      <Grid item xs={12}>
                        <Typography variant="subtitle2" color="primary" gutterBottom>
                          Student Contact Information
                        </Typography>
                      </Grid>
                      {student.phone && (
                        <Grid item xs={12} md={4}>
                          <Box display="flex" alignItems="center" gap={1}>
                            <PhoneIcon fontSize="small" color="action" />
                            <Typography variant="body2">{student.phone}</Typography>
                          </Box>
                        </Grid>
                      )}
                      {student.email && (
                        <Grid item xs={12} md={4}>
                          <Box display="flex" alignItems="center" gap={1}>
                            <EmailIcon fontSize="small" color="action" />
                            <Typography variant="body2">{student.email}</Typography>
                          </Box>
                        </Grid>
                      )}
                      {student.address && (
                        <Grid item xs={12} md={4}>
                          <Box display="flex" alignItems="center" gap={1}>
                            <LocationOnIcon fontSize="small" color="action" />
                            <Typography variant="body2">{student.address}</Typography>
                          </Box>
                        </Grid>
                      )}

                      {student.parents && student.parents.length > 0 && (
                        <>
                          <Grid item xs={12} sx={{ mt: 2 }}>
                            <Typography variant="subtitle2" color="primary" gutterBottom>
                              Parents/Guardians
                            </Typography>
                          </Grid>
                          {student.parents.map((parent, index) => (
                            <Grid item xs={12} key={index}>
                              <Paper variant="outlined" sx={{ p: 2, bgcolor: 'grey.50' }}>
                                <Box display="flex" alignItems="center" gap={1} mb={1}>
                                  <Typography variant="subtitle2" fontWeight="medium">
                                    {parent.first_name} {parent.last_name}
                                  </Typography>
                                  <Chip
                                    label={parent.relationship}
                                    size="small"
                                    color="secondary"
                                  />
                                </Box>
                                <Grid container spacing={1}>
                                  {parent.email && (
                                    <Grid item xs={12} md={4}>
                                      <Box display="flex" alignItems="center" gap={1}>
                                        <EmailIcon fontSize="small" color="action" />
                                        <Typography variant="body2">{parent.email}</Typography>
                                      </Box>
                                    </Grid>
                                  )}
                                  {parent.phone && (
                                    <Grid item xs={12} md={4}>
                                      <Box display="flex" alignItems="center" gap={1}>
                                        <PhoneIcon fontSize="small" color="action" />
                                        <Typography variant="body2">{parent.phone}</Typography>
                                      </Box>
                                    </Grid>
                                  )}
                                  {parent.address && (
                                    <Grid item xs={12} md={4}>
                                      <Box display="flex" alignItems="center" gap={1}>
                                        <LocationOnIcon fontSize="small" color="action" />
                                        <Typography variant="body2">{parent.address}</Typography>
                                      </Box>
                                    </Grid>
                                  )}
                                </Grid>
                              </Paper>
                            </Grid>
                          ))}
                        </>
                      )}

                      <Grid item xs={12} sx={{ mt: 2 }}>
                        <Button
                          variant="outlined"
                          startIcon={<VisibilityIcon />}
                          onClick={() => handleViewFullDetails(student.id)}
                        >
                          View Full Details
                        </Button>
                      </Grid>
                    </Grid>
                  </AccordionDetails>
                </Accordion>
              ))}
            </Paper>
          ))}
        </Box>
      )}

      <Dialog open={detailsOpen} onClose={handleCloseDetails} maxWidth="md" fullWidth>
        <DialogTitle>Student Details</DialogTitle>
        <DialogContent dividers>
          {selectedStudent && (
            <Grid container spacing={2}>
              <Grid item xs={12}>
                <Typography variant="h6">
                  {selectedStudent.first_name} {selectedStudent.last_name}
                </Typography>
                <Typography variant="body2" color="textSecondary">
                  ID: {selectedStudent.id_number}
                </Typography>
              </Grid>
              <Grid item xs={12} md={6}>
                <Typography variant="body2" color="textSecondary">Date of Birth</Typography>
                <Typography variant="body1">{selectedStudent.date_of_birth}</Typography>
              </Grid>
              <Grid item xs={12} md={6}>
                <Typography variant="body2" color="textSecondary">Gender</Typography>
                <Typography variant="body1">{selectedStudent.gender}</Typography>
              </Grid>
              {selectedStudent.parents && selectedStudent.parents.length > 0 && (
                <>
                  <Grid item xs={12} sx={{ mt: 2 }}>
                    <Typography variant="subtitle1" color="primary">
                      Parents/Guardians
                    </Typography>
                  </Grid>
                  {selectedStudent.parents.map((parent, index) => (
                    <Grid item xs={12} key={index}>
                      <Paper variant="outlined" sx={{ p: 2, bgcolor: 'grey.50' }}>
                        <Box display="flex" alignItems="center" gap={1} mb={1}>
                          <Typography variant="subtitle2" fontWeight="medium">
                            {parent.first_name} {parent.last_name}
                          </Typography>
                          <Chip label={parent.relationship} size="small" color="secondary" />
                          {parent.is_primary_contact === 1 && (
                            <Chip icon={<StarIcon />} label="Primary" size="small" color="warning" />
                          )}
                        </Box>
                        <Grid container spacing={1}>
                          <Grid item xs={12} md={6}>
                            <Typography variant="body2" color="textSecondary">Email</Typography>
                            <Typography variant="body2">{parent.email || 'N/A'}</Typography>
                          </Grid>
                          <Grid item xs={12} md={6}>
                            <Typography variant="body2" color="textSecondary">Phone</Typography>
                            <Typography variant="body2">{parent.phone || 'N/A'}</Typography>
                          </Grid>
                        </Grid>
                      </Paper>
                    </Grid>
                  ))}
                </>
              )}
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

export default TownMasterStudents;
