# TODO: Admin-Panel & PlayerService Limit-Enforcement

## Status: Backend abgeschlossen ✓ | Frontend ausstehend

**Letztes Update:** 2025-10-13

Dieses Dokument beschreibt die vollständige Implementation des Admin-Panels für Subscription-Verwaltung und die Erweiterung des PlayerService mit Limit-Enforcement.

## Implementierungs-Fortschritt

✅ **Phase 1: Backend-Grundlagen (ABGESCHLOSSEN)**
- ✅ PlayerService mit Limit-Enforcement
- ✅ Admin Controller (4/4 fertig)
- ✅ API Resources (3/3 fertig)
- ✅ Form Request Validation (5/5 fertig)
- ✅ Permissions & Roles erweitert
- ✅ Admin Routes registriert
- ✅ AdminMiddleware konfiguriert

⏳ **Phase 2: Frontend (AUSSTEHEND)**
- ⏳ Vue/Inertia Components
- ⏳ AdminLayout
- ⏳ Dashboard Pages

⏳ **Phase 3: Testing (AUSSTEHEND)**
- ⏳ Feature Tests
- ⏳ Unit Tests

---

## 1. PlayerService Erweiterung ✓ PRIORITÄT: HOCH

### 1.1 Service-Anpassungen

**Datei:** `app/Services/PlayerService.php`

**Änderungen in `createPlayer()` Method:**

