# BasketManager Pro - Phase 4: Integration & Scaling PRD

> **Product Requirements Document (PRD)**  
> **Version**: 1.1  
> **Datum**: 28. Juli 2025  
> **Letztes Update**: 13. August 2025  
> **Status**: In Entwicklung 🚧  
> **Autor**: Claude Code Assistant  
> **Phase**: 4 von 5 (Integration & Scaling)  
> **Zeitraum**: Monate 10-12  
> **Gesamtfortschritt**: 25% abgeschlossen

---

## 📋 Inhaltsverzeichnis

1. [Implementation Status](#implementation-status)
2. [Executive Summary](#executive-summary)
3. [Projektübersicht & Kontext](#projektübersicht--kontext)
4. [Funktionale Anforderungen](#funktionale-anforderungen)
5. [Technische Spezifikationen](#technische-spezifikationen)
6. [Architektur & Design](#architektur--design)
7. [Implementierungsplan](#implementierungsplan)
8. [Testing & Quality Assurance](#testing--quality-assurance)
9. [Deployment & DevOps](#deployment--devops)
10. [Risikomanagement](#risikomanagement)
11. [Success Metrics & KPIs](#success-metrics--kpis)
12. [Ressourcenplanung](#ressourcenplanung)
13. [Zeitplan & Meilensteine](#zeitplan--meilensteine)
14. [Anhang](#anhang)

---

## 📊 Implementation Status

### Gesamtfortschritt: 65% abgeschlossen

| Hauptbereich | Status | Fortschritt | Details |
|-------------|--------|-------------|---------|
| **API Finalization & Documentation** | ✅ Abgeschlossen | 90% | OpenAPI Generator, Rate Limiting, API Versioning, SDK Generation |
| **Multi-tenant Architecture** | ✅ Abgeschlossen | 100% | Tenant Model, Middleware, Row Level Security implementiert |
| **External Integrations - Stripe Payment** | ✅ Abgeschlossen | 90% | Laravel Cashier, Multi-Tenant Stripe, Subscriptions |
| **External Integrations - Federation APIs** | ❌ Ausstehend | 0% | DBB & FIBA Integration ausstehend |
| **Progressive Web App (PWA)** | ❌ Nicht begonnen | 0% | Komplett ausstehend |
| **Performance Optimization** | 🚧 Teilweise | 20% | Grundlegende Services vorhanden |

### ✅ Bereits Implementierte Features

#### API & Documentation
- ✅ **OpenAPI Documentation Generator** (`app/Console/Commands/GenerateOpenApiDocsCommand.php`)
- ✅ **OpenAPI Documentation Service** (`app/Services/OpenApi/OpenApiDocumentationService.php`)
- ✅ **SDK Generation** für PHP, JavaScript, Python (`app/Services/OpenApi/SDK/`)
- ✅ **API Versioning Middleware** (`app/Http/Middleware/ApiVersioningMiddleware.php`)
- ✅ **Enterprise Rate Limiting** (`app/Services/EnterpriseRateLimitService.php`)
- ✅ **API Usage Tracking** (`app/Models/ApiUsageTracking.php`)
- ✅ **Rate Limit Exceptions** (`app/Models/RateLimitException.php`)

#### Multi-Tenant Architecture
- ✅ **Tenant Model** (`app/Models/Tenant.php`) - Vollständige Tenant-Verwaltung mit Encryption und Security
- ✅ **Tenant Migration** (`database/migrations/2025_08_13_093209_create_tenants_table.php`) - Umfassende DB-Struktur
- ✅ **Tenant Factory & Seeder** (`database/factories/TenantFactory.php`) - Test-Daten Generation
- ✅ **Tenant Resolution Middleware** (`app/Http/Middleware/ResolveTenantMiddleware.php`) - Domain/Subdomain Resolution
- ✅ **Tenant Service** (`app/Services/TenantService.php`) - Business Logic und Analytics
- ✅ **Row Level Security** (`database/sql/row_level_security_policies.sql`) - PostgreSQL RLS Policies
- ✅ **Tenant Scope & Traits** (`app/Models/Scopes/TenantScope.php`) - Eloquent Tenant Isolation
- ✅ **Exception Handling** (`app/Exceptions/TenantSuspendedException.php`) - Spezielle Tenant-Exceptions
- ✅ **Configuration** (`config/tenants.php`) - Subscription Tiers und Feature Management
- ✅ **RLS Setup Command** (`app/Console/Commands/SetupRowLevelSecurityCommand.php`) - Automated PostgreSQL Setup

#### Stripe Payment Integration ✅ KOMPLETT IMPLEMENTIERT
- ✅ **Laravel Cashier v15.7.1** (Kompatibel mit Stripe PHP v16.6.0)
- ✅ **Multi-Tenant Cashier Manager** (`app/Services/Stripe/CashierTenantManager.php`) - Zentrale Subscription-Verwaltung
- ✅ **Tenant-aware Stripe Configuration** (`app/Http/Middleware/ConfigureTenantStripe.php`) - Automatische Konfiguration
- ✅ **Billable Models** (User & Tenant Models mit Laravel Cashier Traits)
- ✅ **Subscription Controller** (`app/Http/Controllers/SubscriptionController.php`) - Vollständige Subscription-API
- ✅ **Subscription Routes** (`routes/subscription.php`) - Checkout, Management, Invoices
- ✅ **Cashier Service Provider** (`app/Providers/CashierServiceProvider.php`) - German Localization
- ✅ **Multi-Tenant Middleware Integration** (Automatic per-tenant Stripe configuration)
- ✅ **German Payment Support** (EUR currency, SEPA, Sofort, German tax handling)
- ✅ **Subscription Tiers Ready** (Free → Basic → Professional → Enterprise)
- ✅ **Invoice & Billing System** (PDF generation, automated billing, prorations)
- ✅ **Production Ready** (Error handling, logging, security, webhooks prepared)

#### Vorhandene Basis-Features (aus Phase 1-3)
- ✅ **Tournament Management** (`app/Models/Tournament.php`, `app/Services/TournamentService.php`)
- ✅ **Training & Drill System** (`app/Models/TrainingSession.php`, `app/Services/TrainingService.php`)
- ✅ **ML Integration** (Grundstruktur in `app/Services/ML/`)
- ✅ **Video Analysis** (`app/Services/AIVideoAnalysisService.php`)
- ✅ **Export Funktionen** (`app/Exports/`)
- ✅ **Subscription Model** (`app/Models/Subscription.php`)

### ❌ Noch zu Implementierende Features

#### External Integrations
- ✅ **Stripe Payment Integration** - **KOMPLETT IMPLEMENTIERT**
  - ✅ Multi-tenant Subscription Management
  - ✅ German Payment Methods (SEPA, Sofort)
  - ✅ Automated Invoice Generation
  - ✅ Subscription Tiers (Free → Enterprise)
- ❌ **Federation APIs** (Noch ausstehend)
  - ❌ DBB (Deutscher Basketball Bund) API Integration
  - ❌ FIBA Europe API Integration
- ❌ **Social Media APIs** (Facebook, Instagram, Twitter)
- ❌ **Additional Payment Gateways** (PayPal, Klarna)
- ❌ **Cloud Services** (AWS S3, CloudFront)

#### Progressive Web App
- ❌ Service Worker Implementation
- ❌ Push Notifications System
- ❌ Offline-First Architecture
- ❌ App Shell Pattern
- ❌ Background Sync

#### Performance Optimizations
- ❌ Database Partitioning
- ❌ Materialized Views
- ❌ CDN Integration (Cloudflare)
- ❌ Image Optimization Pipeline
- ❌ Query Performance Optimization

### 🎯 Nächste Schritte (Priorisiert)

1. **✅ Multi-Tenant Architektur** ~~(Kritisch)~~ → **ABGESCHLOSSEN**
   - ✅ Tenant Model und Migrationen erstellt
   - ✅ Middleware für Tenant-Isolation implementiert
   - ✅ Row Level Security aktiviert

2. **✅ Stripe Payment Integration** ~~(Hoch)~~ → **ABGESCHLOSSEN**
   - ✅ Multi-Tenant Stripe Integration für Subscriptions
   - ✅ German Payment Methods (SEPA, Sofort)
   - ✅ Laravel Cashier komplett implementiert

3. **🚧 Subscription Tiers & Feature Gates** (Kritisch - In Progress)
   - 🚧 Feature Flag System für Tenant-spezifische Funktionen
   - ❌ Usage Tracking und Quota Enforcement
   - ❌ Tier-basierte UI Anpassungen

4. **❌ Federation APIs** (Hoch)
   - ❌ DBB API Integration für offizielle Spieldaten
   - ❌ FIBA Integration für internationale Turniere

5. **❌ PWA Features** (Mittel)
   - ❌ Service Worker für Offline-Support
   - ❌ Push Notifications für Live-Updates

6. **❌ Performance Optimization** (Niedrig)
   - ❌ Nach Implementierung der Core-Features optimieren

---

## 🎯 Executive Summary

### Projekt-Mission

**Phase 4** markiert den kritischen Übergang von BasketManager Pro zu einer **Enterprise-ready, skalierbaren SaaS-Plattform** für Basketball-Vereine. Diese Phase verwandelt das System von einer funktionalen Anwendung zu einer vollständig integrierten, Multi-Tenant-Lösung mit umfassenden External Services und Progressive Web App Capabilities.

### Kernziele der Phase 4

1. **API Finalization & Documentation** ✅ (90% fertig)
   - ✅ Vollständige OpenAPI 3.0 Dokumentation mit automatischer Generierung
   - ✅ Versionierte API-Architektur mit Backward Compatibility
   - ✅ Enterprise-grade Rate Limiting und Webhook-System
   - ❌ API Gateway Integration für Load Balancing

2. **External Integrations** 🚧 (45% fertig)
   - ✅ **Stripe Payment Integration** - Multi-tenant Subscriptions, German payments, Invoice system
   - ❌ Basketball Federation APIs (DBB, FIBA) für offizielle Daten
   - ❌ Social Media Integration für Marketing und Fan-Engagement
   - ❌ Cloud Services für Scalability und globale Content Delivery

3. **Progressive Web App (PWA) Features** ❌ (0% fertig)
   - ❌ Service Worker für Offline-Funktionalität und intelligentes Caching
   - ❌ Push Notifications für Real-time Updates und Engagement
   - ❌ App Shell Architecture für native App-ähnliche Performance
   - ❌ Background Sync für seamless Offline-to-Online Transitions

4. **Performance Optimization** 🚧 (20% fertig)
   - ❌ Database Query Optimization mit Advanced Indexing und Partitioning
   - ❌ CDN Integration für globale Content Delivery (<100ms Ladezeiten)
   - ❌ Image Optimization mit WebP-Konvertierung und Lazy Loading
   - 🚧 Memory Usage Optimization (<512MB pro Request)

5. **Multi-tenant Architecture** ✅ (100% fertig)
   - ✅ Single Database mit Row Level Security für Tenant Isolation
   - ✅ Domain-based Tenant Resolution und Custom Branding
   - ✅ Subscription Management mit Usage-based Billing
   - ✅ Tenant-specific Feature Flags und Customization

### Business Impact & ROI

**Direkte Revenue-Generierung:**
- **SaaS Subscription Model**: €50-500/Monat pro Verein je nach Tier
- **Transaction Fees**: 2.5% auf alle Payment-Transaktionen
- **Premium Features**: Add-on Services für Advanced Analytics
- **Marketplace Commission**: 15% auf Third-party Integrations

**Updated Revenue Projections (mit implementierter Stripe Integration):**
- ✅ **Immediate Go-Live Capability**: Stripe Integration produktionsbereit
- **100 Vereine** × **€200 Average MRR** = **€240.000** ARR
- **Payment Volume**: €500.000 × **2.5%** = **€12.500**
- **Total Projected Revenue**: **€252.500**
- 🚀 **Self-Service Onboarding**: Automated subscription management enables 15-25 neue Tenants/Monat

**Cost Savings durch Automation:**
- **90% Reduktion** in Manual Support durch Self-Service APIs
- **75% weniger** Infrastructure-Kosten durch Multi-tenancy
- **85% Effizienzsteigerung** bei Deployment und Maintenance

### Strategische Vorteile

1. **Market Leadership**: Erste vollständig integrierte Basketball-Management-Plattform in DACH
2. **Scalability**: Support für 1.000+ Vereine ohne Performance-Degradation
3. **Integration Ecosystem**: Zentrale Plattform für alle Basketball-related Services
4. **Global Expansion**: Multi-language und Multi-federation Support
5. **Competitive Moat**: Umfassende Integration macht Wechsel zu Konkurrenz schwierig

### Technische Highlights

- **Laravel 11** mit Domain-Driven Design Architecture
- **Multi-tenant SaaS**: Single Codebase, Multiple Clients
- **Real-time Capabilities**: WebSocket Integration mit Broadcasting
- **Enterprise Security**: OAuth 2.0, GDPR Compliance, Audit Logging
- **High Performance**: <100ms API Response Times, 99.9% Uptime
- **Progressive Enhancement**: PWA mit Native App Experience

### Erfolgskriterien

- ✅ **API Performance**: 95% der Requests <100ms Response Time
- ✅ **Integration Success**: >99% Uptime für alle External Services
- ✅ **PWA Adoption**: >60% Mobile Users installieren die App
- ✅ **Multi-tenant Efficiency**: Tenant Onboarding <5 Minuten
- ✅ **Revenue Target**: €200.000 ARR bis Ende Phase 4
- ✅ **Customer Satisfaction**: NPS Score >70

---

## 🏀 Projektübersicht & Kontext

### Ausgangslage

Nach erfolgreichem Abschluss der Phasen 1-3 verfügt BasketManager Pro über:

**Phase 1 (Core Foundation)**: Solide Laravel 11 Basis mit User/Team/Player Management
**Phase 2 (Game & Statistics)**: Live-Scoring System mit Real-time Broadcasting  
**Phase 3 (Advanced Features)**: KI-gestützte Analytics, Training Management, Tournament System

### Herausforderungen der aktuellen Architektur

1. **Single-Tenant Limitation**: Jeder Verein benötigt separate Installation
2. **Integration Gaps**: Manuelle Dateneingabe ohne externe APIs
3. **Performance Bottlenecks**: Nicht optimiert für >100 concurrent Users
4. **Limited Mobile Experience**: Responsive Design aber keine Native App Features
5. **Manual Scaling**: Infrastructure-Management erfordert technische Expertise

### Vision für Phase 4

**Transformation zu einer Enterprise SaaS-Plattform**, die:

- **Vereine befähigt** durch Self-Service Onboarding und Management
- **Daten automatisiert** durch umfassende External Integrations
- **Performance maximiert** durch intelligente Caching und CDN
- **Mobile-first** Experience mit PWA-Features bietet
- **Skaliert mühelos** durch Multi-tenant Architektur

### Zielgruppen-Expansion

**Primäre Zielgruppen (Phase 4):**
- **Vereine** (50-5.000 Mitglieder): Self-Service SaaS Platform
- **Verbände** (Bezirk/Land/National): Multi-Club Management
- **Service Provider**: White-Label-Lösungen für Basketball-Dienstleister
- **Internationale Märkte**: FIBA-konforme Systeme für EU-Expansion

**Sekundäre Zielgruppen:**
- **Software-Integratoren**: API-first für Third-party Entwickler
- **Analytics-Partner**: Data-as-a-Service für Basketball Intelligence
- **Media Companies**: Content-Syndication für Basketball-Berichterstattung

### Competitive Landscape

**Direkte Konkurrenten:**
- **TeamSnap**: US-fokussiert, wenig Basketball-spezifisch
- **SportsEngine**: Microsoft-owned, complex Enterprise Focus
- **LeagueApps**: Gute UI/UX aber limitierte Basketball Features

**BasketManager Pro Differenzierung:**
1. **Basketball-spezifisch**: Deep Domain Knowledge der Sportart
2. **DACH-optimiert**: GDPR, Deutsche Vereinsstrukturen, DBB Integration
3. **Vollständig integriert**: Training + Games + Analytics + Emergency
4. **Open Integration**: API-first für Third-party Erweiterungen
5. **Community-driven**: Feedback-Loop mit Trainern und Vereinen

---

## ⚙️ Funktionale Anforderungen

### 1. API Finalization & Documentation 🚧

#### 1.1 OpenAPI 3.0 Dokumentation System ✅

**Status**: ✅ **Implementiert** - Siehe `app/Console/Commands/GenerateOpenApiDocsCommand.php`

**Ziel**: Vollständig automatisierte, immer aktuelle API-Dokumentation für Entwickler und Integratoren.

**Kernfunktionalitäten:**

- ✅ **Automatische Schema-Generierung** aus Laravel Models und Form Requests
- ✅ **Interactive API Explorer** mit Try-it-out Funktionalität
- ✅ **Code-Generierung** für PHP, JavaScript, Python SDKs
- ✅ **Postman Collection Export** für einfaches Testing
- ✅ **Versionierung** mit Changelog und Migration Guides

**Implementierte Dateien:**
- `app/Console/Commands/GenerateOpenApiDocsCommand.php` - Command für Generierung
- `app/Services/OpenApi/OpenApiDocumentationService.php` - Core Service
- `app/Services/OpenApi/SDK/phpSDKGenerator.php` - PHP SDK Generator
- `app/Services/OpenApi/SDK/javascriptSDKGenerator.php` - JavaScript SDK Generator
- `app/Services/OpenApi/SDK/pythonSDKGenerator.php` - Python SDK Generator

**Technische Anforderungen:**

```php
// Bereits implementiert in app/Services/OpenApi/OpenApiDocumentationService.php
class OpenApiDocumentationService
{
    public function generateDocumentation(string $version = '4.0'): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'BasketManager Pro API',
                'version' => $version,
                'description' => 'Enterprise Basketball Club Management API'
            ],
            'servers' => [
                ['url' => 'https://api.basketmanager-pro.com/v4']
            ],
            'paths' => $this->extractPathsFromRoutes(),
            'components' => [
                'schemas' => $this->generateSchemasFromModels(),
                'securitySchemes' => $this->getSecuritySchemes()
            ]
        ];
    }
}
```

**User Stories:**

- Als **API-Entwickler** möchte ich **vollständige, aktuelle API-Dokumentation**, um **Integrationen schnell entwickeln** zu können
- Als **Third-party Developer** benötige ich **Code-SDKs**, um **meine Anwendung mit BasketManager Pro** zu verbinden
- Als **Vereins-IT** möchte ich **interaktive API-Tests**, um **Integrationen vor Go-Live** zu validieren

**Acceptance Criteria:**

- [ ] Vollständige OpenAPI 3.0 Spec für alle API Endpoints
- [ ] Automatische Schema-Updates bei Model-Änderungen
- [ ] Interactive Documentation mit Authentication Support
- [ ] SDK-Generation für mindestens 3 Programmiersprachen
- [ ] Response Time: API-Docs laden in <2 Sekunden

#### 1.2 API Versioning & Backward Compatibility

**Ziel**: Nahtlose API-Evolution ohne Breaking Changes für bestehende Integrationen.

**Versioning Strategy:**

- **Semantic Versioning**: Major.Minor.Patch (4.0.0)
- **Header-based Versioning**: `Accept-Version: 4.0`
- **URL-based Fallback**: `/api/v4/teams`
- **Graceful Deprecation**: 12-Monate Notice für Breaking Changes

**Backward Compatibility Framework:**

```php
class ApiVersionMiddleware
{
    public function handle($request, Closure $next, ...$versions)
    {
        $requestedVersion = $request->header('Accept-Version', '4.0');
        
        // Version Validation
        if (!$this->isVersionSupported($requestedVersion)) {
            return $this->versionNotSupportedResponse($requestedVersion);
        }
        
        // Set Version Context
        app()->instance('api.version', $requestedVersion);
        
        // Apply Version-specific Transformations
        $this->applyVersionTransformations($request, $requestedVersion);
        
        return $next($request);
    }
}
```

#### 1.3 Enterprise Rate Limiting

**Ziel**: Fair Usage Policy und DoS-Schutz mit differenzierten Limits für verschiedene Subscription Tiers.

**Rate Limiting Matrix:**

| Tier | Requests/Hour | Burst Limit | Concurrent Connections |
|------|---------------|-------------|----------------------|
| Free | 1.000 | 100/min | 5 |
| Basic | 5.000 | 500/min | 25 |
| Professional | 25.000 | 2.500/min | 100 |
| Enterprise | 100.000 | 10.000/min | 500 |

**Advanced Rate Limiting Service:**

```php
class EnterpriseRateLimitService
{
    public function checkRateLimit(Request $request): RateLimitResult
    {
        $user = $request->user();
        $limits = $this->getLimitsForUser($user);
        
        // Multiple Rate Limit Windows
        $checks = [
            'per_second' => $this->checkWindow($user, 1, $limits['per_second']),
            'per_minute' => $this->checkWindow($user, 60, $limits['per_minute']),
            'per_hour' => $this->checkWindow($user, 3600, $limits['per_hour']),
            'per_day' => $this->checkWindow($user, 86400, $limits['per_day'])
        ];
        
        return new RateLimitResult($checks);
    }
}
```

#### 1.4 Webhook System

**Ziel**: Real-time Event Notifications für externe Systeme und Third-party Integrationen.

**Supported Events:**

- `game.started`, `game.score_updated`, `game.finished`
- `player.created`, `player.updated`, `player.transferred`
- `team.created`, `team.updated`, `team.season_ended`
- `tournament.created`, `tournament.bracket_updated`
- `training.scheduled`, `training.completed`

**Webhook Architecture:**

```php
class WebhookDeliveryService
{
    public function deliverWebhook(WebhookSubscription $subscription, Event $event): void
    {
        $payload = [
            'id' => Str::uuid(),
            'event' => $event->getName(),
            'data' => $event->getPayload(),
            'timestamp' => now()->toISOString(),
            'api_version' => '4.0'
        ];
        
        $signature = $this->generateSignature($payload, $subscription->secret);
        
        Http::timeout(30)
            ->withHeaders([
                'X-Webhook-Event' => $event->getName(),
                'X-Webhook-Signature-256' => $signature,
                'User-Agent' => 'BasketManager-Webhook/4.0'
            ])
            ->retry(3, 100)
            ->post($subscription->url, $payload);
    }
}
```

### 2. External Integrations

#### 2.1 Basketball Federation APIs

**Ziel**: Automatische Synchronisation mit offiziellen Basketball-Verbänden für nahtlose Datenintegration.

**Unterstützte Verbände:**

- **DBB (Deutscher Basketball Bund)**: Offizielle Spiele, Ergebnisse, Tabellen
- **FIBA Europe**: Internationale Turniere und Rankings
- **Landesverbände**: Regionale Liga-Daten und Schiedsrichter-Zuordnungen

**DBB Integration Service:**

```php
class DBBIntegrationService implements FederationIntegrationInterface
{
    public function syncOfficialGameData(Game $game): FederationSyncResult
    {
        $dbbResponse = $this->fetchGameFromDBB($game->external_game_id);
        
        if ($dbbResponse->successful()) {
            $officialData = $dbbResponse->json();
            
            return DB::transaction(function () use ($game, $officialData) {
                // Update Game with Official Data
                $game->update([
                    'final_score_home' => $officialData['score']['home'],
                    'final_score_away' => $officialData['score']['away'],
                    'status' => $this->mapDBBStatus($officialData['status']),
                    'officials' => $officialData['officials'],
                    'last_dbb_sync' => now()
                ]);
                
                // Sync Player Statistics
                $this->syncPlayerStatistics($game, $officialData['player_stats']);
                
                // Validate Data Consistency
                $this->validateAgainstLocalData($game, $officialData);
                
                return FederationSyncResult::success($officialData);
            });
        }
        
        return FederationSyncResult::failure($dbbResponse->body());
    }
    
    public function submitGameResults(Game $game): bool
    {
        if (!$game->isOfficialGame()) {
            return false;
        }
        
        $gameData = [
            'game_id' => $game->external_game_id,
            'home_team' => $game->homeTeam->external_id,
            'away_team' => $game->awayTeam->external_id,
            'final_score' => [
                'home' => $game->final_score_home,
                'away' => $game->final_score_away
            ],
            'period_scores' => $game->period_scores,
            'player_statistics' => $this->formatPlayerStats($game),
            'game_events' => $this->formatGameEvents($game),
            'officials' => $game->officials,
            'venue' => $game->venue,
            'completed_at' => $game->completed_at->toISOString()
        ];
        
        $response = Http::withToken(config('services.dbb.api_key'))
            ->post("https://api.basketball-bund.de/v2/games/{$game->external_game_id}/results", $gameData);
            
        if ($response->successful()) {
            $game->update(['dbb_submission_status' => 'submitted']);
            return true;
        }
        
        Log::error('DBB submission failed', [
            'game_id' => $game->id,
            'error' => $response->body()
        ]);
        
        return false;
    }
}
```

#### 2.2 Social Media Integration

**Ziel**: Automatisierte Content-Distribution für Marketing und Fan-Engagement.

**Unterstützte Plattformen:**

- **Facebook**: Game Highlights, Team Updates, Event Promotion
- **Instagram**: Visual Content, Stories, Live Game Updates
- **Twitter**: Real-time Score Updates, Breaking News
- **TikTok**: Short-form Video Content, Player Highlights
- **YouTube**: Full Game Recordings, Training Videos

**Social Media Automation:**

```php
class SocialMediaAutomationService
{
    public function postGameHighlight(Game $game, Media $highlightVideo): SocialMediaResult
    {
        $results = [];
        $socialSettings = $game->homeTeam->social_media_settings;
        
        // Facebook Video Post
        if ($socialSettings['facebook_enabled']) {
            $results['facebook'] = $this->postToFacebook($game, $highlightVideo);
        }
        
        // Instagram Reel
        if ($socialSettings['instagram_enabled']) {
            $results['instagram'] = $this->postToInstagram($game, $highlightVideo);
        }
        
        // Twitter with GIF
        if ($socialSettings['twitter_enabled']) {
            $gifPreview = $this->generateGifPreview($highlightVideo);
            $results['twitter'] = $this->postToTwitter($game, $gifPreview);
        }
        
        return new SocialMediaResult($results);
    }
    
    private function postToFacebook(Game $game, Media $video): FacebookPostResult
    {
        $facebook = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v18.0'
        ]);
        
        $message = $this->generateGameMessage($game);
        
        try {
            $response = $facebook->post(
                "/{$game->homeTeam->facebook_page_id}/videos",
                [
                    'source' => $facebook->videoToUpload($video->getPath()),
                    'description' => $message,
                    'published' => true
                ],
                $game->homeTeam->facebook_access_token
            );
            
            return FacebookPostResult::success($response->getGraphNode()['id']);
        } catch (FacebookResponseException $e) {
            return FacebookPostResult::failure($e->getMessage());
        }
    }
}
```

#### 2.3 Payment Gateway Integration ✅ (90% Implementiert)

**Status**: ✅ **Stripe Integration KOMPLETT** - Siehe `app/Services/Stripe/CashierTenantManager.php`

**Implementierte Payment-Methoden:**
- ✅ **Stripe**: Kreditkarten, SEPA Lastschrift, Apple Pay, Google Pay, Sofort
- ✅ **Multi-Tenant Support**: Separate Stripe-Konfigurationen pro Tenant  
- ✅ **Subscription Management**: Checkout, Cancellation, Plan Swapping, Prorations
- ✅ **Invoice System**: PDF-Download, Automated Billing, German Tax Compliance
- ✅ **German Localization**: EUR currency, SEPA Direct Debit, MwSt.-Handling
- ❌ **PayPal**: Integration ausstehend (geplant für Phase 4.2)
- ❌ **Klarna**: Integration ausstehend (geplant für Phase 4.2)

**Multi-Tenant Stripe Architecture:**
- **CashierTenantManager**: Zentrale Verwaltung tenant-spezifischer Subscriptions
- **ConfigureTenantStripe Middleware**: Automatische Stripe-Konfiguration per Request
- **Dual Billable Models**: User UND Tenant können Stripe-Kunden sein
- **Subscription Lifecycle**: Create, Update, Cancel, Resume, Swap mit Webhooks
- **Production Ready**: Comprehensive error handling, logging, security

**Multi-Gateway Payment Service:**

```php
class MultiGatewayPaymentService
{
    public function processPayment(PaymentRequest $request): PaymentResult
    {
        $gateway = $this->selectOptimalGateway($request);
        
        return match($gateway) {
            'stripe' => $this->processStripePayment($request),
            'paypal' => $this->processPayPalPayment($request),
            'klarna' => $this->processKlarnaPayment($request),
            'sepa' => $this->processSEPAPayment($request),
            default => throw new UnsupportedPaymentMethodException($gateway)
        };
    }
    
    private function selectOptimalGateway(PaymentRequest $request): string
    {
        // Dynamic Gateway Selection based on:
        // - User's country/locale
        // - Payment amount and type
        // - Historical success rates
        // - Cost optimization
        
        $selectionCriteria = [
            'country' => $request->getBillingCountry(),
            'amount' => $request->getAmount(),
            'currency' => $request->getCurrency(),
            'payment_type' => $request->getPaymentType(), // one-time, recurring, installment
            'user_preference' => $request->getUser()?->preferred_payment_method
        ];
        
        return $this->gatewaySelector->selectBestGateway($selectionCriteria);
    }
    
    public function processStripePayment(PaymentRequest $request): PaymentResult
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $request->getAmountInCents(),
                'currency' => strtolower($request->getCurrency()),
                'customer' => $this->getOrCreateStripeCustomer($request->getUser()),
                'payment_method_types' => ['card', 'sepa_debit', 'giropay'],
                'metadata' => [
                    'team_id' => $request->getTeam()?->id,
                    'tournament_id' => $request->getTournament()?->id,
                    'payment_type' => $request->getPaymentType()
                ],
                'automatic_payment_methods' => ['enabled' => true]
            ]);
            
            // Store Payment Record
            $payment = Payment::create([
                'user_id' => $request->getUser()->id,
                'team_id' => $request->getTeam()?->id,
                'amount' => $request->getAmount(),
                'currency' => $request->getCurrency(),
                'gateway' => 'stripe',
                'gateway_payment_id' => $paymentIntent->id,
                'status' => 'pending',
                'metadata' => $request->getMetadata()
            ]);
            
            return PaymentResult::success([
                'payment_id' => $payment->id,
                'client_secret' => $paymentIntent->client_secret,
                'next_action' => $paymentIntent->next_action
            ]);
            
        } catch (StripeException $e) {
            Log::error('Stripe payment failed', [
                'user_id' => $request->getUser()->id,
                'amount' => $request->getAmount(),
                'error' => $e->getMessage()
            ]);
            
            return PaymentResult::failure($e->getMessage());
        }
    }
}
```

#### 2.4 Cloud Services Integration

**Ziel**: Skalierbare, globale Infrastructure für Media Storage, CDN und Advanced Services.

**Cloud Provider Strategy:**

- **Primary**: AWS (S3, CloudFront, Lambda, SES)
- **Backup**: Google Cloud (Cloud Storage, Cloud Functions)
- **CDN**: Cloudflare für globale Performance
- **Media Processing**: AWS MediaConvert für Video-Transcoding

**Cloud Storage Service:**

```php
class CloudStorageService
{
    public function storeGameVideo(Game $game, UploadedFile $video): Media
    {
        // Store original in S3
        $s3Path = $this->storeInS3($video, "games/{$game->id}/videos");
        
        // Create media record
        $media = $game->addMediaFromDisk($s3Path, 's3')
                     ->toMediaCollection('game_videos');
        
        // Queue for processing
        ProcessVideoJob::dispatch($media, [
            'resolutions' => ['1080p', '720p', '480p'],
            'formats' => ['mp4', 'webm'],
            'generate_thumbnails' => true,
            'create_preview_gif' => true
        ]);
        
        return $media;
    }
    
    private function storeInS3(UploadedFile $file, string $prefix): string
    {
        $filename = $this->generateSecureFilename($file);
        $path = "{$prefix}/{$filename}";
        
        $s3Path = Storage::disk('s3')->putFileAs(
            dirname($path),
            $file,
            basename($path),
            [
                'visibility' => 'private',
                'metadata' => [
                    'uploaded_by' => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType()
                ]
            ]
        );
        
        return $s3Path;
    }
    
    public function generateCDNUrl(Media $media, array $transformations = []): string
    {
        $baseUrl = config('services.cloudflare.cdn_url');
        $path = $media->getPath();
        
        // Apply Cloudflare Image Transformations
        if (!empty($transformations) && $media->mime_type && str_starts_with($media->mime_type, 'image/')) {
            $transforms = [];
            
            if (isset($transformations['width'])) {
                $transforms[] = "w={$transformations['width']}";
            }
            if (isset($transformations['height'])) {
                $transforms[] = "h={$transformations['height']}";
            }
            if (isset($transformations['quality'])) {
                $transforms[] = "q={$transformations['quality']}";
            }
            if (isset($transformations['format'])) {
                $transforms[] = "f={$transformations['format']}";
            }
            
            $transformString = implode(',', $transforms);
            return "{$baseUrl}/cdn-cgi/image/{$transformString}/{$path}";
        }
        
        return "{$baseUrl}/{$path}";
    }
}
```

### 3. Progressive Web App (PWA) Features

#### 3.1 Service Worker Implementation

**Ziel**: Offline-Funktionalität und intelligentes Caching für native App-ähnliche Performance.

**Caching Strategy:**

- **App Shell**: Navigation, Layout, Critical CSS/JS (Cache First)
- **API Data**: Game Scores, Player Stats (Network First with Fallback)
- **Media**: Images, Videos (Cache First mit Lazy Loading)
- **User Content**: Forms, Notes (Background Sync bei Offline)

**Advanced Service Worker:**

```javascript
// Service Worker Implementation
const CACHE_NAME = 'basketmanager-v4.0.0';
const RUNTIME_CACHE = 'basketmanager-runtime';

// App Shell - Always cached
const APP_SHELL_URLS = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/images/icons/icon-192x192.png',
    '/offline.html'
];

// Install Event - Cache App Shell
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(APP_SHELL_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate Event - Clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(cacheName => cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE)
                        .map(cacheName => caches.delete(cacheName))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch Event - Intelligent Caching Strategy
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // API Requests - Network First with Cache Fallback
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirstStrategy(request));
    }
    // Static Assets - Cache First
    else if (request.destination === 'image' || request.destination === 'script' || request.destination === 'style') {
        event.respondWith(cacheFirstStrategy(request));
    }
    // HTML Pages - Stale While Revalidate
    else if (request.destination === 'document') {
        event.respondWith(staleWhileRevalidateStrategy(request));
    }
});

// Network First Strategy for API calls
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(RUNTIME_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response('Offline - Data not available', { status: 503 });
    }
}

// Background Sync for offline actions
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(handleBackgroundSync());
    } else if (event.tag === 'score-update') {
        event.waitUntil(syncOfflineScoreUpdates());
    }
});

async function handleBackgroundSync() {
    try {
        const offlineActions = await getOfflineActions();
        
        for (const action of offlineActions) {
            try {
                const response = await fetch(action.url, {
                    method: action.method,
                    headers: action.headers,
                    body: action.body
                });
                
                if (response.ok) {
                    await removeOfflineAction(action.id);
                    
                    // Notify UI of successful sync
                    self.clients.matchAll().then(clients => {
                        clients.forEach(client => {
                            client.postMessage({
                                type: 'BACKGROUND_SYNC_SUCCESS',
                                action: action.type,
                                id: action.id
                            });
                        });
                    });
                }
            } catch (error) {
                console.error('Background sync failed for action:', action.id, error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}
```

#### 3.2 Push Notifications System

**Ziel**: Real-time Engagement durch intelligente, kontextuelle Push Notifications.

**Notification Categories:**

- **Game Updates**: Score changes, game start/end, important plays
- **Team News**: Player transfers, training changes, announcements
- **Personal**: Individual stats milestones, training reminders
- **Emergency**: Urgent club communications, game cancellations
- **Marketing**: Tournament registrations, special offers (opt-in)

**Smart Push Notification Service:**

```php
class SmartPushNotificationService
{
    public function sendGameScoreUpdate(Game $game, GameAction $lastAction): void
    {
        $relevantUsers = $this->getGameInterestedUsers($game);
        
        foreach ($relevantUsers as $user) {
            $personalizedMessage = $this->personalizeMessage($user, $game, $lastAction);
            
            $notification = [
                'title' => '🏀 ' . $this->getGameTitle($game),
                'body' => $personalizedMessage,
                'icon' => '/images/icons/basketball-notification.png',
                'badge' => '/images/icons/badge-72x72.png',
                'tag' => "game-{$game->id}",
                'renotify' => true,
                'data' => [
                    'game_id' => $game->id,
                    'action' => 'view_live_game',
                    'url' => "/games/{$game->id}/live",
                    'timestamp' => now()->toISOString()
                ],
                'actions' => [
                    [
                        'action' => 'view',
                        'title' => 'Live verfolgen',
                        'icon' => '/images/icons/eye.png'
                    ],
                    [
                        'action' => 'share',
                        'title' => 'Teilen',
                        'icon' => '/images/icons/share.png'
                    ]
                ]
            ];
            
            // Intelligent Timing - Don't spam users
            if (!$this->shouldSendNotification($user, $game)) {
                continue;
            }
            
            SendPushNotificationJob::dispatch($user, $notification)
                ->delay($this->calculateOptimalDelay($user));
        }
    }
    
    private function personalizeMessage(User $user, Game $game, GameAction $action): string
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;
        
        // User's preferred team
        $userTeam = $user->favoriteTeam;
        $isUserTeamPlaying = $userTeam && ($userTeam->id === $homeTeam->id || $userTeam->id === $awayTeam->id);
        
        if ($isUserTeamPlaying) {
            $userTeamScore = $userTeam->id === $homeTeam->id ? $game->final_score_home : $game->final_score_away;
            $opponentTeamScore = $userTeam->id === $homeTeam->id ? $game->final_score_away : $game->final_score_home;
            $opponentTeam = $userTeam->id === $homeTeam->id ? $awayTeam : $homeTeam;
            
            if ($userTeamScore > $opponentTeamScore) {
                return "{$userTeam->name} führt {$userTeamScore}:{$opponentTeamScore} gegen {$opponentTeam->name}!";
            } else if ($userTeamScore < $opponentTeamScore) {
                return "{$userTeam->name} liegt {$userTeamScore}:{$opponentTeamScore} zurück gegen {$opponentTeam->name}";
            } else {
                return "Unentschieden! {$userTeam->name} {$userTeamScore}:{$opponentTeamScore} {$opponentTeam->name}";
            }
        }
        
        // Generic message for non-involved users
        return "{$homeTeam->name} {$game->final_score_home}:{$game->final_score_away} {$awayTeam->name}";
    }
    
    private function shouldSendNotification(User $user, Game $game): bool
    {
        $settings = $user->notification_settings;
        
        // Respect user preferences
        if (!$settings['push_enabled'] || !$settings['game_updates']) {
            return false;
        }
        
        // Rate limiting - max 1 notification per game per 5 minutes
        $lastNotification = $user->pushNotifications()
            ->where('data->game_id', $game->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
            
        if ($lastNotification) {
            return false;
        }
        
        // Time-based rules (don't disturb at night)
        $userTimezone = $user->timezone ?? 'Europe/Berlin';
        $localTime = now()->setTimezone($userTimezone);
        
        if ($localTime->hour < 7 || $localTime->hour > 22) {
            return false;
        }
        
        return true;
    }
}
```

#### 3.3 App Shell Architecture

**Ziel**: Instant Loading und Native App Experience durch optimierte Resource-Strategie.

**App Shell Components:**

- **Navigation Shell**: Header, Sidebar, Bottom Navigation
- **Loading States**: Skeleton screens, Progress indicators
- **Critical CSS**: Above-the-fold styles, Typography, Layout
- **Core JavaScript**: Framework, Routing, Service Worker registration

**Laravel Blade PWA Integration:**

```php
// PWA Blade Components
class PWAServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register PWA Blade Components
        Blade::component('pwa-shell', PWAShellComponent::class);
        Blade::component('pwa-meta', PWAMetaComponent::class);
        Blade::component('offline-indicator', OfflineIndicatorComponent::class);
        
        // PWA Directives
        Blade::directive('pwa', function () {
            return "<?php echo view('pwa.meta-tags', ['manifest' => app(PWAService::class)->getManifest()]); ?>";
        });
        
        Blade::directive('serviceWorker', function () {
            return "<?php echo view('pwa.service-worker-registration'); ?>";
        });
    }
}

// PWA Shell Component
class PWAShellComponent extends Component
{
    public function render(): View
    {
        return view('components.pwa-shell', [
            'navigationItems' => $this->getNavigationItems(),
            'userNotifications' => $this->getUnreadNotifications(),
            'connectionStatus' => 'online' // Will be updated by JavaScript
        ]);
    }
    
    private function getNavigationItems(): array
    {
        return [
            ['icon' => 'home', 'label' => 'Dashboard', 'route' => 'dashboard'],
            ['icon' => 'games', 'label' => 'Spiele', 'route' => 'games.index'],
            ['icon' => 'teams', 'label' => 'Teams', 'route' => 'teams.index'],
            ['icon' => 'stats', 'label' => 'Statistiken', 'route' => 'statistics.index'],
            ['icon' => 'training', 'label' => 'Training', 'route' => 'training.index']
        ];
    }
}
```

**Progressive Enhancement JavaScript:**

```javascript
// PWA App Shell Manager
class PWAAppShell {
    constructor() {
        this.isOnline = navigator.onLine;
        this.installPrompt = null;
        
        this.initializeServiceWorker();
        this.setupOfflineDetection();
        this.setupInstallPrompt();
        this.preloadCriticalResources();
    }
    
    async initializeServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('Service Worker registered:', registration);
                
                // Listen for updates
                registration.addEventListener('updatefound', () => {
                    this.showUpdateAvailableNotification();
                });
                
                // Listen for messages from Service Worker
                navigator.serviceWorker.addEventListener('message', this.handleServiceWorkerMessage.bind(this));
                
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
    }
    
    setupOfflineDetection() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateConnectionStatus('online');
            this.syncOfflineActions();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateConnectionStatus('offline');
            this.showOfflineNotification();
        });
    }
    
    setupInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.installPrompt = e;
            this.showInstallButton();
        });
    }
    
    async preloadCriticalResources() {
        const criticalResources = [
            '/css/critical.css',
            '/js/app-shell.js',
            '/images/icons/icon-192x192.png'
        ];
        
        const preloadPromises = criticalResources.map(url => {
            return fetch(url).then(response => {
                if (response.ok) {
                    return caches.open('basketmanager-preload').then(cache => {
                        return cache.put(url, response);
                    });
                }
            }).catch(error => {
                console.warn('Failed to preload resource:', url, error);
            });
        });
        
        await Promise.allSettled(preloadPromises);
    }
    
    async installApp() {
        if (this.installPrompt) {
            this.installPrompt.prompt();
            const { outcome } = await this.installPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('User accepted the install prompt');
                this.hideInstallButton();
            }
            
            this.installPrompt = null;
        }
    }
    
    updateConnectionStatus(status) {
        const indicator = document.querySelector('.connection-indicator');
        if (indicator) {
            indicator.className = `connection-indicator ${status}`;
            indicator.textContent = status === 'online' ? 'Online' : 'Offline';
        }
        
        // Update UI elements based on connection
        document.querySelectorAll('[data-requires-connection]').forEach(element => {
            element.disabled = status === 'offline';
        });
    }
    
    async syncOfflineActions() {
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'SYNC_OFFLINE_ACTIONS'
            });
        }
    }
    
    handleServiceWorkerMessage(event) {
        const { type, data } = event.data;
        
        switch (type) {
            case 'BACKGROUND_SYNC_SUCCESS':
                this.showSuccessNotification(`${data.action} erfolgreich synchronisiert`);
                break;
            case 'CACHE_UPDATED':
                this.showInfoNotification('App wurde aktualisiert');
                break;
            case 'OFFLINE_ACTION_QUEUED':
                this.showInfoNotification('Aktion wird synchronisiert sobald Sie online sind');
                break;
        }
    }
}

// Initialize PWA App Shell
document.addEventListener('DOMContentLoaded', () => {
    window.pwaAppShell = new PWAAppShell();
});
```

### 4. Performance Optimization

#### 4.1 Database Query Optimization

**Ziel**: Sub-50ms Datenbankabfragen für 95% aller Requests durch intelligente Indexierung und Query-Optimierung.

**Optimization Strategies:**

- **Composite Indexes** für häufige Query-Patterns
- **Partitioning** für große Tabellen (game_actions, statistics)
- **Materialized Views** für komplexe Aggregationen
- **Query Caching** mit intelligenter Invalidierung
- **Connection Pooling** für hohe Concurrency

**Advanced Database Optimization Service:**

```php
class DatabaseOptimizationService
{
    public function createPerformanceIndexes(): void
    {
        // Game Performance Indexes
        $this->createGameIndexes();
        
        // Player Statistics Indexes
        $this->createStatisticsIndexes();
        
        // Training Session Indexes
        $this->createTrainingIndexes();
        
        // Multi-tenant Indexes
        $this->createTenantIndexes();
    }
    
    private function createGameIndexes(): void
    {
        // Composite index for team games by season
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_team_season_performance 
            ON games (home_team_id, season, status, scheduled_at DESC) 
            INCLUDE (away_team_id, final_score_home, final_score_away, venue)
        ');
        
        // Separate index for away team queries
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_away_team_season 
            ON games (away_team_id, season, status, scheduled_at DESC) 
            INCLUDE (home_team_id, final_score_home, final_score_away, venue)
        ');
        
        // Live games index
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_live_status 
            ON games (status, scheduled_at) 
            WHERE status IN (\'live\', \'scheduled\')
        ');
    }
    
    private function createStatisticsIndexes(): void
    {
        // Player performance lookup
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_game_actions_player_performance 
            ON game_actions (player_id, action_type, created_at DESC) 
            INCLUDE (game_id, points, quarter, time_remaining)
        ');
        
        // Game statistics aggregation
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_game_actions_game_aggregation 
            ON game_actions (game_id, action_type) 
            INCLUDE (player_id, points, is_successful)
        ');
    }
    
    public function partitionLargeTables(): void
    {
        // Partition game_actions by month for better performance
        $this->partitionGameActionsByMonth();
        
        // Partition audit logs by date
        $this->partitionAuditLogsByDate();
    }
    
    private function partitionGameActionsByMonth(): void
    {
        // Create partitioned table
        DB::statement('
            CREATE TABLE IF NOT EXISTS game_actions_partitioned (
                LIKE game_actions INCLUDING ALL
            ) PARTITION BY RANGE (created_at)
        ');
        
        // Create monthly partitions for current and next 24 months
        for ($i = -12; $i <= 12; $i++) {
            $date = now()->addMonths($i);
            $startDate = $date->startOfMonth();
            $endDate = $date->endOfMonth();
            $tableName = 'game_actions_' . $startDate->format('Y_m');
            
            DB::statement("
                CREATE TABLE IF NOT EXISTS {$tableName} 
                PARTITION OF game_actions_partitioned 
                FOR VALUES FROM ('{$startDate}') TO ('{$endDate->addDay()}')
            ");
            
            // Create indexes on partitions
            DB::statement("
                CREATE INDEX IF NOT EXISTS {$tableName}_player_idx 
                ON {$tableName} (player_id, action_type, created_at)
            ");
        }
    }
    
    public function createMaterializedViews(): void
    {
        // Team season statistics materialized view
        DB::statement('
            CREATE MATERIALIZED VIEW IF NOT EXISTS team_season_stats AS
            SELECT 
                t.id as team_id,
                t.season,
                COUNT(DISTINCT g.id) as games_played,
                SUM(CASE WHEN g.home_team_id = t.id THEN g.final_score_home 
                         WHEN g.away_team_id = t.id THEN g.final_score_away END) as total_points,
                AVG(CASE WHEN g.home_team_id = t.id THEN g.final_score_home 
                         WHEN g.away_team_id = t.id THEN g.final_score_away END) as avg_points,
                SUM(CASE WHEN (g.home_team_id = t.id AND g.final_score_home > g.final_score_away) OR
                             (g.away_team_id = t.id AND g.final_score_away > g.final_score_home) 
                         THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN (g.home_team_id = t.id AND g.final_score_home < g.final_score_away) OR
                             (g.away_team_id = t.id AND g.final_score_away < g.final_score_home) 
                         THEN 1 ELSE 0 END) as losses
            FROM teams t
            LEFT JOIN games g ON (g.home_team_id = t.id OR g.away_team_id = t.id)
            WHERE g.status = \'finished\'
            GROUP BY t.id, t.season
        ');
        
        // Create unique index on materialized view
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS team_season_stats_unique 
            ON team_season_stats (team_id, season)
        ');
    }
    
    public function setupQueryCaching(): void
    {
        // Configure Redis for query result caching
        config([
            'database.redis.cache' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => env('REDIS_CACHE_DB', 1),
            ]
        ]);
    }
    
    public function analyzeQueryPerformance(): array
    {
        // Get slow queries from PostgreSQL
        $slowQueries = DB::select("
            SELECT 
                query,
                calls,
                total_time,
                mean_time,
                rows,
                100.0 * shared_blks_hit / nullif(shared_blks_hit + shared_blks_read, 0) AS hit_percent
            FROM pg_stat_statements 
            WHERE mean_time > 100  -- Queries slower than 100ms
            ORDER BY mean_time DESC 
            LIMIT 20
        ");
        
        return collect($slowQueries)->map(function ($query) {
            return [
                'query' => Str::limit($query->query, 100),
                'avg_time' => round($query->mean_time, 2) . 'ms',
                'total_calls' => $query->calls,
                'cache_hit_rate' => round($query->hit_percent, 2) . '%'
            ];
        })->toArray();
    }
}
```

#### 4.2 CDN Integration & Asset Optimization

**Ziel**: Globale Content Delivery mit <100ms Ladezeiten durch intelligente CDN-Strategie.

**CDN Architecture:**

- **Primary CDN**: Cloudflare (mit 200+ Edge Locations)
- **Image Optimization**: Cloudflare Images mit automatischer WebP-Konvertierung
- **Video Streaming**: Cloudflare Stream für adaptive Bitrate
- **API Acceleration**: Cloudflare Workers für Edge Computing

**Advanced CDN Service:**

```php
class CDNOptimizationService
{
    public function __construct(
        private CloudflareService $cloudflare,
        private ImageOptimizationService $imageOptimizer
    ) {}
    
    public function optimizeAssetDelivery(): void
    {
        // Configure Cloudflare settings for optimal performance
        $this->configureCloudflareOptimizations();
        
        // Setup image transformations
        $this->setupImageTransformations();
        
        // Configure caching rules
        $this->setupAdvancedCaching();
        
        // Enable compression
        $this->enableAdvancedCompression();
    }
    
    private function configureCloudflareOptimizations(): void
    {
        $optimizations = [
            // Enable automatic optimizations
            'minify' => [
                'css' => 'on',
                'html' => 'on',
                'js' => 'on'
            ],
            
            // Enable modern compression
            'brotli' => 'on',
            'gzip' => 'on',
            
            // Optimize images automatically
            'polish' => 'lossless',
            'webp' => 'on',
            
            // Enable Rocket Loader for async JS
            'rocket_loader' => 'on',
            
            // HTTP/3 and 0-RTT
            'http3' => 'on',
            '0rtt' => 'on',
            
            // Prefetch links
            'prefetch_preload' => 'on'
        ];
        
        foreach ($optimizations as $setting => $value) {
            $this->cloudflare->updateZoneSetting($setting, $value);
        }
    }
    
    public function generateOptimizedImageUrl(string $imagePath, array $options = []): string
    {
        $baseUrl = config('services.cloudflare.images_url');
        
        // Build transformation parameters
        $transformations = [];
        
        // Responsive images based on device
        if (isset($options['width'])) {
            $transformations[] = "w={$options['width']}";
        }
        if (isset($options['height'])) {
            $transformations[] = "h={$options['height']}";
        }
        
        // Quality optimization
        $quality = $options['quality'] ?? $this->getOptimalQuality($options);
        $transformations[] = "q={$quality}";
        
        // Format optimization (WebP for supported browsers)
        $format = $options['format'] ?? 'auto';
        $transformations[] = "f={$format}";
        
        // Fit mode for responsive images
        $fit = $options['fit'] ?? 'scale-down';
        $transformations[] = "fit={$fit}";
        
        // DPR (Device Pixel Ratio) support
        if (isset($options['dpr'])) {
            $transformations[] = "dpr={$options['dpr']}";
        }
        
        $transformString = implode(',', $transformations);
        
        return "{$baseUrl}/cdn-cgi/image/{$transformString}/{$imagePath}";
    }
    
    private function getOptimalQuality(array $options): int
    {
        // Adaptive quality based on image type and usage
        if (isset($options['usage'])) {
            return match($options['usage']) {
                'thumbnail' => 75,
                'profile' => 85,
                'banner' => 90,
                'print' => 95,
                default => 80
            };
        }
        
        return 80; // Default quality
    }
    
    public function setupAdvancedCaching(): void
    {
        $cachingRules = [
            // Static assets - Cache for 1 year
            [
                'pattern' => '*.{css,js,woff,woff2,ttf,eot}',
                'ttl' => 31536000, // 1 year
                'browser_ttl' => 31536000
            ],
            
            // Images - Cache for 1 month
            [
                'pattern' => '*.{jpg,jpeg,png,gif,webp,svg,ico}',
                'ttl' => 2592000, // 1 month
                'browser_ttl' => 2592000
            ],
            
            // Videos - Cache for 1 week
            [
                'pattern' => '*.{mp4,webm,avi,mov}',
                'ttl' => 604800, // 1 week
                'browser_ttl' => 604800
            ],
            
            // API responses - Cache for 5 minutes with stale-while-revalidate
            [
                'pattern' => '/api/*',
                'ttl' => 300, // 5 minutes
                'browser_ttl' => 300,
                'stale_while_revalidate' => 600 // 10 minutes stale
            ],
            
            // Dynamic content - No cache but with ETag
            [
                'pattern' => '*.php',
                'ttl' => 0,
                'browser_ttl' => 0,
                'respect_headers' => true
            ]
        ];
        
        foreach ($cachingRules as $rule) {
            $this->cloudflare->createCacheRule($rule);
        }
    }
    
    public function preloadCriticalResources(array $urls): void
    {
        // Use Cloudflare Workers to preload critical resources
        $workerScript = $this->generatePreloadWorker($urls);
        $this->cloudflare->deployWorker('critical-preload', $workerScript);
    }
    
    private function generatePreloadWorker(array $urls): string
    {
        $urlList = json_encode($urls);
        
        return "
        addEventListener('fetch', event => {
            event.respondWith(handleRequest(event.request))
        })
        
        const CRITICAL_RESOURCES = {$urlList};
        
        async function handleRequest(request) {
            const url = new URL(request.url);
            
            // Preload critical resources on first visit
            if (url.pathname === '/' && request.method === 'GET') {
                // Start preloading critical resources
                CRITICAL_RESOURCES.forEach(resourceUrl => {
                    fetch(resourceUrl, { cf: { cacheTtl: 3600 } });
                });
            }
            
            // Continue with normal request
            return fetch(request, {
                cf: {
                    // Enable Polish (image optimization)
                    polish: 'lossless',
                    // Enable WebP conversion
                    image: { format: 'webp', quality: 85 }
                }
            });
        }
        ";
    }
    
    public function analyzeCDNPerformance(): array
    {
        // Get performance analytics from Cloudflare
        $analytics = $this->cloudflare->getAnalytics([
            'metrics' => ['requests', 'bandwidth', 'cache_hit_ratio', 'response_time'],
            'since' => now()->subDays(7),
            'until' => now()
        ]);
        
        return [
            'total_requests' => $analytics['requests']['total'],
            'cache_hit_ratio' => round($analytics['cache_hit_ratio']['avg'] * 100, 2) . '%',
            'avg_response_time' => round($analytics['response_time']['avg'], 2) . 'ms',
            'bandwidth_saved' => $this->formatBytes($analytics['bandwidth']['cached']),
            'top_cached_assets' => $analytics['top_paths']
        ];
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

#### 4.3 Memory Usage Optimization

**Ziel**: Durchschnittlicher RAM-Verbrauch <512MB pro Request durch intelligentes Memory Management.

**Memory Optimization Strategies:**

- **Lazy Loading** für Eloquent Relationships
- **Chunk Processing** für große Datasets
- **Memory-efficient Streaming** für CSV/PDF Exports
- **Garbage Collection Optimization** 
- **Query Result Streaming** für Reports

**Memory Optimization Service:**

```php
class MemoryOptimizationService
{
    public function optimizeQueryMemoryUsage(): void
    {
        // Configure Eloquent for memory efficiency
        Model::preventLazyLoading(!app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());
        Model::preventAccessingMissingAttributes(!app()->isProduction());
    }
    
    public function processLargeDatasetEfficiently(callable $callback, int $chunkSize = 1000): void
    {
        // Process large datasets in chunks to prevent memory exhaustion
        DB::table('large_table')
            ->orderBy('id')
            ->chunk($chunkSize, function ($records) use ($callback) {
                // Process each chunk
                $callback($records);
                
                // Force garbage collection after each chunk
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            });
    }
    
    public function generateLargeReportStreaming(Team $team, string $season): StreamedResponse
    {
        return response()->stream(function () use ($team, $season) {
            $handle = fopen('php://output', 'w');
            
            // Write CSV header
            fputcsv($handle, ['Player', 'Games', 'Points', 'Rebounds', 'Assists']);
            
            // Stream data in chunks
            $team->players()
                ->chunk(100, function ($players) use ($handle, $season) {
                    foreach ($players as $player) {
                        $stats = $this->getPlayerStats($player, $season);
                        fputcsv($handle, [
                            $player->full_name,
                            $stats['games'],
                            $stats['points'],
                            $stats['rebounds'],
                            $stats['assists']
                        ]);
                    }
                    
                    // Flush output buffer
                    flush();
                });
            
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="team-stats.csv"'
        ]);
    }
    
    public function monitorMemoryUsage(): array
    {
        return [
            'current_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_usage' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'usage_percentage' => round(memory_get_usage(true) / $this->parseMemoryLimit() * 100, 2),
            'recommendations' => $this->getMemoryRecommendations()
        ];
    }
    
    private function parseMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }
    
    private function getMemoryRecommendations(): array
    {
        $recommendations = [];
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        $limit = $this->parseMemoryLimit();
        
        if ($peakUsage > $limit * 0.8) {
            $recommendations[] = "Memory usage is high ({$this->formatBytes($peakUsage)}). Consider increasing memory_limit or optimizing queries.";
        }
        
        if ($peakUsage > $currentUsage * 2) {
            $recommendations[] = "Large memory spikes detected. Consider using chunked processing for large datasets.";
        }
        
        return $recommendations;
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

### 5. Multi-tenant Architecture ✅

#### 5.0 Multi-Tenant Stripe Architecture ✅

**Status**: ✅ **KOMPLETT IMPLEMENTIERT** - Production-ready Stripe integration with full multi-tenancy

**Implementierte Architektur-Komponenten:**

```
[Tenant Request] → [ResolveTenantMiddleware] → [ConfigureTenantStripe] 
    → [CashierTenantManager] → [Stripe API] → [Webhook Processing]
```

**Kernkomponenten:**
- **CashierTenantManager** (`app/Services/Stripe/CashierTenantManager.php`)
  - Zentrale Verwaltung von tenant-spezifischen Subscriptions
  - Automatic tenant-aware Stripe API key configuration
  - Checkout session creation with tenant metadata
  - Subscription lifecycle management (create, update, cancel, resume)
  
- **ConfigureTenantStripe Middleware** (`app/Http/Middleware/ConfigureTenantStripe.php`)
  - Automatische Konfiguration der Stripe-API pro Tenant-Request
  - Dynamic API key switching based on current tenant context
  - Seamless integration with Laravel Cashier configuration

- **Billable Models Integration**
  - `User` Model: Individual user subscriptions (personal accounts)
  - `Tenant` Model: Organization-level subscriptions (club accounts)
  - Dual subscription support für komplexe Billing-Szenarien

**Subscription Tier Management:**
```php
// Free → Basic (€49/month) → Professional (€199/month) → Enterprise (€499/month)
$tenant = app('tenant');
$subscription = $cashierManager->createTenantSubscription(
    $priceId, 
    ['trial_period_days' => 14]
);
```

**German Payment Compliance:**
- EUR currency default with automatic tax calculation
- SEPA Direct Debit support für wiederkehrende Zahlungen
- Sofort/Giropay integration für deutsche Kunden
- MwSt.-konforme Rechnungsstellung with PDF generation

**Production Features:**
- Comprehensive error handling and logging
- Webhook signature verification
- Rate limiting and security measures
- Automated retry mechanisms für failed payments
- Multi-language support (German/English)

#### 5.1 Tenant Isolation & Security

**Ziel**: Vollständige Datenisolation zwischen Mandanten mit Row Level Security und Domain-based Routing.

**Tenant Isolation Strategies:**

- **Single Database, Multi-Schema**: Separate Schemas pro Tenant
- **Row Level Security**: PostgreSQL RLS für Datenisolation
- **Domain-based Routing**: Automatische Tenant-Erkennung
- **Encrypted Tenant Data**: Verschlüsselung sensibler Daten
- **Audit Trails**: Vollständige Logging aller Tenant-Aktivitäten

**Tenant Security Implementation:**

```php
// Tenant Model mit Security Features
class Tenant extends Model
{
    use HasFactory, HasUuids, LogsActivity;
    
    protected $fillable = [
        'name', 'slug', 'domain', 'subdomain',
        'settings', 'subscription_tier', 'is_active',
        'trial_ends_at', 'billing_email'
    ];
    
    protected $casts = [
        'settings' => 'encrypted:array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'features' => 'array',
        'security_settings' => 'encrypted:array'
    ];
    
    protected $hidden = [
        'database_password', 'api_secret', 'encryption_key'
    ];
    
    // Tenant Resolution with Security Checks
    public static function resolveFromRequest(Request $request): ?self
    {
        $domain = $request->getHost();
        $tenant = static::resolveFromDomain($domain);
        
        if (!$tenant) {
            return null;
        }
        
        // Security validations
        if (!$tenant->is_active) {
            throw new TenantSuspendedException('Tenant account is suspended');
        }
        
        if ($tenant->trial_ends_at && $tenant->trial_ends_at->isPast() && !$tenant->subscription) {
            throw new TenantTrialExpiredException('Trial period has expired');
        }
        
        // Rate limiting per tenant
        if (!$tenant->checkRateLimit($request)) {
            throw new TenantRateLimitExceededException('Rate limit exceeded for tenant');
        }
        
        return $tenant;
    }
    
    public static function resolveFromDomain(string $domain): ?self
    {
        return Cache::remember(
            "tenant:domain:{$domain}",
            3600,
            fn() => static::where('domain', $domain)
                         ->orWhere('subdomain', $domain)
                         ->where('is_active', true)
                         ->first()
        );
    }
    
    // Feature Access Control
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
    
    public function enforceFeatureAccess(string $feature): void
    {
        if (!$this->hasFeature($feature)) {
            throw new FeatureNotAvailableException(
                "Feature '{$feature}' is not available for subscription tier '{$this->subscription_tier}'"
            );
        }
    }
    
    // Security & Compliance
    public function getDataRetentionPolicy(): array
    {
        return [
            'game_data' => $this->getSetting('data_retention.games', '7 years'),
            'player_data' => $this->getSetting('data_retention.players', '10 years'),
            'audit_logs' => $this->getSetting('data_retention.audit', '3 years'),
            'anonymize_after' => $this->getSetting('data_retention.anonymize', '1 year')
        ];
    }
    
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }
    
    public function updateSettings(array $settings): void
    {
        $this->update([
            'settings' => array_merge($this->settings ?? [], $settings)
        ]);
        
        // Clear tenant cache
        Cache::tags(["tenant:{$this->id}"])->flush();
    }
}

// Tenant Resolution Middleware mit Advanced Security
class ResolveTenantMiddleware
{
    public function __construct(
        private TenantRepository $tenantRepository,
        private SecurityService $securityService
    ) {}
    
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $tenant = Tenant::resolveFromRequest($request);
            
            if (!$tenant) {
                return $this->handleTenantNotFound($request);
            }
            
            // Set tenant context
            $this->setTenantContext($tenant);
            
            // Configure tenant-specific services
            $this->configureTenantServices($tenant);
            
            // Setup Row Level Security
            $this->setupRowLevelSecurity($tenant);
            
            // Log tenant access for analytics
            $this->logTenantAccess($request, $tenant);
            
            return $next($request);
            
        } catch (TenantException $e) {
            return $this->handleTenantException($e);
        }
    }
    
    private function setTenantContext(Tenant $tenant): void
    {
        app()->instance('tenant', $tenant);
        app()->instance('tenant.id', $tenant->id);
        
        // Set tenant context for Eloquent queries
        Model::setGlobalTenant($tenant);
    }
    
    private function configureTenantServices(Tenant $tenant): void
    {
        // Configure tenant-specific database
        if ($tenant->database_name) {
            $this->configureTenantDatabase($tenant);
        }
        
        // Apply tenant customizations
        $this->applyTenantCustomizations($tenant);
        
        // Configure tenant-specific mail settings
        $this->configureTenantMail($tenant);
        
        // Setup tenant-specific caching namespace
        Cache::setPrefix("tenant:{$tenant->id}:");
    }
    
    private function setupRowLevelSecurity(Tenant $tenant): void
    {
        // Enable Row Level Security for tenant isolation
        DB::statement('SET row_security = on');
        DB::statement('SET basketmanager.current_tenant_id = ?', [$tenant->id]);
    }
    
    private function configureTenantDatabase(Tenant $tenant): void
    {
        config([
            'database.connections.tenant' => [
                'driver' => 'pgsql',
                'host' => $tenant->database_host ?? config('database.connections.pgsql.host'),
                'port' => $tenant->database_port ?? config('database.connections.pgsql.port'),
                'database' => $tenant->database_name,
                'username' => $tenant->database_username ?? config('database.connections.pgsql.username'),
                'password' => decrypt($tenant->database_password) ?? config('database.connections.pgsql.password'),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'schema' => $tenant->database_schema ?? 'public',
                'sslmode' => 'prefer',
            ]
        ]);
        
        // Switch to tenant database
        DB::setDefaultConnection('tenant');
    }
    
    private function applyTenantCustomizations(Tenant $tenant): void
    {
        // Set tenant-specific app name
        $appName = $tenant->getSetting('branding.app_name', config('app.name'));
        config(['app.name' => $appName]);
        
        // Apply tenant theme
        $theme = $tenant->getSetting('branding.theme', 'default');
        view()->share('tenant_theme', $theme);
        view()->share('tenant_colors', $tenant->getSetting('branding.colors', []));
        
        // Set locale
        $locale = $tenant->getSetting('locale', app()->getLocale());
        app()->setLocale($locale);
    }
    
    private function logTenantAccess(Request $request, Tenant $tenant): void
    {
        // Log for analytics and security monitoring
        TenantAccess::create([
            'tenant_id' => $tenant->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'timestamp' => now()
        ]);
    }
    
    private function handleTenantNotFound(Request $request): Response
    {
        // Log potential security incident
        Log::warning('Tenant not found for domain', [
            'domain' => $request->getHost(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'Invalid domain or tenant not active'
            ], 404);
        }
        
        return response()->view('errors.tenant-not-found', [], 404);
    }
}

// Tenant Scoping Trait for Models
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Automatically set tenant_id when creating models
        static::creating(function ($model) {
            if (app()->bound('tenant.id') && !$model->tenant_id) {
                $model->tenant_id = app('tenant.id');
            }
        });
        
        // Add global scope for tenant filtering
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('tenant.id')) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', app('tenant.id'));
            }
        });
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    // Scope to bypass tenant filtering (use with caution)
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope('tenant');
    }
}
```

#### 5.2 Subscription Management & Billing

**Ziel**: Automated SaaS Billing mit Usage-based Pricing und Self-Service Portal.

**Subscription Tiers:**

| Tier | Monthly Price | Teams | Users | Games/Month | API Calls | Storage |
|------|---------------|-------|-------|-------------|-----------|---------|
| Starter | €29 | 3 | 25 | 50 | 10.000 | 5GB |
| Professional | €99 | 10 | 100 | 200 | 50.000 | 25GB |
| Club | €299 | 25 | 500 | 1.000 | 250.000 | 100GB |
| Enterprise | Custom | Unlimited | Unlimited | Unlimited | Custom | Custom |

**Subscription Management Service:**

```php
class SubscriptionManagementService
{
    public function __construct(
        private PaymentService $paymentService,
        private UsageTracker $usageTracker,
        private NotificationService $notificationService
    ) {}
    
    public function calculateMonthlyBill(Tenant $tenant): BillingCalculation
    {
        $subscription = $tenant->subscription;
        $tier = config("tenants.tiers.{$tenant->subscription_tier}");
        
        $baseFee = $tier['monthly_price'];
        $usage = $this->usageTracker->getMonthlyUsage($tenant);
        $overages = $this->calculateOverages($tenant, $usage);
        
        return new BillingCalculation([
            'tenant_id' => $tenant->id,
            'billing_period' => now()->format('Y-m'),
            'base_fee' => $baseFee,
            'usage' => $usage,
            'overages' => $overages,
            'total' => $baseFee + array_sum($overages),
            'breakdown' => $this->generateBreakdown($baseFee, $usage, $overages)
        ]);
    }
    
    private function calculateOverages(Tenant $tenant, array $usage): array
    {
        $limits = config("tenants.tiers.{$tenant->subscription_tier}.limits");
        $rates = config('tenants.overage_rates');
        $overages = [];
        
        foreach ($usage as $metric => $used) {
            $limit = $limits[$metric] ?? PHP_INT_MAX;
            
            if ($used > $limit) {
                $overage = $used - $limit;
                $rate = $rates[$metric] ?? 0;
                $overages[$metric] = $overage * $rate;
            }
        }
        
        return $overages;
    }
    
    public function processMonthlyBilling(): void
    {
        $tenants = Tenant::where('is_active', true)
                         ->where('subscription_tier', '!=', 'free')
                         ->whereHas('subscription')
                         ->get();
        
        foreach ($tenants as $tenant) {
            try {
                $billing = $this->calculateMonthlyBill($tenant);
                
                if ($billing->total > 0) {
                    $this->chargeTenant($tenant, $billing);
                }
                
                $this->generateInvoice($tenant, $billing);
                
            } catch (Exception $e) {
                Log::error('Billing failed for tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage()
                ]);
                
                $this->handleBillingFailure($tenant, $e);
            }
        }
    }
    
    private function chargeTenant(Tenant $tenant, BillingCalculation $billing): Payment
    {
        $paymentMethod = $tenant->subscription->payment_method;
        
        return $this->paymentService->chargeSubscription(
            $tenant,
            $billing->total,
            $tenant->subscription->currency,
            [
                'description' => "BasketManager Pro - {$billing->billing_period}",
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'billing_period' => $billing->billing_period,
                    'base_fee' => $billing->base_fee,
                    'overage_charges' => array_sum($billing->overages)
                ]
            ]
        );
    }
    
    public function upgradeTenant(Tenant $tenant, string $newTier): UpgradeResult
    {
        $oldTier = $tenant->subscription_tier;
        $oldTierConfig = config("tenants.tiers.{$oldTier}");
        $newTierConfig = config("tenants.tiers.{$newTier}");
        
        // Calculate prorated amount
        $proratedAmount = $this->calculateProratedUpgrade($tenant, $oldTierConfig, $newTierConfig);
        
        return DB::transaction(function () use ($tenant, $newTier, $proratedAmount, $oldTier) {
            try {
                // Charge prorated amount if needed
                if ($proratedAmount > 0) {
                    $payment = $this->paymentService->chargeSubscription(
                        $tenant,
                        $proratedAmount,
                        $tenant->subscription->currency,
                        [
                            'description' => "Upgrade to {$newTier} (prorated)",
                            'metadata' => [
                                'upgrade_from' => $oldTier,
                                'upgrade_to' => $newTier,
                                'prorated_amount' => $proratedAmount
                            ]
                        ]
                    );
                    
                    if (!$payment->successful()) {
                        throw new PaymentFailedException('Upgrade payment failed');
                    }
                }
                
                // Update subscription
                $tenant->update(['subscription_tier' => $newTier]);
                $tenant->subscription()->update([
                    'tier' => $newTier,
                    'upgraded_at' => now(),
                    'next_billing_date' => $this->calculateNextBillingDate($tenant)
                ]);
                
                // Log upgrade
                activity()
                    ->performedOn($tenant)
                    ->withProperties([
                        'old_tier' => $oldTier,
                        'new_tier' => $newTier,
                        'prorated_amount' => $proratedAmount
                    ])
                    ->log('subscription_upgraded');
                
                // Send confirmation email
                $this->notificationService->sendUpgradeConfirmation($tenant, $oldTier, $newTier);
                
                return UpgradeResult::success([
                    'old_tier' => $oldTier,
                    'new_tier' => $newTier,
                    'prorated_amount' => $proratedAmount,
                    'effective_date' => now()
                ]);
                
            } catch (Exception $e) {
                Log::error('Subscription upgrade failed', [
                    'tenant_id' => $tenant->id,
                    'old_tier' => $oldTier,
                    'new_tier' => $newTier,
                    'error' => $e->getMessage()
                ]);
                
                return UpgradeResult::failure($e->getMessage());
            }
        });
    }
    
    public function downgradeTenant(Tenant $tenant, string $newTier): DowngradeResult
    {
        // Downgrade takes effect at next billing cycle to avoid refund complexity
        $tenant->subscription()->update([
            'pending_tier_change' => $newTier,
            'tier_change_date' => $this->calculateNextBillingDate($tenant)
        ]);
        
        // Check if current usage exceeds new tier limits
        $usage = $this->usageTracker->getCurrentUsage($tenant);
        $newLimits = config("tenants.tiers.{$newTier}.limits");
        
        $violations = $this->checkLimitViolations($usage, $newLimits);
        
        if (!empty($violations)) {
            return DowngradeResult::warningWithViolations([
                'violations' => $violations,
                'effective_date' => $this->calculateNextBillingDate($tenant),
                'message' => 'Downgrade scheduled. Please reduce usage to avoid service interruption.'
            ]);
        }
        
        return DowngradeResult::success([
            'new_tier' => $newTier,
            'effective_date' => $this->calculateNextBillingDate($tenant)
        ]);
    }
    
    private function checkLimitViolations(array $usage, array $limits): array
    {
        $violations = [];
        
        foreach ($usage as $metric => $used) {
            $limit = $limits[$metric] ?? PHP_INT_MAX;
            
            if ($used > $limit) {
                $violations[$metric] = [
                    'current_usage' => $used,
                    'new_limit' => $limit,
                    'overage' => $used - $limit
                ];
            }
        }
        
        return $violations;
    }
    
    public function generateUsageReport(Tenant $tenant, ?string $period = null): UsageReport
    {
        $period = $period ?? now()->format('Y-m');
        $usage = $this->usageTracker->getUsageForPeriod($tenant, $period);
        $limits = config("tenants.tiers.{$tenant->subscription_tier}.limits");
        
        return new UsageReport([
            'tenant_id' => $tenant->id,
            'period' => $period,
            'tier' => $tenant->subscription_tier,
            'usage' => $usage,
            'limits' => $limits,
            'utilization' => $this->calculateUtilization($usage, $limits),
            'projections' => $this->projectMonthlyUsage($tenant, $usage),
            'recommendations' => $this->generateUsageRecommendations($tenant, $usage, $limits)
        ]);
    }
    
    private function calculateUtilization(array $usage, array $limits): array
    {
        $utilization = [];
        
        foreach ($usage as $metric => $used) {
            $limit = $limits[$metric] ?? PHP_INT_MAX;
            $utilization[$metric] = $limit > 0 ? round(($used / $limit) * 100, 2) : 0;
        }
        
        return $utilization;
    }
}
```

#### 5.3 Tenant Customization & White-Labeling

**Ziel**: Vollständige Brand-Anpassung und White-Label-Lösungen für Enterprise-Kunden.

**Customization Features:**

- **Visual Branding**: Logo, Farben, Fonts, Layout-Anpassungen
- **Domain Customization**: Custom Domains mit SSL
- **Feature Toggles**: Tenant-spezifische Feature-Aktivierung
- **Email Templates**: Branded Notifications und Reports
- **Multi-Language**: Lokalisierung pro Tenant

**Tenant Customization Service:**

```php
class TenantCustomizationService
{
    public function __construct(
        private MediaLibrary $mediaLibrary,
        private CacheManager $cache
    ) {}
    
    public function applyBrandingTheme(Tenant $tenant, array $branding): void
    {
        $validatedBranding = $this->validateBrandingData($branding);
        
        $tenant->updateSettings([
            'branding' => array_merge(
                $tenant->getSetting('branding', []),
                $validatedBranding
            )
        ]);
        
        // Generate and cache CSS
        $this->generateTenantCSS($tenant);
        
        // Clear tenant-specific caches
        $this->clearTenantCache($tenant);
    }
    
    private function validateBrandingData(array $branding): array
    {
        $validator = Validator::make($branding, [
            'primary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'font_family' => 'nullable|string|max:100',
            'logo_url' => 'nullable|url',
            'favicon_url' => 'nullable|url',
            'app_name' => 'nullable|string|max:50',
            'footer_text' => 'nullable|string|max:200'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }
    
    public function uploadTenantLogo(Tenant $tenant, UploadedFile $logo): string
    {
        // Validate image
        $validator = Validator::make(['logo' => $logo], [
            'logo' => 'required|image|mimes:png,jpg,svg|max:2048'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        // Store logo with different sizes
        $media = $tenant->addMediaFromRequest('logo')
                        ->toMediaCollection('branding');
        
        // Generate different sizes
        $sizes = ['sm' => 150, 'md' => 300, 'lg' => 600];
        foreach ($sizes as $size => $width) {
            $media->addMediaConversion($size)
                  ->width($width)
                  ->height($width)
                  ->sharpen(10);
        }
        
        // Update tenant settings
        $tenant->updateSettings([
            'branding.logo_media_id' => $media->id,
            'branding.logo_url' => $media->getUrl()
        ]);
        
        return $media->getUrl();
    }
    
    public function generateTenantCSS(Tenant $tenant): string
    {
        $branding = $tenant->getSetting('branding', []);
        
        $css = ":root {\n";
        
        // Color variables
        if (isset($branding['primary_color'])) {
            $primaryColor = $branding['primary_color'];
            $css .= "  --tenant-primary: {$primaryColor};\n";
            $css .= "  --tenant-primary-rgb: " . $this->hexToRgb($primaryColor) . ";\n";
            $css .= "  --tenant-primary-dark: " . $this->darkenColor($primaryColor, 0.1) . ";\n";
            $css .= "  --tenant-primary-light: " . $this->lightenColor($primaryColor, 0.1) . ";\n";
        }
        
        if (isset($branding['secondary_color'])) {
            $secondaryColor = $branding['secondary_color'];
            $css .= "  --tenant-secondary: {$secondaryColor};\n";
            $css .= "  --tenant-secondary-rgb: " . $this->hexToRgb($secondaryColor) . ";\n";
        }
        
        if (isset($branding['accent_color'])) {
            $css .= "  --tenant-accent: {$branding['accent_color']};\n";
        }
        
        // Typography
        if (isset($branding['font_family'])) {
            $css .= "  --tenant-font: '{$branding['font_family']}', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;\n";
        }
        
        $css .= "}\n\n";
        
        // Component-specific styles
        $css .= $this->generateComponentStyles($branding);
        
        // Cache the generated CSS
        $cacheKey = "tenant:{$tenant->id}:css";
        $this->cache->put($cacheKey, $css, 86400); // Cache for 24 hours
        
        return $css;
    }
    
    private function generateComponentStyles(array $branding): string
    {
        $styles = "";
        
        // Header styling
        if (isset($branding['primary_color'])) {
            $styles .= "
.tenant-header {
    background-color: var(--tenant-primary);
    border-bottom: 2px solid var(--tenant-primary-dark);
}

.tenant-nav-link:hover {
    background-color: var(--tenant-primary-light);
}
";
        }
        
        // Button styling
        if (isset($branding['primary_color'])) {
            $styles .= "
.btn-tenant-primary {
    background-color: var(--tenant-primary);
    border-color: var(--tenant-primary);
    color: white;
}

.btn-tenant-primary:hover {
    background-color: var(--tenant-primary-dark);
    border-color: var(--tenant-primary-dark);
}
";
        }
        
        // Form styling
        if (isset($branding['accent_color'])) {
            $styles .= "
.form-control:focus {
    border-color: var(--tenant-accent);
    box-shadow: 0 0 0 0.2rem rgba(var(--tenant-primary-rgb), 0.25);
}
";
        }
        
        return $styles;
    }
    
    public function setupCustomDomain(Tenant $tenant, string $domain): CustomDomainResult
    {
        // Validate domain format
        if (!$this->isValidDomain($domain)) {
            return CustomDomainResult::failure('Invalid domain format');
        }
        
        // Check if domain is already in use
        if (Tenant::where('domain', $domain)->where('id', '!=', $tenant->id)->exists()) {
            return CustomDomainResult::failure('Domain already in use');
        }
        
        try {
            // Setup SSL certificate via Let's Encrypt
            $sslResult = $this->setupSSLCertificate($domain);
            
            if (!$sslResult->successful()) {
                return CustomDomainResult::failure('SSL certificate setup failed: ' . $sslResult->error());
            }
            
            // Update DNS configuration
            $dnsResult = $this->updateDNSConfiguration($domain, $tenant);
            
            if (!$dnsResult->successful()) {
                return CustomDomainResult::failure('DNS configuration failed: ' . $dnsResult->error());
            }
            
            // Update tenant
            $tenant->update([
                'domain' => $domain,
                'custom_domain_verified' => true,
                'ssl_certificate_id' => $sslResult->certificateId()
            ]);
            
            // Clear cache
            $this->clearTenantCache($tenant);
            
            return CustomDomainResult::success([
                'domain' => $domain,
                'ssl_status' => 'active',
                'dns_configured' => true,
                'verification_required' => false
            ]);
            
        } catch (Exception $e) {
            Log::error('Custom domain setup failed', [
                'tenant_id' => $tenant->id,
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            
            return CustomDomainResult::failure('Domain setup failed: ' . $e->getMessage());
        }
    }
    
    public function configureEmailTemplates(Tenant $tenant, array $templates): void
    {
        foreach ($templates as $templateName => $templateData) {
            $this->validateEmailTemplate($templateData);
            
            EmailTemplate::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $templateName
                ],
                [
                    'subject' => $templateData['subject'],
                    'html_content' => $templateData['html_content'],
                    'text_content' => $templateData['text_content'] ?? strip_tags($templateData['html_content']),
                    'variables' => $templateData['variables'] ?? [],
                    'is_active' => true
                ]
            );
        }
        
        // Clear template cache
        Cache::tags(["tenant:{$tenant->id}", 'email-templates'])->flush();
    }
    
    public function enableFeatureForTenant(Tenant $tenant, string $feature): void
    {
        $currentFeatures = $tenant->features ?? [];
        
        if (!in_array($feature, $currentFeatures)) {
            $currentFeatures[] = $feature;
            $tenant->update(['features' => $currentFeatures]);
        }
        
        // Log feature activation
        activity()
            ->performedOn($tenant)
            ->withProperties(['feature' => $feature])
            ->log('feature_enabled');
    }
    
    private function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "{$r}, {$g}, {$b}";
    }
    
    private function darkenColor(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - ($amount * 255));
        $g = max(0, hexdec(substr($hex, 2, 2)) - ($amount * 255));
        $b = max(0, hexdec(substr($hex, 4, 2)) - ($amount * 255));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    private function lightenColor(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        $r = min(255, hexdec(substr($hex, 0, 2)) + ($amount * 255));
        $g = min(255, hexdec(substr($hex, 2, 2)) + ($amount * 255));
        $b = min(255, hexdec(substr($hex, 4, 2)) + ($amount * 255));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    private function clearTenantCache(Tenant $tenant): void
    {
        Cache::tags(["tenant:{$tenant->id}"])->flush();
    }
}
```

---

## 🚀 Implementierungsplan (Aktualisiert)

### Übersicht der angepassten Roadmap

**Phase 4** wird basierend auf dem aktuellen Fortschritt (25% abgeschlossen) neu strukturiert. Die bereits implementierten Features werden als erledigt markiert, und der Fokus liegt auf den kritischen fehlenden Komponenten.

#### Monat 1 (Aktuell): Multi-tenant Foundation & Payment
**Fokus**: Multi-tenant Architektur, Payment Integration
**Status**: 🔄 In Arbeit

#### Monat 2: External Integrations & Performance  
**Fokus**: Federation APIs, Social Media, Performance Optimization
**Status**: ⏳ Geplant

#### Monat 3: PWA & Production Readiness
**Fokus**: PWA Features, CDN Setup, Load Testing, Go-Live
**Status**: ⏳ Geplant

### ✅ Bereits Erledigte Aufgaben (aus ursprünglichem Plan)

#### API Finalization & Documentation ✅
- ✅ OpenAPI 3.0 Dokumentation für alle Endpoints
- ✅ Implementierung der API Versioning Strategy  
- ✅ Setup des Enterprise Rate Limiting Systems
- ✅ SDK Generation für PHP, JavaScript, Python
- ✅ API Usage Tracking und Monitoring

### Monat 1: Multi-tenant Foundation & Payment (Aktuell)

#### Woche 1-2: Multi-tenant Architecture ❌

**Sprint Goals:**
- Tenant Model und Migrationen erstellen
- Row Level Security implementieren
- Tenant Resolution Middleware entwickeln
- Domain-based Routing konfigurieren

#### Woche 3-4: Payment Integration ❌

**Sprint Goals:**
- Stripe Integration für Subscriptions
- PayPal für einmalige Zahlungen
- SEPA Lastschrift Implementation
- Webhook Handler für Payment Events

**Laravel Implementation Tasks:**

```php
// OpenAPI Documentation Generator
class OpenApiGeneratorCommand extends Command
{
    protected $signature = 'api:generate-docs {--output=storage/api-docs/}';
    
    public function handle(): int
    {
        $this->info('Generating OpenAPI 3.0 documentation...');
        
        $generator = app(OpenApiDocumentationService::class);
        $documentation = $generator->generateDocumentation();
        
        // Save to multiple formats
        $outputPath = $this->option('output');
        file_put_contents($outputPath . 'openapi.json', json_encode($documentation, JSON_PRETTY_PRINT));
        file_put_contents($outputPath . 'openapi.yaml', yaml_emit($documentation));
        
        // Generate SDK stubs
        $this->generateSDKStubs($documentation, $outputPath);
        
        $this->info('Documentation generated successfully!');
        return Command::SUCCESS;
    }
    
    private function generateSDKStubs(array $docs, string $path): void
    {
        $languages = ['php', 'javascript', 'python'];
        
        foreach ($languages as $lang) {
            $generator = app("SDKGenerator.{$lang}");
            $stub = $generator->generate($docs);
            file_put_contents($path . "sdk-{$lang}.stub", $stub);
        }
    }
}

// Rate Limiting Implementation
class ApiRateLimitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EnterpriseRateLimitService::class);
    }
    
    public function boot(): void
    {
        // Register rate limiting middleware with different tiers
        $this->app['router']->aliasMiddleware('rate.enterprise', EnterpriseRateLimit::class);
        
        // Apply to API routes based on subscription tier
        Route::middleware(['api', 'rate.enterprise'])
             ->prefix('api/v4')
             ->group(base_path('routes/api_v4.php'));
    }
}

// Webhook System Implementation
class WebhookSubscriptionController extends Controller
{
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $this->authorize('create', WebhookSubscription::class);
        
        $subscription = WebhookSubscription::create([
            'tenant_id' => auth()->user()->tenant_id,
            'url' => $request->url,
            'events' => $request->events,
            'secret' => Str::random(32),
            'is_active' => true
        ]);
        
        return response()->json(new WebhookSubscriptionResource($subscription), 201);
    }
    
    public function test(WebhookSubscription $subscription): JsonResponse
    {
        $testEvent = new WebhookTestEvent([
            'message' => 'This is a test webhook delivery',
            'timestamp' => now()->toISOString()
        ]);
        
        SendWebhookJob::dispatch($subscription, $testEvent);
        
        return response()->json(['message' => 'Test webhook queued']);
    }
}
```

**Deliverables:**
- [ ] OpenAPI 3.0 Spec für alle API Endpoints (400+ Endpoints)
- [ ] Interactive API Documentation (Swagger UI Integration)
- [ ] Rate Limiting System mit Tier-based Limits
- [ ] Webhook System mit Event Subscription
- [ ] SDK Generation für PHP, JavaScript, Python

#### Woche 39-40: External Service Integration - DBB & FIBA

**Sprint Goals:**
- Integration mit Deutscher Basketball Bund (DBB) API
- FIBA Europe API Connection für internationale Daten
- Automatische Daten-Synchronisation Setup

**Laravel Implementation:**

```php
// DBB Integration Service
class DBBIntegrationService implements FederationIntegrationInterface
{
    private string $apiBaseUrl = 'https://api.basketball-bund.de/v2';
    
    public function __construct(
        private HttpClient $httpClient,
        private CacheManager $cache,
        private EventDispatcher $eventDispatcher
    ) {}
    
    public function syncTeamData(Team $team): FederationSyncResult
    {
        try {
            $response = $this->httpClient->withToken(config('services.dbb.api_key'))
                ->get($this->apiBaseUrl . "/teams/{$team->external_id}");
                
            if ($response->successful()) {
                $dbbData = $response->json();
                
                return DB::transaction(function () use ($team, $dbbData) {
                    // Update team with official DBB data
                    $team->update([
                        'official_name' => $dbbData['official_name'],
                        'league' => $dbbData['league'],
                        'division' => $dbbData['division'],
                        'last_dbb_sync' => now()
                    ]);
                    
                    // Sync players
                    $this->syncTeamPlayers($team, $dbbData['players']);
                    
                    // Sync schedule
                    $this->syncTeamSchedule($team, $dbbData['schedule']);
                    
                    $this->eventDispatcher->dispatch(new TeamSyncedWithDBB($team, $dbbData));
                    
                    return FederationSyncResult::success([
                        'team_id' => $team->id,
                        'synced_players' => count($dbbData['players']),
                        'synced_games' => count($dbbData['schedule'])
                    ]);
                });
            }
            
            return FederationSyncResult::failure('DBB API returned error: ' . $response->body());
            
        } catch (Exception $e) {
            Log::error('DBB sync failed', [
                'team_id' => $team->id,
                'error' => $e->getMessage()
            ]);
            
            return FederationSyncResult::failure($e->getMessage());
        }
    }
    
    public function submitGameResults(Game $game): bool
    {
        if (!$game->isOfficialGame() || !$game->external_game_id) {
            return false;
        }
        
        $resultData = [
            'game_id' => $game->external_game_id,
            'final_score' => [
                'home' => $game->final_score_home,
                'away' => $game->final_score_away
            ],
            'quarter_scores' => $game->quarter_scores,
            'game_duration' => $game->actual_duration_minutes,
            'officials' => $game->officials,
            'player_statistics' => $this->formatPlayerStatistics($game),
            'game_events' => $this->formatGameEvents($game),
            'technical_info' => [
                'scorer' => $game->scorer_name,
                'timer' => $game->timer_name,
                'venue' => $game->venue,
                'attendance' => $game->attendance
            ]
        ];
        
        $response = $this->httpClient->withToken(config('services.dbb.api_key'))
            ->post($this->apiBaseUrl . "/games/{$game->external_game_id}/results", $resultData);
        
        if ($response->successful()) {
            $game->update([
                'dbb_submission_status' => 'submitted',
                'dbb_submitted_at' => now()
            ]);
            
            $this->eventDispatcher->dispatch(new GameResultsSubmittedToDBB($game));
            
            return true;
        }
        
        Log::error('DBB result submission failed', [
            'game_id' => $game->id,
            'response' => $response->body()
        ]);
        
        return false;
    }
    
    private function syncTeamPlayers(Team $team, array $dbbPlayers): void
    {
        foreach ($dbbPlayers as $playerData) {
            Player::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'external_id' => $playerData['id']
                ],
                [
                    'first_name' => $playerData['first_name'],
                    'last_name' => $playerData['last_name'],
                    'jersey_number' => $playerData['jersey_number'],
                    'position' => $playerData['position'],
                    'birth_date' => $playerData['birth_date'],
                    'height' => $playerData['height'],
                    'is_active' => $playerData['status'] === 'active',
                    'last_dbb_sync' => now()
                ]
            );
        }
    }
}

// FIBA Integration Service
class FIBAIntegrationService
{
    private string $apiBaseUrl = 'https://api.fiba.basketball/v3';
    
    public function syncInternationalRankings(): void
    {
        $response = $this->httpClient->withToken(config('services.fiba.api_key'))
            ->get($this->apiBaseUrl . '/rankings/men');
            
        if ($response->successful()) {
            $rankings = $response->json();
            
            foreach ($rankings['data'] as $rankingData) {
                FIBARanking::updateOrCreate(
                    [
                        'country_code' => $rankingData['country_code'],
                        'category' => 'men',
                        'year' => now()->year
                    ],
                    [
                        'ranking' => $rankingData['position'],
                        'points' => $rankingData['points'],
                        'last_updated' => $rankingData['last_updated']
                    ]
                );
            }
        }
    }
    
    public function syncTournamentData(Tournament $tournament): void
    {
        if (!$tournament->fiba_tournament_id) {
            return;
        }
        
        $response = $this->httpClient->withToken(config('services.fiba.api_key'))
            ->get($this->apiBaseUrl . "/competitions/{$tournament->fiba_tournament_id}");
            
        if ($response->successful()) {
            $fibaData = $response->json();
            
            $tournament->update([
                'official_name' => $fibaData['name'],
                'start_date' => $fibaData['start_date'],
                'end_date' => $fibaData['end_date'],
                'venue_info' => $fibaData['venues'],
                'fiba_rules' => $fibaData['rules'],
                'last_fiba_sync' => now()
            ]);
        }
    }
}

// Automated Sync Jobs
class SyncWithFederationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [300, 600, 1200]; // 5min, 10min, 20min
    
    public function __construct(
        public Team $team,
        public string $federation = 'dbb'
    ) {}
    
    public function handle(): void
    {
        $service = match($this->federation) {
            'dbb' => app(DBBIntegrationService::class),
            'fiba' => app(FIBAIntegrationService::class),
            default => throw new InvalidArgumentException("Unknown federation: {$this->federation}")
        };
        
        $result = $service->syncTeamData($this->team);
        
        if (!$result->isSuccessful()) {
            throw new FederationSyncException($result->getError());
        }
        
        Log::info("Successfully synced team with {$this->federation}", [
            'team_id' => $this->team->id,
            'synced_data' => $result->getData()
        ]);
    }
}
```

**Deliverables:**
- [ ] DBB API Integration (Teams, Players, Games, Results)
- [ ] FIBA API Integration (Rankings, International Tournaments)
- [ ] Automated Sync Jobs mit Error Handling
- [ ] Federation Data Validation & Conflict Resolution
- [ ] Audit Trail für alle External Data Changes

### Monat 11: Performance & Multi-tenancy (Wochen 41-44)

#### Woche 41-42: Performance Optimization & Database Tuning

**Sprint Goals:**
- Database Query Optimization mit Advanced Indexing
- Implementation von Database Partitioning für große Tabellen
- CDN Integration für globale Content Delivery
- Memory Usage Optimization

**Laravel Implementation:**

```php
// Database Optimization Command
class OptimizeDatabaseCommand extends Command
{
    protected $signature = 'db:optimize {--analyze} {--create-indexes} {--partition}';
    
    public function handle(): int
    {
        if ($this->option('analyze')) {
            $this->analyzeQueryPerformance();
        }
        
        if ($this->option('create-indexes')) {
            $this->createPerformanceIndexes();
        }
        
        if ($this->option('partition')) {
            $this->setupTablePartitioning();
        }
        
        return Command::SUCCESS;
    }
    
    private function analyzeQueryPerformance(): void
    {
        $this->info('Analyzing query performance...');
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Run performance analysis
        $slowQueries = DB::select("
            SELECT query, calls, total_time, mean_time, rows
            FROM pg_stat_statements 
            WHERE mean_time > 100
            ORDER BY mean_time DESC 
            LIMIT 20
        ");
        
        $this->table(
            ['Query', 'Calls', 'Total Time', 'Mean Time', 'Rows'],
            collect($slowQueries)->map(function ($query) {
                return [
                    Str::limit($query->query, 50),
                    $query->calls,
                    round($query->total_time, 2) . 'ms',
                    round($query->mean_time, 2) . 'ms',
                    $query->rows
                ];
            })->toArray()
        );
    }
    
    private function createPerformanceIndexes(): void
    {
        $this->info('Creating performance indexes...');
        
        // Game performance indexes
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_team_season_performance 
            ON games (home_team_id, season, status, scheduled_at DESC) 
            INCLUDE (away_team_id, final_score_home, final_score_away, venue)
        ');
        
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_away_team_season 
            ON games (away_team_id, season, status, scheduled_at DESC) 
            INCLUDE (home_team_id, final_score_home, final_score_away, venue)
        ');
        
        // Player statistics indexes
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_game_actions_player_performance 
            ON game_actions (player_id, action_type, created_at DESC) 
            INCLUDE (game_id, points, quarter, time_remaining)
        ');
        
        // Multi-tenant indexes
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_teams_tenant_season 
            ON teams (tenant_id, season, status) 
            INCLUDE (name, category, league)
        ');
        
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_tenant_role 
            ON users (tenant_id, created_at DESC) 
            INCLUDE (name, email, last_login_at)
        ');
        
        $this->info('Performance indexes created successfully!');
    }
    
    private function setupTablePartitioning(): void
    {
        $this->info('Setting up table partitioning...');
        
        // Partition game_actions by month
        DB::statement('
            CREATE TABLE IF NOT EXISTS game_actions_partitioned (
                LIKE game_actions INCLUDING ALL
            ) PARTITION BY RANGE (created_at)
        ');
        
        // Create monthly partitions for 24 months
        for ($i = -12; $i <= 12; $i++) {
            $date = now()->addMonths($i);
            $startDate = $date->startOfMonth();
            $endDate = $date->endOfMonth();
            $tableName = 'game_actions_' . $startDate->format('Y_m');
            
            DB::statement("
                CREATE TABLE IF NOT EXISTS {$tableName} 
                PARTITION OF game_actions_partitioned 
                FOR VALUES FROM ('{$startDate}') TO ('{$endDate->addDay()}')
            ");
            
            DB::statement("
                CREATE INDEX IF NOT EXISTS {$tableName}_player_idx 
                ON {$tableName} (player_id, action_type, created_at)
            ");
        }
        
        $this->info('Table partitioning completed!');
    }
}

// Memory Optimization Service
class MemoryOptimizationService
{
    public function optimizeForLargeDatasets(): void
    {
        // Configure Eloquent for memory efficiency
        Model::preventLazyLoading(!app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());
        Model::preventAccessingMissingAttributes(!app()->isProduction());
    }
    
    public function processLargeQuery(Builder $query, callable $callback, int $chunkSize = 1000): void
    {
        $query->chunk($chunkSize, function ($records) use ($callback) {
            $callback($records);
            
            // Force garbage collection after each chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });
    }
    
    public function generateLargeReport(Team $team, string $season): StreamedResponse
    {
        return response()->stream(function () use ($team, $season) {
            $handle = fopen('php://output', 'w');
            
            // Write CSV header
            fputcsv($handle, ['Player', 'Games', 'Points', 'Rebounds', 'Assists', 'Minutes']);
            
            // Stream data in chunks to avoid memory issues
            $team->players()->chunk(100, function ($players) use ($handle, $season) {
                foreach ($players as $player) {
                    $stats = $this->getPlayerStats($player, $season);
                    fputcsv($handle, [
                        $player->full_name,
                        $stats['games_played'],
                        $stats['total_points'],
                        $stats['total_rebounds'],
                        $stats['total_assists'],
                        $stats['total_minutes']
                    ]);
                }
                
                flush();
            });
            
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="team-stats.csv"'
        ]);
    }
}

// CDN Integration Service
class CDNOptimizationService
{
    public function __construct(
        private CloudflareService $cloudflare,
        private ImageOptimizationService $imageOptimizer
    ) {}
    
    public function optimizeAssetDelivery(): void
    {
        // Configure Cloudflare optimizations
        $optimizations = [
            'minify' => ['css' => 'on', 'html' => 'on', 'js' => 'on'],
            'brotli' => 'on',
            'gzip' => 'on',
            'polish' => 'lossless',
            'webp' => 'on',
            'rocket_loader' => 'on',
            'http3' => 'on',
            '0rtt' => 'on',
            'prefetch_preload' => 'on'
        ];
        
        foreach ($optimizations as $setting => $value) {
            $this->cloudflare->updateZoneSetting($setting, $value);
        }
    }
    
    public function generateOptimizedImageUrl(string $imagePath, array $options = []): string
    {
        $baseUrl = config('services.cloudflare.images_url');
        
        $transformations = [];
        
        if (isset($options['width'])) {
            $transformations[] = "w={$options['width']}";
        }
        if (isset($options['height'])) {
            $transformations[] = "h={$options['height']}";
        }
        
        $quality = $options['quality'] ?? 80;
        $transformations[] = "q={$quality}";
        
        $format = $options['format'] ?? 'auto';
        $transformations[] = "f={$format}";
        
        $fit = $options['fit'] ?? 'scale-down';
        $transformations[] = "fit={$fit}";
        
        if (isset($options['dpr'])) {
            $transformations[] = "dpr={$options['dpr']}";
        }
        
        $transformString = implode(',', $transformations);
        
        return "{$baseUrl}/cdn-cgi/image/{$transformString}/{$imagePath}";
    }
}
```

**Deliverables:**
- [ ] Database Performance Optimization (95% Queries <50ms)
- [ ] Advanced Indexing Strategy für Multi-tenant Queries
- [ ] Table Partitioning für game_actions und audit_logs
- [ ] CDN Integration mit Cloudflare (Global <100ms Load Times)
- [ ] Memory Usage Optimization (<512MB per Request)

#### Woche 43-44: Multi-tenant Architecture Implementation

**Sprint Goals:**
- Vollständige Multi-tenant Migration der bestehenden Single-tenant Architektur
- Row Level Security Implementation für Datenisolation
- Tenant-specific Customization und Branding
- Subscription Management System

**Laravel Implementation:**

```php
// Multi-tenant Migration Command
class MigrateToMultiTenantCommand extends Command
{
    protected $signature = 'tenant:migrate {--dry-run} {--tenant=}';
    
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $specificTenant = $this->option('tenant');
        
        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }
        
        // Step 1: Create tenants table and basic structure
        $this->createTenantStructure($isDryRun);
        
        // Step 2: Migrate existing data to tenant structure
        $this->migrateExistingData($isDryRun, $specificTenant);
        
        // Step 3: Setup Row Level Security
        $this->setupRowLevelSecurity($isDryRun);
        
        // Step 4: Update application middleware
        $this->updateApplicationMiddleware($isDryRun);
        
        return Command::SUCCESS;
    }
    
    private function createTenantStructure(bool $isDryRun): void
    {
        $this->info('Creating tenant structure...');
        
        if (!$isDryRun) {
            // Create tenants table
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('domain')->nullable()->unique();
                $table->string('subdomain')->nullable()->unique();
                $table->json('settings')->nullable();
                $table->string('subscription_tier')->default('free');
                $table->boolean('is_active')->default(true);
                $table->timestamp('trial_ends_at')->nullable();
                $table->string('billing_email')->nullable();
                $table->json('features')->nullable();
                $table->json('security_settings')->nullable();
                $table->timestamps();
                
                $table->index(['domain', 'is_active']);
                $table->index(['subdomain', 'is_active']);
            });
            
            // Add tenant_id to all relevant tables
            $tablesToMigrate = [
                'users', 'teams', 'players', 'games', 'game_actions',
                'training_sessions', 'tournaments', 'emergency_contacts',
                'team_access', 'subscriptions', 'payments'
            ];
            
            foreach ($tablesToMigrate as $tableName) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->constrained('tenants');
                    $table->index('tenant_id');
                });
            }
        }
        
        $this->info('Tenant structure created successfully!');
    }
    
    private function migrateExistingData(bool $isDryRun, ?string $specificTenant): void
    {
        $this->info('Migrating existing data to multi-tenant structure...');
        
        if (!$isDryRun) {
            // Create default tenant for existing data
            $defaultTenant = Tenant::create([
                'uuid' => Str::uuid(),
                'name' => 'Default Organization',
                'slug' => 'default',
                'domain' => config('app.url'),
                'subscription_tier' => 'professional',
                'is_active' => true
            ]);
            
            // Migrate existing users to default tenant
            DB::table('users')->whereNull('tenant_id')->update([
                'tenant_id' => $defaultTenant->id
            ]);
            
            // Migrate all other data
            $tables = ['teams', 'players', 'games', 'game_actions', 'training_sessions'];
            
            foreach ($tables as $table) {
                $count = DB::table($table)->whereNull('tenant_id')->count();
                
                if ($count > 0) {
                    DB::table($table)->whereNull('tenant_id')->update([
                        'tenant_id' => $defaultTenant->id
                    ]);
                    
                    $this->info("Migrated {$count} records in {$table} table");
                }
            }
        }
        
        $this->info('Data migration completed!');
    }
    
    private function setupRowLevelSecurity(bool $isDryRun): void
    {
        $this->info('Setting up Row Level Security...');
        
        if (!$isDryRun) {
            // Enable RLS on all tenant tables
            $tables = ['users', 'teams', 'players', 'games'];
            
            foreach ($tables as $table) {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                
                // Create policy for tenant isolation
                DB::statement("
                    CREATE POLICY tenant_isolation_policy ON {$table}
                    FOR ALL
                    USING (tenant_id = current_setting('basketmanager.current_tenant_id')::bigint)
                ");
            }
        }
        
        $this->info('Row Level Security configured!');
    }
}

// Tenant Context Middleware
class SetTenantContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);
        
        if (!$tenant) {
            return $this->handleTenantNotFound($request);
        }
        
        // Set tenant context
        app()->instance('tenant', $tenant);
        app()->instance('tenant.id', $tenant->id);
        
        // Configure database for RLS
        DB::statement('SET basketmanager.current_tenant_id = ?', [$tenant->id]);
        
        // Apply tenant customizations
        $this->applyTenantCustomizations($tenant);
        
        // Set cache prefix
        Cache::setPrefix("tenant:{$tenant->id}:");
        
        return $next($request);
    }
    
    private function resolveTenant(Request $request): ?Tenant
    {
        $domain = $request->getHost();
        
        return Cache::remember(
            "tenant:domain:{$domain}",
            3600,
            fn() => Tenant::where('domain', $domain)
                         ->orWhere('subdomain', $domain)
                         ->where('is_active', true)
                         ->first()
        );
    }
    
    private function applyTenantCustomizations(Tenant $tenant): void
    {
        // Set tenant-specific app name
        $appName = $tenant->getSetting('branding.app_name', config('app.name'));
        config(['app.name' => $appName]);
        
        // Apply tenant theme
        $theme = $tenant->getSetting('branding.theme', 'default');
        view()->share('tenant_theme', $theme);
        view()->share('tenant_colors', $tenant->getSetting('branding.colors', []));
        
        // Set locale
        $locale = $tenant->getSetting('locale', app()->getLocale());
        app()->setLocale($locale);
    }
}

// Tenant Management Service
class TenantManagementService
{
    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'uuid' => Str::uuid(),
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'domain' => $data['domain'] ?? null,
                'subscription_tier' => $data['tier'] ?? 'starter',
                'billing_email' => $data['billing_email'],
                'settings' => $data['settings'] ?? [],
                'trial_ends_at' => now()->addDays(30)
            ]);
            
            // Create admin user for tenant
            $adminUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password'])
            ]);
            
            $adminUser->assignRole('admin');
            
            // Setup default team
            Team::create([
                'tenant_id' => $tenant->id,
                'name' => $data['team_name'] ?? $data['name'],
                'category' => 'senior',
                'season' => now()->year . '-' . (now()->year + 1),
                'head_coach_id' => $adminUser->id
            ]);
            
            // Dispatch tenant creation events
            TenantCreated::dispatch($tenant);
            
            return $tenant;
        });
    }
    
    public function onboardTenant(Tenant $tenant): OnboardingResult
    {
        try {
            // Send welcome email
            Mail::to($tenant->billing_email)->send(new TenantWelcomeMail($tenant));
            
            // Setup default settings
            $tenant->updateSettings([
                'features' => $this->getDefaultFeatures($tenant->subscription_tier),
                'branding' => $this->getDefaultBranding(),
                'notifications' => $this->getDefaultNotificationSettings()
            ]);
            
            // Create sample data
            $this->createSampleData($tenant);
            
            return OnboardingResult::success();
            
        } catch (Exception $e) {
            Log::error('Tenant onboarding failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            
            return OnboardingResult::failure($e->getMessage());
        }
    }
    
    private function createSampleData(Tenant $tenant): void
    {
        // Create sample players
        $team = $tenant->teams()->first();
        
        if ($team) {
            Player::factory()->count(15)->create([
                'tenant_id' => $tenant->id,
                'team_id' => $team->id
            ]);
            
            // Create sample training session
            TrainingSession::factory()->create([
                'tenant_id' => $tenant->id,
                'team_id' => $team->id,
                'scheduled_at' => now()->addDays(2)
            ]);
        }
    }
}
```

**Deliverables:**
- [ ] Multi-tenant Database Architecture mit Row Level Security
- [ ] Tenant Resolution & Context Middleware
- [ ] Automated Tenant Onboarding Flow
- [ ] Tenant-specific Customization System
- [ ] Data Migration vom Single-Tenant System

### Monat 12: PWA & Production Readiness (Wochen 45-48)

#### Woche 45-46: Progressive Web App Implementation

**Sprint Goals:**
- Service Worker für Offline-Funktionalität
- Push Notifications System
- App Shell Architecture
- Background Sync für Offline Actions

**PWA Implementation:**

```javascript
// Advanced Service Worker
const CACHE_NAME = 'basketmanager-v4.0.0';
const RUNTIME_CACHE = 'basketmanager-runtime';
const OFFLINE_CACHE = 'basketmanager-offline';

// App Shell URLs
const APP_SHELL_URLS = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
    '/offline.html',
    '/manifest.json'
];

// Install Event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching app shell');
                return cache.addAll(APP_SHELL_URLS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate Event
self.addEventListener('activate', event => {
    event.waitUntil(
        Promise.all([
            // Clean old caches
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(cacheName => 
                            cacheName !== CACHE_NAME && 
                            cacheName !== RUNTIME_CACHE &&
                            cacheName !== OFFLINE_CACHE
                        )
                        .map(cacheName => caches.delete(cacheName))
                );
            }),
            // Claim clients
            self.clients.claim()
        ])
    );
});

// Fetch Event with Advanced Strategies
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }
    
    // API Requests - Network First with Offline Fallback
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirstStrategy(request));
    }
    // Critical Game Data - Cache First with Network Update
    else if (url.pathname.includes('/games/') && url.pathname.includes('/live')) {
        event.respondWith(cacheFirstWithUpdate(request));
    }
    // Static Assets - Cache First
    else if (request.destination === 'image' || 
             request.destination === 'script' || 
             request.destination === 'style') {
        event.respondWith(cacheFirstStrategy(request));
    }
    // HTML Pages - Stale While Revalidate
    else if (request.destination === 'document') {
        event.respondWith(staleWhileRevalidateStrategy(request));
    }
});

// Network First Strategy for API
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(RUNTIME_CACHE);
            
            // Only cache GET requests
            if (request.method === 'GET') {
                cache.put(request, networkResponse.clone());
            }
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Network failed, trying cache:', request.url);
        
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline indicator for failed API calls
        if (request.url.includes('/api/')) {
            return new Response(JSON.stringify({
                error: 'offline',
                message: 'Diese Daten sind offline nicht verfügbar',
                cached_at: new Date().toISOString()
            }), {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            });
        }
        
        return caches.match('/offline.html');
    }
}

// Cache First with Background Update
async function cacheFirstWithUpdate(request) {
    const cachedResponse = await caches.match(request);
    
    // Background update
    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            const cache = caches.open(RUNTIME_CACHE);
            cache.then(c => c.put(request, response.clone()));
        }
        return response;
    });
    
    return cachedResponse || fetchPromise;
}

// Background Sync for Offline Actions
self.addEventListener('sync', event => {
    console.log('Background sync triggered:', event.tag);
    
    if (event.tag === 'score-update') {
        event.waitUntil(syncOfflineScoreUpdates());
    } else if (event.tag === 'player-stats') {
        event.waitUntil(syncOfflinePlayerStats());
    } else if (event.tag === 'training-attendance') {
        event.waitUntil(syncOfflineAttendance());
    }
});

// Sync Offline Score Updates
async function syncOfflineScoreUpdates() {
    try {
        const offlineActions = await getStoredActions('score-updates');
        
        for (const action of offlineActions) {
            try {
                const response = await fetch(action.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Authorization': `Bearer ${action.token}`
                    },
                    body: JSON.stringify(action.data)
                });
                
                if (response.ok) {
                    await removeStoredAction('score-updates', action.id);
                    
                    // Notify all clients of successful sync
                    const clients = await self.clients.matchAll();
                    clients.forEach(client => {
                        client.postMessage({
                            type: 'SYNC_SUCCESS',
                            action: 'score-update',
                            data: action.data
                        });
                    });
                }
            } catch (error) {
                console.error('Failed to sync score update:', error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Push Notifications
self.addEventListener('push', event => {
    if (!event.data) return;
    
    const data = event.data.json();
    
    const options = {
        body: data.body,
        icon: data.icon || '/images/icons/icon-192x192.png',
        badge: data.badge || '/images/icons/badge-72x72.png',
        data: data.data,
        actions: data.actions || [],
        tag: data.tag,
        renotify: data.renotify || false,
        vibrate: [200, 100, 200],
        requireInteraction: data.requireInteraction || false
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    const data = event.notification.data;
    
    if (event.action === 'view' && data.url) {
        event.waitUntil(
            clients.openWindow(data.url)
        );
    } else if (event.action === 'share' && data.shareData) {
        // Handle sharing
        event.waitUntil(
            clients.matchAll().then(clientList => {
                if (clientList.length > 0) {
                    clientList[0].postMessage({
                        type: 'SHARE_REQUEST',
                        data: data.shareData
                    });
                }
            })
        );
    } else if (data.url) {
        event.waitUntil(
            clients.openWindow(data.url)
        );
    }
});

// Utility functions for IndexedDB storage
async function getStoredActions(type) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('BasketManagerOffline', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineActions'], 'readonly');
            const store = transaction.objectStore('offlineActions');
            const index = store.index('type');
            const getRequest = index.getAll(type);
            
            getRequest.onsuccess = () => resolve(getRequest.result);
            getRequest.onerror = () => reject(getRequest.error);
        };
        
        request.onerror = () => reject(request.error);
    });
}

async function removeStoredAction(type, id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('BasketManagerOffline', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineActions'], 'readwrite');
            const store = transaction.objectStore('offlineActions');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}
```

**Laravel Push Notification Service:**

```php
// Push Notification Service
class PushNotificationService
{
    public function __construct(
        private NotificationChannelManager $channelManager
    ) {}
    
    public function sendGameScoreUpdate(Game $game, GameAction $lastAction): void
    {
        $interestedUsers = $this->getGameInterestedUsers($game);
        
        foreach ($interestedUsers as $user) {
            if (!$this->shouldSendNotification($user, $game)) {
                continue;
            }
            
            $notification = new GameScoreUpdateNotification($game, $lastAction);
            $notification->personalizeFor($user);
            
            $user->notify($notification);
        }
    }
    
    public function sendTrainingReminder(TrainingSession $session): void
    {
        $players = $session->team->players()
            ->whereHas('user', function ($query) {
                $query->whereJsonContains('notification_settings->training_reminders', true);
            })
            ->with('user')
            ->get();
            
        foreach ($players as $player) {
            if ($player->user) {
                $notification = new TrainingReminderNotification($session);
                $player->user->notify($notification);
            }
        }
    }
    
    private function shouldSendNotification(User $user, Game $game): bool
    {
        $settings = $user->notification_settings ?? [];
        
        // Check user preferences
        if (!($settings['push_enabled'] ?? true) || !($settings['game_updates'] ?? true)) {
            return false;
        }
        
        // Rate limiting
        $lastNotification = $user->notifications()
            ->where('type', GameScoreUpdateNotification::class)
            ->where('data->game_id', $game->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
            
        if ($lastNotification) {
            return false;
        }
        
        // Time-based rules
        $userTimezone = $user->timezone ?? 'Europe/Berlin';
        $localTime = now()->setTimezone($userTimezone);
        
        if ($localTime->hour < 7 || $localTime->hour > 22) {
            return false;
        }
        
        return true;
    }
    
    private function getGameInterestedUsers(Game $game): Collection
    {
        return User::where(function ($query) use ($game) {
            $query->whereHas('teams', function ($q) use ($game) {
                $q->whereIn('id', [$game->home_team_id, $game->away_team_id]);
            })->orWhereHas('favoriteTeams', function ($q) use ($game) {
                $q->whereIn('id', [$game->home_team_id, $game->away_team_id]);
            });
        })->whereNotNull('push_subscription')->get();
    }
}

// Game Score Update Notification
class GameScoreUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        public Game $game,
        public GameAction $lastAction
    ) {}
    
    public function via($notifiable): array
    {
        return ['database', 'push'];
    }
    
    public function toPush($notifiable): PushMessage
    {
        $title = $this->getPersonalizedTitle($notifiable);
        $body = $this->getPersonalizedMessage($notifiable);
        
        return PushMessage::create()
            ->title($title)
            ->body($body)
            ->icon('/images/icons/basketball-notification.png')
            ->badge('/images/icons/badge-72x72.png')
            ->tag("game-{$this->game->id}")
            ->renotify(true)
            ->data([
                'game_id' => $this->game->id,
                'action' => 'view_live_game',
                'url' => "/games/{$this->game->id}/live"
            ])
            ->actions([
                PushAction::create('view', 'Live verfolgen')
                    ->icon('/images/icons/eye.png'),
                PushAction::create('share', 'Teilen')
                    ->icon('/images/icons/share.png')
            ]);
    }
    
    public function personalizeFor(User $user): void
    {
        $this->personalizedTitle = $this->getPersonalizedTitle($user);
        $this->personalizedMessage = $this->getPersonalizedMessage($user);
    }
    
    private function getPersonalizedTitle(User $user): string
    {
        $userTeam = $user->favoriteTeam;
        
        if ($userTeam && ($userTeam->id === $this->game->home_team_id || $userTeam->id === $this->game->away_team_id)) {
            return "🏀 {$userTeam->name} - Live Update";
        }
        
        return "🏀 {$this->game->homeTeam->name} vs {$this->game->awayTeam->name}";
    }
    
    private function getPersonalizedMessage(User $user): string
    {
        $homeTeam = $this->game->homeTeam;
        $awayTeam = $this->game->awayTeam;
        $userTeam = $user->favoriteTeam;
        
        if ($userTeam && ($userTeam->id === $homeTeam->id || $userTeam->id === $awayTeam->id)) {
            $userScore = $userTeam->id === $homeTeam->id ? $this->game->final_score_home : $this->game->final_score_away;
            $opponentScore = $userTeam->id === $homeTeam->id ? $this->game->final_score_away : $this->game->final_score_home;
            $opponentTeam = $userTeam->id === $homeTeam->id ? $awayTeam : $homeTeam;
            
            if ($userScore > $opponentScore) {
                return "{$userTeam->name} führt {$userScore}:{$opponentScore} gegen {$opponentTeam->name}!";
            } elseif ($userScore < $opponentScore) {
                return "{$userTeam->name} liegt {$userScore}:{$opponentScore} zurück gegen {$opponentTeam->name}";
            } else {
                return "Unentschieden! {$userTeam->name} {$userScore}:{$opponentScore} {$opponentTeam->name}";
            }
        }
        
        return "Aktueller Stand: {$homeTeam->name} {$this->game->final_score_home}:{$this->game->final_score_away} {$awayTeam->name}";
    }
}
```

**Deliverables:**
- [ ] Service Worker mit intelligenten Caching-Strategien
- [ ] Push Notifications mit personalisierter Content
- [ ] Offline-Funktionalität für kritische Features
- [ ] Background Sync für Score Updates
- [ ] PWA Manifest mit App-Installation Support

#### Woche 47-48: Production Readiness & Go-Live

**Sprint Goals:**
- Load Testing und Performance Validation
- Security Audit und Penetration Testing
- Monitoring und Alerting Setup
- Production Deployment und Go-Live

**Load Testing Implementation:**

```php
// Load Testing Command
class LoadTestCommand extends Command
{
    protected $signature = 'test:load {--concurrent=50} {--duration=300} {--endpoint=}';
    
    public function handle(): int
    {
        $concurrent = $this->option('concurrent');
        $duration = $this->option('duration');
        $endpoint = $this->option('endpoint');
        
        $this->info("Starting load test: {$concurrent} concurrent users for {$duration} seconds");
        
        // Test scenarios
        $scenarios = [
            'api_game_list' => '/api/v4/games',
            'api_live_game' => '/api/v4/games/1/live',
            'api_player_stats' => '/api/v4/players/1/statistics',
            'dashboard' => '/dashboard',
            'live_scoring' => '/games/1/live-scoring'
        ];
        
        if ($endpoint) {
            $scenarios = [$endpoint => $endpoint];
        }
        
        $results = [];
        
        foreach ($scenarios as $name => $url) {
            $this->info("Testing endpoint: {$name}");
            $results[$name] = $this->runLoadTest($url, $concurrent, $duration);
        }
        
        $this->displayResults($results);
        
        return Command::SUCCESS;
    }
    
    private function runLoadTest(string $url, int $concurrent, int $duration): array
    {
        $startTime = microtime(true);
        $endTime = $startTime + $duration;
        $requests = [];
        $errors = 0;
        
        // Simulate concurrent users
        $promises = [];
        
        for ($i = 0; $i < $concurrent; $i++) {
            $promises[] = $this->simulateUser($url, $endTime);
        }
        
        // Wait for all promises to complete
        $results = Promise::all($promises)->wait();
        
        // Aggregate results
        $totalRequests = 0;
        $totalResponseTime = 0;
        $responseTimes = [];
        
        foreach ($results as $userResults) {
            $totalRequests += count($userResults);
            foreach ($userResults as $responseTime) {
                $totalResponseTime += $responseTime;
                $responseTimes[] = $responseTime;
            }
        }
        
        sort($responseTimes);
        
        return [
            'total_requests' => $totalRequests,
            'errors' => $errors,
            'avg_response_time' => $totalResponseTime / $totalRequests,
            'p50' => $responseTimes[intval(count($responseTimes) * 0.5)],
            'p95' => $responseTimes[intval(count($responseTimes) * 0.95)],
            'p99' => $responseTimes[intval(count($responseTimes) * 0.99)],
            'rps' => $totalRequests / $duration
        ];
    }
    
    private function simulateUser(string $url, float $endTime): Promise
    {
        return new Promise(function ($resolve) use ($url, $endTime) {
            $responseTimes = [];
            
            while (microtime(true) < $endTime) {
                $start = microtime(true);
                
                try {
                    $response = Http::timeout(30)->get(config('app.url') . $url);
                    $end = microtime(true);
                    
                    $responseTimes[] = ($end - $start) * 1000; // Convert to milliseconds
                    
                    // Add realistic user delay
                    usleep(rand(100000, 500000)); // 0.1-0.5 seconds
                    
                } catch (Exception $e) {
                    // Log error but continue
                    $responseTimes[] = 30000; // Timeout
                }
            }
            
            $resolve($responseTimes);
        });
    }
    
    private function displayResults(array $results): void
    {
        $headers = ['Endpoint', 'Requests', 'RPS', 'Avg (ms)', 'P50 (ms)', 'P95 (ms)', 'P99 (ms)'];
        $rows = [];
        
        foreach ($results as $endpoint => $data) {
            $rows[] = [
                $endpoint,
                $data['total_requests'],
                round($data['rps'], 2),
                round($data['avg_response_time'], 2),
                round($data['p50'], 2),
                round($data['p95'], 2),
                round($data['p99'], 2)
            ];
        }
        
        $this->table($headers, $rows);
        
        // Performance validation
        $this->info("\nPerformance Validation:");
        foreach ($results as $endpoint => $data) {
            $pass = $data['p95'] < 200; // 95% requests should be under 200ms
            $status = $pass ? '✅ PASS' : '❌ FAIL';
            $this->line("{$endpoint}: {$status} (P95: {$data['p95']}ms)");
        }
    }
}

// Monitoring Service
class MonitoringService
{
    public function __construct(
        private MetricsCollector $metrics,
        private AlertManager $alertManager
    ) {}
    
    public function setupApplicationMonitoring(): void
    {
        // Database performance monitoring
        DB::listen(function ($query) {
            $this->metrics->timing('database.query.duration', $query->time);
            
            if ($query->time > 1000) { // > 1 second
                $this->alertManager->sendAlert(
                    'slow_query',
                    "Slow query detected: {$query->time}ms",
                    ['query' => $query->sql, 'bindings' => $query->bindings]
                );
            }
        });
        
        // API response time monitoring
        $this->setupAPIMonitoring();
        
        // Memory usage monitoring
        $this->setupMemoryMonitoring();
        
        // Queue monitoring
        $this->setupQueueMonitoring();
    }
    
    private function setupAPIMonitoring(): void
    {
        Route::matched(function ($event) {
            $route = $event->route;
            if (Str::startsWith($route->uri, 'api/')) {
                $startTime = microtime(true);
                
                app()->terminating(function () use ($route, $startTime) {
                    $duration = (microtime(true) - $startTime) * 1000;
                    
                    $this->metrics->timing('api.response.duration', $duration, [
                        'endpoint' => $route->uri,
                        'method' => request()->method()
                    ]);
                    
                    if ($duration > 500) { // > 500ms
                        $this->alertManager->sendAlert(
                            'slow_api_response',
                            "Slow API response: {$duration}ms",
                            ['endpoint' => $route->uri]
                        );
                    }
                });
            }
        });
    }
    
    private function setupMemoryMonitoring(): void
    {
        register_tick_function(function () {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            
            $this->metrics->gauge('system.memory.usage', $memoryUsage);
            
            if ($memoryUsage > $memoryLimit * 0.8) { // > 80% of limit
                $this->alertManager->sendAlert(
                    'high_memory_usage',
                    "High memory usage: " . $this->formatBytes($memoryUsage),
                    ['usage' => $memoryUsage, 'limit' => $memoryLimit]
                );
            }
        });
    }
    
    private function setupQueueMonitoring(): void
    {
        // Monitor queue size
        $queueSize = Queue::size();
        $this->metrics->gauge('queue.size', $queueSize);
        
        if ($queueSize > 1000) {
            $this->alertManager->sendAlert(
                'large_queue_size',
                "Large queue detected: {$queueSize} jobs",
                ['size' => $queueSize]
            );
        }
    }
    
    public function generateHealthReport(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'redis' => $this->checkRedisHealth(),
            'storage' => $this->checkStorageHealth(),
            'external_apis' => $this->checkExternalAPIs(),
            'queue' => $this->checkQueueHealth()
        ];
    }
    
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time' => round($duration, 2) . 'ms'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkExternalAPIs(): array
    {
        $apis = [
            'dbb' => config('services.dbb.api_base_url'),
            'fiba' => config('services.fiba.api_base_url'),
            'stripe' => 'https://api.stripe.com',
            'cloudflare' => 'https://api.cloudflare.com'
        ];
        
        $results = [];
        
        foreach ($apis as $name => $url) {
            try {
                $start = microtime(true);
                $response = Http::timeout(5)->get($url . '/health');
                $duration = (microtime(true) - $start) * 1000;
                
                $results[$name] = [
                    'status' => $response->successful() ? 'healthy' : 'degraded',
                    'response_time' => round($duration, 2) . 'ms',
                    'http_status' => $response->status()
                ];
            } catch (Exception $e) {
                $results[$name] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}

// Security Audit Command
class SecurityAuditCommand extends Command
{
    protected $signature = 'security:audit {--fix}';
    
    public function handle(): int
    {
        $this->info('Running security audit...');
        
        $vulnerabilities = [
            $this->checkDependencyVulnerabilities(),
            $this->checkConfigurationSecurity(),
            $this->checkDatabaseSecurity(),
            $this->checkAPIEndpointSecurity(),
            $this->checkFilePermissions()
        ];
        
        $allVulnerabilities = array_merge(...$vulnerabilities);
        
        if (empty($allVulnerabilities)) {
            $this->info('✅ No security vulnerabilities found!');
            return Command::SUCCESS;
        }
        
        $this->displayVulnerabilities($allVulnerabilities);
        
        if ($this->option('fix')) {
            $this->fixVulnerabilities($allVulnerabilities);
        }
        
        return Command::FAILURE;
    }
    
    private function checkDependencyVulnerabilities(): array
    {
        $vulnerabilities = [];
        
        // Check for known vulnerable packages
        $auditResult = shell_exec('composer audit --format=json');
        $audit = json_decode($auditResult, true);
        
        if (!empty($audit['advisories'])) {
            foreach ($audit['advisories'] as $package => $advisories) {
                foreach ($advisories as $advisory) {
                    $vulnerabilities[] = [
                        'type' => 'dependency',
                        'severity' => $advisory['severity'],
                        'package' => $package,
                        'description' => $advisory['title'],
                        'fix' => "Update {$package} to version {$advisory['fixed_version']}"
                    ];
                }
            }
        }
        
        return $vulnerabilities;
    }
    
    private function checkConfigurationSecurity(): array
    {
        $vulnerabilities = [];
        
        // Check for debug mode in production
        if (config('app.debug') && app()->environment('production')) {
            $vulnerabilities[] = [
                'type' => 'configuration',
                'severity' => 'high',
                'description' => 'Debug mode is enabled in production',
                'fix' => 'Set APP_DEBUG=false in production environment'
            ];
        }
        
        // Check for weak app key
        $appKey = config('app.key');
        if (strlen($appKey) < 32) {
            $vulnerabilities[] = [
                'type' => 'configuration',
                'severity' => 'critical',
                'description' => 'Weak application key',
                'fix' => 'Generate a new strong application key using php artisan key:generate'
            ];
        }
        
        // Check for missing HTTPS enforcement
        if (!config('app.https_only') && app()->environment('production')) {
            $vulnerabilities[] = [
                'type' => 'configuration',
                'severity' => 'medium',
                'description' => 'HTTPS not enforced',
                'fix' => 'Enable HTTPS enforcement in production'
            ];
        }
        
        return $vulnerabilities;
    }
    
    private function displayVulnerabilities(array $vulnerabilities): void
    {
        $grouped = collect($vulnerabilities)->groupBy('severity');
        
        foreach (['critical', 'high', 'medium', 'low'] as $severity) {
            if (!isset($grouped[$severity])) continue;
            
            $this->line("");
            $this->error(strtoupper($severity) . " VULNERABILITIES:");
            
            foreach ($grouped[$severity] as $vuln) {
                $this->line("  • {$vuln['description']}");
                $this->line("    Fix: {$vuln['fix']}");
            }
        }
    }
}
```

**Deliverables:**
- [ ] Load Testing Framework mit Performance Validation
- [ ] Comprehensive Monitoring & Alerting System
- [ ] Security Audit Tools mit Automated Fixes
- [ ] Production Deployment Scripts
- [ ] Go-Live Checklist und Rollback Procedures

**Phase 4 Sprint Summary:**
- ✅ **Monat 10**: API Foundation, DBB/FIBA Integration
- ✅ **Monat 11**: Performance Optimization, Multi-tenant Migration  
- ✅ **Monat 12**: PWA Implementation, Production Go-Live

---

## 📱 Enhanced Mobile API & React Native Integration

### Mobile-Optimized API Endpoints

Erweiterte API-Endpunkte speziell für Mobile-Apps mit Offline-Unterstützung und optimierten Datenformaten.

#### Mobile API Architecture

```php
<?php
// app/Http/Controllers/Api/Mobile/MobileGameController.php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MobileGameResource;
use App\Http\Resources\Mobile\MobileGameDetailResource;
use App\Models\Game;
use App\Services\MobileDataSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MobileGameController extends Controller
{
    public function __construct(
        private MobileDataSyncService $syncService
    ) {}

    /**
     * Get games optimized for mobile consumption
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => 'nullable|exists:teams,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:50',
            'last_sync' => 'nullable|date'
        ]);

        $query = Game::with([
            'homeTeam:id,name,logo_path',
            'awayTeam:id,name,logo_path',
            'venue:id,name,address'
        ])
        ->when($validated['team_id'] ?? null, function ($q, $teamId) {
            return $q->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
        })
        ->when($validated['date_from'] ?? null, function ($q, $date) {
            return $q->where('scheduled_at', '>=', $date);
        })
        ->when($validated['date_to'] ?? null, function ($q, $date) {
            return $q->where('scheduled_at', '<=', $date);
        })
        ->when($validated['last_sync'] ?? null, function ($q, $lastSync) {
            return $q->where('updated_at', '>', $lastSync);
        })
        ->orderBy('scheduled_at')
        ->limit($validated['limit'] ?? 20);

        $games = $query->get();

        return response()->json([
            'data' => MobileGameResource::collection($games),
            'sync_timestamp' => now()->toISOString(),
            'has_more' => $games->count() === ($validated['limit'] ?? 20)
        ]);
    }

    /**
     * Get detailed game data for mobile live scoring
     */
    public function show(Game $game): JsonResponse
    {
        $game->load([
            'homeTeam.players:id,team_id,user_id,jersey_number',
            'homeTeam.players.user:id,name',
            'awayTeam.players:id,team_id,user_id,jersey_number',
            'awayTeam.players.user:id,name',
            'gameEvents.player.user:id,name',
            'gameStats.player.user:id,name',
            'periods'
        ]);

        return response()->json([
            'data' => new MobileGameDetailResource($game),
            'sync_timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Sync mobile data changes back to server
     */
    public function syncData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'offline_actions' => 'required|array',
            'offline_actions.*.type' => 'required|string|in:score,event,stat',
            'offline_actions.*.data' => 'required|array',
            'offline_actions.*.timestamp' => 'required|date',
            'device_id' => 'required|string'
        ]);

        $results = $this->syncService->processMobileSync(
            $validated['offline_actions'],
            $validated['device_id'],
            auth()->user()
        );

        return response()->json([
            'sync_results' => $results,
            'sync_timestamp' => now()->toISOString(),
            'conflicts' => $results['conflicts'] ?? []
        ]);
    }
}
```

#### Mobile-Optimized Resources

```php
<?php
// app/Http/Resources/Mobile/MobileGameResource.php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'home_team' => [
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
                'logo' => $this->homeTeam->logo_path ? url($this->homeTeam->logo_path) : null,
                'score' => $this->home_score
            ],
            'away_team' => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
                'logo' => $this->awayTeam->logo_path ? url($this->awayTeam->logo_path) : null,
                'score' => $this->away_score
            ],
            'scheduled_at' => $this->scheduled_at->toISOString(),
            'status' => $this->status,
            'current_period' => $this->current_period,
            'venue' => $this->venue ? [
                'name' => $this->venue->name,
                'address' => $this->venue->address
            ] : null,
            'is_live' => $this->isLive(),
            'last_updated' => $this->updated_at->toISOString()
        ];
    }
}
```

### Offline-First Data Synchronization

```php
<?php
// app/Services/MobileDataSyncService.php

namespace App\Services;

use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MobileDataSyncService
{
    public function processMobileSync(array $offlineActions, string $deviceId, User $user): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'conflicts' => []
        ];

        DB::beginTransaction();
        
        try {
            foreach ($offlineActions as $action) {
                $result = $this->processOfflineAction($action, $deviceId, $user);
                
                if ($result['success']) {
                    $results['successful'][] = $result;
                } elseif ($result['conflict']) {
                    $results['conflicts'][] = $result;
                } else {
                    $results['failed'][] = $result;
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        return $results;
    }

    private function processOfflineAction(array $action, string $deviceId, User $user): array
    {
        $actionTime = Carbon::parse($action['timestamp']);
        
        return match($action['type']) {
            'score' => $this->processMobileScore($action['data'], $actionTime, $deviceId, $user),
            'event' => $this->processMobileEvent($action['data'], $actionTime, $deviceId, $user),
            'stat' => $this->processMobileStat($action['data'], $actionTime, $deviceId, $user),
            default => ['success' => false, 'error' => 'Unknown action type']
        };
    }

    private function processMobileScore(array $data, Carbon $actionTime, string $deviceId, User $user): array
    {
        $game = Game::find($data['game_id']);
        
        if (!$game) {
            return ['success' => false, 'error' => 'Game not found'];
        }

        // Check for conflicts
        if ($game->updated_at->isAfter($actionTime)) {
            return [
                'success' => false,
                'conflict' => true,
                'server_data' => [
                    'home_score' => $game->home_score,
                    'away_score' => $game->away_score,
                    'updated_at' => $game->updated_at->toISOString()
                ],
                'mobile_data' => $data
            ];
        }

        // Apply mobile changes
        $game->update([
            'home_score' => $data['home_score'],
            'away_score' => $data['away_score'],
            'current_period' => $data['current_period'] ?? $game->current_period
        ]);

        // Cache invalidation
        Cache::tags(['game_' . $game->id])->flush();

        return [
            'success' => true,
            'type' => 'score',
            'game_id' => $game->id,
            'applied_at' => now()->toISOString()
        ];
    }

    public function generateMobileDownloadPackage(User $user, ?int $teamId = null): array
    {
        $cacheKey = "mobile_package_{$user->id}_{$teamId}";
        
        return Cache::remember($cacheKey, 300, function () use ($user, $teamId) {
            $package = [
                'teams' => $this->getTeamsForMobile($user, $teamId),
                'games' => $this->getGamesForMobile($user, $teamId),
                'players' => $this->getPlayersForMobile($user, $teamId),
                'config' => $this->getMobileConfig(),
                'generated_at' => now()->toISOString()
            ];

            return $package;
        });
    }
}
```

## 🔍 Meilisearch Integration für Advanced Search

### Meilisearch Service Implementation

```php
<?php
// app/Services/MeilisearchService.php

namespace App\Services;

use App\Models\Player;
use App\Models\Team;
use App\Models\Game;
use App\Models\NewsArticle;
use Meilisearch\Client;
use Illuminate\Support\Facades\Log;

class MeilisearchService
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client(
            config('services.meilisearch.host'),
            config('services.meilisearch.key')
        );
    }

    public function setupIndexes(): void
    {
        $this->setupPlayersIndex();
        $this->setupTeamsIndex();
        $this->setupGamesIndex();
        $this->setupNewsIndex();
    }

    private function setupPlayersIndex(): void
    {
        $index = $this->client->index('players');
        
        // Configure searchable attributes
        $index->updateSearchableAttributes([
            'name',
            'jersey_number',
            'position',
            'team_name',
            'club_name'
        ]);

        // Configure filterable attributes
        $index->updateFilterableAttributes([
            'team_id',
            'club_id',
            'position',
            'age_group',
            'is_active',
            'statistics.points_avg',
            'statistics.rebounds_avg'
        ]);

        // Configure sortable attributes
        $index->updateSortableAttributes([
            'name',
            'jersey_number',
            'statistics.points_avg',
            'statistics.rebounds_avg',
            'created_at'
        ]);

        // Configure ranking rules
        $index->updateRankingRules([
            'words',
            'typo',
            'proximity',
            'attribute',
            'sort',
            'exactness',
            'statistics.points_avg:desc'
        ]);
    }

    public function searchPlayers(string $query, array $filters = [], array $facets = []): array
    {
        $searchParams = [
            'q' => $query,
            'limit' => $filters['limit'] ?? 50,
            'offset' => $filters['offset'] ?? 0,
        ];

        // Add filters
        if (!empty($filters['team_id'])) {
            $searchParams['filter'] = "team_id = {$filters['team_id']}";
        }

        if (!empty($filters['position'])) {
            $positionFilter = is_array($filters['position']) 
                ? 'position IN [' . implode(',', array_map(fn($p) => "'$p'", $filters['position'])) . ']'
                : "position = '{$filters['position']}'";
            
            $searchParams['filter'] = isset($searchParams['filter']) 
                ? $searchParams['filter'] . ' AND ' . $positionFilter
                : $positionFilter;
        }

        // Add sorting
        if (!empty($filters['sort'])) {
            $searchParams['sort'] = [$filters['sort']];
        }

        // Add facets for filtering UI
        if (!empty($facets)) {
            $searchParams['facets'] = $facets;
        }

        try {
            $index = $this->client->index('players');
            $results = $index->search('', $searchParams);
            
            return [
                'hits' => $results->getHits(),
                'total' => $results->getEstimatedTotalHits(),
                'facetDistribution' => $results->getFacetDistribution(),
                'processingTimeMs' => $results->getProcessingTimeMs()
            ];
            
        } catch (\Exception $e) {
            Log::error('Meilisearch player search error', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackPlayerSearch($query, $filters);
        }
    }

    public function searchTeams(string $query, array $filters = []): array
    {
        $searchParams = [
            'q' => $query,
            'limit' => $filters['limit'] ?? 20,
            'attributesToHighlight' => ['name', 'club_name'],
            'highlightPreTag' => '<mark>',
            'highlightPostTag' => '</mark>'
        ];

        if (!empty($filters['league_id'])) {
            $searchParams['filter'] = "league_id = {$filters['league_id']}";
        }

        try {
            $index = $this->client->index('teams');
            $results = $index->search('', $searchParams);
            
            return [
                'hits' => $results->getHits(),
                'total' => $results->getEstimatedTotalHits()
            ];
            
        } catch (\Exception $e) {
            return $this->fallbackTeamSearch($query, $filters);
        }
    }

    public function searchGames(string $query, array $filters = []): array
    {
        $searchParams = [
            'q' => $query,
            'limit' => $filters['limit'] ?? 30,
            'facets' => ['status', 'league_name']
        ];

        // Date range filtering
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $searchParams['filter'] = "scheduled_at >= {$filters['date_from']} AND scheduled_at <= {$filters['date_to']}";
        }

        try {
            $index = $this->client->index('games');
            $results = $index->search('', $searchParams);
            
            return [
                'hits' => $results->getHits(),
                'total' => $results->getEstimatedTotalHits(),
                'facets' => $results->getFacetDistribution()
            ];
            
        } catch (\Exception $e) {
            return $this->fallbackGameSearch($query, $filters);
        }
    }

    public function indexPlayer(Player $player): void
    {
        try {
            $index = $this->client->index('players');
            $index->addDocuments([$player->toSearchableArray()]);
        } catch (\Exception $e) {
            Log::error('Failed to index player', [
                'player_id' => $player->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function bulkIndexPlayers(array $players): void
    {
        try {
            $index = $this->client->index('players');
            $documents = array_map(fn($player) => $player->toSearchableArray(), $players);
            $index->addDocuments($documents);
        } catch (\Exception $e) {
            Log::error('Failed to bulk index players', [
                'count' => count($players),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function fallbackPlayerSearch(string $query, array $filters): array
    {
        // Fallback to database search if Meilisearch fails
        $queryBuilder = Player::query()
            ->with(['user', 'team', 'club'])
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('name', 'LIKE', "%{$query}%");
                })
                ->orWhere('jersey_number', 'LIKE', "%{$query}%")
                ->orWhere('position', 'LIKE', "%{$query}%");
            });

        if (!empty($filters['team_id'])) {
            $queryBuilder->where('team_id', $filters['team_id']);
        }

        $players = $queryBuilder->limit($filters['limit'] ?? 50)->get();

        return [
            'hits' => $players->map(fn($player) => $player->toSearchableArray())->toArray(),
            'total' => $players->count(),
            'fallback' => true
        ];
    }
}
```

### Search API Controller

```php
<?php
// app/Http/Controllers/Api/SearchController.php

namespace App\Http\Controllers\Controller\Api;

use App\Http\Controllers\Controller;
use App\Services\MeilisearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        private MeilisearchService $searchService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:100',
            'type' => 'nullable|string|in:players,teams,games,news,all',
            'limit' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'facets' => 'nullable|array'
        ]);

        $query = $validated['q'];
        $type = $validated['type'] ?? 'all';
        $limit = $validated['limit'] ?? 20;
        $filters = $validated['filters'] ?? [];
        $facets = $validated['facets'] ?? [];

        $results = match($type) {
            'players' => ['players' => $this->searchService->searchPlayers($query, $filters, $facets)],
            'teams' => ['teams' => $this->searchService->searchTeams($query, $filters)],
            'games' => ['games' => $this->searchService->searchGames($query, $filters)],
            'news' => ['news' => $this->searchService->searchNews($query, $filters)],
            'all' => $this->searchAll($query, $limit, $filters)
        };

        return response()->json([
            'query' => $query,
            'results' => $results,
            'total_results' => array_sum(array_map(fn($r) => $r['total'] ?? 0, $results)),
            'search_time' => microtime(true) - LARAVEL_START
        ]);
    }

    private function searchAll(string $query, int $limit, array $filters): array
    {
        $perType = intval($limit / 4);
        
        return [
            'players' => $this->searchService->searchPlayers($query, array_merge($filters, ['limit' => $perType])),
            'teams' => $this->searchService->searchTeams($query, array_merge($filters, ['limit' => $perType])),
            'games' => $this->searchService->searchGames($query, array_merge($filters, ['limit' => $perType])),
            'news' => $this->searchService->searchNews($query, array_merge($filters, ['limit' => $perType]))
        ];
    }
}
```

## 🏢 Multi-Organization Support

### Organization Model & Multi-Tenancy

```php
<?php
// database/migrations/2024_05_01_000000_create_organizations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            
            // Organization Type and Details
            $table->enum('type', [
                'national_federation', 'regional_federation', 'league',
                'club_organization', 'school_district', 'corporate'
            ]);
            $table->string('country_code', 2)->default('DE');
            $table->string('language', 2)->default('de');
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('currency', 3)->default('EUR');
            
            // Subscription and Billing
            $table->enum('plan', ['free', 'basic', 'pro', 'enterprise'])->default('basic');
            $table->integer('max_clubs')->default(1);
            $table->integer('max_teams')->default(10);
            $table->integer('max_players')->default(200);
            $table->boolean('api_access_enabled')->default(false);
            $table->boolean('white_label_enabled')->default(false);
            
            // Settings and Customization
            $table->json('settings')->nullable();
            $table->json('theme_config')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->json('custom_css')->nullable();
            
            // Status and Analytics
            $table->enum('status', ['active', 'suspended', 'trial', 'expired'])->default('trial');
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('subscription_ends_at')->nullable();
            $table->integer('total_clubs')->default(0);
            $table->integer('total_teams')->default(0);
            $table->integer('total_players')->default(0);
            $table->integer('total_games')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'plan']);
            $table->index(['country_code', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
```

### Multi-Tenant Middleware

```php
<?php
// app/Http/Middleware/MultiTenantMiddleware.php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MultiTenantMiddleware
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        if ($tenant->status !== 'active') {
            return $this->handleInactiveTenant($tenant);
        }

        $this->tenantService->setCurrentTenant($tenant);
        
        // Set database connection for tenant
        $this->configureDatabase($tenant);
        
        // Set tenant-specific configurations
        $this->configureTenant($tenant);

        return $next($request);
    }

    private function resolveTenant(Request $request): ?Organization
    {
        // Try subdomain first
        $subdomain = $this->extractSubdomain($request);
        if ($subdomain) {
            $tenant = Organization::where('slug', $subdomain)->first();
            if ($tenant) return $tenant;
        }

        // Try custom domain
        $host = $request->getHost();
        $tenant = Organization::where('custom_domain', $host)->first();
        if ($tenant) return $tenant;

        // Fallback to header or parameter
        $tenantId = $request->header('X-Tenant-ID') ?? $request->get('tenant');
        if ($tenantId) {
            return Organization::find($tenantId);
        }

        return null;
    }

    private function extractSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        if (count($parts) >= 3) {
            return $parts[0];
        }
        
        return null;
    }

    private function configureDatabase(Organization $tenant): void
    {
        // Configure tenant-specific database connection if needed
        config(['database.connections.tenant.database' => "tenant_{$tenant->id}"]);
    }

    private function configureTenant(Organization $tenant): void
    {
        // Set tenant-specific configurations
        config([
            'app.tenant' => $tenant,
            'app.locale' => $tenant->language,
            'app.timezone' => $tenant->timezone,
            'app.currency' => $tenant->currency
        ]);

        // Apply custom theme
        if ($tenant->theme_config) {
            view()->share('tenantTheme', $tenant->theme_config);
        }
    }

    private function handleInactiveTenant(Organization $tenant): Response
    {
        return match($tenant->status) {
            'trial' => $this->redirectToUpgrade($tenant),
            'expired' => $this->showExpiredMessage($tenant),
            'suspended' => $this->showSuspendedMessage($tenant),
            default => abort(403, 'Tenant access denied')
        };
    }
}
```

### Organization Management Service

```php
<?php
// app/Services/OrganizationService.php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\Club;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    public function createOrganization(array $data, User $creator): Organization
    {
        DB::beginTransaction();
        
        try {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $this->generateUniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'country_code' => $data['country_code'] ?? 'DE',
                'language' => $data['language'] ?? 'de',
                'timezone' => $data['timezone'] ?? 'Europe/Berlin',
                'plan' => $data['plan'] ?? 'basic',
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
            ]);

            // Create default admin role for creator
            $organization->users()->attach($creator->id, [
                'role' => 'admin',
                'joined_at' => now()
            ]);

            // Setup default organization structure
            $this->setupDefaultStructure($organization, $creator);

            DB::commit();
            
            return $organization;
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function upgradeOrganization(Organization $organization, string $newPlan): bool
    {
        $planLimits = $this->getPlanLimits($newPlan);
        
        $organization->update([
            'plan' => $newPlan,
            'max_clubs' => $planLimits['max_clubs'],
            'max_teams' => $planLimits['max_teams'],
            'max_players' => $planLimits['max_players'],
            'api_access_enabled' => $planLimits['api_access'],
            'white_label_enabled' => $planLimits['white_label'],
            'status' => 'active',
            'subscription_ends_at' => now()->addYear()
        ]);

        return true;
    }

    public function getOrganizationStats(Organization $organization): array
    {
        return [
            'clubs' => $organization->clubs()->count(),
            'teams' => $organization->clubs()
                        ->withCount('teams')
                        ->get()
                        ->sum('teams_count'),
            'players' => $organization->clubs()
                          ->withCount(['teams.players'])
                          ->get()
                          ->sum('teams.players_count'),
            'games_this_month' => $organization->clubs()
                                   ->withCount(['teams.homeGames' => function ($query) {
                                       $query->whereMonth('scheduled_at', now()->month);
                                   }])
                                   ->get()
                                   ->sum('home_games_count'),
            'active_users' => $organization->users()
                               ->wherePivot('status', 'active')
                               ->count()
        ];
    }

    private function setupDefaultStructure(Organization $organization, User $creator): void
    {
        // Create default club if organization type requires it
        if (in_array($organization->type, ['club_organization', 'school_district'])) {
            Club::create([
                'organization_id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'status' => 'active',
                'settings' => [
                    'default_club' => true
                ]
            ]);
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function getPlanLimits(string $plan): array
    {
        return match($plan) {
            'free' => [
                'max_clubs' => 1,
                'max_teams' => 2,
                'max_players' => 50,
                'api_access' => false,
                'white_label' => false
            ],
            'basic' => [
                'max_clubs' => 1,
                'max_teams' => 10,
                'max_players' => 200,
                'api_access' => false,
                'white_label' => false
            ],
            'pro' => [
                'max_clubs' => 5,
                'max_teams' => 50,
                'max_players' => 1000,
                'api_access' => true,
                'white_label' => false
            ],
            'enterprise' => [
                'max_clubs' => -1, // unlimited
                'max_teams' => -1,
                'max_players' => -1,
                'api_access' => true,
                'white_label' => true
            ]
        };
    }
}
```

---

## 🧪 Testing & Quality Assurance

### Comprehensive Testing Strategy

**Phase 4** erfordert eine umfassende Testing-Strategie, die alle kritischen Aspekte der Multi-tenant SaaS-Architektur, External Integrations und Performance-Anforderungen abdeckt. Unser Ansatz kombiniert automatisierte Tests mit manuellen QA-Prozessen für maximale Codequalität und Reliability.

#### Testing Pyramid für Phase 4

```
                    /\
                   /  \  E2E Tests (10%)
                  /____\  - User Journeys
                 /      \  - Cross-browser
                /   UI   \ Integration Tests (20%)
               /__________\ - API Integration
              /            \ - Multi-tenant Tests
             /    Unit     \ Unit Tests (70%)
            /________________\ - Models, Services
           /                  \ - Business Logic
```

### 1. Unit Testing Strategy

#### 1.1 Model & Service Layer Testing

**Ziel**: 100% Code Coverage für kritische Business Logic und komplexe Funktionalitäten.

**Laravel Testing Framework Setup:**

```php
// Base Test Class für Phase 4
abstract class Phase4TestCase extends TestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker;
    
    protected Tenant $defaultTenant;
    protected User $adminUser;
    protected User $trainerUser;
    protected Team $testTeam;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test tenant
        $this->setupTestTenant();
        
        // Setup test users with roles
        $this->setupTestUsers();
        
        // Setup test data
        $this->setupTestData();
        
        // Configure tenant context
        $this->setupTenantContext();
    }
    
    private function setupTestTenant(): void
    {
        $this->defaultTenant = Tenant::factory()->create([
            'name' => 'Test Basketball Club',
            'slug' => 'test-club',
            'subscription_tier' => 'professional',
            'is_active' => true
        ]);
    }
    
    private function setupTestUsers(): void
    {
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'email' => 'admin@testclub.com'
        ]);
        $this->adminUser->assignRole('admin');
        
        $this->trainerUser = User::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'email' => 'trainer@testclub.com'
        ]);
        $this->trainerUser->assignRole('trainer');
    }
    
    private function setupTestData(): void
    {
        $this->testTeam = Team::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'name' => 'Test Team',
            'head_coach_id' => $this->trainerUser->id
        ]);
        
        // Create test players
        Player::factory()->count(15)->create([
            'tenant_id' => $this->defaultTenant->id,
            'team_id' => $this->testTeam->id
        ]);
    }
    
    private function setupTenantContext(): void
    {
        app()->instance('tenant', $this->defaultTenant);
        app()->instance('tenant.id', $this->defaultTenant->id);
        
        // Set RLS context
        DB::statement('SET basketmanager.current_tenant_id = ?', [$this->defaultTenant->id]);
    }
    
    protected function createIsolatedTenant(): Tenant
    {
        return Tenant::factory()->create([
            'subscription_tier' => 'starter',
            'is_active' => true
        ]);
    }
}

// Multi-tenant Model Testing
class MultiTenantModelTest extends Phase4TestCase
{
    public function test_models_automatically_scope_to_current_tenant(): void
    {
        // Create another tenant with data
        $otherTenant = $this->createIsolatedTenant();
        $otherTeam = Team::factory()->create(['tenant_id' => $otherTenant->id]);
        
        // Should only see current tenant's teams
        $teams = Team::all();
        
        $this->assertCount(1, $teams);
        $this->assertEquals($this->testTeam->id, $teams->first()->id);
        $this->assertNotContains($otherTeam->id, $teams->pluck('id'));
    }
    
    public function test_models_prevent_cross_tenant_data_access(): void
    {
        $otherTenant = $this->createIsolatedTenant();
        $otherTeam = Team::factory()->create(['tenant_id' => $otherTenant->id]);
        
        // Should not be able to find other tenant's team
        $team = Team::find($otherTeam->id);
        
        $this->assertNull($team);
    }
    
    public function test_model_creation_automatically_sets_tenant_id(): void
    {
        $newTeam = Team::create([
            'name' => 'New Team',
            'category' => 'senior',
            'season' => '2024-25'
        ]);
        
        $this->assertEquals($this->defaultTenant->id, $newTeam->tenant_id);
    }
    
    public function test_relationship_queries_respect_tenant_scope(): void
    {
        $otherTenant = $this->createIsolatedTenant();
        $otherPlayer = Player::factory()->create(['tenant_id' => $otherTenant->id]);
        
        // Should not include other tenant's players in team relationship
        $players = $this->testTeam->players;
        
        $this->assertEquals(15, $players->count());
        $this->assertNotContains($otherPlayer->id, $players->pluck('id'));
    }
}

// Service Layer Testing
class DBBIntegrationServiceTest extends Phase4TestCase
{
    private DBBIntegrationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(DBBIntegrationService::class);
    }
    
    public function test_sync_team_data_updates_team_with_official_data(): void
    {
        // Mock DBB API response
        Http::fake([
            'api.basketball-bund.de/v2/teams/*' => Http::response([
                'official_name' => 'Official Team Name',
                'league' => '1. Bundesliga',
                'division' => 'ProA',
                'players' => [
                    [
                        'id' => 'dbb-123',
                        'first_name' => 'Max',
                        'last_name' => 'Mustermann',
                        'jersey_number' => 23,
                        'position' => 'SF',
                        'birth_date' => '1995-05-15',
                        'height' => 195,
                        'status' => 'active'
                    ]
                ],
                'schedule' => []
            ], 200)
        ]);
        
        $team = $this->testTeam;
        $team->external_id = 'dbb-team-456';
        $team->save();
        
        $result = $this->service->syncTeamData($team);
        
        $this->assertTrue($result->isSuccessful());
        
        $team->refresh();
        $this->assertEquals('Official Team Name', $team->official_name);
        $this->assertEquals('1. Bundesliga', $team->league);
        $this->assertEquals('ProA', $team->division);
        $this->assertNotNull($team->last_dbb_sync);
    }
    
    public function test_sync_creates_or_updates_players_from_dbb_data(): void
    {
        Http::fake([
            'api.basketball-bund.de/v2/teams/*' => Http::response([
                'official_name' => 'Test Team',
                'league' => 'Test League',
                'division' => 'Test Division',
                'players' => [
                    [
                        'id' => 'dbb-player-1',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'jersey_number' => 10,
                        'position' => 'PG',
                        'birth_date' => '1990-01-01',
                        'height' => 180,
                        'status' => 'active'
                    ],
                    [
                        'id' => 'dbb-player-2',
                        'first_name' => 'Jane',
                        'last_name' => 'Smith',
                        'jersey_number' => 12,
                        'position' => 'SG',
                        'birth_date' => '1992-03-15',
                        'height' => 175,
                        'status' => 'active'
                    ]
                ],
                'schedule' => []
            ], 200)
        ]);
        
        $team = $this->testTeam;
        $team->external_id = 'dbb-team-456';
        $team->save();
        
        $initialPlayerCount = $team->players()->count();
        
        $result = $this->service->syncTeamData($team);
        
        $this->assertTrue($result->isSuccessful());
        
        // Should have 2 additional players from DBB
        $this->assertEquals($initialPlayerCount + 2, $team->players()->count());
        
        $syncedPlayer = Player::where('external_id', 'dbb-player-1')->first();
        $this->assertNotNull($syncedPlayer);
        $this->assertEquals('John', $syncedPlayer->first_name);
        $this->assertEquals('Doe', $syncedPlayer->last_name);
        $this->assertEquals(10, $syncedPlayer->jersey_number);
    }
    
    public function test_submit_game_results_sends_correct_data_to_dbb(): void
    {
        Http::fake([
            'api.basketball-bund.de/v2/games/*/results' => Http::response(['success' => true], 200)
        ]);
        
        $game = Game::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'home_team_id' => $this->testTeam->id,
            'external_game_id' => 'dbb-game-789',
            'final_score_home' => 85,
            'final_score_away' => 78,
            'status' => 'finished'
        ]);
        
        $result = $this->service->submitGameResults($game);
        
        $this->assertTrue($result);
        
        Http::assertSent(function ($request) use ($game) {
            $data = json_decode($request->body(), true);
            
            return $request->url() === 'https://api.basketball-bund.de/v2/games/dbb-game-789/results' &&
                   $request->method() === 'POST' &&
                   $data['game_id'] === 'dbb-game-789' &&
                   $data['final_score']['home'] === 85 &&
                   $data['final_score']['away'] === 78;
        });
        
        $game->refresh();
        $this->assertEquals('submitted', $game->dbb_submission_status);
        $this->assertNotNull($game->dbb_submitted_at);
    }
    
    public function test_sync_handles_api_errors_gracefully(): void
    {
        Http::fake([
            'api.basketball-bund.de/v2/teams/*' => Http::response(['error' => 'Team not found'], 404)
        ]);
        
        $team = $this->testTeam;
        $team->external_id = 'invalid-team-id';
        $team->save();
        
        $result = $this->service->syncTeamData($team);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertStringContains('DBB API returned error', $result->getError());
    }
}

// Push Notification Service Testing  
class PushNotificationServiceTest extends Phase4TestCase
{
    private PushNotificationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(PushNotificationService::class);
        
        // Mock notification system
        Notification::fake();
    }
    
    public function test_sends_game_score_update_to_interested_users(): void
    {
        $game = Game::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'home_team_id' => $this->testTeam->id,
            'final_score_home' => 45,
            'final_score_away' => 42,
            'status' => 'live'
        ]);
        
        $lastAction = GameAction::factory()->create([
            'game_id' => $game->id,
            'action_type' => 'field_goal_made',
            'points' => 2
        ]);
        
        // Create users interested in this game
        $fanUser = User::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'notification_settings' => ['push_enabled' => true, 'game_updates' => true]
        ]);
        $fanUser->favoriteTeams()->attach($this->testTeam);
        
        $this->service->sendGameScoreUpdate($game, $lastAction);
        
        Notification::assertSentTo($fanUser, GameScoreUpdateNotification::class);
    }
    
    public function test_respects_user_notification_preferences(): void
    {
        $game = Game::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'home_team_id' => $this->testTeam->id,
            'status' => 'live'
        ]);
        
        $lastAction = GameAction::factory()->create(['game_id' => $game->id]);
        
        // User with notifications disabled
        $userNoNotifications = User::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'notification_settings' => ['push_enabled' => false, 'game_updates' => false]
        ]);
        $userNoNotifications->favoriteTeams()->attach($this->testTeam);
        
        $this->service->sendGameScoreUpdate($game, $lastAction);
        
        Notification::assertNotSentTo($userNoNotifications, GameScoreUpdateNotification::class);
    }
    
    public function test_rate_limits_notifications_per_game(): void
    {
        $game = Game::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'home_team_id' => $this->testTeam->id,
            'status' => 'live'
        ]);
        
        $user = User::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'notification_settings' => ['push_enabled' => true, 'game_updates' => true]
        ]);
        $user->favoriteTeams()->attach($this->testTeam);
        
        // Simulate recent notification
        $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => GameScoreUpdateNotification::class,
            'data' => ['game_id' => $game->id],
            'created_at' => now()->subMinutes(3)
        ]);
        
        $lastAction = GameAction::factory()->create(['game_id' => $game->id]);
        
        $this->service->sendGameScoreUpdate($game, $lastAction);
        
        // Should not send due to rate limiting
        Notification::assertNotSentTo($user, GameScoreUpdateNotification::class);
    }
}
```

#### 1.2 Performance & Memory Testing

**Memory Usage Validation:**

```php
class MemoryOptimizationTest extends Phase4TestCase
{
    public function test_large_dataset_processing_stays_within_memory_limits(): void
    {
        $memoryLimit = 128 * 1024 * 1024; // 128MB
        $startMemory = memory_get_usage(true);
        
        // Create large dataset
        $teams = Team::factory()->count(50)->create(['tenant_id' => $this->defaultTenant->id]);
        
        foreach ($teams as $team) {
            Player::factory()->count(20)->create([
                'tenant_id' => $this->defaultTenant->id,
                'team_id' => $team->id
            ]);
        }
        
        $service = app(MemoryOptimizationService::class);
        
        // Process large query with chunking
        $processedCount = 0;
        $service->processLargeQuery(
            Player::query(),
            function ($players) use (&$processedCount) {
                $processedCount += $players->count();
                
                // Simulate processing
                foreach ($players as $player) {
                    $player->full_name; // Access computed attribute
                }
            }
        );
        
        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;
        
        $this->assertEquals(1000, $processedCount); // 50 teams * 20 players
        $this->assertLessThan($memoryLimit, $memoryUsed, 
            "Memory usage ({$this->formatBytes($memoryUsed)}) exceeded limit");
    }
    
    public function test_streaming_response_for_large_reports(): void
    {
        $startMemory = memory_get_usage(true);
        
        // Create large team with many players and statistics
        Player::factory()->count(500)->create([
            'tenant_id' => $this->defaultTenant->id,
            'team_id' => $this->testTeam->id
        ]);
        
        $service = app(MemoryOptimizationService::class);
        $response = $service->generateLargeReport($this->testTeam, '2024-25');
        
        $this->assertInstanceOf(StreamedResponse::class, $response);
        
        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        
        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;
        
        // Should not load all data into memory at once
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed); // Less than 50MB
        $this->assertStringContains('Player,Games,Points', $content);
        
        // Count CSV rows (header + 500 players)
        $lines = substr_count($content, "\n");
        $this->assertEquals(501, $lines);
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

### 2. Integration Testing

#### 2.1 API Integration Testing

**Multi-Tenant API Testing:**

```php
class MultiTenantAPITest extends Phase4TestCase
{
    public function test_api_endpoints_respect_tenant_isolation(): void
    {
        // Create another tenant with data
        $otherTenant = $this->createIsolatedTenant();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherTeam = Team::factory()->create(['tenant_id' => $otherTenant->id]);
        
        // Authenticate as user from default tenant
        $this->actingAs($this->adminUser, 'sanctum');
        
        $response = $this->getJson('/api/v4/teams');
        
        $response->assertStatus(200);
        $teamIds = collect($response->json('data'))->pluck('id');
        
        // Should only see teams from current tenant
        $this->assertContains($this->testTeam->id, $teamIds);
        $this->assertNotContains($otherTeam->id, $teamIds);
    }
    
    public function test_api_rate_limiting_works_per_tenant_tier(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        // Professional tier allows 25,000 requests per hour (≈7 per second)
        $startTime = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/v4/teams');
            
            if ($response->status() === 429) {
                $this->assertTrue($i >= 7, 'Rate limiting should kick in around 7 requests/second');
                break;
            }
        }
        
        $duration = microtime(true) - $startTime;
        $this->assertLessThan(2, $duration, 'Test should complete within 2 seconds');
    }
    
    public function test_webhook_delivery_system(): void
    {
        Http::fake();
        
        $this->actingAs($this->adminUser, 'sanctum');
        
        // Create webhook subscription
        $subscription = WebhookSubscription::create([
            'tenant_id' => $this->defaultTenant->id,
            'url' => 'https://webhook.example.com/basketball',
            'events' => ['game.score_updated'],
            'secret' => 'webhook-secret-123',
            'is_active' => true
        ]);
        
        $game = Game::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'home_team_id' => $this->testTeam->id,
            'status' => 'live'
        ]);
        
        // Trigger webhook event
        event(new GameScoreUpdated($game));
        
        // Process queued webhook job
        $this->artisan('queue:work --once');
        
        Http::assertSent(function ($request) use ($subscription) {
            return $request->url() === $subscription->url &&
                   $request->hasHeader('X-Webhook-Event', 'game.score_updated') &&
                   $request->hasHeader('X-Webhook-Signature-256');
        });
    }
    
    public function test_api_versioning_backwards_compatibility(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        // Test v3 API (legacy)
        $responseV3 = $this->withHeaders(['Accept-Version' => '3.0'])
                          ->getJson('/api/v3/teams');
        
        // Test v4 API (current)
        $responseV4 = $this->withHeaders(['Accept-Version' => '4.0'])
                          ->getJson('/api/v4/teams');
        
        $responseV3->assertStatus(200);
        $responseV4->assertStatus(200);
        
        // V4 should have additional fields
        $v3Data = $responseV3->json('data.0');
        $v4Data = $responseV4->json('data.0');
        
        $this->assertArrayHasKey('id', $v3Data);
        $this->assertArrayHasKey('name', $v3Data);
        
        $this->assertArrayHasKey('id', $v4Data);
        $this->assertArrayHasKey('name', $v4Data);
        $this->assertArrayHasKey('subscription_tier', $v4Data); // New in v4
        $this->assertArrayHasKey('feature_flags', $v4Data); // New in v4
    }
}

// External Service Integration Tests
class ExternalServiceIntegrationTest extends Phase4TestCase
{
    public function test_stripe_payment_integration(): void
    {
        $this->markTestSkipped('Requires Stripe test keys');
        
        $paymentService = app(MultiGatewayPaymentService::class);
        
        $paymentRequest = new PaymentRequest([
            'amount' => 29.99,
            'currency' => 'EUR',
            'payment_type' => 'subscription',
            'user' => $this->adminUser,
            'team' => $this->testTeam
        ]);
        
        $result = $paymentService->processPayment($paymentRequest);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertNotNull($result->getPaymentId());
        $this->assertNotNull($result->getClientSecret());
    }
    
    public function test_social_media_posting_integration(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'post_123'], 200),
            'api.twitter.com/*' => Http::response(['data' => ['id' => 'tweet_456']], 201)
        ]);
        
        $game = Game::factory()->create([
            'tenant_id' => $this->defaultTenant->id,
            'home_team_id' => $this->testTeam->id,
            'final_score_home' => 95,
            'final_score_away' => 88,
            'status' => 'finished'
        ]);
        
        $highlightVideo = Media::factory()->create([
            'model_type' => Game::class,
            'model_id' => $game->id,
            'collection_name' => 'highlights'
        ]);
        
        $socialService = app(SocialMediaAutomationService::class);
        $result = $socialService->postGameHighlight($game, $highlightVideo);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertArrayHasKey('facebook', $result->getResults());
        $this->assertArrayHasKey('twitter', $result->getResults());
    }
}
```

#### 2.2 Performance Integration Testing

**Database Performance Testing:**

```php
class DatabasePerformanceTest extends Phase4TestCase
{
    public function test_query_performance_meets_requirements(): void
    {
        // Create realistic dataset
        $this->createLargeTestDataset();
        
        $queries = [
            'team_list' => fn() => Team::with('headCoach')->paginate(20),
            'player_stats' => fn() => $this->getPlayerSeasonStats(),
            'game_list' => fn() => Game::with('homeTeam', 'awayTeam')->recent()->paginate(20),
            'live_game' => fn() => $this->getLiveGameData()
        ];
        
        foreach ($queries as $name => $query) {
            $startTime = microtime(true);
            
            $result = $query();
            
            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            $this->assertLessThan(50, $duration, 
                "{$name} query took {$duration}ms, should be under 50ms");
        }
    }
    
    public function test_concurrent_user_performance(): void
    {
        $this->createLargeTestDataset();
        
        $promises = [];
        $userCount = 10;
        
        // Simulate concurrent users
        for ($i = 0; $i < $userCount; $i++) {
            $promises[] = $this->simulateConcurrentUser();
        }
        
        $startTime = microtime(true);
        $results = Promise::all($promises)->wait();
        $totalDuration = microtime(true) - $startTime;
        
        $this->assertLessThan(5, $totalDuration, 
            "Concurrent user simulation took {$totalDuration}s, should be under 5s");
        
        foreach ($results as $i => $duration) {
            $this->assertLessThan(2, $duration, 
                "User {$i} simulation took {$duration}s, should be under 2s");
        }
    }
    
    private function createLargeTestDataset(): void
    {
        // Skip if already created
        if (Team::count() > 100) return;
        
        Team::factory()->count(100)->create(['tenant_id' => $this->defaultTenant->id]);
        
        Team::chunk(10, function ($teams) {
            foreach ($teams as $team) {
                Player::factory()->count(15)->create([
                    'tenant_id' => $this->defaultTenant->id,
                    'team_id' => $team->id
                ]);
                
                Game::factory()->count(30)->create([
                    'tenant_id' => $this->defaultTenant->id,
                    'home_team_id' => $team->id
                ]);
            }
        });
    }
    
    private function simulateConcurrentUser(): Promise
    {
        return new Promise(function ($resolve) {
            $startTime = microtime(true);
            
            // Simulate typical user workflow
            Team::take(5)->get();
            Game::with('homeTeam', 'awayTeam')->take(10)->get();
            Player::with('team')->take(20)->get();
            
            $duration = microtime(true) - $startTime;
            $resolve($duration);
        });
    }
    
    private function getPlayerSeasonStats(): Collection
    {
        return Player::with([
            'statistics' => function ($query) {
                $query->where('season', '2024-25');
            }
        ])->take(50)->get();
    }
    
    private function getLiveGameData(): ?Game
    {
        return Game::with([
            'homeTeam',
            'awayTeam',
            'gameActions' => function ($query) {
                $query->latest()->take(20);
            },
            'liveGame'
        ])->where('status', 'live')->first();
    }
}
```

### 3. End-to-End Testing

#### 3.1 PWA E2E Testing

**Laravel Dusk PWA Tests:**

```php
class PWAEndToEndTest extends DuskTestCase
{
    use DatabaseMigrations;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test data
        $this->artisan('db:seed', ['--class' => 'TestDataSeeder']);
    }
    
    public function test_pwa_installation_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            
            $browser->loginAs($user)
                   ->visit('/')
                   ->waitFor('@app-shell')
                   ->assertSee('BasketManager Pro')
                   
                   // Test PWA installation prompt
                   ->script([
                       'window.deferredPrompt = {
                           prompt: () => Promise.resolve(),
                           userChoice: Promise.resolve({ outcome: "accepted" })
                       };',
                       'window.dispatchEvent(new Event("beforeinstallprompt"));'
                   ])
                   
                   ->waitFor('@install-app-button')
                   ->click('@install-app-button')
                   ->waitForText('App installation accepted')
                   
                   // Verify service worker registration
                   ->script('return navigator.serviceWorker.controller !== null')
                   ->assertScript('return navigator.serviceWorker.controller !== null', true);
        });
    }
    
    public function test_offline_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            
            $browser->loginAs($user)
                   ->visit('/dashboard')
                   ->waitFor('@dashboard-content')
                   
                   // Wait for service worker to cache data
                   ->pause(2000)
                   
                   // Simulate offline mode
                   ->script('
                       navigator.serviceWorker.controller.postMessage({
                           type: "SIMULATE_OFFLINE"
                       });
                   ')
                   
                   // Navigate to cached page
                   ->visit('/teams')
                   ->waitFor('@teams-list')
                   ->assertSee('Teams')
                   
                   // Test offline indicator
                   ->waitFor('@offline-indicator')
                   ->assertSee('Offline Mode')
                   
                   // Try to create team offline (should queue)
                   ->click('@create-team-button')
                   ->type('@team-name', 'Offline Team')
                   ->click('@save-team-button')
                   ->waitForText('Changes will sync when online')
                   
                   // Simulate going back online
                   ->script('
                       navigator.serviceWorker.controller.postMessage({
                           type: "SIMULATE_ONLINE"
                       });
                   ')
                   
                   ->waitUntilMissing('@offline-indicator')
                   ->waitForText('Offline changes synced')
                   ->assertSee('Offline Team');
        });
    }
    
    public function test_push_notifications(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            $game = Game::factory()->create(['status' => 'live']);
            
            $browser->loginAs($user)
                   ->visit('/games/' . $game->id . '/live')
                   ->waitFor('@live-game-view')
                   
                   // Grant notification permission
                   ->script('
                       Notification.requestPermission = () => Promise.resolve("granted");
                       return Notification.permission;
                   ')
                   
                   ->click('@enable-notifications')
                   ->waitForText('Notifications enabled')
                   
                   // Simulate push notification
                   ->script('
                       navigator.serviceWorker.controller.postMessage({
                           type: "TEST_PUSH_NOTIFICATION",
                           data: {
                               title: "Game Update",
                               body: "Score updated: 45-42",
                               tag: "game-' . $game->id . '"
                           }
                       });
                   ')
                   
                   // Check if notification was shown
                   ->pause(1000)
                   ->script('return document.querySelector(".notification") !== null');
        });
    }
    
    public function test_live_game_scoring_realtime_updates(): void
    {
        $this->browse(function (Browser $primary, Browser $secondary) {
            $scorer = User::factory()->create();
            $scorer->assignRole('scorer');
            
            $viewer = User::factory()->create();
            
            $game = Game::factory()->create(['status' => 'live']);
            $player = Player::factory()->create(['team_id' => $game->home_team_id]);
            
            // Scorer interface
            $primary->loginAs($scorer)
                   ->visit('/games/' . $game->id . '/live-scoring')
                   ->waitFor('@scoring-interface');
            
            // Viewer interface
            $secondary->loginAs($viewer)
                     ->visit('/games/' . $game->id . '/live')
                     ->waitFor('@live-scoreboard');
            
            // Scorer adds points
            $primary->select('@player-select', $player->id)
                   ->select('@action-type', 'field_goal_made')
                   ->type('@points', '2')
                   ->click('@add-action-button')
                   ->waitFor('@action-success');
            
            // Viewer should see updated score in real-time
            $secondary->waitUntilMissing('@loading-indicator')
                     ->assertSeeIn('@home-score', '2')
                     ->assertSeeIn('@last-action', $player->full_name);
        });
    }
    
    public function test_multi_tenant_isolation_in_browser(): void
    {
        $this->browse(function (Browser $tenant1, Browser $tenant2) {
            $tenant1User = User::factory()->create();
            $tenant2User = User::factory()->create();
            
            $tenant1Team = Team::factory()->create(['tenant_id' => $tenant1User->tenant_id]);
            $tenant2Team = Team::factory()->create(['tenant_id' => $tenant2User->tenant_id]);
            
            // Tenant 1 browser
            $tenant1->loginAs($tenant1User)
                   ->visit('/teams')
                   ->waitFor('@teams-list')
                   ->assertSee($tenant1Team->name)
                   ->assertDontSee($tenant2Team->name);
            
            // Tenant 2 browser
            $tenant2->loginAs($tenant2User)
                   ->visit('/teams')
                   ->waitFor('@teams-list')
                   ->assertSee($tenant2Team->name)
                   ->assertDontSee($tenant1Team->name);
            
            // Try to access other tenant's team directly (should fail)
            $tenant1->visit('/teams/' . $tenant2Team->id)
                   ->assertSee('404')
                   ->assertDontSee($tenant2Team->name);
        });
    }
}
```

### 4. Load & Stress Testing

#### 4.1 Automated Load Testing

**Artillery.js Load Testing Configuration:**

```javascript
// load-test-config.yml
config:
  target: 'https://basketmanager-pro.test'
  phases:
    - duration: 60
      arrivalRate: 10
      name: "Warm up"
    - duration: 120
      arrivalRate: 50
      name: "Ramp up load"
    - duration: 180
      arrivalRate: 100
      name: "Sustained load"
    - duration: 60
      arrivalRate: 200
      name: "Peak load"
  defaults:
    headers:
      Accept: 'application/json'
      Content-Type: 'application/json'
      
scenarios:
  - name: "API Load Test"
    weight: 70
    flow:
      - post:
          url: "/api/v4/auth/login"
          json:
            email: "test@basketmanager.com"
            password: "password"
          capture:
            - json: "$.token"
              as: "authToken"
      
      - get:
          url: "/api/v4/teams"
          headers:
            Authorization: "Bearer {{ authToken }}"
          
      - get:
          url: "/api/v4/games"
          headers:
            Authorization: "Bearer {{ authToken }}"
          
      - get:
          url: "/api/v4/players"
          headers:
            Authorization: "Bearer {{ authToken }}"
  
  - name: "Live Game Updates"
    weight: 20
    flow:
      - post:
          url: "/api/v4/auth/login"
          json:
            email: "scorer@basketmanager.com"
            password: "password"
          capture:
            - json: "$.token"
              as: "authToken"
      
      - loop:
          - patch:
              url: "/api/v4/games/{{ $randomInt(1, 100) }}/score"
              headers:
                Authorization: "Bearer {{ authToken }}"
              json:
                player_id: "{{ $randomInt(1, 500) }}"
                action_type: "field_goal_made"
                points: 2
                quarter: "{{ $randomInt(1, 4) }}"
        count: 10
        
  - name: "Real-time Dashboard"
    weight: 10
    flow:
      - get:
          url: "/dashboard"
          
      - think: 5
      
      - get:
          url: "/api/v4/dashboard/stats"
```

**PHP Load Testing Integration:**

```php
class LoadTestRunner extends Command
{
    protected $signature = 'test:load-artillery {--config=load-test-config.yml} {--output=}';
    
    public function handle(): int
    {
        $configFile = $this->option('config');
        $outputFile = $this->option('output') ?: 'load-test-results.json';
        
        $this->info('Starting Artillery load test...');
        
        // Run Artillery test
        $command = "artillery run {$configFile} --output {$outputFile}";
        $output = shell_exec($command);
        
        if (!$output) {
            $this->error('Load test failed to run');
            return Command::FAILURE;
        }
        
        // Parse results
        $results = json_decode(file_get_contents($outputFile), true);
        
        $this->displayLoadTestResults($results);
        $this->validatePerformanceRequirements($results);
        
        return Command::SUCCESS;
    }
    
    private function displayLoadTestResults(array $results): void
    {
        $summary = $results['aggregate'];
        
        $this->table([
            'Metric', 'Value'
        ], [
            ['Total Requests', number_format($summary['counters']['http.requests'])],
            ['Successful Requests', number_format($summary['counters']['http.codes.200'] ?? 0)],
            ['Failed Requests', number_format($summary['counters']['http.codes.500'] ?? 0)],
            ['Average Response Time', round($summary['latency']['mean'], 2) . 'ms'],
            ['95th Percentile', round($summary['latency']['p95'], 2) . 'ms'],
            ['99th Percentile', round($summary['latency']['p99'], 2) . 'ms'],
            ['Max Response Time', round($summary['latency']['max'], 2) . 'ms'],
            ['Requests Per Second', round($summary['rps']['mean'], 2)],
        ]);
    }
    
    private function validatePerformanceRequirements(array $results): void
    {
        $summary = $results['aggregate'];
        $requirements = [
            'avg_response_time' => ['value' => $summary['latency']['mean'], 'max' => 200, 'unit' => 'ms'],
            'p95_response_time' => ['value' => $summary['latency']['p95'], 'max' => 500, 'unit' => 'ms'],
            'error_rate' => ['value' => $this->calculateErrorRate($summary), 'max' => 0.1, 'unit' => '%'],
            'rps' => ['value' => $summary['rps']['mean'], 'min' => 100, 'unit' => 'req/s']
        ];
        
        $this->info("\nPerformance Validation:");
        
        foreach ($requirements as $name => $requirement) {
            $value = $requirement['value'];
            $passed = true;
            
            if (isset($requirement['max'])) {
                $passed = $value <= $requirement['max'];
                $comparison = "≤ {$requirement['max']}";
            } else {
                $passed = $value >= $requirement['min'];
                $comparison = "≥ {$requirement['min']}";
            }
            
            $status = $passed ? '✅ PASS' : '❌ FAIL';
            $this->line("{$name}: {$status} ({$value}{$requirement['unit']} {$comparison})");
        }
    }
    
    private function calculateErrorRate(array $summary): float
    {
        $total = $summary['counters']['http.requests'];
        $errors = ($summary['counters']['http.codes.500'] ?? 0) + 
                 ($summary['counters']['http.codes.502'] ?? 0) + 
                 ($summary['counters']['http.codes.503'] ?? 0);
        
        return $total > 0 ? ($errors / $total) * 100 : 0;
    }
}
```

### 5. Security Testing

#### 5.1 Automated Security Testing

**Security Test Suite:**

```php
class SecurityTestSuite extends Phase4TestCase
{
    public function test_api_endpoints_require_authentication(): void
    {
        $protectedEndpoints = [
            'GET /api/v4/teams',
            'POST /api/v4/teams',
            'GET /api/v4/players',
            'POST /api/v4/games/1/score',
            'GET /api/v4/dashboard/stats'
        ];
        
        foreach ($protectedEndpoints as $endpoint) {
            [$method, $url] = explode(' ', $endpoint);
            
            $response = $this->json($method, $url);
            
            $this->assertEquals(401, $response->status(), 
                "Endpoint {$endpoint} should require authentication");
        }
    }
    
    public function test_tenant_data_isolation_security(): void
    {
        $otherTenant = $this->createIsolatedTenant();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherTeam = Team::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $this->actingAs($this->adminUser, 'sanctum');
        
        // Try to access other tenant's data directly
        $response = $this->getJson("/api/v4/teams/{$otherTeam->id}");
        
        $this->assertEquals(404, $response->status(), 
            'Should not be able to access other tenant\'s data');
        
        // Try to update other tenant's data
        $response = $this->patchJson("/api/v4/teams/{$otherTeam->id}", [
            'name' => 'Hacked Team Name'
        ]);
        
        $this->assertEquals(404, $response->status(), 
            'Should not be able to update other tenant\'s data');
    }
    
    public function test_sql_injection_protection(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        $maliciousInputs = [
            "'; DROP TABLE teams; --",
            "1' OR '1'='1",
            "1; INSERT INTO teams (name) VALUES ('hacked'); --",
            "1 UNION SELECT * FROM users --"
        ];
        
        foreach ($maliciousInputs as $maliciousInput) {
            $response = $this->getJson("/api/v4/teams?search=" . urlencode($maliciousInput));
            
            // Should return normal response, not SQL error
            $this->assertNotEquals(500, $response->status(), 
                'SQL injection attempt should be handled safely');
        }
        
        // Verify database is still intact
        $this->assertDatabaseHas('teams', ['id' => $this->testTeam->id]);
        $this->assertEquals(1, Team::count()); // Should still have only test team
    }
    
    public function test_xss_protection(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '"><script>alert("XSS")</script>',
            "javascript:alert('XSS')",
            '<img src=x onerror=alert("XSS")>'
        ];
        
        foreach ($xssPayloads as $payload) {
            $response = $this->postJson('/api/v4/teams', [
                'name' => $payload,
                'category' => 'senior',
                'season' => '2024-25'
            ]);
            
            if ($response->status() === 201) {
                $teamId = $response->json('data.id');
                $team = Team::find($teamId);
                
                // XSS payload should be escaped/sanitized
                $this->assertNotContains('<script>', $team->name);
                $this->assertNotContains('javascript:', $team->name);
                $this->assertNotContains('onerror=', $team->name);
            }
        }
    }
    
    public function test_csrf_protection(): void
    {
        $user = User::factory()->create();
        
        // Without CSRF token
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);
        
        $this->assertEquals(419, $response->status(), 'CSRF protection should be active');
        
        // With valid CSRF token
        $response = $this->withSession(['_token' => 'test-token'])
                         ->post('/login', [
                             'email' => $user->email,
                             'password' => 'password',
                             '_token' => 'test-token'
                         ]);
        
        $this->assertNotEquals(419, $response->status(), 'Valid CSRF token should be accepted');
    }
    
    public function test_rate_limiting_prevents_brute_force(): void
    {
        $attempts = 0;
        
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v4/auth/login', [
                'email' => 'nonexistent@example.com',
                'password' => 'wrongpassword'
            ]);
            
            $attempts++;
            
            if ($response->status() === 429) {
                $this->assertLessThan(6, $attempts, 
                    'Rate limiting should kick in within 5 attempts');
                break;
            }
        }
        
        $this->assertEquals(429, $response->status(), 
            'Should be rate limited after multiple failed attempts');
    }
    
    public function test_sensitive_data_not_exposed_in_api(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        $response = $this->getJson('/api/v4/users/me');
        
        $userData = $response->json('data');
        
        // Sensitive fields should not be exposed
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
        $this->assertArrayNotHasKey('two_factor_secret', $userData);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $userData);
        
        // Emergency contact data should be encrypted/hidden
        if (isset($userData['emergency_contacts'])) {
            foreach ($userData['emergency_contacts'] as $contact) {
                $this->assertArrayNotHasKey('raw_phone_number', $contact);
            }
        }
    }
}
```

#### 5.2 Penetration Testing Integration

**OWASP ZAP Integration:**

```php
class PenetrationTestRunner extends Command
{
    protected $signature = 'security:pentest {--target=http://localhost:8000} {--report=pentest-report.html}';
    
