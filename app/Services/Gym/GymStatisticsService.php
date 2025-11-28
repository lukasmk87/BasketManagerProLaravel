<?php

namespace App\Services\Gym;

use App\Models\Club;
use App\Models\GymBooking;
use App\Models\GymTimeSlot;
use App\Models\Team;
use Carbon\Carbon;

/**
 * Service für Gym-Statistiken und Reports.
 *
 * Verantwortlichkeiten:
 * - Wöchentliche Hallenpläne
 * - Auslastungsstatistiken für Clubs und Teams
 * - Verarbeitung vergangener Buchungen
 */
class GymStatisticsService
{
    /**
     * Get weekly schedule for multiple gym halls.
     *
     * @return array<int|string, array<string, mixed>>
     */
    public function getClubWeeklySchedule(Club $club, ?Carbon $weekStart = null): array
    {
        if (! $weekStart) {
            $weekStart = now()->startOfWeek();
        }

        $gymHalls = $club->gymHalls()->active()->with(['timeSlots' => function ($query) {
            $query->active()->with('team:id,name,short_name');
        }])->get();

        $schedule = [];
        foreach ($gymHalls as $hall) {
            $schedule[$hall->id] = [
                'gym_hall' => $hall->only(['id', 'name', 'capacity']),
                'weekly_schedule' => $hall->getWeeklySchedule($weekStart),
            ];
        }

        return $schedule;
    }

    /**
     * Get utilization statistics for a club.
     *
     * @return array<string, mixed>
     */
    public function getClubUtilizationStats(Club $club, Carbon $startDate, Carbon $endDate): array
    {
        $gymHalls = $club->gymHalls()->active()->get();

        $stats = [];
        $totalUtilization = 0;
        $totalBookings = 0;

        foreach ($gymHalls as $hall) {
            $utilization = $hall->getUtilizationRate($startDate, $endDate);
            $bookings = $hall->bookings()
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->whereIn('status', ['reserved', 'confirmed', 'completed'])
                ->count();

            $stats['halls'][$hall->id] = [
                'name' => $hall->name,
                'utilization_rate' => $utilization,
                'total_bookings' => $bookings,
            ];

            $totalUtilization += $utilization;
            $totalBookings += $bookings;
        }

        $stats['overview'] = [
            'total_halls' => $gymHalls->count(),
            'average_utilization' => $gymHalls->count() > 0 ? round($totalUtilization / $gymHalls->count(), 1) : 0,
            'total_bookings' => $totalBookings,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];

        return $stats;
    }

    /**
     * Mark past bookings as completed or no-show.
     *
     * @return array{completed: int, no_show: int}
     */
    public function processPastBookings(): array
    {
        $results = ['completed' => 0, 'no_show' => 0];

        $pastBookings = GymBooking::past()
            ->whereIn('status', ['reserved', 'confirmed'])
            ->get();

        foreach ($pastBookings as $booking) {
            // For now, automatically mark as completed
            // In a real implementation, you might check attendance or have manual input
            $booking->markAsCompleted();
            $results['completed']++;
        }

        return $results;
    }

    /**
     * Get booking statistics for a team.
     *
     * @return array<string, mixed>
     */
    public function getTeamBookingStats(Team $team, Carbon $startDate, Carbon $endDate): array
    {
        $bookings = GymBooking::forTeam($team->id)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->get();

        return [
            'total_bookings' => $bookings->count(),
            'bookings_by_status' => $bookings->groupBy('status')->map->count(),
            'releases_made' => $bookings->where('status', 'released')->count(),
            'substitute_bookings' => $bookings->where('is_substitute_booking', true)->count(),
            'average_utilization' => $this->calculateTeamUtilization($team, $startDate, $endDate),
            'most_used_halls' => $bookings->groupBy('gymTimeSlot.gymHall.name')->map->count()->sortDesc()->take(3),
        ];
    }

    /**
     * Calculate team utilization rate.
     */
    protected function calculateTeamUtilization(Team $team, Carbon $startDate, Carbon $endDate): float
    {
        $totalSlots = GymTimeSlot::forTeam($team->id)->active()->count();
        if ($totalSlots === 0) {
            return 0.0;
        }

        $usedSlots = GymBooking::forTeam($team->id)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereIn('status', ['reserved', 'confirmed', 'completed'])
            ->count();

        $weeksBetween = $startDate->diffInWeeks($endDate) + 1;
        $expectedBookings = $totalSlots * $weeksBetween;

        return $expectedBookings > 0 ? round(($usedSlots / $expectedBookings) * 100, 1) : 0.0;
    }
}
