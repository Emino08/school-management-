# Example: TeacherViewStudent.js Conversion

## Before (MUI)

```jsx
import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { getUserDetails } from "../../redux/userRelated/userHandle";
import { useNavigate, useParams } from "react-router-dom";
import {
  Box,
  Button,
  Collapse,
  Table,
  TableBody,
  TableHead,
  Typography,
} from "@mui/material";
import { KeyboardArrowDown, KeyboardArrowUp } from "@mui/icons-material";
```

## After (Shadcn)

```jsx
import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { getUserDetails } from "../../redux/userRelated/userHandle";
import { useNavigate, useParams } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";
import { ChevronDown, ChevronUp } from "lucide-react";
import { cn } from "@/lib/utils";
```

## Component Conversions

### 1. Box → div with className

**Before:**
```jsx
<Box sx={{ margin: 1 }}>
  Content
</Box>
```

**After:**
```jsx
<div className="m-4">
  Content
</div>
```

### 2. Typography → HTML tags with Tailwind

**Before:**
```jsx
<Typography variant="h6" gutterBottom component="div">
  Attendance Details
</Typography>
```

**After:**
```jsx
<h6 className="text-lg font-semibold mb-2">
  Attendance Details
</h6>
```

### 3. Button with Icons

**Before:**
```jsx
<Button variant="contained" onClick={() => handleOpen(subId)}>
  {openStates[subId] ? (
    <KeyboardArrowUp />
  ) : (
    <KeyboardArrowDown />
  )}
  Details
</Button>
```

**After:**
```jsx
<Button variant="default" onClick={() => handleOpen(subId)}>
  {openStates[subId] ? (
    <ChevronUp className="mr-2 h-4 w-4" />
  ) : (
    <ChevronDown className="mr-2 h-4 w-4" />
  )}
  Details
</Button>
```

### 4. Collapse → Collapsible

**Before:**
```jsx
<Collapse in={openStates[subId]} timeout="auto" unmountOnExit>
  <Box sx={{ margin: 1 }}>
    Content
  </Box>
</Collapse>
```

**After:**
```jsx
<Collapsible open={openStates[subId]}>
  <CollapsibleContent>
    <div className="m-4">
      Content
    </div>
  </CollapsibleContent>
</Collapsible>
```

### 5. Table Components

**Before:**
```jsx
<Table>
  <TableHead>
    <StyledTableRow>
      <StyledTableCell>Subject</StyledTableCell>
    </StyledTableRow>
  </TableHead>
  <TableBody>
    <StyledTableRow>
      <StyledTableCell>{subName}</StyledTableCell>
    </StyledTableRow>
  </TableBody>
</Table>
```

**After:**
```jsx
<Table>
  <TableHeader>
    <TableRow>
      <TableHead>Subject</TableHead>
    </TableRow>
  </TableHeader>
  <TableBody>
    <TableRow>
      <TableCell>{subName}</TableCell>
    </TableRow>
  </TableBody>
</Table>
```

## Full Converted File

