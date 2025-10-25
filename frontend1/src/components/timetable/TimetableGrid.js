import React from "react";
import { Card, CardContent } from "@/components/ui/card";

const TimetableGrid = ({ groupedTimetable, isTeacher = false }) => {
  const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];

  const formatTime = (time) => {
    if (!time) return "";
    const [hours, minutes] = time.split(":");
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? "PM" : "AM";
    const formattedHour = hour % 12 || 12;
    return `${formattedHour}:${minutes} ${ampm}`;
  };

  const getSubjectColor = (subjectName) => {
    const colors = {
      Mathematics: "bg-blue-100 border-blue-300 text-blue-800",
      Math: "bg-blue-100 border-blue-300 text-blue-800",
      English: "bg-purple-100 border-purple-300 text-purple-800",
      Science: "bg-green-100 border-green-300 text-green-800",
      Physics: "bg-green-100 border-green-300 text-green-800",
      Chemistry: "bg-teal-100 border-teal-300 text-teal-800",
      Biology: "bg-lime-100 border-lime-300 text-lime-800",
      History: "bg-orange-100 border-orange-300 text-orange-800",
      Geography: "bg-yellow-100 border-yellow-300 text-yellow-800",
      "Physical Education": "bg-red-100 border-red-300 text-red-800",
      PE: "bg-red-100 border-red-300 text-red-800",
      Art: "bg-pink-100 border-pink-300 text-pink-800",
      Music: "bg-indigo-100 border-indigo-300 text-indigo-800",
      Computer: "bg-cyan-100 border-cyan-300 text-cyan-800",
      ICT: "bg-cyan-100 border-cyan-300 text-cyan-800",
    };

    for (const [key, color] of Object.entries(colors)) {
      if (subjectName?.toLowerCase().includes(key.toLowerCase())) {
        return color;
      }
    }
    return "bg-gray-100 border-gray-300 text-gray-800";
  };

  if (!groupedTimetable || Object.keys(groupedTimetable).length === 0) {
    return (
      <Card>
        <CardContent className="py-8">
          <p className="text-center text-gray-500">No timetable available</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full border-collapse">
        <thead>
          <tr className="bg-gray-100">
            <th className="border border-gray-300 p-3 text-left font-semibold w-32">
              Time
            </th>
            {days.map((day) => (
              <th key={day} className="border border-gray-300 p-3 text-center font-semibold">
                {day}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {(() => {
            // Get all unique time slots
            const timeSlots = new Set();
            Object.values(groupedTimetable).forEach((dayClasses) => {
              dayClasses.forEach((classItem) => {
                const timeKey = `${classItem.start_time}-${classItem.end_time}`;
                timeSlots.add(timeKey);
              });
            });

            const sortedTimeSlots = Array.from(timeSlots).sort((a, b) => {
              const [aStart] = a.split("-");
              const [bStart] = b.split("-");
              return aStart.localeCompare(bStart);
            });

            return sortedTimeSlots.map((timeSlot, index) => {
              const [startTime, endTime] = timeSlot.split("-");

              return (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="border border-gray-300 p-2 text-sm font-medium text-gray-700 align-top">
                    <div className="flex flex-col">
                      <span>{formatTime(startTime)}</span>
                      <span className="text-xs text-gray-500">{formatTime(endTime)}</span>
                    </div>
                  </td>
                  {days.map((day) => {
                    const dayClasses = groupedTimetable[day] || [];
                    const classItem = dayClasses.find(
                      (c) => c.start_time === startTime && c.end_time === endTime
                    );

                    return (
                      <td key={day} className="border border-gray-300 p-2 align-top">
                        {classItem ? (
                          <div
                            className={`p-2 rounded border-l-4 ${getSubjectColor(classItem.subject_name)}`}
                          >
                            <div className="font-semibold text-sm">
                              {classItem.subject_name}
                            </div>
                            {isTeacher ? (
                              <div className="text-xs mt-1">
                                {classItem.class_name}
                              </div>
                            ) : (
                              <div className="text-xs mt-1">
                                {classItem.teacher_name}
                              </div>
                            )}
                            {classItem.room_number && (
                              <div className="text-xs mt-1">
                                Room: {classItem.room_number}
                              </div>
                            )}
                          </div>
                        ) : (
                          <div className="h-16"></div>
                        )}
                      </td>
                    );
                  })}
                </tr>
              );
            });
          })()}
        </tbody>
      </table>
    </div>
  );
};

export default TimetableGrid;
