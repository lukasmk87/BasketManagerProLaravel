# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
In German/Auf Deutsch
## Project Overview

BasketManager Pro is a production-ready Laravel-based basketball club management application with comprehensive features for multi-tenant club operations, live game scoring, player statistics, training management, and GDPR-compliant emergency contact systems.

## Architecture

### Backend Stack
- **Framework**: Laravel 12.x (^12.0)
- **PHP Version**: 8.2+
- **Database**: MySQL 8.0+ / PostgreSQL 14+ with Row Level Security
- **Cache/Queue**: Redis 7.0+
- **Real-time**: Laravel Broadcasting + Pusher WebSockets
- **Authentication**: Laravel Sanctum 4.0 + Jetstream 5.3
- **Payments**: Stripe PHP SDK 16.2+ with Laravel Cashier 15.7+
- **Permissions**: Spatie Laravel Permission 6.21+
- **PDF Generation**: DomPDF 3.1+ (barryvdh/laravel-dompdf)
- **Excel Export**: Maatwebsite Excel 3.1+

### Frontend Stack
- **JavaScript Framework**: Vue.js 3.3.13
- **Build Tool**: Vite 7.0.4
- **CSS Framework**: Tailwind CSS 3.4
- **SPA Framework**: Inertia.js 2.0
- **Data Visualization**: Chart.js 4.5.0
- **Rich Text Editor**: TipTap 3.10.7 (ProseMirror-based)
- **Vue Utilities**: @vueuse/core 14.0.0
- **Date Handling**: date-fns 4.1.0
- **Drag & Drop**: sortablejs 1.15.6 + vue-draggable-plus 0.6.0
- **PWA**: Full Progressive Web App implementation with service worker

### Multi-Tenant Architecture

The application uses tenant-based data isolation with a two-level architecture (Tenant + Club):
- Row Level Security (RLS) enforced at database level
- Club-scoped queries via `TenantService`
- Feature gates controlled by subscription tier (hierarchical: Tenant → Club)
- Tenant and club usage tracking with limits enforcement
- Dynamic tenant resolution via domain/subdomain/slug

### Dynamic Application Branding

The application supports dynamic app naming with tenant-aware configuration:

**Helper Function**: `app_name()`
- Location: `app/Helpers/AppHelper.php`
- Automatically loaded via Composer autoload
- Fallback hierarchy:
  1. Tenant-specific `app_name` (from `tenants.app_name` column)
  2. `APP_NAME` from `.env` configuration
  3. `'BasketManager Pro'` as default fallback

**Usage Examples:**
```php
// In Blade templates
{{ app_name() }}

// In PHP/Controllers
$appName = app_name();

// In Vue.js components (via Inertia shared props)
page.props.appName
```

**Configuration:**
- Set globally in `.env`: `APP_NAME="Your Custom Name"`
- Set per-tenant in database: `tenants.app_name` column (nullable)
- Used in 50+ locations: landing pages, emails, invoices, error pages, Vue components

**Benefits:**
- White-label support for enterprise clients
- Easy rebranding without code changes
- Tenant-specific customization
- Consistent branding across all touchpoints

## RBAC System

**11 Roles** with **136 Permissions** across 14 categories:
- 🔴 Super Admin / Admin (system-level)
- 🔵 Club Admin (club-scoped)
- 🟢 Trainer / Assistant Coach (team-scoped)
- 🟡 Team Manager / Scorer / Referee (specialized)
- 🟠 Player / 🟣 Parent / ⚪ Guest (limited access)

**Super Admin Architecture:**
- **Tenant-Independent**: Super Admins have `tenant_id = null` and can access ALL tenants
- **Club-Independent**: Super Admins are NOT automatically linked to clubs during installation
- **Middleware Bypass**: Super Admins bypass `TenantScope` and `ResolveTenantMiddleware`
- **Created During Installation**: The Installation Wizard creates the first Super Admin without tenant/club associations
- **Manual Club Access**: Super Admins can manually join clubs later if needed

See `BERECHTIGUNGS_MATRIX.md`, `ROLLEN_DOKUMENTATION_README.md`, and `TENANT_FREE_SUPER_ADMIN_ARCHITECTURE.md` for detailed permission matrices and role hierarchies.

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
# Installation & Setup
php artisan installation:unlock              # Unlock installation if stuck or interrupted

# Multi-tenancy
php artisan tenant:setup-rls                 # Configure Row Level Security
php artisan tenant:usage:reset               # Reset tenant usage metrics
php artisan tenant:repair-limits             # Repair tenant subscription limits and features
php artisan tenant:initialize                # Initialize first tenant for new installation
php artisan tenant:analyze-usage             # Analyze and report tenant usage patterns

