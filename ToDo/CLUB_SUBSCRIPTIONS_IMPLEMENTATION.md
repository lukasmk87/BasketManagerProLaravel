# 🏀 Club-basierte Subscriptions - Vollständige Implementierung

**Projekt:** BasketManager Pro - Multi-Tenant Club Subscription System
**Erstellt:** 2025-10-27
**Status:** 🚧 In Planung
**Geschätzte Zeit:** ~3,5 Stunden
**Priorität:** ⭐⭐⭐ Hoch

---

## 📋 Executive Summary

### Projektziel
Implementierung eines vollständigen Club-basierten Subscription-Systems innerhalb der Multi-Tenant-Architektur, das es jedem Tenant ermöglicht, mehrere Clubs mit individuellen Subscription-Plänen zu verwalten.

### Architektur-Übersicht
```
┌─────────────────────────────────────────────────┐
│              Tenant (Professional)              │
│  Features: [live_scoring, advanced_stats, ...]  │
│  Limits: max_teams=100, max_players=500         │
└────────┬────────────────────────────────────────┘
         │
         ├──► Club A (Standard Plan - 49€/Monat)
         │    Features: [live_scoring, training]
         │    Limits: max_teams=10, max_players=150
         │
         ├──► Club B (Premium Plan - 149€/Monat)
         │    Features: [all Tenant features]
         │    Limits: max_teams=50, max_players=500
         │
         └──► Club C (Free Plan - 0€)
              Features: [basic features]
              Limits: max_teams=2, max_players=30
```

### Feature-Hierarchie
1. **Tenant-Ebene** - Globale Limits und Features
2. **Club-Ebene** - Club-spezifische Pläne (innerhalb Tenant-Grenzen)
3. **Stripe-Integration** - Automatische Billing für Club-Subscriptions

### Technologie-Stack
- **Backend:** Laravel 12, PHP 8.2+, MySQL 8.0
- **Frontend:** Vue.js 3, Inertia.js 2.0, Tailwind CSS 3.4
- **Payment:** Stripe (Laravel Cashier 15.7+)
- **Testing:** PHPUnit, Pest (optional)

---

## 🎯 Haupt-Features (4 Bereiche)

### 1. Frontend/UI Dashboard für Tenant-Admins
- ✅ Übersicht aller Clubs und ihrer Pläne
- ✅ CRUD für ClubSubscriptionPlans
- ✅ Plan-Zuweisung zu Clubs
- ✅ Usage-Monitoring (Teams, Players, Storage)
- ✅ Read-only View für Club-Admins

### 2. Policies mit Tenant-Scoping
- ✅ Tenant-basierte Zugriffskontrolle
- ✅ Club-Admin kann nur eigene Clubs sehen
- ✅ Tenant-Admin kann alle Clubs seines Tenants verwalten
- ✅ Cross-Tenant-Access verhindern

### 3. Feature-Tests für Multi-Club-Szenarien
- ✅ Model & Relationship Tests
- ✅ Feature-Gate Tests (Hierarchie)
- ✅ Tenant-Scoping Tests
- ✅ API-Tests
- ✅ Policy-Tests
- ✅ Integration-Tests

### 4. Stripe Integration
- ✅ Checkout-Flow für Club-Subscriptions
- ✅ Webhook-Handler (Payment, Subscription Updates)
- ✅ Proration bei Plan-Wechsel
- ✅ Sync mit Stripe Products/Prices

---

## 📦 Phase 1: Foundation & Policies (30 Min)

### ✅ Status: Abgeschlossen (durch vorherige Migration)
- [x] `tenant_id` zu `clubs` Tabelle hinzugefügt
- [x] Club Model mit `BelongsToTenant` Trait erweitert
- [x] ClubService mit tenant_id Logik erweitert

### 🔨 Zu erledigen

#### 1.1 Database-Migrationen (Stripe-Felder)
- [ ] **Migration:** `add_stripe_fields_to_clubs_table.php`
  ```php
  // Felder:
  - stripe_subscription_id (string, nullable)
  - stripe_customer_id (string, nullable)
  - subscription_status (enum: active, past_due, canceled, trial)
  - subscription_started_at (timestamp, nullable)
  - subscription_trial_ends_at (timestamp, nullable)
  - subscription_ends_at (timestamp, nullable)
  ```

- [ ] **Migration:** `add_stripe_fields_to_club_subscription_plans_table.php`
  ```php
  // Felder:
  - stripe_product_id (string, nullable)
  - stripe_price_id_monthly (string, nullable)
  - stripe_price_id_yearly (string, nullable)
  - is_stripe_synced (boolean, default false)
  - last_stripe_sync_at (timestamp, nullable)
  ```

