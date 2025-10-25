import React, { useEffect, useState } from 'react'
import { getClassStudents, getSubjectDetails } from '../../../redux/sclassRelated/sclassHandle';
import { useNavigate, useParams } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "@/components/ui/button";
import TableTemplate from '../../../components/TableTemplate';
import { MdInsertChart, MdTableChart } from "react-icons/md";

const ViewSubject = () => {
  const navigate = useNavigate()
  const params = useParams()
  const dispatch = useDispatch();
  const { subloading, subjectDetails, sclassStudents, getresponse, error } = useSelector((state) => state.sclass);

  const { classID, subjectID } = params

  useEffect(() => {
    dispatch(getSubjectDetails(subjectID, "Subject"));
    dispatch(getClassStudents(classID));
  }, [dispatch, subjectID, classID]);

  if (error) {
    console.log(error)
  }

  const [value, setValue] = useState('details');

  const [selectedSection, setSelectedSection] = useState('attendance');

  const studentColumns = [
    { id: 'rollNum', label: 'ID Number', minWidth: 100 },
    { id: 'name', label: 'Name', minWidth: 170 },
  ]

  const studentRows = sclassStudents.map((student) => {
    return {
      rollNum: student.rollNum,
      name: student.name,
      id: student._id,
    };
  })

  const StudentsAttendanceButtonHaver = ({ row }) => {
    return (
      <div className="flex gap-2">
        <Button
          variant="default"
          className="bg-blue-600 hover:bg-blue-700"
          onClick={() => navigate("/Admin/students/student/" + row.id)}
        >
          View
        </Button>
        <Button
          variant="default"
          className="bg-purple-600 hover:bg-purple-700"
          onClick={() =>
            navigate(`/Admin/subject/student/attendance/${row.id}/${subjectID}`)
          }
        >
          Take Attendance
        </Button>
      </div>
    );
  };

  const StudentsMarksButtonHaver = ({ row }) => {
    return (
      <div className="flex gap-2">
        <Button
          variant="default"
          className="bg-blue-600 hover:bg-blue-700"
          onClick={() => navigate("/Admin/students/student/" + row.id)}
        >
          View
        </Button>
        <Button
          variant="default"
          className="bg-purple-600 hover:bg-purple-700"
          onClick={() => navigate(`/Admin/subject/student/marks/${row.id}/${subjectID}`)}
        >
          Provide Marks
        </Button>
      </div>
    );
  };

  const SubjectStudentsSection = () => {
    return (
      <>
        {getresponse ? (
          <div className="flex justify-end mt-4">
            <Button
              variant="default"
              className="bg-green-600 hover:bg-green-700"
              onClick={() => navigate("/Admin/class/addstudents/" + classID)}
            >
              Add Students
            </Button>
          </div>
        ) : (
          <>
            <h5 className="text-xl font-semibold mb-4">
              Students List:
            </h5>

            {selectedSection === 'attendance' &&
              <TableTemplate buttonHaver={StudentsAttendanceButtonHaver} columns={studentColumns} rows={studentRows} />
            }
            {selectedSection === 'marks' &&
              <TableTemplate buttonHaver={StudentsMarksButtonHaver} columns={studentColumns} rows={studentRows} />
            }

            <div className="fixed bottom-0 left-0 right-0 bg-background border-t shadow-lg z-10">
              <div className="flex items-center justify-center gap-4 p-4">
                <Button
                  variant={selectedSection === 'attendance' ? "default" : "outline"}
                  onClick={() => setSelectedSection('attendance')}
                  className="flex items-center gap-2"
                >
                  <MdTableChart className="w-5 h-5" />
                  Attendance
                </Button>
                <Button
                  variant={selectedSection === 'marks' ? "default" : "outline"}
                  onClick={() => setSelectedSection('marks')}
                  className="flex items-center gap-2"
                >
                  <MdInsertChart className="w-5 h-5" />
                  Marks
                </Button>
              </div>
            </div>
          </>
        )}
      </>
    )
  }

  const SubjectDetailsSection = () => {
    const numberOfStudents = sclassStudents.length;

    return (
      <div className="space-y-4">
        <h4 className="text-2xl font-bold text-center mb-6">
          Subject Details
        </h4>
        <h6 className="text-lg">
          Subject Name : {subjectDetails && subjectDetails.subName}
        </h6>
        <h6 className="text-lg">
          Subject Code : {subjectDetails && subjectDetails.subCode}
        </h6>
        <h6 className="text-lg">
          Subject Sessions : {subjectDetails && subjectDetails.sessions}
        </h6>
        <h6 className="text-lg">
          Number of Students: {numberOfStudents}
        </h6>
        <h6 className="text-lg">
          Class Name : {subjectDetails && subjectDetails.sclassName && subjectDetails.sclassName.sclassName}
        </h6>
        {subjectDetails && subjectDetails.teacher ? (
          <h6 className="text-lg">
            Teacher Name : {subjectDetails.teacher.name}
          </h6>
        ) : (
          <Button
            variant="default"
            className="bg-green-600 hover:bg-green-700"
            onClick={() => navigate("/Admin/teachers/addteacher/" + subjectDetails._id)}
          >
            Add Subject Teacher
          </Button>
        )}
      </div>
    );
  }

  return (
    <>
      {subloading ? (
        <div className="flex items-center justify-center h-screen">
          <p className="text-lg">Loading...</p>
        </div>
      ) : (
        <div className="w-full">
          <Tabs value={value} onValueChange={setValue}>
            <div className="sticky top-0 bg-background z-10 border-b">
              <TabsList className="w-full justify-start">
                <TabsTrigger value="details">Details</TabsTrigger>
                <TabsTrigger value="students">Students</TabsTrigger>
              </TabsList>
            </div>
            <div className="container mx-auto mt-12 mb-16 p-4">
              <TabsContent value="details">
                <SubjectDetailsSection />
              </TabsContent>
              <TabsContent value="students">
                <SubjectStudentsSection />
              </TabsContent>
            </div>
          </Tabs>
        </div>
      )}
    </>
  )
}

export default ViewSubject
