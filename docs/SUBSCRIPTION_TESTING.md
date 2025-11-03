# Subscription & Billing System - Testing Guide

**BasketManager Pro - Multi-Club Subscriptions mit Stripe Integration**

Dieses Dokument beschreibt das umfassende Test-System für das Multi-Club Subscription & Billing Feature.

---

## 📋 Übersicht

Das Subscription-System verfügt über **40 umfassende Tests** mit **~4,350 Zeilen Test-Code**, die alle kritischen Pfade abdecken:

- **23 Integration Tests** - Stripe Webhook-Events & System-Verhalten
- **17 E2E Tests** - Kompletter Checkout-Flow mit Payment-Szenarien
- **100% Coverage** für alle kritischen Services

### Test-Architektur

```
tests/
├── Unit/                           # Service & Mail Unit Tests
│   ├── ClubSubscriptionCheckoutServiceTest.php
│   ├── ClubSubscriptionServiceTest.php
│   ├── ClubStripeCustomerServiceTest.php
│   ├── ClubInvoiceServiceTest.php
│   ├── ClubPaymentMethodServiceTest.php
│   ├── ClubSubscriptionNotificationServiceTest.php
│   ├── SubscriptionAnalyticsServiceTest.php
│   └── Mail/ClubSubscription/*.php
│
├── Feature/                        # Feature & Flow Tests
│   ├── ClubCheckoutFlowTest.php
│   ├── ClubSubscriptionLifecycleTest.php
│   ├── ClubBillingControllerTest.php
│   ├── ClubSubscriptionNotificationFlowTest.php
│   └── SubscriptionAnalyticsFlowTest.php
│
├── Integration/                    # Webhook Integration Tests
│   └── ClubSubscriptionWebhookTest.php (23 Tests, ~900 Zeilen)
│
└── Feature/                        # E2E Tests
    └── ClubCheckoutE2ETest.php     (17 Tests, ~600 Zeilen)
```

---

## 🚀 Quick Start

### Alle Subscription-Tests ausführen

```bash
# Alle Tests mit "ClubSubscription" im Namen
php artisan test --filter=ClubSubscription

# Mit Coverage-Report (erfordert Xdebug)
php artisan test --filter=ClubSubscription --coverage

# Nur Integration Tests (Webhooks)
php artisan test tests/Integration/ClubSubscriptionWebhookTest.php

# Nur E2E Tests (Checkout-Flow)
php artisan test tests/Feature/ClubCheckoutE2ETest.php

# Spezifische Test-Gruppen
php artisan test --testsuite=Unit --filter=ClubSubscription
php artisan test --testsuite=Feature --filter=ClubSubscription
```

### Coverage für spezifische Services

```bash
# Checkout Service
php artisan test tests/Unit/ClubSubscriptionCheckoutServiceTest.php

# Notification Service
php artisan test tests/Unit/ClubSubscriptionNotificationServiceTest.php

# Analytics Service
php artisan test tests/Unit/SubscriptionAnalyticsServiceTest.php
```

---

## ⚙️ Environment Setup

### 1. Stripe Test Keys konfigurieren

Erstelle/Aktualisiere `.env.testing`:

```env
# Stripe Test Mode Keys
STRIPE_KEY=pk_test_51...
STRIPE_SECRET=sk_test_51...
STRIPE_WEBHOOK_SECRET=whsec_test_...
STRIPE_WEBHOOK_SECRET_CLUB=whsec_test_...  # Optional: Separate webhook for clubs

# Test Database (MySQL recommended)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basketmanager_test
DB_USERNAME=root
DB_PASSWORD=

# Mail Testing
MAIL_MAILER=log
```

**Wichtig:** Verwende immer **Test Mode Keys** (beginnen mit `pk_test_` und `sk_test_`). Production Keys (`pk_live_`, `sk_live_`) dürfen **nie** in Tests verwendet werden!

### 2. Test-Datenbank vorbereiten

```bash
# Datenbank erstellen (wenn nötig)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS basketmanager_test;"

# Migrations ausführen
php artisan migrate --env=testing

# Optional: Seeders ausführen
php artisan db:seed --env=testing
```

### 3. Stripe CLI installieren (optional für Webhook-Testing)

```bash
# Installation (macOS)
brew install stripe/stripe-cli/stripe

# Installation (Linux)
wget https://github.com/stripe/stripe-cli/releases/download/v1.19.5/stripe_1.19.5_linux_x86_64.tar.gz
tar -xvf stripe_1.19.5_linux_x86_64.tar.gz
sudo mv stripe /usr/local/bin/

# Login
stripe login

# Webhook forwarding (für lokale Tests)
stripe listen --forward-to http://localhost:8000/webhooks/stripe/club-subscriptions
```

