import axios from 'axios';
import { CSRFDebugger } from './utils/csrfDebugger.js';

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
async function refreshCsrfToken() {
    try {
        CSRFDebugger.log('Starting CSRF token refresh');
        const response = await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        if (response.ok) {
            CSRFDebugger.logTokenRefresh(true);
            return response;
        } else {
            const error = new Error(`Failed to refresh CSRF token: ${response.status}`);
            CSRFDebugger.logTokenRefresh(false, error);
            throw error;
        }
    } catch (error) {
        CSRFDebugger.logTokenRefresh(false, error);
        throw error;
    }
}

// Function to refresh both CSRF cookie and meta token
async function refreshCsrfTokenAndMeta() {
    try {
        await refreshCsrfToken();
        // Give the server a moment to set the cookie before updating the meta token
        setTimeout(() => {
            // Update the token from the cookie that should now be set
            updateCsrfToken();
        }, 100);
        return true;
    } catch (error) {
        console.error('Failed to refresh CSRF token and meta:', error);
        return false;
    }
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
let failedRequestsQueue = [];

window.axios.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;
        
        if (error.response && error.response.status === 419) {
            CSRFDebugger.logTokenMismatch();
            
            // Prevent multiple simultaneous token refresh attempts
            if (isRefreshingToken) {
                CSRFDebugger.log('Token refresh already in progress, queuing request');
                return new Promise((resolve, reject) => {
                    failedRequestsQueue.push({ resolve, reject, config: originalRequest });
                });
            }
            
            // Mark that we're refreshing the token
            isRefreshingToken = true;
            originalRequest._retry = true;
            
            try {
                refreshTokenPromise = refreshCsrfToken();
                await refreshTokenPromise;
                
                // Update the token from the new cookie
                updateCsrfToken();
                
                // Get the fresh token
                const newToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
                
                if (newToken) {
                    // Update the failed request's headers
                    originalRequest.headers['X-CSRF-TOKEN'] = newToken;
                    
                    // Retry all queued requests
                    failedRequestsQueue.forEach(({ resolve, reject, config }) => {
                        config.headers['X-CSRF-TOKEN'] = newToken;
                        axios(config).then(resolve).catch(reject);
                    });
                    failedRequestsQueue = [];
                    
                    // Retry the original request
                    return axios(originalRequest);
                } else {
                    console.error('Could not get fresh CSRF token from meta tag');
                    // Try to get token from API endpoint as fallback
                    try {
                        const tokenResponse = await fetch('/api/csrf-token', {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        if (tokenResponse.ok) {
                            const tokenData = await tokenResponse.json();
                            if (tokenData.token) {
                                // Update meta tag
                                const metaTag = document.head.querySelector('meta[name="csrf-token"]');
                                if (metaTag) {
                                    metaTag.content = tokenData.token;
                                }
                                
                                // Update request headers
                                originalRequest.headers['X-CSRF-TOKEN'] = tokenData.token;
                                
                                // Retry queued requests
                                failedRequestsQueue.forEach(({ resolve, reject, config }) => {
                                    config.headers['X-CSRF-TOKEN'] = tokenData.token;
                                    axios(config).then(resolve).catch(reject);
                                });
                                failedRequestsQueue = [];
                                
                                return axios(originalRequest);
                            }
                        }
                    } catch (apiError) {
                        console.error('API token refresh also failed:', apiError);
                    }
                    
                    // Final fallback to page reload
                    window.location.reload();
                    return Promise.reject(error);
                }
            } catch (refreshError) {
                console.error('Failed to refresh CSRF token:', refreshError);
                // Clear queued requests
                failedRequestsQueue.forEach(({ reject }) => reject(refreshError));
                failedRequestsQueue = [];
                
                // Show user-friendly error message before reloading
                if (window.confirm('Ihre Sitzung ist abgelaufen. Die Seite wird neu geladen.')) {
                    window.location.reload();
                } else {
                    window.location.reload();
                }
                return Promise.reject(error);
            } finally {
                isRefreshingToken = false;
                refreshTokenPromise = null;
            }
        }
        
        return Promise.reject(error);
    }
);

// Add request interceptor to ensure fresh token on each request
window.axios.interceptors.request.use(
    config => {
        // Ensure we have the latest CSRF token for each request
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token && token.content) {
            config.headers['X-CSRF-TOKEN'] = token.content;
        }
        
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Export functions to manually manage CSRF token if needed
window.updateCsrfToken = updateCsrfToken;
window.refreshCsrfToken = refreshCsrfToken;
window.refreshCsrfTokenAndMeta = refreshCsrfTokenAndMeta;
