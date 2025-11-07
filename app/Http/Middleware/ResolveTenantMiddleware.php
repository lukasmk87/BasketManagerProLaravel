<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantMiddleware
{
    /**
     * Lazy load TenantService to prevent database access during installation
     */
    public function __construct(
        private ?TenantService $tenantService = null
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant resolution during installation to prevent database access before migrations
        if (!file_exists(storage_path('installed'))
            || file_exists(storage_path('installing'))
            || $request->is('install')
            || $request->is('install/*')) {
            return $next($request);
        }

        // Skip tenant resolution for emergency routes (QR code access)
        if ($request->is('emergency/*')) {
            return $next($request);
        }

        try {
            // Resolve tenant from request
            $tenant = $this->resolveTenant($request);

            if (! $tenant) {
                // For staging environment, create a mock tenant
                if (app()->environment('staging') && $request->getHost() === 'staging.basketmanager-pro.de') {
                    $tenant = $this->createMockStagingTenant();
                }
                // Super-Admins are system users - skip tenant resolution entirely
                elseif ($this->isSuperAdmin($request)) {
                    // Super-Admins operate tenant-independently and see ALL data
                    // They have tenant_id = NULL in database
                    // No tenant context is set (app('tenant') = NULL)

                    // Optional: Check if Super Admin selected a specific tenant filter (session-based)
                    if ($request->hasSession()) {
                        $selectedTenantId = $request->session()->get('super_admin_selected_tenant_id');
                        if ($selectedTenantId) {
                            $selectedTenant = Tenant::where('id', $selectedTenantId)->where('is_active', true)->first();
                            if ($selectedTenant) {
                                // Set tenant context only for filtering purposes (not permanent binding)
                                $tenant = $selectedTenant;
                                // Continue with tenant context setup below
                            } else {
                                // Tenant selection is invalid, clear it
                                $request->session()->forget('super_admin_selected_tenant_id');
                                return $next($request);
                            }
                        } else {
                            // No tenant selected - proceed without tenant context
                            return $next($request);
                        }
                    } else {
                        // No session available - proceed without tenant context
                        return $next($request);
                    }
                } else {
                    return $this->handleTenantNotFound($request);
                }
            }

            // Set tenant context throughout the application
            $this->setTenantContext($tenant);

            // Configure tenant-specific services
            $this->configureTenantServices($tenant);

            // Setup Row Level Security for database queries
            $this->setupRowLevelSecurity($tenant);

            // Log tenant access for analytics
            $this->logTenantAccess($request, $tenant);

            return $next($request);

        } catch (\Exception $e) {
            return $this->handleTenantException($e, $request);
        }
    }

    /**
     * Resolve tenant from request using multiple strategies.
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        $resolutionMethods = config('tenants.resolution.methods', ['domain', 'subdomain', 'header']);

        foreach ($resolutionMethods as $method) {
            $tenant = match ($method) {
                'domain' => $this->resolveFromDomain($request),
                'subdomain' => $this->resolveFromSubdomain($request),
                'header' => $this->resolveFromHeader($request),
                'session' => $this->resolveFromSession($request),
                'api_key' => $this->resolveFromApiKey($request),
                default => null
            };

            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Resolve tenant from domain.
     */
    private function resolveFromDomain(Request $request): ?Tenant
    {
        $domain = $request->getHost();

        return Tenant::resolveFromDomain($domain);
    }

    /**
     * Resolve tenant from subdomain.
     */
    private function resolveFromSubdomain(Request $request): ?Tenant
    {
        $domain = $request->getHost();
        $parts = explode('.', $domain);

        // Check if it's a subdomain (more than 2 parts)
        if (count($parts) > 2) {
            $subdomain = $parts[0];

            return Tenant::resolveFromSubdomain($subdomain);
        }

        return null;
    }

    /**
     * Resolve tenant from header.
     */
    private function resolveFromHeader(Request $request): ?Tenant
    {
        $headerName = config('tenants.resolution.header_name', 'X-Tenant-ID');
        $tenantId = $request->header($headerName);

        if ($tenantId) {
            return Cache::remember(
                "tenant:id:{$tenantId}",
                3600,
                fn () => Tenant::where('id', $tenantId)->where('is_active', true)->first()
            );
        }

        return null;
    }

    /**
     * Resolve tenant from session.
     */
    private function resolveFromSession(Request $request): ?Tenant
    {
        // Skip session resolution for API requests without session support
        if (! $request->hasSession()) {
            return null;
        }

        $sessionKey = config('tenants.resolution.session_key', 'tenant_id');
        $tenantId = $request->session()->get($sessionKey);

        if ($tenantId) {
            return Cache::remember(
                "tenant:id:{$tenantId}",
                3600,
                fn () => Tenant::where('id', $tenantId)->where('is_active', true)->first()
            );
        }

        return null;
    }

    /**
     * Resolve tenant from API key.
     */
    private function resolveFromApiKey(Request $request): ?Tenant
    {
        $apiKey = $request->bearerToken() ?? $request->header('X-API-Key');

        if ($apiKey && str_starts_with($apiKey, 'tk_')) {
            return Cache::remember(
                "tenant:api_key:{$apiKey}",
                1800, // 30 minutes cache for API keys
                fn () => Tenant::where('api_key', $apiKey)->where('is_active', true)->first()
            );
        }

        return null;
    }

    /**
     * Set tenant context throughout the application.
     */
    private function setTenantContext(Tenant $tenant): void
    {
        // Set tenant instance in service container
        app()->instance('tenant', $tenant);
        app()->instance('tenant.id', $tenant->id);

        // Set tenant context for Eloquent queries via global scope
        $this->setEloquentTenantScope($tenant);

        // Share tenant data with all views
        View::share('tenant', $tenant);

        // Set tenant-specific configuration
        config([
            'app.name' => $tenant->name,
            'app.timezone' => $tenant->timezone,
            'app.locale' => $tenant->locale,
        ]);

        // Update application locale
        app()->setLocale($tenant->locale);
    }

    /**
     * Set global Eloquent scope for tenant isolation.
     */
    private function setEloquentTenantScope(Tenant $tenant): void
    {
        // Skip for SQLite (test environment)
        if (config('database.default') === 'sqlite') {
            return;
        }

        // This would typically be handled by a global scope on models
        // For now, we'll store the tenant ID in a way models can access it
        app('db')->connection()->getPdo()->exec("SET @tenant_id = '{$tenant->id}'");
    }

    /**
     * Configure tenant-specific services and settings.
     */
    private function configureTenantServices(Tenant $tenant): void
    {
        // Configure tenant-specific caching
        $this->configureTenantCache($tenant);

        // Configure tenant-specific mail settings
        $this->configureTenantMail($tenant);

        // Configure tenant-specific filesystem
        $this->configureTenantFilesystem($tenant);

        // Apply tenant customizations
        $this->applyTenantCustomizations($tenant);
    }

    /**
     * Configure tenant-specific caching namespace.
     */
    private function configureTenantCache(Tenant $tenant): void
    {
        if (config('tenants.isolation.cache_prefix')) {
            $originalPrefix = config('cache.prefix');
            config(['cache.prefix' => $originalPrefix.":tenant:{$tenant->id}"]);
        }
    }

    /**
     * Configure tenant-specific mail settings.
     */
    private function configureTenantMail(Tenant $tenant): void
    {
        $mailSettings = $tenant->getSetting('mail', []);

        if (! empty($mailSettings)) {
            config([
                'mail.from.address' => $mailSettings['from_address'] ?? config('mail.from.address'),
                'mail.from.name' => $mailSettings['from_name'] ?? $tenant->name,
            ]);
        }
    }

    /**
     * Configure tenant-specific filesystem.
     */
    private function configureTenantFilesystem(Tenant $tenant): void
    {
        // Create tenant-specific disk configuration
        config([
            'filesystems.disks.tenant' => [
                'driver' => 'local',
                'root' => storage_path("app/tenants/{$tenant->id}"),
                'url' => env('APP_URL')."/storage/tenants/{$tenant->id}",
                'visibility' => 'private',
            ],
        ]);
    }

    /**
     * Apply tenant-specific customizations.
     */
    private function applyTenantCustomizations(Tenant $tenant): void
    {
        $branding = $tenant->branding ?? [];

        // Share branding with views
        View::share('branding', $branding);

        // Apply custom CSS variables
        if (! empty($branding)) {
            $customCss = $this->generateCustomCss($branding);
            View::share('customCss', $customCss);
        }
    }

    /**
     * Generate custom CSS from branding settings.
     */
    private function generateCustomCss(array $branding): string
    {
        $css = ':root {';

        if (isset($branding['primary_color'])) {
            $css .= "--primary-color: {$branding['primary_color']};";
        }

        if (isset($branding['secondary_color'])) {
            $css .= "--secondary-color: {$branding['secondary_color']};";
        }

        $css .= '}';

        return $css;
    }

    /**
     * Setup Row Level Security for tenant isolation.
     */
    private function setupRowLevelSecurity(Tenant $tenant): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('SET row_security = on');
            DB::statement('SET basketmanager.current_tenant_id = ?', [$tenant->id]);
        }

        // For MySQL, we can use a session variable
        if (config('database.default') === 'mysql') {
            DB::statement('SET @current_tenant_id = ?', [$tenant->id]);
        }

        // For SQLite (test environment), skip SET commands
        // SQLite doesn't support session variables in the same way
    }

    /**
     * Log tenant access for analytics and security.
     */
    private function logTenantAccess(Request $request, Tenant $tenant): void
    {
        // Skip logging for mock tenants
        if ($tenant->id === 'staging-tenant') {
            return;
        }

        // Lazy load TenantService when needed
        if (!$this->tenantService) {
            $this->tenantService = app(TenantService::class);
        }

        // Only log once per session to avoid spam (skip for API requests without session)
        if ($request->hasSession()) {
            $sessionKey = "tenant_access_logged_{$tenant->id}";

            if (! $request->session()->has($sessionKey)) {
                $this->tenantService->logAccess($tenant, $request);
                $request->session()->put($sessionKey, true);
            }
        } else {
            // For API requests without session, log every time (but rate limit this in service)
            $this->tenantService->logAccess($tenant, $request);
        }

        // Update tenant's last activity timestamp (only for real tenants)
        if ($tenant->exists) {
            $tenant->touch('last_activity_at');
        }
    }

    /**
     * Create mock staging tenant for development.
     */
    private function createMockStagingTenant(): Tenant
    {
        $mockTenant = new Tenant;
        $mockTenant->id = 'staging-tenant';
        $mockTenant->name = 'BasketManager Pro Staging';
        $mockTenant->slug = 'staging';
        $mockTenant->domain = 'staging.basketmanager-pro.de';
        $mockTenant->subscription_tier = 'professional';
        $mockTenant->is_active = true;
        $mockTenant->timezone = 'Europe/Berlin';
        $mockTenant->locale = 'de';
        $mockTenant->currency = 'EUR';
        $mockTenant->country_code = 'DE';
        $mockTenant->exists = true; // Mark as existing to avoid save attempts

        return $mockTenant;
    }

    /**
     * Handle case where tenant is not found.
     */
    private function handleTenantNotFound(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'tenant_not_found',
                'message' => 'Tenant could not be resolved from request',
            ], 404);
        }

        // Redirect to tenant selection or main landing page
        if ($request->is('api/*')) {
            return response()->json([
                'error' => 'tenant_required',
                'message' => 'Please specify a valid tenant in your request',
            ], 400);
        }

        return response()->view('errors.tenant-not-found', [], 404);
    }

    /**
     * Handle tenant-related exceptions.
     */
    private function handleTenantException(\Exception $e, Request $request): Response
    {
        // Log the exception
        logger()->error('Tenant resolution error', [
            'exception' => $e->getMessage(),
            'host' => $request->getHost(),
            'path' => $request->path(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'tenant_error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->view('errors.tenant-error', [
            'message' => $e->getMessage(),
        ], 500);
    }

    /**
     * Check if the authenticated user is a Super-Admin.
     */
    private function isSuperAdmin(Request $request): bool
    {
        // Check if user is authenticated
        if (! $request->user()) {
            return false;
        }

        // Check if user has the super_admin role
        return $request->user()->hasRole('super_admin');
    }

}
