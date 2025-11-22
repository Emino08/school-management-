// import { createSlice } from "@reduxjs/toolkit";
//
// const initialState = {
//   academicYearsList: [],
//   loading: false,
//   error: null,
//   response: null,
//     status: "idle",
// };
//
// const academicYearSlice = createSlice({
//   name: "academicYear",
//   initialState,
//   reducers: {
//     getRequest: (state) => {
//       state.loading = true;
//         state.error = null;
//         state.response = null;
//         state.status = "loading";
//     },
//     getSuccess: (state, action) => {
//       state.academicYearsList = action.payload;
//       state.loading = false;
//       state.error = null;
//       state.response = null;
//         state.status = "succeeded";
//     },
//     getFailed: (state, action) => {
//       state.response = action.payload;
//       state.loading = false;
//       state.error = null;
//         state.status = "failed";
//     },
//     getError: (state, action) => {
//       state.loading = false;
//       state.error = action.payload;
//         state.response = null;
//             state.status = "error";
//
//     },
//   },
// });
//
// export const { getRequest,
//
//   getSuccess,
//   getFailed,
//   getError } =
//   academicYearSlice.actions;
//
// export const academicYearReducer = academicYearSlice.reducer;
import { createSlice } from "@reduxjs/toolkit";

const initialState = {
  academicYearData: [],
  academicYearLoading: false,
  academicYearError: null,
  academicYearStatus: "idle",
  academicYearMessage: "",
};

const academicyearSlice = createSlice({
  name: "academicyear",
  initialState,
  reducers: {
    academicYearRequest: (state) => {
      state.academicYearLoading = true;
      state.academicYearError = null;
      state.academicYearStatus = "loading";
    },
    academicYearSuccess: (state, action) => {
      state.academicYearLoading = false;
      state.academicYearError = null;
      state.academicYearMessage = action.payload;
      state.academicYearStatus = "succeeded";

    },
    academicYearDetailsSuccess: (state, action) => {
        state.academicYearLoading = false;
        state.academicYearError = null;
        const years = Array.isArray(action.payload) ? action.payload : [];
        state.academicYearData = years;
        state.academicYearStatus = "succeeded";

        // Persist the currently active academic year (if any) for consumers that read from localStorage
        try {
            const currentYear =
                years.find((item) => item?.is_current === true || item?.is_current === 1) || null;
            if (currentYear) {
                localStorage.setItem('currentAcademicYear', JSON.stringify(currentYear));
            } else {
                localStorage.removeItem('currentAcademicYear');
            }
        } catch (e) {
            console.error('Failed to persist current academic year', e);
        }
    },
    academicYearFailed: (state, action) => {
      state.academicYearLoading = false;
      state.academicYearError = action.payload;
      state.academicYearStatus = "failed";
    },
    academicYearError: (state, action) => {
      state.academicYearLoading = false;
      state.academicYearError = action.payload;
      state.academicYearStatus = "error";
    },
    resetAcademicYearStatus: (state) => {
      state.academicYearLoading = false;
      state.academicYearError = null;
      state.academicYearStatus = "idle";
    },
    // Add an action to set the academic year data.
    setAcademicYearData: (state, action) => {
      state.academicYearData = action.payload;
    },

    // Add an action to clear the academic year data.
    clearAcademicYearData: (state) => {
      state.academicYearData = [];
      try {
        localStorage.removeItem('currentAcademicYear');
      } catch (e) {
        console.error('Failed to clear current academic year from storage', e);
      }
    },
    clearStatusMessage: (state) => {
        state.academicYearMessage = "";
    }
  },
});

export const {
  academicYearRequest,
  academicYearSuccess,
  academicYearFailed,
  academicYearError,
  resetAcademicYearStatus,
  setAcademicYearData,
  clearAcademicYearData,
    academicYearDetailsSuccess,
    clearStatusMessage
} = academicyearSlice.actions;

export const academicYearReducer = academicyearSlice.reducer;
