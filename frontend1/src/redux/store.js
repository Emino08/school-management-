import { configureStore } from "@reduxjs/toolkit";
import { userReducer } from "./userRelated/userSlice";
import { studentReducer } from "./studentRelated/studentSlice";
import { noticeReducer } from "./noticeRelated/noticeSlice";
import { sclassReducer } from "./sclassRelated/sclassSlice";
import { teacherReducer } from "./teacherRelated/teacherSlice";
import { complainReducer } from "./complainRelated/complainSlice";
import { feesPaymentReducer } from "./feesPaymentRelated/feesPaymentSlice";
import { academicYearReducer } from "./academicYearRelated/academicYearSlice";
import timetableReducer from "./timetableRelated/timetableSlice";

const store = configureStore({
  reducer: {
    user: userReducer,
    student: studentReducer,
    teacher: teacherReducer,
    notice: noticeReducer,
    complain: complainReducer,
    sclass: sclassReducer,
    fees: feesPaymentReducer,
    academicYear: academicYearReducer,
    timetable: timetableReducer,
  },
});

export default store;
