# Manual Test Report - Club Admin User Management Frontend

**Date:** 2025-11-22
**Feature:** Club Admin Benutzerverwaltung - Frontend Components
**Files Modified:**
- `resources/js/Pages/Admin/Users.vue` - Club filter dropdown + enhanced clubs column
- `resources/js/Pages/Admin/EditUser.vue` - Enhanced password reset button

---

## Test Environment Setup

**Dev Server:** http://127.0.0.1:8000
**Vite:** http://localhost:5173
**Database:** MySQL (all migrations ran)

### Test Data

**Users:**
1. **Lukas** (lukas@kotowicz.info) - `super_admin`, No clubs
2. **Admin GTV** (gtv@kotowicz.info) - `club_admin`, GTV Baskets
3. **Ole Ahnepohl** (ole@kotowicz.info) - `club_admin`, `trainer`, `player`, GTV Baskets
4. **Max Mustermann** (max.mustermann@test.de) - `player`, GTV Baskets
5. **Anna Schmidt** (anna.schmidt@test.de) - `player`, Starting 5
6. **Tom Becker** (tom.becker@test.de) - `player`, Starting 5 + GTV Baskets

**Clubs:**
1. **Starting 5** (ID: 1) - 2 users
2. **GTV Baskets** (ID: 2) - 4 users

---

## Test Cases

### 1. Club Filter Dropdown (Users.vue)

#### Test 1.1: Filter Dropdown Visibility
- **As:** Super Admin (lukas@kotowicz.info)
- **Action:** Navigate to `/admin/users`
- **Expected:** Club filter dropdown should be visible with all clubs
- **Test URL:** http://127.0.0.1:8000/admin/users
- **Result:** ⏳ Pending

#### Test 1.2: Filter Dropdown for Club Admin
- **As:** Club Admin (gtv@kotowicz.info)
- **Action:** Navigate to `/admin/users`
- **Expected:** Club filter dropdown should only show "GTV Baskets"
- **Test URL:** http://127.0.0.1:8000/admin/users
- **Result:** ⏳ Pending

