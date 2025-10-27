# 🏀 Multi-Club Subscriptions mit Stripe Integration

**Projekt:** BasketManager Pro - Mehrere Clubs pro Tenant mit individuellen Stripe-Subscriptions
**Erstellt:** 2025-10-27
**Zuletzt aktualisiert:** 2025-10-27 23:45
**Status:** ✅ Phase 1 & 2 ABGESCHLOSSEN - 🚧 Phase 3 IN ARBEIT (75%)
**Priorität:** ⭐⭐⭐ Hoch
**Geschätzte verbleibende Zeit:** ~2-3 Arbeitstage
**Aktueller Fortschritt:** Phase 1: 100% (6/6) | Phase 2: 100% (8/8) | Phase 3: 75% (9/12) | Gesamt: ~70%
..
---

## 📋 Executive Summary

### Projektziel
Ermöglichung von **mehreren Clubs pro Tenant**, wobei jeder Club seine eigene Stripe-Subscription haben kann. Dies erweitert die bestehende Tenant-Level-Subscription-Architektur um eine Club-Ebene mit vollständiger Stripe-Integration.

### Aktuelle Situation (Stand: 2025-10-27)

#### ✅ **Was bereits implementiert ist:**

1. **Datenbank-Schema** (100% Complete)
   - ✅ `club_subscription_plans` Tabelle existiert
   - ✅ `Club` Model hat `club_subscription_plan_id` Foreign Key
   - ✅ Alle Relationships definiert (Tenant ↔ ClubSubscriptionPlan ↔ Club)

2. **Backend-Logik** (80% Complete)
   - ✅ `ClubSubscriptionPlan` Model mit Feature/Limit-Checks
   - ✅ `Club::hasFeature()`, `getLimit()`, `canUse()` Methoden
   - ✅ Hierarchische Validierung (Tenant-Features > Club-Features)
   - ✅ `FeatureGateService` mit Club-Level-Methoden
   - ✅ `ClubService` mit CRUD für Plans
   - ✅ API-Controller `ClubSubscriptionPlanController`
   - ✅ API-Routes (`/api/tenants/{tenant}/club-plans`)

3. **Architektur** (100% Complete)
   - ✅ Multi-Tenant-fähig mit Row-Level-Security
   - ✅ Feature-Hierarchie: Tenant → Club → Team
   - ✅ Limit-Hierarchie: `min(tenant_limit, club_limit)`

#### ✅ **Was JETZT implementiert ist (Phase 1 - 100% Complete):**

1. **Stripe Integration auf Club-Ebene** (100% Complete)
   - ✅ **Stripe-Felder in Datenbank** (`clubs` und `club_subscription_plans` Tabellen erweitert)
   - ✅ **Model-Erweiterungen** (Club & ClubSubscriptionPlan mit Helper-Methoden)
   - ✅ **ClubStripeCustomerService** (Stripe Customer Management für Clubs)
   - ✅ **ClubSubscriptionCheckoutService** (Checkout-Flow für Club-Subscriptions)
   - ✅ **ClubSubscriptionService** (Plan-Verwaltung, Cancellation, Swapping, Stripe-Sync, Proration Preview)
   - ✅ **ClubSubscriptionWebhookController** (11 Webhook-Handler für Stripe-Events)
   - ✅ **ClubCheckoutController** (HTTP-Layer für Checkout & Billing-Portal)
   - ✅ **Routes** (Checkout, Success, Cancel, Billing-Portal, Webhooks)
   - ✅ **Feature-Tests** (ClubCheckoutFlowTest, ClubSubscriptionLifecycleTest)

#### ✅ **Was JETZT implementiert ist (Phase 2 - 100% Complete - 8/8 Steps):**

2. **Billing & Payment Management** (100% Complete - 8/8 Steps)
   - ✅ **ClubInvoiceService** (Invoice Management mit Stripe API)
     - Invoice-Liste abrufen mit Pagination & Filtering
     - Einzelne Invoices anzeigen mit detaillierter Formatierung
     - Upcoming Invoice Preview für nächste Abrechnungsperiode
     - PDF-Download für Invoices
     - Payment Intent Retrieval & Invoice-Payment
   - ✅ **ClubPaymentMethodService** (Payment Method Management)
     - Setup Intent Creation für sichere Zahlungsmethoden-Erfassung
     - Payment Method Listing mit Formatierung (Card, SEPA, Giropay, etc.)
     - Attach/Detach Payment Methods mit Ownership-Validation
     - Update Billing Details auf Payment Methods
     - Set Default Payment Method für Customer & Subscription
     - Deutsche Zahlungsmethoden: Card, SEPA Lastschrift, SOFORT, Giropay, EPS, Bancontact, iDEAL
   - ✅ **ClubSubscriptionService Extended** (Proration Feature)
     - `previewPlanSwap()` - Detaillierte Proration-Vorschau bei Plan-Wechsel
     - Credit/Debit-Berechnung für Upgrade/Downgrade
     - Line-Item Breakdown für Transparenz
   - ✅ **ClubBillingController** (11 HTTP-Endpoints)
     - 4 Invoice-Endpoints (Index, Show, Upcoming, PDF-Download)
     - 6 Payment-Method-Endpoints (List, Create Setup, Attach, Detach, Update, Set Default)
     - 1 Proration-Preview-Endpoint
   - ✅ **Extended Routes** (13 neue Routes unter `/club/{club}/billing/*`)
   - ✅ **Extended Webhook-Handler** (5 neue Stripe-Events)
     - `invoice.created`, `invoice.finalized`, `invoice.payment_action_required`
     - `payment_method.attached`, `payment_method.detached`
   - ✅ **Unit Tests** (26 Tests für Invoice & PaymentMethod Services)

#### ❌ **Was noch FEHLT:**

3. **Frontend UI** (75% Complete - 9/12 Steps)
   - ✅ **Stripe.js Integration & Setup** (Dependencies, useStripe composable)
   - ✅ **Subscription Dashboard** (Club/Subscription/Index.vue mit Plan-Auswahl)
   - ✅ **Subscription Components** (SubscriptionOverview, PlanCard, BillingIntervalToggle)
   - ✅ **Checkout-Seiten** (Success.vue, Cancel.vue)
   - ✅ **Invoice Management UI** (InvoiceCard, UpcomingInvoicePreview, Invoices.vue)
   - ✅ **Payment Method Management UI** (PaymentMethodCard, PaymentMethodList, Modals, PaymentMethods.vue)
   - ✅ **Stripe Elements Integration** (Card, SEPA, Payment Element + 60+ Error Messages)
   - ✅ **Enhanced Stripe Components** (PaymentMethodIcon, TestCardSelector, ThreeDSecureModal)
   - ❌ **Plan Swap Modal** (Proration Preview)
   - ❌ **Navigation Updates** (Billing-Menü)
   - ❌ **Deutsche Lokalisierung** (Translation files)
   - ❌ **Testing & Polish** (Responsive, Loading states, Error handling)

4. **Usage Tracking & Analytics** (0% Complete)
   - ❌ Kein Usage-Tracking auf Club-Ebene
   - ❌ Keine Metriken/Analytics
   - ❌ Keine Subscription-Analytics (MRR, Churn, etc.)

5. **Tests** (80% Complete)
   - ✅ Unit Tests für ClubStripeCustomerService (11 Tests)
   - ✅ Unit Tests für ClubSubscriptionCheckoutService (8 Tests)
   - ✅ Unit Tests für ClubSubscriptionService (9 Tests)
   - ✅ Unit Tests für ClubInvoiceService (13 Tests)
   - ✅ Unit Tests für ClubPaymentMethodService (13 Tests)
   - ✅ Feature Tests für ClubCheckoutFlow (11 Tests)
   - ✅ Feature Tests für ClubSubscriptionLifecycle (9 Tests)
   - ✅ Feature Tests für ClubStripeCustomer (7 Tests)
   - ❌ Feature Tests für ClubBillingController fehlen
   - ❌ Integration-Tests für Stripe-Webhooks fehlen
   - ❌ E2E-Tests für kompletten Checkout-Flow mit echtem Stripe fehlen

---

## 🏗️ Architektur-Überblick

### Hierarchie mit Stripe-Integration

```
┌─────────────────────────────────────────────────────────────┐
│ TENANT (Enterprise Tier)                                    │
│ ├── Subscription: Stripe Customer ID (Tenant-Level)         │
│ ├── Features: [live_scoring, advanced_stats, ...]          │
│ ├── Limits: max_clubs=50, max_teams=200                    │
│ └── Stripe Billing: Via Laravel Cashier                     │
└────────────┬────────────────────────────────────────────────┘
             │
             │ 1:n (Tenant hat mehrere Clubs)
             ▼
┌─────────────────────────────────────────────────────────────┐
│ CLUB 1: "FC Bayern Basketball" (Premium Plan - 149€/Monat) │
│ ├── Subscription: Eigene Stripe Customer ID & Subscription │
│ ├── ClubSubscriptionPlan: "Premium Club"                    │
│ ├── Features: [live_scoring, advanced_stats, video]        │
│ ├── Limits: max_teams=50, max_players=500                  │
│ ├── Stripe Customer ID: cus_bayern_xyz123                  │
│ └── Stripe Subscription ID: sub_bayern_abc456              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ CLUB 2: "Nachwuchsclub München" (Standard Plan - 49€/Monat)│
│ ├── Subscription: Eigene Stripe Customer ID & Subscription │
│ ├── ClubSubscriptionPlan: "Standard Club"                   │
│ ├── Features: [live_scoring, training_management]          │
│ ├── Limits: max_teams=10, max_players=150                  │
│ ├── Stripe Customer ID: cus_nachwuchs_xyz789               │
│ └── Stripe Subscription ID: sub_nachwuchs_def789           │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ CLUB 3: "Jugendabteilung" (Free Plan - 0€)                 │
│ ├── Subscription: KEINE Stripe-Subscription                │
│ ├── ClubSubscriptionPlan: "Free Club"                       │
│ ├── Features: [basic_team_management]                      │
│ ├── Limits: max_teams=2, max_players=30                    │
│ ├── Stripe Customer ID: null                                │
│ └── Stripe Subscription ID: null                            │
└─────────────────────────────────────────────────────────────┘
```

### Billing-Flow

**Wichtig:** Jeder Club zahlt separat, nicht der Tenant!

```
Tenant "Bayerischer Basketball Verband"
├── Zahlt Enterprise-Subscription (499€/Monat) → Tenant-Level
│
├── Club "FC Bayern Basketball"
│   └── Zahlt ZUSÄTZLICH Premium Plan (149€/Monat) → Club-Level
│
├── Club "Nachwuchsclub München"
│   └── Zahlt ZUSÄTZLICH Standard Plan (49€/Monat) → Club-Level
│
└── Club "Jugendabteilung"
    └── Zahlt NICHTS (Free Plan) → Kein Stripe
```

**Gesamtkosten für Tenant:**
- Tenant-Subscription: 499€/Monat (Enterprise)
- Club 1: 149€/Monat (Premium)
- Club 2: 49€/Monat (Standard)
- Club 3: 0€/Monat (Free)
- **TOTAL: 697€/Monat**

---

## 🎯 Implementierungsplan

Die Implementierung ist in **8 Phasen** unterteilt, mit klaren Prioritäten:

### **Phase 1: Stripe Integration für Clubs** (Priorität: 🔴 KRITISCH)
**Dauer:** 3-4 Tage | **Status:** ✅ ABGESCHLOSSEN (100% Complete)

#### 1.1 Database Schema erweitern ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 16:40

**Migration:** `add_stripe_fields_to_clubs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Stripe Customer & Subscription
            $table->string('stripe_customer_id')->nullable()->after('club_subscription_plan_id');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');

            // Subscription Status
            $table->enum('subscription_status', [
                'active', 'trial', 'past_due', 'canceled', 'incomplete'
            ])->default('incomplete')->after('stripe_subscription_id');

            // Subscription Timestamps
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_trial_ends_at')->nullable()->after('subscription_started_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_trial_ends_at');
            $table->timestamp('subscription_current_period_start')->nullable();
            $table->timestamp('subscription_current_period_end')->nullable();

            // Billing Info
            $table->string('billing_email')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('payment_method_id')->nullable();

            // Indexes
            $table->index('stripe_customer_id');
            $table->index('stripe_subscription_id');
            $table->index('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropIndex(['stripe_customer_id']);
            $table->dropIndex(['stripe_subscription_id']);
            $table->dropIndex(['subscription_status']);

            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'subscription_status',
                'subscription_started_at',
                'subscription_trial_ends_at',
                'subscription_ends_at',
                'subscription_current_period_start',
                'subscription_current_period_end',
                'billing_email',
                'billing_address',
                'payment_method_id',
            ]);
        });
    }
};
```

**Migration:** `add_stripe_fields_to_club_subscription_plans_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_subscription_plans', function (Blueprint $table) {
            // Stripe Product & Prices
            $table->string('stripe_product_id')->nullable()->after('icon');
            $table->string('stripe_price_id_monthly')->nullable()->after('stripe_product_id');
            $table->string('stripe_price_id_yearly')->nullable()->after('stripe_price_id_monthly');

            // Sync Status
            $table->boolean('is_stripe_synced')->default(false)->after('stripe_price_id_yearly');
            $table->timestamp('last_stripe_sync_at')->nullable()->after('is_stripe_synced');

            // Trial Settings
            $table->integer('trial_period_days')->default(0)->after('last_stripe_sync_at');

            // Indexes
            $table->index('stripe_product_id');
            $table->index('is_stripe_synced');
        });
    }

    public function down(): void
    {
        Schema::table('club_subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['stripe_product_id']);
            $table->dropIndex(['is_stripe_synced']);

            $table->dropColumn([
                'stripe_product_id',
                'stripe_price_id_monthly',
                'stripe_price_id_yearly',
                'is_stripe_synced',
                'last_stripe_sync_at',
                'trial_period_days',
            ]);
        });
    }
};
```

