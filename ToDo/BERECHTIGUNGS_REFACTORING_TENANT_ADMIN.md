# Berechtigungs-Refactoring: admin → tenant_admin

> **Letztes Update:** 2025-12-05
> **Status:** Code-Änderungen abgeschlossen ✅ | Deployment ausstehend ⏳

---

## 🚀 Nächste Schritte

```bash
# 1. Migrations ausführen
php artisan migrate

# 2. Cache leeren
php artisan cache:clear
php artisan permission:cache-reset
php artisan config:clear
php artisan route:clear

# 3. Optional: Seeder erneut ausführen (falls neue Installation)
php artisan db:seed --class=RoleAndPermissionSeeder

# 4. Tests ausführen
composer test
```

---

## Fortschritt

| Phase | Status | Dateien |
|-------|--------|---------|
| 1. Migrations | ✅ Erledigt | 3 Dateien erstellt |
| 2. Models | ✅ Erledigt | User.php, Tenant.php |
| 3. Services | ✅ Erledigt | UserRoleService.php, TenantService.php |
| 4. Middleware | ✅ Erledigt | AdminMiddleware.php, EnsureOnboardingComplete.php |
| 5. Seeder | ✅ Erledigt | RoleAndPermissionSeeder.php |
| 6. Policies | ✅ Erledigt | 21 Dateien |
| 7. Controllers | ✅ Erledigt | 17 Controller-Dateien |
| 8. Routes | ✅ Erledigt | 7 Route-Dateien |
| 9. Requests | ⏳ Ausstehend | 6 Dateien (optional) |
| 10. Migration ausführen | ⏳ Ausstehend | `php artisan migrate` |
| 11. Cache leeren | ⏳ Ausstehend | `php artisan cache:clear && php artisan permission:cache-reset` |
| 12. Tests anpassen | ⏳ Ausstehend | Test-Dateien |

### Geänderte Dateien

**Migrations:**
```
✅ database/migrations/2025_12_05_100001_create_tenant_user_table.php
✅ database/migrations/2025_12_05_100002_rename_admin_role_to_tenant_admin.php
✅ database/migrations/2025_12_05_100003_migrate_admin_users_to_tenant_user.php
```

**Models:**
```
✅ app/Models/User.php (erweitert: administeredTenants(), getAdministeredTenantIds(), isTenantAdminFor(), isTenantAdmin())
✅ app/Models/Tenant.php (erweitert: adminUsers(), activeAdmins())
```

**Services:**
```
✅ app/Services/User/UserRoleService.php (komplett überarbeitet)
✅ app/Services/TenantService.php (Admin-Methoden hinzugefügt: userHasAdminAccess, assignTenantAdmin, removeTenantAdmin, updateTenantAdmin, getTenantAdmins, transferTenantAdmin)
```

**Middleware:**
```
✅ app/Http/Middleware/AdminMiddleware.php (Tenant-Scope-Check)
✅ app/Http/Middleware/EnsureOnboardingComplete.php (admin → tenant_admin)
```

**Seeder:**
```
✅ database/seeders/RoleAndPermissionSeeder.php (admin → tenant_admin)
```

**Policies (21 Dateien):**
```
✅ app/Policies/UserPolicy.php
✅ app/Policies/ClubPolicy.php (inkl. manageBilling Einschränkung für club_admin)
✅ app/Policies/ClubSubscriptionPlanPolicy.php
✅ app/Policies/ClubInvitationPolicy.php
✅ app/Policies/EmergencyContactPolicy.php
✅ app/Policies/GymHallPolicy.php
✅ app/Policies/GameRegistrationPolicy.php
✅ app/Policies/TournamentAwardPolicy.php
✅ app/Policies/TrainingRegistrationPolicy.php
✅ app/Policies/PlayerPolicy.php
✅ app/Policies/TeamPolicy.php
✅ app/Policies/PlaybookPolicy.php
✅ app/Policies/PlayPolicy.php
✅ app/Policies/SeasonPolicy.php
✅ app/Policies/DrillPolicy.php
✅ app/Policies/TrainingSessionPolicy.php
✅ app/Policies/RolePolicy.php
✅ app/Policies/PlayerRegistrationInvitationPolicy.php
```

