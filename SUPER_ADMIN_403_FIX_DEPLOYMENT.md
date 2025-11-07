# Super Admin 403-Fehler Fix - Production Deployment Guide

## Problem
Super Admin User bekommen 403-Fehler auf allen Seiten, einschließlich `/admin/settings`, obwohl sie die erforderlichen Rollen und Permissions haben.

## Root Causes
1. **Permission Cache veraltet** - Spatie Permission cached Rollen und Permissions
2. **Missing Tenant Context** - starting5.eu hat möglicherweise keinen Tenant-Eintrag
3. **Policy Authorization fehlgeschlagen** - Policies hatten keine Super Admin Bypass-Logik
4. **Gate::before() nicht im Cache** - Nach Hinzufügen muss Cache geleert werden

---

## Implementierte Fixes (bereits im Code)

### 1. Gate::before() Callback ✅
**Datei:** `app/Providers/AuthServiceProvider.php:63-69`

Super Admins werden jetzt automatisch für ALLE Authorization-Checks autorisiert.

```php
Gate::before(function ($user, $ability) {
    if ($user->hasRole('super_admin')) {
        return true; // Auto-authorize all actions
    }
    return null;
});
```

### 2. Tenant Context für Super Admins ✅
**Datei:** `app/Http/Middleware/ResolveTenantMiddleware.php:479-503`

Super Admins erhalten automatisch einen Tenant-Kontext:
- Priorität: User's `tenant_id` → Erster aktiver Tenant
- Verhindert "tenant not found" Errors

### 3. AuthorizesUsers Policy Trait ✅
**Datei:** `app/Policies/Concerns/AuthorizesUsers.php`

Alle 13 Policies verwenden jetzt das `AuthorizesUsers` Trait mit `before()` Methode für Super Admin Bypass:

✅ ClubPolicy
✅ UserPolicy
✅ TeamPolicy
✅ GamePolicy
✅ RolePolicy
✅ PlayerPolicy
✅ TrainingSessionPolicy
✅ DrillPolicy
✅ GymHallPolicy
✅ EmergencyContactPolicy
✅ ClubSubscriptionPlanPolicy
✅ PlayerRegistrationInvitationPolicy
✅ ClubInvitationPolicy

### 4. User Model tenant_id ✅
**Dateien:**
- `app/Models/User.php:46` - `tenant_id` in `$fillable`
- `app/Models/User.php:253-256` - `tenant()` BelongsTo Beziehung

---

## Production Deployment Steps

### SCHRITT 1: Code auf Production Server deployen

```bash
# SSH in den Production Server
ssh user@starting5.eu

# Navigate zum Projekt
cd /www/htdocs/w015b7e3/starting5.eu

# Git Pull (oder Datei-Upload via FTP)
git pull origin main

# Oder via FTP alle geänderten Dateien hochladen:
# - app/Providers/AuthServiceProvider.php
# - app/Http/Middleware/ResolveTenantMiddleware.php
# - app/Policies/Concerns/AuthorizesUsers.php (neu)
# - app/Policies/*.php (13 Policies)
# - app/Models/User.php
```

---

### SCHRITT 2: Cache vollständig leeren (KRITISCH!)

**WICHTIG:** Die Permission- und Authorization-Caches müssen geleert werden, damit die Fixes wirksam werden.

```bash
cd /www/htdocs/w015b7e3/starting5.eu

# Alle Application Caches leeren
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Spatie Permission Cache leeren (SEHR WICHTIG!)
php artisan permission:cache-reset

# Kompletten Cache leeren
php artisan optimize:clear

# Caches neu aufbauen
php artisan config:cache
php artisan route:cache
```

**Erwartete Ausgabe:**
```
Application cache cleared successfully.
Configuration cache cleared successfully.
Route cache cleared successfully.
Compiled views cleared successfully.
Permission cache flushed.
```

---

### SCHRITT 3: Tenant für starting5.eu verifizieren/erstellen

**Option A: Via Tinker (empfohlen)**

```bash
php artisan tinker
```

Dann folgende Befehle ausführen:

