import * as React from "react";
import { Link, useLocation } from "react-router-dom";
import {
  MdHome,
  MdExitToApp,
  MdAccountCircle,
  MdAnnouncement,
  MdClass,
  MdAssignment,
  MdAssessment,
  MdCalendarToday,
  MdMessage
} from "react-icons/md";

const StudentSideBar = () => {
  const location = useLocation();

  const isActive = (path) => location.pathname === path || location.pathname.startsWith(path);

  return (
    <div className="flex flex-col h-full">
      <nav className="flex flex-col space-y-1 p-4">
        <Link
          to="/"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/") || isActive("/Student/dashboard")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdHome className="w-5 h-5" />
          <span>Home</span>
        </Link>

        <Link
          to="/Student/subjects"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/Student/subjects")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdAssignment className="w-5 h-5" />
          <span>Subjects</span>
        </Link>

        <Link
          to="/Student/attendance"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/Student/attendance")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdClass className="w-5 h-5" />
          <span>Attendance</span>
        </Link>

        <Link
          to="/Student/results"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/Student/results")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdAssessment className="w-5 h-5" />
          <span>My Results</span>
        </Link>

        <Link
          to="/Student/timetable"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/Student/timetable")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdCalendarToday className="w-5 h-5" />
          <span>Timetable</span>
        </Link>

        <Link
          to="/Student/complain"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/Student/complain") && !isActive("/Student/complaints")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdAnnouncement className="w-5 h-5" />
          <span>Submit Complaint</span>
        </Link>

        <Link
          to="/Student/complaints"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/Student/complaints")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdMessage className="w-5 h-5" />
          <span>My Complaints</span>
        </Link>

        <div className="border-t my-2"></div>

        <div className="pt-2">
          <h3 className="px-4 text-sm font-semibold text-muted-foreground mb-2">
            User
          </h3>
          <div className="space-y-1">
            <Link
              to="/Student/profile"
              className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
                isActive("/Student/profile")
                  ? "bg-primary text-primary-foreground"
                  : "hover:bg-accent hover:text-accent-foreground"
              }`}
            >
              <MdAccountCircle className="w-5 h-5" />
              <span>Profile</span>
            </Link>
            <Link
              to="/logout"
              className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
                isActive("/logout")
                  ? "bg-primary text-primary-foreground"
                  : "hover:bg-accent hover:text-accent-foreground"
              }`}
            >
              <MdExitToApp className="w-5 h-5" />
              <span>Logout</span>
            </Link>
          </div>
        </div>
      </nav>
    </div>
  );
};

export default StudentSideBar;
