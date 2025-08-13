<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TenantService
{
    /**
     * Create a new tenant.
     */
    public function createTenant(array $data): Tenant
    {
        $tenant = Tenant::create($data);
        
        // Setup initial tenant data
        $this->setupTenantDefaults($tenant);
        
        // Log tenant creation
        Log::info('Tenant created', [
            'tenant_id' => $tenant->id,
            'name' => $tenant->name,
            'tier' => $tenant->subscription_tier,
        ]);
        
        return $tenant;
    }

    /**
     * Setup default data for new tenant.
     */
    private function setupTenantDefaults(Tenant $tenant): void
    {
        // Create tenant directory structure
        $this->createTenantDirectories($tenant);
        
        // Setup default settings based on tier
        $this->setupDefaultSettings($tenant);
        
        // Create sample data if enabled
        if (config('tenants.onboarding.demo_data')) {
            $this->createDemoData($tenant);
        }
    }

    /**
     * Create directory structure for tenant.
     */
    private function createTenantDirectories(Tenant $tenant): void
    {
        $tenantPath = storage_path("app/tenants/{$tenant->id}");
        
        $directories = [
            'media',
            'exports',
            'imports',
            'backups',
            'temp',
        ];
        
        foreach ($directories as $dir) {
            $path = "{$tenantPath}/{$dir}";
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Setup default settings for tenant based on tier.
     */
    private function setupDefaultSettings(Tenant $tenant): void
    {
        $tierConfig = config("tenants.tiers.{$tenant->subscription_tier}");
        
        $defaultSettings = [
            'notifications' => [
                'email' => true,
                'push' => $tenant->subscription_tier !== 'free',
                'sms' => $tenant->subscription_tier === 'enterprise',
            ],
            'privacy' => [
                'public_profile' => false,
                'show_statistics' => true,
                'allow_contact' => $tenant->subscription_tier !== 'free',
            ],
            'features' => $tierConfig['features'] ?? [],
            'limits' => $tierConfig['limits'] ?? [],
        ];
        
        $tenant->updateSettings($defaultSettings);
    }

    /**
     * Create demo data for new tenant.
     */
    private function createDemoData(Tenant $tenant): void
    {
        // This would create sample teams, players, games, etc.
        // Implementation depends on existing models and structure
        Log::info('Demo data creation requested', ['tenant_id' => $tenant->id]);
    }

    /**
     * Log tenant access for analytics.
     */
    public function logAccess(Tenant $tenant, Request $request): void
    {
        $data = [
            'tenant_id' => $tenant->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now(),
        ];
        
        // Store in cache for batch processing later
        $cacheKey = "tenant_access_log_{$tenant->id}";
        $existingLogs = Cache::get($cacheKey, []);
        $existingLogs[] = $data;
        
        // Keep only last 100 access logs in cache
        if (count($existingLogs) > 100) {
            array_shift($existingLogs);
        }
        
        Cache::put($cacheKey, $existingLogs, now()->addHours(24));
        
        // Update tenant login count and last login time
        $tenant->increment('total_logins');
        $tenant->update(['last_login_at' => now()]);
    }

    /**
     * Switch user to different tenant.
     */
    public function switchTenant(Tenant $newTenant, Request $request): bool
    {
        // Verify user has access to the new tenant
        if (!$this->userHasAccessToTenant(auth()->user(), $newTenant)) {
            return false;
        }
        
        // Store new tenant in session
        $sessionKey = config('tenants.resolution.session_key', 'tenant_id');
        $request->session()->put($sessionKey, $newTenant->id);
        
        // Clear old tenant cache
        $request->session()->forget('tenant_access_logged_*');
        
        Log::info('User switched tenant', [
            'user_id' => auth()->id(),
            'new_tenant_id' => $newTenant->id,
        ]);
        
        return true;
    }

    /**
     * Check if user has access to tenant.
     */
    public function userHasAccessToTenant($user, Tenant $tenant): bool
    {
        if (!$user) {
            return false;
        }
        
        // Check if user belongs to tenant
        if ($user->tenant_id === $tenant->id) {
            return true;
        }
        
        // Check if user has multi-tenant access (enterprise feature)
        if ($tenant->hasFeature('multi_tenant_access')) {
            return $user->tenants()->where('tenant_id', $tenant->id)->exists();
        }
        
        return false;
    }

    /**
     * Get tenant statistics.
     */
    public function getTenantStatistics(Tenant $tenant): array
    {
        return [
            'users_count' => $tenant->users()->count(),
            'teams_count' => $tenant->teams()->count(),
            'players_count' => $tenant->players()->count(),
            'games_count' => $tenant->games()->count(),
            'active_tournaments' => $tenant->tournaments()->where('status', 'active')->count(),
            'storage_used_gb' => $this->calculateStorageUsage($tenant),
            'api_calls_this_month' => $this->getApiCallsThisMonth($tenant),
            'last_activity' => $tenant->last_activity_at,
            'subscription_status' => $this->getSubscriptionStatus($tenant),
        ];
    }

    /**
     * Calculate storage usage for tenant.
     */
    private function calculateStorageUsage(Tenant $tenant): float
    {
        $tenantPath = storage_path("app/tenants/{$tenant->id}");
        
        if (!file_exists($tenantPath)) {
            return 0;
        }
        
        $bytes = $this->getDirectorySize($tenantPath);
        return round($bytes / (1024 * 1024 * 1024), 2); // Convert to GB
    }

    /**
     * Get directory size recursively.
     */
    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }

    /**
     * Get API calls count for current month.
     */
    private function getApiCallsThisMonth(Tenant $tenant): int
    {
        return $tenant->apiUsage()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('request_count');
    }

    /**
     * Get subscription status.
     */
    private function getSubscriptionStatus(Tenant $tenant): array
    {
        $status = [
            'tier' => $tenant->subscription_tier,
            'is_trial' => $tenant->subscription_tier === 'free' && $tenant->trial_ends_at,
            'trial_ends_at' => $tenant->trial_ends_at,
            'is_active' => $tenant->is_active,
            'is_suspended' => $tenant->is_suspended,
        ];
        
        if ($tenant->trial_ends_at) {
            $status['trial_days_remaining'] = max(0, now()->diffInDays($tenant->trial_ends_at, false));
            $status['trial_expired'] = $tenant->isTrialExpired();
        }
        
        return $status;
    }

    /**
     * Check tenant limits and usage.
     */
    public function checkTenantLimits(Tenant $tenant): array
    {
        $limits = $tenant->getTierLimits();
        $usage = [
            'users' => $tenant->current_users_count,
            'teams' => $tenant->current_teams_count,
            'storage_gb' => $tenant->current_storage_gb,
            'api_calls_per_hour' => $this->getApiCallsLastHour($tenant),
        ];
        
        $status = [];
        
        foreach ($limits as $key => $limit) {
            if ($limit === -1) { // Unlimited
                $status[$key] = [
                    'limit' => 'unlimited',
                    'usage' => $usage[$key] ?? 0,
                    'percentage' => 0,
                    'exceeded' => false,
                ];
            } else {
                $currentUsage = $usage[$key] ?? 0;
                $percentage = $limit > 0 ? ($currentUsage / $limit) * 100 : 0;
                
                $status[$key] = [
                    'limit' => $limit,
                    'usage' => $currentUsage,
                    'percentage' => round($percentage, 1),
                    'exceeded' => $currentUsage >= $limit,
                ];
            }
        }
        
        return $status;
    }

    /**
     * Get API calls in the last hour.
     */
    private function getApiCallsLastHour(Tenant $tenant): int
    {
        return $tenant->apiUsage()
            ->where('created_at', '>=', now()->subHour())
            ->sum('request_count');
    }

    /**
     * Update tenant usage counters.
     */
    public function updateTenantCounters(Tenant $tenant): void
    {
        $tenant->update([
            'current_users_count' => $tenant->users()->count(),
            'current_teams_count' => $tenant->teams()->count(),
            'current_storage_gb' => $this->calculateStorageUsage($tenant),
        ]);
    }

    /**
     * Suspend tenant.
     */
    public function suspendTenant(Tenant $tenant, string $reason): bool
    {
        $tenant->update([
            'is_active' => false,
            'is_suspended' => true,
            'suspension_reason' => $reason,
        ]);
        
        // Clear tenant cache
        $this->clearTenantCache($tenant);
        
        Log::warning('Tenant suspended', [
            'tenant_id' => $tenant->id,
            'reason' => $reason,
        ]);
        
        return true;
    }

    /**
     * Reactivate tenant.
     */
    public function reactivateTenant(Tenant $tenant): bool
    {
        $tenant->update([
            'is_active' => true,
            'is_suspended' => false,
            'suspension_reason' => null,
        ]);
        
        Log::info('Tenant reactivated', [
            'tenant_id' => $tenant->id,
        ]);
        
        return true;
    }

    /**
     * Clear all cached data for tenant.
     */
    public function clearTenantCache(Tenant $tenant): void
    {
        $cacheKeys = [
            "tenant:domain:{$tenant->domain}",
            "tenant:subdomain:{$tenant->subdomain}",
            "tenant:slug:{$tenant->slug}",
            "tenant:id:{$tenant->id}",
            "tenant:api_key:{$tenant->api_key}",
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // Clear tenant-tagged cache
        Cache::tags(["tenant:{$tenant->id}"])->flush();
    }
}