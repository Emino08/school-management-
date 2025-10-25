import React, { useEffect, useState } from 'react'
import { useDispatch, useSelector } from 'react-redux';
import { useParams } from 'react-router-dom';
import { getUserDetails } from '../../../redux/userRelated/userHandle';
import { getSubjectList } from '../../../redux/sclassRelated/sclassHandle';
import { updateStudentFields } from '../../../redux/studentRelated/studentHandle';
import { MdRotateRight } from "react-icons/md";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { toast } from 'sonner';

const StudentAttendance = ({ situation }) => {
    const dispatch = useDispatch();
    const { currentUser, userDetails, loading } = useSelector((state) => state.user);
    const { subjectsList } = useSelector((state) => state.sclass);
    const { response, error, statestatus } = useSelector((state) => state.student);
    const params = useParams()

    const [studentID, setStudentID] = useState("");
    const [subjectName, setSubjectName] = useState("");
    const [chosenSubName, setChosenSubName] = useState("");
    const [status, setStatus] = useState('');
    const [date, setDate] = useState('');

    const [loader, setLoader] = useState(false)

    useEffect(() => {
        if (situation === "Student") {
            setStudentID(params.id);
            const stdID = params.id
            dispatch(getUserDetails(stdID, "Student"));
        }
        else if (situation === "Subject") {
            const { studentID, subjectID } = params
            setStudentID(studentID);
            dispatch(getUserDetails(studentID, "Student"));
            setChosenSubName(subjectID);
        }
    }, [situation]);

    useEffect(() => {
        if (userDetails && userDetails.sclassName && situation === "Student") {
            dispatch(getSubjectList(userDetails.sclassName._id, "ClassSubjects"));
        }
    }, [dispatch, userDetails]);

    const changeHandler = (value) => {
        const selectedSubject = subjectsList.find(
            (subject) => subject.subName === value
        );
        setSubjectName(selectedSubject.subName);
        setChosenSubName(selectedSubject._id);
    }

    const fields = { subName: chosenSubName, status, date }

    const submitHandler = (event) => {
        event.preventDefault()
        setLoader(true)
        dispatch(updateStudentFields(studentID, fields, "StudentAttendance"))
    }

    useEffect(() => {
        if (response) {
            setLoader(false)
            toast.error('Error', { description: response || 'An error occurred.' })
        }
        else if (error) {
            setLoader(false)
            setMessage("error")
        }
        else if (statestatus === "added") {
            setLoader(false)
            setMessage("Done Successfully")
        }
    }, [response, statestatus, error])

    return (
        <>
            {loading
                ?
                <div className="flex items-center justify-center h-screen">
                    <p className="text-lg">Loading...</p>
                </div>
                :
                <>
                    <div className="flex flex-1 items-center justify-center">
                        <div className="w-full max-w-[550px] px-3 py-24">
                            <Card>
                                <CardHeader className="space-y-2">
                                    <CardTitle className="text-2xl">
                                        Student Name: {userDetails.name}
                                    </CardTitle>
                                    {currentUser.teachSubject &&
                                        <CardTitle className="text-2xl">
                                            Subject Name: {currentUser.teachSubject?.subName}
                                        </CardTitle>
                                    }
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={submitHandler} className="space-y-6">
                                        {situation === "Student" &&
                                            <div className="space-y-2">
                                                <Label>Select Subject</Label>
                                                <Select value={subjectName} onValueChange={changeHandler} required>
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Choose an option" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {subjectsList ?
                                                            subjectsList.map((subject, index) => (
                                                                <SelectItem key={index} value={subject.subName}>
                                                                    {subject.subName}
                                                                </SelectItem>
                                                            ))
                                                            :
                                                            <SelectItem value="Select Subject">
                                                                Add Subjects For Attendance
                                                            </SelectItem>
                                                        }
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        }
                                        <div className="space-y-2">
                                            <Label>Attendance Status</Label>
                                            <Select value={status} onValueChange={setStatus} required>
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Choose an option" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Present">Present</SelectItem>
                                                    <SelectItem value="Absent">Absent</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="date">Select Date</Label>
                                            <Input
                                                id="date"
                                                type="date"
                                                value={date}
                                                onChange={(event) => setDate(event.target.value)}
                                                required
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={loader}
                                            className="w-full bg-purple-600 hover:bg-purple-700"
                                        >
                                            {loader ? <MdRotateRight className="w-6 h-6 animate-spin" /> : "Submit"}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </>
            }
        </>
    )
}

export default StudentAttendance
