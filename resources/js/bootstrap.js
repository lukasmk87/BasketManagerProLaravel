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

// Initial CSRF token setup
updateCsrfToken();

// Configure axios for CSRF cookie handling
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Add response interceptor to handle 419 CSRF errors
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            console.warn('CSRF token mismatch detected, attempting to refresh token');
            
            // Try to get a fresh CSRF token from the server
            return axios.get('/sanctum/csrf-cookie')
                .then(() => {
                    // Update the CSRF token from meta tag
                    updateCsrfToken();
                    
                    // Retry the original request
                    const originalRequest = error.config;
                    const newToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
                    if (newToken) {
                        originalRequest.headers['X-CSRF-TOKEN'] = newToken;
                    }
                    
                    return axios(originalRequest);
                })
                .catch(refreshError => {
                    console.error('Failed to refresh CSRF token:', refreshError);
                    // If we can't refresh the token, reload the page
                    window.location.reload();
                    return Promise.reject(error);
                });
        }
        
        return Promise.reject(error);
    }
);

// Export function to manually update CSRF token if needed
window.updateCsrfToken = updateCsrfToken;
