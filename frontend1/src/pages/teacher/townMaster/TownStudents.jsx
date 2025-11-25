import React, { useState, useEffect } from 'react';
import axios from '@/redux/axiosConfig';
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
import { Users, Filter, RefreshCw, Eye } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const TownStudents = ({ townData, onRefresh }) => {
  const [students, setStudents] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedBlock, setSelectedBlock] = useState('');
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [showDetailDialog, setShowDetailDialog] = useState(false);

  useEffect(() => {
    fetchStudents();
  }, [selectedBlock]);

  const fetchStudents = async () => {
    try {
      setLoading(true);
      const params = selectedBlock ? `?block_id=${selectedBlock}` : '';
      const response = await axios.get(`/teacher/town-master/students${params}`);
      if (response.data.success) {
        setStudents(response.data.students || []);
      }
    } catch (error) {
      console.error('Error fetching students:', error);
      toast.error('Failed to fetch students');
    } finally {
      setLoading(false);
    }
  };

  const handleViewDetails = (student) => {
    setSelectedStudent(student);
    setShowDetailDialog(true);
  };

  const getBlockColor = (blockName) => {
    const colors = {
      A: 'bg-red-100 text-red-800',
      B: 'bg-blue-100 text-blue-800',
      C: 'bg-green-100 text-green-800',
      D: 'bg-yellow-100 text-yellow-800',
      E: 'bg-purple-100 text-purple-800',
      F: 'bg-pink-100 text-pink-800',
    };
    return colors[blockName] || 'bg-gray-100 text-gray-800';
  };

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <Users className="w-5 h-5" />
              Registered Students
            </CardTitle>
            <Button onClick={fetchStudents} variant="outline" size="sm" disabled={loading}>
              <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
              Refresh
            </Button>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Filter */}
          <div className="flex items-center gap-4">
            <div className="flex-1">
              <Select value={selectedBlock} onValueChange={setSelectedBlock}>
                <SelectTrigger>
                  <SelectValue placeholder="All Blocks" />
                </SelectTrigger>
                <SelectContent>
                  {townData.blocks?.map((block) => (
                    <SelectItem key={block.id} value={block.id.toString()}>
                      Block {block.name} ({block.current_occupancy || 0}/{block.capacity})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <Badge variant="secondary" className="px-4 py-2">
              Total: {students.length} students
            </Badge>
          </div>

          {/* Block Overview */}
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
            {townData.blocks?.map((block) => (
              <div
                key={block.id}
                className={`p-3 rounded-lg border ${
                  selectedBlock === block.id.toString() ? 'border-purple-500 bg-purple-50' : ''
                }`}
              >
                <div className="text-center">
                  <div className="text-2xl font-bold">Block {block.name}</div>
                  <div className="text-sm text-gray-600">
                    {block.current_occupancy || 0}/{block.capacity}
                  </div>
                  <div className="text-xs text-gray-500 mt-1">
                    {block.capacity - (block.current_occupancy || 0)} spaces
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Students Table */}
          {loading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
            </div>
          ) : students.length > 0 ? (
            <div className="border rounded-lg">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID Number</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Class</TableHead>
                    <TableHead>Block</TableHead>
                    <TableHead>Guardian</TableHead>
                    <TableHead>Contact</TableHead>
                    <TableHead>Action</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {students.map((student) => (
                    <TableRow key={student.id}>
                      <TableCell className="font-medium">{student.id_number}</TableCell>
                      <TableCell>
                        {student.first_name && student.last_name
                          ? `${student.first_name} ${student.last_name}`
                          : student.name}
                      </TableCell>
                      <TableCell>{student.class_name}</TableCell>
                      <TableCell>
                        <Badge className={getBlockColor(student.block_name)}>
                          Block {student.block_name}
                        </Badge>
                      </TableCell>
                      <TableCell>{student.guardian_name || 'N/A'}</TableCell>
                      <TableCell>{student.guardian_phone || 'N/A'}</TableCell>
                      <TableCell>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => handleViewDetails(student)}
                        >
                          <Eye className="w-4 h-4" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">
              <Users className="w-12 h-12 mx-auto mb-2 opacity-50" />
              <p>No students registered yet</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Student Details Dialog */}
      <Dialog open={showDetailDialog} onOpenChange={setShowDetailDialog}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>Student Details</DialogTitle>
          </DialogHeader>
          {selectedStudent && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <div className="text-sm text-gray-600">ID Number</div>
                  <div className="font-medium">{selectedStudent.id_number}</div>
                </div>
                <div>
                  <div className="text-sm text-gray-600">Name</div>
                  <div className="font-medium">
                    {selectedStudent.first_name && selectedStudent.last_name
                      ? `${selectedStudent.first_name} ${selectedStudent.last_name}`
                      : selectedStudent.name}
                  </div>
                </div>
                <div>
                  <div className="text-sm text-gray-600">Class</div>
                  <div className="font-medium">{selectedStudent.class_name}</div>
                </div>
                <div>
                  <div className="text-sm text-gray-600">Block</div>
                  <Badge className={getBlockColor(selectedStudent.block_name)}>
                    Block {selectedStudent.block_name}
                  </Badge>
                </div>
              </div>

              <div className="border-t pt-4">
                <h4 className="font-semibold mb-3">Guardian Information</h4>
                <div className="space-y-2">
                  <div>
                    <div className="text-sm text-gray-600">Name</div>
                    <div className="font-medium">{selectedStudent.guardian_name || 'N/A'}</div>
                  </div>
                  <div>
                    <div className="text-sm text-gray-600">Phone</div>
                    <div className="font-medium">{selectedStudent.guardian_phone || 'N/A'}</div>
                  </div>
                  <div>
                    <div className="text-sm text-gray-600">Email</div>
                    <div className="font-medium">{selectedStudent.guardian_email || 'N/A'}</div>
                  </div>
                  <div>
                    <div className="text-sm text-gray-600">Address</div>
                    <div className="font-medium">{selectedStudent.guardian_address || 'N/A'}</div>
                  </div>
                </div>
              </div>

              <div className="border-t pt-4">
                <div className="text-sm text-gray-600">Academic Year & Term</div>
                <div className="font-medium">
                  Year {selectedStudent.academic_year_id} - Term {selectedStudent.term}
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default TownStudents;
