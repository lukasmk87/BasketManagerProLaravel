# Tenant & Club Architektur

## Übersicht

Dieses Dokument erklärt die Beziehung zwischen **Tenants** und **Clubs** in der BasketManager Pro Anwendung und wie die Multi-Tenancy-Architektur implementiert ist.

## Architektur-Hierarchie

```
Tenant (1) ──┬──> (n) Clubs
             ├──> (n) Users
             ├──> (n) Teams
             ├──> (n) Players
             ├──> (n) Games
             └──> (n) Tournaments
```

## Hauptunterschiede

### Tenant = Multi-Tenancy-Ebene (SaaS-Organisation)

Ein **Tenant** repräsentiert eine eigenständige Organisation/Instanz im SaaS-System:

- **Zweck**: Datenisolation zwischen verschiedenen Organisationen
- **Subscription-Management**: Hat eigene Subscription-Tiers (free, basic, professional, enterprise)
- **Billing**:
  - Eigene Billing-Informationen
  - Stripe-Integration pro Tenant
  - MRR (Monthly Recurring Revenue) Tracking
  - Eigene Stripe-Konfiguration und API-Keys
- **Limits**: Eigene Limits für:
  - `max_users` - Maximale Anzahl Benutzer
  - `max_teams` - Maximale Anzahl Teams
  - `max_storage_gb` - Maximaler Storage in GB
  - `max_api_calls_per_hour` - API-Limitierung
- **Technisch**:
  - Kann eigene Datenbank haben (`database_name`, `database_host`)
  - Eigenes PostgreSQL-Schema (`schema_name`)
  - Row Level Security (RLS) Unterstützung
- **Features**: Feature-Gates basierend auf Subscription-Tier
- **Identifikation**:
  - UUID als Primary Key
  - `domain` (z.B. `basketclub-muenchen.de`)
  - `subdomain` (z.B. `muenchen.basketmanager-pro.com`)
  - `slug` (z.B. `basketclub-muenchen`)

**Datei-Referenzen:**
- Model: `app/Models/Tenant.php`
- Service: `app/Services/TenantService.php`
- Migration: `database/migrations/2025_08_13_093209_create_tenants_table.php`

### Club = Basketball-Vereins-Ebene (Domain-Entity)

Ein **Club** repräsentiert einen konkreten Basketball-Verein:

- **Zweck**: Business-Domain-Entity (Verein mit Teams, Spielern, Spielen)
- **Daten**:
  - Name, Logo, Adresse
  - Vorstandsmitglieder (Präsident, Vize-Präsident, Sekretär, Schatzmeister)
  - Kontaktpersonen
  - Trainingszeiten und Facilities
  - Mitgliedsbeiträge (monatlich/jährlich)
  - Social Media Links
  - GDPR-Compliance-Daten
- **Beziehungen**:
  - Hat Teams (`teams`)
  - Hat Spieler über Teams (`players`)
  - Hat Trainer/Coaches (`coaches`)
  - Hat Gym Halls (`gymHalls`)
  - Hat User-Mitglieder über Pivot-Tabelle (`users`)
- **Zugehörigkeit**: Gehört immer zu einem Tenant über `tenant_id` Foreign Key

**Datei-Referenzen:**
- Model: `app/Models/Club.php`
- Service: `app/Services/ClubService.php` (falls vorhanden)

## Datenbank-Struktur

### Tenant-Tabelle (Hauptfelder)

```sql
tenants
├── id (UUID, PK)
├── name
├── slug (unique, indexed)
├── domain (unique, indexed)
├── subdomain (unique, indexed)
├── subscription_tier (free/basic/professional/enterprise)
├── trial_ends_at
├── is_active (indexed)
├── is_suspended
├── max_users, max_teams, max_storage_gb, max_api_calls_per_hour
├── current_users_count, current_teams_count, current_storage_gb
├── billing_email, billing_name, billing_address, vat_number
├── stripe_id (via Laravel Cashier Billable trait)
└── tenant_id → KEINE (Tenant ist die oberste Ebene)
```