#### 1.2 Policies erweitern

- [ ] **ClubPolicy** - `app/Policies/ClubPolicy.php`
  - [ ] `viewAny()` - Tenant-Scoping hinzufügen
    ```php
    // Nur Clubs des eigenen Tenants anzeigen
    if ($user->hasRole('club_admin')) {
        return Club::where('tenant_id', $user->tenant_id)
            ->whereIn('id', $user->getAdministeredClubIds());
    }
    ```
  - [ ] `managePlanAssignments()` - NEU
  - [ ] `viewPlanUsage()` - NEU

- [ ] **ClubSubscriptionPlanPolicy** - `app/Policies/ClubSubscriptionPlanPolicy.php`
  - [ ] Alle Methoden mit Tenant-Scoping verstärken
  - [ ] `assignToClubs()` - NEU (Bulk-Zuweisung)
  - [ ] `viewUsageAnalytics()` - NEU
  - [ ] `syncWithStripe()` - NEU

- [ ] **TenantSubscriptionPolicy** - `app/Policies/TenantSubscriptionPolicy.php` (NEU)
  ```php
  // Methoden:
  - viewAny(): bool  // Tenant-Admin only
  - managePlans(): bool  // Create/Edit Plans
  - assignPlansToClubs(): bool  // Plan-Zuweisungen
  - viewAnalytics(): bool  // Usage & Revenue Analytics
  ```

#### 1.3 Middleware

- [ ] **EnsureTenantContext** - `app/Http/Middleware/EnsureTenantContext.php`
  ```php
  // Funktionen:
  - Validiert, dass app('tenant') gesetzt ist
  - Prüft, ob User zum aktuellen Tenant gehört
  - Blocked Cross-Tenant-Requests
  - Setzt $request->tenant für einfachen Zugriff
  ```

---

## 🖥️ Phase 2: Backend Services & Controllers (45 Min)

### 2.1 Controllers

#### A. TenantSubscriptionController
- [ ] **Datei:** `app/Http/Controllers/TenantAdmin/TenantSubscriptionController.php`

**Methoden:**
```php
// GET /tenant/subscriptions
public function index(): Inertia
// Zeigt alle Clubs des Tenants mit ihren Plänen

// GET /tenant/subscriptions/plans
public function plans(): Inertia
// Verwaltung der ClubSubscriptionPlans

// POST /tenant/subscriptions/plans
public function storePlan(Request $request): RedirectResponse
// Erstellt neuen ClubSubscriptionPlan
// Validiert gegen Tenant-Capabilities

// PUT /tenant/subscriptions/plans/{plan}
public function updatePlan(Request $request, ClubSubscriptionPlan $plan): RedirectResponse

// DELETE /tenant/subscriptions/plans/{plan}
public function destroyPlan(ClubSubscriptionPlan $plan): RedirectResponse
// Nur wenn keine Clubs zugewiesen

// POST /tenant/subscriptions/assign
public function assignPlan(Request $request): JsonResponse
// Weist Plan einem Club zu
// Validiert: Club & Plan gehören zum selben Tenant

// GET /tenant/subscriptions/analytics
public function analytics(): JsonResponse
// Revenue, Usage-Statistiken
```

**Validation Rules:**
```php
'storePlan' => [
    'name' => 'required|string|max:255',
    'price' => 'required|numeric|min:0',
    'billing_interval' => 'required|in:monthly,yearly',
    'features' => 'required|array',
    'features.*' => 'string|in:' . implode(',', config('club_plans.available_features')),
    'limits' => 'required|array',
    'limits.max_teams' => 'required|integer|min:1',
    'limits.max_players' => 'required|integer|min:1',
]
```

#### B. ClubSubscriptionController (Read-only für Club-Admins)
- [ ] **Datei:** `app/Http/Controllers/ClubAdmin/ClubSubscriptionController.php`

**Methoden:**
```php
// GET /club/subscription
public function show(Club $club): Inertia
// Zeigt aktuellen Plan & Usage

// GET /club/subscription/usage
public function usage(Club $club): JsonResponse
// Aktuelle Nutzung vs. Limits

// GET /club/subscription/available-plans
public function availablePlans(): JsonResponse
// Alle verfügbaren Pläne für diesen Tenant
```

#### C. ClubCheckoutController
- [ ] **Datei:** `app/Http/Controllers/Stripe/ClubCheckoutController.php`

