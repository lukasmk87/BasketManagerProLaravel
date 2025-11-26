<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Exceptions\TenantSuspendedException;
use App\Exceptions\TenantTrialExpiredException;
use App\Exceptions\TenantRateLimitExceededException;
use App\Exceptions\FeatureNotAvailableException;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'billing_email',
        'billing_name',
        'billing_address',
        'vat_number',
        'country_code',
        'timezone',
        'locale',
        'currency',
        'subscription_tier',
        'subscription_plan_id',
        'trial_ends_at',
        'is_active',
        'is_suspended',
        'suspension_reason',
        'features',
        'settings',
        'branding',
        'security_settings',
        'max_users',
        'max_teams',
        'max_storage_gb',
        'max_api_calls_per_hour',
        'current_users_count',
        'current_teams_count',
        'current_storage_gb',
        'database_name',
        'database_host',
        'database_port',
        'database_password',
        'schema_name',
        'api_key',
        'api_secret',
        'webhook_url',
        'webhook_secret',
        'allowed_domains',
        'blocked_ips',
        'last_login_at',
        'last_activity_at',
        'total_logins',
        'total_revenue',
        'monthly_recurring_revenue',
        'gdpr_accepted',
        'gdpr_accepted_at',
        'terms_accepted',
        'terms_accepted_at',
        'data_retention_policy',
        'data_processing_agreement_signed',
        'created_by',
        'onboarded_by',
        'onboarded_at',
        'notes',
        'tags',
        'subscription_status',
        'payment_status',
        'payment_failed_at',
        'last_payment_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'database_password',
        'api_secret',
        'webhook_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_suspended' => 'boolean',
        'features' => 'array',
        'settings' => 'encrypted:array',
        'branding' => 'array',
        'security_settings' => 'encrypted:array',
        'allowed_domains' => 'array',
        'blocked_ips' => 'array',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'gdpr_accepted' => 'boolean',
        'gdpr_accepted_at' => 'datetime',
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'data_retention_policy' => 'array',
        'data_processing_agreement_signed' => 'boolean',
        'onboarded_at' => 'datetime',
        'tags' => 'array',
        'current_storage_gb' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'monthly_recurring_revenue' => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
            
            if (empty($tenant->api_key)) {
                $tenant->api_key = 'tk_' . Str::random(32);
            }
            
            if (empty($tenant->api_secret)) {
                $tenant->api_secret = Crypt::encryptString('ts_' . Str::random(48));
            }
            
            if (empty($tenant->webhook_secret)) {
                $tenant->webhook_secret = Crypt::encryptString('whsec_' . Str::random(32));
            }
            
            // Set default trial period (14 days)
            if (empty($tenant->trial_ends_at) && $tenant->subscription_tier === 'free') {
                $tenant->trial_ends_at = now()->addDays(14);
            }
        });
        
        static::updated(function ($tenant) {
            // Clear tenant cache when updated
            Cache::forget("tenant:id:{$tenant->id}");
            Cache::forget("tenant:domain:{$tenant->domain}");
            Cache::forget("tenant:subdomain:{$tenant->subdomain}");
            Cache::forget("tenant:slug:{$tenant->slug}");
        });
    }

    /**
     * Get activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'subscription_tier', 'is_active', 'is_suspended'])
            ->logOnlyDirty()
            ->useLogName('tenant')
            ->setDescriptionForEvent(fn(string $eventName) => "Tenant has been {$eventName}");
    }

    /**
     * Resolve tenant from request.
     */
    public static function resolveFromRequest(Request $request): ?self
    {
        $domain = $request->getHost();
        $tenant = static::resolveFromDomain($domain);
        
        if (!$tenant) {
            // Try to resolve from subdomain
            $subdomain = explode('.', $domain)[0];
            $tenant = static::resolveFromSubdomain($subdomain);
        }
        
        if (!$tenant) {
            return null;
        }
        
        // Security validations
        if (!$tenant->is_active) {
            throw new TenantSuspendedException('Tenant account is suspended: ' . $tenant->suspension_reason);
        }
        
        if ($tenant->is_suspended) {
            throw new TenantSuspendedException('Tenant account is temporarily suspended');
        }
        
        // Check trial expiration
        if ($tenant->isTrialExpired() && !$tenant->hasActiveSubscription()) {
            throw new TenantTrialExpiredException('Trial period has expired. Please upgrade your subscription.');
        }
        
        // Check IP restrictions
        if (!$tenant->isIpAllowed($request->ip())) {
            abort(403, 'Access denied from this IP address');
        }
        
        // Update last activity
        $tenant->update(['last_activity_at' => now()]);
        
        return $tenant;
    }

    /**
     * Resolve tenant from domain.
     */
    public static function resolveFromDomain(string $domain): ?self
    {
        return Cache::remember(
            "tenant:domain:{$domain}",
            3600,
            fn() => static::where('domain', $domain)
                         ->where('is_active', true)
                         ->first()
        );
    }

    /**
     * Resolve tenant from subdomain.
     */
    public static function resolveFromSubdomain(string $subdomain): ?self
    {
        return Cache::remember(
            "tenant:subdomain:{$subdomain}",
            3600,
            fn() => static::where('subdomain', $subdomain)
                         ->where('is_active', true)
                         ->first()
        );
    }

    /**
     * Resolve tenant from slug.
     */
    public static function resolveFromSlug(string $slug): ?self
    {
        return Cache::remember(
            "tenant:slug:{$slug}",
            3600,
            fn() => static::where('slug', $slug)
                         ->where('is_active', true)
                         ->first()
        );
    }

    /**
     * Resolve the default tenant based on configuration.
     * Priority: 1) Domain from request, 2) Config default domain, 3) First active tenant
     */
    public static function resolveDefaultTenant(?string $currentDomain = null): ?self
    {
        // 1. Try to resolve from current domain
        if ($currentDomain) {
            $tenant = static::resolveFromDomain($currentDomain);
            if ($tenant) {
                return $tenant;
            }
        }

        // 2. Try configured default domain
        $defaultDomain = config('tenants.resolution.default_tenant_domain');
        if ($defaultDomain) {
            $tenant = static::resolveFromDomain($defaultDomain);
            if ($tenant) {
                return $tenant;
            }
        }

        // 3. Fallback: First active tenant
        if (config('tenants.resolution.fallback_to_first_active', true)) {
            return Cache::remember(
                'tenant:default:first_active',
                3600,
                fn() => static::where('is_active', true)->first()
            );
        }

        return null;
    }

    /**
     * Check if tenant has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        // Check subscription tier features
        $tierFeatures = config("tenants.tiers.{$this->subscription_tier}.features", []);
        
        if (in_array($feature, $tierFeatures)) {
            return true;
        }
        
        // Check custom features
        $customFeatures = $this->features ?? [];
        
        return in_array($feature, $customFeatures);
    }

    /**
     * Enforce feature access.
     */
    public function enforceFeatureAccess(string $feature): void
    {
        if (!$this->hasFeature($feature)) {
            throw new FeatureNotAvailableException(
                "Feature '{$feature}' is not available for subscription tier '{$this->subscription_tier}'"
            );
        }
    }

    /**
     * Get subscription tier limits.
     */
    public function getTierLimits(): array
    {
        return config("tenants.tiers.{$this->subscription_tier}.limits", [
            'users' => $this->max_users,
            'teams' => $this->max_teams,
            'storage_gb' => $this->max_storage_gb,
            'api_calls_per_hour' => $this->max_api_calls_per_hour,
        ]);
    }

    /**
     * Check if tenant has reached user limit.
     */
    public function hasReachedUserLimit(): bool
    {
        return $this->current_users_count >= $this->max_users;
    }

    /**
     * Check if tenant has reached team limit.
     */
    public function hasReachedTeamLimit(): bool
    {
        return $this->current_teams_count >= $this->max_teams;
    }

    /**
     * Check if tenant has reached storage limit.
     */
    public function hasReachedStorageLimit(): bool
    {
        return $this->current_storage_gb >= $this->max_storage_gb;
    }

    /**
     * Check if trial has expired.
     */
    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Check if tenant has active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_tier !== 'free' || 
               ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    /**
     * Check if IP is allowed.
     */
    public function isIpAllowed(string $ip): bool
    {
        $blockedIps = $this->blocked_ips ?? [];
        
        // Check if IP is blocked
        foreach ($blockedIps as $blockedIp) {
            if ($this->ipMatches($ip, $blockedIp)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if IP matches pattern (supports wildcards and CIDR).
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // Exact match
        if ($ip === $pattern) {
            return true;
        }
        
        // Wildcard match (e.g., 192.168.1.*)
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match('/^' . $pattern . '$/', $ip);
        }
        
        // CIDR match (e.g., 192.168.1.0/24)
        if (strpos($pattern, '/') !== false) {
            list($subnet, $bits) = explode('/', $pattern);
            $subnet = ip2long($subnet);
            $ip = ip2long($ip);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            return ($ip & $mask) == $subnet;
        }
        
        return false;
    }

    /**
     * Get tenant setting.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Update tenant settings.
     */
    public function updateSettings(array $settings): void
    {
        $this->update([
            'settings' => array_merge($this->settings ?? [], $settings)
        ]);
    }

    /**
     * Get data retention policy.
     */
    public function getDataRetentionPolicy(): array
    {
        return $this->data_retention_policy ?? [
            'game_data' => '7 years',
            'player_data' => '10 years',
            'audit_logs' => '3 years',
            'media_files' => '5 years',
            'anonymize_after' => '1 year after last activity',
        ];
    }

    /**
     * Setup tenant database connection.
     */
    public function setupDatabaseConnection(): void
    {
        if ($this->database_name) {
            config([
                'database.connections.tenant' => [
                    'driver' => 'mysql',
                    'host' => $this->database_host ?? config('database.connections.mysql.host'),
                    'port' => $this->database_port ?? config('database.connections.mysql.port'),
                    'database' => $this->database_name,
                    'username' => config('database.connections.mysql.username'),
                    'password' => $this->database_password ? 
                        Crypt::decryptString($this->database_password) : 
                        config('database.connections.mysql.password'),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ]
            ]);
            
            DB::setDefaultConnection('tenant');
        }
    }

    /**
     * Setup PostgreSQL Row Level Security.
     */
    public function setupRowLevelSecurity(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('SET row_security = on');
            DB::statement('SET basketmanager.current_tenant_id = ?', [$this->id]);
        }
    }

    /**
     * Get tenant URL.
     */
    public function getUrl(): string
    {
        if ($this->domain) {
            return "https://{$this->domain}";
        }
        
        if ($this->subdomain) {
            $baseDomain = config('app.base_domain', 'basketmanager-pro.com');
            return "https://{$this->subdomain}.{$baseDomain}";
        }
        
        return url("/tenant/{$this->slug}");
    }

    /**
     * Relationships
     */
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function trainingSessions()
    {
        return $this->hasManyThrough(TrainingSession::class, Team::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function clubs()
    {
        return $this->hasMany(Club::class);
    }
    
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
    
    public function apiUsage()
    {
        return $this->hasMany(ApiUsageTracking::class);
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function onboarder()
    {
        return $this->belongsTo(User::class, 'onboarded_by');
    }
    
    public function webhookEvents()
    {
        return $this->hasMany(WebhookEvent::class);
    }
    
    public function dbbIntegrations()
    {
        return $this->hasMany(DBBIntegration::class);
    }
    
    public function fibaIntegrations()
    {
        return $this->hasMany(FIBAIntegration::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function planCustomizations()
    {
        return $this->hasMany(TenantPlanCustomization::class);
    }

    public function activeCustomization()
    {
        return $this->hasOne(TenantPlanCustomization::class)->active();
    }

    /**
     * Get club subscription plans for this tenant.
     */
    public function clubSubscriptionPlans()
    {
        return $this->hasMany(ClubSubscriptionPlan::class);
    }

    /**
     * Get active club subscription plans.
     */
    public function activeClubPlans()
    {
        return $this->clubSubscriptionPlans()->active();
    }

    /**
     * Get default club subscription plan.
     */
    public function defaultClubPlan()
    {
        return $this->clubSubscriptionPlans()->default()->first();
    }

    /**
     * Stripe Integration Methods
     */
    
    /**
     * Get Stripe configuration for this tenant.
     */
    public function getStripeConfig(): array
    {
        return $this->getSetting('stripe', [
            'mode' => 'test',
            'publishable_key' => null,
            'secret_key' => null,
            'webhook_secret' => null,
            'account_id' => null, // For Stripe Connect
        ]);
    }

    /**
     * Update Stripe configuration for this tenant.
     */
    public function updateStripeConfig(array $config): void
    {
        $currentConfig = $this->getStripeConfig();
        $newConfig = array_merge($currentConfig, $config);
        
        $this->updateSettings(['stripe' => $newConfig]);
    }

    /**
     * Check if tenant has valid Stripe configuration.
     */
    public function hasValidStripeConfig(): bool
    {
        $config = $this->getStripeConfig();
        
        return !empty($config['publishable_key']) && !empty($config['secret_key']);
    }

    /**
     * Get Stripe Price ID for current subscription tier.
     */
    public function getStripePriceId(): ?string
    {
        return config("stripe.subscriptions.tiers.{$this->subscription_tier}");
    }

    /**
     * Check if tenant can use a specific payment feature.
     */
    public function canUsePaymentFeature(string $feature): bool
    {
        $tierFeatures = config("stripe.subscriptions.features.{$this->subscription_tier}", []);
        
        return in_array($feature, $tierFeatures) || $this->hasFeature($feature);
    }

    /**
     * Get payment limits for current tier.
     */
    public function getPaymentLimits(): array
    {
        $baseLimits = $this->getTierLimits();
        
        return array_merge($baseLimits, [
            'monthly_payment_volume' => $this->getMonthlyPaymentVolumeLimit(),
            'transaction_fee_percentage' => $this->getTransactionFeePercentage(),
            'supported_payment_methods' => $this->getSupportedPaymentMethods(),
        ]);
    }

    /**
     * Get monthly payment volume limit based on subscription tier.
     */
    private function getMonthlyPaymentVolumeLimit(): int
    {
        return match($this->subscription_tier) {
            'free' => 1000, // €1,000
            'basic' => 10000, // €10,000
            'professional' => 100000, // €100,000
            'enterprise' => -1, // Unlimited
            default => 1000,
        };
    }

    /**
     * Get transaction fee percentage for this tenant.
     */
    private function getTransactionFeePercentage(): float
    {
        // Platform fee for shared Stripe account mode
        return match($this->subscription_tier) {
            'free' => 3.5,
            'basic' => 2.9,
            'professional' => 2.4,
            'enterprise' => 1.9,
            default => 3.5,
        };
    }

    /**
     * Get supported payment methods for current tier.
     */
    private function getSupportedPaymentMethods(): array
    {
        $allMethods = ['card', 'sepa_debit', 'sofort', 'paypal', 'apple_pay', 'google_pay'];
        
        return match($this->subscription_tier) {
            'free' => ['card'],
            'basic' => ['card', 'sepa_debit', 'paypal'],
            'professional' => ['card', 'sepa_debit', 'sofort', 'paypal', 'apple_pay', 'google_pay'],
            'enterprise' => $allMethods,
            default => ['card'],
        };
    }

    /**
     * Calculate subscription cost including taxes.
     */
    public function calculateSubscriptionCost(): array
    {
        $tierConfig = config("tenants.tiers.{$this->subscription_tier}");
        
        if (!$tierConfig || $this->subscription_tier === 'free') {
            return [
                'base_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'currency' => 'EUR',
            ];
        }
        
        $baseAmount = $tierConfig['price'];
        $currency = $tierConfig['currency'];
        $taxRate = $this->getTaxRate();
        
        $taxAmount = round($baseAmount * ($taxRate / 100), 2);
        $totalAmount = $baseAmount + $taxAmount;
        
        return [
            'base_amount' => $baseAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'tax_rate' => $taxRate,
        ];
    }

    /**
     * Get tax rate for this tenant's country.
     */
    private function getTaxRate(): float
    {
        $taxRates = config('stripe.tax.rates', []);
        $countryCode = strtolower($this->country_code);
        
        return $taxRates[$countryCode] ?? 19.0; // Default to German VAT
    }

    /**
     * Check if tenant is eligible for trial.
     */
    public function isEligibleForTrial(): bool
    {
        // Can't have trial if already had one
        if ($this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return false;
        }
        
        // Must be on free tier
        if ($this->subscription_tier !== 'free') {
            return false;
        }
        
        return true;
    }

    /**
     * Start trial for this tenant.
     */
    public function startTrial(int $days = 14): void
    {
        if (!$this->isEligibleForTrial()) {
            throw new \Exception('Tenant is not eligible for trial');
        }
        
        $this->update([
            'trial_ends_at' => now()->addDays($days),
        ]);
        
        Log::info('Trial started for tenant', [
            'tenant_id' => $this->id,
            'trial_days' => $days,
            'trial_ends_at' => $this->trial_ends_at,
        ]);
    }

    /**
     * Upgrade tenant to paid subscription.
     */
    public function upgradeTo(string $tier): void
    {
        if (!in_array($tier, ['basic', 'professional', 'enterprise'])) {
            throw new \Exception("Invalid subscription tier: {$tier}");
        }
        
        $oldTier = $this->subscription_tier;
        
        $this->update([
            'subscription_tier' => $tier,
            'trial_ends_at' => null, // Remove trial when upgrading to paid
        ]);
        
        // Update limits based on new tier
        $newLimits = config("tenants.tiers.{$tier}.limits");
        $this->update([
            'max_users' => $newLimits['users'],
            'max_teams' => $newLimits['teams'],
            'max_storage_gb' => $newLimits['storage_gb'],
            'max_api_calls_per_hour' => $newLimits['api_calls_per_hour'],
        ]);
        
        Log::info('Tenant upgraded', [
            'tenant_id' => $this->id,
            'old_tier' => $oldTier,
            'new_tier' => $tier,
        ]);
    }

    /**
     * Downgrade tenant subscription.
     */
    public function downgradeTo(string $tier): void
    {
        if (!in_array($tier, ['free', 'basic', 'professional'])) {
            throw new \Exception("Invalid subscription tier: {$tier}");
        }
        
        $oldTier = $this->subscription_tier;
        
        $this->update([
            'subscription_tier' => $tier,
        ]);
        
        // Update limits based on new tier
        if ($tier !== 'free') {
            $newLimits = config("tenants.tiers.{$tier}.limits");
        } else {
            $newLimits = config('tenants.defaults');
        }
        
        $this->update([
            'max_users' => $newLimits['users'] ?? 10,
            'max_teams' => $newLimits['teams'] ?? 5,
            'max_storage_gb' => $newLimits['storage_gb'] ?? 5,
            'max_api_calls_per_hour' => $newLimits['api_calls_per_hour'] ?? 100,
        ]);
        
        Log::info('Tenant downgraded', [
            'tenant_id' => $this->id,
            'old_tier' => $oldTier,
            'new_tier' => $tier,
        ]);
    }

    /**
     * Get tenant usage records.
     */
    public function usages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TenantUsage::class);
    }

    /**
     * Get current period usage.
     */
    public function currentUsage(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->usages()->currentPeriod();
    }

    /**
     * Get usage for a specific metric.
     */
    public function getUsageForMetric(string $metric): int
    {
        return $this->currentUsage()
            ->forMetric($metric)
            ->sum('usage_count');
    }
}