```php
public function createPlayer(array $data): Player
{
    // HINZUFÜGEN: Limit-Check VOR der Transaction
    $limitEnforcement = App::make(LimitEnforcementService::class);
    $limitEnforcement->enforcePlayerLimit();

    DB::beginTransaction();

    try {
        // Bestehender Code...
        $player = Player::create($data);

        DB::commit();

        // HINZUFÜGEN: Resource-Tracking NACH erfolgreichem Commit
        $limitEnforcement->trackResourceCreation('player');

        return $player;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

**Änderungen in `deletePlayer()` Method:**

```php
public function deletePlayer(Player $player): bool
{
    DB::beginTransaction();

    try {
        $player->delete();

        DB::commit();

        // HINZUFÜGEN: Resource-Tracking für Deletion
        $limitEnforcement = App::make(LimitEnforcementService::class);
        $limitEnforcement->trackResourceDeletion('player');

        return true;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Testing:**
- [ ] Test erstellen: `tests/Feature/PlayerLimitEnforcementTest.php`
- [ ] Player-Erstellung bei Limit-Überschreitung schlägt fehl
- [ ] Player-Counter wird korrekt inkrementiert

---

## 2. Admin-Controller erstellen ✓ PRIORITÄT: HOCH

### 2.1 AdminDashboardController

**Datei:** `app/Http/Controllers/Admin/AdminDashboardController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        $tenants = Tenant::with(['subscriptionPlan', 'activeCustomization'])
            ->withCount(['users', 'teams', 'players', 'games'])
            ->paginate(20);

        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'total_revenue' => Tenant::sum('total_revenue'),
            'mrr' => Tenant::sum('monthly_recurring_revenue'),
        ];

        $planStats = SubscriptionPlan::withCount('tenants')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'tenants' => $tenants,
            'stats' => $stats,
            'planStats' => $planStats,
        ]);
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Controller erstellen
- [x] `index()` Method implementieren
- [x] Tenant-Statistiken berechnen
- [x] Revenue-Metriken aggregieren

---

### 2.2 SubscriptionPlanController

**Datei:** `app/Http/Controllers/Admin/SubscriptionPlanController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Http\Requests\Admin\CreatePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SubscriptionPlanController extends Controller
{
    public function index(): Response
    {
        $plans = SubscriptionPlan::withCount('tenants')
            ->ordered()
            ->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    public function show(SubscriptionPlan $plan): Response
    {
        $plan->loadCount('tenants');

        return Inertia::render('Admin/Plans/Show', [
            'plan' => new SubscriptionPlanResource($plan),
            'tenants' => $plan->tenants()->paginate(20),
        ]);
    }

    public function store(CreatePlanRequest $request): RedirectResponse
    {
        $plan = SubscriptionPlan::create($request->validated());

        return redirect()
            ->route('admin.plans.show', $plan)
            ->with('success', 'Subscription Plan erstellt!');
    }

    public function update(UpdatePlanRequest $request, SubscriptionPlan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return redirect()
            ->route('admin.plans.show', $plan)
            ->with('success', 'Subscription Plan aktualisiert!');
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        // Check if plan has active tenants
        if ($plan->tenants()->where('is_active', true)->exists()) {
            return back()->with('error', 'Plan kann nicht gelöscht werden - es gibt aktive Tenants.');
        }

        $plan->delete();

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Subscription Plan gelöscht!');
    }

    public function clone(SubscriptionPlan $plan): RedirectResponse
    {
        $newPlan = $plan->clonePlan(
            $plan->name . ' (Kopie)',
            $plan->slug . '-copy-' . time()
        );

        return redirect()
            ->route('admin.plans.show', $newPlan)
            ->with('success', 'Plan wurde geklont!');
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Controller erstellen
- [x] CRUD-Methoden implementieren
- [x] `clone()` Method für Plan-Duplizierung
- [x] Validierung dass Plans mit aktiven Tenants nicht gelöscht werden

---

### 2.3 TenantSubscriptionController

**Datei:** `app/Http/Controllers/Admin/TenantSubscriptionController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\TenantPlanCustomization;
use App\Http\Requests\Admin\UpdateTenantSubscriptionRequest;
use App\Http\Requests\Admin\UpdateTenantLimitsRequest;
use App\Http\Requests\Admin\CreateCustomizationRequest;
use App\Http\Resources\TenantSubscriptionResource;
use App\Services\LimitEnforcementService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TenantSubscriptionController extends Controller
{
    public function __construct(
        private LimitEnforcementService $limitEnforcement
    ) {}

    public function index(): Response
    {
        $tenants = Tenant::with(['subscriptionPlan', 'activeCustomization'])
            ->withCount(['users', 'teams', 'players'])
            ->paginate(50);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => TenantSubscriptionResource::collection($tenants),
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load(['subscriptionPlan', 'activeCustomization', 'planCustomizations']);

        $this->limitEnforcement->setTenant($tenant);
        $limits = $this->limitEnforcement->getAllLimits();

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => new TenantSubscriptionResource($tenant),
            'limits' => $limits,
            'availablePlans' => SubscriptionPlan::active()->ordered()->get(),
        ]);
    }

    public function updateSubscription(UpdateTenantSubscriptionRequest $request, Tenant $tenant): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        $tenant->update([
            'subscription_plan_id' => $plan->id,
            'subscription_tier' => $plan->slug,
        ]);

        // Update limits from plan
        $tenant->update([
            'max_users' => $plan->getLimit('users'),
            'max_teams' => $plan->getLimit('teams'),
            'max_storage_gb' => $plan->getLimit('storage_gb'),
            'max_api_calls_per_hour' => $plan->getLimit('api_calls_per_hour'),
        ]);

        return back()->with('success', 'Subscription aktualisiert!');
    }

    public function updateLimits(UpdateTenantLimitsRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update([
            'max_users' => $request->max_users,
            'max_teams' => $request->max_teams,
            'max_storage_gb' => $request->max_storage_gb,
            'max_api_calls_per_hour' => $request->max_api_calls_per_hour,
        ]);

        return back()->with('success', 'Limits aktualisiert!');
    }

    public function createCustomization(CreateCustomizationRequest $request, Tenant $tenant): RedirectResponse
    {
        TenantPlanCustomization::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $tenant->subscription_plan_id,
            'custom_features' => $request->custom_features,
            'disabled_features' => $request->disabled_features,
            'custom_limits' => $request->custom_limits,
            'notes' => $request->notes,
            'effective_from' => $request->effective_from,
            'effective_until' => $request->effective_until,
        ]);

        return back()->with('success', 'Customization erstellt!');
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Controller erstellen
- [x] Tenant-Liste mit Subscription-Details
- [x] Subscription-Änderung für Tenant
- [x] Custom Limits für einzelne Tenants
- [x] Customization-Erstellung (Custom Features/Limits)

---

### 2.4 UsageLimitsController

**Datei:** `app/Http/Controllers/Admin/UsageLimitsController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\LimitEnforcementService;
use App\Http\Resources\UsageLimitResource;
use Illuminate\Http\JsonResponse;

class UsageLimitsController extends Controller
{
    public function __construct(
        private LimitEnforcementService $limitEnforcement
    ) {}

    public function getLimits(Tenant $tenant): JsonResponse
    {
        $this->limitEnforcement->setTenant($tenant);
        $limits = $this->limitEnforcement->getAllLimits();

        return response()->json([
            'limits' => UsageLimitResource::collection($limits),
        ]);
    }

    public function getStats(): JsonResponse
    {
        $approachingLimits = [];

        Tenant::with('subscriptionPlan')->chunk(100, function ($tenants) use (&$approachingLimits) {
            foreach ($tenants as $tenant) {
                $this->limitEnforcement->setTenant($tenant);
                $limits = $this->limitEnforcement->getAllLimits();

                foreach ($limits as $metric => $data) {
                    if ($this->limitEnforcement->isApproachingLimit($metric)) {
                        $approachingLimits[] = [
                            'tenant' => $tenant->name,
                            'tenant_id' => $tenant->id,
                            'metric' => $metric,
                            'percentage' => $data['percentage'],
                            'current' => $data['current'],
                            'limit' => $data['limit'],
                        ];
                    }
                }
            }
        });

        return response()->json([
            'approaching_limits' => $approachingLimits,
        ]);
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Controller erstellen
- [x] `getLimits()` für einzelnen Tenant
- [x] `getStats()` für globale Usage-Statistiken
- [x] Warnung bei Tenants die Limits erreichen (>80%)

---

## 3. Admin-Routes definieren ✓ PRIORITÄT: HOCH

**Datei:** `routes/admin.php` (NEU ERSTELLEN)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TenantSubscriptionController;
use App\Http\Controllers\Admin\UsageLimitsController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Admin routes for subscription and tenant management.
| Only accessible by Super Admins or users with 'manage-subscriptions' permission.
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Subscription Plans Management
    Route::resource('plans', SubscriptionPlanController::class);
    Route::post('plans/{plan}/clone', [SubscriptionPlanController::class, 'clone'])->name('plans.clone');

    // Tenant Subscription Management
    Route::get('tenants', [TenantSubscriptionController::class, 'index'])->name('tenants.index');
    Route::get('tenants/{tenant}', [TenantSubscriptionController::class, 'show'])->name('tenants.show');
    Route::put('tenants/{tenant}/subscription', [TenantSubscriptionController::class, 'updateSubscription'])->name('tenants.subscription.update');
    Route::put('tenants/{tenant}/limits', [TenantSubscriptionController::class, 'updateLimits'])->name('tenants.limits.update');
    Route::post('tenants/{tenant}/customization', [TenantSubscriptionController::class, 'createCustomization'])->name('tenants.customization.create');

    // Usage & Limits
    Route::get('usage/limits/{tenant}', [UsageLimitsController::class, 'getLimits'])->name('usage.limits');
    Route::get('usage/stats', [UsageLimitsController::class, 'getStats'])->name('usage.stats');
});
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Datei `routes/admin.php` erstellen
- [x] Routes für Dashboard definieren
- [x] Routes für Plan-Management (CRUD + Clone)
- [x] Routes für Tenant-Management
- [x] Routes für Usage-Statistics

**In `bootstrap/app.php` registrieren:**

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web')
            ->group(base_path('routes/admin.php'));
    }
)
```

---

## 4. Permissions & Middleware ✓ PRIORITÄT: HOCH

### 4.1 AdminMiddleware erstellen

**Datei:** `app/Http/Middleware/AdminMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Nicht authentifiziert');
        }

        // Check if user is Super Admin or has manage-subscriptions permission
        if (!$user->hasRole('Super Admin') && !$user->can('manage-subscriptions')) {
            abort(403, 'Keine Berechtigung für Admin-Bereich');
        }

        return $next($request);
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Middleware erstellen
- [x] Prüfung auf super_admin und admin Role
- [x] Prüfung auf `manage-subscriptions` Permission
- [x] In `bootstrap/app.php` registrieren

**Registrierung in `bootstrap/app.php`:**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        // ... existing middleware
    ]);
})
```

### 4.2 Permission & Role Seeder erweitern

**Datei:** `database/seeders/RolesAndPermissionsSeeder.php` (erweitern)

```php
// Permission hinzufügen
Permission::create(['name' => 'manage-subscriptions']);

