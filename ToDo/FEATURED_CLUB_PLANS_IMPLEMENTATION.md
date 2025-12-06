# Featured Club Plans - Implementierungsplan

> **Datum:** 2025-12-06
> **Status:** Geplant
> **Priorität:** Hoch

---

## Übersicht

### Ziel
Club Subscription Plans sollen ein `is_featured` Flag erhalten. Featured Pläne werden:
1. Auf der öffentlichen Tenant-Landingpage in der Pricing-Sektion angezeigt
2. Als einzige Pläne für neue Vereine bei der Registrierung/Onboarding auswählbar sein
3. Nicht-Featured Pläne können nur von Tenant Admins oder Super Admins manuell zugeordnet werden

### Aktueller Stand
- `ClubSubscriptionPlan` Model hat bereits `is_active` und `is_default` Flags
- Landing Page Pricing-Sektion verwendet aktuell **hardcodierte Inhalte** aus `LandingPageContent`
- Onboarding zeigt alle aktiven Pläne (`is_active = true`) des Tenants
- Keine Unterscheidung zwischen öffentlichen und internen Plänen

---

## Architektur-Entscheidungen

### 1. Featured vs. LandingPageContent
**Entscheidung:** Featured Pläne ersetzen die statische Pricing-Sektion in `LandingPageContent`

**Begründung:**
- Vermeidet Dateninkonsistenz zwischen Plan-Datenbank und Landing Page Content
- Single Source of Truth für Preise und Features
- Automatische Synchronisation bei Plan-Änderungen

### 2. Tenant-Isolation
- Featured Flag ist pro Tenant unabhängig
- Jeder Tenant kann eigene Featured Pläne definieren
- Super Admin kann tenant-übergreifend arbeiten

### 3. Berechtigungsmodell
| Rolle | Kann Featured Pläne sehen | Kann Non-Featured Pläne zuweisen |
|-------|---------------------------|-----------------------------------|
| Gast/Neuer Nutzer | ✅ (Landingpage + Onboarding) | ❌ |
| Club Admin | ✅ (nur eigener Plan) | ❌ |
| Tenant Admin | ✅ | ✅ |
| Super Admin | ✅ | ✅ |

---

## Phase 1: Datenbank-Migration

### 1.1 Migration erstellen

**Datei:** `database/migrations/YYYY_MM_DD_HHMMSS_add_featured_to_club_subscription_plans_table.php`

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
            $table->boolean('is_featured')
                ->default(false)
                ->after('is_default')
                ->comment('Ob der Plan auf der Landingpage angezeigt und bei Registrierung auswählbar ist');

            // Index für effiziente Abfragen
            $table->index(['tenant_id', 'is_featured', 'is_active'], 'idx_tenant_featured_active');
        });
    }

    public function down(): void
    {
        Schema::table('club_subscription_plans', function (Blueprint $table) {
            $table->dropIndex('idx_tenant_featured_active');
            $table->dropColumn('is_featured');
        });
    }
};
```

### 1.2 Seeder aktualisieren

**Datei:** `database/seeders/ClubSubscriptionPlanSeeder.php`

Standard-Pläne (Free, Standard, Premium) als `is_featured = true` markieren.
Enterprise Plan als `is_featured = false` (nur durch Admin zuweisbar).

---

## Phase 2: Model-Anpassungen

### 2.1 ClubSubscriptionPlan Model

**Datei:** `app/Models/ClubSubscriptionPlan.php`

```php
// Zu $fillable hinzufügen
'is_featured',

// Zu $casts hinzufügen
'is_featured' => 'boolean',

// Neue Scopes
public function scopeFeatured($query)
{
    return $query->where('is_featured', true);
}

public function scopeNotFeatured($query)
{
    return $query->where('is_featured', false);
}

public function scopePubliclyAvailable($query)
{
    return $query->where('is_active', true)
                 ->where('is_featured', true);
}

