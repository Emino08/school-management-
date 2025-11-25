import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import axios from 'axios';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Eye, EyeOff, Loader2, ArrowLeft } from "lucide-react";
import BoSchoolLogo from "@/assets/Bo-School-logo.png";
import BackgroundImage from "@/assets/boSchool.jpg";

const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

const ParentLogin = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const response = await axios.post(`${API_URL}/parents/login`, formData);
      
      if (response.data.success) {
        localStorage.setItem('parent_token', response.data.token);
        localStorage.setItem('parent_data', JSON.stringify(response.data.parent));
        localStorage.setItem('userRole', 'parent');
        navigate('/parent/dashboard');
      } else {
        setError(response.data.message || 'Login failed');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Invalid email or password');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen relative overflow-hidden flex items-center justify-center">
      {/* Background Image with Overlay */}
      <div 
        className="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style={{ backgroundImage: `url(${BackgroundImage})` }}
      >
        <div className="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-900/85 to-purple-900/90 backdrop-blur-[2px]" />
      </div>

      {/* Content */}
      <div className="relative w-full max-w-md mx-auto p-6">
        {/* Back Button */}
        <Link to="/choose">
          <Button 
            variant="ghost" 
            className="mb-6 text-white hover:bg-white/10 hover:text-white"
          >
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back
          </Button>
        </Link>

        {/* Logo */}
        <div className="flex justify-center mb-8">
          <img
            src={BoSchoolLogo}
            alt="Bo Government Secondary School"
            className="h-20 w-auto drop-shadow-2xl"
          />
        </div>

        {/* Login Card */}
        <Card className="border-white/20 bg-white/10 backdrop-blur-md shadow-2xl">
          <CardHeader className="space-y-2 pb-6">
            <CardTitle className="text-3xl text-center text-white font-light tracking-tight">
              Parent Portal
            </CardTitle>
            <p className="text-center text-slate-200 text-sm font-light">
              Sign in to monitor your child's progress
            </p>
          </CardHeader>
          
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-5">
              {error && (
                <div className="bg-red-500/20 border border-red-400/30 text-red-200 px-4 py-3 rounded-lg text-sm">
                  {error}
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="email" className="text-slate-200 font-light">
                  Email
                </Label>
                <Input
                  id="email"
                  name="email"
                  type="email"
                  placeholder="Enter your email"
                  value={formData.email}
                  onChange={handleChange}
                  className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password" className="text-slate-200 font-light">
                  Password
                </Label>
                <div className="relative">
                  <Input
                    id="password"
                    name="password"
                    type={showPassword ? "text" : "password"}
                    placeholder="Enter your password"
                    value={formData.password}
                    onChange={handleChange}
                    className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 pr-10"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white transition-colors"
                  >
                    {showPassword ? <Eye className="h-5 w-5" /> : <EyeOff className="h-5 w-5" />}
                  </button>
                </div>
              </div>

              <div className="flex items-center justify-end pt-2">
                <Link 
                  to="/forgot-password/parent" 
                  className="text-sm text-slate-300 hover:text-white transition-colors font-light"
                >
                  Forgot password?
                </Link>
              </div>

              <Button 
                type="submit" 
                className="w-full bg-white hover:bg-slate-100 text-slate-900 py-6 text-base font-normal shadow-lg transition-all hover:scale-[1.02]" 
                disabled={loading}
              >
                {loading ? (
                  <div className="flex items-center">
                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                    Signing in...
                  </div>
                ) : (
                  "Sign In"
                )}
              </Button>

              <p className="text-center text-sm text-slate-300 pt-4">
                Don't have an account?{" "}
                <Link 
                  to="/parent/register" 
                  className="text-white hover:underline font-normal"
                >
                  Sign up
                </Link>
              </p>
            </form>
          </CardContent>
        </Card>

        {/* School Name */}
        <p className="text-center text-slate-300 text-sm font-light mt-8">
          Bo Government Secondary School
        </p>
      </div>
    </div>
  );
};

export default ParentLogin;
