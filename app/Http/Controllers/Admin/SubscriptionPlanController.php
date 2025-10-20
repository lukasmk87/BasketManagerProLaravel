<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Http\Requests\Admin\CreatePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index(): Response
    {
        $plans = SubscriptionPlan::withCount('tenants')
            ->ordered()
            ->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => SubscriptionPlanResource::collection($plans)->resolve(),
        ]);
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Plans/Create', [
            'featuresList' => config('tenants.features', []),
            'defaultLimits' => config('tenants.defaults', []),
        ]);
    }

    /**
     * Store a newly created subscription plan.
     */
    public function store(CreatePlanRequest $request): RedirectResponse
    {
        try {
            $plan = SubscriptionPlan::create($request->validated());

            Log::info('Subscription plan created', [
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
                'created_by' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.plans.show', $plan)
                ->with('success', 'Subscription Plan erfolgreich erstellt!');

        } catch (\Exception $e) {
            Log::error('Failed to create subscription plan', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Fehler beim Erstellen des Plans: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(SubscriptionPlan $plan): Response
    {
        $plan->loadCount('tenants');

        // Load related tenants with pagination
        $tenants = $plan->tenants()
            ->with(['activeCustomization'])
            ->withCount(['users', 'teams', 'players'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Plans/Show', [
            'plan' => (new SubscriptionPlanResource($plan))->resolve(),
            'tenants' => $tenants,
        ]);
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit(SubscriptionPlan $plan): Response
    {
        return Inertia::render('Admin/Plans/Edit', [
            'plan' => (new SubscriptionPlanResource($plan))->resolve(),
            'featuresList' => config('tenants.features', []),
        ]);
    }

    /**
     * Update the specified subscription plan.
     */
    public function update(UpdatePlanRequest $request, SubscriptionPlan $plan): RedirectResponse
    {
        try {
            $plan->update($request->validated());

            Log::info('Subscription plan updated', [
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
                'updated_by' => auth()->id(),
                'changes' => $request->validated(),
            ]);

            return redirect()
                ->route('admin.plans.show', $plan)
                ->with('success', 'Subscription Plan erfolgreich aktualisiert!');

        } catch (\Exception $e) {
            Log::error('Failed to update subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Fehler beim Aktualisieren des Plans: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified subscription plan.
     */
    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        // Check if plan has active tenants
        $activeTenants = $plan->tenants()->where('is_active', true)->count();

        if ($activeTenants > 0) {
            return back()->with('error', "Plan kann nicht gelöscht werden - es gibt {$activeTenants} aktive Tenant(s).");
        }

        try {
            $planName = $plan->name;
            $plan->delete();

            Log::warning('Subscription plan deleted', [
                'plan_name' => $planName,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.plans.index')
                ->with('success', "Subscription Plan '{$planName}' erfolgreich gelöscht!");

        } catch (\Exception $e) {
            Log::error('Failed to delete subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Löschen des Plans: ' . $e->getMessage());
        }
    }

    /**
     * Clone an existing subscription plan.
     */
    public function clone(SubscriptionPlan $plan): RedirectResponse
    {
        try {
            $newSlug = $plan->slug . '-copy-' . time();
            $newName = $plan->name . ' (Kopie)';

            $newPlan = $plan->clonePlan($newName, $newSlug);

            Log::info('Subscription plan cloned', [
                'original_plan_id' => $plan->id,
                'new_plan_id' => $newPlan->id,
                'cloned_by' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.plans.show', $newPlan)
                ->with('success', 'Plan erfolgreich geklont! Bitte bearbeiten Sie die Details.');

        } catch (\Exception $e) {
            Log::error('Failed to clone subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Klonen des Plans: ' . $e->getMessage());
        }
    }
}
