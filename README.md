# 🏀 BasketManager Pro Laravel

**Umfassende Basketball-Vereinsverwaltung mit modernster Laravel-Technologie**

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green.svg)](https://vuejs.org)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

---

## 📋 Projektübersicht

BasketManager Pro ist eine vollständig ausgestattete Basketball-Vereinsverwaltungs-Anwendung, die mit Laravel 12.x entwickelt wurde. Das System bietet umfassende Funktionen für Vereinsverwaltung, Teammanagement, Live-Scoring, Spielerstatistiken, Subscription-Management und Emergency-Services.

### 🆕 Recent Updates (Stand: Oktober 2025)

**Phase 4.4.x - Subscription Analytics (75% Complete)**
- ✅ **13 Stripe Services** - Vollständige Club-Level Subscription Integration
- ✅ **Multi-Club Subscriptions** - Mehrere Clubs pro Tenant mit individuellen Plans
- ✅ **Billing & Payment Management** - Invoice Management, Payment Methods (SEPA, Sofort, Giropay)
- ✅ **Frontend UI** - Vollständiges Subscription Dashboard mit Stripe.js Integration
- 🔄 **Subscription Analytics** - MRR/ARR-Tracking, Churn-Analyse, Cohort Analysis (Backend fertig)
- 📊 **Chart.js Integration** - Analytics-Dashboards in Entwicklung

**Technologie-Updates:**
- ⬆️ Laravel 11.x → **12.x**
- ⬆️ Vite 5.x → **7.0.4**
- ⬆️ Tailwind CSS 3.x → **4.0** (mit @tailwindcss/vite)
- 📈 **63+ Test-Dateien** (↑ von 37+)
- 🏗️ **69 Models**, **53 Services**, **68 Controllers**

### 🎯 Hauptfunktionen

- **🏀 Vereinsverwaltung**: Multi-Tenant-Architektur für mehrere Vereine
- **👥 Teammanagement**: Vollständige Team- und Spielerverwaltung
- **📊 Live-Scoring**: Real-time Spielstatistiken mit WebSocket-Broadcasting
- **📈 Analytics**: Umfassende Statistiken und ML-basierte Vorhersagen
- **🚨 Emergency System**: QR-Code-basiertes Notfallkontaktsystem
- **🔒 GDPR-Compliance**: Vollständige DSGVO-konforme Datenverwaltung
- **💳 Stripe Integration**: Multi-Club Subscription Management (13 Services)
- **💰 Subscription Analytics**: MRR/ARR-Tracking, Churn-Analyse, Cohorts
- **📱 PWA**: Progressive Web App mit Offline-Funktionalität

---

## 🏗️ Technologie-Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP Version**: 8.2+
- **Datenbank**: MySQL 8.0+ / PostgreSQL 14+
- **Cache/Queue**: Redis 7.0+
- **Authentication**: Laravel Sanctum 4.0 + Jetstream 5.3
- **Permissions**: Spatie Laravel Permission 6.21+
- **Search**: Laravel Scout
- **Payments**: Laravel Cashier 15.7+ (Stripe)

### Frontend
- **Framework**: Vue.js 3.3+
- **Build Tool**: Vite 7.0+
- **CSS Framework**: Tailwind CSS 4.0 (@tailwindcss/vite)
- **UI Components**: Headless UI + Heroicons
- **State Management**: Inertia.js 2.0
- **Charts**: Chart.js 4.5+
- **Payments UI**: Stripe.js 8.2+
- **Date Handling**: date-fns 4.1+

### DevOps & Infrastructure
- **Containerization**: Docker
- **CI/CD**: GitHub Actions (bereit)
- **Monitoring**: Laravel Telescope
- **Payment**: Stripe (Laravel Cashier)
- **Real-time**: Laravel Broadcasting + WebSockets

---

## 🚀 Installation

### Voraussetzungen

- PHP 8.2+
- Node.js 18+
- Composer 2.x
- MySQL 8.0+ / PostgreSQL 14+
- Redis 7.0+

### Installation Schritte

1. **Repository klonen**
   ```bash
   git clone <repository-url>
   cd BasketManagerProLaravel
   ```

2. **Dependencies installieren**
   ```bash
   composer install
   npm install
   ```

3. **Environment konfigurieren**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Datenbank einrichten**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Assets kompilieren**
   ```bash
   npm run build
   ```

6. **Laravel starten**
   ```bash
   php artisan serve
   ```

### 🐳 Docker Installation

```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

---

## 📚 Projektstruktur

### 🏆 Entwicklungsphasen

Das Projekt wurde in 5 Phasen entwickelt:

#### Phase 1: Core Foundation ✅ **ABGESCHLOSSEN**
- Laravel Foundation mit Authentication
- RBAC-System (10+ Rollen, 80+ Permissions)
- Core Models (User, Club, Team, Player)
- API V2 mit vollständigen CRUD-Operationen
- Rollenbasierte Dashboards

#### Phase 2: Game & Statistics ✅ **IMPLEMENTIERT** 
- Game Management System
- Live-Scoring mit Real-time Updates
- Statistics Engine
- Broadcasting System
- Export-Funktionen (PDF, Excel, CSV)

#### Phase 3: Advanced Features ✅ **IMPLEMENTIERT**
- Training & Drill Management
- Tournament Management
- Video Analysis mit AI-Integration
- ML-basierte Predictive Analytics
- Shot Charts & Heat Maps (vorbereitet)

#### Phase 4: Integration & Scaling 🔄 **IN ARBEIT (Phase 4.4.x)**
- ✅ Multi-Tenant Architecture (ABGESCHLOSSEN)
- ✅ Stripe Payment Integration (ABGESCHLOSSEN)
  - Phase 4.4.1: Club-Level Subscriptions ✅
  - Phase 4.4.2: Billing & Payment Management ✅
  - Phase 4.4.x: Subscription Analytics (75% - IN ARBEIT)
- ✅ Federation APIs (DBB, FIBA) (ABGESCHLOSSEN)
- ✅ Progressive Web App (PWA) (ABGESCHLOSSEN)
- ✅ API Documentation & SDK Generation (ABGESCHLOSSEN)

#### Phase 5: Emergency & Compliance ✅ **VOLLSTÄNDIG**
- Emergency Contact System mit QR-Codes
- GDPR/DSGVO Compliance Engine
- Security & Audit Framework
- Mobile Emergency Interface

---

## 🔧 Entwicklung

### Verfügbare Commands

```bash
# Development (⭐ Empfohlen)
composer dev                        # Startet Server + Queue + Logs + Vite parallel
                                    # Nutzt concurrently mit farbcodierter Ausgabe

# Alternative: Einzelne Prozesse
php artisan serve                    # Laravel Server starten (Port 8000)
php artisan queue:listen --tries=1  # Queue Worker starten
php artisan pail --timeout=0        # Real-time Log Viewer (Laravel Pail)
php artisan schedule:work           # Scheduler starten
npm run dev                         # Frontend Development (Vite)

# Testing
php artisan test                    # Tests ausführen
./vendor/bin/phpunit               # PHPUnit Tests
./vendor/bin/phpstan analyse       # Static Analysis

# Code Quality
./vendor/bin/pint                  # Laravel Pint (Code Formatting)

# Datenbank
php artisan migrate:fresh --seed   # Datenbank zurücksetzen
php artisan tinker                 # Laravel Tinker Console

# API Dokumentation
php artisan generate:openapi-docs  # OpenAPI Dokumentation generieren

# Tenant Management
php artisan tenant:setup-rls       # Row Level Security einrichten
php artisan tenant:usage:reset     # Tenant Usage zurücksetzen
```

### 🧪 Testing

Das Projekt verfügt über **71+ Test-Dateien** mit umfassender Coverage:

**Projektstatistiken:**
- **69 Models** - Umfassende Basketball-Domain-Modelle
- **53 Services** - Service-orientierte Architektur
- **68 Controllers** - API & Web Controllers
- **71 Test-Dateien** - Feature, Unit & Integration Tests

```bash
# Alle Tests ausführen
php artisan test

# Spezifische Test-Suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Mit Coverage
php artisan test --coverage
```

#### Subscription & Billing Testing

Das Multi-Club Subscription-System verfügt über **40 umfassende Tests** (~4,350 Zeilen):

- **23 Integration Tests** - Stripe Webhook-Events & System-Verhalten
- **17 E2E Tests** - Kompletter Checkout-Flow mit Payment-Szenarien
- **100% Coverage** für alle kritischen Services

```bash
# Alle Subscription-Tests ausführen
php artisan test --filter=ClubSubscription

# Integration Tests (Webhooks)
php artisan test tests/Integration/ClubSubscriptionWebhookTest.php

# E2E Tests (Checkout-Flow)
php artisan test tests/Feature/ClubCheckoutE2ETest.php

# Mit Coverage-Report
php artisan test --filter=ClubSubscription --coverage
```

**Detaillierte Dokumentation:** Siehe [Subscription Testing Guide](docs/SUBSCRIPTION_TESTING.md)

---

## 🏀 Basketball-spezifische Features

### Core Basketball Entities
- **Teams**: Vollständige Teamverwaltung mit Hierarchien
- **Players**: Detaillierte Spielerprofile mit Statistiken
- **Games**: Live-Scoring mit Real-time Broadcasting
- **Statistics**: 20+ Basketball-Metriken automatisch berechnet
- **Training**: Drill-Management und Performance-Tracking

### Basketball Rules Integration
- Positionsvalidierung (Point Guard, Shooting Guard, etc.)
- Jersey-Nummer-Validierung
- Spielzeit-Management (4x10 Min, 2x20 Min)
- Basketball-spezifische Statistiken (FG%, 3P%, FT%, etc.)

---

## 🔐 Sicherheit & Compliance

### GDPR/DSGVO Compliance
- **Artikel 15**: Auskunftsrecht (Data Export)
- **Artikel 17**: Recht auf Löschung (Right to be Forgotten)
- **Artikel 20**: Datenportabilität (Machine-readable Export)
- **Artikel 30**: Verzeichnis von Verarbeitungstätigkeiten

### Security Features
- **Multi-Factor Authentication (2FA)**
- **Rate Limiting** mit Enterprise-Features
- **Row Level Security** für Multi-Tenancy
- **Security Event Monitoring**
- **CORS Protection**
- **SQL Injection Prevention**

### Emergency System
- **QR-Code basierter Notfall-Zugriff**
- **Offline-Emergency-Interface (PWA)**
- **Automatische Notfallkontakt-Benachrichtigung**
- **Emergency Incident Tracking**

---

## 🌐 API Dokumentation

### REST API V2
- **Base URL**: `/api/v2`
- **Authentication**: Laravel Sanctum
- **Rate Limiting**: Tenant-basiert
- **OpenAPI 3.0** Dokumentation verfügbar

### Hauptendpunkte
```
GET    /api/v2/users           # User-Management
GET    /api/v2/clubs           # Club-Management  
GET    /api/v2/teams           # Team-Management
GET    /api/v2/players         # Player-Management
POST   /api/v2/games/live      # Live-Scoring
```

### SDK Generation
Automatische SDK-Generierung für:
- **PHP SDK** (`storage/api-docs/sdk-php.stub`)
- **JavaScript SDK** (`storage/api-docs/sdk-javascript.stub`)
- **Python SDK** (`storage/api-docs/sdk-python.stub`)

---

## 💳 Subscription & Billing

### Stripe Integration (13 Services)

**Club-Level Subscription Management:**
- `ClubSubscriptionService` - Plan-Verwaltung, Cancellation, Swapping
- `ClubSubscriptionCheckoutService` - Checkout-Flow für Clubs
- `ClubStripeCustomerService` - Stripe Customer Management
- `ClubInvoiceService` - Invoice Management & PDF-Download
- `ClubPaymentMethodService` - Payment Methods (Card, SEPA, Sofort, Giropay)
- `SubscriptionAnalyticsService` - MRR/ARR, Churn-Analyse, Cohorts

**Tenant-Level Services:**
- `StripeSubscriptionService` - Tenant Subscriptions
- `StripePaymentService` - Payment Processing
- `CashierTenantManager` - Multi-Tenant Cashier Integration

**Infrastructure:**
- `CheckoutService` - General Checkout Logic
- `PaymentMethodService` - Payment Method Handling
- `StripeClientManager` - Stripe Client Configuration
- `WebhookEventProcessor` - Webhook Event Handling (16+ Events)

### Subscription Features
- ✅ **Multi-Club Subscriptions** (mehrere Clubs pro Tenant)
- ✅ **Deutsche Zahlungsmethoden** (SEPA, Sofort, Giropay, EPS, iDEAL)
- ✅ **Automated Invoice Generation** mit PDF-Download
- ✅ **Billing Portal** für Self-Service
- ✅ **Proration Preview** bei Plan-Wechsel
- ✅ **Subscription Analytics** (MRR, ARR, Churn)
- ✅ **Usage Tracking** pro Club
- **Subscription Tiers**: Free → Basic → Professional → Enterprise

### Feature Gates
```php
// Club-basierte Feature-Kontrolle
if ($club->hasFeature('live_scoring')) {
    // Live Scoring Feature für diesen Club verfügbar
}

// Hierarchische Limit-Checks (Tenant → Club)
if ($club->canUse('max_teams', 5)) {
    // Club kann 5 Teams verwenden
}
```

---

## 💰 Subscription Analytics (Phase 4.4.x - 75%)

### Business Metrics Tracking

**SubscriptionAnalyticsService** bietet umfassende SaaS-Metriken:

#### Key Metrics
- **MRR (Monthly Recurring Revenue)** - Monatlich wiederkehrende Umsätze
- **ARR (Annual Recurring Revenue)** - Jährlich hochgerechnete Umsätze
- **Churn Rate** - Abwanderungsrate von Clubs
- **Customer Lifetime Value (LTV)** - Durchschnittlicher Kundenwert
- **Cohort Analysis** - Kohortenbasierte Retention-Analyse

#### Verfügbare Analytics

**Revenue Analytics:**
```php
// MRR-Berechnung für Tenant
$mrr = $analyticsService->calculateMRR($tenant);

// ARR-Hochrechnung
$arr = $analyticsService->calculateARR($tenant);

// Revenue-Breakdown nach Plan
$breakdown = $analyticsService->getRevenueBreakdown($tenant);
```

**Churn Analysis:**
```php
// Churn-Rate berechnen
$churnRate = $analyticsService->calculateChurnRate($tenant, $period);

// Retention-Metriken
$retention = $analyticsService->getRetentionMetrics($tenant);
```

**Cohort Analysis:**
```php
// Cohort-Performance tracken
$cohortData = $analyticsService->analyzeCohort($tenant, $startDate);

// Subscription-Events tracken
$events = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
    ->where('event_type', 'subscription_created')
    ->get();
```

### Analytics Models

- **SubscriptionMRRSnapshot** - Tägliche MRR-Snapshots
- **ClubSubscriptionEvent** - Subscription Lifecycle Events
- **ClubSubscriptionCohort** - Kohortenanalyse-Daten

### Visualisierung (in Arbeit)

- 📊 **MRR/ARR Charts** mit Chart.js
- 📈 **Churn Dashboards** mit Trend-Analysen
- 🎯 **Cohort Retention Heatmaps**
- 💹 **Revenue Forecasting** mit ML-Integration

---

## 📱 Progressive Web App (PWA)

### PWA Features
- **Offline-Funktionalität** für Emergency-Access
- **Push Notifications** für Live-Game Updates
- **Install-Prompts** für mobile Geräte
- **Service Worker** für Caching-Strategien

### Emergency Offline Mode
- **Offline Emergency Contact Access**
- **QR-Code Scanning ohne Internet**
- **Local Storage für kritische Daten**

---

## 🤝 Beitragen

### Development Workflow
1. Fork das Repository
2. Feature Branch erstellen (`git checkout -b feature/amazing-feature`)
3. Commits mit aussagekräftigen Nachrichten
4. Tests schreiben und sicherstellen, dass sie bestehen
5. Pull Request erstellen

### Code Style
- **Laravel Pint** für Code Formatting
- **PHPStan** für Static Analysis
- **Vue 3 Composition API** für Frontend
- **Deutsche Kommentare** in Business Logic

---

## 📈 Performance & Monitoring

### Database Optimization
- **Row Level Security** für Multi-Tenancy
- **Database Performance Monitoring**
- **Memory Optimization Service**
- **Query Optimization**

### Real-time Features
- **WebSocket Broadcasting** für Live-Scoring
- **Redis für Caching** und Session Management
- **Queue System** für aufwändige Operationen

---

## 🚨 Emergency Features

### QR-Code Emergency System
```
https://basketmanager.pro/emergency/{qr_code}
```

### Emergency Access
- **Offline-fähige Emergency-Contacts**
- **QR-Code basierter Zugriff ohne Login**
- **Automatic Emergency Incident Logging**
- **Multi-Language Emergency Interface**

---

## 📞 Support & Kontakt

### Dokumentation
- **API Docs**: `/api/documentation`
- **OpenAPI Spec**: `/storage/api-docs/openapi.json`
- **Admin Dashboard**: `/admin/dashboard`

### Support
- **GitHub Issues** für Bug Reports
- **Wiki** für detaillierte Dokumentation
- **Discussion Board** für Feature Requests

---

## 📄 Lizenz

Dieses Projekt ist unter der **MIT License** lizenziert. Siehe [LICENSE](LICENSE) für Details.

---

## 🎯 Roadmap

### ⏳ In Arbeit (Aktuell)
- 🔄 **Phase 4.4.x**: Subscription Analytics (75% Complete)
  - [x] SubscriptionAnalyticsService Backend
  - [x] Analytics Models (MRRSnapshot, Events, Cohorts)
  - [x] Revenue & Churn Calculation
  - [ ] Frontend Analytics Dashboard
  - [ ] Chart.js Visualizations
  - [ ] Cohort Retention Heatmaps

### Kurzfristig (Q1 2025)
- [ ] **Phase 4.4.x Abschluss**: Analytics Dashboard UI
- [ ] Shot Charts & Heat Maps UI für Basketball
- [ ] Performance Optimierung (Caching, Query-Optimierung)
- [ ] Enhanced Testing Coverage (80%+ Ziel)

### Mittelfristig (Q2-Q3 2025)
- [ ] **Mobile App** (React Native) Entwicklung
- [ ] Federation API **Live-Integration** (DBB, FIBA)
- [ ] Video Analysis UI Vervollständigung
- [ ] Advanced ML Model Training & Deployment
- [ ] Advanced Tournament Brackets UI
- [ ] Social Features Integration (Team-Chat, Player-Feed)

### Langfristig (Q4 2025+)
- [ ] Multi-Language Expansion
- [ ] International Federation Support
- [ ] Advanced AI Coaching Features
- [ ] VR/AR Integration Vorbereitung

---

## 📊 Projekt-Status

**BasketManager Pro Laravel** ist eine **Production-Ready** Basketball-Vereinsverwaltung mit:

- ✅ **Phase 1-3**: Vollständig abgeschlossen (Core, Game System, Training & Advanced Features)
- 🔄 **Phase 4**: 95% Complete (nur Analytics Dashboard UI ausstehend)
- ✅ **Phase 5**: Vollständig abgeschlossen (Emergency & Compliance)

**Technische Exzellenz:**
- 🏗️ **69 Models** - Vollständige Basketball-Domain
- 🔧 **53 Services** - Service-orientierte Clean Architecture
- 🎮 **68 Controllers** - REST API & Web
- ✅ **63+ Tests** - Umfassende Test-Coverage
- 💳 **13 Stripe Services** - Enterprise Subscription Management

**Enterprise-Features:**
- Multi-Tenant Architecture mit Row-Level Security
- GDPR/DSGVO vollständig compliant
- Real-time Broadcasting für Live-Games
- Progressive Web App (PWA) mit Offline-Support
- Subscription Analytics (MRR/ARR/Churn)

---

**🏀 BasketManager Pro Laravel - Die Zukunft der Basketball-Vereinsverwaltung**

*Entwickelt mit ❤️ für die Basketball-Community*
*Stand: Oktober 2025 - Version 4.4.x*