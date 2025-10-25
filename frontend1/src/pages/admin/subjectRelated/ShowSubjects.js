import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from "react-router-dom";
import { getSubjectList } from '../../../redux/sclassRelated/sclassHandle';
import { deleteUser } from '../../../redux/userRelated/userHandle';
import { Button } from "@/components/ui/button";
import { MdPostAdd, MdDelete } from "react-icons/md";
import TableTemplate from '../../../components/TableTemplate';
import SpeedDialTemplate from '../../../components/SpeedDialTemplate';

const ShowSubjects = () => {
    const navigate = useNavigate()
    const dispatch = useDispatch();
    const { subjectsList, loading, error, response } = useSelector((state) => state.sclass);
    const { currentUser } = useSelector(state => state.user)

    useEffect(() => {
        if (currentUser?._id) {
            dispatch(getSubjectList(currentUser._id, "AllSubjects"));
        }
    }, [currentUser?._id, dispatch]);

    if (error) {
        console.log(error);
    }
    const deleteHandler = (deleteID, address) => {
        if (currentUser?._id) {
            dispatch(deleteUser(deleteID, address))
                .then(() => {
                    dispatch(getSubjectList(currentUser._id, "AllSubjects"));
                })
        }
    }

    const subjectColumns = [
        { id: 'subName', label: 'Sub Name', minWidth: 170 },
        { id: 'sessions', label: 'Sessions', minWidth: 170 },
        { id: 'sclassName', label: 'Class', minWidth: 170 },
    ]

    const subjectRows = Array.isArray(subjectsList)
        ? subjectsList.map((subject) => {
            return {
                subName: subject.subName,
                sessions: subject.sessions,
                sclassName: subject.sclassName?.sclassName || 'N/A',
                sclassID: subject.sclassName?._id,
                id: subject._id,
            };
        })
        : [];

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
                    onClick={() => navigate(`/Admin/subjects/subject/${row.sclassID}/${row.id}`)}
                >
                    View
                </Button>
            </div>
        );
    };

    const actions = [
        {
            icon: <MdPostAdd className="w-5 h-5 text-blue-600" />, name: 'Add New Subject',
            action: () => navigate("/Admin/subjects/chooseclass")
        },
        {
            icon: <MdDelete className="w-5 h-5 text-red-600" />, name: 'Delete All Subjects',
            action: () => deleteHandler(currentUser._id, "Subjects")
        }
    ];

    return (
        <>
            {loading ?
                <div>Loading...</div>
                :
                <>
                    {response ?
                        <div className="flex justify-end mt-4">
                            <Button
                                variant="default"
                                className="bg-green-600 hover:bg-green-700"
                                onClick={() => navigate("/Admin/subjects/chooseclass")}
                            >
                                Add Subjects
                            </Button>
                        </div>
                        :
                        <div className="w-full overflow-hidden">
                            {Array.isArray(subjectsList) && subjectsList.length > 0 &&
                                <TableTemplate buttonHaver={SubjectsButtonHaver} columns={subjectColumns} rows={subjectRows} />
                            }
                            <SpeedDialTemplate actions={actions} />
                        </div>
                    }
                </>
            }
        </>
    );
};

export default ShowSubjects;
