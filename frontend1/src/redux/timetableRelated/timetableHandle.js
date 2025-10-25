import axios from "axios";
import {
  getRequest,
  getSuccess,
  getUpcomingSuccess,
  getFailed,
  postRequest,
  postSuccess,
  postFailed,
  deleteSuccess,
  updateSuccess,
} from "./timetableSlice";

const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

// Get timetable for a teacher
export const getTeacherTimetable = (teacherId) => async (dispatch) => {
  dispatch(getRequest());
  try {
    const result = await axios.get(`${API_URL}/timetable/teacher/${teacherId}`);
    if (result.data.success) {
      dispatch(getSuccess(result.data));
    } else {
      dispatch(getFailed(result.data.message));
    }
  } catch (error) {
    dispatch(getFailed(error.response?.data?.message || error.message));
  }
};

// Get timetable for a student
export const getStudentTimetable = (studentId) => async (dispatch) => {
  dispatch(getRequest());
  try {
    const result = await axios.get(`${API_URL}/timetable/student/${studentId}`);
    if (result.data.success) {
      dispatch(getSuccess(result.data));
    } else {
      dispatch(getFailed(result.data.message));
    }
  } catch (error) {
    dispatch(getFailed(error.response?.data?.message || error.message));
  }
};

// Get timetable for a class
export const getClassTimetable = (classId) => async (dispatch) => {
  dispatch(getRequest());
  try {
    const result = await axios.get(`${API_URL}/timetable/class/${classId}`);
    if (result.data.success) {
      dispatch(getSuccess(result.data));
    } else {
      dispatch(getFailed(result.data.message));
    }
  } catch (error) {
    dispatch(getFailed(error.response?.data?.message || error.message));
  }
};

// Get upcoming classes for logged-in teacher
export const getUpcomingClasses = (limit = 5) => async (dispatch) => {
  dispatch(getRequest());
  try {
    const result = await axios.get(`${API_URL}/timetable/upcoming?limit=${limit}`);
    if (result.data.success) {
      dispatch(getUpcomingSuccess(result.data));
    } else {
      dispatch(getFailed(result.data.message));
    }
  } catch (error) {
    dispatch(getFailed(error.response?.data?.message || error.message));
  }
};

// Create timetable entry (Admin only)
export const createTimetableEntry = (entryData) => async (dispatch) => {
  dispatch(postRequest());
  try {
    const result = await axios.post(`${API_URL}/timetable`, entryData);
    if (result.data.success) {
      dispatch(postSuccess(result.data));
    } else {
      dispatch(postFailed(result.data.message));
    }
  } catch (error) {
    dispatch(postFailed(error.response?.data?.message || error.message));
  }
};

// Bulk create timetable entries (Admin only)
export const bulkCreateTimetable = (entries) => async (dispatch) => {
  dispatch(postRequest());
  try {
    const result = await axios.post(`${API_URL}/timetable/bulk`, { entries });
    if (result.data.success) {
      dispatch(postSuccess(result.data));
    } else {
      dispatch(postFailed(result.data.message));
    }
  } catch (error) {
    dispatch(postFailed(error.response?.data?.message || error.message));
  }
};

// Update timetable entry (Admin only)
export const updateTimetableEntry = (id, entryData) => async (dispatch) => {
  dispatch(postRequest());
  try {
    const result = await axios.put(`${API_URL}/timetable/${id}`, entryData);
    if (result.data.success) {
      dispatch(updateSuccess({ id, ...entryData }));
      dispatch(postSuccess(result.data));
    } else {
      dispatch(postFailed(result.data.message));
    }
  } catch (error) {
    dispatch(postFailed(error.response?.data?.message || error.message));
  }
};

// Delete timetable entry (Admin only)
export const deleteTimetableEntry = (id) => async (dispatch) => {
  dispatch(postRequest());
  try {
    const result = await axios.delete(`${API_URL}/timetable/${id}`);
    if (result.data.success) {
      dispatch(deleteSuccess(id));
      dispatch(postSuccess(result.data));
    } else {
      dispatch(postFailed(result.data.message));
    }
  } catch (error) {
    dispatch(postFailed(error.response?.data?.message || error.message));
  }
};