**Controllers (17 Dateien):**
```
✅ app/Http/Controllers/GameController.php
✅ app/Http/Controllers/PlayerController.php
✅ app/Http/Controllers/TeamController.php
✅ app/Http/Controllers/ClubController.php
✅ app/Http/Controllers/Api/GameController.php
✅ app/Http/Controllers/Gym/GymCourtController.php
✅ app/Http/Controllers/Gym/GymTimeSlotController.php
✅ app/Http/Controllers/Gym/GymDashboardController.php
✅ app/Http/Controllers/PushNotificationController.php
✅ app/Http/Controllers/StatisticsController.php
✅ app/Http/Controllers/GameImportController.php
✅ app/Http/Controllers/RoleController.php
✅ app/Http/Controllers/ClubAdmin/ClubSubscriptionAdminController.php
✅ app/Http/Controllers/TrainingController.php
```

**Routes (7 Dateien):**
```
✅ routes/web.php (Zeile 284)
✅ routes/api.php (Zeile 170)
✅ routes/debug.php (Zeile 68)
✅ routes/emergency.php (Zeile 117)
✅ routes/player_registration.php (Zeilen 25, 83)
✅ routes/club_invitation.php (Zeile 23)
✅ routes/club_admin.php (Zeile 29)
```

---

## Zusammenfassung

Refactoring der Rollen-Hierarchie von:
- Super Admin / Admin / Club Admin

Zu:
- **Super Admin** (System-Level) - Überblickt ALLE Tenants
- **Tenant-Admin** (Tenant-Level) - Volle Kontrolle im Tenant inkl. Billing
- **Club-Admin** (Club-Level) - Alles im Club, KEINE Billing-Rechte

---

## Ziel-Hierarchie

```
SUPER ADMIN (System-Level)
    │ tenant_id = NULL, sieht ALLE Tenants
    │ Bypassed alle Scopes via Gate::before()
    │
    └── TENANT-ADMIN (Tenant-Level) ← NEU, ersetzt 'admin'
            │ Volle Kontrolle im Tenant inkl. Billing
            │ Kann mehreren Tenants zugeordnet sein (tenant_user Pivot)
            │
            └── CLUB-ADMIN (Club-Level)
                    │ Alles im Club, Basis-Settings ja (Logo, Name)
                    │ KEINE Subscription/Billing-Rechte
                    │
                    └── Trainer (Team-Scoped)
                            │
                            └── Assistant Coach, Team Manager, Scorer, etc.
                                    │
                                    └── Player, Parent, Guest
```

---

## Entscheidungen

| Frage | Entscheidung |
|-------|--------------|
| Admin-Rolle umbenennen oder neue? | **Komplett ersetzen** - 'admin' wird zu 'tenant_admin' |
| Multi-Tenant für Tenant-Admin? | **Ja** - User kann mehreren Tenants zugeordnet sein |
| Tenant-Admin Rechte | **Inklusive Billing** - Volle Kontrolle im Tenant |
| Club-Admin Settings | **Eingeschränkt** - Basis-Settings ja, Subscription nein |

---

## Phase 1: Datenbank-Änderungen

### 1.1 Neue Migration: `create_tenant_user_table.php`

Analog zu `club_user` Pivot-Tabelle:

```php
Schema::create('tenant_user', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();

    // Rollen-Typ im Tenant
    $table->enum('role', ['tenant_admin', 'billing_admin'])->default('tenant_admin');

    $table->date('joined_at');
    $table->boolean('is_active')->default(true);
    $table->boolean('is_primary')->default(false); // Primärer Tenant des Users

    // Permissions innerhalb des Tenants (JSON für Erweiterbarkeit)
    $table->json('permissions')->nullable();

    // Metadata
    $table->text('notes')->nullable();
    $table->json('metadata')->nullable();

    $table->timestamps();

    // Constraints
    $table->unique(['tenant_id', 'user_id']);
    $table->index(['tenant_id', 'role']);
    $table->index(['user_id', 'is_active']);
});
```

### 1.2 Migration: `rename_admin_to_tenant_admin.php`

```php
public function up(): void
{
    DB::table('roles')
        ->where('name', 'admin')
        ->where('guard_name', 'web')
        ->update(['name' => 'tenant_admin']);
}

public function down(): void
{
    DB::table('roles')
        ->where('name', 'tenant_admin')
        ->where('guard_name', 'web')
        ->update(['name' => 'admin']);
}
```

