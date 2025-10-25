import { createSlice } from '@reduxjs/toolkit';

const initialState = {
    currentAcademicYear: null,
    allAcademicYears: [],
    loading: false,
    error: null
};

const academicYearSlice = createSlice({
    name: 'academicYear',
    initialState,
    reducers: {
        setCurrentAcademicYear: (state, action) => {
            state.currentAcademicYear = action.payload;
            // Store in localStorage for persistence
            if (action.payload) {
                localStorage.setItem('currentAcademicYear', JSON.stringify(action.payload));
            }
        },
        setAllAcademicYears: (state, action) => {
            state.allAcademicYears = action.payload;
        },
        setLoading: (state, action) => {
            state.loading = action.payload;
        },
        setError: (state, action) => {
            state.error = action.payload;
        },
        loadCurrentAcademicYear: (state) => {
            const stored = localStorage.getItem('currentAcademicYear');
            if (stored) {
                state.currentAcademicYear = JSON.parse(stored);
            }
        },
        clearAcademicYear: (state) => {
            state.currentAcademicYear = null;
            state.allAcademicYears = [];
            localStorage.removeItem('currentAcademicYear');
        }
    }
});

export const {
    setCurrentAcademicYear,
    setAllAcademicYears,
    setLoading,
    setError,
    loadCurrentAcademicYear,
    clearAcademicYear
} = academicYearSlice.actions;

export default academicYearSlice.reducer;
