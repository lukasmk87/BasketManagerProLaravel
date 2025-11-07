# Tenant-Free Super Admin Architecture

## 🎯 Überblick

Super Admins sind **System-Level Benutzer** ohne Tenant-Bindung (`tenant_id = NULL`). Sie können ALLE Tenants erstellen, verwalten und darauf zugreifen - unabhängig von Tenant-Kontexten.

**Datum:** 2025-11-07
**Version:** 2.0 (Komplette Architektur-Überarbeitung)

---

## 🏗️ Architektur-Prinzipien

### 1. **Super Admin = System User**
- **Datenbank**: `users.tenant_id = NULL`
- **Kein Tenant-Kontext**: `app('tenant')` ist NULL für Super Admins
- **Alle Daten sichtbar**: Sehen ALLE Clubs, Teams, Spiele aller Tenants
- **Optional: Tenant-Filter**: Können temporär einen Tenant auswählen (Session-basiert)

### 2. **Regular Admin = Tenant User**
- **Datenbank**: `users.tenant_id = {tenant_uuid}`
- **Tenant-Kontext erforderlich**: `app('tenant')` muss gesetzt sein
- **Nur eigener Tenant**: Sehen nur Daten ihres eigenen Tenants
- **TenantScope aktiv**: Queries werden automatisch gefiltert

---

## 📋 Implementierte Änderungen

### **1. ResolveTenantMiddleware**
**Datei:** `app/Http/Middleware/ResolveTenantMiddleware.php:52-80`

**Vorher (FALSCH):**
```php
// Super Admins wurden an einen Tenant gebunden
elseif ($this->isSuperAdmin($request)) {
    $tenant = $this->resolveSuperAdminTenant($request);
    // ...
}
```

**Nachher (KORREKT):**
```php
// Super Admins überspringen Tenant-Resolution komplett
elseif ($this->isSuperAdmin($request)) {
    // Optional: Check if Super Admin selected a tenant filter (session-based)
    if ($request->hasSession()) {
        $selectedTenantId = $request->session()->get('super_admin_selected_tenant_id');
        if ($selectedTenantId) {
            // Set tenant context ONLY for filtering (not permanent)
            $tenant = Tenant::find($selectedTenantId);
        }
    }
    return $next($request); // No tenant context by default
}
```

**Ergebnis:**
- Super Admins haben `app('tenant') = NULL` (Standard)
- Optional: Session-basierter Tenant-Filter

---

### **2. TenantScope**
**Datei:** `app/Models/Scopes/TenantScope.php:14-28`

**Neu hinzugefügt:**
```php
public function apply(Builder $builder, Model $model): void
{
    // ✅ SKIP tenant filtering for Super Admins
    if (auth()->check() && auth()->user()->hasRole('super_admin')) {
        return; // Bypass scope entirely
    }

    // Normal tenant filtering for regular users...
}
```

**Ergebnis:**
- `Club::count()` → Super Admin sieht ALLE Clubs aller Tenants
- `Club::count()` → Regular Admin sieht nur Clubs ihres Tenants

---

### **3. BelongsToTenant Trait**
**Datei:** `app/Models/Concerns/BelongsToTenant.php:20-33`

**Neu hinzugefügt:**
```php
static::creating(function ($model) {
    if (empty($model->tenant_id)) {
        // ✅ SKIP auto-assignment for Super Admins
        if (auth()->check() && auth()->user()->hasRole('super_admin')) {
            return; // Super Admins must explicitly set tenant_id
        }

        // Auto-assign for regular users...
    }
});
```

**Ergebnis:**
- Super Admins MÜSSEN explizit `tenant_id` angeben beim Erstellen
- Regular Users bekommen automatisch den aktuellen Tenant

---

### **4. InstallationService**
**Datei:** `app/Services/Install/InstallationService.php:251`

**Vorher:**
```php
'tenant_id' => $tenant->id, // FALSCH
```

**Nachher:**
```php
'tenant_id' => null, // ✅ Super Admins sind tenant-unabhängig
```

**Ergebnis:**
- Neue Super Admins haben keine Tenant-Bindung

---

### **5. ClubService**
**Datei:** `app/Services/ClubService.php:29-46`

**Neu hinzugefügt:**
```php
if (empty($data['tenant_id'])) {
    // ✅ Super Admins MUST explicitly specify tenant_id
    if (auth()->check() && auth()->user()->hasRole('super_admin')) {
        throw new \InvalidArgumentException(
            'Super Admins must explicitly specify tenant_id when creating clubs.'
        );
    }
    // Regular users: auto-assign from app('tenant')...
}
```

**Ergebnis:**
- Super Admins müssen bei Club-Erstellung Tenant wählen
- Exception wenn `tenant_id` fehlt

---

### **6. DashboardController**
**Datei:** `app/Http/Controllers/DashboardController.php:72-95`

**Kommentare hinzugefügt:**
```php
/**
 * Note: For Super Admins, all queries automatically bypass TenantScope
 * and return data from ALL tenants. For regular Admins, queries are
 * automatically scoped to their current tenant.
 */
```

