<?php

namespace App\Http\Controllers\Club;

use App\Exceptions\VoucherException;
use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(
        private VoucherService $voucherService
    ) {}

    /**
     * Validate a voucher code (AJAX).
     */
    public function validateCode(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'plan_id' => 'nullable|uuid|exists:club_subscription_plans,id',
        ]);

        $plan = null;
        if ($request->plan_id) {
            $plan = \App\Models\ClubSubscriptionPlan::find($request->plan_id);
        } elseif ($club->club_subscription_plan_id) {
            $plan = $club->subscriptionPlan;
        }

        $result = $this->voucherService->getVoucherInfo(
            $request->code,
            $club,
            $plan
        );

        return response()->json($result);
    }

    /**
     * Redeem a voucher.
     */
    public function redeem(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'plan_id' => 'nullable|uuid|exists:club_subscription_plans,id',
        ]);

        try {
            $plan = null;
            if ($request->plan_id) {
                $plan = \App\Models\ClubSubscriptionPlan::find($request->plan_id);
            } elseif ($club->club_subscription_plan_id) {
                $plan = $club->subscriptionPlan;
            }

            $redemption = $this->voucherService->redeemVoucherByCode(
                $request->code,
                $club,
                $plan,
                auth()->user()
            );

            $voucher = $redemption->voucher;

            return response()->json([
                'success' => true,
                'message' => 'Voucher erfolgreich eingelöst!',
                'redemption' => [
                    'id' => $redemption->id,
                    'voucher_code' => $voucher?->code,
                    'voucher_name' => $voucher?->name,
                    'type' => $redemption->voucher_type,
                    'type_label' => $redemption->getTypeLabel(),
                    'discount_label' => $redemption->getFormattedDiscount(),
                    'duration_months' => $redemption->duration_months,
                    'is_trial_extension' => $redemption->voucher_type === Voucher::TYPE_TRIAL_EXTENSION,
                    'expires_at' => $redemption->expires_at?->format('d.m.Y'),
                ],
            ]);

        } catch (VoucherException $e) {
            return response()->json([
                'success' => false,
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get active voucher for club.
     */
    public function active(Club $club): JsonResponse
    {
        $redemption = $club->activeVoucherRedemption();

        if (! $redemption) {
            return response()->json([
                'has_active_voucher' => false,
                'redemption' => null,
            ]);
        }

        return response()->json([
            'has_active_voucher' => true,
            'redemption' => [
                'id' => $redemption->id,
                'voucher_code' => $redemption->voucher?->code,
                'voucher_name' => $redemption->voucher?->name,
                'type' => $redemption->voucher_type,
                'type_label' => $redemption->getTypeLabel(),
                'discount_label' => $redemption->getFormattedDiscount(),
                'remaining_months' => $redemption->getRemainingMonths(),
                'duration_months' => $redemption->duration_months,
                'months_applied' => $redemption->months_applied,
                'total_saved' => number_format($redemption->total_discount_amount, 2),
                'expires_at' => $redemption->expires_at?->format('d.m.Y'),
                'is_trial_extension' => $redemption->voucher_type === Voucher::TYPE_TRIAL_EXTENSION,
            ],
        ]);
    }

    /**
     * Get redemption history for club.
     */
    public function history(Club $club): JsonResponse
    {
        $redemptions = $this->voucherService->getClubRedemptionHistory($club);

        return response()->json([
            'redemptions' => $redemptions->map(fn ($r) => [
                'id' => $r->id,
                'voucher_code' => $r->voucher?->code,
                'voucher_name' => $r->voucher?->name,
                'type' => $r->voucher_type,
                'type_label' => $r->getTypeLabel(),
                'discount_label' => $r->getFormattedDiscount(),
                'duration_months' => $r->duration_months,
                'months_applied' => $r->months_applied,
                'is_fully_applied' => $r->is_fully_applied,
                'total_saved' => number_format($r->total_discount_amount, 2),
                'redeemed_at' => $r->created_at->format('d.m.Y H:i'),
                'expires_at' => $r->expires_at?->format('d.m.Y'),
            ]),
        ]);
    }

    /**
     * Preview discount for a potential subscription.
     */
    public function previewDiscount(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'monthly_price' => 'required|numeric|min:0',
            'months' => 'nullable|integer|min:1|max:12',
        ]);

        $preview = $this->voucherService->previewDiscount(
            $club,
            (float) $request->monthly_price,
            (int) ($request->months ?? 1)
        );

        return response()->json($preview);
    }
}
