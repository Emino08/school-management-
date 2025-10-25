import React, { useEffect, useState } from 'react'
import { Card, CardContent } from '@/components/ui/card';
import { useDispatch, useSelector } from 'react-redux';
import { calculateOverallAttendancePercentage } from '../../components/attendanceCalculator';
import CustomPieChart from '../../components/CustomPieChart';
import { getUserDetails } from '../../redux/userRelated/userHandle';
import SeeNotice from '../../components/SeeNotice';
import CountUp from 'react-countup';
import Subject from "../../assets/subjects.svg";
import Assignment from "../../assets/assignment.svg";
import { getSubjectList } from '../../redux/sclassRelated/sclassHandle';

const StudentHomePage = () => {
    const dispatch = useDispatch();

    const { userDetails, currentUser, loading, response } = useSelector((state) => state.user);
    const { subjectsList } = useSelector((state) => state.sclass);

    const [subjectAttendance, setSubjectAttendance] = useState([]);

    const classID = currentUser.sclassName._id

    useEffect(() => {
        dispatch(getUserDetails(currentUser._id, "Student"));
        dispatch(getSubjectList(classID, "ClassSubjects"));
    }, [dispatch, currentUser._id, classID]);

    const numberOfSubjects = subjectsList && subjectsList.length;

    useEffect(() => {
        if (userDetails) {
            setSubjectAttendance(userDetails.attendance || []);
        }
    }, [userDetails])

    const overallAttendancePercentage = calculateOverallAttendancePercentage(subjectAttendance);
    const overallAbsentPercentage = 100 - overallAttendancePercentage;

    const chartData = [
        { name: 'Present', value: overallAttendancePercentage },
        { name: 'Absent', value: overallAbsentPercentage }
    ];

    return (
        <div className="container max-w-7xl mx-auto mt-8 mb-8 px-4">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card className="flex flex-col items-center justify-between h-[200px] p-4 text-center">
                    <img src={Subject} alt="Subjects" className="w-16 h-16 object-contain" />
                    <p className="text-xl font-medium">Total Subjects</p>
                    <span className="text-2xl md:text-3xl text-green-600 font-bold">
                      <CountUp
                        end={Number(numberOfSubjects ?? 0)}
                        duration={1.2}
                        separator="," preserveValue redraw
                      >
                        {({ countUpRef }) => (<span ref={countUpRef} />)}
                      </CountUp>
                    </span>
                </Card>

                <Card className="flex flex-col items-center justify-between h-[200px] p-4 text-center">
                    <img src={Assignment} alt="Assignments" className="w-16 h-16 object-contain" />
                    <p className="text-xl font-medium">Total Assignments</p>
                    <span className="text-2xl md:text-3xl text-green-600 font-bold">
                      <CountUp end={15} duration={1.2} separator="," preserveValue redraw>
                        {({ countUpRef }) => (<span ref={countUpRef} />)}
                      </CountUp>
                    </span>
                </Card>

                <Card className="flex flex-col items-center justify-center h-[200px] p-4 text-center">
                    {response ? (
                        <h6 className="text-lg font-semibold">No Attendance Found</h6>
                    ) : (
                        <>
                            {loading ? (
                                <h6 className="text-lg font-semibold">Loading...</h6>
                            ) : (
                                <>
                                    {subjectAttendance && Array.isArray(subjectAttendance) && subjectAttendance.length > 0 ? (
                                        <CustomPieChart data={chartData} />
                                    ) : (
                                        <h6 className="text-lg font-semibold">No Attendance Found</h6>
                                    )}
                                </>
                            )}
                        </>
                    )}
                </Card>

                <div className="col-span-1 md:col-span-2 lg:col-span-4">
                    <Card>
                        <CardContent className="p-4 flex flex-col">
                            <SeeNotice />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    )
}

export default StudentHomePage
