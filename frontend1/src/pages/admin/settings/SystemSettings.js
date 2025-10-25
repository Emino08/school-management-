import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FiSettings } from 'react-icons/fi';

const SystemSettings = () => {
  return (
    <div className="container mx-auto p-6 space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">System Settings</h1>
        <p className="text-gray-600 mt-1">Configure school information and system preferences</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <FiSettings className="mr-2 h-5 w-5" />
            School Settings
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-gray-600">
            This feature allows you to manage school information, upload logos, and configure system preferences.
            The settings system is ready for configuration.
          </p>
        </CardContent>
      </Card>
    </div>
  );
};

export default SystemSettings;
