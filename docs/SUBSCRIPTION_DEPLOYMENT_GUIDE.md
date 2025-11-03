# 🚀 Club Subscription Deployment Guide

**Version:** 1.0
**Erstellt:** 2025-11-03
**Sprache:** Deutsch
**Zielgruppe:** DevOps, System-Administratoren, Deployment-Engineers

---

## 📋 Inhaltsverzeichnis

1. [Überblick](#überblick)
2. [Pre-Deployment Checkliste](#pre-deployment-checkliste)
3. [Server-Requirements](#server-requirements)
4. [Stripe Live Keys Setup](#stripe-live-keys-setup)
5. [Database Migrations](#database-migrations)
6. [Queue Worker Setup](#queue-worker-setup)
7. [Scheduled Commands (Cron)](#scheduled-commands-cron)
8. [Webhook-Endpoint-Konfiguration](#webhook-endpoint-konfiguration)
9. [Security Hardening](#security-hardening)
10. [Monitoring & Observability](#monitoring--observability)
11. [Rollback-Prozedur](#rollback-prozedur)
12. [Post-Deployment Verifikation](#post-deployment-verifikation)

---

## 🔍 Überblick

Dieses Dokument beschreibt den vollständigen Deployment-Prozess des **Multi-Club Subscription-Systems** für Produktionsumgebungen. Das System ist produktionsreif und wurde in einer Sandbox-Umgebung umfassend getestet.

### Deployment-Architektur

```
┌──────────────────┐
│   Load Balancer  │ (HTTPS, SSL/TLS)
└────────┬─────────┘
         │
┌────────▼─────────┐
│  Application     │ (Laravel App, Port 8000)
│  Servers (2+)    │ (Horizontal Scaling)
└────────┬─────────┘
         │
┌────────▼─────────┐
│  Queue Workers   │ (Webhook Processing, Email Queues)
│  (Supervisor)    │ (Multiple Processes, Auto-Restart)
└────────┬─────────┘
         │
┌────────▼─────────┐
│  MySQL/Postgres  │ (Subscription Data, Analytics)
│  Redis           │ (Cache, Queue, Sessions)
└──────────────────┘
```

### Deployment-Phasen

1. **Pre-Deployment:** Environment-Setup, Backups
2. **Deployment:** Code-Deploy, Migrations, Configuration
3. **Post-Deployment:** Verification, Monitoring, Rollback (bei Bedarf)

---

## ✅ Pre-Deployment Checkliste

### 1. Stripe Account Setup

- [  ] Stripe Live Account erstellt
- [  ] Stripe Account verifiziert (Bank-Konto verknüpft)
- [  ] Live API Keys generiert (`pk_live_...`, `sk_live_...`)
- [  ] Test → Live Migration durchgeführt (Products, Prices)
- [  ] Webhook Endpoint registriert
- [  ] Webhook Signing Secret gespeichert

### 2. Database Backup

```bash
# MySQL Backup
mysqldump -u root -p basketmanager_pro > backup_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL Backup
pg_dump -U postgres basketmanager_pro > backup_$(date +%Y%m%d_%H%M%S).sql

# Upload to S3 (optional)
aws s3 cp backup_*.sql s3://your-bucket/backups/
```

### 3. Environment Variables vorbereiten

```env
# .env (Production)
APP_ENV=production
APP_DEBUG=false
APP_URL=https://basketmanager.pro

# Stripe Live Keys
STRIPE_KEY=pk_live_51...
STRIPE_SECRET=sk_live_51...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_WEBHOOK_SECRET_CLUB=whsec_...

# Database (Production)
DB_CONNECTION=mysql
DB_HOST=production-db.example.com
DB_PORT=3306
DB_DATABASE=basketmanager_prod
DB_USERNAME=prod_user
DB_PASSWORD=...

# Redis (Production)
REDIS_HOST=production-redis.example.com
REDIS_PASSWORD=...
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis
QUEUE_DRIVER=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@basketmanager.pro
MAIL_FROM_NAME="BasketManager Pro"

# Logging & Monitoring
LOG_CHANNEL=stack
LOG_LEVEL=info
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
```

### 4. SSL/TLS Zertifikate

```bash
# Let's Encrypt (certbot)
sudo certbot --nginx -d basketmanager.pro -d www.basketmanager.pro

# Verify SSL Configuration
curl -vI https://basketmanager.pro
```

### 5. Code-Vorbereitung

```bash
# Latest Code pullen
git fetch origin
git checkout main
git pull origin main

# Dependencies installieren (Production)
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Cache optimieren
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🖥️ Server-Requirements

### Minimum Requirements

| Component | Requirement |
|-----------|-------------|
| **PHP** | 8.2+ mit Extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL |
| **Database** | MySQL 8.0+ / PostgreSQL 14+ |
| **Redis** | 7.0+ |
| **Web Server** | Nginx 1.18+ / Apache 2.4+ |
| **Memory** | 2GB RAM (minimum), 4GB+ recommended |
| **CPU** | 2 vCPUs (minimum), 4+ recommended |
| **Storage** | 20GB SSD (minimum), 50GB+ recommended |

### Recommended Production Setup

**Application Servers (2+ instances for HA):**
- 4 vCPUs, 8GB RAM
- Load-balanced (Nginx / AWS ALB)
- Auto-scaling based on CPU/Memory

**Queue Workers (Dedicated servers recommended):**
- 2 vCPUs, 4GB RAM
- Supervisor for process management
- Auto-restart on failure

**Database Server:**
- 4 vCPUs, 16GB RAM
- Dedicated instance (RDS / managed DB)
- Automated backups (daily)

**Redis Server:**
- 2 vCPUs, 4GB RAM
- Persistence enabled (AOF)
- Replication (Master-Slave)

---

## 💳 Stripe Live Keys Setup

### 1. Stripe Live Mode aktivieren

1. **Stripe Dashboard → Modus wechseln** (oben rechts: "Test Mode" → "Live Mode")
2. **Wichtig:** Nur in Live Mode arbeiten, wenn Produktion-Deployment!

### 2. API Keys generieren

1. **Dashboard → Developers → API Keys**
2. **Create secret key** (falls nicht vorhanden)
3. Keys kopieren:

```env
STRIPE_KEY=pk_live_51...        # Publishable Key
STRIPE_SECRET=sk_live_51...     # Secret Key (NIEMALS committen!)
```

**⚠️ Sicherheit:**
- Secret Key NIEMALS in Git
- Verwende Secret Management (AWS Secrets Manager, HashiCorp Vault)
- Rotate Keys regelmäßig (alle 6-12 Monate)

### 3. Products & Prices in Live Mode erstellen

**Option A: Manuell im Stripe Dashboard**

1. **Products → Add Product**
2. Für jeden Plan:
   - Name: "Premium Club", "Standard Club", etc.
   - Pricing: Monthly & Yearly
   - Metadata hinzufügen: `{"plan_id": "2", "tenant_id": "1"}`

**Option B: Migration von Test → Live**

```bash
# Stripe CLI verwenden
stripe products create \
  --name "Premium Club" \
  --description "Vollständige Live-Scoring & Statistiken"

stripe prices create \
  --product prod_... \
  --unit-amount 14900 \
  --currency eur \
  --recurring='{"interval":"month"}'
```

**Option C: Automatisch via Laravel Command**

```php
// In Production Console
php artisan tinker

// Für jeden Plan:
$plan = \App\Models\ClubSubscriptionPlan::find(1);
$service = app(\App\Services\Stripe\ClubSubscriptionService::class);
$result = $service->syncPlanWithStripe($plan);

// Prüfen:
echo "Product ID: {$result['product']->id}\n";
echo "Price Monthly ID: {$result['price_monthly']->id}\n";
echo "Price Yearly ID: {$result['price_yearly']->id}\n";

// In Datenbank speichern
$plan->update([
    'stripe_product_id' => $result['product']->id,
    'stripe_price_id_monthly' => $result['price_monthly']->id,
    'stripe_price_id_yearly' => $result['price_yearly']->id,
    'is_stripe_synced' => true,
]);
```

### 4. Webhook Endpoints konfigurieren (siehe [Webhook-Konfiguration](#webhook-endpoint-konfiguration))

---

## 💾 Database Migrations

### 1. Pre-Migration Checks

```bash
# Aktuelle Schema-Version prüfen
php artisan migrate:status

# Pending Migrations anzeigen
php artisan migrate:status | grep "Pending"

# Dry-Run (optional, erfordert Custom Command)
php artisan migrate --pretend
```

### 2. Subscription-Migrations ausführen

**Wichtige Migrations:**

```bash
# 1. Stripe-Felder für Clubs
php artisan migrate --path=database/migrations/2025_10_27_164000_add_stripe_fields_to_clubs_table.php

# 2. Stripe-Felder für Club Subscription Plans
php artisan migrate --path=database/migrations/2025_10_27_164500_add_stripe_fields_to_club_subscription_plans_table.php

# 3. Analytics-Tabellen
php artisan migrate --path=database/migrations/2025_10_28_120000_create_club_subscription_events_table.php
php artisan migrate --path=database/migrations/2025_10_28_121000_create_subscription_mrr_snapshots_table.php
php artisan migrate --path=database/migrations/2025_10_28_122000_create_club_subscription_cohorts_table.php

# 4. Notification-Tabellen
php artisan migrate --path=database/migrations/2025_10_29_100000_create_notification_preferences_table.php
php artisan migrate --path=database/migrations/2025_10_29_100500_create_notification_logs_table.php

# ODER: Alle Migrations auf einmal
php artisan migrate --force
```

**⚠️ Hinweis:**
- `--force` Flag ist nötig in Production (APP_ENV=production)
- Migrations sind **idempotent** (mehrfaches Ausführen = sicher)
- Bei Fehlern: Rollback mit `php artisan migrate:rollback`

### 3. Post-Migration Verification

```bash
# Schema-Version prüfen
php artisan migrate:status

# Tabellen prüfen
php artisan tinker

# In Tinker:
\Schema::hasTable('club_subscription_events');  // true
\Schema::hasTable('subscription_mrr_snapshots');  // true

// Club Model prüfen
\App\Models\Club::first()->stripe_customer_id;  // Column existiert
```

---

## ⚙️ Queue Worker Setup

Queue Workers sind **KRITISCH** für das Subscription-System. Ohne laufende Workers werden:
- Webhook-Events NICHT verarbeitet
- Email-Benachrichtigungen NICHT gesendet
- Analytics NICHT berechnet

### 1. Supervisor installieren

**Ubuntu/Debian:**
```bash
sudo apt-get install supervisor
```

**CentOS/RHEL:**
```bash
sudo yum install supervisor
```

### 2. Supervisor Konfiguration

**Datei:** `/etc/supervisor/conf.d/basketmanager-worker.conf`

```ini
[program:basketmanager-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/basketmanager-pro/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/basketmanager-pro/storage/logs/worker.log
stopwaitsecs=3600
```

**Erklärung:**
- `numprocs=4` - 4 Worker-Prozesse (empfohlen: min. 2)
- `--tries=3` - 3 Versuche bei Fehlern
- `--max-time=3600` - Worker restart nach 1 Stunde (Memory-Leak-Prevention)
- `--timeout=300` - Job-Timeout 5 Minuten
- `user=www-data` - Linux-User (anpassen wenn nötig)

### 3. Supervisor starten

```bash
# Konfiguration neu laden
sudo supervisorctl reread
sudo supervisorctl update

# Worker starten
sudo supervisorctl start basketmanager-worker:*

# Status prüfen
sudo supervisorctl status
# Output:
# basketmanager-worker:basketmanager-worker_00   RUNNING   pid 12345, uptime 0:00:10
# basketmanager-worker:basketmanager-worker_01   RUNNING   pid 12346, uptime 0:00:10
# basketmanager-worker:basketmanager-worker_02   RUNNING   pid 12347, uptime 0:00:10
# basketmanager-worker:basketmanager-worker_03   RUNNING   pid 12348, uptime 0:00:10
```

### 4. Worker-Logs überwachen

```bash
# Live-Logs anzeigen
tail -f /var/www/basketmanager-pro/storage/logs/worker.log

# Fehler suchen
grep "ERROR" /var/www/basketmanager-pro/storage/logs/worker.log

# Queue-Status prüfen
php artisan queue:monitor
```

### 5. Worker neustarten (nach Deployment)

```bash
# Alle Worker neustarten
sudo supervisorctl restart basketmanager-worker:*

# ODER: Via Artisan (empfohlen)
php artisan queue:restart
# Workers beenden sich selbst nach aktuellem Job, Supervisor startet sie neu
```

---

## ⏰ Scheduled Commands (Cron)

Das Subscription-System benötigt **4 scheduled Commands** für Analytics.

### 1. Cron-Job einrichten

```bash
# Crontab öffnen
crontab -e

# Folgende Zeile hinzufügen:
* * * * * cd /var/www/basketmanager-pro && php artisan schedule:run >> /dev/null 2>&1
```

**⚠️ Wichtig:**
- Nur **EINE** Zeile erforderlich
- Laravel Scheduler führt alle Commands intern aus
- Läuft jede Minute, prüft welche Commands fällig sind

### 2. Scheduled Commands (in `routes/console.php`)

```php
<?php

use Illuminate\Support\Facades\Schedule;

// MRR Snapshot (täglich um 00:00 Uhr)
Schedule::command('subscription:update-mrr daily')
    ->daily()
    ->at('00:00')
    ->onOneServer()
    ->withoutOverlapping();

// MRR Snapshot (monatlich am 1. um 01:00 Uhr)
Schedule::command('subscription:update-mrr monthly')
    ->monthlyOn(1, '01:00')
    ->onOneServer()
    ->withoutOverlapping();

// Churn-Rate berechnen (monatlich am 1. um 02:00 Uhr)
Schedule::command('subscription:calculate-churn')
    ->monthlyOn(1, '02:00')
    ->onOneServer()
    ->withoutOverlapping();

// Cohort-Analytics aktualisieren (monatlich am 1. um 03:00 Uhr)
Schedule::command('subscription:update-cohorts')
    ->monthlyOn(1, '03:00')
    ->onOneServer()
    ->withoutOverlapping();

// Analytics-Report senden (monatlich am 1. um 08:00 Uhr)
Schedule::command('subscription:analytics-report')
    ->monthlyOn(1, '08:00')
    ->onOneServer()
    ->withoutOverlapping();
```

**Features:**
- `onOneServer()` - Verhindert Doppel-Ausführung bei Multi-Server-Setup
- `withoutOverlapping()` - Verhindert gleichzeitige Ausführung desselben Commands
- `daily()`, `monthlyOn(1, '01:00')` - Scheduling-Optionen

### 3. Scheduler testen

```bash
# Manuelle Ausführung (Development)
php artisan subscription:update-mrr daily
php artisan subscription:calculate-churn
php artisan subscription:update-cohorts

# Scheduler-Status prüfen
php artisan schedule:list

# Output:
# 0 0 * * * php artisan subscription:update-mrr daily ........ Next Due: 5 hours from now
# 0 1 1 * * php artisan subscription:update-mrr monthly ...... Next Due: 5 days from now
# 0 2 1 * * php artisan subscription:calculate-churn ......... Next Due: 5 days from now
```

### 4. Scheduler-Logs

```bash
# Scheduler-Logs aktivieren (optional)
# In routes/console.php:
Schedule::command('subscription:update-mrr daily')
    ->daily()
    ->onOneServer()
    ->sendOutputTo(storage_path('logs/scheduler.log'))
    ->emailOutputOnFailure('admin@basketmanager.pro');
```

---

## 🔔 Webhook-Endpoint-Konfiguration

### 1. Webhook-Endpoint im Stripe Dashboard registrieren

1. **Stripe Dashboard → Developers → Webhooks**
2. **Add endpoint**
3. **Endpoint URL:** `https://basketmanager.pro/webhooks/stripe/club-subscriptions`
4. **Description:** Club Subscription Webhooks (Production)
5. **API Version:** Latest (automatisch)

### 2. Events auswählen

**Alle 11 Club-Subscription Events hinzufügen:**

```
✅ checkout.session.completed
✅ customer.subscription.created
✅ customer.subscription.updated
✅ customer.subscription.deleted
✅ invoice.payment_succeeded
✅ invoice.payment_failed
✅ invoice.created
✅ invoice.finalized
✅ invoice.payment_action_required
✅ payment_method.attached
✅ payment_method.detached
```

**Hinweis:** Wähle "Select all events matching..." für schnellere Konfiguration.

### 3. Webhook Secret kopieren

Nach Erstellung des Endpoints:

1. **Signing secret** anzeigen (klicke "Reveal")
2. Secret kopieren (beginnt mit `whsec_`)
3. In Production `.env` einfügen:

```env
STRIPE_WEBHOOK_SECRET_CLUB=whsec_...
```

4. Application Server neustarten:

```bash
# Nginx + PHP-FPM
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx

# ODER: Apache
sudo systemctl reload apache2

# ODER: Laravel Octane (Swoole/RoadRunner)
php artisan octane:reload
```

### 4. Webhook-Endpoint testen

**Von Stripe Dashboard:**

1. **Dashboard → Webhooks → [Your Endpoint]**
2. **Send test webhook**
3. Event auswählen: `checkout.session.completed`
4. **Send test webhook**
5. Status prüfen: **200 OK** = ✅ Success

**Mit curl (Alternative):**

```bash
# Test Webhook Request (mit korrekter Signatur)
curl -X POST https://basketmanager.pro/webhooks/stripe/club-subscriptions \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: t=..." \
  -d @test_webhook.json

# Expected Response:
# {"status":"success"}
```

### 5. Webhook-Logs überwachen

```bash
# Application Logs
tail -f /var/www/basketmanager-pro/storage/logs/laravel.log | grep "webhook"

# Suche nach erfolgreichen Webhook-Events
grep "Club Stripe webhook received" storage/logs/laravel.log

# Suche nach fehlgeschlagenen Webhooks
grep "webhook handler failed" storage/logs/laravel.log
```

---

## 🔒 Security Hardening

### 1. Environment File Permissions

```bash
# .env darf nur von Owner gelesen werden
chmod 600 .env

# Ownership setzen
sudo chown www-data:www-data .env
```

### 2. Secret Key Rotation

```bash
# Neuen Application Key generieren (⚠️ invalidiert Sessions!)
php artisan key:generate --force

# In .env kopieren:
APP_KEY=base64:...
```

### 3. HTTPS erzwingen

**In `app/Http/Middleware/TrustProxies.php`:**

```php
protected $proxies = '*'; // Bei Load Balancer

protected $headers = Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO;
```

**In `app/Providers/AppServiceProvider.php`:**

```php
use Illuminate\Support\Facades\URL;

public function boot(): void
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}
```

### 4. Rate Limiting (Stripe Webhooks)

**In `app/Http/Kernel.php`:**

```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \App\Http\Middleware\EnterpriseRateLimitMiddleware::class,
    ],
];
```

**Webhook-Route ist NICHT rate-limited** (Stripe IPs sind trusted).

### 5. Database Query Logging (Production)

**NUR bei Performance-Problemen aktivieren:**

```php
// config/database.php
'mysql' => [
    // ...
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => true,
    ],
    'log_queries' => env('DB_LOG_QUERIES', false), // Default: false in Prod
],
```

### 6. Stripe API Key Security

```bash
# Secret Manager (AWS)
aws secretsmanager create-secret \
  --name basketmanager/stripe/secret-key \
  --secret-string "sk_live_..."

# Abrufen im Deployment-Script:
export STRIPE_SECRET=$(aws secretsmanager get-secret-value \
  --secret-id basketmanager/stripe/secret-key \
  --query SecretString --output text)
```

---

## 📊 Monitoring & Observability

### 1. Application Performance Monitoring (APM)

**Sentry Integration:**

```env
# .env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
SENTRY_TRACES_SAMPLE_RATE=0.2  # 20% der Requests tracken
```

**In `app/Exceptions/Handler.php`:**

```php
use Sentry\Laravel\Integration;

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        if (app()->bound('sentry') && $this->shouldReport($e)) {
            app('sentry')->captureException($e);
        }
    });
}
```

### 2. Queue Monitoring

**Horizon (Redis Queue Dashboard):**

```bash
# Installation
composer require laravel/horizon

# Publish Config
php artisan horizon:install

# Starten
php artisan horizon
```

**Monitoring-Dashboard:** `https://basketmanager.pro/horizon`

**Supervisor Config für Horizon:**

```ini
[program:basketmanager-horizon]
process_name=%(program_name)s
command=/usr/bin/php /var/www/basketmanager-pro/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/basketmanager-pro/storage/logs/horizon.log
stopwaitsecs=3600
```

### 3. Database Query Monitoring

**Laravel Telescope (Development/Staging only!):**

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**⚠️ NICHT in Production verwenden** (Performance-Impact!)

### 4. Custom Metrics (CloudWatch, Prometheus, etc.)

**Subscription Metrics exportieren:**

```php
// app/Console/Commands/ExportMetrics.php

use App\Services\Stripe\SubscriptionAnalyticsService;

$analyticsService = app(SubscriptionAnalyticsService::class);

// MRR Metric
$mrr = $analyticsService->getCurrentMRR($tenantId);

// Export to CloudWatch
CloudWatch::putMetricData([
    'Namespace' => 'BasketManagerPro/Subscriptions',
    'MetricData' => [
        [
            'MetricName' => 'MRR',
            'Value' => $mrr,
            'Unit' => 'None',
            'Timestamp' => now()->toIso8601String(),
        ],
    ],
]);
```

### 5. Alert Configuration

**Slack Webhooks (Payment Failures):**

```php
// app/Http/Controllers/Webhooks/ClubSubscriptionWebhookController.php

protected function handlePaymentFailed($invoice): void
{
    // ... Standard handling ...

    // Slack Alert wenn Zahlung 3x fehlschlägt
    if ($invoice->attempt_count >= 3) {
        Notification::route('slack', config('services.slack.webhook_url'))
            ->notify(new PaymentFailedCritical($club, $invoice));
    }
}
```

---

## 🔄 Rollback-Prozedur

### 1. Code Rollback

```bash
# Git Tag für letztes stabiles Release
git tag -l
# Output: v1.0.0, v1.1.0, v1.2.0

# Rollback zu v1.1.0
git checkout v1.1.0

# Dependencies reinstallieren
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Caches leeren
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Application Server neustarten
sudo systemctl reload php8.2-fpm
```

### 2. Database Rollback

**⚠️ NUR wenn Migration fehlgeschlagen:**

```bash
# Letzte Migration rückgängig machen
php artisan migrate:rollback --step=1

# Mehrere Migrations rückgängig machen
php artisan migrate:rollback --step=5

# ODER: Zu bestimmtem Batch rollen
php artisan migrate:rollback --batch=3
```

**Hinweis:** Rollback von Subscription-Migrations ist **riskant**, wenn bereits Produktions-Daten vorhanden sind!

### 3. Stripe Rollback

**Produkte/Preise können NICHT gelöscht werden** (Stripe-Policy). Alternative:

1. **Inaktiv setzen:**
   ```bash
   stripe products update prod_... --active=false
   ```

2. **In Laravel deaktivieren:**
   ```php
   $plan->update(['is_active' => false]);
   ```

### 4. Queue Rollback

```bash
# Alle Jobs aus Queue löschen (⚠️ Datenverlust!)
php artisan queue:flush

# Fehlgeschlagene Jobs neu versuchen
php artisan queue:retry all
```

---

## ✅ Post-Deployment Verifikation

### 1. Application Health Check

```bash
# HTTP-Endpoint prüfen
curl -I https://basketmanager.pro/health
# Expected: HTTP/1.1 200 OK

# Subscription-Endpoint prüfen (mit Auth)
curl -H "Authorization: Bearer $TOKEN" \
  https://basketmanager.pro/api/v2/clubs/1/subscription
```

### 2. Stripe Integration prüfen

**Checkout-Flow testen:**

1. Login als Club-Admin
2. Gehe zu **Club → Subscription**
3. Wähle Plan aus
4. Klicke "Jetzt buchen"
5. Verwende Test-Karte: `4242 4242 4242 4242`
6. Checkout abschließen
7. Prüfe: **club.subscription_status = 'active'**

```bash
# In Tinker prüfen
php artisan tinker

$club = \App\Models\Club::find(1);
$club->subscription_status;  // 'active'
$club->stripe_subscription_id;  // 'sub_...'
```

### 3. Webhook-Events prüfen

```bash
# Stripe Dashboard → Webhooks → [Your Endpoint]
# Prüfe: "Last response: 200 OK"

# In Application Logs prüfen:
grep "Club Stripe webhook received" storage/logs/laravel.log | tail -5
```

### 4. Queue Worker prüfen

```bash
# Supervisor Status
sudo supervisorctl status | grep basketmanager-worker
# Expected: RUNNING (pid 12345, uptime 0:05:00)

# Queue-Status
php artisan queue:monitor
# Expected: 0 failed jobs
```

### 5. Scheduled Commands prüfen

```bash
# Scheduler-Status
php artisan schedule:list

# Manuell ausführen (Test)
php artisan subscription:update-mrr daily

# Prüfen in DB:
php artisan tinker

\App\Models\SubscriptionMRRSnapshot::latest()->first();
# Expected: Snapshot mit aktuellem Datum
```

### 6. Email-Benachrichtigungen testen

```bash
# Test-Email senden
php artisan tinker

$club = \App\Models\Club::find(1);
$service = app(\App\Services\ClubSubscriptionNotificationService::class);

$service->sendSubscriptionWelcome($club, $club->subscriptionPlan);

# Prüfe Inbox: noreply@basketmanager.pro
```

### 7. Analytics-Dashboard prüfen

```bash
# MRR abrufen
php artisan tinker

$service = app(\App\Services\Stripe\SubscriptionAnalyticsService::class);
$mrr = $service->getCurrentMRR(1);  // Tenant ID 1

echo "Current MRR: €{$mrr}\n";
```

---

## 📞 Support & Troubleshooting

### Häufige Deployment-Probleme

**Problem 1: Webhook 500 Error**
- **Ursache:** Queue Worker läuft nicht
- **Lösung:** `sudo supervisorctl restart basketmanager-worker:*`

**Problem 2: Checkout schlägt fehl**
- **Ursache:** Plan nicht mit Stripe synchronisiert
- **Lösung:** `php artisan tinker` → `$service->syncPlanWithStripe($plan);`

**Problem 3: Emails werden nicht gesendet**
- **Ursache:** MAIL_* Config falsch
- **Lösung:** `.env` prüfen, `php artisan config:cache`

### Logs & Debugging

```bash
# Application Logs
tail -f storage/logs/laravel.log

# Nginx Error Logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM Logs
sudo tail -f /var/log/php8.2-fpm.log

# Supervisor Logs
sudo tail -f /var/log/supervisor/supervisord.log

# Queue Worker Logs
tail -f storage/logs/worker.log
```

### Kontakt

- **Email:** support@basketmanager.pro
- **Slack:** #devops-support
- **On-Call:** +49 XXX XXXXXXX

---

## 📚 Checkliste: Produktions-Deployment

### Pre-Deployment
- [  ] Stripe Live Account verifiziert
- [  ] Live API Keys generiert
- [  ] Webhook Endpoint registriert
- [  ] Database Backup erstellt
- [  ] .env (Production) vorbereitet
- [  ] SSL/TLS Zertifikate installiert
- [  ] Code auf Production-Branch (main)

### Deployment
- [  ] Code deployed (`git pull`, `composer install`, `npm run build`)
- [  ] Migrations ausgeführt (`php artisan migrate --force`)
- [  ] Caches optimiert (`config:cache`, `route:cache`, `view:cache`)
- [  ] Queue Worker gestartet (`supervisorctl start basketmanager-worker:*`)
- [  ] Cron-Job eingerichtet (`crontab -e`)
- [  ] Application Server restarted

### Post-Deployment
- [  ] Health-Check erfolgreich (`curl /health`)
- [  ] Stripe Checkout funktioniert
- [  ] Webhook-Events werden empfangen
- [  ] Queue Worker läuft (`supervisorctl status`)
- [  ] Scheduled Commands funktionieren (`schedule:list`)
- [  ] Email-Benachrichtigungen funktionieren
- [  ] Analytics-Dashboard zeigt Daten

### Monitoring
- [  ] Sentry/APM konfiguriert
- [  ] CloudWatch/Prometheus Metrics exportiert
- [  ] Slack/Email Alerts konfiguriert
- [  ] Logs werden gesammelt (ELK, Papertrail)

---

**© 2025 BasketManager Pro** | Version 1.0 | Erstellt: 2025-11-03