### 1.3 Migration: `migrate_admin_users_to_tenant_admin.php`

```php
public function up(): void
{
    // Alle User mit admin/tenant_admin Rolle finden
    $adminRole = Role::where('name', 'tenant_admin')->first();
    if (!$adminRole) return;

    $adminUsers = User::role($adminRole->name)->get();

    foreach ($adminUsers as $user) {
        if ($user->tenant_id) {
            // tenant_user Eintrag erstellen
            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => $user->tenant_id, 'user_id' => $user->id],
                [
                    'role' => 'tenant_admin',
                    'joined_at' => $user->created_at ?? now(),
                    'is_active' => true,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
```

---

## Phase 2: Model-Änderungen

### 2.1 User Model (`app/Models/User.php`)

```php
/**
 * Get all tenants this user has admin access to.
 */
public function administeredTenants(): BelongsToMany
{
    return $this->belongsToMany(Tenant::class, 'tenant_user')
        ->withPivot('role', 'joined_at', 'is_active', 'is_primary', 'permissions')
        ->withTimestamps();
}

/**
 * Get IDs of tenants this user can administer.
 */
public function getAdministeredTenantIds(): array
{
    // Super Admin hat Zugriff auf alle Tenants
    if ($this->hasRole('super_admin')) {
        return Tenant::pluck('id')->toArray();
    }

    // Tenant Admin nur auf zugewiesene Tenants
    if ($this->hasRole('tenant_admin')) {
        return $this->administeredTenants()
            ->wherePivot('is_active', true)
            ->pluck('tenants.id')
            ->toArray();
    }

    return [];
}

/**
 * Check if user is Tenant Admin for a specific tenant.
 */
public function isTenantAdminFor(Tenant $tenant): bool
{
    if ($this->hasRole('super_admin')) {
        return true;
    }

    return $this->hasRole('tenant_admin') &&
           in_array($tenant->id, $this->getAdministeredTenantIds());
}
```

### 2.2 Tenant Model (`app/Models/Tenant.php`)

```php
/**
 * Get all admin users for this tenant.
 */
public function adminUsers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'tenant_user')
        ->withPivot('role', 'joined_at', 'is_active', 'is_primary', 'permissions')
        ->withTimestamps();
}

/**
 * Get active tenant admins.
 */
public function activeAdmins(): BelongsToMany
{
    return $this->adminUsers()
        ->wherePivot('is_active', true)
        ->wherePivot('role', 'tenant_admin');
}
```

---

## Phase 3: Service-Änderungen

### 3.1 UserRoleService (`app/Services/User/UserRoleService.php`)

**Änderungen:**

1. `isAdmin()` → `isTenantAdmin()` (+ deprecation notice)
2. `getAdministeredClubs()` erweitern für Tenant-Admin
3. `hasTeamAccess()` erweitern für Tenant-Admin
4. `roleHierarchy`: `'admin' => 9` → `'tenant_admin' => 9`

```php
/**
 * @deprecated Nutze isTenantAdmin() stattdessen
 */
public function isAdmin(User $user): bool
{
    return $this->isTenantAdmin($user);
}

public function isTenantAdmin(User $user): bool
{
    return $user->hasRole('tenant_admin');
}

public function isTenantAdminFor(User $user, Tenant $tenant): bool
{
    if ($user->hasRole('super_admin')) {
        return true;
    }

    return $user->hasRole('tenant_admin') &&
           $user->isTenantAdminFor($tenant);
}

public function getAdministeredClubs(User $user, bool $asQuery = true): Builder|Collection
{
    // Super Admin: alle Clubs
    if ($user->hasRole('super_admin')) {
        return $asQuery ? Club::query() : Club::all();
    }

    // Tenant Admin: alle Clubs in ihren Tenants
    if ($user->hasRole('tenant_admin')) {
        $tenantIds = $user->getAdministeredTenantIds();
        $query = Club::whereIn('tenant_id', $tenantIds);
        return $asQuery ? $query : $query->get();
    }

    // Club Admin: nur Clubs mit pivot role 'admin' oder 'owner'
    $query = $user->clubs()->wherePivotIn('role', ['admin', 'owner']);
    return $asQuery ? $query : $query->get();
}

public function hasTeamAccess(User $user, Team $team, array $permissions = []): bool
{
    if ($user->hasRole('super_admin')) {
        return true;
    }

    // Tenant Admin hat Zugriff auf alle Teams im Tenant
    if ($user->hasRole('tenant_admin')) {
        $tenantIds = $user->getAdministeredTenantIds();
        return in_array($team->tenant_id, $tenantIds);
    }

    // ... Rest bleibt gleich
}

// roleHierarchy anpassen:
private array $roleHierarchy = [
    'super_admin' => 10,
    'tenant_admin' => 9,  // War: 'admin' => 9
    'club_admin' => 8,
    'trainer' => 7,
    // ...
];
```