**Methoden:**
```php
// POST /club/{club}/checkout
public function checkout(Request $request, Club $club): JsonResponse
// Erstellt Stripe Checkout Session
// Validiert: Club gehört zum aktuellen Tenant

// GET /club/checkout/success
public function success(Request $request): RedirectResponse
// Webhook wird Plan aktivieren, hier nur Bestätigung

// GET /club/checkout/cancel
public function cancel(): RedirectResponse
```

#### D. ClubSubscriptionWebhookController
- [ ] **Datei:** `app/Http/Controllers/Webhooks/ClubSubscriptionWebhookController.php`

**Webhook-Events:**
```php
// checkout.session.completed
public function handleCheckoutCompleted(array $payload): void
// - Plan zu Club zuweisen
// - stripe_subscription_id setzen
// - subscription_status = 'active'

// customer.subscription.updated
public function handleSubscriptionUpdated(array $payload): void
// - Status-Updates
// - Plan-Wechsel

// customer.subscription.deleted
public function handleSubscriptionDeleted(array $payload): void
// - Plan entfernen
// - subscription_status = 'canceled'

// invoice.payment_failed
public function handlePaymentFailed(array $payload): void
// - subscription_status = 'past_due'
// - Benachrichtigung senden

// invoice.payment_succeeded
public function handlePaymentSucceeded(array $payload): void
// - Bestätigung loggen
```

### 2.2 Services

#### ClubSubscriptionService
- [ ] **Datei:** `app/Services/Stripe/ClubSubscriptionService.php`

**Methoden:**
```php
// Checkout & Subscription Management
public function createCheckoutSession(Club $club, ClubSubscriptionPlan $plan, array $options = []): Session

public function assignPlanToClub(Club $club, ClubSubscriptionPlan $plan): void
// Setzt club_subscription_plan_id

public function removePlanFromClub(Club $club): void

// Stripe Sync
public function syncPlanWithStripe(ClubSubscriptionPlan $plan): array
// Erstellt Product & Price in Stripe
// Speichert stripe_product_id, stripe_price_id

public function syncAllPlansWithStripe(Tenant $tenant): array
// Sync aller Pläne eines Tenants

// Subscription Lifecycle
public function upgradeClubPlan(Club $club, ClubSubscriptionPlan $newPlan): void
// Plan-Upgrade mit Proration

public function downgradeClubPlan(Club $club, ClubSubscriptionPlan $newPlan): void
// Plan-Downgrade (am Periodenende)

public function cancelClubSubscription(Club $club, bool $immediately = false): void

public function resumeClubSubscription(Club $club): void

// Usage & Limits
public function getClubUsage(Club $club): array
// Current usage für alle metrics

public function checkLimitExceeded(Club $club, string $metric, int $additionalAmount = 0): bool

// Billing
public function calculateProration(Club $club, ClubSubscriptionPlan $newPlan): array
// Berechnet Proration-Betrag

public function getUpcomingInvoice(Club $club): ?Invoice
```

### 2.3 Routes

- [ ] **Datei:** `routes/tenant_subscriptions.php`
```php
Route::middleware(['auth', 'verified', 'ensure_tenant_context'])->group(function () {
    Route::prefix('tenant/subscriptions')->name('tenant.subscriptions.')->group(function () {
        // Overview
        Route::get('/', [TenantSubscriptionController::class, 'index'])
            ->name('index');

        // Plans Management
        Route::get('/plans', [TenantSubscriptionController::class, 'plans'])
            ->name('plans');
        Route::post('/plans', [TenantSubscriptionController::class, 'storePlan'])
            ->name('plans.store');
        Route::put('/plans/{plan}', [TenantSubscriptionController::class, 'updatePlan'])
            ->name('plans.update');
        Route::delete('/plans/{plan}', [TenantSubscriptionController::class, 'destroyPlan'])
            ->name('plans.destroy');

        // Plan Assignment
        Route::post('/assign', [TenantSubscriptionController::class, 'assignPlan'])
            ->name('assign');

        // Analytics
        Route::get('/analytics', [TenantSubscriptionController::class, 'analytics'])
            ->name('analytics');
    });
});
```

