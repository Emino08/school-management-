# MUI to Shadcn Conversion Guide

## Overview

This guide will help you convert all 32 MUI-based files to Shadcn components.

## Files to Convert

### Admin Pages (17 files)
- [ ] AdminHomePage.js
- [ ] AdminProfile.js
- [ ] classRelated/AddClass.js
- [ ] classRelated/ClassDetails.js
- [ ] classRelated/ShowClasses.js
- [ ] noticeRelated/AddNotice.js
- [ ] noticeRelated/ShowNotices.js
- [ ] studentRelated/AddStudent.js
- [ ] studentRelated/SeeComplains.js
- [ ] studentRelated/ShowStudents.js
- [ ] studentRelated/StudentAttendance.js
- [ ] studentRelated/StudentExamMarks.js
- [ ] studentRelated/ViewStudent.js
- [ ] subjectRelated/ShowSubjects.js
- [ ] subjectRelated/SubjectForm.js
- [ ] subjectRelated/ViewSubject.js
- [ ] teacherRelated/AddTeacher.js
- [ ] teacherRelated/ChooseClass.js
- [ ] teacherRelated/ChooseSubject.js
- [ ] teacherRelated/ShowTeachers.js
- [ ] teacherRelated/TeacherDetails.js

### Teacher Pages (5 files)
- [ ] TeacherClassDetails.js
- [ ] TeacherHomePage.js
- [ ] TeacherProfile.js
- [ ] TeacherSideBar.js
- [ ] TeacherViewStudent.js

### Student Pages (6 files)
- [ ] StudentComplain.js
- [ ] StudentHomePage.js
- [ ] StudentProfile.js
- [ ] StudentSideBar.js
- [ ] StudentSubjects.js
- [ ] ViewStdAttendance.js

## Step-by-Step Conversion Process

### Step 1: Install Missing Shadcn Components

```bash
cd frontend1

# Install additional components needed
npx shadcn-ui@latest add collapsible
npx shadcn-ui@latest add tooltip
npx shadcn-ui@latest add popover
npx shadcn-ui@latest add accordion
npx shadcn-ui@latest add progress
```

### Step 2: For Each File

1. **Open the file**
2. **Update imports**:
   - Replace `@mui/material` imports
   - Replace `@mui/icons-material` with `lucide-react`
   - Add `import { cn } from "@/lib/utils"` if using className

3. **Convert Components**:

#### Button
```jsx
// OLD
<Button variant="contained" color="primary">Click</Button>

// NEW
<Button variant="default">Click</Button>
```

#### TextField
```jsx
// OLD
<TextField label="Name" value={name} onChange={handleChange} />

// NEW
<div className="space-y-2">
  <Label htmlFor="name">Name</Label>
  <Input id="name" value={name} onChange={handleChange} />
</div>
```

#### Select
```jsx
// OLD
<Select value={value} onChange={handleChange}>
  <MenuItem value="1">Option 1</MenuItem>
</Select>

// NEW
<Select value={value} onValueChange={handleChange}>
  <SelectTrigger>
    <SelectValue placeholder="Select option" />
  </SelectTrigger>
  <SelectContent>
    <SelectItem value="1">Option 1</SelectItem>
  </SelectContent>
</Select>
```

#### Table
```jsx
// OLD
<Table>
  <TableHead>
    <TableRow>
      <TableCell>Header</TableCell>
    </TableRow>
  </TableHead>
  <TableBody>
    <TableRow>
      <TableCell>Data</TableCell>
    </TableRow>
  </TableBody>
</Table>

// NEW
<Table>
  <TableHeader>
    <TableRow>
      <TableHead>Header</TableHead>
    </TableRow>
  </TableHeader>
  <TableBody>
    <TableRow>
      <TableCell>Data</TableCell>
    </TableRow>
  </TableBody>
</Table>
```

#### Box/Container/Stack
```jsx
// OLD
<Box sx={{ display: 'flex', gap: 2 }}>
  Content
</Box>

// NEW
<div className="flex gap-2">
  Content
</div>
```

#### Typography
```jsx
// OLD
<Typography variant="h1">Heading</Typography>
<Typography variant="body1">Text</Typography>

// NEW
<h1 className="text-4xl font-bold">Heading</h1>
<p className="text-base">Text</p>
```

#### Dialog
```jsx
// OLD
<Dialog open={open} onClose={handleClose}>
  <DialogTitle>Title</DialogTitle>
  <DialogContent>Content</DialogContent>
  <DialogActions>
    <Button onClick={handleClose}>Close</Button>
  </DialogActions>
</Dialog>

// NEW
<Dialog open={open} onOpenChange={setOpen}>
  <DialogContent>
    <DialogHeader>
      <DialogTitle>Title</DialogTitle>
    </DialogHeader>
    <div>Content</div>
    <DialogFooter>
      <Button onClick={handleClose}>Close</Button>
    </DialogFooter>
  </DialogContent>
</Dialog>
```

