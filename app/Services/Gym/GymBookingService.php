<?php

namespace App\Services\Gym;

use App\Models\GymBooking;
use App\Models\GymBookingRequest;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service für Buchungs-Lifecycle und Anfragen-Verwaltung.
 *
 * Verantwortlichkeiten:
 * - Freigabe und Stornierung von Buchungen
 * - Anfragen-Workflow (request, approve, reject)
 * - Berechtigungsprüfungen
 * - Buchungs-Abfragen für Teams
 */
class GymBookingService
{
    /**
     * Release a booking by a trainer or assistant trainer.
     */
    public function releaseBooking(GymBooking $booking, User $releasedBy, ?string $reason = null): bool
    {
        if (! $this->canUserReleaseBooking($booking, $releasedBy)) {
            throw new \Exception('Sie haben keine Berechtigung, diese Buchung freizugeben.');
        }

        return DB::transaction(function () use ($booking, $releasedBy, $reason) {
            $success = $booking->releaseTime($releasedBy, $reason);

            if ($success) {
                // Send notifications to other teams
                $this->notifyTeamsAboutAvailableTime($booking);
            }

            return $success;
        });
    }

    /**
     * Request a released booking for a team.
     */
    public function requestBooking(
        GymBooking $booking,
        Team $requestingTeam,
        User $requestedBy,
        array $details = []
    ): GymBookingRequest {
        if ($booking->status !== 'released') {
            throw new \Exception('Diese Buchung ist nicht verfügbar für Anfragen.');
        }

        if ($booking->original_team_id === $requestingTeam->id) {
            throw new \Exception('Sie können Ihre eigene freigegebene Zeit nicht anfragen.');
        }

        return DB::transaction(function () use ($booking, $requestingTeam, $requestedBy, $details) {
            $request = $booking->requestBooking(
                $requestingTeam,
                $requestedBy,
                $details['message'] ?? null,
                $details
            );

            // Notify original team about the request
            $request->notifyOriginalTeam();

            return $request;
        });
    }

    /**
     * Approve a booking request.
     */
    public function approveBookingRequest(
        GymBookingRequest $request,
        User $reviewedBy,
        ?string $reviewNotes = null,
        array $conditions = []
    ): bool {
        if (! $request->canBeApprovedBy($reviewedBy)) {
            throw new \Exception('Sie haben keine Berechtigung, diese Anfrage zu genehmigen.');
        }

        return DB::transaction(function () use ($request, $reviewedBy, $reviewNotes, $conditions) {
            return $request->approve($reviewedBy, $reviewNotes, $conditions);
        });
    }

    /**
     * Reject a booking request.
     */
    public function rejectBookingRequest(
        GymBookingRequest $request,
        User $reviewedBy,
        string $rejectionReason,
        ?string $reviewNotes = null
    ): bool {
        if (! $request->canBeApprovedBy($reviewedBy)) {
            throw new \Exception('Sie haben keine Berechtigung, diese Anfrage abzulehnen.');
        }

        return DB::transaction(function () use ($request, $reviewedBy, $rejectionReason, $reviewNotes) {
            return $request->reject($reviewedBy, $rejectionReason, $reviewNotes);
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(GymBooking $booking, User $cancelledBy, ?string $reason = null): bool
    {
        if (! $this->canUserCancelBooking($booking, $cancelledBy)) {
            throw new \Exception('Sie haben keine Berechtigung, diese Buchung zu stornieren.');
        }

        return $booking->cancelBooking($cancelledBy, $reason);
    }

    /**
     * Get available time slots for a team to book.
     */
    public function getAvailableTimeSlotsForTeam(Team $team, Carbon $startDate, Carbon $endDate): Collection
    {
        $club = $team->club;

        return GymBooking::whereHas('gymTimeSlot.gymHall', function ($query) use ($club) {
            $query->where('club_id', $club->id);
        })
            ->where('status', 'released')
            ->where('original_team_id', '!=', $team->id) // Exclude own releases
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->with([
                'gymTimeSlot.gymHall:id,name,address_street,address_city',
                'originalTeam:id,name,short_name',
            ])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get pending booking requests for a team to review.
     */
    public function getPendingRequestsForTeam(Team $team): Collection
    {
        return GymBookingRequest::getPendingRequestsForReview($team);
    }

    /**
     * Get booking requests made by a team.
     */
    public function getRequestsByTeam(Team $team, ?string $status = null): Collection
    {
        return GymBookingRequest::getRequestsForTeam($team, $status);
    }

    /**
     * Check if user can release a booking.
     */
    public function canUserReleaseBooking(GymBooking $booking, User $user): bool
    {
        if (! $booking->can_be_released) {
            return false;
        }

        // Check if user is trainer or assistant coach of the team
        return $booking->team->users()
            ->wherePivotIn('role', ['trainer', 'assistant_coach'])
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if user can cancel a booking.
     */
    public function canUserCancelBooking(GymBooking $booking, User $user): bool
    {
        if (! $booking->can_be_cancelled) {
            return false;
        }

        // User can cancel if they are trainer/assistant of the team or if they booked it
        $isTeamTrainer = $booking->team->users()
            ->wherePivotIn('role', ['trainer', 'assistant_coach'])
            ->where('user_id', $user->id)
            ->exists();

        return $isTeamTrainer || $booking->booked_by_user_id === $user->id;
    }

    /**
     * Process expired booking requests.
     */
    public function processExpiredRequests(): int
    {
        return GymBookingRequest::expireOldRequests();
    }

    /**
     * Send notifications to teams about available time.
     */
    protected function notifyTeamsAboutAvailableTime(GymBooking $booking): void
    {
        // This method would implement the actual notification logic
        // For now, it's handled by the GymBooking model's notifyAvailableTeams method
        // but could be expanded here with more complex notification rules
    }
}
