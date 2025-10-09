# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BasketManager Pro is a production-ready Laravel-based basketball club management application with comprehensive features for multi-tenant club operations, live game scoring, player statistics, training management, and GDPR-compliant emergency contact systems.

## Architecture

- **Framework**: Laravel 12.x
- **PHP Version**: 8.2+
- **Frontend**: Vue.js 3.x + Inertia.js + Tailwind CSS
- **Database**: MySQL/PostgreSQL with Row Level Security
- **Cache/Queue**: Redis
- **Real-time**: Laravel Broadcasting + Pusher WebSockets
- **Authentication**: Laravel Sanctum + Jetstream
- **Payments**: Stripe (Laravel Cashier) with multi-tenant subscriptions
- **PWA**: Full Progressive Web App implementation

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
composer dev          # Starts server, queue, logs (pail), and vite concurrently
php artisan serve     # Server only
php artisan queue:work
php artisan schedule:work
npm run dev           # Vite dev server
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

Core services in `app/Services/`:

**Domain Services:**
- `ClubService`, `TeamService`, `PlayerService` - Core basketball entities
- `LiveScoringService` - Real-time game scoring with broadcasting
- `StatisticsService` - Basketball statistics calculation (FG%, 3P%, etc.)
- `TrainingService` - Training session and drill management
- `TournamentService` - Tournament brackets and progression

**Infrastructure Services:**
- `TenantService` - Multi-tenant scope management
- `FeatureGateService` - Subscription-based feature control
- `GDPRComplianceService` - GDPR Article 15/17/20/30 compliance
- `EmergencyAccessService` - QR-code based emergency contacts
- `SecurityMonitoringService` - Security event tracking

**Integration Services:**
- `Stripe/StripeCheckoutService` - Payment processing
- `Federation/DBBApiService` - DBB (German Basketball Federation) API
- `Federation/FIBAApiService` - FIBA API integration
- `PWAService` - Progressive Web App features
- `PushNotificationService` - WebPush notifications

**Performance Services:**
- `BasketballCacheService` - Basketball-specific caching strategies
- `DatabasePerformanceMonitor` - Query performance tracking
- `ApiResponseOptimizationService` - API response optimization
- `EnterpriseRateLimitService` - Tenant-aware rate limiting

## Route Organization

Routes are modular and feature-based:
- `routes/web.php` - Main web routes with locale prefixes
- `routes/api.php` - API v2 endpoints
- `routes/api/` - Versioned API routes (v1, v2)
- `routes/emergency.php` - QR-code emergency access
- `routes/gdpr.php` - GDPR compliance endpoints
- `routes/subscription.php` - Stripe subscription management
- `routes/federation.php` - DBB/FIBA API routes
- `routes/pwa.php` - PWA manifest and service worker
- `routes/webhooks.php` - Stripe webhook handlers
- `routes/channels.php` - Broadcasting channels

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

- Feature tests for all API endpoints and web routes
- Unit tests for services and statistics calculations
- `BasketballTestCase` provides basketball-specific test helpers
- Test database configured in `.env.testing`

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
