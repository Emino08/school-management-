import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { MdCalendarToday } from 'react-icons/md';
import { toast } from 'sonner';

const AcademicYearSelector = ({ compact = false }) => {
    const dispatch = useDispatch();
    const { currentUser } = useSelector(state => state.user);
    const { academicYearData } = useSelector(state => state.academicYear);
    const [academicYears, setAcademicYears] = useState([]);
    const [currentYear, setCurrentYear] = useState(null);
    const [loading, setLoading] = useState(false);

    const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

    useEffect(() => {
        fetchAcademicYears();
    }, []);

    const fetchAcademicYears = async () => {
        try {
            const response = await axios.get(`${API_URL}/academic-years`, {
                headers: currentUser?.token ? { Authorization: `Bearer ${currentUser.token}` } : {}
            });
            if (response.data.success) {
                const years = response.data.academic_years || [];
                setAcademicYears(years);

                // Set current year - handle different data types
                const current = years.find(y => y.is_current == 1 || y.is_current === true);
                if (current) {
                    setCurrentYear(current);
                    localStorage.setItem('currentAcademicYear', JSON.stringify(current));
                }
            }
        } catch (error) {
            console.error('Error fetching academic years:', error);
        }
    };

    const handleYearChange = async (yearId) => {
        if (loading) return;

        setLoading(true);
        try {
            const response = await axios.put(
                `${API_URL}/academic-years/${yearId}/set-current`,
                {},
                {
                    headers: currentUser?.token ? { Authorization: `Bearer ${currentUser.token}` } : {}
                }
            );

            if (response.data.success) {
                // Refresh the list to get updated current year
                await fetchAcademicYears();
                toast.success('Academic year switched successfully');

                // Reload the page to refresh all data
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        } catch (error) {
            toast.error('Failed to switch academic year');
            console.error('Error switching academic year:', error);
        } finally {
            setLoading(false);
        }
    };

    if (academicYears.length === 0) return null;

    if (compact) {
        return (
            <div className="flex items-center gap-2">
                <MdCalendarToday className="text-gray-500" />
                <select
                    value={currentYear?.id || ''}
                    onChange={(e) => handleYearChange(e.target.value)}
                    disabled={loading}
                    className="text-sm bg-white border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    {academicYears.map(year => {
                        const isCurrent = year.is_current == 1 || year.is_current === true;
                        return (
                            <option key={year.id} value={year.id}>
                                {year.year_name} {isCurrent ? '(Current)' : ''}
                            </option>
                        );
                    })}
                </select>
            </div>
        );
    }

    return (
        <div className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-2">
                    <MdCalendarToday className="text-blue-600 text-xl" />
                    <h3 className="font-semibold text-gray-800">Academic Year</h3>
                </div>
                {currentYear && (
                    <Badge className="bg-blue-600 text-white">Current</Badge>
                )}
            </div>

            <div className="space-y-2">
                {academicYears.map(year => {
                    const isCurrent = year.is_current == 1 || year.is_current === true;
                    return (
                        <button
                            key={year.id}
                            onClick={() => handleYearChange(year.id)}
                            disabled={loading || isCurrent}
                            className={`w-full text-left px-3 py-2 rounded-md transition-colors ${
                                isCurrent
                                    ? 'bg-blue-50 border-2 border-blue-600 text-blue-800 font-medium'
                                    : 'bg-gray-50 hover:bg-gray-100 border border-gray-300 text-gray-700'
                            } ${loading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                        >
                            <div className="flex items-center justify-between">
                                <span>{year.year_name}</span>
                                {isCurrent && (
                                    <Badge variant="outline" className="text-xs">Active</Badge>
                                )}
                            </div>
                            {year.start_date && year.end_date && (
                                <p className="text-xs text-gray-500 mt-1">
                                    {new Date(year.start_date).toLocaleDateString()} - {new Date(year.end_date).toLocaleDateString()}
                                </p>
                            )}
                        </button>
                    );
                })}
            </div>

            <div className="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-gray-600">
                <p><strong>Note:</strong> Switching academic year will filter all data (classes, exams, results, etc.) to show only records from the selected year.</p>
            </div>
        </div>
    );
};

export default AcademicYearSelector;
