import { useEffect, useState } from "react";
import axios from "@/redux/axiosConfig";
import { useSelector } from "react-redux";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { BookOpen, Users } from "lucide-react";

export default function TeacherClasses() {
  const { currentUser } = useSelector((state) => state.user);
  const [subjects, setSubjects] = useState([]);
  const [loading, setLoading] = useState(false);
  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";
  const teacherId = currentUser?.teacher?.id || currentUser?._id || currentUser?.id;

  useEffect(() => {
    const fetchSubjects = async () => {
      if (!teacherId) return;
      setLoading(true);
      try {
        const res = await axios.get(`${API_URL}/teachers/${teacherId}/subjects`);
        if (res.data?.success) {
          setSubjects(res.data.subjects || []);
        }
      } catch (error) {
        console.error("Failed to load subjects", error);
      } finally {
        setLoading(false);
      }
    };
    fetchSubjects();
  }, [teacherId, API_URL]);

  return (
    <div className="container mx-auto p-6 max-w-5xl space-y-4">
      <div className="flex items-center gap-3">
        <BookOpen className="w-7 h-7 text-purple-600" />
        <div>
          <h1 className="text-2xl font-bold text-gray-900">My Subjects</h1>
          <p className="text-sm text-gray-600">Subjects you are currently assigned to teach.</p>
        </div>
      </div>

      {loading ? (
        <div className="text-center text-gray-500 py-8">Loading subjects...</div>
      ) : subjects.length === 0 ? (
        <Card>
          <CardContent className="py-8 text-center text-gray-500">No subjects assigned yet.</CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {subjects.map((subj) => (
            <Card key={subj.id}>
              <CardHeader>
                <CardTitle className="flex items-center justify-between">
                  <span>{subj.subject_name || subj.name}</span>
                  <Badge variant="secondary">{subj.class_name || "Class"}</Badge>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2 text-sm text-gray-700">
                <div className="flex items-center gap-2">
                  <Users className="w-4 h-4 text-gray-500" />
                  <span>Class: {subj.class_name || "N/A"}</span>
                </div>
                {subj.section && <div className="text-gray-500">Section: {subj.section}</div>}
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
