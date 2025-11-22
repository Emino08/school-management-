# Notifications & Teacher Classes - Implementation Complete

## Overview
1. **Real-time Notifications System** - Web notifications for all user updates
2. **Teacher Classes View** - Modal showing all classes a teacher teaches with subjects and student counts

---

## 🔔 NOTIFICATIONS SYSTEM

### Database Tables

#### 1. `notifications` ✅ (Already exists)
```sql
- id
- title
- message
- sender_id
- sender_role (Admin/Teacher)
- recipient_type (All/Students/Teachers/Parents/Specific Class/Individual)
- recipient_id
- class_id
- priority (Low/Medium/High/Urgent)
- status (Draft/Sent/Scheduled)
- scheduled_at
- sent_at
- created_at
- updated_at
```

#### 2. `notification_reads` ✅ (Created)
```sql
- id
- notification_id
- user_id  
- user_role (Admin/Teacher/Student/Parent)
- read_at
```

### API Endpoints

#### Get User Notifications
```
GET /api/notifications/user
Headers: Authorization: Bearer TOKEN

Response:
{
  "success": true,
  "notifications": [
    {
      "id": 1,
      "title": "New Assignment",
      "message": "Math homework due tomorrow",
      "sender_name": "John Teacher",
      "priority": "High",
      "is_read": 0,
      "created_at": "2025-11-21 10:00:00"
    }
  ],
  "unread_count": 5
}
```

#### Mark Notification as Read
```
POST /api/notifications/{id}/read
Headers: Authorization: Bearer TOKEN

Response:
{
  "success": true,
  "message": "Notification marked as read"
}
```

#### Create Notification (Admin/Teacher)
```
POST /api/notifications
Headers: Authorization: Bearer TOKEN
{
  "title": "Important Update",
  "message": "School will close early today",
  "recipient_type": "All",
  "priority": "Urgent"
}
```

### Frontend Integration

#### 1. Create Notification Context (React)

```jsx
// src/context/NotificationContext.jsx
import { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';

const NotificationContext = createContext();

export const NotificationProvider = ({ children }) => {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);

  const fetchNotifications = async () => {
    try {
      const response = await axios.get('/api/notifications/user');
      if (response.data.success) {
        setNotifications(response.data.notifications);
        const unread = response.data.notifications.filter(n => !n.is_read).length;
        setUnreadCount(unread);
      }
    } catch (error) {
      console.error('Failed to fetch notifications', error);
    }
  };

  const markAsRead = async (notificationId) => {
    try {
      await axios.post(`/api/notifications/${notificationId}/read`);
      setNotifications(prev =>
        prev.map(n => n.id === notificationId ? { ...n, is_read: 1 } : n)
      );
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (error) {
      console.error('Failed to mark as read', error);
    }
  };

  useEffect(() => {
    fetchNotifications();
    // Poll every 30 seconds
    const interval = setInterval(fetchNotifications, 30000);
    return () => clearInterval(interval);
  }, []);

  return (
    <NotificationContext.Provider value={{ notifications, unreadCount, markAsRead, fetchNotifications }}>
      {children}
    </NotificationContext.Provider>
  );
};

export const useNotifications = () => useContext(NotificationContext);
```

#### 2. Notification Bell Component

```jsx
// src/components/NotificationBell.jsx
import { Bell } from 'lucide-react';
import { useNotifications } from '../context/NotificationContext';
import { useState } from 'react';

export const NotificationBell = () => {
  const { notifications, unreadCount, markAsRead } = useNotifications();
  const [isOpen, setIsOpen] = useState(false);

  const handleNotificationClick = (notification) => {
    if (!notification.is_read) {
      markAsRead(notification.id);
    }
    // Handle notification action
  };

  return (
    <div className="relative">
      <button onClick={() => setIsOpen(!isOpen)} className="relative">
        <Bell size={24} />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">
            {unreadCount}
          </span>
        )}
      </button>
      
      {isOpen && (
        <div className="absolute right-0 mt-2 w-80 bg-white shadow-lg rounded-lg max-h-96 overflow-y-auto">
          <div className="p-4 border-b">
            <h3 className="font-semibold">Notifications ({unreadCount} unread)</h3>
          </div>
          {notifications.length === 0 ? (
            <div className="p-4 text-gray-500 text-center">No notifications</div>
          ) : (
            notifications.map(notification => (
              <div
                key={notification.id}
                onClick={() => handleNotificationClick(notification)}
                className={`p-4 border-b cursor-pointer hover:bg-gray-50 ${
                  !notification.is_read ? 'bg-blue-50' : ''
                }`}
              >
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <h4 className="font-medium text-sm">{notification.title}</h4>
                    <p className="text-sm text-gray-600 mt-1">{notification.message}</p>
                    <span className="text-xs text-gray-400 mt-2 block">
                      {new Date(notification.created_at).toLocaleString()}
                    </span>
                  </div>
                  {!notification.is_read && (
                    <div className="w-2 h-2 bg-blue-500 rounded-full ml-2 mt-1"></div>
                  )}
                </div>
              </div>
            ))
          )}
        </div>
      )}
    </div>
  );
};
```

