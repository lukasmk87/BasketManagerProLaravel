<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameRegistration;
use App\Models\GameParticipation;
use App\Models\Player;
use App\Models\TrainingSession;
use App\Models\TrainingRegistration;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Register a player for a training session.
     */
    public function registerForTraining(int $trainingSessionId, int $playerId, ?string $notes = null): TrainingRegistration
    {
        $session = TrainingSession::findOrFail($trainingSessionId);
        
        $this->validateTrainingRegistration($session, $playerId);
        
        return DB::transaction(function () use ($session, $playerId, $notes) {
            return $session->registerPlayer($playerId, $notes);
        });
    }

    /**
     * Cancel a training registration.
     */
    public function cancelTrainingRegistration(int $trainingSessionId, int $playerId, ?string $reason = null): bool
    {
        $session = TrainingSession::findOrFail($trainingSessionId);
        $registration = $session->getPlayerRegistration($playerId);

        if (!$registration) {
            throw new Exception('Keine Anmeldung für diese Trainingseinheit gefunden.');
        }

        return DB::transaction(function () use ($registration, $reason) {
            return $registration->cancel($reason);
        });
    }

    /**
     * Confirm a training registration (by trainer).
     */
    public function confirmTrainingRegistration(int $registrationId, int $confirmedByUserId, ?string $notes = null): bool
    {
        $registration = TrainingRegistration::findOrFail($registrationId);
        
        return DB::transaction(function () use ($registration, $confirmedByUserId, $notes) {
            return $registration->confirm($confirmedByUserId, $notes);
        });
    }

    /**
     * Decline a training registration (by trainer).
     */
    public function declineTrainingRegistration(int $registrationId, int $declinedByUserId, ?string $reason = null): bool
    {
        $registration = TrainingRegistration::findOrFail($registrationId);
        
        return DB::transaction(function () use ($registration, $declinedByUserId, $reason) {
            return $registration->decline($declinedByUserId, $reason);
        });
    }

    /**
     * Register a player for a game.
     */
    public function registerForGame(int $gameId, int $playerId, string $availabilityStatus = 'available', ?string $notes = null): GameRegistration
    {
        $game = Game::findOrFail($gameId);
        
        $this->validateGameRegistration($game, $playerId);
        
        return DB::transaction(function () use ($game, $playerId, $availabilityStatus, $notes) {
            return $game->registerPlayer($playerId, $availabilityStatus, $notes);
        });
    }

    /**
     * Update player availability for a game.
     */
    public function updateGameAvailability(int $gameId, int $playerId, string $availabilityStatus, ?string $reason = null): bool
    {
        $game = Game::findOrFail($gameId);
        $registration = $game->getPlayerRegistration($playerId);

        if (!$registration) {
            throw new Exception('Keine Anmeldung für dieses Spiel gefunden.');
        }

        return DB::transaction(function () use ($registration, $availabilityStatus, $reason) {
            return $registration->updateAvailability($availabilityStatus, $reason);
        });
    }

    /**
     * Confirm a game registration (by coach/trainer).
     */
    public function confirmGameRegistration(int $registrationId, int $confirmedByUserId, ?string $notes = null): bool
    {
        $registration = GameRegistration::findOrFail($registrationId);
        
        return DB::transaction(function () use ($registration, $confirmedByUserId, $notes) {
            return $registration->confirm($confirmedByUserId, $notes);
        });
    }

    /**
     * Add a player to the game roster.
     */
    public function addPlayerToGameRoster(
        int $gameId,
        int $playerId,
        string $role = 'substitute',
        ?int $jerseyNumber = null,
        ?string $position = null
    ): GameParticipation {
        $game = Game::findOrFail($gameId);
        
        $this->validateGameParticipation($game, $playerId, $jerseyNumber);
        
        return DB::transaction(function () use ($game, $playerId, $role, $jerseyNumber, $position) {
            return $game->addPlayerToRoster($playerId, $role, $jerseyNumber, $position);
        });
    }

    /**
     * Remove a player from the game roster.
     */
    public function removePlayerFromGameRoster(int $gameId, int $playerId): bool
    {
        $game = Game::findOrFail($gameId);
        $participation = $game->getPlayerParticipation($playerId);

        if (!$participation) {
            throw new Exception('Spieler ist nicht im Kader für dieses Spiel.');
        }

        return DB::transaction(function () use ($participation) {
            $participation->delete();
            return true;
        });
    }

    /**
     * Bulk register players for training session.
     */
    public function bulkRegisterForTraining(int $trainingSessionId, array $playerIds, ?string $notes = null): array
    {
        $session = TrainingSession::findOrFail($trainingSessionId);
        $results = [];

        DB::transaction(function () use ($session, $playerIds, $notes, &$results) {
            foreach ($playerIds as $playerId) {
                try {
                    $this->validateTrainingRegistration($session, $playerId);
                    $registration = $session->registerPlayer($playerId, $notes);
                    $results['success'][] = [
                        'player_id' => $playerId,
                        'registration' => $registration
                    ];
                } catch (Exception $e) {
                    $results['errors'][] = [
                        'player_id' => $playerId,
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Bulk register players for game.
     */
    public function bulkRegisterForGame(int $gameId, array $playerData): array
    {
        $game = Game::findOrFail($gameId);
        $results = [];

        DB::transaction(function () use ($game, $playerData, &$results) {
            foreach ($playerData as $data) {
                $playerId = $data['player_id'];
                $availabilityStatus = $data['availability_status'] ?? 'available';
                $notes = $data['notes'] ?? null;

                try {
                    $this->validateGameRegistration($game, $playerId);
                    $registration = $game->registerPlayer($playerId, $availabilityStatus, $notes);
                    $results['success'][] = [
                        'player_id' => $playerId,
                        'registration' => $registration
                    ];
                } catch (Exception $e) {
                    $results['errors'][] = [
                        'player_id' => $playerId,
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Get training registrations with deadline information.
     */
    public function getTrainingRegistrationsWithDeadlines(int $playerId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = TrainingSession::query()
            ->with(['registrations' => function ($q) use ($playerId) {
                $q->where('player_id', $playerId);
            }])
            ->where('allow_registrations', true);

        if ($startDate) {
            $query->where('scheduled_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('scheduled_at', '<=', $endDate);
        }

        return $query->orderBy('scheduled_at')->get()->map(function ($session) {
            return [
                'session' => $session,
                'registration' => $session->registrations->first(),
                'registration_deadline' => $session->getRegistrationDeadline(),
                'is_registration_open' => $session->isRegistrationOpen(),
                'hours_until_deadline' => now()->diffInHours($session->getRegistrationDeadline(), false),
                'has_capacity' => $session->hasCapacity(),
                'available_spots' => $session->getAvailableSpots(),
            ];
        });
    }

    /**
     * Get game registrations with deadline information.
     */
    public function getGameRegistrationsWithDeadlines(int $playerId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = Game::query()
            ->with(['registrations' => function ($q) use ($playerId) {
                $q->where('player_id', $playerId);
            }])
            ->where('allow_player_registrations', true);

        if ($startDate) {
            $query->where('scheduled_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('scheduled_at', '<=', $endDate);
        }

        return $query->orderBy('scheduled_at')->get()->map(function ($game) {
            return [
                'game' => $game,
                'registration' => $game->registrations->first(),
                'registration_deadline' => $game->getRegistrationDeadline(),
                'lineup_deadline' => $game->getLineupDeadline(),
                'is_registration_open' => $game->isRegistrationOpen(),
                'is_lineup_changes_allowed' => $game->isLineupChangesAllowed(),
                'hours_until_registration_deadline' => now()->diffInHours($game->getRegistrationDeadline(), false),
                'hours_until_lineup_deadline' => now()->diffInHours($game->getLineupDeadline(), false),
                'has_roster_capacity' => $game->hasRosterCapacity(),
                'available_roster_spots' => $game->getAvailableRosterSpots(),
            ];
        });
    }

    /**
     * Get upcoming registration deadlines.
     */
    public function getUpcomingDeadlines(?int $playerId = null, int $daysAhead = 7): array
    {
        $endDate = now()->addDays($daysAhead);
        $deadlines = [];

        // Training session deadlines
        $trainingSessions = TrainingSession::query()
            ->where('allow_registrations', true)
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', $endDate)
            ->get();

        foreach ($trainingSessions as $session) {
            $deadline = $session->getRegistrationDeadline();
            if ($deadline->isFuture()) {
                $registration = null;
                if ($playerId) {
                    $registration = $session->getPlayerRegistration($playerId);
                }

                $deadlines[] = [
                    'type' => 'training',
                    'id' => $session->id,
                    'title' => $session->title,
                    'scheduled_at' => $session->scheduled_at,
                    'deadline' => $deadline,
                    'hours_until_deadline' => now()->diffInHours($deadline, false),
                    'is_registered' => $registration !== null,
                    'registration_status' => $registration?->status,
                    'can_register' => $session->isRegistrationOpen() && !$registration,
                ];
            }
        }

        // Game deadlines
        $games = Game::query()
            ->where('allow_player_registrations', true)
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', $endDate)
            ->get();

        foreach ($games as $game) {
            $registrationDeadline = $game->getRegistrationDeadline();
            $lineupDeadline = $game->getLineupDeadline();

            if ($registrationDeadline->isFuture()) {
                $registration = null;
                if ($playerId) {
                    $registration = $game->getPlayerRegistration($playerId);
                }

                $deadlines[] = [
                    'type' => 'game_registration',
                    'id' => $game->id,
                    'title' => "vs " . ($game->homeTeam->name ?? 'TBD'),
                    'scheduled_at' => $game->scheduled_at,
                    'deadline' => $registrationDeadline,
                    'hours_until_deadline' => now()->diffInHours($registrationDeadline, false),
                    'is_registered' => $registration !== null,
                    'registration_status' => $registration?->registration_status,
                    'availability_status' => $registration?->availability_status,
                    'can_register' => $game->isRegistrationOpen() && !$registration,
                ];
            }

            if ($lineupDeadline->isFuture() && $lineupDeadline->ne($registrationDeadline)) {
                $deadlines[] = [
                    'type' => 'game_lineup',
                    'id' => $game->id,
                    'title' => "Aufstellung vs " . ($game->homeTeam->name ?? 'TBD'),
                    'scheduled_at' => $game->scheduled_at,
                    'deadline' => $lineupDeadline,
                    'hours_until_deadline' => now()->diffInHours($lineupDeadline, false),
                    'is_final' => !$game->isLineupChangesAllowed(),
                ];
            }
        }

        // Sort by deadline
        usort($deadlines, function ($a, $b) {
            return $a['deadline']->compare($b['deadline']);
        });

        return $deadlines;
    }

    /**
     * Validate training registration.
     */
    protected function validateTrainingRegistration(TrainingSession $session, int $playerId): void
    {
        if (!$session->isRegistrationOpen()) {
            throw new Exception('Anmeldefrist für diese Trainingseinheit ist bereits abgelaufen.');
        }

        if ($session->isPlayerRegistered($playerId)) {
            throw new Exception('Spieler ist bereits für diese Trainingseinheit angemeldet.');
        }

        if (!$session->hasCapacity() && !$session->enable_waitlist) {
            throw new Exception('Trainingseinheit ist bereits ausgebucht und Warteliste ist nicht aktiviert.');
        }

        // Check if player exists and is active
        $player = Player::find($playerId);
        if (!$player) {
            throw new Exception('Spieler nicht gefunden.');
        }

        if (!$player->is_active) {
            throw new Exception('Spieler ist nicht aktiv und kann sich nicht anmelden.');
        }
    }

    /**
     * Validate game registration.
     */
    protected function validateGameRegistration(Game $game, int $playerId): void
    {
        if (!$game->isRegistrationOpen()) {
            throw new Exception('Anmeldefrist für dieses Spiel ist bereits abgelaufen.');
        }

        if ($game->isPlayerRegistered($playerId)) {
            throw new Exception('Spieler ist bereits für dieses Spiel angemeldet.');
        }

        // Check if player exists and is active
        $player = Player::find($playerId);
        if (!$player) {
            throw new Exception('Spieler nicht gefunden.');
        }

        if (!$player->is_active) {
            throw new Exception('Spieler ist nicht aktiv und kann sich nicht anmelden.');
        }
    }

    /**
     * Validate game participation (roster addition).
     */
    protected function validateGameParticipation(Game $game, int $playerId, ?int $jerseyNumber = null): void
    {
        if (!$game->isLineupChangesAllowed()) {
            throw new Exception('Kader-Änderungen sind für dieses Spiel nicht mehr erlaubt.');
        }

        if ($game->isPlayerParticipating($playerId)) {
            throw new Exception('Spieler ist bereits im Kader für dieses Spiel.');
        }

        if (!$game->hasRosterCapacity()) {
            throw new Exception('Kader ist bereits voll.');
        }

        if ($jerseyNumber) {
            $existingParticipation = GameParticipation::where('game_id', $game->id)
                ->where('jersey_number', $jerseyNumber)
                ->first();

            if ($existingParticipation) {
                throw new Exception("Trikotnummer {$jerseyNumber} ist bereits vergeben.");
            }
        }

        // Check if player exists and is active
        $player = Player::find($playerId);
        if (!$player) {
            throw new Exception('Spieler nicht gefunden.');
        }

        if (!$player->is_active) {
            throw new Exception('Spieler ist nicht aktiv und kann nicht zum Kader hinzugefügt werden.');
        }
    }
}