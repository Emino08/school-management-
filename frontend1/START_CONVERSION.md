# Quick Start: MUI to Shadcn Conversion

## Immediate Next Steps

### Step 1: Install Required Component (2 minutes)

```bash
cd frontend1
npx shadcn-ui@latest add collapsible
```

### Step 2: Choose Your First File (1 minute)

**Recommended order (easiest to hardest):**

1. ✅ **Start Here:** `src/pages/admin/AdminHomePage.js`
2. `src/pages/teacher/TeacherHomePage.js`
3. `src/pages/student/StudentHomePage.js`
4. `src/pages/admin/AdminProfile.js`
5. Then move to other files...

### Step 3: Conversion Workflow

For each file, follow these 5 steps:

#### 1️⃣ Backup (30 seconds)
```bash
# Create a backup
cp src/pages/admin/AdminHomePage.js src/pages/admin/AdminHomePage.js.backup
```

#### 2️⃣ Open Both References (1 minute)
- Open the file you're converting
- Open `EXAMPLE_CONVERSION.md` for reference
- Open `MUI_TO_SHADCN_MAPPING.md` in another tab

#### 3️⃣ Convert Imports (2-3 minutes)

**Find and replace:**

```jsx
// FIND THIS
import { Button, TextField, Box, Typography, Table, TableBody, TableCell, TableHead, TableRow } from "@mui/material";
import { Delete, Edit, Add } from "@mui/icons-material";

// REPLACE WITH
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Trash2, Pencil, Plus } from "lucide-react";
import { cn } from "@/lib/utils";
```

#### 4️⃣ Convert Components (10-30 minutes)

**Use Find & Replace in your editor:**

| Find | Replace |
|------|---------|
| `<Box` | `<div` |
| `</Box>` | `</div>` |
| `<TableHead>` (wrapper) | `<TableHeader>` |
| `variant="contained"` | `variant="default"` |
| `<KeyboardArrowDown />` | `<ChevronDown className="h-4 w-4" />` |
| `<KeyboardArrowUp />` | `<ChevronUp className="h-4 w-4" />` |

**Manual conversions needed:**
- Typography → HTML tags with Tailwind
- sx prop → className with Tailwind
- Collapse → Collapsible

#### 5️⃣ Test (5 minutes)

```bash
# Start dev server
npm run dev

# Navigate to the page and test:
- Page loads without errors
- All buttons work
- Forms submit
- Tables display
- Responsive layout works
```

## Example: Converting AdminHomePage.js

### What You'll Change

**Imports (Before):**
```jsx
import { Container, Grid, Paper, Typography } from "@mui/material";
```

**Imports (After):**
```jsx
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
```

**Component (Before):**
```jsx
<Container>
  <Grid container spacing={3}>
    <Grid item xs={12} md={6}>
      <Paper>
        <Typography variant="h5">Dashboard</Typography>
        <Typography>Welcome to admin panel</Typography>
      </Paper>
    </Grid>
  </Grid>
</Container>
```

**Component (After):**
```jsx
<div className="container mx-auto p-6">
  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
    <Card>
      <CardHeader>
        <CardTitle>Dashboard</CardTitle>
      </CardHeader>
      <CardContent>
        <p>Welcome to admin panel</p>
      </CardContent>
    </Card>
  </div>
</div>
```

## Common Conversions Cheat Sheet

### Typography

| MUI | Shadcn/Tailwind |
|-----|-----------------|
| `<Typography variant="h1">` | `<h1 className="text-4xl font-bold">` |
| `<Typography variant="h2">` | `<h2 className="text-3xl font-bold">` |
| `<Typography variant="h3">` | `<h3 className="text-2xl font-bold">` |
| `<Typography variant="h4">` | `<h4 className="text-xl font-bold">` |
| `<Typography variant="h5">` | `<h5 className="text-lg font-semibold">` |
| `<Typography variant="h6">` | `<h6 className="text-base font-semibold">` |
| `<Typography variant="body1">` | `<p className="text-base">` |
| `<Typography variant="body2">` | `<p className="text-sm">` |
| `<Typography variant="caption">` | `<span className="text-xs text-muted-foreground">` |

### Layout

| MUI | Tailwind |
|-----|----------|
| `<Container>` | `<div className="container mx-auto">` |
| `<Box sx={{ p: 2 }}>` | `<div className="p-2">` |
| `<Box sx={{ mt: 2 }}>` | `<div className="mt-2">` |
| `<Box sx={{ display: 'flex' }}>` | `<div className="flex">` |
| `<Stack spacing={2}>` | `<div className="flex flex-col gap-2">` |
| `<Grid container spacing={2}>` | `<div className="grid gap-2">` |
| `<Grid item xs={12} md={6}>` | `<div className="col-span-12 md:col-span-6">` |

### Buttons

| MUI | Shadcn |
|-----|--------|
| `<Button variant="contained">` | `<Button variant="default">` |
| `<Button variant="outlined">` | `<Button variant="outline">` |
| `<Button variant="text">` | `<Button variant="ghost">` |
| `<Button color="primary">` | `<Button variant="default">` |
| `<Button size="small">` | `<Button size="sm">` |
| `<Button size="large">` | `<Button size="lg">` |

### Icons

| MUI | Lucide |
|-----|--------|
| `import { Delete } from "@mui/icons-material"` | `import { Trash2 } from "lucide-react"` |
| `<Delete />` | `<Trash2 className="h-4 w-4" />` |
| `<Edit />` | `<Pencil className="h-4 w-4" />` |
| `<Add />` | `<Plus className="h-4 w-4" />` |

## Quick Testing Checklist

After converting each file, verify:

- [ ] ✅ No console errors
- [ ] ✅ Page renders correctly
- [ ] ✅ All text is visible and readable
- [ ] ✅ Buttons are clickable
- [ ] ✅ Forms work (if any)
- [ ] ✅ Tables display data (if any)
- [ ] ✅ Modals open/close (if any)
- [ ] ✅ Navigation works
- [ ] ✅ Responsive on mobile (resize browser)

## Progress Tracker

Check off as you complete each file:

### Admin Pages
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

### Teacher Pages
- [ ] TeacherClassDetails.js
- [ ] TeacherHomePage.js
- [ ] TeacherProfile.js
- [ ] TeacherSideBar.js
- [ ] TeacherViewStudent.js

### Student Pages
- [ ] StudentComplain.js
- [ ] StudentHomePage.js
- [ ] StudentProfile.js
- [ ] StudentSideBar.js
- [ ] StudentSubjects.js
- [ ] ViewStdAttendance.js

## Need Help?

1. **For component mappings:** See `MUI_TO_SHADCN_MAPPING.md`
2. **For step-by-step guide:** See `MUI_CONVERSION_GUIDE.md`
3. **For complete example:** See `EXAMPLE_CONVERSION.md`
4. **For full summary:** See `MUI_CONVERSION_SUMMARY.md`

## Ready to Start?

Run this command now:

```bash
cd frontend1
npx shadcn-ui@latest add collapsible
```

Then open your first file and start converting!

**Good luck!** 🚀

---

**Tip:** Work in small increments. Convert one file, test it, commit it (if using git), then move to the next. Don't try to convert everything at once!
