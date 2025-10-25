import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate } from "react-router-dom";
import { getAllTeachers } from "../../../redux/teacherRelated/teacherHandle";
import { Button } from "@/components/ui/button";
import { deleteUser } from "../../../redux/userRelated/userHandle";
import { MdPersonRemove, MdPersonAddAlt1, MdEdit, MdDelete, MdDeleteForever, MdRestore, MdWarning } from "react-icons/md";
import SpeedDialTemplate from "../../../components/SpeedDialTemplate";
import Modal from "../../../components/Modal";
import EditTeacherModal from "../../../components/modals/EditTeacherModal";
import axios from "axios";
import { toast } from "sonner";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";

const ShowTeachers = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { teachersList, loading, error, response } = useSelector(
    (state) => state.teacher,
  );
  const { currentUser } = useSelector((state) => state.user);

  useEffect(() => {
    if (currentUser?._id) {
      dispatch(getAllTeachers(currentUser._id));
    }
  }, [currentUser?._id, dispatch]);

  console.log(teachersList);

  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [deleteMode, setDeleteMode] = useState('soft'); // 'soft' or 'hard'
  const [teacherToDelete, setTeacherToDelete] = useState(null);
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [teacherToEdit, setTeacherToEdit] = useState(null);

  const handleEditClick = (teacher) => {
    console.log('=== EDIT CLICK START ===');
    console.log('Teacher:', teacher);
    console.log('Current URL:', window.location.href);
    
    // Prevent any default behavior
    setTeacherToEdit(teacher);
    console.log('Teacher to edit set');
    
    setEditModalOpen(true);
    console.log('Modal state set to true');
    console.log('=== EDIT CLICK END ===');
  };

  const handleEditSuccess = () => {
    toast.success('Teacher updated successfully');
    dispatch(getAllTeachers(currentUser._id));
    setEditModalOpen(false);
    setTeacherToEdit(null);
  };

  const handleDeleteClick = (teacher, hardDelete = false) => {
    setTeacherToDelete(teacher);
    setDeleteMode(hardDelete ? 'hard' : 'soft');
    setDeleteDialogOpen(true);
  };

  const confirmDelete = async () => {
    if (!teacherToDelete) return;

    const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";
    const isHardDelete = deleteMode === 'hard';

    try {
      const url = isHardDelete 
        ? `${API_URL}/teachers/${teacherToDelete._id}?hard=true`
        : `${API_URL}/teachers/${teacherToDelete._id}`;

      await axios.delete(url, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });

      toast.success(
        isHardDelete 
          ? 'Teacher permanently deleted' 
          : 'Teacher deleted (can be restored)'
      );
      
      dispatch(getAllTeachers(currentUser._id));
    } catch (error) {
      toast.error('Failed to delete teacher: ' + (error.response?.data?.message || error.message));
    } finally {
      setDeleteDialogOpen(false);
      setTeacherToDelete(null);
    }
  };

  const deleteHandler = (deleteID, address) => {
    if (currentUser?._id) {
      dispatch(deleteUser(deleteID, address)).then(() => {
        dispatch(getAllTeachers(currentUser._id));
      });
    }
  };

  const actions = [
    {
      icon: <MdPersonAddAlt1 className="w-5 h-5 text-blue-600" />,
      name: "Add New Teacher",
      action: () => navigate("/Admin/teachers/chooseclass"),
    },
    {
      icon: <MdPersonRemove className="w-5 h-5 text-red-600" />,
      name: "Delete All Teachers",
      action: () => currentUser?._id && deleteHandler(currentUser._id, "Teachers"),
    },
  ];

  const [currentPage, setCurrentPage] = useState(1);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedTeacher, setSelectedTeacher] = useState(null);

  function closeModal() {
    setIsModalOpen(false);
    setSelectedTeacher(null);
  }

  const pageNumbers = [];

  const [itemsPerPage] = useState(10);
  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentItems = teachersList.slice(indexOfFirstItem, indexOfLastItem);

  for (let i = 1; i <= Math.ceil(teachersList.length / itemsPerPage); i++) {
    pageNumbers.push(i);
  }

  return (
    <>
      {/* TEST BUTTON */}
      <div className="p-4 bg-yellow-100 border border-yellow-400">
        <p className="text-sm mb-2">TEST: Click this button to test if modal works outside table</p>
        <Button
          onClick={() => {
            console.log('TEST BUTTON CLICKED');
            if (currentItems.length > 0) {
              handleEditClick(currentItems[0]);
            }
          }}
          className="bg-purple-600"
        >
          Test Edit Modal (First Teacher)
        </Button>
        <p className="text-xs mt-1">Modal open: {editModalOpen ? 'YES' : 'NO'}</p>
      </div>

      <div className="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" className="px-6 py-3">
                Name
              </th>
              <th scope="col" className="px-6 py-3">
                Email
              </th>
              <th scope="col" className="px-6 py-3">
                Phone
              </th>
              <th scope="col" className="px-6 py-3">
                Roles
              </th>
              <th scope="col" className="px-6 py-3">
                Subjects
              </th>
              <th scope="col" className="px-6 py-3">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            {currentItems.map((teacher) => (
              <tr
                key={teacher._id.toString()}
                className="bg-white border-b dark:bg-gray-900 dark:border-gray-700"
              >
                <th
                  scope="row"
                  className="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                >
                  {teacher.name}
                </th>
                <td className="px-6 py-4">{teacher.email}</td>
                <td className="px-6 py-4">{teacher.phone || '-'}</td>
                <td className="px-6 py-4">
                  <div className="flex gap-1 flex-wrap">
                    {teacher.is_class_master && (
                      <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                        Class Master
                      </span>
                    )}
                    {teacher.is_exam_officer && (
                      <span className="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-medium">
                        Exam Officer
                      </span>
                    )}
                    {!teacher.is_class_master && !teacher.is_exam_officer && (
                      <span className="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">
                        Teacher
                      </span>
                    )}
                  </div>
                </td>
                <td className="px-6 py-4">
                  {teacher.subjects || 'No subjects assigned'}
                </td>
                <td className="px-6 py-4">
                  <div className="flex items-center gap-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => {
                        setIsModalOpen(true);
                        setSelectedTeacher(teacher);
                      }}
                      className="text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                      title="View details"
                    >
                      View
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleEditClick(teacher);
                      }}
                      title="Edit teacher"
                      className="text-green-600 hover:text-green-700 hover:bg-green-50"
                    >
                      <MdEdit className="w-5 h-5" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDeleteClick(teacher, false)}
                      title="Soft delete (can restore)"
                      className="text-orange-600 hover:text-orange-700 hover:bg-orange-50"
                    >
                      <MdDelete className="w-5 h-5" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDeleteClick(teacher, true)}
                      title="Permanently delete"
                      className="text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                      <MdDeleteForever className="w-5 h-5" />
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="flex flex-col items-center mt-4">
        <span className="text-sm text-gray-700 dark:text-gray-400">
          Showing <span className="font-semibold">{indexOfFirstItem + 1}</span>
          to <span className="font-semibold">{Math.min(indexOfLastItem, teachersList.length)}</span> of
          <span className="font-semibold"> {teachersList.length}</span> Entries
        </span>
        <div className="inline-flex mt-2 xs:mt-0">
          {pageNumbers.map((pageNumber) => (
            <button
              key={pageNumber}
              onClick={() => setCurrentPage(pageNumber)}
              className={`${
                currentPage === pageNumber
                  ? "bg-gray-800 text-white hover:bg-gray-900 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700"
                  : "text-gray-700 hover:text-gray-900 dark:text-gray-400"
              } flex items-center justify-center px-3 h-8 text-sm font-medium rounded-lg`}
            >
              {pageNumber}
            </button>
          ))}
        </div>
      </div>
      <SpeedDialTemplate actions={actions} />
      
      {/* View Teacher Modal */}
      <Modal
        teacher={selectedTeacher}
        isOpen={isModalOpen}
        onClose={closeModal}
      />

      {/* Edit Teacher Modal */}
      <EditTeacherModal
        open={editModalOpen}
        onOpenChange={setEditModalOpen}
        teacher={teacherToEdit}
        onSuccess={handleEditSuccess}
      />

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent className="max-w-md">
          <AlertDialogHeader>
            <div className="flex items-center gap-3 mb-2">
              <div className={`w-12 h-12 rounded-full flex items-center justify-center ${
                deleteMode === 'hard' ? 'bg-red-100' : 'bg-orange-100'
              }`}>
                <MdWarning className={`w-6 h-6 ${
                  deleteMode === 'hard' ? 'text-red-600' : 'text-orange-600'
                }`} />
              </div>
              <AlertDialogTitle className="text-xl">
                {deleteMode === 'hard' ? 'Permanently Delete Teacher?' : 'Delete Teacher?'}
              </AlertDialogTitle>
            </div>
            <AlertDialogDescription className="text-base space-y-3 pt-2">
              {deleteMode === 'hard' ? (
                <>
                  <p className="font-semibold text-gray-900">
                    Are you sure you want to permanently delete <span className="text-red-600">{teacherToDelete?.name}</span>?
                  </p>
                  <div className="bg-red-50 border border-red-200 rounded-lg p-3 space-y-2">
                    <p className="text-sm text-red-800 font-semibold flex items-center gap-2">
                      <MdWarning className="w-4 h-4" />
                      This action cannot be undone!
                    </p>
                    <ul className="text-sm text-red-700 space-y-1 ml-6 list-disc">
                      <li>Teacher will be permanently removed</li>
                      <li>All associated data will be deleted</li>
                      <li>This action is irreversible</li>
                    </ul>
                  </div>
                </>
              ) : (
                <>
                  <p className="font-semibold text-gray-900">
                    Are you sure you want to delete <span className="text-orange-600">{teacherToDelete?.name}</span>?
                  </p>
                  <div className="bg-orange-50 border border-orange-200 rounded-lg p-3 space-y-2">
                    <p className="text-sm text-orange-800">
                      The teacher will be moved to trash and hidden from the active list.
                    </p>
                    <p className="text-sm text-orange-700 font-semibold">
                      ✓ You can restore this teacher later if needed.
                    </p>
                  </div>
                </>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter className="gap-2 sm:gap-2">
            <AlertDialogCancel className="h-10">Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={confirmDelete}
              className={`h-10 ${
                deleteMode === 'hard' 
                  ? 'bg-red-600 hover:bg-red-700 focus:ring-red-600' 
                  : 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-600'
              }`}
            >
              {deleteMode === 'hard' ? (
                <>
                  <MdDeleteForever className="w-4 h-4 mr-2" />
                  Yes, Delete Permanently
                </>
              ) : (
                <>
                  <MdDelete className="w-4 h-4 mr-2" />
                  Yes, Delete
                </>
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
};

export default ShowTeachers;
