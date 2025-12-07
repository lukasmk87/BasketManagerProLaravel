<?php

namespace App\Services;

use App\Exceptions\VoucherException;
use App\Models\Club;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * VoucherService
 *
 * Handles all voucher-related business logic including creation,
 * validation, redemption, and discount calculations.
 */
class VoucherService
{
    // ====== VOUCHER MANAGEMENT (Admin) ======

    /**
     * Create a new voucher.
     */
    public function createVoucher(array $data, ?User $creator = null): Voucher
    {
        $data['code'] = $data['code'] ?? $this->generateUniqueCode();
        $data['created_by'] = $creator?->id;

        $voucher = Voucher::create($data);

        Log::info('Voucher created', [
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'type' => $voucher->type,
            'tenant_id' => $voucher->tenant_id,
            'created_by' => $creator?->id,
        ]);

        return $voucher;
    }

    /**
     * Update an existing voucher.
     */
    public function updateVoucher(Voucher $voucher, array $data): Voucher
    {
        // Prevent changing code if already redeemed
        if ($voucher->current_redemptions > 0 && isset($data['code'])) {
            unset($data['code']);
        }

        $voucher->update($data);

        Log::info('Voucher updated', [
            'voucher_id' => $voucher->id,
            'changes' => array_keys($data),
        ]);

        return $voucher->fresh();
    }

    /**
     * Deactivate a voucher.
     */
    public function deactivateVoucher(Voucher $voucher): Voucher
    {
        $voucher->update(['is_active' => false]);

        Log::info('Voucher deactivated', ['voucher_id' => $voucher->id]);

        return $voucher;
    }

    /**
     * Activate a voucher.
     */
    public function activateVoucher(Voucher $voucher): Voucher
    {
        $voucher->update(['is_active' => true]);

        Log::info('Voucher activated', ['voucher_id' => $voucher->id]);

        return $voucher;
    }

