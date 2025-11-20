import { useEffect, useState } from 'react';
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { MdDelete, MdPostAdd, MdPersonAddAlt1, MdAddCard, MdMoreVert } from "react-icons/md";
import { ArrowLeft } from "lucide-react";
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { deleteUser } from '../../../redux/userRelated/userHandle';
import { getAllSclasses } from '../../../redux/sclassRelated/sclassHandle';
import TableTemplate from '../../../components/TableTemplate';
import SpeedDialTemplate from '../../../components/SpeedDialTemplate';

const ShowClasses = () => {
  const navigate = useNavigate()
  const dispatch = useDispatch();

  const { sclassesList, loading, error, getresponse } = useSelector((state) => state.sclass);
  const { currentUser } = useSelector(state => state.user)

  const adminID = currentUser?._id

  useEffect(() => {
    if (adminID) {
      dispatch(getAllSclasses(adminID, "Sclass"));
    }
  }, [adminID, dispatch]);

  if (error) {
    console.log(error)
  }

  const deleteHandler = (deleteID, address) => {
    if (adminID) {
      dispatch(deleteUser(deleteID, address))
        .then(() => {
          dispatch(getAllSclasses(adminID, "Sclass"));
        })
    }
  }

  const sclassColumns = [
    { id: 'name', label: 'Class Name', minWidth: 170 },
    { id: 'gradeLevel', label: 'Grade Level', minWidth: 100 },
    { id: 'studentCount', label: 'Students', minWidth: 100 },
    { id: 'subjectCount', label: 'Subjects', minWidth: 100 },
  ]

  const sclassRows = sclassesList && sclassesList.length > 0 && sclassesList.map((sclass) => {
    return {
      name: sclass.sclassName || sclass.class_name,
      gradeLevel: sclass.grade_level || 'N/A',
      studentCount: sclass.student_count || 0,
      subjectCount: sclass.subject_count || 0,
      id: sclass._id || sclass.id,
    };
  })

  const SclassButtonHaver = ({ row }) => {
    const actions = [
      { icon: <MdPostAdd className="w-4 h-4" />, name: 'Add Subjects', action: () => navigate("/Admin/addsubject/" + row.id) },
      { icon: <MdPersonAddAlt1 className="w-4 h-4" />, name: 'Add Student', action: () => navigate("/Admin/class/addstudents/" + row.id) },
    ];
    return (
      <div className="flex items-center justify-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => deleteHandler(row.id, "Sclass")}
        >
          <MdDelete className="w-5 h-5 text-red-600" />
        </Button>
        <Button
          variant="default"
          onClick={() => navigate("/Admin/classes/class/" + row.id)}
        >
          View
        </Button>
        <ActionMenu actions={actions} />
      </div>
    );
  };

  const ActionMenu = ({ actions }) => {
    return (
      <DropdownMenu>
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                  <span className="text-sm mr-1">Add</span>
                  <MdMoreVert className="w-5 h-5" />
                </Button>
              </DropdownMenuTrigger>
            </TooltipTrigger>
            <TooltipContent>
              <p>Add Students & Subjects</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
        <DropdownMenuContent align="end" className="w-48">
          {actions.map((action, index) => (
            <DropdownMenuItem key={index} onClick={action.action}>
              <span className="mr-2">{action.icon}</span>
              {action.name}
            </DropdownMenuItem>
          ))}
        </DropdownMenuContent>
      </DropdownMenu>
    );
  }

  const actions = [
    {
      icon: <MdAddCard className="w-5 h-5 text-blue-600" />, name: 'Add New Class',
      action: () => navigate("/Admin/addclass")
    },
    {
      icon: <MdDelete className="w-5 h-5 text-red-600" />, name: 'Delete All Classes',
      action: () => deleteHandler(adminID, "Sclasses")
    },
  ];

  // Calculate statistics
  const totalClasses = Array.isArray(sclassesList) ? sclassesList.length : 0;
  const totalStudents = Array.isArray(sclassesList)
    ? sclassesList.reduce((sum, sclass) => sum + (sclass.student_count || 0), 0)
    : 0;
  const totalSubjects = Array.isArray(sclassesList)
    ? sclassesList.reduce((sum, sclass) => sum + (sclass.subject_count || 0), 0)
    : 0;

  return (
    <div className="space-y-4">
      <div className="px-6 pt-4">
        <Button
          type="button"
          variant="ghost"
          size="sm"
          className="gap-2 text-gray-600"
          onClick={() => navigate(-1)}
        >
          <ArrowLeft className="w-4 h-4" />
          Back
        </Button>
      </div>
      {loading ? (
        <div className="flex justify-center items-center h-64">
          <div className="text-lg">Loading classes...</div>
        </div>
      ) : (
        <>
          {getresponse ? (
            <div className="flex flex-col items-center justify-center h-64 space-y-4">
              <div className="text-center">
                <h3 className="text-2xl font-semibold mb-2">No Classes Found</h3>
                <p className="text-gray-600 mb-4">Get started by creating your first class</p>
              </div>
              <Button variant="default" className="bg-green-600 hover:bg-green-700" onClick={() => navigate("/Admin/addclass")}>
                Add Class
              </Button>
            </div>
          ) : (
            <>
              {/* Statistics Cards */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-blue-600">Total Classes</p>
                      <p className="text-3xl font-bold text-blue-900">{totalClasses}</p>
                    </div>
                    <div className="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center">
                      <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                      </svg>
                    </div>
                  </div>
                </div>
                <div className="bg-green-50 border border-green-200 rounded-lg p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-green-600">Total Students</p>
                      <p className="text-3xl font-bold text-green-900">{totalStudents}</p>
                    </div>
                    <div className="w-12 h-12 bg-green-200 rounded-full flex items-center justify-center">
                      <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                    </div>
                  </div>
                </div>
                <div className="bg-purple-50 border border-purple-200 rounded-lg p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-purple-600">Total Subjects</p>
                      <p className="text-3xl font-bold text-purple-900">{totalSubjects}</p>
                    </div>
                    <div className="w-12 h-12 bg-purple-200 rounded-full flex items-center justify-center">
                      <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>

              {/* Classes Table */}
              {Array.isArray(sclassesList) && sclassesList.length > 0 ? (
                <>
                  <div className="mb-4">
                    <h2 className="text-2xl font-bold text-gray-900">All Classes</h2>
                    <p className="text-gray-600">Manage your school classes, students, and subjects</p>
                  </div>
                  <TableTemplate buttonHaver={SclassButtonHaver} columns={sclassColumns} rows={sclassRows} />
                </>
              ) : (
                <div className="text-center py-8">
                  <p className="text-gray-500">No classes available</p>
                </div>
              )}
              <SpeedDialTemplate actions={actions} />
            </>
          )}
        </>
      )}
    </div>
  );
};

export default ShowClasses;
