// Example: How to Update TeachersManagement.jsx

// ============================================
// STEP 1: Add state for modals
// ============================================
const [showClassesModal, setShowClassesModal] = useState(false);
const [showSubjectsModal, setShowSubjectsModal] = useState(false);
const [selectedTeacher, setSelectedTeacher] = useState(null);
const [teacherClasses, setTeacherClasses] = useState([]);
const [teacherSubjects, setTeacherSubjects] = useState([]);

// ============================================
// STEP 2: Add fetch functions
// ============================================
const handleViewClasses = async (teacher) => {
  try {
    const response = await fetch(`/api/teachers/${teacher.id}/classes`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });
    const data = await response.json();
    
    if (data.success) {
      setSelectedTeacher(teacher);
      setTeacherClasses(data.classes);
      setShowClassesModal(true);
    } else {
      alert('Failed to load classes');
    }
  } catch (error) {
    console.error('Error loading classes:', error);
    alert('Error loading classes');
  }
};

const handleViewSubjects = async (teacher) => {
  try {
    const response = await fetch(`/api/teachers/${teacher.id}/subjects`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });
    const data = await response.json();
    
    if (data.success) {
      setSelectedTeacher(teacher);
      setTeacherSubjects(data.subjects);
      setShowSubjectsModal(true);
    } else {
      alert('Failed to load subjects');
    }
  } catch (error) {
    console.error('Error loading subjects:', error);
    alert('Error loading subjects');
  }
};

// ============================================
// STEP 3: Update table columns
// ============================================
const columns = [
  {
    Header: 'Name',
    accessor: 'name',
    Cell: ({ row }) => (
      <div>
        {row.original.first_name} {row.original.last_name}
      </div>
    )
  },
  {
    Header: 'Email',
    accessor: 'email'
  },
  {
    Header: 'Classes',
    accessor: 'classes',
    Cell: ({ row }) => (
      <Button 
        variant="outlined" 
        size="small"
        onClick={() => handleViewClasses(row.original)}
      >
        View Classes
      </Button>
    )
  },
  {
    Header: 'Subjects',
    accessor: 'subjects',
    Cell: ({ row }) => (
      <Button 
        variant="outlined" 
        size="small"
        onClick={() => handleViewSubjects(row.original)}
      >
        View Subjects
      </Button>
    )
  },
  {
    Header: 'Actions',
    accessor: 'actions',
    Cell: ({ row }) => (
      <div>
        <IconButton onClick={() => handleEdit(row.original)}>
          <EditIcon />
        </IconButton>
        <IconButton onClick={() => handleDelete(row.original.id)}>
          <DeleteIcon />
        </IconButton>
      </div>
    )
  }
];

// ============================================
// STEP 4: Add modals at the end of component
// ============================================
return (
  <div>
    {/* Existing content */}
    <Table columns={columns} data={teachers} />
    
    {/* Classes Modal */}
    <Dialog 
      open={showClassesModal} 
      onClose={() => setShowClassesModal(false)}
      maxWidth="md"
      fullWidth
    >
      <DialogTitle>
        {selectedTeacher?.first_name} {selectedTeacher?.last_name}'s Classes
      </DialogTitle>
      <DialogContent>
        {teacherClasses.length === 0 ? (
          <Typography>No classes assigned</Typography>
        ) : (
          <List>
            {teacherClasses.map((cls) => (
              <ListItem key={cls.id}>
                <ListItemText
                  primary={cls.class_name}
                  secondary={`${cls.student_count || 0} students`}
                />
              </ListItem>
            ))}
          </List>
        )}
      </DialogContent>
      <DialogActions>
        <Button onClick={() => setShowClassesModal(false)}>Close</Button>
      </DialogActions>
    </Dialog>
    
    {/* Subjects Modal */}
    <Dialog 
      open={showSubjectsModal} 
      onClose={() => setShowSubjectsModal(false)}
      maxWidth="md"
      fullWidth
    >
      <DialogTitle>
        {selectedTeacher?.first_name} {selectedTeacher?.last_name}'s Subjects
      </DialogTitle>
      <DialogContent>
        {teacherSubjects.length === 0 ? (
          <Typography>No subjects assigned</Typography>
        ) : (
          <List>
            {teacherSubjects.map((subject) => (
              <ListItem key={subject.id}>
                <ListItemText
                  primary={subject.subject_name}
                  secondary={`Class: ${subject.class_name} | Code: ${subject.subject_code}`}
                />
              </ListItem>
            ))}
          </List>
        )}
      </DialogContent>
      <DialogActions>
        <Button onClick={() => setShowSubjectsModal(false)}>Close</Button>
      </DialogActions>
    </Dialog>
  </div>
);

// ============================================
// STEP 5: Update Add/Edit Teacher Form
// ============================================

// In your form component, replace:
<TextField
  label="Name"
  name="name"
  value={formData.name}
  onChange={handleChange}
  required
/>

// With:
<Grid container spacing={2}>
  <Grid item xs={12} sm={6}>
    <TextField
      label="First Name"
      name="first_name"
      value={formData.first_name}
      onChange={handleChange}
      required
      fullWidth
    />
  </Grid>
  <Grid item xs={12} sm={6}>
    <TextField
      label="Last Name"
      name="last_name"
      value={formData.last_name}
      onChange={handleChange}
      required
      fullWidth
    />
  </Grid>
</Grid>

// ============================================
// STEP 6: Update form submission
// ============================================

// When submitting, make sure to send:
const submitData = {
  first_name: formData.first_name,
  last_name: formData.last_name,
  email: formData.email,
  phone: formData.phone,
  // ... other fields
};

// Backend will automatically generate full name from first_name + last_name

// ============================================
// REQUIRED IMPORTS (add if missing)
// ============================================
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  List,
  ListItem,
  ListItemText,
  Button,
  IconButton,
  Grid,
  TextField,
  Typography
} from '@mui/material';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';
