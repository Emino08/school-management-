import React from "react";
import { Button } from "@/components/ui/button";
import { FiUserMinus } from "react-icons/fi";

function Modal({ teacher, isOpen, onClose, onDelete }) {
  if (!isOpen || !teacher) {
    return null;
  }

  function deleteHandler(id, _subject_id, _class_id) {}

  return (
    <div className="fixed z-10 inset-0 overflow-y-auto">
      <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 transition-opacity" aria-hidden="true">
          <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span
          className="hidden sm:inline-block sm:align-middle sm:h-screen"
          aria-hidden="true"
        >
          &#8203;
        </span>

        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div className="sm:flex sm:items-start">
              <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3
                  className="text-lg leading-6 font-medium text-gray-900"
                  id="modal-headline"
                >
                  {teacher.name}
                </h3>
                <div className="mt-2">
                  <div className="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                      <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                          <th scope="col" className="px-6 py-3">
                            Class
                          </th>
                          <th scope="col" className="px-6 py-3">
                            Subject name
                          </th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {teacher.teachSubjects.map((subject, i) => (
                          <tr
                            key={subject.subject_id}
                            className="bg-white border-b dark:bg-gray-900 dark:border-gray-700"
                          >
                            <th
                              scope="row"
                              className="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                            >
                              {teacher.teachClasses[i].className}
                            </th>

                            <td className="px-6 py-4">{subject.subjectName}</td>
                            <th
                              scope="row"
                              className="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                            >
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() =>
                                  deleteHandler(
                                    teacher.id,
                                    subject._subject_id,
                                    teacher.teachClasses[i]._id,
                                  )
                                }
                              >
                                <FiUserMinus className="h-5 w-5 text-red-600" />
                              </Button>
                            </th>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <Button
              onClick={onClose}
              className="w-full sm:ml-3 sm:w-auto"
            >
              Close
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Modal;