#### Collapse
```jsx
// OLD
<Collapse in={open}>
  Content
</Collapse>

// NEW
<Collapsible open={open} onOpenChange={setOpen}>
  <CollapsibleContent>
    Content
  </CollapsibleContent>
</Collapsible>
```

#### Icons
```jsx
// OLD
import { KeyboardArrowDown, KeyboardArrowUp } from "@mui/icons-material";
<KeyboardArrowDown />
<KeyboardArrowUp />

// NEW
import { ChevronDown, ChevronUp } from "lucide-react";
<ChevronDown />
<ChevronUp />
```

### Step 3: Handle Special Cases

#### Grid Layout
```jsx
// OLD
<Grid container spacing={2}>
  <Grid item xs={12} md={6}>Item 1</Grid>
  <Grid item xs={12} md={6}>Item 2</Grid>
</Grid>

// NEW
<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>Item 1</div>
  <div>Item 2</div>
</div>
```

#### FormControl
```jsx
// OLD
<FormControl fullWidth>
  <InputLabel>Label</InputLabel>
  <Select>...</Select>
</FormControl>

// NEW
<div className="w-full space-y-2">
  <Label>Label</Label>
  <Select>...</Select>
</div>
```

#### Card
```jsx
// OLD
<Card>
  <CardContent>
    <Typography variant="h5">Title</Typography>
    <Typography>Description</Typography>
  </CardContent>
  <CardActions>
    <Button>Action</Button>
  </CardActions>
</Card>

// NEW
<Card>
  <CardHeader>
    <CardTitle>Title</CardTitle>
    <CardDescription>Description</CardDescription>
  </CardHeader>
  <CardFooter>
    <Button>Action</Button>
  </CardFooter>
</Card>
```

### Step 4: Testing Each File

After converting each file:

1. Start the dev server: `npm run dev`
2. Navigate to the page
3. Test all interactions:
   - [ ] Buttons click correctly
   - [ ] Forms submit
   - [ ] Dialogs open/close
   - [ ] Tables display data
   - [ ] Navigation works
   - [ ] Responsive layout works

### Step 5: Common Tailwind Classes

Use these common classes to replace MUI styles:

| MUI Style | Tailwind Class |
|-----------|----------------|
| `fullWidth` | `w-full` |
| `mt={2}` | `mt-2` |
| `mb={2}` | `mb-2` |
| `ml={2}` | `ml-2` |
| `mr={2}` | `mr-2` |
| `p={2}` | `p-2` |
| `display: flex` | `flex` |
| `flexDirection: column` | `flex-col` |
| `justifyContent: center` | `justify-center` |
| `alignItems: center` | `items-center` |
| `gap: 2` | `gap-2` |
| `textAlign: center` | `text-center` |

### Step 6: Remove MUI Dependencies

After all files are converted and tested:

```bash
npm uninstall @mui/material @mui/icons-material @emotion/react @emotion/styled
```

## Conversion Priority

Start with these files in order:

1. **Simple pages first** (HomePage, Profile)
2. **List/Table pages** (ShowStudents, ShowTeachers, etc.)
3. **Form pages** (AddStudent, AddTeacher, etc.)
4. **Complex pages** (ViewStudent, ClassDetails, etc.)

## Troubleshooting

### Issue: Component not rendering
- Check import paths
- Verify component name is correct
- Check Shadcn component is installed

### Issue: Styling looks wrong
- Review Tailwind classes
- Check className syntax
- Use `cn()` utility for conditional classes

### Issue: Events not firing
- Check event handler names (onClick vs onValueChange)
- Verify state updates are correct

## Example: Complete File Conversion

See `EXAMPLE_CONVERSION.md` for a complete before/after example of TeacherViewStudent.js

## Progress Tracking

Use this checklist to track your progress:

- [ ] Step 1: Install missing components
- [ ] Step 2: Convert Admin pages (0/17)
- [ ] Step 3: Convert Teacher pages (0/5)
- [ ] Step 4: Convert Student pages (0/6)
- [ ] Step 5: Test all pages
- [ ] Step 6: Remove MUI dependencies

## Estimated Time

- Simple file: 10-15 minutes
- Medium complexity: 20-30 minutes
- Complex file: 30-45 minutes

**Total estimated time: 8-12 hours**

## Tips

1. Convert one file at a time
2. Test immediately after converting
3. Keep MUI docs and Shadcn docs open
4. Use search/replace for common patterns
5. Don't forget to update imports
6. Test responsive layout on mobile

## Need Help?

- Shadcn Docs: https://ui.shadcn.com
- Tailwind Docs: https://tailwindcss.com/docs
- Lucide Icons: https://lucide.dev/icons
- See `MUI_TO_SHADCN_MAPPING.md` for detailed mappings
