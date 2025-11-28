<?php

namespace App\Services\Club;

use App\Models\Club;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClubStatisticsService
{
    /**
     * Get comprehensive club statistics.
     */
    public function getClubStatistics(Club $club): array
    {
        try {
            // Basic counts
            $teamStats = $club->teams()->selectRaw('
                COUNT(*) as total_teams,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_teams,
                COUNT(DISTINCT season) as seasons_active,
                COUNT(DISTINCT league) as leagues_participated
            ')->first();

            $playerStats = Player::query()
                ->selectRaw('
                    COUNT(*) as total_players,
                    SUM(CASE WHEN player_team.status = "active" THEN 1 ELSE 0 END) as active_players,
                    AVG(CASE WHEN users.date_of_birth IS NOT NULL THEN YEAR(CURDATE()) - YEAR(users.date_of_birth) END) as avg_player_age
                ')
                ->join('player_team', 'player_team.player_id', '=', 'players.id')
                ->join('teams', 'teams.id', '=', 'player_team.team_id')
                ->leftJoin('users', 'players.user_id', '=', 'users.id')
                ->where('teams.club_id', $club->id)
                ->where('player_team.is_active', true)
                ->whereNull('players.deleted_at')
                ->whereNull('teams.deleted_at')
                ->first();

            $memberStats = DB::table('club_user')
                ->selectRaw('
                    COUNT(*) as total_members,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_members
                ')
                ->where('club_id', $club->id)
                ->first();

            // Game statistics
            $gameStats = $club->teams()
                ->join('games', function ($join) {
                    $join->on('teams.id', '=', 'games.home_team_id')
                        ->orOn('teams.id', '=', 'games.away_team_id');
                })
                ->where('games.status', 'finished')
                ->selectRaw('
                    COUNT(*) as total_games,
                    SUM(CASE
                        WHEN (teams.id = games.home_team_id AND games.home_team_score > games.away_team_score)
                          OR (teams.id = games.away_team_id AND games.away_team_score > games.home_team_score)
                        THEN 1 ELSE 0 END) as total_wins,
                    AVG(CASE
                        WHEN teams.id = games.home_team_id THEN games.home_team_score
                        ELSE games.away_team_score END) as avg_points_scored,
                    AVG(CASE
                        WHEN teams.id = games.home_team_id THEN games.away_team_score
                        ELSE games.home_team_score END) as avg_points_allowed
                ')->first();

            // Financial overview
            $financialStats = [
                'total_annual_revenue' => $club->teams->sum(function ($team) {
                    return ($team->registration_fee ?? 0) * $team->current_roster_size +
                        ($team->monthly_fee ?? 0) * 12 * $team->current_roster_size;
                }),
                'membership_fee_annual' => $club->membership_fee_annual,
                'membership_fee_monthly' => $club->membership_fee_monthly,
            ];

            // Recent activity
            $recentActivity = [
                'teams_created_this_month' => $club->teams()
                    ->whereBetween('created_at', [now()->startOfMonth(), now()])
                    ->count(),
                'players_joined_this_month' => Player::query()
                    ->join('player_team', 'player_team.player_id', '=', 'players.id')
                    ->join('teams', 'teams.id', '=', 'player_team.team_id')
                    ->where('teams.club_id', $club->id)
                    ->where('player_team.is_active', true)
                    ->whereBetween('player_team.joined_at', [now()->startOfMonth(), now()])
                    ->whereNull('players.deleted_at')
                    ->whereNull('teams.deleted_at')
                    ->count(),
                'games_this_month' => $club->teams()
                    ->join('games', function ($join) {
                        $join->on('teams.id', '=', 'games.home_team_id')
                            ->orOn('teams.id', '=', 'games.away_team_id');
                    })
                    ->whereBetween('games.scheduled_at', [now()->startOfMonth(), now()])
                    ->count(),
            ];

            return [
                'basic_stats' => [
                    'total_teams' => $teamStats->total_teams ?? 0,
                    'active_teams' => $teamStats->active_teams ?? 0,
                    'total_players' => $playerStats->total_players ?? 0,
                    'active_players' => $playerStats->active_players ?? 0,
                    'total_members' => $memberStats->total_members ?? 0,
                    'active_members' => $memberStats->active_members ?? 0,
                    'seasons_active' => $teamStats->seasons_active ?? 0,
                    'leagues_participated' => $teamStats->leagues_participated ?? 0,
                    'avg_player_age' => round($playerStats->avg_player_age ?? 0, 1),
                ],
                'game_stats' => [
                    'total_games' => $gameStats->total_games ?? 0,
                    'total_wins' => $gameStats->total_wins ?? 0,
                    'total_losses' => ($gameStats->total_games ?? 0) - ($gameStats->total_wins ?? 0),
                    'win_percentage' => $gameStats->total_games > 0 ? round(($gameStats->total_wins / $gameStats->total_games) * 100, 1) : 0,
                    'avg_points_scored' => round($gameStats->avg_points_scored ?? 0, 1),
                    'avg_points_allowed' => round($gameStats->avg_points_allowed ?? 0, 1),
                ],
                'financial_stats' => $financialStats,
                'recent_activity' => $recentActivity,
                'facilities' => [
                    'has_indoor_courts' => $club->has_indoor_courts ?? false,
                    'has_outdoor_courts' => $club->has_outdoor_courts ?? false,
                    'court_count' => $club->court_count ?? 1,
                    'equipment_available' => $club->equipment_available ?? null,
                ],
                'programs' => [
                    'offers_youth_programs' => $club->offers_youth_programs ?? true,
                    'offers_adult_programs' => $club->offers_adult_programs ?? true,
                    'accepts_new_members' => $club->accepts_new_members ?? true,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to load club statistics', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return basic fallback data structure
            return $this->getFallbackStatistics();
        }
    }

    /**
     * Verify a club.
     */
    public function verifyClub(Club $club): Club
    {
        $club->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        Log::info("Club verified", [
            'club_id' => $club->id,
            'club_name' => $club->name
        ]);

        return $club;
    }

    /**
     * Generate emergency QR code data for club.
     */
    public function generateEmergencyQRData(Club $club): array
    {
        return [
            'type' => 'club_emergency',
            'club_id' => $club->id,
            'club_name' => $club->name,
            'emergency_contact_name' => $club->emergency_contact_name,
            'emergency_contact_phone' => $club->emergency_contact_phone,
            'address' => $club->address,
            'city' => $club->city,
            'contact_person_name' => $club->contact_person_name,
            'contact_person_phone' => $club->contact_person_phone,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get club's available seasons.
     */
    public function getClubSeasons(Club $club): array
    {
        return $club->teams()
            ->select('season')
            ->distinct()
            ->orderBy('season', 'desc')
            ->pluck('season')
            ->toArray();
    }

    /**
     * Get detailed club report.
     */
    public function generateClubReport(Club $club): array
    {
        return [
            'club_info' => [
                'id' => $club->id,
                'name' => $club->name,
                'short_name' => $club->short_name,
                'slug' => $club->slug,
                'founded_at' => $club->founded_at?->format('Y-m-d'),
                'website' => $club->website,
                'email' => $club->email,
                'phone' => $club->phone,
                'is_active' => $club->is_active,
                'is_verified' => $club->is_verified,
                'verified_at' => $club->verified_at?->format('Y-m-d'),
            ],
            'location' => [
                'address' => $club->address,
                'city' => $club->city,
                'state' => $club->state,
                'postal_code' => $club->postal_code,
                'country' => $club->country,
            ],
            'leadership' => [
                'president_name' => $club->president_name,
                'president_email' => $club->president_email,
                'vice_president_name' => $club->vice_president_name,
                'secretary_name' => $club->secretary_name,
                'treasurer_name' => $club->treasurer_name,
                'contact_person_name' => $club->contact_person_name,
                'contact_person_phone' => $club->contact_person_phone,
                'contact_person_email' => $club->contact_person_email,
            ],
            'emergency_info' => [
                'emergency_contact_name' => $club->emergency_contact_name,
                'emergency_contact_phone' => $club->emergency_contact_phone,
            ],
            'teams' => $club->teams()->with(['headCoach:id,name', 'players'])->get()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'season' => $team->season,
                    'league' => $team->league,
                    'head_coach' => $team->headCoach?->name,
                    'player_count' => $team->players->count(),
                    'is_active' => $team->is_active,
                ];
            }),
            'statistics' => $this->getClubStatistics($club),
            'facilities' => [
                'has_indoor_courts' => $club->has_indoor_courts,
                'has_outdoor_courts' => $club->has_outdoor_courts,
                'court_count' => $club->court_count,
                'equipment_available' => $club->equipment_available,
                'facilities' => $club->facilities,
            ],
            'programs' => [
                'offers_youth_programs' => $club->offers_youth_programs,
                'offers_adult_programs' => $club->offers_adult_programs,
                'accepts_new_members' => $club->accepts_new_members,
                'training_times' => $club->training_times,
            ],
            'financial' => [
                'membership_fee_annual' => $club->membership_fee_annual,
                'membership_fee_monthly' => $club->membership_fee_monthly,
            ],
            'social_media' => [
                'facebook' => $club->social_media_facebook,
                'instagram' => $club->social_media_instagram,
                'twitter' => $club->social_media_twitter,
            ],
            'members' => $club->users()->withPivot('role', 'joined_at', 'is_active')->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                    'joined_at' => $user->pivot->joined_at?->format('Y-m-d'),
                    'is_active' => $user->pivot->is_active,
                ];
            }),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get fallback statistics when main query fails.
     */
    private function getFallbackStatistics(): array
    {
        return [
            'basic_stats' => [
                'total_teams' => 0,
                'active_teams' => 0,
                'total_players' => 0,
                'active_players' => 0,
                'total_members' => 0,
                'active_members' => 0,
                'seasons_active' => 0,
                'leagues_participated' => 0,
                'avg_player_age' => 0,
            ],
            'game_stats' => [
                'total_games' => 0,
                'total_wins' => 0,
                'total_losses' => 0,
                'win_percentage' => 0,
                'avg_points_scored' => 0,
                'avg_points_allowed' => 0,
            ],
            'financial_stats' => [
                'total_annual_revenue' => 0,
                'membership_fee_annual' => null,
                'membership_fee_monthly' => null,
            ],
            'recent_activity' => [
                'teams_created_this_month' => 0,
                'players_joined_this_month' => 0,
                'games_this_month' => 0,
            ],
            'facilities' => [
                'has_indoor_courts' => false,
                'has_outdoor_courts' => false,
                'court_count' => 1,
                'equipment_available' => null,
            ],
            'programs' => [
                'offers_youth_programs' => true,
                'offers_adult_programs' => true,
                'accepts_new_members' => true,
            ],
            'error' => 'Einige Statistiken konnten nicht geladen werden.'
        ];
    }
}
