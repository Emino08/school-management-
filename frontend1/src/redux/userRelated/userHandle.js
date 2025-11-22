import axios from '../axiosConfig';
import {
    authRequest,
    stuffAdded,
    authSuccess,
    authFailed,
    authError,
    authLogout,
    doneSuccess,
    getDeleteSuccess,
    getRequest,
    getFailed,
    getError,
} from './userSlice';

export const loginUser = (fields, role) => async (dispatch) => {
    dispatch(authRequest());

    try {
        // Map role to proper endpoint
        const roleMap = {
            'Admin': 'admin',
            'Principal': 'admin',
            'Student': 'students',
            'Teacher': 'teachers'
        };
        const endpoint = `${import.meta.env.VITE_API_BASE_URL}/${roleMap[role]}/login`;
        const result = await axios.post(endpoint, fields, {
            headers: { 'Content-Type': 'application/json' },
            // Avoid global 401 interceptor redirecting to "/" on deliberate auth failures
            skipAuthRedirect: true,
        });
        const data = result.data || {};
        // Normalize payloads across roles so authSuccess can set currentRole
        if (data.success) {
            let userPayload = data;
            if (!data.role) {
                userPayload = {
                    ...data,
                    role,
                    // Flatten common fields for convenience
                    name: data.admin?.school_name || data.teacher?.name || data.student?.name || data.name,
                    token: data.token || data.admin?.token || data.teacher?.token || data.student?.token,
                    // Ensure _id is at the root level for all roles
                    _id: data._id || data.student?.id || data.teacher?.id || data.admin?.id,
                };
            }
            dispatch(authSuccess(userPayload));
        } else {
            dispatch(authFailed(data.message));
        }
    } catch (error) {
        const message = error?.response?.data?.message;
        if (message) {
            dispatch(authFailed(message));
        } else {
            dispatch(authError(error));
        }
    }
};

export const registerUser = (fields, role) => async (dispatch) => {
    dispatch(authRequest());

    try {
        // Map role to proper endpoint
        const roleMap = {
            'Admin': 'admin',
            'Principal': 'admin',
            'Student': 'students',
            'Teacher': 'teachers'
        };
        const endpoint = `${import.meta.env.VITE_API_BASE_URL}/${roleMap[role]}/register`;
        const result = await axios.post(endpoint, fields, {
            headers: { 'Content-Type': 'application/json' },
        });
        if (result.data.schoolName) {
            dispatch(authSuccess(result.data));
        }
        else if (result.data.school) {
            dispatch(stuffAdded());
        }
        else {
            dispatch(authFailed(result.data.message));
        }
    } catch (error) {
        dispatch(authError(error));
    }
};

export const logoutUser = () => (dispatch) => {
    dispatch(authLogout());
};

export const getUserDetails = (id, address) => async (dispatch) => {
    dispatch(getRequest());

    try {
        let url;
        if (address === 'Admin') {
            url = `${import.meta.env.VITE_API_BASE_URL}/admin/profile`;
        } else if (address === 'Teacher') {
            url = `${import.meta.env.VITE_API_BASE_URL}/teachers/${id}`;
        } else if (address === 'Student') {
            url = `${import.meta.env.VITE_API_BASE_URL}/students/${id}`;
        } else {
            url = `${import.meta.env.VITE_API_BASE_URL}/${address}/${id}`; // fallback
        }
        const result = await axios.get(url);
        if (result.data.message) {
            dispatch(getFailed(result.data.message));
        } else {
            dispatch(doneSuccess(result.data));
        }
    } catch (error) {
        console.log("error",error.message)
        dispatch(getError(error.message));
    }
}

export const deleteUser = (id, address) => async (dispatch) => {
    dispatch(getRequest());

    try {
        let url;
        if (address === 'Teacher') url = `${import.meta.env.VITE_API_BASE_URL}/teachers/${id}`;
        else if (address === 'Student') url = `${import.meta.env.VITE_API_BASE_URL}/students/${id}`;
        else if (address === 'Admin') url = `${import.meta.env.VITE_API_BASE_URL}/admin/${id}`;
        else url = `${import.meta.env.VITE_API_BASE_URL}/${address}/${id}`;
        const result = await axios.delete(url);
        if (result.data.message) {
            dispatch(getFailed(result.data.message));
        } else {
            dispatch(getDeleteSuccess());
        }
    } catch (error) {
        dispatch(getError(error));
    }
}

export const updateUser = (fields, id, address) => async (dispatch) => {
    dispatch(getRequest());

    try {
        let url;
        if (address === 'Admin') url = `${import.meta.env.VITE_API_BASE_URL}/admin/profile`;
        else if (address === 'Teacher') url = `${import.meta.env.VITE_API_BASE_URL}/teachers/${id}`;
        else if (address === 'Student') url = `${import.meta.env.VITE_API_BASE_URL}/students/${id}`;
        else url = `${import.meta.env.VITE_API_BASE_URL}/${address}/${id}`;
        const result = await axios.put(url, fields, { headers: { 'Content-Type': 'application/json' } });
        if (result.data.schoolName) {
            dispatch(authSuccess(result.data));
        }
        else {
            dispatch(doneSuccess(result.data));
        }
    } catch (error) {
        dispatch(getError(error));
    }
}

export const addStuff = (fields, address) => async (dispatch) => {
    dispatch(authRequest());

    try {
        let url;
        if (address === 'Notice') url = `${import.meta.env.VITE_API_BASE_URL}/notices`;
        else if (address === 'Complain' || address === 'Complaint') url = `${import.meta.env.VITE_API_BASE_URL}/complaints`;
        else if (address === 'Subject') url = `${import.meta.env.VITE_API_BASE_URL}/subjects`;
        else if (address === 'Sclass' || address === 'Class') url = `${import.meta.env.VITE_API_BASE_URL}/classes`;
        else url = `${import.meta.env.VITE_API_BASE_URL}/${address}Create`;
        const result = await axios.post(url, fields, { headers: { 'Content-Type': 'application/json' } });

        if (result.data.success) {
            dispatch(stuffAdded(result.data));
        } else if (result.data.message) {
            dispatch(authFailed(result.data.message));
        } else {
            dispatch(stuffAdded(result.data));
        }
    } catch (error) {
        dispatch(authError(error));
    }
};
