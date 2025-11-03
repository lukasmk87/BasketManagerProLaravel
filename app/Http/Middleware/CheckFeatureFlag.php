<?php

namespace App\Http\Middleware;

use App\Services\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureFlag
{
    public function __construct(
        private FeatureFlagService $featureFlagService
    ) {}

    /**
     * Handle an incoming request.
     *
     * Usage in routes:
     * - Route::get(...)->middleware('feature:club_subscriptions_enabled')
     * - Route::group(['middleware' => 'feature:club_subscriptions_checkout'], ...)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        // Get tenant and club from request
        $tenant = $request->user()?->tenant ?? session('tenant');
        $club = $request->route('club'); // Assumes route model binding

        // Check if feature is enabled
        if (!$this->featureFlagService->isEnabled($featureKey, $tenant, $club)) {
            return $this->handleDisabledFeature($request, $featureKey);
        }

        return $next($request);
    }

    /**
     * Handle response when feature is disabled.
     */
    private function handleDisabledFeature(Request $request, string $featureKey): Response
    {
        // For API requests, return JSON response
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'This feature is currently not available for your account.',
                'feature' => $featureKey,
                'code' => 'FEATURE_DISABLED',
            ], 403);
        }

        // For web requests, redirect with message
        return redirect()
            ->back()
            ->with('error', trans('features.not_available', [
                'feature' => $this->getFeatureName($featureKey),
            ]));
    }

    /**
     * Get human-readable feature name.
     */
    private function getFeatureName(string $featureKey): string
    {
        $feature = config("features.flags.{$featureKey}");
        return $feature['name'] ?? $featureKey;
    }
}
