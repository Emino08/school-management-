import { createSlice } from "@reduxjs/toolkit";

const initialState = {
    noticesList: [],
    loading: false,
    error: null,
    response: null,
};

const noticeSlice = createSlice({
    name: 'notice',
    initialState,
    reducers: {
        getRequest: (state) => {
            state.loading = true;
        },
        getSuccess: (state, action) => {
            state.noticesList = Array.isArray(action.payload) ? action.payload : [];
            state.loading = false;
            state.error = null;
            state.response = null;
        },
        getFailed: (state, action) => {
            state.response = action.payload;
            state.loading = false;
            state.error = null;
            state.noticesList = []; // Ensure it's always an array
        },
        getError: (state, action) => {
            state.loading = false;
            state.error = action.payload;
            state.noticesList = []; // Ensure it's always an array
        }
    },
});

export const {
    getRequest,
    getSuccess,
    getFailed,
    getError
} = noticeSlice.actions;

export const noticeReducer = noticeSlice.reducer;