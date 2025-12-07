<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeConnectSettings;
use App\Models\StripeConnectTransfer;
use App\Models\Tenant;
use App\Services\Stripe\StripeConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StripeConnectAdminController extends Controller
{
    public function __construct(
        protected StripeConnectService $connectService
    ) {}

    /**
     * Display overview of all connected accounts.
     */
    public function index(): Response
    {
        $tenants = Tenant::query()
            ->whereNotNull('stripe_connect_account_id')
            ->orWhere('stripe_connect_status', '!=', 'not_connected')
            ->with(['clubs' => fn ($q) => $q->select('id', 'tenant_id', 'name')])
            ->orderByDesc('stripe_connect_connected_at')
            ->get()
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'stripe_connect_status' => $tenant->stripe_connect_status,
                'stripe_connect_account_id' => $tenant->stripe_connect_account_id,
                'stripe_connect_charges_enabled' => $tenant->stripe_connect_charges_enabled,
                'stripe_connect_payouts_enabled' => $tenant->stripe_connect_payouts_enabled,
                'stripe_connect_connected_at' => $tenant->stripe_connect_connected_at?->toIso8601String(),
                'clubs_count' => $tenant->clubs->count(),
            ]);

        // Statistics
        $stats = [
            'total_connected' => Tenant::where('stripe_connect_status', 'active')->count(),
            'pending_onboarding' => Tenant::where('stripe_connect_status', 'pending')->count(),
            'restricted' => Tenant::where('stripe_connect_status', 'restricted')->count(),
        ];

        // Monthly application fees
        $monthlyFees = StripeConnectTransfer::query()
            ->where('status', 'succeeded')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('application_fee_amount');

        $stats['monthly_fees'] = $monthlyFees;
        $stats['monthly_fees_formatted'] = number_format($monthlyFees / 100, 2, ',', '.').' EUR';

        // Platform settings
        $platformSettings = StripeConnectSettings::getPlatformDefaults();

        return Inertia::render('Admin/StripeConnect/Index', [
            'tenants' => $tenants,
            'stats' => $stats,
            'platformSettings' => $platformSettings ? [
                'application_fee_percent' => $platformSettings->application_fee_percent,
                'application_fee_fixed' => $platformSettings->application_fee_fixed,
                'payout_schedule' => $platformSettings->payout_schedule,
                'payout_delay_days' => $platformSettings->payout_delay_days,
            ] : null,
        ]);
    }

    /**
     * Show details for a specific tenant's Connect account.
     */
    public function show(Tenant $tenant): Response
    {
        $data = [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'billing_email' => $tenant->billing_email,
                'country_code' => $tenant->country_code,
                'stripe_connect_status' => $tenant->stripe_connect_status,
                'stripe_connect_account_id' => $tenant->stripe_connect_account_id,
                'stripe_connect_charges_enabled' => $tenant->stripe_connect_charges_enabled,
                'stripe_connect_payouts_enabled' => $tenant->stripe_connect_payouts_enabled,
                'stripe_connect_details_submitted' => $tenant->stripe_connect_details_submitted,
                'stripe_connect_connected_at' => $tenant->stripe_connect_connected_at?->toIso8601String(),
                'application_fee_percent' => $tenant->getApplicationFeePercent(),
            ],
        ];

        // Get balance and payouts if connected
        if ($tenant->hasActiveStripeConnect()) {
            $data['balance'] = $this->connectService->getAccountBalance($tenant);
            $data['recentPayouts'] = array_map(fn ($payout) => [
                'id' => $payout->id,
                'amount' => $payout->amount,
                'currency' => $payout->currency,
                'status' => $payout->status,
                'arrival_date' => date('Y-m-d', $payout->arrival_date),
            ], $this->connectService->getRecentPayouts($tenant, 10));
        }

        // Recent transfers
        $data['recentTransfers'] = StripeConnectTransfer::query()
            ->where('tenant_id', $tenant->id)
            ->with('club:id,name')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($transfer) => [
                'id' => $transfer->id,
                'club_name' => $transfer->club?->name,
                'gross_amount' => $transfer->gross_amount,
                'application_fee_amount' => $transfer->application_fee_amount,
                'net_amount' => $transfer->net_amount,
                'currency' => $transfer->currency,
                'status' => $transfer->status,
                'created_at' => $transfer->created_at->toIso8601String(),
            ]);

        // Monthly stats
        $data['monthlyStats'] = [
            'total_volume' => StripeConnectTransfer::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'succeeded')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('gross_amount'),
            'total_fees' => StripeConnectTransfer::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'succeeded')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('application_fee_amount'),
            'transfer_count' => StripeConnectTransfer::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'succeeded')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        return Inertia::render('Admin/StripeConnect/Show', $data);
    }

    /**
     * Get analytics data.
     */
    public function analytics(): JsonResponse
    {
        // Monthly revenue by tenant
        $monthlyByTenant = StripeConnectTransfer::query()
            ->select(
                'tenant_id',
                DB::raw('SUM(gross_amount) as total_volume'),
                DB::raw('SUM(application_fee_amount) as total_fees'),
                DB::raw('COUNT(*) as transfer_count')
            )
            ->where('status', 'succeeded')
            ->where('created_at', '>=', now()->startOfMonth())
            ->groupBy('tenant_id')
            ->with('tenant:id,name')
            ->get();

        // Last 6 months trend
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $data = StripeConnectTransfer::query()
                ->where('status', 'succeeded')
                ->whereBetween('created_at', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ])
                ->selectRaw('SUM(gross_amount) as volume, SUM(application_fee_amount) as fees, COUNT(*) as count')
                ->first();

            $monthlyTrend[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'volume' => (int) ($data->volume ?? 0),
                'fees' => (int) ($data->fees ?? 0),
                'count' => (int) ($data->count ?? 0),
            ];
        }

        return response()->json([
            'byTenant' => $monthlyByTenant,
            'monthlyTrend' => $monthlyTrend,
        ]);
    }

    /**
     * Update platform-wide fee settings.
     */
    public function updatePlatformFee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_fee_percent' => 'required|numeric|min:0|max:50',
            'application_fee_fixed' => 'nullable|numeric|min:0',
            'payout_schedule' => 'nullable|in:daily,weekly,monthly,manual',
            'payout_delay_days' => 'nullable|integer|min:2|max:14',
        ]);

        $settings = StripeConnectSettings::getPlatformDefaults();

        if (! $settings) {
            $settings = StripeConnectSettings::create([
                'tenant_id' => null,
                'application_fee_percent' => $validated['application_fee_percent'],
                'application_fee_fixed' => $validated['application_fee_fixed'] ?? 0,
                'payout_schedule' => $validated['payout_schedule'] ?? 'daily',
                'payout_delay_days' => $validated['payout_delay_days'] ?? 7,
            ]);
        } else {
            $settings->update($validated);
        }

        return response()->json([
            'message' => 'Platform-Gebühren wurden aktualisiert',
            'settings' => $settings,
        ]);
    }

    /**
     * Refresh a tenant's Connect status from Stripe.
     */
    public function refreshTenantStatus(Tenant $tenant): JsonResponse
    {
        if (! $tenant->stripe_connect_account_id) {
            return response()->json([
                'error' => 'Tenant hat keinen verbundenen Stripe Account',
            ], 400);
        }

        try {
            $this->connectService->refreshAccountStatus($tenant);
            $tenant->refresh();

            return response()->json([
                'message' => 'Status wurde aktualisiert',
                'status' => $tenant->stripe_connect_status,
                'charges_enabled' => $tenant->stripe_connect_charges_enabled,
                'payouts_enabled' => $tenant->stripe_connect_payouts_enabled,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
