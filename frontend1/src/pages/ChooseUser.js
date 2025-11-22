import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { UserCog, GraduationCap, Users, Heart, UsersRound, Shield } from 'lucide-react';
import BoSchoolLogo from '@/assets/Bo-School-logo.png';
import BackgroundImage from '@/assets/boSchool.jpg';

const ChooseUser = () => {
  const navigate = useNavigate()

  const navigateHandler = (user) => {
    if (user === "Admin") {
      navigate('/Adminlogin');
    }
    else if (user === "Student") {
      navigate('/Studentlogin');
    }
    else if (user === "Teacher") {
      navigate('/Teacherlogin');
    }
    else if (user === "Principal") {
      navigate('/Adminlogin?account=principal');
    }
    else if (user === "Parent") {
      navigate('/parent/login');
    }
    else if (user === "Medical") {
      navigate('/medical/login');
    }
  }

  const userTypes = [
    {
      type: 'Admin',
      icon: UserCog,
      title: 'Administrator',
      description: 'Access the dashboard to manage app data, users, and system settings.',
    },
    {
      type: 'Principal',
      icon: Shield,
      title: 'Principal',
      description: 'Oversee academics, approvals, and reports through the admin portal.',
    },
    {
      type: 'Student',
      icon: GraduationCap,
      title: 'Student',
      description: 'Explore course materials, assignments, and track your academic progress.',
    },
    {
      type: 'Teacher',
      icon: Users,
      title: 'Teacher',
      description: 'Create courses, manage assignments, and monitor student performance.',
    },
    {
      type: 'Parent',
      icon: UsersRound,
      title: 'Parent',
      description: 'Monitor your children\'s progress, attendance, and communicate with teachers.',
    },
    {
      type: 'Medical',
      icon: Heart,
      title: 'Medical Staff',
      description: 'Manage student health records, treatments, and medical documentation.',
    }
  ];

  return (
    <div className="min-h-screen relative overflow-hidden">
      {/* Background Image with Overlay */}
      <div 
        className="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style={{ backgroundImage: `url(${BackgroundImage})` }}
      >
        <div className="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-900/85 to-purple-900/90 backdrop-blur-[2px]" />
      </div>

      <div className="relative w-full max-w-6xl mx-auto p-8">
        {/* Header */}
        <div className="text-center mb-16 space-y-6 pt-8">
          <div className="flex justify-center mb-8">
            <img
              src={BoSchoolLogo}
              alt="Bo Government Secondary School"
              className="h-24 w-auto drop-shadow-2xl"
            />
          </div>
          <h1 className="text-4xl md:text-5xl font-light text-white tracking-tight">
            Select Portal
          </h1>
          <p className="text-lg text-slate-200 font-light">
            Choose your role to continue
          </p>
        </div>

        {/* Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-8">
          {userTypes.map((user) => {
            const Icon = user.icon;
            return (
              <Card 
                key={user.type}
                className="group relative overflow-hidden border border-white/20 bg-white/10 backdrop-blur-md hover:bg-white/20 hover:border-white/30 transition-all duration-300 cursor-pointer hover:scale-105 hover:shadow-2xl"
                onClick={() => navigateHandler(user.type)}
              >
                <CardHeader className="space-y-4 pb-4">
                  <div className="w-14 h-14 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:bg-white/30 transition-colors duration-300">
                    <Icon className="w-7 h-7 text-white" strokeWidth={1.5} />
                  </div>
                  <CardTitle className="text-xl font-normal text-white">
                    {user.title}
                  </CardTitle>
                </CardHeader>
                
                <CardContent className="pt-0">
                  <CardDescription className="text-slate-200 text-sm leading-relaxed mb-6 font-light">
                    {user.description}
                  </CardDescription>
                  
                  <Button 
                    className="w-full bg-white hover:bg-slate-100 text-slate-900 transition-colors font-normal shadow-lg"
                  >
                    Continue
                  </Button>
                </CardContent>
              </Card>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default ChooseUser;
