import React, { useState } from 'react';
import axios from '@/redux/axiosConfig';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { MdLock, MdSchool, MdEmojiEvents, MdPerson } from 'react-icons/md';

const PublicResultChecker = () => {
    const [pin, setPin] = useState('');
    const [admissionNo, setAdmissionNo] = useState('');
    const [loading, setLoading] = useState(false);
    const [resultData, setResultData] = useState(null);

    const handleCheckResult = async (e) => {
        e.preventDefault();

        if (!pin.trim() || !admissionNo.trim()) {
            toast.error('Please enter both PIN and Admission Number');
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post(
                `${import.meta.env.VITE_API_BASE_URL}/results/check-with-pin`,
                {
                    pin: pin.trim(),
                    admission_no: admissionNo.trim()
                }
            );

            if (response.data.success) {
                setResultData(response.data);
                toast.success('Results loaded successfully!');
            } else {
                toast.error(response.data.message || 'Failed to load results');
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Invalid PIN or Admission Number');
            console.error('Error checking results:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleReset = () => {
        setPin('');
        setAdmissionNo('');
        setResultData(null);
    };

    const getGradeColor = (grade) => {
        const colors = {
            'A': 'bg-green-500',
            'B': 'bg-blue-500',
            'C': 'bg-yellow-500',
            'D': 'bg-orange-500',
            'F': 'bg-red-500'
        };
        return colors[grade] || 'bg-gray-500';
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4">
            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="text-center mb-8">
                    <div className="flex items-center justify-center mb-4">
                        <MdSchool className="text-6xl text-blue-600" />
                    </div>
                    <h1 className="text-4xl font-bold text-gray-800 mb-2">
                        SABITECK School Management System
                    </h1>
                    <p className="text-xl text-gray-600">Student Result Checker</p>
                </div>

                {!resultData ? (
                    /* PIN Entry Form */
                    <Card className="p-8 shadow-xl">
                        <div className="text-center mb-6">
                            <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                                <MdLock className="text-3xl text-blue-600" />
                            </div>
                            <h2 className="text-2xl font-bold text-gray-800 mb-2">Check Your Results</h2>
                            <p className="text-gray-600">Enter your PIN and Admission Number to view your results</p>
                        </div>

                        <form onSubmit={handleCheckResult} className="space-y-6">
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-2">
                                    Result PIN
                                </label>
                                <Input
                                    type="text"
                                    placeholder="Enter your 12-character PIN"
                                    value={pin}
                                    onChange={(e) => setPin(e.target.value.toUpperCase())}
                                    maxLength={12}
                                    className="text-lg tracking-wider"
                                    required
                                />
                                <p className="text-xs text-gray-500 mt-1">
                                    PIN should be provided by your school administration
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-2">
                                    Admission Number
                                </label>
                                <Input
                                    type="text"
                                    placeholder="Enter your admission number"
                                    value={admissionNo}
                                    onChange={(e) => setAdmissionNo(e.target.value)}
                                    className="text-lg"
                                    required
                                />
                            </div>

                            <Button
                                type="submit"
                                className="w-full py-6 text-lg"
                                disabled={loading}
                            >
                                {loading ? (
                                    <span className="flex items-center justify-center gap-2">
                                        <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                                        Checking...
                                    </span>
                                ) : (
                                    'View Results'
                                )}
                            </Button>
                        </form>

                        <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                            <h3 className="font-semibold text-sm text-gray-700 mb-2">Important Notes:</h3>
                            <ul className="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                <li>PINs are case-sensitive and must be entered exactly as provided</li>
                                <li>Each PIN has a limited number of uses</li>
                                <li>PINs expire after the validity period</li>
                                <li>Contact your school administration if you encounter any issues</li>
                            </ul>
                        </div>
                    </Card>
                ) : (
                    /* Results Display */
                    <div className="space-y-6">
                        {/* Student Info Card */}
                        <Card className="p-6 shadow-xl">
                            <div className="flex items-center justify-between mb-6">
                                <div className="flex items-center gap-4">
                                    <div className="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                                        <MdPerson className="text-3xl text-white" />
                                    </div>
                                    <div>
                                        <h2 className="text-2xl font-bold text-gray-800">
                                            {resultData.student_name}
                                        </h2>
                                        <p className="text-gray-600">
                                            ID Number: {resultData.roll_num}
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    variant="outline"
                                    onClick={handleReset}
                                    className="text-sm"
                                >
                                    Check Another Result
                                </Button>
                            </div>

                            <div className="border-t pt-4">
                                <h3 className="text-xl font-semibold text-gray-800 mb-2">
                                    {resultData.exam_name}
                                </h3>
                            </div>
                        </Card>

                        {/* Summary Cards */}
                        {resultData.summary && (
                            <div className="grid md:grid-cols-4 gap-4">
                                <Card className="p-6 bg-gradient-to-br from-blue-500 to-blue-600 text-white">
                                    <p className="text-sm opacity-90 mb-1">Total Marks</p>
                                    <p className="text-3xl font-bold">
                                        {resultData.summary.total_marks_obtained?.toFixed(2)}
                                    </p>
                                    <p className="text-sm opacity-75">
                                        out of {resultData.summary.total_possible_marks}
                                    </p>
                                </Card>

                                <Card className="p-6 bg-gradient-to-br from-green-500 to-green-600 text-white">
                                    <p className="text-sm opacity-90 mb-1">Percentage</p>
                                    <p className="text-3xl font-bold">
                                        {resultData.summary.percentage?.toFixed(2)}%
                                    </p>
                                    <p className="text-sm opacity-75">Overall</p>
                                </Card>

                                <Card className="p-6 bg-gradient-to-br from-purple-500 to-purple-600 text-white">
                                    <p className="text-sm opacity-90 mb-1">Grade</p>
                                    <p className="text-3xl font-bold">
                                        {resultData.summary.grade}
                                    </p>
                                    <p className="text-sm opacity-75">{resultData.summary.remarks}</p>
                                </Card>

                                <Card className="p-6 bg-gradient-to-br from-yellow-500 to-yellow-600 text-white">
                                    <div className="flex items-center gap-2 mb-1">
                                        <MdEmojiEvents className="text-xl" />
                                        <p className="text-sm opacity-90">Position</p>
                                    </div>
                                    <p className="text-3xl font-bold">
                                        {resultData.summary.position}
                                    </p>
                                    <p className="text-sm opacity-75">
                                        out of {resultData.summary.total_students} students
                                    </p>
                                </Card>
                            </div>
                        )}

                        {/* Subject-wise Results */}
                        <Card className="p-6 shadow-xl">
                            <h3 className="text-xl font-bold text-gray-800 mb-4">
                                Subject-wise Performance
                            </h3>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700">Subject</th>
                                            <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Test</th>
                                            <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Exam</th>
                                            <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Total</th>
                                            <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Grade</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {resultData.results && resultData.results.map((result, index) => (
                                            <tr key={index} className="hover:bg-gray-50">
                                                <td className="px-4 py-3">
                                                    <p className="font-medium text-gray-800">{result.subject_name}</p>
                                                    <p className="text-xs text-gray-500">{result.subject_code}</p>
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm text-gray-700">
                                                    {result.test_score?.toFixed(2) || '0.00'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm text-gray-700">
                                                    {result.exam_score?.toFixed(2) || '0.00'}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <span className="font-semibold text-gray-800">
                                                        {result.total_score?.toFixed(2) || '0.00'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <Badge className={`${getGradeColor(result.grade)} text-white`}>
                                                        {result.grade}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-600">
                                                    {result.remarks || '-'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Card>

                        {/* Print Button */}
                        <div className="flex justify-center gap-4">
                            <Button
                                onClick={() => window.print()}
                                className="px-8"
                            >
                                Print Results
                            </Button>
                            <Button
                                variant="outline"
                                onClick={handleReset}
                            >
                                Check Another Result
                            </Button>
                        </div>
                    </div>
                )}
            </div>

            {/* Footer */}
            <div className="text-center mt-12 text-gray-600 text-sm">
                <p>&copy; 2025 SABITECK School Management System. All rights reserved.</p>
            </div>
        </div>
    );
};

export default PublicResultChecker;
