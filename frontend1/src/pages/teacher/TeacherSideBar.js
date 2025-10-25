import * as React from "react";
import { Link, useLocation } from "react-router-dom";
import {
  MdHome,
  MdExitToApp,
  MdAccountCircle,
  MdAnnouncement,
  MdClass,
  MdAssignment,
  MdCheckCircle,
  MdCalendarToday
} from "react-icons/md";
import { useSelector } from "react-redux";

const TeacherSideBar = () => {
  const { currentUser } = useSelector((state) => state.user);
  console.log(currentUser, "currentUser");
  const location = useLocation();

  const isActive = (path) => location.pathname === path || location.pathname.startsWith(path);

  return (
    <div className="flex flex-col h-full">
      <nav className="flex flex-col space-y-1 p-4">
        <Link
          to="/"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/") || isActive("/Teacher/dashboard")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdHome className="w-5 h-5" />
          <span>Home</span>
        </Link>

        <div className="pt-4">
          <h3 className="px-4 text-sm font-semibold text-muted-foreground mb-2">
            Classes
          </h3>
          <div className="space-y-1">
            {currentUser.teachClasses?.map((classItem) => (
              <Link
                key={classItem._id}
                to={`/Teacher/class/student/${classItem._id}`}
                className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
                  isActive(`/Teacher/class/student/${classItem._id}`)
                    ? "bg-primary text-primary-foreground"
                    : "hover:bg-accent hover:text-accent-foreground"
                }`}
              >
                <MdClass className="w-5 h-5" />
                <span>{classItem.sclassName}</span>
              </Link>
            ))}
          </div>
        </div>

        <div className="border-t my-2"></div>

        <div className="pt-4">
          <h3 className="px-4 text-sm font-semibold text-muted-foreground mb-2">
            Academic
          </h3>
          <div className="space-y-1">
            <Link
              to="/Teacher/attendance"
              className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
                isActive("/Teacher/attendance")
                  ? "bg-primary text-primary-foreground"
                  : "hover:bg-accent hover:text-accent-foreground"
              }`}
            >
              <MdCheckCircle className="w-5 h-5" />
              <span>Attendance</span>
            </Link>
            <Link
              to="/Teacher/submit-grades"
              className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
                isActive("/Teacher/submit-grades")
                  ? "bg-primary text-primary-foreground"
                  : "hover:bg-accent hover:text-accent-foreground"
              }`}
            >
              <MdAssignment className="w-5 h-5" />
              <span>Submit Grades</span>
            </Link>
            <Link
              to="/Teacher/timetable"
              className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
                isActive("/Teacher/timetable")
                  ? "bg-primary text-primary-foreground"
                  : "hover:bg-accent hover:text-accent-foreground"
              }`}
            >
              <MdCalendarToday className="w-5 h-5" />
              <span>My Timetable</span>
            </Link>
          </div>
        </div>

        <Link
          to="/Teacher/complain"
          className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
            isActive("/Teacher/complain")
              ? "bg-primary text-primary-foreground"
              : "hover:bg-accent hover:text-accent-foreground"
          }`}
        >
          <MdAnnouncement className="w-5 h-5" />
          <span>Complaint</span>
        </Link>

        <div className="border-t my-2"></div>

        <div className="pt-2">
          <h3 className="px-4 text-sm font-semibold text-muted-foreground mb-2">
            User
          </h3>
          <div className="space-y-1">
            <Link
              to="/Teacher/profile"
              className={`flex items-center gap-3 px-4 py-3 rounded-md transition-colors ${
                isActive("/Teacher/profile")
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

export default TeacherSideBar;
