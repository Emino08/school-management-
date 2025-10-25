import React from "react";
import {
  BrowserRouter as Router,
  Routes,
  Route,
  Navigate,
} from "react-router-dom";
import { useSelector } from "react-redux";
import Homepage from "../Homepage";
import AdminDashboard from "./AdminDashboard";
import StudentDashboard from "../student/StudentDashboard";
import TeacherDashboard from "../teacher/TeacherDashboard";
import LoginPage from "../LoginPage";
import AdminRegisterPage from "./AdminRegisterPage";
import ChooseUser from "../ChooseUser";
import PublicResultChecker from "../PublicResultChecker";

const App = () => {
  const { currentRole } = useSelector((state) => state.user);

  return (
    <Router>
      {currentRole === null && (
        <Routes>
          <Route path="/" element={<Homepage />} />
          <Route path="/choose" element={<ChooseUser visitor="normal" />} />
          <Route
            path="/chooseasguest"
            element={<ChooseUser visitor="guest" />}
          />

          <Route path="/Adminlogin" element={<LoginPage role="Admin" />} />
          <Route path="/Studentlogin" element={<LoginPage role="Student" />} />
          <Route path="/Teacherlogin" element={<LoginPage role="Teacher" />} />

          <Route path="/Adminregister" element={<AdminRegisterPage />} />

          {/* Public Result Checker - No authentication required */}
          <Route path="/check-results" element={<PublicResultChecker />} />

          <Route path="*" element={<Navigate to="/" />} />
        </Routes>
      )}

      {currentRole === "Admin" && (
        <Routes>
          <Route path="/*" element={<AdminDashboard />} />
        </Routes>
      )}

      {currentRole === "Student" && (
        <Routes>
          <Route path="/*" element={<StudentDashboard />} />
        </Routes>
      )}

      {currentRole === "Teacher" && (
        <Routes>
          <Route path="/*" element={<TeacherDashboard />} />
        </Routes>
      )}
    </Router>
  );
};

export default App;
