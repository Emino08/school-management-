import { useEffect, useState } from "react";
import { MdRotateRight } from "react-icons/md";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { toast } from 'sonner';
import { useDispatch, useSelector } from "react-redux";
import axios from "axios";
import { useNavigate } from "react-router-dom";

const StudentComplain = () => {
  const [title, setTitle] = useState("");
  const [complaint, setComplaint] = useState("");
  const [category, setCategory] = useState("general");
  const [priority, setPriority] = useState("medium");
  const [teacherId, setTeacherId] = useState("");
  const [teachers, setTeachers] = useState([]);
  const [loader, setLoader] = useState(false);

  const { currentUser } = useSelector((state) => state.user);
  const navigate = useNavigate();

  useEffect(() => {
    // Fetch teachers for the optional dropdown
    fetchTeachers();
  }, []);

  const fetchTeachers = async () => {
    try {
      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/Teachers`,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        }
      );

      if (response.data.success) {
        setTeachers(response.data.teachers || []);
      }
    } catch (error) {
      console.error("Failed to fetch teachers:", error);
    }
  };

  const submitHandler = async (event) => {
    event.preventDefault();

    if (!complaint.trim()) {
      toast.error("Please enter your complaint");
      return;
    }

    setLoader(true);

    try {
      const complaintData = {
        complaint: complaint.trim(),
        category,
        priority,
      };

      // Add optional fields
      if (title.trim()) {
        complaintData.title = title.trim();
      }

      if (teacherId) {
        complaintData.teacher_id = teacherId;
        complaintData.category = "teacher"; // Auto-set category if teacher is selected
      }

      const response = await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/complaints`,
        complaintData,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
            "Content-Type": "application/json",
          },
        }
      );

      if (response.data.success) {
        toast.success("Done Successfully", {
          description: "Your complaint has been submitted.",
        });

        // Clear form
        setTitle("");
        setComplaint("");
        setCategory("general");
        setPriority("medium");
        setTeacherId("");

        // Optionally navigate to complaints list
        setTimeout(() => {
          navigate("/Student/complaints");
        }, 1500);
      }
    } catch (error) {
      toast.error("Submission Failed", {
        description:
          error.response?.data?.message || "Unable to submit your complaint.",
      });
    } finally {
      setLoader(false);
    }
  };

  return (
    <div className="flex flex-1 items-center justify-center">
      <div className="w-full max-w-[650px] px-3 py-24">
        <Card>
          <CardHeader>
            <CardTitle className="text-2xl">Submit a Complaint</CardTitle>
            <p className="text-sm text-gray-600 mt-1">
              Fill out the form below to submit your complaint to the administration.
            </p>
          </CardHeader>
          <CardContent>
            <form onSubmit={submitHandler} className="space-y-5">
              {/* Title (Optional) */}
              <div className="space-y-2">
                <Label htmlFor="title">
                  Title <span className="text-gray-500 text-sm">(Optional)</span>
                </Label>
                <Input
                  id="title"
                  type="text"
                  placeholder="Brief subject of your complaint"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                />
              </div>

              {/* Category */}
              <div className="space-y-2">
                <Label htmlFor="category">Category</Label>
                <Select value={category} onValueChange={setCategory}>
                  <SelectTrigger id="category">
                    <SelectValue placeholder="Select category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="general">General</SelectItem>
                    <SelectItem value="academic">Academic</SelectItem>
                    <SelectItem value="disciplinary">Disciplinary</SelectItem>
                    <SelectItem value="facilities">Facilities</SelectItem>
                    <SelectItem value="teacher">Teacher Related</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* Teacher Selection (Optional - only show if category is teacher) */}
              {category === "teacher" && (
                <div className="space-y-2">
                  <Label htmlFor="teacher">
                    Select Teacher{" "}
                    <span className="text-gray-500 text-sm">(Optional)</span>
                  </Label>
                  <Select value={teacherId} onValueChange={setTeacherId}>
                    <SelectTrigger id="teacher">
                      <SelectValue placeholder="Select a teacher (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">None</SelectItem>
                      {teachers.map((teacher) => (
                        <SelectItem key={teacher.id} value={teacher.id.toString()}>
                          {teacher.name} - {teacher.subject_name || "Multiple Subjects"}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <p className="text-xs text-gray-500">
                    Select a specific teacher if this complaint is about them
                  </p>
                </div>
              )}

              {/* Priority */}
              <div className="space-y-2">
                <Label htmlFor="priority">Priority Level</Label>
                <Select value={priority} onValueChange={setPriority}>
                  <SelectTrigger id="priority">
                    <SelectValue placeholder="Select priority" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">Low</SelectItem>
                    <SelectItem value="medium">Medium</SelectItem>
                    <SelectItem value="high">High</SelectItem>
                    <SelectItem value="urgent">Urgent</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* Complaint Text */}
              <div className="space-y-2">
                <Label htmlFor="complaint">
                  Complaint Details <span className="text-red-500">*</span>
                </Label>
                <Textarea
                  id="complaint"
                  placeholder="Describe your complaint in detail..."
                  value={complaint}
                  onChange={(e) => setComplaint(e.target.value)}
                  required
                  rows={5}
                />
                <p className="text-xs text-gray-500">
                  Please provide as much detail as possible to help us address your
                  concern
                </p>
              </div>

              {/* Buttons */}
              <div className="flex gap-3">
                <Button
                  type="button"
                  variant="outline"
                  className="flex-1"
                  onClick={() => navigate("/Student/complaints")}
                  disabled={loader}
                >
                  View My Complaints
                </Button>
                <Button
                  type="submit"
                  disabled={loader}
                  className="flex-1 bg-blue-600 hover:bg-blue-700"
                >
                  {loader ? (
                    <MdRotateRight className="w-6 h-6 animate-spin" />
                  ) : (
                    "Submit Complaint"
                  )}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default StudentComplain;