```jsx
import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { getUserDetails } from "../../redux/userRelated/userHandle";
import { useNavigate, useParams } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Collapsible,
  CollapsibleContent,
} from "@/components/ui/collapsible";
import { ChevronDown, ChevronUp } from "lucide-react";
import {
  calculateOverallAttendancePercentage,
  calculateSubjectAttendancePercentage,
  groupAttendanceBySubject,
} from "../../components/attendanceCalculator";
import CustomPieChart from "../../components/CustomPieChart";
import { cn } from "@/lib/utils";

const TeacherViewStudent = () => {
  const navigate = useNavigate();
  const params = useParams();
  const dispatch = useDispatch();
  const { currentUser, userDetails, response, loading, error } = useSelector(
    (state) => state.user,
  );

  const address = "Student";
  const studentID = params.id;
  const teachSubject = currentUser.teachSubject?.subName;
  const teachSubjectID = currentUser.teachSubject?._id;

  useEffect(() => {
    dispatch(getUserDetails(studentID, address));
  }, [dispatch, studentID]);

  if (response) {
    console.log(response);
  } else if (error) {
    console.log(error);
  }

  console.log("userDetails", userDetails, "currentUser", currentUser);
  const [sclassName, setSclassName] = useState("");
  const [studentSchool, setStudentSchool] = useState("");
  const [subjectMarks, setSubjectMarks] = useState("");
  const [subjectAttendance, setSubjectAttendance] = useState([]);

  const [openStates, setOpenStates] = useState({});

  const handleOpen = (subId) => {
    setOpenStates((prevState) => ({
      ...prevState,
      [subId]: !prevState[subId],
    }));
  };

  useEffect(() => {
    if (userDetails) {
      setSclassName(userDetails.sclassName || "");
      setStudentSchool(userDetails.school || "");
      setSubjectMarks(userDetails.examResult || "");
      setSubjectAttendance(userDetails.attendance || []);
    }
  }, [userDetails]);

  const overallAttendancePercentage =
    calculateOverallAttendancePercentage(subjectAttendance);
  const overallAbsentPercentage = 100 - overallAttendancePercentage;

  const chartData = [
    { name: "Present", value: overallAttendancePercentage },
    { name: "Absent", value: overallAbsentPercentage },
  ];

  return (
    <>
      {loading ? (
        <div className="flex items-center justify-center p-8">
          <div className="text-lg">Loading...</div>
        </div>
      ) : (
        <div className="p-6 space-y-6">
          <div className="space-y-2">
            <p className="text-base">
              <span className="font-semibold">Name:</span> {userDetails.name}
            </p>
            <p className="text-base">
              <span className="font-semibold">Id Number:</span> {userDetails.rollNum}
            </p>
            <p className="text-base">
              <span className="font-semibold">Class:</span> {sclassName.sclassName}
            </p>
            <p className="text-base">
              <span className="font-semibold">School:</span> {studentSchool.schoolName}
            </p>
          </div>

          <div className="space-y-4">
            <h3 className="text-xl font-bold">Attendance:</h3>
            {subjectAttendance &&
              Array.isArray(subjectAttendance) &&
              subjectAttendance.length > 0 && (
                <div className="space-y-6">
                  {Object.entries(
                    groupAttendanceBySubject(subjectAttendance),
                  ).map(
                    ([subName, { present, allData, subId, sessions }], index) => {
                      if (subName === teachSubject) {
                        const subjectAttendancePercentage =
                          calculateSubjectAttendancePercentage(present, sessions);

                        return (
                          <div key={index} className="space-y-2">
                            <Table>
                              <TableHeader>
                                <TableRow>
                                  <TableHead>Subject</TableHead>
                                  <TableHead>Present</TableHead>
                                  <TableHead>Total Sessions</TableHead>
                                  <TableHead>Attendance Percentage</TableHead>
                                  <TableHead className="text-center">Actions</TableHead>
                                </TableRow>
                              </TableHeader>

                              <TableBody>
                                <TableRow>
                                  <TableCell>{subName}</TableCell>
                                  <TableCell>{present}</TableCell>
                                  <TableCell>{sessions}</TableCell>
                                  <TableCell>{subjectAttendancePercentage}%</TableCell>
                                  <TableCell className="text-center">
                                    <Button
                                      variant="default"
                                      size="sm"
                                      onClick={() => handleOpen(subId)}
                                    >
                                      {openStates[subId] ? (
                                        <ChevronUp className="mr-2 h-4 w-4" />
                                      ) : (
                                        <ChevronDown className="mr-2 h-4 w-4" />
                                      )}
                                      Details
                                    </Button>
                                  </TableCell>
                                </TableRow>
                                <TableRow>
                                  <TableCell colSpan={6} className="p-0">
                                    <Collapsible open={openStates[subId]}>
                                      <CollapsibleContent>
                                        <div className="p-4">
                                          <h6 className="text-lg font-semibold mb-2">
                                            Attendance Details
                                          </h6>
                                          <Table>
                                            <TableHeader>
                                              <TableRow>
                                                <TableHead>Date</TableHead>
                                                <TableHead className="text-right">
                                                  Status
                                                </TableHead>
                                              </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                              {allData.map((data, index) => {
                                                const date = new Date(data.date);
                                                const dateString =
                                                  date.toString() !== "Invalid Date"
                                                    ? date
                                                        .toISOString()
                                                        .substring(0, 10)
                                                    : "Invalid Date";
                                                return (
                                                  <TableRow key={index}>
                                                    <TableCell>{dateString}</TableCell>
                                                    <TableCell className="text-right">
                                                      {data.status}
                                                    </TableCell>
                                                  </TableRow>
                                                );
                                              })}
                                            </TableBody>
                                          </Table>
                                        </div>
                                      </CollapsibleContent>
                                    </Collapsible>
                                  </TableCell>
                                </TableRow>
                              </TableBody>
                            </Table>
                          </div>
                        );
                      } else {
                        return null;
                      }
                    },
                  )}
                  <div className="text-base font-medium">
                    Overall Attendance Percentage:{" "}
                    {overallAttendancePercentage.toFixed(2)}%
                  </div>

                  <CustomPieChart data={chartData} />
                </div>
              )}
          </div>

          <Button
            variant="default"
            onClick={() =>
              navigate(
                `/Teacher/class/student/attendance/${studentID}/${teachSubjectID}`,
              )
            }
          >
            Add Attendance
          </Button>

          <div className="space-y-4">
            <h3 className="text-xl font-bold">Subject Marks:</h3>
            {subjectMarks &&
              Array.isArray(subjectMarks) &&
              subjectMarks.length > 0 && (
                <div className="space-y-4">
                  {subjectMarks.map((result, index) => {
                    if (result.subName.subName === teachSubject) {
                      return (
                        <Table key={index}>
                          <TableHeader>
                            <TableRow>
                              <TableHead>Subject</TableHead>
                              <TableHead>Marks</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            <TableRow>
                              <TableCell>{result.subName.subName}</TableCell>
                              <TableCell>{result.marksObtained}</TableCell>
                            </TableRow>
                          </TableBody>
                        </Table>
                      );
                    } else if (!result.subName || !result.marksObtained) {
                      return null;
                    }
                    return null;
                  })}
                </div>
              )}
          </div>

          <Button
            variant="default"
            className="bg-purple-600 hover:bg-purple-700"
            onClick={() =>
              navigate(
                `/Teacher/class/student/marks/${studentID}/${teachSubjectID}`,
              )
            }
          >
            Add Marks
          </Button>
        </div>
      )}
    </>
  );
};

export default TeacherViewStudent;
```

