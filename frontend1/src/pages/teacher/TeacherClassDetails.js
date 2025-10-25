import { useEffect } from "react";
import * as React from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom'
import { getClassStudents } from "../../redux/sclassRelated/sclassHandle";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import TableTemplate from "../../components/TableTemplate";
import { MdKeyboardArrowDown } from "react-icons/md";

const TeacherClassDetails = () => {
    const navigate = useNavigate()
    const dispatch = useDispatch();
    const { sclassStudents, loading, error, getresponse } = useSelector((state) => state.sclass);

    const { currentUser } = useSelector((state) => state.user);
    const classID = currentUser.teachSclass?._id
    const subjectID = currentUser.teachSubject?._id

    useEffect(() => {
        dispatch(getClassStudents(classID));
    }, [dispatch, classID])

    if (error) {
        console.log(error)
    }

    const studentColumns = [
        { id: 'name', label: 'Name', minWidth: 170 },
        { id: 'rollNum', label: 'ID Number', minWidth: 100 },
    ]

    const studentRows = sclassStudents.map((student) => {
        return {
            name: student.name,
            rollNum: student.rollNum,
            id: student._id,
        };
    })

    const StudentsButtonHaver = ({ row }) => {
        const handleAttendance = () => {
            navigate(`/Teacher/class/student/attendance/${row.id}/${subjectID}`)
        }
        const handleMarks = () => {
            navigate(`/Teacher/class/student/marks/${row.id}/${subjectID}`)
        };

        return (
            <div className="flex gap-2">
                <Button
                    variant="default"
                    className="bg-blue-600 hover:bg-blue-700"
                    onClick={() => navigate("/Teacher/class/student/" + row.id)}
                >
                    View
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="default" className="bg-gray-800 hover:bg-gray-900">
                            Actions <MdKeyboardArrowDown className="w-4 h-4 ml-1" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={handleAttendance}>
                            Take Attendance
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={handleMarks}>
                            Provide Marks
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        );
    };

    return (
        <>
            {loading ? (
                <div className="flex items-center justify-center h-screen">
                    <p className="text-lg">Loading...</p>
                </div>
            ) : (
                <>
                    <h4 className="text-2xl font-bold text-center mb-6">
                        Class Details
                    </h4>
                    {getresponse ? (
                        <div className="flex justify-end mt-4">
                            <p className="text-base">No Students Found</p>
                        </div>
                    ) : (
                        <Card className="w-full">
                            <CardContent>
                                <h5 className="text-xl font-semibold mb-4">
                                    Students List:
                                </h5>
                                {Array.isArray(sclassStudents) && sclassStudents.length > 0 &&
                                    <TableTemplate buttonHaver={StudentsButtonHaver} columns={studentColumns} rows={studentRows} />
                                }
                            </CardContent>
                        </Card>
                    )}
                </>
            )}
        </>
    );
};

export default TeacherClassDetails;