// Neue Helper-Methode
public function isPubliclyAvailable(): bool
{
    return $this->is_active && $this->is_featured;
}
```

---

## Phase 3: Service-Layer

### 3.1 ClubSubscriptionPlanService erweitern

**Datei:** `app/Services/Club/ClubSubscriptionPlanService.php`

```php
/**
 * Gibt öffentlich verfügbare (Featured + Active) Pläne für einen Tenant zurück.
 * Diese werden auf der Landingpage und im Onboarding angezeigt.
 */
public function getPublicPlans(Tenant $tenant): Collection
{
    return ClubSubscriptionPlan::forTenant($tenant->id)
        ->publiclyAvailable()
        ->orderBy('sort_order')
        ->orderBy('price')
        ->get();
}

/**
 * Gibt alle Pläne (inkl. Non-Featured) für Admins zurück.
 */
public function getAllPlansForAdmin(Tenant $tenant): Collection
{
    return ClubSubscriptionPlan::forTenant($tenant->id)
        ->active()
        ->orderBy('sort_order')
        ->orderBy('price')
        ->get();
}

/**
 * Prüft ob ein Nutzer einen bestimmten Plan zuweisen darf.
 */
public function canAssignPlan(User $user, ClubSubscriptionPlan $plan): bool
{
    // Featured Pläne können von allen zugewiesen werden
    if ($plan->is_featured) {
        return true;
    }

    // Non-Featured nur durch Admins
    return $user->hasRole(['super_admin', 'tenant_admin']);
}
```

### 3.2 OnboardingService anpassen

**Datei:** `app/Services/OnboardingService.php`

```php
// Methode getAvailablePlans() anpassen
public function getAvailablePlans(Tenant $tenant): Collection
{
    return ClubSubscriptionPlan::forTenant($tenant->id)
        ->publiclyAvailable()  // Nur Featured + Active
        ->orderBy('sort_order')
        ->orderBy('price')
        ->get();
}
```

---

## Phase 4: Landing Page Integration

### 4.1 LandingPageService erweitern

**Datei:** `app/Services/LandingPageService.php`

```php
/**
 * Lädt Featured Pläne für die Pricing-Sektion der Landingpage.
 */
public function getFeaturedPlans(?int $tenantId = null): Collection
{
    $query = ClubSubscriptionPlan::publiclyAvailable();

    if ($tenantId) {
        $query->forTenant($tenantId);
    }

    return $query->orderBy('sort_order')
                 ->orderBy('price')
                 ->get();
}

/**
 * Transformiert Pläne für die Landing Page Anzeige.
 */
