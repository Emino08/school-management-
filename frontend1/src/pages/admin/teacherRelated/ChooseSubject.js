import React, { useEffect, useState } from 'react'
import { useDispatch, useSelector } from 'react-redux';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useNavigate, useParams } from 'react-router-dom';
import { getTeacherFreeClassSubjects } from '../../../redux/sclassRelated/sclassHandle';
import { updateTeachSubject } from '../../../redux/teacherRelated/teacherHandle';

const ChooseSubject = ({ situation }) => {
    const params = useParams();
    const navigate = useNavigate()
    const dispatch = useDispatch();

    const [classID, setClassID] = useState("");
    const [teacherID, setTeacherID] = useState("");
    const [loader, setLoader] = useState(false)

    const { subjectsList, loading, error, response } = useSelector((state) => state.sclass);

    useEffect(() => {
        if (situation === "Norm") {
            setClassID(params.id);
            const classID = params.id
            dispatch(getTeacherFreeClassSubjects(classID));
        }
        else if (situation === "Teacher") {
            const { classID, teacherID } = params
            setClassID(classID);
            setTeacherID(teacherID);
            dispatch(getTeacherFreeClassSubjects(classID));
        }
    }, [situation]);

    if (loading) {
        return (
            <div className="flex items-center justify-center h-screen">
                <p className="text-lg">Loading...</p>
            </div>
        );
    } else if (response) {
        return (
            <div>
                <h1 className="text-2xl font-bold mb-4">Sorry all subjects have teachers assigned already</h1>
                <div className="flex justify-end mt-4">
                    <Button
                        variant="default"
                        className="bg-purple-600 hover:bg-purple-700"
                        onClick={() => navigate("/Admin/addsubject/" + classID)}
                    >
                        Add Subjects
                    </Button>
                </div>
            </div>
        );
    } else if (error) {
        console.log(error)
    }

    const updateSubjectHandler = (teacherId, teachSubject) => {
        setLoader(true)
        dispatch(updateTeachSubject(teacherId, teachSubject))
        navigate("/Admin/teachers")
    }

    return (
        <Card className="w-full">
            <CardContent>
                <h6 className="text-lg mb-4">
                    Choose a subject
                </h6>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead></TableHead>
                            <TableHead className="text-center">Subject Name</TableHead>
                            <TableHead className="text-center">Subject Code</TableHead>
                            <TableHead className="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {Array.isArray(subjectsList) && subjectsList.length > 0 && subjectsList.map((subject, index) => (
                            <TableRow key={subject._id}>
                                <TableCell>{index + 1}</TableCell>
                                <TableCell className="text-center">{subject.subName}</TableCell>
                                <TableCell className="text-center">{subject.subCode}</TableCell>
                                <TableCell className="text-center">
                                    {situation === "Norm" ?
                                        <Button
                                            variant="default"
                                            className="bg-green-600 hover:bg-green-700"
                                            onClick={() => navigate("/Admin/teachers/addteacher/" + subject._id)}
                                        >
                                            Choose
                                        </Button>
                                        :
                                        <Button
                                            variant="default"
                                            className="bg-green-600 hover:bg-green-700"
                                            disabled={loader}
                                            onClick={() => updateSubjectHandler(teacherID, subject._id)}
                                        >
                                            {loader ? (
                                                <div className="load"></div>
                                            ) : (
                                                'Choose Sub'
                                            )}
                                        </Button>}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
};

export default ChooseSubject;
