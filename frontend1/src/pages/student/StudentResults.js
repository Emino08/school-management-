import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import axios from '../../redux/axiosConfig';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { MdSchool, MdAssessment, MdEmojiEvents } from 'react-icons/md';

const StudentResults = () => {
    const { currentUser } = useSelector(state => state.user);
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedExam, setSelectedExam] = useState(null);
    const [examResults, setExamResults] = useState(null);

    useEffect(() => {
        fetchStudentResults();
    }, []);

    const fetchStudentResults = async () => {
        if (!currentUser?._id) {
            setLoading(false);
            return;
        }

        try {
            setLoading(true);
            const response = await axios.get(
                `${import.meta.env.VITE_API_BASE_URL}/results/student/${currentUser._id}`
            );

            if (response.data.success) {
                setResults(response.data.results || []);
            }
        } catch (error) {
            toast.error('Failed to load results');
            console.error('Error fetching results:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchExamResults = async (examId) => {
        if (!currentUser?._id) {
            return;
        }

        try {
            const response = await axios.get(
                `${import.meta.env.VITE_API_BASE_URL}/results/student/${currentUser._id}/exam/${examId}`
            );

            if (response.data.success) {
                setExamResults(response.data);
                setSelectedExam(examId);
            } else {
                toast.error(response.data.message || 'Results not available yet');
            }
        } catch (error) {
            const msg = error.response?.data?.message || 'Results not yet published';
            toast.error(msg);
            setExamResults(null);
            setSelectedExam(null);
            console.error('Error fetching exam results:', error);
        }
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

    const groupResultsByExam = () => {
        const grouped = {};
        results.forEach(result => {
            if (!grouped[result.exam_id]) {
                grouped[result.exam_id] = {
                    exam_id: result.exam_id,
                    exam_name: result.exam_name,
                    exam_type: result.exam_type,
                    exam_date: result.exam_date,
                    results: []
                };
            }
            grouped[result.exam_id].results.push(result);
        });
        return Object.values(grouped);
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">Loading your results...</p>
                </div>
            </div>
        );
    }

    const groupedResults = groupResultsByExam();

    return (
        <div className="p-6 max-w-7xl mx-auto">
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                    <MdSchool className="text-blue-600" />
                    My Academic Results
                </h1>
                <p className="text-gray-600">View your exam results and performance</p>
            </div>

            {groupedResults.length === 0 ? (
                <Card className="p-8 text-center">
                    <MdAssessment className="text-6xl text-gray-400 mx-auto mb-4" />
                    <h3 className="text-xl font-semibold text-gray-700 mb-2">No Results Available</h3>
                    <p className="text-gray-500">Your exam results will appear here once they are published.</p>
                </Card>
            ) : (
                <div className="grid gap-6">
                    {/* Exams List */}
                    <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {groupedResults.map((exam) => (
                            <Card
                                key={exam.exam_id}
                                className={`p-6 cursor-pointer transition-all hover:shadow-lg ${
                                    selectedExam === exam.exam_id ? 'ring-2 ring-blue-500' : ''
                                }`}
                                onClick={() => fetchExamResults(exam.exam_id)}
                            >
                                <div className="flex items-start justify-between mb-3">
                                    <h3 className="font-semibold text-lg text-gray-800">{exam.exam_name}</h3>
                                    <Badge className="capitalize">{exam.exam_type}</Badge>
                                </div>
                                <p className="text-sm text-gray-500 mb-2">
                                    Date: {new Date(exam.exam_date).toLocaleDateString()}
                                </p>
                                <p className="text-sm text-gray-600">
                                    Subjects: {exam.results.length}
                                </p>
                                <div className="mt-4 text-sm text-blue-600 font-medium">
                                    Click to view details →
                                </div>
                            </Card>
                        ))}
                    </div>

                    {/* Detailed Results View */}
                    {examResults && (
                        <Card className="p-6 mt-6">
                            <div className="mb-6">
                                <h2 className="text-2xl font-bold text-gray-800 mb-4">
                                    {examResults.summary?.exam_name || 'Exam Results'}
                                </h2>

                                {/* Summary Cards */}
                                {examResults.summary && (
                                    <div className="grid md:grid-cols-4 gap-4 mb-6">
                                        <div className="bg-blue-50 p-4 rounded-lg">
                                            <p className="text-sm text-gray-600 mb-1">Total Marks</p>
                                            <p className="text-2xl font-bold text-blue-600">
                                                {examResults.summary.total_marks_obtained?.toFixed(2)} / {examResults.summary.total_possible_marks}
                                            </p>
                                        </div>
                                        <div className="bg-green-50 p-4 rounded-lg">
                                            <p className="text-sm text-gray-600 mb-1">Percentage</p>
                                            <p className="text-2xl font-bold text-green-600">
                                                {examResults.summary.percentage?.toFixed(2)}%
                                            </p>
                                        </div>
                                        <div className="bg-purple-50 p-4 rounded-lg">
                                            <p className="text-sm text-gray-600 mb-1">Grade</p>
                                            <p className="text-2xl font-bold text-purple-600">
                                                {examResults.summary.grade}
                                            </p>
                                        </div>
                                        <div className="bg-yellow-50 p-4 rounded-lg flex items-center gap-2">
                                            <MdEmojiEvents className="text-3xl text-yellow-600" />
                                            <div>
                                                <p className="text-sm text-gray-600">Position</p>
                                                <p className="text-2xl font-bold text-yellow-600">
                                                    {examResults.summary.position}/{examResults.summary.total_students}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Subject-wise Results */}
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700">Subject</th>
                                            <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Test Score</th>
                                            <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Exam Score</th>
                                            <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Total</th>
                                            <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">Grade</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {examResults.results && examResults.results.map((result, index) => (
                                            <tr key={index} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 text-sm font-medium text-gray-800">
                                                    {result.subject_name}
                                                    <span className="text-gray-500 text-xs ml-2">({result.subject_code})</span>
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm text-gray-700">
                                                    {result.test_score?.toFixed(2) || '0.00'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm text-gray-700">
                                                    {result.exam_score?.toFixed(2) || '0.00'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm font-semibold text-gray-800">
                                                    {result.total_score?.toFixed(2) || '0.00'}
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

                            {/* Overall Remarks */}
                            {examResults.summary?.remarks && (
                                <div className="mt-6 p-4 bg-gray-50 rounded-lg">
                                    <p className="text-sm font-semibold text-gray-700 mb-1">Overall Remarks:</p>
                                    <p className="text-gray-600">{examResults.summary.remarks}</p>
                                </div>
                            )}
                        </Card>
                    )}
                </div>
            )}
        </div>
    );
};

export default StudentResults;