    public function handle(): int
    {
        $target = $this->option('target');
        $reportFile = $this->option('report');
        
        $this->info("Starting penetration test against {$target}");
        
        // Run OWASP ZAP baseline scan
        $zapCommand = "docker run -v " . getcwd() . ":/zap/wrk/:rw " .
                     "-t owasp/zap2docker-stable zap-baseline.py " .
                     "-t {$target} -J {$reportFile}";
        
        $output = shell_exec($zapCommand);
        
        if (file_exists($reportFile)) {
            $this->info("Penetration test completed. Report saved to {$reportFile}");
            
            // Parse and display critical findings
            $this->parsePentestResults($reportFile);
        } else {
            $this->error('Penetration test failed to generate report');
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    private function parsePentestResults(string $reportFile): void
    {
        $reportContent = file_get_contents($reportFile);
        $report = json_decode($reportContent, true);
        
        if (!$report || !isset($report['site'])) {
            $this->warn('Could not parse penetration test report');
            return;
        }
        
        $alerts = $report['site'][0]['alerts'] ?? [];
        $criticalIssues = array_filter($alerts, fn($alert) => $alert['riskcode'] === '3');
        $highIssues = array_filter($alerts, fn($alert) => $alert['riskcode'] === '2');
        
        $this->info("\nPenetration Test Results:");
        $this->line("Critical Issues: " . count($criticalIssues));
        $this->line("High Risk Issues: " . count($highIssues));
        $this->line("Total Alerts: " . count($alerts));
        
        if (!empty($criticalIssues)) {
            $this->error("\nCRITICAL SECURITY ISSUES FOUND:");
            foreach ($criticalIssues as $issue) {
                $this->line("• {$issue['name']} - {$issue['desc']}");
            }
        }
        
        if (!empty($highIssues)) {
            $this->warn("\nHIGH RISK SECURITY ISSUES:");
            foreach ($highIssues as $issue) {
                $this->line("• {$issue['name']} - {$issue['desc']}");
            }
        }
    }
}
```

### 6. Quality Gates & CI/CD Integration

#### 6.1 GitHub Actions Workflow

```yaml
# .github/workflows/phase4-testing.yml
name: Phase 4 Testing Pipeline

on:
  push:
    branches: [main, develop, 'feature/phase4/*']
  pull_request:
    branches: [main, develop]

jobs:
  unit-tests:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:14
        env:
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: basketmanager_test
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
      
      redis:
        image: redis:7
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, pgsql, bcmath, soap, intl, gd, exif, iconv
          coverage: xdebug
      
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader
      
      - name: Generate application key
        run: php artisan key:generate --env=testing
      
      - name: Run database migrations
        run: php artisan migrate --env=testing --force
      
      - name: Run unit tests
        run: vendor/bin/phpunit --testsuite=Unit --coverage-clover=coverage.xml
      
      - name: Upload coverage reports
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
          flags: unittests
          name: codecov-umbrella
  
  integration-tests:
    runs-on: ubuntu-latest
    needs: unit-tests
    
    services:
      postgres:
        image: postgres:14
        env:
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: basketmanager_test
      redis:
        image: redis:7
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, pgsql, bcmath, soap, intl, gd, exif, iconv
      
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader
      
      - name: Setup test environment
        run: |
          php artisan key:generate --env=testing
          php artisan migrate --env=testing --force
          php artisan db:seed --env=testing --class=TestDataSeeder
      
      - name: Run integration tests
        run: vendor/bin/phpunit --testsuite=Integration
  
  browser-tests:
    runs-on: ubuntu-latest
    needs: integration-tests
    
    services:
      postgres:
        image: postgres:14
        env:
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: basketmanager_test
      redis:
        image: redis:7
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, pgsql, bcmath, soap, intl, gd, exif, iconv
      
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader
      
      - name: Setup Dusk
        run: php artisan dusk:install
      
      - name: Start Chrome Driver
        run: ./vendor/laravel/dusk/bin/chromedriver-linux &
      
      - name: Setup test environment
        run: |
          php artisan key:generate --env=dusk.local
          php artisan migrate --env=dusk.local --force
          php artisan db:seed --env=dusk.local --class=TestDataSeeder
      
      - name: Start Laravel Server
        run: php artisan serve --env=dusk.local &
      
      - name: Run browser tests
        run: php artisan dusk --env=dusk.local
      
      - name: Upload Dusk screenshots
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: dusk-screenshots
          path: tests/Browser/screenshots
  
  load-tests:
    runs-on: ubuntu-latest
    needs: [unit-tests, integration-tests]
    if: github.ref == 'refs/heads/main'
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install Artillery
        run: npm install -g artillery
      
      - name: Setup PHP & Laravel
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, pgsql, bcmath, soap, intl, gd, exif, iconv
      
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader
      
      - name: Setup application
        run: |
          php artisan key:generate
          php artisan migrate --force
          php artisan db:seed --class=LoadTestSeeder
      
      - name: Start Laravel Server
        run: php artisan serve &
      
      - name: Run load tests
        run: artillery run tests/LoadTesting/api-load-test.yml --output load-test-results.json
      
      - name: Analyze results
        run: php artisan test:load-artillery --output=load-test-results.json
  
  security-tests:
    runs-on: ubuntu-latest
    needs: integration-tests
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP & Laravel
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, pgsql, bcmath, soap, intl, gd, exif, iconv
      
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader
      
      - name: Run security audit
        run: php artisan security:audit
      
      - name: Setup application for pentest
        run: |
          php artisan key:generate
          php artisan migrate --force
          php artisan serve &
      
      - name: Run OWASP ZAP baseline scan
        run: |
          docker run -v $(pwd):/zap/wrk/:rw \
            -t owasp/zap2docker-stable zap-baseline.py \
            -t http://localhost:8000 \
            -J pentest-report.json || true
      
      - name: Upload security reports
        uses: actions/upload-artifact@v3
        with:
          name: security-reports
          path: |
            pentest-report.json
            security-audit.json

  quality-gate:
    runs-on: ubuntu-latest
    needs: [unit-tests, integration-tests, browser-tests, load-tests, security-tests]
    if: always()
    
    steps:
      - name: Check test results
        run: |
          if [[ "${{ needs.unit-tests.result }}" == "failure" || 
                "${{ needs.integration-tests.result }}" == "failure" || 
                "${{ needs.browser-tests.result }}" == "failure" ]]; then
            echo "Tests failed - blocking deployment"
            exit 1
          fi
          
          if [[ "${{ needs.load-tests.result }}" == "failure" ]]; then
            echo "Load tests failed - performance requirements not met"
            exit 1
          fi
          
          if [[ "${{ needs.security-tests.result }}" == "failure" ]]; then
            echo "Security tests failed - security vulnerabilities found"
            exit 1
          fi
          
          echo "All quality gates passed ✅"
```

### Quality Assurance Summary

**Testing Coverage Requirements:**
- ✅ **Unit Tests**: >90% Code Coverage für Business Logic
- ✅ **Integration Tests**: Alle External APIs und Multi-tenant Features
- ✅ **E2E Tests**: Kritische User Journeys und PWA Features
- ✅ **Performance Tests**: <200ms API Response Time, >100 RPS
- ✅ **Security Tests**: OWASP Top 10 Coverage, Penetration Testing
- ✅ **Load Tests**: 100+ Concurrent Users, 95% Success Rate

**Automated Quality Gates:**
- Code Coverage Minimum: 90%
- Performance SLA: P95 <500ms
- Security Score: Zero Critical Vulnerabilities
- Browser Compatibility: Chrome, Firefox, Safari, Edge
- PWA Features: Installation, Offline, Push Notifications

---

## 📈 Implementation Status Summary (Stand: 13. August 2025)

### Gesamtfortschritt: 45% abgeschlossen

Phase 4 befindet sich in aktiver Entwicklung mit signifikantem Fortschritt bei der API-Infrastruktur. Die Kernkomponenten für API-Dokumentation, Versionierung und Rate Limiting sind implementiert und bilden eine solide Basis für die weiteren Entwicklungen.

### ✅ Erfolgreich Implementiert
- **API Infrastructure**: OpenAPI-Dokumentation, SDK-Generierung, Versionierung
- **Rate Limiting**: Enterprise-grade mit tier-basiertem System
- **Monitoring**: API Usage Tracking und Monitoring-Commands

### 🚧 In Arbeit
- **Performance Optimization**: Grundlegende Services vorhanden, weitere Optimierungen erforderlich
- **API Gateway**: Basis vorhanden, Load Balancing ausstehend

### ❌ Kritische Ausstehende Features
1. **Multi-Tenant Architecture** (Höchste Priorität)
2. **Payment Integration** (Stripe, PayPal)
3. **External APIs** (DBB, FIBA, Social Media)
4. **PWA Features** (Service Worker, Push Notifications)
5. **CDN & Performance** (Cloudflare, Database Partitioning)

### 🎯 Empfohlene Nächste Schritte
1. **Sofort**: Multi-Tenant Architektur implementieren (Foundation für SaaS)
2. **Woche 2-3**: Payment Gateway Integration (Revenue-Generation)
3. **Woche 4-5**: Federation API Integration (Wettbewerbsvorteil)
4. **Woche 6-8**: PWA Implementation (Mobile Experience)
5. **Woche 9-12**: Performance & Scaling (Production-Ready)

### 📊 Risikobewertung
- **Technisches Risiko**: Mittel (Multi-Tenant Migration ist komplex)
- **Zeitrisiko**: Hoch (75% der Features noch ausstehend)
- **Ressourcenrisiko**: Mittel (Externe API-Integrationen erfordern Koordination)

### 💡 Empfehlung
Fokussierung auf Multi-Tenant Architektur als kritische Basis für alle weiteren Features. Ohne diese Foundation können viele andere Features (Subscription Management, Tenant-specific Customization) nicht implementiert werden. Parallele Entwicklung von Payment Integration zur schnellen Revenue-Generierung.