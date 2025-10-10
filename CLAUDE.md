# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BasketManager Pro is a production-ready Laravel-based basketball club management application with comprehensive features for multi-tenant club operations, live game scoring, player statistics, training management, and GDPR-compliant emergency contact systems.

## Architecture

- **Framework**: Laravel 12.x
- **PHP Version**: 8.2+
- **Frontend**: Vue.js 3.x + Inertia.js 2.0 + Tailwind CSS 3.4
- **Database**: MySQL 8.0+ / PostgreSQL 14+ with Row Level Security
- **Cache/Queue**: Redis 7.0+
- **Real-time**: Laravel Broadcasting + Pusher WebSockets
- **Authentication**: Laravel Sanctum 4.0 + Jetstream 5.3
- **Payments**: Stripe (Laravel Cashier 15.7+) with multi-tenant subscriptions
- **PWA**: Full Progressive Web App implementation
- **PDF Generation**: DomPDF (barryvdh/laravel-dompdf)
- **Excel Export**: Maatwebsite Excel
- **Permissions**: Spatie Laravel Permission 6.21+

## Multi-Tenant Architecture

The application uses tenant-based data isolation:
- Row Level Security (RLS) enforced at database level
- Club-scoped queries via `TenantService`
- Feature gates controlled by subscription tier
- Tenant usage tracking and limits

## RBAC System

**11 Roles** with **136 Permissions** across 14 categories:
- 🔴 Super Admin / Admin (system-level)
- 🔵 Club Admin (club-scoped)
- 🟢 Trainer / Assistant Coach (team-scoped)
- 🟡 Team Manager / Scorer / Referee (specialized)
- 🟠 Player / 🟣 Parent / ⚪ Guest (limited access)

See `BERECHTIGUNGS_MATRIX.md` and `ROLLEN_DOKUMENTATION_README.md` for detailed permission matrices and role hierarchies.

## Development Commands

### Quick Start
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Development
```bash
composer dev          # ⭐ Recommended: Starts server + queue + logs (pail) + vite concurrently
                      #    Uses npx concurrently with color-coded output
php artisan serve     # Laravel server only (port 8000)
php artisan queue:listen --tries=1  # Queue worker with single retry
php artisan pail --timeout=0        # Real-time log viewer
php artisan schedule:work           # Run scheduler in foreground
npm run dev           # Vite dev server only
```

### Testing
```bash
composer test                    # Runs tests with config:clear
php artisan test
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test --coverage
```

Use `BasketballTestCase` for tests requiring basketball-specific setup. Test users are documented in `TEST_USERS.md`.

### Code Quality
```bash
./vendor/bin/pint              # Laravel Pint code formatting
./vendor/bin/phpstan analyse   # Static analysis (if configured)
```

### Database
```bash
php artisan migrate:fresh --seed
php artisan tinker
```

### Custom Artisan Commands
```bash
# Multi-tenancy
php artisan tenant:setup-rls         # Configure Row Level Security
php artisan tenant:usage:reset       # Reset tenant usage metrics

# API Documentation
php artisan generate:openapi-docs    # Generate OpenAPI 3.0 docs

# Performance
php artisan cache:management         # Cache management operations
php artisan db:analyze-performance   # Database performance analysis

# Emergency System
php artisan emergency:health-check   # Test emergency contact system

# Subscriptions
php artisan manage:webhooks          # Manage Stripe webhook endpoints
```

## Service-Oriented Architecture

Core services in `app/Services/` with domain-based subdirectory organization:

**Domain Services:**
- `ClubService`, `TeamService`, `PlayerService` - Core basketball entities
- `LiveScoringService` - Real-time game scoring with broadcasting
- `StatisticsService` - Basketball statistics calculation (FG%, 3P%, etc.)
- `TrainingService` - Training session and drill management
- `TournamentService`, `TournamentProgressionService`, `TournamentAnalyticsService` - Tournament management
- `BracketGeneratorService` - Tournament bracket generation

