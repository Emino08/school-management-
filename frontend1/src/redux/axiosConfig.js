import axios from 'axios';

// Configure base URL from environment
const apiBase = (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.VITE_API_BASE_URL)
  || (typeof process !== 'undefined' && process.env && process.env.REACT_APP_BASE_URL)
  || '';
if (apiBase) {
  axios.defaults.baseURL = apiBase;
}

// Add a request interceptor to attach JWT token to all requests
axios.interceptors.request.use(
    (config) => {
        // Get user from localStorage
        const userStr = localStorage.getItem('user');
        try {
            if (userStr) {
                const userData = JSON.parse(userStr);
                if (userData?.token) {
                    config.headers.Authorization = `Bearer ${userData.token}`;
                }
            }
            // Fallback: some older flows store raw token at localStorage.token
            if (!config.headers.Authorization) {
                const rawToken = localStorage.getItem('token');
                if (rawToken) {
                    config.headers.Authorization = `Bearer ${rawToken}`;
                }
            }
        } catch (error) {
            console.error('Error reading auth token:', error);
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add a response interceptor to handle token expiration
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            // Token expired or invalid
            const message = error.response.data?.message;
            if (message && (message.includes('expired') || message.includes('Invalid'))) {
                // Clear user data and redirect to login
                localStorage.removeItem('user');
                window.location.href = '/';
            }
        }
        return Promise.reject(error);
    }
);

export default axios;
