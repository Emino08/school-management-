import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { deleteUser, getUserDetails, updateUser } from '../../../redux/userRelated/userHandle';
import { useNavigate, useParams } from 'react-router-dom'
import { getSubjectList } from '../../../redux/sclassRelated/sclassHandle';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Collapsible, CollapsibleContent } from "@/components/ui/collapsible";
import { Card, CardContent } from "@/components/ui/card";
import { MdKeyboardArrowUp, MdKeyboardArrowDown, MdDelete, MdInsertChart, MdTableChart } from 'react-icons/md';
import { removeStuff, updateStudentFields } from '../../../redux/studentRelated/studentHandle';
import { calculateOverallAttendancePercentage, calculateSubjectAttendancePercentage, groupAttendanceBySubject } from '../../../components/attendanceCalculator';
import CustomBarChart from '../../../components/CustomBarChart'
import CustomPieChart from '../../../components/CustomPieChart'

const ViewStudent = () => {
    const [showTab, setShowTab] = useState(false);

    const navigate = useNavigate()
    const params = useParams()
    const dispatch = useDispatch()
    const { userDetails, response, loading, error } = useSelector((state) => state.user);

    const studentID = params.id
    const address = "Student"

    useEffect(() => {
        dispatch(getUserDetails(studentID, address));
    }, [dispatch, studentID])

    useEffect(() => {
        if (userDetails && userDetails.sclassName && userDetails.sclassName._id !== undefined) {
            dispatch(getSubjectList(userDetails.sclassName._id, "ClassSubjects"));
        }
    }, [dispatch, userDetails]);

    if (response) { console.log(response) }
    else if (error) { console.log(error) }

    const [name, setName] = useState('');
    const [rollNum, setRollNum] = useState('');
    const [password, setPassword] = useState('');
    const [sclassName, setSclassName] = useState('');
    const [studentSchool, setStudentSchool] = useState('');
    const [subjectMarks, setSubjectMarks] = useState('');
    const [subjectAttendance, setSubjectAttendance] = useState([]);

    const [openStates, setOpenStates] = useState({});

    const handleOpen = (subId) => {
        setOpenStates((prevState) => ({
            ...prevState,
            [subId]: !prevState[subId],
        }));
    };

    const [value, setValue] = useState('details');

    const [selectedSection, setSelectedSection] = useState('table');

    const fields = password === ""
        ? { name, rollNum }
        : { name, rollNum, password }

    useEffect(() => {
        if (userDetails) {
            setName(userDetails.name || '');
            setRollNum(userDetails.rollNum || userDetails.id_number || '');
            setSclassName(userDetails.sclassName || '');
            setStudentSchool(userDetails.school || '');
            setSubjectMarks(userDetails.examResult || '');
            setSubjectAttendance(userDetails.attendance || []);
        }
    }, [userDetails]);

    const submitHandler = (event) => {
        event.preventDefault()
        dispatch(updateUser(fields, studentID, address))
            .then(() => {
                dispatch(getUserDetails(studentID, address));
            })
            .catch((error) => {
                console.error(error)
            })
    }

    const deleteHandler = () => {
        dispatch(deleteUser(studentID, address))
            .then(() => {
                navigate(-1)
            })
    }

    const removeHandler = (id, deladdress) => {
        dispatch(removeStuff(id, deladdress))
            .then(() => {
                dispatch(getUserDetails(studentID, address));
            })
    }

    const removeSubAttendance = (subId) => {
        dispatch(updateStudentFields(studentID, { subId }, "RemoveStudentSubAtten"))
            .then(() => {
                dispatch(getUserDetails(studentID, address));
            })
    }

    const overallAttendancePercentage = calculateOverallAttendancePercentage(subjectAttendance);
    const overallAbsentPercentage = 100 - overallAttendancePercentage;

    const chartData = [
        { name: 'Present', value: overallAttendancePercentage },
        { name: 'Absent', value: overallAbsentPercentage }
    ];

    const subjectData = Object.entries(groupAttendanceBySubject(subjectAttendance)).map(([subName, { subCode, present, sessions }]) => {
        const subjectAttendancePercentage = calculateSubjectAttendancePercentage(present, sessions);
        return {
            subject: subName,
            attendancePercentage: subjectAttendancePercentage,
            totalClasses: sessions,
            attendedClasses: present
        };
    });

    const StudentAttendanceSection = () => {
        const renderTableSection = () => {
            return (
                <div className="space-y-4">
                    <h3 className="text-xl font-bold">Attendance:</h3>
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
                            {Object.entries(groupAttendanceBySubject(subjectAttendance)).map(([subName, { present, allData, subId, sessions }], index) => {
                                const subjectAttendancePercentage = calculateSubjectAttendancePercentage(present, sessions);
                                return (
                                    <React.Fragment key={index}>
                                        <TableRow>
                                            <TableCell>{subName}</TableCell>
                                            <TableCell>{present}</TableCell>
                                            <TableCell>{sessions}</TableCell>
                                            <TableCell>{subjectAttendancePercentage}%</TableCell>
                                            <TableCell className="text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button
                                                        variant="default"
                                                        size="sm"
                                                        onClick={() => handleOpen(subId)}
                                                    >
                                                        {openStates[subId] ? <MdKeyboardArrowUp className="w-4 h-4 mr-1" /> : <MdKeyboardArrowDown className="w-4 h-4 mr-1" />}
                                                        Details
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => removeSubAttendance(subId)}
                                                    >
                                                        <MdDelete className="w-5 h-5 text-red-600" />
                                                    </Button>
                                                    <Button
                                                        variant="default"
                                                        size="sm"
                                                        className="bg-purple-600 hover:bg-purple-700"
                                                        onClick={() => navigate(`/Admin/subject/student/attendance/${studentID}/${subId}`)}
                                                    >
                                                        Change
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableCell colSpan={5} className="p-0">
                                                <Collapsible open={openStates[subId]}>
                                                    <CollapsibleContent>
                                                        <div className="p-4">
                                                            <h6 className="text-lg font-semibold mb-2">Attendance Details</h6>
                                                            <Table>
                                                                <TableHeader>
                                                                    <TableRow>
                                                                        <TableHead>Date</TableHead>
                                                                        <TableHead className="text-right">Status</TableHead>
                                                                    </TableRow>
                                                                </TableHeader>
                                                                <TableBody>
                                                                    {allData.map((data, index) => {
                                                                        const date = new Date(data.date);
                                                                        const dateString = date.toString() !== "Invalid Date" ? date.toISOString().substring(0, 10) : "Invalid Date";
                                                                        return (
                                                                            <TableRow key={index}>
                                                                                <TableCell>{dateString}</TableCell>
                                                                                <TableCell className="text-right">{data.status}</TableCell>
                                                                            </TableRow>
                                                                        )
                                                                    })}
                                                                </TableBody>
                                                            </Table>
                                                        </div>
                                                    </CollapsibleContent>
                                                </Collapsible>
                                            </TableCell>
                                        </TableRow>
                                    </React.Fragment>
                                )
                            })}
                        </TableBody>
                    </Table>
                    <div className="text-base font-medium">
                        Overall Attendance Percentage: {overallAttendancePercentage.toFixed(2)}%
                    </div>
                    <div className="flex gap-2">
                        <Button variant="destructive" onClick={() => removeHandler(studentID, "RemoveStudentAtten")}>
                            <MdDelete className="w-4 h-4 mr-2" />
                            Delete All
                        </Button>
                        <Button variant="default" className="bg-green-700 hover:bg-green-800" onClick={() => navigate("/Admin/students/student/attendance/" + studentID)}>
                            Add Attendance
                        </Button>
                    </div>
                </div>
            )
        }
        const renderChartSection = () => {
            return (
                <div>
                    <CustomBarChart chartData={subjectData} dataKey="attendancePercentage" />
                </div>
            )
        }
        return (
            <div>
                {subjectAttendance && Array.isArray(subjectAttendance) && subjectAttendance.length > 0
                    ?
                    <div className="space-y-4">
                        {selectedSection === 'table' && renderTableSection()}
                        {selectedSection === 'chart' && renderChartSection()}

                        <div className="fixed bottom-0 left-0 right-0 bg-background border-t shadow-lg">
                            <div className="flex items-center justify-center gap-4 p-4">
                                <Button
                                    variant={selectedSection === 'table' ? "default" : "outline"}
                                    onClick={() => setSelectedSection('table')}
                                    className="flex items-center gap-2"
                                >
                                    <MdTableChart className="w-5 h-5" />
                                    Table
                                </Button>
                                <Button
                                    variant={selectedSection === 'chart' ? "default" : "outline"}
                                    onClick={() => setSelectedSection('chart')}
                                    className="flex items-center gap-2"
                                >
                                    <MdInsertChart className="w-5 h-5" />
                                    Chart
                                </Button>
                            </div>
                        </div>
                    </div>
                    :
                    <Button variant="default" className="bg-green-700 hover:bg-green-800" onClick={() => navigate("/Admin/students/student/attendance/" + studentID)}>
                        Add Attendance
                    </Button>
                }
            </div>
        )
    }

    const StudentMarksSection = () => {
        const renderTableSection = () => {
            return (
                <div className="space-y-4">
                    <h3 className="text-xl font-bold">Subject Marks:</h3>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Subject</TableHead>
                                <TableHead>Marks</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {subjectMarks.map((result, index) => {
                                if (!result.subName || !result.marksObtained) {
                                    return null;
                                }
                                return (
                                    <TableRow key={index}>
                                        <TableCell>{result.subName.subName}</TableCell>
                                        <TableCell>{result.marksObtained}</TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                    <Button variant="default" className="bg-green-700 hover:bg-green-800" onClick={() => navigate("/Admin/students/student/marks/" + studentID)}>
                        Add Marks
                    </Button>
                </div>
            )
        }
        const renderChartSection = () => {
            return (
                <div>
                    <CustomBarChart chartData={subjectMarks} dataKey="marksObtained" />
                </div>
            )
        }
        return (
            <div>
                {subjectMarks && Array.isArray(subjectMarks) && subjectMarks.length > 0
                    ?
                    <div className="space-y-4">
                        {selectedSection === 'table' && renderTableSection()}
                        {selectedSection === 'chart' && renderChartSection()}

                        <div className="fixed bottom-0 left-0 right-0 bg-background border-t shadow-lg">
                            <div className="flex items-center justify-center gap-4 p-4">
                                <Button
                                    variant={selectedSection === 'table' ? "default" : "outline"}
                                    onClick={() => setSelectedSection('table')}
                                    className="flex items-center gap-2"
                                >
                                    <MdTableChart className="w-5 h-5" />
                                    Table
                                </Button>
                                <Button
                                    variant={selectedSection === 'chart' ? "default" : "outline"}
                                    onClick={() => setSelectedSection('chart')}
                                    className="flex items-center gap-2"
                                >
                                    <MdInsertChart className="w-5 h-5" />
                                    Chart
                                </Button>
                            </div>
                        </div>
                    </div>
                    :
                    <Button variant="default" className="bg-green-700 hover:bg-green-800" onClick={() => navigate("/Admin/students/student/marks/" + studentID)}>
                        Add Marks
                    </Button>
                }
            </div>
        )
    }

    const StudentDetailsSection = () => {
        return (
            <div className="space-y-4">
                <div className="space-y-2">
                    <p className="text-base"><span className="font-semibold">Name:</span> {userDetails.name}</p>
                    <p className="text-base"><span className="font-semibold">ID Number:</span> {userDetails.rollNum || userDetails.id_number}</p>
                    <p className="text-base"><span className="font-semibold">Class:</span> {sclassName.sclassName}</p>
                    <p className="text-base"><span className="font-semibold">School:</span> {studentSchool.schoolName}</p>
                </div>
                {
                    subjectAttendance && Array.isArray(subjectAttendance) && subjectAttendance.length > 0 && (
                        <CustomPieChart data={chartData} />
                    )
                }
                <div className="flex gap-2">
                    <Button variant="destructive" onClick={deleteHandler}>
                        Delete Student
                    </Button>
                    <Button variant="default" onClick={() => { setShowTab(!showTab) }}>
                        {showTab ? <MdKeyboardArrowUp className="w-4 h-4 mr-2" /> : <MdKeyboardArrowDown className="w-4 h-4 mr-2" />}
                        Edit Student
                    </Button>
                </div>
                <Collapsible open={showTab}>
                    <CollapsibleContent>
                        <Card>
                            <CardContent className="pt-6">
                                <form onSubmit={submitHandler} className="space-y-4">
                                    <h3 className="text-xl font-bold">Edit Details</h3>
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            placeholder="Enter user's name..."
                                            value={name}
                                            onChange={(event) => setName(event.target.value)}
                                            autoComplete="name"
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="rollNum">ID Number</Label>
                                        <Input
                                            id="rollNum"
                                            type="number"
                                            placeholder="Enter user's ID Number..."
                                            value={rollNum}
                                            onChange={(event) => setRollNum(event.target.value)}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="password">Password</Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            placeholder="Enter user's password..."
                                            value={password}
                                            onChange={(event) => setPassword(event.target.value)}
                                            autoComplete="new-password"
                                        />
                                    </div>
                                    <Button type="submit">Update</Button>
                                </form>
                            </CardContent>
                        </Card>
                    </CollapsibleContent>
                </Collapsible>
            </div>
        )
    }

    return (
        <>
            {loading
                ?
                <div>Loading...</div>
                :
                <div className="w-full">
                    <Tabs value={value} onValueChange={setValue} className="w-full">
                        <div className="sticky top-0 bg-background z-10 border-b">
                            <TabsList className="w-full justify-start">
                                <TabsTrigger value="details">Details</TabsTrigger>
                                <TabsTrigger value="attendance">Attendance</TabsTrigger>
                                <TabsTrigger value="marks">Marks</TabsTrigger>
                            </TabsList>
                        </div>
                        <div className="container mx-auto mt-12 mb-24 px-4">
                            <TabsContent value="details">
                                <StudentDetailsSection />
                            </TabsContent>
                            <TabsContent value="attendance">
                                <StudentAttendanceSection />
                            </TabsContent>
                            <TabsContent value="marks">
                                <StudentMarksSection />
                            </TabsContent>
                        </div>
                    </Tabs>
                </div>
            }
        </>
    )
}

export default ViewStudent
