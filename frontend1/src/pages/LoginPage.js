import { useEffect, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { useDispatch, useSelector } from "react-redux";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Eye, EyeOff, Loader2, ArrowLeft } from "lucide-react";
import { loginUser } from "../redux/userRelated/userHandle";
import { toast } from "sonner";
import BoSchoolLogo from "@/assets/Bo-School-logo.png";
import BackgroundImage from "@/assets/boSchool.jpg";

const LoginPage = ({ role }) => {
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const { status, currentUser, response, error, currentRole } = useSelector(
    (state) => state.user,
  );
  const [searchParams] = useSearchParams();
  const [toggle, setToggle] = useState(false);
  const [loader, setLoader] = useState(false);
  const [adminAccountType, setAdminAccountType] = useState("Admin");

  const [emailError, setEmailError] = useState(false);
  const [passwordError, setPasswordError] = useState(false);
  const [rollNumberError, setRollNumberError] = useState(false);

  const handleSubmit = (event) => {
    event.preventDefault();
    const effectiveRole = role === "Admin" ? adminAccountType : role;

    if (role === "Student") {
      const rollNum = event.target.rollNumber.value;
      const password = event.target.password.value;

      if (!rollNum || !password) {
        if (!rollNum) setRollNumberError(true);
        if (!password) setPasswordError(true);
        return;
      }
      const fields = { rollNum, password };
      setLoader(true);
      dispatch(loginUser(fields, effectiveRole));
    } else {
      const email = event.target.email.value;
      const password = event.target.password.value;

      if (!email || !password) {
        if (!email) setEmailError(true);
        if (!password) setPasswordError(true);
        return;
      }

      const fields = { email, password };
      setLoader(true);
      dispatch(loginUser(fields, effectiveRole));
    }
  };

  const handleInputChange = (event) => {
    const { name } = event.target;
    if (name === "email") setEmailError(false);
    if (name === "password") setPasswordError(false);
    if (name === "rollNumber") setRollNumberError(false);
  };

  useEffect(() => {
    if (role === "Admin") {
      const accountParam = searchParams.get("account");
      if (accountParam && accountParam.toLowerCase() === "principal") {
        setAdminAccountType("Principal");
      }
    }
  }, [role, searchParams]);

  useEffect(() => {
    if (status === "success") {
      toast.success("Login successful!", {
        description: `Welcome back, ${currentUser?.name || "User"}!`,
      });
      if (currentRole === "Admin" || currentRole === "Principal") {
        navigate("/Admin/dashboard");
      } else if (currentRole === "Student") {
        navigate("/Student/dashboard");
      } else if (currentRole === "Teacher") {
        navigate("/Teacher/dashboard");
      }
    } else if (status === "failed") {
      toast.error("Login failed", {
        description: response || "Invalid credentials. Please try again.",
      });
      setLoader(false);
    } else if (status === "error") {
      toast.error("Network Error", {
        description: "Unable to connect to the server. Please check your connection.",
      });
      setLoader(false);
    }
  }, [status, currentRole, navigate, error, response, currentUser]);

  const portalTitle = role === "Admin" ? adminAccountType : role;

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
              {portalTitle} Portal
            </CardTitle>
            <p className="text-center text-slate-200 text-sm font-light">
              Sign in to continue
            </p>
          </CardHeader>
          
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-5">
              {role === "Admin" && (
                <div className="space-y-3">
                  <Label className="text-sm font-light text-slate-200">
                    Account Type
                  </Label>
                  <div className="grid grid-cols-2 gap-3">
                    {["Admin", "Principal"].map((type) => (
                      <button
                        key={type}
                        type="button"
                        onClick={() => setAdminAccountType(type)}
                        className={`px-4 py-3 rounded-lg text-sm font-light transition-all ${
                          adminAccountType === type
                            ? "bg-white text-slate-900 shadow-lg"
                            : "bg-white/10 text-white border border-white/20 hover:bg-white/20"
                        }`}
                      >
                        {type}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {role === "Student" ? (
                <div className="space-y-2">
                  <Label htmlFor="rollNumber" className="text-slate-200 font-light">
                    ID Number
                  </Label>
                  <Input
                    id="rollNumber"
                    name="rollNumber"
                    type="text"
                    placeholder="Enter your ID number"
                    className={`bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 ${
                      rollNumberError ? "border-red-400" : ""
                    }`}
                    onChange={handleInputChange}
                  />
                  {rollNumberError && (
                    <p className="text-sm text-red-300">ID Number is required</p>
                  )}
                </div>
              ) : (
                <div className="space-y-2">
                  <Label htmlFor="email" className="text-slate-200 font-light">
                    Email
                  </Label>
                  <Input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="Enter your email"
                    className={`bg-white/10 border-white/20 text-white placeholder:text-slate-400 focus:bg-white/20 focus:border-white/40 ${
                      emailError ? "border-red-400" : ""
                    }`}
                    onChange={handleInputChange}
                  />
                  {emailError && <p className="text-sm text-red-300">Email is required</p>}
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="password" className="text-slate-200 font-light">
                  Password
                </Label>
                <div className="relative">
                  <Input
                    id="password"
                    name="password"
                    type={toggle ? "text" : "password"}
                    placeholder="Enter your password"
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
                {passwordError && <p className="text-sm text-red-300">Password is required</p>}
              </div>

              <div className="flex items-center justify-end pt-2">
                <Link 
                  to="/forgot-password" 
                  className="text-sm text-slate-300 hover:text-white transition-colors font-light"
                >
                  Forgot password?
                </Link>
              </div>

              <Button 
                type="submit" 
                className="w-full bg-white hover:bg-slate-100 text-slate-900 py-6 text-base font-normal shadow-lg transition-all hover:scale-[1.02]" 
                disabled={loader}
              >
                {loader ? (
                  <div className="flex items-center">
                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                    Signing in...
                  </div>
                ) : (
                  "Sign In"
                )}
              </Button>

              {role === "Admin" && (
                <p className="text-center text-sm text-slate-300 pt-4">
                  Don't have an account?{" "}
                  <Link 
                    to="/Adminregister" 
                    className="text-white hover:underline font-normal"
                  >
                    Sign up
                  </Link>
                </p>
              )}
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

export default LoginPage;
