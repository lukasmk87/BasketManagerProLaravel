<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /**
     * Routes that should be excluded from onboarding check.
     */
    protected array $excludedRoutes = [
        'onboarding.*',
        'logout',
        'api.*',
        'webhooks.*',
        'livewire.*',
        'ignition.*',
        'sanctum.*',
        'telescope*',
        'pulse*',
        'profile.*',
        '*.profile.*',
        'password.*',
        '*.password.*',
        'two-factor.*',
        '*.two-factor.*',
        'verification.*',
        '*.verification.*',
        'email.*',
        '*.email.*',
        'current-user*',
        'user-profile-information*',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip for unauthenticated users
        if (!$user) {
            return $next($request);
        }

        // Skip for excluded routes
        if ($this->shouldSkipRoute($request)) {
            return $next($request);
        }

        // Skip for super admins (they don't need onboarding)
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Skip for admins (system-level users)
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Redirect to onboarding if not completed
        if (!$user->hasCompletedOnboarding()) {
            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }

    /**
     * Check if the current route should skip onboarding check.
     */
    protected function shouldSkipRoute(Request $request): bool
    {
        foreach ($this->excludedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        // Also skip for AJAX requests to avoid issues with background requests
        if ($request->expectsJson() && !$request->routeIs('dashboard')) {
            return true;
        }

        return false;
    }
}
