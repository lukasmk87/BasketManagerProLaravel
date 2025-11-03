# 🏀 BasketManager Pro Laravel - Installation Guide

Eine umfassende Anleitung für die Installation und Konfiguration von BasketManager Pro, einer Laravel-basierten Basketball-Vereinsverwaltungs-Anwendung.

## 📋 Inhaltsverzeichnis

1. [System-Anforderungen](#system-anforderungen)
2. [Basis-Installation](#basis-installation)
3. [Datenbank Setup](#datenbank-setup)
4. [Frontend Build](#frontend-build)
5. [Laravel Services Konfiguration](#laravel-services-konfiguration)
6. [Development Tools](#development-tools)
7. [Production Setup](#production-setup)
8. [Stripe & Subscription System](#stripe--subscription-system)
9. [Feature Flag System](#feature-flag-system)
10. [Subscription Health Monitoring](#subscription-health-monitoring)
11. [Basketball-spezifische Features](#basketball-spezifische-features)
12. [Testing Setup](#testing-setup)
13. [Troubleshooting](#troubleshooting)
14. [Dokumentation & Referenzen](#dokumentation--referenzen)

---

## 🔧 System-Anforderungen

### Minimale Anforderungen

- **PHP:** 8.2 oder höher
- **Laravel Framework:** ^12.0
- **Node.js:** 18.0 oder höher
- **NPM:** 9.0 oder höher (alternativ Yarn)
- **Composer:** 2.0 oder höher
- **Git:** Für Repository-Management

### Datenbank
- **MySQL:** 8.0+ (empfohlen)
- **PostgreSQL:** 14+ (alternative)
- **SQLite:** Für Development/Testing

### Zusätzliche Services
- **Redis:** 7.0+ (für Cache/Queue/Sessions, erforderlich)
- **Stripe Account:** Für Subscription-Management (erforderlich für Produktion)
- **Meilisearch/Algolia:** Für Laravel Scout (optional)

### PHP Extensions
```bash
# Erforderliche PHP Extensions prüfen
php -m | grep -E "(bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml|zip|gd|curl|exif)"
```

---

## 🚀 Basis-Installation

### 1. Repository klonen

```bash
# HTTPS
git clone https://github.com/[username]/BasketManagerProLaravel.git
cd BasketManagerProLaravel

# SSH (wenn konfiguriert)
git clone git@github.com:[username]/BasketManagerProLaravel.git
cd BasketManagerProLaravel
```

### 2. Environment Setup

```bash
# .env Datei erstellen
cp .env.example .env

# Laravel App Key generieren (wird automatisch bei composer install gemacht)
php artisan key:generate
```

### 3. Composer Dependencies installieren

```bash
# Production Dependencies
composer install --optimize-autoloader

# Development Dependencies (nur für Development)
composer install
```

### 4. NPM Dependencies installieren

```bash
# Mit NPM
npm install

# Oder mit Yarn
yarn install
```

---

## 🗄️ Datenbank Setup

### 1. Datenbank erstellen

#### MySQL/MariaDB
```sql
CREATE DATABASE basketmanager_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'basketmanager'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON basketmanager_pro.* TO 'basketmanager'@'localhost';
FLUSH PRIVILEGES;
```

#### PostgreSQL
```sql
CREATE DATABASE basketmanager_pro;
CREATE USER basketmanager WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE basketmanager_pro TO basketmanager;
```

### 2. .env Konfiguration

```env
# Basis App Configuration
APP_NAME="BasketManager Pro"
APP_ENV=local  # production für Live-System
APP_KEY=base64:GENERATED_KEY_HERE
APP_DEBUG=true  # false für Production
APP_TIMEZONE=Europe/Berlin
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basketmanager_pro
DB_USERNAME=basketmanager
DB_PASSWORD=secure_password

# Redis Configuration (für Cache/Queue/Sessions)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache Configuration
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@basketmanager.local"
MAIL_FROM_NAME="${APP_NAME}"

# Broadcasting (für Real-time Features)
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# Stripe Configuration (Subscription System)
STRIPE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
STRIPE_WEBHOOK_SECRET_CLUB=whsec_your_club_webhook_secret_here
VITE_STRIPE_KEY="${STRIPE_KEY}"

# Stripe Product & Price IDs (nach Stripe Setup konfigurieren)
STRIPE_STANDARD_CLUB_PRODUCT_ID=prod_standard
STRIPE_STANDARD_CLUB_PRICE_MONTHLY=price_standard_monthly
STRIPE_STANDARD_CLUB_PRICE_YEARLY=price_standard_yearly
STRIPE_PREMIUM_CLUB_PRODUCT_ID=prod_premium
STRIPE_PREMIUM_CLUB_PRICE_MONTHLY=price_premium_monthly
STRIPE_PREMIUM_CLUB_PRICE_YEARLY=price_premium_yearly
STRIPE_ENTERPRISE_CLUB_PRODUCT_ID=prod_enterprise
STRIPE_ENTERPRISE_CLUB_PRICE_MONTHLY=price_enterprise_monthly
STRIPE_ENTERPRISE_CLUB_PRICE_YEARLY=price_enterprise_yearly

# Feature Flag System
FEATURE_CLUB_SUBSCRIPTIONS_ENABLED=false
FEATURE_CLUB_SUBSCRIPTIONS_CHECKOUT_ENABLED=false
FEATURE_CLUB_SUBSCRIPTIONS_BILLING_PORTAL_ENABLED=false
FEATURE_CLUB_SUBSCRIPTIONS_PLAN_SWAP_ENABLED=false
FEATURE_CLUB_SUBSCRIPTIONS_ANALYTICS_ENABLED=false
FEATURE_CLUB_SUBSCRIPTIONS_NOTIFICATIONS_ENABLED=false

# Feature Flag Rollout Configuration
FEATURE_ROLLOUT_METHOD=percentage
FEATURE_ROLLOUT_WHITELIST_TENANTS=
FEATURE_CLUB_SUBSCRIPTIONS_ROLLOUT=100
FEATURE_FLAG_PERSISTENCE_ENABLED=true
FEATURE_FLAG_CACHE_TTL=3600
FEATURE_FLAG_LOG_CHANGES=true
FEATURE_BETA_OPT_IN_REQUIRED=true
FEATURE_FLAG_DEVELOPMENT_MODE=false

# Laravel Scout (für Search)
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=null

# File Storage
FILESYSTEM_DISK=local  # s3 für Production mit AWS

# Basketball-spezifische Settings
BASKETBALL_SEASON_START_MONTH=9
BASKETBALL_DEFAULT_ROSTER_SIZE=15
EMERGENCY_QR_ENCRYPTION_KEY=generate-secure-key-here
```

**Hinweis:** Für Production verwende `.env.production.example` als Vorlage, die zusätzliche Sicherheits- und Performance-Einstellungen enthält.

### 3. Datenbank Migrationen ausführen

```bash
# Datenbank Tabellen erstellen (117 Migrationen)
php artisan migrate

# Mit Seed-Daten (für Development)
php artisan migrate --seed

# Oder separat seeden
php artisan db:seed

# WICHTIG: Club Subscription Plans erstellen und mit Stripe synchronisieren
php artisan db:seed --class=ClubSubscriptionPlanSeeder

# Für Production: Mit Stripe synchronisieren
php artisan db:seed --class=ClubSubscriptionPlanSeeder --sync-stripe
```

**Hinweis:** Das Subscription-System benötigt die Seeders für Club Subscription Plans. Der `--sync-stripe` Flag erstellt automatisch die Produkte und Preise in Stripe (nur für Production/Test-Umgebungen mit gültigen Stripe-Keys verwenden).

### 4. Storage Links erstellen

```bash
# Public Storage Link erstellen
php artisan storage:link
```

---

## 🎨 Frontend Build

### 1. Asset Compilation

#### Development
```bash
# Development Build mit Hot Reload
npm run dev

# Einmaliger Development Build
npm run build
```

#### Production
```bash
# Production Build (optimiert/minifiziert)
npm run build
```

### 2. Chart.js Installation (falls nicht automatisch installiert)

```bash
# Chart.js für Basketball-Statistiken
npm install chart.js
```

### 3. Vite Konfiguration prüfen

Die `vite.config.js` sollte bereits korrekt konfiguriert sein:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
```

---

## ⚙️ Laravel Services Konfiguration

### 1. Laravel Telescope (Development)

```bash
# Telescope installieren (nur Development)
php artisan telescope:install
php artisan migrate
```

### 2. Spatie Packages konfigurieren

```bash
# Spatie Permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Spatie Activity Log
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"

# Spatie Media Library
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"

# Migrationen ausführen
php artisan migrate
```

### 3. Laravel Jetstream Setup

```bash
# Jetstream Views publizieren (optional, für Anpassungen)
php artisan vendor:publish --tag=jetstream-views
```

### 4. Queue Workers konfigurieren

#### Development
```bash
# Queue Worker starten
php artisan queue:work

# Mit Supervisor für Production
sudo nano /etc/supervisor/conf.d/basketmanager-queue.conf
```

#### Supervisor Configuration (Production)
```ini
[program:basketmanager-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue.log
stopwaitsecs=3600
```

### 5. Stripe & Subscription System Setup

Das Multi-Club Subscription-System ist ein zentraler Bestandteil der Anwendung und erfordert eine sorgfältige Konfiguration.

#### 5.1 Stripe Account Setup

1. **Stripe Account erstellen** (falls noch nicht vorhanden):
   - Registrierung unter https://dashboard.stripe.com/register
   - Verifizierung des Accounts abschließen

2. **API Keys abrufen**:
   ```
   Dashboard → Developers → API keys

   Test Keys (Development):
   - Publishable key: pk_test_...
   - Secret key: sk_test_...

   Live Keys (Production):
   - Publishable key: pk_live_...
   - Secret key: sk_live_...
   ```

3. **Webhook Endpoints konfigurieren**:
   ```bash
   # Webhook Endpoints erstellen
   Dashboard → Developers → Webhooks → Add endpoint

   Endpoint URL: https://your-domain.com/webhooks/stripe/club-subscriptions

   Events to listen for:
   - checkout.session.completed
   - customer.subscription.created
   - customer.subscription.updated
   - customer.subscription.deleted
   - invoice.payment_succeeded
   - invoice.payment_failed
   - invoice.created
   - invoice.finalized
   - invoice.payment_action_required
   - payment_method.attached
   - payment_method.detached
   ```

4. **Webhook Signing Secret kopieren**:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_...
   STRIPE_WEBHOOK_SECRET_CLUB=whsec_...
   ```

#### 5.2 Subscription Plans in Stripe erstellen

```bash
# Automatisch mit Seeder:
php artisan db:seed --class=ClubSubscriptionPlanSeeder --sync-stripe

# Manuell über Stripe Dashboard:
# Dashboard → Products → Add product
#
# Plan-Struktur:
# 1. Free Club (€0) - 2 Teams, 30 Spieler
# 2. Standard Club (€49/mo, €441/yr) - 10 Teams, 150 Spieler
# 3. Premium Club (€149/mo, €1,341/yr) - 50 Teams, 500 Spieler
# 4. Enterprise Club (€299/mo, €2,691/yr) - 100 Teams, 1000 Spieler
```

#### 5.3 Stripe Product & Price IDs konfigurieren

Nach dem Erstellen der Produkte in Stripe, IDs in `.env` eintragen:

```env
# Stripe Product IDs aus Dashboard kopieren
STRIPE_STANDARD_CLUB_PRODUCT_ID=prod_xxxxx
STRIPE_STANDARD_CLUB_PRICE_MONTHLY=price_xxxxx
STRIPE_STANDARD_CLUB_PRICE_YEARLY=price_xxxxx

STRIPE_PREMIUM_CLUB_PRODUCT_ID=prod_xxxxx
STRIPE_PREMIUM_CLUB_PRICE_MONTHLY=price_xxxxx
STRIPE_PREMIUM_CLUB_PRICE_YEARLY=price_xxxxx

STRIPE_ENTERPRISE_CLUB_PRODUCT_ID=prod_xxxxx
STRIPE_ENTERPRISE_CLUB_PRICE_MONTHLY=price_xxxxx
STRIPE_ENTERPRISE_CLUB_PRICE_YEARLY=price_xxxxx
```

#### 5.4 Payment Methods aktivieren

Im Stripe Dashboard aktivieren:
```
Dashboard → Settings → Payment methods

Empfohlene Payment Methods (deutscher Markt):
✅ Cards (Visa, Mastercard, Amex)
✅ SEPA Direct Debit
✅ Sofort
✅ Giropay
✅ EPS (Österreich)
✅ Bancontact (Belgien)
✅ iDEAL (Niederlande)
```

#### 5.5 Subscription Analytics Setup

```bash
# MRR (Monthly Recurring Revenue) Tracking initialisieren
php artisan subscription:update-mrr

# Cohort Analytics erstellen
php artisan subscription:update-cohorts

# Churn Rate berechnen
php artisan subscription:calculate-churn

# Vollständiger Analytics Report
php artisan subscription:analytics-report
```

#### 5.6 Subscription Health Monitoring

```bash
# Health Check ausführen
php artisan subscriptions:health-check

# Mit E-Mail Alerts für Admins
php artisan subscriptions:health-check --alert

# Health Check API Endpoint:
# GET /api/health/subscriptions
```

### 6. Feature Flag System Setup

Das Feature Flag System ermöglicht kontrollierte Rollouts neuer Features.

#### 6.1 Feature Flag Datenbank Migration

```bash
# Feature Flags Tabelle erstellen
php artisan migrate
```

Die Migration `create_feature_flags_table` erstellt die Tabelle automatisch mit allen Migrations.

#### 6.2 Feature Flags konfigurieren

In `.env` die gewünschten Features aktivieren:

```env
# Core Subscription Features
FEATURE_CLUB_SUBSCRIPTIONS_ENABLED=true
FEATURE_CLUB_SUBSCRIPTIONS_CHECKOUT_ENABLED=true
FEATURE_CLUB_SUBSCRIPTIONS_BILLING_PORTAL_ENABLED=true
FEATURE_CLUB_SUBSCRIPTIONS_PLAN_SWAP_ENABLED=true
FEATURE_CLUB_SUBSCRIPTIONS_ANALYTICS_ENABLED=true
FEATURE_CLUB_SUBSCRIPTIONS_NOTIFICATIONS_ENABLED=true
```

#### 6.3 Rollout-Strategien konfigurieren

```env
# Rollout-Methode wählen:
# - percentage: Schrittweise an X% der Tenants ausrollen
# - whitelist: Nur für spezifische Tenants aktivieren
# - all: Für alle aktivieren

FEATURE_ROLLOUT_METHOD=percentage

# Whitelist-Beispiel (Tenant-IDs):
FEATURE_ROLLOUT_WHITELIST_TENANTS=1,5,12,34

# Prozentuale Rollout-Einstellungen:
FEATURE_CLUB_SUBSCRIPTIONS_ROLLOUT=100  # 100% = alle Tenants

# Cache & Persistence
FEATURE_FLAG_PERSISTENCE_ENABLED=true
FEATURE_FLAG_CACHE_TTL=3600
FEATURE_FLAG_LOG_CHANGES=true

# Beta-Features
FEATURE_BETA_OPT_IN_REQUIRED=true
FEATURE_FLAG_DEVELOPMENT_MODE=false  # true für Development
```

#### 6.4 Feature Flags im Code nutzen

```php
// Service-basierter Check
$featureFlagService = app(FeatureFlagService::class);
if ($featureFlagService->isEnabled('club_subscriptions', $tenant)) {
    // Feature ist aktiv
}

// Middleware-basierter Schutz
Route::middleware(['check.feature:club_subscriptions'])->group(function () {
    // Routes nur wenn Feature aktiv
});
```

---

## 🛠️ Development Tools

### 1. Development Server starten

#### Alle Services gleichzeitig (⭐ EMPFOHLEN)
```bash
# Composer Script verwenden - startet ALLE Services parallel mit farbiger Ausgabe
composer dev

# Startet automatisch:
# - Laravel Server (Port 8000) - blau
# - Queue Worker (mit retry) - lila
# - Laravel Pail (Logs) - rosa
# - Vite Dev Server (HMR) - orange
```

Dieser Command nutzt `npx concurrently` mit farbcodierter Ausgabe für bessere Übersichtlichkeit während der Entwicklung.

#### Einzelne Commands (optional)
```bash
# Laravel Development Server
php artisan serve
# → http://localhost:8000

# Frontend Build Watch (Hot Module Replacement)
npm run dev

# Queue Worker
php artisan queue:work
# Oder mit automatischem Retry:
php artisan queue:listen --tries=1

# Laravel Pail - Real-time Log Viewer (NEU seit Laravel 11)
php artisan pail
# Mit unbegrenztem Timeout:
php artisan pail --timeout=0

# Task Scheduler (für Subscription Analytics)
php artisan schedule:work
```

**Laravel Pail** ist ein neues Development-Tool (Laravel Pail ^1.2.2) für elegantes Log-Streaming direkt im Terminal mit Syntax-Highlighting und Filter-Optionen.

### 2. Debug Tools aktivieren

```env
# .env für Development
APP_DEBUG=true
LOG_LEVEL=debug

# Laravel Debugbar aktivieren
DEBUGBAR_ENABLED=true

# Telescope aktivieren
TELESCOPE_ENABLED=true
```

### 3. Testing Commands

```bash
# Tests ausführen
composer run test

# Oder manuell
php artisan test

# Mit Coverage
php artisan test --coverage
```

---

## 🌐 Production Setup

### 1. Environment Optimierung

```env
# .env für Production
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

# Cache aktivieren
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Broadcasting für Production
BROADCAST_CONNECTION=pusher
```

### 2. Laravel Optimierungen

```bash
# Config Cache
php artisan config:cache

# Route Cache
php artisan route:cache

# View Cache
php artisan view:cache

# Event Cache
php artisan event:cache

# Autoloader optimieren
composer install --optimize-autoloader --no-dev

# Assets kompilieren
npm run build
```

### 3. File Permissions

```bash
# Storage und Bootstrap Cache Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Oder für spezifische Setups
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name basketmanager.yourdomain.com;
    root /path/to/basketmanager/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. SSL Setup mit Certbot

```bash
# Certbot installieren
sudo apt install certbot python3-certbot-nginx

# SSL Zertifikat generieren
sudo certbot --nginx -d basketmanager.yourdomain.com
```

### 6. Scheduled Tasks Setup (WICHTIG für Subscription Analytics)

Das Subscription-System benötigt regelmäßige Cron-Jobs für Analytics und Monitoring.

#### Crontab konfigurieren

```bash
# Crontab editieren
crontab -e

# Folgende Zeile hinzufügen:
* * * * * cd /path/to/basketmanager && php artisan schedule:run >> /dev/null 2>&1
```

#### Automatisch ausgeführte Tasks

Das System führt folgende Tasks automatisch aus (konfiguriert in `routes/console.php`):

```bash
# Täglich um 00:00 Uhr
→ subscription:update-mrr (daily)
  Berechnet tägliche MRR Snapshots für Revenue Analytics

# Monatlich am 1. um 01:00 Uhr
→ subscription:update-mrr (monthly)
  Berechnet monatliche MRR Zusammenfassungen

# Monatlich am 1. um 02:00 Uhr
→ subscription:calculate-churn
  Berechnet Churn Rates (freiwillig & unfreiwillig)

# Monatlich am 1. um 03:00 Uhr
→ subscription:update-cohorts
  Aktualisiert Cohort Analytics (24 Monate Retention)

# Wöchentlich
→ Push Subscription Cleanup
  Räumt abgelaufene Push-Subscriptions auf
```

#### Task Scheduler manuell testen

```bash
# Scheduler im Foreground ausführen (Development)
php artisan schedule:work

# Liste aller scheduled Tasks anzeigen
php artisan schedule:list

# Einzelnen Task manuell ausführen
php artisan subscription:update-mrr
```

---

## 💳 Stripe & Subscription System

Umfassende Dokumentation des Multi-Club Subscription-Systems mit allen verfügbaren Commands und Workflows.

### Verfügbare Artisan Commands

#### Subscription Management

```bash
# 1. Club Migrations & Data Validation
php artisan subscriptions:migrate-clubs
# Migriert bestehende Clubs zum Subscription-System
# Erstellt Stripe Customers und weist Free Club Plan zu

php artisan subscriptions:validate
# Validiert Subscription-Daten Integrität
# Prüft: Stripe Customer IDs, Plan Assignments, Datenbank Konsistenz

# 2. Subscription Analytics
php artisan subscription:update-mrr
# Berechnet MRR (Monthly Recurring Revenue) Snapshots
# Flags: --daily, --monthly

php artisan subscription:calculate-churn
# Berechnet Churn Rates (voluntary & involuntary)
# Analysiert gekündigte Subscriptions

php artisan subscription:update-cohorts
# Aktualisiert Cohort Analytics
# Tracked Retention über 24 Monate

php artisan subscription:analytics-report
# Generiert vollständigen Analytics Report
# Sendet E-Mail an Admins mit MRR, Churn, LTV Metriken

# 3. Health Monitoring
php artisan subscriptions:health-check
# Führt Subscription System Health Check aus
# Prüft 6 Metriken: Stripe Sync, Payment Failures, etc.

php artisan subscriptions:health-check --alert
# Health Check mit E-Mail Alerts
# Sendet Benachrichtigung bei kritischen Problemen

# 4. Webhook Management
php artisan manage:webhooks
# Interaktive Webhook-Verwaltung
# Erstellt, listet, und testet Stripe Webhooks
```

#### Multi-Tenancy Commands

```bash
# Row Level Security Setup
php artisan tenant:setup-rls
# Konfiguriert Row Level Security für Multi-Tenant Isolation

# Usage Tracking
php artisan tenant:usage:reset
# Setzt Tenant Usage Metriken zurück
```

#### API & Performance

```bash
# API Documentation
php artisan generate:openapi-docs
# Generiert OpenAPI 3.0 Dokumentation

# Database Performance
php artisan db:analyze-performance
# Analysiert Database Query Performance

# Cache Management
php artisan cache:management
# Interaktive Cache-Verwaltung
```

#### Emergency System

```bash
# Emergency Contact System Test
php artisan emergency:health-check
# Testet Emergency QR-Code System
```

### Subscription Workflows

#### Neuen Club mit Subscription erstellen

```bash
# 1. Datenbank Seeder ausführen
php artisan db:seed

# 2. Club zu Subscription migrieren
php artisan subscriptions:migrate-clubs

# 3. Subscription Analytics initialisieren
php artisan subscription:update-mrr
php artisan subscription:update-cohorts
```

#### Analytics Reports generieren

```bash
# Täglicher MRR Snapshot
php artisan subscription:update-mrr --daily

# Monatlicher Report
php artisan subscription:analytics-report
```

#### Health Monitoring Setup

```bash
# Einmalig: Health Check durchführen
php artisan subscriptions:health-check

# Mit E-Mail Alerts (für Admins)
php artisan subscriptions:health-check --alert

# Via API:
curl -X GET https://your-domain.com/api/health/subscriptions
```

### Subscription API Endpoints

Das System bietet **17 REST API Endpoints** für Subscription Management:

```bash
# Subscription Overview
GET /club/{club}/subscription

# Checkout
POST /club/{club}/checkout
GET /club/{club}/checkout/success
GET /club/{club}/checkout/cancel

# Billing Portal
POST /club/{club}/billing-portal

# Invoices
GET /club/{club}/billing/invoices
GET /club/{club}/billing/invoices/{invoice}
GET /club/{club}/billing/invoices/upcoming
GET /club/{club}/billing/invoices/{invoice}/pdf

# Payment Methods
GET /club/{club}/billing/payment-methods
POST /club/{club}/billing/payment-methods/setup
POST /club/{club}/billing/payment-methods/attach
DELETE /club/{club}/billing/payment-methods/{pm}
PUT /club/{club}/billing/payment-methods/{pm}
POST /club/{club}/billing/payment-methods/{pm}/default

# Plan Management
POST /club/{club}/billing/preview-plan-swap
POST /club/{club}/billing/swap-plan
```

Siehe `/docs/SUBSCRIPTION_API_REFERENCE.md` für vollständige API-Dokumentation.

---

## 🏥 Subscription Health Monitoring

Das System bietet umfassendes Health Monitoring für das Subscription-System.

### Health Metrics

Das System überwacht **6 kritische Metriken**:

1. **Stripe Sync Status** - Stripe Customer & Subscription Synchronisation
2. **Payment Failures** - Fehlgeschlagene Zahlungen (letzte 7 Tage)
3. **Expired Trials** - Abgelaufene Trials ohne Conversion
4. **Cancellation Rate** - Kündigungsrate (letzte 30 Tage)
5. **Missing Payment Methods** - Active Subscriptions ohne Payment Method
6. **Webhook Health** - Webhook Event Processing Status

### Health Check ausführen

#### Via Artisan Command

```bash
# Basic Health Check
php artisan subscriptions:health-check

# Beispiel Output:
# ✓ Stripe Sync: Healthy (100% synced)
# ⚠ Payment Failures: Warning (5 failures in 7 days)
# ✓ Trial Conversion: Healthy (85% conversion rate)
# ✓ Cancellation Rate: Healthy (2% in 30 days)
# ✓ Payment Methods: Healthy (All active subs have payment methods)
# ✓ Webhook Health: Healthy (99.5% success rate)
#
# Overall Status: HEALTHY (Score: 92/100)

# Mit E-Mail Alerts
php artisan subscriptions:health-check --alert
# Sendet E-Mail an Admins bei Status: WARNING oder CRITICAL
```

#### Via API

```bash
# Public Health Endpoint
GET /api/health/subscriptions

# Response:
{
  "status": "healthy",
  "score": 92,
  "metrics": {
    "stripe_sync": { "status": "healthy", "score": 100 },
    "payment_failures": { "status": "warning", "score": 75 },
    "trial_conversion": { "status": "healthy", "score": 85 },
    ...
  },
  "checked_at": "2025-01-15T10:30:00Z"
}
```

### Health Status Levels

- **🟢 HEALTHY** (Score 80-100): System funktioniert einwandfrei
- **🟡 WARNING** (Score 60-79): Einige Probleme erkannt, Überwachung empfohlen
- **🔴 CRITICAL** (Score 0-59): Kritische Probleme, sofortiges Handeln erforderlich

### Alert Configuration

In `.env` konfigurieren:

```env
# Health Monitoring
SUBSCRIPTION_HEALTH_ALERT_EMAIL=admin@yourclub.com
SUBSCRIPTION_HEALTH_ALERT_THRESHOLD=60  # Score unter 60 = Critical
SUBSCRIPTION_HEALTH_CHECK_ENABLED=true
```

### Scheduled Health Checks

Health Checks können automatisiert werden:

```php
// In routes/console.php oder AppServiceProvider
Schedule::command('subscriptions:health-check --alert')
    ->daily()
    ->at('08:00');
```

---

## 🏀 Basketball-spezifische Features

### 1. Permissions Setup

```bash
# Basketball-spezifische Permissions erstellen
php artisan db:seed --class=BasketballPermissionsSeeder
```

### 2. Default Data Setup

```bash
# Basketball Positionen, Standard-Einstellungen
php artisan db:seed --class=BasketballDefaultDataSeeder
```

### 3. Emergency QR System

Das Emergency Contact System ermöglicht QR-Code-basierten Zugriff auf Notfallkontakte ohne Authentifizierung.

```env
# Emergency System Configuration
EMERGENCY_QR_ENCRYPTION_KEY=your-32-character-encryption-key
EMERGENCY_QR_EXPIRY_HOURS=24
EMERGENCY_CONTACT_EMAIL=emergency@yourclub.com
```

**Health Check:**
```bash
php artisan emergency:health-check
```

**Features:**
- Offline-fähig via PWA Service Worker
- QR-Code Zugriff: `https://your-domain.com/emergency/{qr_code}`
- Automatisches Logging als `EmergencyIncident`
- GDPR-konform mit Audit Trail

### 4. Media Library & Activity Logging

Die Spatie Packages (Media Library & Activity Log) sind automatisch konfiguriert:

```bash
# Media Library Migrations bereits ausgeführt mit php artisan migrate
# Activity Log Migrations bereits ausgeführt mit php artisan migrate
```

**Basketball-spezifische Media Collections:**
- Player Photos
- Team Logos
- Game Videos
- Training Materials

**Activity Logging:**
Automatisches Logging für alle Basketball-relevanten Events (Games, Training, Player Management) via `spatie/laravel-activitylog`.

---

## 🧪 Testing Setup

### 1. Test Datenbank

Das Projekt ist für **schnelles Testing mit SQLite In-Memory** konfiguriert:

```env
# .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Oder separate Test-Datenbank für Production-parity Testing
DB_CONNECTION=mysql
DB_DATABASE=basketmanager_pro_test
```

**Test Environment Optimierungen** (in `phpunit.xml`):
```xml
<env name="PULSE_ENABLED" value="false"/>
<env name="TELESCOPE_ENABLED" value="false"/>
<env name="NIGHTWATCH_ENABLED" value="false"/>
```

### 2. Tests ausführen

```bash
# EMPFOHLEN: Mit Composer (führt automatisch config:clear aus)
composer test

# Alle Tests (72 Test-Dateien)
php artisan test

# Spezifische Test-Suite
php artisan test --testsuite=Unit          # Unit Tests
php artisan test --testsuite=Feature       # Feature Tests
php artisan test --testsuite=Integration   # Integration Tests (NEU)

# Mit Coverage
php artisan test --coverage --min=80

# Parallel Testing (schneller)
php artisan test --parallel

# Einzelne Test-Datei
php artisan test tests/Feature/ClubSubscriptionCheckoutTest.php

# Einzelne Test-Methode
php artisan test --filter=testCanCreateCheckoutSession
```

### 3. Subscription System Tests (40 Tests, ~4,350 Zeilen)

Das Subscription-System verfügt über umfassende Tests:

```bash
# Alle Subscription Tests
php artisan test --filter=ClubSubscription

# Integration Tests - Stripe Webhooks (23 Tests)
php artisan test tests/Integration/ClubSubscriptionWebhookTest.php

# E2E Tests - Checkout Flow (17 Tests)
php artisan test tests/Feature/ClubCheckoutE2ETest.php

# Unit Tests - Services
php artisan test tests/Unit/ClubSubscriptionCheckoutServiceTest.php
php artisan test tests/Unit/ClubSubscriptionServiceTest.php
php artisan test tests/Unit/SubscriptionAnalyticsServiceTest.php
```

**Test Coverage:**
- ✅ 23 Integration Tests - Alle 11 Stripe Webhook Events
- ✅ 17 E2E Tests - Kompletter Checkout Flow
- ✅ 100% Coverage für kritische Services

Siehe `/docs/SUBSCRIPTION_TESTING.md` für vollständige Testing-Dokumentation.

### 4. Basketball-spezifische Tests

```bash
# Service Tests
php artisan test tests/Unit/Services/

# Dashboard Tests
php artisan test tests/Feature/DashboardTest.php

# API Tests
php artisan test tests/Feature/Api/

# LiveScoring Tests
php artisan test tests/Feature/LiveScoringTest.php
```

### 5. BasketballTestCase verwenden

Für Basketball-spezifische Tests gibt es eine spezielle Base-Class:

```php
use Tests\BasketballTestCase;

class YourTest extends BasketballTestCase
{
    // Pre-configured test data verfügbar:
    // - $this->adminUser, $this->clubAdminUser, $this->trainerUser, $this->playerUser
    // - $this->testClub, $this->testTeam, $this->testPlayer

    public function test_example()
    {
        $this->actingAsAdmin();  // Helper method
        // Test code...
    }
}
```

### 6. Test Users

Das Projekt hat vorkonfigurierte Test-Users für alle 11 Rollen.
Siehe `/TEST_USERS.md` für Credentials.

---

## 🔧 Troubleshooting

### Häufige Probleme

#### 1. "Key not found" Fehler
```bash
php artisan key:generate
php artisan config:cache
```

#### 2. Storage Permission Fehler
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 3. NPM Build Fehler
```bash
# Node Modules neu installieren
rm -rf node_modules package-lock.json
npm install
npm run build
```

#### 4. Database Connection Fehler
- .env Datei prüfen
- Datenbank Service läuft: `sudo systemctl status mysql`
- Credentials testen: `mysql -u basketmanager -p basketmanager_pro`

#### 5. Queue Jobs laufen nicht
```bash
# Queue Connection prüfen
php artisan queue:monitor
php artisan queue:restart
```

#### 6. Vite/Assets laden nicht
```bash
# Development
npm run dev

# Production - Assets neu kompilieren
npm run build
php artisan view:clear
```

#### 7. Stripe Webhook Fehler
```bash
# Webhook Secret prüfen
# In Stripe Dashboard: Developers → Webhooks
# Secret kopieren und in .env eintragen:
STRIPE_WEBHOOK_SECRET=whsec_...

# Webhook lokal testen mit Stripe CLI:
stripe listen --forward-to localhost:8000/webhooks/stripe/club-subscriptions

# Webhook Events manuell senden:
stripe trigger checkout.session.completed
```

#### 8. Subscription Sync Probleme
```bash
# Subscription Daten validieren
php artisan subscriptions:validate

# Clubs zu Subscription System migrieren
php artisan subscriptions:migrate-clubs

# Health Check ausführen
php artisan subscriptions:health-check --alert
```

#### 9. Feature Flags funktionieren nicht
```bash
# Feature Flag Konfiguration prüfen
php artisan config:clear
php artisan cache:clear

# In .env prüfen:
FEATURE_CLUB_SUBSCRIPTIONS_ENABLED=true
FEATURE_FLAG_CACHE_TTL=3600

# Feature Flag Tabelle prüfen:
php artisan tinker
>>> \App\Models\FeatureFlag::all();
```

#### 10. MRR/Analytics Daten fehlen
```bash
# MRR Snapshots manuell berechnen
php artisan subscription:update-mrr --daily
php artisan subscription:update-mrr --monthly

# Cohort Analytics neu berechnen
php artisan subscription:update-cohorts

# Churn Rate neu berechnen
php artisan subscription:calculate-churn

# Scheduler prüfen
php artisan schedule:list
```

### Performance Debugging

```bash
# Cache leeren
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Debug Mode aktivieren
APP_DEBUG=true in .env

# Telescope für Performance Monitoring
php artisan telescope:install
```

### Log Dateien prüfen

```bash
# Laravel Logs
tail -f storage/logs/laravel.log

# Nginx Logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM Logs
sudo tail -f /var/log/php8.2-fpm.log
```

---

## 📚 Nützliche Commands

### Laravel Artisan - Core Commands

```bash
# Alle verfügbaren Commands anzeigen
php artisan list

# Maintenance Mode
php artisan down --retry=60
php artisan up

# Clear Commands
php artisan optimize:clear  # Alle Caches löschen

# Database
php artisan migrate:status
php artisan db:show
php artisan db:analyze-performance

# Task Scheduler
php artisan schedule:list
php artisan schedule:work
```

### Subscription & Analytics Commands

```bash
# Subscription Management
php artisan subscriptions:migrate-clubs
php artisan subscriptions:validate
php artisan subscriptions:health-check
php artisan subscriptions:health-check --alert

# Analytics
php artisan subscription:update-mrr --daily
php artisan subscription:update-mrr --monthly
php artisan subscription:calculate-churn
php artisan subscription:update-cohorts
php artisan subscription:analytics-report

# Webhooks
php artisan manage:webhooks
```

### Multi-Tenancy & Performance Commands

```bash
# Multi-Tenancy
php artisan tenant:setup-rls
php artisan tenant:usage:reset

# Performance
php artisan cache:management
php artisan db:analyze-performance

# API
php artisan generate:openapi-docs
```

### Emergency & Security Commands

```bash
# Emergency System
php artisan emergency:health-check

# Security
# (2FA und Security monitoring via Web Interface)
```

### Composer Scripts

```bash
# Development Server mit allen Services (⭐ EMPFOHLEN)
composer dev
# Startet: Laravel Server + Queue Worker + Laravel Pail + Vite

# Tests ausführen
composer test
# Führt: config:clear + phpunit aus
```

---

## 🔐 Sicherheits-Checkliste

### Development
- [ ] `.env` nie in Git committen
- [ ] Debug Mode nur in Development (`APP_DEBUG=true`)
- [ ] Telescope nur in Development aktiviert (`TELESCOPE_ENABLED=true`)
- [ ] Stripe Test Keys verwenden (`pk_test_...`, `sk_test_...`)
- [ ] Feature Flags im Development Mode (`FEATURE_FLAG_DEVELOPMENT_MODE=true`)

### Production
- [ ] `APP_DEBUG=false` und `APP_ENV=production`
- [ ] Sichere Datenbank Credentials
- [ ] SSL/TLS aktiviert (Certbot mit Let's Encrypt)
- [ ] File Permissions korrekt gesetzt (storage & bootstrap/cache)
- [ ] Queue Worker über Supervisor konfiguriert
- [ ] **Stripe Live Keys** verwendet (`pk_live_...`, `sk_live_...`)
- [ ] **Stripe Webhooks** konfiguriert und getestet
- [ ] **Feature Flags** Production-ready (`FEATURE_FLAG_DEVELOPMENT_MODE=false`)
- [ ] **Cron Job** für Scheduler eingerichtet
- [ ] **Health Checks** aktiviert und Alerts konfiguriert
- [ ] Regular Backups konfiguriert (Database + Storage)
- [ ] Log Rotation eingerichtet
- [ ] `.env.production.example` als Vorlage verwendet
- [ ] Session Security aktiviert (`SESSION_SECURE_COOKIE=true`)
- [ ] CSRF Protection aktiviert (EnhancedCsrfProtection Middleware)
- [ ] Security Headers konfiguriert (SecurityHeadersMiddleware)
- [ ] Subscription Analytics Scheduler läuft
- [ ] Subscription Health Monitoring aktiv

### Stripe Security
- [ ] Webhook Signing Secrets konfiguriert
- [ ] Webhook Endpoints nur HTTPS
- [ ] Payment Methods validiert
- [ ] Rate Limiting für API Endpoints
- [ ] Subscription Events geloggt

---

## 📞 Support & Dokumentation

- **Laravel Dokumentation:** https://laravel.com/docs
- **Vue 3 Dokumentation:** https://vuejs.org/guide/
- **Inertia.js Dokumentation:** https://inertiajs.com/
- **Tailwind CSS Dokumentation:** https://tailwindcss.com/docs

---

## 🔄 Updates & Maintenance

### Regular Updates
```bash
# Composer Dependencies
composer update

# NPM Dependencies
npm update

# Laravel Framework
composer update laravel/framework

# Nach Updates immer:
php artisan migrate
php artisan config:clear
php artisan cache:clear
composer test
```

### Subscription System Maintenance

```bash
# MRR/Analytics neu berechnen (nach Datenänderungen)
php artisan subscription:update-mrr
php artisan subscription:update-cohorts
php artisan subscription:calculate-churn

# Subscription Daten Validierung
php artisan subscriptions:validate

# Health Check (regelmäßig durchführen)
php artisan subscriptions:health-check --alert
```

### Performance Optimierung

```bash
# Database Performance analysieren
php artisan db:analyze-performance

# Cache optimieren
php artisan cache:management

# Production Caches neu erstellen
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 📖 Dokumentation & Referenzen

Das Projekt verfügt über umfassende Dokumentation im `/docs/` Verzeichnis:

### Subscription System Dokumentation

- **`SUBSCRIPTION_API_REFERENCE.md`** - Vollständige API-Dokumentation (17 Endpoints, 11 Webhooks)
- **`SUBSCRIPTION_INTEGRATION_GUIDE.md`** - Developer Setup, Webhook-Konfiguration, Service-Nutzung
- **`SUBSCRIPTION_DEPLOYMENT_GUIDE.md`** - Production Deployment, Stripe Live Keys, Queue Workers
- **`SUBSCRIPTION_ARCHITECTURE.md`** - System-Architektur, Data Flows, Analytics Pipeline
- **`SUBSCRIPTION_ADMIN_GUIDE.md`** - User Guide für Club-Administratoren
- **`SUBSCRIPTION_TESTING.md`** - Test Suite (40 Tests, 4,350+ Zeilen)

### Deployment Dokumentation

- **`PRODUCTION_DEPLOYMENT_CHECKLIST.md`** - Vollständige Production Deployment Checkliste
- **`ROLLOUT_STRATEGY.md`** - Feature Rollout-Strategie mit Feature Flags
- **`.env.production.example`** - Production Environment Template

### Weitere Dokumentation

- **`BERECHTIGUNGS_MATRIX.md`** - Vollständige Permissions-Matrix (136 Permissions, 11 Rollen)
- **`ROLLEN_DOKUMENTATION_README.md`** - Rollen-Dokumentation Index
- **`FEATURES.md`** - Umfassende Feature-Liste
- **`TEST_USERS.md`** - Test Account Credentials (alle 11 Rollen)
- **`PRODUCTION_READINESS.md`** - Production Readiness Checklist
- **`CLAUDE.md`** - Projekt-Übersicht für AI-Assistenten

### Laravel & Framework Dokumentation

- **Laravel Dokumentation:** https://laravel.com/docs/12.x
- **Vue 3 Dokumentation:** https://vuejs.org/guide/
- **Inertia.js Dokumentation:** https://inertiajs.com/
- **Tailwind CSS Dokumentation:** https://tailwindcss.com/docs
- **Stripe API Dokumentation:** https://stripe.com/docs/api

### Stripe Integration

- **Stripe Dashboard:** https://dashboard.stripe.com/
- **Stripe Webhooks:** https://dashboard.stripe.com/webhooks
- **Stripe Testing:** https://stripe.com/docs/testing
- **Stripe CLI:** https://stripe.com/docs/stripe-cli

---

## 🎯 Quick Start Checkliste

Für eine schnelle Installation, folge dieser Checkliste:

- [ ] PHP 8.2+, Node.js 18+, Composer 2.0+ installiert
- [ ] Repository geklont: `git clone ...`
- [ ] Dependencies installiert: `composer install && npm install`
- [ ] `.env` erstellt: `cp .env.example .env`
- [ ] App Key generiert: `php artisan key:generate`
- [ ] Datenbank erstellt (MySQL/PostgreSQL)
- [ ] `.env` konfiguriert (DB, Redis, Stripe, Feature Flags)
- [ ] Migrationen ausgeführt: `php artisan migrate`
- [ ] Seeders ausgeführt: `php artisan db:seed`
- [ ] Subscription Plans erstellt: `php artisan db:seed --class=ClubSubscriptionPlanSeeder --sync-stripe`
- [ ] Storage Links erstellt: `php artisan storage:link`
- [ ] Assets kompiliert: `npm run dev` oder `npm run build`
- [ ] Development Server gestartet: `composer dev`
- [ ] Tests ausgeführt: `composer test`
- [ ] Feature Flags aktiviert (in `.env`)
- [ ] Stripe Webhooks konfiguriert
- [ ] Cron Job für Scheduler eingerichtet (Production)
- [ ] Queue Worker konfiguriert (Production)
- [ ] Health Check durchgeführt: `php artisan subscriptions:health-check`

---

**🏀 BasketManager Pro Laravel**
Production-Ready Multi-Tenant Basketball Club Management System

**Version:** 1.0
**Laravel:** 12.x
**Letzte Aktualisierung:** November 2025

**Support:** Siehe `/docs/` für umfassende Dokumentation
**Issues:** https://github.com/[username]/BasketManagerProLaravel/issues