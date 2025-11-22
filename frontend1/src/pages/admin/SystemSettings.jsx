import { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';
import axios from '@/redux/axiosConfig';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import BackButton from '@/components/BackButton';
import {
  FiSettings,
  FiSave,
  FiMail,
  FiDatabase,
  FiShield,
  FiGlobe,
  FiBell,
  FiDownload,
  FiUpload,
  FiRefreshCw,
  FiCheck,
} from 'react-icons/fi';

const GENERAL_DEFAULTS = {
  school_name: '',
  school_code: '',
  school_address: '',
  school_phone: '',
  school_email: '',
  school_website: '',
  school_logo: '',
  academic_year_start_month: 9,
  academic_year_end_month: 6,
  timezone: 'Africa/Lagos',
};

const NOTIFICATION_DEFAULTS = {
  email_enabled: true,
  sms_enabled: false,
  push_enabled: true,
  notify_attendance: true,
  notify_results: true,
  notify_fees: true,
  notify_complaints: true,
};

const EMAIL_DEFAULTS = {
  smtp_host: '',
  smtp_port: 587,
  smtp_username: '',
  smtp_password: '',
  smtp_encryption: 'tls',
  from_email: '',
  from_name: '',
};

const SECURITY_DEFAULTS = {
  force_password_change: false,
  password_min_length: 6,
  password_require_uppercase: true,
  password_require_lowercase: true,
  password_require_numbers: true,
  password_require_special: false,
  session_timeout: 30,
  max_login_attempts: 5,
  two_factor_enabled: false,
};

const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

const SystemSettings = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [activeTab, setActiveTab] = useState('general');

  const [generalSettings, setGeneralSettings] = useState(() => ({ ...GENERAL_DEFAULTS }));
  const [notificationSettings, setNotificationSettings] = useState(() => ({ ...NOTIFICATION_DEFAULTS }));
  const [emailSettings, setEmailSettings] = useState(() => ({ ...EMAIL_DEFAULTS }));
  const [securitySettings, setSecuritySettings] = useState(() => ({ ...SECURITY_DEFAULTS }));

  const [maintenanceMode, setMaintenanceMode] = useState(false);

  useEffect(() => {
    if (!currentUser?.token) return;
    fetchSettings();
  }, [currentUser?.token]);

  const fetchSettings = async () => {
    try {
      if (!currentUser?.token) {
        toast.error('Please log in to view settings');
        return;
      }
      setLoading(true);
      const response = await axios.get('/admin/settings', {
        skipAuthRedirect: true,
        headers: { Authorization: `Bearer ${currentUser.token}` },
      });

      if (response.data.success) {
        const settings = response.data.settings;
        setGeneralSettings({ ...GENERAL_DEFAULTS, ...(settings?.general || {}) });
        setNotificationSettings({ ...NOTIFICATION_DEFAULTS, ...(settings?.notifications || {}) });
        setEmailSettings({ ...EMAIL_DEFAULTS, ...(settings?.email || {}) });
        setSecuritySettings({ ...SECURITY_DEFAULTS, ...(settings?.security || {}) });
        setMaintenanceMode(Boolean(settings?.maintenance_mode));
      }
    } catch (error) {
      if (error.response?.status === 401) {
        toast.error('Your session has expired. Please log in again.');
        localStorage.removeItem('user');
      } else {
        console.error('Error fetching settings:', error);
        toast.error('Failed to load settings');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleSaveSettings = async (settingsType, data) => {
    try {
      setSaving(true);
      if (!currentUser?.token) {
        toast.error('Not authorized');
        return;
      }
      const response = await axios.put(
        '/admin/settings',
        {
          type: settingsType,
          settings: data,
        },
        {
          skipAuthRedirect: true,
          headers: { Authorization: `Bearer ${currentUser.token}` },
        }
      );

      if (response.data.success) {
        toast.success('Settings saved successfully');
        if (['general', 'system'].includes(settingsType)) {
          fetchSettings();
        }
      }
    } catch (error) {
      if (error.response?.status === 401) {
        toast.error('Your session has expired. Please log in again.');
        localStorage.removeItem('user');
      } else {
        console.error('Error saving settings:', error);
        toast.error('Failed to save settings');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleBackup = async () => {
    let toastId;
    try {
      toastId = toast.loading('Creating backup...');
      if (!currentUser?.token) {
        toast.dismiss(toastId);
        toast.error('Not authorized');
        return;
      }
      const response = await axios.post(
        '/admin/settings/backup',
        {},
        { responseType: 'blob', skipAuthRedirect: true, headers: { Authorization: `Bearer ${currentUser.token}` } }
      );

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `backup-${new Date().toISOString()}.sql`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.dismiss(toastId);
      toast.success('Backup created successfully');
    } catch (error) {
      if (toastId) toast.dismiss(toastId);
      if (error.response?.status === 401) {
        toast.error('Your session has expired. Please log in again.');
        localStorage.removeItem('user');
      } else {
        console.error('Error creating backup:', error);
        toast.error('Failed to create backup');
      }
    }
  };

  const handleClearCache = async () => {
    try {
      if (!currentUser?.token) {
        toast.error('Not authorized');
        return;
      }
      const response = await axios.post(
        '/admin/cache/clear',
        {},
        { skipAuthRedirect: true, headers: { Authorization: `Bearer ${currentUser.token}` } }
      );

      if (response.data.success) {
        toast.success('Cache cleared successfully');
      } else {
        toast.error(response.data.message || 'Failed to clear cache');
      }
    } catch (error) {
      if (error.response?.status === 401) {
        toast.error('Your session has expired. Please log in again.');
        localStorage.removeItem('user');
      } else {
        console.error('Error clearing cache:', error);
        toast.error('Failed to clear cache');
      }
    }
  };

  return (
    <div className="p-6 space-y-6">
      {/* Back Button */}
      <BackButton to="/Admin/dashboard" label="Back to Dashboard" />

      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">System Settings</h1>
          <p className="text-gray-600 mt-1">Configure your school management system</p>
        </div>
        <Button variant="outline" onClick={fetchSettings} disabled={loading}>
          <FiRefreshCw className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
          Refresh
        </Button>
      </div>

      {/* Settings Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-5">
          <TabsTrigger value="general">
            <FiGlobe className="mr-2 h-4 w-4" />
            General
          </TabsTrigger>
          <TabsTrigger value="notifications">
            <FiBell className="mr-2 h-4 w-4" />
            Notifications
          </TabsTrigger>
          <TabsTrigger value="email">
            <FiMail className="mr-2 h-4 w-4" />
            Email
          </TabsTrigger>
          <TabsTrigger value="security">
            <FiShield className="mr-2 h-4 w-4" />
            Security
          </TabsTrigger>
          <TabsTrigger value="system">
            <FiDatabase className="mr-2 h-4 w-4" />
            System
          </TabsTrigger>
        </TabsList>

        {/* General Settings */}
        <TabsContent value="general">
          <Card>
            <CardHeader>
              <CardTitle>General Settings</CardTitle>
              <CardDescription>Configure basic school information</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="school_name">School Name *</Label>
                  <Input
                    id="school_name"
                    value={generalSettings.school_name}
                    onChange={(e) =>
                      setGeneralSettings({ ...generalSettings, school_name: e.target.value })
                    }
                    placeholder="Enter school name"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="school_code">School Code</Label>
                  <Input
                    id="school_code"
                    value={generalSettings.school_code}
                    onChange={(e) =>
                      setGeneralSettings({ ...generalSettings, school_code: e.target.value })
                    }
                    placeholder="e.g., SCH001"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="school_address">School Address</Label>
                <Textarea
                  id="school_address"
                  value={generalSettings.school_address}
                  onChange={(e) =>
                    setGeneralSettings({ ...generalSettings, school_address: e.target.value })
                  }
                  placeholder="Enter school address"
                  rows={3}
                />
              </div>

              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="school_phone">Phone</Label>
                  <Input
                    id="school_phone"
                    value={generalSettings.school_phone}
                    onChange={(e) =>
                      setGeneralSettings({ ...generalSettings, school_phone: e.target.value })
                    }
                    placeholder="+234 xxx xxx xxxx"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="school_email">Email</Label>
                  <Input
                    id="school_email"
                    type="email"
                    value={generalSettings.school_email}
                    onChange={(e) =>
                      setGeneralSettings({ ...generalSettings, school_email: e.target.value })
                    }
                    placeholder="info@school.com"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="school_website">Website</Label>
                  <Input
                    id="school_website"
                    value={generalSettings.school_website}
                    onChange={(e) =>
                      setGeneralSettings({ ...generalSettings, school_website: e.target.value })
                    }
                    placeholder="www.school.com"
                  />
                </div>
              </div>

              <Separator />

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Academic Year Start Month</Label>
                  <Input
                    type="number"
                    min="1"
                    max="12"
                    value={generalSettings.academic_year_start_month}
                    onChange={(e) =>
                      setGeneralSettings({
                        ...generalSettings,
                        academic_year_start_month: parseInt(e.target.value),
                      })
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Academic Year End Month</Label>
                  <Input
                    type="number"
                    min="1"
                    max="12"
                    value={generalSettings.academic_year_end_month}
                    onChange={(e) =>
                      setGeneralSettings({
                        ...generalSettings,
                        academic_year_end_month: parseInt(e.target.value),
                      })
                    }
                  />
                </div>
              </div>

              <div className="flex justify-end pt-4">
                <Button
                  onClick={() => handleSaveSettings('general', generalSettings)}
                  disabled={saving}
                >
                  <FiSave className="mr-2 h-4 w-4" />
                  Save Changes
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Notification Settings */}
        <TabsContent value="notifications">
          <Card>
            <CardHeader>
              <CardTitle>Notification Settings</CardTitle>
              <CardDescription>Configure notification preferences</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <Label htmlFor="email_enabled">Email Notifications</Label>
                    <p className="text-sm text-gray-600">Send notifications via email</p>
                  </div>
                  <Switch
                    id="email_enabled"
                    checked={notificationSettings.email_enabled}
                    onCheckedChange={(checked) =>
                      setNotificationSettings({ ...notificationSettings, email_enabled: checked })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div>
                    <Label htmlFor="sms_enabled">SMS Notifications</Label>
                    <p className="text-sm text-gray-600">Send notifications via SMS</p>
                  </div>
                  <Switch
                    id="sms_enabled"
                    checked={notificationSettings.sms_enabled}
                    onCheckedChange={(checked) =>
                      setNotificationSettings({ ...notificationSettings, sms_enabled: checked })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div>
                    <Label htmlFor="push_enabled">Push Notifications</Label>
                    <p className="text-sm text-gray-600">Send browser push notifications</p>
                  </div>
                  <Switch
                    id="push_enabled"
                    checked={notificationSettings.push_enabled}
                    onCheckedChange={(checked) =>
                      setNotificationSettings({ ...notificationSettings, push_enabled: checked })
                    }
                  />
                </div>
              </div>

              <Separator />

              <div className="space-y-4">
                <h3 className="text-sm font-semibold">Notification Types</h3>

                <div className="flex items-center justify-between">
                  <Label htmlFor="notify_attendance">Attendance Notifications</Label>
                  <Switch
                    id="notify_attendance"
                    checked={notificationSettings.notify_attendance}
                    onCheckedChange={(checked) =>
                      setNotificationSettings({ ...notificationSettings, notify_attendance: checked })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <Label htmlFor="notify_results">Results Notifications</Label>
                  <Switch
                    id="notify_results"
                    checked={notificationSettings.notify_results}
                    onCheckedChange={(checked) =>
                      setNotificationSettings({ ...notificationSettings, notify_results: checked })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <Label htmlFor="notify_fees">Fees Notifications</Label>
                  <Switch
                    id="notify_fees"
                    checked={notificationSettings.notify_fees}
                    onCheckedChange={(checked) =>
                      setNotificationSettings({ ...notificationSettings, notify_fees: checked })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <Label htmlFor="notify_complaints">Complaint Notifications</Label>
                  <Switch
                    id="notify_complaints"
                    checked={notificationSettings.notify_complaints}
                    onCheckedChange={(checked) =>
                      setNotificationSettings({ ...notificationSettings, notify_complaints: checked })
                    }
                  />
                </div>
              </div>

              <div className="flex justify-end pt-4">
                <Button
                  onClick={() => handleSaveSettings('notifications', notificationSettings)}
                  disabled={saving}
                >
                  <FiSave className="mr-2 h-4 w-4" />
                  Save Changes
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Email Settings */}
        <TabsContent value="email">
          <Card>
            <CardHeader>
              <CardTitle>Email Configuration</CardTitle>
              <CardDescription>Configure SMTP settings for sending emails</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="smtp_host">SMTP Host *</Label>
                  <Input
                    id="smtp_host"
                    value={emailSettings.smtp_host}
                    onChange={(e) => setEmailSettings({ ...emailSettings, smtp_host: e.target.value })}
                    placeholder="smtp.gmail.com"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="smtp_port">SMTP Port *</Label>
                  <Input
                    id="smtp_port"
                    type="number"
                    value={emailSettings.smtp_port}
                    onChange={(e) =>
                      setEmailSettings({ ...emailSettings, smtp_port: parseInt(e.target.value) })
                    }
                    placeholder="587"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="smtp_username">SMTP Username *</Label>
                  <Input
                    id="smtp_username"
                    value={emailSettings.smtp_username}
                    onChange={(e) =>
                      setEmailSettings({ ...emailSettings, smtp_username: e.target.value })
                    }
                    placeholder="your@email.com"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="smtp_password">SMTP Password *</Label>
                  <Input
                    id="smtp_password"
                    type="password"
                    value={emailSettings.smtp_password}
                    onChange={(e) =>
                      setEmailSettings({ ...emailSettings, smtp_password: e.target.value })
                    }
                    placeholder="••••••••"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="from_email">From Email *</Label>
                  <Input
                    id="from_email"
                    type="email"
                    value={emailSettings.from_email}
                    onChange={(e) => setEmailSettings({ ...emailSettings, from_email: e.target.value })}
                    placeholder="noreply@school.com"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="from_name">From Name *</Label>
                  <Input
                    id="from_name"
                    value={emailSettings.from_name}
                    onChange={(e) => setEmailSettings({ ...emailSettings, from_name: e.target.value })}
                    placeholder="School Name"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-4">
                <Button variant="outline">
                  <FiMail className="mr-2 h-4 w-4" />
                  Test Email
                </Button>
                <Button
                  onClick={() => handleSaveSettings('email', emailSettings)}
                  disabled={saving}
                >
                  <FiSave className="mr-2 h-4 w-4" />
                  Save Changes
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Security Settings */}
        <TabsContent value="security">
          <Card>
            <CardHeader>
              <CardTitle>Security Settings</CardTitle>
              <CardDescription>Configure system security and password policies</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-4">
                <h3 className="text-sm font-semibold">Password Policy</h3>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="password_min_length">Minimum Password Length</Label>
                    <Input
                      id="password_min_length"
                      type="number"
                      min="6"
                      max="32"
                      value={securitySettings.password_min_length}
                      onChange={(e) =>
                        setSecuritySettings({
                          ...securitySettings,
                          password_min_length: parseInt(e.target.value),
                        })
                      }
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="max_login_attempts">Max Login Attempts</Label>
                    <Input
                      id="max_login_attempts"
                      type="number"
                      min="3"
                      max="10"
                      value={securitySettings.max_login_attempts}
                      onChange={(e) =>
                        setSecuritySettings({
                          ...securitySettings,
                          max_login_attempts: parseInt(e.target.value),
                        })
                      }
                    />
                  </div>
                </div>

                <div className="flex items-center justify-between">
                  <Label htmlFor="password_require_uppercase">Require Uppercase Letters</Label>
                  <Switch
                    id="password_require_uppercase"
                    checked={securitySettings.password_require_uppercase}
                    onCheckedChange={(checked) =>
                      setSecuritySettings({ ...securitySettings, password_require_uppercase: checked })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <Label htmlFor="password_require_lowercase">Require Lowercase Letters</Label>
                  <Switch
                    id="password_require_lowercase"
                    checked={securitySettings.password_require_lowercase}
                    onCheckedChange={(checked) =>
                      setSecuritySettings({ ...securitySettings, password_require_lowercase: checked })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <Label htmlFor="password_require_numbers">Require Numbers</Label>
                  <Switch
                    id="password_require_numbers"
                    checked={securitySettings.password_require_numbers}
                    onCheckedChange={(checked) =>
                      setSecuritySettings({ ...securitySettings, password_require_numbers: checked })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <Label htmlFor="password_require_special">Require Special Characters</Label>
                  <Switch
                    id="password_require_special"
                    checked={securitySettings.password_require_special}
                    onCheckedChange={(checked) =>
                      setSecuritySettings({ ...securitySettings, password_require_special: checked })
                    }
                  />
                </div>
              </div>

              <Separator />

              <div className="space-y-4">
                <h3 className="text-sm font-semibold">Session Management</h3>

                <div className="space-y-2">
                  <Label htmlFor="session_timeout">Session Timeout (minutes)</Label>
                  <Input
                    id="session_timeout"
                    type="number"
                    min="5"
                    max="120"
                    value={securitySettings.session_timeout}
                    onChange={(e) =>
                      setSecuritySettings({
                        ...securitySettings,
                        session_timeout: parseInt(e.target.value),
                      })
                    }
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div>
                    <Label htmlFor="two_factor_enabled">Two-Factor Authentication</Label>
                    <p className="text-sm text-gray-600">Enable 2FA for all users</p>
                  </div>
                  <Switch
                    id="two_factor_enabled"
                    checked={securitySettings.two_factor_enabled}
                    onCheckedChange={(checked) =>
                      setSecuritySettings({ ...securitySettings, two_factor_enabled: checked })
                    }
                  />
                </div>
              </div>

              <div className="flex justify-end pt-4">
                <Button
                  onClick={() => handleSaveSettings('security', securitySettings)}
                  disabled={saving}
                >
                  <FiSave className="mr-2 h-4 w-4" />
                  Save Changes
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* System Management */}
        <TabsContent value="system">
          <div className="space-y-4">
            {/* Maintenance Mode */}
            <Card>
              <CardHeader>
                <CardTitle>Maintenance Mode</CardTitle>
                <CardDescription>
                  Enable maintenance mode to prevent users from accessing the system
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between">
                  <div>
                    <Label htmlFor="maintenance_mode">Maintenance Mode</Label>
                    <p className="text-sm text-gray-600">
                      {maintenanceMode ? 'System is in maintenance mode' : 'System is operational'}
                    </p>
                  </div>
                  <Switch
                    id="maintenance_mode"
                    checked={maintenanceMode}
                    onCheckedChange={setMaintenanceMode}
                  />
                </div>
                <div className="flex justify-end mt-4">
                  <Button
                    onClick={() => handleSaveSettings('system', { maintenance_mode: maintenanceMode })}
                    disabled={saving}
                  >
                    <FiSave className="mr-2 h-4 w-4" />
                    Save Changes
                  </Button>
                </div>
              </CardContent>
            </Card>

            {/* Backup & Restore */}
            <Card>
              <CardHeader>
                <CardTitle>Backup & Restore</CardTitle>
                <CardDescription>Create backups and restore system data</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <Button onClick={handleBackup}>
                    <FiDownload className="mr-2 h-4 w-4" />
                    Create Backup
                  </Button>
                  <Button variant="outline">
                    <FiUpload className="mr-2 h-4 w-4" />
                    Restore Backup
                  </Button>
                </div>

                <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                  <p className="text-sm text-yellow-800">
                    <strong>⚠️ Important:</strong> Always create a backup before making major changes
                    or updates to the system.
                  </p>
                </div>
              </CardContent>
            </Card>

            {/* Cache Management */}
            <Card>
              <CardHeader>
                <CardTitle>Cache Management</CardTitle>
                <CardDescription>Clear system cache to improve performance</CardDescription>
              </CardHeader>
              <CardContent>
                <Button onClick={handleClearCache}>
                  <FiRefreshCw className="mr-2 h-4 w-4" />
                  Clear Cache
                </Button>
              </CardContent>
            </Card>

            {/* System Information */}
            <Card>
              <CardHeader>
                <CardTitle>System Information</CardTitle>
                <CardDescription>View system details and version information</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <p className="font-semibold text-gray-600">System Version</p>
                    <p className="text-gray-900">1.0.0</p>
                  </div>
                  <div>
                    <p className="font-semibold text-gray-600">Database Version</p>
                    <p className="text-gray-900">MySQL 8.0</p>
                  </div>
                  <div>
                    <p className="font-semibold text-gray-600">PHP Version</p>
                    <p className="text-gray-900">8.1+</p>
                  </div>
                  <div>
                    <p className="font-semibold text-gray-600">Server Time</p>
                    <p className="text-gray-900">{new Date().toLocaleString()}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default SystemSettings;