- [ ] **Datei:** `routes/club_subscriptions.php`
```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Club-Admin View (Read-only)
    Route::get('/club/subscription', [ClubSubscriptionController::class, 'show'])
        ->name('club.subscription.show');
    Route::get('/club/subscription/usage', [ClubSubscriptionController::class, 'usage'])
        ->name('club.subscription.usage');
    Route::get('/club/subscription/available-plans', [ClubSubscriptionController::class, 'availablePlans'])
        ->name('club.subscription.available-plans');

    // Checkout
    Route::post('/club/{club}/checkout', [ClubCheckoutController::class, 'checkout'])
        ->name('club.checkout');
    Route::get('/club/checkout/success', [ClubCheckoutController::class, 'success'])
        ->name('club.checkout.success');
    Route::get('/club/checkout/cancel', [ClubCheckoutController::class, 'cancel'])
        ->name('club.checkout.cancel');
});

// Webhooks (ohne Auth)
Route::post('/webhooks/stripe/club-subscriptions', [ClubSubscriptionWebhookController::class, 'handleWebhook'])
    ->name('webhooks.stripe.club-subscriptions');
```

### 2.4 Artisan Commands

- [ ] **SyncClubPlansWithStripe**
```bash
php artisan club-plans:sync-stripe {tenant?}
# Synct alle ClubSubscriptionPlans mit Stripe
# Optional: nur für spezifischen Tenant
```

---

## 🎨 Phase 3: Frontend Components & Pages (60 Min)

### 3.1 Vue-Komponenten

#### A. SubscriptionPlans/ClubPlanCard.vue
- [ ] **Datei:** `resources/js/Components/SubscriptionPlans/ClubPlanCard.vue`

**Props:**
```javascript
{
    plan: Object,  // ClubSubscriptionPlan
    selected: Boolean,
    showActions: Boolean,
    compact: Boolean
}
```

**Features:**
- Plan-Name, Preis, Billing-Interval
- Features-Liste mit Icons
- Limits-Übersicht (max_teams, max_players, etc.)
- Badge für "Most Popular", "Best Value"
- Edit/Delete Actions (wenn showActions=true)

#### B. SubscriptionPlans/AssignPlanModal.vue
- [ ] **Datei:** `resources/js/Components/SubscriptionPlans/AssignPlanModal.vue`

**Props:**
```javascript
{
    show: Boolean,
    club: Object,
    plans: Array,
    currentPlanId: String|null
}
```

**Features:**
- Dropdown/Radio-Select für Pläne
- Plan-Vergleich (aktuell vs. neu)
- Proration-Vorschau (wenn Stripe)
- Bestätigungs-Modal
- Success/Error Toast

#### C. SubscriptionPlans/PlanEditorForm.vue
- [ ] **Datei:** `resources/js/Components/SubscriptionPlans/PlanEditorForm.vue`

**Props:**
```javascript
{
    plan: Object|null,  // null = Create mode
    tenantFeatures: Array,  // Verfügbare Features aus Tenant
    tenantLimits: Object,  // Max-Limits aus Tenant
}
```

**Form-Felder:**
- Name, Description
- Price, Currency, Billing Interval
- Features (Multi-Select Checkboxen)
- Limits (Number Inputs mit Validierung gegen Tenant-Limits)
- Color, Icon (optional)
- is_active, is_default

**Validierung:**
- Features dürfen nicht mehr sein als Tenant hat
- Limits dürfen nicht höher sein als Tenant-Limits

#### D. SubscriptionPlans/ClubUsageWidget.vue
- [ ] **Datei:** `resources/js/Components/SubscriptionPlans/ClubUsageWidget.vue`

**Props:**
```javascript
{
    club: Object,
    usage: Object,  // { max_teams, max_players, max_storage_gb, ... }
}
```

**Features:**
- Progress Bars für jedes Limit
- Color-Coding (green < 70%, yellow 70-90%, red > 90%)
- Absolute Zahlen + Prozentsatz
- Warnung bei Limit-Überschreitung

#### E. SubscriptionPlans/FeatureLimitTable.vue
- [ ] **Datei:** `resources/js/Components/SubscriptionPlans/FeatureLimitTable.vue`

**Props:**
```javascript
{
    plans: Array,  // Alle Pläne zum Vergleich
    highlightPlanId: String|null
}
```

**Features:**
- Vergleichstabelle (wie auf SaaS-Websites)
- Feature-Rows mit Checkmarks/X
- Limit-Rows mit Zahlen
- Sticky Header
- "Choose Plan" Button pro Spalte

#### F. ClubAdmin/SubscriptionOverview.vue
- [ ] **Datei:** `resources/js/Components/ClubAdmin/SubscriptionOverview.vue`

**Props:**
```javascript
{
    club: Object,
    plan: Object|null,
    usage: Object
}
```

**Features:**
- Kompaktes Widget für ClubAdmin-Dashboard
- Aktueller Plan mit Preis
- Usage-Bars (kompakt)
- Link zu "Upgrade Plan" (falls verfügbar)
- Read-only (keine Edit-Funktionen)

