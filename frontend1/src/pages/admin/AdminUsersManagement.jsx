import { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from '../../redux/axiosConfig';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { UserPlus, Shield, User, Mail, Phone, Calendar } from 'lucide-react';

const AdminUsersManagement = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [admins, setAdmins] = useState([]);
  const [isSuperAdmin, setIsSuperAdmin] = useState(false);
  const [loading, setLoading] = useState(true);
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [creating, setCreating] = useState(false);
  
  const [formData, setFormData] = useState({
    contact_name: '',
    email: '',
    password: '',
    phone: '',
    signature: ''
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  useEffect(() => {
    checkSuperAdminStatus();
    if (isSuperAdmin) {
      fetchAdminUsers();
    }
  }, [isSuperAdmin]);

  const checkSuperAdminStatus = async () => {
    try {
      const response = await axios.get(`${API_URL}/admin/super-admin-status`);
      setIsSuperAdmin(response.data.is_super_admin);
      setLoading(false);
    } catch (error) {
      console.error('Error checking super admin status:', error);
      setIsSuperAdmin(false);
      setLoading(false);
    }
  };

  const fetchAdminUsers = async () => {
    try {
      const response = await axios.get(`${API_URL}/admin/admin-users`);
      if (response.data.success) {
        setAdmins(response.data.admins);
      }
    } catch (error) {
      console.error('Error fetching admin users:', error);
      toast.error('Failed to load admin users');
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleCreateAdmin = async (e) => {
    e.preventDefault();
    
    if (!formData.contact_name || !formData.email || !formData.password) {
      toast.error('Please fill in all required fields');
      return;
    }

    if (formData.password.length < 6) {
      toast.error('Password must be at least 6 characters');
      return;
    }

    setCreating(true);
    try {
      const response = await axios.post(`${API_URL}/admin/admin-users`, formData);
      
      if (response.data.success) {
        toast.success('Admin user created successfully');
        setShowCreateDialog(false);
        setFormData({
          contact_name: '',
          email: '',
          password: '',
          phone: '',
          signature: ''
        });
        fetchAdminUsers();
      }
    } catch (error) {
      console.error('Error creating admin user:', error);
      const message = error.response?.data?.message || 'Failed to create admin user';
      toast.error(message);
    } finally {
      setCreating(false);
    }
  };

  const generatePassword = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
    let password = '';
    for (let i = 0; i < 12; i++) {
      password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    setFormData(prev => ({ ...prev, password }));
    toast.success('Password generated');
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  if (!isSuperAdmin) {
    return (
      <div className="container mx-auto px-4 py-8">
        <Card className="max-w-2xl mx-auto">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-red-600">
              <Shield className="h-6 w-6" />
              Access Denied
            </CardTitle>
            <CardDescription>
              Only super administrators can access this page.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <p className="text-gray-600">
              You need super admin privileges to manage admin users. Please contact your system administrator.
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
              <Shield className="h-8 w-8 text-blue-600" />
              Admin Users Management
            </h1>
            <p className="mt-2 text-gray-600">
              Manage admin users for your school system
            </p>
          </div>
          
          <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
            <DialogTrigger asChild>
              <Button className="flex items-center gap-2">
                <UserPlus className="h-4 w-4" />
                Add Admin User
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-[500px]">
              <form onSubmit={handleCreateAdmin}>
                <DialogHeader>
                  <DialogTitle>Create New Admin User</DialogTitle>
                  <DialogDescription>
                    Create a new administrator account. They will be part of your school and can manage the system.
                  </DialogDescription>
                </DialogHeader>
                
                <div className="grid gap-4 py-4">
                  <div className="grid gap-2">
                    <Label htmlFor="contact_name">
                      Full Name <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id="contact_name"
                      name="contact_name"
                      value={formData.contact_name}
                      onChange={handleInputChange}
                      placeholder="Enter admin's full name"
                      required
                    />
                  </div>
                  
                  <div className="grid gap-2">
                    <Label htmlFor="email">
                      Email Address <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id="email"
                      name="email"
                      type="email"
                      value={formData.email}
                      onChange={handleInputChange}
                      placeholder="admin@school.com"
                      required
                    />
                  </div>
                  
                  <div className="grid gap-2">
                    <Label htmlFor="password">
                      Temporary Password <span className="text-red-500">*</span>
                    </Label>
                    <div className="flex gap-2">
                      <Input
                        id="password"
                        name="password"
                        type="text"
                        value={formData.password}
                        onChange={handleInputChange}
                        placeholder="Enter or generate password"
                        required
                      />
                      <Button 
                        type="button" 
                        variant="outline"
                        onClick={generatePassword}
                      >
                        Generate
                      </Button>
                    </div>
                    <p className="text-xs text-gray-500">
                      Minimum 6 characters. User will receive this password via email.
                    </p>
                  </div>
                  
                  <div className="grid gap-2">
                    <Label htmlFor="phone">Phone Number</Label>
                    <Input
                      id="phone"
                      name="phone"
                      value={formData.phone}
                      onChange={handleInputChange}
                      placeholder="+1234567890"
                    />
                  </div>
                  
                  <div className="grid gap-2">
                    <Label htmlFor="signature">Signature</Label>
                    <Input
                      id="signature"
                      name="signature"
                      value={formData.signature}
                      onChange={handleInputChange}
                      placeholder="Admin's official signature"
                    />
                  </div>
                </div>
                
                <DialogFooter>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => setShowCreateDialog(false)}
                    disabled={creating}
                  >
                    Cancel
                  </Button>
                  <Button type="submit" disabled={creating}>
                    {creating ? 'Creating...' : 'Create Admin'}
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Admin Users</CardTitle>
          <CardDescription>
            List of all administrators in your school system
          </CardDescription>
        </CardHeader>
        <CardContent>
          {admins.length === 0 ? (
            <div className="text-center py-12">
              <User className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No admin users found</p>
              <p className="text-sm text-gray-500 mt-2">
                Create your first admin user to get started
              </p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Phone</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Created</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {admins.map((admin) => (
                  <TableRow key={admin.id}>
                    <TableCell className="font-medium">
                      <div className="flex items-center gap-2">
                        <User className="h-4 w-4 text-gray-400" />
                        {admin.contact_name || admin.school_name || 'N/A'}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Mail className="h-4 w-4 text-gray-400" />
                        {admin.email}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Phone className="h-4 w-4 text-gray-400" />
                        {admin.phone || 'N/A'}
                      </div>
                    </TableCell>
                    <TableCell>
                      {admin.role === 'super_admin' || admin.is_super_admin ? (
                        <Badge className="bg-purple-100 text-purple-800 border-purple-300">
                          <Shield className="h-3 w-3 mr-1" />
                          Super Admin
                        </Badge>
                      ) : admin.role === 'principal' ? (
                        <Badge className="bg-blue-100 text-blue-800 border-blue-300">
                          Principal
                        </Badge>
                      ) : (
                        <Badge className="bg-green-100 text-green-800 border-green-300">
                          Admin
                        </Badge>
                      )}
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2 text-sm text-gray-500">
                        <Calendar className="h-4 w-4" />
                        {new Date(admin.created_at).toLocaleDateString()}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <div className="mt-8 p-6 bg-blue-50 border border-blue-200 rounded-lg">
        <h3 className="text-lg font-semibold text-blue-900 mb-2">About Super Admin</h3>
        <ul className="space-y-2 text-sm text-blue-800">
          <li className="flex items-start gap-2">
            <Shield className="h-4 w-4 mt-0.5 flex-shrink-0" />
            <span><strong>Super Admin:</strong> The first admin to register for a school. Can create additional admins for the same school.</span>
          </li>
          <li className="flex items-start gap-2">
            <User className="h-4 w-4 mt-0.5 flex-shrink-0" />
            <span><strong>Regular Admin:</strong> Created by super admin. Can manage all school operations but cannot create other admins.</span>
          </li>
          <li className="flex items-start gap-2">
            <User className="h-4 w-4 mt-0.5 flex-shrink-0" />
            <span><strong>Principal:</strong> Created by admin. Can manage school operations but cannot create admins or principals.</span>
          </li>
        </ul>
      </div>
    </div>
  );
};

export default AdminUsersManagement;