### Club-Tabelle (Hauptfelder)

```sql
clubs
├── id (PK)
├── uuid
├── tenant_id (FK → tenants.id, indexed)
├── name
├── slug
├── description
├── address_street, address_city, address_state, address_zip, address_country
├── email, phone, website
├── president_name, secretary_name, treasurer_name
├── membership_fee_annual, membership_fee_monthly
├── has_indoor_courts, has_outdoor_courts, court_count
└── is_active, is_verified
```

### Beziehung im Code

**Tenant → Clubs (1:n)**
```php
// app/Models/Tenant.php:521-523
public function clubs()
{
    return $this->hasMany(Club::class);
}
```

**Club → Tenant (n:1)**
```php
// Implizit durch BelongsToTenant trait
public function tenant()
{
    return $this->belongsTo(Tenant::class);
}
```

## Anwendungsfälle

### Beispiel 1: Basketball-Verband (Multi-Club-Tenant)

Ein großer Verband nutzt das System für mehrere angeschlossene Vereine:

```
Tenant: "Bayerischer Basketball Verband" (enterprise tier)
├── max_users: 1000
├── max_teams: 100
├── Features: live_scoring, advanced_analytics, api_access, white_label
│
├── Club: "FC Bayern Basketball"
│   ├── Team: "Herren 1. Bundesliga"
│   ├── Team: "Damen Regionalliga"
│   ├── Team: "U18 männlich"
│   └── 120 Players
│
├── Club: "ratiopharm Ulm"
│   ├── Team: "Herren 1. Bundesliga"
│   ├── Team: "Herren 2. Liga"
│   └── 80 Players
│
└── Club: "s.Oliver Würzburg"
    ├── Team: "Herren 1. Bundesliga"
    └── 95 Players
```

**Billing:** Der Verband zahlt eine Enterprise-Subscription und verwaltet alle Clubs zentral.

### Beispiel 2: Einzelner Verein (Single-Club-Tenant)

Ein einzelner Verein nutzt das System:

```
Tenant: "München Basketballer e.V." (basic tier)
├── max_users: 50
├── max_teams: 10
├── Features: basic_features
│
└── Club: "München Basketballer e.V."
    ├── Team: "Herren Bezirksliga"
    ├── Team: "Damen Kreisliga"
    ├── Team: "U18 männlich"
    ├── Team: "U16 weiblich"
    └── 45 Players
```

**Billing:** Der Verein zahlt eine Basic-Subscription für 29€/Monat.

## Datenisolation (Row Level Security)

### Automatische Tenant-Filterung

Alle Eloquent-Queries werden automatisch nach dem aktuellen Tenant gefiltert:

#### TenantScope (`app/Models/Scopes/TenantScope.php`)

```php
public function apply(Builder $builder, Model $model): void
{
    $tenant = app('tenant', null);
    if ($tenant && $this->modelHasTenantColumn($model)) {
        $builder->where($model->getQualifiedTenantColumn(), $tenant->id);
    }
}
```

**Effekt:**
```php
// Automatisch gefiltert nach aktuellem Tenant
Club::all();           // → SELECT * FROM clubs WHERE tenant_id = 'current-tenant-uuid'
Team::all();           // → SELECT * FROM teams WHERE tenant_id = 'current-tenant-uuid'
Player::all();         // → SELECT * FROM players WHERE tenant_id = 'current-tenant-uuid'
```

#### Bypass-Möglichkeiten (für Super-Admins)

```php
// Alle Tenants abfragen
Club::allTenants()->get();

// Spezifischen Tenant abfragen
Club::forTenant($otherTenantId)->get();

// Tenant-Scope temporär deaktivieren
Club::withoutGlobalScope(TenantScope::class)->get();
```

### BelongsToTenant Trait (`app/Models/Concerns/BelongsToTenant.php`)

Automatische `tenant_id` Zuweisung beim Erstellen neuer Models:

```php
static::creating(function ($model) {
    if (empty($model->tenant_id)) {
        $tenant = app('tenant', null);
        if ($tenant) {
            $model->tenant_id = $tenant->id;
        }
    }
});
```

