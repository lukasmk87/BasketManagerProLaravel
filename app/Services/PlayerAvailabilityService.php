<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameRegistration;
use App\Models\Player;
use App\Models\PlayerAbsence;
use App\Models\Team;
use App\Models\TrainingRegistration;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlayerAvailabilityService
{
    /**
     * Check if a player has an active absence covering the given date.
     */
    public function hasActiveAbsence(int $playerId, Carbon $date): bool
    {
        return PlayerAbsence::where('player_id', $playerId)
            ->coversDate($date)
            ->exists();
    }

    /**
     * Get the active absence for a player on a specific date.
     */
    public function getActiveAbsence(int $playerId, Carbon $date): ?PlayerAbsence
    {
        return PlayerAbsence::where('player_id', $playerId)
            ->coversDate($date)
            ->first();
    }

    /**
     * Get all absences for a player within a date range.
     */
    public function getAbsencesInRange(int $playerId, Carbon $start, Carbon $end): Collection
    {
        return PlayerAbsence::where('player_id', $playerId)
            ->overlapping($start, $end)
            ->orderByStartDate()
            ->get();
    }

    /**
     * Get player's effective availability status for an event.
     * Combines both direct registration and absence periods.
     */
    public function getEffectiveAvailability(Player $player, $event): array
    {
        $eventDate = $event->scheduled_at instanceof Carbon
            ? $event->scheduled_at
            : Carbon::parse($event->scheduled_at);

        // Check for active absence first
        $absence = $this->getActiveAbsence($player->id, $eventDate);

        if ($absence) {
            return [
                'status' => 'unavailable',
                'reason' => $absence->type_display,
                'reason_type' => $absence->type,
                'source' => 'absence',
                'absence_id' => $absence->id,
                'absence' => ($absence->start_date && $absence->end_date) ? $absence->getSummary() : null,
            ];
        }

        // Check direct registration for games
        if ($event instanceof Game) {
            $registration = GameRegistration::where('game_id', $event->id)
                ->where('player_id', $player->id)
                ->first();

            if ($registration) {
                return [
                    'status' => $registration->availability_status,
                    'reason' => $registration->unavailability_reason,
                    'source' => 'registration',
                    'registration_id' => $registration->id,
                    'registration_status' => $registration->registration_status,
                ];
            }
        }

        // Check direct registration for training sessions
        if ($event instanceof TrainingSession) {
            $registration = TrainingRegistration::where('training_session_id', $event->id)
                ->where('player_id', $player->id)
                ->first();

            if ($registration) {
                $status = match ($registration->status) {
                    'registered', 'confirmed' => 'available',
                    'cancelled', 'declined' => 'unavailable',
                    'waitlist' => 'maybe',
                    default => 'pending',
                };

                return [
                    'status' => $status,
                    'reason' => $registration->cancellation_reason,
                    'source' => 'registration',
                    'registration_id' => $registration->id,
                    'registration_status' => $registration->status,
                ];
            }
        }

        // No registration found - pending
        return [
            'status' => 'pending',
            'reason' => null,
            'source' => 'none',
        ];
    }

    /**
     * Get upcoming events for a player with their availability status.
     */
    public function getUpcomingEventsWithAvailability(Player $player, int $daysAhead = 14): array
    {
        $now = now();
        $endDate = now()->addDays($daysAhead);

        $events = [];

        // Get player's team IDs
        $teamIds = $player->teams()->pluck('teams.id')->toArray();

        if (empty($teamIds)) {
            return $events;
        }

        // Get upcoming games
        $games = Game::where(function ($query) use ($teamIds) {
            $query->whereIn('home_team_id', $teamIds)
                ->orWhereIn('away_team_id', $teamIds);
        })
            ->where('scheduled_at', '>=', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', ['cancelled', 'postponed', 'finished', 'forfeited'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($games as $game) {
            try {
                // Skip games with invalid scheduled_at
                if (! $game->scheduled_at) {
                    continue;
                }

                $availability = $this->getEffectiveAvailability($player, $game);
                $homeName = $game->home_team_name ?? $game->homeTeam?->name ?? 'TBD';
                $awayName = $game->away_team_name ?? $game->awayTeam?->name ?? 'TBD';

                $events[] = [
                    'type' => 'game',
                    'id' => $game->id,
                    'title' => $homeName.' vs '.$awayName,
                    'scheduled_at' => $game->scheduled_at->toIso8601String(),
                    'scheduled_at_formatted' => $game->scheduled_at->format('d.m.Y H:i'),
                    'venue' => $game->venue,
                    'team_id' => in_array($game->home_team_id, $teamIds) ? $game->home_team_id : $game->away_team_id,
                    'is_home_game' => in_array($game->home_team_id, $teamIds),
                    'availability' => $availability,
                    'registration_deadline' => $game->getRegistrationDeadline()?->toIso8601String(),
                    'can_respond' => $game->isRegistrationOpen(),
                ];
            } catch (\Throwable $e) {
                \Log::warning('Failed to process game for availability', [
                    'game_id' => $game->id,
                    'player_id' => $player->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        // Get upcoming training sessions
        $trainings = TrainingSession::whereIn('team_id', $teamIds)
            ->where('scheduled_at', '>=', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($trainings as $training) {
            try {
                // Skip trainings with invalid scheduled_at
                if (! $training->scheduled_at) {
                    continue;
                }

                $availability = $this->getEffectiveAvailability($player, $training);
                $events[] = [
                    'type' => 'training',
                    'id' => $training->id,
                    'title' => $training->title ?: 'Training',
                    'scheduled_at' => $training->scheduled_at->toIso8601String(),
                    'scheduled_at_formatted' => $training->scheduled_at->format('d.m.Y H:i'),
                    'venue' => $training->venue,
                    'team_id' => $training->team_id,
                    'session_type' => $training->session_type,
                    'is_mandatory' => $training->is_mandatory,
                    'availability' => $availability,
                    'registration_deadline' => $training->getRegistrationDeadline()?->toIso8601String(),
                    'can_respond' => $training->isRegistrationOpen(),
                ];
            } catch (\Throwable $e) {
                \Log::warning('Failed to process training for availability', [
                    'training_id' => $training->id,
                    'player_id' => $player->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        // Sort all events by scheduled_at
        usort($events, fn ($a, $b) => strtotime($a['scheduled_at']) - strtotime($b['scheduled_at']));

        return $events;
    }

    /**
     * Get team availability overview for a specific event.
     */
    public function getEventAvailability(string $eventType, int $eventId): array
    {
        if ($eventType === 'game') {
            $event = Game::findOrFail($eventId);
            $teamIds = array_filter([$event->home_team_id, $event->away_team_id]);
        } else {
            $event = TrainingSession::findOrFail($eventId);
            $teamIds = [$event->team_id];
        }

        $players = Player::whereHas('teams', function ($query) use ($teamIds) {
            $query->whereIn('teams.id', $teamIds)
                ->where('player_team.is_active', true);
        })->get();

        $overview = [
            'event' => [
                'type' => $eventType,
                'id' => $eventId,
                'title' => $eventType === 'game'
                    ? ($event->home_team_name.' vs '.$event->away_team_name)
                    : ($event->title ?: 'Training'),
                'scheduled_at' => $event->scheduled_at->toIso8601String(),
                'scheduled_at_formatted' => $event->scheduled_at->format('d.m.Y H:i'),
            ],
            'summary' => [
                'total' => $players->count(),
                'available' => 0,
                'unavailable' => 0,
                'maybe' => 0,
                'pending' => 0,
            ],
            'players' => [],
        ];

        foreach ($players as $player) {
            $availability = $this->getEffectiveAvailability($player, $event);

            $overview['players'][] = [
                'id' => $player->id,
                'name' => $player->full_name ?? $player->user?->name ?? 'Unbekannt',
                'jersey_number' => $player->teams()->whereIn('teams.id', $teamIds)->first()?->pivot?->jersey_number,
                'position' => $player->teams()->whereIn('teams.id', $teamIds)->first()?->pivot?->primary_position,
                'availability' => $availability,
            ];

            // Update summary
            $status = $availability['status'] ?? 'pending';
            if (isset($overview['summary'][$status])) {
                $overview['summary'][$status]++;
            } else {
                $overview['summary']['pending']++;
            }
        }

        // Sort players: available first, then maybe, then pending, then unavailable
        $statusOrder = ['available' => 0, 'maybe' => 1, 'pending' => 2, 'unavailable' => 3];
        usort($overview['players'], function ($a, $b) use ($statusOrder) {
            $orderA = $statusOrder[$a['availability']['status']] ?? 4;
            $orderB = $statusOrder[$b['availability']['status']] ?? 4;

            return $orderA - $orderB;
        });

        return $overview;
    }

    /**
     * Get team availability overview for a date range.
     */
    public function getTeamAvailabilityOverview(int $teamId, Carbon $startDate, Carbon $endDate): array
    {
        $team = Team::findOrFail($teamId);

        // Get all active players
        $players = $team->players()
            ->wherePivot('is_active', true)
            ->get();

        // Get events in range
        $games = Game::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled', 'finished', 'forfeited'])
            ->orderBy('scheduled_at')
            ->get();

        $trainings = TrainingSession::where('team_id', $teamId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('scheduled_at')
            ->get();

        $events = [];

        foreach ($games as $game) {
            $events[] = [
                'type' => 'game',
                'event' => $game,
            ];
        }

        foreach ($trainings as $training) {
            $events[] = [
                'type' => 'training',
                'event' => $training,
            ];
        }

        // Sort by date
        usort($events, fn ($a, $b) => $a['event']->scheduled_at->timestamp - $b['event']->scheduled_at->timestamp);

        // Build overview
        $overview = [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
            ],
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'players' => [],
            'events' => [],
        ];

        // Add player list with absences
        foreach ($players as $player) {
            $absences = $this->getAbsencesInRange($player->id, $startDate, $endDate);

            $overview['players'][] = [
                'id' => $player->id,
                'name' => $player->full_name ?? $player->user?->name ?? 'Unbekannt',
                'jersey_number' => $player->pivot->jersey_number,
                'position' => $player->pivot->primary_position,
                'absences' => $absences->map(fn ($a) => $a->getSummary()),
            ];
        }

        // Add events with availability
        foreach ($events as $eventData) {
            $event = $eventData['event'];
            $eventType = $eventData['type'];

            $eventOverview = [
                'type' => $eventType,
                'id' => $event->id,
                'title' => $eventType === 'game'
                    ? ($event->home_team_name.' vs '.$event->away_team_name)
                    : ($event->title ?: 'Training'),
                'scheduled_at' => $event->scheduled_at->toIso8601String(),
                'scheduled_at_formatted' => $event->scheduled_at->format('d.m.Y H:i'),
                'availability' => [],
            ];

            foreach ($players as $player) {
                $availability = $this->getEffectiveAvailability($player, $event);
                $eventOverview['availability'][$player->id] = $availability['status'];
            }

            $overview['events'][] = $eventOverview;
        }

        return $overview;
    }

    /**
     * Create a new absence for a player.
     */
    public function createAbsence(array $data): PlayerAbsence
    {
        $startDate = $data['start_date'] instanceof Carbon
            ? $data['start_date']
            : Carbon::parse($data['start_date']);

        $endDate = $data['end_date'] instanceof Carbon
            ? $data['end_date']
            : Carbon::parse($data['end_date']);

        return PlayerAbsence::createAbsence(
            playerId: $data['player_id'],
            type: $data['type'],
            startDate: $startDate,
            endDate: $endDate,
            reason: $data['reason'] ?? null,
            notes: $data['notes'] ?? null
        );
    }

    /**
     * Update player's availability for a game.
     */
    public function updateGameAvailability(
        int $gameId,
        int $playerId,
        string $status,
        ?string $reason = null,
        ?string $notes = null
    ): GameRegistration {
        $game = Game::findOrFail($gameId);

        // Check for absence
        $absence = $this->getActiveAbsence($playerId, $game->scheduled_at);
        if ($absence && $status === 'available') {
            throw new \Exception('Spieler hat eine aktive Abwesenheit für dieses Datum.');
        }

        $registration = GameRegistration::firstOrCreate(
            ['game_id' => $gameId, 'player_id' => $playerId],
            [
                'availability_status' => $status,
                'registration_status' => 'pending',
                'registered_at' => now(),
            ]
        );

        $registration->updateAvailability($status, $reason);

        if ($notes) {
            $registration->addPlayerNotes($notes);
        }

        return $registration->fresh();
    }

    /**
     * Update player's availability for a training session.
     */
    public function updateTrainingAvailability(
        int $trainingId,
        int $playerId,
        string $status,
        ?string $reason = null,
        ?string $notes = null
    ): TrainingRegistration {
        $training = TrainingSession::findOrFail($trainingId);

        // Check for absence
        $absence = $this->getActiveAbsence($playerId, $training->scheduled_at);
        if ($absence && $status === 'available') {
            throw new \Exception('Spieler hat eine aktive Abwesenheit für dieses Datum.');
        }

        $registrationStatus = match ($status) {
            'available' => 'registered',
            'unavailable' => 'declined',
            'maybe' => 'registered',
            default => 'registered',
        };

        $registration = TrainingRegistration::updateOrCreate(
            ['training_session_id' => $trainingId, 'player_id' => $playerId],
            [
                'status' => $registrationStatus,
                'registered_at' => now(),
                'cancellation_reason' => $status === 'unavailable' ? $reason : null,
                'registration_notes' => $notes,
            ]
        );

        return $registration->fresh();
    }

    /**
     * Get players who haven't responded to an event.
     */
    public function getPlayersWithoutResponse(string $eventType, int $eventId): Collection
    {
        if ($eventType === 'game') {
            $event = Game::findOrFail($eventId);
            $teamIds = array_filter([$event->home_team_id, $event->away_team_id]);

            $respondedPlayerIds = GameRegistration::where('game_id', $eventId)
                ->pluck('player_id')
                ->toArray();
        } else {
            $event = TrainingSession::findOrFail($eventId);
            $teamIds = [$event->team_id];

            $respondedPlayerIds = TrainingRegistration::where('training_session_id', $eventId)
                ->pluck('player_id')
                ->toArray();
        }

        return Player::whereHas('teams', function ($query) use ($teamIds) {
            $query->whereIn('teams.id', $teamIds)
                ->where('player_team.is_active', true);
        })
            ->whereNotIn('id', $respondedPlayerIds)
            ->get();
    }
}
