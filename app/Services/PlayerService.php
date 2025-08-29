<?php

namespace App\Services;

use App\Models\Player;
use App\Models\User;
use App\Models\Team;
use App\Models\EmergencyContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;

class PlayerService
{

    /**
     * Create a new player.
     */
    public function createPlayer(array $data): Player
    {
        DB::beginTransaction();

        try {
            // Validate user exists
            if (!isset($data['user_id'])) {
                throw new \InvalidArgumentException('Benutzer-ID ist erforderlich.');
            }
            $user = User::findOrFail($data['user_id']);
            
            // Validate team exists if provided
            $team = null;
            if (!empty($data['team_id'])) {
                $team = Team::findOrFail($data['team_id']);
                
                // Check if team can accept new players
                if (!$team->canAcceptNewPlayer()) {
                    throw new \InvalidArgumentException('Team kann keine neuen Spieler aufnehmen.');
                }

                // Validate jersey number uniqueness within team
                if (!empty($data['jersey_number'])) {
                    $existingPlayer = $team->players()
                        ->where('player_team.jersey_number', $data['jersey_number'])
                        ->wherePivot('status', 'active')
                        ->first();
                    
                    if ($existingPlayer) {
                        throw new \InvalidArgumentException("Trikotnummer {$data['jersey_number']} ist bereits vergeben.");
                    }
                }

                // Check if user is already on another active team in the same league/season
                $player = $user->playerProfile;
                $existingMembership = null;
                
                if ($player && $player->status === 'active') {
                    $existingMembership = $player->teams()
                        ->where('teams.season', $team->season)
                        ->where('teams.league', $team->league)
                        ->where('teams.is_active', true)
                        ->wherePivot('status', 'active')
                        ->first();
                }

                if ($existingMembership) {
                    throw new \InvalidArgumentException('Spieler ist bereits in einem anderen aktiven Team in dieser Liga registriert.');
                }
            }

            // Create player record
            $player = Player::create([
                'user_id' => $user->id,
                'team_id' => $data['team_id'] ?? null,
                'first_name' => $data['first_name'] ?? $user->first_name,
                'last_name' => $data['last_name'] ?? $user->last_name,
                'full_name' => $data['full_name'] ?? $user->name,
                'jersey_number' => $data['jersey_number'] ?? null,
                'primary_position' => $data['primary_position'] ?? null,
                'secondary_positions' => $data['secondary_positions'] ?? null,
                'is_starter' => $data['is_starter'] ?? false,
                'is_captain' => $data['is_captain'] ?? false,
                'status' => $data['status'] ?? 'active',
                'joined_at' => $data['joined_at'] ?? now(),
                'contract_start_date' => $data['contract_start_date'] ?? now(),
                'contract_end_date' => $data['contract_end_date'] ?? null,
                'salary' => $data['salary'] ?? null,
                'medical_clearance' => $data['medical_clearance'] ?? false,
                'medical_clearance_date' => $data['medical_clearance_date'] ?? null,
                'medical_clearance_expires' => $data['medical_clearance_expires'] ?? null,
                'insurance_company' => $data['insurance_company'] ?? null,
                'insurance_policy_number' => $data['insurance_policy_number'] ?? null,
                'insurance_expires' => $data['insurance_expires'] ?? null,
                'academic_eligibility' => $data['academic_eligibility'] ?? true,
                'grade_level' => $data['grade_level'] ?? null,
                'gpa' => $data['gpa'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
                'weight_kg' => $data['weight_kg'] ?? null,
                'dominant_hand' => $data['dominant_hand'] ?? 'right',
                'years_experience' => $data['years_experience'] ?? 0,
                'previous_teams' => $data['previous_teams'] ?? null,
                'training_focus_areas' => $data['training_focus_areas'] ?? null,
                'development_goals' => $data['development_goals'] ?? null,
                'coach_notes' => $data['coach_notes'] ?? null,
                'shooting_rating' => $data['shooting_rating'] ?? null,
                'defense_rating' => $data['defense_rating'] ?? null,
                'passing_rating' => $data['passing_rating'] ?? null,
                'rebounding_rating' => $data['rebounding_rating'] ?? null,
                'speed_rating' => $data['speed_rating'] ?? null,
                'overall_rating' => $data['overall_rating'] ?? null,
                'achievements' => $data['achievements'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Add player to team members if team is specified
            if ($team) {
                $team->members()->attach($user->id, [
                    'role' => 'player',
                    'joined_at' => now()
                ]);
            }

            // Create emergency contacts if provided
            if (isset($data['emergency_contacts'])) {
                foreach ($data['emergency_contacts'] as $contactData) {
                    $this->createEmergencyContact($player, $contactData);
                }
            }

            DB::commit();

            Log::info("Player created successfully", [
                'player_id' => $player->id,
                'user_id' => $user->id,
                'team_id' => $player->team_id
            ]);

            return $player->fresh(['user', 'team.club', 'emergencyContacts']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create player", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing player.
     */
    public function updatePlayer(Player $player, array $data): Player
    {
        DB::beginTransaction();

        try {
            // Handle team transfer if team_id is changing
            if (isset($data['team_id']) && (int)$data['team_id'] !== (int)$player->team_id) {
                $newTeam = Team::findOrFail($data['team_id']);
                
                // Provide more detailed error message for team capacity issues
                if (!$newTeam->canAcceptNewPlayer()) {
                    $reasons = $newTeam->getCannotAcceptPlayerReasons();
                    $reasonText = implode(', ', $reasons);
                    throw new \InvalidArgumentException("Ziel-Team kann keine neuen Spieler aufnehmen. Grund: {$reasonText}");
                }
                
                $this->transferPlayer($player, $newTeam);
                unset($data['team_id']); // Remove from update data as it's handled by transfer
            }

            // Validate jersey number uniqueness if changing
            if (isset($data['jersey_number']) && $data['jersey_number'] !== $player->jersey_number && $player->team) {
                $existingPlayer = $player->team->players()
                    ->where('player_team.jersey_number', $data['jersey_number'])
                    ->wherePivot('status', 'active')
                    ->where('players.id', '!=', $player->id)
                    ->first();
                
                if ($existingPlayer) {
                    throw new \InvalidArgumentException("Trikotnummer {$data['jersey_number']} ist bereits vergeben.");
                }
            }

            // Update player data
            $player->update($data);

            // Update emergency contacts if provided
            if (isset($data['emergency_contacts'])) {
                $this->updateEmergencyContacts($player, $data['emergency_contacts']);
            }

            DB::commit();

            Log::info("Player updated successfully", [
                'player_id' => $player->id,
                'updated_fields' => array_keys($data)
            ]);

            return $player->fresh(['user', 'team.club', 'emergencyContacts']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update player", [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete a player.
     */
    public function deletePlayer(Player $player): bool
    {
        DB::beginTransaction();

        try {
            // Check if player has active games or statistics
            $hasActiveGames = $player->gameActions()->exists();
            
            if ($hasActiveGames) {
                // Soft delete to preserve historical data
                $player->update([
                    'status' => 'inactive',
                    'left_at' => now(),
                ]);
                
                // Remove from team members but keep player record
                if ($player->team) {
                    $player->team->members()->detach($player->user_id);
                }
                
                Log::info("Player soft deleted (has game history)", [
                    'player_id' => $player->id
                ]);
            } else {
                // Hard delete if no game history
                $player->emergencyContacts()->delete();
                
                if ($player->team) {
                    $player->team->members()->detach($player->user_id);
                }
                
                $player->delete();
                
                Log::info("Player hard deleted (no game history)", [
                    'player_id' => $player->id
                ]);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete player", [
                'player_id' => $player->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get player statistics for a specific season.
     */
    public function getPlayerStatistics(Player $player, ?string $season = null): array
    {
        // Use provided season, or fallback to current season
        $season = $season ?? config('basketball.season.current');
        
        $stats = App::make(StatisticsService::class)->getPlayerSeasonStats($player, $season);
        
        // Add player-specific calculations
        $basicStats = [
            'games_played' => $player->games_played,
            'games_started' => $player->games_started,
            'minutes_played' => $player->minutes_played,
            'points_scored' => $player->points_scored,
            'field_goals_made' => $player->field_goals_made,
            'field_goals_attempted' => $player->field_goals_attempted,
            'three_pointers_made' => $player->three_pointers_made,
            'three_pointers_attempted' => $player->three_pointers_attempted,
            'free_throws_made' => $player->free_throws_made,
            'free_throws_attempted' => $player->free_throws_attempted,
            'rebounds_offensive' => $player->rebounds_offensive,
            'rebounds_defensive' => $player->rebounds_defensive,
            'rebounds_total' => $player->rebounds_total,
            'assists' => $player->assists,
            'steals' => $player->steals,
            'blocks' => $player->blocks,
            'turnovers' => $player->turnovers,
            'fouls_personal' => $player->fouls_personal,
            'fouls_technical' => $player->fouls_technical,
        ];

        // Calculate per-game averages
        $perGameStats = [];
        if ($player->games_played > 0) {
            $perGameStats = [
                'points_per_game' => $player->points_per_game,
                'rebounds_per_game' => $player->rebounds_per_game,
                'assists_per_game' => $player->assists_per_game,
                'minutes_per_game' => round($player->minutes_played / $player->games_played, 1),
                'steals_per_game' => round($player->steals / $player->games_played, 1),
                'blocks_per_game' => round($player->blocks / $player->games_played, 1),
                'turnovers_per_game' => round($player->turnovers / $player->games_played, 1),
                'fouls_per_game' => round($player->fouls_personal / $player->games_played, 1),
            ];
        }

        // Calculate shooting percentages
        $shootingStats = [
            'field_goal_percentage' => $player->field_goal_percentage,
            'three_point_percentage' => $player->three_point_percentage,
            'free_throw_percentage' => $player->free_throw_percentage,
        ];

        return array_merge($basicStats, $perGameStats, $shootingStats, $stats);
    }

    /**
     * Update player statistics from game data.
     */
    public function updatePlayerStatistics(Player $player, array $gameStats): void
    {
        DB::beginTransaction();

        try {
            // Calculate new totals
            $updates = [];
            
            // Games and minutes
            if (isset($gameStats['minutes_played'])) {
                $updates['minutes_played'] = $player->minutes_played + $gameStats['minutes_played'];
            }
            
            if (isset($gameStats['game_started']) && $gameStats['game_started']) {
                $updates['games_started'] = $player->games_started + 1;
            }
            
            $updates['games_played'] = $player->games_played + 1;

            // Scoring
            if (isset($gameStats['field_goals_made'])) {
                $updates['field_goals_made'] = $player->field_goals_made + $gameStats['field_goals_made'];
                $updates['points_scored'] = $player->points_scored + ($gameStats['field_goals_made'] * 2);
            }
            
            if (isset($gameStats['field_goals_attempted'])) {
                $updates['field_goals_attempted'] = $player->field_goals_attempted + $gameStats['field_goals_attempted'];
            }
            
            if (isset($gameStats['three_pointers_made'])) {
                $updates['three_pointers_made'] = $player->three_pointers_made + $gameStats['three_pointers_made'];
                $updates['points_scored'] = ($updates['points_scored'] ?? $player->points_scored) + $gameStats['three_pointers_made']; // Additional point for 3-pointers
            }
            
            if (isset($gameStats['three_pointers_attempted'])) {
                $updates['three_pointers_attempted'] = $player->three_pointers_attempted + $gameStats['three_pointers_attempted'];
            }
            
            if (isset($gameStats['free_throws_made'])) {
                $updates['free_throws_made'] = $player->free_throws_made + $gameStats['free_throws_made'];
                $updates['points_scored'] = ($updates['points_scored'] ?? $player->points_scored) + $gameStats['free_throws_made'];
            }
            
            if (isset($gameStats['free_throws_attempted'])) {
                $updates['free_throws_attempted'] = $player->free_throws_attempted + $gameStats['free_throws_attempted'];
            }

            // Rebounds
            if (isset($gameStats['rebounds_offensive'])) {
                $updates['rebounds_offensive'] = $player->rebounds_offensive + $gameStats['rebounds_offensive'];
            }
            
            if (isset($gameStats['rebounds_defensive'])) {
                $updates['rebounds_defensive'] = $player->rebounds_defensive + $gameStats['rebounds_defensive'];
            }
            
            $updates['rebounds_total'] = 
                ($updates['rebounds_offensive'] ?? $player->rebounds_offensive) + 
                ($updates['rebounds_defensive'] ?? $player->rebounds_defensive);

            // Other stats
            $statFields = ['assists', 'steals', 'blocks', 'turnovers', 'fouls_personal', 'fouls_technical'];
            foreach ($statFields as $field) {
                if (isset($gameStats[$field])) {
                    $updates[$field] = $player->$field + $gameStats[$field];
                }
            }

            $player->update($updates);

            // Invalidate statistics cache
            App::make(StatisticsService::class)->invalidatePlayerStats($player);

            DB::commit();

            Log::info("Player statistics updated successfully", [
                'player_id' => $player->id,
                'game_stats' => $gameStats
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update player statistics", [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
                'game_stats' => $gameStats
            ]);
            throw $e;
        }
    }

    /**
     * Transfer player to a new team.
     */
    public function transferPlayer(Player $player, Team $newTeam): bool
    {
        DB::beginTransaction();

        try {
            $oldTeam = $player->team;
            
            // Check if new team can accept the player
            if (!$newTeam->canAcceptNewPlayer()) {
                $reasons = $newTeam->getCannotAcceptPlayerReasons();
                $reasonText = implode(', ', $reasons);
                throw new \InvalidArgumentException("Ziel-Team kann keine neuen Spieler aufnehmen. Grund: {$reasonText}");
            }

            // Check for jersey number conflicts
            if ($player->jersey_number) {
                $existingPlayer = $newTeam->players()
                    ->where('player_team.jersey_number', $player->jersey_number)
                    ->wherePivot('status', 'active')
                    ->first();
                
                if ($existingPlayer) {
                    // Clear jersey number for transfer - will need to be reassigned
                    $player->update(['jersey_number' => null]);
                    
                    Log::warning("Jersey number cleared due to conflict during transfer", [
                        'player_id' => $player->id,
                        'old_jersey' => $player->jersey_number,
                        'conflicting_player_id' => $existingPlayer->id
                    ]);
                }
            }

            // Update player team assignment
            $player->update([
                'team_id' => $newTeam->id,
                'is_starter' => false, // Reset starter status
                'is_captain' => false, // Reset captain status
                'status' => 'active',
                'transfer_date' => now(),
            ]);

            // Update team memberships
            if ($oldTeam) {
                $oldTeam->members()->detach($player->user_id);
            }
            
            $newTeam->members()->attach($player->user_id, [
                'role' => 'player',
                'joined_at' => now()
            ]);

            DB::commit();

            Log::info("Player transferred successfully", [
                'player_id' => $player->id,
                'old_team_id' => $oldTeam?->id,
                'new_team_id' => $newTeam->id
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to transfer player", [
                'player_id' => $player->id,
                'new_team_id' => $newTeam->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate comprehensive player report.
     */
    public function generatePlayerReport(Player $player): array
    {
        $currentSeason = $player->team?->season ?? date('Y');
        
        return [
            'player_info' => [
                'id' => $player->id,
                'name' => $player->full_name,
                'jersey_number' => $player->jersey_number,
                'primary_position' => $player->primary_position,
                'secondary_positions' => $player->secondary_positions,
                'status' => $player->status,
                'is_captain' => $player->is_captain,
                'is_starter' => $player->is_starter,
                'height_cm' => $player->height_cm,
                'weight_kg' => $player->weight_kg,
                'dominant_hand' => $player->dominant_hand,
                'years_experience' => $player->years_experience,
            ],
            'user_info' => [
                'name' => $player->user?->name,
                'email' => $player->user?->email,
                'birth_date' => $player->user?->birth_date?->format('Y-m-d'),
                'age' => $player->user?->birth_date?->age,
                'phone' => $player->user?->phone,
            ],
            'team_info' => [
                'team_name' => $player->team?->name,
                'club_name' => $player->team?->club?->name,
                'season' => $player->team?->season,
                'league' => $player->team?->league,
                'head_coach' => $player->team?->headCoach?->name,
            ],
            'contract_info' => [
                'joined_at' => $player->joined_at?->format('Y-m-d'),
                'contract_start_date' => $player->contract_start_date?->format('Y-m-d'),
                'contract_end_date' => $player->contract_end_date?->format('Y-m-d'),
                'salary' => $player->salary,
            ],
            'eligibility' => [
                'can_play' => $player->canPlay(),
                'is_minor' => $player->isMinor(),
                'medical_clearance' => $player->medical_clearance,
                'medical_clearance_date' => $player->medical_clearance_date?->format('Y-m-d'),
                'medical_clearance_expires' => $player->medical_clearance_expires?->format('Y-m-d'),
                'medical_clearance_expired' => $player->medical_clearance_expired,
                'insurance_company' => $player->insurance_company,
                'insurance_expires' => $player->insurance_expires?->format('Y-m-d'),
                'insurance_expired' => $player->insurance_expired,
                'academic_eligibility' => $player->academic_eligibility,
                'grade_level' => $player->grade_level,
                'gpa' => $player->gpa,
            ],
            'statistics' => $this->getPlayerStatistics($player, $currentSeason),
            'development' => [
                'training_focus_areas' => $player->training_focus_areas,
                'development_goals' => $player->development_goals,
                'coach_notes' => $player->coach_notes,
                'ratings' => [
                    'shooting' => $player->shooting_rating,
                    'defense' => $player->defense_rating,
                    'passing' => $player->passing_rating,
                    'rebounding' => $player->rebounding_rating,
                    'speed' => $player->speed_rating,
                    'overall' => $player->overall_rating,
                ],
                'achievements' => $player->achievements,
                'previous_teams' => $player->previous_teams,
            ],
            'emergency_contacts' => $player->getEmergencyContacts(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Create emergency contact for player.
     */
    private function createEmergencyContact(Player $player, array $contactData): EmergencyContact
    {
        return $player->emergencyContacts()->create([
            'name' => $contactData['name'],
            'relationship' => $contactData['relationship'],
            'phone_primary' => $contactData['phone_primary'],
            'phone_secondary' => $contactData['phone_secondary'] ?? null,
            'email' => $contactData['email'] ?? null,
            'address' => $contactData['address'] ?? null,
            'is_primary' => $contactData['is_primary'] ?? false,
            'can_pickup' => $contactData['can_pickup'] ?? false,
            'medical_authority' => $contactData['medical_authority'] ?? false,
            'notes' => $contactData['notes'] ?? null,
        ]);
    }

    /**
     * Update player's emergency contacts.
     */
    private function updateEmergencyContacts(Player $player, array $contactsData): void
    {
        // Delete existing contacts
        $player->emergencyContacts()->delete();
        
        // Create new contacts
        foreach ($contactsData as $contactData) {
            $this->createEmergencyContact($player, $contactData);
        }
    }
}