# Subscription Management & Migration
php artisan subscriptions:migrate-clubs      # Migrate existing clubs to subscription plans
php artisan subscriptions:validate           # Validate subscription data integrity
php artisan db:seed --class=ClubSubscriptionPlanSeeder --sync-stripe  # Sync plans with Stripe

# Subscription Analytics
php artisan subscription:update-mrr          # Calculate MRR snapshots (daily/monthly)
php artisan subscription:calculate-churn     # Calculate churn rates
php artisan subscription:update-cohorts      # Update cohort analytics
php artisan subscription:analytics-report    # Generate analytics reports

# Subscription Health Monitoring
php artisan subscriptions:health-check       # Perform subscription system health check
php artisan subscriptions:health-check --alert  # Health check with email alerts

# Club Management
php artisan club:retry-failed-transfers      # Retry failed club transfers between tenants
php artisan club:cleanup-rollback-data       # Cleanup old transfer rollback data

# Player Management
php artisan player:regenerate-qr-codes       # Regenerate player invitation QR codes

# Rate Limiting & API
php artisan rate-limit:cleanup-usage         # Cleanup old API usage tracking data
php artisan rate-limit:expire-exceptions     # Expire rate limit exceptions
php artisan rate-limit:monitor               # Monitor rate limits and send alerts
php artisan rate-limit:clear                 # Clear rate limits for tenant/user

# Diagnostics & Debugging
php artisan diagnose:user-permissions        # Diagnose user permissions and role issues

# API Documentation
php artisan generate:openapi-docs            # Generate OpenAPI 3.0 documentation

# Performance
php artisan cache:management                 # Cache management operations
php artisan db:analyze-performance           # Database performance analysis
php artisan test:api-optimization            # Test API optimization strategies

# Emergency System
php artisan emergency:health-check           # Test emergency contact system

# Webhooks & Payments
php artisan manage:webhooks                  # Manage Stripe webhook endpoints

# Machine Learning
php artisan train:ml-models                  # Train machine learning models

