import * as React from "react";
import { Link, useLocation } from "react-router-dom";
import { cn } from "@/lib/utils";
import { Separator } from "@/components/ui/separator";
import { Badge } from "@/components/ui/badge";
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
  FiChevronDown,
  FiChevronRight,
  FiMapPin,
} from "react-icons/fi";
import { MdVpnKey } from "react-icons/md";
import BoSchoolLogo from "@/assets/Bo-School-logo.png";
import axios from "axios";

const SideBar = () => {
  const location = useLocation();
  const [notificationCount, setNotificationCount] = React.useState(0);
  const [openSections, setOpenSections] = React.useState({
    academic: true,
    results: false,
    financial: false,
    communication: false,
    system: false,
  });

  React.useEffect(() => {
    fetchNotificationCount();
    // Poll every 30 seconds for new notifications
    const interval = setInterval(fetchNotificationCount, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchNotificationCount = async () => {
    try {
      const response = await axios.get('/notifications/unread-count');
      if (response.data.success) {
        setNotificationCount(response.data.unread_count || 0);
      }
    } catch (error) {
      console.error('Failed to fetch notification count:', error);
      setNotificationCount(0);
    }
  };

  const toggleSection = (section) => {
    setOpenSections((prev) => ({ ...prev, [section]: !prev[section] }));
  };

  const NavItem = ({ to, icon: Icon, children, startsWith, badge }) => {
    const isActive = startsWith
      ? location.pathname.startsWith(to)
      : location.pathname === to;

    return (
      <Link
        to={to}
        className={cn(
          "flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs transition-all hover:bg-purple-50 dark:hover:bg-purple-950",
          isActive
            ? "bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 font-semibold"
            : "text-gray-700 dark:text-gray-300"
        )}
      >
        <Icon className={cn("h-4 w-4 flex-shrink-0", isActive && "text-purple-600")} />
        <span className="truncate flex-1">{children}</span>
        {badge && (
          <Badge variant="destructive" className="h-4 px-1 text-[10px]">
            {badge}
          </Badge>
        )}
      </Link>
    );
  };

  const SectionHeader = ({ isOpen, onClick, children }) => (
    <button
      onClick={onClick}
      className="flex items-center justify-between w-full px-3 py-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-wider hover:text-gray-700 transition-colors"
    >
      <span>{children}</span>
      {isOpen ? <FiChevronDown className="h-3 w-3" /> : <FiChevronRight className="h-3 w-3" />}
    </button>
  );

  return (
    <div className="flex flex-col h-full">
      <div className="flex flex-col items-center gap-1 px-4 pt-6 pb-4 text-center">
        <img
          src={BoSchoolLogo}
          alt="Bo School crest"
          className="h-14 w-auto drop-shadow-[0_10px_20px_rgba(0,0,0,0.45)]"
        />
        <p className="text-xs uppercase tracking-[0.4em] text-amber-500">Bo School</p>
        <p className="text-[10px] text-gray-500">Manners Maketh Man</p>
      </div>
      <Separator className="mb-2" />
      {/* Quick Access */}
      <div className="px-2 py-2 space-y-0.5">
        <NavItem to="/" icon={FiHome} startsWith={false}>
          Home
        </NavItem>
        <NavItem to="/Admin/notifications" icon={FiBell} startsWith={true} badge={notificationCount > 0 ? notificationCount : null}>
          Notifications
        </NavItem>
        <NavItem to="/Admin/users" icon={FiUsers} startsWith={true}>
          Users
        </NavItem>
      </div>

      <Separator className="my-1" />

      {/* Scrollable Content */}
      <div className="flex-1 overflow-y-auto px-2 space-y-1">
        {/* Academic Management */}
        <div>
          <SectionHeader
            isOpen={openSections.academic}
            onClick={() => toggleSection("academic")}
          >
            Academic
          </SectionHeader>
          {openSections.academic && (
            <div className="space-y-0.5 mt-1">
              <NavItem to="/Admin/classes-management" icon={FiBook} startsWith={true}>
                Classes
              </NavItem>
              <NavItem to="/Admin/academic-years" icon={FiCalendar} startsWith={true}>
                Academic Years
              </NavItem>
              <NavItem to="/Admin/subjects-management" icon={FiBook} startsWith={true}>
                Subjects
              </NavItem>
              <NavItem to="/Admin/teachers-management" icon={FiUserCheck} startsWith={true}>
                Teachers
              </NavItem>
              <NavItem to="/Admin/students-management" icon={FiUsers} startsWith={true}>
                Students
              </NavItem>
              <NavItem to="/Admin/town-master" icon={FiMapPin} startsWith={true}>
                Town Master
              </NavItem>
              <NavItem to="/Admin/attendance" icon={FiClipboard} startsWith={true}>
                Attendance
              </NavItem>
              <NavItem to="/Admin/timetable" icon={FiClock} startsWith={true}>
                Timetable
              </NavItem>
            </div>
          )}
        </div>

        {/* Results & Grading */}
        <div>
          <SectionHeader
            isOpen={openSections.results}
            onClick={() => toggleSection("results")}
          >
            Results & Grading
          </SectionHeader>
          {openSections.results && (
            <div className="space-y-0.5 mt-1">
              <NavItem to="/Admin/grading-system" icon={FiAward} startsWith={true}>
                Grading System
              </NavItem>
              <NavItem to="/Admin/result-pins" icon={MdVpnKey} startsWith={true}>
                Result PINs
              </NavItem>
              <NavItem to="/Admin/publish-results" icon={FiSend} startsWith={true}>
                Publish Results
              </NavItem>
              <NavItem to="/Admin/all-results" icon={FiFileText} startsWith={true}>
                View All Results
              </NavItem>
            </div>
          )}
        </div>

        {/* Financial Management */}
        <div>
          <SectionHeader
            isOpen={openSections.financial}
            onClick={() => toggleSection("financial")}
          >
            Financial
          </SectionHeader>
          {openSections.financial && (
            <div className="space-y-0.5 mt-1">
              <NavItem to="/Admin/fees" icon={FiDollarSign} startsWith={true}>
                Fees Structure
              </NavItem>
              <NavItem to="/Admin/payments" icon={FiCreditCard} startsWith={true}>
                Payments & Finance
              </NavItem>
              <NavItem to="/Admin/reports" icon={FiBarChart2} startsWith={true}>
                Reports
              </NavItem>
            </div>
          )}
        </div>

        {/* Communication */}
        <div>
          <SectionHeader
            isOpen={openSections.communication}
            onClick={() => toggleSection("communication")}
          >
            Communication
          </SectionHeader>
          {openSections.communication && (
            <div className="space-y-0.5 mt-1">
              <NavItem to="/Admin/notices" icon={FiBell} startsWith={true}>
                Notices
              </NavItem>
              <NavItem to="/Admin/complains" icon={FiAlertCircle} startsWith={true}>
                Complaints
              </NavItem>
            </div>
          )}
        </div>

        {/* System & Settings */}
        <div>
          <SectionHeader
            isOpen={openSections.system}
            onClick={() => toggleSection("system")}
          >
            System
          </SectionHeader>
          {openSections.system && (
            <div className="space-y-0.5 mt-1">
              <NavItem to="/Admin/users" icon={FiUsers} startsWith={true}>
                Users
              </NavItem>
              <NavItem to="/Admin/reports" icon={FiBarChart2} startsWith={true}>
                Reports
              </NavItem>
              <NavItem to="/Admin/settings" icon={FiSettings} startsWith={true}>
                System Settings
              </NavItem>
              <NavItem to="/Admin/activity-logs" icon={FiActivity} startsWith={true}>
                Activity Logs
              </NavItem>
            </div>
          )}
        </div>
      </div>

      <Separator className="my-1" />

      {/* User Section - Always Visible */}
      <div className="px-2 pb-2 space-y-0.5">
        <div className="px-3 py-1">
          <h3 className="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
            Account
          </h3>
        </div>
        <NavItem to="/Admin/profile" icon={FiUser} startsWith={true}>
          Profile
        </NavItem>
        <NavItem to="/logout" icon={FiLogOut} startsWith={true}>
          Logout
        </NavItem>
      </div>
    </div>
  );
};

export default SideBar;
