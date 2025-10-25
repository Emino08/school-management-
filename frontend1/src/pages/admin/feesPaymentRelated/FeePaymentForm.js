import React, { useCallback, useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { getUserDetails } from "../../../redux/userRelated/userHandle";
import { makePayment } from "../../../redux/feesPaymentRelated/feesPaymentHandle";

const FeePaymentForm = () => {
  let initialFormData;
  initialFormData = {
    studentId: "",
    name: "",
    // address: "",
    className: "",
    amount: "",
    bankDetails: null,
    paymentType: "", // Default payment type
  };

  const [formData, setFormData] = useState(initialFormData);
  const [studentFound, setStudentFound] = useState(null);
  const [isBank, setIsBank] = useState(false);
  const [searchClicked, setSearchClicked] = useState(false);

  const dispatch = useDispatch();
  const { userDetails, response, loading, error } = useSelector(
    (state) => state.user,
  );

  const handleSearch = () => {
    console.log("Search clicked");
    const studentId = formData.studentId;
    const address = "Student";

    if (formData.studentId === "") {
      return alert("Please enter a student ID");
    } else {
      dispatch(getUserDetails(studentId, address));
    }

    setSearchClicked(true);
  };

  const handleChange = useCallback((e) => {
    const { name, value, type } = e.target;

    if (type === "file") {
      handleFileUpload(e);
    } else {
      setFormData((prevData) => ({
        ...prevData,
        [name]: value,
      }));

      if (name === "paymentType") {
        setIsBank(value === "Bank");
      }
    }
  }, []);
  const handleFileUpload = (e) => {
    const file = e.target.files[0];
    setFormData((prevData) => ({
      ...prevData,
      bankDetails: file,
    }));
  };

  useEffect(() => {
    if (searchClicked) {
      if (searchClicked) {
        console.log("User details:", userDetails);
        setStudentFound(false);
        if (userDetails[0]?.role === "Student" && !studentFound) {
          // Populate form fields from the userDetails if studentFound is false
          setFormData((prevData) => ({
            ...prevData,
            studentId: prevData.studentId, // Preserve student ID
            name: userDetails[0].name,
            className: userDetails[0].sclassName.sclassName,
          }));
          setStudentFound(true);
          setSearchClicked(false);
        }
      }
    } else return;
    // setFormData(initialFormData);
    // setStudentFound(null);
  }, [
    formData.paymentType,
    formData.studentId,
    initialFormData,
    searchClicked,
    studentFound,
    userDetails,
  ]);

  // const handleChange = (e) => {
  //   const { name, value, type } = e.target;
  //
  //   if (type === "file") {
  //     handleFileUpload(e);
  //   } else {
  //     setFormData((prevData) => ({
  //       ...prevData,
  //       [name]: value,
  //     }));
  //
  //     if (name === "paymentType") {
  //       setIsBank(value === "Bank");
  //     }
  //   }
  // };

  const handleSubmit = (e) => {
    e.preventDefault();
    // Handle form submission here, including uploaded file (formData.bankDetails)
    dispatch(makePayment(formData));

    console.log(formData);
  };

  return (
    <div className="flex justify-center h-screen">
      <form onSubmit={handleSubmit} className="p-8 min-w-[300px]">
        <div className="mb-6">
          <label htmlFor="default-search" className="mb-2 text-sm font-medium">
            Enter Studen ID
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
              type="number"
              id="default-search"
              name="studentId"
              value={formData.studentId}
              onChange={handleChange}
              className="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              placeholder="Student ID"
              required
            />
            <button
              type="button"
              onClick={handleSearch}
              disabled={loading} // Disable the button when loading
              className={`text-white absolute right-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 ${
                loading
                  ? "cursor-not-allowed opacity-60" // Apply a different style when loading
                  : ""
              }`}
            >
              {loading ? "Searching..." : "Search"}
            </button>
          </div>
        </div>

        {studentFound && (
          <>
            <div className="mb-6">
              <label htmlFor="name" className="mb-2 text-sm font-medium">
                Name
              </label>
              <input
                type="text"
                id="name"
                disabled={true}
                name="name"
                value={formData.name}
                onChange={handleChange}
                className="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Name"
                required
              />
            </div>

            {/*<div className="mb-6">*/}
            {/*  <label htmlFor="address" className="mb-2 text-sm font-medium">*/}
            {/*    Address*/}
            {/*  </label>*/}
            {/*  <input*/}
            {/*    type="text"*/}
            {/*    id="address"*/}
            {/*    disabled={true}*/}
            {/*    name="address"*/}
            {/*    value={formData.address}*/}
            {/*    onChange={handleChange}*/}
            {/*    className="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"*/}
            {/*    placeholder="Address"*/}
            {/*    required*/}
            {/*  />*/}
            {/*</div>*/}

            <div className="mb-6">
              <label htmlFor="className" className="mb-2 text-sm font-medium">
                Class
              </label>
              <input
                type="text"
                id="className"
                disabled={true}
                name="className"
                value={formData.className}
                onChange={handleChange}
                className="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Class"
                required
              />
            </div>

            <div className="mb-6">
              <label htmlFor="amount" className="mb-2 text-sm font-medium">
                Amount
              </label>
              <input
                type="number"
                id="amount"
                name="amount"
                min={500}
                max={1500}
                multiple={500}
                value={formData.amount}
                onChange={handleChange}
                className="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Amount"
                required
              />
            </div>

            <div className="mb-6">
              <label
                htmlFor="paymentType"
                className="block mb-2 text-sm font-medium"
              >
                Select Payment Type
              </label>
              <select
                id="paymentType"
                name="paymentType"
                value={formData.paymentType}
                onChange={handleChange}
                required={true}
                className="bg-gray-50 border py-4 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              >
                <option value="">Select Payment Type</option>
                <option value="Bank">Bank</option>
                <option value="Orange Money">Orange Money</option>
                <option value="Tenkipay">Tenkipay</option>
                <option value="AfriMoney">AfriMoney</option>
                <option value="Ezipay">Ezipay</option>
              </select>
            </div>

            {isBank && (
              <div className="mb-6">
                <label
                  htmlFor="bankDetails"
                  className="block mb-2 text-sm font-medium"
                >
                  Bank Details (Upload File)
                </label>
                <input
                  type="file"
                  id="bankDetails"
                  name="bankDetails"
                  className="block w-full py-4 mb-5 text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                  accept=".pdf, .doc, .docx, .jpg, .jpeg, .png"
                  required
                  onChange={handleFileUpload} // Call the separate function for file upload
                />
              </div>
            )}

            <button
              type="submit"
              disabled={loading} // Disable the button when loading
              className={`text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center ${
                loading ? "cursor-not-allowed opacity-60" : ""
              }`}
            >
              {loading ? "Paying..." : "Pay"}
            </button>

            {/*{!studentFound && (*/}
            {/*  <div className="mb-6">*/}
            {/*    <p className="text-red-500">Student not found</p>*/}
            {/*  </div>*/}
            {/*)}*/}
          </>
        )}
      </form>
    </div>
  );
};

export default FeePaymentForm;