```php
// 1. Prüfen ob Tenant existiert
$tenant = \App\Models\Tenant::where('domain', 'starting5.eu')->first();
dump($tenant);

// 2. Falls KEIN Tenant existiert (null), erstelle einen:
if (!$tenant) {
    $tenant = \App\Models\Tenant::create([
        'name' => 'Starting5 Basketball Club',
        'slug' => 'starting5-eu',
        'domain' => 'starting5.eu',
        'billing_email' => 'admin@starting5.eu',
        'subscription_tier' => 'professional', // oder 'enterprise'
        'is_active' => true,
        'timezone' => 'Europe/Berlin',
        'locale' => 'de',
        'currency' => 'EUR',
        'country_code' => 'DE',
    ]);
    dump('Tenant erstellt:', $tenant->id);
}

// 3. Super Admin User finden und verifizieren
$adminEmail = 'DEINE_ADMIN_EMAIL@example.com'; // ERSETZEN!
$user = \App\Models\User::where('email', $adminEmail)->first();

if (!$user) {
    dump('FEHLER: User nicht gefunden!');
    exit;
}

dump([
    'User ID' => $user->id,
    'Name' => $user->name,
    'Email' => $user->email,
    'Current tenant_id' => $user->tenant_id,
    'Roles' => $user->roles->pluck('name')->toArray(),
    'Has super_admin role' => $user->hasRole('super_admin'),
]);

// 4. User's tenant_id aktualisieren
$user->update(['tenant_id' => $tenant->id]);
dump('User tenant_id aktualisiert auf:', $tenant->id);

// 5. Verifizieren dass "access admin panel" Permission existiert
$permission = Spatie\Permission\Models\Permission::where('name', 'access admin panel')->first();
dump('Permission exists:', $permission ? 'JA' : 'NEIN');

// 6. Verifizieren dass Super Admin alle Permissions hat
$superAdminRole = Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
$permissionCount = $superAdminRole->permissions()->count();
dump('Super Admin hat ' . $permissionCount . ' Permissions');

// Erwartete Anzahl: 136 Permissions
if ($permissionCount < 136) {
    dump('WARNUNG: Super Admin hat nicht alle Permissions!');
    dump('Führe aus: php artisan db:seed --class=RoleAndPermissionSeeder');
}

exit;
```

**Option B: Via MySQL/MariaDB Direktzugriff**

```sql
-- 1. Prüfen ob Tenant existiert
SELECT * FROM tenants WHERE domain = 'starting5.eu';

-- 2. Falls nicht, Tenant erstellen
INSERT INTO tenants (
    name, slug, domain, billing_email, subscription_tier,
    is_active, timezone, locale, currency, country_code,
    created_at, updated_at
) VALUES (
    'Starting5 Basketball Club',
    'starting5-eu',
    'starting5.eu',
    'admin@starting5.eu',
    'professional',
    1,
    'Europe/Berlin',
    'de',
    'EUR',
    'DE',
    NOW(),
    NOW()
);

-- 3. Tenant ID abrufen
SET @tenant_id = LAST_INSERT_ID();

-- 4. Super Admin User finden
SELECT id, name, email, tenant_id FROM users WHERE email = 'DEINE_EMAIL@example.com';

-- 5. User's tenant_id aktualisieren
UPDATE users
SET tenant_id = @tenant_id
WHERE email = 'DEINE_EMAIL@example.com';

-- 6. Verifizieren
SELECT
    u.id, u.name, u.email, u.tenant_id, t.domain,
    GROUP_CONCAT(r.name) as roles
FROM users u
LEFT JOIN tenants t ON t.id = u.tenant_id
LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
LEFT JOIN roles r ON r.id = mhr.role_id
WHERE u.email = 'DEINE_EMAIL@example.com'
GROUP BY u.id;
```

---

### SCHRITT 4: Permissions re-seeden (falls nötig)

Wenn die Permission "access admin panel" nicht existiert oder Super Admin nicht alle Permissions hat:

```bash
cd /www/htdocs/w015b7e3/starting5.eu

# Permissions und Rollen neu seeden
php artisan db:seed --class=RoleAndPermissionSeeder

# Cache erneut leeren
php artisan permission:cache-reset
```

**WARNUNG:** Dieser Seeder wird:
- Fehlende Permissions erstellen
- Fehlende Rollen erstellen
- Super Admin ALLE Permissions zuweisen

Bestehende Daten werden NICHT gelöscht.

---

### SCHRITT 5: Super Admin Zugriff testen

1. **Ausloggen** und neu **einloggen** (wichtig!)
2. Navigiere zu: `https://starting5.eu/admin/settings`
3. Erwartetes Ergebnis: ✅ Zugriff gewährt, Settings-Seite wird angezeigt
4. Teste weitere Admin-Seiten:
   - `/admin/dashboard`
   - `/admin/users`
   - `/admin/clubs`

---

## Troubleshooting

### Problem: Immer noch 403-Fehler

