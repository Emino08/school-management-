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
import { MdKeyboardArrowDown, MdKeyboardArrowUp } from "react-icons/md";
import {
  calculateOverallAttendancePercentage,
  calculateSubjectAttendancePercentage,
  groupAttendanceBySubject,
} from "../../components/attendanceCalculator";
import CustomPieChart from "../../components/CustomPieChart";

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
                                        <MdKeyboardArrowUp className="w-5 h-5 mr-2" />
                                      ) : (
                                        <MdKeyboardArrowDown className="w-5 h-5 mr-2" />
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
