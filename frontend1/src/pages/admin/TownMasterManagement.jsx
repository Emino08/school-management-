import React, { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Home, Plus, Edit, Trash2, Users, User, BarChart2 } from 'lucide-react';

const TownMasterManagement = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [towns, setTowns] = useState([]);
  const [teachers, setTeachers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showTownDialog, setShowTownDialog] = useState(false);
  const [showAssignDialog, setShowAssignDialog] = useState(false);
  const [selectedTown, setSelectedTown] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
  });

  useEffect(() => {
    fetchTowns();
    fetchTeachers();
  }, []);

  const fetchTowns = async () => {
    setLoading(true);
    try {
      const response = await axios.get('/admin/towns', {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (response.data.success) {
        setTowns(response.data.towns || []);
      }
    } catch (error) {
      console.error('Error fetching towns:', error);
      toast.error('Failed to load towns');
    } finally {
      setLoading(false);
    }
  };

  const fetchTeachers = async () => {
    try {
      const response = await axios.get('/teachers', {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (response.data.success) {
        setTeachers(response.data.teachers || []);
      }
    } catch (error) {
      console.error('Error fetching teachers:', error);
    }
  };

  const handleCreateTown = async () => {
    if (!formData.name.trim()) {
      toast.error('Town name is required');
      return;
    }

    try {
      const response = await axios.post(
        '/admin/towns',
        formData,
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );

      if (response.data.success) {
        toast.success('Town created successfully');
        setShowTownDialog(false);
        setFormData({ name: '', description: '' });
        fetchTowns();
      } else {
        toast.error(response.data.message || 'Failed to create town');
      }
    } catch (error) {
      console.error('Error creating town:', error);
      toast.error('Failed to create town: ' + (error.response?.data?.message || error.message));
    }
  };

  const handleUpdateTown = async () => {
    if (!selectedTown || !formData.name.trim()) {
      toast.error('Town name is required');
      return;
    }

    try {
      const response = await axios.put(
        `/admin/towns/${selectedTown.id}`,
        formData,
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );

      if (response.data.success) {
        toast.success('Town updated successfully');
        setShowTownDialog(false);
        setSelectedTown(null);
        setFormData({ name: '', description: '' });
        fetchTowns();
      } else {
        toast.error(response.data.message || 'Failed to update town');
      }
    } catch (error) {
      console.error('Error updating town:', error);
      toast.error('Failed to update town');
    }
  };

  const handleDeleteTown = async (townId) => {
    if (!confirm('Are you sure you want to delete this town?')) return;

    try {
      const response = await axios.delete(`/admin/towns/${townId}`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });

      if (response.data.success) {
        toast.success('Town deleted successfully');
        fetchTowns();
      } else {
        toast.error(response.data.message || 'Failed to delete town');
      }
    } catch (error) {
      console.error('Error deleting town:', error);
      toast.error('Failed to delete town');
    }
  };

  const handleAssignMaster = async (teacherId) => {
    if (!selectedTown || !teacherId) {
      toast.error('Please select a teacher');
      return;
    }

    try {
      const response = await axios.post(
        `/admin/towns/${selectedTown.id}/assign-master`,
        { teacher_id: teacherId },
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );

      if (response.data.success) {
        toast.success('Town master assigned successfully');
        setShowAssignDialog(false);
        setSelectedTown(null);
        fetchTowns();
      } else {
        toast.error(response.data.message || 'Failed to assign town master');
      }
    } catch (error) {
      console.error('Error assigning town master:', error);
      toast.error('Failed to assign town master');
    }
  };

  const openCreateDialog = () => {
    setSelectedTown(null);
    setFormData({ name: '', description: '' });
    setShowTownDialog(true);
  };

  const openEditDialog = (town) => {
    setSelectedTown(town);
    setFormData({ name: town.name, description: town.description || '' });
    setShowTownDialog(true);
  };

  const openAssignDialog = (town) => {
    setSelectedTown(town);
    setShowAssignDialog(true);
  };

  const totalBlocks = towns.reduce((sum, town) => sum + (town.block_count || 0), 0);
  const totalStudents = towns.reduce((sum, town) => sum + (town.student_count || 0), 0);
  const averageAttendance = towns.length
    ? (
        towns.reduce((sum, town) => sum + (town.attendance_rate_30d || 0), 0) / towns.length
      ).toFixed(1)
    : 0;

  return (
    <div className="container mx-auto p-6">
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-gray-500">Towns</CardTitle>
          </CardHeader>
          <CardContent className="flex items-center justify-between">
            <div className="text-2xl font-semibold">{towns.length}</div>
            <Home className="w-5 h-5 text-blue-600" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-gray-500">Blocks</CardTitle>
          </CardHeader>
          <CardContent className="flex items-center justify-between">
            <div className="text-2xl font-semibold">{totalBlocks || 6}</div>
            <Users className="w-5 h-5 text-indigo-600" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-gray-500">Students (towns)</CardTitle>
          </CardHeader>
          <CardContent className="flex items-center justify-between">
            <div className="text-2xl font-semibold">{totalStudents}</div>
            <User className="w-5 h-5 text-emerald-600" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-gray-500">Attendance (30d avg)</CardTitle>
          </CardHeader>
          <CardContent className="flex items-center justify-between">
            <div className="text-2xl font-semibold">{averageAttendance}%</div>
            <BarChart2 className="w-5 h-5 text-orange-600" />
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <Home className="w-6 h-6" />
            Town Master Management
          </CardTitle>
          <Button onClick={openCreateDialog} className="gap-2">
            <Plus className="w-4 h-4" />
            Create Town
          </Button>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">Loading towns...</div>
          ) : towns.length === 0 ? (
            <div className="text-center py-8">
              <Home className="w-12 h-12 mx-auto text-gray-300" />
              <p className="mt-2 text-gray-500">No towns found</p>
              <Button variant="link" onClick={openCreateDialog} className="mt-2">
                Create your first town
              </Button>
            </div>
          ) : (
            <Table>
              <TableHeader>
              <TableRow>
                <TableHead>Town Name</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Blocks</TableHead>
                <TableHead>Students</TableHead>
                <TableHead>Attendance (30d)</TableHead>
                <TableHead>Town Master</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
              <TableBody>
                {towns.map((town) => (
                <TableRow key={town.id}>
                  <TableCell className="font-medium">{town.name}</TableCell>
                  <TableCell>{town.description || 'N/A'}</TableCell>
                  <TableCell>
                    {town.block_count ?? 6} Blocks
                  </TableCell>
                  <TableCell>{town.student_count ?? 0}</TableCell>
                  <TableCell>
                    <div className="flex flex-col text-sm">
                      <span className="font-semibold">{(town.attendance_rate_30d ?? 0)}%</span>
                      <span className="text-gray-500">
                        P: {town.attendance_present_30d ?? 0} | A: {town.attendance_absent_30d ?? 0}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell>
                    {town.town_master?.name || town.town_master_name ? (
                      <div className="flex items-center gap-2">
                        <User className="w-4 h-4" />
                        {town.town_master?.name || town.town_master_name}
                      </div>
                    ) : (
                      <span className="text-gray-400">Not assigned</span>
                    )}
                  </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => openAssignDialog(town)}
                          className="gap-2"
                        >
                          <Users className="w-4 h-4" />
                          Assign Master
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => openEditDialog(town)}
                        >
                          <Edit className="w-4 h-4" />
                        </Button>
                        <Button
                          variant="destructive"
                          size="sm"
                          onClick={() => handleDeleteTown(town.id)}
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Create/Edit Town Dialog */}
      <Dialog open={showTownDialog} onOpenChange={setShowTownDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {selectedTown ? 'Edit Town' : 'Create New Town'}
            </DialogTitle>
            <DialogDescription>
              {selectedTown
                ? 'Update town information'
                : 'Create a new town with 6 blocks (A-F)'}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label htmlFor="name">Town Name *</Label>
              <Input
                id="name"
                value={formData.name}
                onChange={(e) =>
                  setFormData({ ...formData, name: e.target.value })
                }
                placeholder="e.g., North Town, South Town"
              />
            </div>
            <div>
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                value={formData.description}
                onChange={(e) =>
                  setFormData({ ...formData, description: e.target.value })
                }
                placeholder="Optional description"
                rows={3}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowTownDialog(false)}>
              Cancel
            </Button>
            <Button onClick={selectedTown ? handleUpdateTown : handleCreateTown}>
              {selectedTown ? 'Update' : 'Create'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Assign Town Master Dialog */}
      <Dialog open={showAssignDialog} onOpenChange={setShowAssignDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Assign Town Master</DialogTitle>
            <DialogDescription>
              Select a teacher to be the town master for {selectedTown?.name}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label htmlFor="teacher">Select Teacher *</Label>
              <Select onValueChange={(value) => handleAssignMaster(value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Choose a teacher" />
                </SelectTrigger>
                <SelectContent>
                  {teachers.map((teacher) => (
                    <SelectItem key={teacher.id} value={teacher.id.toString()}>
                      {teacher.first_name && teacher.last_name
                        ? `${teacher.first_name} ${teacher.last_name}`
                        : teacher.name || `Teacher #${teacher.id}`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setShowAssignDialog(false)}
            >
              Cancel
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default TownMasterManagement;
