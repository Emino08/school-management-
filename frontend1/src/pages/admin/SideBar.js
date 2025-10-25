import * as React from "react";
import { Link, useLocation } from "react-router-dom";
import { cn } from "@/lib/utils";
import { Separator } from "@/components/ui/separator";
import {
  FiHome,
  FiUsers,
  FiLogOut,
  FiUser,
  FiBell,
  FiBook,
  FiUserCheck,
  FiAlertCircle,
  FiDollarSign,
  FiCalendar,
  FiAward,
  FiSend,
  FiFileText,
  FiClipboard,
  FiClock,
  FiBarChart2,
  FiSettings,
  FiActivity,
  FiCreditCard,
} from "react-icons/fi";
import { MdVpnKey } from "react-icons/md";

const SideBar = () => {
  const location = useLocation();

  const NavItem = ({ to, icon: Icon, children, startsWith }) => {
    const isActive = startsWith
      ? location.pathname.startsWith(to)
      : location.pathname === to;

    return (
      <Link
        to={to}
        className={cn(
          "flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all hover:bg-purple-50 dark:hover:bg-purple-950",
          isActive
            ? "bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300"
            : "text-gray-700 dark:text-gray-300"
        )}
      >
        <Icon className={cn("h-5 w-5", isActive && "text-purple-600")} />
        <span className="font-medium">{children}</span>
      </Link>
    );
  };

  return (
    <div className="flex flex-col gap-1">
      <div className="space-y-1">
        <NavItem
          to="/"
          icon={FiHome}
          startsWith={false}
        >
          Home
        </NavItem>
        <NavItem
          to="/Admin/classes-management"
          icon={FiBook}
          startsWith={true}
        >
          Classes
        </NavItem>
        <NavItem
          to="/Admin/academic-years"
          icon={FiCalendar}
          startsWith={true}
        >
          Academic Years
        </NavItem>
        <NavItem
          to="/Admin/subjects-management"
          icon={FiBook}
          startsWith={true}
        >
          Subjects
        </NavItem>
        <NavItem
          to="/Admin/teachers-management"
          icon={FiUserCheck}
          startsWith={true}
        >
          Teachers
        </NavItem>
        <NavItem
          to="/Admin/students-management"
          icon={FiUsers}
          startsWith={true}
        >
          Students
        </NavItem>
        <NavItem
          to="/Admin/attendance"
          icon={FiClipboard}
          startsWith={true}
        >
          Attendance
        </NavItem>
        <NavItem
          to="/Admin/fees"
          icon={FiDollarSign}
          startsWith={true}
        >
          Fees
        </NavItem>
        <NavItem
          to="/Admin/result-pins"
          icon={MdVpnKey}
          startsWith={true}
        >
          Result PINs
        </NavItem>
        <NavItem
          to="/Admin/grading-system"
          icon={FiAward}
          startsWith={true}
        >
          Grading System
        </NavItem>
        <NavItem
          to="/Admin/publish-results"
          icon={FiSend}
          startsWith={true}
        >
          Publish Results
        </NavItem>
        <NavItem
          to="/Admin/all-results"
          icon={FiFileText}
          startsWith={true}
        >
          View All Results
        </NavItem>
        <NavItem
          to="/Admin/notices"
          icon={FiBell}
          startsWith={true}
        >
          Notices
        </NavItem>
        <NavItem
          to="/Admin/complains"
          icon={FiAlertCircle}
          startsWith={true}
        >
          Complaints
        </NavItem>
        <NavItem
          to="/Admin/timetable"
          icon={FiClock}
          startsWith={true}
        >
          Timetable
        </NavItem>
        <NavItem
          to="/Admin/payments"
          icon={FiCreditCard}
          startsWith={true}
        >
          Payments & Finance
        </NavItem>
        <NavItem
          to="/Admin/notifications"
          icon={FiBell}
          startsWith={true}
        >
          Notifications
        </NavItem>
        <NavItem
          to="/Admin/reports"
          icon={FiBarChart2}
          startsWith={true}
        >
          Reports & Analytics
        </NavItem>
        <NavItem
          to="/Admin/settings"
          icon={FiSettings}
          startsWith={true}
        >
          System Settings
        </NavItem>
        <NavItem
          to="/Admin/activity-logs"
          icon={FiActivity}
          startsWith={true}
        >
          Activity Logs
        </NavItem>
      </div>

      <Separator className="my-2" />

      <div className="space-y-1">
        <div className="px-3 py-2">
          <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
            User
          </h3>
        </div>
        <NavItem
          to="/Admin/profile"
          icon={FiUser}
          startsWith={true}
        >
          Profile
        </NavItem>
        <NavItem
          to="/logout"
          icon={FiLogOut}
          startsWith={true}
        >
          Logout
        </NavItem>
      </div>
    </div>
  );
};

export default SideBar;
