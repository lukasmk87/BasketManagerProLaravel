/**
 * Centralized CSRF Token Management
 * 
 * This module provides utilities for managing CSRF tokens across the application,
 * ensuring proper token handling for all requests and automatic refresh when needed.
 */

/**
 * Get the current CSRF token from various sources
 * Priority: Inertia props > Meta tag > Axios headers
 */
export function getCurrentToken() {
    // Try to get token from Inertia page props first (most reliable)
    if (window.page?.props?.csrf_token) {
        return window.page.props.csrf_token;
    }
    
    // Fallback to meta tag
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (metaToken) {
        return metaToken;
    }
    
    // Last resort: check axios headers
    return window.axios?.defaults?.headers?.common['X-CSRF-TOKEN'];
}

/**
 * Update CSRF token in all relevant places
 */
export function updateToken(newToken) {
    if (!newToken) return false;
    
    // Update meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        metaTag.setAttribute('content', newToken);
    }
    
    // Update axios headers
    if (window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
    }
    
    // Update Inertia page props if available
    if (window.page?.props) {
        window.page.props.csrf_token = newToken;
    }
    
    return true;
}

/**
 * Refresh CSRF token from the server
 */
export async function refreshToken() {
    try {
        const response = await fetch('/api/csrf-token', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.token) {
                updateToken(data.token);
                return data.token;
            }
        }
    } catch (error) {
        console.warn('Failed to refresh CSRF token:', error);
    }
    
    return null;
}

/**
 * Ensure token is set and valid for axios requests
 */
export function ensureTokenForAxios() {
    const token = getCurrentToken();
    if (token && window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        return true;
    }
    return false;
}

/**
 * Get token for manual form submissions
 */
export function getTokenForForm() {
    return getCurrentToken();
}

/**
 * Handle 419 errors by refreshing token and retrying
 */
export async function handle419Error(originalRequest) {
    const newToken = await refreshToken();
    if (newToken && originalRequest) {
        // Update the request with new token
        if (originalRequest.headers) {
            originalRequest.headers['X-CSRF-TOKEN'] = newToken;
        }
        return newToken;
    }
    return null;
}

/**
 * Setup automatic token monitoring
 */
export function setupTokenMonitoring() {
    // Monitor page visibility changes to refresh token when page becomes visible
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            // Page became visible, ensure token is still valid
            const token = getCurrentToken();
            if (token) {
                ensureTokenForAxios();
            } else {
                refreshToken();
            }
        }
    });
    
    // Monitor focus events for similar behavior
    window.addEventListener('focus', () => {
        ensureTokenForAxios();
    });
}

// Initialize token monitoring when module loads
if (typeof window !== 'undefined') {
    setupTokenMonitoring();
}