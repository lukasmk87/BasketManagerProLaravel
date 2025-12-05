<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClubAdmin\UpdateClubSubscriptionRequest;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ClubSubscriptionAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Show subscriptions management page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $primaryClub->load('subscriptionPlan');

        $availablePlans = ClubSubscriptionPlan::query()
            ->where('tenant_id', $primaryClub->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'price', 'currency', 'billing_interval', 'features', 'limits', 'color', 'icon']);

        $currentPlan = $primaryClub->subscriptionPlan;

        $subscriptionLimits = $primaryClub->getSubscriptionLimits();

        return Inertia::render('ClubAdmin/Subscriptions/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
                'club_subscription_plan_id' => $primaryClub->club_subscription_plan_id,
            ],
            'current_plan' => $currentPlan ? [
                'id' => $currentPlan->id,
                'name' => $currentPlan->name,
                'slug' => $currentPlan->slug,
                'description' => $currentPlan->description,
                'price' => $currentPlan->price,
                'currency' => $currentPlan->currency,
                'billing_interval' => $currentPlan->billing_interval,
                'features' => $currentPlan->features,
                'limits' => $currentPlan->limits,
                'color' => $currentPlan->color,
                'icon' => $currentPlan->icon,
            ] : null,
            'available_plans' => $availablePlans,
            'subscription_limits' => $subscriptionLimits,
            'can_change_plan' => $user->hasAnyRole(['super_admin', 'tenant_admin']),
        ]);
    }

    /**
     * Update club subscription plan.
     */
    public function update(UpdateClubSubscriptionRequest $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            abort(403, 'Sie haben keine Berechtigung, den Subscription Plan zu ändern.');
        }

        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $validated = $request->validated();

        try {
            $oldPlanId = $primaryClub->club_subscription_plan_id;

            $primaryClub->update([
                'club_subscription_plan_id' => $validated['club_subscription_plan_id'],
            ]);

            Log::info('Club subscription plan updated', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $validated['club_subscription_plan_id'],
            ]);

            return redirect()->route('club-admin.subscriptions.index')
                ->with('success', 'Subscription Plan wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update club subscription plan', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren des Subscription Plans.')
                ->withInput();
        }
    }
}