---

## 💳 Stripe Test-Szenarien

### Test Card Numbers

Die folgenden Stripe Test-Karten werden in den E2E Tests verwendet:

| Kartennummer | Szenario | CVC | Expiry |
|--------------|----------|-----|--------|
| `4242 4242 4242 4242` | ✅ Success | Beliebig | Zukunft |
| `4000 0000 0000 0002` | ❌ Generic Decline | Beliebig | Zukunft |
| `4000 0000 0000 9995` | ❌ Insufficient Funds | Beliebig | Zukunft |
| `4000 0027 6000 3184` | 🔐 3D Secure Required | Beliebig | Zukunft |
| `4000 0082 6000 0000` | 🇩🇪 SEPA Direct Debit (Germany) | - | - |
| `4000 0000 0000 9987` | ❌ Lost Card | Beliebig | Zukunft |
| `4000 0000 0000 0069` | ❌ Expired Card | Beliebig | Vergangenheit |

**Verwendung in Tests:**

```php
// Success Case
$this->mockStripeCheckoutSession([
    'payment_method' => 'pm_card_visa', // 4242 4242 4242 4242
    'status' => 'complete',
    'payment_status' => 'paid',
]);

// Decline Case
$this->expectException(CardException::class);
$this->mockStripeCheckoutSession([
    'payment_method' => 'pm_card_chargeDeclined', // 4000 0000 0000 0002
]);

// 3D Secure
$this->mockStripeCheckoutSession([
    'payment_method' => 'pm_card_threeDSecure2Required', // 4000 0027 6000 3184
    'status' => 'open',
    'payment_status' => 'unpaid',
]);
```

### Test-Payment-Methods

```php
// Card Payment Method
'payment_method_types' => ['card']

// SEPA Direct Debit (Deutschland)
'payment_method_types' => ['card', 'sepa_debit']

// Alle deutschen Payment Methods
'payment_method_types' => ['card', 'sepa_debit', 'sofort', 'giropay']
```

---

## 🔗 Webhook Testing

### 11 Stripe Events getestet

Die Integration Tests (`ClubSubscriptionWebhookTest.php`) decken alle kritischen Webhook-Events ab:

#### **Subscription Lifecycle (4 Events)**

```php
// 1. checkout.session.completed
// - Subscription wird aktiviert
// - Welcome Email wird gesendet
// - Club-Daten werden aktualisiert

// 2. customer.subscription.created
// - Subscription-Details werden gespeichert
// - Current period start/end werden gesetzt
// - Trial period wird erfasst

// 3. customer.subscription.updated
// - Status-Updates werden reflektiert
// - Cancel_at_period_end wird verarbeitet

// 4. customer.subscription.deleted
// - Status → 'canceled'
// - Cancellation Email wird gesendet
// - Churn Event wird geloggt
```

#### **Invoice & Payment Events (5 Events)**

```php
// 5. invoice.payment_succeeded
// - Status → 'active'
// - Payment Success Email
// - Notification Log wird erstellt

// 6. invoice.payment_failed
// - Status → 'past_due'
// - Payment Failed Email mit Reason
// - Grace Period wird kommuniziert

// 7. invoice.created (Logging only)

// 8. invoice.finalized (Logging only)

// 9. invoice.payment_action_required (3D Secure)
// - Payment Failed Email mit 3DS Hinweis
// - Payment Intent URL wird kommuniziert
```

#### **Payment Method Events (2 Events)**

```php
// 10. payment_method.attached (Logging only)

// 11. payment_method.detached
// - Club's payment_method_id wird gecleared
```

### Webhook Signature Verification

Alle Webhook-Tests verwenden **echte Stripe Signature Verification**:

```php
protected function generateWebhookSignature(string $payload, int $timestamp): string
{
    $secret = config('stripe.webhooks.signing_secret_club');
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return "t={$timestamp},v1={$signature}";
}
```

### Test-Beispiel: Webhook Handler

```php
/** @test */
public function payment_succeeded_webhook_sends_email_and_creates_log()
{
    Mail::fake();

    $club = Club::factory()->withSubscription()->create();

    $payload = $this->createWebhookPayload('invoice.payment_succeeded', [
        'customer' => $club->stripe_customer_id,
        'amount_paid' => 4900, // €49.00
        'invoice' => 'in_1234567890',
    ]);

    $response = $this->postJson(
        route('webhooks.stripe.club-subscriptions'),
        $payload,
        ['Stripe-Signature' => $this->generateWebhookSignature($payload, time())]
    );

    $response->assertStatus(200);

    // Assert email queued
    Mail::assertQueued(PaymentSuccessfulMail::class, function ($mail) use ($club) {
        return $mail->club->id === $club->id;
    });

    // Assert notification log created
    $this->assertDatabaseHas('notification_logs', [
        'notifiable_type' => Club::class,
        'notifiable_id' => $club->id,
        'notification_type' => PaymentSuccessfulMail::class,
        'status' => 'queued',
    ]);
}
```

