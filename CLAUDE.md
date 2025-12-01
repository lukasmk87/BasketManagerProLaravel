# CLAUDE.md

> BasketManager Pro - Laravel Basketball Club Management Application
> Sprache: Deutsch | Stack: Laravel 12 + Vue.js 3 + Inertia.js

---

## Quick Start

```bash
composer dev          # Startet Server + Queue + Logs + Vite (empfohlen)
composer test         # Tests mit config:clear
./vendor/bin/pint     # Code Formatting
```

---

## Tech Stack

| Backend | Frontend |
|---------|----------|
| Laravel 12.x (PHP 8.2+) | Vue.js 3.3 + Vite 7.0 |
| MySQL 8.0+ / PostgreSQL 14+ | Tailwind CSS 3.4 |
| Redis 7.0+ | Inertia.js 2.0 |
| Sanctum + Jetstream | Chart.js 4.5 + TipTap |

**Packages:** Spatie Permission, Laravel Cashier (Stripe), DomPDF, Maatwebsite Excel, PWA

---

## Architektur

### Multi-Tenant (Tenant + Club)
- Row Level Security (RLS) auf Datenbankebene
- Club-scoped Queries via `TenantService`
- Feature Gates durch Subscription Tier (Tenant → Club Hierarchie)
- Tenant-Auflösung via Domain/Subdomain/Slug

→ Details: `TENANT_CLUB_ARCHITEKTUR.md`

### RBAC System
**11 Rollen** mit **136 Permissions** in 14 Kategorien:
- Super Admin / Admin (system-level)
- Club Admin (club-scoped)
- Trainer / Assistant Coach (team-scoped)
- Team Manager / Scorer / Referee (spezialisiert)
- Player / Parent / Guest (eingeschränkt)

→ Details: `BERECHTIGUNGS_MATRIX.md`, `ROLLEN_DOKUMENTATION_README.md`

### Super Admin
- `tenant_id = null` - Zugang zu ALLEN Tenants
- Bypassed `TenantScope` und `ResolveTenantMiddleware`
- Erstellt während Installation Wizard (ohne Club-Zuordnung)

→ Details: `TENANT_FREE_SUPER_ADMIN_ARCHITECTURE.md`

### Dynamic App Branding
```php
app_name()  // Tenant-specific → .env APP_NAME → 'BasketManager Pro'
```
Location: `app/Helpers/AppHelper.php`

---

## Development Commands

### Standard
```bash
composer dev                    # Server + Queue + Logs + Vite
php artisan serve               # Server only (Port 8000)
npm run dev                     # Vite only
php artisan queue:listen --tries=1
php artisan pail --timeout=0    # Real-time Logs
```

### Testing
```bash
composer test                           # Empfohlen
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test --filter=GameTest
php artisan test --coverage
```

### Code Quality
```bash
./vendor/bin/pint                       # Laravel Pint Formatting
./vendor/bin/phpstan analyse            # Static Analysis
```

### Database
```bash
php artisan migrate:fresh --seed
php artisan tinker
```

---

## Custom Artisan Commands

### Installation & Setup
```bash
php artisan installation:unlock         # Installation entsperren
php artisan tenant:initialize           # Ersten Tenant initialisieren
```

### Multi-Tenancy
```bash
php artisan tenant:setup-rls            # Row Level Security
php artisan tenant:usage:reset          # Usage Metrics zurücksetzen
php artisan tenant:repair-limits        # Limits reparieren
php artisan tenant:analyze-usage        # Usage analysieren
```

### Subscriptions
```bash
php artisan subscriptions:migrate-clubs # Clubs zu Plans migrieren
php artisan subscriptions:validate      # Data Integrity prüfen
php artisan subscriptions:health-check  # Health Check
php artisan subscription:update-mrr     # MRR Snapshots
php artisan subscription:calculate-churn
php artisan subscription:analytics-report
```

### Club Management
```bash
php artisan club:retry-failed-transfers # Fehlgeschlagene Transfers
php artisan club:cleanup-rollback-data  # Rollback-Daten aufräumen
php artisan player:regenerate-qr-codes  # QR-Codes neu generieren
```

