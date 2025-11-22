import { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

const AdminProfile = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [schoolSettings, setSchoolSettings] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetchSettings = async () => {
      if (!currentUser?.token) return;
      setLoading(true);
      try {
        const res = await axios.get('/admin/settings', {
          skipAuthRedirect: true,
          headers: currentUser?.token ? { Authorization: `Bearer ${currentUser.token}` } : {},
        });
        if (res.data?.success) {
          setSchoolSettings(res.data.settings?.general || null);
        } else if (res.data?.message) {
          toast.error(res.data.message || 'Failed to load school profile');
        }
      } catch (error) {
        if (error.response?.status === 401) {
          toast.error('Your session has expired. Please log in again.');
          localStorage.removeItem('user');
        } else {
          toast.error('Failed to load school profile');
        }
      } finally {
        setLoading(false);
      }
    };
    fetchSettings();
  }, [currentUser?.token]);

  const general = schoolSettings || {};

  return (
    <div className="container mx-auto p-6 max-w-3xl space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl">Account Information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2">
          <p className="text-base">
            <span className="font-semibold">Name:</span> {currentUser?.name || '—'}
          </p>
          <p className="text-base">
            <span className="font-semibold">Email:</span> {currentUser?.email || '—'}
          </p>
          <p className="text-base">
            <span className="font-semibold">Role:</span> {currentUser?.role || 'Admin'}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-2xl">School Profile</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {loading ? (
            <p className="text-sm text-gray-500">Loading school information...</p>
          ) : (
            <>
              <div className="space-y-1">
                <p className="text-base">
                  <span className="font-semibold">School Name:</span>{' '}
                  {general.school_name || currentUser?.schoolName || '—'}
                </p>
                <p className="text-base">
                  <span className="font-semibold">School Code:</span>{' '}
                  {general.school_code || '—'}
                </p>
                <p className="text-base">
                  <span className="font-semibold">Website:</span>{' '}
                  {general.school_website || '—'}
                </p>
              </div>

              <Separator />

              <div className="space-y-1">
                <p className="text-base">
                  <span className="font-semibold">Address:</span>{' '}
                  {general.school_address || 'Not provided'}
                </p>
                <p className="text-base">
                  <span className="font-semibold">Phone:</span>{' '}
                  {general.school_phone || 'Not provided'}
                </p>
                <p className="text-base">
                  <span className="font-semibold">Contact Email:</span>{' '}
                  {general.school_email || currentUser?.email || 'Not provided'}
                </p>
              </div>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminProfile;