// Super Admin Role
$superAdmin = Role::create(['name' => 'Super Admin']);
$superAdmin->givePermissionTo(Permission::all());
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Permission `manage-subscriptions` hinzufügen (+ 3 weitere subscription-related)
- [x] Super Admin Role (super_admin) bereits vorhanden
- [x] Alle Permissions zu Super Admin und Admin zuweisen

---

## 5. API Resources ✓ PRIORITÄT: MITTEL

### 5.1 SubscriptionPlanResource

**Datei:** `app/Http/Resources/SubscriptionPlanResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period,
            'billing_period_label' => $this->billing_period_label,
            'trial_days' => $this->trial_days,
            'is_active' => $this->is_active,
            'is_custom' => $this->is_custom,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'features' => $this->getFeaturesWithNames(),
            'limits' => $this->getFormattedLimits(),
            'tenants_count' => $this->whenCounted('tenants'),
            'active_tenants_count' => $this->active_tenant_count,
            'monthly_revenue' => $this->monthly_revenue,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Resource erstellen
- [x] Alle Plan-Daten formatieren
- [x] Features mit Display-Namen
- [x] Limits formatiert ausgeben

---

### 5.2 TenantSubscriptionResource

**Datei:** `app/Http/Resources/TenantSubscriptionResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'subscription_tier' => $this->subscription_tier,
            'subscription_plan' => new SubscriptionPlanResource($this->whenLoaded('subscriptionPlan')),
            'customization' => $this->whenLoaded('activeCustomization'),
            'is_active' => $this->is_active,
            'is_suspended' => $this->is_suspended,
            'trial_ends_at' => $this->trial_ends_at,
            'current_counts' => [
                'users' => $this->current_users_count ?? $this->whenCounted('users'),
                'teams' => $this->current_teams_count ?? $this->whenCounted('teams'),
                'players' => $this->whenCounted('players'),
                'games' => $this->whenCounted('games'),
            ],
            'max_limits' => [
                'users' => $this->max_users,
                'teams' => $this->max_teams,
                'storage_gb' => $this->max_storage_gb,
                'api_calls_per_hour' => $this->max_api_calls_per_hour,
            ],
            'revenue' => [
                'total' => $this->total_revenue,
                'mrr' => $this->monthly_recurring_revenue,
            ],
            'last_activity_at' => $this->last_activity_at,
            'created_at' => $this->created_at,
        ];
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Resource erstellen
- [x] Tenant-Daten mit Subscription-Info
- [x] Current Usage Counts
- [x] Revenue-Daten

