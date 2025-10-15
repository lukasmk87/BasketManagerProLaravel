<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use App\Models\Club;
use App\Services\ClubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClubSubscriptionPlanController extends Controller
{
    protected ClubService $clubService;

    public function __construct(ClubService $clubService)
    {
        $this->clubService = $clubService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all club plans for a tenant.
     */
    public function index(Tenant $tenant)
    {
        $this->authorize('viewAny', ClubSubscriptionPlan::class);

        $plans = $tenant->clubSubscriptionPlans()
            ->withCount('clubs', 'activeClubs')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Create a new club plan.
     */
    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('create', ClubSubscriptionPlan::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'billing_interval' => 'required|in:monthly,yearly',
            'features' => 'required|array',
            'limits' => 'required|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $plan = $this->clubService->createClubPlan($tenant, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Club subscription plan created successfully',
                'data' => $plan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a club plan.
     */
    public function update(Request $request, Tenant $tenant, ClubSubscriptionPlan $plan)
    {
        $this->authorize('update', $plan);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'features' => 'array',
            'limits' => 'array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $plan = $this->clubService->updateClubPlan($plan, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Club subscription plan updated successfully',
                'data' => $plan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a club plan.
     */
    public function destroy(Tenant $tenant, ClubSubscriptionPlan $plan)
    {
        $this->authorize('delete', $plan);

        if ($plan->clubs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete plan with assigned clubs',
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Club subscription plan deleted successfully',
        ], 200);
    }

    /**
     * Assign a plan to a club.
     */
    public function assignToClub(Request $request, Club $club)
    {
        $this->authorize('update', $club);

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:club_subscription_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = ClubSubscriptionPlan::findOrFail($request->plan_id);

        try {
            $this->clubService->assignPlanToClub($club, $plan);

            return response()->json([
                'success' => true,
                'message' => 'Plan assigned to club successfully',
                'data' => $club->fresh()->load('subscriptionPlan'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove plan from club.
     */
    public function removeFromClub(Club $club)
    {
        $this->authorize('update', $club);

        $club->removePlan();

        return response()->json([
            'success' => true,
            'message' => 'Plan removed from club successfully',
            'data' => $club->fresh(),
        ], 200);
    }
}