# Data Migration
php artisan migrate:clubs-to-tenants         # Migrate clubs to multi-tenant architecture
php artisan migrate:gym-hall-hours           # Migrate gym hall operating hours
php artisan fix:gym-hall-hours               # Fix gym hall operating hours data
```

**Scheduled Tasks** (configured in `routes/console.php`):
- Daily at 00:00 - MRR snapshot calculation
- Monthly on 1st at 01:00 - Monthly MRR calculation
- Monthly on 1st at 02:00 - Churn rate calculation
- Monthly on 1st at 03:00 - Cohort analytics update
- Weekly - Push subscription cleanup

## Service-Oriented Architecture

**75 Services** in `app/Services/` with domain-based subdirectory organization:

**Domain Services:**
- `ClubService`, `TeamService`, `PlayerService` - Core basketball entities
- `SeasonService` - Season lifecycle management (creation, activation, completion)
- `LiveScoringService` - Real-time game scoring with broadcasting
- `StatisticsService` - Basketball statistics calculation (FG%, 3P%, etc.)
- `TrainingService` - Training session and drill management
- `TournamentService`, `TournamentProgressionService`, `TournamentAnalyticsService` - Tournament management
- `BracketGeneratorService` - Tournament bracket generation
- `ClubInvitationService` - Club invitation system with QR codes
- `PlayerRegistrationService` - Player registration with QR-code invitations
- `ClubTransferService` - Club transfer between tenants with rollback functionality

**Infrastructure Services:**
- `TenantService` - Multi-tenant scope management
- `TenantLimitsService` - Subscription tier limits management (centralized limits for all 4 tiers)
- `ClubUsageTrackingService` - Club usage tracking (teams, players, storage)
- `LimitEnforcementService` - Limit enforcement for tenants/clubs
- `FeatureGateService` - Subscription-based feature control
- `FeatureFlagService` - Feature flag management with rollout strategies (percentage, whitelist, gradual)
- `SubscriptionHealthMonitorService` - Subscription system health monitoring (6 metrics, alerts, health scores)
- `GDPRComplianceService` - GDPR Article 15/17/20/30 compliance
- `EmergencyAccessService` - QR-code based emergency contacts
- `SecurityMonitoringService` - Security event tracking
- `TwoFactorAuthService` - 2FA implementation

**Integration Services (Subdirectories):**
- `Stripe/` - Stripe payment integration (14 services):
  - `CheckoutService` - General payment processing
  - `ClubSubscriptionCheckoutService` - Club-specific checkout sessions
  - `ClubSubscriptionService` - Club plan management (assign, cancel, swap, sync)
  - `ClubStripeCustomerService` - Club Stripe customer management
  - `ClubInvoiceService` - Invoice management and PDF download
  - `ClubPaymentMethodService` - Payment methods (Card, SEPA, Sofort, Giropay, EPS, iDEAL)
  - `SubscriptionAnalyticsService` - MRR/ARR tracking, churn analysis, cohort analytics
  - `StripeSubscriptionService` - Subscription management
  - `StripePaymentService` - Payment handling
  - `PaymentMethodService` - Payment method handling
  - `CashierTenantManager` - Multi-tenant Cashier integration
  - `StripeClientManager` - Stripe client configuration
  - `WebhookEventProcessor` - Webhook event handling (16+ events)
  - `ClubSubscriptionNotificationService` - Subscription notifications
- `Federation/` - Basketball federation APIs:
  - `DBBApiService` - DBB (German Basketball Federation) API
  - `FIBAApiService` - FIBA API integration
- `Install/` - Installation wizard services (5 services):
  - `InstallationService` - Installation wizard orchestration
  - `EnvironmentManager` - .env file management and configuration
  - `RequirementChecker` - Server requirements validation (PHP, extensions, permissions)
  - `PermissionChecker` - File and directory permissions validation
  - `StripeValidator` - Stripe API key validation
- `OpenApi/` - OpenAPI documentation & SDK generation (5 services):
  - `OpenApiDocumentationService` - OpenAPI 3.0 specification generation
  - `javascriptSDKGenerator` - JavaScript SDK generator
  - `phpSDKGenerator` - PHP SDK generator
  - `pythonSDKGenerator` - Python SDK generator
  - `SDKGeneratorInterface` - SDK generator interface
- `ML/` - Machine Learning services (4 services):
  - `MLTrainingService` - ML model training orchestration
  - `MLPredictionService` - ML prediction service
  - `InjuryRiskPredictionService` - Injury risk prediction based on workload
  - `PlayerPerformancePredictionService` - Player performance forecasting
- `Api/` - API infrastructure (1 service):
  - `RouteVersionResolver` - API versioning logic and resolution
- `PWAService` - Progressive Web App features
- `PushNotificationService` - WebPush notifications
- `QRCodeService` - QR code generation
- `LandingPageService` - Landing page CMS and content management

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
- `UserImportService` - Bulk user import from CSV/Excel
- `RedisAvailabilityService` - Redis availability checking and fallback handling

## Route Organization

Routes are modular and feature-based for better maintainability (**29 route files**):

**Core Routes:**
- `routes/web.php` - Main web routes with locale prefixes (`/{locale}/...`)
- `routes/api.php` - Base API routes and authentication endpoints
- `routes/console.php` - Artisan console commands and scheduled tasks
- `routes/channels.php` - Broadcasting channel authorization

**API Versioning:**
- `routes/api/v1.php` - Legacy API v1 (deprecated, backward compatibility)
- `routes/api/v2.php` - **Primary API v2** (main production API, 16KB)
- `routes/api/v4.php` - Latest API v4 (17KB, newer endpoints)
- `routes/api/v4_teams_only.php` - API v4 teams-only specialized endpoints

**Feature-Specific Routes:**
- `routes/api_training.php` - Training and drill management API
- `routes/api_tournament.php` - Tournament management API
- `routes/api_game_registrations.php` - Game registration & roster management API
- `routes/player_registration.php` - Player registration flows with QR codes
- `routes/club_invitation.php` - Club invitation system with QR codes
- `routes/admin.php` - System admin panel routes (Super Admin only)
- `routes/club_admin.php` - Club admin panel routes (members, teams, financials, reports)
- `routes/season.php` - Season management API (create, activate, complete seasons)

**Integration Routes:**
- `routes/checkout.php` - Tenant-level Stripe checkout and subscription management
- `routes/club_checkout.php` - Club-level Stripe subscription checkout (17 endpoints)
- `routes/webhooks.php` - Stripe webhook handlers (11+ webhook events)
- `routes/federation.php` - DBB/FIBA federation API routes

**Specialized Routes:**
- `routes/emergency.php` - QR-code emergency access (no auth required)
- `routes/gdpr.php` - GDPR compliance endpoints (data export/deletion)
- `routes/pwa.php` - PWA manifest and service worker (20KB)
- `routes/security.php` - Security and 2FA endpoints
- `routes/notifications.php` - Push notification endpoints (12KB)
- `routes/health.php` - Subscription health check API endpoints (11 endpoints)
- `routes/install.php` - Installation wizard (7-step process, server validation)

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

## Middleware Architecture

**20 Custom Middleware** for request/response pipeline:

**Multi-Tenancy:**
- `ResolveTenantMiddleware` - Domain/subdomain/slug-based tenant resolution
- `ConfigureTenantStripe` - Dynamic Stripe configuration per tenant

**Security & Access Control:**
- `EnforceFeatureGates` - Subscription tier-based feature access
- `CheckFeatureFlag` - Feature flag-based route protection with rollout strategies
- `EnforceClubLimits` - Usage limit enforcement (users, teams, storage)
- `EnterpriseRateLimitMiddleware` - Advanced tenant-aware rate limiting
- `TenantRateLimitMiddleware` - Tenant-based rate limiting
- `SecurityHeadersMiddleware` - Security headers (CSP, HSTS, X-Frame-Options)
- `EnhancedCsrfProtection` - Enhanced CSRF protection
- `AdminMiddleware` - Admin-only access control (Super Admin/Admin/Subscription Manager)

**Installation:**
- `InstallationSessionMiddleware` - File-based session management during installation
- `PreventInstalledAccess` - Prevent access to installation wizard after completion
- `RedirectIfNotInstalled` - Redirect to installation wizard if not installed

**Performance:**
- `DatabasePerformanceMiddleware` - Query performance monitoring
- `ApiResponseCompressionMiddleware` - Response compression

**API:**
- `ApiVersioningMiddleware` - API version handling (v1, v2, v4)
- `ForceJsonResponse` - Force JSON responses for API routes

**Localization:**
- `LocalizationMiddleware` - Language resolution and URL prefix handling

## Key Models

**78 Models** across **132 migrations** with comprehensive relationships:

**Core Domain Models:**
- `User` - Multi-role users with Spatie Permission
- `Club` - Multi-tenant clubs with settings and subscriptions
- `BasketballTeam` - Teams with seasonal rosters
- `Player` - Players with detailed profiles and statistics
- `Season` - Season lifecycle management with activation and completion
- `SeasonStatistic` - Season-specific statistics aggregation
- `Game` - Games with live scoring support
- `GameAction` - Individual game events (shots, fouls, etc.)
- `GameRegistration` - Player registration for games with availability tracking
- `TrainingSession`, `Drill` - Training management
- `Tournament` - Tournament brackets and standings

**Subscription & Analytics Models:**
- `Subscription` - Tenant/Club subscriptions with Cashier integration
- `SubscriptionPlan`, `ClubSubscriptionPlan` - Plan definitions
- `SubscriptionMRRSnapshot` - Daily MRR tracking for revenue analytics
- `ClubSubscriptionEvent` - Subscription lifecycle events (created, upgraded, cancelled)
- `ClubSubscriptionCohort` - Cohort analytics for retention tracking
- `ClubUsage` - Club usage metrics and limits
- `TenantPlanCustomization` - Tenant-specific plan customizations

**Club Management Models:**
- `ClubInvitation` - Club invitations with QR codes, expiry dates, and usage limits
- `ClubTransfer` - Club transfers between tenants with status tracking
- `ClubTransferLog` - Detailed transfer audit log
- `ClubTransferRollbackData` - Rollback data for failed transfers

**Player & Registration Models:**
- `PlayerRegistrationInvitation` - Player registration invitations with QR codes

**CMS & Content Models:**
- `LandingPageContent` - Landing page CMS for dynamic content
- `LegalPage` - Legal pages (Impressum, Datenschutz, AGB, Cookie Policy)

**Notification Models:**
- `NotificationLog` - Notification audit log with delivery tracking
- `NotificationPreference` - User-specific notification preferences

**Authentication Models:**
- `SocialAccount` - OAuth social login integration (Google, Facebook, etc.)

**Infrastructure Models:**
- `WebhookEvent` - Webhook event log for all incoming webhooks

**Specialized Models:**
- `EmergencyContact` - QR-code enabled emergency contacts
- `GdprDataSubjectRequest` - GDPR Article 15/17 requests
- `ApiUsageTracking` - Tenant API usage limits and rate limiting
- `GymBooking` - Facility scheduling and booking management
- `DBBIntegration`, `FIBAIntegration` - Federation data integration
- `VideoFile`, `VideoAnalysisSession` - Video analysis and annotation
- `MLModel`, `MLPrediction` - Machine learning models and predictions

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

## Machine Learning & Video Analysis

**AI-Powered Features:**
- `AIVideoAnalysisService` - AI-powered video analysis for player performance
- `VideoProcessingService` - Video processing, storage, and frame extraction
- Player tracking algorithms for movement analysis
- Automatic shot chart generation from video
- Frame-level annotations and highlight reel generation

**ML Infrastructure:**
- `MLModel` - Model registry with versioning
- `MLPrediction` - Prediction storage and tracking
- `MLExperiment` - Experiment tracking for model development
- `MLTrainingData` - Training dataset management
- `MLFeatureStore` - Feature storage for ML pipelines

**Predictive Analytics:**
- Injury risk prediction based on player workload
- Player performance forecasting
- Automated insights from game statistics

## Major Features & Systems

### 1. Installation Wizard

Production-ready 7-step installation wizard for zero-configuration deployment:

**Features:**
- Language selection (German/English)
- Server requirements validation (PHP version, extensions, file permissions)
- Database connection testing and automatic migration
- Stripe API key validation (test/live mode detection)
- Super Admin account creation (tenant-free architecture)
- Environment file generation and configuration
- Installation lock to prevent re-runs

**Services:** 5 installation services in `app/Services/Install/`
- `InstallationService` - Orchestrates entire wizard flow
- `EnvironmentManager` - .env file management
- `RequirementChecker` - PHP/extension validation
- `PermissionChecker` - File permissions validation
- `StripeValidator` - Stripe API validation

**Routes:** Dedicated `routes/install.php` with 7-step flow
**Middleware:** 3 installation-specific middleware (session, lock, redirect)
**Commands:** `php artisan installation:unlock` - Unlock if stuck

### 2. Club Transfer System

Enterprise-grade club migration between tenants with full rollback capability:

**Features:**
- Transfer clubs between tenants with all related data
- Preview transfer impact and data analysis before execution
- Automated migration of users, teams, players, games, training, bookings
- Stripe subscription transfer with customer migration
- Queue-based processing for large datasets (handles 1000+ records)
- Comprehensive rollback functionality for failed transfers
- Event-driven architecture (ClubTransferInitiated, Completed, Failed, RolledBack)
- Detailed audit logging with transfer metadata

**Models:** 4 club transfer models
- `ClubTransfer` - Transfer tracking with status (pending, processing, completed, failed, rolled_back)
- `ClubTransferLog` - Detailed audit log
- `ClubTransferRollbackData` - Rollback data for recovery
- `ClubTransferEvent` - Event tracking

**Services:** `ClubTransferService` with 12 transfer methods
**Mail:** 2 notification emails (success/failure)
**Commands:**
- `php artisan club:retry-failed-transfers` - Retry failed transfers
- `php artisan club:cleanup-rollback-data` - Cleanup old rollback data

### 3. Player Registration System

QR-code based self-service player registration with team assignment:

**Features:**
- Generate QR code invitations for player registration
- Self-service registration via QR codes (mobile-optimized)
- Team assignment during registration
- Invitation expiry dates and max-uses limits
- Registration approval workflow for coaches
- Automatic notification to club admins
- Bulk QR code generation for teams

**Models:**
- `PlayerRegistrationInvitation` - QR-code invitations with tracking
- `GameRegistration` - Player registration for specific games

**Services:** `PlayerRegistrationService` with invitation management
**Routes:** Dedicated `routes/player_registration.php`
**Commands:** `php artisan player:regenerate-qr-codes` - Regenerate QR codes

### 4. Club Invitation System

Invite users to clubs via QR codes with role assignment:

**Features:**
- Generate QR code invitations for club membership
- Role-based invitations (trainer, player, team manager, etc.)
- Expiry dates and usage limits (single-use or multi-use)
- Public invitation links (shareable via email/SMS)
- Invitation tracking and analytics
- Automatic role assignment on acceptance
- Revocable invitations

**Models:** `ClubInvitation` - QR-code invitations with metadata
**Services:** `ClubInvitationService` with QR code generation
**Routes:** Dedicated `routes/club_invitation.php`

### 5. Season Management System

Complete season lifecycle management with statistics tracking:

**Features:**
- Create multiple seasons per club (e.g., 2024/2025, 2025/2026)
- Season activation (only one active season per club)
- Season completion and archiving
- Season-specific statistics aggregation
- Player/team performance tracking per season
- Season-based roster management
- Season comparison and analytics

**Models:**
- `Season` - Season metadata (name, start/end dates, status)
- `SeasonStatistic` - Aggregated season statistics

**Services:** `SeasonService` with lifecycle management
**Routes:** Dedicated `routes/season.php`
**Documentation:** `SEASON_MANAGEMENT.md` - Complete season management guide

### 6. Game Registration & Roster Management

Player availability tracking and roster finalization for games:

**Features:**
- Players register availability for upcoming games
- Three-state availability (available, unavailable, maybe)
- Registration deadlines enforced by system
- Coach roster finalization before game
- Automatic reminders for unregistered players
- Availability statistics and reports
- Integration with game scoring system

**Models:** `GameRegistration` - Player game availability
**Routes:** Dedicated `routes/api_game_registrations.php`
**Integration:** Links to live scoring system

### 7. Landing Page CMS

Dynamic content management for marketing and legal pages:

**Features:**
- Visual landing page builder (hero, features, pricing, testimonials)
- Legal pages management (Impressum, Datenschutz, AGB, Cookie Policy)
- Multi-language support (German/English)
- SEO optimization (meta tags, descriptions)
- Version history and draft system
- Mobile-responsive previews
- Public/private page visibility

**Models:**
- `LandingPageContent` - Dynamic landing page sections
- `LegalPage` - Legal documents with versioning

**Services:** `LandingPageService` - Content management
**Admin Interface:** Built-in CMS editor

### 8. Club Admin Panel

Dedicated administration interface for club administrators:

**Features:**
- Dashboard with club statistics and analytics
- Member management (invite, remove, role assignment)
- Team and player management
- Financial management (subscription, invoices, payment methods)
- Reports and analytics (games, training, statistics)
- Club settings and customization
- Activity logs and audit trail

**Routes:** Dedicated `routes/club_admin.php`
**Controller:** `ClubAdminPanelController` with 15+ admin methods
**Permissions:** Club Admin role required

### 9. OpenAPI Documentation & SDK Generation

Automated API documentation and multi-language SDK generation:

**Features:**
- OpenAPI 3.0 specification generation from routes
- Automatic route scanning and documentation
- Multi-language SDK generation (JavaScript, PHP, Python)
- API endpoint documentation with examples
- Authentication documentation (Sanctum)
- Request/response schema generation
- Interactive API explorer integration

**Services:** 5 OpenAPI services in `app/Services/OpenApi/`
- `OpenApiDocumentationService` - Spec generation
- `javascriptSDKGenerator` - JS/TS SDK
- `phpSDKGenerator` - PHP SDK (PSR-7)
- `pythonSDKGenerator` - Python SDK
- `SDKGeneratorInterface` - Common interface

**Commands:** `php artisan generate:openapi-docs` - Generate docs
**Output:** `public/docs/openapi.json` - OpenAPI 3.0 specification

### 10. Enhanced Machine Learning Services

Expanded AI-powered basketball analytics:

**Features:**
- ML model training orchestration
- Injury risk prediction based on workload and patterns
- Player performance forecasting (next game predictions)
- Shot prediction and analysis (shooting patterns)
- Automated insights from game statistics
- Model versioning and experiment tracking
- Integration with Python ML scripts
- Real-time predictions via API

**Services:** 4 ML services in `app/Services/ML/`
- `MLTrainingService` - Model training orchestration
- `MLPredictionService` - Real-time predictions
- `InjuryRiskPredictionService` - Injury risk analysis
- `PlayerPerformancePredictionService` - Performance forecasting

**Models:**
- `MLModel` - Model registry with versioning
- `MLPrediction` - Prediction storage and tracking
- `MLExperiment` - Experiment tracking

**Commands:** `php artisan train:ml-models` - Train models

## Stripe Integration

Multi-tenant subscription system with **two-level architecture**: Tenant-Level and Club-Level subscriptions.

### Tenant-Level Subscriptions

**Subscription Tiers:**
- **Free**: 10 users, 5 teams, 5GB storage
- **Basic** (€29/mo): 50 users, 20 teams, 50GB storage
- **Professional** (€99/mo): 200 users, 50 teams, 200GB storage
- **Enterprise** (Custom): Unlimited resources

### Club-Level Subscriptions (Multi-Club System)

Each club can have its own independent Stripe subscription:

**Club Subscription Plans:**
- **Free Club** (€0): 2 teams, 30 players, basic features
- **Standard Club** (€49/mo, €441/yr): 10 teams, 150 players, live scoring
- **Premium Club** (€149/mo, €1,341/yr): 50 teams, 500 players, advanced stats, video analysis
- **Enterprise Club** (€299/mo, €2,691/yr): 100 teams, 1000 players, all features

**13 Stripe Services:**
- `ClubStripeCustomerService` - Stripe Customer Management
- `ClubSubscriptionCheckoutService` - Checkout Session Creation
- `ClubSubscriptionService` - Subscription Lifecycle (Cancel, Swap, Sync)
- `ClubInvoiceService` - Invoice Management (List, Show, PDF, Upcoming)
- `ClubPaymentMethodService` - Payment Methods (Card, SEPA, Giropay, EPS, iDEAL)
- `SubscriptionAnalyticsService` - MRR/ARR/Churn Analytics (17 methods)
- `ClubSubscriptionNotificationService` - Email Notifications (19 methods)
- `StripeClientManager` - Multi-tenant Stripe client configuration
- `CashierTenantManager` - Cashier integration for tenants
- `CheckoutService`, `StripeSubscriptionService`, `StripePaymentService`, `PaymentMethodService`, `WebhookEventProcessor`

**17 Club Subscription API Endpoints:**
1. `GET /club/{club}/subscription` - Subscription Overview
2. `POST /club/{club}/checkout` - Create Checkout Session
3. `GET /club/{club}/checkout/success` - Success Page
4. `GET /club/{club}/checkout/cancel` - Cancel Page
5. `POST /club/{club}/billing-portal` - Billing Portal Session
6. `GET /club/{club}/billing/invoices` - List Invoices (with pagination & filtering)
7. `GET /club/{club}/billing/invoices/{invoice}` - Show Invoice
8. `GET /club/{club}/billing/invoices/upcoming` - Upcoming Invoice Preview
9. `GET /club/{club}/billing/invoices/{invoice}/pdf` - Download PDF
10. `GET /club/{club}/billing/payment-methods` - List Payment Methods
11. `POST /club/{club}/billing/payment-methods/setup` - Create SetupIntent
12. `POST /club/{club}/billing/payment-methods/attach` - Attach Payment Method
13. `DELETE /club/{club}/billing/payment-methods/{pm}` - Detach Payment Method
14. `PUT /club/{club}/billing/payment-methods/{pm}` - Update Billing Details
15. `POST /club/{club}/billing/payment-methods/{pm}/default` - Set Default
16. `POST /club/{club}/billing/preview-plan-swap` - Proration Preview
17. `POST /club/{club}/billing/swap-plan` - Execute Plan Swap

**Payment Methods** (German market focus):
- Credit/Debit Cards (Visa, Mastercard, Amex)
- SEPA Direct Debit
- Sofort
- Giropay
- EPS (Austria)
- Bancontact (Belgium)
- iDEAL (Netherlands)

**Feature Gates:**
```php
// Check subscription features (hierarchical: Tenant → Club)
if ($club->hasFeature('live_scoring')) {
    // Feature available based on club's plan
}

