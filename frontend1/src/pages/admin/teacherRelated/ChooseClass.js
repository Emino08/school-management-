import React, { useEffect } from 'react'
import { useDispatch, useSelector } from 'react-redux';
import { Button } from "@/components/ui/button";
import { getAllSclasses } from '../../../redux/sclassRelated/sclassHandle';
import { useNavigate } from 'react-router-dom';
import TableTemplate from '../../../components/TableTemplate';

const ChooseClass = ({ situation }) => {
    const navigate = useNavigate()
    const dispatch = useDispatch();

    const { sclassesList, loading, error, getresponse } = useSelector((state) => state.sclass);
    const { currentUser } = useSelector(state => state.user)

    useEffect(() => {
        if (currentUser?._id) {
            dispatch(getAllSclasses(currentUser._id, "Sclass"));
        }
    }, [currentUser?._id, dispatch]);

    if (error) {
        console.log(error)
    }

    const navigateHandler = (classID) => {
        if (situation === "Teacher") {
            navigate("/Admin/teachers/choosesubject/" + classID)
        }
        else if (situation === "Subject") {
            navigate("/Admin/addsubject/" + classID)
        }
    }

    const sclassColumns = [
        { id: 'name', label: 'Class Name', minWidth: 170 },
    ]

    const sclassRows = sclassesList && sclassesList.length > 0 && sclassesList.map((sclass) => {
        return {
            name: sclass.sclassName,
            id: sclass._id,
        };
    })

    const SclassButtonHaver = ({ row }) => {
        return (
            <Button
                variant="default"
                className="bg-purple-600 hover:bg-purple-700"
                onClick={() => navigateHandler(row.id)}
            >
                Choose
            </Button>
        );
    };

    return (
        <>
            {loading ?
                <div className="flex items-center justify-center h-screen">
                    <p className="text-lg">Loading...</p>
                </div>
                :
                <>
                    {getresponse ?
                        <div className="flex justify-end mt-4">
                            <Button variant="default" onClick={() => navigate("/Admin/addclass")}>
                                Add Class
                            </Button>
                        </div>
                        :
                        <>
                            <h6 className="text-lg mb-4">
                                Choose a class
                            </h6>
                            {Array.isArray(sclassesList) && sclassesList.length > 0 &&
                                <TableTemplate buttonHaver={SclassButtonHaver} columns={sclassColumns} rows={sclassRows} />
                            }
                        </>}
                </>
            }
        </>
    )
}

export default ChooseClass
