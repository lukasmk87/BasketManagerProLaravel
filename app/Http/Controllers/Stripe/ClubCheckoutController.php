<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubInvoiceRequest;
use App\Models\ClubSubscriptionPlan;
use App\Services\Stripe\ClubSubscriptionCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ClubCheckoutController extends Controller
{
    public function __construct(
        private ClubSubscriptionCheckoutService $checkoutService
    ) {}

    /**
     * Initiate checkout session for club subscription.
     */
    public function checkout(Request $request, Club $club): JsonResponse|RedirectResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'plan_id' => 'required|exists:club_subscription_plans,id',
                'billing_interval' => 'sometimes|in:monthly,yearly',
                'success_url' => 'sometimes|url',
                'cancel_url' => 'sometimes|url',
            ]);

            $plan = ClubSubscriptionPlan::findOrFail($validated['plan_id']);

            // Authorize: User must be club admin or owner
            $this->authorize('manageBilling', $club);

            // Validate plan belongs to same tenant
            if ($plan->tenant_id !== $club->tenant_id) {
                return response()->json([
                    'error' => 'Plan does not belong to club\'s tenant',
                ], 403);
            }

            // Create checkout session
            $session = $this->checkoutService->createCheckoutSession(
                $club,
                $plan,
                [
                    'billing_interval' => $validated['billing_interval'] ?? 'monthly',
                    'success_url' => $validated['success_url'] ?? null,
                    'cancel_url' => $validated['cancel_url'] ?? null,
                ]
            );

            Log::info('Club checkout session created', [
                'club_id' => $club->id,
                'plan_id' => $plan->id,
                'session_id' => $session->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Club checkout initiation failed', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to create checkout session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle successful checkout completion.
     */
    public function success(Request $request, Club $club): InertiaResponse|RedirectResponse
    {
        // Authorize
        $this->authorize('view', $club);

        $sessionId = $request->query('session_id');

        Log::info('Club checkout success page accessed', [
            'club_id' => $club->id,
            'session_id' => $sessionId,
            'user_id' => auth()->id(),
        ]);

        return Inertia::render('Club/Checkout/Success', [
            'club' => $club->load('subscriptionPlan'),
            'session_id' => $sessionId,
            'message' => 'Subscription activated successfully! Welcome to '.$club->subscriptionPlan?->name.' plan.',
        ]);
    }

    /**
     * Handle checkout cancellation.
     */
    public function cancel(Request $request, Club $club): InertiaResponse|RedirectResponse
    {
        // Authorize
        $this->authorize('view', $club);

        Log::info('Club checkout canceled', [
            'club_id' => $club->id,
            'user_id' => auth()->id(),
        ]);

        return Inertia::render('Club/Checkout/Cancel', [
            'club' => $club,
            'message' => 'Checkout was canceled. You can try again anytime.',
        ]);
    }

    /**
     * Create billing portal session for subscription management.
     */
    public function billingPortal(Request $request, Club $club): JsonResponse|RedirectResponse
    {
        try {
            // Authorize: User must be club admin or owner
            $this->authorize('manageBilling', $club);

            // Check if club has stripe customer
            if (! $club->stripe_customer_id) {
                return response()->json([
                    'error' => 'Club has no active billing account',
                ], 400);
            }

            // Validate return URL
            $validated = $request->validate([
                'return_url' => 'sometimes|url',
            ]);

            $returnUrl = $validated['return_url'] ?? route('club.subscription.index', ['club' => $club->id]);

            // Create billing portal session
            $session = $this->checkoutService->createPortalSession($club, $returnUrl);

            Log::info('Club billing portal session created', [
                'club_id' => $club->id,
                'session_id' => $session->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'portal_url' => $session->url,
            ]);
        } catch (\Exception $e) {
            Log::error('Club billing portal creation failed', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to create billing portal session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show subscription management page for club.
     */
    public function index(Club $club): InertiaResponse
    {
        // Authorize
        $this->authorize('view', $club);

        $club->load(['subscriptionPlan', 'tenant']);

        // Get available plans for this tenant
        $availablePlans = ClubSubscriptionPlan::where('tenant_id', $club->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return Inertia::render('Club/Subscription/Index', [
            'club' => $club,
            'current_plan' => $club->subscriptionPlan,
            'available_plans' => $availablePlans,
            'subscription_limits' => $club->getSubscriptionLimits(),
            'has_active_subscription' => $club->hasActiveSubscription(),
            'is_on_trial' => $club->isOnTrial(),
            'trial_days_remaining' => $club->trialDaysRemaining(),
            'billing_days_remaining' => $club->billingDaysRemaining(),
        ]);
    }

    /**
     * Request invoice payment instead of card payment.
     */
    public function requestInvoicePayment(Request $request, Club $club): RedirectResponse
    {
        try {
            // Authorize: User must be club admin or owner
            $this->authorize('manageBilling', $club);

            // Validate request
            $validated = $request->validate([
                'plan_id' => 'required|exists:club_subscription_plans,id',
                'billing_name' => 'required|string|max:255',
                'billing_email' => 'required|email|max:255',
                'billing_address' => 'nullable|array',
                'billing_address.street' => 'nullable|string|max:255',
                'billing_address.city' => 'nullable|string|max:255',
                'billing_address.postal_code' => 'nullable|string|max:20',
                'billing_address.country' => 'nullable|string|max:2',
                'vat_number' => 'nullable|string|max:50',
                'billing_interval' => 'required|in:monthly,yearly',
            ]);

            $plan = ClubSubscriptionPlan::findOrFail($validated['plan_id']);

            // Validate plan belongs to same tenant
            if ($plan->tenant_id !== $club->tenant_id) {
                return redirect()->back()->withErrors([
                    'plan_id' => 'Der ausgewählte Plan gehört nicht zu diesem Tenant.',
                ]);
            }

            // Check if there's already a pending request
            $existingRequest = ClubInvoiceRequest::where('club_id', $club->id)
                ->where('status', 'pending')
                ->exists();

            if ($existingRequest) {
                return redirect()->back()->withErrors([
                    'general' => 'Es existiert bereits eine ausstehende Rechnungsanfrage für diesen Club.',
                ]);
            }

            // Create invoice request
            ClubInvoiceRequest::create([
                'tenant_id' => $club->tenant_id,
                'club_id' => $club->id,
                'club_subscription_plan_id' => $plan->id,
                'billing_name' => $validated['billing_name'],
                'billing_email' => $validated['billing_email'],
                'billing_address' => $validated['billing_address'] ?? null,
                'vat_number' => $validated['vat_number'] ?? null,
                'billing_interval' => $validated['billing_interval'],
                'status' => 'pending',
                'requested_by' => auth()->id(),
            ]);

            Log::info('Invoice payment requested', [
                'club_id' => $club->id,
                'plan_id' => $plan->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Ihre Rechnungsanfrage wurde eingereicht. Wir werden sie zeitnah bearbeiten.');
        } catch (\Exception $e) {
            Log::error('Invoice payment request failed', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->withErrors([
                'general' => 'Die Anfrage konnte nicht erstellt werden: ' . $e->getMessage(),
            ]);
        }
    }
}
