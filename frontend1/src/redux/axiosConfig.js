import axios from 'axios';

const parseList = (value) =>
  (value || '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);

const resolveBaseUrl = () => {
  const defaultLocal = 'http://localhost:8080/api';
  const env = typeof import.meta !== 'undefined' ? import.meta.env : undefined;
  const nodeEnv = typeof process !== 'undefined' ? process.env : undefined;

  const prodBase =
    (env && env.VITE_API_BASE_URL) ||
    (nodeEnv && nodeEnv.REACT_APP_BASE_URL) ||
    '';

  const localOverride =
    (env && env.VITE_API_BASE_URL_LOCAL) ||
    (nodeEnv && nodeEnv.REACT_APP_BASE_URL_LOCAL) ||
    '';

  const parsedHosts = parseList(env?.VITE_PRODUCTION_HOSTS);
  const productionHosts =
    parsedHosts.length > 0
      ? parsedHosts
      : ['boschool.org', 'www.boschool.org', 'backend.boschool.org'];

  const isBrowser = typeof window !== 'undefined';
  const hostname = isBrowser ? window.location.hostname : '';
  const isProductionHost = hostname && productionHosts.includes(hostname);

  if (!hostname || !isProductionHost) {
    return localOverride || defaultLocal;
  }

  return prodBase || defaultLocal;
};

const apiBase = resolveBaseUrl();
if (apiBase) {
  axios.defaults.baseURL = apiBase;
}

// Add a request interceptor to attach JWT token to all requests
axios.interceptors.request.use(
    (config) => {
        if (config.skipAuthRedirect) {
            config.__skipAuthRedirect = true;
        }
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
            const skipLogout = error.config && error.config.__skipAuthRedirect;
            if (!skipLogout && message && (message.includes('expired') || message.includes('Invalid'))) {
                // Clear user data and redirect to login
                localStorage.removeItem('user');
                window.location.href = '/';
            }
        }
        return Promise.reject(error);
    }
);

export default axios;
