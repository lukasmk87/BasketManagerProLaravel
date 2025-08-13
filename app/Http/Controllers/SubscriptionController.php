<?php

namespace App\Http\Controllers;

use App\Services\Stripe\CashierTenantManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private CashierTenantManager $cashierManager
    ) {}

    /**
     * Show subscription management page.
     */
    public function index(): Response
    {
        $tenant = $this->cashierManager->getCurrentTenant();
        $subscription = $this->cashierManager->getTenantSubscription();
        $pricing = $this->cashierManager->getPricingInfo();
        
        return Inertia::render('Subscription/Index', [
            'tenant' => $tenant->only([
                'id', 'name', 'subscription_tier', 'trial_ends_at'
            ]),
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'quantity' => $subscription->quantity,
                'trial_ends_at' => $subscription->trial_ends_at,
                'ends_at' => $subscription->ends_at,
                'created_at' => $subscription->created_at,
            ] : null,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Create checkout session for subscription.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'price_id' => 'required|string',
        ]);

        try {
            $tenant = $this->cashierManager->getCurrentTenant();
            
            if (!$tenant) {
                return response()->json(['error' => 'Tenant not found'], 404);
            }

            // Create checkout session
            $checkoutSession = $this->cashierManager->createCheckoutSession(
                $request->price_id,
                route('subscription.success'),
                route('subscription.cancel'),
                [
                    'mode' => 'subscription',
                    'allow_promotion_codes' => true,
                    'billing_address_collection' => 'required',
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'price_id' => $request->price_id,
                    ],
                ]
            );

            return response()->json([
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Subscription checkout failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'price_id' => $request->price_id,
            ]);

            return response()->json([
                'error' => 'Failed to create checkout session'
            ], 500);
        }
    }

    /**
     * Handle successful subscription.
     */
    public function success(Request $request): RedirectResponse
    {
        return redirect()->route('subscription.index')
            ->with('success', 'Subscription activated successfully!');
    }

    /**
     * Handle cancelled subscription.
     */
    public function cancel(Request $request): RedirectResponse
    {
        return redirect()->route('subscription.index')
            ->with('info', 'Subscription setup was cancelled.');
    }

    /**
     * Cancel current subscription.
     */
    public function cancelSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'immediately' => 'boolean',
        ]);

        try {
            $success = $this->cashierManager->cancelTenantSubscription(
                'default', 
                $request->boolean('immediately', false)
            );

            if (!$success) {
                return response()->json(['error' => 'No active subscription found'], 404);
            }

            $message = $request->boolean('immediately') 
                ? 'Subscription cancelled immediately.' 
                : 'Subscription will be cancelled at the end of the current period.';

            return response()->json(['message' => $message]);
            
        } catch (\Exception $e) {
            logger()->error('Subscription cancellation failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->cashierManager->getCurrentTenant()->id ?? null,
            ]);

            return response()->json(['error' => 'Failed to cancel subscription'], 500);
        }
    }

    /**
     * Resume cancelled subscription.
     */
    public function resume(): JsonResponse
    {
        try {
            $success = $this->cashierManager->resumeTenantSubscription();

            if (!$success) {
                return response()->json([
                    'error' => 'No subscription to resume or subscription not in grace period'
                ], 400);
            }

            return response()->json(['message' => 'Subscription resumed successfully.']);
            
        } catch (\Exception $e) {
            logger()->error('Subscription resumption failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->cashierManager->getCurrentTenant()->id ?? null,
            ]);

            return response()->json(['error' => 'Failed to resume subscription'], 500);
        }
    }

    /**
     * Swap subscription to different plan.
     */
    public function swap(Request $request): JsonResponse
    {
        $request->validate([
            'price_id' => 'required|string',
        ]);

        try {
            $subscription = $this->cashierManager->swapTenantSubscription(
                $request->price_id,
                'default',
                ['proration_behavior' => 'create_prorations']
            );

            if (!$subscription) {
                return response()->json(['error' => 'No active subscription found'], 404);
            }

            return response()->json([
                'message' => 'Subscription updated successfully.',
                'subscription' => [
                    'stripe_price' => $subscription->stripe_price,
                    'stripe_status' => $subscription->stripe_status,
                ]
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Subscription swap failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->cashierManager->getCurrentTenant()->id ?? null,
                'new_price_id' => $request->price_id,
            ]);

            return response()->json(['error' => 'Failed to update subscription'], 500);
        }
    }

    /**
     * Get subscription invoices.
     */
    public function invoices(): JsonResponse
    {
        try {
            $tenant = $this->cashierManager->getCurrentTenant();
            
            if (!$tenant->hasStripeId()) {
                return response()->json(['invoices' => []]);
            }

            $invoices = $tenant->invoices();

            return response()->json([
                'invoices' => $invoices->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'number' => $invoice->number,
                        'status' => $invoice->status,
                        'total' => $invoice->total(),
                        'currency' => $invoice->currency,
                        'date' => $invoice->date()->toDateString(),
                        'download_url' => route('subscription.invoice', $invoice->id),
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Failed to fetch invoices', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->cashierManager->getCurrentTenant()->id ?? null,
            ]);

            return response()->json(['error' => 'Failed to fetch invoices'], 500);
        }
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoice(Request $request, string $invoiceId)
    {
        try {
            $tenant = $this->cashierManager->getCurrentTenant();
            
            return $tenant->downloadInvoice($invoiceId, [
                'vendor' => 'BasketManager Pro',
                'product' => 'Basketball Club Management',
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Failed to download invoice', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->cashierManager->getCurrentTenant()->id ?? null,
                'invoice_id' => $invoiceId,
            ]);

            abort(404);
        }
    }

    /**
     * Update payment method.
     */
    public function updatePaymentMethod(Request $request): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        try {
            $tenant = $this->cashierManager->getCurrentTenant();
            
            // Update default payment method
            $tenant->updateDefaultPaymentMethod($request->payment_method);

            return response()->json(['message' => 'Payment method updated successfully.']);
            
        } catch (\Exception $e) {
            logger()->error('Failed to update payment method', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->cashierManager->getCurrentTenant()->id ?? null,
            ]);

            return response()->json(['error' => 'Failed to update payment method'], 500);
        }
    }

    /**
     * Get Stripe configuration for frontend.
     */
    public function config(): JsonResponse
    {
        $config = $this->cashierManager->getStripeConfig();
        
        return response()->json([
            'publishable_key' => $config['key'] ?? config('stripe.api_key'),
            'currency' => 'eur',
            'country' => 'DE',
        ]);
    }
}