import { createSlice } from "@reduxjs/toolkit";

const initialState = {
  paymentReport: [],
  loading: false,
  error: null,
  response: null,
  paymentStatus: "idle",
};

const feesPaymentSlice = createSlice({
  name: "fees",
  initialState,
  reducers: {
    paymentRequest: (state) => {
      state.loading = true;
      state.error = null;
      state.response = null;
      state.paymentStatus = "loading";
    },
    paymentSuccess: (state, action) => {
      state.loading = false;
      state.error = null;
      state.response = action.payload;
      state.paymentStatus = "succeeded";
      state.paymentReport = action.payload;
    },
    paymentFailed: (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.response = null;
      state.paymentStatus = "failed";
    },
    paymentError: (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.response = null;
      state.paymentStatus = "error";
    },
    resetPaymentStatus: (state) => {
      state.loading = false;
      state.error = null;
      state.response = null;
      state.paymentStatus = "idle";
    },
    // Add an action to set the payment report data.
    setPaymentReport: (state, action) => {
      state.paymentReport = action.payload;
    },

    // Add an action to clear the payment report data.
    clearPaymentReport: (state) => {
      state.paymentReport = [];
    },
  },
});

export const {
  paymentRequest,
  paymentSuccess,
  paymentFailed,
  paymentError,
  resetPaymentStatus,
  setPaymentReport,
  clearPaymentReport,
} = feesPaymentSlice.actions;

export const feesPaymentReducer = feesPaymentSlice.reducer;