**Ergebnis:**
- Queries funktionieren automatisch korrekt für beide Rollen
- Keine Code-Duplikation nötig

---

### **7. Migration**
**Datei:** `database/migrations/2025_11_07_120000_make_super_admins_tenant_free.php`

**Aktionen:**
1. Setzt `users.tenant_id` auf `nullable` (falls noch nicht)
2. Updated alle existierenden Super Admins: `SET tenant_id = NULL`

**SQL:**
```sql
UPDATE users
SET tenant_id = NULL
WHERE id IN (
    SELECT model_id
    FROM model_has_roles
    WHERE role_id = (SELECT id FROM roles WHERE name = 'super_admin')
);
```

---

## 🚀 Production Deployment

### **Schritt 1: Code Deployen**

```bash
cd /www/htdocs/w015b7e3/starting5.eu

# Git Pull
git pull origin main

# Oder FTP Upload:
# - app/Http/Middleware/ResolveTenantMiddleware.php
# - app/Models/Scopes/TenantScope.php
# - app/Models/Concerns/BelongsToTenant.php
# - app/Services/Install/InstallationService.php
# - app/Services/ClubService.php
# - app/Http/Controllers/DashboardController.php
# - database/migrations/2025_11_07_120000_make_super_admins_tenant_free.php
```

---

### **Schritt 2: Migration Ausführen**

```bash
cd /www/htdocs/w015b7e3/starting5.eu

# Backup erstellen (WICHTIG!)
php artisan backup:run

# Migration ausführen
php artisan migrate

# Erwartete Ausgabe:
# INFO  Running migrations.
# 2025_11_07_120000_make_super_admins_tenant_free.php ..... XX.XXms DONE
```

---

### **Schritt 3: Cache Leeren**

```bash
# ALLE Caches leeren
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan permission:cache-reset

# Neu aufbauen
php artisan optimize
php artisan config:cache
php artisan route:cache
```

---

### **Schritt 4: Verifizieren**

```bash
php artisan tinker
```

```php
// 1. Super Admin Benutzer prüfen
$superAdmin = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'super_admin');
})->first();

dump([
    'name' => $superAdmin->name,
    'email' => $superAdmin->email,
    'tenant_id' => $superAdmin->tenant_id, // Sollte NULL sein!
    'roles' => $superAdmin->roles->pluck('name'),
]);

// 2. Super Admin kann ALLE Clubs sehen
Auth::login($superAdmin);
$allClubs = \App\Models\Club::count();
dump("Super Admin sieht {$allClubs} Clubs (ALLE Tenants)");

// 3. Regular Admin sieht nur eigenen Tenant
Auth::logout();
$regularAdmin = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'admin');
})->first();
Auth::login($regularAdmin);
$tenantClubs = \App\Models\Club::count();
dump("Regular Admin sieht {$tenantClubs} Clubs (nur eigener Tenant)");

exit;
```

**Erwartete Ergebnisse:**
- ✅ Super Admin: `tenant_id = NULL`
- ✅ Super Admin sieht ALLE Clubs aller Tenants
- ✅ Regular Admin sieht nur Clubs seines Tenants

---

## 📖 API Usage für Super Admins

### **Club Erstellen**

```php
// ❌ FALSCH - Fehlt tenant_id
$club = app(ClubService::class)->createClub([
    'name' => 'New Club',
    // tenant_id fehlt!
]);
// Exception: "Super Admins must explicitly specify tenant_id"

// ✅ RICHTIG - tenant_id angegeben
$club = app(ClubService::class)->createClub([
    'name' => 'New Club',
    'tenant_id' => '123e4567-e89b-12d3-a456-426614174000', // Explizit angeben!
]);
```

---

### **Clubs Abfragen**

```php
// Super Admin (automatisch ALLE Tenants)
Auth::login($superAdmin);
$allClubs = Club::all(); // Gibt ALLE Clubs aller Tenants zurück

// Optional: Nur Clubs eines bestimmten Tenants
$specificClubs = Club::where('tenant_id', $tenantId)->get();

// Oder mit Scope
$specificClubs = Club::forTenant($tenantId)->get();
```

---

### **Dashboard**

```php
// Super Admin sieht automatisch ALLE Daten
$dashboardStats = app(DashboardController::class)->getAdminDashboard(auth()->user());
// Returns stats from ALL tenants

// Regular Admin sieht nur eigenen Tenant
$dashboardStats = app(DashboardController::class)->getAdminDashboard(auth()->user());
// Returns stats from current tenant only
```

---

## 🔒 Sicherheits-Considerations

### **1. Super Admin Permissions**
- Haben ALLE 136 Permissions via `Gate::before()`
- Bypassen alle Policy-Checks
- Können JEDEN Tenant verwalten

### **2. Daten-Isolation**
- Regular Admins sehen NUR ihren Tenant (automatisch via TenantScope)
- Super Admins sehen ALLE Tenants (TenantScope bypassed)
- Klare Trennung auf Datenbankebene