// Check usage limits (effective limit = min(tenant_limit, club_limit))
$club->canUse('teams', 1);
$club->getLimit('max_teams');
```

**Subscription Analytics:**
- MRR/ARR (Monthly/Annual Recurring Revenue) tracking
- Churn rate calculation and analysis (voluntary & involuntary)
- Customer Lifetime Value (LTV) metrics
- Cohort analytics with retention tracking (24 months)
- Automated daily/monthly snapshots via scheduled commands
- Subscription health metrics (trial conversion, payment recovery)

**Webhook Events** (11 club-subscription events handled in `ClubSubscriptionWebhookController`):
- `checkout.session.completed` - Checkout completed, subscription activated
- `customer.subscription.created` - Subscription created
- `customer.subscription.updated` - Plan changes, renewals, status updates
- `customer.subscription.deleted` - Cancellations (churn)
- `invoice.payment_succeeded` - Successful payments
- `invoice.payment_failed` - Failed payments (churn risk)
- `invoice.created` - Invoice created
- `invoice.finalized` - Invoice finalized
- `invoice.payment_action_required` - 3D Secure authentication required
- `payment_method.attached` - Payment method added
- `payment_method.detached` - Payment method removed

**Email Notifications (8 Mail classes):**
- `SubscriptionWelcomeMail` - Welcome email after subscription
- `PaymentSuccessfulMail` - Payment confirmation with invoice
- `PaymentFailedMail` - Payment failure notification with retry info
- `SubscriptionCanceledMail` - Cancellation confirmation
- `HighChurnAlertMail` - Alert for clubs at high churn risk (updated name)
- `SubscriptionAnalyticsReportMail` - Monthly analytics reports to admins
- `ClubTransferCompletedMail` - Club transfer completion notification (NEW)
- `ClubTransferFailedMail` - Club transfer failure notification (NEW)

**📚 Comprehensive Documentation:**
- [Subscription API Reference](/docs/SUBSCRIPTION_API_REFERENCE.md) - Complete API documentation (17 endpoints, 11 webhooks)
- [Integration Guide](/docs/SUBSCRIPTION_INTEGRATION_GUIDE.md) - Developer setup, webhook configuration, service usage
- [Deployment Guide](/docs/SUBSCRIPTION_DEPLOYMENT_GUIDE.md) - Production deployment, Stripe Live keys, queue workers
- [Architecture Guide](/docs/SUBSCRIPTION_ARCHITECTURE.md) - System architecture, data flows, analytics pipeline
- [Admin Guide](/docs/SUBSCRIPTION_ADMIN_GUIDE.md) - User guide for club administrators
- [Testing Guide](/docs/SUBSCRIPTION_TESTING.md) - Comprehensive test suite (40 tests, 4,350+ lines)

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

**Test Infrastructure** (**85 test files**, including 40 subscription tests):
- Feature tests for all API endpoints and web routes
- Unit tests for services and statistics calculations
- Integration tests for Stripe webhook events (23 tests)
- E2E tests for complete checkout flows (17 tests)
- Command tests for Artisan commands
- Mail tests for email notifications
- Model tests for Eloquent models
- Policy tests for authorization rules
- Request validation tests
- In-memory SQLite database for fast test execution (configured in `phpunit.xml`)
- MySQL configuration available for production-parity testing
- Test environment automatically disables Telescope, Pulse, Nightwatch

**Test Directory Structure:**
- `tests/Feature/` - Feature tests for endpoints and web routes
- `tests/Feature/Api/V2/` - API v2 endpoint tests
- `tests/Feature/Commands/` - Artisan command tests
- `tests/Integration/` - Integration tests (webhooks, external APIs)
- `tests/Unit/` - Unit tests for services and utilities
- `tests/Unit/Mail/ClubSubscription/` - Subscription email tests
- `tests/Unit/Models/` - Model unit tests
- `tests/Unit/Policies/` - Authorization policy tests
- `tests/Unit/Requests/` - Request validation tests
- `tests/Unit/Services/Install/` - Installation service tests

**Test Suites (phpunit.xml):**
- `Unit` - Unit tests
- `Feature` - Feature tests
- `Integration` - Integration tests with external services

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
composer test                              # Runs config:clear + phpunit (recommended)
php artisan test                           # Run all tests
php artisan test --filter=GameTest         # Run single test file
php artisan test --filter=testCanCreateGame  # Run single test method
php artisan test --testsuite=Feature      # Run only Feature tests
php artisan test --testsuite=Unit         # Run only Unit tests
php artisan test --coverage               # Run with coverage report
php artisan test --parallel               # Run tests in parallel
```

