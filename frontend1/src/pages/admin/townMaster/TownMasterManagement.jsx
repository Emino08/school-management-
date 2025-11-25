import { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';
import {
  FiHome, FiPlus, FiEdit2, FiTrash2, FiSearch, FiUsers, FiLayers
} from 'react-icons/fi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const TownMasterManagement = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [towns, setTowns] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [createDialogOpen, setCreateDialogOpen] = useState(false);
  const [editDialogOpen, setEditDialogOpen] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [selectedTown, setSelectedTown] = useState(null);
  const [teachers, setTeachers] = useState([]);
  const [blocksDialogOpen, setBlocksDialogOpen] = useState(false);
  const [selectedTownBlocks, setSelectedTownBlocks] = useState([]);

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
        headers: { Authorization: `Bearer ${currentUser?.token}` }
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
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      
      if (response.data.success) {
        setTeachers(response.data.teachers || []);
      }
    } catch (error) {
      console.error('Error fetching teachers:', error);
    }
  };

  const fetchTownBlocks = async (townId) => {
    try {
      const response = await axios.get(`/admin/towns/${townId}/blocks`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      
      if (response.data.success) {
        setSelectedTownBlocks(response.data.blocks || []);
      }
    } catch (error) {
      console.error('Error fetching blocks:', error);
      toast.error('Failed to load blocks');
    }
  };

  const handleCreateTown = async () => {
    if (!formData.name) {
      toast.error('Please enter town name');
      return;
    }

    try {
      const response = await axios.post('/admin/towns', formData, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });

      if (response.data.success) {
        toast.success('Town created successfully');
        setCreateDialogOpen(false);
        setFormData({ name: '', description: '' });
        fetchTowns();
      }
    } catch (error) {
      console.error('Error creating town:', error);
      toast.error(error.response?.data?.message || 'Failed to create town');
    }
  };

  const handleUpdateTown = async () => {
    if (!selectedTown) return;

    try {
      const response = await axios.put(
        `/admin/towns/${selectedTown.id}`,
        formData,
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` }
        }
      );

      if (response.data.success) {
        toast.success('Town updated successfully');
        setEditDialogOpen(false);
        setSelectedTown(null);
        setFormData({ name: '', description: '' });
        fetchTowns();
      }
    } catch (error) {
      console.error('Error updating town:', error);
      toast.error(error.response?.data?.message || 'Failed to update town');
    }
  };

  const handleDeleteTown = async () => {
    if (!selectedTown) return;

    try {
      const response = await axios.delete(`/admin/towns/${selectedTown.id}`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });

      if (response.data.success) {
        toast.success('Town deleted successfully');
        setDeleteDialogOpen(false);
        setSelectedTown(null);
        fetchTowns();
      }
    } catch (error) {
      console.error('Error deleting town:', error);
      toast.error(error.response?.data?.message || 'Failed to delete town');
    }
  };

  const handleViewBlocks = async (town) => {
    setSelectedTown(town);
    await fetchTownBlocks(town.id);
    setBlocksDialogOpen(true);
  };

  const handleEditClick = (town) => {
    setSelectedTown(town);
    setFormData({
      name: town.name,
      description: town.description || '',
    });
    setEditDialogOpen(true);
  };

  const handleDeleteClick = (town) => {
    setSelectedTown(town);
    setDeleteDialogOpen(true);
  };

  const filteredTowns = towns.filter((town) =>
    town.name?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Town Master Management</h1>
          <p className="text-gray-600 mt-1">Manage towns and blocks for student housing</p>
        </div>
        <Button
          onClick={() => {
            setFormData({ name: '', description: '' });
            setCreateDialogOpen(true);
          }}
          className="flex items-center gap-2"
        >
          <FiPlus className="w-4 h-4" />
          Create Town
        </Button>
      </div>

      {/* Search */}
      <div className="flex gap-4">
        <div className="relative flex-1">
          <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <Input
            type="text"
            placeholder="Search towns..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>
      </div>

      {/* Towns Grid */}
      {loading ? (
        <div className="flex items-center justify-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
      ) : filteredTowns.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <FiHome className="w-16 h-16 text-gray-300 mb-4" />
            <p className="text-gray-500">No towns found</p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredTowns.map((town) => (
            <Card key={town.id} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <FiHome className="w-5 h-5 text-blue-600" />
                  {town.name}
                </CardTitle>
                {town.description && (
                  <CardDescription>{town.description}</CardDescription>
                )}
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-600">Total Blocks:</span>
                  <span className="font-semibold">{town.block_count || 6}</span>
                </div>
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-600">Total Students:</span>
                  <span className="font-semibold">{town.student_count || 0}</span>
                </div>
                {town.town_master_name && (
                  <div className="text-sm">
                    <span className="text-gray-600">Town Master:</span>
                    <p className="font-semibold">{town.town_master_name}</p>
                  </div>
                )}
                
                <div className="flex gap-2 pt-4 border-t">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleViewBlocks(town)}
                    className="flex-1"
                  >
                    <FiLayers className="w-4 h-4 mr-1" />
                    View Blocks
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => handleEditClick(town)}
                    className="text-blue-600 hover:text-blue-700"
                  >
                    <FiEdit2 className="w-4 h-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => handleDeleteClick(town)}
                    className="text-red-600 hover:text-red-700"
                  >
                    <FiTrash2 className="w-4 h-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Create Town Dialog */}
      <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Create New Town</DialogTitle>
            <DialogDescription>
              Add a new town for student housing management
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Town Name *</Label>
              <Input
                id="name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="Enter town name"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Input
                id="description"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Enter description (optional)"
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleCreateTown}>Create Town</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit Town Dialog */}
      <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Town</DialogTitle>
            <DialogDescription>Update town information</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="edit-name">Town Name *</Label>
              <Input
                id="edit-name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="Enter town name"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="edit-description">Description</Label>
              <Input
                id="edit-description"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Enter description (optional)"
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleUpdateTown}>Update Town</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Town</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{selectedTown?.name}"? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteTown}
              className="bg-red-600 hover:bg-red-700"
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Blocks Dialog */}
      <Dialog open={blocksDialogOpen} onOpenChange={setBlocksDialogOpen}>
        <DialogContent className="max-w-3xl">
          <DialogHeader>
            <DialogTitle>Blocks in {selectedTown?.name}</DialogTitle>
            <DialogDescription>View and manage blocks (A-F)</DialogDescription>
          </DialogHeader>
          <div className="space-y-3 max-h-[400px] overflow-y-auto">
            {selectedTownBlocks.length === 0 ? (
              <p className="text-center text-gray-500 py-8">No blocks configured</p>
            ) : (
              selectedTownBlocks.map((block) => (
                <div
                  key={block.id}
                  className="p-4 border rounded-lg flex items-center justify-between hover:bg-gray-50"
                >
                  <div>
                    <h4 className="font-semibold">Block {block.block_name}</h4>
                    <p className="text-sm text-gray-600">
                      Capacity: {block.capacity} | Current: {block.current_count || 0}
                    </p>
                  </div>
                  <div className="text-right">
                    <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                      (block.current_count || 0) >= block.capacity
                        ? 'bg-red-100 text-red-800'
                        : 'bg-green-100 text-green-800'
                    }`}>
                      {(block.current_count || 0) >= block.capacity ? 'Full' : 'Available'}
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setBlocksDialogOpen(false)}>
              Close
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default TownMasterManagement;
