import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  MdSchool,
  MdPeople,
  MdClass,
  MdBook,
  MdAttachMoney,
  MdCalendarToday,
  MdAssignment,
  MdNotifications,
  MdChevronRight,
  MdCheckCircle,
  MdInfo,
  MdWarning
} from 'react-icons/md';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";

const SystemGuide = () => {
  const [openSections, setOpenSections] = useState({});

  const toggleSection = (section) => {
    setOpenSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const steps = [
    {
      id: 'academic-year',
      title: '1. Set Up Academic Year',
      icon: MdCalendarToday,
      color: 'bg-purple-600',
      description: 'Define the school session period',
      details: [
        'Navigate to Academic Year Management',
        'Create a new academic year (e.g., 2024-2025)',
        'Set start and end dates for the session',
        'Mark it as the current academic year',
        'All data will be filtered by this year'
      ],
      importance: 'critical',
      route: '/Admin/academic-years'
    },
    {
      id: 'classes',
      title: '2. Create Classes',
      icon: MdClass,
      color: 'bg-blue-600',
      description: 'Organize students into classes',
      details: [
        'Go to Classes Management section',
        'Add classes with names (e.g., "Grade 10A")',
        'Set grade levels for student promotion',
        'Classes group students by year/section',
        'Each class can have multiple subjects'
      ],
      importance: 'critical',
      route: '/Admin/classes-management'
    },
    {
      id: 'subjects',
      title: '3. Add Subjects',
      icon: MdBook,
      color: 'bg-green-600',
      description: 'Define subjects for each class',
      details: [
        'Navigate to Subjects Management',
        'Select a class to add subjects',
        'Add subject name and code',
        'Set number of sessions/periods',
        'Subjects are linked to specific classes'
      ],
      importance: 'critical',
      route: '/Admin/subjects-management'
    },
    {
      id: 'teachers',
      title: '4. Register Teachers',
      icon: MdPeople,
      color: 'bg-orange-600',
      description: 'Add teachers to the system',
      details: [
        'Go to Teachers Management',
        'Add teacher details (first name, last name, email)',
        'Assign subjects to teachers',
        'Set teacher as class teacher (optional)',
        'Teachers can manage assigned subjects and attendance'
      ],
      importance: 'high',
      route: '/Admin/teachers-management'
    },
    {
      id: 'students',
      title: '5. Enroll Students',
      icon: MdSchool,
      color: 'bg-pink-600',
      description: 'Add students to classes',
      details: [
        'Navigate to Students Management',
        'Select a class for enrollment',
        'Add student details (first name, last name, ID)',
        'Assign to Town Master/House (optional)',
        'Students enrolled in current academic year'
      ],
      importance: 'high',
      route: '/Admin/students-management'
    },
    {
      id: 'town-master',
      title: '6. Configure Town Master/Houses',
      icon: MdSchool,
      color: 'bg-indigo-600',
      description: 'Set up house system for student organization',
      details: [
        'Navigate to Town Master section',
        'Create houses (e.g., Red, Blue, Green)',
        'Add colors and descriptions',
        'Assign students to houses',
        'Track house points and competitions'
      ],
      importance: 'medium',
      route: '/Admin/town-master'
    },
    {
      id: 'grading',
      title: '7. Configure Grading System',
      icon: MdAssignment,
      color: 'bg-teal-600',
      description: 'Set up grade scales and result formats',
      details: [
        'Go to Grading System section',
        'Define grade boundaries (A, B, C, etc.)',
        'Set pass/fail marks',
        'Configure result calculation methods',
        'Customize grade descriptions'
      ],
      importance: 'high',
      route: '/Admin/grading-system'
    },
    {
      id: 'fees',
      title: '8. Configure Fees Structure',
      icon: MdAttachMoney,
      color: 'bg-yellow-600',
      description: 'Set up fee structure and payment tracking',
      details: [
        'Go to Fees Structure section',
        'Define fee amounts per class/term',
        'Create payment categories',
        'Set payment deadlines',
        'Track payment status and history'
      ],
      importance: 'high',
      route: '/Admin/fees'
    },
    {
      id: 'result-pins',
      title: '9. Generate Result PINs',
      icon: MdAssignment,
      color: 'bg-purple-500',
      description: 'Create access codes for result checking',
      details: [
        'Navigate to Result PINs section',
        'Generate PIN batches',
        'Assign PINs to students',
        'Track PIN usage and validity',
        'Students use PINs to view results'
      ],
      importance: 'medium',
      route: '/Admin/result-pins'
    },
    {
      id: 'timetable',
      title: '10. Create Timetable',
      icon: MdCalendarToday,
      color: 'bg-blue-500',
      description: 'Schedule classes and periods',
      details: [
        'Go to Timetable section',
        'Define school periods/time slots',
        'Assign subjects to time slots',
        'Link teachers to periods',
        'Generate class-wise timetables'
      ],
      importance: 'medium',
      route: '/Admin/timetable'
    },
    {
      id: 'attendance',
      title: '11. Track Attendance',
      icon: MdCheckCircle,
      color: 'bg-green-500',
      description: 'Monitor daily student attendance',
      details: [
        'Access Attendance Management',
        'Select class and date',
        'Mark students present/absent',
        'View attendance statistics',
        'Generate attendance reports',
        'Notify parents of absences'
      ],
      importance: 'high',
      route: '/Admin/attendance'
    },
    {
      id: 'exams',
      title: '12. Manage Exams & Results',
      icon: MdAssignment,
      color: 'bg-red-600',
      description: 'Record and publish exam results',
      details: [
        'Teachers enter exam marks',
        'Results automatically calculated',
        'Generate report cards',
        'Publish results to students',
        'Track academic performance trends'
      ],
      importance: 'high',
      route: '/Admin/all-results'
    },
    {
      id: 'payments',
      title: '13. Manage Payments',
      icon: MdAttachMoney,
      color: 'bg-emerald-600',
      description: 'Track and manage fee payments',
      details: [
        'Go to Payments & Finance',
        'Record student payments',
        'Generate payment receipts',
        'Track outstanding fees',
        'Export payment reports',
        'View payment history by student'
      ],
      importance: 'medium',
      route: '/Admin/payments'
    },
    {
      id: 'notices',
      title: '14. Post Notices & Announcements',
      icon: MdNotifications,
      color: 'bg-orange-500',
      description: 'Communicate with students and staff',
      details: [
        'Navigate to Notices section',
        'Create announcements',
        'Target specific classes or all',
        'Set notice priority levels',
        'View notice history and reach'
      ],
      importance: 'medium',
      route: '/Admin/notices'
    },
    {
      id: 'complaints',
      title: '15. Handle Complaints',
      icon: MdWarning,
      color: 'bg-red-500',
      description: 'Manage student and parent complaints',
      details: [
        'Go to Complaints section',
        'View submitted complaints',
        'Respond to complaints',
        'Track resolution status',
        'Maintain complaint history'
      ],
      importance: 'medium',
      route: '/Admin/complains'
    },
    {
      id: 'users',
      title: '16. Manage User Accounts',
      icon: MdPeople,
      color: 'bg-slate-600',
      description: 'Control user access and roles',
      details: [
        'Navigate to User Management',
        'Create admin accounts',
        'Manage teacher accounts',
        'Set user permissions',
        'Activate/deactivate accounts',
        'Reset user passwords'
      ],
      importance: 'high',
      route: '/Admin/users'
    },
    {
      id: 'reports',
      title: '17. Generate Reports & Analytics',
      icon: MdInfo,
      color: 'bg-cyan-600',
      description: 'Access comprehensive reports',
      details: [
        'Go to Reports section',
        'Generate attendance reports',
        'View financial summaries',
        'Export academic performance data',
        'Analyze trends and statistics',
        'Download reports as PDF/Excel'
      ],
      importance: 'medium',
      route: '/Admin/reports'
    },
    {
      id: 'settings',
      title: '18. Configure System Settings',
      icon: MdInfo,
      color: 'bg-gray-600',
      description: 'Customize system configuration',
      details: [
        'Navigate to System Settings',
        'Update school information',
        'Configure email settings (SMTP)',
        'Set notification preferences',
        'Customize system behavior',
        'Manage security settings'
      ],
      importance: 'high',
      route: '/Admin/settings'
    },
    {
      id: 'notifications',
      title: '19. Monitor Notifications',
      icon: MdNotifications,
      color: 'bg-blue-400',
      description: 'Stay updated with system alerts',
      details: [
        'Check notification center regularly',
        'View pending actions',
        'Respond to urgent alerts',
        'Manage notification settings',
        'Mark notifications as read'
      ],
      importance: 'medium',
      route: '/Admin/notifications'
    },
    {
      id: 'activity-logs',
      title: '20. Review Activity Logs',
      icon: MdInfo,
      color: 'bg-violet-600',
      description: 'Track system usage and changes',
      details: [
        'Go to Activity Logs',
        'Monitor user actions',
        'Track data changes',
        'Audit system access',
        'Export logs for compliance',
        'Identify security issues'
      ],
      importance: 'low',
      route: '/Admin/activity-logs'
    }
  ];

  const keyFeatures = [
    {
      title: 'Academic Year Filtering',
      description: 'All data is automatically filtered by the current academic year, enabling year-over-year comparisons and historical data management.'
    },
    {
      title: 'Student Promotion',
      description: 'Easily promote students to the next grade level at the end of each academic year using the grade level system.'
    },
    {
      title: 'Town Master/House System',
      description: 'Organize students into houses for sports, competitions, and extracurricular activities with point tracking.'
    },
    {
      title: 'Comprehensive Grading',
      description: 'Flexible grading system with customizable grade boundaries, automatic calculation, and multiple result formats.'
    },
    {
      title: 'Timetable Management',
      description: 'Create and manage class timetables with period scheduling, teacher assignments, and conflict detection.'
    },
    {
      title: 'Result PIN System',
      description: 'Secure result access through PIN codes ensuring only authorized students can view their results.'
    },
    {
      title: 'Payment Tracking',
      description: 'Complete financial management with fee structure, payment recording, receipt generation, and outstanding tracking.'
    },
    {
      title: 'Real-time Notifications',
      description: 'Instant alerts for attendance, payments, results, complaints, and system updates across all user roles.'
    },
    {
      title: 'Comprehensive Reports',
      description: 'Generate detailed reports for attendance, fees, exam results, and more with built-in filtering and export options.'
    },
    {
      title: 'Role-Based Access Control',
      description: 'Different interfaces for Admin, Teachers, Students, Parents, Medical Staff, Finance, and Exam Officers with appropriate permissions.'
    },
    {
      title: 'User Management',
      description: 'Centralized user account management with role assignments, permission controls, and account activation/deactivation.'
    },
    {
      title: 'Activity Logging',
      description: 'Comprehensive audit trail of all system activities for compliance, security, and troubleshooting.'
    },
    {
      title: 'Email Integration',
      description: 'Configurable SMTP settings for sending notifications, password resets, and system alerts via email.'
    },
    {
      title: 'Complaint Management',
      description: 'Structured system for receiving, tracking, and resolving student and parent complaints with response tracking.'
    },
    {
      title: 'Attendance Management',
      description: 'Daily attendance tracking with statistics, reports, and parent notifications for absences.'
    },
    {
      title: 'System Customization',
      description: 'Flexible settings for school information, branding, notification preferences, and system behavior.'
    }
  ];

  return (
    <div className="space-y-6">
      <Card className="bg-gradient-to-r from-purple-600 to-blue-600 text-white border-0">
        <CardHeader>
          <CardTitle className="text-2xl flex items-center gap-2">
            <MdInfo className="h-6 w-6" />
            School Management System Guide
          </CardTitle>
          <CardDescription className="text-purple-100">
            Follow these steps to set up and use the system effectively
          </CardDescription>
        </CardHeader>
      </Card>

      {/* Setup Steps */}
      <div className="space-y-3">
        {steps.map((step) => {
          const Icon = step.icon;
          const isOpen = openSections[step.id];

          return (
            <Collapsible key={step.id} open={isOpen} onOpenChange={() => toggleSection(step.id)}>
              <Card className={`overflow-hidden transition-all ${isOpen ? 'ring-2 ring-purple-500' : ''}`}>
                <CollapsibleTrigger className="w-full">
                  <div className="flex items-center justify-between p-4 hover:bg-gray-50 cursor-pointer">
                    <div className="flex items-center gap-4">
                      <div className={`w-12 h-12 ${step.color} rounded-lg flex items-center justify-center text-white`}>
                        <Icon className="h-6 w-6" />
                      </div>
                      <div className="text-left">
                        <div className="flex items-center gap-2">
                          <h3 className="font-semibold text-gray-900">{step.title}</h3>
                          <Badge variant="outline" className={
                            step.importance === 'critical' ? 'border-red-500 text-red-600' :
                            step.importance === 'high' ? 'border-orange-500 text-orange-600' :
                            'border-gray-400 text-gray-600'
                          }>
                            {step.importance === 'critical' ? 'Required' : step.importance === 'high' ? 'Important' : 'Optional'}
                          </Badge>
                        </div>
                        <p className="text-sm text-gray-600">{step.description}</p>
                      </div>
                    </div>
                    <MdChevronRight className={`h-6 w-6 text-gray-400 transition-transform ${isOpen ? 'rotate-90' : ''}`} />
                  </div>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <div className="border-t border-gray-200 p-4 bg-gray-50">
                    <ul className="space-y-2 mb-4">
                      {step.details.map((detail, index) => (
                        <li key={index} className="flex items-start gap-2 text-sm text-gray-700">
                          <MdCheckCircle className="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" />
                          <span>{detail}</span>
                        </li>
                      ))}
                    </ul>
                    {step.route && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          window.location.href = step.route;
                        }}
                      >
                        Go to {step.title.split('. ')[1]}
                        <MdChevronRight className="ml-1 h-4 w-4" />
                      </Button>
                    )}
                  </div>
                </CollapsibleContent>
              </Card>
            </Collapsible>
          );
        })}
      </div>

      {/* Key Features */}
      <Card>
        <CardHeader>
          <CardTitle className="text-xl">Key System Features</CardTitle>
          <CardDescription>Understanding the powerful capabilities of the system</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {keyFeatures.map((feature, index) => (
              <div key={index} className="p-4 bg-gradient-to-br from-blue-50 to-purple-50 rounded-lg border border-blue-200 hover:shadow-md transition-shadow">
                <div className="flex items-start gap-2">
                  <MdCheckCircle className="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <h4 className="font-semibold text-blue-900 mb-1">{feature.title}</h4>
                    <p className="text-sm text-blue-700 leading-relaxed">{feature.description}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Quick Tips Card */}
      <Card className="bg-gradient-to-r from-amber-50 to-orange-50 border-amber-200">
        <CardHeader>
          <CardTitle className="text-lg flex items-center gap-2 text-amber-900">
            <MdInfo className="h-5 w-5" />
            Quick Tips for Success
          </CardTitle>
        </CardHeader>
        <CardContent>
          <ul className="space-y-2 text-sm text-amber-900">
            <li className="flex items-start gap-2">
              <MdCheckCircle className="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
              <span><strong>Start with Academic Year:</strong> Always set up the academic year first as all data depends on it</span>
            </li>
            <li className="flex items-start gap-2">
              <MdCheckCircle className="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
              <span><strong>Configure Grading Early:</strong> Set up your grading system before recording any exam results</span>
            </li>
            <li className="flex items-start gap-2">
              <MdCheckCircle className="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
              <span><strong>Use Town Master:</strong> Assign students to houses for better organization and inter-house competitions</span>
            </li>
            <li className="flex items-start gap-2">
              <MdCheckCircle className="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
              <span><strong>Regular Backups:</strong> Export reports and data regularly for backup and compliance</span>
            </li>
            <li className="flex items-start gap-2">
              <MdCheckCircle className="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
              <span><strong>Configure Email Settings:</strong> Set up SMTP in System Settings to enable password resets and notifications</span>
            </li>
            <li className="flex items-start gap-2">
              <MdCheckCircle className="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
              <span><strong>Monitor Activity Logs:</strong> Check activity logs regularly for security and compliance auditing</span>
            </li>
          </ul>
        </CardContent>
      </Card>
    </div>
  );
};

export default SystemGuide;