## Key Changes Made

1. **Imports**
   - Replaced `@mui/material` imports with Shadcn components
   - Changed `@mui/icons-material` to `lucide-react`
   - Added `cn` utility import

2. **Components**
   - `Box` → `div` with Tailwind classes
   - `Typography` → HTML tags (`h3`, `p`, `span`) with Tailwind
   - `Collapse` → `Collapsible` + `CollapsibleContent`
   - `Button` → Shadcn `Button` with variant props
   - `Table` components → Shadcn table components

3. **Icons**
   - `KeyboardArrowDown` → `ChevronDown`
   - `KeyboardArrowUp` → `ChevronUp`

4. **Styling**
   - `sx` prop → `className` with Tailwind
   - MUI spacing → Tailwind spacing classes
   - Removed `StyledTableCell` and `StyledTableRow` (use Tailwind instead)

5. **Props**
   - `variant="contained"` → `variant="default"`
   - `in={open}` → `open={open}`
   - Added Tailwind classes for spacing, colors, typography

## Before Installing, You Need

```bash
cd frontend1
npx shadcn-ui@latest add collapsible
```

## Testing

After conversion:
1. Start dev server: `npm run dev`
2. Navigate to teacher view student page
3. Test:
   - Student details display
   - Attendance table shows/hides
   - Marks table displays
   - Buttons work correctly
   - Responsive layout

## Notes

- The `PurpleButton` custom component was replaced with a regular Button with purple className
- `StyledTableCell` and `StyledTableRow` should be removed - use regular TableCell/TableRow with className
- Consider creating a custom styled TableCell component if consistent styling is needed
