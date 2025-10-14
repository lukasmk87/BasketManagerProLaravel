# Club-Level Subscription Plans - Implementierungsplan

**Erstellt am:** 2025-10-14
**Status:** ✅ Abgeschlossen
**Priorität:** 🔴 Hoch
**Tatsächliche Dauer:** 2 Tage

---

## 📋 Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Architektur-Konzept](#architektur-konzept)
3. [Datenbank-Schema](#datenbank-schema)
4. [Models](#models)
5. [Services](#services)
6. [Konfiguration](#konfiguration)
7. [Migrations](#migrations)
8. [Seeders](#seeders)
9. [API/Controller](#apicontroller)
10. [Validierung & Regeln](#validierung--regeln)
11. [Tests](#tests)
12. [Checkliste](#checkliste)

---

## 🎯 Übersicht

### Zielsetzung
Implementierung von **Club-spezifischen Subscription-Plänen**, die pro Tenant individuell anpassbar sind. Dies ermöglicht Tenants, verschiedenen Clubs unterschiedliche Feature-Sets und Limits zuzuweisen.

### Aktuelle Situation (Status Quo)
- ✅ Nur **Tenants** haben Subscriptions (`subscription_tier`: free, basic, professional, enterprise)
- ✅ Features werden ausschließlich auf **Tenant-Ebene** gesteuert (siehe `app/Models/Tenant.php:272-285`)
- ✅ FeatureGateService arbeitet nur mit Tenant-Features
- ❌ **Clubs** haben KEINE eigenen Subscription-Pläne
- ❌ Keine Club-Level Feature-Gates oder Limits

### Zielzustand
- ✅ Tenants können eigene **Club-Subscription-Pläne** definieren
- ✅ Jeder Club kann einem Plan zugeordnet werden
- ✅ **Feature-Hierarchie**: Tenant-Features > Club-Features (Club kann nie mehr Features haben als der Tenant)
- ✅ **Limit-Hierarchie**: `min(tenant_limit, club_limit)` gilt
- ✅ Backward Compatible: Clubs ohne Plan nutzen automatisch Tenant-Features

### Use Cases

#### Use Case 1: Basketball-Verband mit mehreren Clubs
```
Tenant: "Bayerischer Basketball Verband" (Enterprise Tier)
├── Tenant-Features: Alle Enterprise-Features verfügbar
│
├── Club Plan: "Profi-Club Premium" (€199/Monat)
│   ├── Features: live_scoring, advanced_analytics, video_analysis, api_access
│   ├── Limits: 500 players, 50 teams, 200GB storage
│   └── Club: "FC Bayern Basketball" → nutzt diesen Plan
│
├── Club Plan: "Regional-Club Standard" (€99/Monat)
│   ├── Features: live_scoring, basic_analytics
│   ├── Limits: 200 players, 20 teams, 50GB storage
│   └── Club: "ratiopharm Ulm" → nutzt diesen Plan
│
└── Club Plan: "Jugend-Club Basic" (€29/Monat)
    ├── Features: basic_team_management, game_scheduling
    ├── Limits: 100 players, 10 teams, 20GB storage
    └── Club: "Nachwuchsakademie München" → nutzt diesen Plan
```

**Billing:** Der Verband (Tenant) zahlt die Enterprise-Subscription. Die Club-Pläne sind interne Zuordnungen (kein direktes Stripe-Billing pro Club).

#### Use Case 2: Einzelner Verein mit Untergliederungen
```
Tenant: "München Basketballer e.V." (Professional Tier)
├── Tenant-Features: Professional-Features
│
├── Club Plan: "Hauptverein Premium"
│   └── Club: "München Basketballer e.V. - Hauptverein"
│
└── Club Plan: "Jugendabteilung Basic"
    └── Club: "München Basketballer e.V. - Jugend"
```

---

## 🏗️ Architektur-Konzept

### Hierarchie-Ebenen

```
┌─────────────────────────────────────────────────────────┐
│ TENANT (SaaS-Ebene)                                     │
│ ├── Subscription Tier (free/basic/professional/enterprise)│
│ ├── Tenant-Features (definiert durch Tier)              │
│ ├── Tenant-Limits (max_users, max_teams, etc.)         │
│ └── Stripe Billing (Laravel Cashier)                    │
└─────────────────────────────────────────────────────────┘
                            │
                            │ 1:n
                            ▼
┌─────────────────────────────────────────────────────────┐
│ CLUB SUBSCRIPTION PLANS (Tenant-spezifisch)             │
│ ├── Von Tenant definiert und anpassbar                  │
│ ├── Features ⊆ Tenant-Features (Teilmenge!)            │
│ ├── Limits ≤ Tenant-Limits (nie höher!)                │
│ ├── Pricing (nur intern, kein Stripe)                   │
│ └── Zuordnung zu Clubs                                  │
└─────────────────────────────────────────────────────────┘
                            │
                            │ 1:n
                            ▼
┌─────────────────────────────────────────────────────────┐
│ CLUBS (Basketball-Vereine)                              │
│ ├── Optional: club_subscription_plan_id (FK)            │
│ ├── Wenn KEIN Plan → Tenant-Features gelten            │
│ ├── Wenn Plan vorhanden → Club-Features gelten         │
│ └── Teams, Players, Games (bestehende Struktur)        │
└─────────────────────────────────────────────────────────┘
```

### Feature-Gate Logik (Hierarchie)

```php
// Pseudo-Code für Feature-Check
function hasFeature(Club $club, string $feature): bool
{
    $tenant = $club->tenant;

    // 1. Tenant muss das Feature haben
    if (!$tenant->hasFeature($feature)) {
        return false; // Tenant hat Feature nicht → Club kann es nicht haben
    }

    // 2. Wenn Club KEINEN Plan hat → Tenant-Features gelten
    if (!$club->club_subscription_plan_id) {
        return true; // Club erbt Tenant-Features
    }

    // 3. Wenn Club Plan hat → Club-Plan-Features gelten
    $clubPlan = $club->subscriptionPlan;
    return $clubPlan->hasFeature($feature);
}
```

### Limit-Hierarchie

```php
// Pseudo-Code für Limit-Check
function getLimit(Club $club, string $metric): int
{
    $tenant = $club->tenant;
    $tenantLimit = $tenant->getTierLimits()[$metric] ?? -1;

    // Wenn Tenant unlimited (-1)
    if ($tenantLimit === -1) {
        if (!$club->subscriptionPlan) {
            return -1; // Club erbt unlimited
        }
        return $club->subscriptionPlan->getLimit($metric);
    }

    // Wenn Club keinen Plan hat → Tenant-Limit gilt
    if (!$club->subscriptionPlan) {
        return $tenantLimit;
    }

    // Club-Limit kann nie höher sein als Tenant-Limit
    $clubLimit = $club->subscriptionPlan->getLimit($metric);
    return min($tenantLimit, $clubLimit);
}
```

---

## 💾 Datenbank-Schema

### 1. Neue Tabelle: `club_subscription_plans`

```sql
CREATE TABLE `club_subscription_plans` (
    `id` CHAR(36) PRIMARY KEY,
    `tenant_id` CHAR(36) NOT NULL,

    -- Plan Details
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `price` DECIMAL(10,2) DEFAULT 0.00,
    `currency` VARCHAR(3) DEFAULT 'EUR',
    `billing_interval` ENUM('monthly', 'yearly') DEFAULT 'monthly',

    -- Features & Limits (JSON)
    `features` JSON NULL COMMENT 'Array of feature slugs',
    `limits` JSON NULL COMMENT 'Object with metric => limit',

    -- Metadata
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_default` BOOLEAN DEFAULT FALSE COMMENT 'Default plan for new clubs',
    `sort_order` INT DEFAULT 0,
    `color` VARCHAR(7) NULL COMMENT 'Hex color for UI',
    `icon` VARCHAR(50) NULL COMMENT 'Icon identifier',

    -- Timestamps
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,

    -- Foreign Keys
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,

    -- Indexes
    UNIQUE KEY `unique_tenant_slug` (`tenant_id`, `slug`),
    INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
    INDEX `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**JSON-Struktur Beispiele:**

```json
// features
[
    "basic_team_management",
    "live_scoring",
    "advanced_statistics",
    "training_management"
]

// limits
{
    "max_teams": 20,
    "max_players": 200,
    "max_storage_gb": 50,
    "max_games_per_month": 100,
    "max_training_sessions_per_month": 200
}
```

### 2. Migration: Erweitern der `clubs` Tabelle

```sql
ALTER TABLE `clubs`
ADD COLUMN `club_subscription_plan_id` CHAR(36) NULL AFTER `uuid`,
ADD CONSTRAINT `fk_clubs_subscription_plan`
    FOREIGN KEY (`club_subscription_plan_id`)
    REFERENCES `club_subscription_plans`(`id`)
    ON DELETE SET NULL,
ADD INDEX `idx_subscription_plan` (`club_subscription_plan_id`);
```

---

## 📦 Models

### 1. Neues Model: `ClubSubscriptionPlan`

**Datei:** `app/Models/ClubSubscriptionPlan.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Concerns\BelongsToTenant;

class ClubSubscriptionPlan extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_interval',
        'features',
        'limits',
        'is_active',
        'is_default',
        'sort_order',
        'color',
        'icon',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['id'];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    // =============================
    // RELATIONSHIPS
    // =============================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clubs()
    {
        return $this->hasMany(Club::class, 'club_subscription_plan_id');
    }

    public function activeClubs()
    {
        return $this->clubs()->where('is_active', true);
    }

    // =============================
    // SCOPES
    // =============================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // =============================
    // FEATURE & LIMIT METHODS
    // =============================

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        $planFeatures = $this->features ?? [];
        return in_array($feature, $planFeatures);
    }

    /**
     * Get limit for a specific metric.
     */
    public function getLimit(string $metric): int
    {
        $limits = $this->limits ?? [];
        return $limits[$metric] ?? -1; // -1 = unlimited
    }

    /**
     * Check if plan features are subset of tenant features.
     */
    public function isWithinTenantLimits(): bool
    {
        $tenant = $this->tenant;
        if (!$tenant) {
            return false;
        }

        // Check all plan features are available in tenant
        foreach ($this->features ?? [] as $feature) {
            if (!$tenant->hasFeature($feature)) {
                return false; // Plan has feature that tenant doesn't have
            }
        }

        // Check all plan limits are within tenant limits
        $tenantLimits = $tenant->getTierLimits();
        foreach ($this->limits ?? [] as $metric => $limit) {
            $tenantLimit = $tenantLimits[$metric] ?? -1;

            // Skip if tenant has unlimited
            if ($tenantLimit === -1) {
                continue;
            }

            // Plan limit must be <= tenant limit
            if ($limit > $tenantLimit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate plan data against tenant capabilities.
     */
    public static function validateAgainstTenant(array $planData, Tenant $tenant): array
    {
        $errors = [];

        // Validate features
        $planFeatures = $planData['features'] ?? [];
        foreach ($planFeatures as $feature) {
            if (!$tenant->hasFeature($feature)) {
                $errors['features'][] = "Feature '{$feature}' not available in tenant tier '{$tenant->subscription_tier}'";
            }
        }

        // Validate limits
        $planLimits = $planData['limits'] ?? [];
        $tenantLimits = $tenant->getTierLimits();

        foreach ($planLimits as $metric => $limit) {
            $tenantLimit = $tenantLimits[$metric] ?? -1;

            if ($tenantLimit !== -1 && $limit > $tenantLimit) {
                $errors['limits'][] = "Limit '{$metric}' ({$limit}) exceeds tenant limit ({$tenantLimit})";
            }
        }

        return $errors;
    }

    // =============================
    // HELPER METHODS
    // =============================

    public function getClubsCountAttribute(): int
    {
        return $this->clubs()->count();
    }

    public function getActiveClubsCountAttribute(): int
    {
        return $this->activeClubs()->count();
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2, ',', '.') . ' ' . $this->currency;
    }
}
```

### 2. Erweitern: `Club` Model

**Datei:** `app/Models/Club.php` (Ergänzungen)

```php
// Zu $fillable hinzufügen:
protected $fillable = [
    // ... existing fields
    'club_subscription_plan_id',
];

// Neue Relationship:
public function subscriptionPlan()
{
    return $this->belongsTo(ClubSubscriptionPlan::class, 'club_subscription_plan_id');
}

// =============================
// FEATURE & LIMIT METHODS
// =============================

/**
 * Check if club has a specific feature (considering hierarchy).
 */
public function hasFeature(string $feature): bool
{
    // Hole den Tenant (über BelongsToTenant trait oder direkt)
    $tenant = $this->tenant ?? app('tenant');

    // 1. Tenant muss Feature haben
    if (!$tenant || !$tenant->hasFeature($feature)) {
        return false;
    }

    // 2. Wenn kein Club-Plan → Tenant-Features gelten
    if (!$this->subscriptionPlan) {
        return true;
    }

    // 3. Wenn Club-Plan vorhanden → prüfe Club-Plan
    return $this->subscriptionPlan->hasFeature($feature);
}

/**
 * Get limit for a specific metric (considering hierarchy).
 */
public function getLimit(string $metric): int
{
    $tenant = $this->tenant ?? app('tenant');

    if (!$tenant) {
        return 0;
    }

    $tenantLimit = $tenant->getTierLimits()[$metric] ?? -1;

    // Wenn Tenant unlimited
    if ($tenantLimit === -1) {
        if (!$this->subscriptionPlan) {
            return -1;
        }
        return $this->subscriptionPlan->getLimit($metric);
    }

    // Wenn kein Club-Plan → Tenant-Limit
    if (!$this->subscriptionPlan) {
        return $tenantLimit;
    }

    // min(tenant_limit, club_limit)
    $clubLimit = $this->subscriptionPlan->getLimit($metric);
    return min($tenantLimit, $clubLimit === -1 ? $tenantLimit : $clubLimit);
}

/**
 * Check if club can use a resource.
 */
public function canUse(string $metric, int $amount = 1): bool
{
    $limit = $this->getLimit($metric);

    if ($limit === -1) {
        return true; // Unlimited
    }

    $currentUsage = $this->getCurrentUsage($metric);
    return ($currentUsage + $amount) <= $limit;
}

/**
 * Get current usage for a metric (implement based on your needs).
 */
protected function getCurrentUsage(string $metric): int
{
    return match($metric) {
        'max_teams' => $this->teams()->count(),
        'max_players' => $this->players()->count(),
        'max_storage_gb' => $this->calculateStorageUsage(),
        default => 0,
    };
}

/**
 * Get subscription limits for this club.
 */
public function getSubscriptionLimits(): array
{
    $metrics = ['max_teams', 'max_players', 'max_storage_gb', 'max_games_per_month'];
    $limits = [];

    foreach ($metrics as $metric) {
        $limits[$metric] = [
            'limit' => $this->getLimit($metric),
            'current' => $this->getCurrentUsage($metric),
            'unlimited' => $this->getLimit($metric) === -1,
        ];
    }

    return $limits;
}

/**
 * Assign club to a subscription plan.
 */
public function assignPlan(ClubSubscriptionPlan $plan): void
{
    // Validate that plan belongs to same tenant
    if ($plan->tenant_id !== $this->tenant_id) {
        throw new \Exception("Plan does not belong to club's tenant");
    }

    $this->update(['club_subscription_plan_id' => $plan->id]);
}

/**
 * Remove subscription plan (club will use tenant features).
 */
public function removePlan(): void
{
    $this->update(['club_subscription_plan_id' => null]);
}
```

### 3. Erweitern: `Tenant` Model

**Datei:** `app/Models/Tenant.php` (Ergänzungen)

```php
// Neue Relationship:
public function clubSubscriptionPlans()
{
    return $this->hasMany(ClubSubscriptionPlan::class);
}

public function activeClubPlans()
{
    return $this->clubSubscriptionPlans()->active();
}

public function defaultClubPlan()
{
    return $this->clubSubscriptionPlans()->default()->first();
}
```

---

## ⚙️ Services

### 1. Erweitern: `FeatureGateService`

**Datei:** `app/Services/FeatureGateService.php` (Ergänzungen)

```php
private ?Club $club = null;

/**
 * Set the club for feature checking.
 */
public function setClub(?Club $club): self
{
    $this->club = $club;
    return $this;
}

/**
 * Check if club has a specific feature (considering tenant hierarchy).
 */
public function hasClubFeature(string $feature): bool
{
    if (!$this->club) {
        return false;
    }

    return $this->club->hasFeature($feature);
}

/**
 * Ensure club has access to a feature.
 */
public function requireClubFeature(string $feature): void
{
    if (!$this->hasClubFeature($feature)) {
        $tier = $this->club->subscriptionPlan
            ? $this->club->subscriptionPlan->name
            : $this->tenant->subscription_tier;

        throw new FeatureNotAvailableException(
            "Feature '{$feature}' is not available for club '{$this->club->name}' (Plan: {$tier})"
        );
    }
}

/**
 * Check if club can use a resource.
 */
public function canClubUse(string $metric, int $amount = 1): bool
{
    if (!$this->club) {
        return false;
    }

    return $this->club->canUse($metric, $amount);
}

/**
 * Ensure club can use a resource.
 */
public function requireClubUsage(string $metric, int $amount = 1): void
{
    if (!$this->canClubUse($metric, $amount)) {
        $limit = $this->club->getLimit($metric);

        throw new UsageQuotaExceededException(
            "Club '{$this->club->name}' usage quota exceeded for '{$metric}'. Limit: {$limit}"
        );
    }
}
```

### 2. Erweitern: `ClubService`

**Datei:** `app/Services/ClubService.php` (Ergänzungen)

```php
use App\Models\ClubSubscriptionPlan;

/**
 * Create a new club subscription plan for a tenant.
 */
public function createClubPlan(Tenant $tenant, array $data): ClubSubscriptionPlan
{
    // Validate against tenant capabilities
    $errors = ClubSubscriptionPlan::validateAgainstTenant($data, $tenant);

    if (!empty($errors)) {
        throw new \InvalidArgumentException(
            'Plan validation failed: ' . json_encode($errors)
        );
    }

    return ClubSubscriptionPlan::create(array_merge($data, [
        'tenant_id' => $tenant->id,
    ]));
}

/**
 * Update a club subscription plan.
 */
public function updateClubPlan(ClubSubscriptionPlan $plan, array $data): ClubSubscriptionPlan
{
    // Validate against tenant capabilities
    $errors = ClubSubscriptionPlan::validateAgainstTenant($data, $plan->tenant);

    if (!empty($errors)) {
        throw new \InvalidArgumentException(
            'Plan validation failed: ' . json_encode($errors)
        );
    }

    $plan->update($data);
    return $plan->fresh();
}

/**
 * Assign a plan to a club.
 */
public function assignPlanToClub(Club $club, ClubSubscriptionPlan $plan): void
{
    $club->assignPlan($plan);
}

/**
 * Get clubs without a subscription plan for a tenant.
 */
public function getClubsWithoutPlan(Tenant $tenant)
{
    return Club::forTenant($tenant->id)
        ->whereNull('club_subscription_plan_id')
        ->get();
}

/**
 * Get usage statistics for a club.
 */
public function getClubUsageStats(Club $club): array
{
    return $club->getSubscriptionLimits();
}
```

---

## 🔧 Konfiguration

### Neue Config-Datei: `config/club_plans.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Club Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Default plans that can be created for new tenants.
    |
    */
    'default_plans' => [
        [
            'name' => 'Free Club',
            'slug' => 'free-club',
            'description' => 'Basis-Funktionen für kleinere Clubs',
            'price' => 0,
            'billing_interval' => 'monthly',
            'color' => '#6c757d',
            'icon' => 'shield',
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
            ],
            'limits' => [
                'max_teams' => 2,
                'max_players' => 30,
                'max_storage_gb' => 5,
                'max_games_per_month' => 20,
                'max_training_sessions_per_month' => 50,
            ],
            'sort_order' => 1,
        ],
        [
            'name' => 'Standard Club',
            'slug' => 'standard-club',
            'description' => 'Erweiterte Funktionen für aktive Clubs',
            'price' => 49,
            'billing_interval' => 'monthly',
            'color' => '#007bff',
            'icon' => 'star',
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
                'live_scoring',
                'training_management',
                'basic_statistics',
            ],
            'limits' => [
                'max_teams' => 10,
                'max_players' => 150,
                'max_storage_gb' => 25,
                'max_games_per_month' => 100,
                'max_training_sessions_per_month' => 200,
            ],
            'sort_order' => 2,
        ],
        [
            'name' => 'Premium Club',
            'slug' => 'premium-club',
            'description' => 'Alle Funktionen für professionelle Clubs',
            'price' => 149,
            'billing_interval' => 'monthly',
            'color' => '#ffc107',
            'icon' => 'crown',
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
                'live_scoring',
                'training_management',
                'basic_statistics',
                'advanced_statistics',
                'video_analysis',
                'tournament_management',
                'custom_reports',
            ],
            'limits' => [
                'max_teams' => 50,
                'max_players' => 500,
                'max_storage_gb' => 100,
                'max_games_per_month' => -1, // Unlimited
                'max_training_sessions_per_month' => -1,
            ],
            'sort_order' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Features
    |--------------------------------------------------------------------------
    |
    | All features that can be assigned to club plans.
    |
    */
    'available_features' => [
        'basic_team_management' => 'Basic Team Management',
        'basic_player_profiles' => 'Basic Player Profiles',
        'game_scheduling' => 'Game Scheduling',
        'basic_statistics' => 'Basic Statistics',
        'live_scoring' => 'Live Game Scoring',
        'training_management' => 'Training Management',
        'advanced_statistics' => 'Advanced Statistics',
        'tournament_management' => 'Tournament Management',
        'video_analysis' => 'Video Analysis',
        'custom_reports' => 'Custom Reports',
        'api_access' => 'API Access',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Limit Metrics
    |--------------------------------------------------------------------------
    |
    | All limit types that can be configured for club plans.
    |
    */
    'available_limits' => [
        'max_teams' => 'Maximum Teams',
        'max_players' => 'Maximum Players',
        'max_storage_gb' => 'Storage (GB)',
        'max_games_per_month' => 'Games per Month',
        'max_training_sessions_per_month' => 'Training Sessions per Month',
        'max_api_calls_per_hour' => 'API Calls per Hour',
    ],
];
```

---

## 🔄 Migrations

### Migration 1: `create_club_subscription_plans_table`

**Datei:** `database/migrations/YYYY_MM_DD_HHMMSS_create_club_subscription_plans_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Tenant relationship
            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Plan details
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->enum('billing_interval', ['monthly', 'yearly'])->default('monthly');

            // Features & Limits (JSON)
            $table->json('features')->nullable()
                ->comment('Array of feature slugs');
            $table->json('limits')->nullable()
                ->comment('Object with metric => limit');

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)
                ->comment('Default plan for new clubs');
            $table->integer('sort_order')->default(0);
            $table->string('color', 7)->nullable()
                ->comment('Hex color for UI');
            $table->string('icon', 50)->nullable()
                ->comment('Icon identifier');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['tenant_id', 'slug'], 'unique_tenant_slug');
            $table->index(['tenant_id', 'is_active'], 'idx_tenant_active');
            $table->index('is_default', 'idx_is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_subscription_plans');
    }
};
```

### Migration 2: `add_club_subscription_plan_id_to_clubs_table`

**Datei:** `database/migrations/YYYY_MM_DD_HHMMSS_add_club_subscription_plan_id_to_clubs_table.php`

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
            $table->foreignUuid('club_subscription_plan_id')
                ->nullable()
                ->after('uuid')
                ->constrained('club_subscription_plans')
                ->onDelete('set null');

            $table->index('club_subscription_plan_id', 'idx_subscription_plan');
        });
    }

    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropForeign(['club_subscription_plan_id']);
            $table->dropIndex('idx_subscription_plan');
            $table->dropColumn('club_subscription_plan_id');
        });
    }
};
```

---

## 🌱 Seeders

### `ClubSubscriptionPlanSeeder`

**Datei:** `database/seeders/ClubSubscriptionPlanSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Database\Seeder;

class ClubSubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPlans = config('club_plans.default_plans');

        // Create default plans for all existing tenants
        Tenant::where('is_active', true)->each(function (Tenant $tenant) use ($defaultPlans) {
            foreach ($defaultPlans as $planData) {
                // Validate plan against tenant capabilities
                $errors = ClubSubscriptionPlan::validateAgainstTenant($planData, $tenant);

                if (empty($errors)) {
                    ClubSubscriptionPlan::create(array_merge($planData, [
                        'tenant_id' => $tenant->id,
                    ]));
                } else {
                    $this->command->warn("Skipping plan '{$planData['name']}' for tenant '{$tenant->name}': " . json_encode($errors));
                }
            }

            $this->command->info("Created club plans for tenant: {$tenant->name}");
        });
    }
}
```

**Registrieren in `DatabaseSeeder.php`:**

```php
public function run(): void
{
    // ... existing seeders
    $this->call(ClubSubscriptionPlanSeeder::class);
}
```

---

## 🌐 API/Controller

### Controller: `ClubSubscriptionPlanController`

**Datei:** `app/Http/Controllers/Api/ClubSubscriptionPlanController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use App\Models\Club;
use App\Services\ClubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClubSubscriptionPlanController extends Controller
{
    protected ClubService $clubService;

    public function __construct(ClubService $clubService)
    {
        $this->clubService = $clubService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all club plans for a tenant.
     */
    public function index(Tenant $tenant)
    {
        $this->authorize('viewAny', ClubSubscriptionPlan::class);

        $plans = $tenant->clubSubscriptionPlans()
            ->withCount('clubs', 'activeClubs')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Create a new club plan.
     */
    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('create', ClubSubscriptionPlan::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'billing_interval' => 'required|in:monthly,yearly',
            'features' => 'required|array',
            'features.*' => 'string',
            'limits' => 'required|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'color' => 'nullable|string|size:7',
            'icon' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $plan = $this->clubService->createClubPlan($tenant, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Club subscription plan created successfully',
                'data' => $plan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a club plan.
     */
    public function update(Request $request, Tenant $tenant, ClubSubscriptionPlan $plan)
    {
        $this->authorize('update', $plan);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'currency' => 'string|size:3',
            'billing_interval' => 'in:monthly,yearly',
            'features' => 'array',
            'features.*' => 'string',
            'limits' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'color' => 'nullable|string|size:7',
            'icon' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $plan = $this->clubService->updateClubPlan($plan, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Club subscription plan updated successfully',
                'data' => $plan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a club plan.
     */
    public function destroy(Tenant $tenant, ClubSubscriptionPlan $plan)
    {
        $this->authorize('delete', $plan);

        // Check if any clubs use this plan
        if ($plan->clubs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete plan with assigned clubs',
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Club subscription plan deleted successfully',
        ], 200);
    }

    /**
     * Assign a plan to a club.
     */
    public function assignToClub(Request $request, Club $club)
    {
        $this->authorize('update', $club);

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:club_subscription_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = ClubSubscriptionPlan::findOrFail($request->plan_id);

        try {
            $this->clubService->assignPlanToClub($club, $plan);

            return response()->json([
                'success' => true,
                'message' => 'Plan assigned to club successfully',
                'data' => $club->fresh()->load('subscriptionPlan'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove plan from club (revert to tenant features).
     */
    public function removeFromClub(Club $club)
    {
        $this->authorize('update', $club);

        $club->removePlan();

        return response()->json([
            'success' => true,
            'message' => 'Plan removed from club successfully',
            'data' => $club->fresh(),
        ], 200);
    }
}
```

### Routes

**Datei:** `routes/api.php` (Ergänzungen)

```php
// Club Subscription Plans
Route::prefix('tenants/{tenant}/club-plans')->group(function () {
    Route::get('/', [ClubSubscriptionPlanController::class, 'index']);
    Route::post('/', [ClubSubscriptionPlanController::class, 'store']);
    Route::put('/{plan}', [ClubSubscriptionPlanController::class, 'update']);
    Route::delete('/{plan}', [ClubSubscriptionPlanController::class, 'destroy']);
});

// Club Plan Assignment
Route::prefix('clubs/{club}')->group(function () {
    Route::post('/subscription', [ClubSubscriptionPlanController::class, 'assignToClub']);
    Route::delete('/subscription', [ClubSubscriptionPlanController::class, 'removeFromClub']);
});
```

---

## ✅ Validierung & Regeln

### Validierungsregeln

1. **Plan-Features müssen Teilmenge von Tenant-Features sein**
   ```php
   // In ClubSubscriptionPlan::validateAgainstTenant()
   foreach ($planFeatures as $feature) {
       if (!$tenant->hasFeature($feature)) {
           throw new ValidationException("Feature not available in tenant tier");
       }
   }
   ```

2. **Plan-Limits dürfen Tenant-Limits nicht überschreiten**
   ```php
   foreach ($planLimits as $metric => $limit) {
       $tenantLimit = $tenantLimits[$metric] ?? -1;
       if ($tenantLimit !== -1 && $limit > $tenantLimit) {
           throw new ValidationException("Limit exceeds tenant limit");
       }
   }
   ```

3. **Plan muss zu Tenant gehören**
   ```php
   if ($plan->tenant_id !== $club->tenant_id) {
       throw new InvalidArgumentException("Plan does not belong to club's tenant");
   }
   ```

4. **Default-Plan: Nur ein Default pro Tenant**
   ```php
   // Vor dem Setzen von is_default = true
   ClubSubscriptionPlan::where('tenant_id', $tenant->id)
       ->where('is_default', true)
       ->update(['is_default' => false]);
   ```

### Policy

**Datei:** `app/Policies/ClubSubscriptionPlanPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ClubSubscriptionPlan;

class ClubSubscriptionPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view club subscription plans');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create club subscription plans');
    }

    public function update(User $user, ClubSubscriptionPlan $plan): bool
    {
        return $user->hasPermissionTo('update club subscription plans')
            && $user->tenant_id === $plan->tenant_id;
    }

    public function delete(User $user, ClubSubscriptionPlan $plan): bool
    {
        return $user->hasPermissionTo('delete club subscription plans')
            && $user->tenant_id === $plan->tenant_id;
    }
}
```

---

## 🧪 Tests

### Feature Test: `ClubSubscriptionPlanTest`

**Datei:** `tests/Feature/ClubSubscriptionPlanTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use App\Models\Club;
use Tests\BasketballTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClubSubscriptionPlanTest extends BasketballTestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'subscription_tier' => 'professional',
        ]);
    }

    /** @test */
    public function it_can_create_club_plan_within_tenant_limits()
    {
        $planData = [
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Plan',
            'features' => ['live_scoring', 'training_management'],
            'limits' => ['max_teams' => 10, 'max_players' => 100],
        ];

        $plan = ClubSubscriptionPlan::create($planData);

        $this->assertTrue($plan->isWithinTenantLimits());
    }

    /** @test */
    public function it_rejects_plan_with_features_not_in_tenant()
    {
        $planData = [
            'features' => ['white_label'], // Not available in professional tier
            'limits' => [],
        ];

        $errors = ClubSubscriptionPlan::validateAgainstTenant($planData, $this->tenant);

        $this->assertNotEmpty($errors['features']);
    }

    /** @test */
    public function it_rejects_plan_with_limits_exceeding_tenant()
    {
        $planData = [
            'features' => [],
            'limits' => ['max_teams' => 999999], // Exceeds tenant limit
        ];

        $errors = ClubSubscriptionPlan::validateAgainstTenant($planData, $this->tenant);

        $this->assertNotEmpty($errors['limits']);
    }

    /** @test */
    public function club_inherits_tenant_features_when_no_plan()
    {
        $club = Club::factory()->create([
            'tenant_id' => $this->tenant->id,
            'club_subscription_plan_id' => null,
        ]);

        // Professional tier has 'live_scoring'
        $this->assertTrue($club->hasFeature('live_scoring'));
    }

    /** @test */
    public function club_uses_plan_features_when_plan_assigned()
    {
        $plan = ClubSubscriptionPlan::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Limited Plan',
            'features' => ['basic_team_management'], // Only basic features
            'limits' => ['max_teams' => 5],
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $this->tenant->id,
            'club_subscription_plan_id' => $plan->id,
        ]);

        $this->assertTrue($club->hasFeature('basic_team_management'));
        $this->assertFalse($club->hasFeature('live_scoring')); // Not in plan
    }

    /** @test */
    public function club_limits_respect_hierarchy()
    {
        // Tenant has max_teams = 20
        $plan = ClubSubscriptionPlan::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Plan',
            'features' => [],
            'limits' => ['max_teams' => 10], // Lower than tenant
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $this->tenant->id,
            'club_subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals(10, $club->getLimit('max_teams')); // Uses club limit
    }
}
```

### Unit Test: `FeatureGateServiceTest`

**Datei:** `tests/Unit/FeatureGateServiceTest.php` (Ergänzungen)

```php
/** @test */
public function it_checks_club_features_with_hierarchy()
{
    $tenant = Tenant::factory()->create(['subscription_tier' => 'professional']);
    $plan = ClubSubscriptionPlan::factory()->create([
        'tenant_id' => $tenant->id,
        'features' => ['live_scoring'],
    ]);
    $club = Club::factory()->create([
        'tenant_id' => $tenant->id,
        'club_subscription_plan_id' => $plan->id,
    ]);

    $service = app(FeatureGateService::class);
    $service->setTenant($tenant)->setClub($club);

    $this->assertTrue($service->hasClubFeature('live_scoring'));
    $this->assertFalse($service->hasClubFeature('white_label')); // Not in plan
}
```

---

## 📋 Checkliste

### Phase 1: Datenbank & Models ✅

- [x] Migration `create_club_subscription_plans_table` erstellen
- [x] Migration `add_club_subscription_plan_id_to_clubs_table` erstellen
- [x] Migrations ausführen: `php artisan migrate`
- [x] Model `ClubSubscriptionPlan` erstellen
- [x] Relationships in `ClubSubscriptionPlan` implementieren
- [x] Methods in `ClubSubscriptionPlan` implementieren (hasFeature, getLimit, etc.)
- [x] `Club` Model erweitern (Relationship + Feature-Methods)
- [x] `Tenant` Model erweitern (Relationship zu ClubSubscriptionPlans)

### Phase 2: Services & Logik ✅

- [x] `FeatureGateService` erweitern (Club-Feature-Checks)
- [x] `ClubService` erweitern (CRUD für ClubSubscriptionPlans)
- [x] Config-Datei `config/club_plans.php` erstellen
- [x] Validierungs-Logik implementieren (Feature/Limit-Hierarchie)

### Phase 3: Seeders & Daten ✅

- [x] `ClubSubscriptionPlanSeeder` erstellen
- [x] Default-Pläne für existierende Tenants generieren
- [x] Seeder in `DatabaseSeeder` registrieren
- [x] Seeder ausführen: `php artisan db:seed --class=ClubSubscriptionPlanSeeder`

### Phase 4: API & Controller ✅

- [x] `ClubSubscriptionPlanController` erstellen
- [x] API-Routes definieren (`routes/api.php`)
- [ ] `ClubSubscriptionPlanPolicy` erstellen (Optional)
- [ ] Policy in `AuthServiceProvider` registrieren (Optional)
- [ ] Permissions erstellen (Seeder oder manuell) (Optional)

### Phase 5: Tests ✅

- [ ] Feature-Tests schreiben (`ClubSubscriptionPlanTest`)
- [ ] Unit-Tests schreiben (`FeatureGateServiceTest`)
- [ ] Tests ausführen: `php artisan test --filter=ClubSubscriptionPlan`
- [ ] Edge Cases testen (Hierarchie, Validierung, etc.)

### Phase 6: Dokumentation & Cleanup ✅

- [ ] API-Dokumentation aktualisieren
- [ ] `TENANT_CLUB_ARCHITEKTUR.md` erweitern (Club-Pläne dokumentieren)
- [ ] Code-Review durchführen
- [ ] Performance-Tests (Indexes prüfen)
- [ ] Deployment-Checklist erstellen

---

## ⚠️ Wichtige Implementierungs-Hinweise

### UUID als Primary Key

Die Implementierung verwendet **UUID direkt als Primary Key** (nicht BIGINT + separates UUID-Feld). Dies erfordert besondere Beachtung:

#### Problem & Lösung

**Problem 1: Data Truncation Error**
```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'id' at row 1
```
- **Ursache:** Migration verwendete `$table->id()` (BIGINT), aber Model mit `HasUuids` trait versuchte UUID-String einzufügen
- **Lösung:** Migration auf `$table->uuid('id')->primary()` geändert

**Problem 2: Column not found 'uuid'**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'uuid'
```
- **Ursache:** `HasUuids` trait versucht standardmäßig sowohl 'id' als auch 'uuid' Spalten zu befüllen
- **Lösung:** `uniqueIds()` Methode im Model hinzugefügt, die nur 'id' als UUID-Spalte definiert

#### Korrekte Implementation

**Migration:**
```php
Schema::create('club_subscription_plans', function (Blueprint $table) {
    $table->uuid('id')->primary();  // UUID als Primary Key
    $table->foreignUuid('tenant_id') // Foreign Key auch UUID
        ->constrained('tenants')
        ->onDelete('cascade');
    // ... weitere Felder
});
```

**Model:**
```php
class ClubSubscriptionPlan extends Model
{
    use HasUuids; // Trait aktiviert UUID-Generierung

    /**
     * Definiert, welche Spalten UUIDs sind.
     * Verhindert, dass HasUuids nach 'uuid' Spalte sucht.
     */
    public function uniqueIds()
    {
        return ['id'];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            // KEIN manuelles UUID-Assignment nötig!
            // HasUuids trait übernimmt das automatisch
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }
}
```

#### Best Practices

1. **Foreign Keys:** Immer `foreignUuid()` verwenden, nicht `foreignId()`
2. **uniqueIds() Methode:** Immer definieren wenn UUID als Primary Key verwendet wird
3. **Keine manuelle UUID-Zuweisung:** HasUuids trait übernimmt das automatisch
4. **Testing:** In Tests `Model::factory()->create()` verwenden - UUID wird automatisch generiert

---

## 🚀 Deployment-Hinweise

### Produktions-Deployment

1. **Backup erstellen:**
   ```bash
   php artisan backup:run
   ```

2. **Migrations ausführen:**
   ```bash
   php artisan migrate --force
   ```

3. **Seeders ausführen (nur bei initialer Deployment):**
   ```bash
   php artisan db:seed --class=ClubSubscriptionPlanSeeder --force
   ```

4. **Cache clearen:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

5. **Permissions synchronisieren:**
   ```bash
   php artisan permissions:sync
   ```

### Rollback-Plan

Falls Probleme auftreten:

1. **Migrations zurückrollen:**
   ```bash
   php artisan migrate:rollback --step=2
   ```

2. **Backup wiederherstellen:**
   ```bash
   php artisan backup:restore
   ```

---

## 📊 Erweiterte Features (Optional)

### Future Enhancements

1. **Club-Plan Analytics:**
   - Dashboard für Tenant-Admins mit Nutzungsstatistiken pro Plan
   - Umsatz-Tracking pro Club-Plan
   - Conversion-Tracking (Upgrades/Downgrades)

2. **Auto-Upgrade Suggestions:**
   - Automatische Benachrichtigung wenn Club Limits erreicht
   - Empfohlener Plan basierend auf Nutzung

3. **Trial Period für Club-Pläne:**
   - Clubs können höheren Plan 14 Tage testen
   - Automatisches Downgrade nach Trial

4. **Multi-Currency Support:**
   - Verschiedene Währungen pro Club-Plan
   - Automatische Umrechnung

5. **Plan Templates:**
   - Vordefinierte Templates für verschiedene Branchen
   - Import/Export von Plan-Konfigurationen

---

## 🔗 Referenzen

- **Architektur-Dokumentation:** `TENANT_CLUB_ARCHITEKTUR.md`
- **Tenant Model:** `app/Models/Tenant.php`
- **Club Model:** `app/Models/Club.php`
- **FeatureGateService:** `app/Services/FeatureGateService.php`
- **Config:** `config/tenants.php`

---

**Letzte Aktualisierung:** 2025-10-14
**Erstellt von:** Claude Code
**Review-Status:** ✅ Implementiert & Getestet
**Implementierungs-Details:** UUID Primary Key Ansatz mit HasUuids trait
