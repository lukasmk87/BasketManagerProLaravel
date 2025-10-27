<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Services\Stripe\ClubInvoiceService;
use App\Services\Stripe\ClubPaymentMethodService;
use App\Services\Stripe\ClubSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Controller for managing club billing operations.
 *
 * Handles invoices, payment methods, and proration previews for clubs
 * with Stripe subscriptions.
 */
class ClubBillingController extends Controller
{
    public function __construct(
        private ClubInvoiceService $invoiceService,
        private ClubPaymentMethodService $paymentMethodService,
        private ClubSubscriptionService $subscriptionService
    ) {}

    // ============================
    // INVOICE MANAGEMENT
    // ============================

    /**
     * List all invoices for a club.
     */
    public function indexInvoices(Request $request, Club $club): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            // Validate request
            $validated = $request->validate([
                'limit' => 'sometimes|integer|min:1|max:100',
                'starting_after' => 'sometimes|string',
                'ending_before' => 'sometimes|string',
                'status' => 'sometimes|in:draft,open,paid,uncollectible,void',
            ]);

            $invoices = $this->invoiceService->getInvoices($club, $validated);

            return response()->json([
                'invoices' => $invoices,
                'club_id' => $club->id,
                'club_name' => $club->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve club invoices', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve invoices: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single invoice by ID.
     */
    public function showInvoice(Request $request, Club $club, string $invoiceId): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            $invoice = $this->invoiceService->getInvoice($club, $invoiceId);

            return response()->json([
                'invoice' => $invoice,
                'club_id' => $club->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve club invoice', [
                'club_id' => $club->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve invoice: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upcoming invoice preview.
     */
    public function upcomingInvoice(Request $request, Club $club): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            $upcomingInvoice = $this->invoiceService->getUpcomingInvoice($club);

            if (! $upcomingInvoice) {
                return response()->json([
                    'message' => 'No upcoming invoice available',
                    'club_id' => $club->id,
                ], 404);
            }

            return response()->json([
                'invoice' => $upcomingInvoice,
                'club_id' => $club->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve upcoming invoice', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve upcoming invoice: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoicePdf(Request $request, Club $club, string $invoiceId): RedirectResponse|JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            $pdfUrl = $this->invoiceService->getInvoicePdfUrl($club, $invoiceId);

            // Redirect to Stripe's PDF URL
            return redirect()->away($pdfUrl);
        } catch (\Exception $e) {
            Log::error('Failed to download invoice PDF', [
                'club_id' => $club->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to download invoice PDF: '.$e->getMessage(),
            ], 500);
        }
    }

    // ============================
    // PAYMENT METHOD MANAGEMENT
    // ============================

    /**
     * List all payment methods for a club.
     */
    public function indexPaymentMethods(Request $request, Club $club): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            // Validate request
            $validated = $request->validate([
                'type' => 'sometimes|in:card,sepa_debit,sofort,giropay,eps,bancontact,ideal',
            ]);

            $type = $validated['type'] ?? 'card';
            $paymentMethods = $this->paymentMethodService->listPaymentMethods($club, $type);

            return response()->json([
                'payment_methods' => $paymentMethods,
                'club_id' => $club->id,
                'type' => $type,
                'available_types' => $this->paymentMethodService->getGermanPaymentMethods(),
                'localized_names' => $this->paymentMethodService->getLocalizedPaymentMethodNames(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve payment methods', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve payment methods: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create SetupIntent for adding payment methods.
     */
    public function createSetupIntent(Request $request, Club $club): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            // Validate request
            $validated = $request->validate([
                'usage' => 'sometimes|in:on_session,off_session',
                'return_url' => 'sometimes|url',
            ]);

            $setupIntent = $this->paymentMethodService->createSetupIntent($club, $validated);

            return response()->json([
                'client_secret' => $setupIntent->client_secret,
                'setup_intent_id' => $setupIntent->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create setup intent', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to create setup intent: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach payment method to club.
     */
    public function attachPaymentMethod(Request $request, Club $club): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            // Validate request
            $validated = $request->validate([
                'payment_method_id' => 'required|string',
                'set_as_default' => 'sometimes|boolean',
            ]);

            $paymentMethod = $this->paymentMethodService->attachPaymentMethod(
                $club,
                $validated['payment_method_id'],
                $validated['set_as_default'] ?? false
            );

            return response()->json([
                'message' => 'Payment method attached successfully',
                'payment_method_id' => $paymentMethod->id,
                'is_default' => $validated['set_as_default'] ?? false,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to attach payment method', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to attach payment method: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detach payment method from club.
     */
    public function detachPaymentMethod(Request $request, Club $club, string $paymentMethodId): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            $this->paymentMethodService->detachPaymentMethod($club, $paymentMethodId);

            return response()->json([
                'message' => 'Payment method detached successfully',
                'payment_method_id' => $paymentMethodId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to detach payment method', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to detach payment method: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update payment method billing details.
     */
    public function updatePaymentMethod(Request $request, Club $club, string $paymentMethodId): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            // Validate request
            $validated = $request->validate([
                'billing_details' => 'required|array',
                'billing_details.name' => 'sometimes|string',
                'billing_details.email' => 'sometimes|email',
                'billing_details.phone' => 'sometimes|string',
                'billing_details.address' => 'sometimes|array',
            ]);

            $paymentMethod = $this->paymentMethodService->updatePaymentMethod(
                $club,
                $paymentMethodId,
                $validated['billing_details']
            );

            return response()->json([
                'message' => 'Payment method updated successfully',
                'payment_method_id' => $paymentMethod->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update payment method', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to update payment method: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set default payment method for club.
     */
    public function setDefaultPaymentMethod(Request $request, Club $club, string $paymentMethodId): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            $this->paymentMethodService->setDefaultPaymentMethod($club, $paymentMethodId);

            return response()->json([
                'message' => 'Default payment method set successfully',
                'payment_method_id' => $paymentMethodId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to set default payment method', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to set default payment method: '.$e->getMessage(),
            ], 500);
        }
    }

    // ============================
    // PRORATION PREVIEW
    // ============================

    /**
     * Preview plan swap with proration details.
     */
    public function previewPlanSwap(Request $request, Club $club): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            // Validate request
            $validated = $request->validate([
                'new_plan_id' => 'required|exists:club_subscription_plans,id',
                'billing_interval' => 'sometimes|in:monthly,yearly',
                'proration_behavior' => 'sometimes|in:create_prorations,none,always_invoice',
            ]);

            $newPlan = ClubSubscriptionPlan::findOrFail($validated['new_plan_id']);

            // Validate plan belongs to same tenant
            if ($newPlan->tenant_id !== $club->tenant_id) {
                return response()->json([
                    'error' => 'Plan does not belong to club\'s tenant',
                ], 403);
            }

            $preview = $this->subscriptionService->previewPlanSwap($club, $newPlan, [
                'billing_interval' => $validated['billing_interval'] ?? 'monthly',
                'proration_behavior' => $validated['proration_behavior'] ?? 'create_prorations',
            ]);

            return response()->json([
                'preview' => $preview,
                'club_id' => $club->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to preview plan swap', [
                'club_id' => $club->id,
                'new_plan_id' => $request->input('new_plan_id'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to preview plan swap: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute plan swap (upgrade/downgrade).
     */
    public function swapPlan(Request $request, Club $club): JsonResponse
    {
        try {
            // Authorize
            $this->authorize('manageBilling', $club);

            // Validate request
            $validated = $request->validate([
                'new_plan_id' => 'required|exists:club_subscription_plans,id',
                'billing_interval' => 'sometimes|in:monthly,yearly',
                'proration_behavior' => 'sometimes|in:create_prorations,none,always_invoice',
            ]);

            $newPlan = ClubSubscriptionPlan::findOrFail($validated['new_plan_id']);

            // Validate plan belongs to same tenant
            if ($newPlan->tenant_id !== $club->tenant_id) {
                return response()->json([
                    'error' => 'Plan does not belong to club\'s tenant',
                ], 403);
            }

            // Execute the swap
            $this->subscriptionService->swapPlan($club, $newPlan, [
                'billing_interval' => $validated['billing_interval'] ?? 'monthly',
                'proration_behavior' => $validated['proration_behavior'] ?? 'create_prorations',
            ]);

            // Reload club to get updated subscription
            $club->refresh();

            return response()->json([
                'message' => 'Plan swapped successfully',
                'club_id' => $club->id,
                'new_plan_id' => $newPlan->id,
                'new_plan_name' => $newPlan->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to swap plan', [
                'club_id' => $club->id,
                'new_plan_id' => $request->input('new_plan_id'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'error' => 'Failed to swap plan: '.$e->getMessage(),
            ], 500);
        }
    }
}