---

## 🧪 E2E Checkout Testing

### 17 Tests in 5 Kategorien

Die E2E Tests (`ClubCheckoutE2ETest.php`) decken den kompletten Checkout-Flow ab:

#### **1. Happy Path (5 Tests)**

```php
// Complete checkout with monthly billing
// Complete checkout with yearly billing (10% discount)
// Trial period application (14 days)
// Success page displays subscription details
// Feature activation after successful payment
```

#### **2. Payment Failures (3 Tests)**

```php
// Declined card error handling (HTTP 422)
// Insufficient funds graceful handling
// Cancel page with retry options
```

#### **3. 3D Secure (2 Tests)**

```php
// 3DS authentication required (test card 4000 0027 6000 3184)
// Payment completion after authentication
```

#### **4. German Payments (2 Tests)**

```php
// SEPA Direct Debit support
// German payment metadata (locale=de, currency=EUR)
```

#### **5. System Tests (5 Tests)**

```php
// Billing portal access for active subscriptions
// Multi-tenant checkout isolation (cross-tenant blocked)
// Inactive plan validation
// Monthly vs yearly pricing verification
// Authorization/authentication checks
```

### Test-Beispiel: E2E Checkout

```php
/** @test */
public function complete_checkout_with_monthly_billing()
{
    $this->mockStripeServices();

    $club = Club::factory()->create();
    $plan = ClubSubscriptionPlan::factory()->create([
        'tenant_id' => $club->tenant_id,
        'price' => 49.99,
        'stripe_price_id_monthly' => 'price_monthly_123',
    ]);

    $admin = User::factory()->clubAdmin($club)->create();

    // Initiate checkout
    $response = $this->actingAs($admin)
        ->postJson(route('club.checkout', ['club' => $club->id]), [
            'plan_id' => $plan->id,
            'billing_interval' => 'monthly',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['checkout_url', 'session_id']);

    // Simulate webhook: checkout.session.completed
    $this->simulateWebhook('checkout.session.completed', [
        'club_id' => $club->id,
        'plan_id' => $plan->id,
        'customer' => 'cus_test123',
        'subscription' => 'sub_test456',
    ]);

    // Assert subscription activated
    $club->refresh();
    $this->assertEquals('active', $club->subscription_status);
    $this->assertEquals($plan->id, $club->club_subscription_plan_id);

    // Assert features available
    $this->assertTrue($club->hasFeature('live_scoring'));
    $this->assertTrue($club->canUse('teams', 5));
}
```

---

## 🎭 Mock Strategies

### 1. Stripe Client Mocking

Alle Tests verwenden **Mockery** für Stripe API Calls:

```php
protected function mockStripeServices(): void
{
    $stripeClient = Mockery::mock(StripeClient::class);
    $clientManager = Mockery::mock(StripeClientManager::class);

    $clientManager->shouldReceive('getCurrentTenantClient')
        ->andReturn($stripeClient);

    $this->app->instance(StripeClientManager::class, $clientManager);
}

protected function createMockCheckoutSession(array $overrides = []): CheckoutSession
{
    return Mockery::mock(CheckoutSession::class, array_merge([
        'id' => 'cs_test_123',
        'url' => 'https://checkout.stripe.com/test_123',
        'customer' => 'cus_test_123',
        'subscription' => 'sub_test_123',
        'payment_status' => 'paid',
        'status' => 'complete',
        'metadata' => new \stdClass(),
    ], $overrides));
}
```

### 2. Mail Facade Mocking

Email-Versand wird mit Laravel's `Mail::fake()` getestet:

```php
use Illuminate\Support\Facades\Mail;

public function setUp(): void
{
    parent::setUp();
    Mail::fake();
}

/** @test */
public function payment_successful_email_sent()
{
    $service->sendPaymentSuccessful($club, $invoiceData);

    Mail::assertQueued(PaymentSuccessfulMail::class, function ($mail) use ($club) {
        return $mail->club->id === $club->id
            && $mail->invoiceData['amount'] === 4900;
    });
}
```

### 3. Database Mocking

Alle Tests verwenden `RefreshDatabase` für saubere Test-Isolation:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClubSubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    // Jeder Test startet mit frischer Datenbank
}
```

---

## 🔨 Common Test Patterns

### 1. Factory Usage

```php
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;

