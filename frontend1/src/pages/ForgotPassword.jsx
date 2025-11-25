import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Loader2, Mail, CheckCircle2, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';
import BoSchoolLogo from '@/assets/Bo-School-logo.png';
import BackgroundImage from '@/assets/boSchool.jpg';

const ForgotPassword = () => {
  const navigate = useNavigate();
  const { role } = useParams();
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);

  const getBackRoute = () => {
    switch (role?.toLowerCase()) {
      case 'parent':
        return '/parent/login';
      case 'medical':
        return '/medical/login';
      case 'examofficer':
        return '/ExamOfficer';
      case 'student':
        return '/Studentlogin';
      case 'teacher':
        return '/Teacherlogin';
      default:
        return '/Adminlogin';
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!email) {
      toast.error('Email Required', {
        description: 'Please enter your email address',
        duration: 3000,
      });
      return;
    }

    setLoading(true);

    try {
      const API_URL =
        import.meta.env.VITE_API_BASE_URL ||
        import.meta.env.VITE_API_BASE_URL_LOCAL ||
        'http://localhost:8080/api';
      const response = await fetch(`${API_URL}/password-reset/request`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
          email, 
          role: role || 'admin' 
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setSent(true);
        
        toast.success('Email Sent Successfully!', {
          description: data.message || `Password reset instructions have been sent to ${email}`,
          duration: 5000,
          icon: <CheckCircle2 className="h-5 w-5" />,
        });

        // Redirect after showing success message
        setTimeout(() => {
          navigate(getBackRoute());
        }, 3000);
      } else {
        // Handle specific error codes
        if (data.error_code === 'EMAIL_NOT_CONFIGURED') {
          toast.error('Password Reset Disabled', {
            description: 'Password reset is currently turned off. Please contact the system administrator to enable email settings.',
            duration: 6000,
            icon: <AlertCircle className="h-5 w-5" />,
          });
        } else if (data.error_code === 'EMAIL_SEND_FAILED' || data.error_code === 'EMAIL_SERVICE_ERROR') {
          toast.error('Email Configuration Error', {
            description: 'The email service is not properly configured. Please contact the system administrator to verify email settings.',
            duration: 6000,
            icon: <AlertCircle className="h-5 w-5" />,
          });
        } else {
          toast.error('Failed to Send Email', {
            description: data.message || 'Please try again later',
            duration: 4000,
          });
        }
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
        <Link to={getBackRoute()}>
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

        {/* Forgot Password Card */}
        <Card className="border-white/20 bg-white/10 backdrop-blur-md shadow-2xl">
          <CardHeader className="space-y-2 pb-6">
            <div className="flex justify-center mb-4">
              <div className="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center">
                <Mail className="w-8 h-8 text-white" />
              </div>
            </div>
            <CardTitle className="text-3xl text-center text-white font-light tracking-tight">
              Forgot Password?
            </CardTitle>
            <p className="text-center text-slate-200 text-sm font-light leading-relaxed">
              {sent 
                ? "Check your email for reset instructions"
                : "Enter your email and we'll send you instructions to reset your password"
              }
            </p>
          </CardHeader>
          
          <CardContent>
            {!sent ? (
              <form onSubmit={handleSubmit} className="space-y-5">
                <div className="space-y-2">
                  <Label htmlFor="email" className="text-slate-200 font-light">
                    Email Address
                  </Label>
                  <Input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="Enter your email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40"
                    disabled={loading}
                    autoFocus
                  />
                </div>

                <Button 
                  type="submit" 
                  className="w-full bg-white hover:bg-slate-100 text-slate-900 py-6 text-base font-normal shadow-lg transition-all hover:scale-[1.02]" 
                  disabled={loading}
                >
                  {loading ? (
                    <div className="flex items-center">
                      <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                      Sending...
                    </div>
                  ) : (
                    <>
                      <Mail className="mr-2 h-5 w-5" />
                      Send Reset Link
                    </>
                  )}
                </Button>

                <div className="text-center pt-4">
                  <Link 
                    to={getBackRoute()} 
                    className="text-sm text-slate-300 hover:text-white transition-colors font-light"
                  >
                    Remember your password? Sign in
                  </Link>
                </div>
              </form>
            ) : (
              <div className="text-center py-8">
                <div className="w-20 h-20 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                  <CheckCircle2 className="w-12 h-12 text-emerald-400" />
                </div>
                <p className="text-white text-lg font-light mb-2">Email Sent!</p>
                <p className="text-slate-300 text-sm font-light mb-6">
                  Check your inbox for password reset instructions
                </p>
                <p className="text-slate-400 text-xs font-light">
                  Redirecting to login...
                </p>
              </div>
            )}
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

export default ForgotPassword;
