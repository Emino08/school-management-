import React, { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { MdRotateRight, MdAdd, MdRemove } from "react-icons/md";
import { useNavigate, useParams } from 'react-router-dom';
import { useSelector } from 'react-redux';
import { toast } from 'sonner';
import axios from '../../../redux/axiosConfig';

const SubjectForm = () => {
    const [subjects, setSubjects] = useState([{ subName: "", subCode: "", sessions: "" }]);

    const navigate = useNavigate()
    const params = useParams()

    const { currentUser } = useSelector(state => state.user);

    const classID = params.id  // This is actually the class ID from the route
    const adminID = currentUser?._id || currentUser?.id
    const address = "Subject"

    const [loader, setLoader] = useState(false)
    
    console.log('SubjectForm - Class ID:', classID);
    console.log('SubjectForm - Admin ID:', adminID);

    const handleSubjectNameChange = (index) => (event) => {
        const newSubjects = [...subjects];
        newSubjects[index].subName = event.target.value;
        setSubjects(newSubjects);
    };

    const handleSubjectCodeChange = (index) => (event) => {
        const newSubjects = [...subjects];
        newSubjects[index].subCode = event.target.value;
        setSubjects(newSubjects);
    };

    const handleSessionsChange = (index) => (event) => {
        const newSubjects = [...subjects];
        newSubjects[index].sessions = event.target.value || 0;
        setSubjects(newSubjects);
    };

    const handleAddSubject = () => {
        setSubjects([...subjects, { subName: "", subCode: "", sessions: "" }]);
    };

    const handleRemoveSubject = (index) => () => {
        const newSubjects = [...subjects];
        newSubjects.splice(index, 1);
        setSubjects(newSubjects);
    };

    const submitHandler = async (event) => {
        event.preventDefault();
        
        // Validate that all subjects have required fields
        const invalidSubjects = subjects.filter(s => !s.subName || !s.subCode || !s.sessions);
        if (invalidSubjects.length > 0) {
            toast.error('Please fill all fields for all subjects');
            return;
        }
        
        setLoader(true);
        
        try {
            console.log('Submitting subjects:', subjects);
            console.log('Class ID:', classID);
            console.log('Admin ID:', adminID);
            
            // Send each subject separately to the backend
            const promises = subjects.map(subject => {
                const payload = {
                    subject_name: subject.subName,
                    subject_code: subject.subCode,
                    sessions: parseInt(subject.sessions) || 0,
                    class_id: parseInt(classID),
                    admin_id: parseInt(adminID)
                };
                console.log('Sending payload:', payload);
                return axios.post(
                    `${import.meta.env.VITE_API_BASE_URL}/subjects`,
                    payload
                );
            });
            
            const results = await Promise.all(promises);
            console.log('All subjects created:', results);
            
            const allSuccess = results.every(r => r.data.success);
            
            if (allSuccess) {
                toast.success('All subjects created successfully!');
                navigate("/Admin/subjects");
            } else {
                toast.error('Some subjects failed to create');
            }
        } catch (error) {
            console.error('Error creating subjects:', error);
            toast.error(error.response?.data?.message || 'Failed to create subjects');
        } finally {
            setLoader(false);
        }
    };

    return (
        <div className="flex flex-1 items-center justify-center p-6">
            <Card className="w-full max-w-4xl">
                <CardHeader>
                    <CardTitle className="text-2xl">Add Subjects</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submitHandler} className="space-y-6">
                        {subjects.map((subject, index) => (
                            <div key={index} className="space-y-4 p-4 border rounded-lg">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor={`subName-${index}`}>Subject Name</Label>
                                        <Input
                                            id={`subName-${index}`}
                                            placeholder="Enter subject name"
                                            value={subject.subName}
                                            onChange={handleSubjectNameChange(index)}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor={`subCode-${index}`}>Subject Code</Label>
                                        <Input
                                            id={`subCode-${index}`}
                                            placeholder="Enter subject code"
                                            value={subject.subCode}
                                            onChange={handleSubjectCodeChange(index)}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor={`sessions-${index}`}>Sessions</Label>
                                        <Input
                                            id={`sessions-${index}`}
                                            type="number"
                                            min="0"
                                            placeholder="Enter number of sessions"
                                            value={subject.sessions}
                                            onChange={handleSessionsChange(index)}
                                            required
                                        />
                                    </div>
                                </div>
                                <div className="flex justify-end">
                                    {index === 0 ? (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={handleAddSubject}
                                        >
                                            <MdAdd className="w-5 h-5 mr-2" />
                                            Add Subject
                                        </Button>
                                    ) : (
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            onClick={handleRemoveSubject(index)}
                                        >
                                            <MdRemove className="w-5 h-5 mr-2" />
                                            Remove
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ))}
                        <div className="flex justify-end">
                            <Button type="submit" disabled={loader}>
                                {loader ? (
                                    <MdRotateRight className="w-5 h-5 animate-spin" />
                                ) : (
                                    'Save Subjects'
                                )}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

export default SubjectForm