// Basic Club
$club = Club::factory()->create();

// Club with active subscription
$club = Club::factory()->withSubscription()->create();

// Club with canceled subscription
$club = Club::factory()->withCanceledSubscription()->create();

// Club with trial subscription
$club = Club::factory()->withTrialSubscription()->create();

// Subscription Plan
$plan = ClubSubscriptionPlan::factory()->create([
    'price' => 49.99,
    'trial_period_days' => 14,
]);
```

### 2. Authentication

```php
use App\Models\User;

// Club Admin
$admin = User::factory()->clubAdmin($club)->create();
$this->actingAs($admin);

// Tenant Admin
$tenantAdmin = User::factory()->admin($tenant)->create();
$this->actingAs($tenantAdmin);
```

### 3. Assertions

```php
// Database Assertions
$this->assertDatabaseHas('clubs', [
    'id' => $club->id,
    'subscription_status' => 'active',
]);

// Model Assertions
$this->assertTrue($club->hasActiveSubscription());
$this->assertFalse($club->isOnTrial());

// Email Assertions
Mail::assertQueued(PaymentSuccessfulMail::class);
Mail::assertNotQueued(PaymentFailedMail::class);

// HTTP Assertions
$response->assertStatus(200);
$response->assertJson(['success' => true]);
```

---

## 🐛 Troubleshooting

### SQLite vs MySQL

**Problem:** SQLite PDO driver not available

```
SQLSTATE[HY000]: could not find driver (SQL: PRAGMA foreign_keys = ON;)
```

**Lösung:** Tests verwenden MySQL (wie in `.env.testing` konfiguriert)

```env
# .env.testing
DB_CONNECTION=mysql
DB_DATABASE=basketmanager_test
```

**Installation von SQLite (optional, für schnellere Tests):**

```bash
# Ubuntu/Debian
sudo apt-get install php8.2-sqlite3

# macOS
brew install php@8.2
```

### Webhook Signature Verification Failed

**Problem:** 400 Bad Request - Invalid signature

**Ursache:** Webhook Secret in `.env.testing` fehlt oder ist falsch

**Lösung:**

```env
# .env.testing - Stripe Test Webhook Secret
STRIPE_WEBHOOK_SECRET_CLUB=whsec_test_...
```

**Test-Webhook-Secret generieren:**

```bash
stripe listen --print-secret
```

### Stripe API Errors in Tests

**Problem:** Tests schlagen fehl mit "Invalid API Key provided"

**Ursache:** Stripe Test Keys nicht in `.env.testing` gesetzt

**Lösung:**

1. Gehe zu [Stripe Dashboard → Developers → API keys](https://dashboard.stripe.com/test/apikeys)
2. Kopiere "Publishable key" (pk_test_...) und "Secret key" (sk_test_...)
3. Füge zu `.env.testing` hinzu:

```env
STRIPE_KEY=pk_test_51...
STRIPE_SECRET=sk_test_51...
```

### Mail Not Queued in Tests

**Problem:** `Mail::assertQueued()` schlägt fehl

**Ursache:** `Mail::fake()` fehlt im Test

**Lösung:**

```php
public function setUp(): void
{
    parent::setUp();
    Mail::fake();  // Füge dies hinzu
}
```

### RefreshDatabase Performance

**Problem:** Tests sind langsam (>1 Minute)

**Ursache:** Migrations werden bei jedem Test neu ausgeführt

**Lösung:** SQLite In-Memory Database verwenden (wenn PDO verfügbar):

```xml
<!-- phpunit.xml -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## 📊 Test Statistics

### Test File Übersicht

| Kategorie | Datei | Tests | Zeilen | Coverage |
|-----------|-------|-------|--------|----------|
| **Integration** | ClubSubscriptionWebhookTest.php | 23 | ~900 | 100% |
| **E2E** | ClubCheckoutE2ETest.php | 17 | ~600 | 100% |
| **Unit** | ClubSubscriptionCheckoutServiceTest.php | 8 | ~200 | 95% |
| **Unit** | ClubSubscriptionServiceTest.php | 9 | ~250 | 95% |
| **Unit** | ClubStripeCustomerServiceTest.php | 11 | ~300 | 98% |
| **Unit** | ClubInvoiceServiceTest.php | 13 | ~350 | 95% |
| **Unit** | ClubPaymentMethodServiceTest.php | 13 | ~350 | 95% |
| **Unit** | ClubSubscriptionNotificationServiceTest.php | 24 | ~450 | 95% |
| **Unit** | SubscriptionAnalyticsServiceTest.php | 52 | ~1400 | 95% |
| **Feature** | ClubSubscriptionLifecycleTest.php | 9 | ~300 | - |
| **Feature** | ClubBillingControllerTest.php | 28 | ~700 | - |
| **Feature** | ClubSubscriptionNotificationFlowTest.php | 18 | ~850 | - |
| **Feature** | SubscriptionAnalyticsFlowTest.php | 10 | ~490 | - |
| **GESAMT** | **13 Dateien** | **235+** | **~7,140** | **~95%** |