#### G. Shared/PlanComparisonTable.vue
- [ ] **Datei:** `resources/js/Components/Shared/PlanComparisonTable.vue`

**Props:**
```javascript
{
    plans: Array,
    currentPlanId: String|null,
    onSelectPlan: Function
}
```

**Features:**
- Horizontaler Scroll für viele Pläne
- Highlight des aktuellen Plans
- Responsive (stapelt auf Mobile)
- Feature-Tooltips

### 3.2 Inertia Pages

#### A. TenantAdmin/Subscriptions/Index.vue
- [ ] **Datei:** `resources/js/Pages/TenantAdmin/Subscriptions/Index.vue`

**Layout:** TenantAdminLayout

**Sections:**
1. **Header**
   - Titel: "Club Subscriptions"
   - Button: "Manage Plans"
   - Stats: Total Revenue, Active Subscriptions

2. **Clubs-Tabelle**
   - Spalten: Club Name, Current Plan, Status, Teams, Players, Actions
   - Status-Badges (Active, Trial, Past Due, Canceled)
   - Actions: Assign Plan, View Usage, Manage

3. **Filter & Search**
   - Filter: Plan, Status
   - Search: Club Name

#### B. TenantAdmin/Subscriptions/Plans.vue
- [ ] **Datei:** `resources/js/Pages/TenantAdmin/Subscriptions/Plans.vue`

**Layout:** TenantAdminLayout

**Sections:**
1. **Header**
   - Titel: "Subscription Plans"
   - Button: "Create New Plan"
   - Button: "Sync with Stripe" (wenn Stripe enabled)

2. **Plans Grid**
   - Grid Layout (3 Spalten)
   - ClubPlanCard für jeden Plan
   - Sortierung: sort_order

3. **Plan-Statistiken**
   - Clubs pro Plan
   - Revenue pro Plan

#### C. TenantAdmin/Subscriptions/Create.vue
- [ ] **Datei:** `resources/js/Pages/TenantAdmin/Subscriptions/Create.vue`

**Layout:** TenantAdminLayout

**Sections:**
1. **Form (PlanEditorForm)**
   - Alle Felder für neuen Plan

2. **Preview**
   - Live-Vorschau als ClubPlanCard

3. **Validation**
   - Tenant-Capabilities checken
   - Fehler-Anzeige

### 3.3 Dashboard-Integration

- [ ] **ClubAdmin Dashboard erweitern**
  - [ ] `resources/js/Pages/ClubAdmin/Dashboard.vue`
    - SubscriptionOverview-Widget hinzufügen
    - Position: Nach Statistics Cards, vor Quick Actions

---

## 🧪 Phase 4: Testing (45 Min)

### 4.1 Model & Relationship Tests

- [ ] **Datei:** `tests/Feature/ClubSubscriptionPlanTest.php`

**Test-Cases:**
```php
test('club subscription plan belongs to tenant')
test('club subscription plan can have multiple clubs')
test('plan validates features against tenant capabilities')
test('plan validates limits against tenant limits')
test('plan cannot exceed tenant features')
test('plan can be soft deleted')
test('deleting plan with assigned clubs fails')
test('club without plan inherits tenant features')
test('club with plan uses plan features')
test('plan limits respect tenant hierarchy')
test('default plan can be set per tenant')
```

### 4.2 Feature-Gate Tests

- [ ] **Datei:** `tests/Feature/ClubFeatureGateTest.php`

**Test-Cases:**
```php
test('club without plan inherits all tenant features')
test('club with plan restricted to plan features')
test('tenant feature removed also removes from club')
test('feature upgrade flow works correctly')
test('limit enforcement blocks exceeding actions')
test('club can check if feature is available')
test('club can check current usage vs limit')
test('usage tracking updates correctly')
```

**Scenario:**
```php
// Arrange
$tenant = Tenant::factory()->create([
    'subscription_tier' => 'professional',
    // has: live_scoring, advanced_stats
]);

$standardPlan = ClubSubscriptionPlan::factory()->create([
    'tenant_id' => $tenant->id,
    'features' => ['live_scoring'],  // NOT advanced_stats
    'limits' => ['max_teams' => 10],
]);

$club = Club::factory()->create([
    'tenant_id' => $tenant->id,
    'club_subscription_plan_id' => $standardPlan->id,
]);

// Assert
expect($club->hasFeature('live_scoring'))->toBeTrue();
expect($club->hasFeature('advanced_stats'))->toBeFalse();  // restricted by plan
expect($club->getLimit('max_teams'))->toBe(10);
```

### 4.3 Tenant-Scoping Tests