**Infrastructure Services:**
- `TenantService` - Multi-tenant scope management
- `FeatureGateService` - Subscription-based feature control
- `GDPRComplianceService` - GDPR Article 15/17/20/30 compliance
- `EmergencyAccessService` - QR-code based emergency contacts
- `SecurityMonitoringService` - Security event tracking
- `TwoFactorAuthService` - 2FA implementation

**Integration Services (Subdirectories):**
- `Stripe/` - Stripe payment integration (7 services):
  - `StripeCheckoutService`, `CheckoutService` - Payment processing
  - `StripeSubscriptionService` - Subscription management
  - `StripePaymentService` - Payment handling
  - `PaymentMethodService` - Payment method management
  - `CashierTenantManager` - Multi-tenant Cashier integration
  - `StripeClientManager` - Stripe client configuration
  - `WebhookEventProcessor` - Webhook event handling
- `Federation/` - Basketball federation APIs:
  - `DBBApiService` - DBB (German Basketball Federation) API
  - `FIBAApiService` - FIBA API integration
- `PWAService` - Progressive Web App features
- `PushNotificationService` - WebPush notifications
- `QRCodeService` - QR code generation

**Performance Services:**
- `BasketballCacheService` - Basketball-specific caching strategies
- `DatabasePerformanceMonitor` - Query performance tracking
- `ApiResponseOptimizationService` - API response optimization
- `EnterpriseRateLimitService` - Tenant-aware rate limiting
- `QueryOptimizationService` - Database query optimization
- `MemoryOptimizationService` - Memory usage optimization

**Specialized Services:**
- `AIVideoAnalysisService` - AI-powered video analysis
- `VideoProcessingService` - Video processing and storage
- `BookingService`, `GymScheduleService` - Facility scheduling
- `ICalImportService` - Calendar import functionality
- `LocalizationService` - Multi-language support
- `UserService` - User management operations

## Route Organization

Routes are modular and feature-based for better maintainability:

**Core Routes:**
- `routes/web.php` - Main web routes with locale prefixes (`/{locale}/...`)
- `routes/api.php` - Main API v2 endpoints
- `routes/console.php` - Artisan console commands

**Feature-Specific API Routes:**
- `routes/api_training.php` - Training and drill management API
- `routes/api_tournament.php` - Tournament management API
- `routes/api_game_registrations.php` - Game registration API

**Integration Routes:**
- `routes/subscription.php` - Stripe subscription management
- `routes/checkout.php` - Stripe checkout flows
- `routes/webhooks.php` - Stripe webhook handlers
- `routes/federation.php` - DBB/FIBA federation API routes

**Specialized Routes:**
- `routes/emergency.php` - QR-code emergency access (no auth required)
- `routes/gdpr.php` - GDPR compliance endpoints (data export/deletion)
- `routes/pwa.php` - PWA manifest and service worker
- `routes/security.php` - Security and 2FA endpoints
- `routes/notifications.php` - Push notification endpoints

**Real-time:**
- `routes/channels.php` - Broadcasting channel authorization

## Real-time Broadcasting

WebSocket channels for live updates:
- `game.{gameId}` - Live game scoring events
- `team.{teamId}` - Team notifications
- `club.{clubId}` - Club-wide announcements
- `training.{trainingId}` - Training session updates

Configure `.env`:
```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-key
PUSHER_APP_SECRET=your-secret
```

## Key Models

**Core Domain Models:**
- `User` - Multi-role users with Spatie Permission
- `Club` - Multi-tenant clubs with settings and subscriptions
- `BasketballTeam` - Teams with seasonal rosters
- `Player` - Players with detailed profiles and statistics
- `Game` - Games with live scoring support
- `GameAction` - Individual game events (shots, fouls, etc.)
- `TrainingSession`, `Drill` - Training management
- `Tournament` - Tournament brackets and standings

**Specialized Models:**
- `EmergencyContact` - QR-code enabled emergency contacts
- `GdprDataSubjectRequest` - GDPR Article 15/17 requests
- `ApiUsageTracking` - Tenant API usage limits
- `GymBooking` - Facility scheduling
- `DBBIntegration`, `FIBAIntegration` - Federation data

All critical models use soft deletes and have comprehensive relationships defined.

## Basketball-Specific Features

