<?php

if (!function_exists('app_name')) {
    /**
     * Get the application name with tenant-aware fallback.
     *
     * Fallback hierarchy:
     * 1. Current tenant's app_name (if set and tenant exists in request)
     * 2. APP_NAME from .env
     * 3. 'BasketManager Pro' (default)
     *
     * @return string
     */
    function app_name(): string
    {
        // Skip tenant resolution during installation
        if (!file_exists(storage_path('installed')) || file_exists(storage_path('installing'))) {
            return config('app.name', 'BasketManager Pro');
        }

        // Try to get tenant-specific app name from request attributes
        // This only works when a request context is available
        try {
            if (app()->has('request')) {
                $request = app('request');
                $tenant = $request->attributes->get('tenant');

                if ($tenant && !empty($tenant->app_name)) {
                    return $tenant->app_name;
                }
            }
        } catch (\Exception $e) {
            // Request not available (e.g., during boot, console commands, or tests)
            // Fall through to default configuration
        }

        // Fallback to APP_NAME from .env or default
        return config('app.name', 'BasketManager Pro');
    }
}
