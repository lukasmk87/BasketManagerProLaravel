# CSRF Fix Deployment Instructions

## Overview
This document outlines the steps to deploy the CSRF token fix to the staging environment at `https://staging.basketmanager-pro.de`.

## Files Changed

### 1. Frontend Files
- `resources/js/bootstrap.js` - Enhanced CSRF token handling
- `resources/js/app.js` - Added periodic token refresh
- `resources/js/Pages/Players/Edit.vue` - Improved form submission
- `resources/js/utils/csrfDebugger.js` - New debugging utility

### 2. Configuration Files
- `.env.staging.example` - Staging environment configuration template
- `app/Http/Middleware/EnhancedCsrfProtection.php` - New middleware for debugging

### 3. Documentation
- `docs/CSRF_TROUBLESHOOTING.md` - Troubleshooting guide
- `scripts/test-csrf-fix.js` - Testing script

## Deployment Steps

### Step 1: Update Environment Configuration
```bash
# On staging server, update .env file with these critical settings:
SESSION_DOMAIN=staging.basketmanager-pro.de
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=staging.basketmanager-pro.de,localhost,127.0.0.1
```

### Step 2: Deploy Code Changes
```bash
# Pull latest changes
git pull origin main

# Install dependencies (if any new ones)
npm install
composer install --no-dev --optimize-autoloader

# Build frontend assets
npm run build
```

### Step 3: Clear Caches
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild config cache
php artisan config:cache
```

### Step 4: Restart Services
```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart web server (nginx/apache)
sudo systemctl restart nginx
# OR
sudo systemctl restart apache2

# Restart queue workers if using
php artisan queue:restart
```

### Step 5: Verify Configuration
```bash
# Check session configuration
php artisan config:show session

# Check Sanctum configuration
php artisan config:show sanctum.stateful

# Test database connection
php artisan migrate:status
```

## Testing the Fix

### Browser Testing
1. Open `https://staging.basketmanager-pro.de`
2. Navigate to `/players/1/edit` (or any player edit page)
3. Open browser console (F12)
4. Look for CSRF debug messages starting with "🛡️"
5. Fill out and submit the form
6. Verify no 419 errors occur

### Script Testing
1. Copy contents of `scripts/test-csrf-fix.js`
2. Paste into browser console on staging site
3. Run the script and check for any failures
4. All tests should pass or show expected behaviors

### Manual Testing Scenarios
1. **Form Submission Test**:
   - Fill out player edit form
   - Submit and verify success
   - Check network tab for no 419 errors

2. **Token Refresh Test**:
   - Open browser console
   - Run: `window.refreshCsrfTokenAndMeta()`
   - Should see success message

3. **Idle Session Test**:
   - Leave form open for 10+ minutes
   - Try to submit form
   - Should either succeed or show friendly error message

## Monitoring

### What to Monitor
```bash
# Monitor 419 errors in Laravel logs
tail -f storage/logs/laravel.log | grep "419\|CSRF"

# Monitor web server access logs for 419 responses
tail -f /var/log/nginx/access.log | grep "419"

# Monitor application metrics
# Look for increased error rates after deployment
```

### Success Indicators
- ✅ No 419 errors in application logs
- ✅ Forms submit successfully
- ✅ CSRF debug messages appear in browser console
- ✅ Token refresh functions work properly
- ✅ Users can complete forms without issues

### Failure Indicators
- ❌ Continued 419 errors
- ❌ Forms fail to submit
- ❌ Console errors about CSRF functions
- ❌ Users report "session expired" errors

## Rollback Plan

If the fix causes issues:

### Quick Rollback
```bash
# Rollback to previous commit
git log --oneline -10  # Find previous commit
git checkout <previous-commit-hash>

# Rebuild assets
npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
```

### Emergency Fix
```bash
# Temporarily disable CSRF for critical routes
# Edit app/Http/Middleware/VerifyCsrfToken.php
# Add problematic routes to $except array (TEMPORARY ONLY)
```

## Post-Deployment Verification

### Checklist
- [ ] All environment variables updated correctly
- [ ] Frontend assets rebuilt and served correctly
- [ ] No JavaScript console errors on page load
- [ ] CSRF debug messages visible in console
- [ ] Forms can be submitted successfully
- [ ] No 419 errors in server logs
- [ ] Token refresh functions working
- [ ] Monitoring alerts configured

### User Acceptance Testing
1. Have test users try the player edit functionality
2. Monitor for any user reports of form failures
3. Check analytics for form completion rates
4. Verify no increase in support tickets related to forms

## Support

### If Issues Persist
1. Check browser console for CSRF debug messages
2. Review server logs for patterns
3. Test with different browsers/devices
4. Verify environment configuration
5. Check network tab for failed requests

### Contact
- Development Team: [team@basketmanager-pro.de]
- DevOps: [devops@basketmanager-pro.de]
- Emergency: [emergency@basketmanager-pro.de]

---

**Deployment Date**: _______________  
**Deployed By**: _______________  
**Tested By**: _______________  
**Sign-off**: _______________