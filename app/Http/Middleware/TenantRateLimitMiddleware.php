<?php

namespace App\Http\Middleware;

use App\Services\FeatureGateService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class TenantRateLimitMiddleware
{
    public function __construct(
        private FeatureGateService $featureGateService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $type  Type of rate limiting (api, web, burst)
     */
    public function handle(Request $request, Closure $next, ?string $type = 'api'): Response
    {
        // Skip rate limiting for routes that don't require a tenant
        if (!app()->bound('tenant') || !app('tenant')) {
            return $next($request);
        }

        $tenant = app('tenant');
        $limits = $this->getRateLimitsForTenant($tenant->subscription_tier, $type);

        if (!$limits) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request, $tenant->id);

        // Apply the rate limiting
        $response = RateLimiter::attempt(
            $key,
            $limits['maxAttempts'],
            function () use ($next, $request) {
                return $next($request);
            },
            $limits['decaySeconds']
        );

        if (!$response) {
            return $this->buildRateLimitResponse($key, $limits['maxAttempts'], $limits['decaySeconds']);
        }

        return $this->addHeaders(
            $response,
            $limits['maxAttempts'],
            RateLimiter::retriesLeft($key, $limits['maxAttempts'])
        );
    }

    /**
     * Get rate limits for a tenant's subscription tier.
     */
    private function getRateLimitsForTenant(string $tier, string $type): ?array
    {
        $rateLimits = config('tenants.rate_limits', []);
        $tierConfig = config("tenants.tiers.{$tier}", []);

        switch ($type) {
            case 'api':
                $limit = $tierConfig['limits']['api_calls_per_hour'] ?? $rateLimits['api'][$tier] ?? $rateLimits['default'];
                return $limit === -1 ? null : [
                    'maxAttempts' => $limit,
                    'decaySeconds' => 3600, // 1 hour
                ];

            case 'web':
                // More generous limits for web interface
                $baseLimit = $tierConfig['limits']['api_calls_per_hour'] ?? $rateLimits['default'];
                $limit = $baseLimit === -1 ? -1 : $baseLimit * 3; // 3x more generous
                return $limit === -1 ? null : [
                    'maxAttempts' => $limit,
                    'decaySeconds' => 3600,
                ];

            case 'burst':
                // Short burst limits (per minute)
                $hourlyLimit = $tierConfig['limits']['api_calls_per_hour'] ?? $rateLimits['default'];
                $burstLimit = $hourlyLimit === -1 ? -1 : min($rateLimits['burst'], $hourlyLimit / 10);
                return $burstLimit === -1 ? null : [
                    'maxAttempts' => $burstLimit,
                    'decaySeconds' => 60, // 1 minute
                ];

            default:
                return null;
        }
    }

    /**
     * Resolve request signature for rate limiting.
     */
    private function resolveRequestSignature(Request $request, string $tenantId): string
    {
        $ip = $request->ip();
        $userId = auth()->id() ?? 'guest';
        
        return sha1("rate_limit:{$tenantId}:{$userId}:{$ip}");
    }

    /**
     * Create a rate limit response.
     */
    private function buildRateLimitResponse(string $key, int $maxAttempts, int $decaySeconds): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => $retryAfter,
                'limit' => $maxAttempts,
                'period' => $decaySeconds,
                'upgrade_url' => route('subscription.index'),
            ], 429);
        }

        return response()->view('errors.rate-limit', [
            'retryAfter' => $retryAfter,
            'limit' => $maxAttempts,
            'period' => $decaySeconds,
        ], 429);
    }

    /**
     * Add rate limiting headers to response.
     */
    private function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);

        return $response;
    }
}