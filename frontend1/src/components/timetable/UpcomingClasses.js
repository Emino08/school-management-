import React from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Clock, MapPin, BookOpen } from "lucide-react";

const UpcomingClasses = ({ classes = [], currentTime }) => {
  if (!classes || classes.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Upcoming Classes</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-gray-500 text-center py-4">No upcoming classes today</p>
        </CardContent>
      </Card>
    );
  }

  const formatTime = (time) => {
    if (!time) return "";
    const [hours, minutes] = time.split(":");
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? "PM" : "AM";
    const formattedHour = hour % 12 || 12;
    return `${formattedHour}:${minutes} ${ampm}`;
  };

  const getTimeUntil = (startTime) => {
    if (!startTime || !currentTime) return "";
    const start = new Date(`2000-01-01T${startTime}`);
    const now = new Date(`2000-01-01T${currentTime}`);
    const diff = (start - now) / 1000 / 60; // minutes

    if (diff < 0) return "In progress";
    if (diff < 60) return `in ${Math.round(diff)} min`;
    const hours = Math.floor(diff / 60);
    const mins = Math.round(diff % 60);
    return `in ${hours}h ${mins}m`;
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg">Upcoming Classes</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {classes.map((classItem, index) => (
          <div
            key={index}
            className={`p-3 rounded-lg border ${
              index === 0
                ? "bg-blue-50 border-blue-200"
                : "bg-gray-50 border-gray-200"
            }`}
          >
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <BookOpen className="w-4 h-4 text-blue-600" />
                  <span className="font-semibold text-gray-900">
                    {classItem.subject_name}
                  </span>
                </div>
                <div className="flex items-center gap-4 text-sm text-gray-600">
                  <span className="flex items-center gap-1">
                    <Clock className="w-3 h-3" />
                    {formatTime(classItem.start_time)} - {formatTime(classItem.end_time)}
                  </span>
                  {classItem.room_number && (
                    <span className="flex items-center gap-1">
                      <MapPin className="w-3 h-3" />
                      {classItem.room_number}
                    </span>
                  )}
                </div>
                {classItem.class_name && (
                  <div className="text-xs text-gray-500 mt-1">
                    Class: {classItem.class_name}
                  </div>
                )}
              </div>
              {index === 0 && (
                <span className="text-xs font-medium text-blue-600 bg-blue-100 px-2 py-1 rounded">
                  {getTimeUntil(classItem.start_time)}
                </span>
              )}
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );
};

export default UpcomingClasses;
