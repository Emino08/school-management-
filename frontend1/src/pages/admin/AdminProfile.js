import { useEffect, useState } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Pencil, Save, X, Info } from 'lucide-react';
import { authSuccess } from '@/redux/userRelated/userSlice';
import { useNavigate } from 'react-router-dom';

const AdminProfile = () => {
  const { currentUser } = useSelector((state) => state.user);
  const dispatch = useDispatch();
  const navigate = useNavigate();
  
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [editing, setEditing] = useState(false);
  
  // Personal info only
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  useEffect(() => {
    if (currentUser) {
      setName(currentUser?.name || '');
      setEmail(currentUser?.email || '');
      setPhone(currentUser?.phone || '');
    }
  }, [currentUser]);

  const handleSave = async () => {
    if (password && password !== confirmPassword) {
      toast.error('Passwords do not match');
      return;
    }
    
    if (!name.trim()) {
      toast.error('Name is required');
      return;
    }

    setSaving(true);
    try {
      const payload = {
        contact_name: name,
        phone,
      };
      
      if (password && password.trim() !== '') {
        payload.password = password;
      }
      
      await axios.put(`/admin/profile`, payload, {
        headers: { Authorization: `Bearer ${currentUser.token}` },
      });
      
      // Update Redux store with new data
      dispatch(authSuccess({
        ...currentUser,
        name,
        phone,
      }));
      
      toast.success('Profile updated successfully');
      setEditing(false);
      setPassword('');
      setConfirmPassword('');
    } catch (error) {
      console.error('Failed to update profile:', error);
      toast.error(error.response?.data?.message || 'Failed to update profile');
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    setName(currentUser?.name || '');
    setEmail(currentUser?.email || '');
    setPhone(currentUser?.phone || '');
    setPassword('');
    setConfirmPassword('');
    setEditing(false);
  };

  return (
    <div className="container mx-auto p-6 max-w-3xl space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">My Profile</h1>
          <p className="text-gray-600 mt-1">Manage your personal information</p>
        </div>
        {!editing && (
          <Button onClick={() => setEditing(true)}>
            <Pencil className="w-4 h-4 mr-2" />
            Edit Profile
          </Button>
        )}
      </div>

      {/* Info Alert */}
      <Alert className="bg-blue-50 border-blue-200">
        <Info className="h-4 w-4 text-blue-600" />
        <AlertDescription className="text-blue-800">
          To edit school information (name, address, etc.), please go to{' '}
          <button 
            onClick={() => navigate('/Admin/settings')}
            className="font-semibold underline hover:text-blue-900"
          >
            System Settings → General
          </button>
        </AlertDescription>
      </Alert>

      {/* Personal Information Card */}
      <Card>
        <CardHeader>
          <CardTitle>Personal Information</CardTitle>
          <CardDescription>
            Your account details and contact information
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Full Name */}
            <div className="space-y-2">
              <Label htmlFor="name">Full Name *</Label>
              {editing ? (
                <Input 
                  id="name" 
                  value={name} 
                  onChange={(e) => setName(e.target.value)}
                  required
                  placeholder="Enter your full name"
                />
              ) : (
                <div className="text-base p-3 bg-gray-50 rounded-md border">
                  {name || '—'}
                </div>
              )}
            </div>
            
            {/* Email (Read-only) */}
            <div className="space-y-2">
              <Label htmlFor="email">Email Address</Label>
              <div className="text-base p-3 bg-gray-100 rounded-md border border-gray-300 text-gray-600">
                {email || '—'}
              </div>
              <p className="text-xs text-gray-500">Email cannot be changed</p>
            </div>
            
            {/* Phone */}
            <div className="space-y-2">
              <Label htmlFor="phone">Phone Number</Label>
              {editing ? (
                <Input 
                  id="phone" 
                  value={phone} 
                  onChange={(e) => setPhone(e.target.value)}
                  placeholder="+1234567890"
                  type="tel"
                />
              ) : (
                <div className="text-base p-3 bg-gray-50 rounded-md border">
                  {phone || 'Not provided'}
                </div>
              )}
            </div>
            
            {/* Role (Read-only) */}
            <div className="space-y-2">
              <Label htmlFor="role">Role</Label>
              <div className="text-base p-3 bg-gray-100 rounded-md border border-gray-300 text-gray-600">
                {currentUser?.role || 'Admin'}
              </div>
            </div>
          </div>
          
          {/* Password Change Section */}
          {editing && (
            <>
              <div className="border-t pt-6">
                <h3 className="text-lg font-semibold mb-4">Change Password</h3>
                <p className="text-sm text-gray-600 mb-4">
                  Leave blank if you don't want to change your password
                </p>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <Label htmlFor="password">New Password</Label>
                    <Input 
                      id="password" 
                      type="password" 
                      value={password} 
                      onChange={(e) => setPassword(e.target.value)}
                      placeholder="Enter new password"
                    />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="confirmPassword">Confirm New Password</Label>
                    <Input 
                      id="confirmPassword" 
                      type="password" 
                      value={confirmPassword} 
                      onChange={(e) => setConfirmPassword(e.target.value)}
                      placeholder="Confirm new password"
                    />
                  </div>
                </div>
              </div>
            </>
          )}
        </CardContent>
      </Card>

      {/* Action Buttons */}
      {editing && (
        <div className="flex justify-end gap-3">
          <Button 
            type="button" 
            variant="outline" 
            onClick={handleCancel}
            disabled={saving}
          >
            <X className="w-4 h-4 mr-2" />
            Cancel
          </Button>
          <Button 
            onClick={handleSave}
            disabled={saving}
          >
            <Save className="w-4 h-4 mr-2" />
            {saving ? 'Saving...' : 'Save Changes'}
          </Button>
        </div>
      )}
    </div>
  );
};

export default AdminProfile;
