import axios from 'axios';

const DEFAULT_LOCAL_BASE = 'http://localhost:8080/api';

const resolveBaseUrl = () => {
  const env = typeof import.meta !== 'undefined' ? import.meta.env : {};
  const nodeEnv = typeof process !== 'undefined' ? process.env : {};

  const productionBase =
    env.VITE_API_BASE_URL || nodeEnv.REACT_APP_BASE_URL || '';
  const developmentBase =
    env.VITE_API_BASE_URL_LOCAL || nodeEnv.REACT_APP_BASE_URL_LOCAL || '';

  const mode = (env.MODE || nodeEnv.NODE_ENV || '').toLowerCase();
  const isDev = env.DEV || mode.includes('dev');
  const isProd = env.PROD || mode === 'production';

  if (isDev) {
    return developmentBase || productionBase || DEFAULT_LOCAL_BASE;
  }

  if (isProd) {
    return productionBase || developmentBase || DEFAULT_LOCAL_BASE;
  }

  const isBrowser = typeof window !== 'undefined';
  const hostname = isBrowser ? window.location.hostname || '' : '';
  const hostOrigin = isBrowser ? `${window.location.protocol}//${hostname}` : '';
  const hostBase = hostname ? `${hostOrigin.replace(/\/$/, '')}/api` : '';

  if (hostname && !hostname.includes('localhost') && !hostname.startsWith('127.')) {
    return productionBase || developmentBase || hostBase || DEFAULT_LOCAL_BASE;
  }

  const fallback = hostBase || DEFAULT_LOCAL_BASE;
  return developmentBase || productionBase || fallback;
};

const apiBase = resolveBaseUrl();
if (apiBase) {
  axios.defaults.baseURL = apiBase;
}

const deriveFixAuthUrl = (baseUrl) => {
  const fallback = 'http://localhost:8080/fix-auth.html';

  if (!baseUrl) {
    return fallback;
  }

  try {
    const url = new URL(baseUrl);
    url.pathname = url.pathname.replace(/\/api\/?$/, '');
    if (!url.pathname.endsWith('/')) {
      url.pathname += '/';
    }
    url.pathname += 'fix-auth.html';
    url.search = '';
    url.hash = '';
    return url.toString();
  } catch (error) {
    return fallback;
  }
};

const authFixUrl = deriveFixAuthUrl(apiBase);

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
            const message = error.response.data?.message || '';
            const errorCode = error.response.data?.error || '';
            const url = error.config?.url || '';
            const isAuthRoute = url.includes('/login') || url.includes('/register');
            const skipLogout = error.config && (error.config.__skipAuthRedirect || isAuthRoute);
            
            // Only logout and redirect if it's a token issue and not an auth route
            if (!skipLogout && (message.includes('expired') || message.includes('Invalid') || message.includes('signature') || errorCode === 'TOKEN_EXPIRED' || errorCode === 'INVALID_TOKEN' || errorCode === 'INVALID_SIGNATURE')) {
                // Clear user data
                localStorage.removeItem('user');
                localStorage.removeItem('token');
                
                // Clear Redux persist if it exists
                const keys = Object.keys(localStorage);
                keys.forEach(key => {
                    if (key.includes('persist') || key.includes('redux')) {
                        localStorage.removeItem(key);
                    }
                });
                
                // Show alert with helpful message
                const fixUrl = authFixUrl;
                const shouldRedirectToFix = confirm(
                    'Your session has expired or is invalid.\n\n' +
                    'Click OK to go to the authentication fix page, or Cancel to stay on the login page.'
                );
                
                if (shouldRedirectToFix) {
                    window.location.href = fixUrl;
                } else {
                    window.location.href = '/';
                }
            }
        }
        return Promise.reject(error);
    }
);

export const API_BASE_URL = apiBase;
export { resolveBaseUrl };
export default axios;
