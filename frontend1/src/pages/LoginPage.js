import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useDispatch, useSelector } from "react-redux";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { FiEye, FiEyeOff, FiLoader } from "react-icons/fi";
import bgpic from "../assets/designlogin.jpg";
import { LightPurpleButton } from "../components/buttonStyles";
import { loginUser } from "../redux/userRelated/userHandle";
import { toast } from "sonner";

const LoginPage = ({ role }) => {
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const { status, currentUser, response, error, currentRole } = useSelector(
    (state) => state.user,
  );
  const [toggle, setToggle] = useState(false);
  const [guestLoader, setGuestLoader] = useState(false);
  const [loader, setLoader] = useState(false);

  const [emailError, setEmailError] = useState(false);
  const [passwordError, setPasswordError] = useState(false);
  const [rollNumberError, setRollNumberError] = useState(false);

  const handleSubmit = (event) => {
    event.preventDefault();

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
      dispatch(loginUser(fields, role));
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
      dispatch(loginUser(fields, role));
    }
  };

  const handleInputChange = (event) => {
    const { name } = event.target;
    if (name === "email") setEmailError(false);
    if (name === "password") setPasswordError(false);
    if (name === "rollNumber") setRollNumberError(false);
  };

  const guestModeHandler = () => {
    const password = "zxc";

    if (role === "Admin") {
      const email = "yogendra@12";
      const fields = { email, password };
      setGuestLoader(true);
      dispatch(loginUser(fields, role));
    } else if (role === "Student") {
      const rollNum = "1";
      const fields = { rollNum, password };
      setGuestLoader(true);
      dispatch(loginUser(fields, role));
    } else if (role === "Teacher") {
      const email = "tony@12";
      const fields = { email, password };
      setGuestLoader(true);
      dispatch(loginUser(fields, role));
    }
  };

  useEffect(() => {
    if (status === "success" || currentUser !== null) {
      console.log(currentRole);
      toast.success("Login successful!", {
        description: `Welcome back, ${currentUser?.name || "User"}!`,
      });
      if (currentRole === "Admin") {
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
      setGuestLoader(false);
    } else if (status === "error") {
      toast.error("Network Error", {
        description: "Unable to connect to the server. Please check your connection.",
      });
      setLoader(false);
      setGuestLoader(false);
    }
  }, [status, currentRole, navigate, error, response, currentUser]);

  return (
    <div className="min-h-screen grid grid-cols-1 lg:grid-cols-2">
      {/* Left Side - Form */}
      <div className="flex items-center justify-center p-8 bg-white">
        <Card className="w-full max-w-md border-0 shadow-none">
          <CardHeader className="space-y-1">
            <CardTitle className="text-4xl text-purple-900 text-center">{role} Login</CardTitle>
            <CardDescription className="text-center text-base">
              Welcome back! Please enter your details
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              {role === "Student" ? (
                <div className="space-y-2">
                  <Label htmlFor="rollNumber">ID Number</Label>
                  <Input
                    id="rollNumber"
                    name="rollNumber"
                    type="text"
                    placeholder="Enter your ID Number"
                    className={rollNumberError ? "border-red-500" : ""}
                    onChange={handleInputChange}
                  />
                  {rollNumberError && (
                    <p className="text-sm text-red-500">ID Number is required</p>
                  )}
                </div>
              ) : (
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="Enter your email"
                    className={emailError ? "border-red-500" : ""}
                    onChange={handleInputChange}
                  />
                  {emailError && <p className="text-sm text-red-500">Email is required</p>}
                </div>
              )}
              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <div className="relative">
                  <Input
                    id="password"
                    name="password"
                    type={toggle ? "text" : "password"}
                    placeholder="Enter your password"
                    className={passwordError ? "border-red-500" : ""}
                    onChange={handleInputChange}
                  />
                  <button
                    type="button"
                    onClick={() => setToggle(!toggle)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                  >
                    {toggle ? <FiEye className="h-5 w-5" /> : <FiEyeOff className="h-5 w-5" />}
                  </button>
                </div>
                {passwordError && <p className="text-sm text-red-500">Password is required</p>}
              </div>

              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  <Checkbox id="remember" />
                  <Label htmlFor="remember" className="cursor-pointer font-normal">
                    Remember me
                  </Label>
                </div>
                <Link to="#" className="text-sm text-purple-600 hover:underline">
                  Forgot password?
                </Link>
              </div>

              <LightPurpleButton type="submit" className="w-full" disabled={loader}>
                {loader ? (
                  <div className="flex items-center">
                    <FiLoader className="mr-2 h-4 w-4 animate-spin" />
                    Loading...
                  </div>
                ) : (
                  "Login"
                )}
              </LightPurpleButton>

              <Button
                type="button"
                onClick={guestModeHandler}
                variant="outline"
                className="w-full border-purple-600 text-purple-600 hover:bg-purple-50"
                disabled={guestLoader}
              >
                {guestLoader ? (
                  <div className="flex items-center">
                    <FiLoader className="mr-2 h-4 w-4 animate-spin" />
                    Loading...
                  </div>
                ) : (
                  "Login as Guest"
                )}
              </Button>

              {role === "Admin" && (
                <p className="text-center text-sm">
                  Don't have an account?{" "}
                  <Link to="/Adminregister" className="text-purple-600 hover:underline font-semibold">
                    Sign up
                  </Link>
                </p>
              )}
            </form>
          </CardContent>
        </Card>
      </div>

      {/* Right Side - Image */}
      <div
        className="hidden lg:block bg-cover bg-center bg-no-repeat"
        style={{ backgroundImage: `url(${bgpic})` }}
      />

      {guestLoader && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white p-6 rounded-lg flex items-center space-x-3">
            <FiLoader className="h-6 w-6 animate-spin text-purple-600" />
            <span className="text-lg">Please Wait...</span>
          </div>
        </div>
      )}
    </div>
  );
};

export default LoginPage;
