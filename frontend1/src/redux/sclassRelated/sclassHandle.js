import axios from '../axiosConfig';
import {
    getRequest,
    getSuccess,
    getFailed,
    getError,
    getStudentsSuccess,
    detailsSuccess,
    getFailedTwo,
    getSubjectsSuccess,
    getSubDetailsSuccess,
    getSubDetailsRequest,
    getClassSubject
} from './sclassSlice';

export const getAllSclasses = (id, address) => async (dispatch) => {
    dispatch(getRequest());

    try {
        // Use REST endpoint; admin context from token
        const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/classes`);
        if (result.data.message) {
            dispatch(getFailedTwo(result.data.message));
        } else {
            const payload = result.data.classes || result.data;
            dispatch(getSuccess(payload));
        }
    } catch (error) {
        dispatch(getError(error));
    }
}

export const getClassStudents = (id) => async (dispatch) => {
    dispatch(getRequest());

    try {
        const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/students/class/${id}`);
        if (result.data.message) {
            dispatch(getFailedTwo(result.data.message));
        } else {
            dispatch(getStudentsSuccess(result.data));
        }
    } catch (error) {
        dispatch(getError(error));
    }
}

export const getClassSubjects = (classId, teacherId) => async (dispatch) => {
    dispatch(getRequest());

    try {
        const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/classes/${classId}/subjects`);
        console.log(result.data)
        if (result.data.message) {
            dispatch(getFailedTwo(result.data.message));
        } else {
            // Extract the subjects array from the response
            const subjects = result.data.subjects || result.data;
            dispatch(getClassSubject(subjects));
        }
    } catch (error) {
        dispatch(getError(error.message));
    }

}

export const getClassDetails = (id, address) => async (dispatch) => {
    dispatch(getRequest());

    try {
        let url = `${import.meta.env.VITE_API_BASE_URL}/classes/${id}`;
        const result = await axios.get(url);
        if (result.data) {
            // Extract the class details from the response
            const classData = result.data.class || result.data;
            dispatch(detailsSuccess(classData));
        }
    } catch (error) {
        dispatch(getError(error));
    }
}

export const getSubjectList = (id, address) => async (dispatch) => {
    dispatch(getRequest());

    try {
        let url;
        if (address === 'ClassSubjects') {
            url = `${import.meta.env.VITE_API_BASE_URL}/classes/${id}/subjects`;
        } else if (address === 'Subject') {
            url = `${import.meta.env.VITE_API_BASE_URL}/subjects/${id}`;
        } else {
            // Fallback to all subjects
            url = `${import.meta.env.VITE_API_BASE_URL}/subjects`;
        }
        const result = await axios.get(url);
        if (result.data.message) {
            dispatch(getFailed(result.data.message));
        } else {
            // Extract the subjects array from the response
            const subjects = result.data.subjects || result.data;
            dispatch(getSubjectsSuccess(subjects));
        }
    } catch (error) {
        dispatch(getError(error));
    }
}

export const getTeacherFreeClassSubjects = (id) => async (dispatch) => {
    dispatch(getRequest());

    try {
        const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/classes/${id}/subjects/free`);
        if (result.data.message) {
            dispatch(getFailed(result.data.message));
        } else {
            // Extract the subjects array from the response
            const subjects = result.data.subjects || result.data;
            dispatch(getSubjectsSuccess(subjects));
        }
    } catch (error) {
        dispatch(getError(error));
    }
}

export const getSubjectDetails = (id, address) => async (dispatch) => {
    dispatch(getSubDetailsRequest());

    try {
        const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/subjects/${id}`);
        if (result.data) {
            dispatch(getSubDetailsSuccess(result.data));
        }
    } catch (error) {
        dispatch(getError(error));
    }
}
