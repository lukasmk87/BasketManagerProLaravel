<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\TournamentBracket;
use App\Models\TournamentOfficial;
use App\Models\TournamentAward;
use App\Models\Team;
use App\Models\User;
use App\Models\Game;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class TournamentService
{
    protected BracketGeneratorService $bracketGenerator;
    protected TournamentProgressionService $progressionService;

    public function __construct(
        BracketGeneratorService $bracketGenerator,
        TournamentProgressionService $progressionService
    ) {
        $this->bracketGenerator = $bracketGenerator;
        $this->progressionService = $progressionService;
    }

    // Tournament Creation and Setup
    public function createTournament(array $data, User $organizer): Tournament
    {
        return DB::transaction(function () use ($data, $organizer) {
            $tournament = Tournament::create([
                ...$data,
                'organizer_id' => $organizer->id,
                'status' => 'draft',
                'registered_teams' => 0,
            ]);

            // Create initial configuration
            $this->setupTournamentDefaults($tournament);

            return $tournament;
        });
    }

    public function updateTournament(Tournament $tournament, array $data): Tournament
    {
        // Validate that critical fields can't be changed after registration opens
        if ($tournament->status !== 'draft') {
            $restrictedFields = ['type', 'min_teams', 'max_teams', 'category', 'gender'];
            $changedRestrictedFields = array_intersect(array_keys($data), $restrictedFields);
            
            if (!empty($changedRestrictedFields)) {
                throw new InvalidArgumentException(
                    'Folgende Felder können nach Anmeldungsstart nicht mehr geändert werden: ' . 
                    implode(', ', $changedRestrictedFields)
                );
            }
        }

        $tournament->update($data);
        return $tournament->fresh();
    }

    public function deleteTournament(Tournament $tournament): bool
    {
        if ($tournament->status === 'in_progress') {
            throw new InvalidArgumentException('Laufende Turniere können nicht gelöscht werden');
        }

        return DB::transaction(function () use ($tournament) {
            // Delete related records
            $tournament->brackets()->delete();
            $tournament->officials()->delete();
            $tournament->awards()->delete();
            
            // Detach teams (keeping records for history)
            $tournament->teams()->detach();
            
            return $tournament->delete();
        });
    }

    // Team Registration
    public function registerTeam(Tournament $tournament, Team $team, User $registeredBy, array $data = []): TournamentTeam
    {
        if (!$tournament->can_register) {
            throw new InvalidArgumentException('Anmeldung für dieses Turnier ist nicht möglich');
        }

        if ($tournament->teams()->where('team_id', $team->id)->exists()) {
            throw new InvalidArgumentException('Team ist bereits für dieses Turnier angemeldet');
        }

        return DB::transaction(function () use ($tournament, $team, $registeredBy, $data) {
            $tournamentTeam = TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'registered_by_user_id' => $registeredBy->id,
                'registered_at' => now(),
                'status' => $tournament->requires_approval ? 'pending' : 'approved',
                'contact_person' => $data['contact_person'] ?? $registeredBy->full_name,
                'contact_email' => $data['contact_email'] ?? $registeredBy->email,
                'contact_phone' => $data['contact_phone'] ?? '',
                'registration_notes' => $data['registration_notes'] ?? '',
                'special_requirements' => $data['special_requirements'] ?? '',
                'roster_players' => $data['roster_players'] ?? [],
                'emergency_contacts' => $data['emergency_contacts'] ?? [],
            ]);

            // Auto-approve if approval not required
            if (!$tournament->requires_approval) {
                $this->approveTeamRegistration($tournamentTeam);
            }

            return $tournamentTeam;
        });
    }

    public function approveTeamRegistration(TournamentTeam $tournamentTeam): TournamentTeam
    {
        if ($tournamentTeam->status !== 'pending') {
            throw new InvalidArgumentException('Team-Anmeldung ist nicht ausstehend');
        }

        return DB::transaction(function () use ($tournamentTeam) {
            $tournamentTeam->approve();
            
            // Increment registered teams count
            $tournamentTeam->tournament->increment('registered_teams');
            
            // Auto-assign seed based on registration order (can be changed later)
            if (!$tournamentTeam->seed) {
                $tournamentTeam->update([
                    'seed' => $tournamentTeam->tournament->registered_teams,
                ]);
            }

            return $tournamentTeam;
        });
    }

    public function rejectTeamRegistration(TournamentTeam $tournamentTeam, string $reason = null): TournamentTeam
    {
        $tournamentTeam->reject($reason);
        return $tournamentTeam;
    }

    public function withdrawTeam(TournamentTeam $tournamentTeam, string $reason = null): TournamentTeam
    {
        return DB::transaction(function () use ($tournamentTeam, $reason) {
            $tournamentTeam->withdraw($reason);
            
            // Decrement registered teams count if was approved
            if ($tournamentTeam->status === 'approved') {
                $tournamentTeam->tournament->decrement('registered_teams');
            }

            return $tournamentTeam;
        });
    }

    // Tournament Status Management
    public function openRegistration(Tournament $tournament): Tournament
    {
        if ($tournament->status !== 'draft') {
            throw new InvalidArgumentException('Anmeldung kann nur aus Entwurf-Status geöffnet werden');
        }

        $tournament->update([
            'status' => 'registration_open',
            'registration_start' => $tournament->registration_start ?? now(),
        ]);

        // Notify potential participants
        $this->notifyRegistrationOpen($tournament);

        return $tournament;
    }

    public function closeRegistration(Tournament $tournament): Tournament
    {
        if ($tournament->status !== 'registration_open') {
            throw new InvalidArgumentException('Anmeldung ist nicht geöffnet');
        }

        if ($tournament->registered_teams < $tournament->min_teams) {
            throw new InvalidArgumentException(
                "Nicht genügend Teams angemeldet. Minimum: {$tournament->min_teams}, Angemeldet: {$tournament->registered_teams}"
            );
        }

        $tournament->update(['status' => 'registration_closed']);

        return $tournament;
    }

    public function startTournament(Tournament $tournament): Tournament
    {
        if ($tournament->status !== 'registration_closed') {
            throw new InvalidArgumentException('Turnier kann nur nach Anmeldeschluss gestartet werden');
        }

        if (!$tournament->canStart()) {
            throw new InvalidArgumentException('Turnier kann nicht gestartet werden - Brackets fehlen oder nicht genügend Teams');
        }

        return DB::transaction(function () use ($tournament) {
            $tournament->update([
                'status' => 'in_progress',
                'start_date' => $tournament->start_date ?? now()->toDateString(),
            ]);

            // Schedule first round games
            $this->scheduleFirstRound($tournament);

            return $tournament;
        });
    }

    public function completeTournament(Tournament $tournament): Tournament
    {
        if ($tournament->status !== 'in_progress') {
            throw new InvalidArgumentException('Nur laufende Turniere können abgeschlossen werden');
        }

        return DB::transaction(function () use ($tournament) {
            // Ensure all standings are calculated
            $this->progressionService->advanceTournament($tournament);

            $tournament->update([
                'status' => 'completed',
                'end_date' => now()->toDateString(),
            ]);

            // Generate final awards
            $this->generateFinalAwards($tournament);

            // Send completion notifications
            $this->notifyTournamentComplete($tournament);

            return $tournament;
        });
    }

    // Bracket Management
    public function generateBrackets(Tournament $tournament): bool
    {
        if (!$tournament->canGenerateBrackets()) {
            throw new InvalidArgumentException('Brackets können nicht generiert werden');
        }

        return DB::transaction(function () use ($tournament) {
            $success = $this->bracketGenerator->generateBrackets($tournament);
            
            if ($success) {
                // Update tournament statistics
                $totalGames = $tournament->brackets()->count();
                $tournament->update(['total_games' => $totalGames]);
                
                // Auto-seed teams if not already seeded
                $this->autoSeedTeams($tournament);
            }

            return $success;
        });
    }

    public function regenerateBrackets(Tournament $tournament): bool
    {
        if ($tournament->status === 'in_progress') {
            throw new InvalidArgumentException('Brackets können während des Turniers nicht neu generiert werden');
        }

        // Clear existing brackets
        $tournament->brackets()->delete();

        return $this->generateBrackets($tournament);
    }

    public function seedTeams(Tournament $tournament, array $seedingData): Tournament
    {
        if ($tournament->status === 'in_progress') {
            throw new InvalidArgumentException('Seeding kann während des Turniers nicht geändert werden');
        }

        return DB::transaction(function () use ($tournament, $seedingData) {
            foreach ($seedingData as $teamId => $seed) {
                $tournament->tournamentTeams()
                          ->where('team_id', $teamId)
                          ->update(['seed' => $seed]);
            }

            // Regenerate brackets with new seeding
            if ($tournament->brackets()->exists()) {
                $this->regenerateBrackets($tournament);
            }

            return $tournament;
        });
    }

    // Officials Management
    public function inviteOfficial(Tournament $tournament, User $official, string $role, array $data = []): TournamentOfficial
    {
        return TournamentOfficial::create([
            'tournament_id' => $tournament->id,
            'user_id' => $official->id,
            'role' => $role,
            'status' => 'invited',
            'response_deadline' => $data['response_deadline'] ?? now()->addWeeks(2),
            'rate_per_game' => $data['rate_per_game'] ?? null,
            'max_games_per_day' => $data['max_games_per_day'] ?? 4,
            'available_dates' => $data['available_dates'] ?? [],
            'certification_level' => $data['certification_level'] ?? '',
            'experience_years' => $data['experience_years'] ?? 0,
        ]);
    }

    public function assignOfficialToGame(TournamentBracket $bracket, User $official, string $role): bool
    {
        $tournamentOfficial = $bracket->tournament
                                    ->officials()
                                    ->where('user_id', $official->id)
                                    ->where('role', $role)
                                    ->where('status', 'confirmed')
                                    ->first();

        if (!$tournamentOfficial) {
            throw new InvalidArgumentException('Official ist nicht für dieses Turnier bestätigt');
        }

        $fieldMap = [
            'head_referee' => 'primary_referee_id',
            'assistant_referee' => 'secondary_referee_id',
        ];

        $field = $fieldMap[$role] ?? null;
        
        if ($field) {
            $bracket->update([$field => $official->id]);
            $tournamentOfficial->assignGame($bracket->id);
            return true;
        }

        return false;
    }

    // Game Management
    public function scheduleGame(TournamentBracket $bracket, \DateTime $dateTime, array $options = []): TournamentBracket
    {
        $bracket->schedule(
            $dateTime,
            $options['venue'] ?? null,
            $options['court'] ?? null
        );

        // Create base game record if needed
        if (!$bracket->game_id) {
            $game = $this->createGameFromBracket($bracket);
            $bracket->update(['game_id' => $game->id]);
        }

        return $bracket;
    }

    public function recordGameResult(TournamentBracket $bracket, array $result): bool
    {
        if ($bracket->status !== 'in_progress') {
            throw new InvalidArgumentException('Spiel ist nicht aktiv');
        }

        return DB::transaction(function () use ($bracket, $result) {
            // Update the base game
            $game = $bracket->game;
            if ($game) {
                $game->update([
                    'home_score' => $result['team1_score'],
                    'away_score' => $result['team2_score'],
                    'score_by_period' => $result['score_by_period'] ?? null,
                    'overtime_periods' => $result['overtime_periods'] ?? 0,
                    'status' => 'completed',
                    'actual_end_time' => now(),
                ]);

                // Process the result through progression service
                return $this->progressionService->processGameResult($bracket, $game);
            }

            return false;
        });
    }

    public function forfeitGame(TournamentBracket $bracket, TournamentTeam $forfeitTeam, string $reason = null): bool
    {
        return DB::transaction(function () use ($bracket, $forfeitTeam, $reason) {
            $bracket->forfeit($forfeitTeam, $reason);
            return $this->progressionService->advanceTournament($bracket->tournament);
        });
    }

    // Statistics and Analytics
    public function getTournamentStatistics(Tournament $tournament): array
    {
        return [
            'basic_stats' => $this->getBasicTournamentStats($tournament),
            'team_stats' => $this->getTeamStatistics($tournament),
            'game_stats' => $this->getGameStatistics($tournament),
            'progression' => $this->getProgressionStatistics($tournament),
        ];
    }

    public function getTeamStandings(Tournament $tournament, string $groupName = null): Collection
    {
        $query = $tournament->tournamentTeams()->where('status', 'approved');
        
        if ($groupName) {
            $query->where('group_name', $groupName);
        }

        return $query->get()->sortByDesc([
            ['tournament_points', 'desc'],
            ['point_differential', 'desc'],
            ['points_for', 'desc'],
        ]);
    }

    public function getUpcomingGames(Tournament $tournament, int $limit = 10): Collection
    {
        return $tournament->brackets()
                         ->where('status', 'scheduled')
                         ->whereNotNull('scheduled_at')
                         ->orderBy('scheduled_at')
                         ->limit($limit)
                         ->with(['team1.team', 'team2.team'])
                         ->get();
    }

    // Award Management
    public function createAward(Tournament $tournament, array $awardData): TournamentAward
    {
        return $tournament->awards()->create($awardData);
    }

    public function assignAward(TournamentAward $award, $recipient): TournamentAward
    {
        if ($recipient instanceof TournamentTeam) {
            $award->update(['recipient_team_id' => $recipient->id]);
        } elseif ($recipient instanceof User) {
            $award->update(['recipient_player_id' => $recipient->id]);
        }

        $award->update(['selected_at' => now()]);
        return $award;
    }

    // Protected Helper Methods
    protected function setupTournamentDefaults(Tournament $tournament): void
    {
        // Set default game rules if not provided
        if (!$tournament->game_rules) {
            $tournament->update([
                'game_rules' => [
                    'periods' => 4,
                    'period_length' => 10,
                    'overtime_enabled' => true,
                    'shot_clock' => 24,
                    'timeouts_per_half' => 3,
                ],
            ]);
        }

        // Set default prizes structure
        if (!$tournament->prizes) {
            $tournament->update([
                'prizes' => [
                    'first_place' => 'Pokal + Urkunden',
                    'second_place' => 'Pokale + Urkunden',
                    'third_place' => 'Medaillen + Urkunden',
                ],
            ]);
        }
    }

    protected function autoSeedTeams(Tournament $tournament): void
    {
        $unseededTeams = $tournament->tournamentTeams()
                                  ->where('status', 'approved')
                                  ->whereNull('seed')
                                  ->orderBy('registered_at')
                                  ->get();

        $nextSeed = $tournament->tournamentTeams()
                             ->where('status', 'approved')
                             ->max('seed') + 1;

        foreach ($unseededTeams as $team) {
            $team->update(['seed' => $nextSeed++]);
        }
    }

    protected function scheduleFirstRound(Tournament $tournament): void
    {
        $firstRoundBrackets = $tournament->brackets()
                                        ->where('round', 1)
                                        ->where('status', 'pending')
                                        ->orderBy('position_in_round')
                                        ->get();

        $currentTime = $tournament->start_date->setTime(
            $tournament->daily_start_time->hour,
            $tournament->daily_start_time->minute
        );

        foreach ($firstRoundBrackets as $bracket) {
            $this->scheduleGame($bracket, $currentTime->copy());
            $currentTime->addMinutes($tournament->game_duration + 30); // 30 min break
        }
    }

    protected function createGameFromBracket(TournamentBracket $bracket): Game
    {
        $tournament = $bracket->tournament;
        
        return Game::create([
            'home_team_id' => $bracket->team1?->team_id,
            'away_team_id' => $bracket->team2?->team_id,
            'scheduled_at' => $bracket->scheduled_at,
            'venue' => $bracket->venue ?? $tournament->primary_venue,
            'court' => $bracket->court,
            'game_type' => 'tournament',
            'status' => 'scheduled',
            'periods' => $tournament->periods,
            'period_length' => $tournament->period_length,
            'overtime_enabled' => $tournament->overtime_enabled,
        ]);
    }

    protected function getBasicTournamentStats(Tournament $tournament): array
    {
        return [
            'total_teams' => $tournament->registered_teams,
            'total_games' => $tournament->total_games,
            'completed_games' => $tournament->completed_games,
            'completion_percentage' => $tournament->completion_progress,
            'days_duration' => $tournament->duration,
            'average_game_duration' => $tournament->average_game_duration,
        ];
    }

    protected function getTeamStatistics(Tournament $tournament): array
    {
        $teams = $tournament->tournamentTeams()->where('status', 'approved')->get();
        
        return [
            'total_points_scored' => $teams->sum('points_for'),
            'average_points_per_team' => $teams->avg('points_for'),
            'highest_scoring_team' => $teams->sortByDesc('points_for')->first(),
            'best_defense_team' => $teams->sortBy('points_against')->first(),
        ];
    }

    protected function getGameStatistics(Tournament $tournament): array
    {
        $brackets = $tournament->brackets()->where('status', 'completed')->get();
        
        return [
            'total_points_scored' => $brackets->sum(fn($b) => ($b->team1_score ?? 0) + ($b->team2_score ?? 0)),
            'average_points_per_game' => $brackets->avg(fn($b) => ($b->team1_score ?? 0) + ($b->team2_score ?? 0)),
            'overtime_games' => $brackets->where('overtime', true)->count(),
            'blowout_games' => $brackets->filter(fn($b) => abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) >= 20)->count(),
        ];
    }

    protected function getProgressionStatistics(Tournament $tournament): array
    {
        return [
            'rounds_completed' => $tournament->brackets()->where('status', 'completed')->max('round') ?? 0,
            'teams_remaining' => $tournament->tournamentTeams()->whereNull('eliminated_at')->count(),
            'next_round_games' => $tournament->brackets()->where('status', 'pending')->count(),
        ];
    }

    protected function generateFinalAwards(Tournament $tournament): void
    {
        // Generate automatic awards based on final standings
        $standings = $this->getTeamStandings($tournament);
        
        if ($standings->isNotEmpty()) {
            // Champion
            $champion = $standings->first();
            $tournament->awards()->updateOrCreate([
                'award_category' => 'champion',
            ], [
                'award_name' => 'Tournament Champion',
                'award_type' => 'team_award',
                'recipient_team_id' => $champion->id,
                'selected_at' => now(),
                'selection_method' => 'automatic',
            ]);

            // Runner-up
            if ($standings->count() > 1) {
                $runnerUp = $standings->get(1);
                $tournament->awards()->updateOrCreate([
                    'award_category' => 'runner_up',
                ], [
                    'award_name' => 'Tournament Runner-Up',
                    'award_type' => 'team_award',
                    'recipient_team_id' => $runnerUp->id,
                    'selected_at' => now(),
                    'selection_method' => 'automatic',
                ]);
            }
        }
    }

    protected function notifyRegistrationOpen(Tournament $tournament): void
    {
        // Implementation for notifying potential participants
        // Could send emails, push notifications, etc.
    }

    protected function notifyTournamentComplete(Tournament $tournament): void
    {
        // Implementation for tournament completion notifications
        // Could send final results, certificates, etc.
    }
}