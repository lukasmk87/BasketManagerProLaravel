import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configure CSRF token for axios requests
function updateCsrfToken() {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        return token.content;
    } else {
        console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
        return null;
    }
}

// Function to get fresh CSRF token from Laravel
function refreshCsrfToken() {
    return fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'same-origin'
    });
}

// Initial CSRF token setup
updateCsrfToken();

// Configure axios for CSRF cookie handling
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Add response interceptor to handle 419 CSRF errors
let isRefreshingToken = false;
let refreshTokenPromise = null;

window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            console.warn('CSRF token mismatch detected, attempting to refresh token');
            
            // Prevent multiple simultaneous token refresh attempts
            if (isRefreshingToken) {
                return refreshTokenPromise.then(() => {
                    const newToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
                    if (newToken) {
                        error.config.headers['X-CSRF-TOKEN'] = newToken;
                        return axios(error.config);
                    }
                    throw error;
                });
            }
            
            isRefreshingToken = true;
            refreshTokenPromise = axios.get('/sanctum/csrf-cookie')
                .then(() => {
                    // Force a page refresh to get new meta token
                    window.location.reload();
                    return Promise.reject(error); // This won't execute due to reload
                })
                .catch(refreshError => {
                    console.error('Failed to refresh CSRF token:', refreshError);
                    window.location.reload();
                    return Promise.reject(error);
                })
                .finally(() => {
                    isRefreshingToken = false;
                    refreshTokenPromise = null;
                });
                
            return refreshTokenPromise;
        }
        
        return Promise.reject(error);
    }
);

// Export functions to manually manage CSRF token if needed
window.updateCsrfToken = updateCsrfToken;
window.refreshCsrfToken = refreshCsrfToken;
