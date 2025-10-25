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
  MdInfo
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
        'Go to Classes section',
        'Add classes with names (e.g., "Grade 10A")',
        'Set grade levels for student promotion',
        'Classes group students by year/section',
        'Each class can have multiple subjects'
      ],
      importance: 'critical',
      route: '/Admin/classes'
    },
    {
      id: 'subjects',
      title: '3. Add Subjects',
      icon: MdBook,
      color: 'bg-green-600',
      description: 'Define subjects for each class',
      details: [
        'Navigate to Subjects section',
        'Select a class to add subjects',
        'Add subject name and code',
        'Set number of sessions/periods',
        'Subjects are linked to specific classes'
      ],
      importance: 'critical',
      route: '/Admin/subjects'
    },
    {
      id: 'teachers',
      title: '4. Register Teachers',
      icon: MdPeople,
      color: 'bg-orange-600',
      description: 'Add teachers to the system',
      details: [
        'Go to Teachers section',
        'Add teacher details (name, email, etc.)',
        'Assign subjects to teachers',
        'Teachers can manage assigned subjects',
        'Track teacher attendance and performance'
      ],
      importance: 'high',
      route: '/Admin/teachers'
    },
    {
      id: 'students',
      title: '5. Enroll Students',
      icon: MdSchool,
      color: 'bg-pink-600',
      description: 'Add students to classes',
      details: [
        'Navigate to Students section',
        'Select a class for enrollment',
        'Add student details (name, ID number, etc.)',
        'Students are enrolled in the current academic year',
        'Manage student records, attendance, and grades'
      ],
      importance: 'high',
      route: '/Admin/students'
    },
    {
      id: 'fees',
      title: '6. Configure Fees',
      icon: MdAttachMoney,
      color: 'bg-yellow-600',
      description: 'Set up fee structure',
      details: [
        'Go to Fees section',
        'Define fee amounts per class/term',
        'Record student payments',
        'Track payment status',
        'Generate fee reports'
      ],
      importance: 'medium',
      route: '/Admin/fees'
    },
    {
      id: 'attendance',
      title: '7. Record Attendance',
      icon: MdCheckCircle,
      color: 'bg-teal-600',
      description: 'Track student attendance',
      details: [
        'Access student profiles',
        'Mark daily attendance',
        'View attendance statistics',
        'Generate attendance reports',
        'Monitor attendance trends'
      ],
      importance: 'medium',
      route: '/Admin/students'
    },
    {
      id: 'exams',
      title: '8. Manage Exams & Grades',
      icon: MdAssignment,
      color: 'bg-indigo-600',
      description: 'Create exams and record results',
      details: [
        'Create exam schedules',
        'Record student exam marks',
        'Calculate grades automatically',
        'Generate report cards',
        'Track academic performance'
      ],
      importance: 'medium',
      route: '/Admin/students'
    },
    {
      id: 'notices',
      title: '9. Post Notices',
      icon: MdNotifications,
      color: 'bg-red-600',
      description: 'Communicate with students/staff',
      details: [
        'Navigate to Notices section',
        'Create announcements',
        'Target specific audiences',
        'View notice history',
        'Keep everyone informed'
      ],
      importance: 'low',
      route: '/Admin/notices'
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
      title: 'Comprehensive Reporting',
      description: 'Generate reports for attendance, fees, exam results, and more with built-in filtering and export options.'
    },
    {
      title: 'Role-Based Access',
      description: 'Different interfaces for Admin, Teachers, and Students with appropriate permissions and access levels.'
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
          <CardTitle>Key Features</CardTitle>
          <CardDescription>Understanding how the system works</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {keyFeatures.map((feature, index) => (
              <div key={index} className="p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h4 className="font-semibold text-blue-900 mb-2">{feature.title}</h4>
                <p className="text-sm text-blue-700">{feature.description}</p>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default SystemGuide;