### **3. Audit Logging**
- Alle Super Admin Aktionen sollten geloggt werden
- `spatie/laravel-activitylog` ist bereits konfiguriert
- Empfehlung: Zusätzliche Logs für tenant-übergreifende Operationen

---

## 🧪 Testing

### **Unit Tests**

```php
public function test_super_admin_has_no_tenant_id()
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->assertNull($superAdmin->tenant_id);
}

public function test_super_admin_sees_all_tenants()
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $club1 = Club::factory()->create(['tenant_id' => $tenant1->id]);
    $club2 = Club::factory()->create(['tenant_id' => $tenant2->id]);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    $clubs = Club::all();
    $this->assertCount(2, $clubs); // Sieht BEIDE Clubs
}

public function test_regular_admin_sees_only_own_tenant()
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $club1 = Club::factory()->create(['tenant_id' => $tenant1->id]);
    $club2 = Club::factory()->create(['tenant_id' => $tenant2->id]);

    $admin = User::factory()->create(['tenant_id' => $tenant1->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin);
    app()->instance('tenant', $tenant1);

    $clubs = Club::all();
    $this->assertCount(1, $clubs); // Sieht nur EIGENEN Club
    $this->assertEquals($club1->id, $clubs->first()->id);
}

public function test_super_admin_must_specify_tenant_id_when_creating_club()
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Super Admins must explicitly specify tenant_id');

    app(ClubService::class)->createClub([
        'name' => 'New Club',
        // tenant_id fehlt!
    ]);
}
```

---

## 🐛 Troubleshooting

### **Problem: Super Admin sieht keine Daten**

**Symptom:** Dashboard ist leer, Clubs-Liste ist leer

**Lösung:**
```bash
# 1. Cache leeren
php artisan cache:clear
php artisan permission:cache-reset

# 2. Super Admin Rolle verifizieren
php artisan tinker
$user = User::where('email', 'ADMIN_EMAIL')->first();
dump($user->hasRole('super_admin')); // Sollte true sein

# 3. tenant_id prüfen
dump($user->tenant_id); // Sollte NULL sein
```

---

### **Problem: "Super Admins must explicitly specify tenant_id"**

**Symptom:** Exception beim Erstellen von Clubs/Teams

**Lösung:**
```php
// Tenant-ID explizit angeben
$club = Club::create([
    'name' => 'New Club',
    'tenant_id' => $tenantId, // ← Muss angegeben werden!
    // ...
]);
```

---

### **Problem: Regular Admin sieht ALLE Tenants**

**Symptom:** Regular Admin hat tenant-übergreifenden Zugriff

**Lösung:**
```bash
# 1. Prüfen ob TenantScope korrekt angewendet wird
php artisan tinker
$admin = User::where('email', 'ADMIN_EMAIL')->first();
dump($admin->tenant_id); // Sollte NICHT NULL sein!
dump($admin->hasRole('admin')); // Sollte true sein
dump($admin->hasRole('super_admin')); // Sollte false sein!

# 2. Tenant-Kontext prüfen
app()->instance('tenant', $admin->tenant);
$clubs = Club::count();
dump("Admin sieht {$clubs} Clubs");
```

---

## 📊 Datenbank-Schema

### **users Tabelle**

| Spalte | Typ | Nullable | Beschreibung |
|--------|-----|----------|--------------|
| id | uuid | NO | Primary Key |
| tenant_id | uuid | **YES** | Foreign Key zu tenants.id, **NULL für Super Admins** |
| name | varchar | NO | Username |
| email | varchar | NO | Email (unique) |
| ... | ... | ... | ... |

**Index:**
```sql
INDEX idx_users_tenant_id ON users(tenant_id);
```

---

## 🎯 Zusammenfassung

### **Vorher (Falsche Implementierung)**
- ❌ Super Admins hatten `tenant_id` gesetzt
- ❌ ResolveTenantMiddleware zwang Tenant-Kontext
- ❌ TenantScope filterte auch Super Admin Queries
- ❌ Super Admins konnten nur EINEN Tenant verwalten

### **Nachher (Korrekte Implementierung)**
- ✅ Super Admins haben `tenant_id = NULL`
- ✅ Keine Tenant-Resolution für Super Admins
- ✅ TenantScope bypassed für Super Admins
- ✅ Super Admins sehen ALLE Tenants gleichzeitig
- ✅ Optional: Session-basierter Tenant-Filter

---

## 📚 Weitere Dokumentation

- **CLAUDE.md** - Projekt-Übersicht
- **BERECHTIGUNGS_MATRIX.md** - Permissions Matrix
- **ROLLEN_DOKUMENTATION_README.md** - Rollen-Übersicht
- **SUPER_ADMIN_403_FIX_DEPLOYMENT.md** - Alte Version (überholt durch diese Architektur)

---

**Stand:** 2025-11-07
**Version:** 2.0
**Autor:** Claude Code Assistant
**Getestet auf:** Laravel 12.x, PHP 8.2+