#### Test 1.3: Filter by Club
- **As:** Super Admin
- **Action:** Select "GTV Baskets" in club filter
- **Expected:**
  - URL changes to `/admin/users?club_id=2`
  - User list shows only: Admin GTV, Ole, Max, Tom (4 users)
  - Anna should NOT appear (she's in Starting 5 only)
- **Result:** ⏳ Pending

#### Test 1.4: Filter by Starting 5
- **As:** Super Admin
- **Action:** Select "Starting 5" in club filter
- **Expected:**
  - URL changes to `/admin/users?club_id=1`
  - User list shows: Anna, Tom (2 users)
- **Result:** ⏳ Pending

#### Test 1.5: Clear Club Filter
- **As:** Super Admin
- **Action:** Select "Alle Clubs" option
- **Expected:**
  - URL changes to `/admin/users` (no club_id parameter)
  - All 6 users should appear
- **Result:** ⏳ Pending

#### Test 1.6: Combine Filters
- **As:** Super Admin
- **Action:**
  1. Select "GTV Baskets" club
  2. Type "Max" in search box
- **Expected:**
  - URL: `/admin/users?club_id=2&search=Max`
  - Only "Max Mustermann" appears
- **Result:** ⏳ Pending

---

### 2. Clubs Column Display (Users.vue)

#### Test 2.1: Clubs Badge Display
- **As:** Super Admin
- **Action:** Navigate to `/admin/users` (no filter)
- **Expected:**
  - Lukas: "Kein Club" (gray text)
  - Admin GTV, Ole, Max: "GTV Baskets" badge (indigo)
  - Anna: "Starting 5" badge (indigo)
  - Tom: Two badges "Starting 5" and "GTV Baskets"
- **Result:** ⏳ Pending

#### Test 2.2: Clubs Badge Styling
- **Expected Styling:**
  - Background: `bg-indigo-100`
  - Text: `text-indigo-800`
  - Rounded corners: `rounded`
  - Size: `text-xs`
  - Padding: `px-2 py-0.5`
- **Result:** ⏳ Pending

---

### 3. Password Reset Button (EditUser.vue)

#### Test 3.1: Password Reset Button as Super Admin
- **As:** Super Admin
- **Action:**
  1. Navigate to `/admin/users`
  2. Click "Bearbeiten" for Max Mustermann
  3. Scroll to bottom
  4. Click "Passwort-Reset senden"
- **Expected:**
  1. Confirmation dialog: "Möchten Sie einen Passwort-Reset-Link an max.mustermann@test.de senden?"
  2. Click "OK"
  3. Button shows loading spinner + "Sende Reset-Link..."
  4. Button disabled during operation
  5. Success alert: "✅ Passwort-Reset-Link wurde erfolgreich an max.mustermann@test.de gesendet."
- **Result:** ⏳ Pending

#### Test 3.2: Password Reset Button as Club Admin
- **As:** Club Admin (gtv@kotowicz.info)
- **Action:**
  1. Navigate to `/admin/users`
  2. Click "Bearbeiten" for Max Mustermann (player in GTV Baskets)
  3. Click "Passwort-Reset senden"
- **Expected:** Same as Test 3.1 (success)
- **Result:** ⏳ Pending

#### Test 3.3: Password Reset - Unauthorized User
- **As:** Club Admin (gtv@kotowicz.info)
- **Action:**
  1. Try to edit Anna Schmidt (player in Starting 5, not in GTV Baskets)
  2. Direct URL: `/admin/users/{anna_id}/edit`
- **Expected:**
  - 403 Forbidden or redirect
  - Should not be able to access edit page
- **Result:** ⏳ Pending

#### Test 3.4: Password Reset - Loading State
- **As:** Super Admin
- **Action:** Click password reset button
- **Expected During Loading:**
  - Button background: `bg-blue-400`
  - Cursor: `cursor-wait`
  - Spinner visible (animated rotating circle)
  - Text: "Sende Reset-Link..."
  - Button disabled
- **Result:** ⏳ Pending

#### Test 3.5: Password Reset - Error Handling
- **As:** Super Admin
- **Action:**
  1. Temporarily disable mail server (or simulate error)
  2. Click password reset button
- **Expected:**
  - Error alert: "❌ Fehler beim Senden des Passwort-Reset-Links."
  - Button returns to normal state
- **Result:** ⏳ Pending (requires mail config manipulation)

---

### 4. Reactive Filtering (Users.vue)

#### Test 4.1: Debounced Search
- **As:** Super Admin
- **Action:** Type "Max" slowly in search box
- **Expected:**
  - No immediate filter (debounce 300ms)
  - After 300ms, filter applies
  - URL updates to `/admin/users?search=Max`
- **Result:** ⏳ Pending

#### Test 4.2: Multiple Filter Changes
- **As:** Super Admin
- **Action:**
  1. Select "GTV Baskets" club
  2. Type "Tom" in search
  3. Select "player" role
- **Expected:**
  - Final URL: `/admin/users?club_id=2&search=Tom&role=player`
  - Only Tom Becker appears (player in GTV Baskets)
- **Result:** ⏳ Pending

#### Test 4.3: Clear All Filters
- **As:** Super Admin
- **Action:** Click "Filter zurücksetzen" button
- **Expected:**
  - All filters cleared
  - selectedClub = ''
  - selectedRole = ''
  - selectedStatus = ''
  - search = ''
  - URL: `/admin/users`
  - All users shown
- **Result:** ⏳ Pending

---

### 5. Authorization & Policy Checks

#### Test 5.1: Club Admin Cannot Edit Other Admins
- **As:** Club Admin (gtv@kotowicz.info)
- **Action:** Try to view/edit Super Admin or other Club Admin
- **Expected:**
  - Delete button should be disabled OR
  - Edit access denied by policy
- **Result:** ⏳ Pending

#### Test 5.2: Club Admin Can Only See Their Clubs
- **As:** Club Admin (gtv@kotowicz.info)
- **Action:** Navigate to `/admin/users`
- **Expected:**
  - Club filter dropdown only shows "GTV Baskets"
  - Should NOT see "Starting 5" option
- **Result:** ⏳ Pending

---

### 6. Visual & UI Checks

#### Test 6.1: Responsive Layout
- **Action:** Resize browser window
- **Expected:**
  - Grid changes from 5 columns to fewer on smaller screens
  - Filter dropdowns stack vertically on mobile
  - Club badges wrap correctly
- **Result:** ⏳ Pending

#### Test 6.2: Loading States
- **Expected:**
  - Page transitions smooth
  - Loading spinner on password reset
  - No flash of unstyled content
- **Result:** ⏳ Pending

---

## Browser Console Checks

### Expected Console Logs (EditUser.vue)

When sending password reset:
```javascript
🗑️ Deleting user: 4 Route: http://127.0.0.1:8000/admin/users/4  // If delete clicked
✅ User deleted successfully  // On delete success
```

No errors expected in console for normal operations.

---

## Backend API Verification

### API Endpoints to Verify

1. **GET `/admin/users?club_id=2`**
   - Should return only users in club 2
   - Response includes `clubs` relationship

2. **POST `/admin/users/{id}/send-password-reset`**
   - Should send password reset email
   - Logs audit entry
   - Returns success flash message

3. **Authorization Check**
   - Club admin with `club_id` parameter should only access users in their clubs
   - Policy enforces admin protection

---

## Test Execution Instructions

### To Run Manual Tests:

1. **Start Dev Server** (already running):
   ```bash
   composer dev
   ```
   - Server: http://127.0.0.1:8000
   - Vite: http://localhost:5173

2. **Login Credentials:**
   - **Super Admin:** lukas@kotowicz.info / password
   - **Club Admin:** gtv@kotowicz.info / password

3. **Test Routes:**
   - Users List: http://127.0.0.1:8000/admin/users
   - Edit User: http://127.0.0.1:8000/admin/users/{id}/edit

4. **Monitor Logs:**
   - Check `storage/logs/laravel.log` for password reset logs
   - Watch browser console for JS errors

5. **Database Checks:**
   ```bash
   php artisan tinker --execute="DB::table('password_reset_tokens')->latest()->first();"
   ```

---

## Checklist Before Testing

- [x] Dev server running
- [x] Database migrated
- [x] Test users created
- [x] Vite building assets
- [x] Queue worker running
- [ ] Browser devtools open
- [ ] Network tab monitoring
- [ ] Console tab monitoring

---

## Next Steps

1. Execute all test cases above
2. Document results (✅ Pass / ❌ Fail / ⚠️ Warning)
3. Fix any issues found
4. Repeat failed tests
5. Mark frontend testing as complete

---

## Notes

- Password reset requires mail configuration (check `.env` for `MAIL_*` settings)
- If mail fails, check `storage/logs/laravel.log`
- Authorization is policy-based, check `app/Policies/UserPolicy.php` for logic
- Club filter uses `club_id` request parameter for filtering

---

**Status:** ⏳ Ready for manual testing
**Tester:** TBD
**Estimated Time:** 30-45 minutes