**Key Test Files:**
- `tests/BasketballTestCase.php` - Base test case with basketball-specific helpers
- `tests/Feature/ClubSubscriptionCheckoutTest.php` - Club subscription checkout tests
- `tests/Feature/ClubSubscriptionLifecycleTest.php` - Full subscription lifecycle tests
- `tests/Unit/ClubSubscriptionCheckoutServiceTest.php` - Checkout service unit tests
- `tests/Unit/ClubSubscriptionServiceTest.php` - Subscription service unit tests

**Subscription Testing:**

Das Multi-Club Subscription-System verfügt über **40 comprehensive tests** (~4,350 Zeilen):
- **23 Integration Tests** - Stripe webhook events (all 11 events covered)
- **17 E2E Tests** - Complete checkout flow with payment scenarios
- **100% Coverage** for critical services

```bash
# Run all subscription tests
php artisan test --filter=ClubSubscription

# Integration tests (Webhooks)
php artisan test tests/Integration/ClubSubscriptionWebhookTest.php

# E2E tests (Checkout)
php artisan test tests/Feature/ClubCheckoutE2ETest.php
```

See `/docs/SUBSCRIPTION_TESTING.md` for comprehensive testing guide.

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

## Documentation Reference

### Core Documentation
- `BERECHTIGUNGS_MATRIX.md` - Complete permission matrix (136 permissions, 11 roles)
- `ROLLEN_DOKUMENTATION_README.md` - Role documentation index
- `TENANT_FREE_SUPER_ADMIN_ARCHITECTURE.md` - Super Admin architecture guide
- `FEATURES.md` - Comprehensive feature list
- `TEST_USERS.md` - Test account credentials (11 roles)
- `PRODUCTION_READINESS.md` - Production readiness checklist

