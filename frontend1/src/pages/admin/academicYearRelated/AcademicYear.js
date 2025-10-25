import React, { useEffect, useState } from "react";
import FeedBack from "../../../components/FeedBack";
import { useDispatch, useSelector } from "react-redux";
import {
  createAcademicYear,
  getAllAcademicYears,
  setAcademicYear
} from "../../../redux/academicYearRelated/academicYearHandle";

const SelectAcademicYear = () => {
  const [selectedYear, setSelectedYear] = useState("Choose an academic year");
  const [showModal, setShowModal] = useState(false);
  const dispatch = useDispatch();
  const academicYearState = useSelector((state) => state.academicYear);
  const {
    academicYearStatus,
    academicYearMessage,
    academicYearLoading,
    academicYearError,
    academicYearData
  } = academicYearState;
  const [message, setMessage] = useState("");
  const [isSuccess, setIsSuccess] = useState("");

  const academicYears = Array.isArray(academicYearData)
    ? academicYearData.map((academicYear) => academicYear.year_name)
    : [];

  const handleCreateAcademicYear = () => {
    const currentYear = new Date().getFullYear();
    const nextYear = currentYear + 1;
    const newAcademicYear = `${currentYear}/${nextYear}`;
    dispatch(createAcademicYear({ academicYearName: newAcademicYear }));
    // setSelectedYear(newAcademicYear);
    setShowModal(true);
  };

  const handleModalClose = () => {
    setShowModal(false);
  };

  const handleSelectionChange = (e) => {
    let academicYearName = e.target.value;
    dispatch(setAcademicYear({ academicYearName }));
    setSelectedYear(e.target.value);
    setShowModal(true); // Show the modal when the selection changes
  };

  useEffect(() => {
    if (
        academicYearLoading === false &&
        academicYearMessage !== "" &&
        academicYearStatus === "succeeded"
    ) {
      setMessage(academicYearMessage);
      setIsSuccess(true);
    } else {
      setMessage(academicYearError);
      setIsSuccess(false);
    }
  }, [
    academicYearStatus,
    academicYearMessage,
    academicYearLoading,
    academicYearError
  ]);

  useEffect(() => {
    dispatch(getAllAcademicYears());
  }, []);

  return (
      <div className="flex justify-center items-center h-screen">
        <div className="bg-white p-4 rounded-lg shadow-md max-w-md w-full text-center">
          <label
              htmlFor="academicYears"
              className="block mb-2 text-sm font-medium text-gray-900"
          >
            Select an Academic Year
          </label>
          <select
              id="academicYears"
              className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              value={selectedYear}
              onChange={handleSelectionChange}
          >
            <option value="Choose an academic year" disabled>
              Choose an academic year
            </option>
            {academicYears.map((year, index) => (
                <option key={index} value={year}>
                  {year}
                </option>
            ))}
          </select>
          <div className="flex items-center mt-2">
            <div className="relative">
              <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg
                    className="w-4 h-4 text-gray-500 dark:text-gray-400"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                  <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                </svg>
              </div>
              <input
                  name="start"
                  type="date"
                  className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                  placeholder="Select date start"
              />
            </div>
            <span className="mx-4 text-gray-500">to</span>
            <div className="relative">
              <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg
                    className="w-4 h-4 text-gray-500 dark:text-gray-400"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                  <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                </svg>
              </div>
              <input
                  name="end"
                  type="date"
                  className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                  placeholder="Select date end"
              />
            </div>
          </div>
          <button
              type="button"
              className="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mt-2 dark:bg-blue-600 dark:hover-bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
              onClick={handleCreateAcademicYear}
          >
            Create Academic Year
          </button>
        </div>
        {showModal && (
            <FeedBack
                message={message}
                isSuccess={isSuccess}
                onClose={handleModalClose}
            />
        )}
      </div>
  );
};

export default SelectAcademicYear;
