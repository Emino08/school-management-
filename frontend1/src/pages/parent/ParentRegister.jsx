import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Eye, EyeOff, Loader2, ArrowLeft, CheckCircle } from 'lucide-react';
import BoSchoolLogo from '@/assets/Bo-School-logo.png';
import BackgroundImage from '@/assets/boSchool.jpg';

const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

const ParentRegister = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    phone: '',
    address: '',
    relationship: 'mother',
    admin_id: 1
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
    setError('');
  };

  const validateForm = () => {
    if (formData.password !== formData.confirmPassword) {
      setError('Passwords do not match');
      return false;
    }
    if (formData.password.length < 6) {
      setError('Password must be at least 6 characters');
      return false;
    }
    if (formData.phone.length < 10) {
      setError('Please enter a valid phone number');
      return false;
    }
    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    setLoading(true);
    setError('');

    try {
      const { confirmPassword, ...registrationData } = formData;
      const response = await axios.post(`${API_URL}/parents/register`, registrationData);
      
      if (response.data.success) {
        setSuccess(true);
        setTimeout(() => {
          navigate('/parent/login');
        }, 2000);
      } else {
        setError(response.data.message || 'Registration failed');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Registration failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="min-h-screen relative overflow-hidden flex items-center justify-center">
        <div 
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{ backgroundImage: `url(${BackgroundImage})` }}
        >
          <div className="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-900/85 to-purple-900/90 backdrop-blur-[2px]" />
        </div>

        <div className="relative w-full max-w-md mx-auto p-6">
          <Card className="border-white/20 bg-white/10 backdrop-blur-md shadow-2xl">
            <CardContent className="pt-12 pb-12 text-center">
              <div className="w-20 h-20 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <CheckCircle className="w-12 h-12 text-emerald-400" />
              </div>
              <h2 className="text-2xl font-light text-white mb-3">Registration Successful!</h2>
              <p className="text-slate-200 font-light mb-6">
                Your account has been created. Redirecting to login...
              </p>
              <Loader2 className="w-6 h-6 animate-spin text-white mx-auto" />
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen relative overflow-hidden flex items-center justify-center py-8">
      {/* Background Image with Overlay */}
      <div 
        className="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style={{ backgroundImage: `url(${BackgroundImage})` }}
      >
        <div className="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-900/85 to-purple-900/90 backdrop-blur-[2px]" />
      </div>

      {/* Content */}
      <div className="relative w-full max-w-2xl mx-auto p-6">
        {/* Back Button */}
        <Link to="/parent/login">
          <Button 
            variant="ghost" 
            className="mb-6 text-white hover:bg-white/10 hover:text-white"
          >
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Login
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

        {/* Register Card */}
        <Card className="border-white/20 bg-white/10 backdrop-blur-md shadow-2xl">
          <CardHeader className="space-y-2 pb-6">
            <CardTitle className="text-3xl text-center text-white font-light tracking-tight">
              Parent Registration
            </CardTitle>
            <p className="text-center text-slate-200 text-sm font-light">
              Create an account to monitor your child's progress
            </p>
          </CardHeader>
          
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-5">
              {error && (
                <div className="bg-red-500/20 border border-red-400/30 text-red-200 px-4 py-3 rounded-lg text-sm">
                  {error}
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div className="space-y-2">
                  <Label htmlFor="name" className="text-slate-200 font-light">
                    Full Name
                  </Label>
                  <Input
                    id="name"
                    name="name"
                    type="text"
                    placeholder="Enter your full name"
                    value={formData.name}
                    onChange={handleChange}
                    className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40"
                    required
                  />
                </div>

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
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div className="space-y-2">
                  <Label htmlFor="phone" className="text-slate-200 font-light">
                    Phone Number
                  </Label>
                  <Input
                    id="phone"
                    name="phone"
                    type="tel"
                    placeholder="Enter phone number"
                    value={formData.phone}
                    onChange={handleChange}
                    className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40"
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="relationship" className="text-slate-200 font-light">
                    Relationship
                  </Label>
                  <select
                    id="relationship"
                    name="relationship"
                    value={formData.relationship}
                    onChange={handleChange}
                    className="flex h-10 w-full rounded-md border border-white/20 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20"
                    required
                  >
                    <option value="mother" className="bg-slate-800">Mother</option>
                    <option value="father" className="bg-slate-800">Father</option>
                    <option value="guardian" className="bg-slate-800">Guardian</option>
                    <option value="other" className="bg-slate-800">Other</option>
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="address" className="text-slate-200 font-light">
                  Address
                </Label>
                <Input
                  id="address"
                  name="address"
                  type="text"
                  placeholder="Enter your address"
                  value={formData.address}
                  onChange={handleChange}
                  className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40"
                  required
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div className="space-y-2">
                  <Label htmlFor="password" className="text-slate-200 font-light">
                    Password
                  </Label>
                  <div className="relative">
                    <Input
                      id="password"
                      name="password"
                      type={showPassword ? "text" : "password"}
                      placeholder="Create a password"
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

                <div className="space-y-2">
                  <Label htmlFor="confirmPassword" className="text-slate-200 font-light">
                    Confirm Password
                  </Label>
                  <div className="relative">
                    <Input
                      id="confirmPassword"
                      name="confirmPassword"
                      type={showConfirmPassword ? "text" : "password"}
                      placeholder="Confirm your password"
                      value={formData.confirmPassword}
                      onChange={handleChange}
                      className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 pr-10"
                      required
                    />
                    <button
                      type="button"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white transition-colors"
                    >
                      {showConfirmPassword ? <Eye className="h-5 w-5" /> : <EyeOff className="h-5 w-5" />}
                    </button>
                  </div>
                </div>
              </div>

              <Button 
                type="submit" 
                className="w-full bg-white hover:bg-slate-100 text-slate-900 py-6 text-base font-normal shadow-lg transition-all hover:scale-[1.02] mt-6" 
                disabled={loading}
              >
                {loading ? (
                  <div className="flex items-center">
                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                    Creating Account...
                  </div>
                ) : (
                  "Create Parent Account"
                )}
              </Button>

              <p className="text-center text-sm text-slate-300 pt-4">
                Already have an account?{" "}
                <Link 
                  to="/parent/login" 
                  className="text-white hover:underline font-normal"
                >
                  Sign in
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

export default ParentRegister;
