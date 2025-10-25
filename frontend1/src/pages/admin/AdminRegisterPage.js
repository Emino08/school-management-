import * as React from 'react';
import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FiEye, FiEyeOff, FiLoader } from 'react-icons/fi';
import bgpic from "../../assets/designlogin.jpg"
import { LightPurpleButton } from '../../components/buttonStyles';
import { registerUser } from '../../redux/userRelated/userHandle';
import { toast } from 'sonner';

const AdminRegisterPage = () => {

    const dispatch = useDispatch()
    const navigate = useNavigate()

    const { status, currentUser, response, error, currentRole } = useSelector(state => state.user);;

    const [toggle, setToggle] = useState(false)
    const [loader, setLoader] = useState(false)

    const [emailError, setEmailError] = useState(false);
    const [passwordError, setPasswordError] = useState(false);
    const [adminNameError, setAdminNameError] = useState(false);
    const [schoolNameError, setSchoolNameError] = useState(false);
    const role = "Admin"

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

        const fields = { name, email, password, role, schoolName }
        setLoader(true)
        dispatch(registerUser(fields, role))
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
            setLoader(false)
        }
        else if (status === 'error') {
            toast.error('Network Error', {
                description: 'Unable to connect to the server. Please check your connection.',
            });
            setLoader(false)
        }
    }, [status, currentUser, currentRole, navigate, error, response]);

    return (
        <div className="min-h-screen grid grid-cols-1 lg:grid-cols-2">
            {/* Left Side - Form */}
            <div className="flex items-center justify-center p-8 bg-white">
                <Card className="w-full max-w-md border-0 shadow-none">
                    <CardHeader className="space-y-1">
                        <CardTitle className="text-4xl text-purple-900 text-center">
                            Admin Register
                        </CardTitle>
                        <CardDescription className="text-center text-base">
                            Create your own school by registering as an admin.
                            <br />
                            You will be able to add students and faculty and manage the system.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="adminName">Admin Name</Label>
                                <Input
                                    id="adminName"
                                    name="adminName"
                                    placeholder="Enter your name"
                                    autoComplete="name"
                                    autoFocus
                                    className={adminNameError ? 'border-red-500' : ''}
                                    onChange={handleInputChange}
                                />
                                {adminNameError && (
                                    <p className="text-sm text-red-500">Name is required</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="schoolName">School Name</Label>
                                <Input
                                    id="schoolName"
                                    name="schoolName"
                                    placeholder="Create your school name"
                                    autoComplete="off"
                                    className={schoolNameError ? 'border-red-500' : ''}
                                    onChange={handleInputChange}
                                />
                                {schoolNameError && (
                                    <p className="text-sm text-red-500">School name is required</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    placeholder="Enter your email"
                                    autoComplete="email"
                                    className={emailError ? 'border-red-500' : ''}
                                    onChange={handleInputChange}
                                />
                                {emailError && (
                                    <p className="text-sm text-red-500">Email is required</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password">Password</Label>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        name="password"
                                        type={toggle ? 'text' : 'password'}
                                        placeholder="Enter your password"
                                        autoComplete="current-password"
                                        className={passwordError ? 'border-red-500' : ''}
                                        onChange={handleInputChange}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setToggle(!toggle)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                    >
                                        {toggle ? (
                                            <FiEye className="h-5 w-5" />
                                        ) : (
                                            <FiEyeOff className="h-5 w-5" />
                                        )}
                                    </button>
                                </div>
                                {passwordError && (
                                    <p className="text-sm text-red-500">Password is required</p>
                                )}
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox id="remember" />
                                <Label htmlFor="remember" className="cursor-pointer font-normal">
                                    Remember me
                                </Label>
                            </div>

                            <LightPurpleButton
                                type="submit"
                                className="w-full"
                                disabled={loader}
                            >
                                {loader ? (
                                    <div className="flex items-center">
                                        <FiLoader className="mr-2 h-4 w-4 animate-spin" />
                                        Registering...
                                    </div>
                                ) : (
                                    "Register"
                                )}
                            </LightPurpleButton>

                            <p className="text-center text-sm">
                                Already have an account?{' '}
                                <Link
                                    to="/Adminlogin"
                                    className="text-purple-600 hover:underline font-semibold"
                                >
                                    Log in
                                </Link>
                            </p>
                        </form>
                    </CardContent>
                </Card>
            </div>

            {/* Right Side - Image */}
            <div
                className="hidden lg:block bg-cover bg-center bg-no-repeat"
                style={{ backgroundImage: `url(${bgpic})` }}
            />
        </div>
    );
}

export default AdminRegisterPage
