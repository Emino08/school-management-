import React from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useSelector } from 'react-redux';

const StudentProfile = () => {
  const { currentUser, response, error } = useSelector((state) => state.user);

  if (response) { console.log(response) }
  else if (error) { console.log(error) }

  const sclassName = currentUser.sclassName
  const studentSchool = currentUser.school

  return (
    <div className="container mx-auto p-6 max-w-4xl">
      <div className="space-y-6">
        <Card>
          <CardContent className="pt-6">
            <div className="flex flex-col items-center space-y-4">
              <Avatar className="w-32 h-32">
                <AvatarFallback className="text-4xl">
                  {String(currentUser.name).charAt(0)}
                </AvatarFallback>
              </Avatar>
              <h2 className="text-2xl font-bold text-center">
                {currentUser.name}
              </h2>
              <p className="text-base text-center">
                Student ID No: {currentUser.rollNum || currentUser.id_number}
              </p>
              <p className="text-base text-center">
                Class: {sclassName.sclassName}
              </p>
              <p className="text-base text-center">
                School: {studentSchool.schoolName}
              </p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Personal Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <p className="text-base">
                  <strong>Date of Birth:</strong> January 1, 2000
                </p>
              </div>
              <div>
                <p className="text-base">
                  <strong>Gender:</strong> Male
                </p>
              </div>
              <div>
                <p className="text-base">
                  <strong>Email:</strong> john.doe@example.com
                </p>
              </div>
              <div>
                <p className="text-base">
                  <strong>Phone:</strong> (123) 456-7890
                </p>
              </div>
              <div>
                <p className="text-base">
                  <strong>Address:</strong> 123 Main Street, City, Country
                </p>
              </div>
              <div>
                <p className="text-base">
                  <strong>Emergency Contact:</strong> (987) 654-3210
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

export default StudentProfile
