<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantAdmin\CreateVoucherRequest;
use App\Http\Requests\TenantAdmin\UpdateVoucherRequest;
use App\Models\ClubSubscriptionPlan;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function __construct(
        private VoucherService $voucherService
    ) {}

    /**
     * Display vouchers for current tenant.
     */
    public function index(): Response
    {
        $tenant = tenant();
        $vouchers = $this->voucherService->getVouchersForTenant($tenant);
        $statistics = $this->voucherService->getOverallStatistics($tenant);

        return Inertia::render('TenantAdmin/Vouchers/Index', [
            'vouchers' => $vouchers->map(fn ($voucher) => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'type' => $voucher->type,
                'type_label' => $voucher->getTypeLabel(),
                'discount_label' => $voucher->getFormattedDiscount(),
                'duration_label' => $voucher->getDurationLabel(),
                'is_system_wide' => $voucher->isSystemWide(),
                'is_active' => $voucher->is_active,
                'status_label' => $voucher->getStatusLabel(),
                'status_color' => $voucher->getStatusColor(),
                'current_redemptions' => $voucher->current_redemptions,
                'max_redemptions' => $voucher->max_redemptions,
                'remaining_redemptions' => $voucher->getRemainingRedemptions(),
                'valid_from' => $voucher->valid_from?->format('d.m.Y'),
                'valid_until' => $voucher->valid_until?->format('d.m.Y'),
                'created_at' => $voucher->created_at->format('d.m.Y H:i'),
                'can_edit' => ! $voucher->isSystemWide(),
            ]),
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): Response
    {
        $tenant = tenant();

        return Inertia::render('TenantAdmin/Vouchers/Create', [
            'plans' => ClubSubscriptionPlan::where('tenant_id', $tenant->id)
                ->orWhereNull('tenant_id')
                ->orderBy('name')
                ->get(['id', 'name', 'price']),
            'voucherTypes' => $this->getVoucherTypes(),
        ]);
    }

    /**
     * Store a new voucher (always tenant-scoped).
     */
    public function store(CreateVoucherRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = tenant()->id;

        $voucher = $this->voucherService->createVoucher($data, auth()->user());

        return redirect()
            ->route('tenant-admin.vouchers.show', $voucher)
            ->with('success', 'Voucher erfolgreich erstellt!');
    }

    /**
     * Display voucher details.
     */
    public function show(Voucher $voucher): Response
    {
        $this->authorize('view', $voucher);

        $statistics = $this->voucherService->getVoucherStatistics($voucher);

        return Inertia::render('TenantAdmin/Vouchers/Show', [
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'description' => $voucher->description,
                'type' => $voucher->type,
                'type_label' => $voucher->getTypeLabel(),
                'discount_percent' => $voucher->discount_percent,
                'discount_amount' => $voucher->discount_amount,
                'trial_extension_days' => $voucher->trial_extension_days,
                'discount_label' => $voucher->getFormattedDiscount(),
                'duration_months' => $voucher->duration_months,
                'duration_label' => $voucher->getDurationLabel(),
                'is_system_wide' => $voucher->isSystemWide(),
                'is_active' => $voucher->is_active,
                'status_label' => $voucher->getStatusLabel(),
                'status_color' => $voucher->getStatusColor(),
                'current_redemptions' => $voucher->current_redemptions,
                'max_redemptions' => $voucher->max_redemptions,
                'remaining_redemptions' => $voucher->getRemainingRedemptions(),
                'valid_from' => $voucher->valid_from?->format('Y-m-d'),
                'valid_until' => $voucher->valid_until?->format('Y-m-d'),
                'created_at' => $voucher->created_at->format('d.m.Y H:i'),
                'can_edit' => ! $voucher->isSystemWide(),
            ],
            'statistics' => [
                'total_redemptions' => $statistics['total_redemptions'],
                'total_discount_given' => number_format($statistics['total_discount_given'], 2),
                'active_redemptions' => $statistics['active_redemptions'],
                'completed_redemptions' => $statistics['completed_redemptions'],
            ],
            'redemptions' => $statistics['redemptions']->map(fn ($r) => [
                'id' => $r->id,
                'club_name' => $r->club?->name,
                'redeemed_by' => $r->redeemedByUser?->name,
                'redeemed_at' => $r->created_at->format('d.m.Y H:i'),
                'months_applied' => $r->months_applied,
                'duration_months' => $r->duration_months,
                'is_fully_applied' => $r->is_fully_applied,
                'total_discount' => number_format($r->total_discount_amount, 2),
            ]),
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(Voucher $voucher): Response
    {
        $this->authorize('update', $voucher);

        $tenant = tenant();

        return Inertia::render('TenantAdmin/Vouchers/Edit', [
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'description' => $voucher->description,
                'type' => $voucher->type,
                'discount_percent' => $voucher->discount_percent,
                'discount_amount' => $voucher->discount_amount,
                'trial_extension_days' => $voucher->trial_extension_days,
                'duration_months' => $voucher->duration_months,
                'is_active' => $voucher->is_active,
                'max_redemptions' => $voucher->max_redemptions,
                'current_redemptions' => $voucher->current_redemptions,
                'valid_from' => $voucher->valid_from?->format('Y-m-d'),
                'valid_until' => $voucher->valid_until?->format('Y-m-d'),
                'applicable_plan_ids' => $voucher->applicable_plan_ids,
            ],
            'plans' => ClubSubscriptionPlan::where('tenant_id', $tenant->id)
                ->orWhereNull('tenant_id')
                ->orderBy('name')
                ->get(['id', 'name', 'price']),
            'voucherTypes' => $this->getVoucherTypes(),
            'canChangeCode' => $voucher->current_redemptions === 0,
        ]);
    }

    /**
     * Update voucher.
     */
    public function update(UpdateVoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $this->authorize('update', $voucher);

        $this->voucherService->updateVoucher($voucher, $request->validated());

        return redirect()
            ->route('tenant-admin.vouchers.show', $voucher)
            ->with('success', 'Voucher erfolgreich aktualisiert!');
    }

    /**
     * Deactivate voucher.
     */
    public function destroy(Voucher $voucher): RedirectResponse
    {
        $this->authorize('delete', $voucher);

        $this->voucherService->deactivateVoucher($voucher);

        return redirect()
            ->route('tenant-admin.vouchers.index')
            ->with('success', 'Voucher deaktiviert!');
    }

    /**
     * Toggle voucher active status.
     */
    public function toggleActive(Voucher $voucher): RedirectResponse
    {
        $this->authorize('update', $voucher);

        if ($voucher->is_active) {
            $this->voucherService->deactivateVoucher($voucher);
            $message = 'Voucher deaktiviert!';
        } else {
            $this->voucherService->activateVoucher($voucher);
            $message = 'Voucher aktiviert!';
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Get voucher types for forms.
     */
    private function getVoucherTypes(): array
    {
        return [
            ['value' => Voucher::TYPE_PERCENT, 'label' => 'Prozent-Rabatt'],
            ['value' => Voucher::TYPE_FIXED_AMOUNT, 'label' => 'Fixbetrag-Rabatt'],
            ['value' => Voucher::TYPE_TRIAL_EXTENSION, 'label' => 'Trial-Verlängerung'],
        ];
    }
}
