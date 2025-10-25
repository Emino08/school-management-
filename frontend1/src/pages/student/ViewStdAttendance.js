import React, { useEffect, useState } from 'react'
import { MdKeyboardArrowDown, MdKeyboardArrowUp, MdInsertChart, MdTableChart } from "react-icons/md";
import { Button } from "@/components/ui/button";
import { Collapsible, CollapsibleContent } from "@/components/ui/collapsible";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useDispatch, useSelector } from 'react-redux';
import { getUserDetails } from '../../redux/userRelated/userHandle';
import { calculateOverallAttendancePercentage, calculateSubjectAttendancePercentage, groupAttendanceBySubject } from '../../components/attendanceCalculator';

import CustomBarChart from '../../components/CustomBarChart'

const ViewStdAttendance = () => {
    const dispatch = useDispatch();

    const [openStates, setOpenStates] = useState({});

    const handleOpen = (subId) => {
        setOpenStates((prevState) => ({
            ...prevState,
            [subId]: !prevState[subId],
        }));
    };

    const { userDetails, currentUser, loading, response, error } = useSelector((state) => state.user);

    useEffect(() => {
        dispatch(getUserDetails(currentUser._id, "Student"));
    }, [dispatch, currentUser._id]);

    if (response) { console.log(response) }
    else if (error) { console.log(error) }

    const [subjectAttendance, setSubjectAttendance] = useState([]);
    const [selectedSection, setSelectedSection] = useState('table');

    useEffect(() => {
        if (userDetails) {
            setSubjectAttendance(userDetails.attendance || []);
        }
    }, [userDetails])

    const attendanceBySubject = groupAttendanceBySubject(subjectAttendance)

    const overallAttendancePercentage = calculateOverallAttendancePercentage(subjectAttendance);

    const subjectData = Object.entries(attendanceBySubject).map(([subName, { subCode, present, sessions }]) => {
        const subjectAttendancePercentage = calculateSubjectAttendancePercentage(present, sessions);
        return {
            subject: subName,
            attendancePercentage: subjectAttendancePercentage,
            totalClasses: sessions,
            attendedClasses: present
        };
    });

    const renderTableSection = () => {
        return (
            <>
                <h4 className="text-2xl font-bold text-center mb-6">
                    Attendance
                </h4>
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
                    {Object.entries(attendanceBySubject).map(([subName, { present, allData, subId, sessions }], index) => {
                        const subjectAttendancePercentage = calculateSubjectAttendancePercentage(present, sessions);

                        return (
                            <TableBody key={index}>
                                <TableRow>
                                    <TableCell>{subName}</TableCell>
                                    <TableCell>{present}</TableCell>
                                    <TableCell>{sessions}</TableCell>
                                    <TableCell>{subjectAttendancePercentage}%</TableCell>
                                    <TableCell className="text-center">
                                        <Button
                                            variant="default"
                                            onClick={() => handleOpen(subId)}
                                            className="flex items-center gap-1"
                                        >
                                            {openStates[subId] ? <MdKeyboardArrowUp className="w-5 h-5" /> : <MdKeyboardArrowDown className="w-5 h-5" />}
                                            Details
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="p-0" colSpan={6}>
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
                                                                <TableHead className="text-right">Status</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {allData.map((data, index) => {
                                                                const date = new Date(data.date);
                                                                const dateString = date.toString() !== "Invalid Date" ? date.toISOString().substring(0, 10) : "Invalid Date";
                                                                return (
                                                                    <TableRow key={index}>
                                                                        <TableCell>
                                                                            {dateString}
                                                                        </TableCell>
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
                            </TableBody>
                        )
                    }
                    )}
                </Table>
                <div className="mt-4 text-lg font-semibold">
                    Overall Attendance Percentage: {overallAttendancePercentage.toFixed(2)}%
                </div>
            </>
        )
    }

    const renderChartSection = () => {
        return (
            <>
                <CustomBarChart chartData={subjectData} dataKey="attendancePercentage" />
            </>
        )
    };

    return (
        <>
            {loading
                ? (
                    <div className="flex items-center justify-center h-screen">
                        <p className="text-lg">Loading...</p>
                    </div>
                )
                :
                <div>
                    {subjectAttendance && Array.isArray(subjectAttendance) && subjectAttendance.length > 0 ?
                        <>
                            {selectedSection === 'table' && renderTableSection()}
                            {selectedSection === 'chart' && renderChartSection()}

                            <div className="fixed bottom-0 left-0 right-0 bg-background border-t shadow-lg z-10">
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
                        </>
                        :
                        <>
                            <h6 className="text-lg mb-4">
                                Currently You Have No Attendance Details
                            </h6>
                        </>
                    }
                </div>
            }
        </>
    )
}

export default ViewStdAttendance