**Ergebnisse:**
- ✅ Migration `add_stripe_fields_to_clubs_table.php` erstellt und ausgeführt
  - 11 neue Felder: `stripe_customer_id`, `stripe_subscription_id`, `subscription_status`, etc.
  - 3 Indexes für Performance
- ✅ Migration `add_stripe_fields_to_club_subscription_plans_table.php` erstellt und ausgeführt
  - 6 neue Felder: `stripe_product_id`, `stripe_price_id_monthly`, `stripe_price_id_yearly`, etc.
  - 2 Indexes für Performance
- ✅ Club Model erweitert
  - 11 Felder zu `$fillable` hinzugefügt
  - 6 neue Casts (datetime, array)
  - 7 neue Helper-Methoden: `hasActiveSubscription()`, `isOnTrial()`, `trialDaysRemaining()`, etc.
- ✅ ClubSubscriptionPlan Model erweitert
  - 6 Felder zu `$fillable` hinzugefügt
  - 3 neue Casts (boolean, datetime, integer)
  - 6 neue Helper-Methoden: `isSyncedWithStripe()`, `needsStripeSync()`, `hasTrialPeriod()`, etc.
- ✅ Verifizierung erfolgreich
  - Alle Datenbank-Felder existieren
  - Models können Stripe-Daten verarbeiten
  - Helper-Methoden funktionieren korrekt

---

#### 1.2 Service: `ClubStripeCustomerService` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 17:30

**Datei:** `app/Services/Stripe/ClubStripeCustomerService.php`

```php
<?php

namespace App\Services\Stripe;

use App\Models\Club;
use Stripe\Customer;
use Stripe\Exception\StripeException;
use Illuminate\Support\Facades\Log;

class ClubStripeCustomerService
{
    public function __construct(
        private StripeClientManager $clientManager
    ) {}

    /**
     * Create or retrieve Stripe Customer for Club.
     */
    public function getOrCreateCustomer(Club $club): Customer
    {
        // If club already has Stripe Customer, retrieve it
        if ($club->stripe_customer_id) {
            try {
                $client = $this->clientManager->getCurrentTenantClient();
                return $client->customers->retrieve($club->stripe_customer_id);
            } catch (StripeException $e) {
                Log::warning('Club Stripe Customer not found, creating new', [
                    'club_id' => $club->id,
                    'old_stripe_customer_id' => $club->stripe_customer_id,
                ]);
                // Customer not found, create new one
            }
        }

        return $this->createCustomer($club);
    }

    /**
     * Create new Stripe Customer for Club.
     */
    public function createCustomer(Club $club): Customer
    {
        $client = $this->clientManager->getCurrentTenantClient();

        $customerData = [
            'name' => $club->name,
            'email' => $club->billing_email ?? $club->email,
            'description' => "Club: {$club->name} (ID: {$club->id})",
            'metadata' => [
                'club_id' => $club->id,
                'club_uuid' => $club->uuid,
                'tenant_id' => $club->tenant_id,
                'club_name' => $club->name,
            ],
        ];

        // Add address if available
        if ($club->address_street && $club->address_city) {
            $customerData['address'] = [
                'line1' => $club->address_street,
                'city' => $club->address_city,
                'postal_code' => $club->address_zip,
                'state' => $club->address_state,
                'country' => $club->address_country ?? 'DE',
            ];
        }

        try {
            $customer = $client->customers->create($customerData);

            // Save Stripe Customer ID to Club
            $club->update([
                'stripe_customer_id' => $customer->id,
            ]);

            Log::info('Club Stripe Customer created', [
                'club_id' => $club->id,
                'stripe_customer_id' => $customer->id,
            ]);

            return $customer;
        } catch (StripeException $e) {
            Log::error('Failed to create Club Stripe Customer', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update Stripe Customer information.
     */
    public function updateCustomer(Club $club, array $data): Customer
    {
        if (!$club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe Customer ID');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $customer = $client->customers->update(
                $club->stripe_customer_id,
                $data
            );

            Log::info('Club Stripe Customer updated', [
                'club_id' => $club->id,
                'stripe_customer_id' => $customer->id,
            ]);

            return $customer;
        } catch (StripeException $e) {
            Log::error('Failed to update Club Stripe Customer', [
                'club_id' => $club->id,
                'stripe_customer_id' => $club->stripe_customer_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete Stripe Customer (when club is deleted).
     */
    public function deleteCustomer(Club $club): void
    {
        if (!$club->stripe_customer_id) {
            return;
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $client->customers->delete($club->stripe_customer_id);

            Log::info('Club Stripe Customer deleted', [
                'club_id' => $club->id,
                'stripe_customer_id' => $club->stripe_customer_id,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to delete Club Stripe Customer', [
                'club_id' => $club->id,
                'stripe_customer_id' => $club->stripe_customer_id,
                'error' => $e->getMessage(),
            ]);

            // Don't throw - deletion should continue even if Stripe fails
        }
    }
}
```

**Ergebnisse:**
- ✅ Service `ClubStripeCustomerService` erfolgreich erstellt
  - 4 Haupt-Methoden: `getOrCreateCustomer()`, `createCustomer()`, `updateCustomer()`, `deleteCustomer()`
  - Dependency Injection für `StripeClientManager`
  - Umfassende Error-Logging mit strukturierten Log-Einträgen
  - Graceful Error Handling (deleteCustomer wirft keine Exceptions)
- ✅ Unit Tests erstellt (`tests/Unit/ClubStripeCustomerServiceTest.php`)
  - 11 Tests abdecken alle Methoden und Edge-Cases
  - Mocked Stripe Client für isolierte Tests
  - Tests für: Customer Creation, Retrieval, Update, Delete, Error Handling
- ✅ Feature Tests erstellt (`tests/Feature/ClubStripeCustomerTest.php`)
  - 7 Integration-Tests mit echter Datenbankanbindung
  - Tests für: Customer-Lifecycle, Multi-Club-Szenarien, Tenant-Isolation, Address-Handling
- ✅ Service erfolgreich instantiiert via Service Container
  - Syntax-Check bestanden (0 Fehler)
  - Kann über Dependency Injection genutzt werden

---

#### 1.3 Service: `ClubSubscriptionCheckoutService` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:15

**Datei:** `app/Services/Stripe/ClubSubscriptionCheckoutService.php`

```php
<?php

namespace App\Services\Stripe;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Stripe\Checkout\Session;
use Stripe\Exception\StripeException;
use Illuminate\Support\Facades\Log;

class ClubSubscriptionCheckoutService
{
    public function __construct(
        private StripeClientManager $clientManager,
        private ClubStripeCustomerService $customerService
    ) {}

    /**
     * Create Stripe Checkout Session for Club Subscription.
     */
    public function createCheckoutSession(
        Club $club,
        ClubSubscriptionPlan $plan,
        array $options = []
    ): Session {
        // Validate plan belongs to same tenant
        if ($plan->tenant_id !== $club->tenant_id) {
            throw new \Exception('Plan does not belong to club\'s tenant');
        }

        // Validate plan is active and synced with Stripe
        if (!$plan->is_active || !$plan->is_stripe_synced) {
            throw new \Exception('Plan is not active or not synced with Stripe');
        }

        // Get billing interval
        $billingInterval = $options['billing_interval'] ?? 'monthly';
        $priceId = $billingInterval === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (!$priceId) {
            throw new \Exception("No Stripe Price ID for {$billingInterval} billing");
        }

        // Get or create Stripe Customer
        $customer = $this->customerService->getOrCreateCustomer($club);

        $client = $this->clientManager->getCurrentTenantClient();

        $sessionData = [
            'mode' => 'subscription',
            'customer' => $customer->id,
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'success_url' => $options['success_url'] ?? route('club.checkout.success', ['club' => $club->id]),
            'cancel_url' => $options['cancel_url'] ?? route('club.checkout.cancel', ['club' => $club->id]),
            'metadata' => [
                'club_id' => $club->id,
                'club_uuid' => $club->uuid,
                'club_subscription_plan_id' => $plan->id,
                'tenant_id' => $club->tenant_id,
                'billing_interval' => $billingInterval,
            ],
            'subscription_data' => [
                'metadata' => [
                    'club_id' => $club->id,
                    'club_uuid' => $club->uuid,
                    'plan_id' => $plan->id,
                ],
            ],
        ];

        // Add trial period if configured
        if ($plan->trial_period_days > 0) {
            $sessionData['subscription_data']['trial_period_days'] = $plan->trial_period_days;
        }

        // Add customer's email
        if ($club->billing_email ?? $club->email) {
            $sessionData['customer_email'] = $club->billing_email ?? $club->email;
        }

        try {
            $session = $client->checkout->sessions->create($sessionData);

            Log::info('Club Checkout Session created', [
                'club_id' => $club->id,
                'plan_id' => $plan->id,
                'session_id' => $session->id,
            ]);

            return $session;
        } catch (StripeException $e) {
            Log::error('Failed to create Club Checkout Session', [
                'club_id' => $club->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create Stripe Portal Session for managing subscription.
     */
    public function createPortalSession(Club $club, string $returnUrl): \Stripe\BillingPortal\Session
    {
        if (!$club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe Customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            return $client->billingPortal->sessions->create([
                'customer' => $club->stripe_customer_id,
                'return_url' => $returnUrl,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to create Billing Portal Session', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

#### 1.4 Service: `ClubSubscriptionService` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:15

**Datei:** `app/Services/Stripe/ClubSubscriptionService.php`

```php
<?php

namespace App\Services\Stripe;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Stripe\Subscription;
use Stripe\Exception\StripeException;
use Illuminate\Support\Facades\Log;

class ClubSubscriptionService
{
    public function __construct(
        private StripeClientManager $clientManager,
        private ClubStripeCustomerService $customerService
    ) {}

    /**
     * Assign plan to club and update Stripe subscription.
     */
    public function assignPlanToClub(Club $club, ClubSubscriptionPlan $plan): void
    {
        // Validate tenant match
        if ($plan->tenant_id !== $club->tenant_id) {
            throw new \Exception("Plan does not belong to club's tenant");
        }

        $club->update(['club_subscription_plan_id' => $plan->id]);

        Log::info('Plan assigned to club', [
            'club_id' => $club->id,
            'plan_id' => $plan->id,
        ]);
    }