### 3.2 TenantService (`app/Services/TenantService.php`)

```php
/**
 * Check if user has admin access to tenant.
 */
public function userHasAdminAccess($user, Tenant $tenant): bool
{
    if (!$user) {
        return false;
    }

    if ($user->hasRole('super_admin')) {
        return true;
    }

    if ($user->hasRole('tenant_admin')) {
        return $user->isTenantAdminFor($tenant);
    }

    return false;
}

/**
 * Assign user as tenant admin.
 */
public function assignTenantAdmin(Tenant $tenant, User $user, array $options = []): void
{
    // Sicherstellen dass User die tenant_admin Rolle hat
    if (!$user->hasRole('tenant_admin')) {
        $user->assignRole('tenant_admin');
    }

    // Pivot-Eintrag erstellen
    $tenant->adminUsers()->syncWithoutDetaching([
        $user->id => [
            'role' => $options['role'] ?? 'tenant_admin',
            'joined_at' => $options['joined_at'] ?? now(),
            'is_active' => $options['is_active'] ?? true,
            'is_primary' => $options['is_primary'] ?? false,
            'permissions' => $options['permissions'] ?? null,
        ]
    ]);
}

/**
 * Remove tenant admin assignment.
 */
public function removeTenantAdmin(Tenant $tenant, User $user): void
{
    $tenant->adminUsers()->detach($user->id);

    // Wenn User keine anderen Tenant-Zuweisungen mehr hat, Rolle entfernen
    if ($user->administeredTenants()->count() === 0) {
        $user->removeRole('tenant_admin');
    }
}
```

---

## Phase 4: Middleware-Änderungen

### 4.1 AdminMiddleware (`app/Http/Middleware/AdminMiddleware.php`)

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (!$user) {
        abort(401, 'Nicht authentifiziert');
    }

    // Super Admin hat immer Zugriff
    if ($user->hasRole('super_admin')) {
        return $next($request);
    }

    // Tenant Admin mit Tenant-Scope-Check
    if ($user->hasRole('tenant_admin')) {
        $tenant = app('tenant');

        // Wenn kein Tenant im Kontext, Zugriff erlauben (für system-weite Admin-Seiten)
        if (!$tenant) {
            return $next($request);
        }

        // Prüfen ob User Admin für diesen Tenant ist
        if ($user->isTenantAdminFor($tenant)) {
            return $next($request);
        }
    }

    // Permission-basierter Fallback
    if ($user->can('manage-subscriptions')) {
        return $next($request);
    }

    abort(403, 'Keine Berechtigung für Admin-Bereich');
}
```

### 4.2 Neue TenantAdminMiddleware (optional)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Nicht authentifiziert');
        }

        // Super Admin hat immer Zugriff
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Tenant muss im Kontext sein
        $tenant = app('tenant');
        if (!$tenant) {
            abort(403, 'Kein Tenant im Kontext');
        }

        // User muss Tenant Admin für diesen Tenant sein
        if (!$user->hasRole('tenant_admin') || !$user->isTenantAdminFor($tenant)) {
            abort(403, 'Keine Berechtigung für diesen Tenant');
        }

        return $next($request);
    }
}
```

### 4.3 EnsureOnboardingComplete anpassen

**Zeile 60-64:**
```php
// ALT:
if ($user->hasRole('admin')) {
    return $next($request);
}

// NEU:
if ($user->hasRole('super_admin') || $user->hasRole('tenant_admin')) {
    return $next($request);
}
```

---

## Phase 5: Policy-Änderungen (21 Dateien)

### Pattern für alle Policies:

