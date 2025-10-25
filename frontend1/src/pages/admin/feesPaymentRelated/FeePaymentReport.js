import React, { useState, useEffect, useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import axios from "../../../redux/axiosConfig";
import { getAllSclasses } from "../../../redux/sclassRelated/sclassHandle";
import { getAllStudents } from "../../../redux/studentRelated/studentHandle";
import {
  getPaymentReport,
  getPaymentReportByClassId,
  getPaymentReportByTermAndClassId,
  studentPaymentReport,
} from "../../../redux/feesPaymentRelated/feesPaymentHandle";

const FeePaymentPage = () => {
  const [selectedTermFilter, setSelectedTermFilter] = useState("full");
  const [selectedClass, setSelectedClass] = useState("");
  const [paymentReportDetails, setPaymentReport] = useState([]);
  const [classList, setClassList] = useState([]);
  const [searchQuery, setSearchQuery] = useState("");

  const dispatch = useDispatch();
  // const { studentsList, loading, error, response } = useSelector((state) => state.student);
  const { sclassesList } = useSelector((state) => state.sclass);
  const { studentsList } = useSelector((state) => state.student);
  const { paymentReport, response } = useSelector((state) => state.fees);

  const { currentUser } = useSelector((state) => state.user);

  const address = "Sclass";

  // Fetch the list of available classes
  useEffect(() => {
    dispatch(getAllSclasses(currentUser._id, address));

    dispatch(getPaymentReport());
    // setClassList(sclassesList);
    // dispatch(getAllStudents(currentUser._id));
  }, []);

  console.log(paymentReport, "paymentReport");
  console.log(paymentReportDetails, "paymentReportDetails");
  console.log(response, "response");

  // console.log(studentsList, "studentsList");
  // console.log(sclassesList, "sclassesList");
  // Fetch the payment report based on the selected filter and class
  useEffect(() => {
    const fetchPaymentReport = async () => {
      try {
        if (selectedTermFilter && selectedClass) {
          // If both filter and class are selected, combine the outcomes
          // response = await axios.get(
          //   `${selectedTermFilter}/TermPayemntReport/${selectedClass}`,
          // );
          console.log("selectedTermFilter && selectedClass");
          dispatch(
            getPaymentReportByTermAndClassId(selectedTermFilter, selectedClass),
          );
          setPaymentReport(paymentReport);
          console.log(paymentReportDetails, "paymentReportDetails1");
        } else if (selectedTermFilter) {
          // response = await axios.get(
          //   `/${selectedClass}TermPayemntReport/${selectedTermFilter}`,
          // );
          console.log("selectedTermFilter");
          dispatch(
            getPaymentReportByTermAndClassId(selectedTermFilter, "334444"),
          );

          setPaymentReport(paymentReport);

          console.log(paymentReportDetails, "paymentReportDetails2");
        } else if (selectedClass) {
          // response = await axios.get(`/${selectedClass}TermPayemntReport`);
          console.log("selectedClass");
          dispatch(getPaymentReportByTermAndClassId("22", selectedClass));
          setPaymentReport(paymentReport);
          console.log(paymentReportDetails, "paymentReportDetails3");
        } else {
          // response = await axios.get(`/FeePaymentReport`);
          console.log("else");
          // dispatch(getPaymentReport());
        }

        // setPaymentReport(response.data);
        setPaymentReport(paymentReport);
      } catch (error) {
        console.error("Error fetching payment report:", error);
      }
    };

    fetchPaymentReport().then((r) => console.log(r));
  }, [
    selectedTermFilter,
    selectedClass,
    paymentReport,
    dispatch,
    paymentReportDetails,
  ]);

  const handleSearch = useCallback(
    async (e) => {
      e.preventDefault();

      try {
        if (searchQuery.trim() === "") {
          return;
        }

        dispatch(studentPaymentReport(searchQuery));
        setPaymentReport(paymentReport);
      } catch (error) {
        console.error("Error searching:", error);
      }
    },
    [searchQuery, dispatch, paymentReport],
  );

  const handleChange = (e) => {
    const { value } = e.target;
    setSearchQuery(value);
  };
  let count = 1;
  return (
    <div className="mx-auto max-w-2xl p-4">
      <h1 className="text-2xl font-bold mb-4">Fee Payment Report</h1>
      <div className="flex flex-wrap">
        <form onSubmit={handleSearch} className="flex-grow mr-0 md:mr-2">
          <label
            htmlFor="default-search"
            className="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white"
          >
            Search
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <svg
                className="w-4 h-4 text-gray-500 dark:text-gray-400"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 20 20"
              >
                <path
                  stroke="currentColor"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                />
              </svg>
            </div>
            <input
              type="search"
              id="default-search"
              className="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              placeholder="Search ID No."
              required
              value={searchQuery}
              onChange={handleChange}
            />
            <button
              type="submit"
              className="text-white absolute right-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-2 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            >
              Search
            </button>
          </div>
        </form>

        <div className="flex-grow ml-0 mt-2 mb-2  md:ml-2 mt-0">
          <select
            value={selectedTermFilter}
            onChange={(e) => setSelectedTermFilter(e.target.value)}
            className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-4 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          >
            <option value="">Select By Payment</option>
            <option value="First">First Term Payment</option>
            <option value="Second">Second Term Payment</option>
            <option value="Full">Third Term Payment</option>
          </select>
        </div>
      </div>
      <div>
        <select
          value={selectedClass}
          onChange={(e) => setSelectedClass(e.target.value)}
          className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-4 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        >
          <option value="">All Classes</option>
          {sclassesList?.map((classItem) => (
            <option key={(count = count + 1)} value={classItem?._id}>
              {classItem?.sclassName}
            </option>
          ))}
        </select>
      </div>
      <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-4">
        <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" className="px-6 py-3">
              Name
            </th>
            <th scope="col" className="px-6 py-3">
              ID Number
            </th>
            <th scope="col" className="px-6 py-3">
              Total Paid
            </th>
          </tr>
        </thead>
        <tbody>
          {paymentReport?.map((student) => (
            <tr
              key={(count = count + 1)}
              className="bg-white border-b dark:bg-gray-900 dark:border-gray-700"
            >
              <th
                scope="row"
                className="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
              >
                {student?.student.name}
              </th>
              <th className="px-6 py-4">{student?.student?.idNumber}</th>
              <th className="px-6 py-4">
                {student?.amountPaid / 500 === 1
                  ? "First Term"
                  : student?.amountPaid / 500 === 2
                  ? "Second Term"
                  : "Third Term"}
              </th>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default FeePaymentPage;
