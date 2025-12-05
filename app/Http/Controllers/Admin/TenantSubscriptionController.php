<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCustomizationRequest;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantLimitsRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Requests\Admin\UpdateTenantSubscriptionRequest;
use App\Http\Resources\TenantSubscriptionResource;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantPlanCustomization;
use App\Services\LimitEnforcementService;
use App\Services\TenantDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantSubscriptionController extends Controller
{
    public function __construct(
        protected TenantDeletionService $tenantDeletionService
    ) {}

    /**
     * Display a listing of tenants with subscription details.
     */
    public function index(): Response
    {
        $request = request();

        $tenants = Tenant::with(['subscriptionPlan', 'activeCustomization'])
            ->withCount(['users', 'teams', 'players'])
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%")
                        ->orWhere('subdomain', 'like', "%{$search}%");
                });
            })
            ->when($request->plan, function ($query, $planId) {
                return $query->where('subscription_plan_id', $planId);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    return $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    return $query->where('is_active', false);
                } elseif ($status === 'suspended') {
                    return $query->where('is_suspended', true);
                } elseif ($status === 'trial') {
                    return $query->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>', now());
                }

                return $query;
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        // Get all plans for the filter dropdown
        $plans = SubscriptionPlan::select('id', 'name', 'slug')
            ->ordered()
            ->get();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => TenantSubscriptionResource::collection($tenants),
            'plans' => $plans,
            'filters' => [
                'search' => $request->search,
                'plan' => $request->plan,
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(): Response
    {
        // Get all subscription plans for selection
        $availablePlans = SubscriptionPlan::active()
            ->ordered()
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => $plan->price,
                    'formatted_price' => $plan->formatted_price ?? '€0',
                    'billing_period' => $plan->billing_period ?? 'monthly',
                    'features' => $plan->features ?? [],
                    'limits' => $plan->limits ?? [],
                ];
            });

        // Timezone options
        $timezones = [
            'Europe/Berlin' => 'Europe/Berlin (CET/CEST)',
            'Europe/London' => 'Europe/London (GMT/BST)',
            'Europe/Paris' => 'Europe/Paris (CET/CEST)',
            'America/New_York' => 'America/New_York (EST/EDT)',
            'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
        ];

        // Country codes
        $countries = [
            'DE' => 'Deutschland',
            'AT' => 'Österreich',
            'CH' => 'Schweiz',
            'GB' => 'Großbritannien',
            'US' => 'USA',
        ];

        return Inertia::render('Admin/Tenants/Create', [
            'availablePlans' => $availablePlans,
            'timezones' => $timezones,
            'countries' => $countries,
        ]);
    }

    /**
     * Store a newly created tenant in storage.
     */
    public function store(StoreTenantRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Auto-generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            // Fallback for billing_email if empty (defense in depth)
            if (empty($validated['billing_email'])) {
                $domain = $validated['domain'] ?? $validated['subdomain'] ?? 'tenant';
                $validated['billing_email'] = config('tenant.billing_email', config('mail.from.address', 'admin@' . $domain));
            }

            // Set defaults
            $validated['is_active'] = $validated['is_active'] ?? true;
            $validated['is_suspended'] = $validated['is_suspended'] ?? false;
            $validated['created_by'] = auth()->id();
            $validated['onboarded_by'] = auth()->id();
            $validated['onboarded_at'] = now();

            // Set default subscription tier if not provided
            if (empty($validated['subscription_tier'])) {
                $validated['subscription_tier'] = 'free';
            }

            // Set default limits based on subscription tier
            if (empty($validated['max_users'])) {
                $validated['max_users'] = 10;
            }
            if (empty($validated['max_teams'])) {
                $validated['max_teams'] = 5;
            }
            if (empty($validated['max_storage_gb'])) {
                $validated['max_storage_gb'] = 5;
            }
            if (empty($validated['max_api_calls_per_hour'])) {
                $validated['max_api_calls_per_hour'] = 100;
            }

            // Create tenant
            $tenant = Tenant::create($validated);

            // If subscription plan is provided, update limits from plan
            if (! empty($validated['subscription_plan_id'])) {
                $plan = SubscriptionPlan::find($validated['subscription_plan_id']);
                if ($plan) {
                    $tenant->update([
                        'subscription_tier' => $plan->slug,
                        'max_users' => $plan->getLimit('users') ?? $tenant->max_users,
                        'max_teams' => $plan->getLimit('teams') ?? $tenant->max_teams,
                        'max_storage_gb' => $plan->getLimit('storage_gb') ?? $tenant->max_storage_gb,
                        'max_api_calls_per_hour' => $plan->getLimit('api_calls_per_hour') ?? $tenant->max_api_calls_per_hour,
                    ]);
                }
            }

            DB::commit();

            Log::info('Tenant created by admin', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.tenants.show', $tenant)
                ->with('success', 'Tenant wurde erfolgreich erstellt!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create tenant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Fehler beim Erstellen des Tenants: '.$e->getMessage());
        }
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
     * Show the form for editing the specified tenant.
     */
    public function edit(Tenant $tenant): Response
    {
        // Get all subscription plans for selection
        $availablePlans = SubscriptionPlan::active()
            ->ordered()
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => $plan->price,
                    'formatted_price' => $plan->formatted_price ?? '€0',
                    'billing_period' => $plan->billing_period ?? 'monthly',
                    'features' => $plan->features ?? [],
                    'limits' => $plan->limits ?? [],
                ];
            });

        // Timezone options
        $timezones = [
            'Europe/Berlin' => 'Europe/Berlin (CET/CEST)',
            'Europe/London' => 'Europe/London (GMT/BST)',
            'Europe/Paris' => 'Europe/Paris (CET/CEST)',
            'America/New_York' => 'America/New_York (EST/EDT)',
            'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
        ];

        // Country codes
        $countries = [
            'DE' => 'Deutschland',
            'AT' => 'Österreich',
            'CH' => 'Schweiz',
            'GB' => 'Großbritannien',
            'US' => 'USA',
        ];

        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => $tenant,
            'availablePlans' => $availablePlans,
            'timezones' => $timezones,
            'countries' => $countries,
        ]);
    }

    /**
     * Update the specified tenant's basic information.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Store old values for logging
            $oldValues = $tenant->only([
                'name', 'slug', 'domain', 'subdomain', 'is_active', 'is_suspended',
            ]);

            // WICHTIG: Alten Plan-ID speichern VOR dem Update
            $oldSubscriptionPlanId = $tenant->subscription_plan_id;

            // Update tenant
            $tenant->update($validated);

            // If subscription plan changed, update tier and limits from plan
            if (! empty($validated['subscription_plan_id']) && $validated['subscription_plan_id'] !== $oldSubscriptionPlanId) {
                $plan = SubscriptionPlan::find($validated['subscription_plan_id']);
                if ($plan) {
                    $tenant->update([
                        'subscription_tier' => $plan->slug,
                        'max_users' => $plan->getLimit('users') ?? $tenant->max_users,
                        'max_teams' => $plan->getLimit('teams') ?? $tenant->max_teams,
                        'max_storage_gb' => $plan->getLimit('storage_gb') ?? $tenant->max_storage_gb,
                        'max_api_calls_per_hour' => $plan->getLimit('api_calls_per_hour') ?? $tenant->max_api_calls_per_hour,
                    ]);
                }
            }

            DB::commit();

            Log::info('Tenant updated by admin', [
                'tenant_id' => $tenant->id,
                'old_values' => $oldValues,
                'new_values' => $validated,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.tenants.show', $tenant)
                ->with('success', 'Tenant wurde erfolgreich aktualisiert!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Fehler beim Aktualisieren des Tenants: '.$e->getMessage());
        }
    }

    /**
     * Preview what will happen when deleting the tenant.
     */
    public function previewDelete(Tenant $tenant): JsonResponse
    {
        // Verify user is Super Admin
        if (!auth()->user() || !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Nur Super-Admins können Tenants löschen.');
        }

        $preview = $this->tenantDeletionService->previewDeletion($tenant);

        return response()->json($preview);
    }

    /**
     * Remove the specified tenant from storage (soft delete).
     * If tenant has clubs, they must be transferred to a target tenant.
     */
    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        // Verify user is Super Admin
        if (!auth()->user() || !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Nur Super-Admins können Tenants löschen.');
        }

        try {
            $request->validate([
                'target_tenant_id' => 'nullable|uuid|exists:tenants,id',
            ]);

            $targetTenant = $request->target_tenant_id
                ? Tenant::find($request->target_tenant_id)
                : null;

            $this->tenantDeletionService->deleteTenant(
                $tenant,
                $targetTenant,
                auth()->user()
            );

            return redirect()->route('admin.tenants.index')
                ->with('success', 'Tenant wurde erfolgreich gelöscht!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());

        } catch (\Exception $e) {
            Log::error('Failed to delete tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Fehler beim Löschen des Tenants: '.$e->getMessage());
        }
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

            return back()->with('error', 'Fehler beim Aktualisieren der Subscription: '.$e->getMessage());
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

            return back()->with('error', 'Fehler beim Aktualisieren der Limits: '.$e->getMessage());
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

            return back()->with('error', 'Fehler beim Erstellen der Customization: '.$e->getMessage());
        }
    }

    /**
     * Select a tenant for filtering (Super Admin only).
     * Sets the tenant ID in session for temporary filtering.
     */
    public function selectTenant(Tenant $tenant): RedirectResponse
    {
        // Verify user is Super Admin
        if (!auth()->user() || !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Only Super Admins can select tenants for filtering.');
        }

        // Verify tenant is active
        if (!$tenant->is_active) {
            return back()->with('error', 'Dieser Tenant ist nicht aktiv und kann nicht ausgewählt werden.');
        }

        // Set tenant in session for filtering
        request()->session()->put('super_admin_selected_tenant_id', $tenant->id);

        Log::info('Super Admin selected tenant for filtering', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'super_admin_id' => auth()->id(),
        ]);

        return back()->with('success', "Tenant '{$tenant->name}' wurde als Filter ausgewählt.");
    }

    /**
     * Clear tenant selection (Super Admin only).
     * Removes the tenant filter and shows all tenants again.
     */
    public function clearTenantSelection(): RedirectResponse
    {
        // Verify user is Super Admin
        if (!auth()->user() || !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Only Super Admins can clear tenant selection.');
        }

        // Clear tenant selection from session
        request()->session()->forget('super_admin_selected_tenant_id');

        Log::info('Super Admin cleared tenant filter', [
            'super_admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Tenant-Filter wurde entfernt. Alle Tenants werden angezeigt.');
    }
}