    /**
     * Generate a unique voucher code.
     */
    public function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }

    // ====== VOUCHER VALIDATION ======

    /**
     * Validate a voucher code for a specific club.
     *
     * @throws VoucherException
     */
    public function validateVoucher(
        string $code,
        Club $club,
        ?ClubSubscriptionPlan $plan = null
    ): Voucher {
        $voucher = Voucher::where('code', strtoupper(trim($code)))->first();

        if (! $voucher) {
            throw VoucherException::notFound();
        }

        // Check tenant scope
        if ($voucher->tenant_id && $voucher->tenant_id !== $club->tenant_id) {
            throw VoucherException::wrongTenant();
        }

        // Check if active
        if (! $voucher->is_active) {
            throw VoucherException::inactive();
        }

        // Check validity period
        if ($voucher->valid_from && $voucher->valid_from->isFuture()) {
            throw VoucherException::notYetValid();
        }

        if ($voucher->valid_until && $voucher->valid_until->isPast()) {
            throw VoucherException::expired();
        }

        // Check redemption limit
        if ($voucher->max_redemptions && $voucher->current_redemptions >= $voucher->max_redemptions) {
            throw VoucherException::exhausted();
        }

        // Check if already redeemed by this club
        if (! $voucher->canBeRedeemedByClub($club)) {
            throw VoucherException::alreadyRedeemed();
        }

        // Check plan restriction
        if ($plan && ! $voucher->isApplicableToPlan($plan)) {
            throw VoucherException::wrongPlan();
        }

        return $voucher;
    }

    /**
     * Get validation info without throwing exceptions.
     */
    public function getVoucherInfo(string $code, Club $club, ?ClubSubscriptionPlan $plan = null): array
    {
        try {
            $voucher = $this->validateVoucher($code, $club, $plan);

            return [
                'valid' => true,
                'voucher' => [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                    'type' => $voucher->type,
                    'type_label' => $voucher->getTypeLabel(),
                    'discount_label' => $voucher->getFormattedDiscount(),
                    'duration_label' => $voucher->getDurationLabel(),
                    'duration_months' => $voucher->duration_months,
                    'description' => $voucher->description,
                    'applicable_plan_ids' => $voucher->applicable_plan_ids,
                    'discount_percent' => $voucher->discount_percent,
                    'discount_amount' => $voucher->discount_amount,
                    'trial_extension_days' => $voucher->trial_extension_days,
                ],
                'message' => 'Voucher ist gültig!',
            ];
        } catch (VoucherException $e) {
            return [
                'valid' => false,
                'voucher' => null,
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    // ====== VOUCHER REDEMPTION ======

    /**
     * Redeem a voucher for a club.
     *
     * @throws VoucherException
     */
    public function redeemVoucher(
        Voucher $voucher,
        Club $club,
        ?ClubSubscriptionPlan $plan = null,
        ?User $redeemedBy = null
    ): VoucherRedemption {
        // Re-validate to ensure it's still valid
        $this->validateVoucher($voucher->code, $club, $plan);

        return DB::transaction(function () use ($voucher, $club, $plan, $redeemedBy) {
            // Create redemption record
            $redemption = VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'club_id' => $club->id,
                'tenant_id' => $club->tenant_id,
                'voucher_type' => $voucher->type,
                'discount_percent' => $voucher->discount_percent,
                'discount_amount' => $voucher->discount_amount,
                'trial_extension_days' => $voucher->trial_extension_days,
                'duration_months' => $voucher->duration_months,
                'applied_to_plan_id' => $plan?->id ?? $club->club_subscription_plan_id,
                'redeemed_by' => $redeemedBy?->id,
                'expires_at' => $voucher->type !== Voucher::TYPE_TRIAL_EXTENSION
                    ? now()->addMonths($voucher->duration_months)
                    : null,
            ]);

            // Increment voucher redemption counter
            $voucher->incrementRedemptions();

            // Apply trial extension immediately if that's the type
            if ($voucher->type === Voucher::TYPE_TRIAL_EXTENSION) {
                $this->applyTrialExtension($club, $voucher->trial_extension_days);
                $redemption->update([
                    'is_fully_applied' => true,
                    'first_applied_at' => now(),
                    'last_applied_at' => now(),
                ]);
            }

            // Log event
            $this->logRedemptionEvent($club, $voucher, $redemption);

            Log::info('Voucher redeemed', [
                'voucher_id' => $voucher->id,
                'club_id' => $club->id,
                'redemption_id' => $redemption->id,
                'type' => $voucher->type,
            ]);

            return $redemption;
        });
    }

    /**
     * Redeem a voucher by code.
     *
     * @throws VoucherException
     */
    public function redeemVoucherByCode(
        string $code,
        Club $club,
        ?ClubSubscriptionPlan $plan = null,
        ?User $redeemedBy = null
    ): VoucherRedemption {
        $voucher = $this->validateVoucher($code, $club, $plan);

        return $this->redeemVoucher($voucher, $club, $plan, $redeemedBy);
    }

    /**
     * Apply trial extension to a club.
     */
    protected function applyTrialExtension(Club $club, int $days): void
    {
        $currentTrialEnd = $club->subscription_trial_ends_at ?? now();

        if ($currentTrialEnd->isPast()) {
            $currentTrialEnd = now();
        }

        $club->update([
            'subscription_trial_ends_at' => $currentTrialEnd->addDays($days),
            'subscription_status' => $club->subscription_status === 'active' ? 'active' : 'trialing',
        ]);

        Log::info('Trial extension applied', [
            'club_id' => $club->id,
            'days' => $days,
            'new_trial_ends_at' => $club->subscription_trial_ends_at,
        ]);
    }

    /**
     * Log voucher redemption as subscription event.
     */
    protected function logRedemptionEvent(Club $club, Voucher $voucher, VoucherRedemption $redemption): void
    {
        ClubSubscriptionEvent::create([
            'tenant_id' => $club->tenant_id,
            'club_id' => $club->id,
            'event_type' => 'voucher_redeemed',
            'stripe_subscription_id' => $club->stripe_subscription_id,
            'metadata' => [
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->code,
                'voucher_name' => $voucher->name,
                'voucher_type' => $voucher->type,
                'discount' => $voucher->getFormattedDiscount(),
                'duration_months' => $voucher->duration_months,
                'redemption_id' => $redemption->id,
            ],
            'event_date' => now(),
        ]);
    }

    // ====== DISCOUNT CALCULATION ======

    /**
     * Calculate discount for a billing amount.
     */
    public function calculateDiscount(Club $club, float $amount): array
    {
        $redemption = $club->activeVoucherRedemption();

        if (! $redemption || ! $redemption->isActive()) {
            return [
                'has_discount' => false,
                'discount_amount' => 0,
                'original_amount' => $amount,
                'final_amount' => $amount,
                'redemption' => null,
            ];
        }

        $discountAmount = $redemption->calculateDiscountForAmount($amount);
        $finalAmount = max(0, $amount - $discountAmount);

        return [
            'has_discount' => $discountAmount > 0,
            'discount_amount' => round($discountAmount, 2),
            'original_amount' => $amount,
            'final_amount' => round($finalAmount, 2),
            'redemption' => [
                'id' => $redemption->id,
                'voucher_id' => $redemption->voucher_id,
                'voucher_code' => $redemption->voucher?->code,
                'type' => $redemption->voucher_type,
                'remaining_months' => $redemption->getRemainingMonths(),
                'discount_label' => $redemption->getFormattedDiscount(),
            ],
        ];
    }

    /**
     * Preview discount for a potential subscription.
     */
    public function previewDiscount(Club $club, float $monthlyPrice, int $months = 1): array
    {
        $redemption = $club->activeVoucherRedemption();

        if (! $redemption || ! $redemption->isActive()) {
            return [
                'has_discount' => false,
                'monthly_discount' => 0,
                'total_discount' => 0,
                'monthly_price' => $monthlyPrice,
                'total_price' => $monthlyPrice * $months,
            ];
        }

        $applicableMonths = min($months, $redemption->getRemainingMonths());
        $monthlyDiscount = $redemption->calculateDiscountForAmount($monthlyPrice);
        $totalDiscount = $monthlyDiscount * $applicableMonths;

        return [
            'has_discount' => true,
            'monthly_discount' => round($monthlyDiscount, 2),
            'total_discount' => round($totalDiscount, 2),
            'monthly_price' => round($monthlyPrice - $monthlyDiscount, 2),
            'total_price' => round(($monthlyPrice * $months) - $totalDiscount, 2),
            'applicable_months' => $applicableMonths,
            'voucher_code' => $redemption->voucher?->code,
            'discount_label' => $redemption->getFormattedDiscount(),
        ];
    }

    /**
     * Mark discount as applied for a billing cycle.
     */
    public function markDiscountApplied(Club $club, float $discountGiven): void
    {
        $redemption = $club->activeVoucherRedemption();

        if ($redemption && $redemption->isActive()) {
            $redemption->applyDiscount($discountGiven);

            Log::info('Voucher discount applied', [
                'club_id' => $club->id,
                'redemption_id' => $redemption->id,
                'discount_amount' => $discountGiven,
                'months_applied' => $redemption->months_applied,
                'is_fully_applied' => $redemption->is_fully_applied,
            ]);
        }
    }

    // ====== QUERY METHODS ======

    /**
     * Get all vouchers for a tenant (including system-wide).
     */
    public function getVouchersForTenant(Tenant $tenant): Collection
    {
        return Voucher::forTenant($tenant->id)
            ->with('creator')
            ->withCount('redemptions')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get only tenant-specific vouchers (excluding system-wide).
     */
    public function getTenantSpecificVouchers(Tenant $tenant): Collection
    {
        return Voucher::where('tenant_id', $tenant->id)
            ->with('creator')
            ->withCount('redemptions')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all system-wide vouchers (Super Admin).
     */
    public function getSystemWideVouchers(): Collection
    {
        return Voucher::systemWide()
            ->with('creator')
            ->withCount('redemptions')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all vouchers (Super Admin view).
     */
    public function getAllVouchers(): Collection
    {
        return Voucher::with(['tenant', 'creator'])
            ->withCount('redemptions')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get available vouchers for a club to redeem.
     */
    public function getAvailableVouchersForClub(Club $club): Collection
    {
        return Voucher::forTenant($club->tenant_id)
            ->active()
            ->valid()
            ->notExhausted()
            ->whereDoesntHave('redemptions', function ($q) use ($club) {
                $q->where('club_id', $club->id);
            })
            ->get();
    }

    /**
     * Get redemption statistics for a voucher.
     */
    public function getVoucherStatistics(Voucher $voucher): array
    {
        $redemptions = $voucher->redemptions()
            ->with(['club', 'redeemedByUser'])
            ->get();

        return [
            'total_redemptions' => $redemptions->count(),
            'total_discount_given' => $redemptions->sum('total_discount_amount'),
            'active_redemptions' => $redemptions->where('is_fully_applied', false)->count(),
            'completed_redemptions' => $redemptions->where('is_fully_applied', true)->count(),
            'remaining_redemptions' => $voucher->getRemainingRedemptions(),
            'redemptions' => $redemptions,
        ];
    }

    /**
     * Get redemption history for a club.
     */
    public function getClubRedemptionHistory(Club $club): Collection
    {
        return VoucherRedemption::where('club_id', $club->id)
            ->with('voucher')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get overall voucher statistics for admin dashboard.
     */
    public function getOverallStatistics(?Tenant $tenant = null): array
    {
        $query = Voucher::query();

        if ($tenant) {
            $query->forTenant($tenant->id);
        }

        $vouchers = $query->withCount('redemptions')->get();

        $totalRedemptions = VoucherRedemption::when($tenant, function ($q) use ($tenant) {
            $q->where('tenant_id', $tenant->id);
        })->count();

        $totalDiscountGiven = VoucherRedemption::when($tenant, function ($q) use ($tenant) {
            $q->where('tenant_id', $tenant->id);
        })->sum('total_discount_amount');

        return [
            'total_vouchers' => $vouchers->count(),
            'active_vouchers' => $vouchers->where('is_active', true)->count(),
            'system_wide_vouchers' => $vouchers->whereNull('tenant_id')->count(),
            'total_redemptions' => $totalRedemptions,
            'total_discount_given' => round($totalDiscountGiven, 2),
            'by_type' => [
                'percent' => $vouchers->where('type', Voucher::TYPE_PERCENT)->count(),
                'fixed_amount' => $vouchers->where('type', Voucher::TYPE_FIXED_AMOUNT)->count(),
                'trial_extension' => $vouchers->where('type', Voucher::TYPE_TRIAL_EXTENSION)->count(),
            ],
        ];
    }
}
