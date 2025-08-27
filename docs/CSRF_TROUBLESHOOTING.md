# CSRF Token Troubleshooting Guide

## Overview
This guide helps diagnose and fix CSRF token issues (HTTP 419 errors) in BasketManager Pro, particularly on the staging environment.

## Common Symptoms
- Forms fail with 419 "Page Expired" errors
- "Failed to refresh CSRF token" console messages
- Users unable to submit forms after being idle
- Random token mismatches during form submissions

## Root Causes

### 1. Session Configuration Issues
- **Domain Mismatch**: Session domain doesn't match the staging domain
- **Cookie Security**: HTTPS-only cookies on non-HTTPS environments
- **SameSite Policy**: Incorrect SameSite settings blocking cookies

### 2. Token Synchronization Issues
- **Meta Tag Stale**: CSRF meta tag not updated after token refresh
- **Axios Headers**: Request headers not synchronized with fresh tokens
- **Multiple Requests**: Concurrent requests causing race conditions

### 3. Browser/Environment Issues
- **Cookie Blocking**: Browser blocking third-party cookies
- **Cache Issues**: Stale JavaScript bundles with old CSRF handling
- **Session Timeout**: User sessions expiring during form entry

## Implemented Solutions

### 1. Enhanced Axios Interceptors (`bootstrap.js`)
```javascript
// Automatic token refresh on 419 errors
// Queue failed requests during token refresh
// Smart retry mechanism with fresh tokens
// User-friendly error messages
```

### 2. Proactive Token Management (`app.js`)
```javascript
// Periodic token refresh (every 30 minutes)
// Refresh on tab visibility change
// Refresh before navigation
```

### 3. Form-Level Protection (`Players/Edit.vue`)
```javascript
// Pre-submission token refresh
// Enhanced error handling
// Better user feedback
```

### 4. Debug Utilities (`csrfDebugger.js`)
```javascript
// Comprehensive CSRF debugging
// Token mismatch detection
// Form submission tracking
// Automatic token synchronization
```

## Environment Configuration

### Staging Environment (`.env.staging.example`)
```env
# Critical CSRF settings for staging
SESSION_DOMAIN=staging.basketmanager-pro.de
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=staging.basketmanager-pro.de,localhost,127.0.0.1
```

### Production Environment
```env
# Stricter security for production
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
SESSION_HTTP_ONLY=true
```

## Debugging Steps

### 1. Enable Debug Mode
```javascript
// Check browser console for CSRF debug messages
// Look for "🛡️ CSRF Debug:" entries
```

### 2. Verify Configuration
```bash
# Check session configuration
php artisan config:show session

# Check Sanctum domains
php artisan config:show sanctum.stateful
```

### 3. Browser Developer Tools
```javascript
// Check cookies in Application tab
// Verify XSRF-TOKEN and laravel_session cookies
// Check Network tab for failed requests with 419 status
```

### 4. Server Logs
```bash
# Check Laravel logs for CSRF errors
tail -f storage/logs/laravel.log | grep -i csrf

# Check web server access logs
tail -f /var/log/nginx/access.log | grep 419
```

## Manual Testing

### 1. Token Refresh Test
```javascript
// In browser console
window.refreshCsrfTokenAndMeta().then(() => console.log('Success'));
```

### 2. Form Submission Test
```javascript
// Check token before/after form submission
const token = document.querySelector('meta[name="csrf-token"]').content;
console.log('Token:', token.substring(0, 10) + '...');
```

### 3. Session Verification
```php
// In Laravel tinker
php artisan tinker
>>> request()->session()->token()
```

## Prevention Strategies

### 1. Regular Token Refresh
- Implement periodic background token refresh
- Refresh tokens on user activity
- Preemptive refresh before form submissions

### 2. Better Error Handling
- User-friendly error messages
- Automatic retry mechanisms
- Graceful degradation on failures

### 3. Environment-Specific Configuration
- Proper domain configuration for each environment
- Appropriate security settings
- Correct Sanctum domain whitelisting

## Emergency Fixes

### If Users Can't Submit Forms
```javascript
// Quick fix via browser console
window.location.reload(); // Nuclear option

// Or refresh token manually
window.refreshCsrfToken().then(() => window.location.reload());
```

### Server-Side Quick Fix
```php
// Temporarily disable CSRF for specific routes (NOT RECOMMENDED)
// In VerifyCsrfToken middleware, add to $except array
protected $except = [
    'emergency-route/*',
];
```

## Monitoring

### Key Metrics to Monitor
- 419 error rate in application logs
- Failed form submissions
- Token refresh success rate
- User session duration

### Alerts to Set Up
- High 419 error rate (>5% of requests)
- Token refresh failures
- Session cookie issues
- Unusual CSRF token patterns

## Support

If CSRF issues persist:
1. Check browser console for debug messages
2. Verify environment configuration
3. Test with different browsers
4. Check server logs for patterns
5. Consider browser extension conflicts

Last updated: $(date)