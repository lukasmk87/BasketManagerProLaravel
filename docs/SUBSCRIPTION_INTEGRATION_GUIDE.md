# 🛠️ Club Subscription Integration & Entwickler-Guide

**Version:** 1.0
**Erstellt:** 2025-11-03
**Sprache:** Deutsch
**Zielgruppe:** Backend- & Frontend-Entwickler

---

## 📋 Inhaltsverzeichnis

1. [Überblick](#überblick)
2. [Lokales Entwicklungs-Setup](#lokales-entwicklungs-setup)
3. [Stripe-Account-Konfiguration](#stripe-account-konfiguration)
4. [Webhook-Setup (Detailliert)](#webhook-setup-detailliert)
5. [Service-Usage im Code](#service-usage-im-code)
6. [Frontend-Integration (Vue.js)](#frontend-integration-vuejs)
7. [Testing-Setup](#testing-setup)
8. [Migration: Tenant → Club Subscriptions](#migration-tenant--club-subscriptions)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## 🔍 Überblick

Dieses Dokument führt Entwickler durch die Integration des **Multi-Club Subscription-Systems** in BasketManager Pro. Das System ermöglicht es jedem Club, seine eigene Stripe-Subscription zu haben, unabhängig vom Tenant-Level-Abonnement.

### Was du lernen wirst

✅ Lokales Development-Environment aufsetzen
✅ Stripe Test-Account konfigurieren
✅ Webhooks einrichten und testen
✅ Services im Code nutzen (13 Stripe-Services)
✅ Frontend-Components verwenden
✅ Tests schreiben und ausführen
✅ Typische Probleme lösen

### Voraussetzungen

- ✅ PHP 8.2+ mit Composer
- ✅ Node.js 18+ mit npm
- ✅ MySQL 8.0+ oder PostgreSQL 14+
- ✅ Redis 7.0+
- ✅ Git
- ✅ Stripe CLI (für Webhook-Testing)

---

## 🏗️ Lokales Entwicklungs-Setup

### 1. Repository klonen & Dependencies installieren

```bash
# Repository klonen
git clone https://github.com/yourorg/basketmanager-pro.git
cd basketmanager-pro

# PHP Dependencies
composer install

# Node Dependencies
npm install
```

### 2. Environment konfigurieren

```bash
# .env-Datei erstellen
cp .env.example .env

# Application Key generieren
php artisan key:generate
```

### 3. .env-Datei erweitern

Füge folgende Stripe-Konfiguration hinzu:

```env
# ============================
# STRIPE CONFIGURATION
# ============================

# Stripe API Keys (Test Mode)
STRIPE_KEY=pk_test_51...
STRIPE_SECRET=sk_test_51...

# Stripe Publishable Key (für Frontend)
VITE_STRIPE_KEY="${STRIPE_KEY}"

# Webhook Secrets
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_WEBHOOK_SECRET_CLUB=whsec_...  # Optional: Separates Secret für Club-Webhooks

# Stripe Configuration
CASHIER_CURRENCY=EUR
CASHIER_CURRENCY_LOCALE=de_DE
CASHIER_LOGGER=stack
```

### 4. Database Migrations ausführen

```bash
# Database erstellen
php artisan migrate

# Seeders ausführen (Test-Daten)
php artisan db:seed

# Spezifische Subscription-Seeders
php artisan db:seed --class=ClubSubscriptionPlanSeeder
```

**Wichtige Migrations für Club-Subscriptions:**

| Migration | Beschreibung |
|-----------|--------------|
| `add_stripe_fields_to_clubs_table` | Stripe-Felder für Clubs (11 Felder) |
| `add_stripe_fields_to_club_subscription_plans_table` | Stripe-Felder für Plans (6 Felder) |
| `create_club_subscription_events_table` | Analytics-Events |
| `create_subscription_mrr_snapshots_table` | MRR-Tracking |
| `create_club_subscription_cohorts_table` | Cohort-Analytics |
| `create_notification_preferences_table` | Email-Preferences |

### 5. Development-Server starten

```bash
# Option 1: Alle Services gleichzeitig (empfohlen)
composer dev
# Startet: Laravel Server + Queue Worker + Logs (Pail) + Vite

# Option 2: Services einzeln
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Queue Worker (für Webhooks & Notifications)
php artisan queue:listen --tries=1

# Terminal 3: Log Viewer
php artisan pail --timeout=0

# Terminal 4: Frontend Build
npm run dev
```

**⚠️ Wichtig:** Der **Queue Worker** MUSS laufen, damit Webhook-Events verarbeitet werden!

---

## 💳 Stripe-Account-Konfiguration

### 1. Stripe Test-Account erstellen

1. Gehe zu https://dashboard.stripe.com/register
2. Erstelle einen Account
3. Aktiviere **Test Mode** (Toggle oben rechts)
4. Notiere deine API Keys: **Dashboard → Developers → API Keys**

### 2. API Keys kopieren

```env
# Test Mode Keys (erkennbar an "test" im Key)
STRIPE_KEY=pk_test_51...               # Publishable Key
STRIPE_SECRET=sk_test_51...            # Secret Key
```

**⚠️ Sicherheit:**
- Secret Key NIEMALS im Frontend verwenden
- Nie in Git committen (`.env` ist in `.gitignore`)
- In Produktion separate Live-Keys verwenden

### 3. Products & Prices in Stripe erstellen

**Option A: Automatisch via Artisan Command**

```bash
# ClubSubscriptionPlans synchronisieren
php artisan tinker

# In Tinker:
$plan = \App\Models\ClubSubscriptionPlan::find(1);
$service = app(\App\Services\Stripe\ClubSubscriptionService::class);
$service->syncPlanWithStripe($plan);
```

**Option B: Manuell im Stripe Dashboard**

1. **Dashboard → Products → Add Product**
2. **Name:** "Premium Club"
3. **Pricing Model:** Recurring
4. **Price:** €149.00
5. **Billing Period:** Monthly
6. **Save**
7. Kopiere **Price ID** (z.B. `price_1234567890`) in `ClubSubscriptionPlan`-Model:

```php
$plan->update([
    'stripe_product_id' => 'prod_...',
    'stripe_price_id_monthly' => 'price_...',
    'stripe_price_id_yearly' => 'price_...',
    'is_stripe_synced' => true,
]);
```

### 4. Test-Pläne anlegen

**Empfohlene Test-Pläne:**

| Name | Price (Monthly) | Price (Yearly) | Features |
|------|-----------------|----------------|----------|
| Free Club | €0 | €0 | Basic features |
| Standard Club | €49 | €441 (10% off) | Live Scoring |
| Premium Club | €149 | €1,341 (10% off) | Advanced Stats |
| Enterprise Club | €299 | €2,691 (10% off) | All features |

**Seeder-Beispiel:**

```php
// database/seeders/ClubSubscriptionPlanSeeder.php
ClubSubscriptionPlan::create([
    'tenant_id' => 1,
    'name' => 'Premium Club',
    'slug' => 'premium',
    'description' => 'Vollständige Live-Scoring & Statistiken',
    'price' => 149.00,
    'currency' => 'EUR',
    'billing_interval' => 'monthly',
    'features' => [
        'live_scoring' => true,
        'advanced_stats' => true,
        'video_analysis' => true,
    ],
    'limits' => [
        'max_teams' => 50,
        'max_players' => 500,
        'max_games' => 500,
    ],
    'is_active' => true,
    'trial_period_days' => 14,
]);
```

---

## 🔔 Webhook-Setup (Detailliert)

Webhooks sind **kritisch** für das Subscription-System. Ohne funktionierende Webhooks werden Subscription-Status-Updates nicht synchronisiert!

### 1. Lokales Webhook-Testing mit Stripe CLI

**Stripe CLI installieren:**

**macOS:**
```bash
brew install stripe/stripe-cli/stripe
```

**Linux:**
```bash
wget https://github.com/stripe/stripe-cli/releases/latest/download/stripe_linux_amd64.tar.gz
tar -xvf stripe_linux_amd64.tar.gz
sudo mv stripe /usr/local/bin/
```

**Windows:**
```powershell
scoop install stripe
```

**Stripe CLI authentifizieren:**

```bash
stripe login
# Öffnet Browser, um sich anzumelden
```

**Webhooks forwarden:**

```bash
# Laravel Server muss laufen (localhost:8000)
stripe listen --forward-to localhost:8000/webhooks/stripe/club-subscriptions

# Output:
# > Ready! Your webhook signing secret is whsec_... (^C to quit)
```

**Webhook Secret in .env einfügen:**

```env
STRIPE_WEBHOOK_SECRET_CLUB=whsec_...
```

**Test-Event triggern:**

```bash
# Test: Checkout abgeschlossen
stripe trigger checkout.session.completed

# Test: Subscription erstellt
stripe trigger customer.subscription.created

# Test: Zahlung erfolgreich
stripe trigger invoice.payment_succeeded

# Test: Zahlung fehlgeschlagen
stripe trigger invoice.payment_failed
```

**Log-Output prüfen:**

```bash
# In anderem Terminal: Logs anzeigen
php artisan pail --timeout=0

# Suche nach:
# "Club Stripe webhook received"
# "Club checkout completed"
# "Club subscription created"
```

### 2. Produktions-Webhook-Setup im Stripe Dashboard

**Schritt 1: Webhook Endpoint erstellen**

1. **Stripe Dashboard → Developers → Webhooks**
2. **Add Endpoint**
3. **Endpoint URL:** `https://basketmanager.pro/webhooks/stripe/club-subscriptions`
4. **Description:** Club Subscription Webhooks
5. **Events to send:** (siehe unten)

**Schritt 2: Events auswählen**

Wähle folgende **11 Events**:

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

**Schritt 3: Webhook Secret kopieren**

1. Nach Erstellung des Endpoints: **Signing secret** anzeigen
2. Kopiere Secret (beginnt mit `whsec_`)
3. Füge in `.env` ein:

```env
STRIPE_WEBHOOK_SECRET_CLUB=whsec_...
```

**Schritt 4: Webhook testen**

1. **Dashboard → Webhooks → [Your Endpoint]**
2. **Send test webhook**
3. Event auswählen (z.B. `checkout.session.completed`)
4. **Send test webhook**
5. Status prüfen: **2xx = Success** ✅

### 3. Webhook-Signatur-Validierung

**Automatische Validierung im Controller:**

```php
// app/Http/Controllers/Webhooks/ClubSubscriptionWebhookController.php

use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

public function handleWebhook(Request $request): JsonResponse
{
    $payload = $request->getContent();
    $sigHeader = $request->header('Stripe-Signature');
    $webhookSecret = config('stripe.webhooks.signing_secret_club');

    try {
        // Stripe validiert die Signatur automatisch
        $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
    } catch (SignatureVerificationException $e) {
        Log::error('Webhook signature verification failed', [
            'error' => $e->getMessage(),
            'ip' => $request->ip(),
        ]);

        return response()->json(['error' => 'Invalid signature'], 400);
    }

    // Event verarbeiten...
    match ($event->type) {
        'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
        // ...
    };

    return response()->json(['status' => 'success']);
}
```

**⚠️ Wichtig:**
- Webhook-Route hat **KEIN `auth` Middleware**
- Validierung erfolgt via Stripe-Signatur
- Ungültige Signaturen werden mit `400` abgelehnt

### 4. Webhook-Retry-Mechanismus

Stripe versucht Webhooks **automatisch erneut**, wenn sie fehlschlagen:

**Retry-Schedule:**
- Nach 5 Minuten
- Nach 1 Stunde
- Nach 3 Stunden
- Nach 6 Stunden
- Nach 12 Stunden
- Nach 24 Stunden

**Best Practice:**
- Webhook-Handler sollten **idempotent** sein (mehrfaches Ausführen = gleiches Ergebnis)
- Bei Fehlern: `500` zurückgeben → Stripe retried
- Bei erfolgreich: `200` zurückgeben → Keine Retries

---

## 💻 Service-Usage im Code

Das Subscription-System basiert auf **13 spezialisierten Services**. Hier lernst du, wie du sie verwendest.

### Service-Architektur

```
ClubStripeCustomerService       → Stripe Customer Management
ClubSubscriptionCheckoutService → Checkout Sessions
ClubSubscriptionService         → Subscription Lifecycle (Cancel, Swap, Sync)
ClubInvoiceService              → Invoice Management
ClubPaymentMethodService        → Payment Method Management
SubscriptionAnalyticsService    → MRR/ARR/Churn Analytics
ClubSubscriptionNotificationService → Email Notifications
```

### 1. ClubStripeCustomerService

**Verwendung: Stripe Customer erstellen/abrufen**

```php
use App\Services\Stripe\ClubStripeCustomerService;
use App\Models\Club;

class YourController extends Controller
{
    public function __construct(
        private ClubStripeCustomerService $customerService
    ) {}

    public function createOrGetCustomer(Club $club)
    {
        // Get or create Stripe Customer
        $customer = $this->customerService->getOrCreateCustomer($club);

        // Ergebnis:
        // - Stripe Customer Object
        // - club.stripe_customer_id wird automatisch gespeichert

        return response()->json([
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
        ]);
    }

    public function updateCustomer(Club $club)
    {
        // Update Customer Informationen
        $customer = $this->customerService->updateCustomer($club, [
            'name' => $club->name,
            'email' => $club->billing_email,
            'address' => [
                'line1' => $club->address_street,
                'city' => $club->address_city,
                'postal_code' => $club->address_zip,
                'country' => 'DE',
            ],
        ]);

        return response()->json(['customer' => $customer]);
    }
}
```

**Methoden:**
- `getOrCreateCustomer(Club $club): Customer` - Erstellt oder ruft Customer ab
- `createCustomer(Club $club): Customer` - Erstellt neuen Customer
- `updateCustomer(Club $club, array $data): Customer` - Aktualisiert Customer
- `deleteCustomer(Club $club): void` - Löscht Customer (bei Club-Deletion)

### 2. ClubSubscriptionCheckoutService

**Verwendung: Checkout Session erstellen**

```php
use App\Services\Stripe\ClubSubscriptionCheckoutService;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;

class CheckoutController extends Controller
{
    public function __construct(
        private ClubSubscriptionCheckoutService $checkoutService
    ) {}

    public function initiateCheckout(Request $request, Club $club)
    {
        $plan = ClubSubscriptionPlan::findOrFail($request->plan_id);

        // Checkout Session erstellen
        $session = $this->checkoutService->createCheckoutSession(
            $club,
            $plan,
            [
                'billing_interval' => $request->billing_interval ?? 'monthly',
                'success_url' => route('club.checkout.success', ['club' => $club->id]),
                'cancel_url' => route('club.checkout.cancel', ['club' => $club->id]),
            ]
        );

        // Redirect zu Stripe Checkout
        return response()->json([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ]);
    }

    public function openBillingPortal(Club $club)
    {
        $returnUrl = route('club.subscription.index', ['club' => $club->id]);

        // Billing Portal Session erstellen
        $session = $this->checkoutService->createPortalSession($club, $returnUrl);

        return response()->json([
            'portal_url' => $session->url,
        ]);
    }
}
```

**Methoden:**
- `createCheckoutSession(Club $club, ClubSubscriptionPlan $plan, array $options): Session`
- `createPortalSession(Club $club, string $returnUrl): \Stripe\BillingPortal\Session`

### 3. ClubSubscriptionService

**Verwendung: Subscription Management (Cancel, Resume, Swap, Sync)**

```php
use App\Services\Stripe\ClubSubscriptionService;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;

class SubscriptionManagementController extends Controller
{
    public function __construct(
        private ClubSubscriptionService $subscriptionService
    ) {}

    // Plan zu Club zuweisen
    public function assignPlan(Club $club, ClubSubscriptionPlan $plan)
    {
        $this->subscriptionService->assignPlanToClub($club, $plan);

        return response()->json(['message' => 'Plan assigned']);
    }

    // Subscription kündigen
    public function cancelSubscription(Club $club, Request $request)
    {
        $immediately = $request->boolean('immediately', false);

        $this->subscriptionService->cancelSubscription($club, $immediately);

        $message = $immediately
            ? 'Subscription canceled immediately'
            : 'Subscription will cancel at end of billing period';

        return response()->json(['message' => $message]);
    }

    // Subscription fortsetzen (Kündigung rückgängig machen)
    public function resumeSubscription(Club $club)
    {
        $this->subscriptionService->resumeSubscription($club);

        return response()->json(['message' => 'Subscription resumed']);
    }

    // Plan wechseln (Upgrade/Downgrade)
    public function swapPlan(Club $club, ClubSubscriptionPlan $newPlan)
    {
        $this->subscriptionService->swapPlan($club, $newPlan, [
            'billing_interval' => 'monthly',
            'proration_behavior' => 'create_prorations', // oder 'none'
        ]);

        return response()->json(['message' => 'Plan swapped successfully']);
    }

    // Plan mit Stripe synchronisieren (Product & Prices erstellen)
    public function syncPlan(ClubSubscriptionPlan $plan)
    {
        $result = $this->subscriptionService->syncPlanWithStripe($plan);

        return response()->json([
            'product_id' => $result['product']->id,
            'price_monthly_id' => $result['price_monthly']->id,
            'price_yearly_id' => $result['price_yearly']->id,
        ]);
    }

    // Proration Preview (vor Plan-Wechsel)
    public function previewSwap(Club $club, ClubSubscriptionPlan $newPlan)
    {
        $preview = $this->subscriptionService->previewPlanSwap($club, $newPlan, [
            'billing_interval' => 'monthly',
        ]);

        return response()->json([
            'proration_amount' => $preview['proration']['amount'],
            'next_invoice_total' => $preview['upcoming_invoice']['total'],
            'is_upgrade' => $preview['is_upgrade'],
        ]);
    }
}
```

**Methoden:**
- `assignPlanToClub(Club $club, ClubSubscriptionPlan $plan): void`
- `cancelSubscription(Club $club, bool $immediately = false): void`
- `resumeSubscription(Club $club): void`
- `swapPlan(Club $club, ClubSubscriptionPlan $newPlan, array $options = []): void`
- `syncPlanWithStripe(ClubSubscriptionPlan $plan): array`
- `previewPlanSwap(Club $club, ClubSubscriptionPlan $newPlan, array $options = []): array`

### 4. ClubInvoiceService

**Verwendung: Rechnungen abrufen & verwalten**

```php
use App\Services\Stripe\ClubInvoiceService;
use App\Models\Club;

class InvoiceController extends Controller
{
    public function __construct(
        private ClubInvoiceService $invoiceService
    ) {}

    public function listInvoices(Club $club, Request $request)
    {
        $invoices = $this->invoiceService->getInvoices($club, [
            'limit' => $request->input('limit', 10),
            'status' => $request->input('status'), // 'paid', 'open', 'draft', etc.
            'starting_after' => $request->input('starting_after'),
        ]);

        return response()->json(['invoices' => $invoices]);
    }

    public function getInvoice(Club $club, string $invoiceId)
    {
        $invoice = $this->invoiceService->getInvoice($club, $invoiceId);

        return response()->json(['invoice' => $invoice]);
    }

    public function getUpcoming(Club $club)
    {
        $upcomingInvoice = $this->invoiceService->getUpcomingInvoice($club);

        return response()->json(['upcoming_invoice' => $upcomingInvoice]);
    }

    public function downloadPdf(Club $club, string $invoiceId)
    {
        $pdfUrl = $this->invoiceService->getInvoicePdfUrl($club, $invoiceId);

        return redirect()->away($pdfUrl);
    }
}
```

**Methoden:**
- `getInvoices(Club $club, array $options = []): array`
- `getInvoice(Club $club, string $invoiceId): array`
- `getUpcomingInvoice(Club $club, array $options = []): ?array`
- `getInvoicePdfUrl(Club $club, string $invoiceId): string`
- `payInvoice(Club $club, string $invoiceId, array $options = []): array`

### 5. ClubPaymentMethodService

**Verwendung: Zahlungsmethoden verwalten**

```php
use App\Services\Stripe\ClubPaymentMethodService;
use App\Models\Club;

class PaymentMethodController extends Controller
{
    public function __construct(
        private ClubPaymentMethodService $paymentMethodService
    ) {}

    public function listPaymentMethods(Club $club, Request $request)
    {
        $type = $request->input('type', 'card'); // 'card', 'sepa_debit', etc.

        $paymentMethods = $this->paymentMethodService->listPaymentMethods($club, $type);

        return response()->json(['payment_methods' => $paymentMethods]);
    }

    public function createSetupIntent(Club $club)
    {
        $setupIntent = $this->paymentMethodService->createSetupIntent($club, [
            'usage' => 'off_session', // oder 'on_session'
        ]);

        return response()->json([
            'client_secret' => $setupIntent->client_secret,
        ]);
    }

    public function attachPaymentMethod(Club $club, Request $request)
    {
        $paymentMethod = $this->paymentMethodService->attachPaymentMethod(
            $club,
            $request->payment_method_id,
            $request->boolean('set_as_default', false)
        );

        return response()->json(['payment_method' => $paymentMethod]);
    }

    public function detachPaymentMethod(Club $club, string $paymentMethodId)
    {
        $this->paymentMethodService->detachPaymentMethod($club, $paymentMethodId);

        return response()->json(['message' => 'Payment method detached']);
    }

    public function setDefault(Club $club, string $paymentMethodId)
    {
        $this->paymentMethodService->setDefaultPaymentMethod($club, $paymentMethodId);

        return response()->json(['message' => 'Default payment method set']);
    }
}
```

**Methoden:**
- `listPaymentMethods(Club $club, string $type = 'card'): array`
- `createSetupIntent(Club $club, array $options = []): SetupIntent`
- `attachPaymentMethod(Club $club, string $paymentMethodId, bool $setAsDefault = false): PaymentMethod`
- `detachPaymentMethod(Club $club, string $paymentMethodId): PaymentMethod`
- `updatePaymentMethod(Club $club, string $paymentMethodId, array $billingDetails): PaymentMethod`
- `setDefaultPaymentMethod(Club $club, string $paymentMethodId): void`

### 6. SubscriptionAnalyticsService

**Verwendung: MRR/ARR/Churn Analytics**

```php
use App\Services\Stripe\SubscriptionAnalyticsService;

class AnalyticsController extends Controller
{
    public function __construct(
        private SubscriptionAnalyticsService $analyticsService
    ) {}

    public function getMRRMetrics(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $period = $request->input('period', 'monthly'); // 'daily' or 'monthly'

        // Current MRR
        $currentMRR = $this->analyticsService->getCurrentMRR($tenantId);

        // MRR Growth Rate
        $growthRate = $this->analyticsService->getMRRGrowthRate($tenantId, $period);

        // MRR History (last 12 months)
        $history = $this->analyticsService->getMRRHistory($tenantId, 12, $period);

        return response()->json([
            'current_mrr' => $currentMRR,
            'growth_rate' => $growthRate,
            'history' => $history,
        ]);
    }

    public function getChurnMetrics(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $months = $request->input('months', 12);

        // Churn Rate
        $churnRate = $this->analyticsService->calculateChurnRate($tenantId, $months);

        // Churn-at-Risk Clubs (past_due status)
        $atRisk = $this->analyticsService->getChurnRiskClubs($tenantId, 0.7);

        return response()->json([
            'churn_rate' => $churnRate,
            'churn_risk_clubs' => $atRisk,
        ]);
    }

    public function getCohortMetrics(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // Cohort Analysis (z.B. Retention Rate)
        $cohorts = $this->analyticsService->getCohortRetentionData($tenantId, 12);

        return response()->json(['cohorts' => $cohorts]);
    }
}
```

**Methoden:**
- `getCurrentMRR(int $tenantId): float`
- `getCurrentARR(int $tenantId): float`
- `getMRRGrowthRate(int $tenantId, string $period = 'monthly'): float`
- `getMRRHistory(int $tenantId, int $months, string $period = 'monthly'): array`
- `calculateChurnRate(int $tenantId, int $months = 12): float`
- `getChurnRiskClubs(int $tenantId, float $threshold = 0.5): array`
- `getCohortRetentionData(int $tenantId, int $months = 12): array`

### 7. ClubSubscriptionNotificationService

**Verwendung: Email-Benachrichtigungen senden**

```php
use App\Services\ClubSubscriptionNotificationService;
use App\Models\Club;

class NotificationTestController extends Controller
{
    public function __construct(
        private ClubSubscriptionNotificationService $notificationService
    ) {}

    public function sendWelcomeEmail(Club $club)
    {
        $plan = $club->subscriptionPlan;

        $this->notificationService->sendSubscriptionWelcome($club, $plan);

        return response()->json(['message' => 'Welcome email sent']);
    }

    public function sendPaymentSuccessEmail(Club $club)
    {
        $invoiceData = [
            'number' => 'INV-2025-001',
            'amount' => 149.00,
            'currency' => 'EUR',
            'paid_at' => now(),
            'next_billing_date' => now()->addMonth(),
            'billing_interval' => 'monthly',
        ];

        $this->notificationService->sendPaymentSuccessful($club, $invoiceData);

        return response()->json(['message' => 'Payment success email sent']);
    }

    public function sendPaymentFailedEmail(Club $club)
    {
        $invoiceData = [
            'number' => 'INV-2025-001',
            'amount' => 149.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
            'grace_period_days' => 3,
        ];

        $failureReason = 'card_declined';

        $this->notificationService->sendPaymentFailed($club, $invoiceData, $failureReason);

        return response()->json(['message' => 'Payment failed email sent']);
    }
}
```

**Methoden:**
- `sendSubscriptionWelcome(Club $club, ClubSubscriptionPlan $plan): void`
- `sendPaymentSuccessful(Club $club, array $invoiceData): void`
- `sendPaymentFailed(Club $club, array $invoiceData, string $failureReason): void`
- `sendSubscriptionCanceled(Club $club, string $reason, ?\Carbon\Carbon $accessUntil): void`
- `sendChurnRiskAlert(Club $club, float $churnProbability, array $recommendations): void`
- `sendAnalyticsReport(int $tenantId, array $metrics): void`

---

## 🎨 Frontend-Integration (Vue.js)

### 1. Stripe.js Setup

**Composable:** `resources/js/composables/useStripe.js`

```javascript
import { ref, readonly } from 'vue';
import { loadStripe } from '@stripe/stripe-js';

const stripe = ref(null);
const loading = ref(false);

export function useStripe() {
    const publishableKey = import.meta.env.VITE_STRIPE_KEY;

    const initializeStripe = async () => {
        if (stripe.value) return stripe.value;

        loading.value = true;
        try {
            stripe.value = await loadStripe(publishableKey);
        } finally {
            loading.value = false;
        }

        return stripe.value;
    };

    const redirectToCheckout = async (checkoutUrl) => {
        window.location.href = checkoutUrl;
    };

    const confirmCardSetup = async (clientSecret, cardElement, billingDetails) => {
        const stripeInstance = await initializeStripe();

        const { setupIntent, error } = await stripeInstance.confirmCardSetup(
            clientSecret,
            {
                payment_method: {
                    card: cardElement,
                    billing_details: billingDetails,
                },
            }
        );

        return { setupIntent, error };
    };

    return {
        stripe: readonly(stripe),
        loading: readonly(loading),
        initializeStripe,
        redirectToCheckout,
        confirmCardSetup,
    };
}
```

### 2. Checkout-Flow implementieren

**Component:** `resources/js/Pages/Club/Subscription/Index.vue`

```vue
<script setup>
import { ref } from 'vue';
import { useStripe } from '@/composables/useStripe';
import axios from 'axios';

const props = defineProps({
    club: Object,
    availablePlans: Array,
});

const { redirectToCheckout } = useStripe();
const loading = ref(false);
const billingInterval = ref('monthly');

const initiateCheckout = async (plan) => {
    loading.value = true;

    try {
        const response = await axios.post(
            route('club.checkout', { club: props.club.id }),
            {
                plan_id: plan.id,
                billing_interval: billingInterval.value,
                success_url: route('club.checkout.success', { club: props.club.id }),
                cancel_url: route('club.checkout.cancel', { club: props.club.id }),
            }
        );

        // Redirect to Stripe Checkout
        if (response.data.checkout_url) {
            await redirectToCheckout(response.data.checkout_url);
        }
    } catch (error) {
        console.error('Checkout failed:', error);
        alert('Fehler beim Checkout. Bitte versuchen Sie es erneut.');
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="subscription-plans">
        <div class="billing-toggle">
            <button @click="billingInterval = 'monthly'">Monatlich</button>
            <button @click="billingInterval = 'yearly'">Jährlich (10% sparen)</button>
        </div>

        <div class="plans-grid">
            <div v-for="plan in availablePlans" :key="plan.id" class="plan-card">
                <h3>{{ plan.name }}</h3>
                <p class="price">
                    {{ formatPrice(plan.price, billingInterval) }}
                </p>
                <button
                    @click="initiateCheckout(plan)"
                    :disabled="loading"
                >
                    {{ loading ? 'Lädt...' : 'Jetzt buchen' }}
                </button>
            </div>
        </div>
    </div>
</template>
```

### 3. Payment Method hinzufügen

**Component:** `resources/js/Components/Club/Billing/AddPaymentMethodModal.vue`

```vue
<script setup>
import { ref, onMounted } from 'vue';
import { useStripe } from '@/composables/useStripe';
import axios from 'axios';

const props = defineProps({
    club: Object,
});

const emit = defineEmits(['success', 'cancel']);

const { initializeStripe, confirmCardSetup } = useStripe();
const cardElement = ref(null);
const loading = ref(false);
const error = ref(null);

onMounted(async () => {
    // Stripe initialisieren
    const stripeInstance = await initializeStripe();
    const elements = stripeInstance.elements();

    // Card Element erstellen
    const cardElementInstance = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
        },
    });

    cardElementInstance.mount('#card-element');
    cardElement.value = cardElementInstance;
});

const handleSubmit = async () => {
    loading.value = true;
    error.value = null;

    try {
        // 1. SetupIntent erstellen
        const setupResponse = await axios.post(
            route('club.billing.payment-methods.setup', { club: props.club.id })
        );

        const { client_secret } = setupResponse.data;

        // 2. Card Setup bestätigen
        const { setupIntent, error: stripeError } = await confirmCardSetup(
            client_secret,
            cardElement.value,
            {
                name: props.club.name,
                email: props.club.billing_email,
            }
        );

        if (stripeError) {
            error.value = stripeError.message;
            return;
        }

        // 3. Payment Method attachen
        await axios.post(
            route('club.billing.payment-methods.attach', { club: props.club.id }),
            {
                payment_method_id: setupIntent.payment_method,
                set_as_default: true,
            }
        );

        emit('success');
    } catch (err) {
        error.value = err.response?.data?.error || 'Fehler beim Hinzufügen der Zahlungsmethode';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="modal">
        <h2>Zahlungsmethode hinzufügen</h2>

        <form @submit.prevent="handleSubmit">
            <div id="card-element"></div>

            <p v-if="error" class="error">{{ error }}</p>

            <div class="buttons">
                <button type="button" @click="emit('cancel')">Abbrechen</button>
                <button type="submit" :disabled="loading">
                    {{ loading ? 'Lädt...' : 'Hinzufügen' }}
                </button>
            </div>
        </form>
    </div>
</template>
```

---

## 🧪 Testing-Setup

### 1. Test-Environment konfigurieren

**phpunit.xml:**

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="STRIPE_KEY" value="pk_test_..."/>
    <env name="STRIPE_SECRET" value="sk_test_..."/>
    <env name="STRIPE_WEBHOOK_SECRET" value="whsec_test_..."/>
</php>
```

### 2. Stripe-Mocking in Tests

**Base Test Case:** `tests/SubscriptionTestCase.php`

```php
<?php

namespace Tests;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\StripeClient;

abstract class SubscriptionTestCase extends TestCase
{
    use RefreshDatabase;

    protected Club $testClub;
    protected ClubSubscriptionPlan $testPlan;
    protected $mockStripeClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Test Club erstellen
        $this->testClub = Club::factory()->create([
            'tenant_id' => 1,
            'name' => 'Test Club',
        ]);

        // Test Plan erstellen
        $this->testPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => 1,
            'name' => 'Premium Club',
            'price' => 149.00,
            'stripe_product_id' => 'prod_test_123',
            'stripe_price_id_monthly' => 'price_test_456',
            'is_stripe_synced' => true,
        ]);

        // Stripe Client mocken
        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->app->instance(StripeClient::class, $this->mockStripeClient);
    }

    protected function mockStripeCheckoutSession(string $sessionId = 'cs_test_123'): void
    {
        $session = (object) [
            'id' => $sessionId,
            'url' => "https://checkout.stripe.com/c/pay/{$sessionId}",
            'customer' => 'cus_test_123',
            'subscription' => 'sub_test_123',
        ];

        $this->mockStripeClient
            ->shouldReceive('checkout->sessions->create')
            ->andReturn($session);
    }

    protected function mockStripeCustomer(string $customerId = 'cus_test_123'): void
    {
        $customer = (object) [
            'id' => $customerId,
            'email' => $this->testClub->email,
            'name' => $this->testClub->name,
        ];

        $this->mockStripeClient
            ->shouldReceive('customers->create')
            ->andReturn($customer);

        $this->mockStripeClient
            ->shouldReceive('customers->retrieve')
            ->with($customerId)
            ->andReturn($customer);
    }
}
```

### 3. Test-Beispiele

**Unit Test:**

```php
<?php

namespace Tests\Unit;

use Tests\SubscriptionTestCase;
use App\Services\Stripe\ClubStripeCustomerService;

class ClubStripeCustomerServiceTest extends SubscriptionTestCase
{
    private ClubStripeCustomerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClubStripeCustomerService::class);
    }

    /** @test */
    public function it_creates_stripe_customer_for_club()
    {
        $this->mockStripeCustomer('cus_new_123');

        $customer = $this->service->getOrCreateCustomer($this->testClub);

        $this->assertEquals('cus_new_123', $customer->id);
        $this->testClub->refresh();
        $this->assertEquals('cus_new_123', $this->testClub->stripe_customer_id);
    }

    /** @test */
    public function it_retrieves_existing_customer()
    {
        $this->testClub->update(['stripe_customer_id' => 'cus_existing_123']);
        $this->mockStripeCustomer('cus_existing_123');

        $customer = $this->service->getOrCreateCustomer($this->testClub);

        $this->assertEquals('cus_existing_123', $customer->id);
    }
}
```

**Feature Test:**

```php
<?php

namespace Tests\Feature;

use Tests\SubscriptionTestCase;
use App\Models\User;

class ClubCheckoutFlowTest extends SubscriptionTestCase
{
    /** @test */
    public function user_can_initiate_checkout()
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $this->actingAs($user);

        $this->mockStripeCheckoutSession();

        $response = $this->postJson(route('club.checkout', ['club' => $this->testClub->id]), [
            'plan_id' => $this->testPlan->id,
            'billing_interval' => 'monthly',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'checkout_url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
            'session_id' => 'cs_test_123',
        ]);
    }
}
```

### 4. Tests ausführen

```bash
# Alle Subscription-Tests
php artisan test --filter=ClubSubscription

# Spezifische Test-Suite
php artisan test tests/Unit/ClubStripeCustomerServiceTest.php

# Mit Coverage
php artisan test --coverage

# Parallel (schneller)
php artisan test --parallel
```

---

## 🔄 Migration: Tenant → Club Subscriptions

Wenn deine App bereits **Tenant-Level-Subscriptions** hat und du auf **Club-Level** migrieren möchtest:

### 1. Daten-Migration-Script

```php
<?php

use App\Models\Tenant;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Support\Facades\DB;

// Script: database/migrations/migrate_tenant_to_club_subscriptions.php

DB::transaction(function () {
    // Für jeden Tenant
    Tenant::with('clubs')->chunk(100, function ($tenants) {
        foreach ($tenants as $tenant) {
            if (!$tenant->subscribed()) {
                continue; // Skip Tenants ohne Subscription
            }

            // Tenant-Subscription-Daten
            $tenantPlanName = $tenant->subscription('default')->stripe_price ?? 'Enterprise';

            // Entsprechenden Club-Plan finden
            $clubPlan = ClubSubscriptionPlan::where('tenant_id', $tenant->id)
                ->where('name', 'LIKE', "%{$tenantPlanName}%")
                ->first();

            if (!$clubPlan) {
                Log::warning("No club plan found for tenant {$tenant->id}");
                continue;
            }

            // Für jeden Club des Tenants
            foreach ($tenant->clubs as $club) {
                $club->update([
                    'club_subscription_plan_id' => $clubPlan->id,
                    'stripe_customer_id' => $tenant->stripe_id, // Optional: Existierenden Customer nutzen
                    'subscription_status' => 'active',
                    'subscription_started_at' => $tenant->subscription('default')->created_at,
                ]);

                Log::info("Migrated club {$club->id} to club subscription plan {$clubPlan->id}");
            }
        }
    });
});
```

### 2. Rollout-Strategie

**Phased Rollout:**

1. **Phase 1: Feature-Flag aktivieren** (nur für Beta-Tenants)
   ```php
   // config/features.php
   'club_subscriptions_enabled' => env('CLUB_SUBSCRIPTIONS_ENABLED', false),
   ```

2. **Phase 2: UI für ausgewählte Tenants anzeigen**
   ```php
   if (config('features.club_subscriptions_enabled') && auth()->user()->tenant->isBetaTester()) {
       // Zeige Club-Subscription-UI
   }
   ```

3. **Phase 3: Daten-Migration für Beta-Tenants**
4. **Phase 4: Schrittweise für alle Tenants aktivieren**
5. **Phase 5: Tenant-Level-Subscriptions deaktivieren**

---

## 🐛 Troubleshooting

### Problem 1: Webhook-Events kommen nicht an

**Symptome:**
- Checkout erfolgreich, aber Club-Status bleibt `incomplete`
- Logs zeigen keine Webhook-Events

**Lösung:**

1. **Queue Worker läuft nicht:**
   ```bash
   # Prüfen
   ps aux | grep "queue:work"

   # Starten
   php artisan queue:listen --tries=1
   ```

2. **Webhook Secret falsch:**
   ```bash
   # .env prüfen
   STRIPE_WEBHOOK_SECRET_CLUB=whsec_...

   # Mit Stripe CLI testen
   stripe listen --forward-to localhost:8000/webhooks/stripe/club-subscriptions
   ```

3. **Firewall blockiert Webhooks (Production):**
   ```bash
   # Stripe IPs whitelisten:
   # 3.18.12.63, 3.130.192.231, 13.235.14.237, etc.
   # https://stripe.com/docs/ips
   ```

### Problem 2: Checkout Session schlägt fehl

**Symptome:**
- Error: "Plan is not synced with Stripe"

**Lösung:**

```bash
# Plan mit Stripe synchronisieren
php artisan tinker

$plan = \App\Models\ClubSubscriptionPlan::find(1);
$service = app(\App\Services\Stripe\ClubSubscriptionService::class);
$service->syncPlanWithStripe($plan);
```

### Problem 3: Payment Method kann nicht attached werden

**Symptome:**
- Error: "Payment method already attached to a different customer"

**Lösung:**

Payment Methods können nur zu **einem** Customer attached werden. Lösung:

1. Payment Method vom alten Customer detachen (im Stripe Dashboard)
2. Neue Payment Method erstellen (neuen SetupIntent)

### Problem 4: Email-Benachrichtigungen werden nicht gesendet

**Symptome:**
- Webhook erfolgreich, aber keine Emails

**Lösung:**

1. **Mail-Konfiguration prüfen:**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=...
   MAIL_PASSWORD=...
   MAIL_FROM_ADDRESS=noreply@basketmanager.pro
   ```

2. **Queue Worker muss laufen:**
   ```bash
   php artisan queue:listen
   ```

3. **Notification Preferences prüfen:**
   ```php
   $club->notificationPreferences()->updateOrCreate([
       'tenant_id' => $club->tenant_id,
   ], [
       'payment_successful' => true,
       'payment_failed' => true,
   ]);
   ```

### Problem 5: Rate Limiting blockt Requests

**Symptome:**
- HTTP 429: Too Many Requests

**Lösung:**

```bash
# Redis Cache leeren
php artisan cache:clear

# Oder Rate Limits temporär erhöhen (config/stripe.php):
'rate_limits' => [
    'free' => 120, // statt 60
],
```

---

## ✅ Best Practices

### 1. Error Handling

```php
use Stripe\Exception\StripeException;
use Illuminate\Support\Facades\Log;

try {
    $session = $this->checkoutService->createCheckoutSession($club, $plan);
} catch (StripeException $e) {
    // Stripe-spezifische Fehler loggen
    Log::error('Stripe API error', [
        'error_type' => get_class($e),
        'error_message' => $e->getMessage(),
        'error_code' => $e->getStripeCode(),
        'club_id' => $club->id,
        'plan_id' => $plan->id,
    ]);

    // User-friendly Fehlermeldung zurückgeben
    return response()->json([
        'error' => 'Checkout konnte nicht gestartet werden. Bitte versuchen Sie es später erneut.',
    ], 500);
} catch (\Exception $e) {
    // Generische Fehler
    Log::error('Unexpected error in checkout', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'error' => 'Ein unerwarteter Fehler ist aufgetreten.',
    ], 500);
}
```

### 2. Idempotency in Webhooks

```php
use Illuminate\Support\Facades\DB;

protected function handleCheckoutCompleted($session): void
{
    $clubId = $session->metadata->club_id;
    $subscriptionId = $session->subscription;

    // Idempotency-Check: Subscription bereits verarbeitet?
    $club = Club::find($clubId);
    if ($club->stripe_subscription_id === $subscriptionId) {
        Log::info('Checkout already processed (idempotent)', [
            'club_id' => $clubId,
            'subscription_id' => $subscriptionId,
        ]);
        return; // Frühzeitig beenden
    }

    // Atomic update via DB transaction
    DB::transaction(function () use ($club, $session) {
        $club->update([
            'stripe_subscription_id' => $session->subscription,
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
        ]);
    });
}
```

### 3. Graceful Degradation

```php
// Frontend: Fallback wenn Stripe.js nicht lädt

<script setup>
import { useStripe } from '@/composables/useStripe';
import { ref } from 'vue';

const { initializeStripe } = useStripe();
const stripeError = ref(null);

const loadStripe = async () => {
    try {
        await initializeStripe();
    } catch (error) {
        stripeError.value = 'Stripe konnte nicht geladen werden. Bitte prüfen Sie Ihre Internetverbindung.';
        console.error('Stripe loading error:', error);
    }
};
</script>

<template>
    <div v-if="stripeError" class="alert alert-error">
        {{ stripeError }}
        <button @click="loadStripe">Erneut versuchen</button>
    </div>
</template>
```

### 4. Monitoring & Alerting

```php
// app/Http/Controllers/Webhooks/ClubSubscriptionWebhookController.php

protected function handlePaymentFailed($invoice): void
{
    $club = Club::where('stripe_customer_id', $invoice->customer)->first();

    // Critical Alert wenn Zahlung 3x fehlschlägt
    if ($invoice->attempt_count >= 3) {
        // Sentry Alert
        if (app()->bound('sentry')) {
            app('sentry')->captureMessage('Critical: Payment failed 3 times', [
                'level' => 'critical',
                'extra' => [
                    'club_id' => $club->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->amount_due / 100,
                ],
            ]);
        }

        // Slack Notification (optional)
        // Notification::route('slack', config('services.slack.webhook_url'))
        //     ->notify(new PaymentFailedCritical($club, $invoice));
    }

    // Standard Payment Failed handling...
}
```

---

## 📞 Weiterführende Ressourcen

- **API Reference:** [SUBSCRIPTION_API_REFERENCE.md](/docs/SUBSCRIPTION_API_REFERENCE.md)
- **Deployment Guide:** [SUBSCRIPTION_DEPLOYMENT_GUIDE.md](/docs/SUBSCRIPTION_DEPLOYMENT_GUIDE.md)
- **Architecture Guide:** [SUBSCRIPTION_ARCHITECTURE.md](/docs/SUBSCRIPTION_ARCHITECTURE.md)
- **Admin Guide:** [SUBSCRIPTION_ADMIN_GUIDE.md](/docs/SUBSCRIPTION_ADMIN_GUIDE.md)
- **Testing Guide:** [SUBSCRIPTION_TESTING.md](/docs/SUBSCRIPTION_TESTING.md)

**Stripe Dokumentation:**
- https://stripe.com/docs/api
- https://stripe.com/docs/billing/subscriptions/overview
- https://stripe.com/docs/webhooks

**Support:**
- GitHub Issues: https://github.com/yourorg/basketmanager-pro/issues
- Email: support@basketmanager.pro

---

**© 2025 BasketManager Pro** | Version 1.0 | Erstellt: 2025-11-03
