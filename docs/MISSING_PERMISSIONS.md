# Missing Permissions Analysis

**Date:** 2025-11-13
**Current Permissions:** 94
**Documented Permissions:** 136
**Missing:** 42 permissions

## Overview

This document lists the 42 permissions that are documented in `BERECHTIGUNGS_MATRIX.md` but missing from `RoleAndPermissionSeeder.php`.

## Missing Permission Categories

### 1. Video Management (6 permissions)
Based on `VideoFileController` and `VideoAnnotationController`:
- `view videos`
- `upload videos`
- `edit videos`
- `delete videos`
- `annotate videos`
- `manage video analysis`

### 2. Gym/Facility Management (7 permissions)
Based on `GymHallController`, `GymBookingRequestController`, `GymManagementController`:
- `view gym halls`
- `create gym halls`
- `edit gym halls`
- `delete gym halls`
- `view bookings`
- `create bookings`
- `manage bookings`

### 3. Federation Integration (4 permissions)
Based on `Federation\DBBController` and `Federation\FIBAController`:
- `view federation data`
- `sync federation data`
- `manage dbb integration`
- `manage fiba integration`

### 4. Machine Learning & Analytics (4 permissions)
Based on `MLAnalyticsController`:
- `view ml models`
- `train ml models`
- `view predictions`
- `manage ml datasets`

### 5. Shot Charts & Advanced Stats (3 permissions)
Based on `ShotChartController`:
- `view shot charts`
- `edit shot charts`
- `export shot charts`

### 6. Push Notifications (3 permissions)
Based on `PushNotificationController`:
- `manage push subscriptions`
- `send push notifications`
- `view notification analytics`

### 7. API Management (3 permissions)
For API token and usage management:
- `view api usage`
- `manage api tokens`
- `view api logs`

### 8. Security Management (3 permissions)
Based on `SecurityController`:
- `view security logs`
- `manage 2fa settings`
- `manage security policies`

### 9. User Preferences (2 permissions)
Based on `UserPreferencesController`:
- `view user preferences`
- `edit user preferences`

### 10. File Management (2 permissions)
Based on `FileUploadController`:
- `upload files`
- `manage file storage`

### 11. Import/Export Features (3 permissions)
Based on `GameImportController` and `UserImportController`:
- `import games`
- `import users`
- `import data`

### 12. PWA Management (2 permissions)
Based on `PWAController`:
- `manage pwa settings`
- `update service worker`

## Permissions by Role Assignment

### Super Admin
- Should have ALL 136 permissions (100%)
- Currently has 94 permissions

### Admin
- Should have 135 permissions (all except impersonate)
- Currently has 93 permissions

### Club Admin
- Should have ~72 permissions
- Currently has ~65 permissions
- Missing: Video management, advanced gym features, ML features

### Trainer
- Should have ~45 permissions
- Currently has ~40 permissions
- Missing: Video annotation, shot chart editing

### Assistant Coach
- Should have ~30 permissions
- Currently has ~28 permissions

### Other Roles
- Permissions mostly complete for basic viewing/access

## Recommended Actions

1. **Immediate:** Add these 42 permissions to `RoleAndPermissionSeeder.php`
2. **Create Migration:** `add_missing_permissions_to_system`
3. **Update Documentation:** Sync `BERECHTIGUNGS_MATRIX.md` with reality
4. **Add Tests:** Verify permission counts match documentation

## Notes

- Super Admin still functions correctly due to `Gate::before()` bypass
- Missing permissions primarily affect non-Super Admin roles
- No critical security issues, but feature access may be inconsistent
- Some controllers may lack proper authorization checks

## Verification

After adding these permissions, verify:
```bash
php artisan tinker --execute="echo Permission::count();"
# Expected output: 136

php artisan tinker --execute="echo Role::where('name', 'super_admin')->first()->permissions->count();"
# Expected output: 136
```
