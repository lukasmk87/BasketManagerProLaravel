<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\TenantPlanCustomization;
use App\Http\Requests\Admin\UpdateTenantSubscriptionRequest;
use App\Http\Requests\Admin\UpdateTenantLimitsRequest;
use App\Http\Requests\Admin\CreateCustomizationRequest;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Resources\TenantSubscriptionResource;
use App\Services\LimitEnforcementService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantSubscriptionController extends Controller
{
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
            if (!empty($validated['subscription_plan_id'])) {
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
                ->with('error', 'Fehler beim Erstellen des Tenants: ' . $e->getMessage());
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
                'name', 'slug', 'domain', 'subdomain', 'is_active', 'is_suspended'
            ]);

            // Update tenant
            $tenant->update($validated);

            // If subscription plan changed, update limits from plan
            if (!empty($validated['subscription_plan_id']) && $validated['subscription_plan_id'] !== $tenant->subscription_plan_id) {
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
                ->with('error', 'Fehler beim Aktualisieren des Tenants: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified tenant from storage (soft delete).
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        DB::beginTransaction();

        try {
            // Safety checks before deletion
            $activeUsersCount = $tenant->users()->count();
            $activeTeamsCount = $tenant->teams()->count();
            $activeClubsCount = $tenant->clubs()->count();

            // Warn if tenant has active data
            if ($activeUsersCount > 0 || $activeTeamsCount > 0 || $activeClubsCount > 0) {
                Log::warning('Attempting to delete tenant with active data', [
                    'tenant_id' => $tenant->id,
                    'active_users' => $activeUsersCount,
                    'active_teams' => $activeTeamsCount,
                    'active_clubs' => $activeClubsCount,
                ]);
            }

            // Store tenant info for logging
            $tenantInfo = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'subdomain' => $tenant->subdomain,
                'active_users' => $activeUsersCount,
                'active_teams' => $activeTeamsCount,
                'active_clubs' => $activeClubsCount,
            ];

            // Soft delete the tenant
            $tenant->delete();

            DB::commit();

            Log::info('Tenant deleted by admin', [
                'tenant_info' => $tenantInfo,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.tenants.index')
                ->with('success', 'Tenant wurde erfolgreich gelöscht!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Fehler beim Löschen des Tenants: ' . $e->getMessage());
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