---

### 5.3 UsageLimitResource

**Datei:** `app/Http/Resources/UsageLimitResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsageLimitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'metric' => $this->resource['metric'] ?? array_key_first($this->resource),
            'current' => $this->resource['current'],
            'limit' => $this->resource['limit'],
            'percentage' => $this->resource['percentage'],
            'unlimited' => $this->resource['unlimited'],
            'is_approaching_limit' => $this->resource['percentage'] > 80,
            'is_at_limit' => $this->resource['percentage'] >= 100,
            'formatted_current' => number_format($this->resource['current'], 0, ',', '.'),
            'formatted_limit' => $this->resource['unlimited'] ? 'Unbegrenzt' : number_format($this->resource['limit'], 0, ',', '.'),
        ];
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Resource erstellen
- [x] Limit-Daten formatieren
- [x] Flags für Warnings (approaching, at limit)
- [x] Severity-Level und Labels hinzugefügt
- [x] Metric-Icons und Units hinzugefügt

---

## 6. Form Request Validation ✓ PRIORITÄT: MITTEL

### 6.1 CreatePlanRequest

**Datei:** `app/Http/Requests/Admin/CreatePlanRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-subscriptions');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:subscription_plans,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_period' => ['required', 'in:monthly,yearly,quarterly'],
            'stripe_price_id' => ['nullable', 'string'],
            'stripe_product_id' => ['nullable', 'string'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'features' => ['required', 'array'],
            'features.*' => ['string'],
            'limits' => ['required', 'array'],
            'limits.users' => ['required', 'integer', 'min:-1'],
            'limits.teams' => ['required', 'integer', 'min:-1'],
            'limits.players' => ['required', 'integer', 'min:-1'],
            'limits.storage_gb' => ['required', 'integer', 'min:-1'],
            'limits.api_calls_per_hour' => ['required', 'integer', 'min:-1'],
        ];
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Request erstellen
- [x] Validierungsregeln definieren
- [x] Authorization-Check
- [x] Custom Attribute-Namen und Fehlermeldungen (DE)

