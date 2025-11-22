import axios from "../axiosConfig";
import {
    academicYearSuccess,
    academicYearFailed,
    academicYearError,
    academicYearRequest,
    academicYearDetailsSuccess,
    clearStatusMessage
} from "./academicYearSlice"; // Import actions from your academicYearSlice

export const createAcademicYear = (fields) => async (dispatch) => {
  dispatch(academicYearRequest());

  try {
    const result = await axios.post(
      `${import.meta.env.VITE_API_BASE_URL}/academic-years`,
        {...fields },
      {
        headers: { "Content-Type": "application/json" },
      },
    );

    if (result.data.success) {
        dispatch(academicYearSuccess(result.data.message || "Academic year created successfully"));
        return result.data;
    } else {
        dispatch(academicYearFailed(result.data.message || "Failed to create academic year"));
        return null;
    }
  } catch (error) {
      const errData = error?.response?.data?.message || error.message || "Failed to create academic year";
    dispatch(academicYearError(errData));
    throw error;
  }
};
export const getAllAcademicYears = () => async (dispatch) => {
  dispatch(academicYearRequest());

  try {
    console.log('=== FETCHING ACADEMIC YEARS ===');
    console.log('API URL:', `${import.meta.env.VITE_API_BASE_URL}/academic-years`);
    
    const result = await axios.get(
      `${import.meta.env.VITE_API_BASE_URL}/academic-years`,
    );
    
    console.log('API Response:', result.data);
    
    if (result.data.success && result.data.academic_years) {
      console.log('Dispatching academic years:', result.data.academic_years);
      dispatch(academicYearDetailsSuccess(result.data.academic_years));
    } else if (result.data.message) {
      console.error('API returned message:', result.data.message);
      dispatch(academicYearFailed(result.data.message));
    } else {
      console.warn('API returned unexpected format, dispatching empty array');
      dispatch(academicYearDetailsSuccess([]));
    }
  } catch (error) {
    console.error('Error fetching academic years:', error);
    console.error('Error response:', error.response?.data);
    dispatch(academicYearError(error.response?.data?.message || error.message));
  }
};

export const setAcademicYear = (academicYear) => async (dispatch) => {
    dispatch(academicYearRequest());

    try {
        const id = academicYear?.id || academicYear?.academicYearId || academicYear?.yearId;
        const result = await axios.put(
          `${import.meta.env.VITE_API_BASE_URL}/academic-years/${id}/set-current`
        );
        if (result.data.message) {
            dispatch(academicYearSuccess(result.data.message));
            // Refresh the list so the current year (and its fee settings) stay in sync across the app
            await dispatch(getAllAcademicYears());
        } else {
            dispatch(academicYearFailed(result.data));
        }
    } catch (error) {
        const errData = error.response?.data?.message || error.message;
        dispatch(clearStatusMessage());
        dispatch(academicYearError(errData));

    }
}

export const updateAcademicYearFields =
  (id, fields, address) => async (dispatch) => {
    dispatch(academicYearRequest());

    try {
      const result = await axios.put(
        `${import.meta.env.VITE_API_BASE_URL}/${address}/${id}`,
        fields,
        {
          headers: { "Content-Type": "application/json" },
        },
      );
      if (result.data.message) {
        dispatch(academicYearFailed(result.data.message));
      } else {
        // You might want to dispatch a success action here if needed
      }
    } catch (error) {
      dispatch(academicYearError(error));
    }
  };

export const removeAcademicYear = (id, address) => async (dispatch) => {
  dispatch(academicYearRequest());

  try {
    const result = await axios.delete(
      `${import.meta.env.VITE_API_BASE_URL}/${address}/${id}`,
    );
    if (result.data.message) {
      dispatch(academicYearFailed(result.data.message));
    } else {
      // You might want to dispatch a success action here if needed
    }
  } catch (error) {
    dispatch(academicYearError(error));
  }
};