### Rate Limiting & API
```bash
php artisan rate-limit:cleanup-usage
php artisan rate-limit:monitor
php artisan generate:openapi-docs       # OpenAPI 3.0 Docs
```

### Diagnostics
```bash
php artisan diagnose:user-permissions   # Permission-Diagnose
php artisan emergency:health-check      # Emergency System Test
php artisan manage:webhooks             # Stripe Webhooks
```

---

## Service-Architektur

**75 Services** in `app/Services/` - domänenbasierte Subdirectories.

### Wichtigste Domain Services
| Service | Funktion |
|---------|----------|
| `ClubService`, `TeamService`, `PlayerService` | Core Entities |
| `SeasonService` | Season Lifecycle |
| `LiveScoringService` | Echtzeit-Spielstand |
| `StatisticsService` | Basketball-Statistiken (FG%, 3P%, etc.) |
| `ClubTransferService` | Club-Transfer mit Rollback |

### Wichtigste Infrastructure Services
| Service | Funktion |
|---------|----------|
| `TenantService` | Multi-Tenant Scope |
| `FeatureGateService` | Subscription Features |
| `FeatureFlagService` | Feature Flags (Rollout) |

### Integration Services
- `Stripe/` - 14 Payment Services
- `Install/` - 5 Installation Services
- `OpenApi/` - 5 SDK Generator Services
- `ML/` - 4 Machine Learning Services

→ Vollständige Liste: `docs/claude/SERVICES.md`

---

## Route-Organisation

**29 Route-Dateien** - modular und feature-basiert.

### API Versionen
| Datei | Status |
|-------|--------|
| `routes/api/v1.php` | Deprecated |
| `routes/api/v2.php` | **Production** |
| `routes/api/v4.php` | Latest |

### Wichtige Route-Gruppen
- `routes/web.php` - Web mit Locale (`/{locale}/...`)
- `routes/club_checkout.php` - 17 Club Subscription Endpoints
- `routes/webhooks.php` - 11+ Stripe Webhook Events
- `routes/emergency.php` - QR-Code Notfallzugang (ohne Auth)

→ Vollständige Liste: `docs/claude/ROUTES.md`

---

## Middleware

**20 Custom Middleware** für Request/Response Pipeline.

### Wichtigste
| Middleware | Funktion |
|------------|----------|
| `ResolveTenantMiddleware` | Tenant-Auflösung |
| `EnforceFeatureGates` | Subscription Features |
| `AdminMiddleware` | Admin-only Access |
| `LocalizationMiddleware` | Sprache + URL-Prefix |

→ Vollständige Liste: `docs/claude/MIDDLEWARE.md`

---

## Models

**78 Models** mit **132 Migrations**.

### Core Models
`User`, `Club`, `BasketballTeam`, `Player`, `Season`, `Game`, `GameAction`, `TrainingSession`

### Subscription Models
`Subscription`, `ClubSubscriptionPlan`, `SubscriptionMRRSnapshot`, `ClubSubscriptionEvent`

### Alle Models nutzen
- Soft Deletes (kritische Models)
- Factories in `database/factories/`
- Spatie Permission (User)
- Laravel Cashier (Club)

→ Vollständige Liste: `docs/claude/MODELS.md`

---

## Testing

### BasketballTestCase
Extend `Tests\BasketballTestCase` für Basketball-spezifische Tests:

```php
// Pre-configured Users
$this->adminUser, $clubAdminUser, $trainerUser, $playerUser

// Pre-configured Data
$this->testClub, $testTeam, $testPlayer

// Helper Methods
$this->actingAsAdmin()
$this->actingAsClubAdmin()
$this->createTestGame($home, $away)
$this->createTeamRoster($team, $count)
```

### Test-Struktur
```
tests/
├── Feature/           # Endpoints, Web Routes
├── Feature/Api/V2/    # API v2 Tests
├── Integration/       # Webhooks, External APIs
└── Unit/              # Services, Models, Policies
```

→ Details: `TESTING.md`, `TEST_USERS.md`

---

## Stripe Integration

