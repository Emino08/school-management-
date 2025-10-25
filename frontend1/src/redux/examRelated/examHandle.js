import axios from "../axiosConfig";
import {
  getExamsRequest,
  getExamsSuccess,
  getExamsFailed,
  getExamsError,
} from "./examSlice"; // Import actions from your examSlice

export const getAllExams = (id) => async (dispatch) => {
  dispatch(getExamsRequest());

  try {
    const result = await axios.get(
      `${import.meta.env.VITE_API_BASE_URL}/exams/${id}`,
    );
    if (result.data.message) {
      dispatch(getExamsFailed(result.data.message));
    } else {
      dispatch(getExamsSuccess(result.data));
    }
  } catch (error) {
    dispatch(getExamsError(error));
  }
};

export const createExam = (examData) => async (dispatch) => {
  dispatch(getExamsRequest());

  try {
    const result = await axios.post(
      `${import.meta.env.VITE_API_BASE_URL}/exams`,
      examData,
      {
        headers: { "Content-Type": "application/json" },
      },
    );
    if (result.data.message) {
      dispatch(getExamsFailed(result.data.message));
    } else {
      // You might want to dispatch a success action here if needed
    }
  } catch (error) {
    dispatch(getExamsError(error));
  }
};

export const updateExam = (id, examData) => async (dispatch) => {
  dispatch(getExamsRequest());

  try {
    const result = await axios.put(
      `${import.meta.env.VITE_API_BASE_URL}/exams/${id}`,
      examData,
      {
        headers: { "Content-Type": "application/json" },
      },
    );
    if (result.data.message) {
      dispatch(getExamsFailed(result.data.message));
    } else {
      // You might want to dispatch a success action here if needed
    }
  } catch (error) {
    dispatch(getExamsError(error));
  }
};

export const deleteExam = (id) => async (dispatch) => {
  dispatch(getExamsRequest());

  try {
    const result = await axios.delete(
      `${import.meta.env.VITE_API_BASE_URL}/exams/${id}`,
    );
    if (result.data.message) {
      dispatch(getExamsFailed(result.data.message));
    } else {
      // You might want to dispatch a success action here if needed
    }
  } catch (error) {
    dispatch(getExamsError(error));
  }
};