- [ ] **Datei:** `tests/Feature/ClubTenantScopingTest.php`

**Test-Cases:**
```php
test('tenant A cannot see clubs from tenant B')
test('tenant A cannot assign plan to club from tenant B')
test('club admin can only see clubs from own tenant')
test('API requests respect tenant boundaries')
test('cross-tenant access returns 403')
test('tenant context middleware blocks invalid requests')
```

**Scenario:**
```php
$tenantA = Tenant::factory()->create();
$tenantB = Tenant::factory()->create();

$clubA = Club::factory()->create(['tenant_id' => $tenantA->id]);
$clubB = Club::factory()->create(['tenant_id' => $tenantB->id]);

$planA = ClubSubscriptionPlan::factory()->create(['tenant_id' => $tenantA->id]);

// Should fail: Assigning Tenant A plan to Tenant B club
expect(fn() => $clubB->assignPlan($planA))
    ->toThrow(\Exception::class, 'Plan does not belong to club\'s tenant');
```

### 4.4 API Tests

- [ ] **Datei:** `tests/Feature/ClubSubscriptionApiTest.php`

**Test-Cases:**
```php
test('can create club subscription plan via API')
test('can list all plans for tenant')
test('can assign plan to club via API')
test('cannot assign plan from different tenant')
test('can get club usage via API')
test('can update plan via API')
test('cannot update plan from different tenant')
test('can delete plan if no clubs assigned')
test('cannot delete plan with assigned clubs')
test('API returns proper error messages')
```

### 4.5 Policy Tests

- [ ] **Datei:** `tests/Feature/ClubPolicyTest.php`

**Test-Cases:**
```php
test('super admin can view all clubs')
test('tenant admin can view clubs from own tenant only')
test('club admin can view own administered clubs only')
test('club admin cannot assign plans')
test('tenant admin can assign plans')
test('club admin can view own club subscription')
test('club admin cannot edit subscription')
```

### 4.6 Integration Tests

- [ ] **Datei:** `tests/Feature/ClubPlanAssignmentIntegrationTest.php`

**Full E2E Scenario:**
```php
test('full subscription lifecycle') {
    // 1. Create Tenant with Professional tier
    $tenant = Tenant::factory()->professional()->create();

    // 2. Create Club within Tenant
    $club = Club::factory()->create(['tenant_id' => $tenant->id]);

    // 3. Create Standard Plan for Tenant
    $standardPlan = ClubSubscriptionPlan::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Standard',
        'price' => 49,
        'features' => ['live_scoring', 'training_management'],
        'limits' => ['max_teams' => 10, 'max_players' => 150],
    ]);

    // 4. Assign Plan to Club
    $club->assignPlan($standardPlan);

    // 5. Verify Club has Plan Features
    expect($club->hasFeature('live_scoring'))->toBeTrue();
    expect($club->getLimit('max_teams'))->toBe(10);

    // 6. Test Limit Enforcement
    Team::factory()->count(10)->create(['club_id' => $club->id]);
    expect($club->canUse('max_teams'))->toBeFalse();  // Limit reached

    // 7. Upgrade to Premium Plan
    $premiumPlan = ClubSubscriptionPlan::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Premium',
        'limits' => ['max_teams' => 50],
    ]);
    $club->assignPlan($premiumPlan);

    // 8. Verify new limits
    expect($club->canUse('max_teams'))->toBeTrue();  // Now has room
}
```

---

## 💳 Phase 5: Stripe Integration (30 Min)

### 5.1 Stripe Service Implementation

- [ ] **ClubSubscriptionService** vollständig implementieren (siehe Phase 2.2)

### 5.2 Stripe Product/Price Sync

- [ ] **Artisan Command:** `club-plans:sync-stripe`
```php
// Für jeden ClubSubscriptionPlan:
// 1. Erstelle Stripe Product (wenn nicht existiert)
// 2. Erstelle Stripe Price (monthly/yearly)
// 3. Speichere stripe_product_id, stripe_price_id
// 4. Setze is_stripe_synced = true
```

### 5.3 Checkout-Flow

**User-Flow:**
1. Tenant-Admin wählt Club aus
2. Klickt "Assign Plan" oder "Upgrade Plan"
3. Modal zeigt verfügbare Pläne
4. Klick auf "Subscribe" → Redirect zu Stripe Checkout
5. Nach Payment → Webhook aktiviert Plan
6. Redirect zu Success-Page mit Bestätigung