```php
// ALT:
if ($user->hasAnyRole(['super_admin', 'admin'])) {
    return true;
}

// NEU:
if ($user->hasRole('super_admin')) {
    return true;
}

if ($user->hasRole('tenant_admin')) {
    $tenantIds = $user->getAdministeredTenantIds();
    // Bei Models mit tenant_id:
    return in_array($model->tenant_id, $tenantIds);
    // Bei Models ohne tenant_id (z.B. via Club):
    return in_array($model->club->tenant_id, $tenantIds);
}
```

### ClubPolicy - Kritische Billing-Einschränkung:

```php
public function manageBilling(User $user, Club $club): bool
{
    // Super Admin: immer ja
    if ($user->hasRole('super_admin')) {
        return true;
    }

    // Tenant Admin: ja (für eigene Tenants)
    if ($user->hasRole('tenant_admin')) {
        $tenantIds = $user->getAdministeredTenantIds();
        return in_array($club->tenant_id, $tenantIds);
    }

    // Club Admin: NEIN - das ist die wichtigste Einschränkung!
    return false;
}

public function manageSubscription(User $user, Club $club): bool
{
    // Gleiche Logik wie manageBilling
    return $this->manageBilling($user, $club);
}
```

### Betroffene Policies:

| Policy | Zeilen mit `hasRole('admin')` |
|--------|-------------------------------|
| UserPolicy | 31, 93, 144, 182, 206, 234 |
| ClubPolicy | 82, 122, 141, 192, 211, 230, 249, 268, 301, 320, 357, 366, 410, 429 |
| TeamPolicy | 19, 38, 85, 104, 130, 150, 181, 215, 262 |
| PlayerPolicy | 469, 488 |
| SeasonPolicy | 19, 37, 69, 87, 111, 135, 159, 178, 196, 227 |
| DrillPolicy | 18, 42, 60, 81, 89, 108, 141, 169, 202, 216, 235 |
| PlayPolicy | 19, 43, 61, 84, 117, 159, 182, 190 |
| PlaybookPolicy | 19, 46, 64, 87, 126, 176, 194, 202 |
| ClubInvitationPolicy | 30, 66 |
| PlayerRegistrationInvitationPolicy | 33, 79, 99, 123, 202 |
| TrainingSessionPolicy | 68 |
| + 10 weitere | ... |

---

## Phase 6: Controller-Änderungen (10+ Dateien)

### Pattern:

```php
// ALT:
->when($user->hasRole('admin') || $user->hasRole('super_admin'), function ($query) {
    return $query; // Alle sehen
})

// NEU:
->when($user->hasRole('super_admin'), fn($q) => $q) // Super Admin: alle
->when($user->hasRole('tenant_admin') && !$user->hasRole('super_admin'), function($q) use ($user) {
    return $q->whereIn('tenant_id', $user->getAdministeredTenantIds());
})
```

### Betroffene Controller:

| Controller | Zeilen |
|------------|--------|
| GameController | 29 |
| PlayerController | 31, 73, 257, 311 |
| TeamController | 33, 88, 280 |
| ClubController | 38 |
| TrainingController | 32, 48, 76, 89, 93, 175, 236 |
| StatisticsController | 44, 84, 129, 177 |
| AdminPanelController | 151, 153, 166 |
| DashboardController | 152 |
| GameImportController | 30, 53, 361, 404, 449, 479 |
| PushNotificationController | 438, 465, 496 |

---

## Phase 7: Route-Änderungen (7 Dateien)

### Pattern:

```php
// ALT:
'role:admin|super_admin'
'role:super_admin|admin|club_admin'

// NEU:
'role:tenant_admin|super_admin'
'role:super_admin|tenant_admin|club_admin'
```

### Betroffene Route-Dateien:

| Datei | Zeile |
|-------|-------|
| `routes/web.php` | 284 |
| `routes/api.php` | 170 |
| `routes/debug.php` | 68 |
| `routes/emergency.php` | 117 |
| `routes/player_registration.php` | 25, 83 |
| `routes/club_invitation.php` | 23 |
| `routes/club_admin.php` | 29 |

---

## Phase 8: Seeder-Änderungen

### RoleAndPermissionSeeder (`database/seeders/RoleAndPermissionSeeder.php`)

**Zeilen 284-406:** `admin` → `tenant_admin`