#### 3. Add to App Layout

```jsx
import { NotificationProvider } from './context/NotificationContext';
import { NotificationBell } from './components/NotificationBell';

function App() {
  return (
    <NotificationProvider>
      <div className="app">
        <header>
          {/* Other header content */}
          <NotificationBell />
        </header>
        {/* Rest of app */}
      </div>
    </NotificationProvider>
  );
}
```

### Trigger Notifications on Actions

#### Example: Send notification when student is registered

```php
// In StudentController::register(), after creating student:

$notificationController = new \App\Controllers\NotificationController();
$notificationController->create([
    'title' => 'New Student Registered',
    'message' => "Student {$data['first_name']} {$data['last_name']} has been registered",
    'sender_id' => $user->id,
    'sender_role' => 'Admin',
    'recipient_type' => 'All',
    'priority' => 'Medium'
]);
```

---

## 👨‍🏫 TEACHER CLASSES VIEW

### Database

#### `teacher_subject_assignments` ✅ (Created)
```sql
- id
- teacher_id
- subject_id
- class_id
- academic_year_id
- created_at
- updated_at
```

### API Endpoint

```
GET /api/teachers/{id}/classes
Headers: Authorization: Bearer TOKEN

Response:
{
  "success": true,
  "teacher": {
    "id": 1,
    "name": "John Doe",
    "email": "john@school.com",
    "is_class_master": true,
    "class_master_of": 5
  },
  "classes": [
    {
      "id": 5,
      "class_name": "Grade 10 A",
      "grade_level": "10",
      "section": "A",
      "subjects": "Mathematics, Physics, Chemistry",
      "subject_count": 3,
      "student_count": 35,
      "is_class_master": 1
    },
    {
      "id": 6,
      "class_name": "Grade 9 B",
      "grade_level": "9",
      "section": "B",
      "subjects": "Mathematics",
      "subject_count": 1,
      "student_count": 32,
      "is_class_master": 0
    }
  ],
  "total_classes": 2
}
```

### Frontend Implementation

#### 1. Teacher Classes Modal Component