---

### 6.2 UpdatePlanRequest

**Datei:** `app/Http/Requests/Admin/UpdatePlanRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-subscriptions');
    }

    public function rules(): array
    {
        $planId = $this->route('plan')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('subscription_plans')->ignore($planId)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'billing_period' => ['sometimes', 'in:monthly,yearly,quarterly'],
            'stripe_price_id' => ['nullable', 'string'],
            'stripe_product_id' => ['nullable', 'string'],
            'trial_days' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string'],
            'limits' => ['sometimes', 'array'],
            'limits.users' => ['sometimes', 'integer', 'min:-1'],
            'limits.teams' => ['sometimes', 'integer', 'min:-1'],
            'limits.players' => ['sometimes', 'integer', 'min:-1'],
            'limits.storage_gb' => ['sometimes', 'integer', 'min:-1'],
            'limits.api_calls_per_hour' => ['sometimes', 'integer', 'min:-1'],
        ];
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Request erstellen
- [x] Validierungsregeln mit `sometimes`
- [x] Unique-Check mit ignore für Update

---

### 6.3 UpdateTenantSubscriptionRequest

**Datei:** `app/Http/Requests/Admin/UpdateTenantSubscriptionRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-subscriptions');
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ];
    }
}
```

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] Request erstellen
- [x] Validierung für Plan-ID

---

### 6.4 UpdateTenantLimitsRequest & CreateCustomizationRequest

**Status: ✅ IMPLEMENTIERT**

**Tasks:**
- [x] `UpdateTenantLimitsRequest` erstellen
- [x] `CreateCustomizationRequest` erstellen

---

## 7. Vue/Inertia Components ✓ PRIORITÄT: MITTEL

### 7.1 Admin-Layout

**Datei:** `resources/js/Layouts/AdminLayout.vue`

```vue
<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 w-64 bg-gray-900">
            <div class="flex items-center justify-center h-16 bg-gray-800">
                <h1 class="text-white text-xl font-bold">BasketManager Pro</h1>
            </div>

            <nav class="mt-8">
                <Link href="/admin/dashboard" class="nav-link">
                    Dashboard
                </Link>
                <Link href="/admin/plans" class="nav-link">
                    Subscription Plans
                </Link>
                <Link href="/admin/tenants" class="nav-link">
                    Tenants
                </Link>
                <Link href="/admin/usage/stats" class="nav-link">
                    Usage Statistics
                </Link>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="ml-64">
            <header class="bg-white shadow">
                <div class="px-6 py-4">
                    <h2 class="text-2xl font-bold">{{ pageTitle }}</h2>
                </div>
            </header>

            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
});
</script>
```

**Tasks:**
- [ ] Layout-Component erstellen
- [ ] Sidebar mit Navigation
- [ ] Header mit Page-Title
- [ ] Styling mit Tailwind

---

### 7.2 Dashboard

**Datei:** `resources/js/Pages/Admin/Dashboard.vue`

**Features:**
- Tenant-Übersicht (Tabelle)
- Revenue-Statistiken
- Plan-Verteilung (Chart)
- Warnings für Tenants die Limits erreichen

**Tasks:**
- [ ] Dashboard-Page erstellen
- [ ] Tenant-Tabelle mit Pagination
- [ ] Revenue-Cards (Total, MRR)
- [ ] Plan-Verteilung Chart (Chart.js oder ähnlich)

---

### 7.3 Subscription Plans

**Dateien:**
- `resources/js/Pages/Admin/Plans/Index.vue`
- `resources/js/Pages/Admin/Plans/Show.vue`
- `resources/js/Pages/Admin/Plans/Edit.vue`
- `resources/js/Components/Admin/PlanCard.vue`
- `resources/js/Components/Admin/FeatureToggle.vue`
- `resources/js/Components/Admin/LimitEditor.vue`

**Tasks:**
- [ ] Plan-Liste mit Karten
- [ ] Plan-Details anzeigen
- [ ] Plan bearbeiten (Features, Limits)
- [ ] Feature-Toggle Component
- [ ] Limit-Editor Component
- [ ] Clone-Button für Plans

---

### 7.4 Tenant Management

**Dateien:**
- `resources/js/Pages/Admin/Tenants/Index.vue`
- `resources/js/Pages/Admin/Tenants/Show.vue`
- `resources/js/Components/Admin/TenantCard.vue`
- `resources/js/Components/Admin/UsageLimitProgress.vue`
- `resources/js/Components/Admin/SubscriptionEditor.vue`
- `resources/js/Components/Admin/CustomizationForm.vue`

**Tasks:**
- [ ] Tenant-Liste mit Suche/Filter
- [ ] Tenant-Details mit Usage-Stats
- [ ] Usage-Limit Progress-Bars
- [ ] Subscription-Editor (Plan wechseln)
- [ ] Customization-Form (Custom Features/Limits)

---

### 7.5 Usage Statistics

**Datei:** `resources/js/Pages/Admin/UsageStats.vue`

**Features:**
- Globale Usage-Übersicht
- Tenants die Limits erreichen (>80%)
- Export-Funktion

**Tasks:**
- [ ] Usage-Stats Page erstellen
- [ ] Warnings für approaching limits
- [ ] Sortierung nach Usage-Percentage

---

## 8. Testing ✓ PRIORITÄT: NIEDRIG

### 8.1 Feature Tests

**Datei:** `tests/Feature/Admin/SubscriptionPlanManagementTest.php`

```php
<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Spatie\Permission\Models\Role;

