<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Player;
use App\Models\User;
use App\Models\Club;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Collection;

class TeamService
{

    /**
     * Create a new team.
     */
    public function createTeam(array $data): Team
    {
        Log::info("TeamService::createTeam - Starting team creation", [
            'input_data' => $data,
            'auth_user_id' => auth()->id(),
            'auth_check' => auth()->check(),
            'session_exists' => session()->getId() ?? 'no_session',
        ]);

        DB::beginTransaction();

        try {
            // Validate user_id first
            $userId = auth()->id();
            if (!$userId) {
                throw new \RuntimeException('Benutzer ist nicht authentifiziert. user_id ist null.');
            }

            Log::info("TeamService::createTeam - User validated", [
                'user_id' => $userId,
                'user' => auth()->user()?->toArray(),
            ]);

            // Only use fields that are validated in the controller
            $teamData = [
                'name' => $data['name'],
                'club_id' => $data['club_id'],
                'season' => $data['season'],
                'league' => $data['league'] ?? null,
                'division' => $data['division'] ?? null,
                'age_group' => $data['age_group'] ?? null,
                'gender' => $data['gender'],
                'is_active' => $data['is_active'] ?? true,
                'description' => $data['description'] ?? null,
                'user_id' => $userId, // Required for Jetstream Team compatibility
                'personal_team' => false, // This is a basketball team, not a personal team
            ];


            Log::info("TeamService::createTeam - Prepared team data", [
                'team_data' => $teamData,
                'original_data' => $data
            ]);

            // Check if club exists
            $club = \App\Models\Club::find($teamData['club_id']);
            if (!$club) {
                throw new \RuntimeException("Club mit ID {$teamData['club_id']} nicht gefunden.");
            }

            Log::info("TeamService::createTeam - Club validated", [
                'club_id' => $club->id,
                'club_name' => $club->name,
            ]);

            // Create the team
            $team = Team::create($teamData);

            if (!$team) {
                throw new \RuntimeException('Team-Erstellung fehlgeschlagen - Team::create() gab null zurück.');
            }

            if (!$team->id) {
                throw new \RuntimeException('Team wurde erstellt, aber hat keine ID.');
            }

            DB::commit();

            Log::info("TeamService::createTeam - Team created successfully", [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'club_id' => $team->club_id,
                'user_id' => $team->user_id,
                'personal_team' => $team->personal_team,
                'created_at' => $team->created_at,
            ]);

            // Verify the team exists in database
            $verifyTeam = Team::find($team->id);
            if (!$verifyTeam) {
                throw new \RuntimeException("Team wurde erstellt (ID: {$team->id}), ist aber nicht in der Datenbank auffindbar.");
            }

            Log::info("TeamService::createTeam - Team verification successful", [
                'verified_team_id' => $verifyTeam->id,
                'verified_team_name' => $verifyTeam->name,
            ]);

            return $team->fresh(['club']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("TeamService::createTeam - Failed to create team", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input_data' => $data,
                'auth_user_id' => auth()->id(),
                'auth_check' => auth()->check(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing team.
     */
    public function updateTeam(Team $team, array $data): Team
    {
        DB::beginTransaction();

        try {
            $team->update($data);

            // Update coach assignments if changed
            if (isset($data['head_coach_id']) && $data['head_coach_id'] !== $team->head_coach_id) {
                if ($team->head_coach_id) {
                    $this->removeCoachFromTeam($team, $team->head_coach_id, 'head_coach');
                }
                if ($data['head_coach_id']) {
                    $this->addCoachToTeam($team, $data['head_coach_id'], 'head_coach');
                }
            }

            if (isset($data['assistant_coaches'])) {
                // Remove existing assistant coaches
                $currentAssistantCoaches = $team->assistant_coaches ?? [];
                foreach ($currentAssistantCoaches as $assistantCoachId) {
                    $this->removeCoachFromTeam($team, $assistantCoachId, 'assistant_coach');
                }
                
                // Add new assistant coaches
                if (is_array($data['assistant_coaches'])) {
                    foreach ($data['assistant_coaches'] as $assistantCoachId) {
                        $this->addCoachToTeam($team, $assistantCoachId, 'assistant_coach');
                    }
                }
            }

            DB::commit();

            Log::info("Team updated successfully", [
                'team_id' => $team->id,
                'team_name' => $team->name
            ]);

            return $team->fresh(['club', 'headCoach']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update team", [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete a team.
     */
    public function deleteTeam(Team $team): bool
    {
        DB::beginTransaction();

        try {
            // Check if team has active players
            if ($team->activePlayers()->count() > 0) {
                throw new \InvalidArgumentException('Team kann nicht gelöscht werden, da noch aktive Spieler vorhanden sind.');
            }

            // Check if team has scheduled games
            $upcomingGames = $team->allGames()
                ->where('scheduled_at', '>', now())
                ->where('status', 'scheduled')
                ->count();

            if ($upcomingGames > 0) {
                throw new \InvalidArgumentException('Team kann nicht gelöscht werden, da noch geplante Spiele vorhanden sind.');
            }

            // Remove all team memberships
            $team->members()->detach();

            // Soft delete the team
            $team->delete();

            DB::commit();

            Log::info("Team deleted successfully", [
                'team_id' => $team->id,
                'team_name' => $team->name
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete team", [
                'team_id' => $team->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get comprehensive team statistics.
     */
    public function getTeamStatistics(Team $team): array
    {
        $currentSeason = $team->season;
        
        return [
            'basic_stats' => [
                'games_played' => $team->games_played,
                'games_won' => $team->games_won,
                'games_lost' => $team->games_lost,
                'games_tied' => $team->games_tied,
                'win_percentage' => $team->win_percentage,
                'points_scored' => $team->points_scored,
                'points_allowed' => $team->points_allowed,
                'points_per_game' => $team->games_played > 0 ? round($team->points_scored / $team->games_played, 1) : 0,
                'points_allowed_per_game' => $team->games_played > 0 ? round($team->points_allowed / $team->games_played, 1) : 0,
            ],
            'roster_stats' => [
                'current_roster_size' => $team->current_roster_size,
                'max_players' => $team->max_players,
                'available_spots' => $team->players_slots_available,
                'average_player_age' => $team->average_player_age,
                'captains_count' => $team->players()->wherePivot('is_captain', true)->wherePivot('is_active', true)->count(),
                'starters_count' => $team->players()->wherePivot('is_starter', true)->wherePivot('is_active', true)->count(),
            ],
            'season_stats' => App::make(StatisticsService::class)->getTeamSeasonStats($team, $currentSeason),
            'recent_performance' => $this->getRecentPerformance($team, 5),
            'player_contributions' => $this->getTopPlayerContributions($team, $currentSeason),
        ];
    }

    /**
     * Add a player to the team.
     */
    public function addPlayerToTeam(Team $team, array $playerData): Player
    {
        DB::beginTransaction();

        try {
            // Check if team can accept new players
            if (!$team->canAcceptNewPlayer()) {
                throw new \InvalidArgumentException('Team kann keine neuen Spieler aufnehmen.');
            }

            // Validate jersey number uniqueness
            if (!empty($playerData['jersey_number'])) {
                $existingPlayer = $team->players()
                    ->where('jersey_number', $playerData['jersey_number'])
                    ->wherePivot('status', 'active')
                    ->first();
                
                if ($existingPlayer) {
                    throw new \InvalidArgumentException("Trikotnummer {$playerData['jersey_number']} ist bereits vergeben.");
                }
            }

            $user = User::findOrFail($playerData['user_id']);
            
            // Check if user is already on another active team in the same league/season
            $existingMembership = $user->players()
                ->whereHas('team', function ($query) use ($team) {
                    $query->where('season', $team->season)
                          ->where('league', $team->league)
                          ->where('is_active', true);
                })
                ->where('players.status', 'active')
                ->first();

            if ($existingMembership) {
                throw new \InvalidArgumentException('Spieler ist bereits in einem anderen aktiven Team in dieser Liga registriert.');
            }

            // Create player record
            $player = Player::create([
                'user_id' => $user->id,
                'team_id' => $team->id,
                'jersey_number' => $playerData['jersey_number'] ?? null,
                'primary_position' => $playerData['position'] ?? null,
                'secondary_positions' => $playerData['secondary_positions'] ?? null,
                'is_starter' => $playerData['is_starter'] ?? false,
                'is_captain' => $playerData['is_captain'] ?? false,
                'status' => 'active',
                'joined_at' => now(),
                'contract_start_date' => $playerData['contract_start_date'] ?? now(),
                'contract_end_date' => $playerData['contract_end_date'] ?? null,
                'salary' => $playerData['salary'] ?? null,
                'notes' => $playerData['notes'] ?? null,
            ]);

            // Add user to team members
            $team->members()->attach($user->id, [
                'role' => 'player',
                'joined_at' => now()
            ]);

            DB::commit();

            Log::info("Player added to team successfully", [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'player_id' => $player->id
            ]);

            return $player->fresh(['user', 'team']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to add player to team", [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
                'player_data' => $playerData
            ]);
            throw $e;
        }
    }

    /**
     * Remove a player from the team.
     */
    public function removePlayerFromTeam(Team $team, Player $player): bool
    {
        DB::beginTransaction();

        try {
            if ($player->team_id !== $team->id) {
                throw new \InvalidArgumentException('Spieler gehört nicht zu diesem Team.');
            }

            // Check if player has upcoming games
            $upcomingGames = $team->allGames()
                ->where('scheduled_at', '>', now())
                ->where('status', 'scheduled')
                ->count();

            if ($upcomingGames > 0 && $player->is_starter) {
                Log::warning("Removing starter player with upcoming games", [
                    'team_id' => $team->id,
                    'player_id' => $player->id,
                    'upcoming_games' => $upcomingGames
                ]);
            }

            // Update player status instead of deleting
            $player->update([
                'status' => 'inactive',
                'left_at' => now(),
                'is_starter' => false,
                'is_captain' => false,
            ]);

            // Remove from team members
            $team->members()->detach($player->user_id);

            DB::commit();

            Log::info("Player removed from team successfully", [
                'team_id' => $team->id,
                'player_id' => $player->id
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to remove player from team", [
                'team_id' => $team->id,
                'player_id' => $player->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate comprehensive team report.
     */
    public function generateTeamReport(Team $team): array
    {
        return [
            'team_info' => [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'club' => $team->club->name,
                'season' => $team->season,
                'league' => $team->league,
                'division' => $team->division,
                'gender' => $team->gender,
                'age_group' => $team->age_group,
                'competitive_level' => $team->competitive_level,
            ],
            'coaching_staff' => [
                'head_coach' => $team->headCoach?->name,
                'assistant_coach' => $team->assistantCoach?->name,
            ],
            'roster' => $this->getTeamRoster($team),
            'statistics' => $this->getTeamStatistics($team),
            'recent_games' => $this->getRecentGames($team, 10),
            'upcoming_games' => $this->getUpcomingGames($team, 5),
            'training_info' => [
                'practice_times' => $team->practice_times,
                'home_venue' => $team->home_venue,
                'venue_details' => $team->venue_details,
            ],
            'financial_info' => [
                'registration_fee' => $team->registration_fee,
                'monthly_fee' => $team->monthly_fee,
                'equipment_provided' => $team->equipment_provided,
            ],
            'requirements' => [
                'insurance_required' => $team->insurance_required,
                'medical_check_required' => $team->medical_check_required,
                'requirements' => $team->requirements,
                'min_age' => $team->min_age,
                'max_age' => $team->max_age,
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Add coach to team.
     */
    private function addCoachToTeam(Team $team, int $userId, string $role): void
    {
        $team->members()->attach($userId, [
            'role' => $role,
            'joined_at' => now()
        ]);
    }

    /**
     * Remove coach from team.
     */
    private function removeCoachFromTeam(Team $team, int $userId, string $role): void
    {
        $team->members()->wherePivot('role', $role)->detach($userId);
    }

    /**
     * Get team roster with detailed player information.
     */
    private function getTeamRoster(Team $team): array
    {
        $players = $team->players()
            ->with(['user:id,name,date_of_birth'])
            ->wherePivot('status', 'active')
            ->wherePivot('is_active', true)
            ->orderBy('player_team.jersey_number')
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->user?->name ?? $player->full_name,
                    'jersey_number' => $player->pivot->jersey_number,
                    'position' => $player->pivot->primary_position,
                    'age' => $player->user?->date_of_birth?->age,
                    'is_captain' => $player->pivot->is_captain,
                    'is_starter' => $player->pivot->is_starter,
                    'status' => $player->pivot->status,
                    'joined_at' => $player->pivot->joined_at?->format('Y-m-d'),
                ];
            });

        return [
            'players' => $players,
            'total_players' => $players->count(),
            'captains' => $players->where('is_captain', true)->values(),
            'starters' => $players->where('is_starter', true)->values(),
        ];
    }

    /**
     * Get recent team performance.
     */
    private function getRecentPerformance(Team $team, int $gameCount): array
    {
        $recentGames = $team->allGames()
            ->where('status', 'finished')
            ->orderBy('scheduled_at', 'desc')
            ->limit($gameCount)
            ->get();

        $wins = 0;
        $totalPoints = 0;
        $totalPointsAllowed = 0;

        foreach ($recentGames as $game) {
            $teamScore = $game->isHomeTeam($team) ? $game->home_team_score : $game->away_team_score;
            $opponentScore = $game->isHomeTeam($team) ? $game->away_team_score : $game->home_team_score;
            
            if ($teamScore > $opponentScore) {
                $wins++;
            }
            
            $totalPoints += $teamScore;
            $totalPointsAllowed += $opponentScore;
        }

        return [
            'games_played' => $recentGames->count(),
            'wins' => $wins,
            'losses' => $recentGames->count() - $wins,
            'win_percentage' => $recentGames->count() > 0 ? round(($wins / $recentGames->count()) * 100, 1) : 0,
            'avg_points_scored' => $recentGames->count() > 0 ? round($totalPoints / $recentGames->count(), 1) : 0,
            'avg_points_allowed' => $recentGames->count() > 0 ? round($totalPointsAllowed / $recentGames->count(), 1) : 0,
        ];
    }

    /**
     * Get top player contributions for the team.
     */
    private function getTopPlayerContributions(Team $team, string $season): array
    {
        $players = $team->players()->wherePivot('status', 'active')->get();
        $contributions = [];

        foreach ($players as $player) {
            $stats = App::make(StatisticsService::class)->getPlayerSeasonStats($player, $season);
            
            $contributions[] = [
                'player_id' => $player->id,
                'name' => $player->user?->name ?? $player->full_name,
                'jersey_number' => $player->jersey_number,
                'avg_points' => $stats['avg_points'] ?? 0,
                'avg_rebounds' => $stats['avg_rebounds'] ?? 0,
                'avg_assists' => $stats['avg_assists'] ?? 0,
                'total_points' => $stats['total_points'] ?? 0,
                'games_played' => $stats['games_played'] ?? 0,
            ];
        }

        // Sort by total points descending
        usort($contributions, function ($a, $b) {
            return $b['total_points'] <=> $a['total_points'];
        });

        return array_slice($contributions, 0, 10); // Top 10 contributors
    }

    /**
     * Get recent games.
     */
    private function getRecentGames(Team $team, int $limit): Collection
    {
        return $team->allGames()
            ->with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('status', 'finished')
            ->orderBy('scheduled_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get upcoming games.
     */
    private function getUpcomingGames(Team $team, int $limit): Collection
    {
        return $team->allGames()
            ->with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->get();
    }
}