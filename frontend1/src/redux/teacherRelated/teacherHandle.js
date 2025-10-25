import axios from "../axiosConfig";
import {
  getRequest,
  getSuccess,
  getFailed,
  getError,
  postDone,
  doneSuccess,
} from "./teacherSlice";
import {
  authError,
  authFailed,
  authSuccess,
  stuffAdded,
} from "../userRelated/userSlice";

export const getAllTeachers = (id) => async (dispatch) => {
  dispatch(getRequest());

  // Backend uses authenticated admin context; id not required

  try {
    const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/teachers`);
    if (result.data.message) {
      dispatch(getFailed(result.data.message));
    } else {
      // Normalize payload to array
      const payload = result.data.teachers || result.data;
      dispatch(getSuccess(payload));
    }
  } catch (error) {
    dispatch(getError(error));
  }
};

export const getTeacherDetails = (id) => async (dispatch) => {
  dispatch(getRequest());

  try {
    const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/teachers/${id}`);
    if (result.data) {
      const payload = result.data.teacher || result.data;
      dispatch(doneSuccess(payload));
    }
  } catch (error) {
    dispatch(getError(error));
  }
};

export const updateTeachSubject =
  (teacherId, teachSubject) => async (dispatch) => {
    dispatch(getRequest());

    try {
      await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/teachers/assign-subject`,
        { teacher_id: teacherId, subject_id: teachSubject },
        { headers: { "Content-Type": "application/json" } },
      );
      dispatch(postDone());
    } catch (error) {
      dispatch(getError(error));
    }
  };

export const getTeachers = () => async (dispatch) => {
  dispatch(getRequest());

  try {
    const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/teachers`);
    if (result.data.message) {
      dispatch(getFailed(result.data.message));
    } else {
      const payload = result.data.teachers || result.data;
      dispatch(getSuccess(payload));
    }
  } catch (error) {
    dispatch(getError(error));
  }
};

export const teacherAssign = (fields) => async (dispatch) => {
  dispatch(getRequest());

  try {
    const body = fields?.teacher_id && fields?.subject_id
      ? fields
      : { teacher_id: fields?.teacherId ?? fields?.teacher_id, subject_id: fields?.teachSubject ?? fields?.subject_id };
    const result = await axios.post(
      `${import.meta.env.VITE_API_BASE_URL}/teachers/assign-subject`,
      body,
      { headers: { "Content-Type": "application/json" } },
    );
    //   if (result.data.message) {
    //     dispatch(getFailed(result.data.message));
    //   } else {
    //     dispatch(postDone());
    //   }
    // } catch (error) {
    //   dispatch(getError(error));
    // }

    if (result.data.message) {
      dispatch(authFailed(result.data.message));
    } else {
      dispatch(stuffAdded());
    }
  } catch (error) {
    dispatch(authError(error));
  }
};

export const deleteSubjectFromTeacher =
  (teacherId, subjectId, classId) => async (dispatch) => {
    dispatch(getRequest());

    try {
      await axios.delete(`${import.meta.env.VITE_API_BASE_URL}/teachers/${teacherId}/subjects/${subjectId}`);
      dispatch(postDone());
    } catch (error) {
      dispatch(getError(error));
    }
  };