class SubscriptionPlanManagementTest extends TestCase
{
    public function test_super_admin_can_create_subscription_plan()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->post('/admin/plans', [
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 99,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'trial_days' => 14,
            'features' => ['feature1', 'feature2'],
            'limits' => [
                'users' => 100,
                'teams' => 10,
                'players' => 200,
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'test-plan',
        ]);
    }

    public function test_regular_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
    }
}
```

**Tasks:**
- [ ] Test-Datei erstellen
- [ ] Admin kann Plans erstellen
- [ ] Admin kann Plans bearbeiten
- [ ] Admin kann Plans löschen (ohne aktive Tenants)
- [ ] Regular User hat keinen Zugriff

---

**Datei:** `tests/Feature/LimitEnforcementTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\BasketballTestCase;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Services\LimitEnforcementService;
use App\Exceptions\UsageQuotaExceededException;

class LimitEnforcementTest extends BasketballTestCase
{
    public function test_team_creation_fails_when_limit_reached()
    {
        // Create tenant with 2-team limit
        $plan = SubscriptionPlan::factory()->create([
            'slug' => 'test-plan',
            'limits' => ['teams' => 2],
        ]);

        $tenant = Tenant::factory()->create([
            'subscription_plan_id' => $plan->id,
        ]);

        app()->instance('tenant', $tenant);

        // Create 2 teams
        $this->createTestTeam();
        $this->createTestTeam();

        // Third team should fail
        $this->expectException(UsageQuotaExceededException::class);
        $this->createTestTeam();
    }

    public function test_player_creation_fails_when_limit_reached()
    {
        // Similar test for players
    }

