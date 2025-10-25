import axios from 'axios';

// Add a request interceptor to attach JWT token to all requests
axios.interceptors.request.use(
    (config) => {
        // Get user from localStorage
        const userStr = localStorage.getItem('user');
        if (userStr) {
            try {
                const userData = JSON.parse(userStr);
                // The user object contains: { token, role, admin/student/teacher, ... }
                // If token exists, add it to Authorization header
                if (userData.token) {
                    config.headers.Authorization = `Bearer ${userData.token}`;
                }
            } catch (error) {
                console.error('Error parsing user from localStorage:', error);
            }
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
