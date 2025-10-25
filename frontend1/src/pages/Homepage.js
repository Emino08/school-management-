import React from "react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import Students from "../assets/boSchool.jpg";
import { GraduationCap, Users, BookOpen, TrendingUp, Award, Shield } from "lucide-react";

const Homepage = () => {
  const features = [
    {
      icon: Users,
      title: "Student Management",
      description: "Efficiently manage student records and profiles"
    },
    {
      icon: BookOpen,
      title: "Course Organization",
      description: "Streamline class and course management"
    },
    {
      icon: TrendingUp,
      title: "Performance Tracking",
      description: "Monitor and assess student progress"
    },
    {
      icon: Award,
      title: "Achievement Records",
      description: "Track grades and accomplishments"
    }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-slate-50 dark:from-slate-900 dark:via-purple-950 dark:to-slate-900">
      {/* Hero Section */}
      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center min-h-screen">
          {/* Left Column - Image */}
          <div className="order-2 lg:order-1 animate-fade-in">
            <div className="relative">
              <div className="absolute -inset-4 bg-gradient-to-r from-purple-500 to-pink-500 rounded-3xl opacity-20 blur-2xl"></div>
              <img
                src={Students}
                className="relative rounded-3xl shadow-2xl w-full object-cover"
                alt="students"
              />
            </div>
          </div>

          {/* Right Column - Content */}
          <div className="order-1 lg:order-2 space-y-8">
            <div className="space-y-4">
              <Badge className="bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900 dark:text-purple-300">
                <Shield className="w-3 h-3 mr-1" />
                Trusted School Management
              </Badge>
              
              <h1 className="text-5xl md:text-6xl lg:text-7xl font-bold leading-tight">
                <span className="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                  Welcome to
                </span>
                <br />
                <span className="text-slate-900 dark:text-white">
                  Bo School
                </span>
                <br />
                <span className="text-slate-700 dark:text-slate-300">
                  Management System
                </span>
              </h1>

              <p className="text-lg text-slate-600 dark:text-slate-400 leading-relaxed max-w-xl">
                Streamline school management, class organization, and add students
                and faculty. Seamlessly track attendance, assess performance, and
                provide feedback. Access records, view marks, and communicate
                effortlessly.
              </p>
            </div>

            {/* Action Buttons */}
            <div className="flex flex-col sm:flex-row gap-4">
              <Link to="/choose" className="flex-1">
                <Button 
                  size="lg" 
                  className="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white shadow-lg"
                >
                  <GraduationCap className="w-5 h-5 mr-2" />
                  Login to Dashboard
                </Button>
              </Link>
              
              <Link to="/chooseasguest" className="flex-1">
                <Button 
                  size="lg" 
                  variant="outline" 
                  className="w-full border-2 border-purple-300 hover:bg-purple-50 dark:border-purple-700 dark:hover:bg-purple-950"
                >
                  Login as Guest
                </Button>
              </Link>
            </div>

            <p className="text-sm text-slate-600 dark:text-slate-400">
              Don't have an account?{" "}
              <Link 
                to="/Adminregister" 
                className="text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 font-semibold underline-offset-4 hover:underline"
              >
                Sign up now
              </Link>
            </p>

            {/* Features Grid */}
            <div className="grid grid-cols-2 gap-4 pt-8">
              {features.map((feature, index) => {
                const Icon = feature.icon;
                return (
                  <Card 
                    key={index} 
                    className="border-slate-200 dark:border-slate-800 hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
                  >
                    <CardContent className="p-4">
                      <div className="flex items-start space-x-3">
                        <div className="bg-purple-100 dark:bg-purple-900 p-2 rounded-lg">
                          <Icon className="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <h3 className="font-semibold text-sm text-slate-900 dark:text-white mb-1">
                            {feature.title}
                          </h3>
                          <p className="text-xs text-slate-600 dark:text-slate-400 line-clamp-2">
                            {feature.description}
                          </p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                );
              })}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Homepage;
