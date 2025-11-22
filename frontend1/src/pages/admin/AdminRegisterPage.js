import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Eye, EyeOff, Loader2, ArrowLeft } from 'lucide-react';
import { registerUser } from '../../redux/userRelated/userHandle';
import { toast } from 'sonner';
import BoSchoolLogo from '@/assets/Bo-School-logo.png';
import BackgroundImage from '@/assets/boSchool.jpg';

const AdminRegisterPage = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();

    const { status, currentUser, response, error, currentRole } = useSelector(state => state.user);

    const [toggle, setToggle] = useState(false);
    const [loader, setLoader] = useState(false);

    const [emailError, setEmailError] = useState(false);
    const [passwordError, setPasswordError] = useState(false);
    const [adminNameError, setAdminNameError] = useState(false);
    const [schoolNameError, setSchoolNameError] = useState(false);
    const role = "Admin";

    const handleSubmit = (event) => {
        event.preventDefault();

        const name = event.target.adminName.value;
        const schoolName = event.target.schoolName.value;
        const email = event.target.email.value;
        const password = event.target.password.value;

        if (!name || !schoolName || !email || !password) {
            if (!name) setAdminNameError(true);
            if (!schoolName) setSchoolNameError(true);
            if (!email) setEmailError(true);
            if (!password) setPasswordError(true);
            return;
        }

        const fields = { name, email, password, role, schoolName };
        setLoader(true);
        dispatch(registerUser(fields, role));
    };

    const handleInputChange = (event) => {
        const { name } = event.target;
        if (name === 'email') setEmailError(false);
        if (name === 'password') setPasswordError(false);
        if (name === 'adminName') setAdminNameError(false);
        if (name === 'schoolName') setSchoolNameError(false);
    };

    useEffect(() => {
        if (status === 'success' || (currentUser !== null && currentRole === 'Admin')) {
            toast.success('Registration successful!', {
                description: `Welcome to ${currentUser?.schoolName || 'the school management system'}!`,
            });
            navigate('/Admin/dashboard');
        }
        else if (status === 'failed') {
            toast.error('Registration failed', {
                description: response || 'Unable to create account. Please try again.',
            });
            setLoader(false);
        }
        else if (status === 'error') {
            toast.error('Network Error', {
                description: 'Unable to connect to the server. Please check your connection.',
            });
            setLoader(false);
        }
    }, [status, currentUser, currentRole, navigate, error, response]);

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
            <div className="relative w-full max-w-lg mx-auto p-6">
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

                {/* Register Card */}
                <Card className="border-white/20 bg-white/10 backdrop-blur-md shadow-2xl">
                    <CardHeader className="space-y-2 pb-6">
                        <CardTitle className="text-3xl text-center text-white font-light tracking-tight">
                            Admin Registration
                        </CardTitle>
                        <p className="text-center text-slate-200 text-sm font-light leading-relaxed">
                            Create your school administration account
                        </p>
                    </CardHeader>
                    
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="adminName" className="text-slate-200 font-light">
                                    Admin Name
                                </Label>
                                <Input
                                    id="adminName"
                                    name="adminName"
                                    type="text"
                                    placeholder="Enter your name"
                                    autoComplete="name"
                                    autoFocus
                                    className={`bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 ${
                                        adminNameError ? "border-red-400" : ""
                                    }`}
                                    onChange={handleInputChange}
                                />
                                {adminNameError && (
                                    <p className="text-sm text-red-300">Name is required</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="schoolName" className="text-slate-200 font-light">
                                    School Name
                                </Label>
                                <Input
                                    id="schoolName"
                                    name="schoolName"
                                    type="text"
                                    placeholder="Enter your school name"
                                    autoComplete="off"
                                    className={`bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 ${
                                        schoolNameError ? "border-red-400" : ""
                                    }`}
                                    onChange={handleInputChange}
                                />
                                {schoolNameError && (
                                    <p className="text-sm text-red-300">School name is required</p>
                                )}
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
                                    autoComplete="email"
                                    className={`bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 ${
                                        emailError ? "border-red-400" : ""
                                    }`}
                                    onChange={handleInputChange}
                                />
                                {emailError && (
                                    <p className="text-sm text-red-300">Email is required</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password" className="text-slate-200 font-light">
                                    Password
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        name="password"
                                        type={toggle ? "text" : "password"}
                                        placeholder="Create a password"
                                        autoComplete="new-password"
                                        className={`bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 pr-10 ${
                                            passwordError ? "border-red-400" : ""
                                        }`}
                                        onChange={handleInputChange}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setToggle(!toggle)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white transition-colors"
                                    >
                                        {toggle ? <Eye className="h-5 w-5" /> : <EyeOff className="h-5 w-5" />}
                                    </button>
                                </div>
                                {passwordError && (
                                    <p className="text-sm text-red-300">Password is required</p>
                                )}
                            </div>

                            <Button 
                                type="submit" 
                                className="w-full bg-white hover:bg-slate-100 text-slate-900 py-6 text-base font-normal shadow-lg transition-all hover:scale-[1.02] mt-6" 
                                disabled={loader}
                            >
                                {loader ? (
                                    <div className="flex items-center">
                                        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                        Creating Account...
                                    </div>
                                ) : (
                                    "Create Admin Account"
                                )}
                            </Button>

                            <p className="text-center text-sm text-slate-300 pt-4">
                                Already have an account?{" "}
                                <Link 
                                    to="/Adminlogin" 
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

export default AdminRegisterPage;
