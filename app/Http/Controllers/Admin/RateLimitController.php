<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\RateLimitException;
use App\Models\ApiUsageTracking;
use App\Services\EnterpriseRateLimitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RateLimitController extends Controller
{
    protected EnterpriseRateLimitService $rateLimitService;

    public function __construct(EnterpriseRateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Get rate limiting dashboard data
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_api_users' => User::where('api_access_enabled', true)->count(),
            'subscriptions_by_tier' => User::selectRaw('subscription_tier, COUNT(*) as count')
                ->groupBy('subscription_tier')
                ->pluck('count', 'subscription_tier'),
            'active_exceptions' => RateLimitException::active()->count(),
            'requests_today' => ApiUsageTracking::whereDate('created_at', today())->sum('request_count'),
            'overage_requests_today' => ApiUsageTracking::whereDate('created_at', today())
                ->where('is_overage', true)
                ->sum('request_count'),
            'top_consumers' => ApiUsageTracking::getTopConsumers(10, 'last_24_hours'),
        ];

        return response()->json($stats);
    }

    /**
     * Get user rate limit status
     */
    public function userStatus(User $user): JsonResponse
    {
        $status = $this->rateLimitService->getStatus($user);
        $usageStats = $user->getApiUsageStats('last_24_hours');
        $subscription = $user->getSubscription();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'subscription_tier' => $user->subscription_tier,
                'api_access_enabled' => $user->api_access_enabled,
            ],
            'subscription' => [
                'tier' => $subscription->tier,
                'status' => $subscription->status,
                'plan_name' => $subscription->plan_name,
                'started_at' => $subscription->started_at,
                'expires_at' => $subscription->expires_at,
                'overage_allowed' => $subscription->overage_allowed,
                'current_overage_cost' => $subscription->current_overage_cost,
            ],
            'rate_limits' => $status,
            'usage_stats' => $usageStats,
        ]);
    }

    /**
     * Create rate limit exception for user
     */
    public function createException(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'exception_type' => 'required|in:unlimited,bypass,increase',
            'scope' => 'required|in:global,endpoint_specific',
            'endpoints' => 'nullable|string',
            'custom_request_limit' => 'nullable|integer|min:1',
            'custom_burst_limit' => 'nullable|integer|min:1',
            'custom_cost_multiplier' => 'nullable|numeric|min:0',
            'duration_hours' => 'required|integer|min:1|max:8760',
            'max_uses' => 'nullable|integer|min:1',
            'reason' => 'required|string|max:500',
            'alert_on_use' => 'boolean',
            'auto_expire' => 'boolean',
        ]);

        $exception = RateLimitException::createTemporary(array_merge($validated, [
            'granted_by' => auth()->id(),
            'status' => 'active',
        ]), $validated['duration_hours']);

        return response()->json([
            'message' => 'Rate limit exception created successfully',
            'exception' => $exception,
        ], 201);
    }

    /**
     * Update subscription tier
     */
    public function updateSubscription(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'tier' => 'required|in:free,basic,premium,enterprise,unlimited',
            'immediate' => 'boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription = $user->getSubscription();
        $oldTier = $subscription->tier;

        if ($validated['tier'] === $oldTier) {
            return response()->json(['message' => 'User is already on this tier'], 400);
        }

        // Determine if upgrade or downgrade
        $tierHierarchy = ['free', 'basic', 'premium', 'enterprise', 'unlimited'];
        $isUpgrade = array_search($validated['tier'], $tierHierarchy) > array_search($oldTier, $tierHierarchy);

        $success = $isUpgrade 
            ? $subscription->upgradeTo($validated['tier'], $validated['immediate'] ?? false)
            : $subscription->downgradeTo($validated['tier'], $validated['immediate'] ?? false);

        if (!$success) {
            return response()->json(['message' => 'Failed to update subscription'], 400);
        }

        // Update user's subscription tier field
        $user->update(['subscription_tier' => $validated['tier']]);

        // Log the change
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'old_tier' => $oldTier,
                'new_tier' => $validated['tier'],
                'immediate' => $validated['immediate'] ?? false,
                'reason' => $validated['reason'],
            ])
            ->log('subscription_tier_changed');

        return response()->json([
            'message' => 'Subscription updated successfully',
            'subscription' => $subscription->fresh(),
        ]);
    }

    /**
     * Get rate limit exceptions
     */
    public function exceptions(Request $request): JsonResponse
    {
        $query = RateLimitException::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->active(); // Default to active exceptions
        }

        $exceptions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($exceptions);
    }

    /**
     * Revoke rate limit exception
     */
    public function revokeException(RateLimitException $exception): JsonResponse
    {
        $reason = 'Revoked by admin ' . auth()->user()->email;
        
        if ($exception->revoke($reason)) {
            return response()->json(['message' => 'Exception revoked successfully']);
        }

        return response()->json(['message' => 'Failed to revoke exception'], 400);
    }

    /**
     * Extend rate limit exception
     */
    public function extendException(Request $request, RateLimitException $exception): JsonResponse
    {
        $validated = $request->validate([
            'additional_hours' => 'required|integer|min:1|max:168', // Max 1 week extension
            'reason' => 'nullable|string|max:500',
        ]);

        $reason = $validated['reason'] ?? 'Extended by admin ' . auth()->user()->email;

        if ($exception->extend($validated['additional_hours'], $reason)) {
            return response()->json([
                'message' => 'Exception extended successfully',
                'exception' => $exception->fresh(),
            ]);
        }

        return response()->json(['message' => 'Failed to extend exception'], 400);
    }

    /**
     * Get API usage analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'last_24_hours');
        $userId = $request->get('user_id');

        if ($userId) {
            $user = User::findOrFail($userId);
            $stats = $user->getApiUsageStats($period);
        } else {
            // Get system-wide analytics
            $start = match($period) {
                'today' => now()->startOfDay(),
                'yesterday' => now()->subDay()->startOfDay(),
                'last_7_days' => now()->subDays(7),
                'last_30_days' => now()->subDays(30),
                'current_month' => now()->startOfMonth(),
                'last_month' => now()->subMonth()->startOfMonth(),
                default => now()->subDay(),
            };

            $end = match($period) {
                'yesterday' => now()->subDay()->endOfDay(),
                'last_month' => now()->subMonth()->endOfMonth(),
                default => now(),
            };

            $records = ApiUsageTracking::whereBetween('created_at', [$start, $end])->get();

            $stats = [
                'total_requests' => $records->sum('request_count'),
                'total_cost' => $records->sum('billable_cost'),
                'unique_users' => $records->whereNotNull('user_id')->unique('user_id')->count(),
                'unique_endpoints' => $records->unique('endpoint')->count(),
                'avg_response_time' => $records->avg('response_time_ms'),
                'success_rate' => $records->whereBetween('response_status', [200, 299])->count() / max($records->count(), 1),
                'overage_requests' => $records->where('is_overage', true)->sum('request_count'),
                'overage_cost' => $records->where('is_overage', true)->sum('billable_cost'),
            ];
        }

        return response()->json([
            'period' => $period,
            'stats' => $stats,
        ]);
    }

    /**
     * Reset user API quota manually
     */
    public function resetQuota(User $user): JsonResponse
    {
        $user->resetApiQuota();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('api_quota_reset');

        return response()->json(['message' => 'API quota reset successfully']);
    }
}