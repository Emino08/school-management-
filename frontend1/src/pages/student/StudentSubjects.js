import React, { useEffect, useState } from 'react'
import { useDispatch, useSelector } from 'react-redux';
import { getSubjectList } from '../../redux/sclassRelated/sclassHandle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { getUserDetails } from '../../redux/userRelated/userHandle';
import CustomBarChart from '../../components/CustomBarChart'
import { MdInsertChart, MdTableChart, MdSchool, MdBook } from "react-icons/md";

const StudentSubjects = () => {

    const dispatch = useDispatch();
    const { subjectsList } = useSelector((state) => state.sclass);
    const { userDetails, currentUser, loading, response, error } = useSelector((state) => state.user);

    useEffect(() => {
        if (currentUser?.id || currentUser?._id) {
            const studentId = currentUser.id || currentUser._id;
            dispatch(getUserDetails(studentId, "Student"));
        }
    }, [dispatch, currentUser?.id, currentUser?._id])

    if (response) { console.log(response) }
    else if (error) { console.log(error) }

    const [subjectMarks, setSubjectMarks] = useState([]);
    const [selectedSection, setSelectedSection] = useState('table');

    useEffect(() => {
        if (userDetails) {
            setSubjectMarks(userDetails.examResult || []);
        }
    }, [userDetails])

    // Fetch subjects and class details when class ID is available
    useEffect(() => {
        const classId = currentUser?.sclassName?._id || currentUser?.class_id || userDetails?.class_id;
        if (classId) {
            dispatch(getSubjectList(classId, "ClassSubjects"));
        }
    }, [dispatch, currentUser?.sclassName?._id, currentUser?.class_id, userDetails?.class_id]);

    const renderTableSection = () => {
        return (
            <>
                <h4 className="text-2xl font-bold text-center mb-6">
                    Subject Marks
                </h4>
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
            </>
        );
    };

    const renderChartSection = () => {
        return <CustomBarChart chartData={subjectMarks} dataKey="marksObtained" />;
    };

    const renderClassDetailsSection = () => {
        const className = userDetails?.sclassName?.sclassName || userDetails?.class_name ||
                         currentUser?.sclassName?.sclassName || currentUser?.class_name ||
                         'Not Assigned';
        const gradeLevel = userDetails?.sclassName?.grade_level || currentUser?.sclassName?.grade_level;
        const section = userDetails?.sclassName?.section || currentUser?.sclassName?.section;

        return (
            <div className="container mx-auto p-4 max-w-4xl">
                {/* Class Information Card */}
                <Card className="mb-6">
                    <CardHeader className="bg-purple-50">
                        <CardTitle className="flex items-center gap-2 text-purple-700">
                            <MdSchool className="w-6 h-6" />
                            Class Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="space-y-2">
                            <div className="flex items-center gap-2">
                                <span className="font-semibold text-gray-700">Class:</span>
                                <span className="text-lg text-purple-600 font-medium">{className}</span>
                            </div>
                            {gradeLevel && (
                                <div className="flex items-center gap-2">
                                    <span className="font-semibold text-gray-700">Grade Level:</span>
                                    <span className="text-gray-600">{gradeLevel}</span>
                                </div>
                            )}
                            {section && (
                                <div className="flex items-center gap-2">
                                    <span className="font-semibold text-gray-700">Section:</span>
                                    <span className="text-gray-600">{section}</span>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Subjects Card */}
                <Card>
                    <CardHeader className="bg-blue-50">
                        <CardTitle className="flex items-center gap-2 text-blue-700">
                            <MdBook className="w-6 h-6" />
                            Your Subjects
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        {subjectsList && Array.isArray(subjectsList) && subjectsList.length > 0 ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {subjectsList.map((subject, index) => (
                                    <Card key={index} className="border border-blue-200 hover:shadow-md transition-shadow">
                                        <CardContent className="p-4">
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <h3 className="font-semibold text-lg text-gray-800">
                                                        {subject.subject_name || subject.subName}
                                                    </h3>
                                                    <p className="text-sm text-gray-500 mt-1">
                                                        Code: {subject.subject_code || subject.subCode}
                                                    </p>
                                                    {subject.sessions && (
                                                        <p className="text-xs text-gray-400 mt-1">
                                                            {subject.sessions} sessions per week
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="bg-blue-100 rounded-full p-2">
                                                    <MdBook className="w-5 h-5 text-blue-600" />
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <MdBook className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                                <p className="text-gray-500">No subjects assigned yet</p>
                                <p className="text-sm text-gray-400 mt-2">
                                    Please contact your administrator to assign subjects to your class
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
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
                <div>
                    {subjectMarks && Array.isArray(subjectMarks) && subjectMarks.length > 0
                        ?
                        (<>
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
                        </>)
                        :
                        (<>
                            {renderClassDetailsSection()}
                        </>)
                    }
                </div>
            )}
        </>
    );
};

export default StudentSubjects;
