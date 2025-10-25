import React, { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent } from "@/components/ui/card";
import { useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { addStuff } from '../../../redux/userRelated/userHandle';
import { underControl } from '../../../redux/userRelated/userSlice';
import { toast } from 'sonner';
import Classroom from "../../../assets/classroom.png";
import { MdRotateRight } from "react-icons/md";

const AddClass = () => {
    const [sclassName, setSclassName] = useState("");
    const [gradeLevel, setGradeLevel] = useState("");

    const dispatch = useDispatch()
    const navigate = useNavigate()

    const userState = useSelector(state => state.user);
    const { status, currentUser, response, error, tempDetails } = userState;

    const adminID = currentUser?._id
    const address = "Sclass"

    const [loader, setLoader] = useState(false)

    const fields = {
        sclassName,
        grade_level: parseInt(gradeLevel) || 0,
        adminID,
    };

    const submitHandler = (event) => {
        event.preventDefault()
        setLoader(true)
        dispatch(addStuff(fields, address))
    };

    useEffect(() => {
        if (status === 'added' && tempDetails) {
            toast.success('Class created successfully!', {
                description: `Class ${sclassName} has been added.`,
            });
            navigate("/Admin/classes/class/" + tempDetails._id)
            dispatch(underControl())
            setLoader(false)
        }
        else if (status === 'failed') {
            toast.error('Failed to create class', {
                description: response || 'An error occurred while creating the class.',
            });
            setLoader(false)
        }
        else if (status === 'error') {
            toast.error('Network Error', {
                description: 'Unable to connect to the server. Please check your connection.',
            });
            setLoader(false)
        }
    }, [status, navigate, error, response, dispatch, tempDetails, sclassName]);

    return (
        <>
            <div className="flex flex-1 items-center justify-center">
                <Card className="w-full max-w-[550px] mt-4">
                    <CardContent className="p-12">
                        <div className="flex items-center justify-center mb-6">
                            <img
                                src={Classroom}
                                alt="classroom"
                                className="w-4/5 object-contain"
                            />
                        </div>
                        <form onSubmit={submitHandler} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="classname">Class Name</Label>
                                <Input
                                    id="classname"
                                    type="text"
                                    placeholder="Enter class name (e.g., Grade 10A)..."
                                    value={sclassName}
                                    onChange={(event) => {
                                        setSclassName(event.target.value);
                                    }}
                                    required
                                    className="w-full"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="gradelevel">Grade Level</Label>
                                <Input
                                    id="gradelevel"
                                    type="number"
                                    min="1"
                                    max="12"
                                    placeholder="Enter grade level (1-12)..."
                                    value={gradeLevel}
                                    onChange={(event) => {
                                        setGradeLevel(event.target.value);
                                    }}
                                    required
                                    className="w-full"
                                />
                                <p className="text-sm text-gray-500">
                                    This will be used for promoting students to the next level
                                </p>
                            </div>
                            <Button
                                className="w-full"
                                size="lg"
                                type="submit"
                                disabled={loader}
                            >
                                {loader ? (
                                    <MdRotateRight className="w-5 h-5 animate-spin" />
                                ) : (
                                    "Create Class"
                                )}
                            </Button>
                            <Button
                                variant="outline"
                                className="w-full"
                                type="button"
                                onClick={() => navigate(-1)}
                            >
                                Go Back
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    )
}

export default AddClass
