import React from "react";
import {
  BrowserRouter,
  HashRouter,
  Routes,
  Route,
  Navigate,
} from "react-router-dom";
import { useSelector } from "react-redux";
import { isHistorySupported } from "../../polyfills";
import Homepage from "../Homepage";
import AdminDashboard from "./AdminDashboard";
import StudentDashboard from "../student/StudentDashboard";
import TeacherDashboard from "../teacher/TeacherDashboard";
import LoginPage from "../LoginPage";
import AdminRegisterPage from "./AdminRegisterPage";
import ChooseUser from "../ChooseUser";
import PublicResultChecker from "../PublicResultChecker";
import ForgotPassword from "../ForgotPassword";
import ResetPassword from "../ResetPassword";

// Parent Portal
import ParentLogin from "../parent/ParentLogin";
import ParentRegister from "../parent/ParentRegister";
import ParentDashboard from "../parent/ParentDashboard";
import LinkChild from "../parent/LinkChild";
import ParentNotifications from "../parent/ParentNotifications";
import ChildProfile from "../parent/ChildProfile";
import ParentCommunications from "../parent/ParentCommunications";

// Medical Portal
import MedicalLogin from "../medical/MedicalLogin";
import MedicalDashboard from "../medical/MedicalDashboard";
import CreateMedicalRecord from "../medical/CreateMedicalRecord";
import MedicalStudentSearch from "../medical/MedicalStudentSearch";

// Exam Officer Portal
import ExamOfficerLogin from "../examOfficer/ExamOfficerLogin";
import ExamOfficerDashboard from "../examOfficer/ExamOfficerDashboard";

// House Management
import RegisterStudentToHouse from "../house/RegisterStudentToHouse";

const App = () => {
  const { currentRole } = useSelector((state) => state.user);

  const RouterComponent = isHistorySupported ? BrowserRouter : HashRouter;

  return (
    <RouterComponent>
      {currentRole === null && (
        <Routes>
          <Route path="/" element={<Homepage />} />
          <Route path="/choose" element={<ChooseUser />} />

          <Route path="/Adminlogin" element={<LoginPage role="Admin" />} />
          <Route path="/Studentlogin" element={<LoginPage role="Student" />} />
          <Route path="/Teacherlogin" element={<LoginPage role="Teacher" />} />

          <Route path="/Adminregister" element={<AdminRegisterPage />} />

          {/* Password Reset Routes */}
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/forgot-password/:role" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />

          {/* Parent Portal Routes */}
          <Route path="/parent/login" element={<ParentLogin />} />
          <Route path="/parent/register" element={<ParentRegister />} />
          <Route path="/parent/dashboard" element={<ParentDashboard />} />
          <Route path="/parent/link-child" element={<LinkChild />} />
          <Route path="/parent/notifications" element={<ParentNotifications />} />
          <Route path="/parent/child/:id" element={<ChildProfile />} />
          <Route path="/parent/communications" element={<ParentCommunications />} />

          {/* Medical Portal Routes */}
          <Route path="/medical/login" element={<MedicalLogin />} />
          <Route path="/medical/dashboard" element={<MedicalDashboard />} />
          <Route path="/medical/create-record" element={<CreateMedicalRecord />} />
          <Route path="/medical/search-student" element={<MedicalStudentSearch />} />

          {/* Exam Officer Portal */}
          <Route path="/ExamOfficer" element={<ExamOfficerLogin />} />
          <Route path="/ExamOfficer/dashboard" element={<ExamOfficerDashboard />} />

          {/* House Management Routes */}
          <Route path="/house/register-student" element={<RegisterStudentToHouse />} />

          {/* Public Result Checker - No authentication required */}
          <Route path="/check-results" element={<PublicResultChecker />} />

          <Route path="*" element={<Navigate to="/" />} />
        </Routes>
      )}

      {(currentRole === "Admin" || currentRole === "Principal") && (
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
    </RouterComponent>
  );
};

export default App;
