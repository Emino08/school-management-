import { useEffect, useState } from "react";
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom'
import { getClassDetails, getClassStudents, getSubjectList } from "../../../redux/sclassRelated/sclassHandle";
import { deleteUser } from '../../../redux/userRelated/userHandle';
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { resetSubjects } from "../../../redux/sclassRelated/sclassSlice";
import TableTemplate from "../../../components/TableTemplate";
import SpeedDialTemplate from "../../../components/SpeedDialTemplate";
import { MdDelete, MdPostAdd, MdPersonAddAlt1, MdPersonRemove } from "react-icons/md";
import SubjectModal from "../../../components/modals/SubjectModal";
import StudentModal from "../../../components/modals/StudentModal";
import TeacherModal from "../../../components/modals/TeacherModal";

const ClassDetails = () => {
    const params = useParams()
    const navigate = useNavigate()
    const dispatch = useDispatch();
    const { subjectsList, sclassStudents, sclassDetails, loading, error, response, getresponse } = useSelector((state) => state.sclass);

    const classID = params.id

    useEffect(() => {
        dispatch(getClassDetails(classID, "Sclass"));
        dispatch(getSubjectList(classID, "ClassSubjects"))
        dispatch(getClassStudents(classID));
    }, [dispatch, classID])

    if (error) {
        console.log(error)
    }

    const [value, setValue] = useState('details');

    const deleteHandler = (deleteID, address) => {
        dispatch(deleteUser(deleteID, address))
            .then(() => {
                dispatch(getClassStudents(classID));
                dispatch(resetSubjects())
                dispatch(getSubjectList(classID, "ClassSubjects"))
            })
    }

    const subjectColumns = [
        { id: 'name', label: 'Subject Name', minWidth: 170 },
        { id: 'code', label: 'Subject Code', minWidth: 100 },
    ]

    const subjectRows = subjectsList && subjectsList.length > 0 && subjectsList.map((subject) => {
        return {
            name: subject.subName,
            code: subject.subCode,
            id: subject._id,
        };
    })

    const SubjectsButtonHaver = ({ row }) => {
        return (
            <div className="flex items-center justify-center gap-2">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => deleteHandler(row.id, "Subject")}
                >
                    <MdDelete className="w-5 h-5 text-red-600" />
                </Button>
                <Button
                    variant="default"
                    onClick={() => {
                        navigate(`/Admin/class/subject/${classID}/${row.id}`)
                    }}
                >
                    View
                </Button>
            </div>
        );
    };

    const subjectActions = [
        {
            icon: <MdPostAdd className="w-5 h-5 text-blue-600" />, name: 'Add New Subject',
            action: () => {
                console.log('=== NAVIGATING TO ADD SUBJECT ===');
                console.log('classID:', classID);
                console.log('Path:', "/Admin/addsubject/" + classID);
                navigate("/Admin/addsubject/" + classID);
            }
        },
        {
            icon: <MdDelete className="w-5 h-5 text-red-600" />, name: 'Delete All Subjects',
            action: () => deleteHandler(classID, "SubjectsClass")
        }
    ];

    const ClassSubjectsSection = () => {
        return (
            <>
                {response ?
                    <div className="flex justify-end mt-4">
                        <Button
                            variant="default"
                            className="bg-green-600 hover:bg-green-700"
                            onClick={() => {
                                console.log('=== ADD SUBJECT BUTTON CLICKED (No subjects) ===');
                                console.log('classID:', classID);
                                navigate("/Admin/addsubject/" + classID);
                            }}
                        >
                            Add Subjects
                        </Button>
                    </div>
                    :
                    <>
                        <h5 className="text-xl font-semibold mb-4">
                            Subjects List:
                        </h5>

                        <TableTemplate buttonHaver={SubjectsButtonHaver} columns={subjectColumns} rows={subjectRows} />
                        <SpeedDialTemplate actions={subjectActions} />
                    </>
                }
            </>
        )
    }

    const studentColumns = [
        { id: 'name', label: 'Name', minWidth: 170 },
        { id: 'rollNum', label: 'ID Number', minWidth: 100 },
    ]

    const studentRows = Array.isArray(sclassStudents)
        ? sclassStudents.map((student) => {
            return {
                name: student.name,
                rollNum: student.rollNum,
                id: student._id,
            };
        })
        : [];

    const StudentsButtonHaver = ({ row }) => {
        return (
            <div className="flex items-center justify-center gap-2">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => deleteHandler(row.id, "Student")}
                >
                    <MdPersonRemove className="w-5 h-5 text-red-600" />
                </Button>
                <Button
                    variant="default"
                    onClick={() => navigate("/Admin/students/student/" + row.id)}
                >
                    View
                </Button>
                <Button
                    variant="default"
                    className="bg-purple-600 hover:bg-purple-700"
                    onClick={() =>
                        navigate("/Admin/students/student/attendance/" + row.id)
                    }
                >
                    Attendance
                </Button>
            </div>
        );
    };

    const studentActions = [
        {
            icon: <MdPersonAddAlt1 className="w-5 h-5 text-blue-600" />, name: 'Add New Student',
            action: () => navigate("/Admin/class/addstudents/" + classID)
        },
        {
            icon: <MdPersonRemove className="w-5 h-5 text-red-600" />, name: 'Delete All Students',
            action: () => deleteHandler(classID, "StudentsClass")
        },
    ];

    const ClassStudentsSection = () => {
        return (
            <>
                {getresponse ? (
                    <>
                        <div className="flex justify-end mt-4">
                            <Button
                                variant="default"
                                className="bg-green-600 hover:bg-green-700"
                                onClick={() => navigate("/Admin/class/addstudents/" + classID)}
                            >
                                Add Students
                            </Button>
                        </div>
                    </>
                ) : (
                    <>
                        <h5 className="text-xl font-semibold mb-4">
                            Students List:
                        </h5>

                        <TableTemplate buttonHaver={StudentsButtonHaver} columns={studentColumns} rows={studentRows} />
                        <SpeedDialTemplate actions={studentActions} />
                    </>
                )}
            </>
        )
    }

    const ClassTeachersSection = () => {
        return (
            <>
                Teachers
            </>
        )
    }

    const ClassDetailsSection = () => {
        const numberOfSubjects = Array.isArray(subjectsList) ? subjectsList.length : 0;
        const numberOfStudents = Array.isArray(sclassStudents) ? sclassStudents.length : 0;

        return (
            <div className="space-y-4">
                <h4 className="text-2xl font-bold text-center">
                    Class Details
                </h4>
                <h5 className="text-xl font-semibold">
                    Class Name: {sclassDetails && sclassDetails.sclassName}
                </h5>
                <h6 className="text-lg font-medium">
                    Grade Level: {sclassDetails && sclassDetails.grade_level ? sclassDetails.grade_level : 'N/A'}
                </h6>
                <h6 className="text-lg font-medium">
                    Number of Subjects: {numberOfSubjects}
                </h6>
                <h6 className="text-lg font-medium">
                    Number of Students: {numberOfStudents}
                </h6>
                <div className="flex gap-4">
                    {getresponse &&
                        <Button
                            variant="default"
                            className="bg-green-600 hover:bg-green-700"
                            onClick={() => navigate("/Admin/class/addstudents/" + classID)}
                        >
                            Add Students
                        </Button>
                    }
                    {response &&
                        <Button
                            variant="default"
                            className="bg-green-600 hover:bg-green-700"
                            onClick={() => {
                                console.log('=== ADD SUBJECT BUTTON CLICKED (Details section) ===');
                                console.log('classID:', classID);
                                navigate("/Admin/addsubject/" + classID);
                            }}
                        >
                            Add Subjects
                        </Button>
                    }
                </div>
            </div>
        );
    }

    return (
        <>
            {loading ? (
                <div>Loading...</div>
            ) : (
                <div className="w-full">
                    <Tabs value={value} onValueChange={setValue} className="w-full">
                        <div className="sticky top-0 bg-background z-10 border-b">
                            <TabsList className="w-full justify-start">
                                <TabsTrigger value="details">Details</TabsTrigger>
                                <TabsTrigger value="subjects">Subjects</TabsTrigger>
                                <TabsTrigger value="students">Students</TabsTrigger>
                                <TabsTrigger value="teachers">Teachers</TabsTrigger>
                            </TabsList>
                        </div>
                        <div className="container mx-auto mt-12 mb-16 px-4">
                            <TabsContent value="details">
                                <ClassDetailsSection />
                            </TabsContent>
                            <TabsContent value="subjects">
                                <ClassSubjectsSection />
                            </TabsContent>
                            <TabsContent value="students">
                                <ClassStudentsSection />
                            </TabsContent>
                            <TabsContent value="teachers">
                                <ClassTeachersSection />
                            </TabsContent>
                        </div>
                    </Tabs>
                </div>
            )}
        </>
    );
};

export default ClassDetails;