**Technischer Flow:**
```
[Frontend] AssignPlanModal
    ↓ POST /club/{club}/checkout
[Backend] ClubCheckoutController::checkout()
    ↓ ClubSubscriptionService::createCheckoutSession()
    ↓ Stripe API: Create Checkout Session
    ↓ return { checkout_url }
[Frontend] window.location.href = checkout_url
    ↓ User completes payment on Stripe
[Stripe] Webhook: checkout.session.completed
    ↓ POST /webhooks/stripe/club-subscriptions
[Backend] ClubSubscriptionWebhookController::handleCheckoutCompleted()
    ↓ Club::assignPlan()
    ↓ Update stripe_subscription_id, subscription_status
[Stripe] Redirect to success_url
[Frontend] ClubCheckoutController::success()
    ↓ Show success message
    ↓ Redirect to /tenant/subscriptions
```

### 5.4 Webhook-Testing

- [ ] **Lokales Testing mit Stripe CLI**
```bash
# Stripe CLI installieren
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Webhook forwarding
stripe listen --forward-to localhost:8000/webhooks/stripe/club-subscriptions

# Test events senden
stripe trigger checkout.session.completed
stripe trigger customer.subscription.updated
stripe trigger invoice.payment_failed
```

- [ ] **Test-Cases für Webhooks**
```php
test('webhook checkout completed assigns plan to club')
test('webhook subscription updated updates club status')
test('webhook payment failed marks subscription as past_due')
test('webhook subscription deleted removes plan from club')
test('webhook validates signature')
test('webhook ignores invalid tenant_id')
```

### 5.5 Proration-Handling

**Upgrade-Scenario:**
```php
// Club hat Standard Plan (49€/month)
// Upgrade zu Premium Plan (149€/month)
// Mitte des Monats (15 Tage verbleibend)

$prorationAmount = ClubSubscriptionService::calculateProration($club, $premiumPlan);
// Result:
// {
//   'current_plan_remaining': 24.50,  // 49/2
//   'new_plan_prorated': 74.50,       // 149/2
//   'amount_due_now': 50.00           // Differenz
// }
```

---

## 🚀 Phase 6: Deployment & Rollout (15 Min)

### 6.1 Pre-Deployment Checklist

- [ ] **Datenbank-Backups**
  ```bash
  php artisan backup:run --only-db
  ```

- [ ] **Migrations testen**
  ```bash
  php artisan migrate --pretend
  php artisan migrate
  ```

- [ ] **Seeds laufen lassen**
  ```bash
  php artisan db:seed --class=ClubSubscriptionPlanSeeder
  ```

- [ ] **Stripe Products synchronisieren**
  ```bash
  php artisan club-plans:sync-stripe
  ```

- [ ] **Permissions überprüfen**
  ```bash
  php artisan permission:cache-reset
  ```

### 6.2 Deployment-Reihenfolge

1. ✅ **Git Commit & Push**
2. ✅ **Backend Deploy**
   - Pull latest code
   - `composer install --no-dev`
   - `php artisan migrate --force`
   - `php artisan optimize`
3. ✅ **Frontend Build**
   - `npm run build`
   - Deploy assets
4. ✅ **Cache Clear**
   - `php artisan config:clear`
   - `php artisan route:clear`
   - `php artisan view:clear`
5. ✅ **Queue Restart**
   - `php artisan queue:restart`
6. ✅ **Stripe Webhook Setup**
   - Dashboard → Webhooks → Add endpoint
   - URL: `https://basketmanager-pro.de/webhooks/stripe/club-subscriptions`
   - Events: `checkout.session.completed`, `customer.subscription.*`, `invoice.*`

### 6.3 Post-Deployment Testing

- [ ] **Smoke Tests**
  - [ ] Login als Tenant-Admin funktioniert
  - [ ] `/tenant/subscriptions` lädt korrekt
  - [ ] Plans werden angezeigt
  - [ ] Plan-Zuweisung funktioniert
  - [ ] ClubAdmin kann eigenen Plan sehen

- [ ] **Stripe-Testing**
  - [ ] Test-Checkout durchführen
  - [ ] Webhook wird empfangen
  - [ ] Plan wird aktiviert
  - [ ] Dashboard zeigt korrekten Status

### 6.4 Rollback-Plan

**Falls Probleme auftreten:**
```bash
# Migrations rückgängig machen
php artisan migrate:rollback --step=2

# Code-Rollback via Git
git revert <commit-hash>
git push

# Cache clearen
php artisan optimize:clear
```

### 6.5 Monitoring

- [ ] **Laravel Logs überwachen**
  ```bash
  tail -f storage/logs/laravel.log | grep -i "subscription\|stripe"
  ```

