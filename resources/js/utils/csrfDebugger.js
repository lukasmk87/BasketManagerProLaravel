/**
 * CSRF Token Debugger Utility
 * Helps diagnose CSRF token issues in development and staging environments
 */

export class CSRFDebugger {
    static enabled = process.env.NODE_ENV !== 'production';
    
    static log(message, data = {}) {
        if (!this.enabled) return;
        
        console.group(`🛡️ CSRF Debug: ${message}`);
        console.log('Timestamp:', new Date().toISOString());
        
        // Log current token state
        const metaToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
        const axiosToken = window.axios?.defaults?.headers?.common['X-CSRF-TOKEN'];
        
        console.log('Meta Token:', metaToken?.substring(0, 10) + '...');
        console.log('Axios Token:', axiosToken?.substring(0, 10) + '...');
        console.log('Tokens Match:', metaToken === axiosToken);
        
        if (Object.keys(data).length > 0) {
            console.log('Additional Data:', data);
        }
        
        console.groupEnd();
    }
    
    static logTokenMismatch() {
        this.log('Token Mismatch Detected', {
            userAgent: navigator.userAgent,
            url: window.location.href,
            referrer: document.referrer,
            cookiesEnabled: navigator.cookieEnabled,
            sessionStorage: !!window.sessionStorage,
            localStorage: !!window.localStorage,
        });
    }
    
    static logTokenRefresh(success = true, error = null) {
        this.log(`Token Refresh ${success ? 'Success' : 'Failed'}`, {
            success,
            error: error?.message,
            timestamp: Date.now()
        });
    }
    
    static logFormSubmission(formAction, method) {
        const metaToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
        
        this.log('Form Submission', {
            action: formAction,
            method,
            hasToken: !!metaToken,
            tokenLength: metaToken?.length,
            timestamp: Date.now()
        });
    }
    
    static startPeriodicCheck() {
        if (!this.enabled) return;
        
        setInterval(() => {
            const metaToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
            const axiosToken = window.axios?.defaults?.headers?.common['X-CSRF-TOKEN'];
            
            if (metaToken && axiosToken && metaToken !== axiosToken) {
                this.log('Periodic Check: Token Mismatch Found');
                // Auto-fix the mismatch
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = metaToken;
                this.log('Auto-fixed token mismatch');
            }
        }, 30000); // Check every 30 seconds
    }
}

// Auto-start periodic checking in debug mode
if (CSRFDebugger.enabled) {
    document.addEventListener('DOMContentLoaded', () => {
        CSRFDebugger.startPeriodicCheck();
        CSRFDebugger.log('CSRF Debugger Initialized');
    });
}

export default CSRFDebugger;