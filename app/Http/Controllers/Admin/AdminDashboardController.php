<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard with tenant and subscription statistics.
     */
    public function index(): Response
    {
        // Get paginated tenants with their subscription and usage data
        $tenants = Tenant::with(['subscriptionPlan', 'activeCustomization'])
            ->withCount(['users', 'teams', 'players', 'games'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate overall statistics
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'suspended_tenants' => Tenant::where('is_suspended', true)->count(),
            'trial_tenants' => Tenant::whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())
                ->count(),
            'total_revenue' => Tenant::sum('total_revenue'),
            'mrr' => Tenant::sum('monthly_recurring_revenue'),
            'total_users' => Tenant::sum('current_users_count'),
            'total_teams' => Tenant::sum('current_teams_count'),
        ];

        // Get subscription plan distribution with tenant counts
        $planStats = SubscriptionPlan::withCount('tenants')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => $plan->price,
                    'formatted_price' => $plan->formatted_price,
                    'tenants_count' => $plan->tenants_count,
                    'active_tenants_count' => $plan->active_tenant_count,
                    'monthly_revenue' => $plan->monthly_revenue,
                    'is_active' => $plan->is_active,
                ];
            });

        // Get tier distribution (for tenants without explicit subscription plan)
        $tierDistribution = Tenant::select('subscription_tier')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('subscription_tier')
            ->get()
            ->pluck('count', 'subscription_tier');

        // Get recent activity (last 30 days)
        $recentActivity = [
            'new_tenants' => Tenant::where('created_at', '>=', now()->subDays(30))->count(),
            'new_users' => Tenant::where('created_at', '>=', now()->subDays(30))
                ->sum('current_users_count'),
            'upgrades' => Tenant::where('updated_at', '>=', now()->subDays(30))
                ->where('subscription_tier', '!=', 'free')
                ->count(),
        ];

        // Get tenants approaching limits (>80% usage)
        $approachingLimits = Tenant::with('subscriptionPlan')
            ->where('is_active', true)
            ->get()
            ->filter(function ($tenant) {
                $limits = $tenant->getTierLimits();

                // Check user limit
                if (isset($limits['users']) && $limits['users'] > 0) {
                    $userPercentage = ($tenant->current_users_count / $limits['users']) * 100;
                    if ($userPercentage > 80) {
                        return true;
                    }
                }

                // Check team limit
                if (isset($limits['teams']) && $limits['teams'] > 0) {
                    $teamPercentage = ($tenant->current_teams_count / $limits['teams']) * 100;
                    if ($teamPercentage > 80) {
                        return true;
                    }
                }

                // Check storage limit
                if (isset($limits['storage_gb']) && $limits['storage_gb'] > 0) {
                    $storagePercentage = ($tenant->current_storage_gb / $limits['storage_gb']) * 100;
                    if ($storagePercentage > 80) {
                        return true;
                    }
                }

                return false;
            })
            ->take(10)
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'tenants' => $tenants,
            'stats' => $stats,
            'planStats' => $planStats,
            'tierDistribution' => $tierDistribution,
            'recentActivity' => $recentActivity,
            'approachingLimits' => $approachingLimits,
        ]);
    }
}
