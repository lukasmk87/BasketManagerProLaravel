<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Http\Requests\Admin\CreateClubPlanRequest;
use App\Http\Requests\Admin\UpdateClubPlanRequest;
use App\Http\Resources\ClubSubscriptionPlanResource;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ClubSubscriptionPlanController extends Controller
{
    /**
     * Display a listing of club subscription plans.
     */
    public function index(): Response
    {
        $plans = ClubSubscriptionPlan::withoutGlobalScopes()
            ->with('tenant')
            ->withCount('clubs')
            ->orderBy('tenant_id')
            ->orderBy('sort_order')
            ->get();

        // Group by tenant for display
        $plansByTenant = $plans->groupBy('tenant_id');

        return Inertia::render('Admin/ClubPlans/Index', [
            'plans' => ClubSubscriptionPlanResource::collection($plans)->resolve(),
            'plansByTenant' => $plansByTenant->map(function ($plans) {
                return ClubSubscriptionPlanResource::collection($plans)->resolve();
            }),
            'tenants' => Tenant::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new club subscription plan.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/ClubPlans/Create', [
            'tenants' => Tenant::where('is_active', true)->orderBy('name')->get(),
            'featuresList' => config('club_plans.available_features', []),
            'defaultLimits' => config('club_plans.available_limits', []),
        ]);
    }

    /**
     * Store a newly created club subscription plan.
     */
    public function store(CreateClubPlanRequest $request): RedirectResponse
    {
        try {
            $plan = ClubSubscriptionPlan::create($request->validated());

            Log::info('Club subscription plan created', [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'tenant_id' => $plan->tenant_id,
                'created_by' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.club-plans.show', $plan)
                ->with('success', 'Club Subscription Plan erfolgreich erstellt!');

        } catch (\Exception $e) {
            Log::error('Failed to create club subscription plan', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Fehler beim Erstellen des Plans: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified club subscription plan.
     */
    public function show(ClubSubscriptionPlan $plan): Response
    {
        // Bypass TenantScope for admin
        $plan = ClubSubscriptionPlan::withoutGlobalScopes()
            ->with(['tenant', 'clubs'])
            ->withCount('clubs')
            ->findOrFail($plan->id);

        return Inertia::render('Admin/ClubPlans/Show', [
            'plan' => (new ClubSubscriptionPlanResource($plan))->resolve(),
            'clubs' => $plan->clubs()->paginate(20),
        ]);
    }

    /**
     * Show the form for editing the specified club subscription plan.
     */
    public function edit(ClubSubscriptionPlan $plan): Response
    {
        // Bypass TenantScope for admin
        $plan = ClubSubscriptionPlan::withoutGlobalScopes()
            ->with('tenant')
            ->findOrFail($plan->id);

        return Inertia::render('Admin/ClubPlans/Edit', [
            'plan' => (new ClubSubscriptionPlanResource($plan))->resolve(),
            'tenants' => Tenant::where('is_active', true)->orderBy('name')->get(),
            'featuresList' => config('club_plans.available_features', []),
            'defaultLimits' => config('club_plans.available_limits', []),
        ]);
    }

    /**
     * Update the specified club subscription plan.
     */
    public function update(UpdateClubPlanRequest $request, ClubSubscriptionPlan $plan): RedirectResponse
    {
        // Bypass TenantScope for admin
        $plan = ClubSubscriptionPlan::withoutGlobalScopes()->findOrFail($plan->id);

        try {
            $plan->update($request->validated());

            Log::info('Club subscription plan updated', [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'updated_by' => auth()->id(),
                'changes' => $request->validated(),
            ]);

            return redirect()
                ->route('admin.club-plans.show', $plan)
                ->with('success', 'Club Subscription Plan erfolgreich aktualisiert!');

        } catch (\Exception $e) {
            Log::error('Failed to update club subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Fehler beim Aktualisieren des Plans: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified club subscription plan.
     */
    public function destroy(ClubSubscriptionPlan $plan): RedirectResponse
    {
        // Bypass TenantScope for admin
        $plan = ClubSubscriptionPlan::withoutGlobalScopes()
            ->withCount('clubs')
            ->findOrFail($plan->id);

        // Check if plan has active clubs
        if ($plan->clubs_count > 0) {
            return back()->with('error', "Plan kann nicht gelöscht werden - es gibt {$plan->clubs_count} Club(s) mit diesem Plan.");
        }

        try {
            $planName = $plan->name;
            $plan->delete();

            Log::warning('Club subscription plan deleted', [
                'plan_name' => $planName,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.club-plans.index')
                ->with('success', "Club Subscription Plan '{$planName}' erfolgreich gelöscht!");

        } catch (\Exception $e) {
            Log::error('Failed to delete club subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Löschen des Plans: ' . $e->getMessage());
        }
    }

    /**
     * Clone an existing club subscription plan.
     */
    public function clone(ClubSubscriptionPlan $plan): RedirectResponse
    {
        // Bypass TenantScope for admin
        $plan = ClubSubscriptionPlan::withoutGlobalScopes()->findOrFail($plan->id);

        try {
            $newPlan = $plan->replicate();
            $newPlan->name = $plan->name . ' (Kopie)';
            $newPlan->slug = $plan->slug . '-copy-' . time();
            $newPlan->stripe_product_id = null;
            $newPlan->stripe_price_id_monthly = null;
            $newPlan->stripe_price_id_yearly = null;
            $newPlan->is_stripe_synced = false;
            $newPlan->save();

            Log::info('Club subscription plan cloned', [
                'original_plan_id' => $plan->id,
                'new_plan_id' => $newPlan->id,
                'cloned_by' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.club-plans.show', $newPlan)
                ->with('success', 'Plan erfolgreich geklont! Bitte bearbeiten Sie die Details.');

        } catch (\Exception $e) {
            Log::error('Failed to clone club subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Klonen des Plans: ' . $e->getMessage());
        }
    }
}
