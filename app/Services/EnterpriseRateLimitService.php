<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Models\ApiUsageTracking;
use App\Models\RateLimitException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnterpriseRateLimitService
{
    /**
     * Default tier configurations
     */
    protected array $tierLimits = [
        'free' => [
            'requests_per_hour' => 1000,
            'burst_per_minute' => 100,
            'concurrent_requests' => 10,
            'cost_multiplier' => 1.0,
            'priority' => 'low',
        ],
        'basic' => [
            'requests_per_hour' => 5000,
            'burst_per_minute' => 300,
            'concurrent_requests' => 25,
            'cost_multiplier' => 0.8,
            'priority' => 'normal',
        ],
        'premium' => [
            'requests_per_hour' => 25000,
            'burst_per_minute' => 1500,
            'concurrent_requests' => 100,
            'cost_multiplier' => 0.6,
            'priority' => 'high',
        ],
        'enterprise' => [
            'requests_per_hour' => 100000,
            'burst_per_minute' => 5000,
            'concurrent_requests' => 500,
            'cost_multiplier' => 0.4,
            'priority' => 'priority',
        ],
        'unlimited' => [
            'requests_per_hour' => PHP_INT_MAX,
            'burst_per_minute' => PHP_INT_MAX,
            'concurrent_requests' => 1000,
            'cost_multiplier' => 0.2,
            'priority' => 'highest',
        ],
    ];

    /**
     * Endpoint cost weights for different operation types
     */
    protected array $endpointCosts = [
        // Authentication - Low cost
        'auth/*' => 0.5,
        
        // General API - Standard cost
        'api/v1/teams' => 1.0,
        'api/v1/players' => 1.0,
        'api/v1/clubs' => 1.0,
        
        // Live scoring - Higher cost due to real-time nature
        'api/v1/games/*/live' => 3.0,
        'api/v1/live/*' => 3.0,
        
        // Analytics and reports - High cost due to processing
        'api/v1/analytics/*' => 5.0,
        'api/v1/reports/*' => 5.0,
        'api/v1/export/*' => 8.0,
        
        // Bulk operations - Very high cost
        'api/v1/bulk/*' => 10.0,
        'api/v1/import/*' => 15.0,
        
        // Admin operations - Moderate cost
        'api/v1/admin/*' => 2.0,
        
        // File uploads - High cost
        'api/v1/upload/*' => 12.0,
    ];

    /**
     * Check if request should be rate limited
     */
    public function shouldLimit(Request $request, ?User $user = null): array
    {
        $identifier = $this->getIdentifier($request, $user);
        $endpoint = $this->normalizeEndpoint($request);
        $costWeight = $this->calculateCostWeight($endpoint);
        
        // Get user limits (including exceptions)
        $limits = $this->getEffectiveLimits($user, $endpoint);
        
        // Check sliding window limits
        $hourlyUsage = $this->getHourlyUsage($identifier, $user);
        $minutelyUsage = $this->getMinutelyUsage($identifier, $user);
        $concurrentRequests = $this->getConcurrentRequests($identifier);
        
        // Calculate effective usage with cost weights
        $effectiveHourlyUsage = $hourlyUsage['total_cost'];
        $effectiveMinutelyUsage = $minutelyUsage['total_cost'];
        
        $limitInfo = [
            'allowed' => true,
            'limits' => $limits,
            'usage' => [
                'hourly' => $hourlyUsage,
                'minutely' => $minutelyUsage,
                'concurrent' => $concurrentRequests,
            ],
            'cost_weight' => $costWeight,
            'identifier' => $identifier,
            'endpoint' => $endpoint,
            'limit_type_hit' => null,
            'retry_after' => null,
            'overage_cost' => 0.0,
        ];

        // Check hourly limits
        if ($effectiveHourlyUsage + $costWeight > $limits['requests_per_hour']) {
            $limitInfo['allowed'] = false;
            $limitInfo['limit_type_hit'] = 'hourly';
            $limitInfo['retry_after'] = $hourlyUsage['time_remaining'];
            
            // Calculate overage cost if allowed
            if ($user && $user->getSubscription()->overage_allowed) {
                $excessRequests = ($effectiveHourlyUsage + $costWeight) - $limits['requests_per_hour'];
                $limitInfo['overage_cost'] = $user->getSubscription()->calculateOverageCost($excessRequests);
                $limitInfo['allowed'] = true; // Allow with overage
            }
        }

        // Check burst (minutely) limits
        if ($limitInfo['allowed'] && $effectiveMinutelyUsage + $costWeight > $limits['burst_per_minute']) {
            $limitInfo['allowed'] = false;
            $limitInfo['limit_type_hit'] = 'burst';
            $limitInfo['retry_after'] = $minutelyUsage['time_remaining'];
        }

        // Check concurrent request limits
        if ($limitInfo['allowed'] && $concurrentRequests >= $limits['concurrent_requests']) {
            $limitInfo['allowed'] = false;
            $limitInfo['limit_type_hit'] = 'concurrent';
            $limitInfo['retry_after'] = 5; // 5 seconds for concurrent limit
        }

        return $limitInfo;
    }

    /**
     * Record API usage for rate limiting
     */
    public function recordUsage(Request $request, array $limitInfo, ?User $user = null, int $responseStatus = 200, int $responseTime = 0, int $responseSize = 0): void
    {
        $data = [
            'user_id' => $user?->id,
            'api_key_hash' => $this->getApiKeyHash($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'endpoint' => $limitInfo['endpoint'],
            'route_name' => $request->route()?->getName(),
            'api_version' => $this->extractApiVersion($request),
            'request_count' => 1,
            'cost_weight' => $limitInfo['cost_weight'],
            'response_time_ms' => $responseTime,
            'response_status' => $responseStatus,
            'response_size_bytes' => $responseSize,
            'exceeded_limit' => !$limitInfo['allowed'],
            'limit_type_hit' => $limitInfo['limit_type_hit'],
            'country_code' => $this->getCountryCode($request->ip()),
            'region' => $this->getRegion($request->ip()),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'billable_cost' => $limitInfo['overage_cost'],
            'subscription_tier' => $user?->subscription_tier ?? 'free',
            'is_overage' => $limitInfo['overage_cost'] > 0,
        ];

        // Record for both hourly and minutely windows
        ApiUsageTracking::recordUsage(array_merge($data, ['window_type' => 'hourly']));
        ApiUsageTracking::recordUsage(array_merge($data, ['window_type' => 'minutely']));

        // Update user's current API usage if applicable
        if ($user) {
            $user->increment('current_api_usage', $limitInfo['cost_weight']);
            if ($limitInfo['overage_cost'] > 0) {
                $subscription = $user->getSubscription();
                $subscription->increment('current_overage_cost', $limitInfo['overage_cost']);
            }
        }

        // Log high-cost operations
        if ($limitInfo['cost_weight'] > 5.0) {
            Log::info('High-cost API operation recorded', [
                'user_id' => $user?->id,
                'endpoint' => $limitInfo['endpoint'],
                'cost_weight' => $limitInfo['cost_weight'],
                'subscription_tier' => $user?->subscription_tier,
            ]);
        }

        // Alert on limit violations
        if (!$limitInfo['allowed'] && $limitInfo['overage_cost'] === 0) {
            $this->alertLimitViolation($limitInfo, $user, $request);
        }
    }

    /**
     * Get effective rate limits for user (including exceptions)
     */
    protected function getEffectiveLimits(?User $user, string $endpoint): array
    {
        if (!$user) {
            // Anonymous requests get free tier limits
            return $this->tierLimits['free'];
        }

        $baseLimits = $user->getApiLimits();
        
        // Apply any active rate limit exceptions
        $exceptions = RateLimitException::findActiveFor('user', $user->id, $endpoint);
        
        foreach ($exceptions as $exception) {
            $baseLimits = $exception->getEffectiveLimits($baseLimits);
        }

        return $baseLimits;
    }

    /**
     * Get identifier for rate limiting (user ID, API key, or IP)
     */
    protected function getIdentifier(Request $request, ?User $user): string
    {
        if ($user) {
            return "user:{$user->id}";
        }

        $apiKey = $this->getApiKeyHash($request);
        if ($apiKey) {
            return "apikey:{$apiKey}";
        }

        return "ip:{$request->ip()}";
    }

    /**
     * Get API key hash from request
     */
    protected function getApiKeyHash(Request $request): ?string
    {
        $apiKey = $request->bearerToken() ?? $request->header('X-API-Key');
        return $apiKey ? hash('sha256', $apiKey) : null;
    }

    /**
     * Normalize endpoint for pattern matching
     */
    protected function normalizeEndpoint(Request $request): string
    {
        return trim($request->getPathInfo(), '/');
    }

    /**
     * Calculate cost weight for endpoint
     */
    protected function calculateCostWeight(string $endpoint): float
    {
        foreach ($this->endpointCosts as $pattern => $cost) {
            if ($this->matchesEndpointPattern($endpoint, $pattern)) {
                return $cost;
            }
        }

        return 1.0; // Default cost
    }

    /**
     * Check if endpoint matches pattern
     */
    protected function matchesEndpointPattern(string $endpoint, string $pattern): bool
    {
        // Convert pattern to regex
        $regexPattern = str_replace(['*', '/'], ['.*', '\/'], $pattern);
        return (bool) preg_match("/^{$regexPattern}$/i", $endpoint);
    }

    /**
     * Get hourly usage from sliding window
     */
    protected function getHourlyUsage(string $identifier, ?User $user): array
    {
        if ($user) {
            return ApiUsageTracking::getCurrentWindowUsage($user->id, null, 'hourly');
        }

        $ipAddress = str_replace('ip:', '', $identifier);
        return ApiUsageTracking::getCurrentWindowUsage(null, $ipAddress, 'hourly');
    }

    /**
     * Get minutely usage from sliding window
     */
    protected function getMinutelyUsage(string $identifier, ?User $user): array
    {
        if ($user) {
            return ApiUsageTracking::getCurrentWindowUsage($user->id, null, 'minutely');
        }

        $ipAddress = str_replace('ip:', '', $identifier);
        return ApiUsageTracking::getCurrentWindowUsage(null, $ipAddress, 'minutely');
    }

    /**
     * Get current concurrent requests
     */
    protected function getConcurrentRequests(string $identifier): int
    {
        $cacheKey = "concurrent_requests:{$identifier}";
        return Cache::get($cacheKey, 0);
    }

    /**
     * Increment concurrent requests counter
     */
    public function incrementConcurrentRequests(string $identifier): void
    {
        $cacheKey = "concurrent_requests:{$identifier}";
        Cache::increment($cacheKey);
        Cache::expire($cacheKey, 300); // 5 minutes
    }

    /**
     * Decrement concurrent requests counter
     */
    public function decrementConcurrentRequests(string $identifier): void
    {
        $cacheKey = "concurrent_requests:{$identifier}";
        $current = Cache::get($cacheKey, 0);
        if ($current > 0) {
            Cache::decrement($cacheKey);
        }
    }

    /**
     * Extract API version from request
     */
    protected function extractApiVersion(Request $request): string
    {
        // Try header first
        if ($version = $request->header('Accept-Version')) {
            return $version;
        }

        // Try URL path
        if (preg_match('/\/api\/v(\d+(?:\.\d+)?)\//', $request->getPathInfo(), $matches)) {
            return "v{$matches[1]}";
        }

        return 'v1'; // Default
    }

    /**
     * Get country code from IP (placeholder - would integrate with GeoIP service)
     */
    protected function getCountryCode(string $ipAddress): ?string
    {
        // Placeholder - in production would use GeoIP service
        return null;
    }

    /**
     * Get region from IP (placeholder)
     */
    protected function getRegion(string $ipAddress): ?string
    {
        // Placeholder - in production would use GeoIP service
        return null;
    }

    /**
     * Sanitize headers for logging (remove sensitive data)
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie', 'x-forwarded-for'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * Alert on rate limit violations
     */
    protected function alertLimitViolation(array $limitInfo, ?User $user, Request $request): void
    {
        Log::warning('Rate limit violation', [
            'user_id' => $user?->id,
            'subscription_tier' => $user?->subscription_tier,
            'ip_address' => $request->ip(),
            'endpoint' => $limitInfo['endpoint'],
            'limit_type' => $limitInfo['limit_type_hit'],
            'usage' => $limitInfo['usage'],
            'limits' => $limitInfo['limits'],
            'user_agent' => $request->userAgent(),
        ]);

        // In production, could send alerts to monitoring systems
        // or notify administrators of potential abuse
    }

    /**
     * Get rate limit status for user
     */
    public function getStatus(?User $user): array
    {
        if (!$user) {
            return $this->tierLimits['free'];
        }

        $limits = $user->getApiLimits();
        $hourlyUsage = $this->getHourlyUsage("user:{$user->id}", $user);
        $minutelyUsage = $this->getMinutelyUsage("user:{$user->id}", $user);
        $concurrent = $this->getConcurrentRequests("user:{$user->id}");

        return [
            'subscription_tier' => $user->subscription_tier,
            'limits' => $limits,
            'usage' => [
                'hourly' => $hourlyUsage,
                'minutely' => $minutelyUsage,
                'concurrent' => $concurrent,
            ],
            'percentage_used' => [
                'hourly' => min(100, ($hourlyUsage['total_cost'] / $limits['requests_per_hour']) * 100),
                'minutely' => min(100, ($minutelyUsage['total_cost'] / $limits['burst_per_minute']) * 100),
                'concurrent' => min(100, ($concurrent / $limits['concurrent_requests']) * 100),
            ],
            'time_until_reset' => [
                'hourly' => $hourlyUsage['time_remaining'],
                'minutely' => $minutelyUsage['time_remaining'],
            ],
        ];
    }
}