**Effekt:**
```php
// tenant_id wird automatisch gesetzt
$club = Club::create([
    'name' => 'Neuer Verein',
    // tenant_id → automatisch vom aktuellen Tenant
]);
```

### Models mit Tenant-Isolation

Laut `database/migrations/2025_08_13_093209_create_tenants_table.php:108-112`:

```php
$tables = [
    'teams', 'players', 'games', 'tournaments', 'training_sessions',
    'clubs', 'seasons', 'game_actions', 'game_statistics',
    'training_drills', 'media', 'emergency_contacts'
];
```

Alle diese Tabellen haben eine `tenant_id` Foreign Key Spalte.

## User-Zugehörigkeit

Users können zu **beiden** Ebenen gehören:

### 1. User → Tenant (n:1)

Direkte Zugehörigkeit über `users.tenant_id`:

```php
// app/Models/Tenant.php:491-494
public function users()
{
    return $this->hasMany(User::class);
}
```

```php
$user->tenant_id = $tenant->id;  // User gehört zu einem Tenant
```

### 2. User → Club (n:m)

Many-to-Many Beziehung über Pivot-Tabelle `club_user`:

```php
// app/Models/Club.php:159-169
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class)
        ->withPivot([
            'role', 'joined_at', 'membership_expires_at', 'is_active',
            'is_verified', 'membership_number', 'membership_type',
            'membership_fee_paid', 'last_payment_date', 'payment_status',
            'permissions', 'restricted_areas', 'receive_newsletters',
            'receive_game_notifications', 'receive_emergency_alerts',
            'notes', 'metadata'
        ])
        ->withTimestamps();
}
```

**Beispiel:**
```php
// User gehört zu einem Tenant
$user->tenant_id = 'tenant-uuid-123';

// User ist Mitglied in mehreren Clubs innerhalb des Tenants
$club1->users()->attach($user, ['role' => 'trainer', 'is_active' => true]);
$club2->users()->attach($user, ['role' => 'player', 'is_active' => true]);

// User hat verschiedene Rollen in verschiedenen Clubs
$user->clubs; // Collection von Clubs mit Pivot-Daten
```

## Subscription & Features

### Feature-Gates (Tenant-Ebene)

Features werden auf **Tenant-Ebene** gesteuert, nicht auf Club-Ebene:

```php
// app/Models/Tenant.php:272-285
public function hasFeature(string $feature): bool
{
    // Check subscription tier features
    $tierFeatures = config("tenants.tiers.{$this->subscription_tier}.features", []);

    if (in_array($feature, $tierFeatures)) {
        return true;
    }

    // Check custom features
    $customFeatures = $this->features ?? [];

    return in_array($feature, $customFeatures);
}
```

**Verwendung im Code:**
```php
// Feature-Gate überprüfen
if (tenant()->hasFeature('live_scoring')) {
    // Live-Scoring-Funktionalität anzeigen
}

// Feature-Zugriff erzwingen (wirft Exception wenn nicht verfügbar)
tenant()->enforceFeatureAccess('advanced_analytics');
```

### Subscription-Tiers

Konfiguriert in `config/tenants.php`:

| Tier | Preis | Users | Teams | Storage | Features |
|------|-------|-------|-------|---------|----------|
| **free** | 0€ | 10 | 5 | 5 GB | Basis-Features |
| **basic** | 29€/Monat | 50 | 20 | 50 GB | + Live Scoring |
| **professional** | 99€/Monat | 200 | 50 | 200 GB | + Analytics, API |
| **enterprise** | Custom | Unlimited | Unlimited | Unlimited | + White Label, SSO |

### Limits & Usage Tracking

```php
// Limits prüfen
$tenant->hasReachedUserLimit();     // bool
$tenant->hasReachedTeamLimit();     // bool
$tenant->hasReachedStorageLimit();  // bool

// Aktuelle Usage
$tenant->current_users_count;
$tenant->current_teams_count;
$tenant->current_storage_gb;

// Limits
$tenant->max_users;
$tenant->max_teams;
$tenant->max_storage_gb;
$tenant->max_api_calls_per_hour;
```

