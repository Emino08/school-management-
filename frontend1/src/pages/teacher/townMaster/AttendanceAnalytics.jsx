import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { BarChart3, TrendingDown, AlertTriangle, Calendar, RefreshCw } from 'lucide-react';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080/api';

const AttendanceAnalytics = ({ townData }) => {
  const [attendance, setAttendance] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedBlock, setSelectedBlock] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');
  const [dateRange, setDateRange] = useState({
    start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    end: new Date().toISOString().split('T')[0],
  });

  useEffect(() => {
    fetchAttendance();
  }, [selectedBlock, selectedStatus, dateRange]);

  const fetchAttendance = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (selectedStatus) params.append('status', selectedStatus);
      if (dateRange.start) params.append('start_date', dateRange.start);
      if (dateRange.end) params.append('end_date', dateRange.end);

      const response = await axios.get(
        `${API_URL}/teacher/town-master/attendance?${params.toString()}`
      );
      if (response.data.success) {
        let records = response.data.attendance || [];
        
        // Filter by block if selected
        if (selectedBlock) {
          records = records.filter(r => r.block_id?.toString() === selectedBlock);
        }
        
        setAttendance(records);
      }
    } catch (error) {
      console.error('Error fetching attendance:', error);
      toast.error('Failed to fetch attendance analytics');
    } finally {
      setLoading(false);
    }
  };

  // Calculate statistics
  const stats = {
    total: attendance.length,
    present: attendance.filter((a) => a.status === 'present').length,
    absent: attendance.filter((a) => a.status === 'absent').length,
    late: attendance.filter((a) => a.status === 'late').length,
    excused: attendance.filter((a) => a.status === 'excused').length,
  };

  // Group by student to find frequent absentees
  const studentAbsences = {};
  attendance.forEach((record) => {
    if (record.status === 'absent') {
      const key = record.student_id || record.student_name;
      if (!studentAbsences[key]) {
        studentAbsences[key] = {
          student_id: record.student_id,
          student_name: record.student_name,
          id_number: record.id_number,
          block_name: record.block_name,
          count: 0,
        };
      }
      studentAbsences[key].count++;
    }
  });

  const frequentAbsentees = Object.values(studentAbsences)
    .sort((a, b) => b.count - a.count)
    .slice(0, 10);

  // Group by block
  const blockStats = {};
  attendance.forEach((record) => {
    const block = record.block_name;
    if (!blockStats[block]) {
      blockStats[block] = { present: 0, absent: 0, late: 0, excused: 0, total: 0 };
    }
    blockStats[block][record.status]++;
    blockStats[block].total++;
  });

  const getStatusColor = (status) => {
    switch (status) {
      case 'present':
        return 'bg-green-100 text-green-800';
      case 'absent':
        return 'bg-red-100 text-red-800';
      case 'late':
        return 'bg-yellow-100 text-yellow-800';
      case 'excused':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="space-y-6">
      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <BarChart3 className="w-5 h-5" />
            Attendance Analytics
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="space-y-2">
              <Label>Block Filter</Label>
              <Select value={selectedBlock} onValueChange={setSelectedBlock}>
                <SelectTrigger>
                  <SelectValue placeholder="All Blocks" />
                </SelectTrigger>
                <SelectContent>
                  {townData.blocks?.map((block) => (
                    <SelectItem key={block.id} value={block.id.toString()}>
                      Block {block.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Status Filter</Label>
              <Select value={selectedStatus} onValueChange={setSelectedStatus}>
                <SelectTrigger>
                  <SelectValue placeholder="All Statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="present">Present</SelectItem>
                  <SelectItem value="absent">Absent</SelectItem>
                  <SelectItem value="late">Late</SelectItem>
                  <SelectItem value="excused">Excused</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Start Date</Label>
              <input
                type="date"
                value={dateRange.start}
                onChange={(e) => setDateRange({ ...dateRange, start: e.target.value })}
                className="w-full px-3 py-2 border rounded-md"
              />
            </div>

            <div className="space-y-2">
              <Label>End Date</Label>
              <input
                type="date"
                value={dateRange.end}
                onChange={(e) => setDateRange({ ...dateRange, end: e.target.value })}
                className="w-full px-3 py-2 border rounded-md"
                max={new Date().toISOString().split('T')[0]}
              />
            </div>
          </div>

          <div className="flex justify-end">
            <Button onClick={fetchAttendance} variant="outline" size="sm" disabled={loading}>
              <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
              Refresh
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Overall Statistics */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Overall Statistics</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div className="p-4 border rounded-lg bg-gray-50">
              <div className="text-sm text-gray-600">Total Records</div>
              <div className="text-2xl font-bold">{stats.total}</div>
            </div>
            <div className="p-4 border rounded-lg bg-green-50">
              <div className="text-sm text-green-600">Present</div>
              <div className="text-2xl font-bold text-green-700">{stats.present}</div>
              <div className="text-xs text-green-600">
                {stats.total > 0 ? Math.round((stats.present / stats.total) * 100) : 0}%
              </div>
            </div>
            <div className="p-4 border rounded-lg bg-red-50">
              <div className="text-sm text-red-600">Absent</div>
              <div className="text-2xl font-bold text-red-700">{stats.absent}</div>
              <div className="text-xs text-red-600">
                {stats.total > 0 ? Math.round((stats.absent / stats.total) * 100) : 0}%
              </div>
            </div>
            <div className="p-4 border rounded-lg bg-yellow-50">
              <div className="text-sm text-yellow-600">Late</div>
              <div className="text-2xl font-bold text-yellow-700">{stats.late}</div>
              <div className="text-xs text-yellow-600">
                {stats.total > 0 ? Math.round((stats.late / stats.total) * 100) : 0}%
              </div>
            </div>
            <div className="p-4 border rounded-lg bg-blue-50">
              <div className="text-sm text-blue-600">Excused</div>
              <div className="text-2xl font-bold text-blue-700">{stats.excused}</div>
              <div className="text-xs text-blue-600">
                {stats.total > 0 ? Math.round((stats.excused / stats.total) * 100) : 0}%
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Block Statistics */}
      {Object.keys(blockStats).length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Block-wise Statistics</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="border rounded-lg">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Block</TableHead>
                    <TableHead>Present</TableHead>
                    <TableHead>Absent</TableHead>
                    <TableHead>Late</TableHead>
                    <TableHead>Excused</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Attendance Rate</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {Object.entries(blockStats).map(([block, data]) => (
                    <TableRow key={block}>
                      <TableCell className="font-medium">Block {block}</TableCell>
                      <TableCell className="text-green-600">{data.present}</TableCell>
                      <TableCell className="text-red-600">{data.absent}</TableCell>
                      <TableCell className="text-yellow-600">{data.late}</TableCell>
                      <TableCell className="text-blue-600">{data.excused}</TableCell>
                      <TableCell className="font-medium">{data.total}</TableCell>
                      <TableCell>
                        <Badge
                          className={
                            (data.present / data.total) * 100 >= 90
                              ? 'bg-green-100 text-green-800'
                              : (data.present / data.total) * 100 >= 75
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-red-100 text-red-800'
                          }
                        >
                          {Math.round((data.present / data.total) * 100)}%
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Frequent Absentees */}
      {frequentAbsentees.length > 0 && (
        <Card className="border-amber-200 bg-amber-50">
          <CardHeader>
            <CardTitle className="text-lg flex items-center gap-2 text-amber-800">
              <AlertTriangle className="w-5 h-5" />
              Frequent Absentees
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="border rounded-lg bg-white">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID Number</TableHead>
                    <TableHead>Student Name</TableHead>
                    <TableHead>Block</TableHead>
                    <TableHead>Absences</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {frequentAbsentees.map((student, index) => (
                    <TableRow key={index}>
                      <TableCell className="font-medium">{student.id_number}</TableCell>
                      <TableCell>{student.student_name}</TableCell>
                      <TableCell>Block {student.block_name}</TableCell>
                      <TableCell className="font-bold text-red-600">{student.count}</TableCell>
                      <TableCell>
                        {student.count >= 3 ? (
                          <Badge className="bg-red-100 text-red-800">
                            <AlertTriangle className="w-3 h-3 mr-1" />
                            Urgent
                          </Badge>
                        ) : (
                          <Badge className="bg-yellow-100 text-yellow-800">
                            <TrendingDown className="w-3 h-3 mr-1" />
                            Watch
                          </Badge>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            <div className="mt-4 p-3 bg-white border border-amber-200 rounded-lg text-sm text-amber-800">
              <strong>Note:</strong> Students with 3 or more absences in the selected period are
              flagged as urgent. The principal has been automatically notified.
            </div>
          </CardContent>
        </Card>
      )}

      {/* Recent Records */}
      {attendance.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Recent Attendance Records</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="border rounded-lg">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Time</TableHead>
                    <TableHead>Student</TableHead>
                    <TableHead>Block</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Notes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {attendance.slice(0, 20).map((record, index) => (
                    <TableRow key={index}>
                      <TableCell>{new Date(record.date).toLocaleDateString()}</TableCell>
                      <TableCell>{record.time}</TableCell>
                      <TableCell>{record.student_name}</TableCell>
                      <TableCell>Block {record.block_name}</TableCell>
                      <TableCell>
                        <Badge className={getStatusColor(record.status)}>{record.status}</Badge>
                      </TableCell>
                      <TableCell className="text-sm text-gray-600">
                        {record.notes || '-'}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {attendance.length > 20 && (
              <div className="mt-2 text-sm text-gray-500 text-center">
                Showing 20 of {attendance.length} records
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {attendance.length === 0 && !loading && (
        <Card>
          <CardContent className="py-12 text-center text-gray-500">
            <Calendar className="w-12 h-12 mx-auto mb-4 opacity-50" />
            <p>No attendance records found for the selected criteria.</p>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default AttendanceAnalytics;