**Statistics Calculation:**
- Automatic calculation of FG%, 3P%, FT%, PER, TS%
- Advanced metrics: AST/TO ratio, defensive rating
- Shot charts and heat maps (data layer implemented)

**Game Scoring:**
- Real-time score updates via WebSockets
- Game action tracking (shots, rebounds, assists, fouls)
- Quarter/period management (4x10 min, 2x20 min)
- Jersey number and position validation

**Position Validation:**
Point Guard (PG), Shooting Guard (SG), Small Forward (SF), Power Forward (PF), Center (C)

## Stripe Integration

Multi-tenant subscription system:
```php
// Feature gates based on subscription
if (tenant()->hasFeature('live_scoring')) {
    // Feature available
}

// Subscription tiers: free, basic, professional, enterprise
```

Webhook events handled in `StripeWebhookController`:
- `checkout.session.completed`
- `customer.subscription.updated`
- `invoice.payment_succeeded`

## Emergency System

QR-code based emergency contact access:
```
https://basketmanager.pro/emergency/{qr_code}
```

Works offline via PWA service worker. No authentication required for emergency access (logged as `EmergencyIncident`).

## GDPR Compliance

Automated compliance with GDPR Articles:
- **Article 15**: Data export via `GDPRController@exportData`
- **Article 17**: Right to deletion via data subject requests
- **Article 20**: Machine-readable export (JSON)
- **Article 30**: Processing activity records

All personal data access logged via `spatie/laravel-activitylog`.

## Testing Strategy

**Test Infrastructure:**
- Feature tests for all API endpoints and web routes
- Unit tests for services and statistics calculations
- In-memory SQLite database for fast test execution (configured in `phpunit.xml`)
- Test environment automatically disables Telescope, Pulse, Nightwatch

**BasketballTestCase Base Class:**

Extend `Tests\BasketballTestCase` instead of `TestCase` for basketball-specific tests. It provides:

*Pre-configured Test Data:*
- `$adminUser`, `$clubAdminUser`, `$trainerUser`, `$playerUser` - Users with different roles
- `$testClub` - Sample basketball club
- `$testTeam` - Sample team with coach assigned
- `$testPlayer` - Sample player on the team

*Helper Methods:*
```php
// Authentication helpers
$this->actingAsAdmin()      // Authenticate as admin
$this->actingAsClubAdmin()  // Authenticate as club admin
$this->actingAsTrainer()    // Authenticate as trainer
$this->actingAsPlayer()     // Authenticate as player

// Data creation helpers
$this->createTestGame($homeTeam, $awayTeam)  // Create a test game
$this->createTeamRoster($team, $count)       // Create roster of N players
$this->createSampleStatistics($player)       // Generate sample stats

// Assertion helpers
$this->assertUserHasBasketballRole($user, 'trainer')
$this->assertUserCanAccess($user, 'manage-games')
$this->assertStatisticsCorrect($stats, $expected)
```

**Running Tests:**
```bash
composer test                    # Runs config:clear + phpunit
php artisan test --filter=GameTest  # Run single test file
```

Test users available for all 11 roles (see `TEST_USERS.md`).

## Localization

Primary language: German (de)

Uses `mcamara/laravel-localization` with locale URL prefixes:
```
/{locale}/dashboard
/{locale}/teams/{team}
```

Translations in `resources/lang/`.

## Production Deployment

See `PRODUCTION_DEPLOYMENT.md` for detailed deployment checklist including:
- Database migrations and seeders
- Queue worker setup
- Broadcasting configuration
- Stripe webhook configuration
- SSL/TLS requirements
- Cron job setup for scheduler

Common deployment fixes documented in:
- `HTTP_500_FIX_DEPLOYMENT.md`
- `LOCALE_ROUTES_FIX.md`
- `DEPLOYMENT_FIX.md`

## Key Files

- `BERECHTIGUNGS_MATRIX.md` - Complete permission matrix
- `ROLLEN_DOKUMENTATION_README.md` - Role documentation index
- `FEATURES.md` - Comprehensive feature list
- `TEST_USERS.md` - Test account credentials
- `PRODUCTION_READINESS.md` - Production readiness checklist