### Subscription Tiers (Club-Level)
| Plan | Preis | Teams | Players |
|------|-------|-------|---------|
| Free | €0 | 2 | 30 |
| Standard | €49/mo | 10 | 150 |
| Premium | €149/mo | 50 | 500 |
| Enterprise | €299/mo | 100 | 1000 |

### Payment Methods (German Market)
Card, SEPA, Sofort, Giropay, EPS, Bancontact, iDEAL

### Feature Gates
```php
if ($club->hasFeature('live_scoring')) { ... }
$club->canUse('teams', 1);
$club->getLimit('max_teams');
```

### Webhook Events
`checkout.session.completed`, `customer.subscription.*`, `invoice.*`, `payment_method.*`

→ Vollständige Dokumentation:
- `docs/SUBSCRIPTION_API_REFERENCE.md` (17 Endpoints, 11 Webhooks)
- `docs/SUBSCRIPTION_INTEGRATION_GUIDE.md`
- `docs/SUBSCRIPTION_ARCHITECTURE.md`
- `docs/SUBSCRIPTION_TESTING.md` (40 Tests)

---

## Basketball-Spezifisch

### Statistiken
FG%, 3P%, FT%, PER, TS%, AST/TO Ratio, Defensive Rating

### Positionen
PG (Point Guard), SG (Shooting Guard), SF (Small Forward), PF (Power Forward), C (Center)

### Live Scoring
- WebSocket Channels: `game.{id}`, `team.{id}`, `club.{id}`
- Quarter Management (4x10min, 2x20min)
- Game Actions: Shots, Rebounds, Assists, Fouls

---

## Localization

Primärsprache: **Deutsch (de)**

```
/{locale}/dashboard
/{locale}/teams/{team}
```

Package: `mcamara/laravel-localization`
Translations: `resources/lang/`

---

## Dokumentations-Index

### Core
| Datei | Inhalt |
|-------|--------|
| `FEATURES.md` | Alle Features |
| `TESTING.md` | Teststrategie |
| `TEST_USERS.md` | Testaccounts (11 Rollen) |

### Architektur
| Datei | Inhalt |
|-------|--------|
| `BERECHTIGUNGS_MATRIX.md` | 136 Permissions × 11 Rollen |
| `TENANT_CLUB_ARCHITEKTUR.md` | Multi-Tenant Details |
| `TENANT_FREE_SUPER_ADMIN_ARCHITECTURE.md` | Super Admin |

### Stripe/Subscriptions
| Datei | Inhalt |
|-------|--------|
| `docs/SUBSCRIPTION_API_REFERENCE.md` | API Docs (17 Endpoints) |
| `docs/SUBSCRIPTION_INTEGRATION_GUIDE.md` | Developer Setup |
| `docs/SUBSCRIPTION_ARCHITECTURE.md` | System Architecture |
| `docs/SUBSCRIPTION_TESTING.md` | Test Suite (40 Tests) |
| `docs/SUBSCRIPTION_ADMIN_GUIDE.md` | Admin Guide |
| `docs/SUBSCRIPTION_DEPLOYMENT_GUIDE.md` | Production Deployment |

### Deployment
| Datei | Inhalt |
|-------|--------|
| `PRODUCTION_DEPLOYMENT.md` | Deployment Checklist |
| `docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md` | Detaillierte Steps |
| `HTTP_500_FIX_DEPLOYMENT.md` | Troubleshooting |

### Claude Code Detail-Referenzen
| Datei | Inhalt |
|-------|--------|
| `docs/claude/SERVICES.md` | Alle 75 Services |
| `docs/claude/ROUTES.md` | Alle 29 Route-Dateien |
| `docs/claude/MIDDLEWARE.md` | Alle 20 Middleware |
| `docs/claude/MODELS.md` | Alle 78 Models |

---

## Quick Links

- **Entwicklung starten**: `composer dev`
- **Tests ausführen**: `composer test`
- **Permissions prüfen**: `BERECHTIGUNGS_MATRIX.md`
- **Subscription API**: `docs/SUBSCRIPTION_API_REFERENCE.md`
- **Deployment**: `PRODUCTION_DEPLOYMENT.md`