**1. Debug-Modus temporär aktivieren:**

`.env`:
```
APP_DEBUG=true
LOG_LEVEL=debug
```

**2. Laravel Log prüfen:**

```bash
tail -f storage/logs/laravel.log
```

Dann die fehlerhafte Seite erneut aufrufen und Log-Ausgabe analysieren.

**3. Verifizieren dass alle Caches geleert wurden:**

```bash
# Komplettes Cleanup
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan permission:cache-reset

# Und erneut einloggen!
```

**4. User-Rolle verifizieren:**

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'DEINE_EMAIL')->first();
dd([
    'has_super_admin_role' => $user->hasRole('super_admin'),
    'roles' => $user->roles->pluck('name'),
    'permissions_count' => $user->getAllPermissions()->count(),
    'tenant_id' => $user->tenant_id,
    'tenant_exists' => $user->tenant ? 'JA' : 'NEIN',
]);
```

**5. Gate::before() verifizieren:**

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'DEINE_EMAIL')->first();
Auth::login($user);

// Test Gate Check
$result = Gate::allows('access admin panel');
dump('Gate allows access admin panel:', $result ? 'JA' : 'NEIN');

$result2 = Gate::allows('manage-users');
dump('Gate allows manage-users:', $result2 ? 'JA' : 'NEIN');
```

Wenn beide `false` zurückgeben, ist `Gate::before()` nicht aktiv → Cache Problem!

---

### Problem: "Class 'ZipArchive' not found"

Wenn `php artisan` Befehle diesen Fehler zeigen:

```bash
# PHP Zip Extension installieren
# Debian/Ubuntu:
sudo apt-get install php-zip

# CentOS/RHEL:
sudo yum install php-zip

# Oder in php.ini aktivieren:
extension=zip

# PHP-FPM neu starten
sudo service php-fpm restart
```

---

### Problem: Tenant wird nicht gefunden

**Symptom:** Error "Tenant could not be resolved from request"

**Lösung:**

```bash
php artisan tinker
```

```php
// Alle Tenants auflisten
\App\Models\Tenant::all(['id', 'name', 'domain', 'is_active']);

// Prüfen ob Domain-Mapping stimmt
$host = 'starting5.eu';
$tenant = \App\Models\Tenant::where('domain', $host)->orWhere('slug', $host)->first();
dd($tenant);

// Falls nicht gefunden, siehe SCHRITT 3
```

---

## Verifizierungs-Checkliste

Nach dem Deployment:

- [ ] Code auf Production Server deployed
- [ ] Alle Caches geleert (`cache:clear`, `permission:cache-reset`, etc.)
- [ ] Tenant für starting5.eu existiert in DB
- [ ] Super Admin User hat `tenant_id` gesetzt
- [ ] Super Admin User hat `super_admin` Rolle
- [ ] Super Admin User hat alle 136 Permissions (oder Gate::before() bypassed)
- [ ] Ausgeloggt und neu eingeloggt
- [ ] `/admin/settings` zeigt KEINE 403-Fehler mehr
- [ ] Andere Admin-Seiten funktionieren
- [ ] Debug-Modus wieder deaktiviert (`APP_DEBUG=false`)

---

## Zusammenfassung der Änderungen

**Geänderte Dateien:**
1. `app/Providers/AuthServiceProvider.php` - Gate::before() hinzugefügt
2. `app/Http/Middleware/ResolveTenantMiddleware.php` - Tenant Context für Super Admins
3. `app/Policies/Concerns/AuthorizesUsers.php` - Neues Trait für Policy Bypass
4. `app/Policies/*.php` - 13 Policies mit AuthorizesUsers Trait
5. `app/Models/User.php` - tenant_id Beziehung hinzugefügt

**Neue Dateien:**
- `app/Policies/Concerns/AuthorizesUsers.php`

**Datenbank-Änderungen:**
- `users` Tabelle sollte bereits `tenant_id` Spalte haben
- Tenant-Eintrag für starting5.eu muss existieren

---

## Support

Bei weiteren Problemen:

1. Laravel Log prüfen: `storage/logs/laravel.log`
2. PHP Error Log prüfen: `/var/log/php-fpm/error.log`
3. Nginx/Apache Error Log prüfen
4. Debug-Modus aktivieren und detaillierte Fehler analysieren

**Kontakt:** Siehe Projekt-Dokumentation

---

**Stand:** 2025-11-07
**Version:** 1.0
**Getestet auf:** Laravel 12.x, PHP 8.2+
