import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import axios from '../../../redux/axiosConfig';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { toast } from 'sonner';
import { MdVpnKey, MdPeople, MdPerson, MdContentCopy, MdRefresh, MdGroups, MdDownload } from 'react-icons/md';

const ManageResultPins = () => {
    const { currentUser } = useSelector(state => state.user);
    const [pins, setPins] = useState([]);
    const [students, setStudents] = useState([]);
    const [classes, setClasses] = useState([]);
    const [exams, setExams] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showBulkDialog, setShowBulkDialog] = useState(false);
    const [showSingleDialog, setShowSingleDialog] = useState(false);
    const [showAllStudentsDialog, setShowAllStudentsDialog] = useState(false);

    // Form states
    const [bulkForm, setBulkForm] = useState({
        class_id: '',
        exam_id: '',
        academic_year_id: '',
        valid_days: '30',
        max_checks: '5'
    });

    const [allStudentsForm, setAllStudentsForm] = useState({
        valid_days: '30',
        max_checks: '5'
    });

    const [singleForm, setSingleForm] = useState({
        student_id: '',
        exam_id: '',
        academic_year_id: '',
        valid_days: '30'
    });

    useEffect(() => {
        fetchPins();
        fetchClasses();
        fetchExams();
        fetchStudents();
    }, []);

    const fetchPins = async () => {
        try {
            const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/results/pins`);
            if (response.data.success) {
                setPins(response.data.pins || []);
            }
        } catch (error) {
            console.error('Error fetching PINs:', error);
        }
    };

    const fetchClasses = async () => {
        try {
            const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/classes`);
            if (response.data.success) {
                setClasses(response.data.classes || []);
            }
        } catch (error) {
            console.error('Error fetching classes:', error);
        }
    };

    const fetchExams = async () => {
        try {
            const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/exams`);
            if (response.data.success) {
                setExams(response.data.exams || []);
            }
        } catch (error) {
            console.error('Error fetching exams:', error);
        }
    };

    const fetchStudents = async () => {
        try {
            const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/students`);
            if (response.data.success) {
                setStudents(response.data.students || []);
            }
        } catch (error) {
            console.error('Error fetching students:', error);
        }
    };

    const handleBulkGenerate = async () => {
        if (!bulkForm.class_id || !bulkForm.exam_id || !bulkForm.academic_year_id) {
            toast.error('Please fill all required fields');
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post(
                `${import.meta.env.VITE_API_BASE_URL}/results/bulk-generate-pins`,
                bulkForm
            );

            if (response.data.success) {
                toast.success(`Generated ${response.data.total} PINs successfully!`);
                setShowBulkDialog(false);
                setBulkForm({ class_id: '', exam_id: '', academic_year_id: '', valid_days: '30', max_checks: '5' });
                fetchPins();
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Failed to generate PINs');
        } finally {
            setLoading(false);
        }
    };

    const handleAllStudentsGenerate = async () => {
        setLoading(true);
        try {
            const response = await axios.post(
                `${import.meta.env.VITE_API_BASE_URL}/results/bulk-generate-pins-all`,
                allStudentsForm
            );

            if (response.data.success) {
                toast.success(`Generated ${response.data.total} PINs for all students successfully!`);
                setShowAllStudentsDialog(false);
                setAllStudentsForm({ valid_days: '30', max_checks: '5' });
                fetchPins();
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Failed to generate PINs');
        } finally {
            setLoading(false);
        }
    };

    const handleSingleGenerate = async () => {
        if (!singleForm.student_id || !singleForm.exam_id || !singleForm.academic_year_id) {
            toast.error('Please fill all required fields');
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post(
                `${import.meta.env.VITE_API_BASE_URL}/results/generate-pin`,
                singleForm
            );

            if (response.data.success) {
                toast.success('PIN generated successfully!');
                setShowSingleDialog(false);
                setSingleForm({ student_id: '', exam_id: '', academic_year_id: '', valid_days: '30' });
                fetchPins();
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Failed to generate PIN');
        } finally {
            setLoading(false);
        }
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        toast.success('PIN copied to clipboard!');
    };

    const getPinStatus = (pin) => {
        const now = new Date();
        const validUntil = new Date(pin.valid_until);
        const isExpired = now > validUntil;
        const isExhausted = pin.usage_count >= pin.usage_limit;

        if (!pin.is_active || isExpired || isExhausted) {
            return { label: 'Inactive', color: 'bg-red-500' };
        }
        return { label: 'Active', color: 'bg-green-500' };
    };

    const handleExportCSV = async (classId = null) => {
        try {
            let url = `${import.meta.env.VITE_API_BASE_URL}/results/pins/export-csv`;
            if (classId) {
                url += `?class_id=${classId}`;
            }

            const response = await axios.get(url, {
                responseType: 'blob'
            });

            // Create a download link
            const blob = new Blob([response.data], { type: 'text/csv' });
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `result_pins_${classId ? 'class_' + classId : 'all'}_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(downloadUrl);

            toast.success('PINs exported successfully!');
        } catch (error) {
            toast.error(error.response?.data?.message || 'Failed to export PINs');
        }
    };

    return (
        <div className="p-6 max-w-7xl mx-auto">
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                    <MdVpnKey className="text-blue-600" />
                    Result PIN Management
                </h1>
                <p className="text-gray-600">Generate and manage result access PINs</p>
            </div>

            {/* Action Buttons */}
            <div className="grid md:grid-cols-3 gap-4 mb-6">
                <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer" onClick={() => setShowAllStudentsDialog(true)}>
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <MdGroups className="text-2xl text-purple-600" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-lg text-gray-800">Bulk Generate - All Students</h3>
                            <p className="text-sm text-gray-600">Generate PINs for all students</p>
                        </div>
                    </div>
                </Card>

                <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer" onClick={() => setShowBulkDialog(true)}>
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <MdPeople className="text-2xl text-blue-600" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-lg text-gray-800">Bulk Generate by Class</h3>
                            <p className="text-sm text-gray-600">Generate PINs for a specific class</p>
                        </div>
                    </div>
                </Card>

                <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer" onClick={() => setShowSingleDialog(true)}>
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <MdPerson className="text-2xl text-green-600" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-lg text-gray-800">Generate for Student</h3>
                            <p className="text-sm text-gray-600">Generate PIN for a specific student</p>
                        </div>
                    </div>
                </Card>
            </div>

            {/* Generated PINs List */}
            <Card className="p-6">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-xl font-bold text-gray-800">Generated PINs ({pins.length})</h2>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" onClick={() => handleExportCSV()}>
                            <MdDownload className="mr-2" />
                            Export All
                        </Button>
                        <Button variant="outline" size="sm" onClick={fetchPins}>
                            <MdRefresh className="mr-2" />
                            Refresh
                        </Button>
                    </div>
                </div>

                {pins.length === 0 ? (
                    <div className="text-center py-8 text-gray-500">
                        <MdVpnKey className="text-6xl mx-auto mb-4 opacity-30" />
                        <p>No PINs generated yet</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700">PIN Code</th>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700">Student</th>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700">Exam</th>
                                    <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Usage</th>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700">Valid Until</th>
                                    <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                                    <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {pins.map((pin, index) => {
                                    const status = getPinStatus(pin);
                                    return (
                                        <tr key={index} className="hover:bg-gray-50">
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <code className="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                                        {pin.pin_code}
                                                    </code>
                                                    <button
                                                        onClick={() => copyToClipboard(pin.pin_code)}
                                                        className="text-blue-600 hover:text-blue-800"
                                                    >
                                                        <MdContentCopy />
                                                    </button>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>
                                                    <p className="font-medium text-gray-800">{pin.student_name}</p>
                                                    <p className="text-xs text-gray-500">
                                                        {pin.admission_no} | Roll: {pin.roll_num}
                                                    </p>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">{pin.exam_name || 'N/A'}</td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-700">
                                                {pin.usage_count}/{pin.usage_limit}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                {new Date(pin.valid_until).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Badge className={`${status.color} text-white`}>
                                                    {status.label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => copyToClipboard(
                                                        `PIN: ${pin.pin_code}\nAdmission No: ${pin.admission_no}\nStudent: ${pin.student_name}\nExam: ${pin.exam_name}`
                                                    )}
                                                >
                                                    Copy Details
                                                </Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>

            {/* Bulk Generate Dialog */}
            <AlertDialog open={showBulkDialog} onOpenChange={setShowBulkDialog}>
                <AlertDialogContent className="max-w-md">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Bulk Generate PINs for Class</AlertDialogTitle>
                        <AlertDialogDescription>
                            Generate result access PINs for all students in a class
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div className="space-y-4 py-4">
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Class</label>
                            <select
                                value={bulkForm.class_id}
                                onChange={(e) => setBulkForm({ ...bulkForm, class_id: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                            >
                                <option value="">Select Class</option>
                                {classes.map((cls) => (
                                    <option key={cls.id} value={cls.id}>
                                        {cls.class_name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Exam</label>
                            <select
                                value={bulkForm.exam_id}
                                onChange={(e) => setBulkForm({ ...bulkForm, exam_id: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                            >
                                <option value="">Select Exam</option>
                                {exams.map((exam) => (
                                    <option key={exam.id} value={exam.id}>
                                        {exam.exam_name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Academic Year ID</label>
                            <Input
                                type="number"
                                value={bulkForm.academic_year_id}
                                onChange={(e) => setBulkForm({ ...bulkForm, academic_year_id: e.target.value })}
                                placeholder="Enter academic year ID"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Max Checks per PIN</label>
                            <Input
                                type="number"
                                value={bulkForm.max_checks}
                                onChange={(e) => setBulkForm({ ...bulkForm, max_checks: e.target.value })}
                                min="1"
                                max="20"
                            />
                            <p className="text-xs text-gray-500 mt-1">Number of times each PIN can be used</p>
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Valid Days</label>
                            <Input
                                type="number"
                                value={bulkForm.valid_days}
                                onChange={(e) => setBulkForm({ ...bulkForm, valid_days: e.target.value })}
                                min="1"
                                max="365"
                            />
                            <p className="text-xs text-gray-500 mt-1">Number of days the PINs will remain valid</p>
                        </div>
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleBulkGenerate} disabled={loading}>
                            {loading ? 'Generating...' : 'Generate PINs'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Single Generate Dialog */}
            <AlertDialog open={showSingleDialog} onOpenChange={setShowSingleDialog}>
                <AlertDialogContent className="max-w-md">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Generate PIN for Student</AlertDialogTitle>
                        <AlertDialogDescription>
                            Generate a result access PIN for a specific student
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div className="space-y-4 py-4">
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Student</label>
                            <select
                                value={singleForm.student_id}
                                onChange={(e) => setSingleForm({ ...singleForm, student_id: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                            >
                                <option value="">Select Student</option>
                                {students.map((student) => (
                                    <option key={student.id} value={student.id}>
                                        {student.name} - {student.admission_no}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Exam</label>
                            <select
                                value={singleForm.exam_id}
                                onChange={(e) => setSingleForm({ ...singleForm, exam_id: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                            >
                                <option value="">Select Exam</option>
                                {exams.map((exam) => (
                                    <option key={exam.id} value={exam.id}>
                                        {exam.exam_name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Academic Year ID</label>
                            <Input
                                type="number"
                                value={singleForm.academic_year_id}
                                onChange={(e) => setSingleForm({ ...singleForm, academic_year_id: e.target.value })}
                                placeholder="Enter academic year ID"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Valid Days</label>
                            <Input
                                type="number"
                                value={singleForm.valid_days}
                                onChange={(e) => setSingleForm({ ...singleForm, valid_days: e.target.value })}
                                min="1"
                                max="365"
                            />
                            <p className="text-xs text-gray-500 mt-1">Number of days the PIN will remain valid</p>
                        </div>
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleSingleGenerate} disabled={loading}>
                            {loading ? 'Generating...' : 'Generate PIN'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* All Students Generate Dialog */}
            <AlertDialog open={showAllStudentsDialog} onOpenChange={setShowAllStudentsDialog}>
                <AlertDialogContent className="max-w-md">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Generate PINs for All Students</AlertDialogTitle>
                        <AlertDialogDescription>
                            Generate result access PINs for all students in the school
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="bg-amber-50 border border-amber-200 rounded-md p-4">
                            <p className="text-sm text-amber-800">
                                ⚠️ This will generate PINs for all students in the school. Make sure you want to proceed.
                            </p>
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Max Checks per PIN</label>
                            <Input
                                type="number"
                                value={allStudentsForm.max_checks}
                                onChange={(e) => setAllStudentsForm({ ...allStudentsForm, max_checks: e.target.value })}
                                min="1"
                                max="20"
                            />
                            <p className="text-xs text-gray-500 mt-1">Number of times each PIN can be used</p>
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Valid Days</label>
                            <Input
                                type="number"
                                value={allStudentsForm.valid_days}
                                onChange={(e) => setAllStudentsForm({ ...allStudentsForm, valid_days: e.target.value })}
                                min="1"
                                max="365"
                            />
                            <p className="text-xs text-gray-500 mt-1">Number of days the PINs will remain valid</p>
                        </div>
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleAllStudentsGenerate} disabled={loading}>
                            {loading ? 'Generating...' : 'Generate All PINs'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
};

export default ManageResultPins;
