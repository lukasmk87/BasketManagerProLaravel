<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Stripe\StripeConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StripeConnectController extends Controller
{
    public function __construct(
        protected StripeConnectService $connectService
    ) {}

    /**
     * Show the Connect dashboard for tenant admins.
     */
    public function index(Request $request): Response
    {
        $tenant = $this->getTenant($request);

        $data = [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'stripe_connect_status' => $tenant->stripe_connect_status,
                'stripe_connect_account_id' => $tenant->stripe_connect_account_id,
                'stripe_connect_charges_enabled' => $tenant->stripe_connect_charges_enabled,
                'stripe_connect_payouts_enabled' => $tenant->stripe_connect_payouts_enabled,
                'stripe_connect_details_submitted' => $tenant->stripe_connect_details_submitted,
                'stripe_connect_connected_at' => $tenant->stripe_connect_connected_at?->toIso8601String(),
            ],
            'applicationFeePercent' => $tenant->getApplicationFeePercent(),
        ];

        // Add balance and payouts if connected
        if ($tenant->hasActiveStripeConnect()) {
            $data['balance'] = $this->connectService->getAccountBalance($tenant);
            $data['recentPayouts'] = array_map(function ($payout) {
                return [
                    'id' => $payout->id,
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                    'status' => $payout->status,
                    'arrival_date' => date('Y-m-d', $payout->arrival_date),
                ];
            }, $this->connectService->getRecentPayouts($tenant, 5));
        }

        return Inertia::render('Tenant/StripeConnect/Index', $data);
    }

    /**
     * Start the Connect onboarding process.
     */
    public function startOnboarding(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);

        // Prevent re-onboarding if already connected
        if ($tenant->hasActiveStripeConnect()) {
            return response()->json([
                'error' => 'Tenant already has an active Stripe Connect account',
            ], 400);
        }

        $returnUrl = route('stripe-connect.callback');
        $refreshUrl = route('stripe-connect.refresh');

        try {
            $url = $this->connectService->createAccountLink($tenant, $returnUrl, $refreshUrl);

            return response()->json([
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle the OAuth callback from Stripe.
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant($request);

        // Refresh account status from Stripe
        $this->connectService->refreshAccountStatus($tenant);

        $message = $tenant->isStripeConnectReady()
            ? 'Stripe Connect erfolgreich eingerichtet!'
            : 'Stripe Connect Einrichtung gestartet. Bitte vervollständigen Sie die Verifizierung.';

        return redirect()
            ->route('stripe-connect.index')
            ->with('success', $message);
    }

    /**
     * Handle refresh URL (when onboarding is interrupted).
     */
    public function refresh(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant($request);

        // Generate a new onboarding link
        $returnUrl = route('stripe-connect.callback');
        $refreshUrl = route('stripe-connect.refresh');

        try {
            $url = $this->connectService->createAccountLink($tenant, $returnUrl, $refreshUrl);

            return redirect()->away($url);
        } catch (\Exception $e) {
            return redirect()
                ->route('stripe-connect.index')
                ->with('error', 'Onboarding konnte nicht fortgesetzt werden: '.$e->getMessage());
        }
    }

    /**
     * Get current Connect status.
     */
    public function status(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);

        // Refresh status from Stripe
        if ($tenant->stripe_connect_account_id) {
            $this->connectService->refreshAccountStatus($tenant);
            $tenant->refresh();
        }

        return response()->json([
            'status' => $tenant->stripe_connect_status,
            'account_id' => $tenant->stripe_connect_account_id,
            'charges_enabled' => $tenant->stripe_connect_charges_enabled,
            'payouts_enabled' => $tenant->stripe_connect_payouts_enabled,
            'details_submitted' => $tenant->stripe_connect_details_submitted,
            'connected_at' => $tenant->stripe_connect_connected_at?->toIso8601String(),
            'is_ready' => $tenant->isStripeConnectReady(),
        ]);
    }

    /**
     * Disconnect the Connect account.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);

        if (! $tenant->stripe_connect_account_id) {
            return response()->json([
                'error' => 'Kein Stripe Connect Account verbunden',
            ], 400);
        }

        try {
            $this->connectService->disconnectAccount($tenant);

            return response()->json([
                'message' => 'Stripe Connect Account wurde getrennt',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Redirect to Express Dashboard.
     */
    public function dashboard(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant($request);

        if (! $tenant->hasActiveStripeConnect()) {
            return redirect()
                ->route('stripe-connect.index')
                ->with('error', 'Bitte richten Sie zuerst Stripe Connect ein');
        }

        try {
            $url = $this->connectService->getExpressDashboardLink($tenant);

            return redirect()->away($url);
        } catch (\Exception $e) {
            return redirect()
                ->route('stripe-connect.index')
                ->with('error', 'Dashboard konnte nicht geöffnet werden: '.$e->getMessage());
        }
    }

    /**
     * Get balance information.
     */
    public function balance(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);

        if (! $tenant->hasActiveStripeConnect()) {
            return response()->json([
                'error' => 'Kein aktiver Stripe Connect Account',
            ], 400);
        }

        $balance = $this->connectService->getAccountBalance($tenant);

        return response()->json($balance);
    }

    /**
     * Preview fees for an amount.
     */
    public function previewFees(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:50', // Min 50 cents
        ]);

        $tenant = $this->getTenant($request);
        $amountInCents = $request->input('amount');

        $applicationFee = $tenant->calculateApplicationFee($amountInCents);
        $netAmount = $amountInCents - $applicationFee;

        return response()->json([
            'gross_amount' => $amountInCents,
            'application_fee' => $applicationFee,
            'net_amount' => $netAmount,
            'fee_percent' => $tenant->getApplicationFeePercent(),
        ]);
    }

    /**
     * Get the current tenant from the request.
     */
    protected function getTenant(Request $request): Tenant
    {
        $tenant = app('tenant');

        if (! $tenant) {
            abort(403, 'Kein Tenant-Kontext verfügbar');
        }

        return $tenant;
    }
}
