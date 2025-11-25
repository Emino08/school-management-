import { useState, useEffect } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Loader2, Eye, EyeOff, Lock, CheckCircle2, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';
import BoSchoolLogo from '@/assets/Bo-School-logo.png';
import BackgroundImage from '@/assets/boSchool.jpg';

const ResetPassword = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const email = searchParams.get('email');

  const [formData, setFormData] = useState({
    password: '',
    confirmPassword: ''
  });
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [tokenValid, setTokenValid] = useState(true);
  const [success, setSuccess] = useState(false);

  useEffect(() => {
    if (!token || !email) {
      toast.error('Invalid Reset Link', {
        description: 'The password reset link is invalid or has expired',
        duration: 4000,
        icon: <AlertCircle className="h-5 w-5" />,
      });
      setTokenValid(false);
    }
  }, [token, email]);

  const validatePassword = () => {
    if (!formData.password) {
      toast.error('Password Required', {
        description: 'Please enter a new password',
        duration: 3000,
      });
      return false;
    }

    if (formData.password.length < 6) {
      toast.error('Password Too Short', {
        description: 'Password must be at least 6 characters long',
        duration: 3000,
      });
      return false;
    }

    if (formData.password !== formData.confirmPassword) {
      toast.error('Passwords Don\'t Match', {
        description: 'Please make sure both passwords match',
        duration: 3000,
      });
      return false;
    }

    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validatePassword()) return;

    setLoading(true);

    try {
      const API_URL =
        import.meta.env.VITE_API_BASE_URL ||
        import.meta.env.VITE_API_BASE_URL_LOCAL ||
        'http://localhost:8080/api';
      const response = await fetch(`${API_URL}/password-reset/reset`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token,
          password: formData.password,
          confirm_password: formData.confirmPassword
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setSuccess(true);
        
        toast.success('Password Reset Successfully!', {
          description: data.message || 'Your password has been updated. You can now sign in with your new password.',
          duration: 5000,
          icon: <CheckCircle2 className="h-5 w-5" />,
        });

        // Redirect to login after showing success
        setTimeout(() => {
          navigate('/Adminlogin');
        }, 3000);
      } else {
        toast.error('Reset Failed', {
          description: data.message || 'Failed to reset password. Please try again.',
          duration: 4000,
          icon: <AlertCircle className="h-5 w-5" />,
        });
      }
    } catch (error) {
      console.error('Password reset error:', error);
      toast.error('Connection Error', {
        description: 'Unable to connect to the server. Please check your internet connection.',
        duration: 4000,
        icon: <AlertCircle className="h-5 w-5" />,
      });
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  if (!tokenValid) {
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
              <div className="w-20 h-20 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <AlertCircle className="w-12 h-12 text-red-400" />
              </div>
              <h2 className="text-2xl font-light text-white mb-3">Invalid Reset Link</h2>
              <p className="text-slate-200 font-light mb-6">
                This password reset link is invalid or has expired.
              </p>
              <Link to="/forgot-password">
                <Button className="bg-white hover:bg-slate-100 text-slate-900">
                  Request New Link
                </Button>
              </Link>
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

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
                <CheckCircle2 className="w-12 h-12 text-emerald-400" />
              </div>
              <h2 className="text-2xl font-light text-white mb-3">Password Reset Complete!</h2>
              <p className="text-slate-200 font-light mb-6">
                Your password has been successfully updated. Redirecting to login...
              </p>
              <Loader2 className="w-6 h-6 animate-spin text-white mx-auto" />
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

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
        <Link to="/Adminlogin">
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

        {/* Reset Password Card */}
        <Card className="border-white/20 bg-white/10 backdrop-blur-md shadow-2xl">
          <CardHeader className="space-y-2 pb-6">
            <div className="flex justify-center mb-4">
              <div className="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center">
                <Lock className="w-8 h-8 text-white" />
              </div>
            </div>
            <CardTitle className="text-3xl text-center text-white font-light tracking-tight">
              Reset Password
            </CardTitle>
            <p className="text-center text-slate-200 text-sm font-light leading-relaxed">
              Enter your new password below
            </p>
            {email && (
              <p className="text-center text-slate-400 text-xs font-light">
                Resetting password for: {email}
              </p>
            )}
          </CardHeader>
          
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-5">
              <div className="space-y-2">
                <Label htmlFor="password" className="text-slate-200 font-light">
                  New Password
                </Label>
                <div className="relative">
                  <Input
                    id="password"
                    name="password"
                    type={showPassword ? "text" : "password"}
                    placeholder="Enter new password"
                    value={formData.password}
                    onChange={handleChange}
                    className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 pr-10"
                    disabled={loading}
                    autoFocus
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white transition-colors"
                  >
                    {showPassword ? <Eye className="h-5 w-5" /> : <EyeOff className="h-5 w-5" />}
                  </button>
                </div>
                <p className="text-xs text-slate-400 font-light">
                  Must be at least 6 characters
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="confirmPassword" className="text-slate-200 font-light">
                  Confirm New Password
                </Label>
                <div className="relative">
                  <Input
                    id="confirmPassword"
                    name="confirmPassword"
                    type={showConfirmPassword ? "text" : "password"}
                    placeholder="Confirm new password"
                    value={formData.confirmPassword}
                    onChange={handleChange}
                    className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 pr-10"
                    disabled={loading}
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

              <Button 
                type="submit" 
                className="w-full bg-white hover:bg-slate-100 text-slate-900 py-6 text-base font-normal shadow-lg transition-all hover:scale-[1.02] mt-6" 
                disabled={loading}
              >
                {loading ? (
                  <div className="flex items-center">
                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                    Resetting Password...
                  </div>
                ) : (
                  <>
                    <Lock className="mr-2 h-5 w-5" />
                    Reset Password
                  </>
                )}
              </Button>

              <div className="text-center pt-4">
                <Link 
                  to="/Adminlogin" 
                  className="text-sm text-slate-300 hover:text-white transition-colors font-light"
                >
                  Remember your password? Sign in
                </Link>
              </div>
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

export default ResetPassword;