## Tenant-Resolution (Wie wird der aktuelle Tenant ermittelt?)

### 1. Domain-basiert

```php
// app/Models/Tenant.php:230-238
public static function resolveFromDomain(string $domain): ?self
{
    return Cache::remember(
        "tenant:domain:{$domain}",
        3600,
        fn() => static::where('domain', $domain)
                     ->where('is_active', true)
                     ->first()
    );
}
```

**Beispiel:** `basketclub-muenchen.de` → Tenant "München Basketballer e.V."

### 2. Subdomain-basiert

```php
// app/Models/Tenant.php:244-252
public static function resolveFromSubdomain(string $subdomain): ?self
{
    return Cache::remember(
        "tenant:subdomain:{$subdomain}",
        3600,
        fn() => static::where('subdomain', $subdomain)
                     ->where('is_active', true)
                     ->first()
    );
}
```

**Beispiel:** `muenchen.basketmanager-pro.com` → Tenant "München Basketballer e.V."

### 3. Slug-basiert

```php
// app/Models/Tenant.php:258-266
public static function resolveFromSlug(string $slug): ?self
{
    return Cache::remember(
        "tenant:slug:{$slug}",
        3600,
        fn() => static::where('slug', $slug)
                     ->where('is_active', true)
                     ->first()
    );
}
```

**Beispiel:** `/tenant/basketclub-muenchen` → Tenant "München Basketballer e.V."

### Middleware

Tenant wird durch Middleware aufgelöst:
- `app/Http/Middleware/ResolveTenantMiddleware.php`
- `app/Http/Middleware/TenantRateLimitMiddleware.php`
- `app/Http/Middleware/ConfigureTenantStripe.php`

## Stripe-Integration

### Tenant-basierte Stripe-Konfiguration

```php
// app/Models/Tenant.php:584-603
public function getStripeConfig(): array
{
    return $this->getSetting('stripe', [
        'mode' => 'test',
        'publishable_key' => null,
        'secret_key' => null,
        'webhook_secret' => null,
        'account_id' => null, // For Stripe Connect
    ]);
}

public function updateStripeConfig(array $config): void
{
    $currentConfig = $this->getStripeConfig();
    $newConfig = array_merge($currentConfig, $config);

    $this->updateSettings(['stripe' => $newConfig]);
}
```

### Laravel Cashier Integration

```php
// app/Models/Tenant.php:24
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable;
}
```

**Tenant kann:**
- Subscriptions verwalten (`$tenant->subscriptions`)
- Payment Methods speichern
- Invoices generieren
- Stripe Webhooks empfangen

**Service:** `app/Services/Stripe/CashierTenantManager.php`

## Vergleichstabelle

| Aspekt | Tenant | Club |
|--------|--------|------|
| **Ebene** | SaaS/Infrastruktur | Business-Domain |
| **Anzahl** | 1 pro Organisation | n pro Tenant (0 bis unlimited) |
| **Subscription** | ✅ Ja (free, basic, pro, enterprise) | ❌ Nein |
| **Billing** | ✅ Ja (Stripe, Cashier) | ❌ Nein |
| **Limits** | ✅ Ja (users, teams, storage, API) | ❌ Nein |
| **Features** | ✅ Ja (Feature-Gates) | ❌ Nein |
| **Teams** | Indirekt über Clubs | ✅ Direkt (`club_id` FK) |
| **Players** | Indirekt über Teams | ✅ Indirekt über Teams |
| **Users** | ✅ Direkt (`tenant_id` FK) | ✅ Many-to-Many (Pivot) |
| **Datenisolation** | Oberste Ebene (RLS) | Innerhalb Tenant |
| **Use Case** | Mandantenfähigkeit | Basketball-Vereinsverwaltung |
| **Identifikation** | domain/subdomain/slug | name/slug |
| **Eigene DB** | ✅ Möglich | ❌ Nein |
| **API Keys** | ✅ Ja | ❌ Nein |
| **Webhooks** | ✅ Ja | ❌ Nein |

