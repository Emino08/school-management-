import React, { useEffect, useState } from 'react'
import { useDispatch, useSelector } from 'react-redux';
import { getSubjectList } from '../../redux/sclassRelated/sclassHandle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { getUserDetails } from '../../redux/userRelated/userHandle';
import CustomBarChart from '../../components/CustomBarChart'
import { MdInsertChart, MdTableChart } from "react-icons/md";

const StudentSubjects = () => {

    const dispatch = useDispatch();
    const { subjectsList, sclassDetails } = useSelector((state) => state.sclass);
    const { userDetails, currentUser, loading, response, error } = useSelector((state) => state.user);

    useEffect(() => {
        dispatch(getUserDetails(currentUser._id, "Student"));
    }, [dispatch, currentUser._id])

    if (response) { console.log(response) }
    else if (error) { console.log(error) }

    const [subjectMarks, setSubjectMarks] = useState([]);
    const [selectedSection, setSelectedSection] = useState('table');

    useEffect(() => {
        if (userDetails) {
            setSubjectMarks(userDetails.examResult || []);
        }
    }, [userDetails])

    useEffect(() => {
        if (subjectMarks.length === 0) {
            dispatch(getSubjectList(currentUser.sclassName._id, "ClassSubjects"));
        }
    }, [subjectMarks, dispatch, currentUser.sclassName._id]);

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
        return (
            <div className="container mx-auto p-4">
                <h4 className="text-2xl font-bold text-center mb-6">
                    Class Details
                </h4>
                <h5 className="text-xl font-semibold mb-4">
                    You are currently in Class {sclassDetails && sclassDetails.sclassName}
                </h5>
                <h6 className="text-lg mb-4">
                    And these are the subjects:
                </h6>
                {subjectsList &&
                    subjectsList.map((subject, index) => (
                        <div key={index} className="mb-2">
                            <p className="text-base">
                                {subject.subName} ({subject.subCode})
                            </p>
                        </div>
                    ))}
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
