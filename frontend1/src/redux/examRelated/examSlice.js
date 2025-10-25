import {createSlice} from '@reduxjs/toolkit';

const initialState = {
    exams: [],
    loading: false,
    error: null,
    response: null,
};

const examSlice = createSlice({
    name: 'exam',
    initialState,
    reducers: {
        getExamsRequest: (state) => {
            state.loading = true;
        },
        getExamsSuccess: (state, action) => {
            state.exams = action.payload;
            state.loading = false;
            state.error = null;
            state.response = null;
        },
        getExamsFailed: (state, action) => {
            state.response = action.payload;
            state.loading = false;
            state.error = null;
        },
        getExamsError: (state, action) => {
            state.loading = false;
            state.error = action.payload;
        },
    },
});

export const {
    getExamsRequest,
    getExamsSuccess,
    getExamsFailed,
    getExamsError,
} = examSlice.actions;

export const examReducer = examSlice.reducer;

// Example Usage:
// You can use these actions and reducer in your Redux store to manage the state related to exams.