- [ ] **Stripe Dashboard prüfen**
  - Payments
  - Subscriptions
  - Webhook-Logs

- [ ] **Database-Queries überwachen**
  - Langsame Queries für Subscription-Checks
  - N+1 Problems

---

## 📊 Metriken & KPIs

### Technische Metriken
- [ ] **Code Coverage:** > 80% für neue Features
- [ ] **API Response Time:** < 200ms für Subscription-Endpoints
- [ ] **Webhook Success Rate:** > 99%
- [ ] **Database Query Count:** < 10 pro Subscription-Check

### Business-Metriken
- [ ] **Club Conversion Rate:** % Clubs mit bezahltem Plan
- [ ] **Average Revenue per Club (ARPC)**
- [ ] **Plan Distribution:** Free vs. Standard vs. Premium
- [ ] **Churn Rate:** Clubs downgrading/canceling

---

## 📝 Changelog

### Version 1.0.0 (2025-10-27)
- [x] Initiale Struktur erstellt
- [x] `tenant_id` zu Clubs hinzugefügt
- [x] ClubSubscriptionPlan Model erstellt
- [x] Basis-Policies vorhanden

### Version 1.1.0 (Geplant)
- [ ] Frontend-Dashboard
- [ ] Erweiterte Policies
- [ ] Feature-Tests
- [ ] Stripe-Integration

---

## 🐛 Bekannte Probleme & TODOs

### Offene Issues
1. [ ] **Proration-Berechnung:** Muss mit Stripe-API synchronisiert werden
2. [ ] **Trial-Perioden:** Noch nicht implementiert
3. [ ] **Bulk-Actions:** Plan mehreren Clubs gleichzeitig zuweisen
4. [ ] **Email-Benachrichtigungen:** Bei Plan-Änderungen
5. [ ] **Audit-Log:** Wer hat wann welchen Plan zugewiesen?

### Performance-Optimierungen
- [ ] **Caching:** Club-Pläne für 1 Stunde cachen
- [ ] **Eager Loading:** `with('subscriptionPlan', 'tenant')` in Queries
- [ ] **Database Indexing:** Index auf `stripe_subscription_id`

### Security-Considerations
- [ ] **Webhook-Signatur:** Stripe Signature Validation
- [ ] **Rate-Limiting:** Checkout-Requests limitieren
- [ ] **CSRF-Protection:** Alle POST-Requests schützen
- [ ] **Input-Validation:** Alle User-Inputs sanitizen

---

## 📚 Referenzen & Ressourcen

### Dokumentation
- [Laravel Cashier Docs](https://laravel.com/docs/11.x/billing)
- [Stripe API Reference](https://stripe.com/docs/api)
- [Inertia.js Docs](https://inertiajs.com/)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)

### Code-Beispiele
- `app/Services/Stripe/StripeSubscriptionService.php` - Basis-Implementation
- `app/Policies/ClubSubscriptionPlanPolicy.php` - Policy-Beispiele
- `resources/js/Pages/ClubAdmin/Dashboard.vue` - Dashboard-Struktur

### Testing
- `tests/Feature/EmergencyAccessTest.php` - Umfangreicher Feature-Test
- `tests/Feature/GDPRComplianceTest.php` - Policy & Permission Tests

---

## ✅ Abschließende Checkliste

### Code-Qualität
- [ ] Alle ESLint-Warnungen behoben
- [ ] PHP-Code mit Laravel Pint formatiert
- [ ] Keine N+1 Query-Probleme
- [ ] Alle TODOs entfernt oder als Issues erfasst

### Dokumentation
- [ ] README aktualisiert
- [ ] API-Dokumentation generiert
- [ ] Inline-Kommentare für komplexe Logik

### Tests
- [ ] Alle Tests grün
- [ ] Code Coverage > 80%
- [ ] Edge Cases getestet

### Deployment
- [ ] Production-Migrations getestet
- [ ] Rollback-Plan dokumentiert
- [ ] Monitoring eingerichtet

---

## 🎉 Projektabschluss

**Definition of Done:**
- ✅ Alle Features implementiert und getestet
- ✅ Code-Review abgeschlossen
- ✅ Production-Deployment erfolgreich
- ✅ Dokumentation vollständig
- ✅ Stakeholder-Abnahme erhalten

**Next Steps:**
1. User-Feedback sammeln
2. Performance-Metriken auswerten
3. Verbesserungen priorisieren
4. Nächste Features planen

---

**Erstellt von:** Claude Code
**Letzte Aktualisierung:** 2025-10-27
**Status:** 📋 Bereit für Implementierung