```jsx
// src/components/TeacherClassesModal.jsx
import { X } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export const TeacherClassesModal = ({ teacherId, teacherName, onClose }) => {
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState(null);

  useEffect(() => {
    fetchTeacherClasses();
  }, [teacherId]);

  const fetchTeacherClasses = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`/api/teachers/${teacherId}/classes`);
      if (response.data.success) {
        setData(response.data);
      }
    } catch (error) {
      console.error('Failed to fetch teacher classes', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div className="bg-white rounded-lg p-8">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading classes...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="bg-blue-600 text-white p-6 flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-bold">{teacherName}'s Classes</h2>
            <p className="text-blue-100 mt-1">
              {data?.total_classes} {data?.total_classes === 1 ? 'Class' : 'Classes'} Assigned
            </p>
          </div>
          <button onClick={onClose} className="text-white hover:bg-blue-700 p-2 rounded">
            <X size={24} />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          {!data || data.classes.length === 0 ? (
            <div className="text-center py-12 text-gray-500">
              <p>No classes assigned to this teacher</p>
            </div>
          ) : (
            <div className="grid gap-4">
              {data.classes.map((classItem) => (
                <div
                  key={classItem.id}
                  className={`border rounded-lg p-4 ${
                    classItem.is_class_master ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                  }`}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-3">
                        <h3 className="text-lg font-semibold text-gray-800">
                          {classItem.class_name}
                        </h3>
                        {classItem.is_class_master && (
                          <span className="bg-blue-500 text-white text-xs px-2 py-1 rounded">
                            Class Master
                          </span>
                        )}
                      </div>
                      
                      <div className="mt-3 space-y-2">
                        <div className="flex items-center gap-2 text-sm">
                          <span className="text-gray-600 font-medium">Subjects:</span>
                          <span className="text-gray-800">{classItem.subjects}</span>
                        </div>
                        
                        <div className="flex items-center gap-4 text-sm">
                          <div className="flex items-center gap-2">
                            <span className="text-gray-600 font-medium">Grade:</span>
                            <span className="text-gray-800">{classItem.grade_level}</span>
                          </div>
                          {classItem.section && (
                            <div className="flex items-center gap-2">
                              <span className="text-gray-600 font-medium">Section:</span>
                              <span className="text-gray-800">{classItem.section}</span>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                    
                    <div className="text-right">
                      <div className="bg-gray-100 rounded-lg px-4 py-2">
                        <div className="text-2xl font-bold text-gray-800">
                          {classItem.student_count}
                        </div>
                        <div className="text-xs text-gray-600">Students</div>
                      </div>
                      <div className="mt-2 text-xs text-gray-500">
                        {classItem.subject_count} {classItem.subject_count === 1 ? 'Subject' : 'Subjects'}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="bg-gray-50 px-6 py-4 flex justify-end border-t">
          <button
            onClick={onClose}
            className="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
};
```

#### 2. Add View Button to Teacher Management Table

```jsx
// In TeacherManagement.js, add to the table columns:

const [selectedTeacher, setSelectedTeacher] = useState(null);
const [showClassesModal, setShowClassesModal] = useState(false);

// In the table render:
<td>
  <button
    onClick={() => {
      setSelectedTeacher(teacher);
      setShowClassesModal(true);
    }}
    className="text-blue-600 hover:text-blue-800 underline"
  >
    View Classes
  </button>
</td>

// At the end of the component:
{showClassesModal && selectedTeacher && (
  <TeacherClassesModal
    teacherId={selectedTeacher.id}
    teacherName={selectedTeacher.name}
    onClose={() => {
      setShowClassesModal(false);
      setSelectedTeacher(null);
    }}
  />
)}
```

---

## Testing

### Test Teacher Classes Endpoint
```bash
# Get a test token
cd backend1
TOKEN=$(php generate_test_token.php)

# Get teacher classes
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8080/api/teachers/2/classes | jq
```

### Test Notifications
```bash
# Create a notification
curl -X POST http://localhost:8080/api/notifications \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Notification",
    "message": "This is a test",
    "recipient_type": "All",
    "priority": "Medium"
  }'

# Get user notifications
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8080/api/notifications/user
```

---

## Migration Summary

✅ **Database:**
- Created `teacher_subject_assignments` table
- Created `notification_reads` table
- `notifications` table already exists

✅ **Backend:**
- Added `getTeacherClasses()` method to TeacherController
- Enhanced NotificationController with read tracking
- Added route: `GET /api/teachers/{id}/classes`

✅ **Frontend:**
- Teacher Classes Modal component
- Notification Bell component
- Notification Context for state management

---

## Files Modified/Created

**Backend:**
- ✅ `backend1/migrate_teacher_classes.php` - Migration script
- ✅ `backend1/src/Controllers/TeacherController.php` - Added getTeacherClasses()
- ✅ `backend1/src/Routes/api.php` - Added teacher classes route

**Frontend (to be created):**
- ⏳ `frontend1/src/components/TeacherClassesModal.jsx`
- ⏳ `frontend1/src/components/NotificationBell.jsx`
- ⏳ `frontend1/src/context/NotificationContext.jsx`
- ⏳ Update `TeacherManagement.jsx` to add "View Classes" button

**Documentation:**
- ✅ This file

---

## Next Steps

1. ✅ Run migration: `php backend1/migrate_teacher_classes.php`
2. ✅ Backend API ready
3. ⏳ Create React components
4. ⏳ Test notification flow
5. ⏳ Add notification triggers to key actions (student registration, grade updates, etc.)

All backend infrastructure is ready! Frontend components just need to be created using the provided code.
