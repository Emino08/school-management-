import { createSlice } from '@reduxjs/toolkit';

const initialState = {
    status: 'idle',
    userDetails: [],
    tempDetails: [],
    loading: false,
    currentUser: JSON.parse(localStorage.getItem('user')) || null,
    currentRole: (JSON.parse(localStorage.getItem('user')) || {}).role || null,
    error: null,
    response: null,
    validationErrors: null,
    darkMode: true
};

const userSlice = createSlice({
    name: 'user',
    initialState,
    reducers: {
        authRequest: (state) => {
            state.status = 'loading';
            state.validationErrors = null;
        },
        underControl: (state) => {
            state.status = 'idle';
            state.response = null;
            state.validationErrors = null;
        },
        stuffAdded: (state, action) => {
            state.status = 'added';
            state.response = null;
            state.error = null;
            state.tempDetails = action.payload;
            state.validationErrors = null;
        },
        authSuccess: (state, action) => {
            state.status = 'success';
            state.currentUser = action.payload;
            state.currentRole = action.payload.role;
            localStorage.setItem('user', JSON.stringify(action.payload));
            state.response = null;
            state.error = null;
            state.validationErrors = null;
        },
        authFailed: (state, action) => {
            state.status = 'failed';
            state.response = action.payload;
            state.validationErrors = null;
        },
        authError: (state, action) => {
            state.status = 'error';
            state.error = action.payload;
            state.validationErrors = null;
        },
        authLogout: (state) => {
            localStorage.removeItem('user');
            state.currentUser = null;
            state.status = 'idle';
            state.error = null;
            state.currentRole = null
            state.validationErrors = null;
        },
        setValidationErrors: (state, action) => {
            state.validationErrors = action.payload;
        },

        doneSuccess: (state, action) => {
            state.userDetails = action.payload;
            state.loading = false;
            state.error = null;
            state.response = null;
            state.validationErrors = null;
        },
        getDeleteSuccess: (state) => {
            state.loading = false;
            state.error = null;
            state.response = null;
            state.validationErrors = null;
        },

        getRequest: (state) => {
            state.loading = true;
            state.validationErrors = null;
        },
        getFailed: (state, action) => {
            state.response = action.payload;
            state.loading = false;
            state.error = null;
            state.validationErrors = null;
        },
        getError: (state, action) => {
            state.loading = false;
            state.error = action.payload;
            state.validationErrors = null;
        },
        toggleDarkMode: (state) => {
            state.darkMode = !state.darkMode;
        }
    },
});

export const {
    authRequest,
    underControl,
    stuffAdded,
    authSuccess,
    authFailed,
    authError,
    authLogout,
    setValidationErrors,
    doneSuccess,
    getDeleteSuccess,
    getRequest,
    getFailed,
    getError,
    toggleDarkMode
} = userSlice.actions;

export const userReducer = userSlice.reducer;