    public function test_unlimited_plan_allows_infinite_teams()
    {
        $plan = SubscriptionPlan::factory()->create([
            'slug' => 'enterprise',
            'limits' => ['teams' => -1], // Unlimited
        ]);

        $tenant = Tenant::factory()->create([
            'subscription_plan_id' => $plan->id,
        ]);

        app()->instance('tenant', $tenant);

        // Should be able to create many teams
        for ($i = 0; $i < 100; $i++) {
            $this->createTestTeam();
        }

        $this->assertEquals(100, $tenant->teams()->count());
    }
}
```

**Tasks:**
- [ ] Test-Datei erstellen
- [ ] Team-Limit wird enforced
- [ ] Player-Limit wird enforced
- [ ] Unlimited (-1) funktioniert
- [ ] Counter werden korrekt inkrementiert

---

## 9. Finale Integration & Testing ✓ PRIORITÄT: HOCH

### 9.1 Checkliste vor Deployment

**Backend:**
- [ ] Alle Migrationen ausführen: `php artisan migrate`
- [ ] Subscription Plans seeden: `php artisan db:seed --class=SubscriptionPlanSeeder`
- [ ] Super Admin Role & Permission seeden
- [ ] Admin-Routes in `bootstrap/app.php` registrieren
- [ ] AdminMiddleware registrieren
- [ ] Tests ausführen: `php artisan test`

**Frontend:**
- [ ] Alle Vue-Components erstellt
- [ ] Tailwind-Styles kompiliert: `npm run build`
- [ ] Admin-Navigation getestet
- [ ] Responsive-Design geprüft

**Funktionalität:**
- [ ] Team-Erstellung mit Limit-Check testen
- [ ] Player-Erstellung mit Limit-Check testen
- [ ] Admin kann Plans erstellen/bearbeiten
- [ ] Admin kann Tenant-Subscription ändern
- [ ] Usage-Statistics werden korrekt angezeigt

---

## 10. Dokumentation ✓ PRIORITÄT: NIEDRIG

### 10.1 README aktualisieren

**In `README.md` ergänzen:**

```markdown
## Admin-Panel

Das Admin-Panel ermöglicht die Verwaltung von Subscription Plans und Tenants.

### Zugriff

Nur Super Admins oder Benutzer mit `manage-subscriptions` Permission haben Zugriff.

**URL:** `/admin/dashboard`

### Features

- **Subscription Plans:** CRUD-Operationen für Plans
- **Tenant Management:** Subscription-Änderung, Custom Limits
- **Usage Statistics:** Übersicht über Tenant-Usage und Limit-Warnings

### Limit-Enforcement

Limits werden automatisch enforced bei:
- Team-Erstellung
- Player-Erstellung
- User-Erstellung
- Storage-Upload

Bei Limit-Überschreitung wird eine `UsageQuotaExceededException` geworfen.
```

**Tasks:**
- [ ] README.md aktualisieren
- [ ] Admin-Panel dokumentieren
- [ ] API-Endpoints dokumentieren

---

## Geschätzte Zeit

- **PlayerService:** 30 Min
- **Admin-Controller:** 2 Stunden
- **Routes & Middleware:** 30 Min
- **API Resources & Requests:** 1 Stunde
- **Vue Components:** 3-4 Stunden
- **Testing:** 1-2 Stunden
- **Integration & Testing:** 1 Stunde

**Gesamt:** 8-10 Stunden

---

## Prioritäten

1. **HOCH:** PlayerService, Controller, Routes, Middleware
2. **MITTEL:** API Resources, Form Requests, Vue Components
3. **NIEDRIG:** Testing, Dokumentation

---

## Offene Fragen

- [ ] Sollen Admins auch Stripe Price IDs direkt bearbeiten können?
- [ ] Soll es eine Audit-Log für Admin-Aktionen geben?
- [ ] Sollen Email-Benachrichtigungen bei Limit-Überschreitung verschickt werden?
- [ ] Soll es eine Batch-Operation für Subscription-Änderungen geben?

---

**Stand:** {{ now() }}
**Erstellt von:** Claude AI
**Version:** 1.0
