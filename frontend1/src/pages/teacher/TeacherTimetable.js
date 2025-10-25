import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { getTeacherTimetable, getUpcomingClasses } from "../../redux/timetableRelated/timetableHandle";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import UpcomingClasses from "../../components/timetable/UpcomingClasses";
import TimetableGrid from "../../components/timetable/TimetableGrid";
import { Calendar, Clock, Grid } from "lucide-react";

const TeacherTimetable = () => {
  const dispatch = useDispatch();
  const { currentUser } = useSelector((state) => state.user);
  const { groupedTimetable, upcomingClasses, loading } = useSelector(
    (state) => state.timetable
  );

  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    if (currentUser?.id) {
      dispatch(getTeacherTimetable(currentUser.id));
      dispatch(getUpcomingClasses(5));
    }

    // Update current time every minute
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 60000);

    return () => clearInterval(timer);
  }, [dispatch, currentUser?.id]);

  const getTodayDay = () => {
    const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    return days[new Date().getDay()];
  };

  const todayClasses = groupedTimetable[getTodayDay()] || [];
  const currentTimeString = currentTime.toLocaleTimeString("en-US", {
    hour12: false,
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });

  if (loading) {
    return (
      <div className="container max-w-7xl mx-auto mt-8 mb-8 px-4">
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="container max-w-7xl mx-auto mt-8 mb-8 px-4">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">My Timetable</h1>
        <p className="text-gray-600 mt-1">View your weekly schedule and upcoming classes</p>
      </div>

      {/* Today's Overview */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div className="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Clock className="w-5 h-5" />
                Today's Schedule - {getTodayDay()}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {todayClasses.length > 0 ? (
                <div className="space-y-3">
                  {todayClasses.map((classItem, index) => {
                    const isPast = classItem.end_time < currentTimeString;
                    const isCurrent =
                      classItem.start_time <= currentTimeString &&
                      classItem.end_time > currentTimeString;

                    return (
                      <div
                        key={index}
                        className={`p-4 rounded-lg border ${
                          isCurrent
                            ? "bg-green-50 border-green-300"
                            : isPast
                            ? "bg-gray-50 border-gray-200 opacity-60"
                            : "bg-blue-50 border-blue-200"
                        }`}
                      >
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                              <span className="font-semibold text-lg">
                                {classItem.subject_name}
                              </span>
                              {isCurrent && (
                                <span className="text-xs bg-green-500 text-white px-2 py-1 rounded">
                                  Now
                                </span>
                              )}
                            </div>
                            <div className="text-sm text-gray-600">
                              Class: {classItem.class_name}
                            </div>
                            {classItem.room_number && (
                              <div className="text-sm text-gray-600">
                                Room: {classItem.room_number}
                              </div>
                            )}
                          </div>
                          <div className="text-right">
                            <div className="text-sm font-medium">
                              {classItem.start_time.substring(0, 5)} -{" "}
                              {classItem.end_time.substring(0, 5)}
                            </div>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-center text-gray-500 py-8">
                  No classes scheduled for today
                </p>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="lg:col-span-1">
          <UpcomingClasses classes={upcomingClasses} currentTime={currentTimeString} />
        </div>
      </div>

      {/* Weekly Timetable Tabs */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Calendar className="w-5 h-5" />
            Weekly Schedule
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Tabs defaultValue="grid" className="w-full">
            <TabsList className="grid w-full max-w-md grid-cols-2 mb-4">
              <TabsTrigger value="grid" className="flex items-center gap-2">
                <Grid className="w-4 h-4" />
                Grid View
              </TabsTrigger>
              <TabsTrigger value="list" className="flex items-center gap-2">
                <Calendar className="w-4 h-4" />
                List View
              </TabsTrigger>
            </TabsList>

            <TabsContent value="grid">
              <TimetableGrid groupedTimetable={groupedTimetable} isTeacher={true} />
            </TabsContent>

            <TabsContent value="list">
              <div className="space-y-6">
                {["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"].map((day) => {
                  const dayClasses = groupedTimetable[day] || [];

                  return (
                    <div key={day}>
                      <h3 className="text-lg font-semibold mb-3 text-gray-900">
                        {day}
                      </h3>
                      {dayClasses.length > 0 ? (
                        <div className="space-y-2">
                          {dayClasses.map((classItem, index) => (
                            <div
                              key={index}
                              className="flex items-center gap-4 p-3 bg-gray-50 rounded-lg border border-gray-200"
                            >
                              <div className="text-sm font-medium text-gray-700 min-w-[100px]">
                                {classItem.start_time.substring(0, 5)} -{" "}
                                {classItem.end_time.substring(0, 5)}
                              </div>
                              <div className="flex-1">
                                <div className="font-semibold">{classItem.subject_name}</div>
                                <div className="text-sm text-gray-600">
                                  {classItem.class_name}
                                  {classItem.room_number && ` • Room ${classItem.room_number}`}
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="text-gray-500 text-sm italic">No classes scheduled</p>
                      )}
                    </div>
                  );
                })}
              </div>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
};

export default TeacherTimetable;
