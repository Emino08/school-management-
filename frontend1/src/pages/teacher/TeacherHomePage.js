import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import SeeNotice from "../../components/SeeNotice";
import CountUp from "react-countup";
import {
  MdPeople,
  MdMenuBook,
  MdAssignment,
  MdSchedule,
  MdTrendingUp,
  MdCheckCircle,
  MdPendingActions,
  MdAnnouncement,
  MdClass,
  MdGrade,
  MdBarChart,
} from "react-icons/md";
import {
  getClassStudents,
  getSubjectDetails,
} from "../../redux/sclassRelated/sclassHandle";
import { useDispatch, useSelector } from "react-redux";
import { useEffect, useState } from "react";
import axios from "../../redux/axiosConfig";
import useUpcomingForTeacher from "@/hooks/useUpcomingForTeacher";
import useTeacherTodaySchedule from "@/hooks/useTeacherTodaySchedule";
import { RefreshCw, Clock, CalendarDays } from "lucide-react";

const TeacherHomePage = () => {
  const dispatch = useDispatch();

  const { currentUser } = useSelector((state) => state.user);
  const { subjectDetails, sclassStudents } = useSelector(
    (state) => state.sclass,
  );

  const [dashboardStats, setDashboardStats] = useState(null);
  const [statsLoading, setStatsLoading] = useState(true);

  const classID = currentUser.teachSclass?._id;
  const subjectID = currentUser.teachSubject?._id;

  useEffect(() => {
    if (subjectID) {
      dispatch(getSubjectDetails(subjectID, "Subject"));
    }
    if (classID) {
      dispatch(getClassStudents(classID));
    }

    // Fetch dashboard statistics from backend
    const fetchDashboardStats = async () => {
      try {
        setStatsLoading(true);
        const response = await axios.get(
          `${import.meta.env.VITE_API_BASE_URL}/teachers/dashboard-stats`
        );

        if (response.data?.success) {
          setDashboardStats(response.data.stats);
        } else {
          console.warn("Dashboard stats request unsuccessful:", response.data);
        }
      } catch (error) {
        console.error("Failed to fetch dashboard stats:", error);
      } finally {
        setStatsLoading(false);
      }
    };

    fetchDashboardStats();
  }, [dispatch, subjectID, classID]);

  const numberOfStudents =
    dashboardStats?.students ?? (sclassStudents && sclassStudents.length) ?? 0;
  const numberOfSessions =
    dashboardStats?.lessons ?? (subjectDetails && subjectDetails.sessions) ?? 0;
  const numberOfTests = dashboardStats?.tests ?? 0;
  const totalHours = dashboardStats?.hours ?? 0;
  const numberOfSubjects = dashboardStats?.subjects ?? 0;
  const { upcoming, next, loading: upcomingLoading, refresh: refreshUpcoming } = useUpcomingForTeacher(5);
  const [now, setNow] = useState(new Date());
  useEffect(() => { const id = setInterval(() => setNow(new Date()), 1000); return () => clearInterval(id); }, []);
  const nextStartsIn = (() => {
    if (!next) return '';
    const [sh, sm, ss] = (next.start_time || '00:00:00').split(':').map(Number);
    const start = new Date();
    start.setHours(sh||0, sm||0, ss||0, 0);
    const diff = start - now;
    if (diff <= 0) return 'starting...';
    const totalSec = Math.floor(diff/1000);
    const h = Math.floor(totalSec / 3600);
    const m = Math.floor((totalSec % 3600) / 60);
    const s = totalSec % 60;
    if (h>0) return `${h}h ${m}m ${s}s`;
    if (m>0) return `${m}m ${s}s`;
    return `${s}s`;
  })();

  const { entries: todaySchedule, loading: todayLoading, refresh: refreshToday } = useTeacherTodaySchedule();

  return (
    <div className="container max-w-7xl mx-auto mt-8 mb-8 px-4">
      {/* Welcome Section */}
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-800">
          Welcome back, {currentUser?.name || "Teacher"}!
        </h1>
        <p className="text-gray-600 mt-1">Here's your teaching overview</p>
      </div>

      {/* Main Analytics Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {/* Total Students */}
        <Card className="hover:shadow-lg transition-shadow">
          <CardContent className="flex flex-col items-center justify-between h-[180px] p-6 text-center">
            <div className="bg-blue-100 p-3 rounded-full">
              <MdPeople className="w-8 h-8 text-blue-600" />
            </div>
            <div>
              <p className="text-gray-600 text-sm mb-1">Class Students</p>
              {statsLoading ? (
                <span className="text-2xl font-bold text-gray-400">Loading...</span>
              ) : (
                <span className="text-3xl font-bold text-blue-600">
                  <CountUp
                    end={Number(numberOfStudents)}
                    duration={1.2}
                    preserveValue
                    redraw
                  >
                    {({ countUpRef }) => <span ref={countUpRef} />}
                  </CountUp>
                </span>
              )}
              {!statsLoading && numberOfStudents === 0 && (
                <p className="text-xs text-gray-500 mt-1">No students assigned</p>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Subjects Taught */}
        <Card className="hover:shadow-lg transition-shadow">
          <CardContent className="flex flex-col items-center justify-between h-[180px] p-6 text-center">
            <div className="bg-purple-100 p-3 rounded-full">
              <MdClass className="w-8 h-8 text-purple-600" />
            </div>
            <div>
              <p className="text-gray-600 text-sm mb-1">Subjects Taught</p>
              {statsLoading ? (
                <span className="text-2xl font-bold text-gray-400">Loading...</span>
              ) : (
                <span className="text-3xl font-bold text-purple-600">
                  <CountUp
                    end={Number(numberOfSubjects)}
                    duration={1.2}
                    preserveValue
                    redraw
                  >
                    {({ countUpRef }) => <span ref={countUpRef} />}
                  </CountUp>
                </span>
              )}
              {!statsLoading && numberOfSubjects === 0 && (
                <p className="text-xs text-gray-500 mt-1">No subjects assigned</p>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Total Lessons */}
        <Card className="hover:shadow-lg transition-shadow">
          <CardContent className="flex flex-col items-center justify-between h-[180px] p-6 text-center">
            <div className="bg-green-100 p-3 rounded-full">
              <MdMenuBook className="w-8 h-8 text-green-600" />
            </div>
            <div>
              <p className="text-gray-600 text-sm mb-1">Total Lessons</p>
              {statsLoading ? (
                <span className="text-2xl font-bold text-gray-400">Loading...</span>
              ) : (
                <span className="text-3xl font-bold text-green-600">
                  <CountUp
                    end={Number(numberOfSessions)}
                    duration={1.2}
                    preserveValue
                    redraw
                  >
                    {({ countUpRef }) => <span ref={countUpRef} />}
                  </CountUp>
                </span>
              )}
              {!statsLoading && numberOfSessions === 0 && (
                <p className="text-xs text-gray-500 mt-1">No lessons planned</p>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Tests/Exams */}
        <Card className="hover:shadow-lg transition-shadow">
          <CardContent className="flex flex-col items-center justify-between h-[180px] p-6 text-center">
            <div className="bg-orange-100 p-3 rounded-full">
              <MdAssignment className="w-8 h-8 text-orange-600" />
            </div>
            <div>
              <p className="text-gray-600 text-sm mb-1">Tests Conducted</p>
              {statsLoading ? (
                <span className="text-2xl font-bold text-gray-400">Loading...</span>
              ) : (
                <span className="text-3xl font-bold text-orange-600">
                  <CountUp
                    end={Number(numberOfTests)}
                    duration={1.2}
                    preserveValue
                    redraw
                  >
                    {({ countUpRef }) => <span ref={countUpRef} />}
                  </CountUp>
                </span>
              )}
              {!statsLoading && numberOfTests === 0 && (
                <p className="text-xs text-gray-500 mt-1">No tests yet</p>
              )}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Secondary Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        {/* Teaching Hours */}
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-lg flex items-center gap-2">
              <MdSchedule className="w-5 h-5" />
              Teaching Hours
            </CardTitle>
          </CardHeader>
          <CardContent>
            {statsLoading ? (
              <div className="text-center py-4">
                <p className="text-gray-400">Loading...</p>
              </div>
            ) : (
              <div className="text-center">
                <p className="text-4xl font-bold text-indigo-600">
                  <CountUp
                    end={Number(totalHours)}
                    duration={1.2}
                    suffix=" hrs"
                    preserveValue
                    redraw
                  >
                    {({ countUpRef }) => <span ref={countUpRef} />}
                  </CountUp>
                </p>
                <p className="text-sm text-gray-600 mt-1">This academic year</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Grading Status */}
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-lg flex items-center gap-2">
              <MdGrade className="w-5 h-5" />
              Grading Status
            </CardTitle>
          </CardHeader>
          <CardContent>
            {statsLoading ? (
              <div className="text-center py-4">
                <p className="text-gray-400">Loading...</p>
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-gray-600">Graded</p>
                  <p className="text-2xl font-bold text-green-600">
                    {dashboardStats?.graded ?? 0}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Pending</p>
                  <p className="text-2xl font-bold text-yellow-600">
                    {dashboardStats?.pending_grades ?? 0}
                  </p>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Class Performance */}
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-lg flex items-center gap-2">
              <MdBarChart className="w-5 h-5" />
              Class Average
            </CardTitle>
          </CardHeader>
          <CardContent>
            {statsLoading ? (
              <div className="text-center py-4">
                <p className="text-gray-400">Loading...</p>
              </div>
            ) : dashboardStats?.class_average ? (
              <div className="text-center">
                <p
                  className={`text-4xl font-bold ${
                    dashboardStats.class_average >= 75
                      ? "text-green-600"
                      : dashboardStats.class_average >= 50
                      ? "text-blue-600"
                      : "text-orange-600"
                  }`}
                >
                  {dashboardStats.class_average.toFixed(1)}%
                </p>
                <p className="text-sm text-gray-600 mt-1">Recent exams</p>
                {dashboardStats.class_average >= 75 && (
                  <p className="text-xs text-green-600 mt-1 flex items-center justify-center gap-1">
                    <MdTrendingUp className="w-3 h-3" /> Excellent!
                  </p>
                )}
              </div>
            ) : (
              <p className="text-gray-500 text-center py-4">No data yet</p>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Teaching Info Card */}
      {currentUser?.teachSubject && (
        <Card className="mb-6 bg-gradient-to-r from-purple-50 to-blue-50 border-purple-200">
          <CardHeader className="pb-3">
            <CardTitle className="text-lg flex items-center gap-2">
              <MdClass className="w-5 h-5 text-purple-600" />
              Teaching Assignment
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p className="text-sm text-gray-600 mb-1">Primary Subject</p>
                <p className="text-lg font-semibold text-purple-700">
                  {currentUser.teachSubject?.subName || "Not Assigned"}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600 mb-1">Class</p>
                <p className="text-lg font-semibold text-purple-700">
                  {currentUser.teachSclass?.sclassName || "Not Assigned"}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600 mb-1">Teacher ID</p>
                <p className="text-lg font-semibold text-purple-700">
                  {currentUser.id_number || currentUser._id || "N/A"}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Next Periods (Timers) */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Clock className="w-5 h-5" /> Next Period
          </CardTitle>
        </CardHeader>
        <CardContent>
          {upcomingLoading ? (
            <div className="text-gray-500">Loading next period...</div>
          ) : next ? (
            <>
              {/* Primary next period */}
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                  <div className="text-sm text-gray-600">{next.day_of_week}</div>
                  <div className="text-lg font-semibold text-gray-900">{next.subject_name} • {next.class_name}</div>
                  <div className="text-sm text-gray-600">{next.start_time?.slice(0,5)} - {next.end_time?.slice(0,5)} {next.room_number ? `• Room ${next.room_number}` : ''}</div>
                </div>
                <div className="flex items-center gap-3">
                  <div className="text-emerald-700 font-medium">Starts in {nextStartsIn}</div>
                  <button className="inline-flex items-center justify-center rounded-md border px-2 py-1 text-sm" onClick={refreshUpcoming} title="Refresh next periods">
                    <RefreshCw className="w-4 h-4" />
                  </button>
                </div>
              </div>
              {/* Following periods list */}
              {upcoming.length > 1 && (
                <div className="divide-y border rounded-md">
                  {upcoming.slice(1).map((u, idx) => {
                    const [sh, sm, ss] = (u.start_time || '00:00:00').split(':').map(Number);
                    const start = new Date();
                    start.setHours(sh||0, sm||0, ss||0, 0);
                    const diff = start - now;
                    const totalSec = Math.max(0, Math.floor(diff/1000));
                    const h = Math.floor(totalSec / 3600);
                    const m = Math.floor((totalSec % 3600) / 60);
                    const s = totalSec % 60;
                    const label = diff <= 0 ? 'starting...' : (h>0 ? `${h}h ${m}m ${s}s` : (m>0 ? `${m}m ${s}s` : `${s}s`));
                    return (
                      <div key={`${u.id || idx}-${u.start_time}`} className="flex items-center justify-between p-3">
                        <div className="min-w-0">
                          <div className="text-sm text-gray-600 truncate">{u.day_of_week}</div>
                          <div className="text-sm font-medium text-gray-900 truncate">{u.subject_name} • {u.class_name}</div>
                          <div className="text-xs text-gray-600">{u.start_time?.slice(0,5)} - {u.end_time?.slice(0,5)} {u.room_number ? `• Room ${u.room_number}` : ''}</div>
                        </div>
                        <div className="text-sm text-emerald-700 whitespace-nowrap ml-3">In {label}</div>
                      </div>
                    );
                  })}
                </div>
              )}
            </>
          ) : (
            <div className="text-gray-500">No more classes scheduled today</div>
          )}
        </CardContent>
      </Card>

      {/* Today's Schedule */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <CalendarDays className="w-5 h-5" /> Today's Schedule
          </CardTitle>
        </CardHeader>
        <CardContent>
          {todayLoading ? (
            <div className="text-gray-500">Loading today&#39;s schedule...</div>
          ) : todaySchedule.length === 0 ? (
            <div className="text-gray-500">No classes scheduled for today</div>
          ) : (
            <div className="divide-y border rounded-md">
              {todaySchedule.map((e, idx) => {
                const [sh, sm, ss] = (e.start_time || '00:00:00').split(':').map(Number);
                const [eh, em, es] = (e.end_time || '00:00:00').split(':').map(Number);
                const start = new Date(); start.setHours(sh||0, sm||0, ss||0, 0);
                const end = new Date(); end.setHours(eh||0, em||0, es||0, 0);
                const nowMs = now.getTime();
                const status = nowMs >= start.getTime() && nowMs <= end.getTime() ? 'now' : (nowMs < start.getTime() ? 'upcoming' : 'ended');
                let badge = null;
                if (status === 'now') badge = <span className="ml-2 inline-block text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">Now</span>;
                if (status === 'ended') badge = <span className="ml-2 inline-block text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">Ended</span>;
                let rightLabel = '';
                if (status === 'upcoming') {
                  const diff = start - now;
                  const totalSec = Math.max(0, Math.floor(diff/1000));
                  const h = Math.floor(totalSec / 3600);
                  const m = Math.floor((totalSec % 3600) / 60);
                  const s = totalSec % 60;
                  rightLabel = h>0 ? `${h}h ${m}m ${s}s` : (m>0 ? `${m}m ${s}s` : `${s}s`);
                } else if (status === 'now') {
                  const diff = end - now; const m = Math.max(0, Math.floor(diff/60000)); rightLabel = `${m}m left`;
                }
                return (
                  <div key={`${e.id || idx}-${e.start_time}`} className={`flex items-center justify-between p-3 ${status==='now' ? 'bg-emerald-50' : ''}`}>
                    <div className="min-w-0">
                      <div className="text-sm font-medium text-gray-900 truncate">{e.subject_name} • {e.class_name} {badge}</div>
                      <div className="text-xs text-gray-600">{e.start_time?.slice(0,5)} - {e.end_time?.slice(0,5)} {e.room_number ? `• Room ${e.room_number}` : ''}</div>
                    </div>
                    <div className="flex items-center gap-3 ml-3 whitespace-nowrap">
                      <span className={`${status==='now' ? 'text-emerald-700' : 'text-gray-600'} text-sm`}>{rightLabel}</span>
                      <button className="inline-flex items-center justify-center rounded-md border px-2 py-1 text-sm" onClick={refreshToday} title="Refresh schedule">
                        <RefreshCw className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Notices Section */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MdAnnouncement className="w-6 h-6" />
            Recent Notices
          </CardTitle>
        </CardHeader>
        <CardContent>
          <SeeNotice />
        </CardContent>
      </Card>
    </div>
  );
};

export default TeacherHomePage;
