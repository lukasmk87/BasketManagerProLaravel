<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymBooking;
use App\Services\Gym\GymBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GymBookingController extends Controller
{
    public function __construct(
        private GymBookingService $bookingService
    ) {}

    /**
     * Training absagen / Buchung freigeben.
     *
     * Setzt den Status der Buchung auf "released", sodass andere Teams
     * diese Zeit anfragen können.
     */
    public function release(Request $request, GymBooking $gymBooking): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->bookingService->releaseBooking(
                $gymBooking,
                $request->user(),
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Training wurde abgesagt. Die Zeit ist nun für andere Teams verfügbar.',
                'data' => [
                    'booking_id' => $gymBooking->id,
                    'status' => 'released',
                    'booking_date' => $gymBooking->booking_date->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Buchung stornieren.
     */
    public function cancel(Request $request, GymBooking $gymBooking): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->bookingService->cancelBooking(
                $gymBooking,
                $request->user(),
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Buchung wurde storniert.',
                'data' => [
                    'booking_id' => $gymBooking->id,
                    'status' => 'cancelled',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Buchungen für ein Team abrufen.
     */
    public function forTeam(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = $user->currentTeam;

        if (! $team) {
            return response()->json([
                'success' => false,
                'message' => 'Kein Team ausgewählt.',
            ], 400);
        }

        $bookings = GymBooking::where('team_id', $team->id)
            ->with(['gymTimeSlot.gymHall:id,name', 'bookedByUser:id,name'])
            ->where('booking_date', '>=', now()->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }
}
