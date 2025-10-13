# Anleitung: Demo-Tenants auf Staging entfernen

## Problem
Auf `staging.basketmanager-pro.de` werden im Admin Dashboard Demo-/Test-Tenants angezeigt, die durch den `TenantSeeder` erstellt wurden.

## Lösung
Der `TenantSeeder` wurde so angepasst, dass Demo-Tenants nur noch im `local` Environment erstellt werden. Existierende Demo-Tenants müssen jedoch manuell aus der Staging-Datenbank entfernt werden.

---

## Schritt 1: Auf Staging-Server verbinden

```bash
ssh ihr-staging-server
cd /pfad/zum/projekt
```

## Schritt 2: Demo-Tenants identifizieren

**Via Artisan Tinker:**
```bash
php artisan tinker
```

```php
// Liste aller Tenants anzeigen
\App\Models\Tenant::all(['id', 'slug', 'name'])->toArray();

// Demo-Tenants identifizieren (slugs aus Seeder)
$demoSlugs = ['demo', 'munich-eagles', 'berlin-thunder', 'german-academy', 'expired-trial', 'suspended'];
\App\Models\Tenant::whereIn('slug', $demoSlugs)->get(['id', 'slug', 'name']);
```

## Schritt 3A: Demo-Tenants via Tinker löschen (EMPFOHLEN)

**Soft Delete (empfohlen):**
```php
// Soft delete aller Demo-Tenants
$demoSlugs = ['demo', 'munich-eagles', 'berlin-thunder', 'german-academy', 'expired-trial', 'suspended'];
\App\Models\Tenant::whereIn('slug', $demoSlugs)->delete();

// Prüfen ob gelöscht
\App\Models\Tenant::whereIn('slug', $demoSlugs)->count(); // sollte 0 sein
```

**Hard Delete (permanent):**
```php
// ACHTUNG: Permanent! Kann nicht rückgängig gemacht werden!
$demoSlugs = ['demo', 'munich-eagles', 'berlin-thunder', 'german-academy', 'expired-trial', 'suspended'];
\App\Models\Tenant::whereIn('slug', $demoSlugs)->forceDelete();
```

**Zusätzlich Factory-generierte Demo-Tenants löschen:**
```php
// Prüfen ob es viele Tenants mit generischen Namen gibt
\App\Models\Tenant::where('subscription_tier', 'free')
    ->whereNotIn('slug', ['basketmanager-pro', 'staging'])
    ->get(['id', 'slug', 'name', 'subscription_tier']);

// Falls Demo-Tenants gefunden, diese auch löschen (VORSICHT!)
// Nur ausführen, wenn sicher dass es Demo-Daten sind!
```

## Schritt 3B: Demo-Tenants via SQL löschen

**Via MySQL/PostgreSQL Client:**

```bash
# Auf Staging-Server
php artisan tinker
```

```php
// Datenbankverbindung prüfen
DB::connection()->getPdo();

// Demo-Tenants anzeigen
DB::table('tenants')->whereIn('slug', [
    'demo',
    'munich-eagles',
    'berlin-thunder',
    'german-academy',
    'expired-trial',
    'suspended'
])->get(['id', 'slug', 'name']);

// Soft delete (setzt deleted_at)
DB::table('tenants')->whereIn('slug', [
    'demo',
    'munich-eagles',
    'berlin-thunder',
    'german-academy',
    'expired-trial',
    'suspended'
])->update(['deleted_at' => now()]);

// ODER Hard delete (permanent)
// ACHTUNG: Kann nicht rückgängig gemacht werden!
DB::table('tenants')->whereIn('slug', [
    'demo',
    'munich-eagles',
    'berlin-thunder',
    'german-academy',
    'expired-trial',
    'suspended'
])->delete();
```

## Schritt 4: Cache leeren

Nach dem Löschen den Cache leeren, damit alte Daten nicht mehr angezeigt werden:

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Schritt 5: Admin Dashboard überprüfen

Öffnen Sie `https://staging.basketmanager-pro.de/admin/dashboard` und prüfen Sie:
- ✅ Nur noch "BasketManager Pro" und "BasketManager Pro Staging" sollten sichtbar sein
- ✅ Demo-Tenants (Munich Eagles, Berlin Thunder, etc.) sollten verschwunden sein
- ✅ Statistiken sollten nur noch echte Tenants zählen

---

## Sicherheitsprüfung: Welche Tenants bleiben erhalten?

Diese beiden echten Tenants **NICHT löschen**:
1. **basketmanager-pro** - Production Tenant (basketmanager-pro.de)
2. **staging** - Staging Tenant (staging.basketmanager-pro.de)

```php
// Überprüfen dass echte Tenants noch existieren
\App\Models\Tenant::whereIn('slug', ['basketmanager-pro', 'staging'])->get(['slug', 'name', 'domain']);
```

---

## Alternative: Direkt via MySQL/PostgreSQL

Falls Sie direkten Datenbankzugriff haben:

```sql
-- MySQL/MariaDB
-- Demo-Tenants anzeigen
SELECT id, slug, name, subscription_tier
FROM tenants
WHERE slug IN ('demo', 'munich-eagles', 'berlin-thunder', 'german-academy', 'expired-trial', 'suspended')
AND deleted_at IS NULL;

-- Soft Delete (empfohlen)
UPDATE tenants
SET deleted_at = NOW()
WHERE slug IN ('demo', 'munich-eagles', 'berlin-thunder', 'german-academy', 'expired-trial', 'suspended');

-- ODER Hard Delete (permanent)
-- ACHTUNG: Kann nicht rückgängig gemacht werden!
DELETE FROM tenants
WHERE slug IN ('demo', 'munich-eagles', 'berlin-thunder', 'german-academy', 'expired-trial', 'suspended');
```

---

## Zukünftige Deployments

Nach dieser Änderung werden bei `php artisan db:seed` auf Staging **keine** Demo-Tenants mehr erstellt.

**Local Development:** Demo-Tenants werden weiterhin erstellt (Environment = `local`)
**Staging/Production:** Nur echte Tenants werden erstellt

---

## Troubleshooting

### Problem: Demo-Tenants erscheinen nach erneutem Seeding wieder

**Ursache:** `.env` auf Staging hat `APP_ENV=local` statt `APP_ENV=staging`

**Lösung:**
```bash
# .env auf Staging prüfen
cat .env | grep APP_ENV

# Falls APP_ENV=local, ändern zu:
APP_ENV=staging

# Config neu laden
php artisan config:clear
```

### Problem: Tenant-Count ist immer noch hoch

**Ursache:** Factory-generierte Tenants (Zeile 207-211 im alten Seeder)

**Lösung:**
```php
// In Tinker alle Tenants außer echte löschen
\App\Models\Tenant::whereNotIn('slug', ['basketmanager-pro', 'staging'])->delete();
```

---

## Dokumentation

Diese Änderung wurde dokumentiert in:
- `database/seeders/TenantSeeder.php` - Umgebungsbasierte Seeder-Logik
- Commit-Message sollte referenzieren: "Fix: Demo-Tenants nur in local Environment erstellen"