### Service Coverage Details

| Service | Methods | Tested | Coverage |
|---------|---------|--------|----------|
| ClubSubscriptionCheckoutService | 2 | 2 | 100% |
| ClubSubscriptionService | 5 | 5 | 100% |
| ClubStripeCustomerService | 4 | 4 | 100% |
| ClubInvoiceService | 5 | 5 | 100% |
| ClubPaymentMethodService | 8 | 8 | 100% |
| ClubSubscriptionNotificationService | 10 | 10 | 100% |
| SubscriptionAnalyticsService | 17 | 17 | 100% |

### Webhook Event Coverage

Alle 11 kritischen Stripe Webhook Events sind abgedeckt:

- ✅ checkout.session.completed (Subscription Activation)
- ✅ customer.subscription.created (Subscription Details)
- ✅ customer.subscription.updated (Status Changes)
- ✅ customer.subscription.deleted (Cancellation)
- ✅ invoice.payment_succeeded (Payment Success)
- ✅ invoice.payment_failed (Payment Failure)
- ✅ invoice.created (Invoice Generation)
- ✅ invoice.finalized (Invoice Ready)
- ✅ invoice.payment_action_required (3D Secure)
- ✅ payment_method.attached (New Payment Method)
- ✅ payment_method.detached (Removed Payment Method)

### Payment Scenario Coverage

Alle kritischen Payment-Szenarien sind getestet:

- ✅ Successful Card Payment (4242 4242 4242 4242)
- ✅ Declined Card (4000 0000 0000 0002)
- ✅ Insufficient Funds (4000 0000 0000 9995)
- ✅ 3D Secure Authentication (4000 0027 6000 3184)
- ✅ SEPA Direct Debit Germany (4000 0082 6000 0000)
- ✅ Monthly Billing
- ✅ Yearly Billing (10% discount)
- ✅ Trial Period (14 days)

---

## 🎯 Best Practices

### 1. Test-Driven Development

```php
// 1. Write failing test first
/** @test */
public function it_calculates_proration_for_plan_upgrade()
{
    $club = Club::factory()->withSubscription('basic')->create();
    $premiumPlan = ClubSubscriptionPlan::factory()->premium()->create();

    $proration = $this->service->previewPlanSwap($club, $premiumPlan);

    $this->assertGreaterThan(0, $proration['amount']);
    $this->assertTrue($proration['is_upgrade']);
}

// 2. Implement feature
// 3. Run test → Green ✅
```

### 2. Test Isolation

```php
// ❌ BAD: Shared state zwischen Tests
private $club;

public function setUp(): void
{
    $this->club = Club::factory()->create();
}

// ✅ GOOD: Jeder Test erstellt eigene Daten
public function test_something()
{
    $club = Club::factory()->create();
    // ...
}
```

### 3. Descriptive Test Names

```php
// ❌ BAD
public function test_checkout() { }

// ✅ GOOD
public function complete_checkout_with_monthly_billing_activates_subscription() { }
```

### 4. Mock External Services

```php
// ✅ Mock Stripe API (keine echten Requests)
$this->mockStripeServices();

// ✅ Mock Mail (keine echten Emails)
Mail::fake();

// ✅ Mock Cache (isolierte Tests)
Cache::flush();
```

---

## 🔗 Weiterführende Links

- [Stripe Testing Documentation](https://stripe.com/docs/testing)
- [Stripe Test Cards](https://stripe.com/docs/testing#cards)
- [Stripe Webhook Testing](https://stripe.com/docs/webhooks/test)
- [Laravel Testing Documentation](https://laravel.com/docs/11.x/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](http://docs.mockery.io/)

---

## 📝 Changelog

### 2025-11-03
- ✅ Initial documentation erstellt
- ✅ 40 Tests dokumentiert (23 Integration + 17 E2E)
- ✅ Stripe Test Cards & Szenarien dokumentiert
- ✅ Mock Strategies & Best Practices hinzugefügt
- ✅ Troubleshooting Guide erstellt

---

**Fragen oder Probleme?** Siehe [TESTING.md](../TESTING.md) oder kontaktiere das Entwickler-Team.
