<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\LimitEnforcementService;
use App\Http\Resources\UsageLimitResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
    public function getStats(): JsonResponse
    {
        try {
            $approachingLimits = [];
            $atLimits = [];
            $unlimitedTenants = [];

            // Process tenants in chunks to avoid memory issues
            Tenant::with('subscriptionPlan')
                ->where('is_active', true)
                ->chunk(100, function ($tenants) use (&$approachingLimits, &$atLimits, &$unlimitedTenants) {
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

                            // At limit (100% or more)
                            if ($percentage >= 100) {
                                $atLimits[] = [
                                    'tenant_id' => $tenant->id,
                                    'tenant_name' => $tenant->name,
                                    'subscription_tier' => $tenant->subscription_tier,
                                    'metric' => $metric,
                                    'percentage' => $percentage,
                                    'current' => $data['current'],
                                    'limit' => $data['limit'],
                                    'severity' => 'critical',
                                ];
                            }
                            // Approaching limit (80-99%)
                            elseif ($limitEnforcement->isApproachingLimit($metric)) {
                                $approachingLimits[] = [
                                    'tenant_id' => $tenant->id,
                                    'tenant_name' => $tenant->name,
                                    'subscription_tier' => $tenant->subscription_tier,
                                    'metric' => $metric,
                                    'percentage' => $percentage,
                                    'current' => $data['current'],
                                    'limit' => $data['limit'],
                                    'severity' => 'warning',
                                ];
                            }
                        }

                        // Track tenants with all unlimited metrics
                        $hasUnlimited = collect($limits)->contains('unlimited', true);
                        if ($hasUnlimited) {
                            $unlimitedTenants[] = [
                                'tenant_id' => $tenant->id,
                                'tenant_name' => $tenant->name,
                                'subscription_tier' => $tenant->subscription_tier,
                            ];
                        }
                    }
                });

            // Sort by percentage (descending)
            usort($approachingLimits, fn($a, $b) => $b['percentage'] <=> $a['percentage']);
            usort($atLimits, fn($a, $b) => $b['percentage'] <=> $a['percentage']);

            // Calculate summary statistics
            $summary = [
                'total_active_tenants' => Tenant::where('is_active', true)->count(),
                'tenants_approaching_limits' => count(array_unique(array_column($approachingLimits, 'tenant_id'))),
                'tenants_at_limits' => count(array_unique(array_column($atLimits, 'tenant_id'))),
                'total_warnings' => count($approachingLimits),
                'total_critical' => count($atLimits),
                'metrics_at_risk' => [
                    'users' => count(array_filter($approachingLimits, fn($item) => $item['metric'] === 'users')),
                    'teams' => count(array_filter($approachingLimits, fn($item) => $item['metric'] === 'teams')),
                    'players' => count(array_filter($approachingLimits, fn($item) => $item['metric'] === 'players')),
                    'storage_gb' => count(array_filter($approachingLimits, fn($item) => $item['metric'] === 'storage_gb')),
                ],
            ];

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'approaching_limits' => $approachingLimits,
                'at_limits' => $atLimits,
                'unlimited_tenants' => $unlimitedTenants,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get usage statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Statistiken: ' . $e->getMessage(),
            ], 500);
        }
    }
}
