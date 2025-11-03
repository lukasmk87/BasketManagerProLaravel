# Installation Wizard Fix - Troubleshooting Guide

## Problem

Der Installation Wizard erstellte Tenants **ohne korrekte Subscription-Limits** und **fehlende Feature-Flags**, was dazu führte, dass:
- Feature Gates nicht funktionierten (`hasFeature()` Checks schlugen fehl)
- Usage Limits zu restriktiv waren (10 User statt 200 bei Professional)
- Billing-Funktionen nicht richtig arbeiteten

## Root Cause

Die `InstallationService::createSuperAdmin()` Methode fehlte die Integration mit dem Subscription Tier System:
- Keine tier-basierten Limits (`max_users`, `max_teams`, `max_storage_gb`, `max_api_calls_per_hour`)
- Keine Feature-Flags in `settings['features']`
- Fehlende Pflichtfelder (`billing_email`, `country_code`, `locale`, `currency`)
- Falscher Feldname: `database` statt `database_name`

## Solution

### 1. Shared Service erstellt

**`app/Services/TenantLimitsService.php`**
- Zentrale Verwaltung aller Subscription Tier Limits
- 4 Tiers: Free, Basic, Professional, Enterprise
- DRY Principle: Wird von Installation Wizard UND Console Command genutzt

### 2. InstallationService aktualisiert

**Was wurde gefixt:**
```php
// ✅ NEU: TenantLimitsService Integration
$limits = TenantLimitsService::getLimits($data['subscription_tier']);

$tenant = Tenant::create([
    // ✅ Korrekte Feld-Namen
    'database_name' => ...,  // Vorher: 'database'

    // ✅ Fehlende Pflichtfelder hinzugefügt
    'billing_email' => $data['admin_email'],
    'country_code' => 'DE',
    'locale' => $data['language'],
    'currency' => 'EUR',
    'is_active' => true,

    // ✅ Subscription Limits basierend auf Tier
    'max_users' => $limits['max_users'],
    'max_teams' => $limits['max_teams'],
    'max_storage_gb' => $limits['max_storage_gb'],
    'max_api_calls_per_hour' => $limits['max_api_calls_per_hour'],

    // ✅ Settings mit Features, Branding, Contact
    'settings' => [
        'language' => $data['language'],
        'timezone' => config('app.timezone'),
        'features' => $limits['features'],  // KRITISCH!
        'branding' => [...],
        'contact' => [...],
    ],
]);
```

### 3. InitializeTenantCommand refaktoriert

- Alte `getSubscriptionLimits()` Methode entfernt
- Nutzt jetzt `TenantLimitsService::getLimits()`
- Keine Code-Duplizierung mehr

### 4. RepairTenantLimitsCommand erstellt

**Für existierende Tenants**, die vor dem Fix erstellt wurden:

```bash
# Vorschau (dry-run)
php artisan tenant:repair-limits --dry-run

# Alle Tenants reparieren
php artisan tenant:repair-limits --force

# Spezifischen Tenant reparieren
php artisan tenant:repair-limits --tenant=1 --force
```

**Was der Command repariert:**
- ✅ Setzt korrekte `max_users`, `max_teams`, `max_storage_gb`, `max_api_calls_per_hour`
- ✅ Fügt `settings['features']` Array hinzu
- ✅ Fügt `settings['branding']` und `settings['contact']` hinzu
- ✅ Setzt fehlende Felder (`billing_email`, `country_code`, `locale`, `currency`, `is_active`)
- ✅ Behält custom Settings bei (merged, nicht überschrieben)

## Testing

### 1. TenantLimitsServiceTest ✅
```bash
php artisan test tests/Unit/Services/TenantLimitsServiceTest.php
```
**14 Tests, 97 Assertions** - Alle PASSED

**Testabdeckung:**
- ✅ Alle 4 Tiers (free, basic, professional, enterprise)
- ✅ Invalid Tier Handling (defaults to professional)
- ✅ Feature Progression (mehr Features bei höheren Tiers)
- ✅ Limits Progression (mehr Ressourcen bei höheren Tiers)
- ✅ Unlimited für Enterprise (-1)
- ✅ Formatierte Ausgabe

### 2. InstallationServiceTest
```bash
php artisan test tests/Unit/Services/Install/InstallationServiceTest.php
```
**13 Tests** - Umfassende Tests der `createSuperAdmin()` Methode

**Testabdeckung:**
- ✅ Tenant wird mit korrekten Limits erstellt
- ✅ Features in Settings vorhanden
- ✅ Branding und Contact Settings
- ✅ Billing Email korrekt gesetzt
- ✅ User mit Tenant verknüpft
- ✅ Super Admin Rolle zugewiesen
- ✅ Club erstellt und verknüpft
- ✅ Rollback bei Fehlern

### 3. InstallWizardTest (E2E)
```bash
php artisan test tests/Feature/InstallWizardTest.php
```
**13 Tests** - Vollständiger Installation Wizard Flow

**Testabdeckung:**
- ✅ Complete Installation Flow (alle 7 Steps)
- ✅ Alle 4 Subscription Tiers
- ✅ Settings (Features, Branding, Contact)
- ✅ Required Fields
- ✅ User/Tenant/Club Verknüpfung
- ✅ Validation (invalid Tier, fehlende Migrations)
- ✅ Sprach-Auswahl
- ✅ Password Hashing

### 4. RepairTenantLimitsCommandTest
```bash
php artisan test tests/Feature/Commands/RepairTenantLimitsCommandTest.php
```
**12 Tests** - Command Funktionalität

**Testabdeckung:**
- ✅ Repariert fehlende Limits
- ✅ Fügt Features hinzu
- ✅ Fügt Branding/Contact hinzu
- ✅ Setzt Required Fields
- ✅ Überspringt korrekte Tenants
- ✅ Dry-run Mode
- ✅ Spezifischer Tenant by ID
- ✅ Multiple Tiers
- ✅ Preserviert Custom Settings

