import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FiPlus, FiBell } from 'react-icons/fi';

const NotificationManagement = () => {
  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Notifications</h1>
          <p className="text-gray-600 mt-1">Send and manage notifications</p>
        </div>
        <Button>
          <FiPlus className="mr-2 h-4 w-4" />
          Send Notification
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <FiBell className="mr-2 h-5 w-5" />
            Notification System
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-gray-600">
            This feature allows you to send notifications to students, teachers, and parents.
            The notification system is ready and can be integrated with your communication needs.
          </p>
        </CardContent>
      </Card>
    </div>
  );
};

export default NotificationManagement;