### Feature-Specific Documentation
- `SEASON_MANAGEMENT.md` - Season management system guide
- `CLUB_ADMIN_USER_MANAGEMENT.md` - Club admin user management
- `TENANT_CLUB_ARCHITEKTUR.md` - Tenant/Club architecture details
- `INSTALLATION.md` - Installation guide and troubleshooting

### API & Integration Documentation
- `docs/SUBSCRIPTION_API_REFERENCE.md` - Complete API documentation (17 endpoints, 11 webhooks)
- `docs/SUBSCRIPTION_INTEGRATION_GUIDE.md` - Developer setup and integration
- `docs/SUBSCRIPTION_ARCHITECTURE.md` - System architecture and data flows
- `docs/SUBSCRIPTION_TESTING.md` - Comprehensive test suite guide (40 tests)
- `docs/SUBSCRIPTION_ADMIN_GUIDE.md` - Admin user guide for subscriptions
- `docs/INSTALLATION_WIZARD_ARCHITECTURE.md` - Installation wizard architecture

### Deployment & Operations
- `PRODUCTION_DEPLOYMENT.md` - Production deployment checklist
- `docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md` - Detailed deployment steps
- `docs/SUBSCRIPTION_DEPLOYMENT_GUIDE.md` - Stripe Live keys and production setup
- `HTTP_500_FIX_DEPLOYMENT.md` - HTTP 500 error troubleshooting
- `LOCALE_ROUTES_FIX.md` - Locale routing fixes
- `DEPLOYMENT_FIX.md` - General deployment fixes
- `CSRF_FIX_DEPLOYMENT.md` - CSRF protection troubleshooting
- `docs/ROLLOUT_STRATEGY.md` - Feature rollout strategies

### Installation & Setup
- `INSTALLATION_WIZARD_FIX.md` - Installation wizard fixes and troubleshooting