```php
// Zeile 283-285:
$tenantAdmin = Role::firstOrCreate([
    'name' => 'tenant_admin',  // War: 'admin'
    'guard_name' => 'web'
]);

$tenantAdmin->givePermissionTo([
    // ... gleiche Permissions wie vorher admin
    // PLUS Subscription/Billing Permissions:
    'manage-subscriptions',
    'view tenant subscriptions',
    'edit tenant subscriptions',
    'view club subscription plans',
    'create club subscription plans',
    'update club subscription plans',
    'delete club subscription plans',
]);
```

---

## Phase 9: Request-Änderungen (6+ Dateien)

### Pattern:

```php
// ALT:
return $this->user()->hasRole(['admin', 'super_admin']);

// NEU:
return $this->user()->hasRole(['tenant_admin', 'super_admin']);
```

### Betroffene Requests:

- `app/Http/Requests/Admin/StoreTenantRequest.php` (Zeile 15)
- `app/Http/Requests/Admin/UpdateTenantRequest.php` (Zeile 15)
- `app/Http/Requests/Admin/CreateClubInvoiceRequest.php` (Zeile 14)
- `app/Http/Requests/Admin/UpdateClubInvoiceRequest.php` (Zeile 14)
- `app/Http/Requests/Admin/MarkInvoicePaidRequest.php` (Zeile 14)
- `app/Http/Requests/UpdatePricingSettingsRequest.php`

---

## Implementierungsreihenfolge

| # | Phase | Dateien | Status |
|---|-------|---------|--------|
| 1 | Migrations erstellen | 3 neue Migrations | ✅ Erledigt |
| 2 | Models erweitern | User.php, Tenant.php | ✅ Erledigt |
| 3 | Services anpassen | UserRoleService.php, TenantService.php | ✅ Erledigt |
| 4 | Middleware anpassen | AdminMiddleware.php, EnsureOnboardingComplete.php | ✅ Erledigt |
| 5 | Seeder anpassen | RoleAndPermissionSeeder.php | ✅ Erledigt |
| 6 | Policies anpassen | 21 Policy-Dateien | ✅ Erledigt |
| 7 | Controllers anpassen | 17 Controller | ✅ Erledigt |
| 8 | Routes anpassen | 7 Route-Dateien | ✅ Erledigt |
| 9 | Requests anpassen | 6 Request-Dateien | ⏳ Optional |
| 10 | `php artisan migrate` | - | ⏳ Ausstehend |
| 11 | `php artisan cache:clear && php artisan permission:cache-reset` | - | ⏳ Ausstehend |
| 12 | Tests anpassen | Test-Dateien | ⏳ Ausstehend |

---

## Kritische Dateien (Priorität)

| Datei | Priorität | Änderung |
|-------|-----------|----------|
| `database/seeders/RoleAndPermissionSeeder.php` | 🔴 Hoch | admin → tenant_admin |
| `app/Services/User/UserRoleService.php` | 🔴 Hoch | Tenant-Scope Logik |
| `app/Models/User.php` | 🔴 Hoch | Neue Relationships |
| `app/Models/Tenant.php` | 🔴 Hoch | Neue Relationships |
| `app/Http/Middleware/AdminMiddleware.php` | 🔴 Hoch | Scope-Check |
| `app/Policies/ClubPolicy.php` | 🔴 Hoch | manageBilling Einschränkung |

---

## Risiken & Mitigationen

| Risiko | Mitigation |
|--------|------------|
| Bestehende admin-User verlieren Zugriff | Daten-Migration erstellt tenant_user Einträge automatisch |
| Tests schlagen fehl | Alle `hasRole('admin')` in Tests anpassen |
| Frontend-Referenzen | Nach `admin` im Frontend-Code suchen und ersetzen |
| Cache-Probleme | `php artisan cache:clear && php artisan permission:cache-reset` |
| API-Breaking Changes | API-Dokumentation aktualisieren |

---

## Nach der Implementierung

1. `php artisan cache:clear`
2. `php artisan permission:cache-reset`
3. `php artisan config:clear`
4. `php artisan route:clear`
5. `composer test` - alle Tests durchlaufen
6. Manueller Test der Admin-Funktionen

---

## Offene Fragen

- [ ] Soll es eine UI geben, um Tenant-Admins zuzuweisen?
- [ ] Brauchen wir ein `billing_admin` zusätzlich zu `tenant_admin`?
- [ ] Sollen bestehende admin-User automatisch allen Tenants zugewiesen werden oder nur ihrem aktuellen?
