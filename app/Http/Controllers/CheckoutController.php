<?php

namespace App\Http\Controllers;

use App\Services\Stripe\CheckoutService;
use App\Services\Stripe\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

/**
 * Controller for handling Stripe checkout flows
 * Supports subscriptions, one-time payments, and payment method setup
 */
class CheckoutController extends Controller
{
    private CheckoutService $checkoutService;
    private PaymentMethodService $paymentMethodService;

    public function __construct(
        CheckoutService $checkoutService,
        PaymentMethodService $paymentMethodService
    ) {
        $this->checkoutService = $checkoutService;
        $this->paymentMethodService = $paymentMethodService;
        $this->middleware('auth');
        $this->middleware('tenant');
    }

    /**
     * Show subscription checkout page
     *
     * @param Request $request
     * @return View
     */
    public function subscription(Request $request): View
    {
        $tenant = $request->get('tenant');
        
        // Define subscription tiers and prices
        $subscriptionTiers = [
            'basic' => [
                'name' => 'Basic',
                'price_id' => config('services.stripe.prices.basic_monthly'),
                'price' => 4900, // €49.00
                'features' => [
                    'Bis zu 5 Teams',
                    '50 Spieler',
                    'Live-Scoring',
                    'Basis-Analytics',
                    'E-Mail Support',
                ],
            ],
            'professional' => [
                'name' => 'Professional',
                'price_id' => config('services.stripe.prices.professional_monthly'),
                'price' => 14900, // €149.00
                'features' => [
                    'Bis zu 20 Teams',
                    '500 Spieler',
                    'KI-Insights',
                    'Video-Analyse',
                    'API-Zugriff',
                    'Priority Support',
                ],
                'recommended' => true,
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price_id' => config('services.stripe.prices.enterprise_monthly'),
                'price' => 29900, // €299.00
                'features' => [
                    'Unbegrenzte Teams',
                    'Unbegrenzte Spieler',
                    'Erweiterte KI-Features',
                    'White-Label Option',
                    'Dedicated Support',
                    'Custom Integrationen',
                ],
            ],
        ];

        return view('checkout.subscription', [
            'tenant' => $tenant,
            'tiers' => $subscriptionTiers,
            'currentTier' => $tenant->subscription_tier ?? 'trial',
        ]);
    }

    /**
     * Create subscription checkout session
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createSubscriptionSession(Request $request): JsonResponse
    {
        $request->validate([
            'price_id' => 'required|string',
            'billing_cycle' => 'in:monthly,yearly',
        ]);

        try {
            $tenant = $request->get('tenant');
            $priceId = $request->input('price_id');
            
            // Adjust price ID for billing cycle
            if ($request->input('billing_cycle') === 'yearly') {
                $priceId = str_replace('_monthly', '_yearly', $priceId);
            }

            $session = $this->checkoutService->createSubscriptionCheckout($tenant, $priceId, [
                'subscription_data' => [
                    'trial_period_days' => $tenant->hasTrialExpired() ? 0 : 14,
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'billing_cycle' => $request->input('billing_cycle', 'monthly'),
                    ],
                ],
            ]);

            Log::info('Subscription checkout session created', [
                'tenant_id' => $tenant->id,
                'price_id' => $priceId,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to create subscription checkout session', [
                'tenant_id' => $request->get('tenant')->id,
                'price_id' => $request->input('price_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Checkout-Session konnte nicht erstellt werden. Bitte versuchen Sie es erneut.',
            ], 400);
        }
    }

    /**
     * Show payment methods management page
     *
     * @param Request $request
     * @return View
     */
    public function paymentMethods(Request $request): View
    {
        $tenant = $request->get('tenant');
        $paymentMethods = $this->paymentMethodService->getPaymentMethods($tenant);
        
        return view('checkout.payment-methods', [
            'tenant' => $tenant,
            'paymentMethods' => $paymentMethods,
            'supportedMethods' => $this->paymentMethodService->getLocalizedPaymentMethodNames(),
        ]);
    }