    /**
     * Cancel club subscription.
     */
    public function cancelSubscription(Club $club, bool $immediately = false): void
    {
        if (!$club->stripe_subscription_id) {
            throw new \Exception('Club has no active subscription');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            if ($immediately) {
                // Cancel immediately
                $subscription = $client->subscriptions->cancel($club->stripe_subscription_id);

                $club->update([
                    'subscription_status' => 'canceled',
                    'subscription_ends_at' => now(),
                    'club_subscription_plan_id' => null,
                ]);
            } else {
                // Cancel at period end
                $subscription = $client->subscriptions->update($club->stripe_subscription_id, [
                    'cancel_at_period_end' => true,
                ]);

                $club->update([
                    'subscription_status' => 'active', // Still active until period end
                    'subscription_ends_at' => $subscription->current_period_end
                        ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end)
                        : null,
                ]);
            }

            Log::info('Club subscription canceled', [
                'club_id' => $club->id,
                'subscription_id' => $club->stripe_subscription_id,
                'immediately' => $immediately,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to cancel club subscription', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resume a canceled subscription.
     */
    public function resumeSubscription(Club $club): void
    {
        if (!$club->stripe_subscription_id) {
            throw new \Exception('Club has no subscription to resume');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $subscription = $client->subscriptions->update($club->stripe_subscription_id, [
                'cancel_at_period_end' => false,
            ]);

            $club->update([
                'subscription_status' => 'active',
                'subscription_ends_at' => null,
            ]);

            Log::info('Club subscription resumed', [
                'club_id' => $club->id,
                'subscription_id' => $club->stripe_subscription_id,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to resume club subscription', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upgrade/Downgrade club to different plan.
     */
    public function swapPlan(Club $club, ClubSubscriptionPlan $newPlan, array $options = []): void
    {
        if (!$club->stripe_subscription_id) {
            throw new \Exception('Club must have active subscription to swap plans');
        }

        // Validate new plan
        if ($newPlan->tenant_id !== $club->tenant_id) {
            throw new \Exception("New plan does not belong to club's tenant");
        }

        if (!$newPlan->is_stripe_synced) {
            throw new \Exception('New plan is not synced with Stripe');
        }

        $billingInterval = $options['billing_interval'] ?? 'monthly';
        $newPriceId = $billingInterval === 'yearly'
            ? $newPlan->stripe_price_id_yearly
            : $newPlan->stripe_price_id_monthly;

        if (!$newPriceId) {
            throw new \Exception("New plan has no Stripe Price ID for {$billingInterval}");
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // Get current subscription
            $subscription = $client->subscriptions->retrieve($club->stripe_subscription_id);
            $currentItem = $subscription->items->data[0];

            // Update subscription
            $updatedSubscription = $client->subscriptions->update($club->stripe_subscription_id, [
                'items' => [
                    [
                        'id' => $currentItem->id,
                        'price' => $newPriceId,
                    ],
                ],
                'proration_behavior' => $options['proration_behavior'] ?? 'create_prorations',
            ]);

            // Update club
            $club->update([
                'club_subscription_plan_id' => $newPlan->id,
            ]);

            Log::info('Club plan swapped', [
                'club_id' => $club->id,
                'old_plan_id' => $club->club_subscription_plan_id,
                'new_plan_id' => $newPlan->id,
                'subscription_id' => $club->stripe_subscription_id,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to swap club plan', [
                'club_id' => $club->id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync plan with Stripe (create Product & Prices).
     */
    public function syncPlanWithStripe(ClubSubscriptionPlan $plan): array
    {
        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // Create or update Product
            if ($plan->stripe_product_id) {
                $product = $client->products->update($plan->stripe_product_id, [
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'tenant_id' => $plan->tenant_id,
                    ],
                ]);
            } else {
                $product = $client->products->create([
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'tenant_id' => $plan->tenant_id,
                    ],
                ]);

                $plan->update(['stripe_product_id' => $product->id]);
            }

            // Create or update Monthly Price
            if ($plan->price > 0) {
                if ($plan->stripe_price_id_monthly) {
                    $priceMonthly = $client->prices->retrieve($plan->stripe_price_id_monthly);
                } else {
                    $priceMonthly = $client->prices->create([
                        'product' => $product->id,
                        'unit_amount' => (int)($plan->price * 100), // Convert to cents
                        'currency' => strtolower($plan->currency),
                        'recurring' => ['interval' => 'month'],
                        'metadata' => [
                            'plan_id' => $plan->id,
                            'billing_interval' => 'monthly',
                        ],
                    ]);

                    $plan->update(['stripe_price_id_monthly' => $priceMonthly->id]);
                }

                // Create Yearly Price (with discount)
                $yearlyAmount = (int)($plan->price * 12 * 0.9 * 100); // 10% discount
                if ($plan->stripe_price_id_yearly) {
                    $priceYearly = $client->prices->retrieve($plan->stripe_price_id_yearly);
                } else {
                    $priceYearly = $client->prices->create([
                        'product' => $product->id,
                        'unit_amount' => $yearlyAmount,
                        'currency' => strtolower($plan->currency),
                        'recurring' => ['interval' => 'year'],
                        'metadata' => [
                            'plan_id' => $plan->id,
                            'billing_interval' => 'yearly',
                        ],
                    ]);

                    $plan->update(['stripe_price_id_yearly' => $priceYearly->id]);
                }
            }

            // Mark as synced
            $plan->update([
                'is_stripe_synced' => true,
                'last_stripe_sync_at' => now(),
            ]);

            Log::info('Club plan synced with Stripe', [
                'plan_id' => $plan->id,
                'stripe_product_id' => $product->id,
            ]);

            return [
                'product' => $product,
                'price_monthly' => $priceMonthly ?? null,
                'price_yearly' => $priceYearly ?? null,
            ];
        } catch (StripeException $e) {
            Log::error('Failed to sync plan with Stripe', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

#### 1.5 Webhook-Handler: `ClubSubscriptionWebhookController` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:15

**Datei:** `app/Http/Controllers/Webhooks/ClubSubscriptionWebhookController.php`

```php
<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class ClubSubscriptionWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhooks.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'event_id' => $event->id,
        ]);

        // Handle event
        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
                'customer.subscription.created' => $this->handleSubscriptionCreated($event->data->object),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
                'invoice.payment_succeeded' => $this->handlePaymentSucceeded($event->data->object),
                'invoice.payment_failed' => $this->handlePaymentFailed($event->data->object),
                default => Log::info('Unhandled webhook event', ['type' => $event->type]),
            };
        } catch (\Exception $e) {
            Log::error('Webhook handler failed', [
                'type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Handler failed'], 500);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle checkout.session.completed event.
     */
    protected function handleCheckoutCompleted($session): void
    {
        $clubId = $session->metadata->club_id ?? null;
        $planId = $session->metadata->club_subscription_plan_id ?? null;

        if (!$clubId || !$planId) {
            Log::warning('Checkout completed without club_id or plan_id', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $club = Club::find($clubId);
        if (!$club) {
            Log::error('Club not found for checkout session', [
                'club_id' => $clubId,
                'session_id' => $session->id,
            ]);
            return;
        }

        // Update club with subscription info
        $club->update([
            'stripe_customer_id' => $session->customer,
            'stripe_subscription_id' => $session->subscription,
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
            'club_subscription_plan_id' => $planId,
        ]);

        Log::info('Checkout completed for club', [
            'club_id' => $clubId,
            'subscription_id' => $session->subscription,
        ]);
    }

    /**
     * Handle customer.subscription.created event.
     */
    protected function handleSubscriptionCreated($subscription): void
    {
        $clubId = $subscription->metadata->club_id ?? null;
        if (!$clubId) return;

        $club = Club::find($clubId);
        if (!$club) return;

        $club->update([
            'stripe_subscription_id' => $subscription->id,
            'subscription_status' => $subscription->status,
            'subscription_current_period_start' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_start),
            'subscription_current_period_end' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_end),
        ]);

        if ($subscription->trial_end) {
            $club->update([
                'subscription_trial_ends_at' => \Carbon\Carbon::createFromTimestamp($subscription->trial_end),
            ]);
        }

        Log::info('Subscription created for club', [
            'club_id' => $clubId,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Handle customer.subscription.updated event.
     */
    protected function handleSubscriptionUpdated($subscription): void
    {
        $club = Club::where('stripe_subscription_id', $subscription->id)->first();
        if (!$club) return;

        $club->update([
            'subscription_status' => $subscription->status,
            'subscription_current_period_start' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_start),
            'subscription_current_period_end' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_end),
        ]);

        // Check if subscription is canceled
        if ($subscription->cancel_at_period_end) {
            $club->update([
                'subscription_ends_at' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_end),
            ]);
        }

        Log::info('Subscription updated for club', [
            'club_id' => $club->id,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
        ]);
    }

    /**
     * Handle customer.subscription.deleted event.
     */
    protected function handleSubscriptionDeleted($subscription): void
    {
        $club = Club::where('stripe_subscription_id', $subscription->id)->first();
        if (!$club) return;

        $club->update([
            'subscription_status' => 'canceled',
            'subscription_ends_at' => now(),
            'club_subscription_plan_id' => null, // Remove plan assignment
        ]);

        Log::info('Subscription deleted for club', [
            'club_id' => $club->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Handle invoice.payment_succeeded event.
     */
    protected function handlePaymentSucceeded($invoice): void
    {
        $club = Club::where('stripe_customer_id', $invoice->customer)->first();
        if (!$club) return;

        // Update payment status
        $club->update([
            'subscription_status' => 'active',
        ]);

        Log::info('Payment succeeded for club', [
            'club_id' => $club->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_paid / 100,
        ]);

        // TODO: Send payment confirmation email
    }

    /**
     * Handle invoice.payment_failed event.
     */
    protected function handlePaymentFailed($invoice): void
    {
        $club = Club::where('stripe_customer_id', $invoice->customer)->first();
        if (!$club) return;

        // Mark subscription as past due
        $club->update([
            'subscription_status' => 'past_due',
        ]);

        Log::warning('Payment failed for club', [
            'club_id' => $club->id,
            'invoice_id' => $invoice->id,
        ]);

        // TODO: Send payment failure notification to club admin
    }
}
```

---

#### 1.6 Routes + Controller für Stripe-Integration ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:15

**Dateien:**
- `routes/club_checkout.php`
- `app/Http/Controllers/Stripe/ClubCheckoutController.php`

```php
<?php

use App\Http\Controllers\Stripe\ClubCheckoutController;
use App\Http\Controllers\Webhooks\ClubSubscriptionWebhookController;
use Illuminate\Support\Facades\Route;

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Checkout
    Route::post('/club/{club}/checkout', [ClubCheckoutController::class, 'checkout'])
        ->name('club.checkout');

    // Success/Cancel pages
    Route::get('/club/checkout/success', [ClubCheckoutController::class, 'success'])
        ->name('club.checkout.success');

    Route::get('/club/checkout/cancel', [ClubCheckoutController::class, 'cancel'])
        ->name('club.checkout.cancel');

    // Billing Portal
    Route::post('/club/{club}/billing-portal', [ClubCheckoutController::class, 'billingPortal'])
        ->name('club.billing-portal');
});

// Webhook (no auth)
Route::post('/webhooks/stripe/club-subscriptions', [ClubSubscriptionWebhookController::class, 'handleWebhook'])
    ->name('webhooks.stripe.club-subscriptions');
```

**Registrieren in `bootstrap/app.php` oder `app/Providers/RouteServiceProvider.php`:**

```php
Route::middleware('web')
    ->group(base_path('routes/club_checkout.php'));
```

---

### **Phase 2: Billing & Payment Features** (Priorität: 🔴 HOCH)
**Dauer:** 2-3 Tage | **Status:** ✅ ABGESCHLOSSEN (100% Complete - 8/8 Steps)

#### 2.1 Service: `ClubInvoiceService` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:17

**Datei:** `app/Services/Stripe/ClubInvoiceService.php` (500+ Zeilen)

**Funktionalität:**
- **Invoice Management** für Club-Subscriptions via Stripe API
- **5 Hauptmethoden:**
  1. `getInvoices(Club $club, array $options)` - Liste aller Invoices mit Pagination, Filtering (status, limit, starting_after, ending_before)
  2. `getInvoice(Club $club, string $invoiceId)` - Einzelne Invoice mit detaillierter Formatierung
  3. `getUpcomingInvoice(Club $club, array $options)` - Vorschau der nächsten Invoice
  4. `getInvoicePdfUrl(Club $club, string $invoiceId)` - PDF-Download-Link
  5. `payInvoice(Club $club, string $invoiceId, array $options)` - Manuelles Payment triggern

**Features:**
- Ownership-Validation (Club muss Stripe Customer sein)
- Detaillierte Invoice-Formatierung mit allen relevanten Feldern
- Support für Stripe-Invoice-Status: `draft`, `open`, `paid`, `uncollectible`, `void`
- Payment Intent Retrieval für 3D Secure Handling
- Umfassende Error-Logging & Exception-Handling

**Unit Tests:** `tests/Unit/ClubInvoiceServiceTest.php` (13 Tests)
- ✅ Invoice-Liste abrufen mit Filtering & Pagination
- ✅ Einzelne Invoice abrufen mit Formatting
- ✅ Upcoming Invoice Preview
- ✅ PDF-URL Generation
- ✅ Payment Intent Retrieval
- ✅ Manual Invoice Payment
- ✅ Ownership-Validation (Exception wenn Club kein Customer)
- ✅ Error-Handling für nicht gefundene Invoices

---

#### 2.2 Service: `ClubPaymentMethodService` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:17

**Datei:** `app/Services/Stripe/ClubPaymentMethodService.php` (550+ Zeilen)

**Funktionalität:**
- **Payment Method Management** für Club-Subscriptions
- **Deutsche Zahlungsmethoden:** Card, SEPA Lastschrift, SOFORT, Giropay, EPS, Bancontact, iDEAL
- **8 Hauptmethoden:**
  1. `createSetupIntent(Club $club, array $options)` - Setup Intent für sichere Payment Method Erfassung
  2. `listPaymentMethods(Club $club, string $type)` - Liste aller Payment Methods (filterable by type)
  3. `attachPaymentMethod(Club $club, string $paymentMethodId, bool $setAsDefault)` - Attach Payment Method
  4. `detachPaymentMethod(Club $club, string $paymentMethodId)` - Detach Payment Method
  5. `setDefaultPaymentMethod(Club $club, string $paymentMethodId)` - Set Default Payment Method
  6. `updatePaymentMethod(Club $club, string $paymentMethodId, array $billingDetails)` - Update Billing Details
  7. `getGermanPaymentMethods()` - Liste deutscher Payment Methods
  8. `getLocalizedPaymentMethodNames()` - Deutsche Namen für Payment Methods

**Features:**
- **Setup Intent:** Für sichere Client-Side Payment Method Collection via Stripe Elements
- **Payment Method Lifecycle:** Attach, Detach, Update, Set Default
- **Ownership-Validation:** Verhindert, dass Payment Methods von anderen Clubs detached werden
- **Default Payment Method:** Synchronisiert mit Customer und Subscription
- **Formatierung:** Detaillierte Payment Method Formatierung mit Brand, Last4, Expiry
- **Deutsche Lokalisierung:** "Kreditkarte / EC-Karte", "SEPA Lastschrift", "SOFORT Überweisung", etc.

**Unit Tests:** `tests/Unit/ClubPaymentMethodServiceTest.php` (13 Tests)
- ✅ Setup Intent Creation mit Usage Options
- ✅ Payment Method Listing (Card & SEPA)
- ✅ Attach Payment Method mit & ohne Default-Flag
- ✅ Detach Payment Method mit Ownership-Validation
- ✅ Set Default Payment Method (Customer & Subscription)
- ✅ Update Billing Details
- ✅ German Payment Methods Liste
- ✅ Localized Payment Method Names

---

#### 2.3 Service Extension: `ClubSubscriptionService::previewPlanSwap()` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:17

**Datei:** `app/Services/Stripe/ClubSubscriptionService.php` (Extended +150 Zeilen)

**Neue Methode:**
```php
public function previewPlanSwap(
    Club $club,
    ClubSubscriptionPlan $newPlan,
    array $options = []
): array
```

**Funktionalität:**
- **Proration Preview** für Plan-Wechsel (Upgrade/Downgrade)
- Zeigt vorher an, was der Plan-Wechsel kosten wird
- Ermöglicht User-Transparenz vor Bestätigung des Plan-Swaps

**Return-Daten:**
```php
[
    'current_plan' => [...],  // Current Plan Details (ID, Name, Price)
    'new_plan' => [...],      // New Plan Details
    'billing_interval' => 'monthly|yearly',
    'proration' => [
        'amount' => 0.00,      // Total Proration Amount
        'credit' => 0.00,      // Credit from unused time
        'debit' => 0.00,       // Charge for new plan
        'currency' => 'EUR',
    ],
    'upcoming_invoice' => [
        'amount_due' => 0.00,
        'amount_remaining' => 0.00,
        'subtotal' => 0.00,
        'total' => 0.00,
        'currency' => 'EUR',
        'period_start' => timestamp,
        'period_end' => timestamp,
    ],
    'line_items' => [          // Detailed breakdown per line
        ['description' => '...', 'amount' => 0.00, 'proration' => true, ...],
        ...
    ],
    'effective_date' => timestamp,
    'next_billing_date' => timestamp,
    'is_upgrade' => true|false,
    'is_downgrade' => true|false,
]
```

**Features:**
- Validate Plan Ownership & Stripe Sync
- Calculate Proration Credits & Debits
- Line-Item Breakdown für volle Transparenz
- Upgrade/Downgrade Detection
- Support für Monthly/Yearly Billing Intervals

---

#### 2.4 Controller: `ClubBillingController` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:17

**Datei:** `app/Http/Controllers/Stripe/ClubBillingController.php` (450+ Zeilen)

**11 HTTP-Endpoints:**

**Invoice Management (4 Endpoints):**
1. `GET /club/{club}/billing/invoices` - List Invoices
2. `GET /club/{club}/billing/invoices/{invoice}` - Show Single Invoice
3. `GET /club/{club}/billing/invoices/upcoming` - Upcoming Invoice Preview
4. `GET /club/{club}/billing/invoices/{invoice}/pdf` - Download Invoice PDF

**Payment Method Management (6 Endpoints):**
5. `GET /club/{club}/billing/payment-methods` - List Payment Methods
6. `POST /club/{club}/billing/payment-methods/setup` - Create Setup Intent
7. `POST /club/{club}/billing/payment-methods/attach` - Attach Payment Method
8. `DELETE /club/{club}/billing/payment-methods/{paymentMethod}` - Detach Payment Method
9. `PUT /club/{club}/billing/payment-methods/{paymentMethod}` - Update Payment Method
10. `POST /club/{club}/billing/payment-methods/{paymentMethod}/default` - Set Default

**Proration Preview (1 Endpoint):**
11. `POST /club/{club}/billing/preview-plan-swap` - Preview Plan Swap with Proration

**Features:**
- **Authorization:** Alle Endpoints prüfen `$this->authorize('manageBilling', $club)`
- **Validation:** Request-Validation für alle Input-Parameter
- **Error-Handling:** Try-Catch mit detailliertem Logging
- **JSON-Responses:** Strukturierte Response-Formate
- **Dependency Injection:** `ClubInvoiceService`, `ClubPaymentMethodService`, `ClubSubscriptionService`

---

#### 2.5 Routes: Billing Routes Extended ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:17

**Datei:** `routes/club_checkout.php` (Extended +35 Zeilen)

**13 neue Routes:**
```php
// Invoice Routes
Route::get('/club/{club}/billing/invoices', [ClubBillingController::class, 'indexInvoices'])
    ->name('club.billing.invoices.index');

Route::get('/club/{club}/billing/invoices/upcoming', [ClubBillingController::class, 'upcomingInvoice'])
    ->name('club.billing.invoices.upcoming');

Route::get('/club/{club}/billing/invoices/{invoice}', [ClubBillingController::class, 'showInvoice'])
    ->name('club.billing.invoices.show');

Route::get('/club/{club}/billing/invoices/{invoice}/pdf', [ClubBillingController::class, 'downloadInvoicePdf'])
    ->name('club.billing.invoices.pdf');

// Payment Method Routes
Route::get('/club/{club}/billing/payment-methods', [ClubBillingController::class, 'indexPaymentMethods'])
    ->name('club.billing.payment-methods.index');

Route::post('/club/{club}/billing/payment-methods/setup', [ClubBillingController::class, 'createSetupIntent'])
    ->name('club.billing.payment-methods.setup');

Route::post('/club/{club}/billing/payment-methods/attach', [ClubBillingController::class, 'attachPaymentMethod'])
    ->name('club.billing.payment-methods.attach');

Route::delete('/club/{club}/billing/payment-methods/{paymentMethod}', [ClubBillingController::class, 'detachPaymentMethod'])
    ->name('club.billing.payment-methods.detach');

Route::put('/club/{club}/billing/payment-methods/{paymentMethod}', [ClubBillingController::class, 'updatePaymentMethod'])
    ->name('club.billing.payment-methods.update');

Route::post('/club/{club}/billing/payment-methods/{paymentMethod}/default', [ClubBillingController::class, 'setDefaultPaymentMethod'])
    ->name('club.billing.payment-methods.default');

// Proration Preview Route
Route::post('/club/{club}/billing/preview-plan-swap', [ClubBillingController::class, 'previewPlanSwap'])
    ->name('club.billing.preview-plan-swap');
```

**Features:**
- Alle Routes geschützt mit `['auth', 'verified', 'tenant']` Middleware
- RESTful Route-Naming
- Route-Model-Binding für `{club}` Parameter
- Billing-Specific Route-Group unter `/club/{club}/billing/*`

---

#### 2.6 Webhook-Handler Extended ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 21:17

**Datei:** `app/Http/Controllers/Webhooks/ClubSubscriptionWebhookController.php` (Extended +140 Zeilen)

**5 neue Stripe Webhook-Events:**
1. `invoice.created` - Neue Invoice erstellt
2. `invoice.finalized` - Invoice finalisiert und bereit für Payment
3. `invoice.payment_action_required` - 3D Secure Authentication erforderlich
4. `payment_method.attached` - Payment Method zu Customer hinzugefügt
5. `payment_method.detached` - Payment Method von Customer entfernt

**Neue Handler-Methoden:**
```php
protected function handleInvoiceCreated($invoice): void
protected function handleInvoiceFinalized($invoice): void
protected function handlePaymentActionRequired($invoice): void
protected function handlePaymentMethodAttached($paymentMethod): void
protected function handlePaymentMethodDetached($paymentMethod): void
```

**Features:**
- **Invoice Events:** Logging & Notification-Vorbereitung (TODO: Email senden)
- **3D Secure:** Spezielle Behandlung für Payment Action Required
- **Payment Method Events:** Synchronisation mit Club Model (payment_method_id clearing)
- **Comprehensive Logging:** Alle Events werden mit Club-ID, Tenant-ID, etc. geloggt

**Webhook-Event-Mapping (Gesamt: 11 Events):**
- ✅ `checkout.session.completed`
- ✅ `customer.subscription.created`
- ✅ `customer.subscription.updated`
- ✅ `customer.subscription.deleted`
- ✅ `invoice.payment_succeeded`
- ✅ `invoice.payment_failed`
- ✅ **`invoice.created`** (Phase 2)
- ✅ **`invoice.finalized`** (Phase 2)
- ✅ **`invoice.payment_action_required`** (Phase 2)
- ✅ **`payment_method.attached`** (Phase 2)
- ✅ **`payment_method.detached`** (Phase 2)

---

#### 2.7 Policy: `ClubPolicy::manageBilling()` ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 22:00

**Datei:** `app/Policies/ClubPolicy.php`

**Neue Methode:**
```php
/**
 * Determine whether the user can manage club billing (Stripe subscriptions).
 */
public function manageBilling(User $user, Club $club): bool
{
    // Super admins and admins can manage billing for any club
    if ($user->hasRole(['super_admin', 'admin'])) {
        return true;
    }

    // Club admins can only manage billing for clubs they administer
    if ($user->hasRole('club_admin') && $user->can('view financial data')) {
        $administeredClubIds = $user->getAdministeredClubIds();
        return in_array($club->id, $administeredClubIds);
    }

    return false;
}
```

**Funktionalität:**
- **Authorization für alle 11 Billing-Endpoints** im `ClubBillingController`
- **Role-based Access Control:**
  - ✅ Super Admins: Vollzugriff auf alle Clubs
  - ✅ Admins: Vollzugriff auf alle Clubs
  - ✅ Club Admins: Nur Zugriff auf ihre eigenen Clubs
  - ✅ Andere Rollen: Kein Zugriff
- **Permission Check:** Benötigt zusätzlich `view financial data` Permission
- **Pattern:** Folgt dem Design von `manageFinances()` und `manageSettings()`

**Ergebnisse:**
- ✅ Policy-Methode erfolgreich hinzugefügt
- ✅ Alle 11 Billing-Endpoints sind jetzt autorisiert
- ✅ Verhindert Authorization-Fehler (403 Forbidden)
- ✅ Sichert Club-Billing gegen unautorisierten Zugriff ab

---

#### 2.8 Config: Stripe Webhook-Konfiguration ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 22:00

**Datei:** `config/stripe.php`

**Änderung 1 - Separater Webhook-Secret für Club-Subscriptions:**
```php
'webhooks' => [
    'tolerance' => 300,
    'signing_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'signing_secret_club' => env('STRIPE_WEBHOOK_SECRET_CLUB', env('STRIPE_WEBHOOK_SECRET')),
    'events' => [
        // ...
    ],
],
```

**Änderung 2 - Club-Subscription Events hinzugefügt:**
```php
'events' => [
    // ... existing events ...

    // Club Subscription events (Phase 2)
    'checkout.session.completed',
    'invoice.payment_succeeded', // Bereits vorhanden, aber relevant für Clubs
],
```

**Änderung 3 - ClubSubscriptionWebhookController Config-Key korrigiert:**
```php
// Alt:
$webhookSecret = config('stripe.webhook_secret_club');

// Neu:
$webhookSecret = config('stripe.webhooks.signing_secret_club');
```

**Änderung 4 - .env.example erweitert:**
```env
# Stripe Configuration
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_WEBHOOK_SECRET_CLUB=whsec_...  # Optional: Separate webhook for club subscriptions
```

**Funktionalität:**
- **Separates Webhook-Endpoint:** Ermöglicht separate Stripe Webhook-Endpoints für Club-Subscriptions
- **Fallback-Mechanismus:** Verwendet Haupt-Secret als Fallback, wenn Club-Secret nicht konfiguriert
- **Event-Dokumentation:** Alle 11 Club-Subscription Events sind dokumentiert
- **Deployment-Ready:** Klare .env-Konfiguration für Produktionsumgebung

**Ergebnisse:**
- ✅ Webhook-Secret-Konfiguration vollständig
- ✅ Separates Club-Webhook-Endpoint möglich
- ✅ Webhook-Signatur-Verifikation funktioniert korrekt
- ✅ Deployment-Checkliste vollständig

**Webhook-Events Liste (Gesamt: 11 Events):**
1. `checkout.session.completed` - Checkout abgeschlossen
2. `customer.subscription.created` - Subscription erstellt
3. `customer.subscription.updated` - Subscription aktualisiert
4. `customer.subscription.deleted` - Subscription gelöscht
5. `invoice.payment_succeeded` - Payment erfolgreich
6. `invoice.payment_failed` - Payment fehlgeschlagen
7. `invoice.created` - Invoice erstellt **(Phase 2)**
8. `invoice.finalized` - Invoice finalisiert **(Phase 2)**
9. `invoice.payment_action_required` - 3D Secure erforderlich **(Phase 2)**
10. `payment_method.attached` - Payment Method hinzugefügt **(Phase 2)**
11. `payment_method.detached` - Payment Method entfernt **(Phase 2)**

---

### **Phase 3: Frontend UI** (Priorität: 🔴 HOCH)
**Dauer:** 3-4 Tage | **Status:** 🚧 IN ARBEIT (75% Complete - 9/12 Steps)

#### 3.1 Stripe.js Integration & Setup ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 22:00

**Dateien:**
- `package.json` (Updated)
- `.env.example` (Extended)
- `resources/js/composables/useStripe.js` (240+ Zeilen)

**NPM Package Installation:**
```bash
npm install @stripe/stripe-js
```

**Environment Configuration:**
```env
# .env.example
VITE_STRIPE_KEY="${STRIPE_KEY}"
```

**Composable: `useStripe.js`** (240+ Zeilen)

Zentrales Vue 3 Composable für Stripe.js Integration mit folgenden Features:
- Stripe Instance Initialization mit `loadStripe()`
- Checkout Redirect Helper für Stripe Checkout Sessions
- Payment Confirmation Methods:
  - `confirmCardPayment()` - Credit/Debit Card Payments mit 3D Secure
  - `confirmCardSetup()` - Card Setup Intent Confirmation
  - `confirmSepaDebitSetup()` - SEPA Lastschrift Setup
- Amount Formatting für deutsche Locale (EUR)
- Payment Method Helpers:
  - `getPaymentMethodIcon()` - Icon für Payment Method Typ
  - `getPaymentMethodName()` - Deutscher Name für Payment Method

**Ergebnisse:**
- ✅ @stripe/stripe-js Package installiert (npm)
- ✅ VITE_STRIPE_KEY zu .env.example hinzugefügt
- ✅ useStripe Composable erstellt (240+ Zeilen)
- ✅ Support für Card, SEPA, SOFORT, Giropay, EPS, Bancontact, iDEAL
- ✅ German Locale Formatting (EUR, de-DE)
- ✅ Reactive Stripe Instance mit Loading States
- ✅ Comprehensive Error Handling

---

#### 3.2 Subscription Dashboard & Components ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 22:15

**Dateien:**
- `resources/js/Pages/Club/Subscription/Index.vue` (450+ Zeilen)
- `resources/js/Components/Club/Subscription/SubscriptionOverview.vue` (250+ Zeilen)
- `resources/js/Components/Club/Subscription/PlanCard.vue` (200+ Zeilen)
- `resources/js/Components/Club/Subscription/BillingIntervalToggle.vue` (80+ Zeilen)

**3.2.1 Main Dashboard: Club/Subscription/Index.vue**

Haupt-Subscription-Management-Seite für Clubs mit:
- Current Subscription Overview (Status, Plan, Next Billing)
- Usage Statistics mit Progress Bars (Teams, Players, Games)
- Available Plans Grid mit Billing Interval Toggle
- Stripe Checkout Integration (`initiateCheckout()`)
- Billing Portal Access (`openBillingPortal()`)
- Subscription Cancellation Modal mit Confirm Dialog

**Key Features:**
```vue
const initiateCheckout = async (plan) => {
    const response = await axios.post(route('club.checkout', { club: props.club.id }), {
        plan_id: plan.id,
        billing_interval: billingInterval.value,
        success_url: route('club.checkout.success', { club: props.club.id }),
        cancel_url: route('club.checkout.cancel', { club: props.club.id }),
    });

    if (response.data.checkout_url) {
        redirectToCheckout(response.data.checkout_url);
    }
};
```

**3.2.2 SubscriptionOverview Component**

Displays current subscription status with:
- Status Badges (active, trial, past_due, canceled)
- Trial Period Warnings mit Countdown
- Next Billing Date Display
- Manage Billing & Cancel Buttons
- Empty State für Clubs ohne Subscription

**3.2.3 PlanCard Component**

Individual Plan Display mit:
- Plan Icon & Name
- Description & Features List mit Checkmarks
- Dynamic Pricing (Monthly/Yearly mit 10% Discount)
- Limits Display (Teams, Players, Games)
- Subscribe/Manage Buttons
- Current Plan Highlighting
- Recommended Badge

**3.2.4 BillingIntervalToggle Component**

Toggle zwischen Monthly/Yearly mit:
- Active State Styling
- "10% sparen" Badge für Yearly
- Disabled State Support
- v-model Integration

**Ergebnisse:**
- ✅ Subscription Dashboard (Index.vue) - 450+ Zeilen
- ✅ SubscriptionOverview Component - 250+ Zeilen
- ✅ PlanCard Component - 200+ Zeilen
- ✅ BillingIntervalToggle Component - 80+ Zeilen
- ✅ Checkout Flow Integration mit Stripe.js
- ✅ Billing Portal Integration
- ✅ Cancellation Flow mit Modal

---

#### 3.3 Checkout Success & Cancel Pages ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 22:15

**Dateien:**
- `resources/js/Pages/Club/Checkout/Success.vue` (180+ Zeilen)
- `resources/js/Pages/Club/Checkout/Cancel.vue` (160+ Zeilen)

**3.3.1 Success Page**

Checkout Success Confirmation mit:
- Green Gradient Header mit Success Icon
- Success Message Display
- Subscription Details (Plan, Price, Billing Interval)
- Next Steps Checklist:
  - ✓ Subscription aktiviert
  - ✓ Bestätigungs-Email wird gesendet
  - ✓ Features sind jetzt verfügbar
- Navigation Buttons:
  - "Zur Abonnement-Verwaltung" (Primary)
  - "Zum Dashboard" (Secondary)

**3.3.2 Cancel Page**

Checkout Cancellation Page mit:
- Gray Gradient Header mit Cancel Icon
- Cancellation Message
- "Was ist passiert?" Section
- "Mögliche Gründe" List:
  - Browser geschlossen/zurück navigiert
  - Auf "Abbrechen" geklickt
  - Checkout-Vorgang hat zu lange gedauert
  - Anderen Plan wählen
- "Was können Sie tun?" Section mit Suggestions
- Navigation Buttons:
  - "Erneut versuchen" (Primary)
  - "Zum Dashboard" (Secondary)
- Support Contact Info

**Ergebnisse:**
- ✅ Success Page (Success.vue) - 180+ Zeilen
- ✅ Cancel Page (Cancel.vue) - 160+ Zeilen
- ✅ Clear User Messaging & Guidance
- ✅ Next Steps für beide Szenarien
- ✅ Navigation Integration
- ✅ Support Contact Info

---

#### 3.4 Invoice Management UI ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 22:20

**Dateien:**
- `resources/js/Pages/Club/Billing/Invoices.vue` (300+ Zeilen)
- `resources/js/Components/Club/Billing/InvoiceCard.vue` (180+ Zeilen)
- `resources/js/Components/Club/Billing/UpcomingInvoicePreview.vue` (140+ Zeilen)

**3.4.1 Invoices Page**

Complete Invoice Management mit:
- Upcoming Invoice Preview (if available)
- Invoice List mit Pagination ("Load More" Button)
- Status Filter Dropdown (Alle, Bezahlt, Offen, Entwurf, Uneinbringlich, Storniert)
- Empty, Loading, and Error States
- PDF Download Integration
- Info Box mit wichtigen Informationen

**Key Features:**
```vue
const fetchInvoices = async (append = false) => {
    const params = { limit: 10 };
    if (startingAfter.value && append) params.starting_after = startingAfter.value;
    if (statusFilter.value !== 'all') params.status = statusFilter.value;

    const response = await axios.get(
        route('club.billing.invoices.index', { club: props.club.id }),
        { params }
    );

    // Handle pagination...
};
```

**3.4.2 InvoiceCard Component**

Individual Invoice Display mit:
- Status Badges mit Icons:
  - 📝 Entwurf (gray)
  - ⏳ Offen (yellow)
  - ✓ Bezahlt (green)
  - ✕ Uneinbringlich (red)
  - ∅ Storniert (gray)
- Invoice Number & Date
- Amount Due mit Formatting
- Past Due Warnings (red border & text)
- Due Date Display mit "überfällig" Indicator
- Line Items Preview (max 3 items, then "+X weitere")
- Action Buttons:
  - "Details" (View Details)
  - "PDF" (Download PDF)

**3.4.3 UpcomingInvoicePreview Component**

Next Billing Invoice Preview mit:
- Blue Gradient Background
- "Nächste Rechnung" Header mit Calendar Icon
- Next Billing Date Badge
- Days Until Billing Countdown
- Line Items Breakdown mit Period Dates
- Totals Section:
  - Zwischensumme
  - MwSt (if applicable)
  - Rabatt (if applicable, in green)
  - Gesamt (large, bold)
- Info Note über automatische Abrechnung

**Ergebnisse:**
- ✅ Invoices Page (Invoices.vue) - 300+ Zeilen
- ✅ InvoiceCard Component - 180+ Zeilen
- ✅ UpcomingInvoicePreview Component - 140+ Zeilen
- ✅ Complete Invoice Lifecycle Display
- ✅ Pagination & Filtering
- ✅ PDF Download Integration
- ✅ German Localization

---

#### 3.5 Payment Method Management UI ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 23:00

**Dateien:**
- `resources/js/Pages/Club/Billing/PaymentMethods.vue` (400+ Zeilen)
- `resources/js/Components/Club/Billing/PaymentMethodCard.vue` (250+ Zeilen)
- `resources/js/Components/Club/Billing/PaymentMethodList.vue` (320+ Zeilen)
- `resources/js/Components/Club/Billing/AddPaymentMethodModal.vue` (500+ Zeilen)
- `resources/js/Components/Club/Billing/UpdateBillingDetailsModal.vue` (280+ Zeilen)

**3.5.1 PaymentMethods.vue Page**

Haupt-Payment-Method-Management-Seite mit:
- Active Subscription Info Box (Plan, Status, Next Billing)
- PaymentMethodList Component Integration
- AddPaymentMethodModal für neue Zahlungsmethoden
- UpdateBillingDetailsModal für Billing-Informationen
- API Integration mit 6 Endpoints:
  - `GET /club/{club}/billing/payment-methods` - Liste aller Payment Methods
  - `POST /club/{club}/billing/payment-methods/setup` - Setup Intent erstellen
  - `POST /club/{club}/billing/payment-methods/attach` - Payment Method anhängen
  - `DELETE /club/{club}/billing/payment-methods/{pm}` - Payment Method entfernen
  - `PUT /club/{club}/billing/payment-methods/{pm}` - Billing Details aktualisieren
  - `POST /club/{club}/billing/payment-methods/{pm}/default` - Standard Payment Method setzen
- Toast Notifications für User Feedback
- Quick Action Links zu Invoices & Subscription

**Key Features:**
```vue
const handleAddPaymentMethod = async (setupIntent) => {
    try {
        // SetupIntent bereits von Modal bestätigt
        await axios.post(
            route('club.billing.payment-methods.attach', { club: props.club.id }),
            {
                payment_method_id: setupIntent.payment_method,
                set_as_default: true
            }
        );

        showAddModal.value = false;
        await fetchPaymentMethods();
        showToast('Zahlungsmethode wurde erfolgreich hinzugefügt', 'success');
    } catch (error) {
        showToast('Fehler beim Hinzufügen der Zahlungsmethode', 'error');
    }
};
```

**3.5.2 PaymentMethodCard Component**

Individual Payment Method Display mit:
- Payment Method Icon & Brand Display (Visa, Mastercard, SEPA, etc.)
- Card/SEPA Details (Last4, Expiry, Bank Name)
- Default Badge für Standard-Zahlungsmethode
- Expiration Warnings (Karten ablaufen in <2 Monaten)
- Billing Details Display (Name, Email, Address)
- Action Buttons:
  - "Als Standard festlegen" (für nicht-default Payment Methods)
  - "Bearbeiten" (Update Billing Details)
  - "Löschen" (mit Bestätigungsdialog)
- Delete Confirmation Modal mit Warning

**Payment Method Details Function:**
```javascript
const getPaymentMethodDetails = (pm) => {
    if (pm.card) {
        return `${pm.card.brand?.toUpperCase() || 'Karte'} •••• ${pm.card.last4}`;
    }
    if (pm.sepa_debit) {
        return `SEPA •••• ${pm.sepa_debit.last4}`;
    }
    if (pm.sofort) {
        return 'SOFORT Überweisung';
    }
    if (pm.giropay) {
        return 'Giropay';
    }
    return pm.type || 'Zahlungsmethode';
};
```

**3.5.3 PaymentMethodList Component**

Payment Method Liste mit:
- Type Filter Dropdown (Alle, Karte, SEPA, SOFORT, Giropay)
- Grid Layout (Responsive: 1 col mobile, 2 cols desktop)
- Loading State mit Skeleton Loaders
- Empty State mit "Neue Zahlungsmethode hinzufügen" CTA
- Error State mit Retry Button
- Info Box mit wichtigen Hinweisen:
  - Standard-Zahlungsmethode wird automatisch belastet
  - Mindestens eine Zahlungsmethode erforderlich
  - Daten sind PCI-compliant verschlüsselt

**Events:**
- `@add` - Emit wenn "Hinzufügen" geklickt
- `@set-default` - Emit mit Payment Method ID
- `@update` - Emit mit Payment Method für Billing Details Update
- `@delete` - Emit mit Payment Method ID für Deletion

**3.5.4 AddPaymentMethodModal Component**

Modal für neue Payment Methods mit:
- Tab Navigation (Kreditkarte / SEPA Lastschrift)
- Stripe CardElement Integration (via StripeCardElement component)
- Stripe SepaDebitElement Integration (via StripeSepaElement component)
- Billing Details Form:
  - Name (required)
  - Email (required)
  - Telefon (optional)
  - Adresse (Straße, PLZ, Stadt, Land)
- Country Dropdown (DE, AT, CH, FR, IT, NL, BE, ES, etc.)
- "Als Standard festlegen" Checkbox
- 3-Step Flow:
  1. Setup Intent erstellen (Backend API)
  2. Payment Method mit Stripe.js bestätigen (confirmCardSetup/confirmSepaDebitSetup)
  3. Payment Method an Customer anhängen (Backend API)
- Error Handling mit stripeErrors.js Integration
- Security Info Box mit PCI Compliance Hinweis
- SEPA Mandate Disclaimer (German legal text)

**SetupIntent Flow:**
```javascript
// Step 1: Create Setup Intent
const setupResponse = await axios.post(
    route('club.billing.payment-methods.setup', { club: props.clubId }),
    { payment_method_types: [selectedType.value] }
);

const { client_secret: clientSecret } = setupResponse.data;

// Step 2: Confirm Setup Intent with Stripe
let result;
if (selectedType.value === 'card') {
    result = await stripe.confirmCardSetup(clientSecret, {
        payment_method: {
            card: cardElement,
            billing_details: {
                name: billingDetails.name,
                email: billingDetails.email,
                phone: billingDetails.phone || null,
                address: {
                    line1: billingDetails.address || null,
                    postal_code: billingDetails.postal_code || null,
                    city: billingDetails.city || null,
                    country: billingDetails.country || 'DE',
                },
            },
        },
    });
} else if (selectedType.value === 'sepa_debit') {
    result = await stripe.confirmSepaDebitSetup(clientSecret, {
        payment_method: {
            sepa_debit: ibanElement,
            billing_details: { ... },
        },
    });
}

// Step 3: Emit success
if (result.error) {
    errorMessage.value = formatStripeError(result.error);
} else {
    emit('success', result.setupIntent);
}
```

**3.5.5 UpdateBillingDetailsModal Component**

Modal für Billing Details Update mit:
- Pre-filled Form mit aktuellen Billing Details
- Same Fields wie AddPaymentMethodModal (Name, Email, Phone, Address)
- Payment Method Info Display (Type, Last4)
- Success Message mit Auto-Close (2s)
- Error Handling

**API Call:**
```javascript
const saveBillingDetails = async () => {
    try {
        await axios.put(
            route('club.billing.payment-methods.update', {
                club: props.clubId,
                paymentMethod: props.paymentMethod.id
            }),
            {
                billing_details: {
                    name: billingDetails.name,
                    email: billingDetails.email,
                    phone: billingDetails.phone || null,
                    address: {
                        line1: billingDetails.address || null,
                        postal_code: billingDetails.postal_code || null,
                        city: billingDetails.city || null,
                        country: billingDetails.country || 'DE',
                    },
                },
            }
        );

        showSuccessMessage.value = true;
        setTimeout(() => emit('close'), 2000);
    } catch (error) {
        errorMessage.value = 'Fehler beim Aktualisieren der Zahlungsinformationen';
    }
};
```

**Ergebnisse:**
- ✅ PaymentMethods.vue Page - 400+ Zeilen
- ✅ PaymentMethodCard Component - 250+ Zeilen
- ✅ PaymentMethodList Component - 320+ Zeilen
- ✅ AddPaymentMethodModal Component - 500+ Zeilen
- ✅ UpdateBillingDetailsModal Component - 280+ Zeilen
- ✅ Complete Payment Method CRUD Workflow
- ✅ PCI-compliant Payment Method Collection (Setup Intent Flow)
- ✅ 6 API Endpoints Integration
- ✅ Multi-Payment-Method Support (Card, SEPA, SOFORT, Giropay)
- ✅ German Localization & Legal Compliance (SEPA Mandate)
- ✅ Comprehensive Error Handling

---

#### 3.6 Enhanced Stripe Elements Integration ✅ **ABGESCHLOSSEN**

**Implementiert am:** 2025-10-27 23:30

**Dateien:**
- `resources/js/utils/stripeErrors.js` (150+ Zeilen)
- `resources/js/Components/Stripe/StripeCardElement.vue` (200+ Zeilen)
- `resources/js/Components/Stripe/StripeSepaElement.vue` (180+ Zeilen)
- `resources/js/Components/Stripe/PaymentMethodIcon.vue` (200+ Zeilen)
- `resources/js/Components/Stripe/TestCardSelector.vue` (120+ Zeilen)
- `resources/js/Components/Stripe/ThreeDSecureModal.vue` (180+ Zeilen)
- `resources/js/Components/Stripe/StripePaymentElement.vue` (250+ Zeilen)

**3.6.1 stripeErrors.js Utility**

German Error Message Mapping für 60+ Stripe Error Codes:

**Error Categories:**
- **Card Errors:** card_declined, insufficient_funds, lost_card, stolen_card, expired_card, incorrect_cvc, processing_error, incorrect_number, invalid_expiry_year
- **SEPA Errors:** iban_invalid, bank_account_unusable, debit_not_authorized
- **Payment Intent Errors:** payment_intent_authentication_failure, payment_method_unactivated
- **Setup Intent Errors:** setup_intent_authentication_failure
- **General Errors:** api_error, rate_limit, authentication_required

**Helper Functions:**
```javascript
// 1. Format error message in German
export function formatStripeError(error) {
    if (!error) return 'Ein unbekannter Fehler ist aufgetreten.';

    if (error.code) {
        return getGermanErrorMessage(error.code, error.message);
    }

    if (error.decline_code) {
        return getGermanErrorMessage(error.decline_code, error.message);
    }

    return error.message || 'Ein Fehler ist aufgetreten.';
}

// 2. Check if error is retriable
export function isRetriableError(error) {
    const retriableCodes = [
        'processing_error',
        'issuer_not_available',
        'try_again_later',
        'api_error',
        'rate_limit',
    ];
    return error?.code && retriableCodes.includes(error.code);
}

// 3. Get suggested action for error
export function getErrorAction(error) {
    const actionMap = {
        insufficient_funds: 'add_funds',
        card_declined: 'contact_bank',
        expired_card: 'update_card',
        incorrect_cvc: 'check_cvc',
        lost_card: 'contact_bank',
        stolen_card: 'contact_bank',
        authentication_failure: 'retry_authentication',
        processing_error: 'retry',
    };
    return actionMap[error?.code] || 'contact_support';
}

// 4. Check if error requires support contact
export function requiresSupportContact(error) {
    const supportCodes = [
        'api_error',
        'processing_error',
        'rate_limit',
        'payment_method_unactivated',
    ];
    return error?.code && supportCodes.includes(error.code);
}
```

**Error Messages Examples:**
```javascript
const STRIPE_ERROR_MESSAGES = {
    // Card Errors
    card_declined: 'Ihre Karte wurde abgelehnt. Bitte verwenden Sie eine andere Zahlungsmethode.',
    insufficient_funds: 'Unzureichende Deckung. Bitte verwenden Sie eine andere Karte.',
    lost_card: 'Diese Karte wurde als verloren gemeldet. Bitte verwenden Sie eine andere Karte.',
    expired_card: 'Ihre Karte ist abgelaufen. Bitte verwenden Sie eine gültige Karte.',
    incorrect_cvc: 'Die eingegebene Kartenprüfnummer (CVC) ist ungültig.',

    // SEPA Errors
    iban_invalid: 'Die eingegebene IBAN ist ungültig. Bitte überprüfen Sie die Eingabe.',
    bank_account_unusable: 'Dieses Bankkonto kann nicht verwendet werden.',

    // ... 60+ weitere Fehler
};
```

**3.6.2 StripeCardElement Component**

Reusable Card Element Component mit:
- Stripe CardElement Integration
- Props:
  - `disabled` (Boolean) - Element deaktivieren
  - `autofocus` (Boolean) - Auto-focus on mount
  - `hidePostalCode` (Boolean) - PLZ-Feld ausblenden
  - `hideIcon` (Boolean) - Card-Icon ausblenden
  - `customStyle` (Object) - Custom Styling Override
  - `showErrorMessage` (Boolean) - Fehler anzeigen
  - `helperText` (String) - Hilfstext
- Events:
  - `@ready` - Element geladen
  - `@change` - Element Status geändert (empty, complete, error)
  - `@focus` - Element fokussiert
  - `@blur` - Element verloren Fokus
  - `@error` - Fehler aufgetreten
  - `@complete` - Eingabe vollständig & valide
- Exposed Methods:
  - `focus()` - Element fokussieren
  - `blur()` - Fokus entfernen
  - `clear()` - Element leeren
  - `update(options)` - Element Options aktualisieren
  - `getElement()` - Stripe Element Instanz abrufen
- Auto-Styling:
  - Focus State (Blue border & ring)
  - Error State (Red border & background)
  - Complete State (Green border)
  - Disabled State (Gray background, not-allowed cursor)

**Element Configuration:**
```javascript
const elementOptions = computed(() => ({
    style: {
        base: {
            fontSize: '16px',
            color: '#1f2937',
            fontFamily: 'system-ui, -apple-system, sans-serif',
            '::placeholder': { color: '#9ca3af' },
        },
        invalid: {
            color: '#dc2626',
            iconColor: '#dc2626',
        },
        complete: {
            color: '#059669',
        },
    },
    hidePostalCode: props.hidePostalCode,
    hideIcon: props.hideIcon,
    disabled: props.disabled,
}));
```

**3.6.3 StripeSepaElement Component**

Reusable SEPA/IBAN Element Component mit:
- Stripe IbanElement Integration
- SEPA Mandate Text Display (German legal text)
- Bank Name Detection (automatisch von Stripe)
- Country Detection (DE, AT, etc.)
- Props:
  - Same as CardElement +
  - `supportedCountries` (Array) - z.B. ['SEPA']
  - `placeholderCountry` (String) - z.B. 'DE'
  - `showMandate` (Boolean) - SEPA Mandat anzeigen
  - `mandateText` (String) - Custom Mandate Text
  - `merchantName` (String) - z.B. 'BasketManager Pro'
- Exposed Properties:
  - `bankName` (Ref) - Erkannte Bank
  - `country` (Ref) - Erkanntes Land

**SEPA Mandate Text:**
```javascript
const getDefaultMandateText = () => {
    return `Durch Angabe Ihrer IBAN und Bestätigung dieser Zahlung ermächtigen Sie ${props.merchantName} und Stripe, unserem Zahlungsdienstleister, eine Anweisung an Ihre Bank zu senden, Ihr Konto zu belasten, sowie Ihre Bank, Ihr Konto entsprechend dieser Anweisung zu belasten. Sie haben Anspruch auf Erstattung von Ihrer Bank gemäß den Bedingungen Ihres Vertrages mit Ihrer Bank. Eine Erstattung muss innerhalb von 8 Wochen ab dem Datum der Belastung Ihres Kontos beantragt werden.`;
};
```

**3.6.4 PaymentMethodIcon Component**

SVG Icon Component für alle Payment Methods:

**Supported Payment Methods (15+):**
- 💳 Credit/Debit Cards:
  - Visa
  - Mastercard
  - American Express (Amex)
  - Discover
  - JCB
  - Diners Club
  - UnionPay
- 🏦 SEPA/Bank:
  - SEPA Lastschrift
  - Giropay
  - SOFORT Überweisung
  - iDEAL (Netherlands)
  - Bancontact (Belgium)
  - EPS (Austria)
- 📱 Wallets:
  - Generic Card Icon (Fallback)

**Props:**
- `type` (String, required) - Payment method type (visa, mastercard, sepa, etc.)
- `size` (String) - xs, sm, md, lg, xl (default: md)
- `colorMode` (String) - default, grayscale
- `showTitle` (Boolean) - Tooltip anzeigen

**Size Classes:**
```javascript
const sizeClass = computed(() => {
    const sizes = {
        xs: 'icon-xs',  // 24x16px
        sm: 'icon-sm',  // 32x21px
        md: 'icon-md',  // 48x32px
        lg: 'icon-lg',  // 64x42px
        xl: 'icon-xl',  // 96x64px
    };
    return sizes[props.size] || sizes.md;
});
```

**Usage Example:**
```vue
<PaymentMethodIcon type="visa" size="md" />
<PaymentMethodIcon type="mastercard" size="lg" color-mode="grayscale" />
<PaymentMethodIcon type="sepa_debit" size="sm" :show-title="false" />
```

**3.6.5 TestCardSelector Component**

Development Helper für Stripe Test Cards:

**Visibility:**
- Nur in Development Mode sichtbar (`import.meta.env.DEV`)
- Nicht in Production Build enthalten

**Test Card Categories (30+ Cards):**

**1. ✅ Erfolgreiche Zahlungen:**
- Visa - 4242 4242 4242 4242
- Visa Debit - 4000 0566 5566 5556
- Mastercard - 5555 5555 5555 4444
- Mastercard 2-Series - 2223 0031 2200 3222
- Mastercard Debit - 5200 8282 8282 8210
- American Express - 3782 822463 10005
- Discover - 6011 1111 1111 1117
- Diners Club - 3056 9309 0259 04
- JCB - 3566 0020 2036 0505
- UnionPay - 6200 0000 0000 0005

**2. 🔐 3D Secure / SCA:**
- 3DS Required (Success) - 4000 0027 6000 3184
- 3DS Required (Fail) - 4000 0000 0000 3055
- 3DS Optional - 4000 0025 0000 0003

**3. ❌ Fehlgeschlagene Zahlungen:**
- Generic Decline - 4000 0000 0000 0002
- Insufficient Funds - 4000 0000 0000 9995
- Lost Card - 4000 0000 0000 9987
- Stolen Card - 4000 0000 0000 9979
- Expired Card - 4000 0000 0000 0069
- Incorrect CVC - 4000 0000 0000 0127
- Processing Error - 4000 0000 0000 0119
- Incorrect Number - 4242 4242 4242 4241 (Luhn check fail)

**4. ⚠️ Spezielle Szenarien:**
- Dispute - Fraudulent - 4000 0000 0000 0259
- Dispute - Product Not Received - 4000 0000 0000 2685
- Live Mode Test (Declined) - 4000 0000 0000 0101

**Features:**
- Dropdown Selection mit Optgroups
- Card Details Display (Number, Expiry, CVC, ZIP)
- Copy-to-Clipboard Button für Card Number
- Scenario Descriptions (German)
- Event Emission (`@card-selected`) für Auto-Fill

**Card Data Structure:**
```javascript
const testCards = {
    success_visa: {
        number: '4242 4242 4242 4242',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: 'Standard Visa-Testkarte - Zahlung wird immer erfolgreich sein',
    },
    '3ds_required': {
        number: '4000 0027 6000 3184',
        expiry: '12/34',
        cvc: '123',
        zip: '12345',
        description: '3D Secure wird verlangt und erfolgreich sein (Authentifizierung erforderlich)',
    },
    // ... 30+ weitere
};
```

**3.6.6 ThreeDSecureModal Component**

Enhanced UX für 3D Secure Authentication:

**Features:**
- 3 Status States:
  - `processing` - Authentication läuft
  - `success` - Authentication erfolgreich
  - `error` - Authentication fehlgeschlagen
- Progress Bar mit Timeout Warning
- User Instructions (Popup-Fenster-Hinweise)
- Animated Spinner & Status Icons
- Cancel Button (emits `@cancel`)
- Timeout Event (emits `@timeout`)
- Props:
  - `show` (Boolean) - Modal anzeigen
  - `status` (String) - processing, success, error
  - `title` (String) - Modal-Titel
  - `subtitle` (String) - Untertitel
  - `processingMessage` (String) - Processing-Nachricht
  - `errorMessage` (String) - Fehlermeldung
  - `instructions` (String) - Anweisungen
  - `showProgressBar` (Boolean) - Progress Bar anzeigen
  - `timeout` (Number, ms) - Timeout (default: 60000 = 60s)

**Processing State:**
```vue
<div v-if="status === 'processing'" class="text-center">
    <!-- Animated Spinner -->
    <div class="flex justify-center mb-4">
        <div class="relative">
            <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
            <svg class="absolute inset-0 m-auto w-8 h-8 text-blue-600">
                <path d="M12 15v2m-6 4h12..."/>
            </svg>
        </div>
    </div>

    <h4>{{ processingMessage }}</h4>
    <p>{{ processingDescription }}</p>

    <!-- Progress Bar (75% zeigt Timeout Warning) -->
    <div v-if="showProgressBar" class="w-full bg-gray-200 rounded-full h-2 mb-4">
        <div class="bg-blue-600 h-2 rounded-full" :style="{ width: `${progress}%` }"></div>
    </div>

    <!-- Timeout Warning (bei 75% Progress) -->
    <div v-if="showTimeoutWarning" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p>Die Authentifizierung dauert länger als erwartet. Bitte haben Sie noch einen Moment Geduld.</p>
    </div>
</div>
```

**Timeout Logic:**
```javascript
const startProgressBar = () => {
    progress.value = 0;
    elapsedTime.value = 0;

    progressInterval = setInterval(() => {
        elapsedTime.value += 100;
        progress.value = Math.min((elapsedTime.value / props.timeout) * 100, 100);

        // Show warning at 75% of timeout
        if (progress.value >= 75 && !showTimeoutWarning.value) {
            showTimeoutWarning.value = true;
        }
    }, 100);
};

const startTimeoutTimer = () => {
    timeoutTimer = setTimeout(() => {
        emit('timeout');
        showTimeoutWarning.value = false;
    }, props.timeout);
};
```

**3.6.7 StripePaymentElement Component**

Stripe's Unified Payment Element (neueste Stripe.js API):

**Features:**
- Alle Payment Methods in einem Element
- Dynamic Payment Method Display
- Apple Pay / Google Pay Integration
- Appearance API (Theming)
- Layout Options (tabs, accordion, auto)
- Props:
  - `clientSecret` (String, required) - PaymentIntent oder SetupIntent Client Secret
  - `appearance` (Object) - Theme & Styling
  - `layout` (String) - tabs, accordion, auto
  - `paymentMethodTypes` (Array) - ['card', 'sepa_debit', 'giropay', ...]
  - `wallets` (Object) - Apple Pay / Google Pay Config
  - `terms` (Object) - Terms Display Options
  - `fields` (Object) - Billing Details Fields
  - `business` (Object) - Business Info

**Appearance Configuration:**
```javascript
const elementsOptions = computed(() => ({
    clientSecret: props.clientSecret,
    appearance: {
        theme: props.appearance.theme || 'stripe', // 'stripe', 'night', 'flat'
        variables: {
            colorPrimary: '#3b82f6',
            colorBackground: '#ffffff',
            colorText: '#1f2937',
            colorDanger: '#dc2626',
            fontFamily: 'system-ui, -apple-system, sans-serif',
            spacingUnit: '4px',
            borderRadius: '6px',
            ...props.appearance.variables,
        },
        rules: {
            '.Input': {
                border: '1px solid #d1d5db',
                boxShadow: 'none',
            },
            '.Input:focus': {
                border: '1px solid #3b82f6',
                boxShadow: '0 0 0 3px rgba(59, 130, 246, 0.1)',
            },
            ...props.appearance.rules,
        },
    },
    locale: 'de',
}));
```

**Payment Element Options:**
```javascript
const paymentElementOptions = computed(() => {
    const options = {
        layout: props.layout, // 'tabs' | 'accordion' | 'auto'
    };

    // Payment Method Order
    if (props.paymentMethodTypes?.length > 0) {
        options.paymentMethodOrder = props.paymentMethodTypes;
    }

    // Wallets (Apple Pay / Google Pay)
    if (props.wallets) {
        options.wallets = props.wallets; // { applePay: 'auto', googlePay: 'auto' }
    }

    // Terms (SEPA Mandate, etc.)
    if (props.terms) {
        options.terms = props.terms; // { card: 'auto', sepaDebit: 'auto' }
    }

    // Fields (Billing Details)
    if (props.fields) {
        options.fields = props.fields; // { billingDetails: 'auto' }
    }

    return options;
});
```

**Usage Example:**
```vue
<StripePaymentElement
    :client-secret="clientSecret"
    :appearance="{ theme: 'stripe', variables: { colorPrimary: '#3b82f6' } }"
    layout="tabs"
    :payment-method-types="['card', 'sepa_debit', 'giropay']"
    :wallets="{ applePay: 'auto', googlePay: 'auto' }"
    @ready="handleReady"
    @complete="handleComplete"
/>
```

**Ergebnisse:**
- ✅ stripeErrors.js - 60+ German Error Messages
- ✅ StripeCardElement - Reusable Card Component
- ✅ StripeSepaElement - Reusable SEPA/IBAN Component
- ✅ PaymentMethodIcon - 15+ SVG Icons
- ✅ TestCardSelector - 30+ Test Cards für Development
- ✅ ThreeDSecureModal - Enhanced 3DS UX
- ✅ StripePaymentElement - Unified Payment Element
- ✅ Complete Stripe.js Integration
- ✅ PCI-compliant Tokenization
- ✅ 3D Secure / SCA Support
- ✅ German Localization
- ✅ Comprehensive Error Handling
- ✅ Developer-Friendly Testing Tools

---

#### ⏳ **Was noch FEHLT (3/12 Steps):**

**3.7 Plan Swap Modal mit Proration Preview** (Ausstehend)
- ❌ PlanSwapModal Component
- ❌ Current vs New Plan Comparison
- ❌ Proration Preview Display (Credits/Debits)
- ❌ Line-Item Breakdown
- ❌ Confirmation Flow
- ❌ Integration mit `previewPlanSwap()` API

**3.8 Navigation Updates** (Ausstehend)
- ❌ Add "Billing" Menu Item zu Club Navigation
- ❌ Add Sub-Menu Items:
  - "Abonnement" → Club/Subscription/Index
  - "Rechnungen" → Club/Billing/Invoices
  - "Zahlungsmethoden" → Club/Billing/PaymentMethods

**3.9 Deutsche Lokalisierung** (Ausstehend)
- ❌ Create `resources/lang/de/subscription.php`
- ❌ Add all translation strings
- ❌ Replace hardcoded German text mit `$t()` translation keys
- ❌ Support für Multi-Language (de/en)

**3.10 Testing & Polish** (Ausstehend)
- ❌ Responsive Design Testing (Mobile, Tablet, Desktop)
- ❌ Loading States Testing
- ❌ Error Handling Testing
- ❌ Empty States Testing
- ❌ Browser Compatibility Testing
- ❌ Accessibility (a11y) Testing

---

## 📊 Testing-Strategie

### Unit Tests

```php
// tests/Unit/ClubStripeCustomerServiceTest.php
test('it creates stripe customer for club')
test('it retrieves existing stripe customer')
test('it updates stripe customer information')
test('it deletes stripe customer when club deleted')

// tests/Unit/ClubSubscriptionCheckoutServiceTest.php
test('it creates checkout session for club')
test('it validates plan belongs to tenant')
test('it includes trial period if configured')
test('it throws error for inactive plan')

// tests/Unit/ClubSubscriptionServiceTest.php
test('it assigns plan to club')
test('it cancels subscription immediately')
test('it cancels subscription at period end')
test('it resumes canceled subscription')
test('it swaps plan with proration')
test('it syncs plan with stripe')
```

### Feature Tests

```php
// tests/Feature/ClubCheckoutFlowTest.php
test('club admin can initiate checkout')
test('checkout session redirects to stripe')
test('successful payment activates subscription')
test('canceled checkout returns to club page')
test('webhook activates club subscription')

// tests/Feature/ClubSubscriptionLifecycleTest.php
test('club can subscribe to plan')
test('club can upgrade plan')
test('club can downgrade plan')
test('club can cancel subscription')
test('club can resume subscription')
test('expired subscription removes features')
```

### Integration Tests

```php
// tests/Integration/StripeWebhookTest.php
test('checkout completed webhook assigns plan')
test('subscription updated webhook updates status')
test('payment failed webhook marks past_due')
test('subscription deleted webhook cancels plan')
test('webhook validates signature')
test('webhook ignores invalid events')
```

---

## 🚀 Deployment-Checkliste

### Pre-Deployment

- [ ] **Backup erstellen**
  ```bash
  php artisan backup:run --only-db
  ```

- [ ] **Migrations testen**
  ```bash
  php artisan migrate --pretend
  ```

- [ ] **Environment-Variablen prüfen**
  ```env
  STRIPE_KEY=pk_live_...
  STRIPE_SECRET=sk_live_...
  STRIPE_WEBHOOK_SECRET=whsec_...
  ```

### Deployment

1. [ ] **Code deployen**
2. [ ] **Migrations ausführen**
   ```bash
   php artisan migrate --force
   ```
3. [ ] **Stripe-Pläne synchronisieren**
   ```bash
   php artisan club-plans:sync-stripe
   ```
4. [ ] **Webhooks konfigurieren**
   - Stripe Dashboard → Developers → Webhooks
   - Endpoint URL: `https://basketmanager-pro.de/webhooks/stripe/club-subscriptions`
   - Webhook Secret kopieren und als `STRIPE_WEBHOOK_SECRET_CLUB` in .env speichern
   - Folgende 11 Events auswählen:
     * `checkout.session.completed` - Checkout abgeschlossen
     * `customer.subscription.created` - Subscription erstellt
     * `customer.subscription.updated` - Subscription aktualisiert
     * `customer.subscription.deleted` - Subscription gelöscht
     * `invoice.payment_succeeded` - Payment erfolgreich
     * `invoice.payment_failed` - Payment fehlgeschlagen
     * `invoice.created` - Invoice erstellt (Phase 2)
     * `invoice.finalized` - Invoice finalisiert (Phase 2)
     * `invoice.payment_action_required` - 3D Secure erforderlich (Phase 2)
     * `payment_method.attached` - Payment Method hinzugefügt (Phase 2)
     * `payment_method.detached` - Payment Method entfernt (Phase 2)
5. [ ] **Cache clearen**
   ```bash
   php artisan optimize:clear
   ```

### Post-Deployment

- [ ] **Smoke Tests**
- [ ] **Test-Checkout durchführen**
- [ ] **Webhook-Logs überwachen**
- [ ] **Produktions-Logs prüfen**

---

## 📈 Fortschritt-Tracking

| Phase | Status | Geschätzte Dauer | Tatsächliche Dauer | Fortschritt |
|-------|--------|------------------|-----------------------|-------------|
| **Phase 1: Stripe Integration** | ✅ Abgeschlossen | 3-4 Tage | 1.5 Tage | **100%** (6/6 Steps) |
| └─ 1.1 Database Schema | ✅ Abgeschlossen | 0.5 Tage | 0.5 Tage | 100% |
| └─ 1.2 ClubStripeCustomerService | ✅ Abgeschlossen | 0.5 Tage | 0.5 Tage | 100% |
| └─ 1.3 ClubSubscriptionCheckoutService | ✅ Abgeschlossen | 0.5 Tage | 0.5 Tage | 100% |
| └─ 1.4 ClubSubscriptionService | ✅ Abgeschlossen | 1 Tag | 0.5 Tage | 100% |
| └─ 1.5 Webhook-Handler | ✅ Abgeschlossen | 0.5 Tage | 0.25 Tage | 100% |
| └─ 1.6 Routes + Controller | ✅ Abgeschlossen | 0.25 Tage | 0.25 Tage | 100% |
| **Phase 2: Billing & Payment** | ✅ Abgeschlossen | 2-3 Tage | 1 Tag | **100%** (8/8 Steps) |
| └─ 2.1 ClubInvoiceService | ✅ Abgeschlossen | 0.5 Tage | 0.25 Tage | 100% |
| └─ 2.2 ClubPaymentMethodService | ✅ Abgeschlossen | 0.5 Tage | 0.25 Tage | 100% |
| └─ 2.3 ClubSubscriptionService Extended | ✅ Abgeschlossen | 0.5 Tage | 0.25 Tage | 100% |
| └─ 2.4 ClubBillingController | ✅ Abgeschlossen | 0.5 Tage | 0.125 Tage | 100% |
| └─ 2.5 Routes Extended | ✅ Abgeschlossen | 0.25 Tage | 0.0625 Tage | 100% |
| └─ 2.6 Webhook-Handler Extended | ✅ Abgeschlossen | 0.25 Tage | 0.0625 Tage | 100% |
| └─ 2.7 ClubPolicy Extended | ✅ Abgeschlossen | 0.1 Tage | 0.05 Tage | 100% |
| └─ 2.8 Stripe Config Extended | ✅ Abgeschlossen | 0.1 Tage | 0.05 Tage | 100% |
| **Phase 3: Frontend UI** | 🚧 In Arbeit | 3-4 Tage | 0.5 Tage | **50%** (6/12 Steps) |
| └─ 3.1 Stripe.js Integration & Setup | ✅ Abgeschlossen | 0.5 Tage | 0.125 Tage | 100% |
| └─ 3.2 Subscription Dashboard & Components | ✅ Abgeschlossen | 1 Tag | 0.25 Tage | 100% |
| └─ 3.3 Checkout Success & Cancel Pages | ✅ Abgeschlossen | 0.25 Tage | 0.0625 Tage | 100% |
| └─ 3.4 Invoice Management UI | ✅ Abgeschlossen | 0.5 Tage | 0.125 Tage | 100% |
| └─ 3.5 Payment Method Management UI | ⏳ Ausstehend | 0.5 Tage | - | 0% |
| └─ 3.6 Stripe Elements Integration | ⏳ Ausstehend | 0.5 Tage | - | 0% |
| └─ 3.7 Plan Swap Modal | ⏳ Ausstehend | 0.25 Tage | - | 0% |
| └─ 3.8 Navigation Updates | ⏳ Ausstehend | 0.1 Tage | - | 0% |
| └─ 3.9 Deutsche Lokalisierung | ⏳ Ausstehend | 0.25 Tage | - | 0% |
| └─ 3.10 Testing & Polish | ⏳ Ausstehend | 0.5 Tage | - | 0% |
| **Phase 4: Usage Tracking** | ⏳ Ausstehend | 2 Tage | - | 0% |
| **Phase 5: Notifications** | ⏳ Ausstehend | 1-2 Tage | - | 0% |
| **Phase 6: Testing** | ⏳ Ausstehend | 2-3 Tage | - | 0% |
| **Phase 7: Dokumentation** | ⏳ Ausstehend | 1 Tag | - | 0% |
| **Phase 8: Migration & Rollout** | ⏳ Ausstehend | 1-2 Tage | - | 0% |
| **GESAMT** | **~45%** | **15-21 Tage** | **3 Tage** | 🟩🟩🟩🟩🟩⬜⬜⬜⬜⬜ |

---

## 🔗 Referenzen

- **Existierende Dokumentation:**
  - `CLUB_SUBSCRIPTION_PLANS_IMPLEMENTATION.md` - Basis-Architektur (✅ Abgeschlossen)
  - `CLUB_SUBSCRIPTIONS_IMPLEMENTATION.md` - UI & Erweiterte Features (🚧 In Planung)

- **Laravel Docs:**
  - [Laravel Cashier](https://laravel.com/docs/11.x/billing)
  - [Stripe API](https://stripe.com/docs/api)

- **Code-Beispiele:**
  - `app/Services/Stripe/StripeSubscriptionService.php` - Tenant-Level Subscriptions
  - `app/Models/Tenant.php` - Cashier Billable Trait

---

---

## 📝 Changelog

### 2025-10-27 22:30 - Phase 3 50% Abgeschlossen (6/12 Steps)
- ✅ **Stripe.js Integration & Setup** (Step 3.1 - 100%)
  - NPM Package `@stripe/stripe-js` installiert
  - VITE_STRIPE_KEY zu .env.example hinzugefügt
  - useStripe Composable erstellt (240+ Zeilen)
  - Support für Card, SEPA, SOFORT, Giropay, EPS, Bancontact, iDEAL
  - German Locale Formatting (EUR, de-DE)
  - Reactive Stripe Instance mit Loading States
  - Comprehensive Error Handling

- ✅ **Subscription Dashboard & Components** (Step 3.2 - 100%)
  - Club/Subscription/Index.vue erstellt (450+ Zeilen)
  - SubscriptionOverview Component (250+ Zeilen)
  - PlanCard Component (200+ Zeilen)
  - BillingIntervalToggle Component (80+ Zeilen)
  - Checkout Flow Integration mit Stripe.js
  - Billing Portal Integration
  - Subscription Cancellation Modal

- ✅ **Checkout Success & Cancel Pages** (Step 3.3 - 100%)
  - Club/Checkout/Success.vue erstellt (180+ Zeilen)
  - Club/Checkout/Cancel.vue erstellt (160+ Zeilen)
  - Clear User Messaging & Guidance
  - Next Steps für beide Szenarien
  - Navigation Integration
  - Support Contact Info

- ✅ **Invoice Management UI** (Step 3.4 - 100%)
  - Club/Billing/Invoices.vue Page (300+ Zeilen)
  - InvoiceCard Component (180+ Zeilen)
  - UpcomingInvoicePreview Component (140+ Zeilen)
  - Complete Invoice Lifecycle Display
  - Pagination & Filtering (Status Dropdown)
  - PDF Download Integration
  - German Localization

- 📊 **Fortschritt-Update:**
  - Phase 1: 100% ✅ (6/6 Steps)
  - Phase 2: 100% ✅ (8/8 Steps)
  - Phase 3: 50% 🚧 (6/12 Steps)
  - **Gesamt: ~45%** (von ~30%)
  - Tatsächliche Dauer bisher: 3 Tage (von geschätzten 15-21 Tagen)

- 📂 **Erstellte Dateien (10 neue Vue Files):**
  1. `resources/js/composables/useStripe.js`
  2. `resources/js/Components/Club/Subscription/BillingIntervalToggle.vue`
  3. `resources/js/Components/Club/Subscription/PlanCard.vue`
  4. `resources/js/Components/Club/Subscription/SubscriptionOverview.vue`
  5. `resources/js/Components/Club/Billing/InvoiceCard.vue`
  6. `resources/js/Components/Club/Billing/UpcomingInvoicePreview.vue`
  7. `resources/js/Pages/Club/Subscription/Index.vue`
  8. `resources/js/Pages/Club/Checkout/Success.vue`
  9. `resources/js/Pages/Club/Checkout/Cancel.vue`
  10. `resources/js/Pages/Club/Billing/Invoices.vue`

- ⏭️ **Nächste Schritte (6 Steps verbleibend):**
  - 3.5 Payment Method Management UI (PaymentMethods.vue + Components)
  - 3.6 Stripe Elements Integration (Card & SEPA Elements)
  - 3.7 Plan Swap Modal mit Proration Preview
  - 3.8 Navigation Updates (Billing-Menü)
  - 3.9 Deutsche Lokalisierung (Translation files)
  - 3.10 Testing & Polish (Responsive, Error Handling, a11y)

---

### 2025-10-27 21:17 - Phase 2 VOLLSTÄNDIG Abgeschlossen (All Steps)
- ✅ **ClubInvoiceService** implementiert (500+ Zeilen Code)
  - `getInvoices()` - Invoice-Liste mit Pagination & Filtering
  - `getInvoice()` - Einzelne Invoice mit Formatting
  - `getUpcomingInvoice()` - Preview der nächsten Invoice
  - `getInvoicePdfUrl()` - PDF-Download Link
  - `payInvoice()` - Manuelles Payment triggern
  - Unit Tests erstellt (`ClubInvoiceServiceTest.php` - 13 Tests)

- ✅ **ClubPaymentMethodService** implementiert (550+ Zeilen Code)
  - `createSetupIntent()` - Setup Intent für Payment Method Collection
  - `listPaymentMethods()` - Liste aller Payment Methods
  - `attachPaymentMethod()` - Payment Method hinzufügen
  - `detachPaymentMethod()` - Payment Method entfernen
  - `setDefaultPaymentMethod()` - Default Payment Method setzen
  - `updatePaymentMethod()` - Billing Details aktualisieren
  - Deutsche Zahlungsmethoden: Card, SEPA, SOFORT, Giropay, EPS, Bancontact, iDEAL
  - Unit Tests erstellt (`ClubPaymentMethodServiceTest.php` - 13 Tests)

- ✅ **ClubSubscriptionService erweitert** (+150 Zeilen)
  - `previewPlanSwap()` - Proration Preview für Plan-Wechsel
  - Credit/Debit-Berechnung für Upgrade/Downgrade
  - Line-Item Breakdown für Transparenz
  - Upgrade/Downgrade Detection

- ✅ **ClubBillingController** erstellt (450+ Zeilen, 11 Endpoints)
  - 4 Invoice-Endpoints (Index, Show, Upcoming, PDF)
  - 6 Payment-Method-Endpoints (List, Setup, Attach, Detach, Update, Default)
  - 1 Proration-Preview-Endpoint
  - Authorization via `manageBilling` Policy
  - Request-Validation & Error-Handling

- ✅ **Routes erweitert** (`routes/club_checkout.php`)
  - 13 neue Billing-Routes unter `/club/{club}/billing/*`
  - RESTful Route-Naming
  - Authentication & Tenant-Middleware

- ✅ **Webhook-Handler erweitert** (+140 Zeilen, 5 neue Events)
  - `invoice.created` - Neue Invoice Logging
  - `invoice.finalized` - Invoice bereit für Payment
  - `invoice.payment_action_required` - 3D Secure Handling
  - `payment_method.attached` - Payment Method hinzugefügt
  - `payment_method.detached` - Payment Method entfernt mit Club-Sync
  - Gesamt: 11 Webhook-Events unterstützt

- ✅ **ClubPolicy erweitert** (Phase 2.7)
  - `manageBilling()` Methode hinzugefügt
  - Authorization für alle 11 Billing-Endpoints
  - Role-based Access Control: Super Admins, Admins, Club Admins
  - Benötigt zusätzlich 'view financial data' Permission
  - Verhindert unautorisierten Zugriff auf Club-Billing

- ✅ **Stripe Config erweitert** (Phase 2.8)
  - Separater Webhook-Secret für Club-Subscriptions (`STRIPE_WEBHOOK_SECRET_CLUB`)
  - Config-Key korrigiert in ClubSubscriptionWebhookController (nested path)
  - Alle 11 Club-Subscription Events dokumentiert in config/stripe.php
  - .env.example mit Stripe-Konfiguration erweitert
  - Fallback-Mechanismus: Verwendet Haupt-Secret wenn Club-Secret nicht konfiguriert
  - Deployment-ready mit klarer Webhook-Event-Liste

- 🎯 **Phase 2 Status:** 100% abgeschlossen (alle 8 Steps: 2.1-2.8)
- ⏭️ **Nächster Schritt:** Phase 3 - Frontend UI implementieren

### 2025-10-27 21:15 - Phase 1 VOLLSTÄNDIG Abgeschlossen (Steps 1.3-1.6)
- ✅ **ClubSubscriptionCheckoutService** implementiert
  - `createCheckoutSession()` mit Trial, Tax, Payment Methods, Locale
  - `createPortalSession()` für Stripe Billing Portal
  - Unit Tests erstellt (`ClubSubscriptionCheckoutServiceTest.php`)

- ✅ **ClubSubscriptionService** implementiert
  - `assignPlanToClub()` - Plan zuweisen
  - `cancelSubscription()` - Sofort oder am Perioden-Ende
  - `resumeSubscription()` - Stornierte Subscription fortsetzen
  - `swapPlan()` - Plan-Wechsel mit Proration
  - `syncPlanWithStripe()` - Stripe Product & Prices erstellen
  - Unit Tests erstellt (`ClubSubscriptionServiceTest.php`)
  - Feature Tests erstellt (`ClubSubscriptionLifecycleTest.php` - 9 Tests)

- ✅ **ClubSubscriptionWebhookController** erstellt
  - 6 Webhook-Handler für Stripe-Events:
    - `checkout.session.completed` - Subscription aktivieren
    - `customer.subscription.created/updated/deleted` - Subscription-Status verwalten
    - `invoice.payment_succeeded/failed` - Payment-Status verarbeiten
  - Signature-Verifikation & umfassendes Error-Logging

- ✅ **ClubCheckoutController** erstellt
  - `checkout()` - Checkout-Session initiieren
  - `success()` / `cancel()` - Success/Cancel-Seiten
  - `billingPortal()` - Billing-Portal öffnen
  - `index()` - Subscription-Übersicht
  - Authorization Policies integriert

- ✅ **Routes** definiert (`routes/club_checkout.php`)
  - 5 authentifizierte Routes (checkout, success, cancel, billing-portal, subscription.index)
  - 1 Webhook-Route (ohne Auth)
  - In `bootstrap/app.php` registriert

- ✅ **Feature-Tests** erstellt (`ClubCheckoutFlowTest.php` - 11 Tests)
  - Checkout-Flow (Auth, Validation, Tenant-Isolation)
  - Billing-Portal (Customer-Validierung)
  - Yearly/Monthly-Billing

- 🎯 **Phase 1 Status:** 100% abgeschlossen (alle 6 Steps)
- ⏭️ **Nächster Schritt:** Phase 2 - Billing & Payment Features

### 2025-10-27 17:30 - Phase 1.2 Abgeschlossen
- ✅ Service `ClubStripeCustomerService` erstellt und implementiert
  - 4 Haupt-Methoden für Stripe Customer Management
  - Dependency Injection Pattern mit `StripeClientManager`
  - Umfassendes Error Handling und strukturiertes Logging
- ✅ Unit Tests erstellt (`ClubStripeCustomerServiceTest.php`)
  - 11 Test-Cases mit Mocked Stripe Client
  - Vollständige Abdeckung aller Methoden und Edge-Cases
- ✅ Feature Tests erstellt (`ClubStripeCustomerTest.php`)
  - 7 Integration-Tests mit echter Datenbankanbindung
  - Tests für Multi-Club-Szenarien und Tenant-Isolation
- ✅ Service erfolgreich instantiiert und verifiziert
- ⏭️ **Nächster Schritt:** Phase 1.3 - ClubSubscriptionCheckoutService implementieren

### 2025-10-27 16:45 - Phase 1.1 Abgeschlossen
- ✅ Zwei Migrations erstellt und ausgeführt
  - `add_stripe_fields_to_clubs_table.php` (11 Felder, 3 Indexes)
  - `add_stripe_fields_to_club_subscription_plans_table.php` (6 Felder, 2 Indexes)
- ✅ Club Model erweitert (11 fillable, 6 casts, 7 methods)
- ✅ ClubSubscriptionPlan Model erweitert (6 fillable, 3 casts, 6 methods)
- ✅ Verifizierung erfolgreich durchgeführt
- ⏭️ **Nächster Schritt:** Phase 1.2 - ClubStripeCustomerService implementieren

---

**Erstellt von:** Claude Code
**Datum:** 2025-10-27
**Version:** 1.2.0
**Status:** ✅ Phase 1 & 2 VOLLSTÄNDIG abgeschlossen - Ready for Phase 3
**Nächster Schritt:** Phase 3 - Frontend UI implementieren
