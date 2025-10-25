import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate, useParams } from "react-router-dom";
import { getSubjectDetails } from "../../../redux/sclassRelated/sclassHandle";
import {
  getTeacherDetails,
  getTeachers,
  teacherAssign,
} from "../../../redux/teacherRelated/teacherHandle";
import { toast } from 'sonner';
import { registerUser } from "../../../redux/userRelated/userHandle";
import { underControl } from "../../../redux/userRelated/userSlice";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { MdRotateRight } from "react-icons/md";

const AddTeacher = () => {
  const params = useParams();
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const subjectID = params.id;

  const {
    teachersList,
    loading: teacherListLoading,
    error: teacherListError,
    response: teacherListResponse,
  } = useSelector((state) => state.teacher);

  const { teacherDetails } = useSelector((state) => state.teacher);
  const { status, response, error } = useSelector((state) => state.user);
  const { subjectDetails } = useSelector((state) => state.sclass);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [checked, setChecked] = useState(false);
  const [isExamOfficer, setIsExamOfficer] = useState(false);
  const [canApproveResults, setCanApproveResults] = useState(false);
  
  // Class Master fields
  const [isClassMaster, setIsClassMaster] = useState(false);
  const [classMasterOf, setClassMasterOf] = useState("");
  const [classes, setClasses] = useState([]);
  const [phone, setPhone] = useState("");
  const [address, setAddress] = useState("");
  const [qualification, setQualification] = useState("");
  const [experienceYears, setExperienceYears] = useState("");

  const [loader, setLoader] = useState(false);

  useEffect(() => {
    dispatch(getSubjectDetails(subjectID, "Subject"));
    dispatch(getTeachers());
    fetchClasses();
    let teacherId = name?.split(" ")[0];
    if (teacherId) {
      dispatch(getTeacherDetails(teacherId));
    }
  }, [dispatch, name, subjectID]);

  const { currentUser } = useSelector((state) => state.user);

  const fetchClasses = async () => {
    try {
      const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";
      const response = await fetch(`${API_URL}/classes`, {
        headers: { 
          Authorization: `Bearer ${currentUser?.token}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setClasses(data.classes || []);
      }
    } catch (error) {
      console.error("Error fetching classes:", error);
    }
  };

  const role = "Teacher";
  const school = subjectDetails && subjectDetails.school;
  const teachSubject = subjectDetails && subjectDetails._id;
  const teachSclass =
    subjectDetails &&
    subjectDetails.sclassName &&
    subjectDetails.sclassName._id;

  const fields = {
    name,
    email,
    password,
    role,
    school,
    teachSubject,
    teachSclass,
    is_exam_officer: isExamOfficer,
    can_approve_results: canApproveResults,
    is_class_master: isClassMaster,
    class_master_of: isClassMaster ? classMasterOf : null,
    phone,
    address,
    qualification,
    experience_years: experienceYears,
    // When creating from a subject context, send subjects array like edit modal
    teachSubjects: teachSubject ? [teachSubject] : undefined,
  };

  const handleOnChange = (value) => {
    setName(value);
  };

  const submitHandler = (event) => {
    event.preventDefault();
    setLoader(true);

    if (checked) {
      dispatch(
        teacherAssign({
          teacherId: teacherDetails._id,
          teachSubject,
          teachSclass,
        }),
      );
    } else {
      dispatch(registerUser(fields, role));
    }
    console.log(teacherDetails);
  };

  useEffect(() => {
    if (status === "added") {
      dispatch(underControl());
      navigate("/Admin/teachers");
    } else if (status === "failed") {
      toast.error('Error', { description: response || 'An error occurred.' });
      setLoader(false);
    } else if (status === "error") {
      toast.error('Network Error', { description: 'Unable to connect to the server.' });
      setLoader(false);
    }
  }, [status, navigate, error, response, dispatch]);

  return (
    <div className="flex flex-1 items-center justify-center p-6">
      <Card className="w-full max-w-2xl">
        <CardHeader>
          <CardTitle className="text-2xl">
            {checked ? "Assign" : "Add"} Teacher
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={submitHandler} className="space-y-6">
            <div className="space-y-4">
              <p className="text-base">
                <span className="font-semibold">Subject:</span> {subjectDetails && subjectDetails.subName}
              </p>
              <p className="text-base">
                <span className="font-semibold">Class:</span>{" "}
                {subjectDetails &&
                  subjectDetails.sclassName &&
                  subjectDetails.sclassName.sclassName}
              </p>
            </div>

            <div className="flex items-center space-x-2">
              <Checkbox
                id="oldTeacher"
                checked={checked}
                onCheckedChange={(value) => setChecked(value)}
              />
              <Label htmlFor="oldTeacher" className="cursor-pointer">
                Assign Existing Teacher
              </Label>
            </div>

            {checked ? (
              <div className="space-y-2">
                <Label>Teacher Name</Label>
                <Select value={name} onValueChange={handleOnChange} required>
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Select Teacher" />
                  </SelectTrigger>
                  <SelectContent>
                    {teachersList?.map((teacher) => (
                      <SelectItem key={teacher._id} value={teacher._id}>
                        {teacher.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            ) : (
              <>
                <div className="space-y-2">
                  <Label htmlFor="name">Name</Label>
                  <Input
                    id="name"
                    type="text"
                    placeholder="Enter teacher's name..."
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    autoComplete="name"
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="Enter teacher's email..."
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                    autoComplete="email"
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="password">Password</Label>
                  <Input
                    id="password"
                    type="password"
                    placeholder="Enter teacher's password..."
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    autoComplete="new-password"
                    required
                  />
                </div>

                {/* Additional Contact Info */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="phone">Phone (Optional)</Label>
                    <Input
                      id="phone"
                      type="tel"
                      placeholder="Enter phone number..."
                      value={phone}
                      onChange={(event) => setPhone(event.target.value)}
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="address">Address (Optional)</Label>
                    <Input
                      id="address"
                      type="text"
                      placeholder="Enter address..."
                      value={address}
                      onChange={(event) => setAddress(event.target.value)}
                    />
                  </div>
                </div>

                {/* Professional Details */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="qualification">Qualification (Optional)</Label>
                    <Input
                      id="qualification"
                      type="text"
                      placeholder="e.g., B.Ed, M.Sc"
                      value={qualification}
                      onChange={(event) => setQualification(event.target.value)}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="experience">Experience Years (Optional)</Label>
                    <Input
                      id="experience"
                      type="number"
                      min="0"
                      placeholder="e.g., 5"
                      value={experienceYears}
                      onChange={(event) => setExperienceYears(event.target.value)}
                    />
                  </div>
                </div>

                {/* Class Master Options */}
                <div className="space-y-3 p-4 bg-green-50 rounded-md border border-green-200">
                  <h4 className="font-semibold text-sm text-green-900">Class Master Designation (Optional)</h4>
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="isClassMaster"
                      checked={isClassMaster}
                      onCheckedChange={(value) => {
                        setIsClassMaster(value);
                        if (!value) setClassMasterOf("");
                      }}
                    />
                    <Label htmlFor="isClassMaster" className="cursor-pointer text-sm">
                      Assign as Class Master
                    </Label>
                  </div>

                  {isClassMaster && (
                    <div className="space-y-2 ml-6">
                      <Label>Select Class</Label>
                      <Select value={classMasterOf} onValueChange={setClassMasterOf} required={isClassMaster}>
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Select a class" />
                        </SelectTrigger>
                        <SelectContent>
                          {classes.map((cls) => (
                            <SelectItem key={cls.id} value={cls.id.toString()}>
                              {cls.class_name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  )}

                  <p className="text-xs text-green-700">
                    Class masters can mark attendance for their assigned class and update their signature on result sheets.
                  </p>
                </div>

                {/* Exam Officer Options */}
                <div className="space-y-3 p-4 bg-blue-50 rounded-md border border-blue-200">
                  <h4 className="font-semibold text-sm text-blue-900">Exam Officer Designation (Optional)</h4>
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="isExamOfficer"
                      checked={isExamOfficer}
                      onCheckedChange={(value) => {
                        setIsExamOfficer(value);
                        if (!value) setCanApproveResults(false);
                      }}
                    />
                    <Label htmlFor="isExamOfficer" className="cursor-pointer text-sm">
                      Designate as Exam Officer
                    </Label>
                  </div>

                  {isExamOfficer && (
                    <div className="flex items-center space-x-2 ml-6">
                      <Checkbox
                        id="canApproveResults"
                        checked={canApproveResults}
                        onCheckedChange={(value) => setCanApproveResults(value)}
                      />
                      <Label htmlFor="canApproveResults" className="cursor-pointer text-sm">
                        Can approve exam results
                      </Label>
                    </div>
                  )}

                  <p className="text-xs text-blue-700">
                    Exam officers review and approve grades uploaded by teachers before publication.
                  </p>
                </div>
              </>
            )}

            <Button type="submit" disabled={loader} className="w-full">
              {loader ? (
                <MdRotateRight className="w-5 h-5 animate-spin" />
              ) : checked ? (
                "Assign"
              ) : (
                "Register"
              )}
            </Button>
          </form>
        </CardContent>
      </Card>
      <Popup
        message={message}
        setShowPopup={setShowPopup}
        showPopup={showPopup}
      />
    </div>
  );
};

export default AddTeacher;
