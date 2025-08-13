<?php

namespace App\Http\Middleware;

use App\Services\FeatureGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceFeatureGates
{
    public function __construct(
        private FeatureGateService $featureGateService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $feature  The required feature for this route
     * @param  string|null  $usage  Usage metric to track (format: metric:amount)
     */
    public function handle(Request $request, Closure $next, ?string $feature = null, ?string $usage = null): Response
    {
        // Skip enforcement for routes that don't require a tenant (like webhooks)
        if (!app()->bound('tenant') || !app('tenant')) {
            return $next($request);
        }

        // Check feature access if feature is specified
        if ($feature) {
            $this->featureGateService->requireFeature($feature);
        }

        // Track usage if specified
        if ($usage) {
            $this->trackUsage($usage);
        }

        // Check for common route-based usage tracking
        $this->trackRouteBasedUsage($request);

        return $next($request);
    }

    /**
     * Track usage based on the usage parameter.
     */
    private function trackUsage(string $usage): void
    {
        if (!str_contains($usage, ':')) {
            // If no amount specified, default to 1
            $this->featureGateService->trackUsage($usage, 1);
            return;
        }

        [$metric, $amount] = explode(':', $usage, 2);
        $amount = (int) $amount;

        // Check if tenant can use this resource
        $this->featureGateService->requireUsage($metric, $amount);

        // Track the usage
        $this->featureGateService->trackUsage($metric, $amount);
    }

    /**
     * Track usage based on common route patterns.
     */
    private function trackRouteBasedUsage(Request $request): void
    {
        $routeName = $request->route()?->getName();
        $method = $request->method();

        if (!$routeName) {
            return;
        }

        // Track API calls for API routes
        if (str_starts_with($routeName, 'api.') || $request->is('api/*')) {
            $this->featureGateService->trackUsage('api_calls_per_hour');
        }

        // Track specific feature usage based on route patterns
        $usageMap = $this->getRouteUsageMap();

        foreach ($usageMap as $pattern => $metric) {
            if (str_contains($routeName, $pattern)) {
                $this->featureGateService->trackUsage($metric);
                break;
            }
        }

        // Track resource creation
        if ($method === 'POST') {
            $this->trackResourceCreation($routeName);
        }
    }

    /**
     * Get mapping of route patterns to usage metrics.
     */
    private function getRouteUsageMap(): array
    {
        return [
            'games.live-scoring' => 'games_per_month',
            'training' => 'training_sessions_per_month',
            'export' => 'data_export',
            'analytics' => 'advanced_analytics',
            'video' => 'video_analysis',
            'tournament' => 'tournament_management',
        ];
    }

    /**
     * Track resource creation based on POST routes.
     */
    private function trackResourceCreation(string $routeName): void
    {
        if (str_contains($routeName, 'games') && str_contains($routeName, 'store')) {
            $this->featureGateService->trackUsage('games_per_month');
        }

        if (str_contains($routeName, 'training') && str_contains($routeName, 'store')) {
            $this->featureGateService->trackUsage('training_sessions_per_month');
        }

        if (str_contains($routeName, 'players') && str_contains($routeName, 'store')) {
            // Check if adding this player would exceed limits
            $this->featureGateService->requireUsage('players', 1);
            // Don't track here as this will be tracked in the controller
        }

        if (str_contains($routeName, 'teams') && str_contains($routeName, 'store')) {
            $this->featureGateService->requireUsage('teams', 1);
        }

        if (str_contains($routeName, 'users') && str_contains($routeName, 'store')) {
            $this->featureGateService->requireUsage('users', 1);
        }
    }
}
