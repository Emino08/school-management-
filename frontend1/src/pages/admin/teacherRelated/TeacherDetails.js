import React, { useEffect } from 'react';
import { getTeacherDetails } from '../../../redux/teacherRelated/teacherHandle';
import { useParams, useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { Button } from "@/components/ui/button";

const TeacherDetails = () => {
    const navigate = useNavigate();
    const params = useParams();
    const dispatch = useDispatch();
    const { loading, teacherDetails, error } = useSelector((state) => state.teacher);

    const teacherID = params.id;

    useEffect(() => {
        dispatch(getTeacherDetails(teacherID));
    }, [dispatch, teacherID]);

    if (error) {
        console.log(error);
    }

    const isSubjectNamePresent = teacherDetails?.teachSubject?.subName;

    const handleAddSubject = () => {
        navigate(`/Admin/teachers/choosesubject/${teacherDetails?.teachSclass?._id}/${teacherDetails?._id}`);
    };

    return (
        <>
            {loading ? (
                <div className="flex items-center justify-center h-screen">
                    <p className="text-lg">Loading...</p>
                </div>
            ) : (
                <div className="container mx-auto p-4">
                    <h4 className="text-2xl font-bold text-center mb-6">
                        Teacher Details
                    </h4>
                    <h6 className="text-lg mb-4">
                        Teacher Name: {teacherDetails?.name}
                    </h6>
                    <h6 className="text-lg mb-4">
                        Class Name: {teacherDetails?.teachSclass?.sclassName}
                    </h6>
                    {isSubjectNamePresent ? (
                        <>
                            <h6 className="text-lg mb-4">
                                Subject Name: {teacherDetails?.teachSubject?.subName}
                            </h6>
                            <h6 className="text-lg mb-4">
                                Subject Sessions: {teacherDetails?.teachSubject?.sessions}
                            </h6>
                        </>
                    ) : (
                        <Button variant="default" onClick={handleAddSubject}>
                            Add Subject
                        </Button>
                    )}
                </div>
            )}
        </>
    );
};

export default TeacherDetails;
