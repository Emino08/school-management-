import { createSlice } from "@reduxjs/toolkit";

const initialState = {
  timetableList: [],
  upcomingClasses: [],
  loading: false,
  error: null,
  response: null,
  groupedTimetable: {},
};

const timetableSlice = createSlice({
  name: "timetable",
  initialState,
  reducers: {
    getRequest: (state) => {
      state.loading = true;
      state.error = null;
    },
    getSuccess: (state, action) => {
      state.timetableList = action.payload.timetable || [];
      state.groupedTimetable = action.payload.grouped || {};
      state.loading = false;
      state.error = null;
    },
    getUpcomingSuccess: (state, action) => {
      state.upcomingClasses = action.payload.upcoming_classes || [];
      state.loading = false;
      state.error = null;
    },
    getFailed: (state, action) => {
      state.loading = false;
      state.error = action.payload;
    },
    postRequest: (state) => {
      state.loading = true;
      state.error = null;
      state.response = null;
    },
    postSuccess: (state, action) => {
      state.loading = false;
      state.response = action.payload;
      state.error = null;
    },
    postFailed: (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.response = null;
    },
    deleteSuccess: (state, action) => {
      state.timetableList = state.timetableList.filter(
        (entry) => entry.id !== action.payload
      );
      state.loading = false;
      state.error = null;
    },
    updateSuccess: (state, action) => {
      const index = state.timetableList.findIndex(
        (entry) => entry.id === action.payload.id
      );
      if (index !== -1) {
        state.timetableList[index] = action.payload;
      }
      state.loading = false;
      state.error = null;
    },
    clearResponse: (state) => {
      state.response = null;
      state.error = null;
    },
  },
});

export const {
  getRequest,
  getSuccess,
  getUpcomingSuccess,
  getFailed,
  postRequest,
  postSuccess,
  postFailed,
  deleteSuccess,
  updateSuccess,
  clearResponse,
} = timetableSlice.actions;

export default timetableSlice.reducer;
