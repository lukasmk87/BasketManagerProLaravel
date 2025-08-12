<?php

namespace App\Http\Middleware;

use App\Services\EnterpriseRateLimitService;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnterpriseRateLimitMiddleware
{
    protected EnterpriseRateLimitService $rateLimitService;

    public function __construct(EnterpriseRateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $startTime = microtime(true);
        $user = $this->resolveUser($request);
        
        // Check rate limits
        $limitInfo = $this->rateLimitService->shouldLimit($request, $user);
        $identifier = $limitInfo['identifier'];

        // Track concurrent requests
        $this->rateLimitService->incrementConcurrentRequests($identifier);

        // Block request if rate limited (and no overage allowed)
        if (!$limitInfo['allowed']) {
            $this->rateLimitService->decrementConcurrentRequests($identifier);
            
            return $this->createRateLimitedResponse($limitInfo);
        }

        // Process the request
        $response = $next($request);

        // Calculate response metrics
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $responseSize = strlen($response->getContent());
        $responseStatus = $response->getStatusCode();

        // Record usage
        $this->rateLimitService->recordUsage(
            $request,
            $limitInfo,
            $user,
            $responseStatus,
            (int) $responseTime,
            $responseSize
        );

        // Decrement concurrent requests
        $this->rateLimitService->decrementConcurrentRequests($identifier);

        // Add rate limit headers to response
        $response = $this->addRateLimitHeaders($response, $limitInfo);

        return $response;
    }

    /**
     * Resolve user from request (supports multiple authentication methods)
     */
    protected function resolveUser(Request $request): ?User
    {
        // Try Laravel Sanctum authentication first
        if ($user = $request->user()) {
            return $user;
        }

        // Try API key authentication
        $apiKey = $request->bearerToken() ?? $request->header('X-API-Key');
        if ($apiKey) {
            $user = User::where('api_key_hash', hash('sha256', $apiKey))
                       ->where('api_access_enabled', true)
                       ->first();

            if ($user) {
                $user->updateApiKeyUsage();
                return $user;
            }
        }

        return null;
    }

    /**
     * Create response for rate limited requests
     */
    protected function createRateLimitedResponse(array $limitInfo): Response
    {
        $retryAfter = $limitInfo['retry_after'] ?? 3600; // Default 1 hour
        $limitType = $limitInfo['limit_type_hit'] ?? 'unknown';

        $errorData = [
            'error' => 'rate_limit_exceeded',
            'message' => $this->getRateLimitMessage($limitType),
            'limit_type' => $limitType,
            'limits' => [
                'current' => $limitInfo['limits'],
                'usage' => $limitInfo['usage'],
            ],
            'retry_after_seconds' => $retryAfter,
            'retry_after_human' => $this->formatRetryAfter($retryAfter),
            'documentation' => url('/docs/api/rate-limiting'),
            'contact' => 'support@basketmanager-pro.de',
        ];

        // Add upgrade suggestion for non-enterprise users
        if (isset($limitInfo['limits']['tier']) && $limitInfo['limits']['tier'] !== 'enterprise') {
            $errorData['upgrade_suggestion'] = [
                'message' => 'Consider upgrading your subscription for higher limits',
                'upgrade_url' => url('/subscription/upgrade'),
                'enterprise_limits' => [
                    'requests_per_hour' => 100000,
                    'burst_per_minute' => 5000,
                    'concurrent_requests' => 500,
                ],
            ];
        }

        $response = response()->json($errorData, 429);

        return $this->addRateLimitHeaders($response, $limitInfo, $retryAfter);
    }

    /**
     * Get appropriate error message based on limit type
     */
    protected function getRateLimitMessage(string $limitType): string
    {
        return match ($limitType) {
            'hourly' => 'Hourly API request limit exceeded. Please wait before making more requests.',
            'burst' => 'Request rate too high. Please slow down your requests.',
            'concurrent' => 'Too many concurrent requests. Please wait for existing requests to complete.',
            default => 'API rate limit exceeded. Please try again later.',
        };
    }

    /**
     * Format retry after seconds into human readable format
     */
    protected function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes} minutes";
        } else {
            $hours = floor($seconds / 3600);
            $remainingMinutes = floor(($seconds % 3600) / 60);
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours} hours";
        }
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(Response $response, array $limitInfo, ?int $retryAfter = null): Response
    {
        $limits = $limitInfo['limits'];
        $usage = $limitInfo['usage'];

        // Standard rate limit headers
        $response->headers->set('X-RateLimit-Limit-Hourly', $limits['requests_per_hour']);
        $response->headers->set('X-RateLimit-Remaining-Hourly', max(0, $limits['requests_per_hour'] - $usage['hourly']['total_cost']));
        $response->headers->set('X-RateLimit-Reset-Hourly', time() + $usage['hourly']['time_remaining']);

        $response->headers->set('X-RateLimit-Limit-Burst', $limits['burst_per_minute']);
        $response->headers->set('X-RateLimit-Remaining-Burst', max(0, $limits['burst_per_minute'] - $usage['minutely']['total_cost']));
        $response->headers->set('X-RateLimit-Reset-Burst', time() + $usage['minutely']['time_remaining']);

        $response->headers->set('X-RateLimit-Limit-Concurrent', $limits['concurrent_requests']);
        $response->headers->set('X-RateLimit-Used-Concurrent', $usage['concurrent']);

        // Additional enterprise headers
        if (isset($limitInfo['cost_weight'])) {
            $response->headers->set('X-RateLimit-Cost', $limitInfo['cost_weight']);
        }

        if (isset($limitInfo['overage_cost']) && $limitInfo['overage_cost'] > 0) {
            $response->headers->set('X-RateLimit-Overage-Cost', $limitInfo['overage_cost']);
            $response->headers->set('X-RateLimit-Overage', 'true');
        }

        // Retry after header for rate limited responses
        if ($retryAfter) {
            $response->headers->set('Retry-After', $retryAfter);
        }

        // Subscription tier information
        if (isset($limits['tier'])) {
            $response->headers->set('X-Subscription-Tier', $limits['tier']);
        }

        return $response;
    }

    /**
     * Handle request termination (cleanup)
     */
    public function terminate(Request $request, SymfonyResponse $response): void
    {
        // Cleanup any remaining concurrent request tracking if needed
        // This is handled automatically by the cache TTL, but could add explicit cleanup here
    }
}