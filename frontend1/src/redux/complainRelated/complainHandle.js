import axios from '../axiosConfig';
import {
    getRequest,
    getSuccess,
    getFailed,
    getError
} from './complainSlice';

export const getAllComplains = (id, address) => async (dispatch) => {
    dispatch(getRequest());

    if (!id) {
        dispatch(getError({ message: 'Invalid user ID' }));
        return;
    }

    try {
        const result = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/${address}List/${id}`);
        if (result.data.message) {
            dispatch(getFailed(result.data.message));
        } else {
            dispatch(getSuccess(result.data));
        }
    } catch (error) {
        dispatch(getError(error));
    }
}