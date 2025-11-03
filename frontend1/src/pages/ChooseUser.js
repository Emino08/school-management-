import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { UserCog, GraduationCap, Users, Loader2 } from 'lucide-react';
import { useDispatch, useSelector } from 'react-redux';
import { loginUser } from '../redux/userRelated/userHandle';
import { toast } from 'sonner';

const ChooseUser = ({ visitor }) => {
  const dispatch = useDispatch()
  const navigate = useNavigate()
  const password = "zxc"

  const { status, currentUser, currentRole } = useSelector(state => state.user);;

  const [loader, setLoader] = useState(false)

  const navigateHandler = (user) => {
    if (user === "Admin") {
      if (visitor === "guest") {
        const email = "yogendra@12"
        const fields = { email, password }
        setLoader(true)
        dispatch(loginUser(fields, user))
      }
      else {
        navigate('/Adminlogin');
      }
    }

    else if (user === "Student") {
      if (visitor === "guest") {
        const rollNum = "1"
        const fields = { rollNum, password }
        setLoader(true)
        dispatch(loginUser(fields, user))
      }
      else {
        navigate('/Studentlogin');
      }
    }

    else if (user === "Teacher") {
      if (visitor === "guest") {
        const email = "tony@12"
        const fields = { email, password }
        setLoader(true)
        dispatch(loginUser(fields, user))
      }
      else {
        navigate('/Teacherlogin');
      }
    }
  }

  useEffect(() => {
    if (status === 'success' || currentUser !== null) {
      toast.success('Login successful!', {
        description: `Welcome back as ${currentRole}!`,
      });
      if (currentRole === 'Admin') {
        navigate('/Admin/dashboard');
      }
      else if (currentRole === 'Student') {
        navigate('/Student/dashboard');
      } else if (currentRole === 'Teacher') {
        navigate('/Teacher/dashboard');
      }
    }
    else if (status === 'error') {
      setLoader(false)
      toast.error('Network Error', {
        description: 'Unable to connect to the server. Please check your connection.',
      });
    }
  }, [status, currentRole, navigate, currentUser]);

  const userTypes = [
    {
      type: 'Admin',
      icon: UserCog,
      title: 'Administrator',
      description: 'Access the dashboard to manage app data, users, and system settings.',
      color: 'from-blue-500 to-indigo-600',
      iconBg: 'bg-blue-100 dark:bg-blue-950',
      iconColor: 'text-blue-600 dark:text-blue-400'
    },
    {
      type: 'Student',
      icon: GraduationCap,
      title: 'Student',
      description: 'Explore course materials, assignments, and track your academic progress.',
      color: 'from-green-500 to-emerald-600',
      iconBg: 'bg-green-100 dark:bg-green-950',
      iconColor: 'text-green-600 dark:text-green-400'
    },
    {
      type: 'Teacher',
      icon: Users,
      title: 'Teacher',
      description: 'Create courses, manage assignments, and monitor student performance.',
      color: 'from-purple-500 to-pink-600',
      iconBg: 'bg-purple-100 dark:bg-purple-950',
      iconColor: 'text-purple-600 dark:text-purple-400'
    }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 flex items-center justify-center p-4">
      <div className="w-full max-w-6xl">
        {/* Header Section */}
        <div className="text-center mb-12 space-y-4">
          <Badge variant="outline" className="mb-4 bg-white/10 text-white border-white/20 backdrop-blur-sm">
            School Management System
          </Badge>
          <h1 className="text-4xl md:text-5xl font-bold text-white mb-4">
            Welcome Back
          </h1>
          <p className="text-lg text-slate-300 max-w-2xl mx-auto">
            Choose your role to continue to the dashboard
          </p>
        </div>

        {/* Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {userTypes.map((user) => {
            const Icon = user.icon;
            return (
              <Card 
                key={user.type}
                className="group relative overflow-hidden border-slate-700 bg-slate-800/50 backdrop-blur-sm hover:bg-slate-800/80 transition-all duration-300 cursor-pointer hover:scale-105 hover:shadow-2xl hover:shadow-purple-500/20"
                onClick={() => navigateHandler(user.type)}
              >
                {/* Gradient overlay on hover */}
                <div className={`absolute inset-0 bg-gradient-to-br ${user.color} opacity-0 group-hover:opacity-10 transition-opacity duration-300`} />
                
                <CardHeader className="relative">
                  <div className={`w-16 h-16 rounded-full ${user.iconBg} flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300`}>
                    <Icon className={`w-8 h-8 ${user.iconColor}`} />
                  </div>
                  <CardTitle className="text-2xl text-white">
                    {user.title}
                  </CardTitle>
                </CardHeader>
                
                <CardContent className="relative">
                  <CardDescription className="text-slate-300 text-base leading-relaxed">
                    {user.description}
                  </CardDescription>
                  
                  <Button 
                    className={`w-full mt-6 bg-gradient-to-r ${user.color} hover:opacity-90 transition-opacity`}
                    disabled={loader}
                  >
                    {loader ? (
                      <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        Loading...
                      </>
                    ) : (
                      `Login as ${user.title}`
                    )}
                  </Button>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {/* Guest Mode Badge */}
        {visitor === "guest" && (
          <div className="text-center mt-8">
            <Badge variant="secondary" className="bg-amber-500/20 text-amber-300 border-amber-500/30">
              Guest Mode Enabled
            </Badge>
          </div>
        )}
      </div>

      {/* Loading Overlay */}
      {loader && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
          <div className="bg-slate-800 p-8 rounded-lg shadow-2xl flex flex-col items-center space-y-4">
            <Loader2 className="w-12 h-12 text-purple-500 animate-spin" />
            <p className="text-white text-lg font-medium">Please wait...</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default ChooseUser;