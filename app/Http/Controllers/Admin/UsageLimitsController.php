<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\LimitEnforcementService;
use App\Http\Resources\UsageLimitResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UsageLimitsController extends Controller
{
    /**
     * Get usage limits for a specific tenant.
     */
    public function getLimits(Tenant $tenant): JsonResponse
    {
        try {
            $limitEnforcement = app(LimitEnforcementService::class);
            $limitEnforcement->setTenant($tenant);
            $limits = $limitEnforcement->getAllLimits();

            // Transform limits data for UsageLimitResource
            $limitsCollection = collect($limits)->map(function ($data, $metric) {
                return array_merge(['metric' => $metric], $data);
            })->values();

            return response()->json([
                'success' => true,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subscription_tier' => $tenant->subscription_tier,
                ],
                'limits' => UsageLimitResource::collection($limitsCollection),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get tenant limits', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Limits: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get global usage statistics across all tenants.
     */
    public function getStats(): Response
    {
        try {
            $allLimits = [];

            // Process tenants in chunks to avoid memory issues
            Tenant::with('subscriptionPlan')
                ->where('is_active', true)
                ->chunk(100, function ($tenants) use (&$allLimits) {
                    foreach ($tenants as $tenant) {
                        $limitEnforcement = app(LimitEnforcementService::class);
                        $limitEnforcement->setTenant($tenant);
                        $limits = $limitEnforcement->getAllLimits();

                        foreach ($limits as $metric => $data) {
                            // Skip unlimited metrics
                            if ($data['unlimited']) {
                                continue;
                            }

                            $percentage = $data['percentage'];

                            // Only include metrics at 80% or above
                            if ($percentage >= 80) {
                                $allLimits[] = [
                                    'tenant_id' => $tenant->id,
                                    'tenant' => $tenant->name,
                                    'subscription_tier' => $tenant->subscription_tier,
                                    'metric' => $metric,
                                    'percentage' => round($percentage, 1),
                                    'current' => $data['current'],
                                    'limit' => $data['limit'],
                                ];
                            }
                        }
                    }
                });

            // Sort by percentage (descending)
            usort($allLimits, fn($a, $b) => $b['percentage'] <=> $a['percentage']);

            return Inertia::render('Admin/UsageStats', [
                'approaching_limits' => $allLimits,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get usage statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return error page with Inertia
            return Inertia::render('Admin/UsageStats', [
                'approaching_limits' => [],
                'error' => 'Fehler beim Laden der Statistiken: ' . $e->getMessage(),
            ]);
        }
    }
}
