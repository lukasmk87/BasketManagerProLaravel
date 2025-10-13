<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\TenantPlanCustomization;
use App\Http\Requests\Admin\UpdateTenantSubscriptionRequest;
use App\Http\Requests\Admin\UpdateTenantLimitsRequest;
use App\Http\Requests\Admin\CreateCustomizationRequest;
use App\Http\Resources\TenantSubscriptionResource;
use App\Services\LimitEnforcementService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class TenantSubscriptionController extends Controller
{
    /**
     * Display a listing of tenants with subscription details.
     */
    public function index(): Response
    {
        $tenants = Tenant::with(['subscriptionPlan', 'activeCustomization'])
            ->withCount(['users', 'teams', 'players'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => TenantSubscriptionResource::collection($tenants),
        ]);
    }

    /**
     * Display the specified tenant with subscription and usage details.
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->load(['subscriptionPlan', 'activeCustomization', 'planCustomizations']);

        // Get current limits and usage
        $limitEnforcement = app(LimitEnforcementService::class);
        $limitEnforcement->setTenant($tenant);
        $limits = $limitEnforcement->getAllLimits();

        // Get available subscription plans
        $availablePlans = SubscriptionPlan::active()
            ->ordered()
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => $plan->price,
                    'formatted_price' => $plan->formatted_price,
                    'billing_period' => $plan->billing_period,
                    'features' => $plan->features,
                    'limits' => $plan->limits,
                ];
            });

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => new TenantSubscriptionResource($tenant),
            'limits' => $limits,
            'availablePlans' => $availablePlans,
        ]);
    }

    /**
     * Update the tenant's subscription plan.
     */
    public function updateSubscription(UpdateTenantSubscriptionRequest $request, Tenant $tenant): RedirectResponse
    {
        try {
            $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

            $oldPlanId = $tenant->subscription_plan_id;
            $oldTier = $tenant->subscription_tier;

            // Update tenant subscription
            $tenant->update([
                'subscription_plan_id' => $plan->id,
                'subscription_tier' => $plan->slug,
            ]);

            // Update limits from plan
            $tenant->update([
                'max_users' => $plan->getLimit('users'),
                'max_teams' => $plan->getLimit('teams'),
                'max_storage_gb' => $plan->getLimit('storage_gb'),
                'max_api_calls_per_hour' => $plan->getLimit('api_calls_per_hour'),
            ]);

            Log::info('Tenant subscription updated by admin', [
                'tenant_id' => $tenant->id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $plan->id,
                'old_tier' => $oldTier,
                'new_tier' => $plan->slug,
                'updated_by' => auth()->id(),
            ]);

            return back()->with('success', 'Subscription erfolgreich aktualisiert!');

        } catch (\Exception $e) {
            Log::error('Failed to update tenant subscription', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Aktualisieren der Subscription: ' . $e->getMessage());
        }
    }

    /**
     * Update custom limits for a specific tenant.
     */
    public function updateLimits(UpdateTenantLimitsRequest $request, Tenant $tenant): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $oldLimits = [
                'max_users' => $tenant->max_users,
                'max_teams' => $tenant->max_teams,
                'max_storage_gb' => $tenant->max_storage_gb,
                'max_api_calls_per_hour' => $tenant->max_api_calls_per_hour,
            ];

            $tenant->update($validated);

            Log::info('Tenant limits updated by admin', [
                'tenant_id' => $tenant->id,
                'old_limits' => $oldLimits,
                'new_limits' => $validated,
                'updated_by' => auth()->id(),
            ]);

            return back()->with('success', 'Limits erfolgreich aktualisiert!');

        } catch (\Exception $e) {
            Log::error('Failed to update tenant limits', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Aktualisieren der Limits: ' . $e->getMessage());
        }
    }

    /**
     * Create a custom plan customization for a tenant.
     */
    public function createCustomization(CreateCustomizationRequest $request, Tenant $tenant): RedirectResponse
    {
        try {
            $validated = $request->validated();

            // Deactivate any existing active customizations
            $tenant->planCustomizations()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create new customization
            $customization = TenantPlanCustomization::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $tenant->subscription_plan_id,
                'custom_features' => $validated['custom_features'] ?? [],
                'disabled_features' => $validated['disabled_features'] ?? [],
                'custom_limits' => $validated['custom_limits'] ?? [],
                'notes' => $validated['notes'] ?? null,
                'effective_from' => $validated['effective_from'] ?? now(),
                'effective_until' => $validated['effective_until'] ?? null,
                'is_active' => true,
            ]);

            Log::info('Tenant plan customization created by admin', [
                'tenant_id' => $tenant->id,
                'customization_id' => $customization->id,
                'created_by' => auth()->id(),
            ]);

            return back()->with('success', 'Customization erfolgreich erstellt!');

        } catch (\Exception $e) {
            Log::error('Failed to create tenant customization', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Erstellen der Customization: ' . $e->getMessage());
        }
    }
}