public function transformPlansForLandingPage(Collection $plans): array
{
    return $plans->map(function ($plan) {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price' => $plan->price,
            'formatted_price' => $plan->formatted_price,
            'currency' => $plan->currency,
            'billing_interval' => $plan->billing_interval,
            'description' => $plan->description,
            'features' => $plan->getFeaturesList(),
            'limits' => $plan->getLimitsList(),
            'color' => $plan->color,
            'icon' => $plan->icon,
            'is_default' => $plan->is_default,
            'trial_period_days' => $plan->trial_period_days,
            'cta_text' => 'Jetzt starten',
            'cta_link' => route('register') . '?plan=' . $plan->slug,
        ];
    })->toArray();
}
```

### 4.2 Web Route anpassen

**Datei:** `routes/web.php`

```php
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    $landingPageService = app(\App\Services\LandingPageService::class);
    $tenantId = null; // TODO: Get from domain

    $content = $landingPageService->getAllContent($tenantId);

    // Featured Pläne für Pricing-Sektion laden
    $featuredPlans = $landingPageService->getFeaturedPlans($tenantId);
    $content['pricing']['plans'] = $landingPageService->transformPlansForLandingPage($featuredPlans);

    return view('landing', ['content' => $content]);
})->name('landing');
```

### 4.3 Landing Blade Template anpassen

**Datei:** `resources/views/landing.blade.php`

Die Pricing-Sektion (Zeilen ca. 297-409) von statischen `$content['pricing']['items']` auf dynamische `$content['pricing']['plans']` umstellen:

```blade
{{-- Pricing Section --}}
<section id="pricing" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                {{ $content['pricing']['headline'] ?? 'Transparente Preise' }}
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                {{ $content['pricing']['subheadline'] ?? 'Wähle den Plan, der zu deinem Verein passt' }}
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-{{ min(count($content['pricing']['plans'] ?? []), 4) }} gap-8">
            @foreach($content['pricing']['plans'] ?? [] as $plan)
                <div class="bg-white rounded-2xl shadow-lg p-8 {{ $plan['is_default'] ? 'ring-2 ring-orange-500 scale-105' : '' }} hover:shadow-xl transition-all duration-300">
                    {{-- Plan Header --}}
                    <div class="text-center mb-6">
                        @if($plan['is_default'])
                            <span class="inline-block px-3 py-1 text-xs font-semibold text-orange-600 bg-orange-100 rounded-full mb-3">
                                Empfohlen
                            </span>
                        @endif
                        <h3 class="text-2xl font-bold text-gray-900">{{ $plan['name'] }}</h3>
                        <p class="text-gray-500 mt-2">{{ $plan['description'] }}</p>
                    </div>

                    {{-- Preis --}}
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold text-gray-900">{{ $plan['formatted_price'] }}</span>
                        <span class="text-gray-500">/{{ $plan['billing_interval'] === 'yearly' ? 'Jahr' : 'Monat' }}</span>
                        @if($plan['trial_period_days'] > 0)
                            <p class="text-sm text-green-600 mt-2">{{ $plan['trial_period_days'] }} Tage kostenlos testen</p>
                        @endif
                    </div>

                    {{-- Features --}}
                    <ul class="space-y-3 mb-8">
                        @foreach($plan['features'] as $feature)
                            <li class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    {{-- Limits --}}
                    <div class="border-t pt-4 mb-6">
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Teams:</span>
                            <span class="font-medium">{{ $plan['limits']['max_teams'] ?? 'Unbegrenzt' }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500 mt-1">
                            <span>Spieler:</span>
                            <span class="font-medium">{{ $plan['limits']['max_players'] ?? 'Unbegrenzt' }}</span>
                        </div>
                    </div>

                    {{-- CTA Button --}}
                    <a href="{{ $plan['cta_link'] }}"
                       class="block w-full py-3 px-4 text-center rounded-lg font-semibold transition-colors duration-200
                              {{ $plan['is_default']
                                 ? 'bg-orange-500 text-white hover:bg-orange-600'
                                 : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                        {{ $plan['cta_text'] }}
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Money-back guarantee --}}
        <p class="text-center text-gray-500 mt-8">
            30 Tage Geld-zurück-Garantie • Jederzeit kündbar • DSGVO-konform
        </p>
    </div>
</section>
```

---

## Phase 5: Onboarding-Anpassung

### 5.1 OnboardingController

**Datei:** `app/Http/Controllers/OnboardingController.php`

```php
public function index()
{
    // ...existing code...

    // Nur Featured Pläne für Onboarding laden
    $availablePlans = $this->onboardingService->getAvailablePlans($tenant);

    // ...rest of method...
}
```

### 5.2 StorePlanRequest Validierung

**Datei:** `app/Http/Requests/Onboarding/StorePlanRequest.php`

```php
public function rules(): array
{
    return [
        'plan_id' => [
            'required',
            'string',
            Rule::exists('club_subscription_plans', 'id')->where(function ($query) {
                $query->where('is_active', true)
                      ->where('is_featured', true); // Nur Featured Pläne erlaubt
            }),
        ],
        'billing_interval' => ['nullable', 'string', Rule::in(['monthly', 'yearly'])],
    ];
}
```

---

## Phase 6: Admin-Bereich

### 6.1 Admin Controller anpassen

**Datei:** `app/Http/Controllers/Admin/ClubSubscriptionPlanController.php`

Index-Methode: Featured-Status anzeigen
Create/Edit: Checkbox für `is_featured` hinzufügen

### 6.2 Vue Admin Komponenten

**Datei:** `resources/js/Pages/Admin/ClubSubscriptionPlans/Index.vue`

- Badge für Featured-Status hinzufügen (z.B. gelber Stern)
- Filter nach Featured/Non-Featured

**Datei:** `resources/js/Pages/Admin/ClubSubscriptionPlans/Form.vue`

```vue
<div class="mb-4">
    <label class="flex items-center">
        <input type="checkbox"
               v-model="form.is_featured"
               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
        <span class="ml-2 text-sm text-gray-700">
            Featured Plan
            <span class="text-gray-500">(Auf Landingpage anzeigen & bei Registrierung auswählbar)</span>
        </span>
    </label>
</div>
```

### 6.3 Manuelle Plan-Zuweisung (Non-Featured)

**Neue Route:** `POST /admin/clubs/{club}/assign-plan`

**Controller-Methode:**
```php
public function assignPlan(Request $request, Club $club)
{
    $this->authorize('assignToClub', ClubSubscriptionPlan::class);

    $validated = $request->validate([
        'plan_id' => ['required', 'exists:club_subscription_plans,id'],
    ]);

    $plan = ClubSubscriptionPlan::findOrFail($validated['plan_id']);

    // Prüfen ob Admin Non-Featured Plan zuweisen darf
    if (!$plan->is_featured && !auth()->user()->hasRole(['super_admin', 'tenant_admin'])) {
        abort(403, 'Nur Administratoren können nicht-öffentliche Pläne zuweisen.');
    }

    $club->update(['club_subscription_plan_id' => $plan->id]);

    return back()->with('success', "Plan '{$plan->name}' wurde zugewiesen.");
}
```

---

## Phase 7: Policy-Anpassungen

### 7.1 ClubSubscriptionPlanPolicy erweitern

**Datei:** `app/Policies/ClubSubscriptionPlanPolicy.php`

```php
/**
 * Bestimmt ob ein Nutzer einen bestimmten Plan zuweisen kann.
 * Featured Pläne: Alle authentifizierten Nutzer
 * Non-Featured Pläne: Nur Super Admin / Tenant Admin
 */
public function assignPlan(User $user, ClubSubscriptionPlan $plan): bool
{
    // Featured Pläne können von allen zugewiesen werden
    if ($plan->is_featured && $plan->is_active) {
        return true;
    }

    // Non-Featured nur durch Admins
    return $user->hasRole(['super_admin', 'tenant_admin']);
}

/**
 * Bestimmt ob ein Nutzer das Featured-Flag ändern kann.
 */
public function toggleFeatured(User $user, ClubSubscriptionPlan $plan): bool
{
    // Nur Super Admin oder Tenant Admin des zugehörigen Tenants
    if ($user->hasRole('super_admin')) {
        return true;
    }

    if ($user->hasRole('tenant_admin')) {
        return in_array($plan->tenant_id, $user->getAdministeredTenantIds());
    }

    return false;
}
```

---

## Phase 8: API-Anpassungen

### 8.1 API Controller

**Datei:** `app/Http/Controllers/Api/ClubSubscriptionPlanController.php`

```php
/**
 * Öffentliche Featured Pläne für Landingpage/Registrierung.
 */
public function publicPlans(Request $request)
{
    $tenant = $this->resolveTenant($request);

    $plans = ClubSubscriptionPlan::forTenant($tenant->id)
        ->publiclyAvailable()
        ->orderBy('sort_order')
        ->get();

    return ClubSubscriptionPlanResource::collection($plans);
}
```

### 8.2 Resource erweitern

**Datei:** `app/Http/Resources/ClubSubscriptionPlanResource.php`

```php
'is_featured' => $this->is_featured,
'is_publicly_available' => $this->isPubliclyAvailable(),
```

---

## Phase 9: Tests

### 9.1 Unit Tests

**Datei:** `tests/Unit/Models/ClubSubscriptionPlanTest.php`

```php
public function test_scope_featured_returns_only_featured_plans(): void
{
    $featured = ClubSubscriptionPlan::factory()->create(['is_featured' => true]);
    $notFeatured = ClubSubscriptionPlan::factory()->create(['is_featured' => false]);

    $result = ClubSubscriptionPlan::featured()->get();

    $this->assertTrue($result->contains($featured));
    $this->assertFalse($result->contains($notFeatured));
}

public function test_scope_publicly_available_requires_active_and_featured(): void
{
    $activeAndFeatured = ClubSubscriptionPlan::factory()->create([
        'is_active' => true,
        'is_featured' => true
    ]);
    $inactiveButFeatured = ClubSubscriptionPlan::factory()->create([
        'is_active' => false,
        'is_featured' => true
    ]);
    $activeButNotFeatured = ClubSubscriptionPlan::factory()->create([
        'is_active' => true,
        'is_featured' => false
    ]);

    $result = ClubSubscriptionPlan::publiclyAvailable()->get();

    $this->assertTrue($result->contains($activeAndFeatured));
    $this->assertFalse($result->contains($inactiveButFeatured));
    $this->assertFalse($result->contains($activeButNotFeatured));
}
```

### 9.2 Feature Tests

**Datei:** `tests/Feature/Onboarding/PlanSelectionTest.php`

```php
public function test_onboarding_only_shows_featured_plans(): void
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $featuredPlan = ClubSubscriptionPlan::factory()->create([
        'tenant_id' => $tenant->id,
        'is_featured' => true,
        'is_active' => true,
    ]);
    $internalPlan = ClubSubscriptionPlan::factory()->create([
        'tenant_id' => $tenant->id,
        'is_featured' => false,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('onboarding.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('availablePlans', 1)
        ->where('availablePlans.0.id', $featuredPlan->id)
    );
}

public function test_selecting_non_featured_plan_during_onboarding_fails(): void
{
    // ...setup...

    $response = $this->actingAs($user)->post(route('onboarding.plan'), [
        'plan_id' => $internalPlan->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['plan_id']);
}

public function test_admin_can_assign_non_featured_plan(): void
{
    // ...setup with tenant_admin user...

    $response = $this->actingAs($adminUser)->post(
        route('admin.clubs.assign-plan', $club),
        ['plan_id' => $internalPlan->id]
    );

    $response->assertRedirect();
    $this->assertEquals($internalPlan->id, $club->fresh()->club_subscription_plan_id);
}
```

### 9.3 Landing Page Tests

**Datei:** `tests/Feature/LandingPageTest.php`

```php
public function test_landing_page_shows_only_featured_plans_in_pricing(): void
{
    $tenant = Tenant::factory()->create();

    $featuredPlan = ClubSubscriptionPlan::factory()->create([
        'tenant_id' => $tenant->id,
        'is_featured' => true,
        'is_active' => true,
        'name' => 'Pro Plan',
    ]);

    $internalPlan = ClubSubscriptionPlan::factory()->create([
        'tenant_id' => $tenant->id,
        'is_featured' => false,
        'is_active' => true,
        'name' => 'Internal Plan',
    ]);

    $response = $this->get('/');

    $response->assertSee('Pro Plan');
    $response->assertDontSee('Internal Plan');
}
```

---

## Datei-Übersicht

### Zu erstellende Dateien
| Datei | Beschreibung |
|-------|--------------|
| `database/migrations/XXXX_add_featured_to_club_subscription_plans_table.php` | Migration |

### Zu ändernde Dateien
| Datei | Änderung |
|-------|----------|
| `app/Models/ClubSubscriptionPlan.php` | `is_featured` Feld + Scopes |
| `app/Services/Club/ClubSubscriptionPlanService.php` | Neue Methoden |
| `app/Services/OnboardingService.php` | Filter auf Featured |
| `app/Services/LandingPageService.php` | Featured Plans Methoden |
| `routes/web.php` | Landing Page Route anpassen |
| `resources/views/landing.blade.php` | Dynamische Pricing-Sektion |
| `app/Http/Controllers/OnboardingController.php` | Featured Filter |
| `app/Http/Requests/Onboarding/StorePlanRequest.php` | Validierung |
| `app/Http/Controllers/Admin/ClubSubscriptionPlanController.php` | Featured Feld |
| `resources/js/Pages/Admin/ClubSubscriptionPlans/Index.vue` | Featured Badge |
| `resources/js/Pages/Admin/ClubSubscriptionPlans/Form.vue` | Featured Checkbox |
| `app/Policies/ClubSubscriptionPlanPolicy.php` | assignPlan + toggleFeatured |
| `app/Http/Controllers/Api/ClubSubscriptionPlanController.php` | publicPlans Endpoint |
| `app/Http/Resources/ClubSubscriptionPlanResource.php` | is_featured Feld |
| `database/seeders/ClubSubscriptionPlanSeeder.php` | Default Featured-Werte |
| `config/club_plans.php` | is_featured in Default-Config |

### Zu erstellende Tests
| Datei | Beschreibung |
|-------|--------------|
| `tests/Unit/Models/ClubSubscriptionPlanFeaturedTest.php` | Model Scope Tests |
| `tests/Feature/Onboarding/FeaturedPlanSelectionTest.php` | Onboarding Tests |
| `tests/Feature/LandingPage/FeaturedPricingTest.php` | Landing Page Tests |
| `tests/Feature/Admin/AssignNonFeaturedPlanTest.php` | Admin Tests |

---

## Migrations-Checkliste

### Vor der Migration
- [ ] Backup der Datenbank erstellen
- [ ] Bestehende Pläne dokumentieren

### Nach der Migration
- [ ] Standard-Pläne (Free, Standard, Premium) als Featured markieren
- [ ] Enterprise-Plan als Non-Featured belassen (oder nach Bedarf)
- [ ] Landing Page testen
- [ ] Onboarding-Flow testen
- [ ] Admin-Zuweisung testen

---

## Geschätzte Dateien nach Komponente

| Komponente | Dateien | Komplexität |
|------------|---------|-------------|
| Datenbank | 1 Migration, 1 Seeder Update | Niedrig |
| Model | 1 Datei | Niedrig |
| Services | 2 Dateien | Mittel |
| Controllers | 3 Dateien | Mittel |
| Vue Components | 2-3 Dateien | Mittel |
| Blade Templates | 1 Datei | Mittel |
| Policies | 1 Datei | Niedrig |
| Tests | 4 Dateien | Mittel |

---

## Offene Fragen

1. **Sollen alle bestehenden Pläne initial als Featured markiert werden?**
   - Empfehlung: Free, Standard, Premium = Featured; Enterprise = Non-Featured

2. **Soll die statische Pricing-Sektion in LandingPageContent entfernt oder als Fallback behalten werden?**
   - Empfehlung: Als Fallback behalten, falls keine Featured Pläne vorhanden

3. **Wie soll die Mobile-Darstellung der dynamischen Pricing-Sektion aussehen?**
   - Empfehlung: Responsive Grid wie aktuell (1 Spalte mobile, 2-4 Desktop)

4. **Sollen Non-Featured Pläne im Admin-Bereich visuell unterschiedlich dargestellt werden?**
   - Empfehlung: Ja, mit grauem Badge oder fehlender Stern-Icon

---

## Nächste Schritte

1. Migration erstellen und ausführen
2. Model und Services anpassen
3. Landing Page Template aktualisieren
4. Onboarding-Flow anpassen
5. Admin-Bereich erweitern
6. Tests schreiben und ausführen
7. Manuelle QA auf Staging-Umgebung
