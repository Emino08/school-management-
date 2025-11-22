import React, { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { useDispatch, useSelector } from "react-redux";
import { registerUser } from "../../../redux/userRelated/userHandle";
import { toast } from 'sonner';
import { underControl } from "../../../redux/userRelated/userSlice";
import { getAllSclasses } from "../../../redux/sclassRelated/sclassHandle";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { MdRotateRight } from "react-icons/md";

const AddStudent = ({ situation }) => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const params = useParams();

  const userState = useSelector((state) => state.user);
  const { status, currentUser, response, error } = userState;
  const { sclassesList } = useSelector((state) => state.sclass);

  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [rollNum, setRollNum] = useState("");
  const [password, setPassword] = useState("");
  const [className, setClassName] = useState("");
  const [sclassName, setSclassName] = useState("");

  const adminID = currentUser._id;
  const role = "Student";
  const attendance = [];

  useEffect(() => {
    if (situation === "Class") {
      setSclassName(params.id);
    }
  }, [params.id, situation]);

  const [loader, setLoader] = useState(false);

  useEffect(() => {
    dispatch(getAllSclasses(adminID, "Sclass"));
  }, [adminID, dispatch]);

  const changeHandler = (value) => {
    if (value === "Select Class") {
      setClassName("Select Class");
      setSclassName("");
    } else {
      const selectedClass = sclassesList.find(
        (classItem) => classItem.sclassName === value,
      );
      setClassName(selectedClass.sclassName);
      setSclassName(selectedClass._id);
    }
  };

  const submitHandler = (event) => {
    event.preventDefault();
    if (sclassName === "") {
      toast.error("Please select a classname");
    } else if (!firstName.trim() || !lastName.trim()) {
      toast.error("Please provide both first name and surname");
    } else {
      setLoader(true);
      const fullName = `${firstName} ${lastName}`.trim();
      const fields = {
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        name: fullName,
        rollNum,
        password,
        sclassName,
        adminID,
        role,
        attendance,
      };
      dispatch(registerUser(fields, role));
    }
  };

  useEffect(() => {
    if (status === "added") {
      dispatch(underControl());
      navigate(-1);
    } else if (status === "failed") {
      toast.error('Error', { description: response || 'An error occurred.' });
      setLoader(false);
    } else if (status === "error") {
      toast.error('Network Error', { description: 'Unable to connect to the server.' });
      setLoader(false);
    }
  }, [status, navigate, error, response, dispatch]);

  return (
    <>
      <div className="flex flex-1 items-center justify-center p-6">
        <Card className="w-full max-w-2xl">
          <CardHeader>
            <CardTitle className="text-2xl">Add Student</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={submitHandler} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="firstName">First Name</Label>
                  <Input
                    id="firstName"
                    type="text"
                    placeholder="Enter student's first name..."
                    value={firstName}
                    onChange={(event) => setFirstName(event.target.value)}
                    autoComplete="given-name"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="lastName">Surname</Label>
                  <Input
                    id="lastName"
                    type="text"
                    placeholder="Enter student's surname..."
                    value={lastName}
                    onChange={(event) => setLastName(event.target.value)}
                    autoComplete="family-name"
                    required
                  />
                </div>
              </div>

              {situation === "Student" && (
                <div className="space-y-2">
                  <Label>Class</Label>
                  <Select value={className} onValueChange={changeHandler} required>
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Select Class" />
                    </SelectTrigger>
                    <SelectContent>
                      {sclassesList.map((classItem, index) => (
                        <SelectItem key={index} value={classItem.sclassName}>
                          {classItem.sclassName}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="rollNum">Id Number</Label>
                <Input
                  id="rollNum"
                  type="number"
                  placeholder="Enter student's Id Number..."
                  value={rollNum}
                  onChange={(event) => setRollNum(event.target.value)}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input
                  id="password"
                  type="password"
                  placeholder="Enter student's password..."
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  autoComplete="new-password"
                  required
                />
              </div>

              <Button type="submit" disabled={loader} className="w-full">
                {loader ? <MdRotateRight className="w-5 h-5 animate-spin" /> : "Add Student"}
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </>
  );
};

export default AddStudent;