## Code-Referenzen

### Models
- `app/Models/Tenant.php` - Tenant Model mit Billable Trait
- `app/Models/Club.php` - Club Model mit HasMedia
- `app/Models/User.php` - User gehört zu Tenant und Clubs
- `app/Models/Team.php` - Team gehört zu Club und Tenant
- `app/Models/Player.php` - Player gehört zu Tenant

### Services
- `app/Services/TenantService.php` - Tenant-Verwaltung, Statistics, Limits
- `app/Services/Stripe/CashierTenantManager.php` - Multi-Tenant Cashier Integration
- `app/Services/FeatureGateService.php` - Feature-Gates und Subscription-Checks

### Middleware
- `app/Http/Middleware/ResolveTenantMiddleware.php` - Tenant Resolution
- `app/Http/Middleware/TenantRateLimitMiddleware.php` - Rate Limiting pro Tenant
- `app/Http/Middleware/ConfigureTenantStripe.php` - Stripe Config pro Tenant

### Traits & Scopes
- `app/Models/Concerns/BelongsToTenant.php` - Tenant-Zugehörigkeit
- `app/Models/Scopes/TenantScope.php` - Automatische Tenant-Filterung

### Migrations
- `database/migrations/2025_08_13_093209_create_tenants_table.php` - Tenant-Tabelle
- `database/migrations/2025_08_13_121308_create_tenant_usages_table.php` - Usage Tracking
- `database/migrations/2025_10_13_102053_create_tenant_plan_customizations_table.php` - Plan Customizations

### Config
- `config/tenants.php` - Tenant-Konfiguration, Tiers, Features, Limits

## Best Practices

### 1. Immer Tenant-Kontext verwenden

```php
// ✅ Gut: Automatische Tenant-Filterung
$clubs = Club::all();

// ❌ Schlecht: Tenant-Scope umgehen (nur für Super-Admins)
$clubs = Club::withoutGlobalScope(TenantScope::class)->get();
```

### 2. Tenant-ID nicht manuell setzen

```php
// ✅ Gut: Automatische Zuweisung durch BelongsToTenant
$club = Club::create(['name' => 'Neuer Verein']);

// ❌ Schlecht: Manuelle Zuweisung (redundant und fehleranfällig)
$club = Club::create([
    'name' => 'Neuer Verein',
    'tenant_id' => auth()->user()->tenant_id
]);
```

### 3. Feature-Gates überprüfen

```php
// ✅ Gut: Feature-Gate prüfen
if (tenant()->hasFeature('live_scoring')) {
    // Feature verfügbar
}

// ✅ Gut: Exception werfen wenn Feature nicht verfügbar
tenant()->enforceFeatureAccess('api_access');
```

### 4. Limits respektieren

```php
// ✅ Gut: Limit prüfen vor Erstellung
if (!tenant()->hasReachedUserLimit()) {
    $user = User::create([...]);
} else {
    throw new \Exception('User limit reached. Upgrade subscription.');
}
```

### 5. Tenant-Switch sicher durchführen

```php
// ✅ Gut: Service nutzen
app(TenantService::class)->switchTenant($newTenant, $request);

// ❌ Schlecht: Direkte Session-Manipulation
session()->put('tenant_id', $newTenant->id);
```

## Zusammenfassung

Die **Tenant-Club-Architektur** ermöglicht es BasketManager Pro:

1. **Multi-Tenancy**: Komplette Datenisolation zwischen Organisationen
2. **Flexibilität**: Sowohl große Verbände als auch einzelne Vereine können das System nutzen
3. **Skalierbarkeit**: Subscription-basierte Limits und Features
4. **Sicherheit**: Automatische Row-Level-Security durch Global Scopes
5. **SaaS-Fähigkeit**: Billing, API-Keys, Webhooks, Custom Domains pro Tenant

**Tenant** = "Wer zahlt und verwaltet das Abo?"
**Club** = "Welcher Basketball-Verein nutzt das System?"

Ein Tenant kann 0 bis n Clubs haben, je nach Use Case.