## Subscription Tier Limits

### Free Tier
- **Users**: 10
- **Teams**: 5
- **Storage**: 5 GB
- **API Calls**: 100/hour
- **Features**: `basic_stats`, `team_management`, `player_roster`

### Basic Tier (€29/mo)
- **Users**: 50
- **Teams**: 20
- **Storage**: 50 GB
- **API Calls**: 1,000/hour
- **Features**: Basic + `advanced_stats`, `training_management`, `game_scheduling`

### Professional Tier (€99/mo)
- **Users**: 200
- **Teams**: 50
- **Storage**: 200 GB
- **API Calls**: 5,000/hour
- **Features**: Basic + `live_scoring`, `video_analysis`, `tournament_management`, `api_access`

### Enterprise Tier (Custom)
- **Users**: Unlimited (-1)
- **Teams**: Unlimited (-1)
- **Storage**: Unlimited (-1)
- **API Calls**: Unlimited (-1)
- **Features**: All + `white_label`, `custom_domain`, `priority_support`, `sla_guarantee`

## Verwendung

### Installation Wizard (Neu)
Der Wizard erstellt ab sofort automatisch Tenants mit korrekten Limits:

```
/install → Sprache → Welcome → Requirements → Permissions → Environment → Database → Admin
```

Im **Admin-Step** (Step 6):
1. Tenant Name eingeben
2. Subscription Tier wählen (Free/Basic/Professional/Enterprise)
3. Admin Daten eingeben
4. **Tenant wird mit korrekten Limits erstellt** ✅

### Console Command (Unverändert)
```bash
php artisan tenant:initialize --force
```
Nutzt jetzt ebenfalls `TenantLimitsService` (refaktoriert).

### Existierende Tenants reparieren
```bash
# Preview
php artisan tenant:repair-limits --dry-run

# Alle reparieren
php artisan tenant:repair-limits --force

# Einzelner Tenant
php artisan tenant:repair-limits --tenant=1 --force
```

## Dateien geändert

### Core Services
- ✅ `app/Services/TenantLimitsService.php` (NEU)
- ✅ `app/Services/Install/InstallationService.php` (AKTUALISIERT)

### Console Commands
- ✅ `app/Console/Commands/InitializeTenantCommand.php` (REFACTORED)
- ✅ `app/Console/Commands/RepairTenantLimitsCommand.php` (NEU)

### Tests
- ✅ `tests/Unit/Services/TenantLimitsServiceTest.php` (NEU - 14 tests)
- ✅ `tests/Unit/Services/Install/InstallationServiceTest.php` (NEU - 13 tests)
- ✅ `tests/Feature/InstallWizardTest.php` (NEU - 13 tests)
- ✅ `tests/Feature/Commands/RepairTenantLimitsCommandTest.php` (NEU - 12 tests)

**Gesamt: 52 neue Tests mit vollständiger Abdeckung**

## Verification

Nach der Installation überprüfen:

```php
$tenant = Tenant::first();

// Check Limits
$tenant->max_users;           // z.B. 200 (Professional)
$tenant->max_teams;           // z.B. 50
$tenant->max_storage_gb;      // z.B. 200
$tenant->max_api_calls_per_hour;  // z.B. 5000

// Check Features
$tenant->settings['features']['live_scoring'];      // true
$tenant->settings['features']['video_analysis'];    // true

// Check Settings
$tenant->settings['branding']['primary_color'];  // '#4F46E5'
$tenant->settings['contact']['support_email'];   // admin email

// Check Required Fields
$tenant->billing_email;  // nicht null
$tenant->country_code;   // 'DE'
$tenant->locale;         // 'de'
$tenant->currency;       // 'EUR'
$tenant->is_active;      // true
```

## Migration für Production

Wenn du bereits Tenants in Production hast:

```bash
# 1. Preview der Änderungen
php artisan tenant:repair-limits --dry-run

# 2. Backup der Datenbank erstellen
mysqldump basketmanager > backup_before_repair.sql

# 3. Repair ausführen
php artisan tenant:repair-limits --force

# 4. Verify
php artisan tinker
>>> Tenant::all()->each(fn($t) => dump([
>>>     'name' => $t->name,
>>>     'tier' => $t->subscription_tier,
>>>     'limits' => [
>>>         'users' => $t->max_users,
>>>         'teams' => $t->max_teams,
>>>     ],
>>>     'features' => count($t->settings['features'] ?? []),
>>> ]))
```

## Troubleshooting

### Problem: Feature Gates schlagen fehl
```php
if ($club->hasFeature('live_scoring')) { // false trotz Professional Tier
```

**Lösung:**
```bash
php artisan tenant:repair-limits --force
```

### Problem: Usage Limit Errors
```
User limit reached (10/10)
```
Obwohl Professional Tier 200 Users erlaubt.

**Lösung:**
```bash
php artisan tenant:repair-limits --force
```

### Problem: Billing Email fehlt
```
SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'billing_email' cannot be null
```

**Lösung:**
```bash
php artisan tenant:repair-limits --force
```

## Next Steps

✅ **Installierter Fix**: Neue Installationen funktionieren korrekt
✅ **Repair Command**: Existierende Tenants können repariert werden
✅ **Tests**: Umfassende Test-Suite (52 Tests)
✅ **Dokumentation**: Troubleshooting Guide erstellt

**Empfohlen für Production:**
1. Backup erstellen
2. `tenant:repair-limits --dry-run` ausführen (Preview)
3. `tenant:repair-limits --force` ausführen
4. Feature Gates testen
5. Usage Limits verifizieren
