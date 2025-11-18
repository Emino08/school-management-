import React, { useCallback, useEffect, useMemo, useState } from 'react'
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { getUserDetails } from '../../../redux/userRelated/userHandle';
import { getSubjectList } from '../../../redux/sclassRelated/sclassHandle';
import { MdRotateRight, MdRefresh } from "react-icons/md";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from 'sonner';
import axios from '@/redux/axiosConfig';
import dayjs from 'dayjs';

const StudentAttendance = ({ situation }) => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const { currentUser, userDetails, loading } = useSelector((state) => state.user);
    const { subjectsList } = useSelector((state) => state.sclass);
    const params = useParams()

    const [studentID, setStudentID] = useState("");
    const [selectedSubjectId, setSelectedSubjectId] = useState("");
    const [status, setStatus] = useState('');
    const [date, setDate] = useState(dayjs().format('YYYY-MM-DD'));
    const [filters, setFilters] = useState({ subjectId: '', start: '', end: '' });
    const [attendanceRecords, setAttendanceRecords] = useState([]);
    const [stats, setStats] = useState(null);
    const [loader, setLoader] = useState(false);
    const [fetching, setFetching] = useState(true);

    const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

    const headers = useMemo(() => ({
        Authorization: `Bearer ${currentUser?.token}`,
    }), [currentUser?.token]);

    useEffect(() => {
        if (situation === "Student") {
            setStudentID(params.id);
            dispatch(getUserDetails(params.id, "Student"));
        } else if (situation === "Subject") {
            const { studentID, subjectID } = params;
            setStudentID(studentID);
            setSelectedSubjectId(subjectID);
            dispatch(getUserDetails(studentID, "Student"));
        }
    }, [dispatch, params, situation]);

    useEffect(() => {
        if (userDetails?.sclassName?._id && situation === "Student") {
            dispatch(getSubjectList(userDetails.sclassName._id, "ClassSubjects"));
        }
    }, [dispatch, userDetails, situation]);

    const fetchAttendance = useCallback(async () => {
        if (!studentID || !headers.Authorization) return;
        setFetching(true);
        try {
            const res = await axios.get(`${API_URL}/students/${studentID}/attendance`, {
                headers,
                params: {
                    subject_id: filters.subjectId || undefined,
                    start: filters.start || undefined,
                    end: filters.end || undefined,
                },
            });
            setAttendanceRecords(Array.isArray(res.data?.attendance) ? res.data.attendance : []);
        } catch (error) {
            console.error("Failed to fetch attendance", error);
            toast.error("Unable to load attendance history");
        } finally {
            setFetching(false);
        }
    }, [API_URL, studentID, headers, filters]);

    const fetchStats = useCallback(async () => {
        if (!studentID || !headers.Authorization) return;
        try {
            const res = await axios.get(`${API_URL}/attendance/students/${studentID}/stats`, { headers });
            setStats(res.data?.stats || null);
        } catch (error) {
            console.error("Failed to fetch stats", error);
        }
    }, [API_URL, studentID, headers]);

    useEffect(() => {
        if (studentID) {
            fetchAttendance();
            fetchStats();
        }
    }, [studentID, fetchAttendance, fetchStats]);

    const handleFilterChange = (field, value) => {
        setFilters((prev) => ({ ...prev, [field]: value }));
    };

    const handleFilterSubmit = (e) => {
        e.preventDefault();
        fetchAttendance();
    };

    const submitHandler = async (event) => {
        event.preventDefault();
        if (!selectedSubjectId) {
            toast.error("Please select a subject to mark attendance");
            return;
        }
        setLoader(true);
        try {
            await axios.post(`${API_URL}/attendance`, {
                student_id: studentID,
                subject_id: selectedSubjectId,
                status,
                date,
            }, { headers });
            toast.success("Attendance recorded");
            fetchAttendance();
            fetchStats();
            setStatus('');
        } catch (error) {
            console.error("Failed to mark attendance", error);
            toast.error(error.response?.data?.message || "Unable to mark attendance");
        } finally {
            setLoader(false);
        }
    };

    const filteredSubjects = useMemo(() => {
        if (!Array.isArray(subjectsList)) return [];
        return subjectsList.map((subject) => ({
            id: subject._id || subject.id,
            name: subject.subName || subject.subject_name,
        })).filter((s) => s.id);
    }, [subjectsList]);

    const summaryCards = [
        {
            title: "Total Sessions",
            value: stats?.total_sessions ?? attendanceRecords.length,
            badge: "Academic year",
        },
        {
            title: "Present",
            value: stats?.present ?? attendanceRecords.filter((r) => r.status?.toLowerCase() === "present").length,
            badge: "Sessions",
        },
        {
            title: "Absent",
            value: stats?.absent ?? attendanceRecords.filter((r) => r.status?.toLowerCase() === "absent").length,
            badge: "Sessions",
        },
        {
            title: "Attendance Rate",
            value: stats?.percentage ? `${stats.percentage}%` : "--",
            badge: "This year",
        },
    ];

    const recentRecords = attendanceRecords.slice(0, 50);

    return (
        <div className="max-w-6xl mx-auto py-8 px-4 space-y-6">
            {loading ? (
                <div className="flex items-center justify-center h-40">
                    <p className="text-lg text-gray-500 flex items-center gap-2">
                        <MdRotateRight className="h-5 w-5 animate-spin" /> Loading student attendance...
                    </p>
                </div>
            ) : (
                <>
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-gray-500">Attendance overview</p>
                            <h1 className="text-2xl font-bold text-gray-900">{userDetails?.name || "Student"}</h1>
                            <p className="text-sm text-gray-500">
                                {userDetails?.sclassName?.class_name || userDetails?.sclassName?.sclassName || "Class not set"}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => navigate(-1)}>
                                Back
                            </Button>
                            <Button variant="outline" onClick={() => { fetchAttendance(); fetchStats(); }}>
                                <MdRefresh className="h-4 w-4 mr-2" />
                                Refresh
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {summaryCards.map((card) => (
                            <Card key={card.title}>
                                <CardContent className="pt-4">
                                    <p className="text-xs uppercase tracking-wide text-gray-500 flex items-center justify-between">
                                        {card.title}
                                        <Badge variant="secondary">{card.badge}</Badge>
                                    </p>
                                    <p className="text-2xl font-semibold mt-2">{card.value}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Mark Attendance</CardTitle>
                            <CardDescription>Capture a new attendance record for this student</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitHandler} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div className="space-y-2">
                                    <Label>Select Subject</Label>
                                    <Select value={selectedSubjectId} onValueChange={setSelectedSubjectId} required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Choose subject" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filteredSubjects.length > 0 ? (
                                                filteredSubjects.map((subject) => (
                                                    <SelectItem key={subject.id} value={subject.id.toString()}>
                                                        {subject.name}
                                                    </SelectItem>
                                                ))
                                            ) : (
                                                <SelectItem value="none" disabled>
                                                    No subjects available
                                                </SelectItem>
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Attendance Status</Label>
                                    <Select value={status} onValueChange={setStatus} required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Choose status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="present">Present</SelectItem>
                                            <SelectItem value="absent">Absent</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Date</Label>
                                    <Input
                                        type="date"
                                        value={date}
                                        max={dayjs().format('YYYY-MM-DD')}
                                        onChange={(event) => setDate(event.target.value)}
                                        required
                                    />
                                </div>
                                <div className="flex items-end">
                                    <Button
                                        type="submit"
                                        disabled={loader}
                                        className="w-full bg-purple-600 hover:bg-purple-700"
                                    >
                                        {loader ? <MdRotateRight className="h-4 w-4 animate-spin" /> : "Record Attendance"}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Attendance History</CardTitle>
                            <CardDescription>Filter and review attendance entries for this student</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form onSubmit={handleFilterSubmit} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <Label>Subject</Label>
                                    <Select
                                        value={filters.subjectId}
                                        onValueChange={(value) => handleFilterChange('subjectId', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All subjects" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">All</SelectItem>
                                            {filteredSubjects.map((subject) => (
                                                <SelectItem key={subject.id} value={subject.id.toString()}>
                                                    {subject.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Start Date</Label>
                                    <Input
                                        type="date"
                                        value={filters.start}
                                        onChange={(e) => handleFilterChange('start', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <Label>End Date</Label>
                                    <Input
                                        type="date"
                                        value={filters.end}
                                        onChange={(e) => handleFilterChange('end', e.target.value)}
                                    />
                                </div>
                                <div className="flex items-end gap-2">
                                    <Button type="submit" className="flex-1">
                                        Apply Filters
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setFilters({ subjectId: '', start: '', end: '' });
                                            fetchAttendance();
                                        }}
                                    >
                                        Reset
                                    </Button>
                                </div>
                            </form>

                            {fetching ? (
                                <div className="flex items-center justify-center py-8 text-gray-500">
                                    <MdRotateRight className="h-4 w-4 animate-spin mr-2" />
                                    Loading records...
                                </div>
                            ) : recentRecords.length === 0 ? (
                                <div className="text-center py-10 text-gray-500 border rounded-lg">
                                    No attendance records found for the selected range.
                                </div>
                            ) : (
                                <div className="overflow-auto rounded-lg border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Subject</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Remarks</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {recentRecords.map((record) => (
                                                <TableRow key={`${record.id}-${record.date}`}>
                                                    <TableCell>{dayjs(record.date).format('DD MMM YYYY')}</TableCell>
                                                    <TableCell>{record.subject_name || record.subject || '—'}</TableCell>
                                                    <TableCell>
                                                        <Badge variant={record.status?.toLowerCase() === 'present' ? 'secondary' : 'destructive'}>
                                                            {record.status}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>{record.remarks || '—'}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </>
            )}
        </div>
    )
}

export default StudentAttendance
