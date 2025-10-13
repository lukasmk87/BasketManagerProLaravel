# Migration Fix: tenant_id zur api_usage_tracking Tabelle hinzufügen

## Problem
Das Admin Dashboard zeigte folgenden Fehler:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'd0446535.api_usage_trackings' doesn't exist
```

**Root Cause:**
1. Die Tabelle `api_usage_tracking` (Singular) existiert, Laravel suchte aber nach `api_usage_trackings` (Plural)
2. Die Tabelle hatte keine `tenant_id` Spalte, aber `Tenant::apiUsage()` Relationship erwartete eine

## Lösung

### 1. Neue Migration erstellt
**Datei:** `database/migrations/2025_10_13_154756_add_tenant_id_to_api_usage_tracking_table.php`

**Änderungen:**
- ✅ `tenant_id` UUID Spalte hinzugefügt (nullable)
- ✅ Foreign Key Constraint zu `tenants` Tabelle
- ✅ Index für Performance: `tenant_window_idx`

### 2. ApiUsageTracking Model angepasst
**Datei:** `app/Models/ApiUsageTracking.php`

**Änderungen:**
- ✅ Tabellenname explizit gesetzt: `protected $table = 'api_usage_tracking';`
- ✅ `tenant_id` zu `$fillable` hinzugefügt
- ✅ `tenant()` Relationship hinzugefügt

---

## Deployment auf Staging

### Schritt 1: Code deployen

```bash
# Auf Staging-Server
cd /pfad/zum/projekt
git pull origin main

# Oder falls Files manuell hochgeladen wurden:
# - database/migrations/2025_10_13_154756_add_tenant_id_to_api_usage_tracking_table.php
# - app/Models/ApiUsageTracking.php (updated)
```

### Schritt 2: Migration ausführen

```bash
# Composer dependencies aktualisieren (falls nötig)
composer install --no-dev --optimize-autoloader

# Prüfen ob Tabelle existiert
php artisan tinker
```

```php
// In Tinker
DB::select('SHOW TABLES LIKE "api_usage_tracking"');
// Falls Tabelle nicht existiert, erst die ursprüngliche Migration ausführen

// Prüfen welche Migrationen fehlen
exit
```

```bash
# Migrationen ausführen
php artisan migrate

# Erwartete Ausgabe:
# Running: 2025_10_13_154756_add_tenant_id_to_api_usage_tracking_table
# Migrated: 2025_10_13_154756_add_tenant_id_to_api_usage_tracking_table
```

### Schritt 3: Verifizieren

```bash
php artisan tinker
```

```php
// Tabellenstruktur prüfen
DB::select('DESCRIBE api_usage_tracking');

// Sollte jetzt tenant_id Spalte zeigen:
// - Field: tenant_id
// - Type: char(36)
// - Null: YES
// - Key: MUL
// - Default: NULL

// Relationship testen
\App\Models\Tenant::first()->apiUsage;
// Sollte keine Fehler mehr werfen

exit
```

### Schritt 4: Cache leeren

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Schritt 5: Admin Dashboard testen

Öffnen Sie `https://staging.basketmanager-pro.de/admin/dashboard` und prüfen Sie:
- ✅ Keine Fehlermeldung mehr
- ✅ Dashboard lädt ohne Errors
- ✅ Tenant-Statistiken werden korrekt angezeigt

---

## Troubleshooting

### Problem: Migration schlägt fehl - "Table doesn't exist"

**Ursache:** Die ursprüngliche `api_usage_tracking` Tabelle existiert noch nicht.

**Lösung:**
```bash
# Prüfen welche Migrationen fehlen
php artisan migrate:status

# Falls create_api_usage_tracking_table fehlt, alle Migrationen ausführen
php artisan migrate
```

### Problem: Foreign Key Constraint Error

**Ursache:** Es gibt bereits Records in `api_usage_tracking` die keinen gültigen `tenant_id` haben.

**Lösung:**
```bash
php artisan tinker
```

```php
// Alte Records löschen (VORSICHT!)
DB::table('api_usage_tracking')->truncate();

// ODER: Migration anpassen und tenant_id NOT NULL machen nach Datenmigration
```

### Problem: "Duplicate key name 'tenant_window_idx'"

**Ursache:** Index existiert bereits.

**Lösung:**
```bash
# Migration rollback und erneut ausführen
php artisan migrate:rollback --step=1
php artisan migrate
```

### Problem: Laravel sucht immer noch nach `api_usage_trackings` (Plural)

**Ursache:** Cache oder OpCache nicht geleert.

**Lösung:**
```bash
# Cache komplett leeren
php artisan cache:clear
php artisan config:clear
php artisan optimize:clear

# Falls OpCache aktiv ist
php artisan opcache:clear
# ODER Server neustarten
sudo systemctl restart php8.2-fpm
```

---

## Rollback (falls nötig)

Falls die Migration Probleme verursacht:

```bash
# Migration rückgängig machen
php artisan migrate:rollback --step=1

# Tabelle manuell anpassen (falls Rollback fehlschlägt)
php artisan tinker
```

```php
DB::statement('ALTER TABLE api_usage_tracking DROP FOREIGN KEY api_usage_tracking_tenant_id_foreign');
DB::statement('ALTER TABLE api_usage_tracking DROP INDEX tenant_window_idx');
DB::statement('ALTER TABLE api_usage_tracking DROP COLUMN tenant_id');
```

---

## Datenmigration (falls existierende Records vorhanden)

Falls bereits API Usage Records existieren, müssen diese einem Tenant zugeordnet werden:

```bash
php artisan tinker
```

```php
// Alle Records einem Standard-Tenant zuordnen
$defaultTenant = \App\Models\Tenant::where('slug', 'basketmanager-pro')->first();

DB::table('api_usage_tracking')
    ->whereNull('tenant_id')
    ->update(['tenant_id' => $defaultTenant->id]);

// Verifizieren
DB::table('api_usage_tracking')->whereNull('tenant_id')->count(); // sollte 0 sein
```

---

## Nächste Schritte

Nach erfolgreicher Migration:

1. ✅ Admin Dashboard funktioniert wieder
2. ✅ API Usage Tracking ist jetzt Multi-Tenant-fähig
3. ✅ Performance-Indizes sind gesetzt

**Monitoring:**
- Überwachen Sie die Admin Dashboard Performance
- Prüfen Sie ob API Usage korrekt getrackt wird
- Stellen Sie sicher, dass neue Records automatisch `tenant_id` erhalten

---

## Commit Message

```
Fix: Add tenant_id to api_usage_tracking table for multi-tenant support

- Add migration to add tenant_id column to api_usage_tracking
- Add foreign key constraint to tenants table
- Add performance index: tenant_window_idx
- Update ApiUsageTracking model to include tenant_id in fillable
- Add tenant() relationship to ApiUsageTracking model
- Explicitly set table name to 'api_usage_tracking' (singular)

Fixes: Admin Dashboard error "Table api_usage_trackings doesn't exist"
```