    /**
     * Create setup session for adding payment methods
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createSetupSession(Request $request): JsonResponse
    {
        try {
            $tenant = $request->get('tenant');
            
            $session = $this->checkoutService->createSetupCheckout($tenant);

            Log::info('Setup checkout session created', [
                'tenant_id' => $tenant->id,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to create setup checkout session', [
                'tenant_id' => $request->get('tenant')->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Zahlungsmethoden-Setup konnte nicht gestartet werden.',
            ], 400);
        }
    }

    /**
     * Handle successful subscription checkout
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function subscriptionSuccess(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            return redirect()->route('subscription.index')
                ->with('error', 'Keine gültige Checkout-Session gefunden.');
        }

        try {
            $tenant = $request->get('tenant');
            $session = $this->checkoutService->retrieveSession($sessionId);
            
            if ($session->payment_status === 'paid') {
                $subscription = $this->checkoutService->handleSuccessfulSubscriptionCheckout($session, $tenant);
                
                if ($subscription) {
                    Log::info('Subscription activated successfully', [
                        'tenant_id' => $tenant->id,
                        'subscription_id' => $subscription->id,
                        'session_id' => $sessionId,
                    ]);

                    return redirect()->route('dashboard')
                        ->with('success', 'Ihr Abonnement wurde erfolgreich aktiviert! Willkommen bei BasketManager Pro.');
                }
            }

            return redirect()->route('subscription.index')
                ->with('warning', 'Ihr Abonnement wird verarbeitet. Sie erhalten eine Bestätigung per E-Mail.');

        } catch (ApiErrorException $e) {
            Log::error('Failed to handle subscription success', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('subscription.index')
                ->with('error', 'Es gab ein Problem bei der Aktivierung Ihres Abonnements. Unser Support wurde benachrichtigt.');
        }
    }

    /**
     * Handle cancelled checkout
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function subscriptionCancel(Request $request): RedirectResponse
    {
        Log::info('Subscription checkout cancelled', [
            'tenant_id' => $request->get('tenant')->id,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('subscription.index')
            ->with('info', 'Checkout wurde abgebrochen. Sie können jederzeit zurückkehren, um Ihr Abonnement zu aktivieren.');
    }

    /**
     * Handle successful payment method setup
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function paymentMethodSuccess(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            return redirect()->route('checkout.payment-methods')
                ->with('error', 'Keine gültige Setup-Session gefunden.');
        }

        try {
            $session = $this->checkoutService->retrieveSession($sessionId);
            
            if ($session->setup_intent) {
                Log::info('Payment method setup completed', [
                    'tenant_id' => $request->get('tenant')->id,
                    'session_id' => $sessionId,
                    'setup_intent' => $session->setup_intent,
                ]);

                return redirect()->route('checkout.payment-methods')
                    ->with('success', 'Zahlungsmethode wurde erfolgreich hinzugefügt.');
            }

        } catch (ApiErrorException $e) {
            Log::error('Failed to handle payment method setup success', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('checkout.payment-methods')
            ->with('error', 'Es gab ein Problem beim Hinzufügen der Zahlungsmethode.');
    }

    /**
     * Remove payment method
     *
     * @param Request $request
     * @param string $paymentMethodId
     * @return JsonResponse
     */
    public function removePaymentMethod(Request $request, string $paymentMethodId): JsonResponse
    {
        try {
            $tenant = $request->get('tenant');
            
            $this->paymentMethodService->detachPaymentMethod($tenant, $paymentMethodId);

            Log::info('Payment method removed', [
                'tenant_id' => $tenant->id,
                'payment_method_id' => $paymentMethodId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Zahlungsmethode wurde erfolgreich entfernt.',
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to remove payment method', [
                'tenant_id' => $request->get('tenant')->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Zahlungsmethode konnte nicht entfernt werden.',
            ], 400);
        }
    }

    /**
     * Set default payment method
     *
     * @param Request $request
     * @param string $paymentMethodId
     * @return JsonResponse
     */
    public function setDefaultPaymentMethod(Request $request, string $paymentMethodId): JsonResponse
    {
        try {
            $tenant = $request->get('tenant');
            
            $this->paymentMethodService->setDefaultPaymentMethod($tenant, $paymentMethodId);

            Log::info('Default payment method updated', [
                'tenant_id' => $tenant->id,
                'payment_method_id' => $paymentMethodId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Standard-Zahlungsmethode wurde aktualisiert.',
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to set default payment method', [
                'tenant_id' => $request->get('tenant')->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Standard-Zahlungsmethode konnte nicht gesetzt werden.',
            ], 400);
        }
    }
}